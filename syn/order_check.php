<title>异常订单核对程序</title><?php
set_time_limit(0);
define('INI_WEB', true);
$_BEGINTIME = microtime(TRUE);
require('../lib/global.php');              //引入全局文件
require('../lib/time.fun.php');  

$order_sn_back_contents = file_get_contents(ROOT_PATH.'data-cache/order_sn_back.php');

if (!empty($_GET['act'])){
	$order_sn_back_contents = '';
	file_put_contents(ROOT_PATH.'data-cache/order_sn_back.php',$order_sn_back_contents);
	echo '清空成功。';
}




$orderArr = explode(',',$order_sn_back_contents);

foreach($orderArr as $key => $val){
	
	if (!empty($val)){
		$snArr = explode('|',$val);
		echo $key.'：';
		$sql = "SELECT order_id FROM  eload_order_info where order_sn = '".$snArr[0]."' ";
		if(!$db->getOne($sql)){
			echo '<font color="#ff000">'.$snArr[0].' 不存在</font> '.$snArr[1];
		}else{
			echo $snArr[0].' 正常  '.$snArr[1];
		}
		echo '<br>';
	}
}





?>
<a href="?act=del">清空</a>