<?php
namespace dog;
class Control{
	private $request = [];
	public function __construct(){
		$this->request = $_REQUEST;
	}
	
	protected  function get_value($key){
		return $this->request[$key];
	}
	
	protected  function exist_key($key){
		if(!isset($this->request[$key])) return false;
		return true;
	}
}