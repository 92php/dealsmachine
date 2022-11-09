<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/class.function.php');
$data = array();
write_static_cache('share_click',$data,1);
exit;

?>