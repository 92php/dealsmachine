<?php
session_start();
define('INI_WEB', true);
require_once("../lib/inc.fun.php");
require_once('../lib/class.image.php');
if(!empty($_GET['act'])&&$_GET['act'] == 'check_verifycode'){
	if(!$_REQUEST['code']){
		echo 'false';
	}else {
		if($_SESSION['verify'] == md5($_REQUEST['code'])){
			echo 'true';
		}else {
			echo 'false';
		}
	}
}else{
	Image::buildImageVerify(); //输出
}
?>