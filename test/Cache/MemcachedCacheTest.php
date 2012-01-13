<?php

class MemcachedCacheTest extends CacheTest {
	public function getCacheUrl() {
		return 'memcached://dev-backend001.ci:11211/';
	}
	
	public function setup() {
		parent::setup();
		
		// $this->key is set via uniqid() in parent::setup, so we force its
		// deletion just in case it already exists in memcached
		$this->cache->delete($this->key);
	}

	/**
	 * construct()
	 */
	public function test_construct_ThrowsExceptionIfNoServersAreSupplied() {
		try {
			$cache = new MemcachedCache();
		} catch (Exception $expected) {
			return;
		}
		$this->fail();
	}

	/**
	 * flush()
	 */
	public function test_flush_DoesNothing_ReturnsFalse() {
		self::assertFalse($this->cache->flush());
	}
}
