<?php
/**
 * view_file.php     查看文件
 * 
 * @author           mashanling(msl-138@163.com)
 * @date             2011-10-26
 * @last modify      2011-11-30 by mashanling
 */
define('INI_WEB', true);
require_once('../../lib/global.php');
require_once('../../lib/is_loging.php');
require_once('../../lib/param.class.php');

$filename = Param::get('file');
$filename = ROOT_PATH . $filename;

!is_file($filename) && exit("需要查看的文件 {$filename} 不存在");

highlight_file($filename);
?>