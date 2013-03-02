<?php

namespace h4kuna;

use \Nette\Http\FileUpload,
    \Nette\Http\SessionSection,
    \Nette\Localization\ITranslator,
    \Nette\Latte\Compiler,
    \Nette\Latte\Engine;

require_once 'Gettext.php';

/**
 * @author Milan Matějček <milan.matejcek@gmail.com>
 */
class GettextLatte extends Gettext implements ITranslator {

    /** @var SessionSection */
    private $section;

    /** @var \Nette\Latte\Compiler */
    private $compiler;

    /** @var string */
    private $helpers;

    /** @var bool */
    private $escape = TRUE;

    /**
     * callback for prepare text before render
     * @var type
     */
    private $macros = array();

    /**
     * set how write plural in latte
     * TRUE {_n'dog', $number}
     * FALSE {_n'dog', 'dogs', $number}
     * @var bool
     */
    private $oneParam = TRUE;

    /**
     * optional
     * @param \Nette\Http\SessionSection $section
     * @return type
     */
    public function setSection(SessionSection $section) {
        $this->section = $section;
        if ($this->section->language === NULL) {
            $this->setLanguage($this->detectLanguage());
        }
        return $this;
    }

    /** @param \Nette\Latte\Compiler $compiler */
    protected function setCompiler(Compiler $compiler) {
        $this->compiler = $compiler;
        $set = new \Nette\Latte\Macros\MacroSet($compiler);
        $set->addMacro('_', callback($this, 'macroGettext'));
        return $this;
    }

    /** @return string */
    public function getLanguage() {
        $lang = parent::getLanguage();
        return $this->section($lang);
    }

    /**
     * set session
     * @param type $lang
     * @return \h4kuna\GettextLatte
     */
    public function setLanguage($lang) {
        parent::setLanguage($lang);
        $this->section($this->language);
        return $this;
    }

//----------------- setup for latte
    /**
     * @return GettextLatte
     */
    public function addHelper($h) {
        $this->helpers .= '|' . $h;
        return $this;
    }

    public function addMacro(\Nette\Callback $cb) {
        $this->macros[] = $cb;
        return $this;
    }

    public function setEscape($bool) {
        $this->escape = (bool) $bool;
    }

    /**
     * czech orphans
     * @return type
     */
    public function enableOrphans($escape = FALSE) {
        $this->addMacro(new \Nette\Callback(__CLASS__, 'orphans'));
        $this->setEscape($escape);
    }

    /**
     * macro for support gettext
     * @param \Nette\Latte\MacroNode $node
     * @param type $writer
     * @return type
     */
    public function macroGettext(\Nette\Latte\MacroNode $node, $writer) {
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
        foreach ($this->macros as $macro) {
            foreach ($argsGettext as $k => $arg) {
                if ($isPlural && $k == 2) {
                    continue;
                }
                $argsGettext[$k] = $macro->invokeArgs(array($arg));
            }
        }

        $out = $fce . '(' . implode(', ', $argsGettext) . ')';

        // gettext extension is off
        if ($this->useHelper) {
            $out = $this->prefix() . '$translator ? ' . $this->prefix() . $out . ' : ' . $out;
        }

        // use sprintf?
        $diff = $this->foundReplace($data[0]);
        if ($diff) {
            $argsSprintf = array_slice($data, $diff);
            // escape non gettext params
            if (!$this->escape) {
                foreach ($argsSprintf as &$v) {
                    $v = "%escape($v)";
                }
                unset($v);
            }

            $out = 'sprintf(' . $out . ', ' . implode(', ', $argsSprintf) . ')';
        }

        $out = 'echo %modify(' . $out . ')';

        if ($this->helpers || !$this->escape) {
            $node->modifiers .= $this->helpers;
            if (!$this->escape) {
                $node->modifiers = str_replace('|escape', '', $node->modifiers);
            }
            return \Nette\Latte\PhpWriter::using($node, $this->compiler)->write($out);
        }

        return $writer->write($out);
    }

    /**
     * set default mode for ngettext in latte
     * @return \h4kuna\GettextLatte
     */
    public function oneParamOff() {
        $this->oneParam = FALSE;
        return $this;
    }

//------------------------------------------------------------------------------

    /**
     *
     * @param type $message
     * @param type $count
     * @return type
     */
    public function translate($message, $count = NULL) {
        $data = func_get_args();

        if (!self::$translator) {
            return call_user_func_array('parent::t', $data);
        }

        $key = $this->oneParam ? 1 : 2;
        $numArgs = func_num_args();
        $isPlural = FALSE;

        if ($numArgs > $key && is_numeric($data[$key])) {
            $isPlural = TRUE;
            $this->pluralData($data);
        }

        $fce = $this->prefix();
        $slice = $this->method($isPlural, $fce);
        $t = call_user_func_array($fce, array_slice($data, 0, $slice));

        $diff = $this->foundReplace($data[0]);
        if ($diff) {
            return vsprintf($t, array_slice($data, $diff));
        }
        return $t;
    }

    /**
     * router defined languages
     * @return type
     */
    public function routerAccept() {
        return implode('|', array_keys($this->langs));
    }

    /**
     * install this latte macro
     * @param self $translator
     * @param \Nette\Latte\Engine $service
     * @return \Nette\Latte\Engine
     */
    static function latte(self $translator, Engine $service = NULL) {
        if (!$service) {
            $service = new \Nette\Latte\Engine;
        }

        $translator->setCompiler($service->compiler);
        return $service;
    }

    /**
     * czech orphans
     * @param type $s
     * @return type
     */
    static function orphans($s) {
        return preg_replace('/( +(a|č\.|do|i|k|ke|na|o|od|po|s|tj\.|u|v|z|za) +)/i', ' $2&nbsp;', $s);
    }

    /**
     * save uploaded files
     * @param string $lang
     * @param \Nette\Http\FileUpload $po
     * @param \Nette\Http\FileUpload $mo
     */
    public function upload($lang, FileUpload $po, FileUpload $mo) {
        $mo->move($this->getFile($lang, '!mo'));
        $po->move($this->getFile($lang, 'po'));
    }

    /**
     * @see parent
     */
    protected function method($isPlural, &$fce) {
        $slice = parent::method($isPlural, $fce);
        if ($this->oneParam && $slice == 3) {
            $slice = 2;
        }
        return $slice;
    }

    /**
     * set and return language
     * @param type $val
     * @return null|string
     */
    private function section($val = NULL) {
        if ($this->section) {
            if (!$val) {
                return $this->section->language;
            }
            $this->section->language = $val;
        }
        return $val;
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
        if ($this->oneParam) {
            array_unshift($data, $data[0]);
        }
    }

    /**
     * @param string $s
     * @return string
     */
    static private function stringToArgs($s) {
        preg_match_all("/(?: ?)([^,]*\(.*?\)|[^,]*'[^']*'|[^,]*\"[^\"]*\"|.+?)(?: ?)(?:,|$)/", $s, $found);
        return $found[1];
    }

}