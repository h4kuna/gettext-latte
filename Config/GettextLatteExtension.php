<?php

namespace h4kuna\Config;

use Nette;

class GettextLatteExtension extends Nette\Config\CompilerExtension {

    public $defaults = array(
        'langs' => array('cs' => 'cs_CZ.utf8', 'en' => 'en_US.utf8'),
        'localePath' => '%wwwDir%/../locale/',
        'session' => 'gettextLatte',
    );

    public function loadConfiguration() {
        $builder = $this->getContainerBuilder();
        $config = $this->getConfig($this->defaults);

        $client = $builder->addDefinition($this->prefix('translator'))
                ->setClass('h4kuna\Gettextlatte')
                ->setArguments(array($config['localePath'], $config['langs']));

        if ($config['session']) {
            $builder->addDefinition($this->prefix('session'))
                    ->setClass('Nette\Http\SessionSection')
                    ->setArguments(array('@session', $config['session']));
            $client->addSetup('setSection', $this->prefix('@session'));
        }

        $engine = $builder->getDefinition('nette.latte');
        $install = 'h4kuna\Macros\Latte::install(?->getCompiler(), ?)';
        $engine->addSetup($install, '@self', $this->prefix('@translator'));
    }

    public function afterCompile(Nette\Utils\PhpGenerator\ClassType $class) {
        /**
         * old template must regenerate
         * if you use translate macro {_''} and after start this extension, you will see only exception
         * Nette\MemberAccessException
         * Call to undefined method Nette\Templating\FileTemplate::translate()
         * let's clear temp directory
         */
        $temp = $this->containerBuilder->parameters['tempDir'] . '/cache/_Nette.FileTemplate';
        if (file_exists($temp) && $this->containerBuilder->parameters['debugMode']) {
            foreach (Nette\Utils\Finder::find('*')->in($temp) as $file) {
                //$file = new \SplFileInfo;
                @unlink($file->getPathname());
            }
        }
    }

    /**
     * @param \Nette\Configurator $configurator
     */
    public static function register(Nette\Config\Configurator $configurator) {
        $that = new static;
        $configurator->onCompile[] = function ($config, Nette\Config\Compiler $compiler) use ($that) {
                    $compiler->addExtension('gettextLatte', $that);
                };
    }

}