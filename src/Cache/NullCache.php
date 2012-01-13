<?php
/**
 *
 */
class NullCache extends Cache {
	/**
	 * 
	 */
	public function add($key, $value, $timeout=0) {
		return false;
	}
	
	/**
	 * 
	 */
	public function decrement($key, $value=1) {
		return false;
	}
	
	/**
	 * 
	 */
	public function delete($key) {
		return true;
	}
	
	/**
	 * 
	 */
	public function flush() {
		return true;
	}
	
	/**
	 * 
	 */
	public function get($key) {
		return false;
	}
	
	/**
	 * 
	 */
	public function increment($key, $value=1) {
		return false;
	}
	
	/**
	 * 
	 */
	public function replace($key, $value, $timeout=0) {
		return false;
	}
	
	/**
	 * 
	 */
	public function set($key, $value, $timeout=0) {
		return true;
	}
}
