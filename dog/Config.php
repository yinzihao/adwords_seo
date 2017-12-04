<?php
namespace dog;

/**
 * 系统配置文件
 * @author yinjingjing
 *
 */
class Config{
	
	private $data = array();
	public function __construct($config_key = ''){
		$this->data = parse_ini_file(CONFIG_PATH,true);
		if($this->data && $config_key){
			$this->data = $this->data[$config_key];
		}
	}
	
	public function getData(){
		return $this->data;
	}
	
}