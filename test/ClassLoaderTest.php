<?php

// setClassMap/getClassMap

// getClassMap builds if not yet built

// ClassMap building if path is not empty
// 	can use setClassMap(null) to force this

// duplicate class name

class ClassLoaderTest extends PHPUnit_Framework_TestCase {
	public function test_SupplyingInvalidReadCallbackThrowsException() {
		try {
			ClassLoader::autoRegister('', 'invalid_callback');
		} catch (InvalidArgumentException $e) {
			return;
		}

		$this->fail();
	}

	public function test_SupplyingInvalidWriteCallbackThrowsException() {
		try {
			ClassLoader::autoRegister('', 'strlen', 'invalid_callback');
		} catch (InvalidArgumentException $e) {
			return;
		}

		$this->fail();
	}

	public function test_RegisteringAddsToAutoloadStack() {
		$loader = new ClassLoader('');
		$this->assertFalse(array_search(array($loader, 'load'), spl_autoload_functions(), true) !== false);
		$this->assertTrue($loader->register());
		$this->assertTrue(array_search(array($loader, 'load'), spl_autoload_functions(), true) !== false);
		$loader->unregister();
	}

	public function test_AutoRegisteringAddsToAutoloadStack() {
		$loader = ClassLoader::autoRegister('');
		$this->assertTrue(array_search(array($loader, 'load'), spl_autoload_functions(), true) !== false);
		$loader->unregister();
	}

	public function test_UnregisteringRemovesFromAutoloadStack() {
		$loader = ClassLoader::autoRegister('');
		$this->assertTrue($loader->unregister());
		$this->assertFalse(array_search(array($loader, 'load'), spl_autoload_functions(), true) !== false);
		$loader->unregister();
	}

	public function test_LoadingMissingFileTriggersRebuildingTheClassMap() {
		$class = 'TestMissingFile';
		$file = __DIR__ . "/{$class}.php";
		$loader = ClassLoader::autoRegister('.');
		$loader->setClassMap(array($class => ''));
		$this->assertNotContains($file, $loader->getClassMap());
		new $class;
		$this->assertContains($file, $loader->getClassMap());
		$loader->unregister();
	}

	public function test_InstantiatingUndeclaredClassFindsAndLoadsProperFile() {
		// this test leverages the class loader registered via bootstrap.php
		$class = 'TestUndeclaredClass';
		$this->assertFalse(class_exists($class, false));
		new $class();
		$this->assertTrue(class_exists($class, false));
	}

	public function test_AccessingNullClassMapTriggersBuildingClassMap() {
		$loader = ClassLoader::autoRegister('.');
		$loader->setClassMap(null);
		$this->assertNotNull($loader->getClassMap());
		$loader->unregister();
	}

	public function test_PathIsReadable() {
		$path = uniqid();
		$loader = ClassLoader::autoRegister($path);
		$this->assertEquals($path, $loader->getPath());
		$loader->unregister();
	}

	/**
	 * @dataProvider dataProvider_RawAndNormalizedPaths
	 */
	public function test_PathsAreCanonicalizedAndDeduplicatedAndSorted($raw_path, $normalized_path) {
		$loader = ClassLoader::autoRegister($raw_path);
		$this->assertEquals($normalized_path, $loader->getNormalizedPath());
		$loader->unregister();
	}

	public function dataProvider_RawAndNormalizedPaths() {
		return array(
			array('.', __DIR__),
			array('.:.', __DIR__),
			array('../', dirname(__DIR__)),
			array('/c:/a:/b', '/a:/b:/c'),
		);
	}

	// @TODO need to support non-recursive FileSearch, or ignore lists/patterns
	// public function test_InstantiatingDuplicateClassNameThrowsException() {
	// 	ClassLoader::autoRegister(__DIR__);

	// 	try {
	// 		new DuplicateClass();
	// 	} catch (Exception $e) {
	// 		return;
	// 	}

	// 	$this->fail();
	// }

}
