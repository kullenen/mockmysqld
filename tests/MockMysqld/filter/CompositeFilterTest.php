<?php

namespace Test\MockMysqld\filter;

use PHPUnit\Framework\TestCase;
use MockMysqld\filter\CompositeFilter;

class CompositeScriptTest extends TestCase {
	public function testAppend() {
		$filter = new CompositeFilter;
		$this->assertEquals($filter, $filter->append('strlen'));
	}

	public function testFilter() {
		$filter = (new CompositeFilter)->append('strlen')->append('md5');
		$this->assertEquals(md5('0'), $filter(''));
	}
}
