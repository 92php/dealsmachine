<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/class.function.php');
$click = read_static_cache('share_click',1);
$num = rand(1,10);
if(!empty($click)){
	
	foreach($click as $key=>$row){
		$click[$key] = $row+$num;
		
	}
}else{

	for($i =0;$i<20;$i++){
		$click[$i] = rand(1,10);		
	}
}

write_static_cache('share_click',$click,1);

?>