GettextLatte
===========

is localization addon for framework [Nette](http://nette.org/), whose support native [gettext](http://php.net/manual/en/book.gettext.php).  
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
```

Section **factories** install new macro to latte engine. Where are alias for native gettext function [{_'' /*, ...*/}](http://www.php.net/manual/en/function.gettext.php) and [{_n'', '', '' /*, ...*/}](http://www.php.net/manual/en/function.ngettext.php).

```
factories:
    nette.latte:
        factory: \h4kuna\GettextLatte::latte
```
Run service
-------------------
Load dictionary as soon as possible.

```php
<?php
use Nette\Application\UI\Presenter;

abstract class BasePresenter extends Presenter {

    /** @persistent */
    public $lang;

    protected function startup() {
        parent::startup();
        $this->context->translator->setLanguage($this->lang);
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
echo srintf(_('%s possible %s %s'), 'another', 'optional', 'params'); // is faster
echo $this->context->translator->translate(_('%s possible %s %s'), 'another', 'optional', 'params');

```

In template you using macros. Number of parameters is't limited. Function **sprintf** is automatically added.

<table>
<tr>
<th>macro in template</th><th>translate to php</th>
</tr>
<tr>
<td>{!_'Hi'}</td><td>echo gettext('Hi');</td>
</tr>
<tr>
<td>{!_'Today is %s', $date}</td><td>echo sprintf(gettext('Today is %s'), $date);</td>
</tr>
<tr>
<td>{!_n'dog', 'dogs', $count}</td><td>echo ngettext('dog', 'dogs', $count);</td>
</tr>
<tr>
<td>{!_n'%s dog has email %s', '%s dogs have email %s', $count, $email}</td><td>echo sprintf(ngettext('%s dog', '%s dogs', $count), $count, $email);</td>
</tr>
<tr>
<th colspan="2">If you need to decline to negative, variable must contain abs.</th>
</tr>
<tr>
<td>{!_n'today is %s degree temperature', 'today is %s degrees temperature', $absTemperature}</td><td>echo sprintf(ngettext('today is %s degree temperature', 'today is %s degrees temperature', abs($absTemperature)), $absTemperature);</td>
</tr>
</table>

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
<php
$this->context->translator->download('cs'); //Offers catalog download
```
