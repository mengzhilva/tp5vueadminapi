<?php
use GuzzleHttp\json_decode;
use mylib\IpLocation;
use think\facade\Cache;
use Curl\Curl;
use mylib\Smtp;
use app\common\model\UserAppModel;
use app\common\model\UserModel;
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
error_reporting(E_ERROR | E_WARNING | E_PARSE);
//是否是测试服务器
function is_test(){
	if(empty($_SERVER['HTTP_HOST'])){
		return false;
	}
    if(($_SERVER['HTTP_HOST'] != 'itest3.1314wallet.com'&&$_SERVER['HTTP_HOST'] != 'itest2.1314wallet.com'&&$_SERVER['HTTP_HOST'] != 'ssl.1314wallet.com')){

    	return  true;
    }
    return false;
}
function mk_get_rand_str($len){
    $str = "1234567890asdfghjklqwertyuiopzxcvbnmASDFGHJKLZXCVBNMPOIUYTREWQ";
    return substr(str_shuffle($str),0,$len);
}
//获取app排名接口
function getrank($appid,$keywords){
	$url = 'http://open.api.ddashi.com/token';
	$data['akid'] = 'ihongbao';
	$data['aks'] = '4aedebf5a1c3fa';
	$curl = new Curl();
	$res = $curl->post($url,$data);
	$token = $res->response;
	$urls = 'http://open.api.ddashi.com/api/v2/rank/'.$appid;
	$datas['token'] = $token;
	$datas['keywords'] = rtrim($keywords,',');
	$res = $curl->post($urls,$datas);
	$res = json_decode($res->response,true);
	return $res;
}
//返回毫秒
function get_total_millisecond()
{
  $time = explode (" ", microtime () );
  $time = $time [1] . ($time [0] * 1000);
  $time2 = explode ( ".", $time );
  $time = $time2 [0];
  return $time;
}
//人民币大小写转
function numTrmb($num){
	$d = array("零", "壹", "贰", "叁", "肆", "伍", "陆", "柒", "捌", "玖");
	$e = array('元', '拾', '佰', '仟', '万', '拾万', '佰万', '仟万', '亿', '拾亿', '佰亿', '仟亿');
	$p = array('分', '角');
	$zheng = "整";
	$final = array();
	$inwan = 0;//是否有万
	$inyi = 0;//是否有亿
	$len = 0;//小数点后的长度
	$y = 0;
	$num = round($num, 2);//精确到分
	if(strlen($num) > 15){
		return "金额太大";
		die();
	}
	if($c = strpos($num, '.')){//有小数点,$c为小数点前有几位
		$len=strlen($num)-strpos($num,'.')-1;//小数点后有几位数
	}else{//无小数点
		$c = strlen($num);
		$zheng = '整';
	}
	for($i = 0; $i < $c; $i++){
		$bit_num = substr($num, $i, 1);
		if ($bit_num != 0 || substr($num, $i + 1, 1) != 0) {
			@$low = $low . $d[$bit_num];
		}
		if ($bit_num || $i == $c - 1) {
			@$low = $low . $e[$c - $i - 1];
		}
	}
	if($len!=1){
		for ($j = $len; $j >= 1; $j--) {
			$point_num = substr($num, strlen($num) - $j, 1);
			@$low = $low . $d[$point_num] . $p[$j - 1];
		}
	}else{
		$point_num = substr($num, strlen($num) - $len, 1);
		$low=$low.$d[$point_num].$p[$len];
	}
	$chinses = str_split($low, 3);//字符串转化为数组
	for ($x = count($chinses) - 1; $x >= 0; $x--) {
		if ($inwan == 0 && $chinses[$x] == $e[4]) {//过滤重复的万
			$final[$y++] = $chinses[$x];
			$inwan = 1;
		}
		if ($inyi == 0 && $chinses[$x] == $e[8]) {//过滤重复的亿
			$final[$y++] = $chinses[$x];
			$inyi = 1;
			$inwan = 0;
		}
		if ($chinses[$x] != $e[4] && $chinses[$x] !== $e[8]) {
			$final[$y++] = $chinses[$x];
		}
	}
	$newstr = (array_reverse($final));
	$nstr = join($newstr);
	if((substr($num, -2, 1) == '0') && (substr($num, -1) <> 0)){
		$nstr = substr($nstr, 0, (strlen($nstr) -6)).'零'. substr($nstr, -6, 6);
	}
	$nstr=(strpos($nstr,'零角')) ? substr_replace($nstr,"",strpos($nstr,'零角'),6) : $nstr;
	return $nstr = (substr($nstr,-3,3)=='元') ? $nstr . $zheng : $nstr;
}
///查询微信weixin_openid绑定次数
function getweixin_openidnums($weixin_openid){
	$num =0;
	if($weixin_openid){
	$model = new UserModel();
	$num = $model->where(['weixin_openid'=>$weixin_openid])->count();
	}
	return $num;
}
///查询支付宝姓名绑定次数
function getalipaynamenums($alipayname){
	$num =0;
	if($alipayname){
	$model = new UserModel();
	$num = $model->where(['alipayname'=>$alipayname])->count();
	}
	return $num;
}
		//徒弟封号，师傅减钱
