<?php

include __DIR__ . '/bootstrap.php';

use h4kuna\Gettext\Macros\Latte2PhpTokenizer;

$param = "'Ahoj jak se (máš) \' lála\"\"', 'sdasdasad', 6, \$ahoj, \$template->helper('n', \$a1, foo('ahoj2')), strstr('a', \$a, 5), \$f(), \$template->helper('n', \$a1, foo('ahoj2'))";

$params = new Latte2PhpTokenizer($param);
dd($params->getArgs());
