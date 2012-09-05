<?php

if (!$bindText) {

    function gettext($message) {
        return \Utility\GettextLatte::gettext($message);
    }

    function _($message) {
        return gettext($message);
    }

    function ngettext($msgid1, $msgid2, $n) {
        return \Utility\GettextLatte::ngettext($msgid1, $msgid2, $n);
    }

    // načítáno v objektu
    $this->useHelper = FALSE;
}