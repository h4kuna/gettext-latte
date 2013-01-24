<?php

function gettext($message) {
    return \h4kuna\GettextLatte::gettext($message);
}

function _($message) {
    return gettext($message);
}

function ngettext($msgid1, $msgid2, $n) {
    return \h4kuna\GettextLatte::ngettext($msgid1, $msgid2, $n);
}
