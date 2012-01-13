<?php

abstract class CacheTest extends PHPUnit_Framework_TestCase {
	protected $cache; // should be set in the setup() method of all child classes
	protected $key;
	
	abstract public function getCacheUrl();

	public function setup() {
		$this->cache = Cache::getByUrl($this->getCacheUrl());
		$this->key = md5(uniqid());
	}
	
	/**
	 * add()
	 */
	public function test_add_KeyDoesNotExist_AddsToCache_ReturnsTrue() {
		self::assertTrue($this->cache->add($this->key, 100));
		self::assertEquals(100, $this->cache->get($this->key));
	}

	public function test_add_KeyIsExpired_AddsToCache_ReturnsTrue() {
		$this->cache->set($this->key, M_PI, strtotime('yesterday'));
		self::assertTrue($this->cache->add($this->key, 100));
		self::assertEquals(100, $this->cache->get($this->key));
	}
	
	public function test_add_KeyExists_DoesNotAddToCache_ReturnsFalse() {
		$this->cache->set($this->key, 100);
		self::assertFalse($this->cache->add($this->key, M_PI));
		self::assertEquals(100, $this->cache->get($this->key), 'cached value should not have changed');
	}

	/**
	 * decrement()
	 */
	public function test_decrement_KeyDoesNotExist_DoesNotAddToCache_ReturnsFalse() {
		self::assertFalse($this->cache->decrement($this->key));
		self::assertFalse($this->cache->get($this->key));
	}

	public function test_decrement_KeyIsExpired_DoesNotAddToCache_ReturnsFalse() {
		$this->cache->set($this->key, M_PI, strtotime('yesterday'));
		self::assertFalse($this->cache->decrement($this->key));
		self::assertFalse($this->cache->get($this->key));
	}

	public function test_decrement_KeyExists_DecrementsCachedValue_ReturnsNewValue() {
		$this->cache->set($this->key, 101);
		self::assertEquals(100, $this->cache->decrement($this->key));

		$this->cache->set($this->key, 133);
		self::assertEquals(100, $this->cache->decrement($this->key, 33));
	}

	/** @dataProvider dataProvider_decrement */
	public function test_decrement_KeyExists_ParameterAndCachedValueConvertedToIntegers($start_value, $decrement_value, $expected_value) {
		$this->cache->set($this->key, $start_value);
		self::assertEquals($expected_value, $this->cache->decrement($this->key, $decrement_value));
	}
	
	public function dataProvider_decrement() {
		return array(
			// $start_value, $decrement_value, $expected_value
			array(101, 1, 100),
			array(99, -1, 100),
			array(133, 33, 100),
			array(100.99, 0.01, 100),
			array(100.01, 0.99, 100),
			array('a string', 1, 0),
			array('a string', M_PI, 0),
			array(100, 'a string', 100),
			array('a string', 'another string', 0),
		);
	}
	
	/**
	 * delete()
	 */
	public function test_delete_KeyDoesNotExist_ReturnsFalse() {
		self::assertFalse($this->cache->delete($this->key));
	}

	public function test_delete_KeyIsExpired_ReturnsFalse() {
		$this->cache->set($this->key, M_PI, strtotime('yesterday'));
		self::assertFalse($this->cache->get($this->key)); // MemcacheCache fails the next assert when get() is not called first :(
		self::assertFalse($this->cache->delete($this->key));
	}

	public function test_delete_KeyExists_RemovesCachedValue_ReturnsTrue() {
		$this->cache->set($this->key, M_PI);
		self::assertTrue($this->cache->delete($this->key));
		self::assertFalse($this->cache->get($this->key));
	}

	/**
	 * get()
	 */
	public function test_get_KeyDoesNotExist_ReturnsFalse() {
		self::assertFalse($this->cache->get($this->key));
	}

	public function test_get_KeyIsExpired_ReturnsFalse() {
		$this->cache->set($this->key, M_PI, strtotime('yesterday'));
		self::assertFalse($this->cache->get($this->key));
	}

	public function test_get_KeyExists_ReturnsCachedValue() {
		$this->cache->set($this->key, 100);
		self::assertEquals(100, $this->cache->get($this->key));
	}

	/**
	 * increment()
	 */
	public function test_increment_KeyDoesNotExist_DoesNotAddToCache_ReturnsFalse() {
		self::assertFalse($this->cache->increment($this->key));
		self::assertFalse($this->cache->get($this->key));
	}

	public function test_increment_KeyIsExpired_DoesNotAddToCache_ReturnsFalse() {
		$this->cache->set($this->key, M_PI, strtotime('yesterday'));
		self::assertFalse($this->cache->increment($this->key));
		self::assertFalse($this->cache->get($this->key));
	}

	public function test_increment_KeyExists_IncrementsCachedValue_ReturnsNewValue() {
		$this->cache->set($this->key, 99);
		self::assertEquals(100, $this->cache->increment($this->key));
		self::assertEquals(100, $this->cache->get($this->key)); 

		$this->cache->set($this->key, 67);
		self::assertEquals(100, $this->cache->increment($this->key, 33));
	}

	/** @dataProvider dataProvider_increment */
	public function test_increment_KeyExists_ParameterAndCachedValueConvertedToIntegers($start_value, $increment_value, $expected_value) {
		$this->cache->set($this->key, $start_value);
		self::assertEquals($expected_value, $this->cache->increment($this->key, $increment_value));
	}
	
	public function dataProvider_increment() {
		return array(
			// $start_value, $increment_value, $expected_value
			array(99, 1, 100),
			array(101, -1, 100),
			array(67, 33, 100),
			array(100.99, 0.01, 100),
			array(100.01, 0.99, 100),
			array('a string', 1, 1),
			array('a string', M_PI, 3),
			array(100, 'a string', 100),
			array('a string', 'another string', 0),
		);
	}
	
	/**
	 * replace()
	 */
	public function test_replace_KeyDoesNotExist_DoesNotAddToCache_ReturnsFalse() {
		self::assertFalse($this->cache->replace($this->key, 100));
		self::assertFalse($this->cache->get($this->key));
	}

	public function test_replace_KeyIsExpired_DoesNotAddToCache_ReturnsFalse() {
		$this->cache->set($this->key, M_PI, strtotime('yesterday'));
		self::assertFalse($this->cache->replace($this->key, 100));
		self::assertFalse($this->cache->get($this->key));
	}

	public function test_replace_KeyExists_UpdatesCachedValue_ReturnsTrue() {
		$this->cache->set($this->key, M_PI);
		self::assertTrue($this->cache->replace($this->key, 100));
		self::assertEquals(100, $this->cache->get($this->key));
	}

	/**
	 * set()
	 */
	public function test_set_KeyDoesNotExist_AddsToCache_ReturnsTrue() {
		self::assertTrue($this->cache->set($this->key, 100));
		self::assertEquals(100, $this->cache->get($this->key));
	}

	public function test_set_KeyIsExpired_UpdatesCachedValue_ResetsTimeout_ReturnsTrue() {
		$this->cache->set($this->key, M_PI, strtotime('yesterday'));
		self::assertTrue($this->cache->set($this->key, 100));
		self::assertEquals(100, $this->cache->get($this->key));
	}

	public function test_set_KeyExists_UpdatesCachedValue_ReturnsTrue() {
		$this->cache->set($this->key, M_PI);
		self::assertTrue($this->cache->set($this->key, 100));
		self::assertEquals(100, $this->cache->get($this->key));
	}
}
