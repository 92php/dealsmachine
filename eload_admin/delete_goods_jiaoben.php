<?php

/**
 * 商品删除脚本
 * by jim 2013.6.11
 * */
set_time_limit(0);
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
//require_once('../lib/is_loging.php');
$type = empty($_GET['type']) ? 0 : 1;		//0直接输入商品SN   1使用文件导入
define("IDENTITY", "cd4120018520b76382b27520b55a24d5");
$identity = empty($_REQUEST['identity'])?'':trim($_REQUEST['identity']);

if($identity==IDENTITY){
	$act = empty($_REQUEST['act'])?'select':trim($_REQUEST['act']);
	if($type == 1)
	{
		$file_path = ROOT_PATH.'./syn/delete_goods_sn.csv';
		$goods_list_str = file_get_contents($file_path);
		$goods_list = explode("\n",$goods_list_str);

		$goods_sn = array();
		foreach ($goods_list as $key => $goods)
		{
			if(!empty($goods))
			{
				$goods_array = explode(",",$goods);
				$goods_sn[] = trim($goods_array[0]);
			}
		}

	}
	else
	{
		$goods_sn = isset($_REQUEST['goods_sn'])?$_REQUEST['goods_sn']:'';			//参数格式 type=1&goods_sn ='HB0127307','AW0008803','AW0008802'
	}
	if(!empty($goods_sn)){
		$array = explode(',',$goods_sn);
		$num=$db->getOne("select count(*) from ".GOODS." where goods_sn in ('". implode("','",$array)."')");
		if($num>0){
			if($act == 'delete'){
				
				delete_goods_sn($goods_sn);
				//生成跳转缓存文件
				
				$sql = "SELECT goods_id , cat_id FROM " . GOODS_DELETE . " ORDER BY id ASC";
				$goods_list = $db->arrQuery($sql);
				$data = array();
				foreach ($goods_list as $key=>$value)
				{
					$data[$value['goods_id']] = $value['cat_id'];
					//$purgeUrlList = "purge_url=" . DOMAIN .get_details_link($value['goods_id'],'','',1);
					//$aa = post_purge_cache(CDN_API_PATH,$purgeUrlList);
				}
				write_static_cache('qinquan.log', $data,2);
				
				die($num." success");
			}else {
				
				die($num);
			}
		}else {
			die('0');
		}
		
	}else{
		die('goods_sn为空');
	}
}else{
	die("验证失败");
}

/**
 * 批量删除商品
 * @param   mix     $goods_sn   商品编码列表：可以逗号格开，也可以是数组
 * @return  void
 */
