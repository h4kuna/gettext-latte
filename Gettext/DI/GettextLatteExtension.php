<?php

namespace h4kuna\Gettext\DI;

use Nette\PhpGenerator\ClassType;
use Nette\DI\CompilerExtension;
use Nette\Configurator;
use Nette\Utils\Finder;
use Nette\DI\Compiler;

class GettextLatteExtension extends CompilerExtension {

    public $defaults = array(
        'langs' => array('cs' => 'cs_CZ.utf8', 'en' => 'en_US.utf8'),
        'dictionaryPath' => '%appDir%/../locale/',
        'session' => 'gettextLatte',
        'development' => '%debugMode%',
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
        $builder->addDefinition($this->prefix('setup'))
                ->setClass('h4kuna\GettextSetup')
                ->setArguments(array($config['langs'], $this->prefix('@dictionary'), $this->prefix('@os')));



//        if ($config['session']) {
//            $builder->addDefinition($this->prefix('session'))
//                    ->setClass('Nette\Http\SessionSection')
//                    ->setArguments(array('@session', $config['session']));
//            $client->addSetup('setSection', array($this->prefix('@session')));
//        }
        // $engine = $builder->getDefinition('nette.latte');
        // $engine->addSetup('h4kuna\GettextLatte\Macros\Latte::install(?->getCompiler(), ?)', array('@self', $this->prefix('@translator')));
    }

    public function afterCompile(ClassType $class) {
        /**
         * old template must regenerate
         * if you use translate macro {_''} and after start this extension, you will see only exception
         * Nette\MemberAccessException
         * Call to undefined method Nette\Templating\FileTemplate::translate()
         * let's clear temp directory
         */
//        $temp = $this->containerBuilder->parameters['tempDir'] . '/cache/_Nette.FileTemplate';
//        if (file_exists($temp) && $this->containerBuilder->parameters['debugMode']) {
//            foreach (Finder::find('*')->in($temp) as $file) {
//                //$file = new \SplFileInfo;
//                @unlink($file->getPathname());
//            }
//        }
    }

}
