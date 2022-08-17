<?php
namespace app\home\controller;
use think\Cache;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
class Index
{
    public function index()
    {
    	$menberId = 1;
		$value = [1,2,3,4];
		//这里的参数我就不多说了，多看手册。
		cache('redis_value'.$menberId, $value);
		$menberId = 2;
		$value = [5,6,7,8];
		cache('redis_value'.$menberId, $value);
		$aa = cache('redis_value1');
		var_dump($aa);
		echo 'aa';exit;
    }
    public function test()
    {	
    	
    	$con = new \Redis();
        $con->connect(config('redis.host'), config('redis.port'), 5);
   		$con->auth('12345'); //密码验证
        $con->set('key','abcd');
        $key = $con->get('key');
        $key = $con->get('16-1568563200');
        var_dump($key);
		echo 'bb';exit;
    }
    function mq(){
    	$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
		$channel = $connection->channel();
		$channel->queue_declare('hello', false, false, false, false);
		$msg = new AMQPMessage('Hello World!');
		$channel->basic_publish($msg, '', 'hello');
		echo " [x] Sent 'Hello World!'\n";
		$channel->close();
		$connection->close();
    }
    function mqx(){
    	$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
    	$channel = $connection->channel();
    	$channel->queue_declare('hello', false, false, false, false);
    	echo " [*] Waiting for messages. To exit press CTRL+C\n";
    	$callback = function ($msg) {
    		echo ' [x] Received ', $msg->body, "\n";
    	};
    	$channel->basic_consume('hello', '', false, true, false, false, $callback);
    	while(count($channel->callbacks)) {
    		$channel->wait();
    	}
    	$channel->close();
    	$connection->close();
    }
}
