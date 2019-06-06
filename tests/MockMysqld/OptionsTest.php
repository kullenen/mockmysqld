<?php

namespace Test\MockMysqld;

use PHPUnit\Framework\TestCase;
use MockMysqld\Options;

class OptionsTest extends TestCase {
	public function testConstructor() {
		$this->assertArraySubset(
			[
				'port' => '13306',
				'bind-address' => 'localhost',
				'tmpdir' => sys_get_temp_dir(),
			],
			iterator_to_array(new Options)
		);

		$custom = [
				'port' => '111',
				'bind-address' => '1.2.3.4',
				'tmpdir' => '/mytemp',
		];
		$options = new Options($custom);

		foreach ($custom as $key => $value) {
			$this->assertEquals($value, $options[$key]);
		}

		foreach (['datadir', 'pid-file', 'socket', 'log_error'] as $key) {
			$this->assertStringStartsWith($custom['tmpdir'], $options[$key]);
		}
	}
}
