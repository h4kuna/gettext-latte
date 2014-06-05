<?php

namespace h4kuna;

use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Localization\ITranslator;

/**
 * @author Milan Matějček <milan.matejcek@gmail.com>
 */
class GettextNette extends GettextSetup implements ITranslator {

    /** @var SessionSection */
    private $section;

    /**
     * Event if language change.
     * For example use flashmessage.
     *
     * @var array
     */
    public $onChangeLanguage;

    
    /**
     * Optional, if you set Session than enable automatic language dection.
     *
     * @param Session $session
     * @return self
     */
    public function setSession(Session $session, $live = '+7 days') {
        $this->section = $session->getSection(__CLASS__);
        if (!isset($this->section->language)) {
            $this->setLanguage($this->detectLanguage());
            $this->section->setExpiration($live);
        }
        return $this;
    }

    /**
     * Set session
     *
     * @param string $lang
     * @return GettextLatte
     */
    public function setLanguage($lang) {
        parent::setLanguage($lang);
        if ($this->section && $this->section->language != $this->getLanguage()) {
            $this->section->language = $this->getLanguage();
            $this->onChangeLanguage($this->getLanguage());
        }
        return $this;
    }

    /**
     * Router defined languages.
     *
     * @return string
     */
    public function routerAccept() {
        return implode('|', array_keys($this->getLanguages()));
    }

    /**
     *
     * @param string $message
     * @param mixed $count
     * @return string
     */
    public function translate($message, $count = NULL) {
        return call_user_func_array('sprintf', func_get_args());
    }

}