function sonstatus($uid,$status){
		//徒弟封号，师傅减钱
		$userinfo =  model('User')->where(['id' => $uid])->find();
		if($userinfo['parent_uid']){
			$money = model('UserBalanceLog')->where(['uid' => $userinfo['parent_uid'],'sonuid'=>$uid])->sum('money');
			if($money){
				$data = [];
				if($status == 0){
					$data['money'] = -$money;
					$data['type'] = 7;
				}else{
					$data['money'] = $money;
					$data['type'] = 8;
				}
				$data['uid'] = $userinfo['parent_uid'];
				model('UserBalanceLog')->userStatus($data);
			}
		}
	}
/**
 * 获取指定日期段内每一天的日期
 * @param  Date  $startdate 开始日期
 * @param  Date  $enddate   结束日期
 * @return Array
 */
function getDateFromRange($startdate, $enddate){

	$stimestamp = strtotime($startdate);
	$etimestamp = strtotime($enddate);

	// 计算日期段内有多少天
	$days = ($etimestamp-$stimestamp)/86400+1;

	// 保存每天日期
	$date = array();

	for($i=0; $i<$days; $i++){
		$date[] = date('Y-m-d', $stimestamp+(86400*$i));
	}

	return $date;
}
//转换table名称
function mk_get_table_name($name, $f = 1) {
    if($f){
    	$name = str_replace('-', '__', $name);
    	return str_replace('.', '_', $name);
    }
    $name = str_replace('__', '-', $name);
    return str_replace('_', '.', $name);
}
//去除表情
function filterEmoji($text, $replaceTo = ''){
	$clean_text = "";
	// Match Emoticons
	$regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
	$clean_text = preg_replace($regexEmoticons, $replaceTo, $text);
	// Match Miscellaneous Symbols and Pictographs
	$regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
	$clean_text = preg_replace($regexSymbols, $replaceTo, $clean_text);
	// Match Transport And Map Symbols
	$regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
	$clean_text = preg_replace($regexTransport, $replaceTo, $clean_text);
	// Match Miscellaneous Symbols
	$regexMisc = '/[\x{2600}-\x{26FF}]/u';
	$clean_text = preg_replace($regexMisc, $replaceTo, $clean_text);
	// Match Dingbats
	$regexDingbats = '/[\x{2700}-\x{27BF}]/u';
	$clean_text = preg_replace($regexDingbats, $replaceTo, $clean_text);
	return $clean_text;
}
/**
 * @author ddz
 * @copyright 2010
 * $path 引用外部的xml文件路径
 * $num  需要修改的节点数据
 * $node 需要修改的节点名
 */
