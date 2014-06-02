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

    const DOMAIN = 'messages';

    /** @var string */
    private $path;

    /**
     * List of domains
     *
     * @var array
     */
    private $domains = array();

    /** @var string */
    private $domain;

    /** @var Cache */
    private $cache;

    const PHP_DIR = '/LC_MESSAGES/';

    /**
     * Check path wiht dictionary
     * 
     * @param string $path
     * @throws GettextException
     */
    public function __construct($path, IStorage $storage) {
        $this->cache = new Cache($storage, __CLASS__);
        $this->setPath($path)->loadDomains();
        if (count($this->domains) === 1) {
            $this->setDomain(key($this->domains));
        }
    }

    /**
     * What domain you want.
     * 
     * @param string $domain
     * @throws GettextException
     */
    public function setDomain($domain) {
        if (!isset($this->domains[$domain])) {
            throw new GettextException('This domain does not exests: ' . $domain);
        }
        if ($this->domains[$domain] === FALSE) {
            bindtextdomain($domain, $this->path);
            bind_textdomain_codeset($domain, 'UTF-8');
            $this->domains[$domain] = TRUE;
        }
        textdomain($domain);
        $this->domain = $domain;
        return $this;
    }

    public function loadAllDomains($default) {
        foreach ($this->domains as $domain => $_n) {
            if ($domain != $default) {
                $this->setDomain($domain);
            }
        }
        $this->setDomain($default);
    }

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
     * Filesystem path for domain
     * 
     * @param string $lang
     * @param string $extension
     * @return string
     */
    private function getFile($lang, $extension = 'mo') {
        $file = $this->path . $lang . self::PHP_DIR . $this->getDomain() . '.' . $extension;

        if (!is_file($file)) {
            throw new GettextException('File not found: ' . $file);
        }

        return $file;
    }

    /**
     * Check for available domain
     * 
     * @return array
     */
    private function loadDomains() {
        if (isset($this->cache[self::DOMAIN])) {
            return $this->domains = $this->cache[self::DOMAIN];
        }

        $files = $match = $domains = array();
        $find = Finder::findFiles('*.mo');
        foreach ($find->from($this->path) as $file) {
            /* @var $file SplFileInfo */
            if (preg_match('~' . $this->path . '(.*)/~U', $file->getPath(), $match)) {
                $domains[$match[1]][$file->getBasename('.mo')] = $file->getBasename('.mo');
                $files[] = $file->getPathname();
            }
        }

        $dictionary = $domains;
        foreach ($domains as $lang => $_domains) {
            unset($dictionary[$lang]);
            foreach ($dictionary as $value) {
                $diff = array_diff($_domains, $value);
                if ($diff) {
                    throw new GettextException('For this language (' . $lang . ') you have one or more different dicitonaries: ' . implode('.mo, ', $diff) . '.mo');
                }
            }
        }

        $data = array_combine($_domains, array_fill_keys($_domains, FALSE));
        return $this->domains = $this->cache->save(self::DOMAIN, $data, array(Cache::FILES => $files));
    }

    /**
     * Check dictionary path
     * 
     * @param string $path
     * @throws GettextException
     */
    private function setPath($path) {
        $this->path = realpath($path);
        if (!$this->path) {
            throw new GettextException('Path does not exists: ' . $path);
        }

        $this->path .= DIRECTORY_SEPARATOR;
        return $this;
    }

}
