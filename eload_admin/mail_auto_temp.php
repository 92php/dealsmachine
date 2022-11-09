<?php
/**
 * mail_auto_temp.php		常规邮件自动生成
 *
 * @author                      lchen
 * @last modify                 2013/12/3 by lchen
 */
set_time_limit(0);
define('INI_WEB', true);
require_once('../lib/global.php'); //引入全局文件
require_once(LIB_PATH . 'is_loging.php');
require_once(LIB_PATH . 'time.fun.php');
require(ROOT_PATH . 'lib/class.mail_auto_temp.php');

admin_priv('mail_auto_temp'); //检查权限

$_ACT   = isset($_GET['act']) ? $_GET['act'] : 'list';

new Mail_Auto_Temp($_ACT);

$_ACT   = 'mail_auto_temp_' . $_ACT;
temp_disp();