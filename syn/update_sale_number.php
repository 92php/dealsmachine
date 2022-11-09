<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/syn_public_fun.php');

$stime = date('Y-m-d', gmstr2time('-31 day'));
$etime = date('Y-m-d', gmtime());

$act = empty($_GET['act'])?'':trim($_GET['act']);




if ($act == 'cnum'){
	if($cur_lang != $default_lang){
		$typeArray =  read_static_cache($cur_lang.'_category_c_key',2);
	}else {
		$typeArray =  read_static_cache('category_c_key',2);
	}	
	
	foreach($typeArray as $key => $val){
		if (empty($val['parent_id']) && $val['is_show']  && $val["cat_id"] != '961'){
			$cnum = getDaLeiNum($typeArray,$val['cat_id']);
			$sql = "update eload_category set cnum = '$cnum' where cat_id = '".$val["cat_id"]."';";
			$db->query($sql);
		}
	}
	
	creat_category();
    echo '大类下的产品数量更新成功';

}else{
	
	//不参与排序的产品
	$fliterArr = array('CON0039','MBE9193','CON0635','CON0634','CON0633');

	$sql = "select sum( o.goods_number ) AS num, o.goods_id,o.goods_sn
	FROM `eload_order_goods` AS o, eload_order_info AS oi , eload_goods as g
	WHERE o.order_id = oi.order_id and  o.goods_id = g.goods_id and g.goods_number > 0 and g.is_delete = 0 and oi.order_status > 0 and oi.order_status < 9 AND oi.add_time >= '".(gmstr2time($stime) - 15*3600)."' AND oi.add_time <= '".(gmstr2time($etime) - 15*3600)."' and o.goods_price > 1  group by o.goods_id  order by num desc limit 232";
	
	$goodsArr = $db->arrQuery($sql);
	
	foreach($goodsArr as $val){
		if (!in_array(strtoupper($val['goods_sn']),$fliterArr)){
			$UpdateSql = "update ".GOODS."  SET sale_number = '".$val['num']."'  where goods_id = '".$val['goods_id']."'";
			$db->query($UpdateSql);
		}
	}
	
    echo '销售量更新成功';
	
	
	
	
	
	
	//生成每日特销售
	$daydeal_time = local_strtotime(local_date('Y-m-d'));
	//echo $daydeal_time;
	$daydealArr = $db->selectinfo("select market_price,shop_price,goods_title,goods_id,url_title,goods_thumb from ".GOODS."  WHERE  daydeal_time = '$daydeal_time' limit 1 ");
	if(!empty($daydealArr)){
		$goods_id = $daydealArr['goods_id'];
		$sql = "select count(*) from  " . VPRICE ."   WHERE  goods_id = '$goods_id' and price_type = '6'";
		$status = $db->getOne($sql);
		if(!$status){
			//删除多级价格
				$sql = "UPDATE " . VPRICE .
					   " SET price_type = '6' WHERE  goods_id = '$goods_id'";
				$db->query($sql);
				
				$sql = "update ".GOODS."  set   shop_price = daydeal_price ,daydeal_price = '".$daydealArr['shop_price']."',is_daydeal = '2' where goods_id = '$goods_id'";
				//$sql = "update ".GOODS."  set  market_price = shop_price , shop_price = daydeal_price , is_daydeal = '2' where goods_id = '$goods_id'";
				$db->query($sql);				
				
				$url = $_CFG['creat_html_domain'].'index.php';				
				$content = file_get_contents($url);
				$filename = '../index.htm';				
				$size = round(strlen($content)/1024,2);	
				if ($size>0){
					file_put_contents($filename,$content);
					echo '首页更新成功了';
				}
		}

	}
	
	
	
	//自动审核通过非VIP会员的申请
	//$sql = "update ".USERS." set user_type = 2  where user_rank  = '0' and user_type = 1 ";
	//$db->query($sql);
	//echo '<br>自动审核通过非VIP会员的申请影响行数：'.$db->affectedRows();
	

}


//取得所有所子类，并返回数组
function getChildArr($catKeyArr,$cat_id){
	$cat_idArr = '';
	foreach($catKeyArr as $kk => $vv){
		if($vv['parent_id'] == $cat_id){
			$cat_idArr .= $vv['cat_id'].','.getChildArr($catKeyArr,$vv['cat_id']);
		}
	}
	return $cat_idArr;
	
}



function getDaLeiNum($catKeyArr,$cat_id){
	$children =' cat_id '. db_create_in(getChildArr($catKeyArr,$cat_id));
	$SQL = 'select count(*) from '.GOODS.' as g where '.$children." AND g.is_delete = 0 AND g.is_on_sale = 1  and g.is_alone_sale = 1  and g.goods_thumb <>'' AND  DATEDIFF(curdate(),FROM_UNIXTIME(add_time,'%Y-%m-%d')) <= 15  ";
	//echo $SQL.'<br>';
	return $GLOBALS['db']->getOne($SQL);
}




//echo $sql;

//$UpdateSql = "update ".GOODS." as g,`eload_order_goods` as o,eload_order_info AS oi SET g.sale_number =  sum(o.goods_number)  where o.goods_id = g.goods_id and o.order_id = oi.order_id and o.goods_id = g.goods_id and oi.order_status > 0 and oi.order_status < 9 AND oi.add_time >= '".(gmstr2time($stime) - 15*3600)."' AND oi.add_time <= '".(gmstr2time($etime) - 15*3600)."' and o.goods_price > 1  group by o.goods_id";
//$db->query($UpdateSql);

//$page++;
//echo "<META HTTP-EQUIV='Refresh' Content='1;URL=modify_price.php?page=".$page."'>";

?>