<?php

namespace h4kuna\Gettext\DI;

use Nette\PhpGenerator\ClassType;
use Nette\DI\CompilerExtension;
use Nette\Utils\Finder;

class GettextLatteExtension extends CompilerExtension {

    public $defaults = array(
        'langs' => array('cs' => 'cs_CZ.utf8', 'en' => 'en_US.utf8'),
        'dictionaryPath' => '%appDir%/../locale/',
        'session' => '+1 week'
    );

    public function loadConfiguration() {
        $builder = $this->getContainerBuilder();
        $config = $this->getConfig($this->defaults);

        // os
        $builder->addDefinition($this->prefix('os'))
                ->setClass('h4kuna\Gettext\Os');

        // dictionary
        $builder->addDefinition($this->prefix('dictionary'))
                ->setClass('h4kuna\Gettext\Dictionary')
                ->setArguments(array($config['dictionaryPath'], '@cacheStorage'));

        // setup
        $setup = $builder->addDefinition($this->prefix('setup'))
                ->setClass('h4kuna\GettextSetup')
                ->setArguments(array($config['langs'], $this->prefix('@dictionary'), $this->prefix('@os')));

        if ($config['session'] !== NULL && $config['session'] !== FALSE) {
            $setup->addSetup('setSession', array($builder->getDefinition('session'), $config['session']));
        }

        $latte = $builder->getDefinition('nette.latteFactory');
        $latte->addSetup('?->onCompile[] = function($engine) { h4kuna\Gettext\Macros\Latte::install($engine->getCompiler()); }', array('@self'));

        // $engine = $builder->getDefinition('nette.latte');
        // $engine->addSetup('h4kuna\Gettext\Macros\Latte::install(?->getCompiler(), ?)', array('@self', $this->prefix('@setup')));
    }

    public function afterCompile(ClassType $class) {
        /**
         * old template must regenerate
         * if you use translate macro {_''} and after start this extension, you will see only exception
         * Nette\MemberAccessException
         * Call to undefined method Nette\Templating\FileTemplate::translate()
         * let's clear temp directory
         * _Nette.FileTemplate
         */
        $temp = $this->containerBuilder->parameters['tempDir'] . '/cache/latte';
        if (file_exists($temp) && $this->containerBuilder->parameters['debugMode']) {
            foreach (Finder::find('*')->in($temp) as $file) {
                /* @var $file \SplFileInfo */
                @unlink($file->getPathname());
            }
        }
    }

}
