<?php

namespace MockMysqld\filter;

class ChangeToMemoryEngineFilter {
	private $changeTypeCallback;

	public function __construct(callable $changeTypeCallback = null) {
		$this->changeTypeCallback = $changeTypeCallback;
	}

	private function changeType($table, $column, $type) {
		if ($this->changeTypeCallback && preg_match('/blob|text/mis', $type)) {
			return call_user_func_array($this->changeTypeCallback, [$table, $column, $type]);
		}

		return $type;
	}

	private static function concatGroups($groups, $numbers) {
		return implode('', array_intersect_key($groups, array_flip($numbers)));
	}

	private function replaceDefinition($table, $definition) {
		$notField = preg_match(
			'/\s*(INDEX|KEY|FULLTEXT|SPATIAL|CONSTRAINT|PRIMARY|UNIQUE|FOREIGN|CHECK)/mis',
			$definition
		);
		if ($notField) {
			return $definition;
		}

		return preg_replace_callback(
			'/(\W*)(\w+)(\W*)(\w+([^\(]*\([^\)]*\)){0,1})(.*)/mis',
			function ($m) use ($table) {
				$m[4] = $this->changeType($table, $m[2], $m[4]);
				return self::concatGroups($m, [1, 2, 3, 4, 6]);
			},
			$definition
		);
	}

	private function replaceBody($table, $body) {
		return preg_replace_callback(
			'/((([^\(\),]+)(\([^\)]+\))*)+)(,{0,1})/mis',
			function ($m) use ($table) {
				$m[1] = $this->replaceDefinition($table, $m[1]);
				return self::concatGroups($m, [1, 5]);
			},
			$body
		);
	}

	public function __invoke($script) {
		return preg_replace_callback(
			'/(CREATE\s+)(TEMPORARY\s+){0,1}(TABLE)(\s+IF\s+NOT\s+EXISTS){0,1}([\s\'"`]+)'
			// table name
			. '([\w\.]+)'
			. '([\s\'"`]+\(\s*)'
			// body
			. '(([^\(\)]+|\([^\)]+\))+)'
			. '(\))([^;]+?)(ENGINE\s*=\s*)(\w+)/mis',
			function ($m) {
				$m[8] = $this->replaceBody($m[6], $m[8]);
				if (!preg_match('/\W(tiny|long|medium){0,1}(blob|text)[^\w`\'"]/mis', $m[8])) {
					$m[13] = 'MEMORY';
				}
				return self::concatGroups($m, array_merge(range(1, 8), range(10, 13)));
			},
			$script
		);
	}
}
