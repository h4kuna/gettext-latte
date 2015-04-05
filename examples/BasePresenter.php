<?php

namespace h4kuna;

/**
 * How setup BasePresenter for support lang and automatic detect language
 *
 * @author Milan Matějček
 */
abstract class BasePresenter extends \Nette\Application\UI\Presenter {
    /**
     * PHP < 5.4 ***************************************************************
     * *************************************************************************
     */

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
     * or PHP 5.4+ *************************************************************
     * *************************************************************************
     */
    use \h4kuna\Gettext\InjectTranslator;
}
