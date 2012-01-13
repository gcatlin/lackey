<?php

// Register a class loader for this library
require __DIR__ . '/ClassLoader.php';
$loader = new ClassLoader(__DIR__);
$loader->register();
$loader->setClassMap(array(
	'Autoloader'     => __DIR__ . '/Autoloader.php',
	'ApcCache'       => __DIR__ . '/Cache/ApcCache.php',
	'ArrayCache'     => __DIR__ . '/Cache/ArrayCache.php',
	'FileCache'      => __DIR__ . '/Cache/FileCache.php',
	'MemcachedCache' => __DIR__ . '/Cache/MemcachedCache.php',
	'NullCache'      => __DIR__ . '/Cache/NullCache.php',
	'Cache'          => __DIR__ . '/Cache.php',
	'ClassExtractor' => __DIR__ . '/ClassExtractor.php',
	'ClassLoader'    => __DIR__ . '/ClassLoader.php',
	'FileSearch'     => __DIR__ . '/FileSearch.php',
	'Logger'         => __DIR__ . '/Logger.php',
	'Template'       => __DIR__ . '/Template.php',
));

// Output new class map
// $class_map = $loader->getClassMap();
// $longest = strlen(array_reduce(array_keys($class_map), function($a, $b) {
// 	return (strlen($a) >= strlen($b) ? $a : $b); }
// ));
// foreach ($class_map as $class => $file) {
// 	printf(
// 		"\t'%s' %s=> __DIR__ . '%s',\n",
// 		$class,
// 		str_repeat(' ', $longest - strlen($class)),
// 		str_replace(__DIR__, '', $file)
// 	);
// }
