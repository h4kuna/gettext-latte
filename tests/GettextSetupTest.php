<?php

use h4kuna\GettextSetup;
use Tester\Assert;

$container = require_once __DIR__ . '/bootstrap.php';

/* @var $gettext GettextSetup */
$gettext = $container->getService('gettextExtension.setup');
dump($gettext);

Assert::equal(gettext('Ahoj světe'), 'Ahoj světe');

$gettext->setLanguage('EN'); //same en
Assert::equal(gettext('Ahoj světe'), 'Hello world');

$gettext->setLanguage('en');
Assert::equal(gettext('Ahoj světe'), 'Hello world');

$gettext->setLanguage(NULL);
Assert::equal(gettext('Ahoj světe'), 'Hello world');

$gettext->setLanguage('Unknownc language');
Assert::equal(gettext('Ahoj světe'), 'Hello world');


dump($gettext);
