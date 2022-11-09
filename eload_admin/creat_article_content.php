<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
/* 权限检查 */
admin_priv('creat_article_content');
$msg ='';
$sql = "SELECT url_title,article_id FROM " .ARTICLE. "  WHERE is_open = 1";
$arr = $db->arrQuery($sql);

foreach($arr as $k => $v){
	
	$yuan_url = $_CFG['creat_html_domain'].'m-article.htm?id='.$v['article_id'];
	$filepath = ARTICLE_DIR;
	$path_dir = ROOT_PATH .$filepath;
	
	if (!file_exists($path_dir)){
		if (!make_dir($path_dir)){sys_msg( '目录不能写，请检查读写权限', 1, array(), false);exit;}}	
		
	$filename = '../'.$filepath.$v['url_title'];
	$content = file_get_contents($yuan_url);
	if (file_put_contents($filename,$content)){
		if(file_exists($filename)){
			$size = round(filesize($filename)/1024,2);
			$msg .= $v['url_title'].'生成成功，文件大小'.$size.'k     <a href="'.$filename.'" target="_blank">查看</a><br>';
		}else{
			$msg .= '生成失败<br>';
		}
	}else{
		$msg = '生成失败<br>';
	}
}


sys_msg( $msg, 1, array(), false);

?>