<?php
/**
 * 定时发送到货通知邮件
 * */
set_time_limit(0);

define('INI_WEB', true);
require('../../lib/global.php');              //引入全局文件
require('../../lib/time.fun.php');

$sql = "SELECT ns.id , ns.user_id , ns.goods_id , ns.add_time , g.goods_sn , g.goods_title , g.url_title , g.goods_thumb , u.email , u.firstname FROM " . NOTIFY_IN_STOCK . " AS ns " . 
		" LEFT JOIN " . GOODS . " AS g ON ns.goods_id = g.goods_id " . 
		" LEFT JOIN " . USERS . " AS u ON ns.user_id = u.user_id " . 
		" WHERE ns.notify_status = 0 AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.goods_number > 0 ";
$data = $db->arrQuery($sql);
foreach ($data as $key => $value)
{
	$Tpl->template_dir = ROOT_PATH . 'temp/' . SKIN;
	$Tpl->compile_dir = ROOT_PATH . 'temp_c/' . SKIN;
	
	$Tpl->assign( 'firstname', empty($value['firstname']) ? 'my friend' : $value['firstname'] );
	$Tpl->assign( 'add_time', local_date($GLOBALS['_CFG']['AM_time_format'], $value['add_time']) );
	$Tpl->assign( 'goods_name', $value['goods_title'] );
	$Tpl->assign( 'url_title', get_details_link($value['goods_id'],$value['url_title'],'',1) );
	$Tpl->assign( 'goods_thumb', get_image_path($value['goods_id'], $value['goods_thumb'], true) );
	$Tpl->assign( 'email', $value['email'] );

	require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');  //取得模版标题
	$email        = $value['email'];
	$mail_subject = $mail_conf[30];
	$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/30.html');
	exec_send($email,$mail_subject,$mail_body);
	
	$sql = "UPDATE " . NOTIFY_IN_STOCK . " SET notify_status = 1 WHERE id = " . $value['id'];
	$db->query($sql);
}
?>