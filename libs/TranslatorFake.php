<?php

namespace h4kuna;

use Nette\Object,
    Nette\Localization\ITranslator;

/**
 * Only replace, this does't translate
 */
class TranslatorFake extends Object implements ITranslator {

    function translate($message, $count = NULL) {
        return call_user_func_array('self::t', func_get_args());
    }

    static function t($message /* , ... */) {
        $args = func_get_args();
        array_shift($args);
        return ($args) ? vsprintf($message, $args) : $message;
    }

}
