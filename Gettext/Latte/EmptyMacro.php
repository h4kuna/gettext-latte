<?php

namespace h4kuna\Gettext\Latte;

class EmptyMacro implements \Nette\Latte\IMacro {

    public function finalize() {
        
    }

    public function initialize() {
        
    }

    public function nodeClosed(\Latte\MacroNode $node) {
        
    }

    public function nodeOpened(\Latte\MacroNode $node) {
        $node->isEmpty = $node->closing = TRUE;
        return TRUE;
    }

}
