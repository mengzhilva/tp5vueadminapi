<?php
namespace mylib;
use GuzzleHttp\json_decode;
use think\Db;
use app\common\model\UserWithdrawLogModel;
use GuzzleHttp\json_encode;
use think\Exception;
use app\common\model\UserModel;
class qypay{
	private $app_id = "2019080615590093";
	private $app_secret = "311fccf7a8264ceeb55f47755029bfa4";
	
	public function pay($data){

		$app_id = $this->app_id;
		
		$app_secret = $this->app_secret;

		$alipay_data = array(
				'app_id' => $this->app_id,
				'nonce_str' => genLetterDigitRandom(16),
				'time_stamp' => time() . "",
				'alipay_account' => $data['alipay_account'], //支付宝账号
				'account_name' => $data['account_name'], //真实姓名
				'phone' => $data['phone'],	//手机号，可选
				'identity_card_number' => $data['identity_card_number'], //身份证号
				'amount' => $data['amount'],	//金额
				'order_info' => '用户提现',
				'remark' => '',
				'out_order_no' => $data['out_order_no']//生成订单号；"20190918384748" . rand(100, 999)
		);
		
		$sortPara = buildRequestPara($alipay_data, $app_id, $app_secret);
		
		$html_text = buildRequestJSON($sortPara, 'http://stag.q-yd.com/api/platform/alipay/payments');

		return json_decode($html_text,true);
	}
	public function buildRequestSign($para_temp){
		//除去待签名参数数组中的空值和签名参数
		$para_filter = paraFilter($para_temp);
		//对待签名参数数组排序
		$para_sort = argSort($para_filter);
		//生成签名结果
		$mysign = buildRequestSign($para_sort, $this->app_id, $this->app_secret);
		return $mysign;
	}
	public function payment_results($order_no){
		$app_id = $this->app_id;
		
		$app_secret = $this->app_secret;
		$query_data = array(
				'app_id' => $app_id,
				'nonce_str' => genLetterDigitRandom(16),
				'time_stamp' => time() . "",
				'order_no' => $order_no
		);
		$sortPara = buildRequestPara($query_data, $app_id, $app_secret);
		// echo json_encode($sortPara);
		$html_text = buildRequestJSON($sortPara, 'http://stag.q-yd.com/api/platform/payment_results');
		
		return json_decode($html_text,true);
	}
	
	
	//业务逻辑
	//根据返回结果修改提现交易结果
	public function result_check($data){

		$out_order_no = $data['out_order_no'];
		$UserWithdrawLogModel = new UserWithdrawLogModel();
		$pay = new qypay();
		$userlog = $UserWithdrawLogModel->where(['out_order_no'=>$out_order_no])->field('money,id')->find();
		if($data['pay_result'] == 'SUCCESS'&&$data['amount']  == $userlog['money']){ // 支付成功
			$UserWithdrawLogModel->where(['id'=>$userlog['id']])
			->update(['status'=>3,'pay_result'=>1,'pay_message'=>$data['pay_message'],'paytime'=>time()]);
		}else{
			if($data['pay_result'] == 'PENDING'){
				//如果是支付中，暂不处理
			}else{
				$UserWithdrawLogModel->where(['id'=>$userlog['id']])
				->update(['status'=>4,'pay_result'=>2,'pay_message'=>$data['pay_message'],'pay_message'=>$data['pay_message']]);
	        	$pay->balance($userlog['id']); //回滚余额
			}
		}
	}
	//审核失败或支付失败回滚余额
	public function balance($id){
		if(!$id){
			return false;
		}
		$UserWithdrawLogModel = new UserWithdrawLogModel();
		$usermodel = new UserModel();
		$userlog = $UserWithdrawLogModel->where(['id' => $id])->find();
		$user = $usermodel->where('id='.$userlog['uid'])->find();
		Db::startTrans();
		try {
		
			$data['balance'] = $user['balance']+$userlog['money'];
			$usermodel->where('id='.$userlog['uid'])->update($data);
			Db::commit();
            model('User')->delUserInfoIndex($userlog['uid']);
		} catch(Exception $e) {
			Db::rollback();
			$UserWithdrawLogModel->where(['id' => $id])->update(['status' => 0,'pay_message'=>'操作失败，请重新审核']);

			return $this->json_error('操作失败');
		}
	}
}