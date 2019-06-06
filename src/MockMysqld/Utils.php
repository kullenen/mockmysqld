<?php

namespace MockMysqld;

class Utils {
	public static function ensureTraversable($iterator) {
		if ($iterator instanceof \Traversable) {
			return $iterator;
		}

		if (is_object($iterator)) {
			throw new \InvalidArgumentException('Must be array or Traversable');
		}

		return new \ArrayIterator((array) $iterator);
	}

	public static function path(array $names) {
		return implode(DIRECTORY_SEPARATOR, $names);
	}

	public static function ensureDir($dir) {
		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
		}

		return $dir;
	}

	public static function runInitAction($initMarkFile, $initMarkContents, callable $action, array $actionParams = []) {
		if (!file_exists($initMarkFile) || (file_get_contents($initMarkFile) != $initMarkContents)) {
			self::ensureDir(dirname($initMarkFile));
			call_user_func_array($action, (array) $actionParams);
			file_put_contents($initMarkFile, $initMarkContents);

			return true;
		}

		return false;
	}

	public static function copyDir($source, $dest) {
		$dest = self::ensureDir($dest);

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $item) {
			$destName = self::path([$dest, $iterator->getSubPathName()]);

			if ($item->isDir()) {
				self::ensureDir($destName);
			} else {
				copy($item, $destName) || self::throwLastError();
			}
		}
	}

	public static function removeDir($dir) {
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($iterator as $item) {
			if ($item->isDir()) {
				rmdir($item) || self::throwLastError();
			} else {
				unlink($item) || self::throwLastError();
			}
		}

		rmdir($dir) || self::throwLastError();
	}

	private static function throwLastError() {
		$e = error_get_last() ?: ['message' => 'IO error', 'type' => E_WARNING, 'file' => __FILE__, 'line' => __LINE__];
		throw new \ErrorException($e['message'], 0, $e['type'], $e['file'], $e['line']);
	}
}
