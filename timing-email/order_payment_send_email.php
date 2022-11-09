<?php
/**
 * 邮件邀请付款定时任务脚本
 * */
set_time_limit(0);
define('INI_WEB', true);
require('../lib/global.php');                               //引入全局文件
require('../lib/time.fun.php');
require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');  //取得模版标题
include(ROOT_PATH . 'languages/en/common.php');
include(ROOT_PATH . 'languages/en/user.php');
$Arr['lang']        =  $_LANG;
$Tpl->caching       = false;        //使用缓存
$email_temp_id      = 32;		    //模板ID
$email_histroy_type = 4;	        //邮件发送历史类型
//print_r(get_order_goods(841230, 719635));
//邮件邀请阶梯价格PCODE优惠率配置变量
$PCode_config_array = array(
	3 => array(
			'min' => 0,
			'max' => 15,
			),
	4 => array(
			'min' => 15,
			'max' => 40,
			),
	5 => array(
			'min' => 40,
			'max' => 100,
			),
	6 => array(
			'min' => 100,
			'max' => 'max',
			)
);

//系统自动生成当天的PCODE编码
$pcodeArr = array(
	'create_time' => gmtime(),
	'exp_time' => gmtime()+24*3600*7,
	'cishu' => 0,
	'times' => 0,
	'users' => '',
	'goods' => '',
	'fangshi' => 1,
	'is_applay' => 0
);
foreach ($PCode_config_array as $key=>$value)
{	
	$error_no = 0;
	do
	{
		$pcodeArr['youhuilv']  = $key;
		$pcodeArr['code']   = randomkeys(8);
		$db->autoExecute(PCODE, $pcodeArr);
		$error_no = $db->Errno;			
		if ($error_no > 0 && $error_no != 1062)
		{
			die($GLOBALS['db']->errorMsg());
		}
		$PCode_config_array[$key]['code'] = $pcodeArr['code'];
	}
	while ($error_no == 1062); //如果是PCODE编码重复则重新提交数据
	$PCode_config_array[$key]['id'] = $GLOBALS['db']->insertId();
}
$result_msg = '';
//获得一个月内付款次数超过5次的用户
$month_time = time()-(30*24*60*60);
$sql = "SELECT user_id ,count(user_id) AS pay_num FROM eload_order_info WHERE add_time >= " . $month_time . " AND order_status > 0 GROUP BY user_id";
$query = $db->query($sql);
$filter_user = array();
while ($rows = $db->fetchRow($query)){
	if($rows['pay_num'] >= 5){
		$filter_user[] = $rows['user_id'];
	}
}
//获得一天内有付款记录的用户
$there_day_time = time()-(60*60*24*1) ;
$sql = "SELECT user_id FROM eload_order_info WHERE add_time >= " . $there_day_time . " AND order_status > 0";
$three_day_pay = $db->arrQuery($sql);
foreach ($three_day_pay as $key=>$value)
{
	$filter_user[] = $value['user_id'];
}
$no_user_string = implode("','",$filter_user);
$start_time = strtotime(date('Y-m-d',time()-(3*24*60*60)));
$end_time = $start_time + 24*60*60;
$sql = "SELECT u.firstname,u.lastname,o.email,u.user_id,u.lang,o.order_id,o.order_amount,o.add_time,FROM_UNIXTIME(o.add_time,'%Y-%m-%d') as date," .
		" o.order_sn,DATEDIFF(curdate(),FROM_UNIXTIME(o.add_time,'%Y-%m-%d')) as days ". 
		" FROM eload_users AS u, eload_order_info AS o " . 
		" WHERE u.user_id = o.user_id AND o.user_id NOT IN ('" . $no_user_string . "') AND add_time >= " . $start_time ." AND add_time <= " . $end_time . " and o.order_status = 0 ";
/*$sql = "SELECT u.firstname,u.lastname,o.email,u.user_id,o.order_id,o.order_amount,o.add_time,FROM_UNIXTIME(o.add_time,'%Y-%m-%d') as date," .
		" o.order_sn,DATEDIFF(curdate(),FROM_UNIXTIME(o.add_time,'%Y-%m-%d')) as days ". 
		" FROM eload_users AS u, eload_order_info AS o " . 
		" WHERE u.user_id = o.user_id AND (order_sn = '" . $_GET['order_sn'] . "') and o.order_status = 0 ";*/

