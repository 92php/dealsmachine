<?php
/**
 * dealsmachine定时导订单
 *
 * @author wuwenlong <2011-07-26>
 */
set_time_limit(0);
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');              //引入全局文件
require_once('api_config.php');
require_once('nusoap.php');
$client = new nusoap_client($webserviceUrl,true);
$client->soap_defencoding = 'UTF-8';
$client->decode_utf8 = false;
$client->xml_encoding = 'utf-8';
$_INPUT['identity'] = IDENTITY;
$order_sn = isset($_GET['order_sn']) ? trim($_GET['order_sn']) : '';    //人为导单，传order_sn
$number = isset($_GET['number']) ? trim($_GET['number']) : 50;    //设定每次导单数量
$is_test = isset($_GET['is_test']) ? trim($_GET['is_test']) : 0;    //设定每次导单数量
$where    = $order_sn ? "order_sn='{$order_sn}'" : 'is_to_erp=0';
if(isset($_GET['number'])) $where.=" and order_amount>0.01";
$area_Arr = read_static_cache('area_key',2);
$sql = "select i.*,u.email from eload_order_info i left join ".USERS." u on i.user_id=u.user_id where {$where} order by order_id desc limit $number ";
$OrderArr = $db->arrQuery($sql);
$errors = '';
if(!empty($OrderArr)){
	foreach($OrderArr as $_ROW){
	    $_INPUT['user_email'] = $_ROW['email'];	//用户表里的email
	    $_INPUT['user_id'] = $_ROW['user_id'];	//用户表里的email
		$_INPUT['order_number'] = $_ROW['order_sn'];
		$_INPUT['order_id'] = $_ROW['order_id'];
		$_INPUT['pay_ip'] = $_ROW['pay_ip'];
		$_INPUT['payment_status'] = $_ROW['order_status'];
		$_INPUT['customers']    = urlencode($_ROW['consignee']);
		$_INPUT['invoice']  = $_ROW['Invoice'];
		$_INPUT['city']         = urlencode($_ROW['city']);
		$_INPUT['state']        = urlencode($_ROW['province']);
		$_INPUT['remark']       = urlencode($_ROW['postscript']);
		$_INPUT['point_money']             = $_ROW['point_money'];	
		$_INPUT['yuan_goods_amount']       = $_ROW['yuan_goods_amount'];	
		$_INPUT['goods_amount']            = $_ROW['goods_amount'];	
		$_INPUT['promotion_code_youhuilv'] = urlencode($_ROW['promotion_code_youhuilv']);	
		$_INPUT['promotion_code']          = $_ROW['promotion_code'];	
		$_INPUT['used_point']      = $_ROW['used_point'];
		$_INPUT['order_currency']          = $_ROW['order_currency']; //币种
		$_INPUT['order_rate']              = $_ROW['order_rate'];     //汇率
		$_INPUT['order_create_time'] = $_ROW['add_time'];		//订单创建时间戳
		$_INPUT['order_from']        = 'dealsmachine';   		    //订单来源		
		$_INPUT['amount_info'] = urlencode("Items Sub-total :{$_ROW['yuan_goods_amount']} USD - saving:" . ($_ROW['yuan_goods_amount'] - $_ROW['goods_amount'] + $_ROW['point_money']) . ' USD ' . (empty($_ROW['promotion_code_youhuilv']) ? '' : " - coupon: {$_ROW['promotion_code_youhuilv']}") . " + Insurance {$_ROW['insure_fee']} USD + Shipping Costs :" . ($_ROW['shipping_fee'] + $_ROW['free_shipping_fee']) . " + tracking_number_price: {$_ROW['Need_Traking_number']}=Grand Total:{$_ROW['order_amount']} USD");
		if(!empty($area_Arr[$_ROW['country']])){
			$_INPUT['country']      = $area_Arr[$_ROW['country']]['region_code'];
			$_INPUT['customer_email'] = $_ROW['email'];
			$_INPUT['address1']       = urlencode($_ROW['address']);
			$_INPUT['address2']       = '';
			$_INPUT['zip_code']       = urlencode($_ROW['zipcode']);
			$_INPUT['tel']            = $_ROW['tel'];
			$_INPUT['tracking_number_price']  = $_ROW['Need_Traking_number'];
			if ($_ROW['pay_id'] == 'WesternUnion'){
				$_ROW['pay_id'] = '西联';
			}else if($_ROW['pay_id'] == 'CreditCard'){
				$_ROW['pay_id'] = 'PAYPAL';
			}else if($_ROW['pay_id'] == 'WiredTransfer'){
				$_ROW['pay_id'] = '银行转账';
			}else if($_ROW['pay_id'] == 'PayPal'){
				$_ROW['pay_id'] = 'PAYPAL';
			}else if($_ROW['pay_id'] == 'GiftCard'){
				$_ROW['pay_id'] = 'GiftCard';
			}else if($_ROW['pay_id'] == 'webcollect'){
				$_ROW['pay_id'] = 'webcollect';
			}else{
				$_ROW['pay_id'] = '其他';
			}
			$_ROW['pay_id'] = strtoupper($_ROW['pay_id']);
			//PAYPAL
			//西联
			//信用卡
			//银行转账
			//其他
			$_INPUT['payment']       = $_ROW['pay_id'];			  //问题1   	 	cari
			$_INPUT['insure_fee']    = $_ROW['insure_fee'];			  //问题1   	 	cari
			$_INPUT['express']       = $_ROW['shipping_id'];	                    //问题2
			$_INPUT['total_post']    = $_ROW['shipping_fee'] + $_ROW['free_shipping_fee'];
			//总优惠
			$_INPUT['preferential'] = !empty($_ROW['yuan_goods_amount'])?($_ROW['yuan_goods_amount'] - $_ROW['goods_amount'] + $_ROW['point_money']):$_ROW['point_money'];
			$_INPUT['order_date'] = local_date($GLOBALS['_CFG']['time_format'], $_ROW['add_time']);
			$_INPUT['total_price'] = $_ROW['order_amount'];
			$_INPUT['order_product_array'] = array();
			$sql = "select if(g.gifts_id >0,1,0) as is_gift,if(g.is_free_shipping = 1,1,2) as is_free_shipping,og.main_goods_id,free_shipping_fee AS shipping_price,og.goods_sn as product_code,og.goods_number as quantity,og.goods_price as product_sale_price , 0 as product_post_price, CONCAT_WS(' ',og.goods_attr, custom_size) as product_remark,og.goods_type from eload_order_goods og left join eload_goods g on og.goods_id = g.goods_id  where order_id ='".$_ROW['order_id']."'";
			$_INPUT['order_product_array'] = $db->arrQuery($sql);
			if($is_test)print_r($sql);
			if($is_test)print_r($_INPUT);
			$result = $client->call('add_order', $_INPUT);
			if($result == '1'){
				$sql = "update eload_order_info set is_to_erp = 1 where order_id = '".$_ROW['order_id']."'";
				$db->query($sql);
                echo "<h3 >恭喜您, 订单".$_ROW['order_id'].": ".$_ROW['order_sn']." 导入成功!</h3>";
			}else{
				$errors .=  $_ROW['order_sn']  .':'. print_r($result,true) . '</br>  ';
				if ( strpos($errors,'已经存在') !== false )
				{
					$sql = "update eload_order_info set is_to_erp = 1 where order_id = '".$_ROW['order_id']."'";
					$db->query($sql);

					$sql = "insert into eload_order_to_erp_records (order_id,addtime) values ('".$_ROW['order_id']."','".gmtime()."')";
					$db->query($sql);
					$errors = '';
				}
			}
		}
	}
	if(!empty($errors)){
		exec_send('snipersheep@aliyun.com',' Web Service order result ',$errors);
		print_r($errors);
	}
}else{
	echo '没找到适合条件的订单';
}
