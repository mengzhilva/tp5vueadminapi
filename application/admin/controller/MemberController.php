<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\facade\Request;
use think\facade\Session;
use app\admin\model\UserModel;


class MemberController extends  Controller
{


	public function loginin()
	{
		$request = Request::instance();
		$data = $request->param();
		$model = new UserModel();
		$user = $model->getOne(array('username'=>$data['phone']));
		if($user&&$user['password'] == md5(md5($data['password']))){
			session::set('userinfo',$user);
			$token = md5($user['username'].time());
			$model->updatedatas(['token'=>$token, 'updatetime' => date('Y-m-d H:i:s', time())],['id'=>$user['id']]);
			$info=Db::name('user')->where(['username'=>$data['phone']])->find();
			
			$this->json_success('成功',$info);
		}else{
			$this->json_error('密码错误');
		}
	
	}

	public function json_success($msg , $data=[]) {
		echo json_encode(['msg' => $msg, 'code' => 0,'data'=>$data]);
		
	}
	
	public function json_error($msg,$data=[]) {
		 
		echo json_encode(['msg' => $msg, 'code' => 1,'data'=>$data]);
		
	}
	
}
