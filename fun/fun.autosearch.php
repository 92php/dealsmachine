<?php
if (!defined('INI_WEB')){die('访问拒绝');}

require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');
require_once(ROOT_PATH . 'lib/class.page.php');
require_once(ROOT_PATH . 'lib/lib.f.goods.php');
$q = strtolower($_GET["q"]);
$callback = isset($_REQUEST['jsoncallback']) ? $_REQUEST['jsoncallback'] : '';
//if($cur_lang != $default_lang){
//	$typeArray =  read_static_cache($cur_lang.'_category_c_key',2);
//}else {
//	$typeArray =  read_static_cache('category_c_key',2);
//}
$sql = "SELECT keyword from eload_search_suggestions where keyword like '%".$q."%' limit 20 " ;
$goodArr = $db->arrQuery($sql);
$echostr  = '';
foreach($goodArr as $row){
	$echostr  .= ''.$row['keyword'].'|'.title_to_url($row['keyword'])."\n"; 
}
echo $callback. '('.json_encode(array('data'=>$echostr)). ')';
exit;
?>