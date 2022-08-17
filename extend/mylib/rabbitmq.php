<?php

namespace mylib;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use GuzzleHttp\json_decode;
use GuzzleHttp\json_encode;
use think\Db;
use app\common\model\CommonOutTaskModel;
use app\common\model\CommonOutTaskKeywordModel;
use app\common\model\CommonTaskKeywordModel;
//消息队列
class rabbitmq{
	//存储任务$data 字符串
	function savetask($data){
		if(is_test()){
			$this->dowork($data);
			return ;
			exit;
		}
		//作为任务发布者，
		$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
		
		
		$channel = $connection->channel();
		 
		$channel->queue_declare('task_queue_log', false, true, false, false);
		//$data = "work1";
		$data = json_encode($data); 
		$msg = new AMQPMessage($data,
				array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
		);
		 
		$channel->basic_publish($msg, '', 'task_queue_log');
		 
		//echo " [x] Sent ", $data, "\n";
		 
		$channel->close();
		$connection->close();
	}
	//执行任务
	function runwork(){

		//作为worker执行任务
		$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
		$channel = $connection->channel();
		 
		$channel->queue_declare('task_queue_log', false, true, false, false);
		 
		echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
		 
		$callback = function($msg){
    		$this->dowork($msg);
    		//$msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    	};
		$channel->basic_qos(null, 1, null);
		$channel->basic_consume('task_queue_log', '', false, false, false, false, $callback);
		 
		while(count($channel->callbacks)) {
			$channel->wait();
		}
		 
		$channel->close();
		$connection->close();
	}
	function dowork($msg){
			$data = json_decode($msg->body,true);
			if(!is_test()){
				echo " [x] Received ", $msg->body, "\n";
			}else{
				$data = $msg;
			}
			if($msg->body!='"[\"abc\",\"ddd\"]"'){
			//sleep(substr_count($msg->body, '.'));
			//处理程序
			switch ($data['type']){
				case 'union_repeat':
					$this->union_repeat($data['data']);
					break;
				case 'union_active':
					$this->union_active($data);
					break;
				case 'union_click':
					$this->union_click($data);
					break;
				case 'task_active':
					$this->task_active($data);
					break;
				case 'task_click':
					$this->task_click($data);
					break;
				case 'task_repeat':
					$this->task_repeat($data);
					break;
				
			}
			
			}
			if(!is_test()){
			echo " [x] Done", "\n";
			$msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
			}
	}
    public function new_db($db_config) {
		
        return Db::connect($db_config);

    }
    function task_click($data){
    	$param = $data['param'];
    	$postData = $data['postData'];
    	//点击日志数据组成
    	$clickData = ['appid' => $param['appid'],
    	'ad_user_id' => $param['ad_user_id'], 'idfa' => $param['idfa'],
    	'keyword' => $param['keyword'],
    	'url' => $postData['url'],
    	'result' => $postData['result'],
    	'create_at' => time()
    	];
		$postData['keyword'] = $data['keyword'];
    	
    	$this->new_db('db_log_check')->table('click_log')->insertGetId($clickData);
    	
    	$table = $this->new_db('db_log_check')->table(mk_get_table_name($data['bundleid']));
    	
    	$postDatanew = $postData;
    	unset($postDatanew['ip']);
    	unset($postDatanew['os']);
    	unset($postDatanew['device']);
    	$id = $table->insertGetId($postDatanew);
    	 
    }
    private function _insert_mbund_repeat_idfa($bundleid, $data) {
        
        $bundleid = mk_get_table_name($bundleid);
        $bundleTable =  $this->new_db('db_bunds')->table($bundleid);
        return $bundleTable->insertGetId($data);

    }
    function task_repeat($data){

    	$param = $data['param'];
    	$postData = $data['postData'];
    	$bundleid = $data['bundleid'];
		$postData['keyword'] = $data['keyword'];
    	//查询去重日志记录数据
    	$repeat_log = [
    	'appid' => $param['appid'],
    	'ad_user_id' => $param['ad_user_id'],
    	'idfa' => $param['idfa'],
    	'keyword' => $param['keyword'],
    	'url' => $postData['url'],
    	'result' => $postData['result'],
    	'create_at' => time()
    	];
    	
    	$res = $this->new_db('db_log_check')->table('repeat_log')->insert($repeat_log);

    	
    	$table = $this->new_db('db_log_check')->table(mk_get_table_name($bundleid));
    	
    	$postDatanew = $postData;
    	unset($postDatanew['ip']);
    	unset($postDatanew['os']);
    	unset($postDatanew['device']);
    	$id = $table->insertGetId($postDatanew);
    	
    	if($id) {
    	
    		$this->_insert_mbund_repeat_idfa($bundleid . '_repeat',
    				['uid' => $param['uid'],
    				'idfa' => $param['idfa'],
    				'repeat_id' => $id,
    				'task_id' => $param['task_id'],
    				'create_at' => time()
    				]
    		);
    	
    		
    	
    	}
    }
    function task_active($data){

    	$param = $data['param'];
    	$postData = $data['postData'];
    	$bundleid = $data['bundleid'];
    	$taskInfo = $data['taskInfo'];
    	$activeLog = $postData;
    	$postData['keyword'] = $data['keyword'];
    	unset($activeLog['udid']);
    	unset($activeLog['uid']);
    	unset($activeLog['task_id']);
    	unset($activeLog['stype']);
    	$activeLog['appid'] = $data['appid'];
    	if($data['app']['type']!=2){
	    	//快速任务 修改任务完成数量
	    	$km = new CommonTaskKeywordModel();
	    	$keywor = $km->where(['id' => $taskInfo['keyword_id']])
	    	->field('id,com_num')->find();
	    	if($keywor){
	    		$d['com_num'] = $keywor['com_num']+1;
	    		$km->where(['id' => $taskInfo['keyword_id']])->update($d);
	    	}
    	}
    	$active_log_id = db('active_log', 'db_log_check')->insertGetId($activeLog);
    	$table = $this->new_db('db_log_check')->table(mk_get_table_name($bundleid));
    	$postDatanew = $postData;
    	unset($postDatanew['ip']);
    	unset($postDatanew['os']);
    	unset($postDatanew['device']);
    	$id = $table->insertGetId($postDatanew);
    	
    	if($id) {
    	
    		$data = ['idfa' => $param['idfa'],
    		'uid' => $param['uid'],
    		'task_id' => $param['task_id'],
    		'auser_id' => $postData['ad_user_id'],
    		'activate_id' => $id,
    		'keyword_id' => $taskInfo['keyword_id'],
    		'keyword' => $taskInfo['keyword'],
    		'status' => 1,
    		'create_at' => time(),
    		'ip' => $postData['ip'],
    		'os' => $postData['os'],
    		'device' => $postData['device']
    		];
    		$ishave = $this->new_db('db_bunds')->table(mk_get_table_name($bundleid) . '_active' )->where(['idfa'=>$param['idfa']])->find();
    		if(!$ishave){
    			$this->new_db('db_bunds')->table(mk_get_table_name($bundleid) . '_active')->insert($data);
    		}
    	}
    }
	function union_repeat($data){
		$id = 0;//repeatlog 的id
		foreach ($data['db_log_check'] as $k=>$v){
			$res = $this->new_db('db_log_check')->table($v['table'])->insertGetId($v['data']);
			if($v['table']=='repeat_log'){
				$id = $res;
			}
		}
		foreach ($data['db_bunds'] as $k=>$v){
			$v['data']['repeat_id'] = $id;
			$res = $this->new_db('db_bunds')->table($v['table'])->insertGetId($v['data']);
				
		}
	}
	function union_active($data){
		$postData = $data['postData'];
		$appinfo = $data['appinfo'];
		$postData['task_id'] = $postData['task_id']+0;
		$clickData = [
		'appid' => $data['appid'],
		'ad_user_id' => $data['appinfo']['ad_user_admin_id'],
		'idfa' => $postData['idfa'],
		'keyword' => $data['keyword'],
		'client_url' => $postData['client_url'],
		'client_result' => $postData['client_result'],
		'url' => $postData['url'],
		'result' => $postData['result'],
		'create_at' => time()
		];
		$postData['keyword'] = $data['keyword'];
		$rid = $this->new_db('db_log_check')->table('active_log')->insertGetId($clickData);
		
		$table = $this->new_db('db_log_check')->table(mk_get_table_name($appinfo['bundleid']));
		
		$postDatanew = $postData;
		unset($postDatanew['ip']);
		unset($postDatanew['os']);
		unset($postDatanew['device']);
		$id = $table->insertGetId($postDatanew);
		if($id) {
			$ishave = $this->new_db('db_bunds')->table(mk_get_table_name($appinfo['bundleid']) . '_active' )->where(['idfa'=>$postData['idfa']])->find();
			if(!$ishave){
				$this->new_db('db_bunds')->table( mk_get_table_name($appinfo['bundleid']) . '_active' )->insertGetId([
						'idfa' => $postData['idfa'],
						'uid' => 0,
						'task_id' => 0,
						'auser_id' => $appinfo['ad_user_admin_id'],
						'out_auser_id' => $postData['ad_user_id'],
						'activate_id' => $id,
						'keyword' => $clickData['keyword'],
						'status' => 1,
						'create_at' => time()
						]);
			}
					//var_dump($appinfo);
			$ishave = $this->new_db('db_bunds')->table( 'outer_active' )->where(['idfa'=>$postData['idfa']])->find();
			if(!$ishave){
				$outactive = [
				'idfa' => $postData['idfa'],
				'uid' => 0,
				'task_id' => 0, 'appid' => $appinfo['id'],
				'auser_id' => $appinfo['ad_user_admin_id'],
				'out_auser_id' => $postData['ad_user_id'],
				'activate_id' => $id,
				'keyword' => $clickData['keyword'],
				'ip' => $postData['ip'],
				'device' => $postData['device'],
				'status' => 1,
				'create_at' => time()
				];
				//记录下放任务激活完成数量mk_common_out_task的com_num 和关键词com_num
				$outtask = $data['outtask'];
				if($data['outtask']){ //如果查询到外放任务
					//var_dump($appinfo);
					//判断产品是否是回调，如果回调则暂不加完成，在回调里完成
					if($appinfo['type'] !=2){
	        			$outtaskm = new CommonOutTaskModel();
						$d['com_num'] = $outtask['com_num']+1;
						$outtaskm->where('id='.$outtask['id'])->update($d);
						//修改关键词完成数量
						$outtaskmkey = new CommonOutTaskKeywordModel();
						$keyw = $outtaskmkey->where('task_id='.($outtask['id']+0))->select()->toarray();
			
						if($keyw){
							foreach ($keyw as $k=>$v){
								if($v['keyword'] == $clickData['keyword']){
									$dk['com_num'] = $v['com_num']+1;
									$outtaskmkey->where('id='.$v['id'])->update($dk);
								}
							}
						}
					}else{
						//如果是回调
						$outactive['status'] = 0;
					}
				}
				$this->new_db('db_bunds')->table('outer_active')->insertGetId($outactive);
				
			}
		
		
		}
	}
	function union_click($data){
		$postData = $data['postData'];
		$appinfo = $data['appinfo'];
		$zq_callback = $data['zq_callback'];
		$appid = $data['appid'];
		$idfa = $postData['idfa'];
		$keyword = $data['keyword'];
		$outer_user_id = $postData['ad_user_id'];

		$postData['keyword'] = $keyword;
		$clickData = [
		'appid' => $appid,
		'ad_user_id' => $appinfo['ad_user_admin_id'],
		'idfa' => $idfa,
		'keyword' => $keyword,
		'client_url' => $postData['client_url'],
		'client_result' => $postData['client_result'],
		'url' => $postData['url'],
		'result' => $postData['result'],
		'create_at' => time()
		];
		if($zq_callback&&$appinfo['type']==2){
			$clickData['callback'] = $zq_callback;
			//如果是回调类型任务
			//存日志
			$dataout['appid'] = $appid;
			$dataout['idfa'] = $idfa;
			$dataout['url'] = urldecode($clickData['callback']);
			$dataout['out_id'] = $outer_user_id;
			$dataout['form_is_back'] = 0;
			$dataout['out_is_back'] = 0;
			$dataout['click_time'] = time();
			$dataout['keyword'] = $keyword;
			$this->new_db('db_log_check')->table('callback_out_log')->insertGetId($dataout);
			 
		}
		// var_dump($clickData);
		$this->new_db('db_log_check')->table('click_log')->insertGetId($clickData);
		
		$table = $this->new_db('db_log_check')->table(mk_get_table_name($appinfo['bundleid']));
    	$postDatanew = $postData;
    	unset($postDatanew['ip']);
    	unset($postDatanew['os']);
    	unset($postDatanew['device']);
    	$id = $table->insertGetId($postDatanew);
		
	}
}