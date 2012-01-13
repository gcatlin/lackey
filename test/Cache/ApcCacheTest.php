<?php

class ApcCacheTest extends CacheTest {
	public function getCacheUrl() {
		return 'apc:';
	}
}
