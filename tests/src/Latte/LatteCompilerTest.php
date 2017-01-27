<?php

namespace h4kuna\Gettext\Latte;

use Tester,
	Tester\Assert;

$container = require_once __DIR__ . '/../../bootstrap.php';

class LatteCompilerTest extends Tester\TestCase
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
		Tester\Environment::skip();
		$compiler = $this->compiler;
		$path = self::getBasePath();
		$compiler->addInclude($path);
		$compiler->addExclude($path . '/example.latte');
		$compiler->addInclude($path);
		$compiler->run();
	}

	private static function getBasePath()
	{
		return __DIR__ . '/../../../examples';
	}

}

$factory = function() use ($container) {
	return $container->createService('gettextLatteExtension.compiler');
};

$test = new LatteCompilerTest($factory);
$test->run();
