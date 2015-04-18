<?php

namespace h4kuna\Gettext;

use Iterator;
use Nette\Http;
use Nette\Object;
use RuntimeException;
use Nette\Localization\ITranslator;

/**
 * @author Milan Matějček
 */
class GettextSetup extends Object implements Iterator, ITranslator {

    /** @var string */
    private $default;

    /** @var string */
    private $language;

    /** @var string */
    private $languagePrev;

    /** @var string[] */
    private $languages;

    /** @var Os */
    private $os;

    /** @var Dictionary */
    private $dictionary;

    /** @var Http\SessionSection */
    private $section;

    /** @var Http\Request */
    private $request;

    /**
     * Event if language set.
     * For example use change currency.
     *
     * @var array
     */
    public $onSetLanguage;

    /**
     * Event if language change.
     * For example use flashmessage.
     *
     * @var array
     */
    public $onChangeLanguage;

    /**
     *
     * @param array $languages
     * @param Dictionary $dictionary
     * @throws GettextException
     */
    public function __construct(array $languages, Dictionary $dictionary, Os $os, Http\Request $request) {
        if (!function_exists('bindtextdomain')) {
            throw new GettextException('You have not instaled gettext extension.');
        } elseif (PHP_SAPI === 'cli') {
            putenv("LANGUAGE=");
        }
        $this->dictionary = $dictionary;
        $this->os = $os;
        $this->request = $request;
        $this->setLanguages($languages);
    }

    /**
     * Try find user language.
     *
     * @return string
     */
    public function detectLanguage() {
        if ($this->default) {
            return $this->default;
        }
        $lang = $this->request->detectLanguage(array_keys($this->languages));
        if ($lang) {
            return $lang;
        }
        return $this->getDefault();
    }

    /**
     * If you need change language in iterator
     *
     * @param sring $lang
     * @return self
     */
    public function changeHomeLang($lang) {
        if ($this->languagePrev === NULL) {
            $this->languagePrev = $this->getLanguage();
        }
        return $this->setLanguage($lang);
    }

    /**
     * If iterator is end call this method
     */
    public function revertHomeLang() {
        if ($this->languagePrev) {
            $this->setLanguage($this->languagePrev);
            $this->languagePrev = NULL;
        }
    }

    /**
     * Default language
     *
     * @return string
     */
    public function getDefault() {
        return $this->default;
    }

    /**
     * Actived language
     *
     * @return string
     */
    public function getLanguage() {
        if ($this->language === NULL) {
            $this->language = $this->getDefault();
        }
        return $this->language;
    }

    /** @return array */
    public function getLanguages() {
        return $this->languages;
    }

    /**
     * Load language dictionary
     *
     * @param string $domain
     */
    public function loadDomain($domain) {
        $this->dictionary->loadDomain($domain);
    }

    /**
     *
     * @see Dictionary::download
     * @param string $language
     */
    public function download($language) {
        $this->checkLanguage($language);
        $this->dictionary->download($language);
    }

    /**
     * @see Dictionary::upload
     * @param string $language
     * @param FileUpload $po
     * @param FileUpload $mo
     */
    public function upload($language, FileUpload $po, FileUpload $mo) {
        $this->checkLanguage($language);
        $this->dictionary->upload($language, $po, $mo);
    }

    /**
     * Is active default language?
     *
     * @return bool
     */
    public function isDefault() {
        return $this->getLanguage() === $this->getDefault();
    }

    /**
     * Load all possible language dictionary
     *
     * @param string $default
     */
    public function loadAllDomains($default) {
        $this->dictionary->loadAllDomains($default);
    }

    /**
     * @param string $lang
     * @return string
     * @throws RuntimeException
     */
    public function setLanguage($lang) {
        $lang = $lang ? strtolower($lang) : $this->getDefault();

        if ($this->language == $lang) {
            return $this->language;
        }

        $this->checkLanguage($lang);
        $this->language = $lang;
        $this->loadDictionary();
        $this->onSetLanguage($lang);

        if ($this->section && $this->section->language != $lang) {
            $this->section->language = $lang;
            $this->onChangeLanguage($lang);
        }
        return $this->language;
    }

    /**
     * Optional, if you set Session than enable automatic language dection.
     *
     * @param Http\Session $session
     * @return self
     */
    public function setSession(Http\Session $session, $live = '+1 week') {
        $this->section = $session->getSection(__CLASS__);
        if (!isset($this->section->language)) {
            $this->setLanguage($this->detectLanguage());
            $this->section->setExpiration($live);
        }
        return $this;
    }

    /**
     *
     * @param string $language
     * @throws GettextException
     */
    final protected function checkLanguage($language) {
        if (!isset($this->languages[$language])) {
            throw new GettextException('Language is not defined: ' . $language);
        }
    }

    /**
     * Load language dictionary
     *
     * @throws GettextException
     */
    private function loadDictionary() {
        $constLC = defined('LC_MESSAGES') ? LC_MESSAGES : LC_ALL;
        if ($this->os->isWindows()) {
            // @todo win7+ not work
            putenv('LANG=' . $this->language);
            putenv('LC_ALL=' . $this->language);
            setlocale($constLC, $this->language);
            $set = TRUE;
        } else {
            $set = setlocale($constLC, $this->languages[$this->language]);
        }

        if (!$set) {
            throw new GettextException('Probaly you have not instaled locale support on your machine. Let\'s try command: $ locale -a, or ' . __CLASS__ . '::showAvailableLanguages()');
        }
    }

    /**
     * Router defined languages.
     *
     * @return string
     */
    public function routerAccept() {
        return implode('|', array_keys($this->languages));
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

    /**
     * List of avaible languages
     *
     * @param array $langs
     * @return self
     */
    private function setLanguages(array $langs) {
        if (!$langs) {
            throw new GettextException('Define list of languages. Forexample array(\'en\' => \'en_US.utf8\').');
        }

        foreach ($langs as $lang => $encoding) {
            $lang = strtolower($lang);
            if ($this->default === NULL) {
                $this->default = $lang;
            }
            $this->languages[$lang] = $this->os->isMac() ? str_replace('utf8', 'UTF-8', $encoding) : $encoding;
        }
        return $this;
    }

    /**
     * Show you posibble languages
     *
     * @return array
     */
    static function showAvailableLanguages() {
        $return = NULL;
        exec('locale -a', $return);
        $out = array();
        foreach ($return as $line) {
            if (preg_match('/\w{2,}\.utf-?8/i', $line)) {
                $out[] = $line;
            }
        }
        return $out;
    }

    /**
     * IMPLEMENTS ITERATOR *****************************************************
     * *************************************************************************
     */

    /**
     * Is language active?
     *
     * @return bool
     */
    public function current() {
        return $this->key() === $this->getLanguage();
    }

    public function key() {
        return key($this->languages);
    }

    public function next() {
        next($this->languages);
    }

    public function rewind() {
        return reset($this->languages);
    }

    public function valid() {
        return isset($this->languages[$this->key()]);
    }

}
