<?php
/**
 * 重置管理员密码，限定20131121执行，执行完成后可删除
 *
 * @file            reset_password.php
 * @author          mashanling <msl-138@163.com>
 * @date            2013-11-21 13:48:32
 * @lastmodify      $Author: fangxin $ $Date: 2014-03-06 13:51:00 +0800 (周四, 2014-03-06) $
 */
define('INI_WEB', true);
require_once('../lib/global.php');
require_once('../lib/is_loging.php');
require_once('libs/fun.admin.php');
require_once('../lib/time.fun.php');

$date = '20140305';
$rand = array_merge(range('a', 'z'), range('A', 'Z'), range(0, 9), array('!', '@', '#', '$'));
$rand = join('', $rand);

if ($date != local_date('Ymd')) {
    exit('Access Denied');
}
elseif (read_static_cache('old_password_arr.' . $date, 2)) {
    exit('密码已经重置！');
}

$admin_arr          = read_static_cache('land', 2);
$reset_password_arr = array();
$new_password       = '';

foreach($admin_arr as $admin_id => $item) {
    $reset_password_arr[$admin_id] = $item['sa_pswd'];
    $shuffle            = str_shuffle($rand);
    $password           = substr($shuffle, 0, rand(8, 12));
    $new_password      .= $item['real_name'] . '：' . $password . PHP_EOL;
    $admin_arr[$admin_id]['sa_pswd'] = md5($password . $_CFG['keys_code']);

    $db->update(SADMIN, "sa_pswd='" . $admin_arr[$admin_id]['sa_pswd'] . "'", 'said=' . $admin_id);
}

write_static_cache('old_password_arr.' . $date, $reset_password_arr, 2);
copy(ROOT_PATH . 'eload_admin/cache_files/land.php', ROOT_PATH . 'eload_admin/cache_files/land.' . $date . '.php');
write_static_cache('land', $admin_arr, 2);
file_put_contents('cache_files/new_password.txt', $new_password);

echo '修改成功，请下载/eload_admin/cache_files/new_password.txt后并删除，以及删除/eload_admin/reset_password.php';