<?php

namespace h4kuna\Gettext;

use Tester\Assert;

$container = include __DIR__ . '/../bootstrap.php';

class GettextSetupTest extends \Tester\TestCase
{

	/** @var GettextSetup */
	private $gettext;

	/** @var callable */
	private $factory;

	public function __construct($factory)
	{
		$this->factory = $factory;
	}

	public function setUp()
	{
		$factory = $this->factory;
		$this->gettext = $factory();
	}

	public function testDefault()
	{
		Assert::same('cs', $this->gettext->getDefault());
		Assert::true($this->gettext->isDefault());

		$this->gettext->setLanguage('cs'); // nothing change
		Assert::equal('Ahoj světe', gettext('Ahoj světe'));
	}

	public function testChangeHomeLang()
	{
		$this->gettext->changeHomeLang('EN'); //same en
		Assert::equal('Hello world', gettext('Ahoj světe'));
		$this->gettext->revertHomeLang();
		Assert::equal('Ahoj světe', gettext('Ahoj světe'));
	}

	public function testBindDomain()
	{
		$this->gettext->loadDomain('foo'); // load dictionary if not loaded
		Assert::equal('Čus světe', dgettext('foo', 'Ahoj světe'));
		Assert::equal('Ahoj světe', gettext('Ahoj světe'));

		$this->gettext->changeHomeLang('en');
		Assert::equal('Welcome world', dgettext('foo', 'Ahoj světe'));
		Assert::equal('Hello world', gettext('Ahoj světe'));
		// equal
		Assert::equal('Hello world', dgettext('messages', 'Ahoj světe'));
	}

	/**
	 * @throws \h4kuna\Gettext\GettextException
	 */
	public function testBadSetLanguage()
	{
		$this->gettext->setLanguage('Unknown language');
	}

	public function setDefaultLanguage()
	{
		$this->gettext->setLanguage('en');
		Assert::equal('en', $this->gettext->getLanguage());
		$this->gettext->setLanguage(NULL); // set default
		Assert::equal('Ahoj světe', gettext('Ahoj světe'));
		Assert::equal('cs', $this->gettext->getLanguage());
	}

	public function testRouterAccept()
	{
		Assert::equal('cs|en', $this->gettext->routerAccept());
	}

}

$factory = function() use ($container) {
	return $container->createService('gettextLatteExtension.gettext');
};

(new GettextSetupTest($factory))->run();
