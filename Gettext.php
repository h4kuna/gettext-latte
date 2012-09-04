<?php

namespace Utility;

use Nette\DI\Container;

/**
 * @author Milan Matějček <milan.matejcek@gmail.com>
 */
class Gettext extends TranslatorFake {

    /** @var GettextNatural */
    private static $translator;
    private $useHelper;
    private $langs;
    private $path;
    private $messages;

    /**
     *
     * @param type $path
     * @param type $langs
     * @param boolean $useHelper not supported on windows
     * @param type $msg
     */
    public function __construct($path, $langs, $useHelper = FALSE, $msg = 'messages') {
        $this->useHelper = $useHelper;
        $this->langs = $langs;
        $this->path = $path;
        $this->messages = $msg;
    }

    public function setLanguage($lang) {
        $l = $this->langs[$lang];
        $set = setlocale(\LC_ALL, $l);
        if (strstr(strtolower(php_uname('u')), 'windows') !== FALSE) {
            putenv('LANG=' . $lang);//only for windows
            $set = FALSE;
        }
        if (!$set || $this->useHelper) {
            $file = $this->path . $lang . '/' . 'LC_MESSAGES/' . $this->messages . '.mo';
            setlocale(\LC_ALL, '');
            self::$translator = new GettextNatural($file, $lang);
        } elseif (!$set) {
            throw new \RuntimeException($l . ' locale is not supported on your machine. Set useHelper on TRUE.');
        } else {
            bindtextdomain($this->messages, $this->path);
            bind_textdomain_codeset($this->messages, 'UTF-8');
            textdomain($this->messages);
        }
    }

    /**
     * instalace maktra
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
     * @param \Nette\DI\Container $context
     * @return \Nette\Latte\Engine
     */
    static function latte(Container $context) {
        $service = new \Nette\Latte\Engine;
        self::install($service->compiler, $context->translator);
        return $service;
    }

    // metody pri použití berličky když nejsou na stoji nainstalované lokalizace
    static function gettext($message) {
        return (self::$translator) ? self::$translator->gettext($message) : $message;
    }

    static function ngettext($msgid1, $msgid2, $n) {
        return (self::$translator) ? self::$translator->ngettext($msgid1, $msgid2, $n) : $msgid1;
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
        $l = substr($args, 0, 1);
        if ($l == 'n' || $l == 'c') {
            $name = '_' . $l;
            $args = substr($args, 1);
        }

        $data = self::stringToArgs($args);
        if ($name == '_') {
            $fce = 'gettext';
            $args = array_slice($data, 0, 1);
            $sprint = array_slice($data, 1);
        } else {
            $fce = 'ngettext';
            $args = array_slice($data, 0, 3);
            $sprint = array_slice($data, ($l == 'n') ? 2 : 3);
            if ($this->useHelper && self::$translator) {
                $original = $args[2];
                $args[2] = 1;
            }
        }

        $out = $fce . '(' . implode(', ', $args) . ')';

        if ($this->useHelper) {
            $out = '\\' . __CLASS__ . '::' . $fce . '(' . $out;

            if ($name != '_') {
                if (self::$translator) {
                    $args[2] = $original;
                }
                $out .= ', ' . implode(', ', array_slice($args, 1, 2));
            }

            $out .= ')';
        }

        if ($sprint) {
            $out = 'sprintf(' . $out . ', ' . implode(', ', $sprint) . ')';
        }

        return $writer->write('echo %modify(' . $out . ')');
    }

    /**
     * @todo přepsat na regulár
     * @param type $s
     * @param type $s
     * @return type
     */
    static private function stringToArgs($s) {
        $len = strlen($s);
        $out = array();
        $outI = 0;

        $in = FALSE;
        $slash = NULL;
        for ($i = 0; $i < $len; ++$i) {

            if ($i + 1 < $len && $s{$i} . $s{$i + 1} == '\\' . $slash) {
                $out[$outI] .= $s{$i} . $s{$i + 1};
                ++$i;
                continue;
            }
            if (!isset($out[$outI])) {
                $out[$outI] = '';
            }
            $out[$outI] .= $s{$i};

            if ($s{$i} == "'" || $s{$i} == '"') {
                $in = !$in;
                $slash = $in ? $s{$i} : NULL;
            }

            if (!$in && $s{$i} == ',') {
                ++$outI;
            }
        }

        array_walk($out, function(&$s) {
                    $s = trim($s, "\t \n\r\0\x0B,");
                });
        return $out;
    }

    public function translate($message, $count = null) {
        if (self::$translator) {
            return self::$translator->translate($message, $count);
        }
        return parent::translate($message, $count);
    }

}