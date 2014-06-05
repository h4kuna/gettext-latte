<?php

use h4kuna\Gettext\Dictionary;
use Tester\Assert;

$container = require_once __DIR__ . '/bootstrap.php';


$dictionary = new Dictionary(__DIR__ . '/../locale', $container->getService('cacheStorage'));
$dictionary->setDomain('messages');
Assert::true(file_exists($dictionary->getFile('cs', 'mo')));
Assert::true(file_exists($dictionary->getFile('cs')));
Assert::true(file_exists($dictionary->getFile('cs', 'po')));

try {
    $path = $dictionary->getFile('fr', 'po');
    Assert::equal('This is bad.', $path);
} catch (h4kuna\GettextException $e) {
    // good
}

