<?php

class FileSearch extends FilterIterator {
	protected $pattern;

	public function __construct($path, $pattern, $recursive = true) {
		$this->pattern = $pattern;
		$paths = explode(PATH_SEPARATOR, $path);
		if (count($paths) <= 1) {
			$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
		} else {
			$it = new AppendIterator();
			foreach ($paths as $path) {
				$it->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)));
			}
		}
		parent::__construct($it);
	}

	public function accept() {
		// change behavior depending on pattern type
		return preg_match($this->pattern, $this->current()->getFilename());
		// return !strcmp($this->current()->getFilename(), $this->pattern);
	}

	public function getMatches() {
		$matches = array();
		foreach ($this as $match) {
			$matches[] = $match->getPathname();
		}
		return $matches;
	}
}
