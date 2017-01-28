<?php

use Nette\Utils;

include __DIR__ . '/../vendor/autoload.php';

$tempDir = __DIR__ . '/temp';
$logDir = $tempDir . '/log';
Utils\FileSystem::createDir($tempDir . '/cache/latte');
Utils\FileSystem::createDir($logDir);

$configurator = new Nette\Configurator;
$configurator->enableDebugger($logDir);
$configurator->setTempDirectory($tempDir);
$configurator->addConfig(__DIR__ . '/config/test.neon');
$container = $configurator->createContainer();

Tester\Environment::setup();

return $container;



