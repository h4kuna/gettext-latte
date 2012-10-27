<?php

namespace h4kuna;

use Nette,
    Nette\Caching;

/**
 * Split compilation and rendering
 */
class Template extends \Nette\Templating\FileTemplate {

    /**
     * @return string
     * @throws Nette\InvalidStateException
     * @throws \Nette\Templating\FilterException
     */
    public function compileTemplate() {
        if ($this->getFile() == NULL) { // intentionally ==
            throw new Nette\InvalidStateException("Template file name was not specified.");
        }

        $cache = new Caching\Cache($storage = $this->getCacheStorage(), 'Nette.FileTemplate');
        if ($storage instanceof Caching\Storages\PhpFileStorage) {
            $storage->hint = str_replace(dirname(dirname($this->getFile())), '', $this->getFile());
        }
        $cached = $compiled = $cache->load($this->getFile());

        if ($compiled === NULL) {
            try {
                $compiled = "<?php\n\n// source file: {$this->getFile()}\n\n?>" . $this->compile();
            } catch (FilterException $e) {
                $e->setSourceFile($this->getFile());
                throw $e;
            }

            $cache->save($this->getFile(), $compiled, array(
                Caching\Cache::FILES => $this->getFile(),
                Caching\Cache::CONSTS => 'Nette\Framework::REVISION',
            ));
            $cached = $cache->load($this->getFile());
        }

        $storage = $this->getCacheStorage();
        if ($cached !== NULL && $storage instanceof Caching\Storages\PhpFileStorage) {
            return $cached;
        }
        return $compiled;
    }

    /**
     * Renders template to output.
     * @return void
     */
    public function render() {
        $source = $this->compileTemplate();
        if (is_array($source)) {
            Nette\Utils\LimitedScope::load($source['file'], $this->getParameters());
        } else {
            Nette\Utils\LimitedScope::evaluate($source, $this->getParameters());
        }
    }

}

