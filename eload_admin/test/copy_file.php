<?php
/**
 * copy_file.php     复制文件，只允许复制文件
 * 
 * @author           mashanling(msl-138@163.com)
 * @date             2011-10-26
 * @last modify      2011-12-14 by mashanling
 */
define('INI_WEB', true);
require_once('../../lib/global.php');
require_once('../../lib/is_loging.php');
require_once('../../lib/param.class.php');

date_default_timezone_set('asia/shanghai');

$source       = Param::get('source');    //需要复制的文件名
$dest         = Param::get('dest');      //需要复制到的目标文件名
$dir          = Param::get('dir');       //目标文件所在目录

(!$source || !$dest) && exit('需要复制的文件参数为空或需要复制到的目标文件参数为空');

$dir          = ROOT_PATH . $dir . '/';
$backup_dir   = $dir . 'backup/';    //备份目录路径
$backup_name  = $backup_dir . $dest . date('YmdHi');    //备份文件名

!is_dir($backup_dir) && var_export(mkdir($backup_dir));    //如果备份目录不存在，则创建

strpos($source, $dest) === false && exit('源文件与目标文件不相同');

$source       = $dir . $source;    //需要复制的文件名在backup下
$dest         = $dir . $dest;

!is_file($source) && exit("需要复制的文件 {$source} 不存在");

var_export(copy($dest, $backup_name));    //备份
var_export(copy($source, $dest));    //复制

highlight_file($dest);
?>