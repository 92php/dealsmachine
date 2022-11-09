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
require('../../lib/global.php');
require('../../lib/time.fun.php');
require('../../lib/class.function.php');
require('../../lib/lib.f.order.php');
require('../../lib/lib.f.transaction.php');
require('../../languages/en/user.php');
require('../email_temp/mail_conf.php');  //取得模版标题

$order_sn = isset($_POST['order_sn']) ? trim($_POST['order_sn']) : '';
$order_sn = isset($_GET['order_sn']) ? trim($_GET['order_sn']) : $order_sn;
//写日志
//file_put_contents('post_partial_order_email_1.txt',var_export($order_sn,true));
//$order_sn = 'a:1:{s:18:\\"DD1306190410562909\\";a:1:{s:9:\\"NA0051801\\";s:1:\\"1\\";}}';

empty($order_sn) && exit();
$order_sn = unserialize(stripslashes(strtolower($order_sn)));
empty($order_sn) && exit();

$script        = $_SERVER['SCRIPT_NAME'];
$time_start    = microtime(true);
$log_info      = '';
$num           = 0;
$Tpl->template_dir = ROOT_PATH . 'temp/' . SKIN;
$Tpl->compile_dir = ROOT_PATH . 'temp_c/' . SKIN;

//写日志
//file_put_contents('post_partial_order_email_2.txt',var_export($order_sn,true));

foreach ($order_sn as $order_sn => $goods_sn) {
    $order_info   = $db->selectinfo('SELECT o.order_id,u.email,u.lang FROM ' . ORDERINFO . ' AS o JOIN ' . USERS . " AS u ON o.user_id=u.user_id WHERE o.order_sn='{$order_sn}'");
	//echo 'SELECT o.order_id,u.email,u.lang FROM ' . ORDERINFO . ' AS o JOIN ' . USERS . " AS u ON o.user_id=u.user_id WHERE o.order_sn='{$order_sn}'";
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
            $email = $order_info['email'];
			//获得收货人信息
			$sql = "SELECT email, firstname, lang FROM " . USERS . " WHERE email = '" . $email . "'";
			$user_info =  $db->selectinfo($sql);
			$firstname = $user_info['firstname'];
			if(empty($firstname)) {
				$firstname = 'my friend';
			}
			$Tpl->assign('firstname',$firstname);
            $Tpl->assign('order', $order_detail);
            $Tpl->assign('goods_list', $goods_list);
			$lang = $order_info['lang'];
			if(empty($lang) || $lang == 'en') {
				$mail_subject  = $mail_conf['en'][29];
				$tpl           = ADMIN_PATH . 'email_temp/en/29.html';
			} elseif(!empty($lang)) {
				$mail_subject  = $mail_conf[$lang][29];
				$tpl           = ADMIN_PATH . 'email_temp/'. $lang .'/29.html';
			}
            $content           = $Tpl->fetch($tpl);
            $temp  = strtoupper($order_sn) . "({$email})";
			if($order_info['email'] && $mail_subject && $content) {
				if (exec_send($order_info['email'], $mail_subject, $content)) {
					$num++;
					$log_info .= $temp . '成功' . var_export($goods_sn, true) . PHP_EOL;
				}
				else {
					$log_info .= $temp . '失败' . PHP_EOL;
				}
			}
        }
    }
    else {
        $log_info .= $order_sn . ' empty!' . PHP_EOL;
    }
}

Logger::filename(LOG_FILENAME_PATH);
trigger_error($script, $num . '=>' . $log_info);