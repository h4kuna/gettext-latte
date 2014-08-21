<?php

namespace h4kuna\Gettext\Latte;

use InvalidArgumentException;
use Latte\RuntimeException;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Bridges\ApplicationLatte\TemplateFactory;
use Nette\Latte\CompileException;
use Nette\Latte\Engine;
use Nette\UnexpectedValueException;
use Nette\Utils\Finder;
use SplFileInfo;

class LatteCompiler {

    /** @var array */
    private $mask = array('*.latte');

    /** @var Template */
    private $template;

    /** @var SplFileInfo[] */
    private $skippedFiles = array();

    /** @var SplFileInfo[] */
    private $files = array();

    /** @var string */
    private $temp;

    public function __construct(TemplateFactory $templateFactory) {
        $this->template = $templateFactory->createTemplate(new FakeControl());
        $this->temp = dirname($this->template->getLatte()->getCacheFile('foo'));
    }

    public function addMask($mask) {
        $this->mask[] = $mask;
    }

    public function addExclude($path) {
        $this->files = array_diff_key($this->files, $this->getFiles($path));
        return $this;
    }

    public function addInclude($path) {
        $this->files += $this->getFiles($path);
        return $this;
    }

    /**
     * 
     * @param string $path
     * @return SplFileInfo[]
     */
    private function getFiles($path) {
        $fileInfo = new SplFileInfo($path);
        if ($fileInfo->isFile()) {
            return array($fileInfo->getRealPath() => $fileInfo);
        }

        $found = array();
        $finder = call_user_func_array('\Nette\Utils\Finder::findFiles', $this->mask);
        foreach ($finder->from($fileInfo->getRealPath()) as $file) {
            $found[$file->getRealPath()] = $file;
        }
        return $found;
    }

    public function getTemp() {
        return $this->temp;
    }

    private function clearTemp() {
        /* @var $file SplFileInfo  */
        foreach (Finder::findFiles('*')->from($this->temp) as $file) {
            @unlink($file->getPathname());
        }
    }

    public function getTemplate() {
        return $this->template;
    }

    public function prepareFiles() {
        if ($this->skippedFiles) {
            $out = $this->skippedFiles;
            $this->skippedFiles = array();
            return $out;
        }

        return $this->files;
    }

    public function run() {
        error_reporting(E_ALL & ~(E_NOTICE));
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
            } catch (UnexpectedValueException $e) {
                // uninteresting
            } catch (InvalidArgumentException $e) {
                // uninteresting
            } catch (CompileException $e) {
                $find = NULL;
                if (!preg_match('/Unknown macro \{(.*)\}/U', $e->getMessage(), $find)) {
                    throw $e;
                }

                $macroName = $find[1];
                $this->template->getLatte()->onCompile[] = function(Engine $engine) use ($macroName) {
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
