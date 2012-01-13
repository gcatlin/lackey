<?php

// @TODO static methods/properties for managing a logger hierarchy, using 
//       SplObjectStorage, SplObserver, SplSubject
// @TODO each logger has a level (inherited by default), 
//       writer/appender/facility/destination, message format, date format (optional)
// @TODO output drivers??? write to stream???
// @TODO test w/ logrotate

class Logger {
	const Trace = 100;
	const Debug = 200;
	const Info  = 300;
	const Warn  = 400;
	const Error = 500;
	const Off   = PHP_INT_MAX;

	const DefaultDateFormat = 'D M j H:i:s Y,000';
	const DefaultLevel = self::Info;
		
	protected static $levels = array(
		self::Trace => 'TRACE',
		self::Debug => 'DEBUG',
		self::Info  => 'INFO',
		self::Warn  => 'WARN',
		self::Error => 'ERROR',
		self::Off   => '',
	);
	
	protected $date_format;
	protected $file;
	protected $level;
	protected $pid;
	
	public function __construct($file, $level = self::DefaultLevel, $date_format = self::DefaultDateFormat) {
		$this->file = $file;
		$this->setDateFormat($date_format);
		$this->setLevel($level);
	}
	
	public function debug($message) {
		if ($this->level <= self::Debug) {
			$args = func_get_args();
			unset($args[0]);
			return $this->write(self::Debug, $message, $args);
		}
	}
	
	public function error($message) {
		if ($this->level <= self::Error) {
			$args = func_get_args();
			unset($args[0]);
			return $this->write(self::Error, $message, $args);
		}
	}
	
	public function getDateFormat() {
		return $this->date_format;
	}
	
	public function getLevel() {
		return $this->level;
	}
	
	public function info($message) {
		if ($this->level <= self::Info) {
			$args = func_get_args();
			unset($args[0]);
			return $this->write(self::Info, $message, $args);
		}
	}
	
	public function log($level, $message) {
		if ($level == self::Off || !isset(self::$levels[$level])) {
			return false;
		}
		
		$args = func_get_args();
		unset($args[0], $args[1]);
		return $this->write($level, $message, $args);
	}

	public function setDateFormat($date_format) {
		$this->date_format = $date_format;
	}
	
	public function setLevel($level) {
		if (isset(self::$levels[$level])) {
			$this->level = $level;
		} else {
			throw new Exception('Invalid log level supplied');
		}
	}
	
	public function trace($message) {
		if ($this->level <= self::Trace) {
			$args = func_get_args();
			unset($args[0]);
			return $this->write(self::Trace, $message, $args);
		}
	}

	public function warn($message) {
		if ($this->level <= self::Warn) {
			$args = func_get_args();
			unset($args[0]);
			return $this->write(self::Warn, $message, $args);
		}
	}
	
	protected function write($level, $message, $args=array()) {
		if ($args) {
			$callbacks = array_filter($args, 'is_array');
			if ($callbacks) {
				foreach ($callbacks as $i => $arg) {
					list($callback, $params) = $arg;
					if (is_callable($callback)) {
						$args[$i] = call_user_func_array($callback, (array) $params);
					} else {
						// @TODO throw an exception
						$args[$i] = implode(' ', $params);
					}
	 			}
			}
			$message = vsprintf($message, $args);
		}
		
		// @TODO move to protected getPid() method???
		if ($this->pid === null) {
			$this->pid = getmypid();
		}
		
		// $time = microtime();
		// $msecs = $time[2] . $time[3] . $time[4];
		// $secs = substr($time, -10);
		// $format = str_replace('k', $msecs, $this->date_format);
		// $date = date($date_format, $secs);
		$date = date($this->date_format);
		$formatted_message = sprintf("%s [%s] %-5s - %s\n", $date, $this->pid, self::$levels[$level], $message);

		return error_log($formatted_message, 3, $this->file);
	}
}
