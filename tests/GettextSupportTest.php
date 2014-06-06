<?php

use h4kuna\Gettext\Macros\GettextSupport;

$container = require_once __DIR__ . '/bootstrap.php';

/* @var $macro GettextSupport */
$macro = $container->getService('gettextExtension.gettextSupport');

$macro->parse($args);
