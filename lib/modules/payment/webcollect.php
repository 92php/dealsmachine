<?php

/**
 * 插件
 */

if (!defined('INI_WEB')){die('访问拒绝');}

/**
 * 类
 */
class webcollect
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

    function webcollect()
	{
		$this->db = $GLOBALS['db'];
    }

    function __construct()
    {
        $this->webcollect();
    }

    /**
     * 生成支付代码
     * @param   array   $order  订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order)
    {
		global $_CFG;

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
		//if(empty($ratearr[$order['order_currency_huili']]) || $ratearr[$order['order_currency_huili']] == 0 || $ratearr[$order['order_currency_huili']] == "0"){
			//$orderhuili = 1;
			//$orderhuobi = "USD";
		//}else{
		//	$orderhuili = $ratearr[$order['order_currency_huili']];
		//	$orderhuobi = $order['order_currency_huili'];
		//}
		if($order['order_currency'] == 'USD' || $order['order_rate'] == 1 || $order['order_rate'] == 0){
			$orderhuili = 1;
			$orderhuobi = "USD";
		}else{
			$orderhuili = $order['order_rate'];
			$orderhuobi = $order['order_currency'];
		}

		$nowpaymoney = number_format($order['order_amount']*$orderhuili, 2, '.', '');
		if($nowpaymoney == 0){
			$nowpaymoney = 0.1;
		}


		$order['Need_Traking_number'] = number_format($order['Need_Traking_number']*$orderhuili, 2, '.', '');
		$order['point_money']         = number_format($order['point_money']*$orderhuili, 2, '.', '');
		$order['yuan_goods_amount']   = number_format($order['yuan_goods_amount']*$orderhuili, 2, '.', '');
		$order['goods_amount'] = number_format($order['goods_amount']*$orderhuili, 2, '.', '');
		$order['shipping_fee'] = number_format($order['shipping_fee']*$orderhuili, 2, '.', '');
		$order['insure_fee']   = number_format($order['insure_fee']*$orderhuili, 2, '.', '');

        $data_amount        = $nowpaymoney;//$order['order_amount'];

		$OUR_HOST           = 'www.bestafford.com';
		$OUR_HTTP_HOST      = 'http://www.bestafford.com/';


    	$area_list          = area_list();
    	$country_id = empty($order['country_id'])?$order['country']:$order['country_id'];
		$country_code       = $area_list[$country_id]['region_code'];
		$country_name       = $area_list[$country_id]['region_name'];

        $data_return_url    = DOMAIN_CART.'/m-flow-a-payok.htm?oid='.$order['order_sn'];// 付款后页面
        $data_notify_url    = DOMAIN_CART.'/m-flow-a-payok-code-webcollect.htm'; // 付款响应页面
        $cancel_return      = DOMAIN_CART.'/m-flow-a-cart.htm';
		$image_url          = "https://cloud6.faout.com/imagecache/A/ximages/ppplogo.jpg";
		$md5sign            = md5(rand_string(10,5));
		$_SESSION['md5sign']= $md5sign;

		//$def_url = '<FORM METHOD="POST" ACTION="/security/creditcard/" name="formsub">'.
		//$def_url = '<FORM METHOD="POST" ACTION="https://security.igogo.es/testcc/" name="formsub">'.
		//$def_url = '<FORM METHOD="POST" ACTION="http://www.ies.com/security/creditcard/" name="formsub">'.
		$def_url = '<FORM METHOD="POST" ACTION="https://security.bestafford.com/creditcard/index.php?o='.$order['order_id'].'" name="formsub">';
		if(IS_LOCAL) $def_url = '<FORM METHOD="POST" ACTION="https://security.everbuying.com/creditcard/index.php?gmode=test&o='.$order['order_id'].'" name="formsub">';

		$def_url .= '<input type="hidden" name="site"  id="site" value="'.$OUR_HOST.'" />
		<input type="hidden" name="md5sign" id="md5sign" value="'.$md5sign.'-'.$_COOKIE['PHPSESSID'].'" />
		<input type="hidden" name="item_number" id="item_number" value="'.$order['order_sn'].'">
		<input type="hidden" name="orderid" id="orderid" value="'.$order['order_id'].'" />
		<input type="hidden" name="amount" id="amount" value="'.$data_amount.'">
		<input type="hidden" name="CURRENCYCODE" id="CURRENCYCODE" value="'.$orderhuobi.'">
		<input type="hidden" name="firstName" id="firstName" value="'.$AdrArr['firstname'].'" />
		<input type="hidden" name="lastName" id="lastName" value="'.$AdrArr['lastname'].'" />
		<input type="hidden" name="address1" id="address1" value="'.$AdrArr['addressline1'].'" />
		<input type="hidden" name="address2" id="address2" value="'.$AdrArr['addressline2'].'" />
		<input type="hidden" name="countrycode" id="countrycode" value="'.$country_code.'" />
		<input type="hidden" name="phone" id="phone" value="'.$AdrArr['tel'].'" />
		<input type="hidden" name="city" id="city" value="'.$AdrArr['city'].'" />
		<input type="hidden" name="country" id="country" value="'.$country_name.' " />
		<input type="hidden" name="state" id="state" value="'.$AdrArr['province'].'" />
		<input type="hidden" name="zip" id="zip" value="'.$AdrArr['zipcode'].'" />

		<input type="hidden" name="shipping_firstName" id="shipping_firstName" value="'.$shipping_address['firstname'].'" />
		<input type="hidden" name="shipping_lastName" id="shipping_lastName" value="'.$shipping_address['lastname'].'" />
		<input type="hidden" name="shipping_address" id="shipping_address" value="'.$shipping_address['addressline1'].'" />
		<input type="hidden" name="shipping_address1" id="shipping_address1" value="'.$shipping_address['addressline2'].'" />
		<input type="hidden" name="shipping_countrycode" id="shipping_countrycode" value="'.$country_code.'" />
		<input type="hidden" name="shipping_phone" id="shipping_phone" value="'.$shipping_address['tel'].'" />
		<input type="hidden" name="shipping_city" id="shipping_city" value="'.$shipping_address['city'].'" />
		<input type="hidden" name="country" id="country" value="'.$country_name.' " />
		<input type="hidden" name="shipping_state" id="shipping_state" value="'.$shipping_address['province'].'" />
		<input type="hidden" name="shipping_zip" id="shipping_zip" value="'.$shipping_address['zipcode'].'" />

		<input type="hidden" name="email" id="email" value="'.$_SESSION['email'].'" />
		<input type="hidden" name="BackUrl" value="'.$data_return_url.'">
		<input type="hidden" name="data_notify_url" value="'.$data_notify_url.'">
		<input type="hidden" name="cancel_return" value="'.$cancel_return.'">
		<input type="hidden" name="Need_Traking_number" value="'.$order['Need_Traking_number'].'">
		<input type="hidden" name="point_money" value="'.$order['point_money'].'">
		<input type="hidden" name="yuan_goods_amount" value="'.$order['yuan_goods_amount'].'">
		<input type="hidden" name="goods_amount" value="'.$order['goods_amount'].'">
		<input type="hidden" name="insure_fee" value="'.$order['insure_fee'].'">
		<input type="hidden" name="shipping_fee" value="'.$order['shipping_fee'].'">
		<input type="hidden" name="languagecode" value="en">
		<input type="submit" value="Pay by Credit Card" class = "formsubbotton">
		</form>';
		//$def_url .="<script type='text/javascript'>formsub.submit();</script>";

		return $def_url;
    }

    /**
     * 响应操作
     */
    function respond()
    {
        $md5sign       = empty($_POST['md5sign'])?'':$_POST['md5sign'];
        $Post_orderid  = empty($_POST['orderid'])?'':$_POST['orderid'];
		$orderstatus   = empty($_POST['orderstatus'])?'':$_POST['orderstatus'];
		//$content = var_export($_SESSION, true);

		$file_name = LOG_PATH . 'credit_cart_record.txt';
		$content = "SESSION.md5sign ='".$_SESSION['md5sign']."';md5sign='".$_POST['md5sign']."';orderid='".$_POST['orderid']."|".$_SESSION['turn_order']['order_id']."'"."\n";
		$fp = fopen($file_name,'ab'); //以二进制追加方式打开文件,没文件就创建
		fwrite($fp, $content, strlen($content)); //插入第一条记录
		fclose($fp); //关闭文件

		//echo $file_name;
        if ($md5sign == $_SESSION['md5sign'] && $Post_orderid)
        {

			$order_id = empty($_SESSION['turn_order']['order_id'])?$Post_orderid:$_SESSION['turn_order']['order_id'];
			$sql = "select order_amount,order_sn,order_id,order_status from ".ORDERINFO." where order_id = '".$order_id."'";
			$order = $this->db->selectinfo($sql);
			$order_amount = floatval($order['order_amount']);

			if(empty($order['order_status'])){
				if($orderstatus == '6'){
					$sql = " order_status = '6',pay_time = '".gmtime()."' ";
					$this->db->update(ORDERINFO,$sql," order_id = '".$order_id."'");
				}else{
					$sql = " order_status = '1',pay_time = '".gmtime()."' ";
					$this->db->update(ORDERINFO,$sql," order_id = '".$order_id."'");
					/* 增加是否给客服发送邮件选项 */
					$order_info['firstname'] = empty($_COOKIE['firstname'])?'':$_COOKIE['firstname'];
					$order_info['order_no']  = $order['order_sn'];
					$order_info['order_id']  = $order['order_id'];
					send_email($_SESSION['email'],17,$order_info);  //发送已收到款邮件


					$_SESSION['order_sn_ssaid'] = $order['order_sn'];
					$_SESSION['amount_ssaid'] = $payment_amount;

					unset($_SESSION['md5sign']);
					//送积分
					if(!empty($_SESSION['groupdeals_recomm_user_id']) && $payment_amount > 0){
						$note = 'Group Deals-Invite Friends to buy sucessfully get 10 DM points';
						add_point($_SESSION['groupdeals_recomm_user_id'],10,2,$note);
					}
					//return "GC payment successful";
				}
			}else{
				//return "GC paied ";
			}
        }
        else
        {
          // return 'Fatal error, please do not submit empty link';
        }
		exit;

    }
}

?>