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
        $this->lang = $translator->loadLanguage($this->lang);
    }
}

