<?php
/**
 * auto_login.php           模拟用户自动登陆
 *
 * @author                  mashanling(msl-138@163.com)
 * @date                    2011-12-21 14:48:41
 * @last modify             2011-12-21 14:48:41 by mashanling
 */
define('INI_WEB', true);
require_once('../../lib/global.php');
require_once('../../lib/is_loging.php');
require_once('../../lib/time.fun.php');
require_once('../../lib/param.class.php');

$email = Param::get('email');
$sql   = 'SELECT * FROM ' . USERS. " WHERE email='{$email}'";
$row   = $db->selectInfo($sql);

if (empty($row)) {
    echo '用户不存在';
}
else {
	$_SESSION['user_id']            = $row['user_id'];
    $_SESSION['firstname']          = $row['firstname'];
    $_SESSION['lastname']           = $row['lastname'];
	$_SESSION['email']              = $row['email'];
	//$_SESSION['user_type_id']       = $row['type_id'];
	//$_SESSION['grade_id']           = $row['grade_id'];
	$_SESSION['discount']           = 1;
    //$_SESSION['is_dingyue_success'] = $row['is_dingyue_success'];

	$user_rank = read_static_cache('users_grade', ADMIN_STATIC_CACHE_PATH);
	$_SESSION['user_rank'] = $user_rank[$row['user_rank']]['grade_en_name'];

	setcookie('usertype', $row['user_type'], gmtime() + 86400 * 30, '/', COOKIESDIAMON); //判断是否为广告联盟用户

	header('Location: /m-users.html');
}
?>