function changexml_fun($path,$val,$pathto){
	//$pathto = WEB_ROOT .'static/udid.mobileconfig.xml';
	$xml = new DOMDocument();
	$xml->load($path);
	foreach($xml->getElementsByTagName('string') as $list){
		$value = $list->nodeValue;
		if($value=='getUuid'){
			$list->nodeValue=$val;
			$xml->save($pathto); //最后保存新生成的xml文件
		}
	}
}
//获取ip地址
function getipaddress($ipOrDomain){
	$iplocation = new IpLocation();
	$iplocation->IpLocation();
	$location = $iplocation->getlocation($ipOrDomain);
	//var_dump($location);
	$country=mb_convert_encoding($location['country'], "utf-8", "gbk");
	
	$area=mb_convert_encoding($location['area'], "utf-8", "gbk");
	$arr['country'] = $country;
	$arr['area'] = $area;
	//var_dump($arr);
	return $arr;
}
//获取ip地址
function getipaddressxq($ipOrDomain){
	$arr = getipaddress($ipOrDomain);
	return $arr['country'].$arr['area'];
}
//发送邮件
function sendemail($mailto,$mailsubject,$mailbody){
	//$mailto='878544154@qq.com';
	//$mailsubject="下放任务";
	//$mailbody='这里是邮件内容';
	$smtpserver     = "smtpdm.aliyun.com";
	$smtpserverport = 80;
	$smtpusermail   = "postmaster@itest3.1314wallet.com";
	// 发件人的账号，填写控制台配置的发信地址,比如xxx@xxx.com
	$smtpuser       = "postmaster@itest3.1314wallet.com";
	// 访问SMTP服务时需要提供的密码(在控制台选择发信地址进行设置)
	$smtppass       = "Donghua123L";
	$mailsubject    = "=?UTF-8?B?" . base64_encode($mailsubject) . "?=";
	$mailtype       = "HTML";
	//可选，设置回信地址
	$smtpreplyto    = "";
	$smtp           = new Smtp($smtpserver, $smtpserverport, true, $smtpuser, $smtppass);
	$smtp->debug    = FALSE;
	$cc   ="";
	$bcc  = "";
	$additional_headers = "";
	//设置发件人名称，名称用户可以自定义填写。
	$sender  = "i红包";
	$res = $smtp->sendmail($mailto,$smtpusermail, $mailsubject, $mailbody, $mailtype, $cc, $bcc, $additional_headers, $sender, $smtpreplyto);
	return $res;
}
//上传文件
function upload($dir='',$isoldname=false){

	$arr['uploaded'] = 1;
	$file = request()->file('file');
	$path = '/uploads/file/';
	$pathr = '/uploads/file/';
	if(!is_dir(BASEPATH.'/uploads/file/')){
		//				mkdir(BASEPATH.'/uploads/'.$dir.'/',true);
		mkdir(BASEPATH.'/uploads/file/');
	}
	if($dir){

		if(!is_dir(BASEPATH.'/uploads/file/'.$dir.'/')){
			//				mkdir(BASEPATH.'/uploads/'.$dir.'/',true);
			mkdir(BASEPATH.'/uploads/file/'.$dir.'/',0777, true); 
			chmod(BASEPATH.'/uploads/file/'.$dir.'/', 0777);
			
		}
		$path = $path.$dir.'/';
		$pathr = $pathr.$dir.'/';
	}
	// 移动到框架应用根目录/public/uploads/ 目录下
	if($file){
		if($isoldname){
			$info = $file->move(BASEPATH .$path,'');
		}else{
			$info = $file->move(BASEPATH .$path);
		}
		if($info){
			// 成功上传后 获取上传信息
			// 输出 jpg
			$uploadFilename = $pathr.$info->getSaveName();;
			$arr['url'] = $uploadFilename;
			$arr['src'] = $uploadFilename;
			$arr['fileName'] = $uploadFilename;
			$arr['success'] = 1;

			return  json_encode($arr);
		}else{
			// 上传失败获取错误信息
			return $file->getError();
		}
	}


}

/**
 * curl 请求
 */
