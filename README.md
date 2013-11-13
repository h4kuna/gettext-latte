GettextLatte
===========

is localization addon for [Nette framework](http://nette.org/), which natively supports [gettext](http://php.net/manual/en/book.gettext.php).

You can use it without Nette if you use Gettext.php.

[Forum](http://forum.nette.org/cs/12021-gettext-na-100-v-sablonach#p86467)

Conditions for start-up
----------------------
* gettext extension enabled
* language installed on server, you can check by using command **$ locale -a**
* your application written in UTF-8 encoding

In the repository is directory _locale_ containing prepared directory structure for your project and it needs permision 777 _locale/*_. In folder _example_ are files, whose help you setup this translator. You can write your application in your native language, here is example in english, but it may be czech, slovak, german language...

Start-up
---------------------
Clone this repository or use composer [h4kuna/gettext-latte](https://packagist.org/packages/h4kuna/gettext-latte).

And install in bootstrap.php
```php
\h4kuna\DI\GettextLatteExtension::register($configurator);
```

Look into _examples/RouterFactory.php_.

Example for router setup from [nette sandbox](https://github.com/nette/sandbox/blob/master/app/router/RouterFactory.php);
```php
/**
 * @return Nette\Application\IRouter
 */
public function createRouter(\h4kuna\GettextLatte $translator) {
    $router = new RouteList();
    $router[] = new Route('index.php', 'Homepage:default', Route::ONE_WAY);
    $router[] = new Route('[<lang ' . $translator->routerAccept() . '>/]<presenter>/<action>/[<id>/]', array(
        'presenter' => 'Homepage',
        'action' => 'default',
        'lang' => $translator->getDefault()
    ));

    return $router;
}
```

### examples/config.neon
There are three optional **variables**.

On Mac encoding is represented as 'en_US.UTF-8' everytime dojo format 'en_US.utf8'.
```
gettextLatte:
    localePath: %wwwDir%/anotherPath/
    # default is %wwwDir%/../locale/

    langs: {'cs' : 'cs_CZ.utf8', 'en' : 'en_US.utf8', 'de' : 'de_DE.utf8', 'it' : 'it_IT.utf8'}
    # default is cs and en - first language is considered to be default

    session: FALSE
    #default is ON
```

Install new macro to latte engine with alias for native gettext function [{_'' /*, ...*/}](http://www.php.net/manual/en/function.gettext.php) and [{_n'', '', '' /*, ...*/}](http://www.php.net/manual/en/function.ngettext.php).


Optional setup, where you can register callbacks and helpers
-------------------
enable only for default language, because it is used in compile time
```
translator:
    class: \h4kuna\GettextLatte(%appDir%/../locale/, %langs%)
    setup:
        - enableOrphans # look at addMacro()
```
or add helper after escape, applied for all language and you must register helper orphans
```
translator:
    class: \h4kuna\GettextLatte(%appDir%/../locale/, %langs%)
    setup:
        - addHelper('orphans')
```

Run service and support automatic detection of language
-------------------
Load language as soon as possible.

```php
<?php
use Nette\Application\UI\Presenter;

abstract class BasePresenter extends Presenter {

    /** @persistent */
    public $lang;

    /** @var \h4kuna\GettextLatte */
    protected $translator;

    /**
     * Inject translator
     * @param \h4kuna\GettextLatte
     */
    public function injectTranslator(\h4kuna\GettextLatte $translator) {
        $this->translator = $translator;
    }

    protected function startup() {
        parent::startup();
        $this->lang = $this->translator->loadLanguage($this->lang);
    }
}
```

After install translator please empty temp directory, otherwise you may get "Call to undefined method Nette\Templating\FileTemplate::translate()".

How to write texts
---------------
Outside the template using gettext.

```php
<?php
echo gettext('Hi'); //or alias _
echo _('Hi');
echo ngettext('dog', 'dogs', 2);

// The following two are the same
echo sprintf(_('%s possible %s %s'), 'another', 'optional', 'params'); // is faster
echo $this->translator->translate(_('%s possible %s %s'), 'another', 'optional', 'params');

```

In template using macros. Number of parameters isn't limited. Function **sprintf** is automatically added. Look at examples/example.latte

<table>
<tr>
<th>macro in template</th><th>translate to php</th>
</tr>
<tr>
<td>{_'Hi'}</td><td>echo gettext('Hi');</td>
</tr>
<tr>
<td>{_'Today is %s', $date}</td><td>echo sprintf(gettext('Today is %s'), $date);</td>
</tr>
<tr>
<th colspan="2">In the previous version, the inflection was written like this.</th>
</tr>
<tr>
<td>{_n'dog', 'dogs', $count}</td><td>echo ngettext('dog', 'dogs', $count);</td>
</tr>
<tr>
<th colspan="2">Now it's off and is written following way.* (It is possible to turn on with third parameter in constructor.)</th>
</tr>
<tr>
<td>{_n'dog', $count}</td><td>echo ngettext('dog', 'dog', $count);</td>
</tr>
<tr>
<td>{_n'%s dog has email %s', $count, $email}</td><td>echo sprintf(ngettext('%s dog has email %s', '%s dog has email %s', $count), $count, $email);</td>
</tr>
<tr>
<th colspan="2">If you need to decline to negative, variable must contain abs.</th>
</tr>
<tr>
<td>{_n'today is %s degree temperature', $absTemperature}</td><td>echo sprintf(ngettext('today is %s degree temperature', 'today is %s degree temperature', abs($absTemperature)), $absTemperature);</td>
</tr>
<tr>
<th colspan="2">If you have many variables and replace the first variable not governed translation variable must contain plural.</th>
</tr>
<tr>
<td>{_n'Name: %s, age: %s year old', $name, $pluralYear}</td><td>echo sprintf(ngettext('Name: %s, age: %s year old', 'Name: %s, age: %s year old', $pluralYear), $name, $pluralYear);</td>
</tr>
</table>

\* It was changed, because inflection is defined in catalog everytime, for language whose has more than 2 level inflection.

Hack in Latte
-------------
Maybe you want translate sentence containing many apostrophes and quotation marks in latte file.

Forexample:
```php
{* not function *}
{_'We believe in \'streading lightly\', whether on foot, bicycle, horseback or venturing below the ocean\'s surface.'}
Rewrite like this
{=_('We believe in \'streading lightly\', whether on foot, bicycle, horseback or venturing below the ocean\'s surface.')}
```

Let's start translate
---------------------
Download [PoEdit](http://www.poedit.net/download.php).
Before each Poedit run you must have all templates compiled to php in temp directory, for this is _examples/TemplatePresenter.php_ and run **actionTranslate()**.

Open the **.po** file. Setup directory search - by default it is **temp/cache/_Nette.FileTemplate** and **app**  and click "update catalog", after update catalog you don't need [restart apache](http://php.net/manual/en/function.gettext.php#110735).


If you write application in language with three inflection levels instead of two, for example czech, you must have catalog with translation czech to czech but only for plural.

Downloadable catalog
---------------
For your translators you provide catalog for download.

```php
$this->translator->download('cs'); //Offers catalog download
```