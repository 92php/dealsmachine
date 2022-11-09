<?php
/**
 * 分类商品统计报表
 * 
 * */
set_time_limit(0);
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once(ROOT_PATH . 'lib/lib_order.php');
require_once(ROOT_PATH . 'lib/class.page.php');

//获得分类列表
$typeArray =  read_static_cache('category_c_key',2);

$return_cat_arr_1 = array();
$return_cat_arr_2 = array();
$return_cat_arr_3 = array();

$start_date = local_strtotime('2012-5-01');
$end_date = local_strtotime('2012-6-31');

foreach ($typeArray AS $key => $value)
{
	//$children = get_children($value['cat_id']);	
	$children = new_get_children($value['cat_id'], true, false);
	
	$where = " WHERE og.order_id = oi.order_id and g.goods_id = og.goods_id and oi.order_status > 0 and oi.order_status < 9 and oi.is_dao = 0 AND oi.add_time >=$start_date AND oi.add_time <= $end_date AND g.cat_id IN($children)";
	
	$sql = "select SUM(og.goods_number) as goods_num,SUM(og.goods_number*og.goods_price) as goods_price  ".
           "FROM ".ODRGOODS." AS og, ".GOODS." AS g, ".
           ORDERINFO." AS oi  " .$where ;
           //echo $sql."<br>"; exit();
    $children = '';
	$tj = $GLOBALS['db']->selectinfo($sql);
	//print_r($tj);exit();
	
	
	if($value['level'] ==1)
	{
		$return_cat_arr_1[$value['cat_id']]['cat_name'] = str_replace(",","=",$value['cat_name']);
		$return_cat_arr_1[$value['cat_id']]['goods_price'] =  $tj['goods_price'];
		$return_cat_arr_1[$value['cat_id']]['goods_num'] =  $tj['goods_num'];
	}
	elseif ($value['level'] ==2)
	{
		$return_cat_arr_2[$value['parent_id']][$value['cat_id']]['cat_name'] = str_replace(",","=",$value['cat_name']);
		$return_cat_arr_2[$value['parent_id']][$value['cat_id']]['goods_price'] =  $tj['goods_price'];
		$return_cat_arr_2[$value['parent_id']][$value['cat_id']]['goods_num'] =  $tj['goods_num'];
	}
	elseif ($value['level'] ==3)
	{
		$return_cat_arr_3[$value['parent_id']][$value['cat_id']]['cat_name'] = str_replace(",","=",$value['cat_name']);
		$return_cat_arr_3[$value['parent_id']][$value['cat_id']]['goods_price'] =  $tj['goods_price'];
		$return_cat_arr_3[$value['parent_id']][$value['cat_id']]['goods_num'] =  $tj['goods_num'];
	}
	elseif ($value['level'] ==4)
	{
		$return_cat_arr_4[$value['parent_id']][$value['cat_id']]['cat_name'] = str_replace(",","=",$value['cat_name']);
		$return_cat_arr_4[$value['parent_id']][$value['cat_id']]['goods_price'] =  $tj['goods_price'];
		$return_cat_arr_4[$value['parent_id']][$value['cat_id']]['goods_num'] =  $tj['goods_num'];
	}
	
}

/*echo "<br>one=";
	print_r($return_cat_arr_1);
	echo "<br>two=";
	print_r($return_cat_arr_2);
	echo "<br>three=";
	print_r($return_cat_arr_3);
exit();*/
$echo_str = '';
foreach ($return_cat_arr_1 as $key_one => $cat_one)
{
	$echo_str .= $cat_one['cat_name'].'| | | |' .$cat_one['goods_price'] .'|' . $cat_one['goods_num'] ."\n";
	if(!empty($return_cat_arr_2[$key_one]))
	{
		foreach ($return_cat_arr_2[$key_one] as $key_two => $cat_two)
		{
			$echo_str .= ' |' . $cat_two['cat_name']. '| | |' .$cat_two['goods_price'] .'|' . $cat_two['goods_num'] ."\n";
			if(!empty($return_cat_arr_3[$key_two]))
			{
				foreach ($return_cat_arr_3[$key_two] as $key_three => $cat_three)
				{
					$echo_str .= ' | |' . $cat_three['cat_name']. '| |' .$cat_three['goods_price'] .'|' . $cat_three['goods_num'] ."\n";
					if(!empty($return_cat_arr_4[$key_three]))
					{
						foreach ($return_cat_arr_4[$key_three] as $key_four => $cat_four)
						{
							$echo_str .= ' | | |'.$cat_four['cat_name'] ."|" .$cat_four['goods_price'] .'|' . $cat_four['goods_num'] ."\n";
						}
					}
				}
			}
		}
	}
}
echo $echo_str;
exit();
header("Content-type:application/vnd.ms-excel");
header("content-Disposition:filename=goods_cat_sale_price.csv ");


?>