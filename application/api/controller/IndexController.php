<?php
namespace app\api\controller;
use app\api\controller\base\BaseController;
use think\facade\Log;
use think\facade\Request;
use think\Exception;
use think\Db;
use app\common\model\UserModel;
use think\facade\Cache;
use app\common\model\UserActiveDayModel;
use app\common\model\UserSignModel;
use app\common\model\CommonNoticeModel;
use GuzzleHttp\json_encode;

class IndexController extends BaseController
{


	function index(){
		echo 'aa';
	}
    //积分墙开关
    public function isshow() {
    	
        return json(['status' => $status,'url'=>$url,'ip'=>$ip,'ipaddress'=>json_encode($res),'bundID'=>$bundID,'request'=>json_encode($_REQUEST)]);
    }
	

    function dcwmsg(){
    	$request = Request();
    	$data = $request->param();
    	$phone = $data['phone'];
    	if(empty($phone)){
    		echo json_encode(['status'=>1,'msg'=>'请填写联系方式']);
    		exit;
    		
    	}
    	$content = $data['content'];
    	//if($_POST){
    	//$phone = input('post.phone');
    	//$content = input('post.content');
    	//}
    	//var_dump($data);
    	$dataa['phone'] = $phone;
    	$dataa['content'] = $content;
    	$dataa['addtime'] = time();
    	 
    	if($phone&&$content)Db::table('dcw_message')->insert($dataa);
    	echo json_encode(['status'=>1,'post'=>json_encode($_POST),'data'=>json_encode($data),'dataa'=>json_encode($dataa)]);
    }
}
