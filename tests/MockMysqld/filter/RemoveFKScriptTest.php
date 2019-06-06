<?php

namespace Test\MockMysqld\filter;

use PHPUnit\Framework\TestCase;
use MockMysqld\filter\RemoveFKFilter;

class RemoveFKFilterTest extends TestCase {
	public function testFilter() {
		$script = <<<EOS
-BEFORE-,  CONSTRAINT `0_38775` FOREIGN KEY (`A`, `D`)
REFERENCES `ibtest11a` (`A`, `D`)
ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `0_38776` FOREIGN KEY (`B`, `C`)
REFERENCES `ibtest11a` (`B`, `C`)
ON DELETE CASCADE ON UPDATE CASCADE
-AFTER-
EOS;
		$filter = new RemoveFKFilter;
		$this->assertRegExp('/-BEFORE-\s*-AFTER-/', $filter($script));
	}
}
