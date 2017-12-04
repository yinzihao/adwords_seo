<?php
namespace dog\pattern;

/**
 * 公用的函数调用(带有匿名函数)
 * 1 计算算法耗时
 * ...
 * @author Frank
 *
 */
class CalculatorSETime {
	public static function arithmetic($function = null){
		$start_time = time();
		$function();
		return '算法耗时：'.(time()-$start_time);
	}
}
