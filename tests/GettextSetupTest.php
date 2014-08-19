<?php

use h4kuna\GettextSetup;
use Tester\Assert;

$container = require_once __DIR__ . '/bootstrap.php';

/* @var $gettext GettextSetup */
$gettext = $container->getService('gettextLatteExtension.gettext');
$gettext->setDomain('messages');

Assert::same('cs', $gettext->getDefault());
Assert::true($gettext->isDefault());
Assert::equal('Ahoj světe', gettext('Ahoj světe'));

$gettext->setLanguage('cs');
Assert::equal('Ahoj světe', gettext('Ahoj světe'));

$gettext->setLanguage('EN'); //same en
Assert::equal('Hello world', gettext('Ahoj světe'));

$gettext->bind('foo'); // load dictionary if not loaded
Assert::equal('Hello world foo', dgettext('foo', 'Ahoj světe'));
Assert::equal('Hello world', gettext('Ahoj světe'));

$gettext->setLanguage('en');
Assert::equal('Hello world', gettext('Ahoj světe'));

try {
    $gettext->setLanguage('Unknown language');
    Assert::true(FALSE);
} catch (h4kuna\GettextException $e) {
    // good
}

$gettext->revertLanguage();
Assert::equal('Ahoj světe', gettext('Ahoj světe'));

$gettext->setLanguage('en');
Assert::equal('Hello world', gettext('Ahoj světe'));

$gettext->setLanguage(NULL); // set default
Assert::equal('Ahoj světe', gettext('Ahoj světe'));

// Only for development information about availeble languages on your machine.
Assert::true(is_array(GettextSetup::showAvailableLanguages()));


// DETECT LANGUAGE
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = NULL;
Assert::true($gettext->detectLanguage() == 'cs');

$_SERVER['HTTP_ACCEPT_LANGUAGE'] = '';
Assert::true($gettext->detectLanguage() == 'cs');

// chrome 35
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'cs-CZ,cs;q=0.8,en;q=0.6,sk;q=0.4';
Assert::true($gettext->detectLanguage() == 'cs');

// firefox 29
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'cs,en-us;q=0.7,en;q=0.3';
Assert::true($gettext->detectLanguage() == 'cs');

// opera 12.16
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'cs-CZ,cs;q=0.9,en;q=0.8';
Assert::true($gettext->detectLanguage() == 'cs');

// IE 8
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'cs';
Assert::true($gettext->detectLanguage() == 'cs');

/**
 * COMPILATOR ******************************************************************
 * *****************************************************************************
 */
/* @var $compiler h4kuna\Gettext\Latte\LatteCompiler */
$compiler = $container->getService('gettextLatteExtension.compiler');
$compiler->addInclude(__DIR__ . '/../examples');
$compiler->addExclude(__DIR__ . '/../examples/example.latte');
$compiler->addInclude(__DIR__ . '/../examples');
$compiler->run();
echo 'ok';
