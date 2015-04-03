<?php

include __DIR__ . "/../vendor/autoload.php";

function dd($var /* ... */) {
    foreach (func_get_args() as $arg) {
        \Tracy\Debugger::dump($arg);
    }
    exit;
}

// Tester\Environment::setup();
// 2# Create Nette Configurator
$configurator = new Nette\Configurator;

$tmp = __DIR__ . '/temp/' . php_sapi_name();
@mkdir($tmp, 0755, TRUE);
@mkdir($tmp . '/cache/latte', 0755, TRUE);
$configurator->enableDebugger($tmp);
$configurator->setTempDirectory($tmp);
$configurator->setDebugMode(FALSE);
$configurator->addConfig(__DIR__ . '/test.neon');
$container = $configurator->createContainer();
Tracy\Debugger::enable(false);

function setAppLocale($lang) {
    $domain = 'messages';

    switch ($lang) {
        case 'cs':
            $locale = 'czech';
            $strings = 'cs_CZ';
            break;

        case 'en':
            $locale = 'english';
            $strings = 'en_US';
            break;

        case 'de':
            $locale = 'german';
            $strings = 'de_DE';
            break;

        case 'hu':
            $locale = 'hungarian';
            $strings = 'hu_HU';
            break;

        case 'ru':
            $locale = 'russian';
            $strings = 'ru_RU';
            break;
    }

dump($locale);
    dump(putenv("LANGUAGE=$strings"));
    dump(putenv("LANG=" . $strings));
    dump(putenv("LC_ALL=$strings"));
    dump($path = setlocale(LC_ALL, $locale));

    $locale = __DIR__ . "/locale";
    dump(bindtextdomain($domain, $locale));

    $dir = $locale . '/' . $path . h4kuna\Gettext\Dictionary::PHP_DIR;
    dump($dir, is_dir($dir), is_file($dir . '/' . $domain . '.mo'));
//    dump(bind_textdomain_codeset($domain, "UTF-8"));
    dump(textdomain($domain));
}

// I18N support information here
//$language = "en_US";
//$locale = 'English_Australia.utf8';
//dump(putenv("LANG=" . $language));
//dump(putenv("LANGUAGE=" . $language));
//dump(putenv("LC_ALL=" . $language));
//dump(putenv("LC_MESSAGES=" . $language));
//dump(setlocale(LC_ALL, $locale));
//
//// Set the text domain as "messages"
//$domain = "messages";
//dump(bindtextdomain($domain, __DIR__ . "/locale"));
//dump(bind_textdomain_codeset($domain, 'UTF-8'));
//
//dump(textdomain($domain));

setAppLocale('en');

dump(_('Ahoj světe'));

exit;
return $container;



