<?php
namespace dog\db;

use dog\Config;
class Db
{
	private $host = null;
	private $username = null;
	private $password = null;
	private $port = null;
	private $dbname = null;
	
	private static $db_con = null;
	
	public function __construct()
	{
		$data = (new Config('mysql'))->getData();
		$this->host = $data['host'];
		$this->port = empty($data['port'])?3306:$data['port'];
		$this->dbname = $data['dbname'];
		$this->username = $data['username'];
		$this->password = $data['password'];
		if(!self::$db_con){
			self::$db_con = mysqli_connect($this->host,$this->username,$this->password,$this->dbname,$this->port);
			if (mysqli_connect_errno(self::$db_con)){
				throw new \Exception("连接 MySQL 失败: " . mysqli_connect_error());	
			}
		}
	}
	
	public function execute($sql)
	{
		return mysqli_query(self::$db_con, $sql);
	}
	
	public function getError(){
		return mysqli_connect_error();
	}

	public function select($sql)
	{
		$result = mysqli_query(self::$db_con,$sql);
		$data = [];
		while ($row = mysqli_fetch_assoc($result)){
			$data[] = $row;
  		}
		return $data;
	}
	
	public function selectInfo($sql){
		$data = $this->select($sql); 
		if(empty($data)){
			return array();
		}else{
			return $data[0];
		}
	}
	
	public function __destruct(){
		//mysqli_close(self::$db_con);
	}
}