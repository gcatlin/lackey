<?php
/**
 * 
 */
class MemcachedCache extends Cache
{
	/**
	 * Points to the port where memcached is listening for connections. Set this 
	 * parameter to 0 when using UNIX domain sockets.
	 */
	const DefaultHost = '127.0.0.1';

	/**
	 * Points to the port where memcached is listening for connections. Set this 
	 * parameter to 0 when using UNIX domain sockets.
	 */
	const DefaultPort = 11211;
	
	/**
	 * Controls how often a failed server will be retried, the default value is 
	 * 15 seconds. Setting this parameter to -1 disables automatic retry. 
	 * Neither this nor the persistent parameter has any effect when the 
	 * extension is loaded dynamically via dl().
	 *
	 * Each failed connection struct has its own timeout and before it has 
	 * expired the struct will be skipped when selecting backends to serve a 
	 * request. Once expired the connection will be successfully reconnected or 
	 * marked as failed for another retry_interval seconds. The typical effect 
	 * is that each web server child will retry the connection about every 
	 * retry_interval seconds when serving a page.
	 */
	const RetryInterval = 15;
	
	/**
	 * Controls if the server should be flagged as online. Setting this 
	 * parameter to FALSE and retry_interval to -1 allows a failed server to be 
	 * kept in the pool so as not to affect the key distribution algorithm. 
	 * Requests for this server will then failover or fail immediately depending
	 * on the memcache.allow_failover setting. Defaults to TRUE, meaning the 
	 * server should be considered online.
	 */
	const Status = true;
	
	/**
	 * Value in seconds which will be used for connecting to the daemon. Think 
	 * twice before changing the default value of 1 second - you can lose all 
	 * the advantages of caching if your connection is too slow.
	 */
	const Timeout = 1;
	
	/**
	 * Controls the use of a persistent connection. Defaults to TRUE.
	 */
	const UsePersistentConnection = true;
	
	/**
	 * Number of buckets to create for each server which in turn control its 
	 * probability of it being selected. The probability is relative to the 
	 * total weight of all servers.
	 */
	const Weight = 1;
	
	// /**
	//  * Allows the user to specify a callback function to run upon encountering 
	//  * an error. The callback is run before failover is attempted. The function 
	//  * takes two parameters, the hostname and port of the failed server.
	//  * 
	//  * @var mixed
	//  */
	// protected $failure_callback;
	
	/**
	 * Wrapper object provided by the Memcache PECL extension.
	 * http://us.php.net/manual/en/book.memcache.php
	 *
	 * @var Memcache
	 */
	protected $memcache;
	
	/**
	 *
	 */
	public function __construct($name=null, $host=self::DefaultHost, $port=self::DefaultPort, $options)
	{
		if (!function_exists('memcache_connect')) {
			throw new Exception('Memcache client library is not installed.');
		}
		
		$this->memcache = new Memcache();

		// foreach ($hosts as $host) {
			$this->memcache->addServer(
				$host,
				(int) $port,                         // 11211
				self::UsePersistentConnection, // true
				self::Weight,                  // 1
				self::Timeout,                 // 1 second
				self::RetryInterval,           // 15 seconds
				self::Status,                  // true
				null // $failure_callback
			);
		// }
	}

	/** 
	 * Store something in the shared memcached, but only if it doesn't already exist.
	 *
	 * @param string  $key   The cache key to insert
	 * @param mixed   $value  What to store
	 * @param integer $timeout the time to expiration, defaults to 3600 seconds
	 *               0 will 'never' expire unless the server is restarted or
	 *               runs out of memory.
	 * @return boolean True if sucess, False if failure (including if the key already exists)
	 */
	public function add($key, $value, $timeout=0)
	{
		return $this->memcache->add($key, $value, 0, (int) $timeout);
	}

	/**
	 * Decrements a value in memcached
	 *
	 * @param string  $key
	 * @param integer $value How much to increment it by
	 * @return integer The item's new value, or false on failure.
	 */
	public function decrement($key, $value=1)
	{
		if ($value < 0) {
			return $this->memcache->increment($key, (int) -$value);
		}
		return $this->memcache->decrement($key, (int) $value);
	}
	
	/** 
	 * Delete something from the shared memcached
	 *
	 * @param string $key is the key to delete
	 */
	public function delete($key)
	{
		return $this->memcache->delete($key);
	}

	/** 
	 * You thought we'd make it that easy to flush *everything* from Memcached? HA!
	 *
	 */
	public function flush()
	{
		return false;
	}

	/**
	 * retrieve something from the shared memcached
	 * @param key is the key of the entry
	 */
	public function get($key)
	{
		return $this->memcache->get($key);
	}
	
	/**
	 * Increment a value in memcached
	 *
	 * @param string  $key
	 * @param integer $value How much to increment it by
	 * @return integer The item's new value, or false on failure.
	 */
	public function increment($key, $value=1)
	{
		return $this->memcache->increment($key, (int) $value);
	}
	
	/**
	 * @see http://code.google.com/p/memcached/wiki/FAQ#Emulating_locking_with_the_add_command
	 */
	public function lock($key, $lock_timeout=1, $acquire_timeout=null)
	{
		$lock_key = $this->getLockKey($key);
		$random_value = rand();
	
		// Returns immediately if a lock cannot be acquired.
		if ($acquire_timeout === 0 || $acquire_timeout < 0) {
			return $this->memcache->add($lock_key, $random_value, $lock_timeout);
		}
		
		// If $acquire_timeout is null this will block forever, otherwise it 
		// blocks until $acquire_timeout expires. If $acquire_timeout is greater
		// than 2592000 (30 days), it is assumed to be a unix timestamp.
		else {
			$try_until = $acquire_timeout + ($acquire_timeout < 2592000 ? microtime(true) : 0);
			while (($acquire_timeout === null) || microtime(true) < $try_until) {
				if ($this->memcache->add($lock_key, $random_value, $lock_timeout)) {
					return true;
				}
				usleep(10000);
			}	
		}
		
		return false;
	}

	/** 
	 *
	 */
	public function replace($key, $value, $timeout=0)
	{
		return $this->memcache->replace($key, $value, 0, (int) $timeout);
	}

	/** 
	 * store something in the shared memcached
	 *
	 * @param string  $key        required=true
	 * @param mixed   $value      required=true
	 * @param integer $iExpires    required=false preset=true
	 * @param expire the time to expiration, defaults to 3600 seconds
	 *               0 will 'never' expire unless the server is restarted or
	 *               runs out of memory.
	 */
	public function set($key, $value, $timeout=0)
	{
		return $this->memcache->set($key, $value, 0, (int) $timeout);
	}

	/**
	 * See comment in lock() method above.
	 */
	public function unlock($key)
	{
		$lock_key = $this->getLockKey($key);
		if ($this->memcache->get($lock_key)) {
			return $this->memcache->delete($lock_key);
		}
		return true;
	}
	
	/**
	 * 
	 */
	protected function getLockKey($key) {
		return $key . '.lock';
	}
}
