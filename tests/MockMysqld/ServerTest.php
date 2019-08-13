<?php

namespace Test\MockMysqld;

use PHPUnit\Framework\TestCase;
use MockMysqld\Server;
use MockMysqld\Options;
use Symfony\Component\Process\Process;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;
use Symfony\Component\Process\PhpExecutableFinder;

class ServerTest extends TestCase {
	private $root;
	private $options;
	private $dbStruct = [
		'dir' => [
			'dir1' => ['file' => 'contents']
		]
	];

	public function setUp(): void {
		$this->root = vfsStream::setup('mockmysqld');
		vfsStream::create(['tmpdir' => [], 'datadir' => ['file' => '...']]);
		$this->options = new Options(
			[
				'tmpdir' => $this->root->getChild('tmpdir')->url(),
				'datadir' => $this->root->getChild('datadir')->url()
			]
		);
	}

	public function testInitDataDir() {
		$factory1 = function (Options $o) {
			$process = $this->createMock(Process::class);

			$process->method('mustRun')->will(
				$this->returnCallback(
					function () use ($o) {
						$this->assertArrayHasKey('initialize-insecure', $o);
						$this->assertEquals($this->root->getChild('datadir')->url() . '.tmp', $o['datadir']);

						vfsStream::create($this->dbStruct, $this->root->getChild(basename($o['datadir'])));
					}
				)
			);

			return $process;
		};


		(new Server($this->options, null, $factory1))->initDataDir();

		$this->assertEquals(
			$this->dbStruct,
			vfsStream::inspect(
				new vfsStreamStructureVisitor,
				$this->root->getChild('datadir')->getChild('dir')
			)->getStructure()
		);



		/* test repeated call */
		$process = $this->createMock(Process::class);
		$process->expects($this->exactly(0))->method('mustRun');
		(new Server($this->options, [], $this->getProcessFactory($process)))->initDataDir();



		/* test repeated call with changed not empty scripts */
		$struct = vfsStream::inspect(new vfsStreamStructureVisitor)->getStructure();
		vfsStream::create(['trash' => '...'], $this->root->getChild('datadir.tmp'));

		$factory3 = function (Options $o) {
			$process = $this->createMock(Process::class);

			$process->method('mustRun')->will(
				$this->returnCallback(
					function () use ($o) {
						$mergedScripts = file_get_contents($o['init-file']);
						$this->assertStringContainsString('script1', $mergedScripts);
						$this->assertStringContainsString('script2', $mergedScripts);
					}
				)
			);

			return $process;
		};

		(new Server($this->options, ['script1', 'script2'], $factory3))->initDataDir();

		$this->assertEquals(
			['datadir.tmp' => []],
			vfsStream::inspect(new vfsStreamStructureVisitor, $this->root->getChild('datadir.tmp'))->getStructure()
		);
	}

	public function testKillOldProcess() {
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('signaled');

		$php = (new PhpExecutableFinder)->find(false);
		$this->assertNotFalse($php);

		$process = new Process([$php, '-r', 'sleep(3);']);
		$process->start();
		$this->assertTrue($process->isRunning());

		file_put_contents($this->options['pid-file'], $process->getPid());

		(new Server($this->options, null, $this->getProcessFactory(null)))->killOldProcess();
		$process->wait();
	}

	public function testStop() {
		$process = $this->createMock(Process::class);
		$process->expects($this->once())->method('isRunning')->willReturn(true);
		$process->expects($this->once())->method('stop');
		(new Server(new Options, null, $this->getProcessFactory($process)))->stop();
	}

	public function startDataProvider() {
		return [
			'start() if not running' => [false, 1],
			'start() if already running' => [true, 0],
		];
	}

	/**
	 * @dataProvider startDataProvider
	 */
	public function testStart($isRunning, $expectedCount) {
		$process = $this->createMock(Process::class);
		$process->expects($this->once())->method('isRunning')->willReturn($isRunning);
		$process->expects($this->exactly($expectedCount))->method('start');

		$server = $this->getMockBuilder(Server::class)
				->setConstructorArgs([$this->options, null, $this->getProcessFactory($process)])
				->setMethods(['initDataDir', 'killOldProcess', 'waitForConnection'])
				->getMock();

		$server->expects($this->exactly($expectedCount))->method('initDataDir');
		$server->expects($this->exactly($expectedCount))->method('killOldProcess');
		$server->expects($this->exactly($expectedCount))->method('waitForConnection');

		$server->start();
	}

	public function testWaitForConnectionError() {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('timeout');

		$server = new Server($this->options, null, $this->getProcessFactory(null));
		$server->waitForConnection(1);
	}

	public function testWaitForConnection() {
		$php = (new PhpExecutableFinder)->find(false);
		$this->assertNotFalse($php);

		$command = [
			$php,
			__DIR__ . '/../../vendor/bin/phiremock',
			'--port',
			$this->options['port'],
		];

		$http = new Process($command);
		$http->start();

		$server = new Server($this->options, null, $this->getProcessFactory(null));
		$server->waitForConnection(10);
	}

	public function testSetInitTimeout() {
		$server = (new Server($this->options, null, $this->getProcessFactory(null)));
		$expected = $server->getInitTimeout() + 1;
		$server->setInitTimeout($expected);
		$this->assertEquals($expected, $server->getInitTimeout());
	}

	private function getProcessFactory($process) {
		return function (Options $o) use ($process) {
			return $process;
		};
	}
}
