<?php

namespace h4kuna\Gettext\Latte;

use Latte\RuntimeException;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Bridges\ApplicationLatte\TemplateFactory;
use Nette\Latte\CompileException;
use Nette\Utils\Finder;

class LatteCompiler {

    /** @var array */
    private $mask = array('*.latte');

    /** @var array */
    private $include;

    /** @var array */
    private $exclude;

    /** @var string */
    private $tempLatte;

    /** @var Template */
    private $template;

    /** @var array */
    private $skippedFiles = array();

    public function __construct(TemplateFactory $templateFactory, array $include) {
        $this->template = $templateFactory->createTemplate(new FakeControl());
        $this->tempLatte = dirname($this->template->getLatte()->getCacheFile('foo'));
        $this->include = $include;
    }

    public function setMask($mask) {
        $this->mask = $mask;
    }

    public function setExclude(array $exclude) {
        $this->exclude = $exclude;
    }

    private function clearTemp() {
        /* @var $file SplFileInfo  */
        foreach (Finder::findFiles('*')->from($this->tempLatte) as $file) {
            @unlink($file->getPathname());
        }
    }

    public function prepareFiles() {
        if ($this->skippedFiles) {
            $out = $this->skippedFiles;
            $this->skippedFiles = array();
            return $out;
        }
        $found = array();

        $finder = call_user_func_array('\Nette\Utils\Finder::findFiles', $this->mask);
        /* @var $file SplFileInfo */
        foreach (call_user_func_array(array(clone $finder, 'from'), $this->include) as $file) {
            $found[$file->getRealPath()] = $file;
        }

        if (!$this->exclude) {
            return $found;
        }

        foreach ($this->exclude as $k => $file) {
            if (is_file($file) && $path = realpath($file)) {
                unset($found[realpath($path)]);
                unset($this->exclude[$k]);
            } elseif (!file_exists($file)) {
                unset($this->exclude[$k]);
            }
        }

        if (!$this->exclude) {
            return $found;
        }

        foreach (call_user_func_array(array($finder, 'from'), $this->exclude) as $file) {
            unset($found[$file->getRealPath()]);
        }


        return $found;
    }

    public function run() {
        if (!$this->skippedFiles) {
            $this->clearTemp();
        }
        /* @var $file SplFileInfo */
        foreach ($this->prepareFiles() as $file) {

            try {
                echo $file->getPathname() . "\n";
                ob_end_clean();
                ob_start();
                $this->template->setFile($file->getPathname())->render();
            } catch (RuntimeException $e) {
                if (substr($e->getMessage(), 0, 30) !== 'Cannot include undefined block') {
                    throw $e;
                }
            } catch (InvalidArgumentException $e) {
// uninteresting
            } catch (CompileException $e) {
                $find = NULL;
                if (!preg_match('/Unknown macro \{(.*)\}/U', $e->getMessage(), $find)) {
                    throw $e;
                }

                $macroName = $find[1];
                $this->template->getLatte()->onCompile[] = function(Latte\Engine $engine) use ($macroName) {
                    $engine->addMacro($macroName, new EmptyMacro());
//goto checkFile;
                };
                $this->skippedFiles[] = $file;
            }
            ob_end_clean();
        }
        if ($this->skippedFiles) {
            $this->run();
        }
    }

}
