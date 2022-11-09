<?php
/**
 * 更新帐户帐单地址
 * $user_id 需要更新的用户帐单地址，一个用户只能有一个帐单地址
 * $post 需要更新或加入的帐单地址
 * 20121116
 */

define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/syn_public_fun.php');

//print_r($_REQUEST);

$keys_code = empty($_REQUEST['keys_code'])?'':$_REQUEST['keys_code'];
if ($keys_code!=$_CFG['keys_code']){die('Error,key code error');}

$act = $_REQUEST['act'];
$order_id = $_POST['order_id'];
$order_sn = $_POST['item_number'];

if($act == 'write'){
	$user_id = $db->getOne("select user_id from ".ORDERINFO." where order_id='".$order_id."' and order_sn='".$order_sn."'");
	if($user_id > 0){
		include_once('../lib/lib.f.transaction.php');
		$address_id = $db->getOne("select address_id from ".BILLADDR." where user_id=".$user_id);
		$address = array(
			'user_id'      => $user_id,
			'address_id'   => intval($address_id),
			'country'      => isset($_POST['countrycode'])? trim($_POST['countrycode'])  : '',
			'province'     => isset($_POST['state'])      ? trim($_POST['state'])        : '',
			'city'         => isset($_POST['city'])       ? trim($_POST['city'])         : '',
			'addressline1' => isset($_POST['address1'])   ? trim($_POST['address1'])     : '',
			'addressline2' => isset($_POST['address2'])   ? trim($_POST['address2'])     : '',
			'firstname'    => isset($_POST['firstname'])  ? trim($_POST['firstname'])    : '',
			'lastname'     => isset($_POST['lastname'])   ? trim($_POST['lastname'])     : '',
			'email'        => '',
			'tel'          => isset($_POST['phone'])      ? make_semiangle(trim($_POST['phone'])) : '',
			'zipcode'      => isset($_POST['postcode'])   ? make_semiangle(trim($_POST['postcode'])) : '',
		);
		//file_put_contents('aaa.txt', print_r($address,true));
		//return true
		if(update_billing_address($address)){
			//echo 'success';
		}
	}
}elseif($act == 'read'){
	$user_id = $db->getOne("select user_id from ".ORDERINFO." where order_id='".$order_id."' and order_sn='".$order_sn."'");
	if($user_id > 0){
		$address_id = $db->getOne("select address_id from ".BILLADDR." where user_id=".$user_id);
		if($address_id > 0){
			echo serialize($db->selectinfo("select * from ".BILLADDR." where address_id=".$address_id));
		}else{
			echo serialize($db->selectinfo("select * from ".ADDR." where address_id =(select address_id from ".USERS." where user_id =(select user_id from ".ORDERINFO." where order_id=".$order_id."))"));
		}
	}
}elseif($act == 'update_ip'){//更新付款ip
	$order_id = empty($_POST['order_id'])?'':$_POST['order_id'];
	$ip_address = empty($_POST['ip_address'])?'':$_POST['ip_address'];
	if(is_int($order_id)&&$ip_address){
		$db->update(ORDERINFO, "pay_ip='$ip_address'","order_id = '$order_id'");
	}
	echo 'ok';
}
?>