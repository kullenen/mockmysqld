<?php

namespace Test\MockMysqld;

use PHPUnit\Framework\TestCase;
use MockMysqld\Options;

class OptionsTest extends TestCase {
	public function testConstructor() {
		$expectedDefaults = [
				'port' => '13306',
				'bind-address' => 'localhost',
				'tmpdir' => sys_get_temp_dir(),
		];
		$actual = iterator_to_array(new Options);

		foreach ($expectedDefaults as $key => $value) {
			$this->assertArrayHasKey($key, $actual);
			$this->assertEquals($value, $actual[$key]);
		}

		$expectedCustom = [
				'port' => '111',
				'bind-address' => '1.2.3.4',
				'tmpdir' => '/mytemp',
		];
		$options = new Options($expectedCustom);

		foreach ($expectedCustom as $key => $value) {
			$this->assertEquals($value, $options[$key]);
		}

		foreach (['datadir', 'pid-file', 'socket', 'log_error'] as $key) {
			$this->assertStringStartsWith($expectedCustom['tmpdir'], $options[$key]);
		}
	}
}
