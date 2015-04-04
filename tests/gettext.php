<?php

require __DIR__ . '/bootstrap.php';
// The only way I found to deal with windows is manually set locale at Control Panel \ Region \ Format
// by changin it to `German (Germany)` or `Ukrainian (Ukraine)` you can change output of script
// the only one nice thing is that changes apply immediatelly without reboots
Tracy\Debugger::enable(false);
$localeDir = __DIR__ . DIRECTORY_SEPARATOR . 'locale';
//
$lang = $locale = 'cs_CZ';
$locale = 'english';
//
//dump(putenv("LANGUAGE=$lang"));
//dump(putenv("LANG=$lang"));
dump(putenv("LC_ALL=$lang"));
dump(setlocale(LC_ALL, $locale));
$domain = 'messages';
dump(bindtextdomain($domain, $localeDir));
//dump(textdomain($domain));


//bind_textdomain_codeset($domain, 'UTF-8'); // seems to be optional if your encoding is utf
//textdomain($domain); // optional if `$domain == 'messages'`

dump(_('Ahoj světe'));
exit;

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
