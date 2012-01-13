<?php

if (!defined('T_TRAIT')) {
	define('T_TRAIT', 'trait');
}

class ClassExtractor {
	public static function getClasses($file) {
		$classFound      = false;
		$interfaceFound  = false;
		$nsFound         = false;
		$nsProc          = false;
		$inNamespace     = null;
		$inClass         = false;
		$bracketCount    = 0;
		$classBracket    = 0;
		$bracketNS       = false;
		$extendsFound    = false;
		$implementsFound = false;
		$useFound        = false;
		$lastClass       = '';
		$classNameStart  = false;
		$foundClasses    = array();

		$token = token_get_all(file_get_contents($file));
		foreach ($token as $pos => $tok) {
			if (!is_array($tok)) {
				switch ($tok) {
					case '{': {
						$bracketCount++;
						if ($nsProc) {
							$bracketNS = true;
						}

						$nsProc = false;
						$implementsFound = false;
						$extendsFound = false;
						$useFound = false;
						break;
					}
					case '}': {
						$bracketCount--;
						if ($bracketCount==0 && $inNamespace && $bracketNS) {
							$inNamespace = null;
						}
						if ($bracketCount == $classBracket) {
							$inClass = false;
						}
						break;
					}
					case ";": {
						if ($nsProc) {
							$nsProc	= false;
							$bracketNS = false;
						}
						break;
					}
					case ',': {
						if ($useFound) {
							$classNameStart = true;
						}
						break;
					}
				}
				continue;
			}

			switch ($tok[0]) {
				case T_CURLY_OPEN:
				case T_DOLLAR_OPEN_CURLY_BRACES: {
					$bracketCount++;
					continue;
				}
				case T_IMPLEMENTS: {
					$implementsFound = true;
					$classNameStart = true;
					continue;
				}
				case T_EXTENDS: {
					$extendsFound = true;
					$implementsFound = false;
					$classNameStart = true;
					continue;
				}
				case T_TRAIT:
				case T_CLASS: {
					$classFound = true;
					$inClass = true;
					$classBracket = $bracketCount + 1;
					continue;
				}
				case T_INTERFACE: {
					$interfaceFound = true;
					continue;
				}
				case T_NAMESPACE: {
					if ($token[$pos + 1][0] == T_NS_SEPARATOR) {
						// Ignore inline use of namespace keyword
						continue;
					}
					$nsFound = true;
					$nsProc = true;
					$inNamespace = null;
					continue;
				}
				case T_NS_SEPARATOR: {
					if ($nsProc) {
						$nsFound = true;
						$inNamespace .= '\\\\';
					}
					if ($extendsFound || $implementsFound) {
						$classNameStart = false;
					}
					continue;
				}
				case T_USE: {
					if ($inClass && ($bracketCount == $classBracket)) {
						$useFound = true;
					}
					continue;
				}
				case T_STRING: {
					if ($nsFound) {
						$inNamespace .= $tok[1];
						$nsFound = false;
					} elseif ($classFound || $interfaceFound) {
						$lastClass = $inNamespace ? $inNamespace .'\\\\' : '';
						$lastClass .= $tok[1];
						if (isset($foundClasses[$lastClass])) {
							throw new Exception(sprintf(
								"Redeclaration of class '%s' detected\n   Original:  %s\n   Secondary: %s\n\n",
								$lastClass,
								$foundClasses[$lastClass],
								$file
							));
						}
						$foundClasses[] = $lastClass;
						$classFound = false;
						$interfaceFound = false;
					} elseif ($extendsFound || $implementsFound || $useFound) {
						if ($classNameStart && $inNamespace) {
							$classNameStart = false;
						}
					}
					continue;
				}
			}
		}

		return $foundClasses;
	}
}