<?php

namespace h4kuna\GettextLatte\Macros;

use Nette;
use Nette\Latte\Compiler;
use Nette\Latte\MacroNode;
use Nette\Latte\PhpWriter;
use h4kuna\GettextLatte;
use Nette\Latte\Macros\MacroSet;

/**
 * @author Milan Matějček
 */
class Latte extends MacroSet {

    /** @var GettextLatte */
    private $translator;

    public function setTranslator(GettextLatte $translator) {
        $this->translator = $translator;
        return $this;
    }

    /**
     * @param Compiler $compiler
     * @return ImgMacro|MacroSet
     */
    public static function install(Compiler $compiler, GettextLatte $translator = NULL) {
        $me = new static($compiler);
        $me->addMacro('_', callback($me, 'macroGettext'));
        $me->setTranslator($translator);
        return $me;
    }

    /**
     * macro for support gettext
     * @param Nette\Latte\MacroNode $node
     * @param Nette\Latte\PhpWriter $writer
     * @return string
     * @throws Nette\Latte\CompileException
     */
    public function macroGettext(MacroNode $node, PhpWriter $writer) {
        $args = $node->args;
        $isPlural = 'n' == substr($args, 0, 1);
        if ($isPlural) {
            $args = substr($args, 1);
        }
        $data = self::stringToArgs($args);

        $fce = NULL;
        $slice = $this->method($isPlural, $fce);
        $argsGettext = array_slice($data, 0, $slice);

        if ($isPlural) {
            $this->pluralData($argsGettext);

            // set another variable as plural
            foreach ($data as $param) {
                if (preg_match('/plural/i', $param)) {
                    $argsGettext[2] = $param;
                    break;
                }
            }

            // absolute value
            if (preg_match('/abs/i', $argsGettext[2])) {
                $argsGettext[2] = 'abs(' . $argsGettext[2] . ')';
            }
        }



        // accept macros
        foreach ($this->translator->getMacros() as $macro) {
            foreach ($argsGettext as $k => $arg) {
                if ($isPlural && $k == 2) {
                    continue;
                }
                $argsGettext[$k] = $macro->invokeArgs(array($arg));
            }
        }

        $out = $fce . '(' . implode(', ', $argsGettext) . ')';

        // gettext extension is off
        if ($this->translator->useHelper()) {
            $out = $this->translator->prefix() . '$translator ? ' . $this->translator->prefix() . $out . ' : ' . $out;
        }

        // use sprintf?
        $diff = $this->foundReplace($data[0]);
        if ($diff) {
            $argsSprintf = array_slice($data, $diff);
            // escape non gettext params
            if (!$this->translator->getEscape()) {
                foreach ($argsSprintf as &$v) {
                    $v = "%escape($v)";
                }
                unset($v);
            }

            $out = 'sprintf(' . $out . ', ' . implode(', ', $argsSprintf) . ')';
        }

        $out = 'echo %modify(' . $out . ')';

        if ($this->translator->getHelpers() || !$this->translator->getEscape()) {
            $node->modifiers .= $this->translator->getHelpers();
            if (!$this->translator->getEscape()) {
                $node->modifiers = str_replace('|escape', '', $node->modifiers);
            }
            return \Nette\Latte\PhpWriter::using($node, $this->compiler)->write($out);
        }

        return $writer->write($out);
    }

    /**
     * @param string $s
     * @return string
     */
    static private function stringToArgs($s) {
        preg_match_all("/(?: ?)([^,]*\(.*?\)|[^,]*'[^']*'|[^,]*\"[^\"]*\"|.+?)(?: ?)(?:,|$)/", $s, $found);
        return $found[1];
    }

    /**
     * logic gettext or ngettext
     * @param type $isNgettext
     * @param type $fce
     * @return int
     */
    protected function method($isPlural, &$fce) {
        $slice = 1;
        if ($isPlural) {
            $fce .= 'n';
            $slice = 3;
        }

        $fce .= 'gettext';
        if ($this->translator->isOneParam() && $slice == 3) {
            $slice = 2;
        }
        return $slice;
    }

    /**
     * has term for replace
     * @param string $str
     * @return int
     */
    private function foundReplace($str) {
        return -1 * substr_count($str, '%s');
    }

    /**
     * prepare data to native function, only for plural
     * @param array $data
     */
    private function pluralData(array &$data) {
        if ($this->translator->isOneParam()) {
            array_unshift($data, $data[0]);
        }
    }

}
