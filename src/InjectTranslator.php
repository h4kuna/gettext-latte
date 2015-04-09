<?php

namespace h4kuna\Gettext;

/**
 * @author Milan Matějček
 * @version PHP 5.4+
 */
trait InjectTranslator {

    /** @persistent */
    public $lang;

    /** @var GettextSetup */
    protected $translator;

    public function injectGettexSetup(GettextSetup $translator) {
        $this->translator = $translator;
    }

    protected function startup() {
        parent::startup();
        $this->lang = $this->translator->setLanguage($this->lang);
    }

}
