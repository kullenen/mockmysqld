<?php

namespace MockMysqld;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;

class ProcessFactory {
	private $executableFinder;

	public function createMysqldCommand(Options $options) {
		$executable = $this->executableFinder->find('mysqld');
		if (!$executable) {
			throw new \Exception('Cannot find mysqld executable');
		}

		$args = [$executable];
		foreach ($options as $name => $value) {
			$args[] = sprintf('--%s%s', $name, strlen($value) ? '=' . $value : '');
		}

		return $args;
	}

	public function __construct(ExecutableFinder $executableFinder = null) {
		$this->executableFinder = $executableFinder ?: new ExecutableFinder;
	}

	public function __invoke(Options $options) {
		return new Process($this->createMysqldCommand($options));
	}
}
