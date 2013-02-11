<?php

namespace h4kuna;

require_once 'libs/GettextException.php';
require_once 'libs/TranslatorFake.php';

/**
 * Description of Gettext
 *
 * @author Milan Matějček
 */
class Gettext extends TranslatorFake {

    const PHP_DIR = '/LC_MESSAGES/';

    /** @var GettextNatural */
    public static $translator;

    /** @var bool */
    protected $useHelper;

    /** @var array */
    protected $langs;

    /** @var string */
    private $path;

    /**
     * property for temporary resolve bug
     * @var type
     */
    private $messages;
    private $msg;

    /** @var string */
    private $default;

    /** @var string */
    protected $language;

    /**
     *
     * @param string $path
     * @param array $langs
     * @param boolean $useHelper if gettext extension is not instaled
     * @param type $msg catalog name
     */
    public function __construct($path, array $langs, $msg = 'messages', $useHelper = FALSE) {
        reset($langs);
        $this->default = key($langs);
        $this->langs = $langs;
        $this->useHelper = (bool) $useHelper;

        $this->path = $path;
        $this->msg = $this->messages = $msg;
    }

    /**
     * try find users language
     * @return string
     */
    public function detectLanguage() {
        $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        if ($accept) {
            $country = NULL;
            if (preg_match('/[a-z]{2}-[A-Z]{2}/', $accept, $found)) {
                $country = str_replace('-', '_', $found[0]);
            }

            foreach ($this->langs as $k => $v) {
                if (preg_match('/' . $k . '/', $accept) ||
                        ($country && preg_match('/' . $country . '/', $v))) {
                    return $k;
                }
            }
        }
        return $this->default;
    }

    /**
     * offer file download
     * @param string $lang
     * @throws GettextException
     */
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
        throw new GettextException('File not found: ' . $file);
    }

    /**
     * default language
     * @return string
     */
    public function getDefault() {
        return $this->default;
    }

    /**
     * choosen language
     * @return type
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * filesystem path for catalog
     * @param type $lang
     * @param type $extension
     * @return type
     */
    public function getFile($lang, $extension = 'mo') {
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
     * is active default language?
     * if return NULL you don't call method setLanguage
     * @return NULL|TRUE|FALSE
     */
    public function isDefault() {
        if (!$this->getLanguage()) {
            return NULL; // exception?
        }
        return $this->getLanguage() == $this->getDefault();
    }

    /**
     * @todo switch catalog
     * @param string $lang
     * @return \h4kuna\Gettext
     * @throws \RuntimeException
     */
    public function setLanguage($lang = NULL) {
        $this->language = $lang ? $lang : $this->getLanguage();
        $l = $this->langs[$this->language];
        $system = php_uname('s');
        $const = defined('\LC_MESSAGES') ? \LC_MESSAGES : \LC_ALL;

        if ($system == 'Windows NT') {// Windows
            putenv('LANG=' . $this->language);
            $set = TRUE;
        } else {
            if ($system == 'Darwin') {// Mac
                $l = str_replace('utf8', 'UTF-8', $l);
            }
            $set = setlocale($const, $l);
        }

        $bindText = function_exists('bindtextdomain');
        if (!$set && $this->useHelper || !$bindText) {
            if (!$bindText) {
                require_once 'libs/fce.php';
                $this->useHelper = FALSE;
            }
            setlocale($const, '');
            require_once 'libs/GettextNatural.php';
            self::$translator = new GettextNatural($this->getFile($this->language, '!mo'), $this->language);
        } elseif (!$set) {
            throw new \RuntimeException($l . ' locale is not supported on your machine. Set useHelper on TRUE.');
        } else {
            $this->checkFile($this->language);
            bindtextdomain($this->messages, $this->path);
            bind_textdomain_codeset($this->messages, 'UTF-8');
            textdomain($this->messages);
        }
        return $this;
    }

//------------------------------------------------------------------------------
    /**
     * if gettext extension is not instaled
     * @param string $message
     * @return string
     */
    public static function gettext($message) {
        return self::$translator->gettext($message);
    }

    public static function ngettext($msgid1, $msgid2, $n) {
        return self::$translator->ngettext($msgid1, $msgid2, $n);
    }

//------------------------------------------------------------------------------
    protected function prefix() {
        return '\\' . __CLASS__ . '::';
    }

    /**
     * logic gettext or ngettext
     * @param type $isNgettext
     * @param type $fce
     * @return int
     */
    protected function method($isPlural, &$fce) {
        $slice = 1;
        if ($isPlural) {
            $fce .= 'n';
            $slice = 3;
        }

        $fce .= 'gettext';
        return $slice;
    }

    /**
     * bug http://www.php.net/manual/en/function.gettext.php#58310
     * @param type $lang
     * @return type
     */
    private function checkFile($lang) {
        $mo = $this->getFile($lang, '!mo');
        if (!file_exists($mo)) {
            if ($this->isDefault()) {
                return;
            }
            throw new GettextException('File not found ' . $mo);
        }
        $this->messages = filemtime($mo) . $this->messages;
        $moTemp = $this->getFile($lang);
        if (!file_exists($moTemp)) {
            if (!@copy($mo, $moTemp)) {
                throw new GettextException('Directory is not writeable: ' . dirname($mo));
            }
            $po = basename($this->getFile($lang, 'po'));
            foreach (new \DirectoryIterator(dirname($mo)) as $file) {
                switch ($file->getBasename()) {
                    case basename($mo):
                    case basename($moTemp):
                    case $po:
                        continue 2;
                }

                unlink($file->getPathname());
            }
        }
    }

}

