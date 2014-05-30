<?php

namespace h4kuna\Gettext;

use h4kuna\GettextException;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Object;
use Nette\Utils\Finder;
use SplFileInfo;

/**
 *
 * @author Milan Matějček
 */
class Dictionary extends Object {

    const DOMAIN = 'message';

    /**
     * Property for temporary resolve bug
     * 
     * @var type
     */
    private $messages;
    private $msg;

    /** @var string */
    private $path;

    /**
     * List of domains
     *
     * @var array
     */
    private $domains = array();

    /** @var string */
    private $domain = self::DOMAIN;

    /** @var Cache */
    private $cache;

    /** @var bool */
    private $development;

    const PHP_DIR = '/LC_MESSAGES/';

    /**
     * Check path wiht dictionary
     * 
     * @param string $path
     * @throws GettextException
     */
    public function __construct($path, $development, IStorage $storage) {
        $this->setPath($path);
        $this->cache = new Cache($storage, __CLASS__);
        $this->development = $development;
        $this->loadDomains();
        dump($this->domains);
    }

    private function setPath($path) {
        $this->path = realpath($path);
        if (!$this->path) {
            throw new GettextException('Path does not exists: ' . $path);
        }

        $this->path .= DIRECTORY_SEPARATOR;
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
            $msg = $this->getDomain();
        }
        return $this->path . $lang . self::PHP_DIR . $msg . '.' . $extension;
    }

    public function getDomain() {
        return $this->domain;
    }

    // <editor-fold defaultstate="collapsed" desc="Setters"> 
    public function setDomain($domain) {
        $this->domain = $domain;
    }

    public function setDomains(array $domains) {
        foreach ($domains as $domain) {
            $this->setDomain($domain);
        }
    }

    /**
     * *.po file name
     * @param type $msg
     * @return self
     */
    public function setCatalog($msg) {
        $this->msg = $this->messages = $msg;
        textdomain($this->getDomain());
        return $this;
    }

// </editor-fold>

    /**
     * Offer file download
     * 
     * @param string $language
     * @throws GettextException
     */
    public function download($language) {
        $file = $this->getFile($language, 'po');
        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename=' . $language . '-' . basename($file));
            header('Content-Length: ' . filesize($file));
            flush();
            readfile($file);
            exit;
        }
        throw new GettextException('File not found: ' . $file);
    }

    /**
     * 
     * @return array
     */
    private function loadDomains() {
        if (isset($this->cache[self::DOMAIN])) {
            return $this->domains = $this->cache[self::DOMAIN];
        }

        $math = $domains = array();
        $find = Finder::findFiles('*.po');
        foreach ($find->from($this->path) as $file) {
            /* @var $file SplFileInfo */
            if (preg_match('~' . $this->path . '(.*)/~U', $file->getPath(), $math)) {
                $domains[$math[1]] = $file->getBasename('.po');
            }
        }

        $options = NULL;
        if (!$this->development) {
            $options[Cache::EXPIRATION] = '+10 seconds';
        }
        return $this->domains = $this->cache->save(self::DOMAIN, $domains, $options);
    }

    /**
     * Bug http://www.php.net/manual/en/function.gettext.php#58310
     * 
     * @param string $lang
     * @return void
     */
    public function checkFile($lang) {
        $mo = $this->getFile($lang, '!mo');
        if (!file_exists($mo)) {
            throw new GettextException('File not found ' . $mo);
        }
        $this->domain = filemtime($mo) . $this->domain;
        $moTemp = $this->getFile($lang);
        if (!file_exists($moTemp)) {
            if (!@copy($mo, $moTemp)) {
                throw new GettextException('Directory is not writeable: ' . dirname($mo));
            }
            $po = basename($this->getFile($lang, 'po'));
            foreach (new FilesystemIterator(dirname($mo)) as $filepath => $file) {
                switch ($file->getBasename()) {
                    case basename($mo):
                    case basename($moTemp):
                    case $po:
                        continue 2;
                }

                unlink($filepath);
            }
        }
    }

}
