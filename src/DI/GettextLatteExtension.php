<?php

namespace h4kuna\Gettext\DI;

use Nette\PhpGenerator\ClassType;
use Nette\DI\CompilerExtension;
use Nette\Utils\Finder;

class GettextLatteExtension extends CompilerExtension {

    public $defaults = array(
        'langs' => array(),
        'dictionaryPath' => '%appDir%/../locale/',
        'session' => '+1 week',
        'loadAllDomains' => 'messages',
        'localeTranslate' => array(
            'en_US' => 'English_United_States',
            'en_EN' => 'English_United_Kingdom',
            'de_DE' => 'German_Standard',
            'sk_SK' => 'Slovak',
            'cs_CZ' => 'Czech',
            'it_IT' => 'Italian_Standard'
        )
    );

    public function loadConfiguration() {
        $builder = $this->getContainerBuilder();

        $config = $this->getConfig($this->defaults);
        if (!$config['langs']) {
            $config['langs'] = array('cs' => 'cs_CZ.utf8', 'en' => 'en_US.utf8');
        }
        // os
        $builder->addDefinition($this->prefix('os'))
                ->setClass('h4kuna\Gettext\Os')
                ->setArguments(array($config['localeTranslate']));

        // dictionary
        $builder->addDefinition($this->prefix('dictionary'))
                ->setClass('h4kuna\Gettext\Dictionary')
                ->setArguments(array($config['dictionaryPath'], '@cacheStorage'));

        // setup
        $gettext = $builder->addDefinition($this->prefix('gettext'))
                ->setClass('h4kuna\Gettext\GettextSetup')
                ->setArguments(array($config['langs'], $this->prefix('@dictionary'), $this->prefix('@os')));

        if ($config['loadAllDomains']) {
            $gettext->addSetup('loadAllDomains', array($config['loadAllDomains']));
        }

        if ($config['session'] !== NULL && $config['session'] !== FALSE && PHP_SAPI != 'cli') {
            $gettext->addSetup('setSession', array($builder->getDefinition('session'), $config['session']));
        }

        // compiler
        $builder->addDefinition($this->prefix('compiler'))
                ->setClass('h4kuna\Gettext\Latte\LatteCompiler')
                ->setArguments(array($builder->getDefinition('nette.templateFactory')));

        $latte = $builder->getDefinition('nette.latteFactory');
        $latte->addSetup('?->onCompile[] = function($engine) { h4kuna\Gettext\Macros\Latte::install($engine->getCompiler()); }', array('@self'));
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
