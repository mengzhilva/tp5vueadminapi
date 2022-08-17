<?php
namespace mylib;
use GuzzleHttp\json_decode;
use think\Db;

use think\facade\Cache;

class ipcheck{
	
	public static function checktask($ip,$id){
		if(strpos($ip, '.')!==false){
			$ip = ip2long($ip);
		}
		$key = $ip.'task'.$id;
		$num = Cache::get($key);
		if(Cache::store('redis')&&$num!==false){;
		}else{
			$num = Db::name('user_task_ip')->where('ip='.$ip.' and taskid='.$id)->count();
		}
		return $num;
	}
	public static function checkapp($ip,$id){
		if(strpos($ip, '.')!==false){
			$ip = ip2long($ip);
		}
		$key = $ip.'app'.$id;
		$num = Cache::get($key);
		if(Cache::store('redis')&&$num!==false){;
		}else{
			$num = Db::name('user_task_ip')->where('ip='.$ip.' and appid='.$id)->count();
		}
		return $num;
	}

	public static function ipcache($ip,$taskid,$appid){
		if(strpos($ip, '.')!==false){
			$ip = ip2long($ip);
		}
		if(Cache::store('redis')){
			$key = $ip.'task'.$taskid;
			$num = 0;
	        if(false !== Cache::get($key)&&!empty(Cache::get($key))) {
				$num = Cache::get($key);
	        }
			$num++;
			Cache::set($key, $num);
			$num = Cache::get($key);
			$key = $ip.'app'.$appid;
			$num = 0;
	        if(false !== Cache::get($key)&&!empty(Cache::get($key))) {
				$num = Cache::get($key);
	        }
			$num++;
			Cache::set($key, $num);
		}
		$data['ip'] = $ip;
		$data['taskid'] = $taskid;
		$data['appid'] = $appid;
		Db::name('user_task_ip')->insert($data);
	}
}