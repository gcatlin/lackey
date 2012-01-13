<?php
/**
 *
 */
class ArrayCache extends Cache {
	/**
	 * 
	 */
	protected static $options_map = array(
		'max_items' => 'setMaxItems',
	);
	
	/**
	 * Container for cached data.
	 *
	 * @var array
	 */
	protected $cache = array();

	/**
	 * Container for keys. Used to garbage collect cached items in FIFO order.
	 * Only used if $max_items is not null.
	 *
	 * @var object
	 */
	protected $keys = array();

	/**
	 * Container for timeouts, indexed by cache key. Used to determine if an
	 * item has expired.
	 *
	 * @var array
	 */
	protected $timeouts = array();

	/**
	 * The maximum number of items that this cache object holds. If $max_items
	 * is null, there is no maximum (this is the default behavior). If a maximum
	 * is set and an item is added that would cause the maximum to be exceed
	 * the oldest item is deleted.
	 *
	 * @var integer
	 */
	protected $max_items;
	
	/**
	 * 
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param integer $timeout
	 * @return boolean
	 */
	public function add($key, $value, $timeout=0) {
		if (!$this->isValid($key)) {
			return $this->write($key, $value, $timeout);
		}
		return false;
	}
	
	/**
	 * 
	 *
	 * @param string $key
	 * @param integer $value
	 * @return integer
	 */
	public function decrement($key, $value=1) {
		return $this->increment($key, -$value);
	}
	
	/**
	 * 
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function delete($key) {
		if ($this->isValid($key)) {
			unset($this->cache[$key], $this->timeouts[$key]);
			return true;
		}
		return false;
	}
	
	/**
	 * 
	 *
	 * @return boolean
	 */
	public function flush() {
		$this->cache = array();
		$this->keys = array();
		$this->timeouts = array();
		return true;
	}
	
	/**
	 * 
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		if ($this->isValid($key)) {
			return $this->cache[$key];
		}
		return false;
	}
	
	/**
	 * 
	 *
	 * @return array
	 */
	public function getAll() {
		return $this->cache;
	}
	
	/**
	 * If the item exists it will be converted to an integer
	 *
	 * @param string $key
	 * @param integer $value
	 * @return integer
	 */
	public function increment($key, $value=1) {
		if ($this->isValid($key)) {
			$value = (int) $value;
			$this->cache[$key] = (int) $this->cache[$key];
			$this->cache[$key] += ($this->cache[$key] == 0 ? max(0, $value) : $value);
			return $this->cache[$key];
		}
		return false;
	}
	
	/**
	 * 
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param integer $timeout
	 * @return boolean
	 */
	public function replace($key, $value, $timeout=0) {
		if ($this->isValid($key)) {
			return $this->write($key, $value, $timeout);
		}
		return false;
	}
	
	/**
	 * 
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param integer $timeout
	 * @return boolean
	 */
	public function set($key, $value, $timeout=0) {
		return $this->write($key, $value, $timeout);
	}
	
	/**
	 * 
	 *
	 * @param array $options
	 */
	public function setMaxItems($max_items) {
		$this->max_items = ($max_items === null ? null : max(1, (int) $max_items));
	}
	
	/**
	 * 
	 *
	 * @param array $options
	 */
	public function setOptions($options) {
		foreach ($options as $key => $value) {
			if (isset(self::$options_map[$key])) {
				$method = self::$options_map[$key];
				$this->$method($value);
			}
		}
	}
	
	/**
	 * 
	 *
	 * @param string $key
	 * @return boolean
	 */
	protected function isValid($key) {
		if (isset($this->cache[$key])) {
			if (!isset($this->timeouts[$key]) || time() < $this->timeouts[$key]) {
				return true;
			} else {
				// the item's cache timeout expired so delete it.
				// we don't remove the item from keys because it is not an O(1) operation
				unset($this->cache[$key], $this->timeouts[$key]);
				return false;
			}
		}
		return false;
	}
	
	/**
	 * 
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param integer $timeout
	 * @return boolean
	 */
	protected function write($key, $value, $timeout) {
		$key = (string) $key;
		
		// too many items in cache, delete oldest item
		if ($this->max_items !== null) {
			if ($this->max_items == count($this->keys)) {
				$oldest_key = array_shift($this->keys);
				unset($this->cache[$oldest_key], $this->timeouts[$oldest_key]);
			}
			$this->keys[] = $key;
		}

		// a timeout greater than 2592000 (30 days) is assumed to be a unix timestamp
		unset($this->timeouts[$key]);
		if ($timeout != 0) {
			if ($timeout <= 2592000) {
				$timeout += time();
			}
			$this->timeouts[$key] = (int) $timeout;
		}
		
		$this->cache[$key] = $value;
		return true;
	}
}
