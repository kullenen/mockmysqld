<?php

namespace Test\MockMysqld\filter;

use PHPUnit\Framework\TestCase;
use MockMysqld\filter\ChangeToMemoryEngineFilter;

class ChangeToMemoryEngineFilterTest extends TestCase {
	public function testFilter() {
		$script = <<<EOS
CREATE TABLE `accounts` (
	`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`code` char(32) DEFAULT NULL,
	`abbrev` char(32) DEFAULT NULL,
	`blocked` tinyint(1) NOT NULL DEFAULT '0',
	`c1` BLOB,
	`c2` TINYBLOB,
	`c3` MEDIUMBLOB,
	`c4` LONGBLOB,
	`c5` text,
	`c6` TINYTEXT,
	`c7` MEDIUMTEXT,
	`c8` LONGTEXT,
	KEY `code` (`code`)
) ENGINE = InnoDB AUTO_INCREMENT=6126 DEFAULT CHARSET=utf8
EOS;

		$filter =  new ChangeToMemoryEngineFilter;
		$this->assertNotContains('memory', strtolower($filter($script)));

		$filter = new ChangeToMemoryEngineFilter(
			function ($table, $column, $type) {
				return 'varchar(1234)';
			}
		);

		$filtered = $filter($script);

		$this->assertNotContains('text', strtolower($filtered));
		$this->assertNotContains('blob', strtolower($filtered));
		$this->assertNotContains('innodb', strtolower($filtered));
		$this->assertContains('memory', strtolower($filtered));
		$this->assertEquals(8, substr_count($filtered, 'varchar(1234)'));
	}
}
