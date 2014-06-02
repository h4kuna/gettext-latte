<?php

use h4kuna\GettextSetup;
use Tester\Assert;

$container = require_once __DIR__ . '/bootstrap.php';

/* @var $gettext GettextSetup */
$gettext = $container->getService('gettextExtension.setup');

$gettext->loadAllDomains('messages');


Assert::equal('Ahoj světe', gettext('Ahoj světe'));

$gettext->setLanguage('cs');
Assert::equal('Ahoj světe', gettext('Ahoj světe'));

$gettext->setLanguage('EN'); //same en
Assert::equal('Hello world', gettext('Ahoj světe'));

// dcgettext($domain, $message, $category); // unsupported
dump(dgettext('foo', 'Ahoj světe'));
// $gettext->setDomain('foo');
// Assert::equal(gettext('Ahoj světe'), 'Sado maso');


$gettext->setLanguage('en');
Assert::equal(gettext('Ahoj světe'), 'Hello world');

$gettext->setLanguage(NULL);
Assert::equal('Hello world', gettext('Ahoj světe'));

try {
    $gettext->setLanguage('Unknown language');
    Assert::true(FALSE);
} catch (h4kuna\GettextException $e) {
    // good
}
Assert::equal('Hello world', gettext('Ahoj světe'));

Assert::true(is_array($gettext::showAvailableLanguages()));

dump($gettext);
