<?php

/**
 * 插件
 */

if (!defined('INI_WEB')){die('访问拒绝');}
global  $cur_lang;
$payment_lang = ROOT_PATH . 'languages/' .$cur_lang. '/payment/CreditCard.php';

if (file_exists($payment_lang))
{
    global $_LANG;

    include_once($payment_lang);
}


/**
 * 类
 */
class CreditCard
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
	var $MD5key = "ERCYRbIW";

    function CreditCard()
    {
    }

    function __construct()
    {
        $this->CreditCard();
    }

    /**
     * 生成支付代码
     * @param   array   $order  订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order)
    {
		$MerNo = "3088";
		$BillNo = trim($order['order_sn']);
		$CurrencyStr = "15";
		$Amount =  trim($order['order_amount']);
		$Language = "2";
		$ReturnURL = $GLOBALS['_CFG']['creat_html_domain'].'m-flow-a-payok-code-CreditCard.htm';
		$md5src=$MerNo.$BillNo.$CurrencyStr.$Amount.$Language.$ReturnURL.$this->MD5key;		//MD5加密
		$MD5info=strtoupper(md5($md5src));			                                    //'转换大写
		$Remark=htmlspecialchars(trim($order['postscript']));

		$def_url  = '<form method="post" action="https://payment.ctopay.com/payment/Interface"  name="formsub">
			  <input type="hidden" name="MerNo" value="'.$MerNo.'">
			  <input type="hidden" name="Currency" value="'.$CurrencyStr.'">
			  <input type="hidden" name="BillNo" value="'.$BillNo.'">
			  <input type="hidden" name="Amount" value="'.$Amount.'">
			  <input type="hidden" name="ReturnURL" value="'.$ReturnURL.'" size="60">
			  <input type="hidden" name="Language" value="'.$Language.'">
			  <input type="hidden" name="MD5info" value="'.$MD5info.'">
			  <input type="hidden" name="Remark" value="'.$Remark.'">
			  <input type="submit" name="b1" class = "formsubbotton" style="height:30px" value="Pay By Credit Card">
		  </form>';
        return $def_url;
    }



	//响应操作
    function respond(){
		global $db;
		$BillNo       = !empty($_REQUEST["BillNo"])?trim($_REQUEST["BillNo"]):'';
		$CurrencyStr  = !empty($_REQUEST["Currency"])?trim($_REQUEST["Currency"]):'';
		$Amount       = !empty($_REQUEST["Amount"])?trim($_REQUEST["Amount"]):'';
		$Succeed      = !empty($_REQUEST["Succeed"])?trim($_REQUEST["Succeed"]):'';
		$Result       = !empty($_REQUEST["Result"])?trim($_REQUEST["Result"]):'';
		$MD5info      = !empty($_REQUEST["MD5info"])?trim($_REQUEST["MD5info"]):'';

		if (trim($BillNo)==''){
			return "Paid error, Order No. is null";
		}

		$md5src = $BillNo + $CurrencyStr + $Amount + $Succeed + $this->MD5key;
		$MD5info=strtoupper(md5($md5src));			                                    //'转换大写

		if ($Succeed=='1'){
		  if ($md5info == $md5str){
				$sql = " order_status = '1' ";
				$db->update(ORDERINFO,$sql," order_sn = '".$BillNo."'");
			return "Credit Card payment successful";
		  }
		}
	}


}

?>