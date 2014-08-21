<?php

use h4kuna\Gettext\Latte\MagicObject;

$container = require_once __DIR__ . '/bootstrap.php';

/* @var $compiler h4kuna\Gettext\Latte\LatteCompiler */
$compiler = $container->getByType('h4kuna\Gettext\Latte\LatteCompiler');
$compiler->addInclude(__DIR__ . '/../examples');
$compiler->addExclude(__DIR__ . '/../examples/example.latte');
$compiler->addInclude(__DIR__ . '/../examples');

// undefoned property
$template = $compiler->getTemplate();
$template->languages = $template->mail = $template->menu = new MagicObject(array());
$compiler->run();
