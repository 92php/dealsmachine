<?php
/*
+----------------------------------
* ebanx 付款相关
* 
* @author jim 2013-12-26
* 
+----------------------------------
*/
if (!defined('INI_WEB')){die('Access denied');}
require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');
require(ROOT_PATH . 'config/ebanx_config.php');

$act = empty($_GET['a'])?'':$_GET['a'];


// let us query EBANX for the payment info

$Arr = array();
if($act == 'response'){
	
	response();
	//print_r($Arr);
	
}elseif($act == 'notification'){
	notification();
}

/**
 * 客人响应,从ebanx返回我们网站
 
 */
function response(){
	require(ROOT_PATH . 'config/ebanx_config.php');
	global $Arr,$params,$db;
	$hash = empty($_REQUEST['hash'])?'':$_REQUEST['hash'];
	if(empty($hash)) {
		// this should not happen. Only if you called your URL manually. EBANX will always pass the hash
		die("Empty hash in the response URL");
	}	
	$params = 'integration_key=' . $ebanx_config['integration_key'];
	$params .= '&hash=' . $hash;
	//echo $params;exit;
	// use CURL to perform the POST request
	//print_r($ebanx_config);exit;
	$ch = curl_init($ebanx_config['base_url'] . 'ws/query');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // RETURN THE CONTENTS OF THE CALL
	$json_response = curl_exec($ch);
	curl_close($ch);
	
	$response = json_decode($json_response,true);
	
	if($response['status'] == 'SUCCESS') { //状态返回成功
		$payment = $response['payment'];
		$order_sn = $payment['merchant_payment_code'];
		$Arr['order_sn'] = $order_sn;
		if($payment['status'] == 'CO') {//确定收到款
											
			$sql = "select order_amount,order_sn,order_id,order_status from ".ORDERINFO." where order_sn = '".$order_sn."'";
			$order = $db->selectinfo($sql);
			if(!$order){//订单不存在
				$url_contect[] = 'Go to my order list';
				$url_link[] = '/m-users-a-order_list.htm';
				show_message('order not found',$url_contect,$url_link,'warning');
			}
			$order_amount = floatval($order['order_amount']);
						
			if($order['order_status'] == '0'){
				$sql = " order_status = '1',pay_time = '".gmtime()."' ";
				$db->update(ORDERINFO,$sql," order_id = '".$order_id."'");
								/* 增加是否给客服发送邮件选项 */
				$order_info['firstname'] = empty($_COOKIE['firstname'])?'':$_COOKIE['firstname'];
				$order_info['order_no']  = $order['order_sn'];
				$order_info['order_id']  = $order['order_id'];
				send_email($_SESSION['email'],17,$order_info);  //发送已收到款邮件
			
			
			}	
		}
	}
	else {
		// this should not happen. EBANX will always pass a valid hash.
		die("{$response['status']} - {$response['status_code']} - {$response['status_message']}");
	}
	
	$Arr['response'] = $response;
	$Arr['payment'] = $payment;
	$Arr['payment_type_code'] = $payment['payment_type_code'];
	$Arr['print_boleto_url'] = $ebanx_config['base_url'] . 'ws/boleto/printHTML?hash=' . $payment['hash'] ;
	
	//return $Arr;
}

/**
 * ebanx通知接口

 */
function notification(){
	global $Arr,$params,$db;
	require(ROOT_PATH . 'config/ebanx_config.php');
	$hash_codes = empty($_REQUEST['hash_codes'])?'':$_REQUEST['hash_codes'];
	if(empty($hash_codes)) {
		// this should not happen. Only if you called your URL manually. EBANX will always pass the hash
		die("Empty hash_codes parameter in the notification URL");
	}
	//echo $hash_codes;
	$hash_codes_array = explode(',', $hash_codes);
	//file_put_contents('test/ebanx.txt',var_export($hash_codes_array,true));

	foreach($hash_codes_array as $hash) {
	
		if(empty($hash)) {
		// this should not happen. Only if you called your URL manually. EBANX will always pass the hash
			die("Empty hash in the response URL");
		}	
		$params = 'integration_key=' . $ebanx_config['integration_key'];
		$params .= '&hash=' . $hash;
		//echo $params;exit;
		// use CURL to perform the POST request
		//print_r($ebanx_config);exit;
		$ch = curl_init($ebanx_config['base_url'] . 'ws/query');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // RETURN THE CONTENTS OF THE CALL
		
		$json_response = curl_exec($ch);
		$response = json_decode($json_response,true);
		curl_close($ch);
		//print_r($response);
		if($response['status'] == 'SUCCESS') {
			$payment = $response['payment'];
			echo "OK {$payment['status']} {$payment['hash']}<br />";
			if($payment['status'] == 'CO') {
				$order_sn = $payment['merchant_payment_code'];											
				$sql = "select order_amount,order_sn,order_id,order_status from ".ORDERINFO." where order_sn = '".$order_sn."'";
				$order = $db->selectinfo($sql);
				if(!$order){//订单不存在
					$url_contect[] = 'Go to my order list';
					$url_link[] = '/m-users-a-order_list.htm';
					show_message('order not found',$url_contect,$url_link,'warning');
				}
				$order_amount = floatval($order['order_amount']);
							
				if($order['order_status'] == '0'){
					$sql = " order_status = '1',pay_time = '".gmtime()."' ";
					if(!empty($order_sn))$db->update(ORDERINFO,$sql," order_sn = '".$order_sn."'");
									/* 增加是否给客服发送邮件选项 */
					$order_info['firstname'] = empty($_COOKIE['firstname'])?'':$_COOKIE['firstname'];
					$order_info['order_no']  = $order['order_sn'];
					$order_info['order_id']  = $order['order_id'];
					send_email($_SESSION['email'],17,$order_info);  //发送已收到款邮件					
				}
			}
			else if($payment['status'] == 'PE') {
				$order_sn = $payment['merchant_payment_code'];
				$sql = " order_status = '6'";
				if(!empty($order_sn))$db->update(ORDERINFO,$sql," order_sn = '".$order_sn."'");
				// the payment is not confirmed, it is still pending
				// and can be confirmed in the next hours or days
			}
			else if($payment['status'] == 'CA') {
				$order_sn = $payment['merchant_payment_code'];
				$sql = " order_status = '11'";
				if(!empty($order_sn))$db->update(ORDERINFO,$sql," order_sn = '".$order_sn."'");
			}
		}
		else {
			// this should not happen. EBANX will always pass a valid hash.
			echo("{$response['status']} - {$response['status_code']} - {$response['status_message']}");
		}
		}
		
		// your notification URL must always finish with a "OK"
		echo "OK END<br />";
}


?>