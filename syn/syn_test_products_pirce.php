<?php
set_time_limit(0);
define('INI_WEB', true);
$_BEGINTIME = microtime(TRUE);
require('../lib/global.php');              //引入全局文件
require('../lib/time.fun.php');  
require(ROOT_PATH . 'lib/lib.f.order.php');


$goods_sn = !empty($_GET['goods_sn'])?trim($_GET['goods_sn']):'';

$sql = "select * from eload_goods where goods_sn = '".$goods_sn."' ";
$gArr = $db->arrQuery($sql);
foreach($gArr as $x =>$y){
	$y['keys_code'] = $_CFG['keys_code'];
	$y['sa_user']  = 'wuwenlong';
	echo post_data($y);
}


function post_data($fields){
	$url = 'http://www.testahappydeal.com/syn/syn_price.php' ; 
	//$post_data = implode('&',$fields);   
	//open connection   
	$ch = curl_init() ;   
	//set the url, number of POST vars, POST data   
	curl_setopt($ch, CURLOPT_URL,$url) ;   
	curl_setopt($ch, CURLOPT_POST,count($fields)) ; // 启用时会发送一个常规的POST请求，类型为：application/x-www-form-urlencoded，就像表单提交的一样。   
	curl_setopt($ch, CURLOPT_POSTFIELDS,$fields); // 在HTTP中的"POST"操作。如果要传送一个文件，需要一个@开头的文件名   
	ob_start();   
	curl_exec($ch);   
	$result = ob_get_contents() ;   
	ob_end_clean();   
	return $result;   
	//close connection   
	curl_close($ch) ;  
}

?>

