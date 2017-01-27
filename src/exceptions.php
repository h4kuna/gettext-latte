<?php

namespace h4kuna\Gettext;

class GettextException extends \Exception {}

class UnsupportedOperationSystemException extends GettextException {}

class UnsupportedTranslateMacroException extends GettextException {}
