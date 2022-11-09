<?php
/*刷新分类数据*/

define('INI_WEB', true);

set_time_limit(0);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
$catArr_parr = read_static_cache('category_c_key',2);

/*$a = get_parent_id_new(184);

print_r($a);*/
$parent_id_arr = array();
foreach ($catArr_parr as $key_1 =>$cid)
{
	$parent_id_str = get_parent_id_new($key_1,$catArr_parr);
	
	$parent_id_str = $key_1.",".$parent_id_str;
	$parent_id_str = rtrim($parent_id_str,'0,');
	$parent_id_arr[$key_1]['cat_id'] = explode(",",$parent_id_str);
	krsort ($parent_id_arr[$key_1]['cat_id']);
	$parent_id_str_new[$key_1]['cat_id'] = implode(",",$parent_id_arr[$key_1]['cat_id']);
	$parent_id_str_new[$key_1]['cat_num'] = count($parent_id_arr[$key_1]['cat_id']);
	
	$sql = "UPDATE eload_category SET node = '" . $parent_id_str_new[$key_1]['cat_id'] . "' , level = " . $parent_id_str_new[$key_1]['cat_num']. " WHERE cat_id = " .$key_1;
	$db->query($sql);
}
print_r($parent_id_str_new);


function get_parent_id_new($cid,$catArr){
	$pids = '';
	//$catArr = read_static_cache('category_c_key',2);
	if (isset($catArr[$cid]['parent_id'])){
		$pids .= $catArr[$cid]['parent_id'];
		$npids = get_parent_id_new($catArr[$cid]['parent_id'],$catArr);
		if(isset($npids)) $pids .= ','.$npids;
	}
	return $pids;
}
?>