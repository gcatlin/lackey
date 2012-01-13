<?php

class ArrayCacheTest extends CacheTest {
	public function getCacheUrl() {
		return 'array:';
	}

	public function fillCache($cache, $num_items=3) {
		for ($i = 0; $i < $num_items; $i++) {
			$cache->set(md5(uniqid()), rand());
		}
	}
	
	/**
	 * flush()
	 */
	public function test_flush_DeletesCache_ReturnsTrue() {
		$this->fillCache($this->cache);
		self::assertTrue($this->cache->flush());

		$empty_list = $this->cache->getAll();
		self::assertTrue(is_array($empty_list));
		self::assertEquals(0, count($empty_list));
	}
		
	/**
	 * getAll()
	 */
	public function test_getAll_ReturnsCacheArray() {
		$num_items = 5;
		$this->fillCache($this->cache, $num_items);
		$all_items = $this->cache->getAll();
		self::assertTrue(is_array($all_items));
		self::assertEquals($num_items, count($all_items));
	}

	/**
	 * set()
	 */
	public function test_set_ExceedsMaxItems_OldestItemIsDeleted_SizeStaysAtMaxItems() {
		$max_items = 10;
		$cache = new ArrayCache(null, null, null, array('max_items' => $max_items));
		$cache->set('first_key', M_PI);
		$this->fillCache($cache, $max_items - 1);

		self::assertTrue($cache->set('new_key', 100));
		self::assertFalse($cache->get('first_key'));
		self::assertEquals($max_items, count($cache->getAll()));
	}
}
