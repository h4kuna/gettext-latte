<?php

namespace Utility;

/**
 * GettextTranslator od Romana Sklenáře
 */
class GettextNatural extends \GettextTranslator {

    private $plural;

    public function __construct($filename, $locale = NULL) {
        parent::__construct($filename, $locale);
        $this->setPlural();
    }

    protected function setPlural() {
        $s = preg_replace('/([a-z]+)/', '$$1', $this->meta['Plural-Forms']);
        $s .= ' return $plural;';
        $this->plural = create_function('$n', $s);
    }

    public function gettext($message) {
        if (!isset($this->dictionary[$message])) {
            return $message;
        }
        return $this->dictionary[$message]->translate();
    }

    public function ngettext($msgid1, $msgid2, $n) {
        $p = $this->plural;
        if (!isset($this->dictionary[$msgid1])) {
            return $n == 1 ? $msgid1 : $msgid2;
        }
        return $this->dictionary[$msgid1]->translate($p($n));
    }

}

