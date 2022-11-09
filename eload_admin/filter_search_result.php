<?php
ob_start();
/**
 * filter_search_result.php     优化关键字，and关系查询有查询结果
 * 
 * @author                      mashanling(msl-138@163.com)
 * @date                        2012-11-23 10:04:18
 * @last modify                 2012-11-23 10:04:18 by mashanling
 */

define('INI_WEB', true);
require_once('../lib/global.php');
require_once(LIB_PATH . 'is_loging.php');
require_once(LIB_PATH . 'time.fun.php');
require_once(ROOT_PATH . 'eload_admin/libs/class.filterkeyword.php');
require_once(ROOT_PATH . 'eload_admin/libs/class.filterkeywordresult.php');

$class     = new FilterKeywordResult();
$filename  = basename(__FILE__ , '.php');

admin_priv($filename);    //检查权限

$_ACT          = isset($_GET['act']) ? $_GET['act'] : 'get_data';    //操作

switch ($_ACT) {
    case 'query':    //查询
        $class->query();
        
    default:
        $_ACT = $filename;
        $Arr['data'] = $class->getData();
        temp_disp();
        break;
}