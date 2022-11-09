<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once('../lib/lib_goods.php');
require_once('../lib/syn_public_fun.php');
$_ACT = 'list';
$_ID  = '';
$goods_id = 0;
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));
if (!empty($_GET['goods_id'])) $goods_id = intval(trim($_GET['goods_id']));

/*------------------------------------------------------ */
//-- 商品列表，商品回收站
/*------------------------------------------------------ */

if ($_ACT == 'list' || $_ACT == 'trash')
{
    admin_priv('goods_daydeal');



	 if (!empty($_REQUEST['cat_id'])){
		foreach($_REQUEST['cat_id'] as $key => $val){
			if($_REQUEST['cat_id'][$key] == '') unset($_REQUEST['cat_id'][$key]);
		}
	  }
	  

	 if (!empty($_GET['cat_id'])){
		foreach($_GET['cat_id'] as $key => $val){
			if($_GET['cat_id'][$key] == '') unset($_GET['cat_id'][$key]);
		}
	  }

    $cat_id = empty($_GET['cat_id']) ? 0 : intval(is_array($_GET['cat_id'])?end($_GET['cat_id']):$_GET['cat_id']);

    $_REQUEST['cat_id'] = empty($_REQUEST['cat_id']) ? 0 : intval(is_array($_REQUEST['cat_id'])?end($_REQUEST['cat_id']):$_REQUEST['cat_id']);
	
	
	
	
	
    $code   = empty($_GET['extension_code']) ? '' : trim($_GET['extension_code']);

    $handler_list = array();

    if ($_ACT == 'list' && isset($handler_list[$code]))
    {
        $Arr['add_handler'] =      $handler_list[$code];
    }	
	
	
	
	if($cat_id){
		$Arr["cat_list"] = '';
		$Arr["target_cat_list"] = '';
		$parent_id_str = get_parent_id($cat_id);
		$parent_id_Arr = explode(',',$parent_id_str);
		$parent_id_Arr = array_reverse ($parent_id_Arr); //数组逆序
		foreach($parent_id_Arr as $key => $val){
			if ($val!=''){
				$parent_id = $val;
				$selectid = isset($parent_id_Arr[$key+1])?$parent_id_Arr[$key+1]:$cat_id;
				//echo '$parent_id:'.$parent_id.'$selectid'.$selectid.'<br>';
				$Arr["cat_list"] .=  get_lei_select($parent_id,'cat_id[]','cat_id','goods_cat',$selectid);
				$Arr["target_cat_list"] .=  get_lei_select($parent_id,'target_cat[]','cat_id','OtherCat','','','请选择分类');
			}
		}
	}else{
		$Arr["cat_list"] =  get_lei_select('0','cat_id[]','cat_id','goods_cat','','','所有分类');
		$Arr["target_cat_list"] =  get_lei_select('0','target_cat[]','cat_id','OtherCat','','','请选择分类');
	}
	
	
	
    //$Arr['intro_list'] =   get_intro_list();
    $Arr['list_type']   =    $_ACT == 'list' ? 'goods' : 'trash';
    $Arr['use_storage'] =  empty($_CFG['use_storage']) ? 0 : 1;

    $goods_list = goods_daydeal_list($_ACT == 'list' ? 0 : 1, ($_ACT == 'list') ? (($code == '') ? 1 : 0) : -1);
    $Arr['goods_list'] =   $goods_list['goods'];
	$Arr['attr_list'] =   goods_type_list(0);
    $sort_flag  = sort_flag($goods_list['filter']);
    $Arr[$sort_flag['tag']] = $sort_flag['img'];
	$goods_list['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];
	
    $Arr['filter'] =       $goods_list['filter'];
    /* 排序标记 */
	
	$page=new page(array('total'=>$goods_list['record_count'],'perpage'=>$goods_list['page_size'])); 
	$Arr["pagestr"]  = $page->show();
	
}

else if ($_ACT == 'add'){

   admin_priv('goods_daydeal');
   $goodsArr = array();
   if($_ID){
	   $goodsArr    = $db->selectinfo("select goods_sn,shop_price,daydeal_price,market_price,is_daydeal,daydeal_time from ".GOODS." where goods_id = '$_ID' ");
	   $goodsArr['shop_price'] = ($goodsArr['is_daydeal'] == '2')?$goodsArr['market_price']:$goodsArr['shop_price'];
	   $goodsArr['zhekoulv']   = ($goodsArr['shop_price']>0)?round((($goodsArr['shop_price'] - $goodsArr['daydeal_price'])/$goodsArr['shop_price']) * 100,2).' %' :'';
	   $goodsArr['daydeal_time'] = local_date('Y-m-d', $goodsArr['daydeal_time']);

   }
	   
   $Arr['goods'] = $goodsArr;
   $Arr['url'] = $_ID?'?act=update&id='.$_ID:'?act=insert';
}

else if ($_ACT == 'insert' || $_ACT == 'update'){

   admin_priv('goods_daydeal');
   
   $goods_sn     = empty($_POST['goods_sn'])?'':trim($_POST['goods_sn']);
   $shop_price   = empty($_POST['shop_price'])?'':trim($_POST['shop_price']);
   $daydeal_price = empty($_POST['daydeal_price'])?'':trim($_POST['daydeal_price']);
   $daydeal_time  = empty($_POST['daydeal_time'])?'':trim($_POST['daydeal_time']);
   $status       = empty($_POST['status'])?'':intval($_POST['status']);
	
   $uptimestr = '';
   if ($daydeal_time){
	       $daydeal_time = local_strtotime($daydeal_time);
		   $uptimestr = " , daydeal_time = '$daydeal_time' ";
   }
		   
	if($_ID){//修改
	    //当产品为发布状态下只修改促销价格
		if($status == '2'){
			
			$thisStatus = $db->getOne("select is_daydeal from ".GOODS."  where goods_sn = '$goods_sn'");
			if($thisStatus == '1'){
				//删除多级价格
				$sql = "UPDATE " . VPRICE .
					   " SET price_type = '6' WHERE  goods_id = '$_ID'";
				$db->query($sql);
				
				$sql = "update ".GOODS."  set  shop_price_backup = shop_price , shop_price = '$daydeal_price',daydeal_price = '$daydeal_price',is_daydeal = '$status' $uptimestr where goods_sn = '$goods_sn'";
			}else{
				$sql = "update ".GOODS."  set   shop_price = '$daydeal_price',daydeal_price = '$daydeal_price',is_daydeal = '$status' $uptimestr where goods_sn = '$goods_sn'";
			}		
			
		}else{	
			
			$sql = "select shop_price_backup,goods_id,cat_id from ".GOODS." WHERE goods_sn = '$goods_sn'";
			$goods = $db->selectinfo($sql);
			$shop_price = $goods['shop_price_backup'];
			$goods_id   = $goods['goods_id'];
			
			//多级价格还原
			$sql = "UPDATE " . VPRICE .
				   " SET price_type = '1' WHERE  goods_id = '$goods_id'";
			$db->query($sql);
			
			$sql = "update ".GOODS."  set  shop_price = '$shop_price', is_daydeal = '$status'  $uptimestr  where goods_sn = '$goods_sn'";
			
		}
		
		$db->query($sql);
		
	}else{
		
		if($status == '2'){
			//发布状态时候才修改原价
			$sql = "update ".GOODS."  set shop_price_backup = shop_price , shop_price = '$daydeal_price',daydeal_price = '$daydeal_price',is_daydeal = '$status' $uptimestr where goods_sn = '$goods_sn'";
			$db->query($sql);
			
			$goods_id = $db->getOne("select goods_id from ".GOODS." where goods_sn = '$goods_sn' ");
			//删除多级价格
			$sql = "UPDATE " . VPRICE .
				   " SET price_type = '6' WHERE  goods_id = '$goods_id'";
			$db->query($sql);
			
		}else{
			$sql = "update ".GOODS."  set daydeal_price = '$daydeal_price',shop_price_backup = shop_price,is_daydeal = '$status' where goods_sn = '$goods_sn'";
			$db->query($sql);
		}
		
		admin_log('','将' , '商品'.$goods_sn.'设为了每日特效。');
	}
	
	
	
	
    $links[0]["name"] = "返回每日特销列表";
	$links[0]["url"] = "?";	
    sys_msg($_ID ? "修改成功" : "添加成功", 0, $links);
   
}


//取消每日特销
elseif ($_ACT == 'del'){
	
	$sql = "select market_price,goods_id,cat_id,goods_sn from ".GOODS." WHERE goods_id = '$_ID'";
	$goods = $db->selectinfo($sql);
	$shop_price = $goods['market_price'];
	$goods_sn   = $goods['goods_sn'];
	$goods_id   = $_ID;
	
	$market_price = get_market_price($shop_price);
	
	//多级价格还原
	$sql = "UPDATE " . VPRICE .
	   " SET price_type = '1' WHERE  goods_id = '$_ID'";
	$db->query($sql);
	
	$sql = "update ".GOODS."  set market_price = '$market_price' , shop_price = '$shop_price', is_daydeal = '0' , daydeal_time = '0'  where goods_id = '$_ID'";
	
	$db->query($sql);
	
	admin_log('','将' , '商品'.$goods_sn.'删除了每日特效。');
    $links[0]["name"] = "返回每日特销列表";
	$links[0]["url"] = "?";	
    sys_msg("删除成功", 0, $links);
		
}

//通过编码得到正常销售价格
elseif ($_ACT == 'goods_sn_get_shop_price'){
	
	$goods_sn = strtolower($_GET["goods_sn"]);
	$goods = array();
	
	if (!$goods_sn) return;
	$sql = "select shop_price from ".GOODS." where goods_sn = '".$goods_sn."' limit 1 ";
	$goods = $db->selectinfo($sql);
	
	//$sql = "select real_name from ".SADMIN." where sa_user = '".$goods['purchaser']."' order by real_name";
   // $goods['purchaser'] = $db->getOne($sql);
	echo json_encode($goods);
	exit;
}

/**
 * 获得商品列表
 *
 * @access  public
 * @params  integer $isdelete
 * @params  integer $real_goods
 * @return  array
 */
function goods_daydeal_list($is_delete, $real_goods=1)
{
    /* 过滤条件 */
    $param_str = '-' . $is_delete . '-' . $real_goods;
    $result = get_filter($param_str);
    if ($result === false)
    {
        $day = getdate();
        $today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

        $filter['cat_id']           = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
        $filter['intro_type']       = empty($_REQUEST['intro_type']) ? '' : trim($_REQUEST['intro_type']);
        $filter['keyword']          = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
      //  if ($_REQUEST['is_ajax'] == 1)
        //{
        //    $filter['keyword'] = json_str_iconv($filter['keyword']);
       // }
        $filter['sort_by']          = empty($_REQUEST['sort_by']) ? 'daydeal_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order']       = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);


        $where = " AND g.is_daydeal > 0  "; 
		
		if ($filter['cat_id']){
			$children = $filter['cat_id'] > 0 ? get_children($filter['cat_id']):'';	
			 $where .= " AND $children ";
		}
		
        /* 推荐类型 */
        switch ($filter['intro_type'])
        {
            case '1':
                $where .= " AND g.is_daydeal=1";
                break;
            case '2':
                $where .= ' AND g.is_daydeal=2';
                break;
        }

        /* 关键字 */
        if (!empty($filter['keyword']))
        {
            $where .= " AND (g.goods_sn LIKE '%" . mysql_like_quote($filter['keyword']) . "%' OR g.goods_title LIKE '%" . mysql_like_quote($filter['keyword']) . "%')";
        }

        /* 记录总数 */
        $sql = "SELECT COUNT(*) FROM  ".GOODS." as g  WHERE 1 $where";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
		

        /* 分页大小 */
        $filter = page_and_size($filter);

        $sql = "SELECT g.goods_thumb,g.shop_price,g.market_price,g.goods_sn,g.shop_price_backup,g.goods_id,g.goods_number,g.goods_title,g.cat_id,g.daydeal_price,g.is_daydeal,g.daydeal_time ".
                    " FROM  ".GOODS." as g 				
					WHERE 1 $where " .
                    " ORDER BY $filter[sort_by] $filter[sort_order] ".
                    " LIMIT " . $filter['start'] . ",$filter[page_size]";

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql, $param_str);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
			
    $rss = $GLOBALS['db']->arrQuery($sql);
	$catArr = read_static_cache('category_c_key',2);
	
	$localdate = local_date('Y-m-d');
	
	foreach($rss as $k => $row){
		
		
		
		//if($row['is_daydeal'] == 2){
		$rss[$k]["shop_price"]   = $row['daydeal_price'];
		//}
		//echo $row['is_daydeal'];
	   $rss[$k]['zhekoulv']   = ($rss[$k]['market_price']>0)?round((($rss[$k]['market_price'] - $rss[$k]['shop_price'])/$rss[$k]['market_price']) * 100,2).' %' :'';
	   
		$rss[$k]["market_price"] = $row['shop_price_backup'];
		
		$thisdate = local_date('Y-m-d', $rss[$k]["daydeal_time"]);
		$rss[$k]["is_daydeal"] = ($thisdate.'' == $localdate.'')?2:1;
		
		$rss[$k]["daydeal_time"] = $thisdate;
		
		
		$rss[$k]["goods_title"] = varResume($row['goods_title']);
		$rss[$k]["cat_name"] = $catArr[$row["cat_id"]]['cat_name'];
	}

    return array('goods' => $rss, 'filter' => $filter, 'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);
}


$_ACT = $_ACT == 'msg'?'msg':'goods_daydeal'.$_ACT;
temp_disp();

?>