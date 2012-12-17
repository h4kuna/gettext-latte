<?php

namespace h4kuna;

use Nette\DI\Container;

require_once 'TranslatorFake.php';

/**
 * @author Milan Matějček <milan.matejcek@gmail.com>
 */
class GettextLatte extends TranslatorFake {

    const PHP_DIR = '/LC_MESSAGES/';

    /** @var GettextNatural */
    public static $translator;
    private $useHelper;
    private $langs;
    private $path;

    /**
     * property for temporary resolve bug
     * @var type
     */
    private $messages;
    private $msg;

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
        $this->msg = $this->messages = $msg;
    }

    public function setLanguage($lang) {
        $l = $this->langs[$lang];
        $const = defined('\LC_MESSAGES') ? \LC_MESSAGES : \LC_ALL;
        $set = setlocale($const, $l);
        if (!$set && strstr(strtolower(php_uname('u')), 'windows') !== FALSE) {
            putenv('LANG=' . $lang);
            $set = TRUE;
        }

        $bindText = function_exists('bindtextdomain');
        if (!$set && $this->useHelper || !$bindText) {
            require_once 'fce.php';
            setlocale($const, '');
            $file = $this->getFile($lang);
            self::$translator = new GettextNatural($file, $lang);
        } elseif (!$set) {
            throw new \RuntimeException($l . ' locale is not supported on your machine. Set useHelper on TRUE.');
        } else {
            $this->checkFile($lang);
            bindtextdomain($this->messages, $this->path);
            bind_textdomain_codeset($this->messages, 'UTF-8');
            textdomain($this->messages);
        }
    }

    public function download($lang) {
        $file = $this->getFile($lang, 'po');
        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename=' . $lang . '-' . basename($file));
            header('Content-Length: ' . filesize($file));
            flush();
            readfile($file);
            exit;
        }
        new \Nette\FileNotFoundException($file);
    }

    public function upload($lang, FileUpload $po, FileUpload $mo) {
        $mo->move($this->getFile($lang, '!mo'));
        $po->move($this->getFile($lang, 'po'));
    }

    /**
     * bug http://www.php.net/manual/en/function.gettext.php#58310
     * @param type $lang
     * @return type
     */
    private function checkFile($lang) {
        $po = $this->getFile($lang, 'po');
        $mtime = @filemtime($po);
        if (!$mtime) {
            return;
        }
        $this->messages = $mtime . $this->messages;
        $mo = $this->getFile($lang);
        if (!file_exists($mo)) {
            @copy($this->getFile($lang, '!mo'), $mo);
        }
    }

    /**
     * filesystem path for catalog
     * @param type $lang
     * @param type $extension
     * @return type
     */
    private function getFile($lang, $extension = 'mo') {
        if ($extension == 'po') {
            $msg = $this->msg;
        } elseif ($extension == '!mo') {
            $msg = $this->msg;
            $extension = 'mo';
        } else {
            $msg = $this->messages;
        }
        return $this->path . $lang . self::PHP_DIR . $msg . '.' . $extension;
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
    public static function gettext($message) {
        return self::$translator->gettext($message);
    }

    public static function ngettext($msgid1, $msgid2, $n) {
        return self::$translator->ngettext($msgid1, $msgid2, $n);
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
        if ($l == 'n') {
            $name = '_' . $l;
            $args = substr($args, 1);
        }
        $prefix = $this->prefix();
        $fce = NULL;
        $slice = $this->method($name != '_', $fce);

        $data = self::stringToArgs($args);
        $argsGettext = array_slice($data, 0, $slice);
        if (isset($argsGettext[2])) {
            if (stristr($argsGettext[2], 'abs') !== FALSE) {
                $argsGettext[2] = 'abs(' . $argsGettext[2] . ')';
            }
        }
        $out = $fce . '(' . implode(', ', $argsGettext) . ')';

        if ($this->useHelper) {
            $out = $prefix . '$translator ? ' . $prefix . $out . ' : ' . $out;
        }

        $diff = $this->foundReplce($data[0]);
        if ($diff) {
            $out = 'sprintf(' . $out . ', ' . implode(', ', array_slice($data, $diff)) . ')';
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

    private function method($isNgettext, &$fce) {
        $slice = 1;
        if ($isNgettext) {
            $fce .= 'n';
            $slice = 3;
        }

        $fce .= 'gettext';
        return $slice;
    }

    private function foundReplce($str) {
        return -1 * substr_count($str, '%s');
    }

    private function prefix() {
        return '\\' . __CLASS__ . '::';
    }

    public function translate($message, $count = null) {
        if (!self::$translator) {
            return call_user_func_array('parent::t', func_get_args());
        }

        $fce = $this->prefix();
        $slice = $this->method(func_num_args() > 2, $fce);
        $data = func_get_args();
        $t = call_user_func_array($fce, array_slice($data, 0, $slice));
        $diff = $this->foundReplce($data[0]);

        if ($diff) {
            return vsprintf($t, array_slice($data, $diff));
        }
        return $t;
    }

}