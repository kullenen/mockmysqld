<?php

namespace MockMysqld;

class Options extends \ArrayIterator {
	public function __construct($options = []) {
		parent::__construct(iterator_to_array(Utils::ensureTraversable($options)));
		$this->initOptions();
	}

	private function initOption($name, $default) {
		$this[$name] = isset($this[$name]) ? $this[$name] : $default;
	}

	private function initOptions() {
		$this->initOption('port', '13306');
		$this->initOption('bind-address', 'localhost');
		$this->initOption('tmpdir', sys_get_temp_dir());
		$this->initOption(
			'datadir',
			Utils::path([$this['tmpdir'], 'mock.mysqld.datadir', $this['port']])
		);
		$this->initOption('pid-file', Utils::path([$this['datadir'], 'mysqld.pid']));
		$this->initOption('socket', Utils::path([$this['datadir'], 'mysqld.sock']));
		$this->initOption('log_error', Utils::path([$this['datadir'], 'mysqld.error.log']));
		$this->initOption('general-log', '0');
		$this->initOption('general-log-file', Utils::path([$this['datadir'], 'mysqld.general.log']));
	}
}
