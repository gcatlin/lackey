<?php

// PSR-0 compatibility
// https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md

// @TODO file patterns
// @TODO exclude dirs
// @TODO handle duplicate class names
// @TODO support interfaces

// http://weierophinney.net/matthew/archives/244-Applying-FilterIterator-to-Directory-Iteration.html
// http://github.com/alfallouji/PHP-Autoload-Manager/
// http://github.com/theseer/Autoload
// http://raw.github.com/theseer/Autoload/master/src/classfinder.php
// http://gist.github.com/221634
// http://phpcrossref.com/zendframework/library/Zend/Reflection/File.php.html

//
// @TODO
//

// support directories with no classes
// support namespaces
// provide a way to designate this CL as the "one true CL", which rebuilds the cache every time a cache miss occurs???

// ignore-dirs (part of FileSearch?)
// ignore-files (part of FileSearch?)
// extensions (part of FileSearch?, spl_autoload_extensions())
// non-recursive searches?

// persist class map (use Cache class for this?)
// hash normalized path as config key??? 
// store basepath separately when persisting data?
// array (
//   'BasePaths => array(
//     0 => '/path/to/code',
//     1 => '/usr/share/php'),
//   'Classes' => array(
//     'Class1' => array(0, 'path/to/Class1.php')
// )

/**
 *
 */
class ClassLoader {
	/**
	 *
	 */
	protected $class_map;

	/**
	 *
	 */
	protected $read_callback;
	protected $read_callback_name;

	/**
	 *
	 */
	protected $normalized_path;

	/**
	 *
	 */
	protected $path;

	/**
	 *
	 */
	protected $write_callback;
	protected $write_callback_name;

	/**
	 *
	 */
	public static function autoRegister($path, $read_callback = null, $write_callback = null) {
		$loader = new ClassLoader($path, $read_callback, $write_callback);
		$loader->register();
		return $loader;
	}

	/**
	 *
	 */
	// @TODO accept a FileSearch instead of a path?
	public function __construct($path, $read_callback = null, $write_callback = null) {
		$this->path = $path;

		// move to setters?
		if ($read_callback !== null) {
			if (is_callable($read_callback, false, $read_callback_name)) {
				$this->read_callback = $read_callback;
				$this->read_callback_name = $read_callback_name;
			} else {
				throw new InvalidArgumentException(sprintf(
					"Read callback '%s' is invalid",
					$read_callback_name
				));
			}		
		}

		if ($write_callback !== null) {
			if (is_callable($write_callback, false, $write_callback_name)) {
				$this->write_callback = $write_callback;
				$this->write_callback_name = $write_callback_name;
			} else {
				throw new InvalidArgumentException(sprintf(
					"Write callback '%s' is invalid",
					$write_callback_name
				));
			}
		}
	}

	/**
	 *
	 */
	protected function buildClassMap() {
		$this->class_map = array();

		$path = $this->getNormalizedPath();
		if (!empty($path)) {
			$files = new FileSearch($path, '/.+\.(inc|php)$/');
			foreach ($files as $file_info) {
				$file = $file_info->getRealPath();
				$classes = ClassExtractor::getClasses($file);
				foreach ($classes as $class) {
					if (empty($this->class_map[$class])) {
						$this->class_map[$class] = $file;
					} else {
						throw new Exception(sprintf(
							"Duplicate class name '%s' found in '%s' and '%s'",
							$class,
							$this->class_map[$class],
							$file
						));
					}
				}
			}
		}
	}

	/**
	 *
	 */
	public function getClassMap() {
		if ($this->class_map === null) {
			$this->buildClassMap();
		}
		return $this->class_map;
	}

	/**
	 * Canonicalize, de-duplicate, and sort elements in path
	 */
	public function getNormalizedPath() {
		if ($this->normalized_path === null) {
			$paths = array();
			foreach (explode(PATH_SEPARATOR, $this->path) as $p) {
				if (isset($p[0]) && !isset($paths[$p])) {
					if ($p[0] !== '/') {
						$p = realpath($p);
					}
					$paths[$p] = true;
				}
			}
			ksort($paths);
			$this->normalized_path = implode(PATH_SEPARATOR, array_keys($paths));
		}
		return $this->normalized_path;
	}

	/**
	 *
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 *
	 */
	public function load($class) {
		$is_mapped = isset($this->class_map[$class]);
		$is_loaded = false;
		$build_map = false;
		
		if ($is_mapped) {
			if (!empty($this->class_map[$class])) {
				$is_loaded = (bool) include($this->class_map[$class]);
			}
			if (!$is_loaded) {
				$build_map = true;
			}
		} elseif ($this->class_map === null) {
			$build_map = !$this->readClassMap();
		}

		if ($build_map) {
			$this->buildClassMap();
			$this->writeClassMap();
			return $this->load($class);
		}
		return $is_loaded;
	}

	/**
	 *
	 */
	protected function readClassMap() {
		if ($this->read_callback !== null) {
			$class_map = call_user_func($this->read_callback, $this->getNormalizedPath());
			if ($class_map === null) {
				throw new Exception(sprintf(
					"Read callback '%s' must return a class map array",
					$this->read_callback_name
				));
			}
			$this->setClassMap($class_map);
			return true;
		}
		return false;
	}

	/**
	 *
	 */
	public function register($append = false) {
		return spl_autoload_register(array($this, 'load'), true, !$append);
	}

	/**
	 *
	 */
	public function setClassMap($class_map) {
		if (!is_array($class_map)) {
			$class_map = null;
		}
		$this->class_map = $class_map;
	}

	/**
	 *
	 */
	public function unregister() {
		return spl_autoload_unregister(array($this, 'load'));
	}

	/**
	 *
	 */
	protected function writeClassMap() {
		if ($this->write_callback !== null) {
			call_user_func($this->write_callback, $this->getClassMap());
		}
	}

	// /**
	//  *
	//  */
	// protected function writeCache() {
	// 	// only write if class map is an array
	// 	$php = '<?php return ' . var_export($this->class_map, true) . ';';
	// 	file_put_contents($this->getCacheFile(), $php);
	// 	// @TODO error handling
	// }
}
