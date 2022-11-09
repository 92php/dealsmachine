<?php
/**
 * 插件
 */
if (!defined('INI_WEB')){die('访问拒绝');}
$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/paypal.php';
if (file_exists($payment_lang))
{
    global $_LANG;
    include_once($payment_lang);
}

/**
 * 类
 */
class paypal
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function paypal()
    {
    }

    function __construct()
    {
        $this->paypal();
    }

    /**
     * 生成支付代码
     * @param   array   $order  订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order)
    {
        $def_url  = '<form action="/m-flow-a-checkout_page_paypal_ec.htm?order_sn='.$order['order_sn'].'" method="post" name="formsub" id="formsub"></form>';
        return $def_url;
    }
	/*	 
    function get_code($order)
    {
        $data_order_id      = $order['order_id'];
		if($order['order_currency'] == 'USD' || $order['order_rate'] == 1 || $order['order_rate'] == 0){
			$orderhuili = 1;
			$orderhuobi = "USD";
		}else{
			$orderhuili = $order['order_rate'];
			$orderhuobi = $order['order_currency'];
		}
		$data_amount        = number_format($order['order_amount']*$orderhuili, 2, '.', ''); //$order['order_amount'];
		$currency_code      = $orderhuobi;
        //$data_amount        = $order['order_amount']; 	
    	//print_r($order);
    	$order_goods         = isset($order['goodsListArr']) ? $order['goodsListArr'] : order_goods($data_order_id);
    	
    	if(count($order_goods)<96)return $this->new_get_code($order);//WPS 不能显示超过100项的产品

        $data_return_url    = $GLOBALS['_CFG']['creat_html_domain'].'m-flow-a-payok.htm?oid='.$order['order_sn'];// 付款后页面
		$data_pay_account   = ($order['order_amount']<=9)?'minipayment@bestafford.com':'ahappydealpayment@gmail.com';//$payment['paypal_account']davismicropayment@gmail.com
        //$currency_code      = 'USD';//$payment['paypal_currency']
        $data_notify_url    = DOMAIN_CART.'/'. $GLOBALS['cur_lang'] .'/m-flow-a-payok-code-PayPal.htm'; // 付款响应页面
        $cancel_return      = DOMAIN_USER.'/'. $GLOBALS['cur_lang'] .'/m-users-a-order_list.htm';;
        $def_url  = '<br /><form style="text-align:center;" action="https://www.paypal.com/cgi-bin/webscr" method="post" name="formsub">' .   // 不能省略
            "<input type='hidden' name='cmd' value='_xclick'>" .                             // 不能省略
            "<input type='hidden' name='business' value='$data_pay_account'>" .              // 贝宝帐号
            "<input type='hidden' name='item_name' value='Order:#$order[order_sn]'>" .              // payment for
            "<input type='hidden' name='amount' value='$data_amount'>" .                     // 订单金额
            "<input type='hidden' name='currency_code' value='$currency_code'>" .            // 货币
            "<input type='hidden' name='return' value='$data_return_url'>" .                 // 付款后页面
            "<input type='hidden' name='invoice' value='$order[order_sn]'>" .                  // 订单号
            "<input type='hidden' name='charset' value='utf-8'>" .                           // 字符集
            "<input type='hidden' name='no_shipping' value='0'>" .                           // 不要求客户提供收货地址
            "<input type='hidden' name='no_note' value=''>" .                                // 付款说明
            "<input type='hidden' name='notify_url' value='$data_notify_url'>" .
            "<input type='hidden' name='image_url' value='https://security.bestafford.com/images/bestafford.jpg'>" .
            "<input type='hidden' name='rm' value='2'>" .
            "<input type='hidden' name='cancel_return' value='$cancel_return'>" .
            '<input type="image" border="0" name="submit" class = "formsubbotton" alt="'. $GLOBALS['_LANG']['paypal_button'] .'" src="/temp/skin3/ximages/x-click-but5.gif" >' .
            "</form><br />";

        return $def_url;
    }
	*/
    
	/**
     * 生成支付代码
     * @param   array   $order  订单信息
     * @param   array   $payment    支付方式信息
     */
	/* 
    function new_get_code($order)
    {
        $data_order_id      = $order['order_id'];
        $data_amount        = $order['order_amount'];
        $data_return_url    = $GLOBALS['_CFG']['creat_html_domain'].'m-flow-a-payok.htm?oid='.$order['order_sn'];// 付款后页面
		$data_pay_account   = ($order['order_amount']<=9)?'minipayment@bestafford.com':'ahappydealpayment@gmail.com';//$payment['paypal_account']davismicropayment@gmail.com
        $currency_code      = 'USD';//$payment['paypal_currency']
        $data_notify_url    = DOMAIN_CART.'/'. $GLOBALS['cur_lang'] .'/m-flow-a-payok-code-PayPal.htm'; // 付款响应页面
        $cancel_return      = DOMAIN_USER.'/'. $GLOBALS['cur_lang'] .'/m-users-a-order_list.htm';;
        $order_goods         = isset($order['goodsListArr']) ? $order['goodsListArr'] : order_goods($data_order_id);
        $n                   = 0;
        $item_str            = '';
                
 		foreach ($order_goods as $item) {
            $n++;
            $item_str .= '<input type="hidden" name="item_name_' . $n . '" value="' . $item['goods_name'] . '" />';
            $item_str .= '<input type="hidden" name="amount_' . $n . '" value="' . $item['goods_price'] . '" />';
            $item_str .= '<input type="hidden" name="item_number_' . $n . '" value="' . $item['goods_sn'] . '" />';
            $item_str .= '<input type="hidden" name="quantity_' . $n . '" value="' . $item['goods_number'] . '" />';
        }
        //echo price_format($order['shipping_fee'] + $order['Need_Traking_number']);
        if ($order['free_shipping_fee'] > 0 ||$order['shipping_fee'] > 0 || $order['Need_Traking_number'] > 0) {//运费
            $n++;
            $item_str .= '<input type="hidden" name="item_name_' . $n . '" value="Shipping fee" />';
            $item_str .= '<input type="hidden" name="amount_' . $n . '" value="' . (price_format($order['shipping_fee'] +$order['free_shipping_fee']+ $order['Need_Traking_number'])) . '" />';
        }
        
        if ($order['insure_fee'] > 0) {//保险费
            $n++;
            $item_str .= '<input type="hidden" name="item_name_' . $n . '" value="Insurance" />';
            $item_str .= '<input type="hidden" name="amount_' . $n . '" value="' . $order['insure_fee'] . '" />';
        }
        
        if ($order['point_money'] > 0 || $order['yuan_goods_amount'] - $order['goods_amount'] > 0) {//优惠
            $item_str .= '<input type="hidden" name="discount_amount_cart" value="' . (price_format($order['point_money'] + $order['yuan_goods_amount'] - $order['goods_amount'])) . '" />';
        }       
        
        $def_url  = '<br /><form style="text-align:center;" action="https://www.paypal.com/cgi-bin/webscr" method="post" name="formsub">' .   // 不能省略
            '<input type="hidden" name="cmd" value="_cart" />' . // 不能省略
            '<input type="hidden" name="upload" value="1" />' . 
            "<input type='hidden' name='business' value='$data_pay_account'>" .              // 贝宝帐号
            "<input type='hidden' name='item_name' value='Order:#$order[order_sn]'>" .              // payment for
            "<input type='hidden' name='amount' value='$data_amount'>" .                     // 订单金额
            "<input type='hidden' name='currency_code' value='$currency_code'>" .            // 货币
            "<input type='hidden' name='return' value='$data_return_url'>" .                 // 付款后页面
            "<input type='hidden' name='invoice' value='$order[order_sn]'>" .                  // 订单号
            "<input type='hidden' name='charset' value='utf-8'>" .                           // 字符集
            "<input type='hidden' name='no_shipping' value='0'>" .                           // 不要求客户提供收货地址
            "<input type='hidden' name='no_note' value=''>" .                                // 付款说明
            "<input type='hidden' name='notify_url' value='$data_notify_url'>" .
            "<input type='hidden' name='image_url' value='https://security.bestafford.com/images/bestafford.jpg'>" .
            "<input type='hidden' name='rm' value='2'>" .
            "<input type='hidden' name='cancel_return' value='$cancel_return'>" .
            '<input type="image" border="0" name="submit" class = "formsubbotton" alt="'. $GLOBALS['_LANG']['paypal_button'] .'" src="/temp/skin3/ximages/x-click-but5.gif" >' .
            $item_str . 
            "</form><br />";

        return $def_url;
    } 
	*/   

    /**
     * 响应操作
     */
    function respond()
    {
		global $db,$Arr;
		
		
        $payment['paypal_currency'] = 'USD';
       // $merchant_id    = $payment['paypal_account'];               ///获取商户编号
		$merchant_id   = 'ahappydealpayment@gmail.com';//$payment['paypal_account']ahappydealonlinepayment@gmail.com


        // read the post from PayPal system and add 'cmd'
        $req = 'cmd=_notify-validate';
        foreach ($_POST as $key => $value)
        {
            $value = urlencode(stripslashes($value));
            $req .= "&$key=$value";
        }

        // post back to PayPal system to validate
        $header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($req) ."\r\n\r\n";
        $fp = fsockopen ('www.paypal.com', 80, $errno, $errstr, 30);

        // assign posted variables to local variables
		
        $item_name         = empty($_POST['item_name'])?'':$_POST['item_name'];
        $item_number       = empty($_POST['item_number'])?'':$_POST['item_number'];
        $payment_status    = empty($_POST['payment_status'])?'':$_POST['payment_status'];
        $payment_amount    = empty($_POST['mc_gross'])?'0.00':floatval($_POST['mc_gross']);
        $payment_currency  = empty($_POST['mc_currency'])?'':$_POST['mc_currency'];
        $txn_id            = empty($_POST['txn_id'])?'':$_POST['txn_id'];
        $receiver_email    = empty($_POST['receiver_email'])?'':$_POST['receiver_email'];
        $payer_email       = empty($_POST['payer_email'])?'':$_POST['payer_email'];
        $order_sn          = empty($_POST['invoice'])?'':$_POST['invoice'];
        $memo              = empty($_POST['memo'])?'':$_POST['memo'];
        $action_note       = $txn_id . '（' . $GLOBALS['_LANG']['paypal_txn_id'] . '）' . $memo;
        $payment_type	   = empty($_POST['payment_type'])?'':$_POST['payment_type'];

        if (!$fp)
        {
            fclose($fp);

           return 'Fatal error, please do not submit empty link';
        }
        else
        {
			
			//$content = var_export($_POST, true);
			//file_put_contents(realpath('pay_record.txt'),$content);
		
            fputs($fp, $header . $req);
            while (!feof($fp))
            {
				
				
		
		
                $res = fgets($fp, 1024);	
                if (strcmp($res, 'VERIFIED') == 0)
           		{
                    // process payment
					
					//查订单金额是否和付款金额相等
					$sql = "select order_amount,order_sn,order_id,order_status from ".ORDERINFO." where order_sn = '".$order_sn."'";
					$order = $db->selectinfo($sql);
					$order_amount = floatval($order['order_amount']);					
					
					if($payment_status == 'Completed')		//付款已完成，资金已成功增加到您的账户余额中
           			{
						if(empty($order['order_status'])){
							$sql = " order_status = '1',realpay = '".$payment_amount."',pay_time = '".gmtime()."' "; 
						}else{
							$sql = " realpay = '".$payment_amount."' "; 
						}
						$db->update(ORDERINFO,$sql," order_sn = '".$order_sn."'");
						/* 增加是否给客户发送邮件选项 */
						$order_info['firstname'] = empty($_SESSION['firstname'])?$order['firstname']:$_SESSION['firstname'];
						$order_info['order_no']  = $order['order_sn'];
						$order_info['order_id']  = $order['order_id'];
						send_email($_SESSION['email'], 17, $order_info);  //发送已收到款邮件
					}
					elseif($payment_status == 'Pending') 
					{
						if($payment_type == 'echeck')
						{
							$sql = " order_status = '6' ";	//电子支票付款中
							$db->update(ORDERINFO,$sql," order_sn = '".$order_sn."'");
						}
					}
					
					
					
                    fclose($fp);					
					$_SESSION['order_sn_ssaid'] = $order_sn;
					$_SESSION['amount_ssaid'] = $payment_amount;				
					
					return "PayPal payment successful";
                }
                elseif (strcmp($res, 'INVALID') == 0)
                {
                   // log for manual investigation
                   fclose($fp);
                  return "Illegal PayPal links";
                }
            }
        }
		
    }
}

?>