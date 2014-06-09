<?php

include __DIR__ . "/../vendor/autoload.php";

function dd($var /* ... */) {
    foreach (func_get_args() as $arg) {
        \Tracy\Debugger::dump($arg);
    }
    exit;
}

// 2# Create Nette Configurator
$configurator = new Nette\Configurator;

$tmp = __DIR__ . '/temp/' . php_sapi_name();
@mkdir($tmp, 0777, TRUE);
$configurator->enableDebugger($tmp);
$configurator->setTempDirectory($tmp);
$configurator->setDebugMode();

$configurator->defaultExtensions['gettextExtension'] = '\h4kuna\Gettext\DI\GettextLatteExtension';
$configurator->addConfig(__DIR__ . '/test.neon');
$container = $configurator->createContainer();
$container->getService('session')->start();
return $container;



