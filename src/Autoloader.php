<?php
// @TODO file patterns
// @TODO exclude dirs
// @TODO handle duplicate class names
// @TODO support interfaces

// http://github.com/alfallouji/PHP-Autoload-Manager/
// http://weierophinney.net/matthew/archives/244-Applying-FilterIterator-to-Directory-Iteration.html
// http://github.com/theseer/Autoload
// http://gist.github.com/221634

// 
class Autoloader
{
	protected $cache_file;
	protected $class_map = array();
	protected $path;
	
	public static function addPath($path) {
		$autoloader = new Autoloader($path);
		$autoloader->initialize();
		$autoloader->register();
	}
	
	public function __construct($path) {
		$this->path = rtrim($path, '/');
		$this->cache_file = rtrim(sys_get_temp_dir(), '/') . '/' . md5($this->path);
	}
	
	public function autoload($class_name) {
		if (!isset($this->class_map[$class_name]) || $this->class_map[$class_name] == '') {
			$this->cache();
		}

		$is_valid_class_path = include_once($this->path . '/' . $this->class_map[$class_name]);
		if (!$is_valid_class_path) {
			unset($this->class_map[$class_name]);
			$this->cache();
			if (isset($this->class_map[$class_name])) {
				include_once($this->path . '/' . $this->class_map[$class_name]);
			}
		}
	}
	
	public function cache() {
		$this->class_map = $this->findPhpClasses();

		$php = '<?php return ' . var_export($this->class_map, true) . ';';
		file_put_contents($this->cache_file, $php);
	}
	
	public function initialize() {
		$class_map = include_once($this->cache_file);
		if (is_array($class_map)) {
			$this->class_map = $class_map;
		} else {
			$this->class_map = array();
		}

		if (empty($this->class_map)) {
			$this->cache();
		}
	}
	
	public function register() {
		spl_autoload_register(array($this, 'autoload'));
	}
	
	/**
	 *
	 */
	public function findPhpClasses($pattern='*.php') {
		$files = rglob($this->path, $pattern);
		foreach ($files as $filename) {
			$file = file_get_contents($filename);
			$filename = str_replace($this->path . '/', '', $filename);
			if (strpos($file, 'class ') !== false) {
				$num_matches = preg_match_all('/^(?:abstract )?class (\w+)/m', $file, $matches);
				if ($num_matches) {
					foreach ($matches[1] as $class_name) {
						$class_map[$class_name] = $filename;
					}
				}
			}
		}
		return $class_map;
	}
}

if (!function_exists('rglob')) {
	function rglob($dir, $pattern, $flags=GLOB_NOSORT) {
		$files = glob("{$dir}/{$pattern}", $flags);
		$dirs = glob("{$dir}/*", GLOB_ONLYDIR | GLOB_NOSORT);
		foreach ($dirs as $dir) {
			$files = array_merge($files, rglob($dir, $pattern, $flags));
		}
		return $files;
	}
}
