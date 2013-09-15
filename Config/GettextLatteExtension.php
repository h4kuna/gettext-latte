<?php

namespace h4kuna\Config;

use Nette\PhpGenerator\ClassType;
use Nette\DI\CompilerExtension;
use Nette\Configurator;
use Nette\Utils\Finder;
use Nette\DI\Compiler;

if (!defined('\Nette\Framework::VERSION_ID') || \Nette\Framework::VERSION_ID < 20100) {
    class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
    class_alias('Nette\Utils\PhpGenerator\ClassType', 'Nette\PhpGenerator\ClassType');
    class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
}

class GettextLatteExtension extends CompilerExtension {

    public $defaults = array(
        'langs' => array('cs' => 'cs_CZ.utf8', 'en' => 'en_US.utf8'),
        'localePath' => '%wwwDir%/../locale/',
        'session' => 'gettextLatte',
    );

    public function loadConfiguration() {
        $builder = $this->getContainerBuilder();
        $config = $this->getConfig($this->defaults);

        $client = $builder->addDefinition($this->prefix('translator'))
                ->setClass('h4kuna\GettextLatte')
                ->setArguments(array($config['localePath'], $config['langs']));

        if ($config['session']) {
            $builder->addDefinition($this->prefix('session'))
                    ->setClass('Nette\Http\SessionSection')
                    ->setArguments(array('@session', $config['session']));
            $client->addSetup('setSection', $this->prefix('@session'));
        }

        $engine = $builder->getDefinition('nette.latte');
        $install = 'h4kuna\Macros\Latte::install(?->getCompiler())->setTranslator(?)';
        $engine->addSetup($install, array('@self', $this->prefix('@translator')));
    }

    public function afterCompile(ClassType $class) {
        /**
         * old template must regenerate
         * if you use translate macro {_''} and after start this extension, you will see only exception
         * Nette\MemberAccessException
         * Call to undefined method Nette\Templating\FileTemplate::translate()
         * let's clear temp directory
         */
        $temp = $this->containerBuilder->parameters['tempDir'] . '/cache/_Nette.FileTemplate';
        if (file_exists($temp) && $this->containerBuilder->parameters['debugMode']) {
            foreach (Finder::find('*')->in($temp) as $file) {
                //$file = new \SplFileInfo;
                @unlink($file->getPathname());
            }
        }
    }

    /**
     * @param \Nette\Configurator $configurator
     */
    public static function register(Configurator $configurator) {
        $that = new static;
        $configurator->onCompile[] = function ($config, Compiler $compiler) use ($that) {
                    $compiler->addExtension('gettextLatte', $that);
                };
    }

}