<?php

namespace h4kuna\Gettext;

use Nette,
    Tester,
    Tester\Assert;

$container = require_once __DIR__ . '/../bootstrap.php';

class GettextSetupTest extends Tester\TestCase {

    /** @var GettextSetup */
    private $gettext;

    /** @var callable */
    private $factory;

    function __construct($factory) {
        $this->factory = $factory;
    }

    function setUp() {
        $factory = $this->factory;
        $this->gettext = $factory();
    }

    function testDefault() {
        Assert::same('cs', $this->gettext->getDefault());
        Assert::true($this->gettext->isDefault());

        $this->gettext->setLanguage('cs'); // nothing change
        Assert::equal('Ahoj světe', gettext('Ahoj světe'));
    }

    function testChangeHomeLang() {
        $this->gettext->changeHomeLang('EN'); //same en
        Assert::equal('Hello world', gettext('Ahoj světe'));
        $this->gettext->revertHomeLang();
        Assert::equal('Ahoj světe', gettext('Ahoj světe'));
    }

    public function testBindDomain() {
        $this->gettext->loadDomain('foo'); // load dictionary if not loaded
        Assert::equal('Čus světe', dgettext('foo', 'Ahoj světe'));
        Assert::equal('Ahoj světe', gettext('Ahoj světe'));

        $this->gettext->changeHomeLang('en');
        Assert::equal('Welcome world', dgettext('foo', 'Ahoj světe'));
        Assert::equal('Hello world', gettext('Ahoj světe'));
        // equal
        Assert::equal('Hello world', dgettext('messages', 'Ahoj světe'));
    }

    public function testBadSetLanguage() {
        $gettext = $this->gettext;
        Assert::exception(function() use ($gettext) {
            $gettext->setLanguage('Unknown language');
        }, '\h4kuna\Gettext\GettextException');
    }

    public function setDefaultLanguage() {
        $this->gettext->setLanguage('en');
        Assert::equal('en', $this->gettext->getLanguage());
        $this->gettext->setLanguage(NULL); // set default
        Assert::equal('Ahoj světe', gettext('Ahoj světe'));
        Assert::equal('cs', $this->gettext->getLanguage());
    }
    
    public function testRouterAccept() {
        Assert::equal('cs|en', $this->gettext->routerAccept());
    }

}

$factory = function() use ($container) {
    return $container->createService('gettextLatteExtension.gettext');
};

$test = new GettextSetupTest($factory);
$test->run();
