<?php

namespace MockMysqld\filter;

class CompositeFilter {
	private $filters = [];

	public function append(callable $filter) {
		$this->filters[] = $filter;
		return $this;
	}

	public function __invoke($script) {
		foreach ($this->filters as $filter) {
			$script = call_user_func($filter, $script);
		}

		return $script;
	}
}
