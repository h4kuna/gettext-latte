<?php

namespace Utility;

use Nette\Object,
    \Nette\Localization\ITranslator;

class TranslatorFake extends Object implements ITranslator {

    function translate($message, $count = null) {
        $args = func_get_args();
        array_shift($args);
        return ($args) ? vsprintf($message, $args) : $message;
    }

}
