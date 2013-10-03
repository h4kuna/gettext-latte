<?php

namespace h4kuna;

use Nette\Http\FileUpload;
use Nette\Http\SessionSection;
use Nette\Localization\ITranslator;

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
     * Callback for prepare text before render
     *
     * @var type
     */
    private $macros = array();

    /**
     * Set how write plural in latte
     *
     * TRUE {_n'dog', $number}
     * FALSE {_n'dog', 'dogs', $number}
     * @var bool
     */
    private $oneParam = TRUE;

    /**
     * Identificato if language has been changed
     *
     * @var bool
     */
    protected $langChange = FALSE;

    /**
     * Optional, if you set upt than enable automatic language dection and langChange
     *
     * @param SessionSection $section
     * @return type
     */
    public function setSection(SessionSection $section) {
        $this->section = $section;
        if ($this->section->language === NULL) {
            $this->setLanguage($this->detectLanguage());
        }
        return $this;
    }

    /**
     * Actual using language
     *
     * @return string
     */
    public function getLanguage() {
        $lang = parent::getLanguage();
        return $this->section($lang);
    }

    /**
     * Set session
     *
     * @param string $lang
     * @return GettextLatte
     */
    public function setLanguage($lang) {
        parent::setLanguage($lang);
        $this->section($this->language);
        return $this;
    }

//----------------- setup for latte macro --------------------------------------
    /**
     * Add helers to template
     *
     * @return GettextLatte
     */
    public function addHelper($h) {
        $this->helpers .= '|' . $h;
        return $this;
    }

    /**
     * Add macro to template
     *
     * @param \Nette\Callback $cb
     * @return GettextLatte
     */
    public function addMacro(\Nette\Callback $cb) {
        $this->macros[] = $cb;
        return $this;
    }

    /**
     * Need escape?
     *
     * @param type $bool
     */
    public function setEscape($bool) {
        $this->escape = (bool) $bool;
    }

    /**
     * Czech orphans
     *
     * @return void
     */
    public function enableOrphans($escape = FALSE) {
        $this->addMacro(new \Nette\Callback(__CLASS__, 'orphans'));
        $this->setEscape($escape);
    }

    /**
     * Set default mode for ngettext in latte
     *
     * @return GettextLatte
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

    public function isChanged() {
        return $this->langChange;
    }

//------------------------------------------------------------------------------

    /**
     *
     * @deprecated
     * @param string $message
     * @param mixed $count
     * @return string
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
     * Router defined languages
     *
     * @return string
     */
    public function routerAccept() {
        return implode('|', array_keys($this->langs));
    }

    /**
     * Czech orphans
     *
     * @param string $s
     * @return string
     */
    static function orphans($s) {
        return preg_replace('/( +(a|č\.|do|i|k|ke|na|o|od|po|s|tj\.|u|v|z|za) +)/i', ' $2&nbsp;', $s);
    }

    /**
     * Save uploaded files
     *
     * @param string $lang
     * @param FileUpload $po
     * @param FileUpload $mo
     */
    public function upload($lang, FileUpload $po, FileUpload $mo) {
        $mo->move($this->getFile($lang, '!mo'));
        $po->move($this->getFile($lang, 'po'));
    }

    /**
     * Set and return language
     *
     * @param string $val
     * @return NULL|string
     */
    private function section($val = NULL) {
        if ($this->section) {
            if (!$val) {
                return $this->section->language;
            }
            if (!$this->langChange && $this->section->language != $val) {
                $this->langChange = TRUE;
            }
            $this->section->language = $val;
        }
        return $val;
    }

}
