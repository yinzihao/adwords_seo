<?php
//设置时区：等于date_default_timezone_set('PRC') 表示中国区
ini_set('date.timezone','Asia/Shanghai');

//设置php错误日志路径
ini_set('error_log', 'runtime/php_errors_log');

header('Content-Type: text/html; charset=UTF-8');
session_start();

$input = file_get_contents('php://input');

require __DIR__.'/dog/Core.php';


