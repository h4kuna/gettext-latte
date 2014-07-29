<?php

namespace h4kuna\Gettext;

use h4kuna\GettextSetup;

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
        $this->lang = $this->translator->loadLanguage($this->lang);
    }

}
