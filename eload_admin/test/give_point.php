<?php
/**
 * give_point.php           送积分
 *
 * @author                  mashanling <msl-138@163.com>
 * @date                    2012-01-20 11:40:29
 * @lastmodify              2013-10-10 14:51:16 by mashanling
 */
define('INI_WEB', true);
require_once('../../lib/global.php');
require_once(ROOT_PATH . 'lib/is_loging.php');
require_once(ROOT_PATH . 'lib/time.fun.php');

$data  = isset($_GET['data']) ? trim($_GET['data']) : null;
$memo  = isset($_GET['memo']) ? trim($_GET['memo']) : null;//送积分备注
$memo  = $memo ? $memo : 'for good reviews';
$point = isset($_GET['point']) ? intval($_GET['point']) : null;;//送积分数
$point = $point ? $point : 10;

if (!$data && is_file($file = './give_point.data')) {//支持上传文件
    $data = file_get_contents($file);
    unlink($file);
}

!$data && exit('请输入数据！');

$output = '';

foreach (explode(',', $data) as $info) {
    $item  = explode('|', $info);
    $email = $item[0];
    $point = empty($item[1]) ? $point : intval($item[1]);

    $user_id = $db->getOne('SELECT user_id FROM ' . USERS . " WHERE email='{$email}'");

    if (!$user_id) {
        $output .= $email . ' 不存在<br />';
    }
    elseif ($point > 0) {
        add_point($user_id, $point, 2, $memo);
        $output .= "<p><a href=\"/eload_admin/users.php?act=edit&amp;id={$user_id}\" target=\"_blank\">{$email}</a>, <a href=\"/eload_admin/users.php?act=ebpoint&amp;id={$user_id}\" target=\"_blank\">积分信息</a></p>";
    }
}

echo $output;