function getCurlu($url = '', $params = array(), $type = '', $success = 0) {

	$ch = curl_init();   // 初始化
	curl_setopt($ch, CURLOPT_URL, $url); //请求地址
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
	//curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 设置超时时间
	curl_setopt($ch, CURLOPT_TIMEOUT_MS, 750);//500毫秒超时时间
	switch ($type) {
		case 'GET':
			curl_setopt($ch, CURLOPT_HTTPGET, true);
			break;
		case 'POST':
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			break;
		case 'PUT':
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			break;
		case 'DELETE':
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			break;

		default:
	}
	$data = curl_exec($ch);//获得返回值

	curl_close($ch);
	if (!$success) {
		$data = json_decode($data, true);
	}

	return $data;
}
function mydate($date){
	if($date){
	return date("Y-m-d H:i:s",$date);
	}
	return '';
}
//存储激活过的app和idfa
function setappIdfaCache($appid,$uid){
	if(Cache::store('redis')){
		$key = 'appidfa_'.$appid.$uid;
		$appidfa = Cache::get($key);
		if(!$appidfa) {
			Cache::set($key, $appid.$uid);
		}
	}
	$model = new UserAppModel();
	$ishas = $model->where(['uid'=>$uid,'appid'=>$appid])->find();
	if(!$ishas){
		$model->insert(['uid'=>$uid,'appid'=>$appid,'addtime'=>time()]);
	}
	//return $appidfa;
}
function getappIdfaCache($appid,$uid){
	//不能用idfa来判断是否安装，要用uid
	$appidfa = false;
	if(Cache::store('redis')){
		$key = 'appidfa_'.$appid.$uid;
		$appidfa = Cache::get($key);
		
	}else{
		$model = new UserAppModel();
		$appidfa = $model->where(['uid'=>$uid,'appid'=>$appid])->find();
	}
	return $appidfa;
}
//存储激活过的app和idfa、、外放的
function setappIdfaCacheUnion($appid,$idfa){
	if(Cache::store('redis')){
		$key = 'appidfa_'.$appid.$idfa;
		$appidfa = Cache::get($key);
		if(!$appidfa) {
			Cache::set($key, $appid.$idfa);
		}
	}
	$model = new UserAppModel();
	$ishas = $model->where(['uid'=>$idfa,'appid'=>$appid])->find();
	if(!$ishas){
	$model->insert(['uid'=>$idfa,'appid'=>$appid,'addtime'=>time()]);
	}
	//return $appidfa;
}
function getappIdfaCacheUnion($appid,$idfa){
	//不能用idfa来判断是否安装，要用uid
	$appidfa = false;
	if(Cache::store('redis')){
		$key = 'appidfa_'.$appid.$idfa;
		$appidfa = Cache::get($key);
		
	}else{
		$model = new UserAppModel();
		$appidfa = $model->where(['uid'=>$idfa,'appid'=>$appid])->find();
	}
	return $appidfa;
}
//中文替换xing
function replace_xing($str){
	$len = mb_strlen($str);
	$x = '';
	for($i=1;$i<$len;$i++){
		$x.='*';
	}
	$str = $x.mb_substr($str, -1);
	return $str;
}
//激活正则验证
function active_check_preg($regex,$res,$idfa){
	$ret = ['msg' => 'success', 'status' => 0];
	if($regex) {

		@preg_match_all($regex, $res, $m);
		//var_dump($m);
	
		if(isset($m[0][0])) {
			if(isset($m[0][0])) {
				$ret['status'] = 1;
				$ret['msg'] = '';
			} else {
				$ret['msg'] = '领取失败，任务未达标';
			}
		} else {
			$ret['msg'] = '数据解析失败';
		}
	
	} else {
	
		$ret['msg'] = '';
	}
	return $ret;
	
}
///点击验证正则函数
function click_check_preg($regex,$res){
	$ret = ['msg' => 'success', 'status' => 0];
	if($regex) {

		@preg_match_all($regex, $res, $m);
		//var_dump($m);
	
		if(isset($m[0][0])) {
			if(isset($m[0][0])) {
				$ret['status'] = 1;
				$ret['msg'] = '解析成功';
			} else {
				$ret['msg'] = '解析失败';
			}
		} else {
			$ret['msg'] = '数据解析失败';
		}
	
	} else {
	
		$ret['msg'] = '';
	}
	return $ret;
}
//去重判断正则验证
function repeat_check_preg($regex,$res,$idfa){
    $ret = ['status' => 0, 'msg' => '不可安装', 'query' => '', 'request' => []];
	if(strpos($res, $idfa)===false){
		$res = $idfa.'__'.$res;
	}
	@preg_match_all($regex['preg'], $res, $m);
	if(isset($m[0][0])) {
		$ret['match_str'] = $m[0][0];
		if($regex['valmatchposition'] && isset($m[(int)$regex['valmatchposition']])) {
			if($m[(int)$regex['valmatchposition']][0] != $regex['installval']) {
				$ret['status'] = 1;
				$ret['msg'] = '可以安装';
			}else{
				$ret['msg'] = '您已下载过该app';
				
			}
		}
	
		$pos = $regex['idfamatchposition'];
	
		if(!isset($m[$pos][0])) {
			$ret['msg'] = 'idfa解析不正确';
			$ret['status'] = 0;
		}
	
	} else {
		$ret['msg'] = '数据解析失败';
	}
	return $ret;
}


/*支付帮助函数*/

function buildRequestPara($para_temp, $app_id, $app_secret) {
	//除去待签名参数数组中的空值和签名参数
	$para_filter = paraFilter($para_temp);
	//对待签名参数数组排序
	$para_sort = argSort($para_filter);
	//生成签名结果
	$mysign = buildRequestSign($para_sort, $app_id, $app_secret);
	//签名结果与签名方式加入请求提交参数组中
	$para_sort['sign'] = $mysign;
	foreach ($para_sort as $key => $value) {
		$para_sort[$key] = $value;
	}
	return $para_sort;
}
function mk_sign($data) {

	$data = json_encode($data);
	$passwd = '8D4F16E8F94796FC';//加密密钥
	 
	$result = md5($data);
	
	return $result;

}
/**
 * 生成签名结果
 * @param $para_sort 已排序要签名的数组
 * return 签名结果字符串
 */
