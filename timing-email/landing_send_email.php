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
$sql = "select email,firstname,lastname,lang,DATEDIFF(curdate(),FROM_UNIXTIME(last_login,'%Y-%m-%d')) as days,FROM_UNIXTIME(last_login,'%Y-%m-%d') as riqi from ".USERS." WHERE (DATEDIFF(curdate(),FROM_UNIXTIME(last_login,'%Y-%m-%d')) mod 30 = 0) and  (DATEDIFF(curdate(),FROM_UNIXTIME(last_login,'%Y-%m-%d'))>29 ) order by user_id";
//and ((DATEDIFF(curdate(),FROM_UNIXTIME(last_login,'%Y-%m-%d')) div 30) mod 2 <> 0 )
$user_list = $db->arrQuery($sql);
$result_msg = '<b>dealsmachine.com No member of the landing</b><br>';
if (!empty($user_list )){
	
	foreach($user_list as $row){
		
		$Arr['email']     = md5($row['email']);
		$Arr['firstname'] = empty($row['firstname'])?'My friend':$row['firstname'];
		$Arr['lastname']  = $row['lastname'];
		$Arr['date']      = local_date('d/m/Y');
		foreach( $Arr as $key => $value ){
			$Tpl->assign( $key, $value );
		}		
		$email        = $row['email'];		
		$lang         = $row['lang'];
		if(empty($lang) || $lang == 'en') {
			$mail_subject = $mail_conf['en'][21];			
			$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/en/21.html');				
		} else {
			$mail_subject = $mail_conf[$lang][21];			
			$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $lang .'/21.html');								
		}	
		if($email && $mail_subject && $mail_body) {								
			//exec_send($email,$mail_subject,$mail_body);  //取消coupon和代金券的促进下单邮件发送 2014/02/14 fangxin
		}
		$result_msg .= '<b>'.$row['email'].'</b> Sent successfully.<br>';
	}
}else{
	$result_msg .= ' Does not meet the conditions of the one,three,five,';
}
//exec_send('zhongqiren@msn.com','Result of no landing On dealsmachine',$result_msg);
//exec_send('snipersheep@163.com','Result of no landing On dealsmachine',$result_msg);
echo $result_msg;
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