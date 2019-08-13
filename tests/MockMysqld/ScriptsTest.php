<?php

namespace Test\MockMysqld;

use PHPUnit\Framework\TestCase;
use MockMysqld\filter\FilterInterface;
use MockMysqld\Scripts;
use org\bovigo\vfs\vfsStream;

class ScriptsTest extends TestCase {
	private $root;
	private $files;

	public function setUp(): void {
		$structure = ['f1' => 'contents1', 'f2' => 'contents2'];
		$this->root = vfsStream::setup('mockmysqld', null, $structure);

		foreach ($structure as $key => $contents) {
			$this->files[$this->root->getChild($key)->url()] = $contents;
		}
	}

	public function testWithoutFilter() {
		$files = $this->files;
		foreach (new Scripts(array_keys($files)) as $script) {
			$this->assertEquals(array_shift($files), $script);
		}
	}

	public function testWithFilter() {
		$files = $this->files;

		foreach (new Scripts(array_keys($files), 'md5') as $script) {
			$this->assertEquals(md5(array_shift($files)), $script);
		}
	}
}
