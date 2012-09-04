<?php

$useHelper = FALSE;

if(!function_exists('gettext'))
{
  function gettext($message) {
    return \Utility\Gettext::gettext($message);
  }

  function _($message) {
    return gettext($message);
  }

  function ngettext($msgid1, $msgid2, $n) {
    return \Utility\Gettext::ngettext($msgid1, $msgid2, $n);
  }
  $useHelper = TRUE;
}