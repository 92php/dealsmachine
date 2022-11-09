<?php
define('INI_WEB', true);
require('../lib/global.php');              //引入全局文件
require('../lib/time.fun.php');
require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');  //取得模版标题
include(ROOT_PATH . 'languages/en/common.php');
include(ROOT_PATH . 'languages/en/user.php');
$Arr['lang']   =  $_LANG;
$Tpl->caching = false;        //使用缓存


$result_msg = '';

echo  '<pre>';
$sql = "SELECT u.firstname,u.lastname,u.email,u.user_id,o.order_id,FROM_UNIXTIME(o.add_time,'%Y-%m-%d') as date,o.order_sn,DATEDIFF(curdate(),FROM_UNIXTIME(o.add_time,'%Y-%m-%d')) as days FROM eload_users AS u, eload_order_info AS o WHERE u.user_id = o.user_id and u.email = 'qngb3@163.com' order by o.order_id desc limit 3";
$user_list = $db->arrQuery($sql);
//o.email = 'oootc@126.com'DATEDIFF(curdate(),FROM_UNIXTIME(o.add_time,'%Y-%m-%d')) = 7 and o.order_status = 0 and u.is_unsub = 0 AND 
include_once(ROOT_PATH . 'lib/lib.f.transaction.php');



if (!empty($user_list )){
	foreach($user_list as $row){
		$sql = "SELECT count(*) FROM " . Email_send_history . " where email = '".$row['email']."' and state = 1 ";
		$alrady_email = $db->getOne($sql);
		if ($alrady_email == 0){
		
			$order_id = $row['order_id'];
			$user_id = $row['user_id'];
			$order = get_order_detail($order_id, $user_id);
			$area_Arr = read_static_cache('area_key',2);
			$order['country'] = $area_Arr[$order['country']]['region_name'];
			if ($order !== false)
			{	
			/* 订单商品 */
					$goods_list = order_goods($order_id);
					foreach ($goods_list AS $key => $value)
					{
						$urlfile = get_details_link($value['goods_title'],$value['cat_id'],$value['goods_id']);
						$goods_list[$key]['market_price'] = price_format($value['market_price'], false);
						$goods_list[$key]['goods_price']  = price_format($value['goods_price'], false);
						$goods_list[$key]['subtotal']     = price_format($value['subtotal'], false);
						$goods_list[$key]['url_title'] = $urlfile;
					}
					if ($order['order_amount'] > 0)
					{
						if ($order['order_status'] == 0)
						{
							$user = user_info($order['user_id']);
						}
					}
					if ($order['order_amount'] > 0 && $order['order_status'] == 0)
					{
						$payment_list = available_payment_list(false, 0, true);
						$payment_list = $payment_list[$order['pay_id']];
						$Arr['payment_list'] = $payment_list;
					}
				
					$Arr['order'] =  $order;
					$Arr['goods_list'] = $goods_list;
					$Arr['order_id'] = $row['order_id'];
					$Arr['order_no'] = $row['order_sn'];
					$Arr['firstname'] = $row['firstname'];
					$Arr['email'] = md5($row['email']);
					
					foreach( $Arr as $key => $value ){
						$Tpl->assign( $key, $value );
					}
					
					echo $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/20.html');
					//exit;
					
					
					$email        = $row['email'];
					$mail_subject = $mail_conf[20];
					$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/20.html');
					
					$history = array();
					$history['firstname']     = $row['firstname'];
					$history['lastname']      = $row['lastname'];       
					$history['email']         = $row['email'];             
					$history['order_num']     = 1; 
					$history['turnover']      = $order['formated_order_amount'];
					$history['template_id']   = 20;             
					$history['email_type_id'] = 3; 
					$history['pri']           = 0;
					$history['last_send'] = time();
/*						
					if(exec_send($email,$mail_subject,$mail_body)){
						exec_send('65886309@qq.com',$mail_subject,$mail_body);
						$history['state'] = 1;
						$result_msg .= $row['email'].' Sent successfully<br>';
					}else{
						$history['state'] = 0;
						$result_msg .= $row['email'].' Sending failed, please check the mail server configuration<br>';
					}
					$db->autoExecute(Email_send_history,$history);  //记录发件历史
*/					 
			}
		}else{
			$result_msg .= $row['email'].' Sending fails, it repeat<br>';
		}
	}
}else{
	$result_msg .= ' Does not meet the conditions of the e-mail 7 days';
}


//exec_send('zhongqiren@msn.com','Result of no pay email',$result_msg);
//exec_send('65886309@qq.com','Result of no pay email ',$result_msg);

echo $result_msg;
?>

<script type="text/javascript">

	function jump(count) {   
		window.setTimeout(function(){   
			count--;   
			if(count > 0) {                          
				jump(count);   
			} else { 
			//	window.open('','_self','');
				//window.close();
			}   
		}, 1000);
	};

jump(5);
</script>