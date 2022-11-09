<?php
set_time_limit(0);
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/cls_image.php');
require_once('../lib/syn_public_fun.php');

//$content =  var_export($_POST, true);
//file_put_contents(realpath('post.txt'),$content);
//exit;

$keys_code = empty($_REQUEST['keys_code'])?'':$_REQUEST['keys_code'];
if ($keys_code!=$_CFG['keys_code']){die('Error,key code error');}

$data = unserialize(stripslashes($_REQUEST['data']));

if(is_array($data) && !empty($data))
{
	foreach ($data as $key => $value)
	{
		$goods_sn = empty($value['goods_sn'])?'':$value['goods_sn'];
		$goods_state = empty($value['goods_state'])?'':$value['goods_state'];
		$_USR = empty($value['sa_user'])?'':$value['sa_user'];
		$last_update=gmtime();
		
		/*if($goods_sn=='' || $goods_state==''){
			echo '参数有误';
			exit();
		}*/
		$shelf_down_stock = empty($value['shelf_down_stock'])?0:$value['shelf_down_stock'];
		//产品类型(0为普通,100为清仓)
		$shelf_down_type = empty($value['shelf_down_type'])?'':$value['shelf_down_type'];
		$shelf_number = 99999999;
		$shelf_down_type_sql = '';
		if($shelf_down_type == 100){
			$shelf_number = $shelf_down_stock;
			$shelf_down_type_sql = ' ,shelf_down_type = 100';
		}		
		
		
		
		switch (intval($goods_state)){
		case 1:
			$sql="update ".GOODS." set goods_number=$shelf_number$shelf_down_type_sql , is_on_sale=1, last_update='$last_update' where goods_sn='$goods_sn'";
			$db->query($sql);
			$log_info = $_USR." 将 $goods_sn 上架了，系统将库存设为$shelf_number";
			break;
		case 2:
			$sql="update ".GOODS." set is_on_sale=0, last_update='$last_update', promote_start_date=0, promote_end_date=0, is_promote=0 where goods_sn='$goods_sn'";
			$db->query($sql);
			//echo $sql;
			$log_info = $_USR." 将 $goods_sn 下架了";
			break;
		case 3:
			$sql="update ".GOODS." set goods_number=0 , is_on_sale=1, last_update='$last_update' where goods_sn='$goods_sn'";
			$db->query($sql);
			//echo $sql;
			$log_info = $_USR." 将 $goods_sn 上架了，系统将库存设为0";
			break;
		}
		
		//每周特销商品需要清除CDN商品的商品详情页面缓存
		$sql = "SELECT goods_id FROM " . GOODS . " WHERE goods_sn='$goods_sn'";
		$goods_id = $db->getOne($sql);
		$purgeUrlList = "purge_url=" . CDN_CLEAR_URL_PATH .get_details_link($goods_id,'','',1);
		$aa = post_purge_cache(CDN_API_PATH,$purgeUrlList);
		//print_r($aa);
		
		if($goods_state){
		
			$sql = 'INSERT INTO ' . ALOGS . ' (log_time, user_id, log_info, ip_address) ' .
					" VALUES ('" . gmtime() . "', '0', '" . addslashes($log_info) . "', '" . real_ip() . "')";
			$db->query($sql);
		}
	}
	echo 'success';
}
exit();
?>