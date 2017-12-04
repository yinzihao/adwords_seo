<?php
namespace dog\library;

/**
 * 框架自动加载类
 * @author Frank
 *
 */
class Loader{
	
	/**
	 * 加载类
	 * @param string $class
	 */
	public static function autoload($class){
		spl_autoload_register(function($class){
			if($class){
				$file = str_replace('\\', '/', $class);
				$file .= '.php';
		
				if(file_exists($file)) include $file;
			}
		});
	}
	
	/**
	 * spl_autoload_register 
	 * @param string $autoload
	 */
	public static function register($autoload = ''){
		spl_autoload_register($autoload?:'\dog\library\Loader::autoload');
	}
}