<?php

namespace h4kuna;

use \Nette\Http\FileUpload,
    \Nette\Http\SessionSection,
    \Nette\Localization\ITranslator;

require_once 'Gettext.php';

/**
 * @author Milan Matějček <milan.matejcek@gmail.com>
 */
class GettextLatte extends Gettext implements ITranslator {

    /** @var SessionSection */
    private $section;

    /** @var bool */
    private $oneParam;

    public function __construct($path, array $langs, $oneParamPlural = TRUE, $msg = 'messages', $useHelper = FALSE) {
        $this->oneParam = $oneParamPlural;
        parent::__construct($path, $langs, $msg, $useHelper);
    }

    /**
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
            $this->section->language = $lang = $this->detectLanguage();
            $this->setLanguage($lang);
        }
    }

    public function getLanguage() {
        $lang = parent::getLanguage();
        return ($lang === NULL) ? $this->section->language : $lang;
    }

    /**
     * set session
     * @param type $lang
     * @return \h4kuna\GettextLatte
     */
    public function setLanguage($lang) {
        parent::setLanguage($lang);
        $this->section->language = $this->language;
        return $this;
    }

    /**
     * session expiration
     * @param string|int $expire
     * @return \h4kuna\GettextLatte
     */
    public function setExpiration($expire) {
        $this->section->setExpiration($expire, 'language');
        return $this;
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

            foreach ($data as $param) {
                if (preg_match('/plural/i', $param)) {
                    $argsGettext[2] = $param;
                    break;
                }
            }

            if (preg_match('/abs/i', $argsGettext[2])) {
                $argsGettext[2] = 'abs(' . $argsGettext[2] . ')';
            }
        }

        $out = $fce . '(' . implode(', ', $argsGettext) . ')';
        if ($this->useHelper) {
            $out = $this->prefix() . '$translator ? ' . $this->prefix() . $out . ' : ' . $out;
        }

        $diff = $this->foundReplace($data[0]);
        if ($diff) {
            $out = 'sprintf(' . $out . ', ' . implode(', ', array_slice($data, $diff)) . ')';
        }

        return $writer->write('echo %modify(' . $out . ')');
    }

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
     * has term for replace
     * @param string $str
     * @return int
     */
    private function foundReplace($str) {
        return -1 * substr_count($str, '%s');
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