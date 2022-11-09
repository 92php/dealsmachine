<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once('../lib/lib_goods.php');

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
    admin_priv('goods_tuijian');



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

    /* 模板赋值 */
    //$Arr['cat_list'] =     cat_list($cat_id);


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

    $goods_list = goods_tuijian_list($_ACT == 'list' ? 0 : 1, ($_ACT == 'list') ? (($code == '') ? 1 : 0) : -1);
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

else if ($_ACT == 'goods_tuijian'){

     admin_priv('goods_tuijian');
   $field = $_GET['field'];
    $state = empty($_GET['state'])?'1':'0';
    $idArr = explode(',',$_GET['did']);
    $goods_id = $idArr[0];
    $cat_id = $idArr[1];

	if($state){
		$sql = "update ".GOODSTUIJIAN." set $field = '$state' where goods_id = '$goods_id' and cat_id = '$cat_id'";
	}else{
		$sql = "update ".GOODSTUIJIAN." set $field = NULL where goods_id = '$goods_id' and cat_id = '$cat_id'";
	}
	$db->query($sql);
	echo $state;
	exit;
}

/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */

elseif ($_ACT == 'batch')
{
	/* 检查权限 */
    admin_priv('goods_tuijian');

    /* 取得要操作的商品编号 */
    $goodsArr = !empty($_POST['checkboxes']) ? $_POST['checkboxes'] : array();
    $_TYPE = !empty($_POST['type'])?$_TYPE = $_POST['type']:'';
    if (isset($_TYPE))
    {
       /* 删除推荐信息 */
        if ($_TYPE == 'del_tuijian')
        {
			foreach($goodsArr as $val){
				$gArr = explode('|',$val);
				$gid = $gArr[0];
				$cid = $gArr[1];
				$sql = "delete from ".GOODSTUIJIAN." where goods_id='$gid' and cat_id='$cid' ";
				$db->query($sql);
			}
			/* 记录日志 */
			$goods_id = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;
            admin_log('', '批量把', '商品推荐信息ID为：'.$goods_id." 删除推荐信息");
        }

        /* 设为特价 */
        elseif ($_TYPE == 'best')
        {
			foreach($goodsArr as $val){
				$gArr = explode('|',$val);
				$gid = $gArr[0];
				$cid = $gArr[1];
				$sql = "update ".GOODSTUIJIAN." set is_best = '1' where goods_id='$gid' and cat_id='$cid' ";
				$db->query($sql);
			}
        }

        /* 取消特价 */
        elseif ($_TYPE == 'not_best')
        {
			foreach($goodsArr as $val){
				$gArr = explode('|',$val);
				$gid = $gArr[0];
				$cid = $gArr[1];
				$sql = "update ".GOODSTUIJIAN." set is_best = NULL  where goods_id='$gid' and cat_id='$cid' ";
				$db->query($sql);
			}
        }
        /* 设为新品  */
        elseif ($_TYPE == 'new')
        {
			foreach($goodsArr as $val){
				$gArr = explode('|',$val);
				$gid = $gArr[0];
				$cid = $gArr[1];
				$sql = "update ".GOODSTUIJIAN." set is_new = '1' where goods_id='$gid' and cat_id='$cid' ";
				$db->query($sql);
			}
        }

        /* 取消新品  */
        elseif ($_TYPE == 'not_new')
        {
			foreach($goodsArr as $val){
				$gArr = explode('|',$val);
				$gid = $gArr[0];
				$cid = $gArr[1];
				$sql = "update ".GOODSTUIJIAN." set is_new = NULL  where goods_id='$gid' and cat_id='$cid' ";
				$db->query($sql);
			}
        }
        /* 设为热卖 */
        elseif ($_TYPE == 'hot')
        {
			foreach($goodsArr as $val){
				$gArr = explode('|',$val);
				$gid = $gArr[0];
				$cid = $gArr[1];
				$sql = "update ".GOODSTUIJIAN." set is_hot = '1' where goods_id='$gid' and cat_id='$cid' ";
				$db->query($sql);
			}
        }

        /* 取消热卖 */
        elseif ($_TYPE == 'not_hot')
        {
			foreach($goodsArr as $val){
				$gArr = explode('|',$val);
				$gid = $gArr[0];
				$cid = $gArr[1];
				$sql = "update ".GOODSTUIJIAN." set is_hot = NULL  where goods_id='$gid' and cat_id='$cid' ";
				$db->query($sql);
			}
        }

	}

	$link[0]["name"] = "返回上一页";
	$link[0]["url"] = $_SERVER["HTTP_REFERER"];

	sys_msg("批量操作成功", 0, $link);

}
/**
 * 获得商品列表
 *
 * @access  public
 * @params  integer $isdelete
 * @params  integer $real_goods
 * @return  array
 */
function goods_tuijian_list($is_delete, $real_goods=1)
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
        $filter['sort_by']          = empty($_REQUEST['sort_by']) ? 'goods_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order']       = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);


        $where = $filter['cat_id'] > 0 ? " AND t.cat_id = " . $filter['cat_id'] : '';

        /* 推荐类型 */
        switch ($filter['intro_type'])
        {
            case 'is_best':
                $where .= " AND t.is_best=1";
                break;
            case 'is_hot':
                $where .= ' AND t.is_hot=1';
                break;
            case 'is_new':
				$where .= ' AND t.is_new=1';
            case 'is_super_star':
				$where .= ' AND t.is_super_star=1';
				break;
        }

        /* 关键字 */
        if (!empty($filter['keyword']))
        {
            $where .= " AND (g.goods_sn LIKE '%" . mysql_like_quote($filter['keyword']) . "%' OR g.goods_title LIKE '%" . mysql_like_quote($filter['keyword']) . "%')";
        }

        /* 记录总数 */
        $sql = "SELECT COUNT(*) FROM " .GOODSTUIJIAN. " AS t left join  ".GOODS." as g on t.goods_id = g.goods_id  WHERE 1 $where";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);


        /* 分页大小 */
        $filter = page_and_size($filter);

        $sql = "SELECT g.is_free_shipping,g.goods_thumb,g.shop_price,g.goods_sn,g.goods_number,g.is_on_sale,g.goods_title,t.* ".
                    " FROM " . GOODSTUIJIAN . " AS t left join  ".GOODS." as g on t.goods_id = g.goods_id
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
	foreach($rss as $k => $row){
		$rss[$k]["goods_title"] = varResume($row['goods_title']);
		$rss[$k]["cat_name"] = $catArr[$row["cat_id"]]['cat_name'];
        $rss[$k]['goods_thumb']  = get_image_path($row['goods_id'],$row['goods_thumb']);
        $rss[$k]['url_title']    = get_details_link($row['goods_id']);
	}

    return array('goods' => $rss, 'filter' => $filter, 'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);
}


$_ACT = $_ACT == 'msg'?'msg':'goods_tuijian'.$_ACT;
temp_disp();

?>