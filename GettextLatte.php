<?php

namespace h4kuna;

use \Nette\Http\FileUpload,
    \Nette\Http\SessionSection;

require_once 'Gettext.php';

/**
 * @author Milan Matějček <milan.matejcek@gmail.com>
 */
class GettextLatte extends Gettext {

    /** @var SessionSection */
    private $section;

    /**
     *
     * @param \Nette\Http\SessionSection $section
     * @return type
     * @throws Nette\InvalidStateException
     */
    public function injectSection(SessionSection $section) {
        if ($this->section) {
            throw new \Nette\InvalidStateException('Settings has already been set');
        }
        $this->section = $section;

        if ($this->section->language === NULL) {
            $country = NULL;
            $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            if (preg_match('/[a-z]{2}-[A-Z]{2}/', $accept, $found)) {
                $country = str_replace('-', '_', $found[0]);
            }

            foreach ($this->langs as $k => $v) {
                if (preg_match('/' . $k . '/', $accept) ||
                        ($country && preg_match('/' . $country . '/', $v))) {
                    $this->language = $this->section->language = $k;
                    break;
                }
            }

            $this->language = $this->section->language = $this->default;
        }
    }

    public function setExpiration($expire) {
        $this->section->setExpiration($expire, 'language');
        return $this;
    }

    /**
     * makro pro podporu gettextu
     * @param \Nette\Latte\MacroNode $node
     * @param type $writer
     * @return type
     */
    public function macroGettext(\Nette\Latte\MacroNode $node, $writer) {
        $name = $node->name;
        $args = $node->args;
        $fce = NULL;

        $l = substr($args, 0, 1);
        if ($l == 'n') {
            $name = '_' . $l;
            $args = substr($args, 1);
        }

        $slice = $this->method($name != '_', $fce);
        $data = self::stringToArgs($args);
        $argsGettext = array_slice($data, 0, $slice);

        if (isset($argsGettext[2])) {
            if (preg_mach('/abs/i', $argsGettext[2])) {
                $argsGettext[2] = 'abs(' . $argsGettext[2] . ')';
            }
        }
        $out = $fce . '(' . implode(', ', $argsGettext) . ')';

        if ($this->useHelper) {
            $out = $this->prefix() . '$translator ? ' . $this->prefix() . $out . ' : ' . $out;
        }

        $diff = $this->foundReplce($data[0]);
        if ($diff) {
            $out = 'sprintf(' . $out . ', ' . implode(', ', array_slice($data, $diff)) . ')';
        }

        return $writer->write('echo %modify(' . $out . ')');
    }

    public function upload($lang, FileUpload $po, FileUpload $mo) {
        $mo->move($this->getFile($lang, '!mo'));
        $po->move($this->getFile($lang, 'po'));
    }

    /**
     * macro install
     * @param \Nette\Latte\Compiler $parser
     * @param self $gettext
     */
    static function install(\Nette\Latte\Compiler $parser, self $gettext) {
        $set = new \Nette\Latte\Macros\MacroSet($parser);
        $callback = callback($gettext, 'macroGettext');
        $set->addMacro('_', $callback);
    }

    /**
     * použitelná ukázka jak registrovat makro do Latte, podminkou je mít servisu translator a v ní instanci této třídy
     * @param GettextLatte
     * @return \Nette\Latte\Engine
     */
    static function latte(self $translator) {
        $service = new \Nette\Latte\Engine;
        self::install($service->compiler, $translator);
        return $service;
    }

    /**
     * @param type $s
     * @return type
     */
    static private function stringToArgs($s) {
        preg_match_all("/(?: ?)([^,]*\(.*?\)|[^,]*'[^']*'|[^,]*\"[^\"]*\"|.+?)(?: ?)(?:,|$)/", $s, $found);
        return $found[1];
    }

}