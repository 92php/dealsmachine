<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require(ROOT_PATH . 'eload_admin/email_temp/mail_conf.php');  //取得模版标题

$Tpl->caching = false;        //使用缓存
$host = "http://".$_SERVER['HTTP_HOST'];
$time = time()-3600*24*7;
$sql  = "SELECT user_id, firstname, lastname, email, lang, is_coupon FROM ". USERS ." WHERE reg_time > ". $time ." AND is_coupon = 0";
//echo $sql; exit;
if($res = $db->arrQuery($sql)) {
	$num = 0;
	foreach($res as $key=>$value) {
		$user_id = $value['user_id'];
		$lang    = $value['lang'];
		$email   = $value['email'];
		$sql = "SELECT count(1) as count FROM ". ORDERINFO ." WHERE user_id = ". $user_id ."";
		$res = $db->arrQuery($sql);
		if($res[0][count] == 0) {
			switch($lang) {
				case 'fr':
					$first_name = 'mon ami';
					break;
				case 'ru':
					$first_name = 'мой друг';
					break;
				case 'es':
					$first_name = 'mi amigo';
					break;
				case 'de':
					$first_name = 'mein freund';
					break;
				case 'pt':
					$first_name = 'meu amigo';
					break;						
				default:
					$first_name = 'my friend';
					break;							
			}	
			//$email = 'snipersheep@163.com';
			$coupon_code = randstr(8);			
			//生成促销码
			$pcodeArr['code']        = $coupon_code;
			$pcodeArr['users']       = '';
			$pcodeArr['exp_time']    = time()+3600*24*30;
			$pcodeArr['youhuilv']    = '30-3';  //优惠率，30直减3
			$pcodeArr['goods']       = ''; //针对使用产品，空为不限制
			$pcodeArr['fangshi']     = 2;  //1百分比，2直减
			$pcodeArr['times']       = 0;  //使用次数
			$pcodeArr['is_applay']   = 0;  //0促销码，1代金券
			$pcodeArr['cat_id']      = 0;  //针对使用分类，空为不限制
			$pcodeArr['create_time'] = gmtime();  //开始日期   
			$end_date = date("Y-m-d H:i:s", $pcodeArr['exp_time']);
			if ($db->autoExecute(PCODE, $pcodeArr) !== false){
				$sql = "UPDATE ". USERS ." SET is_coupon = 1  WHERE user_id = '".$user_id ."' ";
				$db->query($sql);
				admin_log($sn = '', _ADDSTRING_, '促销码 '.$pcodeArr['code']);
			}
			$Tpl->assign('firstname', $first_name);
			$Tpl->assign('coupon_code', $coupon_code);
			$Tpl->assign('end_date', $end_date);			
			if(!empty($lang)) {
				$mail_subject = $mail_conf[$lang][51];
				$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $lang .'/51.html');
			} 
			if(empty($mail_subject) || empty($mail_body)) {
				$mail_subject = $mail_conf['en'][51];
				$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/en/51.html');		
			}					
			if($email && $mail_subject && $mail_body){
				if (exec_send($email, $mail_subject, $mail_body)) {
					$num += 1;
					$log_info .= '邮件 ' . $email . ' 发送成功<br>';
				}
				else {
					$log_info .= '邮件 ' . $email . ' 发送失败<br>';
				}				
			}
			unset($email);
			unset($mail_subject);
			unset($email_body);
		}
	}
	echo '共发送邮件：' . $num . '<br>' . $log_info;
}

function randstr($length) {
   $hash = '';
   $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
   $max = strlen($chars) - 1;
   mt_srand((double)microtime() * 1000000);
   for($i = 0; $i < $length; $i++) {
		$hash .= $chars[mt_rand(0, $max)];
   }
   return $hash;
}
?>

