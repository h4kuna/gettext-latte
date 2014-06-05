<?php

namespace h4kuna\Gettext;

trait InjectTranslator {

    /** @persistent */
    public $lang;

    /** @var \h4kuna\GettextSetup @inject */
    public $translator;

    protected function startup() {
        parent::startup();
        $this->lang = $this->translator->loadLanguage($this->lang);
    }

}
