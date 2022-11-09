<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once('../lib/lib_goods.php');
require_once('../lib/inc.html.php');
$_ACT = 'product';
$_ID  = '';
$goods_id = 0;
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));
if (!empty($_GET['goods_id'])) $goods_id     = intval(trim($_GET['goods_id']));



$catArr    = read_static_cache('category_c_key',2);


$sql      = "select g.cat_id,g.goods_id from ".GOODS." as g left join ".CATALOG." as c on g.cat_id = c.cat_id where g.goods_id <=3396 order by c.parent_id,c.cat_id,g.goods_id";
$goodsArr = $db -> arrQuery($sql);
$catArr    = read_static_cache('category_c_key',2);

$sql = " update ".GOODS." set goods_sn = ''  where goods_id <=3396 ";
$db->query($sql);
//exit;
$parent_id = 'xx';
foreach ($goodsArr as $key => $row){
	
	$cat_id    = $row['cat_id'];
	echo $catArr[$cat_id]['cat_name'].$catArr[$cat_id]['parent_id'].'<br>';
	if ($parent_id!= $catArr[$cat_id]['parent_id'])
	$max_id = 1;
	
	$parent_id = $catArr[$cat_id]['parent_id'];
	$catqian = $catArr[$cat_id]['grade'];
	echo $catArr[$cat_id]['cat_name'].$parent_id.' '.$max_id.'<br>';
	$goods_sn = $catqian.str_pad($max_id, 4, '0', STR_PAD_LEFT);
	$sql = " update ".GOODS." set goods_sn = '$goods_sn'  where goods_id = ".$row['goods_id'];
	$db->query($sql);
	$max_id = $max_id+1;
}
echo '修复完成';
?>

