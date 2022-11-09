<?php

/**
 * 插件
 */

if (!defined('INI_WEB')){die('访问拒绝');}
global  $cur_lang;
$payment_lang = ROOT_PATH . 'languages/' .$cur_lang. '/payment/WiredTransfer.php';

if (file_exists($payment_lang))
{
    global $_LANG;

    include_once($payment_lang);
}


/**
 * 类
 */
class WiredTransfer
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function WiredTransfer()
    {
    }

    function __construct()
    {
        $this->WiredTransfer();
    }

    /**
     * 生成支付代码
     * @param   array   $order  订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order)
    {
    	global $_LANG;
        $data_order_id      = $order['order_id'];
        //$data_amount        = $order['order_amount'];
		$data_amount        =  price_format($order['order_amount'] * 0.95,false);

        $def_url  = '<div style=" text-align:left; width:600px;">
		Pay us through Wired Transfer, you have <font color="#CC0000">5% off</font> (Subtotal:<b><font color="#FF0000">&nbsp;&nbsp;USD '.$data_amount.' </font></b>)<br><br>
		<p>Please pay in a week in accordance with the way you choose remittances Please mark your remittance orders!</p>';

		$payment_arr    = read_static_cache('payment', ADMIN_STATIC_CACHE_PATH);
		$payment_info   = $payment_arr['WiredTransfer'];

		if ($order['order_amount'] < 1000) {//小于1000
			$def_url .= $payment_info['pay_desc_lt1000'];
		}
		else {//其它
			$def_url .= $payment_info['pay_desc_gt1000'];
		}

        return $def_url;
    }
}

?>