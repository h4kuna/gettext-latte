<?php

namespace h4kuna\Gettext;

use h4kuna\Gettext\GettextException;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Object;
use Nette\Utils\Finder;
use SplFileInfo;
use Nette\Http\FileUpload;

/**
 *
 * @author Milan Matějček
 */
class Dictionary extends Object
{

	const PHP_DIR = '/LC_MESSAGES/';
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

	/**
	 * Check path wiht dictionary
	 *
	 * @param string $path
	 * @throws GettextException
	 */
	public function __construct($path, IStorage $storage)
	{
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
	 * @return self
	 * @throws GettextException
	 */
	public function setDomain($domain)
	{
		if ($this->domain == $domain) {
			return $this;
		}
		$this->loadDomain($domain);
		$this->domain = textdomain($domain);
		return $this;
	}

	/**
	 * Load dictionary if not loaded.
	 *
	 * @param string $domain
	 * @throws GettextException
	 */
	public function loadDomain($domain)
	{
		if (!isset($this->domains[$domain])) {
			throw new GettextException('This domain does not exists: ' . $domain);
		}
		if ($this->domains[$domain] === FALSE) {
			bindtextdomain($domain, $this->path);
			bind_textdomain_codeset($domain, 'UTF-8');
			$this->domains[$domain] = TRUE;
		}
		return $domain;
	}

	/** @return string */
	public function getDomain()
	{
		return $this->domain;
	}

	/**
	 * Load all dictionaries.
	 *
	 * @param string $default
	 */
	public function loadAllDomains($default)
	{
		foreach ($this->domains as $domain => $_n) {
			$this->loadDomain($domain);
		}
		$this->setDomain($default);
	}

	/**
	 * Offer file download.
	 *
	 * @param string $language
	 * @throws GettextException
	 */
	public function download($language)
	{
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
	 * Save uploaded files.
	 *
	 * @param string $lang
	 * @param FileUpload $po
	 * @param FileUpload $mo
	 */
	public function upload($lang, FileUpload $po, FileUpload $mo)
	{
		$mo->move($this->getFile($lang, 'mo'));
		$po->move($this->getFile($lang, 'po'));
	}

	/**
	 * Filesystem path for domain
	 *
	 * @param string $lang
	 * @param string $extension
	 * @return string
	 */
	public function getFile($lang, $extension = 'mo')
	{
		$file = $this->path . $lang . self::PHP_DIR . $this->domain . '.' . $extension;

		if (!is_file($file)) {
			throw new GettextException('File not found: ' . $file);
		}

		return $file;
	}

	/**
	 * Check for available domain.
	 *
	 * @return array
	 */
	private function loadDomains()
	{
		if ($this->cache->load(self::DOMAIN) !== NULL) {
			return $this->domains = $this->cache->load(self::DOMAIN);
		}

		$files = $match = $domains = array();
		$find = Finder::findFiles('*.po');
		foreach ($find->from($this->path) as $file) {
			/* @var $file SplFileInfo */
			if (preg_match('/' . preg_quote($this->path, '/') . '(.*)(?:\\\|\/)/U', $file->getPath(), $match)) {
				$_dictionary = $file->getBasename('.po');
				$domains[$match[1]][$_dictionary] = $_dictionary;
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

		if (!isset($_domains)) {
			// @todo https://github.com/josscrowcroft/php.mo
			throw new GettextException('Let\'s generate *.mo files.');
		}


		$data = array_combine($_domains, array_fill_keys($_domains, FALSE));
		return $this->domains = $this->cache->save(self::DOMAIN, $data, array(Cache::FILES => $files));
	}

	/**
	 * Check dictionary path.
	 *
	 * @param string $path
	 * @throws GettextException
	 */
	private function setPath($path)
	{
		$this->path = realpath($path);
		if (!$this->path) {
			throw new GettextException('Path does not exists: ' . $path);
		}

		$this->path .= DIRECTORY_SEPARATOR;
		return $this;
	}

}
