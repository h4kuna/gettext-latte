GettextLatte
===========

is localization addon for [Nette framework](http://nette.org/), whose support native [gettext](http://php.net/manual/en/book.gettext.php).

You can use without Nette if you use Gettext.php.

[Forum](http://forum.nette.org/cs/12021-gettext-na-100-v-sablonach#p86467)

Conditions for start-up
----------------------
* gettext extension enabled
* language instaled on server, you can check the command **$ locale -a**
* your application written in UTF-8 encoding

In repository is directory _locale_, where is prepared directory structure for your project and it need permision 777 _locale/*_. In folder _example_ are files, whose help you setup this translator. Application, you can write in your native language, here is example in english, but it may be czech, slovak, german language...

Start-up
---------------------
Clone of this repository or you can use composer [h4kuna/gettext-latte](https://packagist.org/packages/h4kuna/gettext-latte).

### examples/config.neon
There are three section **parameters**, where do you define all your languages. Key is web presentation and value in array is value of statement command above **$ locale -a**. First language in array is defined as default.

On Mac encoding is represented as 'en_US.UTF-8' everytime dojo format 'en_US.utf8'.
```
parameters:
    langs: {'cs' : 'cs_CZ.utf8', 'en' : 'en_US.utf8'}
```

Section **services** has two parametrs. First is path to _locale_ directory and second is array above.

```
services:
    translator:
        class: \h4kuna\GettextLatte(%appDir%/../locale/, %langs%)
        setup:
            - setSection(@sessionSection('translator')) # optional, enable automatic language detection
```

Section **factories** install new macro to latte engine. Where are alias for native gettext function [{_'' /*, ...*/}](http://www.php.net/manual/en/function.gettext.php) and [{_n'', '', '' /*, ...*/}](http://www.php.net/manual/en/function.ngettext.php).

```
factories:
    nette.latte:
        factory: \h4kuna\GettextLatte::latte

    sessionSection: # optional
        parameters: [section]
        class: \Nette\Http\SessionSection(@session, %section%)
```

Optional setup, where you can register callbacks and helpers
-------------------
enable only for default language, because it use in compile time
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
Example for setup router and BasePresenter is in _examples/BasePresenter.php_.

Router:
```php
$router[] = new R\Route('[<lang ' . $container->translator->routerAccept() . '>/]<presenter>/<action>/[<id>/]', array(
            'presenter' => 'Homepage',
            'action' => 'default',
            'lang' => $container->translator->getDefault()
        ));
```

Load dictionary as soon as possible.

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
How write texts
---------------
Out of the template you are using gettext.

```php
<?php
echo gettext('Hi'); //or alias _
echo _('Hi');
echo ngettext('dog', 'dogs', 2);

// The following two are the same
echo sprintf(_('%s possible %s %s'), 'another', 'optional', 'params'); // is faster
echo $this->context->translator->translate(_('%s possible %s %s'), 'another', 'optional', 'params');

```

In template you using macros. Number of parameters is't limited. Function **sprintf** is automatically added.

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
<th colspan="2">In the previous version, the inflection wrote like this.</th>
</tr>
<tr>
<td>{_n'dog', 'dogs', $count}</td><td>echo ngettext('dog', 'dogs', $count);</td>
</tr>
<tr>
<th colspan="2">Now it's off and writes.* But it is possible to turn on with third parameter in constructor.</th>
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

Let's starting translate
---------------------
Download [PoEdit](http://www.poedit.net/download.php).
Before each run Poedit you must have all template compiled to php in temp directory, for this is _examples/TemplatePresenter.php_ and run **actionTranslate()**.

You open **.po** file. Setup directory search by default in repository are **temp/cache/_Nette.FileTemplate** and **app**. And click "update catalog", after update catalog you don't need [restart apache](http://php.net/manual/en/function.gettext.php#110735).


If you write application in language whose has three levels instead of two inflections, forexample czech. You must have catalog with translation czech to czech but only for plural.

Download catalog
---------------
For your translators can do catalog for download.

```php
$this->context->translator->download('cs'); //Offers catalog download
```
