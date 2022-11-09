<?php
/**
 * 每月25日发邮件通知评论好评最多的前十个评论用户，让他邀请他的好友来好评他的评论
 * */
set_time_limit(0);

define('INI_WEB', true);
require('../../lib/global.php');              //引入全局文件
require('../../lib/time.fun.php');
require_once('../../lib/lib_goods.php');

$current_time = local_date('d/m/Y H:i:s A');								//获取当前时间
list($date,$time)= explode(" ",$current_time);
list($day,$month,$year) = explode("/",$date);
if($day > 16)
{
	$start_time = local_mktime(0,0,0,$month,16,$year);
	$end_day = date('t',$start_time);
	$end_time = local_mktime(23,59,59,$month,$end_day,$year);
}
else 
{
	$start_time = local_mktime(0,0,0,$month,1,$year);
	$end_time = local_mktime(23,59,59,$month,15,$year);
}
/*$current_time = local_date('d/m/Y H:i:s A');								//获取当前时间
list($date,$time)= explode(" ",$current_time);
list($day,$month,$year) = explode("/",$date);
$start_time = local_mktime(12,0,0,$month,1,$year);;
$end_day = date('t',$start_time);
$end_time = local_mktime(0,0,0,$month,$end_day,$year);*/
$sql = "SELECT r.* , g.goods_title , g.url_title , g.goods_grid , u.user_id , u.email , u.firstname FROM " . REVIEW . " AS r " . 
		" LEFT JOIN " . GOODS . " AS g  ON r.goods_id = g.goods_id " .
		" LEFT JOIN " . USERS . " AS u ON r.user_id = u.user_id " . 
		" WHERE r.is_pass = 1 AND r.addtime_real >= " . $start_time . " AND r.addtime_real <= " . $end_time . 
		" AND r.helpful_yes > 0 AND r.goods_id = g.goods_id ORDER BY r.helpful_yes DESC LIMIT 10";
$review_list = $db->arrQuery($sql);
foreach ($review_list as $k=>$v){
	$Arr['firstname'] = empty($v['firstname']) ? 'My friend' : $v['firstname'];
	$Arr['goods']['url_title'] = get_details_link($v['goods_id'],$v['url_title']);
	$Arr['goods']['goods_grid'] = get_image_path($v['goods_id'], $v['goods_grid'], true);
	$Arr['goods']['goods_name'] = $v['goods_title'];
	$Arr['review'] = get_reviews($v['goods_id'],$v['rid']);
	
	foreach( $Arr as $key => $value ){
		$Tpl->assign( $key, $value );
	}
	
	//发送邮件
	require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');  //取得模版标题		
	//获得用户信息 fangxin 2013/07/18
	$email = $v['email'];
	$sql = "SELECT email, firstname, lang FROM " . USERS . " WHERE email = '" . $email . "'";
	$user_info =  $db->selectinfo($sql);
	$lang      = $user_info['lang'];
	if(!empty($lang)) {
		$mail_subject = $mail_conf[$lang][46];
		$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $lang .'/46.html');
	} 
	if(empty($mail_subject)) {
		$mail_subject = $mail_conf['en'][46];
		$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/en/46.html');			
	}	
	if($email && $mail_subject && $mail_body) {		
		exec_send($email,$mail_subject,$mail_body);
	}
}

?>