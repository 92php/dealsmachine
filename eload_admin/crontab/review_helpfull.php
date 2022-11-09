<?php
/**
 * 计算每月评论好评最多的评论，并且赠送积分（当月添加评论）
 * */
set_time_limit(0);

define('INI_WEB', true);
require('../../lib/global.php');              //引入全局文件
require('../../lib/time.fun.php');
require('../../lib/lib.f.goods.php');

$current_time = local_date('d/m/Y H:i:s A');								//获取当前时间
list($date,$time)= explode(" ",$current_time);
list($day,$month,$year) = explode("/",$date);
if($day >= 16)
{
	$start_time = local_mktime(0,0,0,$month,1,$year);
	$end_time = local_mktime(23,59,59,$month,15,$year);
}
else 
{
	$last_month_time = gmstr2time('-1 month');
	list($year,$month) = explode("-",date('Y-m',$last_month_time));
	$start_time = local_mktime(0,0,0,$month,16,$year);
	$end_day = date('t',$start_time);
	$end_time = local_mktime(23,59,59,$month,$end_day,$year);
}

/*$last_month_time = gmstr2time('-1 month');
list($year,$month) = explode("-",date('Y-m',$last_month_time));
$start_time = local_mktime(12,0,0,$month,1,$year);
$end_day = date('t',$start_time);
$end_time = local_mktime(0,0,0,$month,$end_day,$year);*/

$sql = "SELECT r.* , g.goods_title , g.url_title , g.goods_grid , u.user_id , u.email , u.firstname FROM " . REVIEW . " AS r " . 
			" LEFT JOIN " . GOODS . " AS g ON r.goods_id = g.goods_id " .
			" LEFT JOIN " . USERS . " AS u ON r.user_id = u.user_id " . 
			" WHERE r.is_pass = 1 AND r.addtime_real >= " . $start_time . " AND r.addtime_real <= " . $end_time . " AND r.user_id > 0 " . 
			" AND r.helpful_yes > 0 AND r.goods_id = g.goods_id ORDER BY r.helpful_yes DESC LIMIT 1";
$query = $db->query($sql);
$review_helpfull = $db->fetchRow($query);

if(!empty($review_helpfull) && $review_helpfull['user_id'])
{
	//添加活动获奖记录
	$sql = "INSERT INTO " .REVIEW_HELPFUL_WINTER."(user_id,rid,win_time)VALUE(".$review_helpfull['user_id'].",".$review_helpfull['rid'].",'".$year.'-'.$month.'-'.$day."')";
	$db->query($sql);
	
	//赠送积分
	$note = "Won customer review competition and get 500 points";
	add_point($review_helpfull['user_id'],500,2,$note);
	
	$Arr['firstname'] = empty($review_helpfull['firstname']) ? 'My friend' : $review_helpfull['firstname'];
	$Arr['goods']['url_title'] = get_details_link($review_helpfull['goods_id'],$review_helpfull['url_title']);
	$Arr['goods']['goods_grid'] = get_image_path($review_helpfull['goods_id'], $review_helpfull['goods_grid'], true);
	$Arr['goods']['goods_name'] = $review_helpfull['goods_title'];
	$Arr['review'] = get_review($review_helpfull['goods_id'],1,3);
	
	foreach( $Arr as $key => $value ){
		$Tpl->assign( $key, $value );
	}
	
	//发送邮件
	require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');  //取得模版标题
	//获得用户信息 fangxin 2013/07/18
	$email = $review_helpfull['email'];
	$sql = "SELECT email, firstname, lang FROM " . USERS . " WHERE email = '" . $email . "'";
	$user_info =  $db->selectinfo($sql);
	$lang      = $user_info['lang'];
	if(!empty($lang)) {
		$mail_subject = $mail_conf[$lang][47];
		$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $lang .'/47.html');
	} 
	if(empty($mail_subject)) {
		$mail_subject = $mail_conf['en'][47];
		$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/en/47.html');			
	}	
	if($email && $mail_subject && $mail_body) {		
		exec_send($email,$mail_subject,$mail_body);
	}
}
?>