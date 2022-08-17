<?php
namespace mylib;
use Redis;


class myredis{
	private static  $inc;
	public function __construct(){
        self::$inc = new Redis();
		self::$inc->connect('127.0.0.1', 6379);
		self::$inc->auth('12345');
	}
	public static function getRedis(){
		if(!self::$inc){
			 new self();
		}
		return self::$inc;
	}

	private function __clone(){}
}