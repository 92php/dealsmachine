<?php
/**
 * collection_email.php         加入收藏,一周,超过4个,发邮件
 *
 * @author                      mashanling(msl-138@163.com)
 * @date                        2013-06-15 09:05:26
 * @lastmodify                  $Author: msl $ $Date: 2013-06-18 16:39:34 +0800 (周二, 2013-06-18) $
 */
define('INI_WEB', true);
set_time_limit(0);
require('../../lib/global.php');
require(ROOT_PATH . 'lib/time.fun.php');
require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');  //取得模版标题
$time_start = microtime(true);
$now        = gmtime();
$basename   = basename(__FILE__, '.php') . '.log';
$last_id    = read_static_cache($basename, 2);

if (false === $last_id) {
    $now        = gmtime();
    $start      = $now - 86400 * 7;
    $last_id    = $db->getOne('SELECT rec_id FROM ' . COLLECT . " WHERE add_time>{$start} ORDER BY rec_id ASC LIMIT 1");
}

if (!$last_id) {
    function_exists('e_log') && e_log(var_export($last_id, true), '', $time_start);
    exit;
}

$email_date = local_date('md', local_strtotime('this monday'));
$sql        = 'SELECT GROUP_CONCAT(g.goods_sn) AS goods_sn,COUNT(c.goods_id) AS num,c.user_id,u.email,u.firstname,u.lang FROM ' . COLLECT . ' AS c JOIN ' . USERS . ' AS u ON u.user_id=c.user_id JOIN ' . GOODS . " AS g ON c.goods_id=g.goods_id WHERE g.is_on_sale=1 AND g.is_delete=0 AND g.goods_number>0 AND c.rec_id>{$last_id} GROUP BY c.user_id HAVING(num>3)";
$arr        = $db->arrQuery($sql);

foreach($arr as $row) {
    if ($row['num'] > 4) {
        $row['goods_sn'] = join(',', array_slice(explode(',', $row['goods_sn']), 0, 4));
    }

    $Tpl->assign(array(
        'email_date'    => 'A' . $email_date,
        'email'         => md5($row['email']),
        'firstname'     => $row['firstname'],
        'wish_goods'    => get_mail_template_goods_data($row['goods_sn']),
    ));
	$lang = $row['lang'];
	if(!empty($lang)) {
		$mail_subject = $mail_conf[$lang][48];
		$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $lang .'/48.html');
	} 
	if(empty($mail_subject)) {
		$mail_subject = $mail_conf['en'][48];
		$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/en/48.html');			
	}
	if($row['email'] && $mail_subject && $mail_body) {	
	    exec_send($row['email'], $mail_subject, $mail_body);
	} else {
		$err .= $row['email'] . ' ';
	}
}

if(!empty($err)) {
	exec_send('snipersheep@163.com', 'bestafford收藏有礼邮件', 'Email is empty:' . $err);
}

write_static_cache($basename, $db->getOne('SELECT rec_id FROM ' . COLLECT . ' ORDER BY rec_id DESC LIMIT 1'), 2);

function_exists('e_log') && e_log($arr ? var_export($arr, true) : 'no data' . $last_id, '', $time_start);