<?php
/**
 * Static class helping autoloading other classes.
 * Just call method register with parent directory of all classes.
 * {@link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md}
 */
class Autoloader {
	public static $directory = "";

	/**
	 * Registers own autoloader the SPL autoloader stack.
	 * @return boolean Returns true if registration was successful, false otherwise.
	 */
	public static function register($directory) {
		self::$directory = $directory;
		// as spl_autoload_register() disables __autoload() and
		//  this behavior might be unwanted, we put it onto autoload stack
		if (function_exists('__autoload')) {
		  spl_autoload_register('__autoload');
		}

		// Registers own loader function conforming
		// https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
		return spl_autoload_register(function ($className) {
			$className = ltrim($className, '\\');
			$fileName  = '';
			$namespace = '';
			if ($lastNsPos = strripos($className, '\\')) {
				$namespace = substr($className, 0, $lastNsPos);
				$className = substr($className, $lastNsPos + 1);
				$fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
			}
			$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

			require Autoloader::$directory.$fileName;
		});
	}
}