<?php
namespace app\api\controller\base;
use think\Controller;
use think\facade\Log;
use think\Db;
use think\facade\Cache;
use think\facade\Request;

class BaseController extends Controller
{
    public $G_PARAM = [];
    public $GP = [];
    public function initialize()
    {
        $this->init();
    }


    private function _getTestData() {
        $str = '{"device":"iPhone6","uid":"22","os":"12.4",
            "request_time":1567742230000,
            "udid":"6f4dfa9dccc9834bdfc8343926b3c2cb14e8c523",
        	"open_udid":"24bbd1c9682b49bb99bc09798402be5189688c33",
            "task_id":"26","ip":"10.1.20.91",
            "idfa":"6A06CB41-B9A4-4097-961E-36D835E0C3EB"}';

        $dd = json_decode($str, 1);
        return $dd;

    }

    public function init()
    {
        Log::error('code data: '. $this->request->param('code') );
        $this->_isTest = false;
		if(input('istest')){
        	$this->_isTest = true;
			
		}

        $code = $this->request->param('code');
        if($code || $this->_isTest) {
             //$code = 'JrRBpUiKxzbKlu4d+IGNjQ==';
            if($this->_isTest) {
                
                $this->G_PARAM = $this->_getTestData();
                
            } else {
                $r = $this->mk_decypt($code);
                $this->G_PARAM = json_decode( $r, 1 );
            }
          
            Log::error('parse data: '. json_encode($this->G_PARAM));

            if(!$this->G_PARAM) {
                return $this->json_error('请求错误');
            }
            $this->GP = $this->G_PARAM;
	    	$request = Request::instance();
	    	$ip = $request->ip();
            //$this->GP['ip'] = $ip;
        }
		
        $this->uid =  $this->G_PARAM['uid'] ?  $this->G_PARAM['uid'] : 0 ;

    }

    //检查请求数
    public function checkStartTaskNum($task_id, $act = 'inc') {
        $key = 'task_req_' . $task_id;
            Cache::set($key, 0);
        if(false === Cache::get($key)) {
            Cache::set($key, 0);
        }
        $d = Cache::get($key);
        $d < 0 && $d = 0;

       if( $d  <= 100) {
           Log::error('data='. $d);
           if($act == 'inc') {
                Cache::inc($key);//+
           } else {
                Cache::dec($key);//-
           }
            return true;
       }

       return false;
       
    }


    public function new_db_table($db_config, $table) {

        return Db::connect($db_config)->table($table);
        
    }

    public function new_db($db_config) {

        return Db::connect($db_config);

    }

    public function getRedis() {
        return Cache::store('redis');
    }

    public function json_success($msg) {
        return json(['msg' => $msg, 'status' => 1]);
    }

    public function json_error($msg) {
        if(isset($this->task_id)) {
            $this->checkStartTaskNum($this->task_id, 'dec');
        }
        return json(['msg' => $msg, 'status' => 0]);
    }

    public function show_data($info, $encypt = false) {
        if(isset($this->task_id))$this->checkStartTaskNum($this->task_id, 'dec');
        if($encypt) {

        }
        return json(['status' => 1, 'data' => $info]);
    }


    public function mk_encypt($data) {
    
        $data = json_encode($data);
        $method = 'AES-128-CBC';//加密方法
        $passwd = '8D4F16E8F94796FC';//加密密钥
        $options = 0;//数据格式选项（可选）
        $iv = '0102030405060708';//加密初始化向量（可选）
       
        $result = openssl_encrypt($data, $method, $passwd, OPENSSL_RAW_DATA, $iv);
        $result = base64_encode($result);  
        return $result;    
    
    }

    public function mk_decypt($data) {
     
       $method = 'AES-128-CBC';//加密方法
       $passwd = '8D4F16E8F94796FC';//加密密钥
       $options = 0;//数据格式选项（可选）
       $iv = '0102030405060708';//加密初始化向量（可选）
       $result = base64_decode($data);
       $result = openssl_decrypt($result, $method, $passwd,OPENSSL_RAW_DATA, $iv);
       return $result;

    }

    public function show_encypt_json2($data) {
        return  $this->show_encypt_json( $this->mk_encypt($data) );
    }


    public function show_encypt_json($data) {
        return json(['dataReq' => $data, 'dataSign' => strtoupper(md5($data)) ]);
    }


}
