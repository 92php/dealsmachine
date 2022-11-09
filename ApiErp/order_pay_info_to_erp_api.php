<?php
/**
 * 同步订单付款信息到ERP接口任务
 * 
 * @author XYL on 2014-04-28
 * 
 * 提交接口参数：'signature='.$signature_string.'&data='.$json_data;
 * 
 * signature：数字签名
 * data：付款信息
 * 
 * $Signature_key = 'hjiyGHDnmkLITbd874dnn#ppoubnlmdf';				//数组签名Key
 * 
 * 返回结果结构（json格式）：array('status'=>'fail'/'success','data'=>array(array('status'=>1,'order_sn'=>'WW1404231935505426','msg'=>''),array('status'=>0,'order_sn'=>'WW1404231935506897','msg'=>'错误信息')))
 * status：状态
 * order_sn：订单号
 * msg：错误信息
 * */
set_time_limit(0);

define('INI_WEB', true);
require_once('../lib/global.php');
require(ROOT_PATH . 'lib/time.fun.php');
require_once('api_config.php');

$payinfo = new OrderPayInfoToErp;

//获取同步付款信息
$payinfolist = $payinfo->GetOrderPayInfoList(50);
print_r($payinfolist);
if(!empty($payinfolist))
{
	//提交同步付款信息
	$returninfo = $payinfo->postData($payinfolist);
	$returninfo = json_decode($returninfo,true);
	print_r($returninfo);
	if(!empty($returninfo) && is_array($returninfo) && $returninfo['status'] == 'success')
	{
		$returninfo['data'] = json_decode($returninfo['data'],true);
		print_r($returninfo);
		$payinfo->ChangeOrderPayInfoStatus($returninfo['data']);
	}
}


class OrderPayInfoToErp
{
	public $website = 'ahappydeal.com';		//来源网站
	public $Signature_key = 'hjiyGHDnmkLITbd874dnn#ppoubnlmdf';				//数组签名Key
	public $url = 'http://www.davismicro.com.cn:9000/stock_admin/sync_payment_info.php';						//默认接口方法
	//public $url = 'http://www.gear.com/ApiErp/test.php';						//默认接口方法
	
	/**
	 * 更新订单付款信息的同步状态
	 * 
	 * @param 		array		$returninfo		接口返回信息
	 * */
	public function ChangeOrderPayInfoStatus($returninfo)
	{
		$order_list = array();
		foreach ($returninfo AS $k=>$v)
		{
			if($v['status'] == 1)
			{
				$order_list[] =  $v['order_sn'];
			}
		}
		
		//根据返回更改付款信息状态
		if(!empty($order_list))
		{
			$sql = "UPDATE " . ORDERPAYPALINFO . " SET is_to_erp = 1 WHERE order_sn IN ('" . implode("','",$order_list) . "')";
			$GLOBALS['db']->arrQuery($sql);
		}
	}
	
	/**
	 * 获取未同步到ERP的付款订单付款信息方法
	 * 
	 * @param 		int			$limit		取数据条数
	 * */
	public function GetOrderPayInfoList($limit=50)
	{
		$return_payinfolist = array();
		$sql = "SELECT * FROM " . ORDERPAYPALINFO . " WHERE is_to_erp = 0 AND paymentstatus = 'Completed' ORDER BY id DESC LIMIT $limit";
		$payinfolist = $GLOBALS['db']->arrQuery($sql);
		foreach ($payinfolist as $k_1 =>$v_1)
		{
			$return_payinfolist[$k_1] = $v_1;
			$return_payinfolist[$k_1]['website'] = $this->website;
			$return_payinfolist[$k_1]['paymethod'] = 'paypal';
		}
		return $return_payinfolist;
	}
	
	/**
	 * 模拟POST请求
	 * @param 		string		$method		请求地址
	 * @param 		string		$data		Post请求数据
	 * 
	 * @return 		$contents				抓取页面返回数据
	 */
	public function postData($data,$method='')
	{
		$this->url = empty($method) ? $this->url : $method;
		$curl_data = $this->_UrlPostParam($data);
		//print_r($curl_data);
	    $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
		$contents = curl_exec($ch);
		curl_close($ch);
	    return $contents;
	}
	
	/**
	 * 组合POST传递参数
	 * 
	 * @param 		mix		$data		传递数据
	 * */
	private function _UrlPostParam($data)
	{
		$json_data = json_encode($data);
		//print_r($json_data);
		$signature_string = $this->_Signature($json_data);
		
		return 'signature='.$signature_string.'&data='.urlencode($json_data);
	}
	
	/**
	 * 对数据进行数据签名方法
	 * 
	 * @param 		string		$data		签名数据
	 * 
	 * @return 		string					签名后数据
	 * */
	private function _Signature($data)
	{
		return md5($this->Signature_key.$data); 
	}
}
?>