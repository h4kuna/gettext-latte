<?php

function gettext($message) {
    return \h4kuna\Gettext::gettext($message);
}

function _($message) {
    return gettext($message);
}

function ngettext($msgid1, $msgid2, $n) {
    return \h4kuna\Gettext::ngettext($msgid1, $msgid2, $n);
}
