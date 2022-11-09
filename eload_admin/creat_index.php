<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
//require_once('../lib/is_loging.php');
/* 权限检查 */
//admin_priv('creat_index');
$default_lang = 'en';

//$url = 'http://www.testmicro.com/index.php';
$url = $_CFG['creat_html_domain'].'index.php';	
$content = file_get_contents($url);
$filename = '../index.html';
if (file_put_contents($filename,$content)){
	if(file_exists($filename)){
		$size = round(filesize($filename)/1024,2);
		/*
		file_get_contents('http://acloud.faout.com/purge/index.html');    //清缓存
		file_get_contents('http://acloud.faout.com/purge/');    //清缓存
		file_get_contents('http://purge.faout.com:9090/peal.php?purge_url=http://www.bestafford.com/');    //清缓存	
		file_get_contents('http://purge.faout.com:9090/peal.php?purge_url=http://www.bestafford.com/index.html');    //清缓存		
		*/
		$msg = '原语言首页生成成功，文件大小'.$size.'k<br>';
	}
}


//多语言
$sql = "SELECT * FROM " . Mtemplates_language. " WHERE status = 1 ORDER BY orders ASC";
$lang = $db->arrQuery($sql);
foreach($lang as $value) {
	$url = $_CFG['creat_html_domain'] . $value['title_e'] .'/index.php';		
	$content = file_get_contents($url);
	$content = checkBOM($content);
	$filename = '../';
	if($value['title_e'] != $default_lang) {
		$file_folder = $filename . $value['title_e'];
		$filename .= $value['title_e'] . '/';		
	}
	$filename .= 'index.html';
	if(!file_exists($file_folder)) {mkdir($file_folder);}
	unset($file_folder);
	if (file_put_contents($filename,$content)){
		if(file_exists($filename)){
			$size = round(filesize($filename)/1024,2);
			/*
			file_get_contents('http://acloud.faout.com/purge/'.$value['title_e'].'/index.html');    //清缓存
			file_get_contents('http://acloud.faout.com/purge/'.$value['title_e'].'/');    //清缓存
			file_get_contents('http://purge.faout.com:9090/peal.php?purge_url=http://www.bestafford.com/'.$value['title_e'].'/');    //清缓存				
			file_get_contents('http://purge.faout.com:9090/peal.php?purge_url=http://www.bestafford.com/'.$value['title_e'].'/index.html');    //清缓存				
			*/
			$msg .= $value['title'] . '首页生成成功，文件大小'.$size.'k<br>';
		}else{
			$msg .= $value['title'] . '生成失败<br>';
		}
	}else{
		$msg .= $value['title'] . '生成失败<br>';
	}
}


function checkBOM($contents) {
	$charset[1]=substr($contents, 0, 1);
	$charset[2]=substr($contents, 1, 1);
	$charset[3]=substr($contents, 2, 1);
	if (ord($charset[1])==239 && ord($charset[2])==187 && ord($charset[3])==191) {
		$contents = substr($contents, 3);
	}
	return $contents;
}

sys_msg( $msg, 1, array(), false);

?>