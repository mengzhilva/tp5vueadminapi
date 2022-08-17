<?php
namespace mylib;

use app\common\model\CommonTaskModel;
use app\common\model\UserActiveDayModel;
use app\common\model\UserModel;
use app\common\model\UserWithdrawLogModel;
use app\common\model\UserBalanceLogModel;
use app\common\model\UserTaskModel;

class charts{
	
	public static $daytime;
	public function __construct($daytime=''){
		if(!$daytime){
			$daytime = date('Y-m-d');
		}
		self::$daytime = $daytime;
	}
	//投放任务
	public static function task($daytime=''){
		if(!$daytime){
			$daytime = date('Y-m-d');
		}
		//投放任务
		$commontask = new CommonTaskModel();
		$task = $commontask->where(' dateline >='.strtotime($daytime.'00:00:00').' and dateline<='.strtotime($daytime.'23:59:59'))->sum('amount');
		if($task<0){
			$task = 0;
		}
		return $task;
	}
	//完成任务
	public static function taskf($daytime=''){
		if(!$daytime){
			$daytime = date('Y-m-d');
		}

		//完成任务
		$commontask = new UserTaskModel();
		$taskf = $commontask->where('result=1 and complete_time >='.strtotime($daytime.'00:00:00').' and complete_time<='.strtotime($daytime.'23:59:59'))->count();
		if($taskf<0){
			$taskf = 0;
		}
		return $taskf;
	}
		//日活
	public static function day($daytime=''){
		if(!$daytime){
			$daytime = date('Y-m-d');
		}

		//日活
		$UserActiveDayModel = new UserActiveDayModel();
		$daytime = strtotime($daytime);
		$day = $UserActiveDayModel->getNum(['daytime'=>$daytime]);
		return $day;
	}
		//新增
	public static function useradd($daytime=''){
		if(!$daytime){
			$daytime = date('Y-m-d');
		}

		//新增
		$user = new UserModel();
		$usernum = $user->getNum(' create_time >='.strtotime($daytime.'00:00:00').' and create_time<='.strtotime($daytime.'23:59:59'));

		return $usernum;
	}
	//用户取现
	public static function withdraw($daytime=''){
		if(!$daytime){
			$daytime = date('Y-m-d');
		}

		$user = new UserWithdrawLogModel();
		$usernum = $user->getSum(' timeline >='.strtotime($daytime.'00:00:00').' and timeline<='.strtotime($daytime.'23:59:59'));

		return $usernum;
	}
	//用户真实取现
	public static function withdraw_true($daytime=''){
		if(!$daytime){
			$daytime = date('Y-m-d');
		}

		$user = new UserWithdrawLogModel();
		$usernum = $user->getSum('status=3 and timeline >='.strtotime($daytime.'00:00:00').' and timeline<='.strtotime($daytime.'23:59:59'));

		return $usernum;
	}
	//用户收入
	public static function income($daytime=''){
		if(!$daytime){
			$daytime = date('Y-m-d');
		}

		$user = new UserBalanceLogModel();
		$usernum = $user->getSum(' dateline >='.strtotime($daytime.'00:00:00').' and dateline<='.strtotime($daytime.'23:59:59'));

		return $usernum;
	}
	//任务支出
	public static function taskpay($daytime=''){
		if(!$daytime){
			$daytime = date('Y-m-d');
		}

		$user = new UserTaskModel();
		$usernum = $user->getSum(' complete_time >='.strtotime($daytime.'00:00:00').' and complete_time<='.strtotime($daytime.'23:59:59'));

		return $usernum;
	}
	
}