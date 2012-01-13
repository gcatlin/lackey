<?php

/**
 * 
 */
abstract class Cache {
	/**
	 * Maps URL schemes to specific MessageQueue implementations.
	 *
	 * @var array
	 */
	protected static $scheme_map = array(
		'array'     => 'ArrayCache',
		'apc'       => 'ApcCache',
		'file'      => 'FileCache',
		'memcached' => 'MemcachedCache',
		'null'      => 'NullCache',
	);
	
	/**
	 * The name of the host on which the message queue resides.
	 *
	 * @var string
	 */
	protected $host;

	/**
	 * The name of the message queue.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The port number on which the host is listening.
	 *
	 * @var integer
	 */
	protected $port;

	/**
	 * Instantiates a new Cache object based on the supplied URL. The URL
	 * scheme must be defined in self::$scheme_map.
	 *
	 * @param string $url
	 * @return Cache
	 * @throws Exception
	 */
	public static function getByUrl($url) {
		$parsed_url = parse_url($url);
		if ($parsed_url === false) {
			throw new Exception('Malformed URL: ' . $url); // @TODO InvalidUrlException
		}

		$scheme = (isset($parsed_url['scheme']) ? $parsed_url['scheme'] : null);
		if (!isset(self::$scheme_map[$scheme])) {
			throw new Exception('Unsupported scheme in URL: ' . $url); // @TODO InvalidUrlException
		}

		$cache_class = self::$scheme_map[$scheme];
		$host = (isset($parsed_url['host']) ? $parsed_url['host'] : null);
		$port = (isset($parsed_url['port']) ? $parsed_url['port'] : null);
		$path = (isset($parsed_url['path']) ? $parsed_url['path'] : null);
		$query = (isset($parsed_url['query']) ? $parsed_url['query'] : '');
		parse_str($query, $options);
		$cache = new $cache_class($path, $host, $port, $options);

		return $cache;
	}
	
	/**
	 * 
	 * 
	 * @param string $name
	 * @param string $host
	 * @param string $port
	 */
	public function __construct($name=null, $host=null, $port=null, $options=null) {
		$this->name = $name;
		$this->host = $host;
		$this->port = $port;
		if ($options !== null && is_array($options)) {
			$this->setOptions($options);
		}
	}
	
	/**
	 * same as set() w/ $overwrite=true but fails if key is already set
	 */
	abstract public function add($key, $value, $timeout=0);

	/**
	 *
	 */
	abstract public function decrement($key, $value=1);

	/**
	 * Removes an item from the cache by key. Returns TRUE on success or FALSE 
	 * on failure.
	 *
	 * @param string $key
	 * @return bool
	 */
	abstract public function delete($key);

	/**
	 *
	 */
	abstract public function flush();

	/**
	 * Returns previously stored data if an item with such key exists or FALSE
	 * on failure.
	 *
	 * @param string $key
	 * @return mixed
	 */
	abstract public function get($key);
	
	/**
	 *
	 * @param string $key
	 * @return 
	 */
	abstract public function increment($key, $value=1);

	/**
	 * same as set() w/ $overwrite=false but fails if key is not already set
	 */
	abstract public function replace($key, $value, $timeout=0);

	/**
	 * Cache a variable in the data store by key.
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $timeout
	 * @return mixed
	 */
	abstract public function set($key, $value, $timeout=0);
}
