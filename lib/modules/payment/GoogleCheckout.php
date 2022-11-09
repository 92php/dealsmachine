<?php

/**
 * 插件
 */

if (!defined('INI_WEB')){die('访问拒绝');}
global  $cur_lang;
$payment_lang = ROOT_PATH . 'languages/' .$cur_lang. '/payment/GoogleCheckout.php';

if (file_exists($payment_lang))
{
    global $_LANG;

    include_once($payment_lang);
}


/**
 * 类
 */
class GoogleCheckout
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function GoogleCheckout()
    {
    }

    function __construct()
    {
        $this->GoogleCheckout();
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


	$def_url  = '<form method="POST"  name="formsub" action="https://checkout.google.com/cws/v2/Merchant/103439583641328/checkoutForm" accept-charset="utf-8">
	  <input type="hidden" name="item_name_1" value="Davis Micro #001 Special"/>
	  <input type="hidden" name="item_description_1" value="Order Number:'.$order['order_sn'].'"/>
	  <input type="hidden" name="item_quantity_1" value="1"/>
	  <input type="hidden" name="item_price_1" value="'.$data_amount.'"/>
	  <input type="hidden" name="tax_rate" value="0.0775"/>
	  <input type="hidden" name="tax_us_state" value="CA"/>
	  <input type="image" name="Google Checkout"  class = "formsubbotton"  alt="Checkout through Google" src="http://www.bestafford.com/temp/skin1/images/checkout.gif" height="46" width="180"/><br />
	  <input type="submit"  class = "formsubbotton"  value="' . $GLOBALS['_LANG']['paypal_button'] . '">
    </form>';

        return $def_url;
    }

}

?>