function buildRequestSign($para_sort, $app_id, $app_secret) {
	$prestr = createLinkstring($para_sort);
	// echo $prestr . "\n";
	return hash_hmac("sha1", $prestr, $app_secret, false);
}

function buildRequestJSON($request_data,$url) {
	$sResult = '';

	//远程获取数据
	$sResult = getHttpResponseJSON($url, $request_data);

	return $sResult;
}

/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
function createLinkstring($para) {
	$arg  = "";
	while (list ($key, $val) = each ($para)) {
		$arg.=$key."=".$val."&";
	}
	//去掉最后一个&字符
	$arg = substr($arg,0,count((array)$arg)-2);
	//file_put_contents("log.txt","转义前:".$arg."\n", FILE_APPEND);
	//如果存在转义字符，那么去掉转义
	if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
	//file_put_contents("log.txt","转义后:".$arg."\n", FILE_APPEND);
	return $arg;
}
/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
function createLinkstringUrlencode($para) {
	$arg  = "";
	while (list ($key, $val) = each ($para)) {
		$arg.=$key."=".urlencode($val)."&";
	}
	//去掉最后一个&字符
	$arg = substr($arg,0,count($arg)-2);

	//如果存在转义字符，那么去掉转义
	if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

	return $arg;
}
/**
 * 除去数组中的空值和签名参数
 * @param $para 签名参数组
 * return 去掉空值与签名参数后的新签名参数组
 */
function paraFilter($para) {
	$para_filter = array();
	while (list ($key, $val) = each ($para)) {
		if($key == "sign" || $val == "")continue;
		else	$para_filter[$key] = $para[$key];
	}
	return $para_filter;
}
/**
 * 对数组排序
 * @param $para 排序前的数组
 * return 排序后的数组
 */
function argSort($para) {
	ksort($para);
	reset($para);
	return $para;
}
/**
 * 写日志，方便测试（看网站需求，也可以改成把记录存入数据库）
 * 注意：服务器需要开通fopen配置
 * @param $word 要写入日志里的文本内容 默认值：空值
 */
function logResult($word='') {
	$fp = fopen("log.txt","a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}

/**
 * 远程获取数据，POST模式
 * 注意：
 * @param $url 指定URL完整路径地址
 * @param $para 请求的数据
 * return 远程输出的数据
 */
function getHttpResponseJSON($url, $para) {
	$json = json_encode($para);
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果

	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
	'Content-Type: application/json',
	'Content-Length: ' . strlen($json))
	);
	$responseText = curl_exec($curl);
	file_put_contents(__DIR__ ."/../runtime/log/paylog.txt","返回值:".$responseText."\n", FILE_APPEND);
	curl_close($curl);
	return $responseText;
}

/**
 * 实现多种字符解码方式
 * @param $input 需要解码的字符串
 * @param $_output_charset 输出的解码格式
 * @param $_input_charset 输入的解码格式
 * return 解码后的字符串
 */
function charsetDecode($input,$_input_charset ,$_output_charset) {
	$output = "";
	if(!isset($_input_charset) )$_input_charset  = $_input_charset ;
	if($_input_charset == $_output_charset || $input ==null ) {
		$output = $input;
	} elseif (function_exists("mb_convert_encoding")) {
		$output = mb_convert_encoding($input,$_output_charset,$_input_charset);
	} elseif(function_exists("iconv")) {
		$output = iconv($_input_charset,$_output_charset,$input);
	} else die("sorry, you have no libs support for charset changes.");
	return $output;
}

//格式化时间戳
function local_date($format, $time = NULL)
{
	if ($time === NULL)
	{
		$time = gmtime();
	}
	elseif ($time <= 0)
	{
		return '';
	}
	return date($format, $time);
}

function genLetterDigitRandom($size) {
	$allLetterDigit = array("0","1","2","3","4","5","6","7","8","9","A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
	$randomSb = "";
	$digitSize = count($allLetterDigit)-1;
	for($i = 0; $i < $size; $i ++){
		$randomSb .= $allLetterDigit[rand(0,$digitSize)];
	}
	return $randomSb;
}
/*支付帮助函数结束*/