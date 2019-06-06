<?php

namespace Test\MockMysqld;

use PHPUnit\Framework\TestCase;
use MockMysqld\ProcessFactory;
use MockMysqld\Options;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;

class ProcessFactoryTest extends TestCase {
	public function testInvoke() {
		$options = new Options;
		$finder = $this->createMock(ExecutableFinder::class);
		$finder->method('find')->willReturn('test-mysqld-executable');

		$factory = new ProcessFactory($finder);
		$this->assertTrue(is_callable($factory));
		$process = call_user_func($factory, $options);
		$this->assertInstanceOf(Process::class, $process);

		$this->assertContains('test-mysqld-executable', $process->getCommandLine());

		foreach ($options as $name => $value) {
			$this->assertContains('--' . $name, $process->getCommandLine());
			$value && $this->assertContains($value, $process->getCommandLine());
		}
	}

	/**
	 * @expectedException \Exception
	 */
	public function testInvokeError() {
		$options = new Options;
		$finder = $this->createMock(ExecutableFinder::class);
		$finder->method('find')->willReturn(null);

		$factory = new ProcessFactory($finder);
		$this->assertTrue(is_callable($factory));
		$process = call_user_func($factory, $options);
	}
}
