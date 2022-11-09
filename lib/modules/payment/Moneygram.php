<?php

/**
 * 插件
 */

if (!defined('INI_WEB')){die('访问拒绝');}
global  $cur_lang;
$payment_lang = ROOT_PATH . 'languages/' .$cur_lang. '/payment/Moneygram.php';

if (file_exists($payment_lang))
{
    global $_LANG;

    include_once($payment_lang);
}


/**
 * 类
 */
class Moneygram
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function Moneygram()
    {
    }

    function __construct()
    {
        $this->Moneygram();
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
        $data_amount        = $order['order_amount'];


		$def_url  = '<div style=" text-align:left; width:600px;">'.$_LANG['Moneygram_desc'].'</div>';

        return $def_url;
    }

}

?>