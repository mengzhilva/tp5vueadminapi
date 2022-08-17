<?php
namespace app\admin\controller;

use app\admin\model\Settings_module;
use think\facade\Session;
use think\Controller;
use think\facade\Log;
use think\Db;
use think\facade\Cache;
use think\facade\Request;
use app\admin\model\Settings_moduleModel;

class BaseController extends  Controller
{

	public function initialize()
	{
		$user = Session::get('userinfo');
		$this->chektoken();
		
	}
	public function json_success($msg , $data=[]) {
		echo json_encode(['msg' => $msg, 'code' => 0,'data'=>$data]);
		
	}
	
	public function json_error($msg,$data=[]) {
		 
		echo json_encode(['msg' => $msg, 'code' => 1,'data'=>$data]);
		
	}

	public function chektoken(){
		$token = Request::instance()->header('Authorization');
		$info=Db::name('user')->where(['token'=>$token])->find();
		
		if(!$token||!$info){
	
			echo json_encode(['msg' => '请求超时，请重新登录', 'code' => 400]);
			exit;
		}
		
		return $info;
	}
	
}