<?php

namespace h4kuna\Gettext;

use Tester\Assert;

$container = include __DIR__ . '/../bootstrap.php';

class DictionaryTest extends \Tester\TestCase
{

	/** @var Dictionary */
	private $dictionary;

	/** @var callable */
	private $factory;

	public function __construct($factory)
	{
		$this->factory = $factory;
	}

	public function setUp()
	{
		$factory = $this->factory;
		$this->dictionary = $factory();
	}

	public function testExistsFiles()
	{
		$this->dictionary->setDomain('messages');
		Assert::true(file_exists($this->dictionary->getFile('cs', 'mo')));
		Assert::true(file_exists($this->dictionary->getFile('cs')));
		Assert::true(file_exists($this->dictionary->getFile('cs', 'po')));

		$dictionary = $this->dictionary;
		Assert::exception(function() use ($dictionary) {
			$dictionary->getFile('fr', 'po');
		}, \h4kuna\Gettext\FileNotFoundException::class);
	}

	public function testDomain()
	{
		Assert::equal(NULL, $this->dictionary->getDomain());
		$this->dictionary->loadDomain('messages');
		Assert::equal(NULL, $this->dictionary->getDomain());

		$this->dictionary->setDomain('foo');
		Assert::equal('foo', $this->dictionary->getDomain());

		$this->dictionary->setDomain('messages');
		Assert::equal('messages', $this->dictionary->getDomain());

		$dictionary = $this->dictionary;
		Assert::exception(function() use ($dictionary) {
			$dictionary->loadDomain('unknown');
		}, \h4kuna\Gettext\DomainDoesNotExistsException::class);
	}

}

$factory = function() use ($container) {
	return $container->createService('gettextLatteExtension.dictionary');
};

(new DictionaryTest($factory))->run();
