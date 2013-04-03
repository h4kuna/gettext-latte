<?php

namespace h4kuna;

//----------------- router setup -----------------------------------------------
$router[] = new R\Route('[<lang ' . $container->translator->routerAccept() . '>/]<presenter>/<action>/[<id>/]', array(
    'presenter' => 'Homepage',
    'action' => 'default',
    'lang' => $container->translator->getDefault()
        ));
//------------------------------------------------------------------------------

/**
 * How setup BasePresenter for support lang and automatic detect language
 *
 * @author Milan Matějček
 */
abstract class BasePresenter extends \Nette\Application\UI\Presenter {

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

    /**
     * example how to use in Presenter or everytime without latte template
     * @return \Nette\Application\UI\Form
     */
    public function createComponentForm() {
        $form = new \Nette\Application\UI\Form;
        // you don't register translator
        $form->addText('name', _('Jméno'))->getControlPrototype()->placeholder(_('Jméno'));
        $form->addText('email', _('E-mail'))->addRule(\Nette\Application\UI\Form::EMAIL, _('Vložte validní meailovou adresu.'));
        $form->addText('zip', _('PSČ'))->addRule(\Nette\Application\UI\Form::MIN_LENGTH, _('Minimální počet je %s znaků.'), 5);
        $data = array();
        for ($i = 0; $i < 6; $i++) {
            // druhý parametr vyplňte libovolně, protože pro jazyky, které mají více skloňovacích stupňu musíte provest překlad přes slovník
            $data[$i] = sprintf(ngettext('%s pes', '%s pes', $i), $i);
        }
        $form->addRadioList("dog", _('Počet'), $data);
        $form->addSubmit('send', _('Uložit'));
        return $form;
    }

}

