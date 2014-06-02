<?php

use h4kuna\Gettext\Dictionary;
use Tester\Assert;

$container = require_once __DIR__ . '/bootstrap.php';


$dictionary = new Dictionary(__DIR__ . '/../locale', TRUE, $container->getService('cacheStorage'));

Assert::true(file_exists($dictionary->getFile('cs', '!mo')));

Assert::true(file_exists($dictionary->getFile('cs')));
Assert::true(file_exists($dictionary->getFile('cs', 'po')));

try {
    $path = $dictionary->getFile('fr', 'po');
    Assert::equal(NULL, $path);
} catch (h4kuna\GettextException $e) {
    
}


$dictionary->setDomain('lala');



