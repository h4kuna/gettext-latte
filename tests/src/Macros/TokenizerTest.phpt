<?php

namespace h4kuna\Gettext\Macros;

use Nette,
    Tester,
    Tester\Assert;

$container = require_once __DIR__ . '/../../bootstrap.php';

class TokenizerTest extends Tester\TestCase {

    public function testArgs() {
        $param = "'Ahoj jak se (máš) \' lála\"\"', 'sdasdasad', 6, \$ahoj, \$template->helper('n', \$a1, foo('ahoj2')), strstr('a', \$a, 5), \$f(), \$template->helper('n', \$a1, foo('ahoj2'))";

        $token = new Latte2PhpTokenizer($param);
        $params = $token->getArgs();
        Assert::equal(array(
            "'Ahoj jak se (máš) \' lála\"\"'",
            "'sdasdasad'",
            "6",
            "\$ahoj",
            "\$template->helper('n',\$a1,foo('ahoj2'))",
            "strstr('a',\$a,5)",
            "\$f()",
            "\$template->helper('n',\$a1,foo('ahoj2'))",
                ), $params);
    }

}

$test = new TokenizerTest();
$test->run();
