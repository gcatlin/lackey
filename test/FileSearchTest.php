<?php

class FileSearchTest extends PHPUnit_Framework_TestCase {
	public function test_FindThisFile() {
		$search = new FileSearch(__DIR__, '/'.basename(__FILE__).'/');
		self::assertEquals(array(__FILE__), $search->getMatches());
	}
}