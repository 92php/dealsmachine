<?php
/*
* 2013/12/4
* fun.mail_auto_temp.php 自助邮件模板预览
*/
require_once(ROOT_PATH . 'fun/fun.global.php');
require(ROOT_PATH . 'lib/class.mail_auto_temp.php');
$_ACT   = isset($_GET['act']) ? $_GET['act'] : 'see';
new Mail_Auto_Temp($_ACT);

$_ACT   = 'mail_auto_temp_' . $_ACT;
temp_disp();
exit;