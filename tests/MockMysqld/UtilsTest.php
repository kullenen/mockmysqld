<?php

namespace Test\MockMysqld;

use PHPUnit\Framework\TestCase;
use MockMysqld\Utils;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;

class UtilsTest extends TestCase {
	private $root;

	public function setUp() {
		$this->root = vfsStream::setup('mockmysqld');
	}

	public function ensureTraversableProvider() {
		return [
			[[], null],
			[[], []],
			[[1,2,3], [1,2,3]],
			[['a' => 1, 'b' => 2], ['a' => 1, 'b' => 2]],
			[[], new \ArrayIterator],
			[['c' => 1, 'd' => 2], new \ArrayIterator(['c' => 1, 'd' => 2])],
		];
	}

	/**
	 * @dataProvider ensureTraversableProvider
	 */
	public function testEnsureTraversable($expected, $input) {
		$result = Utils::ensureTraversable($input);
		$this->assertInstanceOf(\Traversable::class, $result);
		$this->assertEquals($expected, iterator_to_array($result));
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testEnsureTraversableError() {
		Utils::ensureTraversable(new \stdClass);
	}

	public function testPath() {
		$this->assertEquals('a'. DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'c', Utils::path(['a','b','c']));
	}

	public function testEnsureDir() {
		$dir =  $this->root->url() . DIRECTORY_SEPARATOR . 'dir1';

		$this->assertDirectoryNotExists($dir);
		$this->assertDirectoryExists(Utils::ensureDir($dir));

		$dir =  $this->root->url() . DIRECTORY_SEPARATOR . 'dir2';
		mkdir($dir);
		$this->assertDirectoryExists($dir);
		$this->assertDirectoryExists(Utils::ensureDir($dir));
	}

	public function testRunInitAction() {
		$markFile = $this->root->url() . DIRECTORY_SEPARATOR . 'mark';
		$callback = function ($a) {
			$this->abc = $a;
		};

		$this->assertFileNotExists($markFile);

		$this->abc = '';
		$this->assertTrue(Utils::runInitAction($markFile, 'contents1', $callback, ['abc']));
		$this->assertEquals('abc', $this->abc);
		$this->assertFileExists($markFile);
		$this->assertEquals('contents1', file_get_contents($markFile));

		$this->abc = '';
		$this->assertFalse(Utils::runInitAction($markFile, 'contents1', $callback, ['abc']));
		$this->assertEquals('', $this->abc);

		$this->abc = '';
		$this->assertTrue(Utils::runInitAction($markFile, 'contents2', $callback, ['abc']));
		$this->assertEquals('abc', $this->abc);
		$this->assertEquals('contents2', file_get_contents($markFile));
	}

	public function testCopyDir() {
		$old =  [
			'source' => [
				'dir1' => [
					'file1' => 's11',
					'file3' => 's33',
				],
				'dir2' => [],
				'file1' => 's1',
				'file3' => 's3',
			],
			'dest' => [
				'dir1' => [
					'file1' => 'd11',
					'file2' => 'd22',
				],
				'file1' => 'd1',
				'file2' => 'd2',
				'file3' => 's3',
			]
		];

		$new =  $old;
		$new['dest'] = [
			'dir1' => [
				'file1' => 's11',
				'file2' => 'd22',
				'file3' => 's33',
			],
			'dir2' => [],
			'file1' => 's1',
			'file2' => 'd2',
			'file3' => 's3',
		];

		vfsStream::create($old);

		Utils::copyDir($this->root->getChild('source')->url(), $this->root->getChild('dest')->url());

		$this->assertEquals(
			[$this->root->getName() => $new],
			vfsStream::inspect(new vfsStreamStructureVisitor)->getStructure()
		);

		$new0 =  $new;
		$new0['dest0'] = $new['source'];

		Utils::copyDir($this->root->getChild('source')->url(), $this->root->url() . DIRECTORY_SEPARATOR . 'dest0');

		$this->assertEquals(
			[$this->root->getName() => $new0],
			vfsStream::inspect(new vfsStreamStructureVisitor)->getStructure()
		);
	}

	public function testRemoveDir() {
		$structure =  [
			'dir' => [
				'dir1' => [
					'file1' => 's11',
					'file3' => 's33',
				],
				'dir2' => [
					'dir' => ['file' => '']
				],
				'file1' => 's1',
				'file3' => 's3',
			],
		];

		vfsStream::create($structure);

		Utils::removeDir($this->root->getChild('dir')->url());

		$this->assertEquals(
			[$this->root->getName() => []],
			vfsStream::inspect(new vfsStreamStructureVisitor)->getStructure()
		);
	}

	/**
	 * @expectedException \ErrorException
	 */
	public function testRemoveDirError() {
		$structure =  [
			'dir' => [
				'dir1' => [
					'dir2' => ['file' => '']
				],
			],
		];

		vfsStream::create($structure);
		$this->root->getChild('dir')->getChild('dir1')->chmod(0444);
		Utils::removeDir($this->root->getChild('dir')->url());
	}
}
