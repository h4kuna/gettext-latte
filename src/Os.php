<?php

namespace h4kuna\Gettext;

use h4kuna\Gettext\GettextException;

/**
 * @author Milan Matějček
 */
class Os
{

	/**
	 * OS platforms
	 */
	const
		LINUX = 'linux',
		MAC = 'mac',
		WINDOWS = 'windows';

	/** @var string */
	private $os;

	/** @var array */
	private $translateLocale = array();

	public function __construct(array $winLocaleTranslate)
	{
		$this->translateLocale[self::WINDOWS] = $winLocaleTranslate;
	}

	public function getWindowsLocale($key)
	{
		$key = preg_replace('/\.utf8$/', '', $key);
		return $this->translateLocale[self::WINDOWS][$key];
	}

	public function getOs()
	{
		if ($this->os !== NULL) {
			return $this->os;
		}

		switch (strtolower(substr(PHP_OS, 0, 5))) {
			case 'windo':
			case 'winnt':
				$this->os = self::WINDOWS;
				break;
			case 'darwi':
				$this->os = self::MAC;
				break;
			case 'linux':
			case 'freeb':
				$this->os = self::LINUX;
				break;
			default:
				throw new GettextException('Unsupported OS please write to autor. Your system is ' . PHP_OS . '.');
		}
		return $this->os;
	}

	public function isMac()
	{
		return $this->getOs() === self::MAC;
	}

	public function isLinux()
	{
		return $this->getOs() === self::LINUX;
	}

	public function isWindows()
	{
		return $this->getOs() === self::WINDOWS;
	}

	public function __toString()
	{
		return (string) $this->getOs();
	}

}
