<?php

/**
 * 框架核心初始化
 * @author Frank
 * 
 */
define('DS', DIRECTORY_SEPARATOR);
define('DOG_PATH', __DIR__.DS);
define('ROOT_PATH', dirname(__DIR__).DS);
define('LIB_PATH', dirname(__DIR__).DS.'lib'.DS);
define('CONFIG_PATH', dirname(__DIR__).DS.'config.ini');

define('LIBRARY_PATH', __DIR__.DS.'library'.DS);
define('CORE_PATH_FILENAME', DOG_PATH.'Core.php');
//程序运行模式包括：  cli,cgi
define('APP_RUN_MODE', 'cli');

require LIBRARY_PATH.'Loader.php';
require DOG_PATH.'App.php';

error_reporting (E_ALL & ~ E_WARNING);
function error_handler($error_level, $error_message, $file, $line) {
	$EXIT = FALSE;
	switch ($error_level) {
		case E_NOTICE:
		case E_USER_NOTICE:
			$error_type = 'Notice';
			break;
		case E_WARNING:
		case E_USER_WARNING:
			$error_type = 'Warning';
			break;
		case E_ERROR:
		case E_USER_ERROR:
			$error_type = 'Fatal Error';
			$EXIT = TRUE;
			break;
		default:
			$error_type = 'Unknown';
			$EXIT = TRUE;
			break;
	}
	$log = sprintf("%s: %s in %s on line %d\n", $error_type, $error_message, $file, $line);
	file_put_contents(ROOT_PATH.DS.'runtime'.DS.date('Y-m-d').'txt',$log);
	if ($EXIT) {
		die();
	}
}
set_error_handler('error_handler');


\dog\library\Loader::register();
\dog\App::run(empty($argv)?array():$argv);
