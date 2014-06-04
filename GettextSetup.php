<?php

namespace h4kuna;

use h4kuna\Gettext\Dictionary;
use h4kuna\Gettext\Os;
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

    public function setDomain($domain) {
        $this->dictionary->setDomain($domain);
        return $this;
    }

    /**
     * try find users language
     * @return string
     */
    public function detectLanguage($HTTP_ACCEPT_LANGUAGE) {
        $accept = $HTTP_ACCEPT_LANGUAGE;
        if ($accept) {
            $country = NULL;
            if (preg_match('/[a-z]{2}-[A-Z]{2}/', $accept, $found)) {
                $country = str_replace('-', '_', $found[0]);
            }

            foreach ($this->languages as $k => $v) {
                if (preg_match('/' . $k . '/', $accept) ||
                        ($country && preg_match('/' . $country . '/', $v))) {
                    return $k;
                }
            }
        }
        return $this->default;
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

    /**
     * @return array
     */
    public function getLanguages() {
        return $this->languages;
    }

// </editor-fold>

    public function bind($domain) {
        $this->dictionary->bind($domain);
    }

    /**
     * Is active default language?
     * 
     * @return bool
     */
    public function isDefault() {
        return $this->getLanguage() === $this->getDefault();
    }

    public function loadAllDomains($default) {
        $this->dictionary->loadAllDomains($default);
    }

    /**
     * @param string $lang
     * @return self
     * @throws RuntimeException
     */
    public function setLanguage($lang) {
        $lang = strtolower($lang);

        if (!$lang || $this->language == $lang) {
            return $this;
        }

        if (!isset($this->languages[$lang])) {
            throw new GettextException('Language is not defined: ' . $lang);
        }

        $this->language = $lang;
        $this->loadDictionary();
        return $this;
    }

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

// </editor-fold>
//-----------------
}
