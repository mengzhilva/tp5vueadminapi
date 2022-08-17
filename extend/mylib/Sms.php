<?php

/**
 * Created by PhpStorm.
 * User: liuzhengbin
 * Date: 2018/5/14
 * Time: 18:31
 */
namespace mylib;

class Sms {
    public static function juhecurl($params=false,$ispost=0){
        $url = 'http://v.juhe.cn/sms/send';
//    $httpInfo = array();
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
        curl_setopt( $ch, CURLOPT_USERAGENT , 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22' );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 30 );
        curl_setopt( $ch, CURLOPT_TIMEOUT , 30);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
        if( $ispost )
        {
            curl_setopt( $ch , CURLOPT_POST , true );
            curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
            curl_setopt( $ch , CURLOPT_URL , $url );
        }
        else
        {
            if($params){
                curl_setopt( $ch , CURLOPT_URL , $url.'?'.$params );
            }else{
                curl_setopt( $ch , CURLOPT_URL , $url);
            }
        }
        $response = curl_exec( $ch );
        if ($response === FALSE) {
            //echo "cURL Error: " . curl_error($ch);
            return false;
        }
//    $httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
//    $httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
        curl_close( $ch );
        return $response;
    }


    /**
     * 聚合平台发送短信
     * $code  验证码   tplid  固定的  mobile 为电话
     */
    //$return = $Sms->sendmsg($mobile, 90812, $code);
    public static function sendmsg($mobile,$tplid,$code){
        //下面注释的为实际的juhe_msg_appkey
        //19ff8caaff5a57e36f127736feb76da0
        $appkey = config("juhe_msg_appkey");
        $smsConf = array(
            'key'   => $appkey, //您申请的APPKEY
            'mobile'    => $mobile, //接受短信的用户手机号码
            'tpl_id'    => $tplid, //您申请的短信模板ID，根据实际情况修改
            'tpl_value' =>"#code#={$code}" //您设置的模板变量，根据实际情况修改
        );
        return self::juhecurl($smsConf,1);
    }
}