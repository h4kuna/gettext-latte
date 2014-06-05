<?php

namespace h4kuna;

use h4kuna\Gettext\Dictionary;
use h4kuna\Gettext\Os;
use Locale;
use Nette\Http\FileUpload;
use Nette\Object;
use RuntimeException;

/**
 * Description of Gettext
 *
 * @author Milan Matějček
 */
class GettextSetup extends Object {

    /** @var string */
    private $default;

    /** @var string */
    private $language;

    /** @var string */
    private $languagePrev;

    /** @var array */
    private $languages;

    /** @var Os */
    private $os;

    /** @var Dictionary */
    private $dictionary;

    /**
     * 
     * @param array $languages
     * @param Dictionary $dictionary
     * @throws GettextException
     */
    public function __construct(array $languages, Dictionary $dictionary, Os $os) {
        if (!function_exists('bindtextdomain')) {
            throw new GettextException('You have not instaled gettext extension.');
        }
        $this->dictionary = $dictionary;
        $this->os = $os;
        $this->setLanguages($languages);
    }

    /**
     * Try find user language.
     * 
     * @return string
     */
    public function detectLanguage() {
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return $this->getDefault();
        }

        $header = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

        if (ini_get_all('intl', FALSE)) {
            $country = Locale::acceptFromHttp($header);
        } else {
            $found = $country = NULL;
            if (preg_match('/[a-z]{2}-[A-Z]{2}/', $header, $found)) {
                $country = str_replace('-', '_', $found[0]);
            } else {
                $country = strtolower(substr($header, 0, 2));
            }
        }

        if (!$country) {
            return $this->default;
        }

        if (isset($this->languages[$country])) {
            return $country;
        }

        foreach ($this->languages as $k => $v) {
            if (preg_match('/' . $country . '/i', $v)) {
                return $k;
            }
        }

        return $this->default;
    }

    /**
     * 
     * @return self
     */
    public function revertLanguage() {
        if ($this->languagePrev) {
            return $this->setLanguage($this->languagePrev);
        }
        return $this->setLanguage(NULL);
    }

// <editor-fold defaultstate="collapsed" desc="Getters">
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

// </editor-fold>

    /**
     * Load language dictionary
     * 
     * @param string $domain
     */
    public function bind($domain) {
        $this->dictionary->bind($domain);
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
     * Set default language dictionary
     * 
     * @param string $domain
     * @return self
     */
    public function setDomain($domain) {
        $this->dictionary->setDomain($domain);
        return $this;
    }

    /**
     * @param string $lang
     * @return self
     * @throws RuntimeException
     */
    public function setLanguage($lang) {
        $lang = $lang ? strtolower($lang) : $this->getDefault();

        if ($this->language == $lang) {
            return $this;
        }

        $this->checkLanguage($lang);
        $this->languagePrev = $this->language;
        $this->language = $lang;
        $this->loadDictionary();
        return $this;
    }

    /**
     * 
     * @param string $language
     * @throws GettextException
     */
    final protected function checkLanguage($language) {
        if (!isset($this->languages[$language])) {
            throw new GettextException('Language is not defined: ' . $lang);
        }
    }

    /**
     * Load language dictionary
     * 
     * @throws GettextException
     */
    private function loadDictionary() {
        if ($this->os->isWindows()) {
            putenv('LANG=' . $this->language);
            $set = TRUE;
        } else {
            $set = setlocale(defined('LC_MESSAGES') ? LC_MESSAGES : LC_ALL, $this->languages[$this->language]);
        }

        if (!$set) {
            throw new GettextException('Probaly you have not instaled locale support on your machine. Let\'s try command $: locale -a');
        }
    }

// <editor-fold defaultstate="collapsed" desc="Constructors setters">    

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

// </editor-fold>

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

}
