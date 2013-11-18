<?php

namespace h4kuna;

class TemplatePresenter extends BasePresenter {

//------------------------------------------------------------------------------
    private $classTemplate = NULL;

    /**
     * vygeneruje sablony
     */
    public function startup() {
        //clear directory
        $temp = $this->context->parameters['tempDir'] . '/cache/_Nette.FileTemplate';
        foreach (\Nette\Utils\Finder::findFiles('*')->from($temp) as $file) {
            unlink($file->getPathname());
        }

        // buil template from appDir
        $this->classTemplate = '\h4kuna\Template';
        $tpl = $this->template;
        foreach (\Nette\Utils\Finder::findFiles('*.latte')->from($this->context->parameters['appDir']) as $file) {
            $tpl->setFile($file->getPathname());
            $tpl->compileTemplate();
        }
        $this->terminate();
    }

    protected function createTemplate($class = NULL) {
        if ($this->classTemplate) {
            $class = $this->classTemplate;
        }
        return \Nette\Application\UI\Control::createTemplate($class);
    }

}
