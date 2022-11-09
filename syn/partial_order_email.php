<?php
/**
 * partial_order_email.php  部分发货订单邮件
 *
 * @author                  mashanling(msl-138@163.com)
 * @date                    2012-01-06 13:33:02
 * @last modify             2013-01-04 09:13:22 by mashanling
 */

set_time_limit(0);
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/lib.f.order.php');
require_once('../lib/class.function.php');
require_once('../lib/lib.f.transaction.php');
include(ROOT_PATH.'languages/en/user.php');
include(ROOT_PATH.'languages/en/shopping_flow.php');
require(ROOT_PATH . 'eload_admin/email_temp/mail_conf.php');  //取得模版标题

$order_sn = isset($_POST['order_sn']) ? trim($_POST['order_sn']) : '';
$order_sn = isset($_GET['order_sn']) ? trim($_GET['order_sn']) : $order_sn;

empty($order_sn) && exit();

$order_sn = unserialize(stripslashes(strtolower($order_sn)));

empty($order_sn) && exit();

$script        = $_SERVER['SCRIPT_NAME'];
$time_start    = microtime(true);
$log_info      = '';
$num           = 0;
$mail_subject  = $mail_conf[30];
if(!empty($lang)) {
	$tpl       = ROOT_PATH . 'email_temp/'. $lang .'/30.html';
} else {
	$tpl       = ROOT_PATH.'eload_admin/email_temp/en/30.html';
}
$Tpl->template_dir = ROOT_PATH . 'temp/' . SKIN;
$Tpl->compile_dir = ROOT_PATH . 'temp_c/' . SKIN;

foreach ($order_sn as $order_sn => $goods_sn) {
    $order_info   = $db->selectinfo('SELECT o.order_id,u.email FROM ' . ORDERINFO . ' AS o JOIN ' . USERS . " AS u ON o.user_id=u.user_id WHERE o.order_sn='{$order_sn}'");
    $order_id     = $order_info['order_id'];
    $order_detail = get_order_detail($order_id, 0);
    $goods_list   = order_goods($order_id);

    if (!empty($order_info) && !empty($order_detail) && !empty($goods_list)) {

        foreach ($goods_list as $key => $value) {
            $sn = strtolower($value['goods_sn']);

            if (!array_key_exists($sn, $goods_sn)) {
                unset($goods_list[$key]);
                continue;
            }

    		$urlfile = get_details_link($value['goods_id']);
    		$goods_list[$key]['goods_number'] = $goods_sn[$sn];
            $goods_list[$key]['goods_price']  = price_format($value['goods_price'], false);
            $goods_list[$key]['subtotal']     = price_format($value['goods_price'] * $goods_sn[$sn], false);
        }

        if (!empty($goods_list)) {
            $Tpl->assign('order', $order_detail);
            $Tpl->assign('goods_list', $goods_list);
            $content           = $Tpl->fetch($tpl);
            $email = $order_info['email'];
            $temp  = strtoupper($order_sn) . "({$email})";
			if($order_info['email'] && $mail_subject && $content){
				if (exec_send($order_info['email'], $mail_subject, $content)) {
					$num++;
					$log_info .= $temp . '成功' . var_export($goods_sn, true) . PHP_EOL;
				}
				else {
					$log_info .= $temp . '失败' . PHP_EOL;
				}
			} else {
				exec_send('snipersheep@163.com','affiliate申请通知','客户邮件为空');
			}
        }
    }
    else {
        $log_info .= $order_sn . ' empty!' . PHP_EOL;
    }
}

Logger::filename(LOG_FILENAME_PATH);
trigger_error($num . '=>' . $log_info);