$user_list = $db->arrQuery($sql);
include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
if (!empty($user_list )){
	foreach($user_list as $row){		
		$order_id = $row['order_id'];
		$user_id = $row['user_id'];
		$order = get_order_detail($order_id, $user_id);		
		if ($order !== false)
		{	
			$Arr['order_id'] = $row['order_id'];
			$Arr['order_sn'] = $row['order_sn'];
			$Arr['add_time'] = $row['date'];
			$Arr['order_amount'] = $row['order_amount'];
			$Arr['firstname'] = $row['firstname'];			
			//当前订单PCODE编号和优惠率
			if($Arr['order_amount']>$PCode_config_array[3]['min'] && $Arr['order_amount']<=$PCode_config_array[3]['max'])
			{
				$Arr['code'] = $PCode_config_array[3]['code'];
				$Arr['youhuilv'] = 3;
				$coupon_code = $PCode_config_array[3]['code'];
				$coupon_id = $PCode_config_array[3]['id'];
			}
			elseif ($Arr['order_amount']>$PCode_config_array[4]['min'] && $Arr['order_amount']<=$PCode_config_array[4]['max'])
			{
				$Arr['code'] = $PCode_config_array[4]['code'];
				$Arr['youhuilv'] = 4;
				$coupon_code = $PCode_config_array[4]['code'];
				$coupon_id = $PCode_config_array[4]['id'];
			}
			elseif ($Arr['order_amount']>$PCode_config_array[5]['min'] && $Arr['order_amount']<=$PCode_config_array[5]['max'])
			{
				$Arr['code'] = $PCode_config_array[5]['code'];
				$Arr['youhuilv'] = 5;
				$coupon_code = $PCode_config_array[5]['code'];
				$coupon_id = $PCode_config_array[5]['id'];
			}
			elseif ($Arr['order_amount']>$PCode_config_array[6]['min'] && $Arr['order_amount']<=$PCode_config_array[6]['max'])
			{
				$Arr['code'] = $PCode_config_array[6]['code'];
				$Arr['youhuilv'] = 6;
				$coupon_code = $PCode_config_array[6]['code'];
				$coupon_id = $PCode_config_array[6]['id'];
			}	
			$order_data = get_order_goods($order_id, $user_id);	
			if(!empty($order_data)) {
				$Arr['goods_list'] = $order_data['goods_list'];
				$Arr['recommend_goods'] = $order_data['recommend_goods'];
			}				
			foreach( $Arr as $key => $value ){
				$Tpl->assign( $key, $value );
			}					
			$email        = $row['email'];
			$lang         = $row['lang'];
			if(empty($lang) || $lang == 'en') {
				$mail_subject = $mail_conf['en'][$email_temp_id];			
				$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/en/'.$email_temp_id.'.html');				
			} else {
				$mail_subject = $mail_conf[$lang][$email_temp_id];			
				$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $lang .'/'.$email_temp_id.'.html');								
			}			
			$history = array();
			$history['firstname']     = $row['firstname'];
			$history['lastname']      = $row['lastname'];       
			$history['email']         = $row['email'];             
			$history['order_num']     = 1; 
			$history['turnover']      = $order['formated_order_amount'];
			$history['template_id']   = $email_temp_id;             
			$history['email_type_id'] = $email_histroy_type; 
			$history['pri']           = 0;
			$history['last_send'] = gmtime();			
			if($email &&  $mail_subject && $mail_body) {
				if(exec_send($email,$mail_subject,$mail_body)){
					$history['state'] = 1;
					$result_msg .= $row['email'].' Sent successfully<br>';					
					//把订单标示成邮件邀请付款
					$db->update(ORDERINFO,' email_pay_invite = 1 '," order_id = '".$row['order_id']."'");					
					//生成订单邀请邮件记录
					$order_payment_email_array = array(
												'order_id' => $row['order_id'],
												'order_sn' => $row['order_sn'],
												'order_amount' =>$row['order_amount'],
												'order_add_time' => $row['add_time'],
												'order_status' => 0,
												'user_id' => $row['user_id'],
												'email' => $row['email'],
												'send_time' => gmtime(),
												'coupon_id' => $coupon_id,
												'coupon_code' => $coupon_code
												);
					$db->autoExecute(ORDER_PAYMENT_EMAIL,$order_payment_email_array,'INSERT');
				}else{
					$history['state'] = 0;
					$result_msg .= $row['email'].' Sending failed, please check the mail server configuration<br>';
				}
				$db->autoExecute(Email_send_history,$history,'INSERT');  //记录发件历史		
			}
		}
	}
}else{
	$result_msg .= ' Does not meet the conditions of the e-mail 3 days';
}
echo $result_msg;
$dir  = ROOT_PATH . 'eload_admin/crontab/log/' . local_date('Y/m/d/');
!is_dir($dir) && mkdir($dir, 0775, true);
file_put_contents($dir . basename($_SERVER['PHP_SELF']) . local_date('-His') . '.log', '');    //执行日志

function randomkeys($length)
{
	$key = '';
	$pattern='1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
	for($i=0;$i<$length;$i++)
	{
	   $key .= $pattern{mt_rand(0,35)};    //生成php随机数
	}
	return $key;
}

function get_order_goods($order_id, $user_id) {
	global $db;
	$sql = "SELECT * FROM ". ODRGOODS ." WHERE order_id = ". $order_id ."";
	$goods_list = $db->arrQuery($sql);
	$goods_amount = 0;
	foreach ($goods_list as $key => $value)
	{
		$urlfile = $urlfile = get_details_link($value['goods_id'],'');
		$goods_list[$key]['market_price'] = price_format($value['market_price'], false);
		$goods_list[$key]['shop_price']  = price_format($value['goods_price'], false);
		$goods_list[$key]['subtotal']     = price_format($value['goods_price'] * $value['goods_number'], false);
		$goods_list[$key]['goods_title']  = $value['goods_name'];
		$goods_list[$key]['url_title']    = $urlfile;
	}
	$result['goods_list'] = $goods_list;
	$result['recommend_goods'] = get_mail_template_goods(20, $mail_conf);		
	return $result;
}


?>

<script type="text/javascript">

	function jump(count) {   
		window.setTimeout(function(){
			count--;   
			if(count > 0) {                          
				jump(count);   
			} else { 
				window.open('','_self','');
				window.close();
			}   
		}, 1000);
	};

jump(5);
</script>