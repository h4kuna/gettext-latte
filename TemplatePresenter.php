<?php

class TemplatePresenter extends BasePresenter {

//------------------------------------------------------------------------------
    private $classTemplate = NULL;

    /**
     * vygeneruje sablony
     */
    public function actionTranslate() {
        // buil template from appDir
        $this->classTemplate = '\h4kuna\Template';
        $tpl = $this->template;
        foreach (\Nette\Utils\Finder::findFiles('*.latte')->from($this->context->parameters['appDir']) as $file) {
            $tpl->setFile($file->getPathname());
            $tpl->render();
        }
        $this->terminate();
    }

    protected function createTemplate($class = NULL) {
        if ($this->classTemplate) {
            $class = $this->classTemplate;
        }
        return parent::createTemplate($class);
    }

}