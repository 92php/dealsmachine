<?php
define('INI_WEB', true);
require_once("fun/inc.main.php");
require_once("paypalfunctions.php");
require_once('fun/fun.global.php');
require_once('fun/fun.public.php');
require_once('lib/lib.f.order.php');
require_once('lib/modules/ipb.php');
$paymentAmount = $_SESSION["Payment_Amount"];
if(!empty($_GET['test'])) print_r($_SESSION);
$currencyCodeType = "USD";
$paymentType = "Sale";
$hostname = 'http://ba.everbuying.net';

//跳转到对应语言的页面
$default_lang     = 'en';      //默认语言
$cur_lang = empty($_GET['cur_lang_exp']) ? (empty($cur_lang) ? $default_lang : $cur_lang) : trim($_GET['cur_lang_exp']);
$return_lang = '';
if($cur_lang != $default_lang)
{
	$return_lang = "/".$cur_lang;
}
//checkout页面
if(!empty($pay_step) && $pay_step=='checkout')
{
    $returnURL = "{$hostname}{$return_lang}/m-flow-a-DoExpressCheckoutPayment.htm?order_sn=". $order['order_sn'];
    $cancelURL = "{$hostname}{$return_lang}/m-flow-a-cart.htm";
    $resArray = CallShortcutExpressCheckout_1 ($paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $cart_goods, $order);
}
else //cart页面
{
	$cart_goods = get_cart_goods();    //商品信息
	if(empty($cart_goods['goods_list'])){
		header("Location: {$return_lang}/m-flow-a-cart.htm");
		exit;
	}	
    $returnURL = "{$hostname}{$return_lang}/m-flow-a-exp_checkout.htm";
    $cancelURL = "{$hostname}{$return_lang}/m-flow-a-cart.htm";
    $resArray = CallShortcutExpressCheckout ($paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $cart_goods);
}
$ack = strtoupper($resArray["ACK"]);
if($ack=="SUCCESS")
{
	RedirectToPayPal ( $resArray["TOKEN"] );
} 
else
{
	//Display a user friendly Error on the page using any of the following error information returned by PayPal
	$ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
	$ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
	$ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
	$ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
	
	echo "SetExpressCheckout API call failed. ";
	echo "Detailed Error Message: " . $ErrorLongMsg;
	echo "Short Error Message: " . $ErrorShortMsg;
	echo "Error Code: " . $ErrorCode;
	echo "Error Severity Code: " . $ErrorSeverityCode;
}
?>