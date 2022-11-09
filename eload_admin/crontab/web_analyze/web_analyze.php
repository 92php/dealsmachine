<?php
/**
 * web_anaylze.php           网站客户分析（定时执行）
 * 
 * @author                   mashanling(msl-138@163.com)
 * @date                     2011-08-26
 * @last modify              2011-09-07 by mashanling
 */

define('INI_WEB', true);
require_once('../../../lib/global.php');              //引入全局文件
require_once('../../../lib/time.fun.php');
require_once('../../../lib/param.class.php');
date_default_timezone_set('asia/shanghai');
define('FILE_ANALYZE_RECORD', 'analyze_record');    //记录信息文件
define('FILE_ANALYZE_RECORD_NAME', ROOT_PATH . '/eload_admin/cache_files/analyze_record.txt');    //记录信息文件
require('class.analyze.php');
require('class.analyze_customers.php');
require('class.analyze_customers_action.php');
require('class.analyze_country.php');
require('class.analyze_order.php');
require('class.analyze_sale.php');
require_once(ROOT_PATH.'lib/time.fun.php');
set_time_limit(0);
ob_start();


//$c = new AnalyzeCountrySale;
//Analyze::insertDatetime($c);
  //exit();
//Analyze::setAnalyze($c);
//$c->setAnalyze(strtotime('2012-1-10'));
//exit();

//$db         = new MySql('localhost', 'root', '', 'db_ahappydeal');
//$class      = Param::get('class');
//$class_arr  = $class ? array($class) : array('AnalyzeCountrySale');    //分析类
//$class_arr  = array('AnalyzeSales');
set_time_limit(0);
ob_start();
$class      = Param::get('class');
$class_arr  = $class ? array($class) : array('AnalyzeCountrySale','AnalyzeCustomers', 'AnalyzeCustomersAction', 'AnalyzeOrders', 'AnalyzeSales');    //分析类


$time_start = microtime(true);
foreach ($class_arr as $class) {
    $class = new $class();
    Analyze::insertDatetime($class);
    Analyze::setAnalyze($class);
}

echo '统计结束，用时' . execute_time($time_start);

$content = ob_get_contents();
file_put_contents(ROOT_PATH . '/eload_admin/crontab/log/web_analyze/' . date('YmdHi') . '.log', $content);
?>