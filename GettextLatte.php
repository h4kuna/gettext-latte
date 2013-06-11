<?php

namespace h4kuna;

use \Nette\Http\FileUpload,
    \Nette\Http\SessionSection,
    \Nette\Localization\ITranslator;

/**
 * @author Milan Matějček <milan.matejcek@gmail.com>
 */
class GettextLatte extends Gettext implements ITranslator {

    /** @var SessionSection */
    private $section;

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

//----------------- setup for latte macro --------------------------------------
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
     * set default mode for ngettext in latte
     * @return \h4kuna\GettextLatte
     */
    public function oneParamOff() {
        $this->oneParam = FALSE;
        return $this;
    }

//----------------- api for latte macro ----------------------------------------
    public function getMacros() {
        return $this->macros;
    }

    public function isOneParam() {
        return $this->oneParam;
    }

    public function useHelper() {
        return $this->useHelper;
    }

    public function getHelpers() {
        return $this->helpers;
    }

    public function getEscape() {
        return $this->escape;
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

}