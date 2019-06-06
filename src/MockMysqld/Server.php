<?php

namespace MockMysqld;

class Server {
	private $options;
	private $processFactory;
	private $initScripts;
	private $process;

	public function __construct(Options $options, $initScripts, callable $processFactory = null) {
		$this->options = $options;
		$this->initScripts = Utils::ensureTraversable($initScripts);
		$this->processFactory = $processFactory ?: new ProcessFactory;
		$this->process = $this->createProcessObject($this->options);
	}

	public function initDataDir() {
		$initScript = $this->createMergedInitScript();
		if ($initScript) {
			$tmpDataDir = $this->options['datadir'] . '.tmp';

			file_exists($tmpDataDir) && Utils::removeDir($tmpDataDir);

			Utils::ensureDir($tmpDataDir);

			$this->initializeInsecure($tmpDataDir, $initScript);

			Utils::copyDir($tmpDataDir, $this->options['datadir']);
		}
	}

	public function killOldProcess() {
		$pidFile = $this->options['pid-file'];
		if (file_exists($pidFile)) {
			$pid = trim(file_get_contents($pidFile));
			posix_kill($pid, SIGTERM);
		}
	}

	public function start($startTimeout = 10) {
		if ($this->process->isRunning()) {
			return;
		}

		$this->killOldProcess();
		$this->initDataDir();

		$this->process->start();
		$this->waitForConnection($startTimeout);
	}

	public function stop() {
		if ($this->process->isRunning()) {
			$this->process->stop();
		}
	}

	public function waitForConnection($startTimeout) {
		$time = time();
		$host = gethostbyname($this->options['bind-address']);
		$port = $this->options['port'];

		while (time() - $time <= $startTimeout) {
			usleep(1000);
			try {
				$f = @fsockopen($host, $port, $errno, $errstr, 1);
				if ($f) {
					fclose($f);

					return;
				}
			} catch (\Exception $e) {
			}
		}

		throw new \Exception('Server start timeout');
	}

	private function createMergedInitScript() {
		$dir = Utils::ensureDir($this->options['datadir']);
		$scriptFile = Utils::path([$dir, 'merged-init-scripts.sql']);
		$markFile = Utils::path([$dir, 'merged-init-scripts.sql.mark']);

		$merged = implode("\n-- merged script --\n", iterator_to_array($this->initScripts));
		$markContents = md5($merged);

		$new = Utils::runInitAction($markFile, $markContents, 'file_put_contents', [$scriptFile, $merged]);

		return $new ? $scriptFile : false;
	}

	private function initializeInsecure($datadir, $initFile) {
		$options = new Options($this->options);
		$options['datadir'] = $datadir;
		$options['initialize-insecure'] = null;
		$options['init-file'] = $initFile;
		$process = $this->createProcessObject($options);
		$process->mustRun();
	}

	private function createProcessObject(Options $options) {
		return call_user_func($this->processFactory, $options);
	}
}
