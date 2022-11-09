<?php
/**
 * 根据国家，重量，运输方式计算出运费
 * @var string key_code 验证码
 * @var string country_code 国家编码
 * @var int shipping_method 运输方式ID
 * @var float weight 产品重量
 * @example http://www.dealsmachine.com/ApiErp/shipping_cost.php?key_code=c71d26cabebad7bce64c28bb6fea6770&country_code=GB&shipping_method=2&weight=1&freeship_weight=0.45
 * @author Jim liang 2013-1-9
 */
define('INI_WEB', true);
require ("../fun/inc.main.php");
require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');
require(ROOT_PATH . 'lib/lib.f.order.php');
require(ROOT_PATH . 'lib/lib.f.goods.php');
require_once(ROOT_PATH . 'lib/lib.f.transaction.php');
require_once(ROOT_PATH . 'lib/syn_public_fun.php');
if(empty($cur_lang))$cur_lang = 'eng';
require_once(ROOT_PATH . "languages/$cur_lang/shopping_flow.php");
if(!empty($_LANG))$Arr['lang'] =  $_LANG;


$weight = empty($_GET['weight'])?0:floatval($_GET['weight']); //产品重量
$freeship_weight = empty($_GET['freeship_weight'])?0:floatval($_GET['freeship_weight']); //免邮产品重量
$country_code = empty($_GET['country_code'])?0:$_GET['country_code']; //国家代码
$shipping_method = empty($_GET['shipping_method'])?0:intval($_GET['shipping_method']); //运输方式
$key_code = empty($_GET['key_code'])?0:$_GET['key_code']; //验证码

if ($key_code != $_CFG['keys_code']){die('Error,key code error');}
if(!$country_code)die('Country is empty!');
if(!$shipping_method)die('shiping method is empty');
if(!$weight)die('shipping weight is empty');

$country_id = $db->getone("select region_id from eload_region where region_code ='$country_code' limit 1");//取得指定国家的运费信息
if(!$country_id)die('Country not found!');
$shipping_list     = available_shipping_list();
if(empty($shipping_list))die('shiping method not found');
$total = 0;
$shipping_cost = shipping_fee_cost($shipping_list,$country_id,0,$weight,0,$weight,$total); //计算运费
//$shipping_cost = shipping_fee_cost($shipping_list,$country_id,$weight,0,$weight,,$total); //计算运费
$msg = '';
//echo $freeship_weight;
if(empty($shipping_cost[$shipping_method])){
	$msg .= 'shipping method error!';
}else{
	$msg .= $shipping_cost[$shipping_method]['ship_price'];
	if($freeship_weight){
	
		$shipping_cost = get_shipping_fee(0,$freeship_weight);//计算免邮运费
		$msg .= '|'.number_format($shipping_cost,2);

	}else{
		$msg .= '|0';
	}
}
echo $msg;

?>