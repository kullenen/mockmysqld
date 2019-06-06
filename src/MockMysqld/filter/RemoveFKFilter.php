<?php

namespace MockMysqld\filter;

class RemoveFKFilter {
	public function __invoke($script) {
		return preg_replace(
			'/,\s*[^,]+?(foreign\s+key)[^(]+'
			. '\([^)]+\)'
			. '\s+references[^(]+'
			. '\([^)]+\)'
			. '(\s*on\s+(delete|update)\s*'
			.'(RESTRICT|CASCADE|SET\s+NULL|NO\s+ACTION|SET\sDEFAULT)){0,2}/ism',
			'',
			$script
		);
	}
}
