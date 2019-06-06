<?php

namespace MockMysqld;

class Scripts extends \IteratorIterator {
	private $filter;

	public function __construct(array $fileNames, callable $filter = null) {
		parent::__construct(new \ArrayIterator($fileNames));
		$this->filter = $filter;
	}

	public function current(){
		$contents = file_get_contents(parent::current());
		return $this->filter ? call_user_func($this->filter, $contents) : $contents;
	}
}
