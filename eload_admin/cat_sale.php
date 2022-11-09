<?php
/* 分类销售统计
 * @author jim
 */

define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/lib_goods.php');
require_once(ROOT_PATH . 'lib/syn_public_fun.php');
include_once(ROOT_PATH.'config/language_cfg.php');
include(ROOT_PATH . 'languages/en/shopping_flow.php');

admin_priv('cat_sale');

$_ACT   = empty($_REQUEST['act'])?'list':$_REQUEST['act'];
$table_name = 'eload_cat_sale';  //表名

if(!IS_LOCAL&&function_exists('get_slave_db'))$db   = get_slave_db();  //线上使用从服务器
$sql = "CREATE TABLE   IF NOT EXISTS  `$table_name` (
  `cat_id` int(11) DEFAULT 0,
  `parent_id` int(11) DEFAULT 0,
  `sale_number` int(11) DEFAULT '0',
  `sale_amount` float DEFAULT '0',
  KEY `cat_id` (`cat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$db->query($sql); // 建表

if($_ACT=='stat'){// 统计
	$days = empty($_GET['days'])?90:$_GET['days'];  //默认统计90天内的销售情况
	$days = gmtime()-$days*3600*24;
	$sql = "insert into $table_name(cat_id,sale_number,sale_amount) 
select cat_id,count(og.goods_number) as qty, sum(og.goods_number*og.goods_price) as amt 
from eload_order_goods og 
inner join eload_order_info i on og.order_id =i.order_id 
inner join eload_goods g on og.goods_id = g.goods_id 
where i.add_time > $days and cat_id in (select cat_id from eload_category where is_show=1) and order_status between 1 and 9 group by cat_id ";
	$db->query("TRUNCATE TABLE $table_name");
	$db->query($sql);
	if(!empty($_GET['is_test']))echo $sql;
	//header("Location:cat_sale.php"); 
	//exit();
	$_ACT = 'list';
}


$Arr['lang_arr'] = $lang_arr;

/*------------------------------------------------------ */
//-- 统计列表
/*------------------------------------------------------ */
if ($_ACT == 'list')
{  

	$tree=array();
	$catArr = read_static_cache('category_c_key',2);//var_export(getChilds($catArr, 0));exit;	

	$cat_stat = array();
	foreach($catArr as $k=>$v)
	{
		if($v['parent_id'] == 0&&$v['is_show']){
			$cat_stat[]= get_stat($v['cat_id']);		
			get_child_cat_sale($v['cat_id'],$catArr);
		}
	}
    unset($catArr);
	$Arr["catArr"] = $cat_stat;
}

/**
 * 统计指定分类的所有下级分类的销售情况
 * 
 * @param int $cat_id
 */
function get_child_cat_sale($cat_id){
	global $cat_stat,$catArr;

	if(new_get_children($cat_id)==$cat_id)return;
	foreach ($catArr as $k=>$v){
		if($v['parent_id'] == $cat_id&&$v['is_show']){
			$cat_stat[] = get_stat($v['cat_id']);
			//echo $v['cat_id']."\n";
			get_child_cat_sale($v['cat_id']);
		}
	}

}
/**
 * 统计指定分类的销售情况
 * @param $cat_id 需要统计的分类ID
 * @param unknown_type $cat_id
 */
function get_stat($cat_id){
	global $db,$catArr;

	$sql  = "select sum(sale_number) as qty,sum(sale_amount) as amt from eload_cat_sale where cat_id in(".new_get_children($cat_id).")";

	
	$arr = $db->selectInfo($sql);
	$arr['qty'] = number_format($arr['qty'],0);
	$arr['amt'] = number_format($arr['amt'],2);
	$arr['cat_id'] = $cat_id;
	$arr['level'] = $catArr[$cat_id]['level'];
	$arr['cat_name'] = get_cat_ext_string($arr['level']).$catArr[$cat_id]['cat_name'];
	$arr['parent_id'] = $catArr[$cat_id]['parent_id'];
	$arr['url_title'] = !empty($catArr[$cat_id]['url_title'])?$catArr[$cat_id]['url_title']:$catArr[$cat_id]['link_url'];
	
	//$arr['on_sale_count'] = $db->getone("select count(1) from eload_goods where is_on_sale =1 and goods_number >0 and ".get_children($cat_id,'')); 
	$arr['goods_count'] = $db->getone("select count(1) from eload_goods where is_on_sale =1 and  cat_id in(".new_get_children($cat_id).")") ;
	//echo $cat_id."<br>";
	$arr['child_cat_id'] = new_get_children($cat_id);
	return $arr;
}

function get_cat_ext_string($level){
	$str='';
	for($i = 1;$i<$level;$i++ ){
		$str .='&nbsp;&nbsp;';
	}
	if($level>1){$str .='├ ';}
	return $str;
}

 $_ACT = $_ACT == 'msg'?'msg':'cat_sale';
temp_disp();
?>