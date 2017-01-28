<?php

namespace h4kuna\Gettext\Latte;

use Tester\Assert;

$container = include __DIR__ . '/../../bootstrap.php';

class LatteCompilerTest extends \Tester\TestCase
{

	private $factory;

	/** @var LatteCompiler */
	private $compiler;

	public function __construct($factory)
	{
		$this->factory = $factory;
	}

	protected function setUp()
	{
		$factory = $this->factory;
		$this->compiler = $factory();
	}

	public function testCompile()
	{
		$compiler = $this->compiler;
		$path = self::getBasePath();
		$compiler->addInclude($path);
		$compiler->addExclude($path . '/example.latte');
		$compiler->addInclude($path);
		$compiler->run();
		Assert::true(true); // no exception
	}

	private static function getBasePath()
	{
		return __DIR__;
	}

}

$factory = function() use ($container) {
	return $container->createService('gettextLatteExtension.compiler');
};

(new LatteCompilerTest($factory))->run();
