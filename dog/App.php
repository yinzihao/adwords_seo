<?php
namespace dog;

/**
 * 框架入库应用处理
 * @author Frank
 *
 */
class App{
	public static function run($argv = array()){
		//加载应用/控制器/方法
		
		if(APP_RUN_MODE == 'cli'){
			//cli运行模式下获取参数
			$uri_array = explode('/', $argv[1]);
			array_unshift($uri_array, '');
		}else{
			/**
			 * 获取url info 地址
			 * 例如：http://www.project.com/index.php/index/index/run?id=1
			 * 只会获取/index/index/run 这一截
			 * 如果是uri的方式获取就是/index.php/index/index/run?id=1
			 */
			$uri = $_SERVER['PATH_INFO'];
			$uri_array = explode('/', $uri);
		}
		$app_name = !empty($uri_array[1])?$uri_array[1]:'index';
		$control_name = !empty($uri_array[2])?$uri_array[2]:'index';
		$method_name = !empty($uri_array[3])?$uri_array[3]:'index';
		$class_r = new \ReflectionClass("\\app\\$app_name\\control\\".$control_name);
		$control = $class_r->newInstance();
		if(!$class_r->hasMethod($method_name)) {
			echo "[Method ERROR] $control_name->$method_name, Method '$method_name' not found"; 
			return;
		}
		$method = $class_r->getMethod($method_name);
		echo $method->invoke($control);exit;
	}
}