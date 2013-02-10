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

    protected function startup() {
        parent::startup();
        $this->lang = $this->context->translator->setLanguage($this->lang)->getLanguage();
    }

}