function delete_goods_sn($goods_sn)
{
	
    if (empty($goods_sn))
    {
        return;
    }

    // 取得有效商品id
    $sql = "SELECT DISTINCT goods_id FROM " . GOODS .
            " WHERE goods_sn " . db_create_in($goods_sn);
    $goods_id = $GLOBALS['db']->getCol($sql);
    //echo count($goods_id);
    if (empty($goods_id))
    {
        return;
    }
    
    //记录彻底删除的商品
    $delete_time = local_strtotime(local_date('Y-m-d')); //当天开始时间
	$insert_goods_sql = "INSERT INTO " . GOODS_DELETE . "(goods_id , cat_id , delete_time) SELECT goods_id , cat_id , " .$delete_time." FROM " . GOODS . " WHERE goods_id " . db_create_in($goods_id);
	$GLOBALS['db']->query($insert_goods_sql);

    // 删除商品图片和轮播图片文件
    $sql = "SELECT goods_thumb, goods_img,goods_grid, original_img, url_title,cat_id " .
            "FROM " . GOODS .
            " WHERE goods_id " . db_create_in($goods_id);
    $res = $GLOBALS['db']->query($sql);

    while ($goods = $GLOBALS['db']->fetchRow($res))
    {
        //删除商品封面和相册图片
        $syn_gallery_image_ser1 = serialize(array());
	    $post_data="goods_thumb=".$goods['goods_thumb']."&goods_grid=".$goods['goods_grid']."&goods_img=".$goods['goods_img']."&original_img=".$goods['original_img']."&syn_gallery_image=$syn_gallery_image_ser1&action=del";
	    echo post_image_info(IMG_API_PATH,$post_data);//到图片库删除相册

		$path_dir = ROOT_PATH .GOODS_DIR.$goods['cat_id'].'/'.$goods['url_title'];
		if (file_exists($path_dir)){
			@unlink($path_dir);
		}

    }

    /* 删除商品 */
    $sql = "DELETE FROM " . GOODS .
            " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);

    
    
    /* 删除商品扩展表 */
    //$sql = "DELETE FROM " . GOODS_EXTEND .
    //		" WHERE goods_id " . db_create_in($goods_id);
    //$GLOBALS['db']->query($sql);

    /* 删除商品阶梯价格表 */
    $sql = "DELETE FROM " . VPRICE . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);

    /* 删除商品相册的图片文件 */
    $sql = "SELECT img_url, thumb_url, img_original " .
            "FROM " . GGALLERY .
            " WHERE goods_id " . db_create_in($goods_id);
    $res = $GLOBALS['db']->query($sql);

    while ($row1 = $GLOBALS['db']->fetchRow($res))
    {
       $syn_gallery_image1[]="img_url@@".$row1['img_url']."@@@thumb_url@@".$row1['thumb_url']."@@@img_original@@".$row1['img_original'];
    }
	if(!empty($syn_gallery_image1)){
	    $syn_gallery_image_ser1=serialize($syn_gallery_image1);
	    //删除商品封面和相册图片
	    $post_data="syn_gallery_image=$syn_gallery_image_ser1&action=del";
	    echo post_image_info(IMG_API_PATH,$post_data);//到图片库删除相册
	}
    /* 删除商品相册 */
    $sql = "DELETE FROM " . GGALLERY . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);

    $sql = "DELETE FROM " . GROUPGOODS . " WHERE parent_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);
    $sql = "DELETE FROM " . GROUPGOODS . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);

    $sql = "DELETE FROM " . GOODSCAT . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);
    /* 删除相关表记录 */
    $sql = "DELETE FROM " . COLLECT . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);
    $sql = "DELETE FROM " . GATTR . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);
    $sql = "DELETE FROM " . COMMENT . " WHERE comment_type = 0 AND id_value " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);

    
    //删除商品点击率
 	 $sql = "DELETE FROM " . GOODS_HITS . " WHERE goods_id " . db_create_in($goods_id);
    if(defined('IS_LOCAL')&&!IS_LOCAL){
    	$db_s = get_slave_db();	   
	    $db_s->query($sql);
	    unset($db_s);    	
    }else{
	    $GLOBALS['db']->query($sql);   	
    }

    //$db->close();

    //删除推荐商品
    $sql = "DELETE FROM " . GOODSTUIJIAN . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);

	//删除专题活动商品
    //$sql = "DELETE FROM " . SPECIAL_GOODS . " WHERE goods_id = '$goods_id'";
    //$GLOBALS['db']->query($sql);

    //删除商品咨询表
    $sql = "DELETE FROM " . PRO_INQUIRY . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);

    //获得商品评论ID
    $sql = "SELECT rid FROM " . REVIEW . " WHERE goods_id " . db_create_in($goods_id);
    $rid = $GLOBALS['db']->getCol($sql);

    //删除商品评论回复表
    $sql = "DELETE FROM " . REVIEW_REPLY . " WHERE rid " . db_create_in($rid);

    $GLOBALS['db']->query($sql);
    //删除商品评论图片表
    $sql = "DELETE FROM " . REVIEW_PIC . " WHERE rid " . db_create_in($rid);
    $GLOBALS['db']->query($sql);
    //删除商品评论视屏表
    $sql = "DELETE FROM " . REVIEW_VIDEO . " WHERE rid " . db_create_in($rid);
    $GLOBALS['db']->query($sql);
    //删除商品评论主表
    $sql = "DELETE FROM " . REVIEW . " WHERE rid " . db_create_in($rid);
    $GLOBALS['db']->query($sql);

    //删除购物车中的商品
    $sql = "DELETE FROM " . CART . " WHERE goods_id " .db_create_in($goods_id);
    $GLOBALS['db']->query($sql);

}

/**
 * post 数据到图片库，同步添加
 * parm $url post地址
 * parm $data post数据
 */
function post_image_info($url,$data)
{
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
	$contents = curl_exec($ch);
	curl_close($ch);

    return $contents;
}
?>