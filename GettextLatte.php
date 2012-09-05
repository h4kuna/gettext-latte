<?php

namespace Utility;

use Nette\DI\Container;
use Nette\Object,
    \Nette\Localization\ITranslator;

/**
 * @author Milan Matějček <milan.matejcek@gmail.com>
 */
class GettextLatte extends Object implements ITranslator {

  /** @var GettextNatural */
  public static $translator;
  private $useHelper;
  private $langs;
  private $path;
  private $messages;

  /**
   *
   * @param type $path
   * @param type $langs
   * @param boolean $useHelper not supported on windows
   * @param type $msg
   */
  public function __construct($path, $langs, $useHelper = FALSE, $msg = 'messages') {
    $this->useHelper = $useHelper;
    $this->langs = $langs;
    $this->path = $path;
    $this->messages = $msg;
  }

  public function setLanguage($lang) {
    $l = $this->langs[$lang];
    $set = setlocale(\LC_ALL, $l);
    if (strstr(strtolower(php_uname('u')), 'windows') !== FALSE) {
      putenv('LANG=' . $lang);
      $set = FALSE;
    }

    if (!$set && $this->useHelper) {
      require_once 'fce.php';
      setlocale(\LC_ALL, '');
      $file = $this->path . $lang . '/' . 'LC_MESSAGES/' . $this->messages . '.mo';
      self::$translator = new GettextNatural($file, $lang);
    } elseif (!$set) {
      throw new \RuntimeException($l . ' locale is not supported on your machine. Set useHelper on TRUE.');
    } else {
      bindtextdomain($this->messages, $this->path);
      bind_textdomain_codeset($this->messages, 'UTF-8');
      textdomain($this->messages);
    }
  }

  /**
   * instalace maktra
   * @param \Nette\Latte\Compiler $parser
   * @param self $gettext
   */
  static function install(\Nette\Latte\Compiler $parser, self $gettext) {
    $set = new \Nette\Latte\Macros\MacroSet($parser);
    $callback = callback($gettext, 'macroGettext');
    $set->addMacro('_', $callback);
  }

  /**
   * použitelná ukázka jak registrovat makro do Latte, podminkou je mít servisu translator a v ní instanci této třídy
   * @param \Nette\DI\Container $context
   * @return \Nette\Latte\Engine
   */
  static function latte(Container $context) {
    $service = new \Nette\Latte\Engine;
    self::install($service->compiler, $context->translator);
    return $service;
  }

  // metody pri použití berličky když nejsou na stoji nainstalované lokalizace
  public static function gettext($message) {
    return self::$translator->gettext($message);
  }

  public static function ngettext($msgid1, $msgid2, $n) {
    return self::$translator->ngettext($msgid1, $msgid2, $n);
  }

  /**
   * makro pro podporu gettextu
   * @param \Nette\Latte\MacroNode $node
   * @param type $writer
   * @return type
   */
  public function macroGettext(\Nette\Latte\MacroNode $node, $writer) {
    $name = $node->name;
    $args = $node->args;
    $l = substr($args, 0, 1);
    if ($l == 'n') {
      $name = '_' . $l;
      $args = substr($args, 1);
    }
    $prefix = $this->prefix();
    $fce = NULL;
    $slice = $this->method($name != '_', $fce);

    $data = self::stringToArgs($args);
    $out = $fce . '(' . implode(', ', array_slice($data, 0, $slice)) . ')';

    if ($this->useHelper) {
      $out = $prefix . '$translator ? ' . $prefix . $out . ':' . $out;
    }

    $diff = $this->foundReplce($data[0]);
    if ($diff) {
      $out = 'sprintf(' . $out . ', ' . implode(', ', array_slice($data, $diff)) . ')';
    }

    return $writer->write('echo %modify(' . $out . ')');
  }

  /**
   * @todo přepsat na regulár
   * @param type $s
   * @param type $s
   * @return type
   */
  static private function stringToArgs($s) {
    $len = strlen($s);
    $out = array();
    $outI = 0;

    $in = FALSE;
    $slash = NULL;
    for ($i = 0; $i < $len; ++$i) {

      if ($i + 1 < $len && $s{$i} . $s{$i + 1} == '\\' . $slash) {
        $out[$outI] .= $s{$i} . $s{$i + 1};
        ++$i;
        continue;
      }
      if (!isset($out[$outI])) {
        $out[$outI] = '';
      }
      $out[$outI] .= $s{$i};

      if ($s{$i} == "'" || $s{$i} == '"') {
        $in = !$in;
        $slash = $in ? $s{$i} : NULL;
      }

      if (!$in && $s{$i} == ',') {
        ++$outI;
      }
    }

    array_walk($out, function(&$s) {
              $s = trim($s, "\t \n\r\0\x0B,");
            });
    return $out;
  }

  private function method($isNgettext, &$fce) {
    $slice = 1;
    if ($isNgettext) {
      $fce .= 'n';
      $slice = 3;
    }

    $fce .= 'gettext';
  }

  private function foundReplce($str) {
    return -1 * substr_count($str, '%s');
  }

  private function prefix() {
    return '\\' . __CLASS__ . '::';
  }

  public function translate($message, $count = null) {
    if (!self::$translator) {
      return $message;
    }

    $fce = $this->prefix();
    $slice = $this->method(func_num_args() > 2, $fce);
    $data = func_get_args();
    $t = call_user_func_array($fce, array_slice($data, 0, $slice));
    $diff = $this->foundReplce($data[0]);

    if ($diff) {
      return vsprintf($t, array_slice($data, $diff));
    }
    return $t;
  }

}