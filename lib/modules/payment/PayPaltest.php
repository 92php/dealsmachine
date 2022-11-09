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
class PayPaltest
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function PayPaltest()
    {
    }

    function __construct()
    {
        $this->PayPaltest();
    }

    /**
     * 生成支付代码
     * @param   array   $order  订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order)
    {
        $data_order_id      = $order['order_id'];
        $data_amount        = $order['order_amount'];
        $data_return_url    = $GLOBALS['_CFG']['creat_html_domain'].'m-flow-a-payok.htm?oid='.$order['order_sn'];// 付款后页面
		$data_pay_account   = ($order['order_amount']<=9)?'minipayment@bestafford.com':'payment@bestafford.com';//$payment['paypal_account']davismicropayment@gmail.com
        $currency_code      = 'USD';//$payment['paypal_currency']
        $data_notify_url    = $GLOBALS['_CFG']['creat_html_domain'].'m-flow-a-payok-code-PayPal.htm'; // 付款响应页面
        $cancel_return      = $GLOBALS['_CFG']['creat_html_domain'].'m-flow-a-fails.htm';;
        $def_url  = '<br /><form style="text-align:center;" action="https://www.paypal.com/cgi-bin/webscr" method="post" name="formsub">' .   // 不能省略
            "<input type='hidden' name='cmd' value='_xclick'>" .                             // 不能省略
            "<input type='hidden' name='business' value='$data_pay_account'>" .              // 贝宝帐号
            "<input type='hidden' name='item_name' value='Order:#$order[order_sn]'>" .              // payment for
            "<input type='hidden' name='amount' value='$data_amount'>" .                     // 订单金额
            "<input type='hidden' name='currency_code' value='$currency_code'>" .            // 货币
            "<input type='hidden' name='return' value='$data_return_url'>" .                 // 付款后页面
            "<input type='hidden' name='invoice' value='$order[order_sn]'>" .                  // 订单号
            "<input type='hidden' name='charset' value='utf-8'>" .                           // 字符集
            "<input type='hidden' name='no_shipping' value='1'>" .                           // 不要求客户提供收货地址
            "<input type='hidden' name='no_note' value=''>" .                                // 付款说明
            "<input type='hidden' name='notify_url' value='$data_notify_url'>" .
            "<input type='hidden' name='rm' value='2'>" .
            "<input type='hidden' name='cancel_return' value='$cancel_return'>" .
            '<input type="image" border="0" name="submit" class = "formsubbotton" alt="'. $GLOBALS['_LANG']['paypal_button'] .'" src="http://www.bestafford.com/temp/skin1/images/x-click-but5.gif" ><br /><br />' .                                                          // 按钮
            "<input type='submit' value='" . $GLOBALS['_LANG']['paypal_button'] . "'  class = 'formsubbotton' >" .                      // 按钮
            "</form><br />";

        return $def_url;
    }

    /**
     * 响应操作
     */
    function respond()
    {
		global $db,$Arr;
		
		
        $payment['paypal_currency'] = 'USD';
       // $merchant_id    = $payment['paypal_account'];               ///获取商户编号
		$merchant_id   = 'payment@bestafford.com';//$payment['paypal_account']ahappydealonlinepayment@gmail.com


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
//print_r($fp);	
//$content =  var_export($_POST, true);
//file_put_contents(ROOT_PATH.'syn/post.txt',$content);
//exit;
//$order_sn = 'E1102081703512838';
//$payment_amount = ' 2.48 ';

		
/*      $item_number    = $_POST['item_number'];
        $payment_status = $_POST['payment_status'];
        $payment_amount = $_POST['mc_gross'];
        $payment_currency = $_POST['mc_currency'];
        $txn_id         = $_POST['txn_id'];
        $receiver_email = $_POST['receiver_email'];
        $payer_email = $_POST['payer_email'];
        $order_sn = $_POST['invoice'];
        $memo = !empty($_POST['memo']) ? $_POST['memo'] : '';
        $action_note = $txn_id . '（' . $GLOBALS['_LANG']['paypal_txn_id'] . '）' . $memo;
*/
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
                if (strcmp($res, 'VERIFIED'))// == 0
                {
                    // process payment
					$order_sn = 'E1105112232152913';
					//查订单金额是否和付款金额相等
					$sql = "select order_amount,order_sn,order_id from ".ORDERINFO." where order_sn = '".$order_sn."'";
					$order = $db->selectinfo($sql);
					$order_amount = floatval($order['order_amount']);
					
					$sql = " order_status = '1',realpay = '".$payment_amount."',pay_time = '".gmtime()."' "; 
					
					//if ($order_amount > $payment_amount){
					//	$sql = " order_status = '11',realpay = '".$payment_amount."' ";   //订单状态取消
					//}
					
					$db->update(ORDERINFO,$sql," order_sn = '".$order_sn."'");
					
					/* 增加是否给客服发送邮件选项 */
					$order_info['firstname'] = empty($_COOKIE['firstname'])?'':$_COOKIE['firstname'];
					$order_info['order_no']  = $order['order_sn'];
					$order_info['order_id']  = $order['order_id'];
					send_email($_SESSION['email'],17,$order_info);  //发送已收到款邮件
					
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