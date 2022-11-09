<?php

/**
 * 插件
 * boleto Bancario 付款
 * @author Jim 2014-1-22
 */

if (!defined('INI_WEB')){die('访问拒绝');}

/**
 * 类
 */
class boletoBancario
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */

	var $db = '';

    function boletoBancario()
	{
		$this->db = $GLOBALS['db'];
    }

//    function __construct()
//    {
//        $this->boletoBancario();
//    }

    /**
     * 生成支付代码
     * @param   array   $order  订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order)
    {
		global $_CFG;
		require_once(ROOT_PATH . 'config/ebanx_config.php');

		if (empty($order)) return 'Order Data is empty.';
		//抽取地址
		$AdrArr = $this->db->selectinfo("SELECT * from eload_user_billing_address  where user_id = '".$_SESSION['user_id']."' limit 1;");

		$shipping_address = $this->db->selectinfo("SELECT * from eload_user_address  where address_id = (select address_id from ".USERS." where user_id = '".$_SESSION['user_id']."');");
    	if(empty($AdrArr)){
			$AdrArr = $shipping_address;
		}
		
		//$shipping_address = serialize($shipping_address);
		$rate_arr = read_static_cache('exchange',2);

		$ratearr = $rate_arr['Rate'];

		if($order['order_currency'] == 'USD' || $order['order_rate'] == 1 || $order['order_rate'] == 0){
			$orderhuili = 1;
			$orderhuobi = "USD";
		}else{
			$orderhuili = $order['order_rate'];
			$orderhuobi = $order['order_currency'];
		}

		$nowpaymoney = number_format($order['order_amount']*$orderhuili, 1, '.', '');
		if($nowpaymoney == 0){
			$nowpaymoney = 0.1;
		}


		$order['Need_Traking_number'] = number_format($order['Need_Traking_number']*$orderhuili, 2, '.', '');
		$order['point_money']         = number_format($order['point_money']*$orderhuili, 2, '.', '');
		$order['yuan_goods_amount']   = number_format($order['yuan_goods_amount']*$orderhuili, 2, '.', '');
		$order['goods_amount'] = number_format($order['goods_amount']*$orderhuili, 2, '.', '');
		$order['shipping_fee'] = number_format($order['shipping_fee']*$orderhuili, 2, '.', '');
		$order['insure_fee']   = number_format($order['insure_fee']*$orderhuili, 2, '.', '');

        //$data_amount        = $nowpaymoney;//$order['order_amount'];

		$OUR_HOST           = 'www.bestafford.com';
		$OUR_HTTP_HOST      = 'http://www.bestafford.com/';


    	$area_list          = area_list();
    	$country_id = empty($order['country_id'])?$order['country']:$order['country_id'];
		$country_code       = $area_list[$country_id]['region_code'];
		$country_name       = $area_list[$country_id]['region_name'];

        $data_return_url    = DOMAIN_CART.'/m-flow-a-payok.htm?oid='.$order['order_sn'];// 付款后页面
        $data_notify_url    = DOMAIN_CART.'/m-flow-a-payok-code-boletoBancario.htm'; // 付款响应页面
        $cancel_return      = DOMAIN_CART.'/m-flow-a-cart.htm';
		$image_url          = "https://cloud6.faout.com/ximages/ppplogo.jpg";

		
		
		$params1 = "&order_number={$order['order_sn']}";
		
		$ebanx_responese = $this->ebanx($params1);
		//print_r($ebanx_responese);
		if($ebanx_responese->status == 'SUCCESS') {
			
			$url = $ebanx_config['base_url'].'ws/?hash='.$ebanx_responese->hash;
			
		}
	    //$response = $this->ebanx(null);
		//print_r($response);exit;
		$def_url = '';
		if($ebanx_responese->status == 'SUCCESS') {
			// OK, now just redirect the user to our EBANX
			// Once all the work is done there, EBANX will redirect the user back to your response URL.
			// You can change your response URL in the Options screen of your Merchant Area
				$def_url = '<FORM METHOD="POST" ACTION="'.$ebanx_responese->redirect_url.'" name="formsub">
				<input type="submit" value="Pay by Boleto Bancario" class = "formsubbotton">
				
				</form>';
		}
		else {
			$params = '';
			$name = $AdrArr['firstname']." ".$AdrArr['lastname'];
			$params .= '&name=' . $name;
			$params .= '&email=' . $order['email'];
			$params .= '&payment_type_code=boleto';
			// the transaction value. decimal separator is '.' , do not use thousands separator
			$params .= '&amount='.$nowpaymoney;
			// the transaction currency (US dollars)
			$params .= '&currency_code='.$orderhuobi;
			// the order number for this order in your system
			// for this sample, we just generate it here. in your site, it would probably be stored in the database
			$params .= '&merchant_payment_code=' . $order['order_sn'];
			$params .= '&bypass_boleto_screen=true';
			$params .= "&order_number={$order['order_sn']}";

			$params .= "&zipcode={$AdrArr['zipcode']}";
	//		$params .= "&street_number=";
			$params .= "&phone_number={$AdrArr['tel']}";
			//print_r($params);exit;
			$ebanx_responese = $this->ebanx($params);
		//print_r($ebanx_responese);exit;
			if($ebanx_responese->status == 'SUCCESS') {
				
				$def_url = '<FORM METHOD="POST" ACTION="'.$ebanx_responese->redirect_url.'" name="formsub">
				<input type="submit" value="Pay by Boleto Bancario" class = "formsubbotton">
				
				</form>';
			}
			// some parameter was incorrect, you would probably generate a nice error message here
			//die("$ebanx_responese->status - $ebanx_responese->status_code - $ebanx_responese->status_message");
		}
		return $def_url;
    }

	/**
	 * ebanx 发请求
	 * Enter description here ...
	 * @param unknown_type $order
	 * @param unknown_type $AdrArr
	 */    
	function ebanx($params){
		//global $ebanx_config;
		require(ROOT_PATH . 'config/ebanx_config.php');
		$params = 'integration_key=' . $ebanx_config['integration_key'].$params;
		
//		$params .= '&bypass_boleto_screen=true';
		//var_dump($params);
		//$params = "integration_key=794cbd45177354567b712d65f1f35764a7f462b839a787161c6aa187ca2793fcbca2549f853d2bf5614c6ffad8328199cffc&name=ji&email=sachiel.liang@gmail.com&payment_type_code=boleto&amount=7.09&currency_code=USD&merchant_payment_code=M1312050210139589&cpf=31335846930&zipcode=321fdsf&street_number=&phone_number=321123&bypass_boleto_screen=true";
		//$params='integration_key=794cbd45177354567b712d65f1f35764a7f462b839a787161c6aa187ca2793fcbca2549f853d2bf5614c6ffad8328199cffc&name=jiji du&email=sachiel.liang@gmail.com&payment_type_code=boleto&amount=7.09&currency_code=USD&merchant_payment_code=M1312050210139589&cpf=31335846930&zipcode=321fdsf&street_number=&phone_number=321123&bypass_boleto_screen=true';
		//$params='integration_key=794cbd45177354567b712d65f1f35764a7f462b839a787161c6aa187ca2793fcbca2549f853d2bf5614c6ffad8328199cffc&name=Demonstration Test Customer&email=demo-1386228983@example.com&payment_type_code=boleto&amount=50&currency_code=USD&merchant_payment_code=1386264128&cpf=31335846930&birth_date=12/04/1979&zipcode=01519000&street_number=999&phone_number=1199998888&bypass_boleto_screen=true';
		// use CURL to perform the POST request
		$ch = curl_init($ebanx_config['base_url'] . 'ws/request');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // RETURN THE CONTENTS OF THE CALL
		$json_response = curl_exec($ch);
		curl_close($ch);
		$response = json_decode($json_response);
		//print_r($response);exit;
		//echo $ebanx_config['base_url'];
		return $response;
		
	}   
    /**
     * 响应操作
     */
    function respond()
    {

    	require(ROOT_PATH . 'config/ebanx_config.php');
    	$hash = $_REQUEST['hash'];
		if(empty($hash)) {
			// this should not happen. Only if you called your URL manually. EBANX will always pass the hash
			die("Empty hash in the response URL");
		}
		
		// let us query EBANX for the payment info
		$params = 'integration_key=' . $ebanx_config['integration_key'];
		$params .= '&hash=' . $hash;
		
		// use CURL to perform the POST request
		$ch = curl_init($ebanx_config['base_url'] . 'ws/query');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // RETURN THE CONTENTS OF THE CALL
		$json_response = curl_exec($ch);
		curl_close($ch);
		
		$response = json_decode($json_response);
		
		if($response->status == 'SUCCESS') {
			$payment = $response->payment;
			if($payment->status == 'CO') {
				$order_sn = $response->payment->merchant_payment_code;

				
				
				$sql = "select order_amount,order_sn,order_id,order_status from ".ORDERINFO." where order_sn = '".$order_sn."'";
				$order = $this->db->selectinfo($sql);
				if(!$order){//订单不存在
					$url_contect[] = 'Go to my order list';
					$url_link[] = '/m-users-a-order_list.htm';
					show_message('order not found',$url_contect,$url_link,'warning');
				}
				$order_amount = floatval($order['order_amount']);
	
				
				

				if($order['order_status'] == '0'){
					$sql = " order_status = '1',pay_time = '".gmtime()."' ";
					$this->db->update(ORDERINFO,$sql," order_id = '".$order_id."'");
						/* 增加是否给客服发送邮件选项 */
						$order_info['firstname'] = empty($_COOKIE['firstname'])?'':$_COOKIE['firstname'];
						$order_info['order_no']  = $order['order_sn'];
						$order_info['order_id']  = $order['order_id'];
						send_email($_SESSION['email'],17,$order_info);  //发送已收到款邮件
	
	
						$_SESSION['order_sn_ssaid'] = $order['order_sn'];
						$_SESSION['amount_ssaid'] = $payment_amount;
				}

				
				// This means the payment is confirmed, you can confirm it in your
				// database. For example, you can already release the services to the
				// user or ship the products.
				// In our sample, we do nothing, just display some information below
			}
			else {
				// the payment is not confirmed
			}
		}
		else {
			// this should not happen. EBANX will always pass a valid hash.
			die("$response->status - $response->status_code - $response->status_message");
		}
    	
    	
        

    }
    
    /**
     * 服务器间响应
     */
    function server_notification(){
		$hash_codes = $_REQUEST['hash_codes'];
		if(empty($hash_codes)) {
			// this should not happen. Only if you called your URL manually. EBANX will always pass the hash
			die("Empty hash_codes parameter in the notification URL");
		}
		
		$hash_codes_array = explode(',', $hash_codes);
		
		foreach($hash_codes_array as $hash) {
		
			// let us query EBANX for the payment info
			$params = 'integration_key=' . $ebanx_config['integration_key'];
			$params .= '&hash=' . $hash;
		
			// use CURL to perform the POST request
			$ch = curl_init($ebanx_config['base_url'] . 'ws/query');
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // RETURN THE CONTENTS OF THE CALL
			$json_response = curl_exec($ch);
			curl_close($ch);
		
			$response = json_decode($json_response);
		
			if($response->status == 'SUCCESS') {
				$payment = $response->payment;
				echo "OK $payment->status $payment->hash<br />";
				if($payment->status == 'CO') {
					// This means the payment is confirmed, you can confirm it in your
					// database. For example, you can already release the services to the
					// user or ship the products.
					// In our sample, we do nothing
				}
				else if($payment->status == 'PE') {
					// the payment is not confirmed, it is still pending
					// and can be confirmed in the next hours or days
				}
				else if($payment->status == 'CA') {
					// the payment is not confirmed, it is CANCELLED
					// and can be will not be confirmed anymore
				}
			}
			else {
				// this should not happen. EBANX will always pass a valid hash.
				echo "ERROR $response->status - $response->status_code - $response->status_message <br />";
			}
		}
    }
	
}

?>