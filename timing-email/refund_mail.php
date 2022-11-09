<?php
/**
 * 退款时发送邮件
 * by fangxin on 2014-02-14 PM
 */
define('INI_WEB', true);
$order = empty($_REQUEST['order'])?'':$_REQUEST['order'];
$order = trim($order);
$order = stripslashes($order);
require('../lib/global.php');              //引入全局文件
include(ROOT_PATH.'languages/en/common.php');	
/*
//demo 
$order = array (
  'record_id' => '401235',
  'payment_id' => '1H4732224B612200D',
  'order_number' => 'DD1309090707045772',
  'amount' => '132.32',
  'product_list' => 
  array (
    0 => 
    array (
      'product_code' => 'UU0038801',
      'quantity' => '4',
    ),
  ),
  'template' => '8',
  'amount' => '100',
  'type' => '2'
);
//序列化数组
$order = serialize($order);
//$order ='a:7:{s:9:"record_id";s:6:"401235";s:12:"order_number";s:18:"DD1309090707045772";s:10:"payment_id";s:17:"1H4732224B612200D";s:6:"amount";s:6:"132.32";s:12:"product_list";a:0:{}s:8:"template";s:1:"8";s:4:"type";s:1:"2";}';
*/


$keys_code = empty($_REQUEST['keys_code'])?'':$_REQUEST['keys_code'];
if ($keys_code!=$_CFG['keys_code']){die('Error,key code error');}
if(empty($order)){
	echo '数据无效';
	exit();
}

//file_put_contents('20140218order.txt',var_export($order,true));

$order = unserialize($order);
if(!is_array($order)){
	echo $_REQUEST['order'];
	echo '数据无效';
	exit();
}
$order_sn = $order['order_number'];
ini_set('display_errors', 1);
error_reporting(E_ALL);
require('../lib/time.fun.php');
require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');  //取得模版标题
include(ROOT_PATH . 'languages/en/common.php');
include(ROOT_PATH . 'languages/en/user.php');
require(ROOT_PATH . 'lib/lib.f.transaction.php');
$Arr['lang']   =  $_LANG;
$Tpl->caching = false;        //使用缓存
$now_time = gmtime();
$order_id = $db->getOne("select order_id from eload_order_info where order_sn='$order_sn'");
if(empty($order_id)){
	echo 'order not found';
	exit();
}
if(!empty($order['amount']))$Arr['refund_money'] = $order['amount'];
$Arr['order'] = get_order_detail($order_id);
$Arr['email'] = $Arr['order']['email'];
$Arr['payment_id'] = $order['payment_id'];
if(!empty($order['product_list'])){
	$goods_list = order_goods($order_id);
	foreach ($goods_list as $k=>$v){
		$goods_number = find_goods_in_array($order['product_list'],$v['goods_sn']);
		if($goods_number>0){
			$goods_list[$k]['goods_number'] = $goods_number;
		}else {
			unset($goods_list[$k]);
		}
	}
	$Arr['goods_list'] = $goods_list;
}

//将原ID为35，36,37,38,39,40,41,43邮件合并为一封邮件，ID号为43.
if(8 == $order['template']) {
	$send_temp_id = 43;
	switch($order['type']){
		case 1: //缺货 缺货/部分发货,客户取消订单,价格问题
			$desc = $_LANG['refund_desc_1'];
			break;
		case 2: //客户取消_下重单,客户取消_下单产品数量重复 ,客户取消_付重款 ,客户取消_下错单 ,客户取消_预期不能收到货 ,客户取消-其他 发货不及时 
			$desc = $_LANG['refund_desc_2'];
			break;
		case 3: //地址联系不上
			$desc = $_LANG['refund_desc_3'];
			break;
		case 4: //风险控制_黑名单客户,风险控制_危险订单,风险控制_联系其它方式付款
			$desc = $_LANG['refund_desc_4'];
			break;
		case 5: //运输问题_偏远,运输问题_超体积,运输问题_更换运输方式 运输问题_禁忌品问题
			$desc = $_LANG['refund_desc_5'];
			break;
		case 6: //其他退款（保险费、 折扣、配件未发,换货退差价）
			$desc = $_LANG['refund_desc_6'];
			break;
	}
} else {
	echo $order['record_id'];
	exit();
}
$email        = $Arr['order']['email'];
//检查是否已发邮件
if(check_have_send_mail($email,$send_temp_id,$order['record_id'])){
	echo $order['record_id'];
	exit;
};
$Tpl->assign('refund_desc', $desc);
foreach( $Arr as $key => $value ){
	$Tpl->assign($key, $value );
}
//获得收货人信息 fangxin 2013/07/22
$sql = "SELECT email, firstname, lang FROM " . USERS . " WHERE email = '" . $Arr['order']['email'] ."'";
$user_info = $db->selectinfo($sql);
$lang = $user_info['lang'];
if(!empty($lang)) {
	$mail_subject = $mail_conf[$lang][$send_temp_id];
	$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $lang .'/'.$send_temp_id.'.html');	
} 
if(empty($mail_subject)) {
	$mail_subject = $mail_conf['en'][$send_temp_id];
	$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/en/'.$send_temp_id.'.html');	
}		
$mail_subject = str_replace('$order_sn',$Arr['order']['order_sn'],$mail_subject);		
		
if(!empty($_REQUEST['test'])){
	echo $email;
	echo "<br>";
	echo $mail_subject;
	echo $mail_body;
}

if($email && $mail_subject && $mail_body){
	//exec_send('snipersheep@aliyun.com',$mail_subject,$mail_body);
	//exec_send('senv-1988@163.com',$mail_subject,$mail_body);	
	if(exec_send($email,$mail_subject,$mail_body)){
		$Arr['order']['send_mail_state'] = 1;
	}else {
		$Arr['order']['send_mail_state'] = 0;	
	}
}
echo $order['record_id'];
//邮件发送记录保存 20140304 fangxin PM
$data = array(
	'firstname' => '',
	'lastname' => '',
	'email' => $email,
	'order_num' => $order_id,
	'turnover' => $Arr['refund_money'],
	'template_id' => $send_temp_id,
	'pri' => 5,
	'state' => $Arr['order']['send_mail_state'],
	'erp_record_id' => $order['record_id'],
	'last_send' => time()
);
add_mail_log($data);
exit;		

//查找退款产品的数量
function find_goods_in_array($arr,$goods_sn){
	$goods_number = 0;
	foreach ($arr as $v){
		if($v['product_code'] == $goods_sn){
			$goods_number = $v['quantity'];
			break;
		}
	}
	return $goods_number;
}
