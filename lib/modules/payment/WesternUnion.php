
<?php

/**
 * 插件
 */

if (!defined('INI_WEB')){die('访问拒绝');}
global  $cur_lang;
$payment_lang = ROOT_PATH . 'languages/' .$cur_lang. '/payment/WesternUnion.php';

if (file_exists($payment_lang))
{
    global $_LANG;

    include_once($payment_lang);
}


/**
 * 类
 */
class WesternUnion
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function WesternUnion()
    {
    }

    function __construct()
    {
        $this->WesternUnion();
    }

    /**
     * 生成支付代码
     * @param   array   $order  订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order)
    {
        $data_amount        =  price_format($order['order_amount'] * 0.95,false);


		  $def_url  = '<div style=" text-align:left; width:600px;">'.$GLOBALS['_LANG']['get_code_WesternUnion_1'] . '&nbsp;&nbsp;<span class="bizhong">USD</span> <span class="my_shop_price" orgp="'.$data_amount.'"></span>' .$GLOBALS['_LANG']['get_code_WesternUnion_2'].'</div>';






/*        $def_url  = '<br /><form style="text-align:center;" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">' .   // 不能省略
            "<input type='hidden' name='cmd' value='_xclick'>" .                             // 不能省略
            "<input type='hidden' name='business' value='$data_pay_account'>" .                 // 贝宝帐号
            "<input type='hidden' name='item_name' value='$order[order_sn]'>" .                 // payment for
            "<input type='hidden' name='amount' value='$data_amount'>" .                        // 订单金额
            "<input type='hidden' name='currency_code' value='$currency_code'>" .            // 货币
            "<input type='hidden' name='return' value='$data_return_url'>" .                    // 付款后页面
            "<input type='hidden' name='invoice' value='$data_order_id'>" .                      // 订单号
            "<input type='hidden' name='charset' value='utf-8'>" .                              // 字符集
            "<input type='hidden' name='no_shipping' value='1'>" .                              // 不要求客户提供收货地址
            "<input type='hidden' name='no_note' value=''>" .                                  // 付款说明
            "<input type='hidden' name='notify_url' value='$data_notify_url'>" .
            "<input type='hidden' name='rm' value='2'>" .
            "<input type='hidden' name='cancel_return' value='$cancel_return'>" .
            '<input type="image" border="0" name="submit" alt="'. $GLOBALS['_LANG']['paypal_button'] .'" src="/temp/skin1/images/x-click-but5.gif" ><br /><br />' .                                                          // 按钮
            "<input type='submit' value='" . $GLOBALS['_LANG']['paypal_button'] . "'>" .                      // 按钮
            "</form><br />";
*/
        return $def_url;
    }

}

?>