<?php
define('INI_WEB', true);
ob_start();
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once('../lib/lib_goods.php');
require_once('lib/common.fun.php');
require_once(ROOT_PATH . 'lib/syn_public_fun.php');
$_ACT = 'list';
$_ID  = '';
$goods_id = 0;
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));
if (!empty($_GET['goods_id'])) $goods_id = is_array($_GET['goods_id']) ? $_GET['goods_id'] : intval(trim($_GET['goods_id']));
//多语言
$lang = get_lang();
$lang = check_lang_power($lang);
$Arr['lang_arr'] = $lang;
$default_power_lang = check_default_lang_power($lang);
$Arr['default_lang'] = $default_power_lang;

/*------------------------------------------------------ */
//-- 商品列表，商品回收站
/*------------------------------------------------------ */
if ($_ACT == 'list' || $_ACT == 'trash')
{
	$timezone = isset($_SESSION['timezone']) ? $_SESSION['timezone'] : $GLOBALS['_CFG']['timezone'];
    admin_priv('goods_list');
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
    $activity = empty($_GET['activity']) ? 0 : intval(is_array($_GET['activity'])?end($_GET['activity']):$_GET['activity']);
    $is_24h_ship = empty($_GET['is_24h_ship']) ? 0 :$_GET['is_24h_ship'];
    $goods_grade = empty($_GET['goods_grade']) ? 0 :$_GET['goods_grade'];
    $_REQUEST['cat_id'] = empty($_REQUEST['cat_id']) ? 0 : intval(is_array($_REQUEST['cat_id'])?end($_REQUEST['cat_id']):$_REQUEST['cat_id']);
    $code = empty($_GET['extension_code']) ? '' : trim($_GET['extension_code']);
    $handler_list = array();
    $Arr['is_24h_ship'] = $is_24h_ship;
    $Arr['goods_grade'] = $goods_grade;
    if ($_ACT == 'list' && isset($handler_list[$code]))
    {
        $Arr['add_handler'] =      $handler_list[$code];
    }
    /* 模板赋值 */
    $Arr['orher_cat_list'] =     cat_list($cat_id);
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
				$Arr["cat_list"] .=  get_lei_select($parent_id,'cat_id[]','cat_id','goods_cat',$selectid);
				$Arr["target_cat_list"] .=  get_lei_select($parent_id,'target_cat[]','cat_id','OtherCat','','','请选择分类');
			}
		}
	}else{
		$Arr["cat_list"] =  get_lei_select('0','cat_id[]','cat_id','goods_cat','','','所有分类');
		$Arr["target_cat_list"] =  get_lei_select('0','target_cat[]','cat_id','OtherCat','','','请选择分类');
	}
	$title_url = '';
	$back_url = '';
	foreach($_GET as $key => $val){
		if ($key!='act' && $key!='goods_id'){
			if(is_array($_GET[$key])){
				foreach($_GET[$key] as $row){
					$title_url .= '&'.$key.'[]='.$row;
					$back_url .= '&'.$key.'[]='.$row;
				}
			}else{
				$back_url .= '&'.$key.'='.$val;
				if ($key!='sort_by' && $key!='sort_order')
					$title_url .= '&'.$key.'='.$val;
			}
		}
	}
	$sql = 'select * from eload_activity where type=1 ORDER BY id DESC';
	$Arr['activity_putong'] = $db->arrQuery($sql);
    $sql = 'select * from eload_activity where type=2 ORDER BY id DESC';
	$Arr['activity_guding'] = $db->arrQuery($sql);
	$Arr['search_url'] = $back_url;
	$Arr['activity'] =$activity;
	$Arr['title_url'] = $title_url;
    $Arr['list_type']   =    $_ACT == 'list' ? 'goods' : 'trash';
    $Arr['use_storage'] =  empty($_CFG['use_storage']) ? 0 : 1;
    $goods_list = goods_list($_ACT == 'list' ? 0 : 1, ($_ACT == 'list') ? (($code == '') ? 1 : 0) : -1);
    $Arr['goods_list'] =   $goods_list['goods'];
    $Arr['gifts_arr'] =   read_static_cache('gifts_c_key',2);
	$Arr['attr_list'] =   goods_type_list(0);
    $sort_flag  = sort_flag($goods_list['filter']);
    $Arr[$sort_flag['tag']] = $sort_flag['img'];
	$goods_list['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];
    $Arr['filter'] =       $goods_list['filter'];
    /* 排序标记 */
	$page=new page(array('total'=>$goods_list['record_count'],'perpage'=>$goods_list['page_size']));
	$Arr["pagestr"]  = $page->show();
}

/*------------------------------------------------------ */
//-- 添加新商品 编辑商品
/*------------------------------------------------------ */
elseif ($_ACT == 'add' || $_ACT == 'edit' || $_ACT == 'copy')
{
    if($_ACT == 'add'){
        admin_priv('goods_add'); // 检查权限
    }elseif($_ACT == 'edit'){
        admin_priv('goods_edit'); // 检查权限
    }
	$tag_msg="添加";
    $is_add = $_ACT == 'add'; // 添加还是编辑的标识
    $is_copy = $_ACT == 'copy'; //是否复制
    $code = empty($_GET['extension_code']) ? '' : trim($_GET['extension_code']);
	if($goods_id!=''){
		if ($is_copy){
			$url = "?act=insert&goods_id=0";
		}else{
			$back_url = '';
			foreach($_GET as $key => $val){
				if ($key!='act' && $key!='goods_id'){
					if(is_array($_GET[$key])){
						foreach($_GET[$key] as $row){
							$back_url .= '&'.$key.'[]='.$row;
						}
					}else{
						$back_url .= '&'.$key.'='.$val;
					}
				}
			}
			$url = "?act=update&goods_id=$goods_id".$back_url;
		}
	}else{
		$url = "?act=insert";
	}
    if ($is_add)
    {
        /* 默认值 */
        $last_choose = array(0, 0);
        if (!empty($_COOKIE['WEB']['last_choose']))
        {
            $last_choose = explode('|', $_COOKIE['WEB']['last_choose']);
        }
        $goods = array(
            'goods_id'      => 0,
            'goods_desc'    => '',
            'cat_id'        => $last_choose[0],
            'is_on_sale'    => '0',
            'is_home'    => '0',
            'is_alone_sale' => '1',
            'is_free_shipping' => '1',
            'other_cat'     => array(), // 扩展分类
            'goods_type'    => 0,       // 商品类型
            'shop_price'    => 0,
            'promote_price' => 0,
            'promote_lv' => 0,
            'market_price'  => 0,
            'goods_number'  => $_CFG['default_storage'],
            'warn_number'   => 1,
            'promote_start_date' => local_date('Y-m-d'),
            'promote_end_date'   => local_date('Y-m-d', local_strtotime('+1 month')),
            'goods_weight'  => 0,
			'goods_volume_weight' => 0,
        );
        $img_list = array();
        if ($code != '')
        {
            $goods['goods_number'] = 0;
        }
        /* 组合商品 */
        $group_goods_list = array();
        $sql = "DELETE FROM " . GROUPGOODS .
                " WHERE parent_id = 0  AND admin_id = '".$_SESSION['WebUserInfo']['said']."'";
        $db->query($sql);
    }
    else
    {
        /* 商品信息 */
        $sql = "SELECT * FROM " . GOODS . " WHERE goods_id = '$goods_id'";
        $goods = $db->selectinfo($sql);
        $sql = "SELECT * FROM " . GOODS_STATE . " WHERE goods_id = '$goods_id'";
        $goods_state = $db->selectinfo($sql);
        $goods['is_24h_ship'] = $goods_state['is_24h_ship'];
		$goods['youtube'] = $goods_state['youtube'];
        /* 卡商品复制时, 将其库存置为0*/
        if ($is_copy && $code != '')
        {
            $goods['goods_number'] = 0;
        }
        if (empty($goods) === true)
        {
            /* 默认值 */
            $goods = array(
                'goods_id'      => 0,
                'goods_desc'    => '',
                'cat_id'        => 0,
                'is_on_sale'    => '1',
				'is_home'       => '0',
				'is_alone_sale' => '1',
				'other_cat'     => array(), // 扩展分类
                'goods_type'    => 0,       // 商品类型
                'shop_price'    => 0,
                'promote_price' => 0,
                'promote_lv' => 0,
                'market_price'  => 0,
                'goods_number'  => 1,
                'warn_number'   => 1,
                'promote_start_date' => local_date($GLOBALS['_CFG']['time_format']),
                'promote_end_date'   => local_date($GLOBALS['_CFG']['time_format'], gmstr2tome('+1 month')),
                'goods_weight'  => 0,
                'goods_volume_weight' => 0,
            );
        }

        /* 商品查找属性 */
        if($goods['goods_search_attr'])
        {
        	$goods['goods_search_attr'] = trim($goods['goods_search_attr'],",");
        	$goods['goods_search_attr'] = str_replace("_"," ",$goods['goods_search_attr']);
        	$goods_search_attr = explode(", ,",$goods['goods_search_attr']);
        	$goods['goods_search_attr_array'] = $goods_search_attr;
        }

        /* 根据商品重量的单位重新计算 */
        if ($goods['goods_weight'] > 0)
        {
            $goods['goods_weight_by_unit'] = ($goods['goods_weight'] >= 1) ? $goods['goods_weight'] : ($goods['goods_weight'] / 0.001);
        }

        if($goods['goods_volume_weight'] > 0)
        {
        	$goods['goods_volume_weight_by_unit'] = ($goods['goods_volume_weight'] >= 1) ? $goods['goods_volume_weight'] : ($goods['goods_volume_weight'] / 0.001);
        }

        if (!empty($goods['goods_brief']))
        {
            $goods['goods_brief'] = $goods['goods_brief'];
        }
        if (!empty($goods['keywords']))
        {
            $goods['keywords']    = $goods['keywords'];
        }

        /* 如果不是促销，处理促销日期 */
        if (isset($goods['is_promote']) && $goods['is_promote'] == '0')
        {
            unset($goods['promote_start_date']);
            unset($goods['promote_end_date']);
        }
        else
        {
            $goods['promote_start_date'] = local_date($GLOBALS['_CFG']['time_format'], $goods['promote_start_date']);
            $goods['promote_end_date'] = local_date($GLOBALS['_CFG']['time_format'], $goods['promote_end_date']);
        }

        /* 预售 */
        if ($goods['presale_date_from'] )
        {
            $goods['presale_date_from'] = local_date('Y-m-d', $goods['presale_date_from']);
            $goods['presale_date_to'] = local_date('Y-m-d', $goods['presale_date_to']);
        }else {
        	$goods['presale_date_to'] = 0;
        	$goods['presale_date_from'] = 0;
        }

        /* 如果是复制商品，处理 */
        if ($_ACT == 'copy')
        {
            // 商品信息
            $goods['goods_id'] = 0;
            $goods['goods_sn'] = '';
            $goods['goods_name'] = '';
            $goods['goods_title'] = '';
            $goods['goods_img'] = '';
            $goods['goods_thumb'] = '';
            $goods['original_img'] = '';

            // 配件
            $sql = "DELETE FROM " . GROUPGOODS .
                    " WHERE parent_id = 0  AND admin_id = '".$_SESSION['WebUserInfo']['said']."'";
            $db->query($sql);

            $sql = "SELECT 0 AS parent_id, goods_id, goods_price, '".$_SESSION['WebUserInfo']['said']."' AS admin_id " .
                    "FROM " . GROUPGOODS .
                    " WHERE parent_id = '$_REQUEST[goods_id]' ";
            $res = $db->query($sql);
            while ($row = $db->fetchRow($res))
            {
                $db->autoExecute(GROUPGOODS, $row, 'INSERT');
            }

            // 商品属性
            $sql = "DELETE FROM " . GATTR . " WHERE goods_id = 0";
            $db->query($sql);

            $sql = "SELECT 0 AS goods_id, attr_id, attr_value, attr_price " .
                    "FROM " . GATTR .
                    " WHERE goods_id = '$goods_id' ";
            $res = $db->arrQuery($sql);
            foreach ($res as $row)
            {
                $db->autoExecute(GATTR, addslashes_deep($row), 'INSERT');
            }
        }

        $group_goods_list   = get_group_goods($goods['goods_id']); // 配件

        // 扩展分类
        $other_cat_list = array();
        $sql = "SELECT cat_id FROM " . GOODSCAT . " WHERE goods_id = '$_REQUEST[goods_id]'";
        $goods['other_cat'] = $db->getCol($sql);
        foreach ($goods['other_cat'] as $n => $cat_id)
        {
			$catStr = '';
			$parent_id_str = get_parent_id($cat_id);
			$parent_id_Arr = explode(',',$parent_id_str);
			$parent_id_Arr = array_reverse ($parent_id_Arr); //数组逆序
			foreach($parent_id_Arr as $key => $val){
				if ($val!=''){
					$parent_id = $val;
					$selectid = isset($parent_id_Arr[$key+1])?$parent_id_Arr[$key+1]:$cat_id;
					$catStr .= get_lei_select($val,'other_cat['.($n+1).'][]','','OtherCat',$selectid,($n+1));
				}
			}
			$other_cat_list[$cat_id] = $catStr.'<br><br>';
        }
		$Arr['ext_catnum'] = $goods['other_cat']?count($goods['other_cat']):'1';
        $Arr['other_cat_list'] =  $other_cat_list;
		$Arr['gifts_arr'] =   read_static_cache('gifts_c_key',2);

        /* 商品图片路径 */
        if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 10) && !empty($goods['original_img']))
        {
            $goods['goods_img'] = get_image_path($goods_id, $goods['goods_img']);
            $goods['goods_thumb'] = get_image_path($goods_id, $goods['goods_thumb'], true);
        }

        /* 图片列表 */
        $sql = "SELECT * FROM " . GGALLERY . " WHERE goods_id = '$goods[goods_id]'";
        $img_list = $db->arrQuery($sql);

        /* 格式化相册图片路径 */
        if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 0))
        {
            foreach ($img_list as $key => $gallery_img)
            {
                $gallery_img[$key]['img_url'] = get_image_path($gallery_img['goods_id'], $gallery_img['img_original'], false, 'gallery');
                $gallery_img[$key]['thumb_url'] = get_image_path($gallery_img['goods_id'], $gallery_img['goods_thumb'], true, 'gallery');
            }
        }
        else
        {
            foreach ($img_list as $key => $gallery_img)
            {
                $gallery_img[$key]['thumb_url'] = empty($gallery_img['thumb_url']) ? get_image_path($gallery_img['goods_id'],$gallery_img['img_url']) : get_image_path($gallery_img['goods_id'],$gallery_img['thumb_url']);

                $img_list[$key]['thumb_url'] = empty($gallery_img['thumb_url']) ? get_image_path($gallery_img['goods_id'],$gallery_img['img_url']) : get_image_path($gallery_img['goods_id'],$gallery_img['thumb_url']);
            }
        }
    }

   /* 拆分商品名称样式 */
   $goods_name_style = explode('+', empty($goods['goods_name_style']) ? '+' : $goods['goods_name_style']);
   $goods['groupbuy_start_date'] = !empty($goods['groupbuy_start_date'])?local_date($GLOBALS['_CFG']['time_format'],$goods['groupbuy_start_date']):0;
   $goods['groupbuy_end_date'] = !empty($goods['groupbuy_end_date'])?local_date($GLOBALS['_CFG']['time_format'],$goods['groupbuy_end_date']):0;

    /* 模板赋值 */
    $Arr['lang'] = array('strong' => '加粗', 'em' => '斜体', 'u' => '下划线', 'strike' => '删除线');
    $Arr['code'] =    $code;
    $Arr['goods'] = $goods;
    $Arr['goods_name_color'] = $goods_name_style[0];
    $Arr['goods_name_style'] = $goods_name_style[1];

	/*开始分类*/
	if($is_add){
		$Arr["cat_list"] = get_lei_select('0','cat_id[]','select_cat','goods_cat');
	}else{
		$Arr["cat_list"] = '';
		$parent_id_str = get_parent_id($goods['cat_id']);
		$parent_id_Arr = explode(',',$parent_id_str);
		$parent_id_Arr = array_reverse ($parent_id_Arr); //数组逆序
		foreach($parent_id_Arr as $key => $val){
			if ($val!=''){
				$parent_id = $val;
				$selectid = isset($parent_id_Arr[$key+1])?$parent_id_Arr[$key+1]:$goods['cat_id'];
				$Arr["cat_list"] .=  get_lei_select($parent_id,'cat_id[]','select_cat','goods_cat',$selectid);
			}
		}
	}
	$Arr["pei_jian_cat_list"] =  get_lei_select('0','cat_id2[]','cat_id2','peijian_cat');

	/*结束分类*/
    $Arr['unit_list'] = get_unit_list();
    $Arr['weight_unit'] = $is_add ? '1' : ($goods['goods_weight'] >= 1 ? '1' : '0.001');
    $Arr['weight_unit_volume'] = $is_add ? '1' : ($goods['goods_volume_weight'] >= 1 ? '1' : '0.001');
    $Arr['form_act'] = $is_add ? 'insert' : ($_ACT == 'edit' ? 'update' : 'insert');
    if ($_ACT == 'add' || $_ACT == 'edit'){
        $Arr['is_add'] = true;
    }
    $Arr['img_list'] = $img_list;
    $Arr['goods_type_list'] = goods_type_list($goods['goods_type']);
    $Arr['gd'] = gd_version();
    $Arr['thumb_width'] = $_CFG['thumb_width'];
    $Arr['thumb_height'] = $_CFG['thumb_height'];
    $Arr['goods_attr_html'] = build_attr_html($goods['goods_type'], $goods['goods_id']);
    $volume_price_list = get_volume_price_list($goods_id);
	$price_great  = explode('|',$_CFG['price_grade']);  //加载价格区间
	$grade = '';
	if($goods['cat_id']){
		$fenleiArr = get_zhuijia_price_and_fenlei_bili($goods['cat_id'],$goods['chuhuo_price']);
		$grade = $fenleiArr['bili'];
	}
    $Arr['price_great'] = $grade;
	if (empty($volume_price_list))
    {
        $volume_price_list = array('0'=>array('number'=>'','price'=>''));
    }

	//多语言
	$goods_language_id = $goods['goods_id'];
	foreach($lang as $value) {
		$language = $value['title_e'];
		$sql = "SELECT * FROM " .GOODS. "_". $language ." WHERE goods_id = $goods_language_id";
		$res = $db->selectInfo($sql);
		$goods_language[$language] = $res;
	}
	$Arr['goods_language'] = $goods_language;

    //活动列表
    @$Arr["activity_list1"] = get_join_activity_list($goods['goods_sn'],1);
    @$Arr["activity_list2"] = get_join_activity_list($goods['goods_sn'],2);
    $Arr['volume_price_list'] = $volume_price_list;
    $Arr['group_goods_list'] = $group_goods_list;
	$Arr['tag_msg'] = $tag_msg;
	$Arr['url'] = $url;
	$Arr['cfg'] = $_CFG;
	$_ACT = 'add';
}

// 多语言保存 fangxin 2013/07/02
elseif ($_ACT == 'add_save') {
	$language      = $_POST['language'];
	$goods_id      = $_POST['goods_id'];
	if(empty($goods_id)) {
		echo '请先添加原始语言信息!';
		exit(0);
	}
	$goods_title   = addslashes(stripslashes($_POST['goods_title']));
	$goods_name    = addslashes(stripslashes($_POST['goods_name']));
	$goods_desc    = addslashes(stripslashes($_POST['goods_desc']));
	$keywords      = addslashes(stripslashes($_POST['keywords']));
	$goods_brief   = addslashes(stripslashes($_POST['goods_brief']));
	$seller_note   = addslashes(stripslashes($_POST['seller_note']));
	$sql = "SELECT count(1) record_num FROM " .GOODS. "_". $language ." WHERE goods_id = $goods_id LIMIT 1";
	$res = $db->selectInfo($sql);
	if($res['record_num'] > 0) {
		$sql = "UPDATE " .GOODS. "_". $language ." SET ".
			   "goods_title = '" .str_replace('\\\'\\\'', '\\\'', $goods_title). "', ".
			   "goods_name  = '$goods_name', ".
			   "goods_desc  = '$goods_desc', ".
			   "keywords  = '$keywords', ".
			   "goods_brief  = '$goods_brief', ".
			   "seller_note  = '$seller_note' ".
			   "WHERE goods_id = '$goods_id'";
	} else {
		$sql = "INSERT " .GOODS. "_". $language ." (goods_id, goods_title, goods_name, goods_desc, keywords, goods_brief, seller_note) VALUES ('$goods_id', '" .str_replace('\\\'\\\'', '\\\'', $goods_title). "', '$goods_name', '$goods_desc', '$keywords', '$goods_brief', '$seller_note') ";
	}
	$res = $db->query($sql);
	if($res) {
		echo '操作成功';
	} else {
		echo '操作失败';
	}
	exit(0);
}

/*------------------------------------------------------ */
//-- 插入商品 更新商品
/*------------------------------------------------------ */
elseif ($_ACT == 'insert' || $_ACT == 'update')
{
	require_once('../lib/cls_image.php');
	require_once(ROOT_PATH . 'lib/class.keyword.php');
	$image = new cls_image();
    $code = empty($_GET['extension_code']) ? '' : trim($_GET['extension_code']);
    /* 是否处理缩略图 */
    $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0)? false : true;
    admin_priv('goods_add'); // 检查权限
    /* 检查货号是否重复 */
    if ($_POST['goods_sn'])
    {
        $sql = "SELECT COUNT(*) FROM " . GOODS .
                " WHERE goods_sn = '$_POST[goods_sn]' AND is_delete = 0 AND goods_id <> '$_POST[goods_id]'";
        if ($db->getOne($sql) > 0)
        {
            sys_msg("货号是有重复，请仔细核对！", 1, array(), false);
        }
    }
	$catgory_id = empty($_POST['cat_id']) ? '' : $_POST['cat_id'];
	if (is_array($catgory_id)) {
		$catgory_id = array_filter($catgory_id, create_function('$v', 'return intval($v);'));//最后的下拉框为“请选择”时最后的值为空了，不简单的end()。
	    $catgory_id = end($catgory_id);
	}
	empty($catgory_id) && sys_msg('未选择商品分类，请仔细核对！', 1, array(), false);
	$category_arr = read_static_cache('category_c_key', 2);
    empty($category_arr[$catgory_id]) && sys_msg('商品分类不存在，请仔细核对！', 1, array(), false);
	$category_arr    = $category_arr[$catgory_id];//商品分类
	$pingbi_language = $category_arr['clang'];//屏蔽语言
	$is_login        = $category_arr['is_login'];//是否购买限制
    /* 检查图片：如果有错误，检查尺寸是否超过最大值；否则，检查文件类型 */
	/*
    if (isset($_FILES['goods_img']['error'])) // php 4.2 版本才支持 error
    {
        // 最大上传文件大小
        $php_maxsize = ini_get('upload_max_filesize');
        $htm_maxsize = '2M';

        // 商品图片
        if ($_FILES['goods_img']['error'] == 0)
        {
            if (!$image->check_img_type($_FILES['goods_img']['type']))
            {
                sys_msg('商品图片格式不正确！', 1, array(), false);
            }
        }
        elseif ($_FILES['goods_img']['error'] == 1)
        {
            sys_msg(sprintf("商品图片文件太大了（最大值：%s），无法上传。", $php_maxsize), 1, array(), false);
        }
        elseif ($_FILES['goods_img']['error'] == 2)
        {
            sys_msg(sprintf("商品图片文件太大了（最大值：%s），无法上传。", $htm_maxsize), 1, array(), false);
        }

        // 商品缩略图
        if (isset($_FILES['goods_thumb']))
        {
            if ($_FILES['goods_thumb']['error'] == 0)
            {
                if (!$image->check_img_type($_FILES['goods_thumb']['type']))
                {
                    sys_msg("商品缩略图格式不正确！", 1, array(), false);
                }
            }
            elseif ($_FILES['goods_thumb']['error'] == 1)
            {
                sys_msg(sprintf("商品缩略图文件太大了（最大值：%s），无法上传。", $php_maxsize), 1, array(), false);
            }
            elseif ($_FILES['goods_thumb']['error'] == 2)
            {
                sys_msg(sprintf("商品缩略图文件太大了（最大值：%s），无法上传。", $htm_maxsize), 1, array(), false);
            }
        }

        // 相册图片
        foreach ($_FILES['img_url']['error'] AS $key => $value)
        {
            if ($value == 0)
            {
                if (!$image->check_img_type($_FILES['img_url']['type'][$key]))
                {
                    sys_msg(sprintf("商品相册中第%s个图片格式不正确!", $key + 1), 1, array(), false);
                }
            }
            elseif ($value == 1)
            {
                sys_msg(sprintf("商品相册中第%s个图片文件太大了（最大值：%s），无法上传。", $key + 1, $php_maxsize), 1, array(), false);
            }
            elseif ($_FILES['img_url']['error'] == 2)
            {
                sys_msg(sprintf("商品相册中第%s个图片文件太大了（最大值：%s），无法上传。", $key + 1, $htm_maxsize), 1, array(), false);
            }
        }
    }
	*/

    //插入还是更新的标识
    $is_insert = $_ACT == 'insert';

	/*
    //处理商品图片
    $goods_img        = '';  // 初始化商品图片
    $goods_thumb      = '';  // 初始化商品缩略图
    $original_img     = '';  // 初始化原始图片
    $goods_grid       = '';  // 初始化网格图片
    $old_original_img = '';  // 初始化原始图片旧图

    // 如果上传了商品图片，相应处理
    if ($_FILES['goods_img']['tmp_name'] != '' && $_FILES['goods_img']['tmp_name'] != 'none')
    {
        if ($goods_id > 0)
        {
            //删除原来的图片文件
            $sql = "SELECT goods_thumb, goods_img, original_img " .
                    " FROM " . GOODS .
                    " WHERE goods_id = '$goods_id'";
            $row = $db->selectinfo($sql);
            if ($row['goods_thumb'] != '' && is_file('../' . $row['goods_thumb']))
            {
                @unlink('../' . $row['goods_thumb']);
            }
            if ($row['goods_img'] != '' && is_file('../' . $row['goods_img']))
            {
                @unlink('../' . $row['goods_img']);
            }
            if ($row['original_img'] != '' && is_file('../' . $row['original_img']))
            {
                //先不处理，以防止程序中途出错停止
                //$old_original_img = $row['original_img']; //记录旧图路径
            }
            //清除原来商品图片
            if ($proc_thumb === false)
            {
                get_image_path($goods_id, $row['goods_img'], false, 'goods', true);
                get_image_path($goods_id, $row['goods_thumb'], true, 'goods', true);
            }
        }

        $original_img   = $image->upload_image($_FILES['goods_img']); // 原始图片
        if ($original_img === false)
        {
            sys_msg($image->error_msg(), 1, array(), false);
        }
        $goods_img      = $original_img;   // 商品图片

        //复制一份相册图片
        //添加判断是否自动生成相册图片
        if ($_CFG['auto_generate_gallery'])
        {
            $img        = $original_img;   // 相册图片
            $pos        = strpos(basename($img), '.');
            $newname    = dirname($img) . '/' . $image->random_filename() . substr(basename($img), $pos);
            if (!copy('../' . $img, '../' . $newname))
            {
                sys_msg('fail to copy file: ' . realpath('../' . $img), 1, array(), false);
            }
            $img        = $newname;

            $gallery_img    = $img;
            $gallery_thumb  = $img;
        }


		// 是否上传商品缩略图
		if (isset($_FILES['goods_thumb']) && $_FILES['goods_thumb']['tmp_name'] != '' &&
			isset($_FILES['goods_thumb']['tmp_name']) &&$_FILES['goods_thumb']['tmp_name'] != 'none')
		{
			// 上传了，直接使用，原始大小
			$goods_thumb = $image->upload_image($_FILES['goods_thumb']);
			if ($goods_thumb === false)
			{
				sys_msg($image->error_msg(), 1, array(), false);
			}
		}else{
			// 未上传，如果自动选择生成，且上传了商品图片，生成所略图
			if ($proc_thumb && isset($_POST['auto_thumb']) && !empty($original_img))
			{
				// 如果设置缩略图大小不为0，生成缩略图
				if ($_CFG['thumb_width'] != 0 || $_CFG['thumb_height'] != 0)
				{
					$goods_thumb = $image->make_thumb('../' . $original_img, $GLOBALS['_CFG']['thumb_width'],  $GLOBALS['_CFG']['thumb_height']);
					if ($goods_thumb === false)
					{
						sys_msg($image->error_msg(), 1, array(), false);
					}
				}
				else
				{
					$goods_thumb = $original_img;
				}
			}

		}

		//中图
		if (isset($_FILES['goods_grid']) && $_FILES['goods_grid']['tmp_name'] != '' && isset($_FILES['goods_grid']['tmp_name']) &&$_FILES['goods_grid']['tmp_name'] != 'none')
		{
			// 上传了，直接使用，原始大小
			$goods_grid  = $image->upload_image($_FILES['goods_grid']); //网格图
			if ($goods_grid === false)
			{
				sys_msg($image->error_msg(), 1, array(), false);
			}
		}else{
			// 未上传，如果自动选择生成，且上传了商品图片，生成所略图
			if ($proc_thumb && isset($_POST['auto_thumb']) && !empty($original_img))
			{
				// 如果设置缩略图大小不为0，生成缩略图
				if ($_CFG['webgrid_width'] != 0 || $_CFG['webgrid_height'] != 0)
				{
					$goods_grid = $image->make_thumb('../' . $original_img, $GLOBALS['_CFG']['webgrid_width'],  $GLOBALS['_CFG']['webgrid_height']);
					if ($goods_grid === false)
					{
						sys_msg($image->error_msg(), 1, array(), false);
					}
				}
				else
				{
					$goods_grid = $original_img;
				}
			}
		}

        // 如果系统支持GD，缩放商品图片，且给商品图片和相册图片加水印
        if ($proc_thumb && $image->gd_version() > 0 && $image->check_img_function($_FILES['goods_img']['type']))
        {
            // 如果设置大小不为0，缩放图片
            if ($_CFG['image_width'] != 0 || $_CFG['image_height'] != 0)
            {
                $goods_img = $image->make_thumb('../'. $goods_img , $GLOBALS['_CFG']['image_width'],  $GLOBALS['_CFG']['image_height']);
                if ($goods_img === false)
              {
                    sys_msg($image->error_msg(), 1, array(), false);
                }
            }

            //添加判断是否自动生成相册图片
            if ($_CFG['auto_generate_gallery'])
            {
                $newname    = dirname($img) . '/' . $image->random_filename() . substr(basename($img), $pos);
                if (!copy('../' . $img, '../' . $newname))
                {
                    sys_msg('fail to copy file: ' . realpath('../' . $img), 1, array(), false);
                }
                $gallery_img        = $newname;
            }

            // 加水印
            if (!empty($GLOBALS['_CFG']['watermark']))
            {
                if ($image->add_watermark('../'.$original_img,'',$GLOBALS['_CFG']['watermark'],'', $GLOBALS['_CFG']['watermark_alpha']) === false)
                {
                    sys_msg($image->error_msg(), 1, array(), false);
                }
                //添加判断是否自动生成相册图片
                if ($_CFG['auto_generate_gallery'])
                {
                    if ($image->add_watermark('../'. $gallery_img,'',$GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false)
                    {
                        sys_msg($image->error_msg(), 1, array(), false);
                    }
                }
            }

            // 相册缩略图
            //添加判断是否自动生成相册图片
            if ($_CFG['auto_generate_gallery'])
            {
                if ($_CFG['thumb_width'] != 0 || $_CFG['thumb_height'] != 0)
                {
                    $gallery_thumb = $image->make_thumb('../' . $img, $GLOBALS['_CFG']['thumb_width'],  $GLOBALS['_CFG']['thumb_height']);
                    if ($gallery_thumb === false)
                    {
                        sys_msg($image->error_msg(), 1, array(), false);
                    }
                }
            }
        }
    }
	*/


    /* 如果没有输入商品货号则自动生成一个商品货号 */
    if (empty($_POST['goods_sn']))
    {
        $max_id     = $is_insert ? $db->getOne("SELECT MAX(goods_id) + 1 FROM ".GOODS) : $goods_id;
        $goods_sn   = generate_goods_sn($max_id);
    }
    else
    {
        $goods_sn   = $_POST['goods_sn'];
    }

    /* 处理商品数据 */
    $shop_price = !empty($_POST['shop_price']) ? floatval($_POST['shop_price']) : 0;
	if($shop_price<0.01)	sys_msg('产品价格有误，请检查.');
	$shop_price_start = empty($_POST['shop_price_start'])?'':$_POST['shop_price_start'];
	$shop_price < $shop_price_start&&sys_msg("销售价格不能小于原销售价格！",1, array(), false);
    //配件销售价格 by mashanling on 2012-07-30 09:48:42
    $peijian_price = isset($_POST['peijian_price']) ? floatval($_POST['peijian_price']) : 0.00;
    $is_24h_ship = !empty($_POST['is_24h_ship']) ? $_POST['is_24h_ship'] : 0;
	$url_title = title_to_url($_POST['goods_name']);
	$url_title_temp = $url_title;
	$what_is_worng = '';
    $gifts_id = !empty($_POST['gifts_id']) ? $_POST['gifts_id'] : 0;
    $market_price = !empty($_POST['market_price']) ? $_POST['market_price'] : 0;
	$discount_rate = !empty($_POST['discount_rate']) ? $_POST['discount_rate'] : 0;
	if($discount_rate > 0 && $shop_price > 0) {
		if($_POST['promote_price'] > 0) {
			$discount_price = $_POST['promote_price'];
		} else {
			$discount_price = $_POST['shop_price'];
		}
		$market_price = price_format(($discount_price * 100)/(100-$discount_rate), 2);
	}
    $promote_lv = !empty($_POST['promote_lv']) ? floatval($_POST['promote_lv'] ) : 0;
    $promote_price = !empty($_POST['promote_price']) ? floatval($_POST['promote_price'] ) : 0;
	$is_promote = !empty($_POST['is_promote']) ? floatval($_POST['is_promote'] ) : 0;
    $promote_start_date = ($is_promote && !empty($_POST['promote_start_date'])) ? local_strtotime($_POST['promote_start_date']) : 0;
    $promote_end_date = ($is_promote && !empty($_POST['promote_end_date'])) ? local_strtotime($_POST['promote_end_date']) : 0;
    $presale_date_from = !empty($_POST['presale_date_from']) ? local_strtotime($_POST['presale_date_from']) : 0;
    $presale_date_to = (!empty($_POST['presale_date_to'])) ? local_strtotime($_POST['presale_date_to']) : 0;
	$_POST['groupbuy_people_first_number'] =1;
    $is_groupbuy = empty($_POST['is_groupbuy']) ? 0 : 1;
    $groupbuy_start_date = ($is_groupbuy && !empty($_POST['groupbuy_start_date'])) ? local_strtotime($_POST['groupbuy_start_date']) : 0;
    $groupbuy_end_date = ($is_groupbuy && !empty($_POST['groupbuy_end_date'])) ? local_strtotime($_POST['groupbuy_end_date']) : 0;
    $groupbuy_price = !empty($_POST['groupbuy_price']) ? floatval($_POST['groupbuy_price'] ) : 0;
    $groupbuy_max_number = !empty($_POST['groupbuy_max_number']) ? floatval($_POST['groupbuy_max_number'] ) : 0;
    $groupbuy_final_price = !empty($_POST['groupbuy_price']) ? floatval($_POST['groupbuy_price'] ) : 0;
    $groupbuy_people_first_number = !empty($_POST['groupbuy_people_first_number']) ? intval($_POST['groupbuy_people_first_number']) : 0;
    $groupbuy_people_final_number = !empty($_POST['groupbuy_people_first_number']) ? intval($_POST['groupbuy_people_first_number']) : 0;
    $groupbuy_ad_desc = !empty($_POST['groupbuy_ad_desc']) ? trim($_POST['groupbuy_ad_desc']) : '';
	if($is_groupbuy){
		if($groupbuy_end_date <=  $groupbuy_start_date) $what_is_worng .= '团购时间设置不正确，请仔细检查。<br>';
		if($groupbuy_price >=$shop_price ) $what_is_worng .= '团购价必须小于店铺价，请仔细检查。<br>';
		if(!$groupbuy_price) $what_is_worng .= '团购价格不能为零。<br>';
		if(!$groupbuy_max_number)$what_is_worng .= '请设置每人限制购买的数量。<br>';
		//if(!$groupbuy_people_first_number || !$groupbuy_people_final_number || ($groupbuy_people_first_number > $groupbuy_people_final_number)) $what_is_worng .= '团购人数设置不正确，请仔细检查。<br>';
		if ($what_is_worng)
		sys_msg($what_is_worng, 1, array(), false);
	}
	if($is_promote){
		if($promote_price>=$shop_price)$what_is_worng .= '促销价必须小于店铺价，请仔细检查。<br>';
	}
	if(!empty($what_is_worng))sys_msg($what_is_worng, 1, array(), false);
    $goods_weight = !empty($_POST['goods_weight']) ? $_POST['goods_weight'] : 0;
    $goods_volume_weight = !empty($_POST['goods_volume_weight']) ? $_POST['goods_volume_weight'] : 0;		//体积重量
    $is_free_shipping = !empty($_POST['is_free_shipping']) ? intval($_POST['is_free_shipping']):0;
    $is_best = !empty($_POST['is_best']) ? intval($_POST['is_best']) : 0;
    $is_new = !empty($_POST['is_new']) ? intval($_POST['is_new']) : 0;
    $is_hot = !empty($_POST['is_hot']) ? intval($_POST['is_hot']) : 0;
    $is_on_sale = !empty($_POST['is_on_sale']) ? intval($_POST['is_on_sale'])  : 0;
    if($is_on_sale==1)//上架检查图片是否完整
    {
        $curl_url = 'http://www.faout.com/code/api.php';
        $curl_data = "act=check_queue&goods_id_str=$goods_id&website=A";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $curl_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
        $contents = curl_exec($ch);
        curl_close($ch);
        if($contents)
        {
            $is_on_sale=0;
            $shangjia_tishi=", 图片不完整，禁止上架。";
        }
    }
    $is_home = !empty($_POST['is_home']) ? intval($_POST['is_home'])  : 0;
    $is_alone_sale = !empty($_POST['is_alone_sale']) ? intval($_POST['is_alone_sale'])  : 0;
    $is_direct_sale_off  = !empty($_POST['is_direct_sale_off']) ? intval($_POST['is_direct_sale_off'])  : 0;  //直销－打折
   // $is_not_update = !empty($_POST['is_not_update']) ? intval($_POST['is_not_update'])  : 0;  //为1时，从库同步的时候不更新该产品
    $goods_number = isset($_POST['goods_number']) ? $_POST['goods_number'] : 0;
    $warn_number = isset($_POST['warn_number']) ? $_POST['warn_number'] : 0;
    $goods_type = isset($_POST['goods_type']) ? $_POST['goods_type'] : 0;
    $goods_name_style = $_POST['goods_name_color'] . '+' . $_POST['goods_name_style'];
	/*
    $goods_img = (empty($goods_img) && !empty($_POST['goods_img_url']) && goods_parse_url($_POST['goods_img_url'])) ? htmlspecialchars(trim($_POST['goods_img_url'])) : $goods_img;
    $goods_thumb = (empty($goods_thumb) && !empty($_POST['goods_thumb_url']) && goods_parse_url($_POST['goods_thumb_url'])) ? htmlspecialchars(trim($_POST['goods_thumb_url'])) : $goods_thumb;
    $goods_grid = (empty($goods_grid) && !empty($_POST['goods_grid']) && goods_parse_url($_POST['goods_grid'])) ? htmlspecialchars(trim($_POST['goods_grid'])) : $goods_grid;
    $goods_thumb = (empty($goods_thumb) && isset($_POST['auto_thumb']))? $goods_img : $goods_thumb;
    $goods_grid = (empty($goods_grid) && isset($_POST['auto_thumb']))? $goods_img : $goods_grid;
	*/
	$goods_img = '';
	$goods_thumb = '';
	$goods_grid = '';
    $similar_goods = !empty($_POST['similar_goods']) ? trim($_POST['similar_goods']) : '';
    //活动
    /*
    $activity_str="";
    $activity_arr= empty($_POST['activity'])?'':$_POST['activity'];
    $activity_num = count($activity_arr);
    for( $i = 0; $i < $activity_num; $i ++ )
    {
        if(!empty($activity_arr[$i]))$activity_str.=",".$activity_arr[$i];
    }
    if(!empty($activity_str))$activity_str.=",";
	*/
	$Is_Mod_Desc = '';
	$Modify_What = '';

    /* 入库 */
    if ($is_insert)
    {
	$sql = "INSERT INTO " . GOODS . " (gifts_id,peijian_price,is_login,clang,is_direct_sale_off,goods_name,goods_title, goods_name_style, goods_sn, " .
			"cat_id,  shop_price, market_price, is_promote, promote_price, promote_lv, " .
			"promote_start_date, promote_end_date, goods_img, goods_thumb, original_img, keywords, goods_brief, " .
			"seller_note, goods_weight, goods_number, warn_number,  is_free_shipping, is_best, is_new, is_hot, " .
			"is_on_sale,is_home,is_alone_sale, goods_desc, add_time, last_update, goods_type,add_user,goods_grid,is_groupbuy,groupbuy_price,groupbuy_final_price,groupbuy_people_first_number,groupbuy_people_final_number,groupbuy_start_date,groupbuy_end_date,groupbuy_ad_desc,activity_list,groupbuy_max_number,goods_volume_weight,similar_goods)" .
		"VALUES (gifts_id,{$peijian_price},{$is_login},'{$pingbi_language}',$is_direct_sale_off,'$_POST[goods_name]','$_POST[goods_title]', '$goods_name_style', '$goods_sn', '$catgory_id', " .
			" '$shop_price', '$market_price', '$is_promote','$promote_price','$promote_lv', ".
			"'$promote_start_date', '$promote_end_date', '$goods_img', '$goods_thumb', '$original_img', ".
			"'$_POST[keywords]', '$_POST[goods_brief]', '$_POST[seller_note]', '$goods_weight', '$goods_number',".
			" '$warn_number',  '$is_free_shipping', '$is_best', '$is_new', '$is_hot', '$is_on_sale','$is_home','$is_alone_sale', ".
			" '$_POST[goods_desc]', '" . gmtime() . "', '". gmtime() ."', '$goods_type','".$_SESSION["WebUserInfo"]["sa_user"]."','$goods_grid','$is_groupbuy','$groupbuy_price','$groupbuy_final_price','$groupbuy_people_first_number','$groupbuy_people_final_number','$groupbuy_start_date','$groupbuy_end_date','$groupbuy_ad_desc','$activity_str','$groupbuy_max_number',$goods_volume_weight,'$similar_goods')";
    }
    else
    {
        /* 如果有上传图片，删除原来的商品图 */
        $sql = "SELECT * FROM " . GOODS . " WHERE goods_id = '$goods_id'";
        $row = $db->selectinfo($sql);
		$Modify_What = '';
		//团购日志
		if ($row['is_groupbuy'] != $is_groupbuy) $Modify_What .= '，是否团购'.$row['is_groupbuy'].'改成了'.$is_groupbuy;
		if ($row['groupbuy_price'] != $groupbuy_price) $Modify_What .= '，团购价'.$row['groupbuy_price'].'改成了'.$groupbuy_price;
		if ($row['groupbuy_ad_desc'] != $groupbuy_ad_desc) $Modify_What .= '，团购广告标语'.$row['groupbuy_ad_desc'].'改成了'.$groupbuy_ad_desc;


		//if ($row['groupbuy_final_price'] != $groupbuy_final_price) $Modify_What .= '，最终团购价'.$row['groupbuy_final_price'].'改成了'.$groupbuy_final_price;
		if ($row['groupbuy_start_date'] != $groupbuy_start_date) $Modify_What .= '，团购开始日期'.local_date($GLOBALS['_CFG']['time_format'], $row['groupbuy_start_date']).'改成了'.local_date($GLOBALS['_CFG']['time_format'], $groupbuy_start_date);
		if ($row['groupbuy_end_date'] != $groupbuy_end_date) $Modify_What .= '，团购结束日期'.local_date($GLOBALS['_CFG']['time_format'], $row['groupbuy_end_date']).'改成了'.local_date($GLOBALS['_CFG']['time_format'], $groupbuy_end_date);
		//if ($row['groupbuy_people_first_number'] != $groupbuy_people_first_number) $Modify_What .= '，第一团购价所需要人数'.$row['groupbuy_people_first_number'].'改成了'.$groupbuy_people_first_number;
		//if ($row['groupbuy_people_final_number'] != $groupbuy_people_final_number) $Modify_What .= '，最终团购价所需要人数'.$row['groupbuy_people_final_number'].'改成了'.$groupbuy_people_final_number;

        //配件销售价by mashanling on 15:25 2012-07-30
        if ($row['peijian_price'] != $peijian_price) $Modify_What .= '，' . (isset($_POST['edit_peijian_price']) ? '所有' : '') . '配件销售' . '价格'.$row['peijian_price'].'改成了'.$peijian_price;
		if ($row['goods_title'] != $_POST['goods_title']) $Modify_What .= '，标题'.$row['goods_title'].'改成了'.$_POST['goods_title'];
		if ($row['goods_name'] != $_POST['goods_name']) $Modify_What .= '，名称'.$row['goods_name'].'改成了'.$_POST['goods_name'];
		if ($row['cat_id'] != $catgory_id) $Modify_What .= '，分类'.$row['cat_id'].'改成了'.$catgory_id;
		if ($row['shop_price'] != $shop_price) $Modify_What .= '，价格'.$row['shop_price'].'改成了'.$shop_price;
		if ($row['discount_rate'] != $discount_rate) $Modify_What .= '，折扣率'.$row['discount_rate'].'改成了'.$discount_rate;
		if ($row['is_promote'] != $is_promote) $Modify_What .= '，促销'.$row['is_promote'].'改成了'.$is_promote;
		if ($row['promote_lv'] != $promote_lv) $Modify_What .= '，促销利润率'.$row['promote_lv'].'改成了'.$promote_lv;
		if ($row['promote_price'] != $promote_price) $Modify_What .= '，促销价格'.$row['promote_price'].'改成了'.$promote_price;
		if ($row['promote_start_date'] != $promote_start_date) $Modify_What .= '，促销开始时间'.local_date($GLOBALS['_CFG']['time_format'],$row['promote_start_date']).'改成了'.local_date($GLOBALS['_CFG']['time_format'],$promote_start_date);
		if ($row['promote_end_date'] != $promote_end_date) $Modify_What .= '，促销结束时间'.local_date($GLOBALS['_CFG']['time_format'],$row['promote_end_date']).'改成了'.local_date($GLOBALS['_CFG']['time_format'],$promote_end_date);
		if ($row['presale_date_from'] != $presale_date_from) $Modify_What .= '，预售交货开始时间'.local_date($GLOBALS['_CFG']['time_format'],$row['presale_date_from']).'改成了'.local_date($GLOBALS['_CFG']['time_format'],$presale_date_from);
		if ($row['presale_date_to'] != $presale_date_to) $Modify_What .= '，预售交货结束时间'.local_date($GLOBALS['_CFG']['time_format'],$row['$presale_date_to']).'改成了'.local_date($GLOBALS['_CFG']['time_format'],$promote_end_date);
		if ($row['goods_weight'] != $goods_weight) $Modify_What .= '，重量'.$row['goods_weight'].'改成了'.$goods_weight;
		if ($row['goods_volume_weight'] != $goods_volume_weight) $Modify_What .= '，重量'.$row['goods_volume_weight'].'改成了'.$goods_volume_weight;
		if ($row['goods_number'] != $goods_number) $Modify_What .= '，数量'.$row['goods_number'].'改成了'.$goods_number;
		if ($row['is_free_shipping'] != $is_free_shipping) $Modify_What .= '，免邮'.$row['is_free_shipping'].'改成了'.$is_free_shipping;
		if ($row['is_best'] != $is_best) $Modify_What .= '，推荐'.$row['is_best'].'改成了'.$is_best;
		if ($row['is_new'] != $is_new) $Modify_What .= '，新品'.$row['is_new'].'改成了'.$is_new;
		if ($row['is_hot'] != $is_hot) $Modify_What .= '，热卖'.$row['is_hot'].'改成了'.$is_hot;
		if ($row['is_on_sale'] != $is_on_sale) $Modify_What .= '，上下架'.$row['is_on_sale'].'改成了'.$is_on_sale;
		if ($row['is_home'] != $is_home) $Modify_What .= '，首页显示'.$row['is_home'].'改成了'.$is_home;
		if ($row['is_direct_sale_off'] != $is_direct_sale_off) $Modify_What .= '，促销-打折'.$row['is_direct_sale_off'].'改成了'.$is_direct_sale_off;
		//if ($row['is_not_update'] != $is_not_update) $Modify_What .= '，不从产品库更新该产品'.$row['is_not_update'].'改成了'.$is_not_update;
		if ($row['is_alone_sale'] != $is_alone_sale) $Modify_What .= '，单独销售'.$row['is_alone_sale'].'改成了'.$is_alone_sale;
		if ($row['goods_type'] != $goods_type) $Modify_What .= '，商品类型'.$row['goods_type'].'改成了'.$goods_type;
		if ($row['gifts_id'] != $gifts_id){
			$gifts_name = $db->getOne("select gifts_name from ".GIFTS." where gifts_id = $gifts_id");
			if(empty($gifts_name))$gifts_name="非赠品";
			$Modify_What .= '，赠品属性：改成了 '.$gifts_name;
			$db->query("update ".CART." set gifts_id=$gifts_id where goods_id =$goods_id");
		}
		$arr_state['is_24h_ship'] =$is_24h_ship;
		$db->autoExecute(GOODS_STATE,$arr_state,'UPDATE',"goods_id=$goods_id");
        if ($proc_thumb && $goods_img && $row['goods_img'] && !goods_parse_url($row['goods_img']))
        {
            @unlink(ROOT_PATH . $row['goods_img']);
            @unlink(ROOT_PATH . $row['original_img']);
        }

        if ($proc_thumb && $goods_thumb && $row['goods_thumb'] && !goods_parse_url($row['goods_thumb']))
        {
            @unlink(ROOT_PATH . $row['goods_thumb']);
        }

		//修改本店售价，促销价 fangxin 2013/10/08
		$shop_price = format_price($shop_price);
		$promote_price = format_price($promote_price);

        $sql = "UPDATE " . GOODS . " SET " .
                "goods_name = '$_POST[goods_name]', " .
                "goods_title = '$_POST[goods_title]', " .
                "goods_name_style = '$goods_name_style', " .
                "goods_sn = '$goods_sn', " .
                "cat_id = '$catgory_id', " .
                "shop_price = '$shop_price', " .
				"discount_rate = '$discount_rate', " .
                "peijian_price = {$peijian_price}, " . //配件销售价 by mashanling on 2012-07-30 09:50:08
                "market_price = '$market_price', " .
                "update_user = '".$_SESSION["WebUserInfo"]["sa_user"]."', " .
                "is_promote = '$is_promote', " .
                "promote_price = '$promote_price', " .
                "gifts_id = '$gifts_id', " .
                "promote_lv = '$promote_lv', " .
                //"url_title = '$url_title', " .
                "promote_start_date = '$promote_start_date', " .
                "promote_end_date = '$promote_end_date', ".
                "presale_date_from = '$presale_date_from', ".
                "presale_date_to = '$presale_date_to', ";


        /* 如果有上传图片，需要更新数据库 */
        if ($goods_img)
        {
            $sql .= "goods_img = '$goods_img', original_img = '$original_img', ";
        }
        if ($goods_thumb)
        {
            $sql .= "goods_thumb = '$goods_thumb', ";
            $sql .= "goods_grid = '$goods_grid', ";
        }
        if ($code != '')
        {
            $sql .= "is_real=0, extension_code='$code', ";
        }

		//团购产品
		$sql .= " groupbuy_max_number='$groupbuy_max_number',is_groupbuy = '$is_groupbuy',groupbuy_price = '$groupbuy_price',groupbuy_final_price = '$groupbuy_final_price',groupbuy_people_first_number = '$groupbuy_people_first_number',groupbuy_people_final_number = '$groupbuy_people_final_number',groupbuy_start_date = '$groupbuy_start_date',groupbuy_end_date = '$groupbuy_end_date' , groupbuy_ad_desc = '$groupbuy_ad_desc' , ";

		if ($groupbuy_end_date > gmtime())
		$sql .= "  is_emailed = 0		, ";
        $sql .= "keywords = '$_POST[keywords]', " .
                "goods_brief = '$_POST[goods_brief]', " .
                "seller_note = '$_POST[seller_note]', " .
                "goods_weight = '$goods_weight'," .
                "goods_volume_weight = '$goods_volume_weight', " .
                "goods_number = '$goods_number', " .
                "warn_number = '$warn_number', " .
                "is_best = '$is_best', " .
                "is_free_shipping = '$is_free_shipping', " .
                "is_new = '$is_new', " .
                "is_hot = '$is_hot', " .
                "is_on_sale = '$is_on_sale', " .
                "is_home = '$is_home', " .
                "is_alone_sale = '$is_alone_sale', " .
                "is_direct_sale_off  = '$is_direct_sale_off', " .
               //"is_not_update   = '$is_not_update', " .
                "goods_desc = '$_POST[goods_desc]', " .
                "last_update = '". gmtime() ."', ".
                "goods_type = '$goods_type', " .
                "similar_goods='$similar_goods',".
                "is_login={$is_login},clang='{$pingbi_language}',similar_goods='$similar_goods'" .
                "WHERE goods_id = '$goods_id' LIMIT 1";
    }
    $db->query($sql);

    //修改同款的SEO描述
    if(isset($_POST['seller_note_sames'])&&!$is_insert){
		$seller_goods_sn = substr($row['goods_sn'], 0, 7);
        $sql = "UPDATE ".GOODS." SET seller_note='$_POST[seller_note]' WHERE goods_sn like '". $seller_goods_sn ."%'";
        $db->query($sql);
    }

    /* 商品编号 */
    $goods_id = $is_insert ? $db->insertId() : $goods_id;
    if($is_insert){
    	$db->insert(GOODS_STATE,'goods_id',$goods_id);  //产品状态表插入
    }
	$youtube	 = $_POST['youtube'];
	if($is_on_sale == 1){ //更新上架时间
            $db->query("UPDATE ".GOODS_STATE." SET sale_time = '".gmtime()."',youtube = '{$youtube}' WHERE goods_id = '".$goods_id."'");
    }
    /* 记录日志 */
	$actStr = $is_insert?_ADDSTRING_:_EDITSTRING_;
	admin_log('',$actStr , '商品'.$goods_sn.$Modify_What);

    /* 处理属性 */
    if (isset($_POST['attr_id_list']) && isset($_POST['attr_value_list']))
    {
        // 取得原有的属性值
        $goods_attr_list = array();
        $keywords_arr = explode(",", $_POST['keywords']);
        $keywords_arr = array_flip($keywords_arr);
        if (isset($keywords_arr['']))
        {
            unset($keywords_arr['']);
        }
        $sql = "SELECT attr_id, attr_index FROM " . ATTR . " WHERE cat_id = '$goods_type'";
        $attr_res = $db->query($sql);
        $attr_list = array();
        while ($row = $db->fetchRow($attr_res))
        {
            $attr_list[$row['attr_id']] = $row['attr_index'];
        }

        $sql = "SELECT * FROM " . GATTR . " WHERE goods_id = '$goods_id'";

        $res = $db->query($sql);

        while ($row = $db->fetchRow($res))
        {
            $goods_attr_list[$row['attr_id']][$row['attr_value']] = array('sign' => 'delete', 'goods_attr_id' => $row['goods_attr_id']);
        }


        // 循环现有的，根据原有的做相应处理
        foreach ($_POST['attr_id_list'] AS $key => $attr_id)
        {
            $attr_value = $_POST['attr_value_list'][$key];
            $attr_price = $_POST['attr_price_list'][$key];

            if (!empty($attr_value))
            {

                if (isset($goods_attr_list[$attr_id][$attr_value]))
                {
                    // 如果原来有，标记为更新
                    $goods_attr_list[$attr_id][$attr_value]['sign'] = 'update';
                    $goods_attr_list[$attr_id][$attr_value]['attr_price'] = $attr_price;
                }
                else
                {
                    // 如果原来没有，标记为新增
                    $goods_attr_list[$attr_id][$attr_value]['sign'] = 'insert';
                    $goods_attr_list[$attr_id][$attr_value]['attr_price'] = $attr_price;
                }

                $val_arr = explode(',', $attr_value);

                foreach ($val_arr AS $k => $v)
                {
                    if (!isset($keywords_arr[$v]) && !empty($attr_list[$attr_id])&&$attr_list[$attr_id] == "1")
                    {
                        $keywords_arr[$v] = $v;
                    }
                }
            }
        }
        $keywords = join(',', array_flip($keywords_arr));
        /* 插入、更新、删除数据 */
        foreach ($goods_attr_list as $attr_id => $attr_value_list)
        {
            foreach ($attr_value_list as $attr_value => $info)
            {
                if ($info['sign'] == 'insert')
                {
                    $sql = "INSERT INTO " .GATTR. " (attr_id, goods_id, attr_value, attr_price)".
                            "VALUES ('$attr_id', '$goods_id', '$attr_value', '$info[attr_price]')";
                }
                elseif ($info['sign'] == 'update')
                {
                    $sql = "UPDATE " .GATTR. " SET attr_price = '$info[attr_price]' WHERE goods_attr_id = '$info[goods_attr_id]' LIMIT 1";
                }
                else
                {
                    $sql = "DELETE FROM " .GATTR. " WHERE goods_attr_id = '$info[goods_attr_id]' LIMIT 1";
                }
                $db->query($sql);
            }
        }
    }

    /* 处理会员价格 */
    if (isset($_POST['user_rank']) && isset($_POST['user_price']))
    {
        handle_member_price($goods_id, $_POST['user_rank'], $_POST['user_price']);
    }

    /* 处理优惠价格 */
    if (isset($_POST['volume_number']) && isset($_POST['volume_price']))
    {
        $temp_num = array_count_values($_POST['volume_number']);
        foreach($temp_num as $v)
        {
            if ($v > 1)
            {
                sys_msg("优惠数量重复！", 1, array(), false);
                break;
            }
        }
        $volume_price = $_POST['volume_price'];
		$volume_price[0] = format_price($volume_price[0]); //修改阶梯价，只修改价梯为1价格 fangxin 2013/10/08
		if(!empty($volume_price[0])) {
        	handle_volume_price($goods_id, $_POST['volume_number'], $volume_price);
		}
    }

    if ($is_insert)
    {
        /* 处理组合商品 */
        handle_group_goods($goods_id);
    }

	/*
    //重新格式化图片名称
    $original_img = reformat_image_name('goods', $goods_id, $original_img, 'source');
    $goods_img = reformat_image_name('goods_mid', $goods_id, $goods_img, 'goods');
    $goods_thumb = reformat_image_name('goods_thumb', $goods_id, $goods_thumb, 'thumb');
    $goods_grid = reformat_image_name('grid_thumb', $goods_id, $goods_grid, 'grid');


    if ($goods_img !== false)
    {
        $db->query("UPDATE " . GOODS . " SET goods_img = '$goods_img' WHERE goods_id='$goods_id'");
    }

    if ($original_img !== false)
    {
        $db->query("UPDATE " . GOODS . " SET original_img = '$original_img' WHERE goods_id='$goods_id'");
    }

    if ($goods_thumb !== false)
    {
        $db->query("UPDATE " . GOODS . " SET goods_thumb = '$goods_thumb' WHERE goods_id='$goods_id'");
    }
    if ($goods_grid !== false)
    {
        $db->query("UPDATE " . GOODS . " SET goods_grid = '$goods_grid' WHERE goods_id='$goods_id'");
    }

    //如果有图片，把商品图片加入图片相册
    if (isset($img))
    {
        //重新格式化图片名称
        $img = reformat_image_name('gallery', $goods_id, $img, 'source');
        $gallery_img = reformat_image_name('gallery', $goods_id, $gallery_img, 'goods');
        $gallery_thumb = reformat_image_name('gallery_thumb', $goods_id, $gallery_thumb, 'thumb');
        $sql = "INSERT INTO " . GGALLERY . " (goods_id, img_url, img_desc, thumb_url, img_original) " .
                "VALUES ('$goods_id', '$gallery_img', '', '$gallery_thumb', '$img')";
        $db->query($sql);
    }

    //处理相册图片
    handle_gallery_image($goods_id, $_FILES['img_url'], $_POST['img_desc']);

    /编辑时处理相册图片描述
    if (!$is_insert && isset($_POST['old_img_desc']))
    {
        foreach ($_POST['old_img_desc'] AS $img_id => $img_desc)
        {
            $sql = "UPDATE " . GGALLERY . " SET img_desc = '$img_desc' WHERE img_id = '$img_id' LIMIT 1";
            $db->query($sql);
        }
    }
	*/
    /* 处理扩展分类 */
    if (isset($_POST['other_cat']))
    {
		$cat_Arr = array();
		foreach($_POST['other_cat'] as $k => $val){
			 foreach($val as $a => $b){
				 if(empty($_POST['other_cat'][$k][$a]))  unset($_POST['other_cat'][$k][$a]);
			 }
			 if(empty($_POST['other_cat'][$k])) unset($_POST['other_cat'][$k]);

			 if(!empty($_POST['other_cat'][$k]))
			   $cat_Arr[] =  end($_POST['other_cat'][$k]);
		}

        handle_other_cat($goods_id, array_unique($cat_Arr));
    }

    /* 不保留商品原图的时候删除原图 */
    if ($proc_thumb && !$_CFG['retain_original_img'] && !empty($original_img))
    {
        $db->query("UPDATE " . GOODS . " SET original_img='' WHERE `goods_id`='{$goods_id}'");
        @unlink('../' . $original_img);
    }

    /* 记录上一次选择的分类 */
    setcookie('WEB[last_choose]', $catgory_id, gmtime() + 86400,'/',COOKIESDIAMON);



	$back_url = '';
	foreach($_GET as $key => $val){
		if ($key!='act' && $key!='goods_id'){
			if(is_array($_GET[$key])){
				foreach($_GET[$key] as $row){
					$back_url .= '&'.$key.'[]='.$row;
				}
			}else{
				$back_url .= '&'.$key.'='.$val;
			}
		}
	}



    $links[0]["name"] = "返回上一页";
	$links[0]["url"] = "goods.php?act=".$back_url;

    $links[1]["name"] = "返回商品列表";
	$links[1]["url"] = "goods.php?act=";

    if ($is_insert){
		$links[2]["name"] = "返回继续添加";
		$links[2]["url"] = "goods.php?act=add";
    }else{
		$links[2]["name"] = "还需要修改";
		$links[2]["url"] = "goods.php?act=edit&goods_id=".$goods_id.$back_url;
	}

	//$kw = new  keywords();
	//$kw->create_goods_keyword($goods_id);//生成关键词
   //creat_count_category_goods_num($catgory_id);    //统计商品个数
	//if ($is_insert) handle_title_keyword($_POST['goods_title']); //处理关键字，加入到ABC索引里

    if (isset($_POST['edit_peijian_price'])) {//同时修改所有配件价格 by mashanling on 2012-07-30 10:20:57
	    $db->update(GROUPGOODS, 'goods_price=' . $peijian_price, 'goods_id=' . $goods_id);
	}
	echo '<script>alert("操作成功");location.href="goods.php?act=edit&goods_id='. $goods_id.$back_url .'";</script>';
    //sys_msg($is_insert ? "添加成功" : "修改成功 ".$shangjia_tishi, 0, $links);
}


/*------------------------------------------------------ */
//-- 增加一个配件
/*------------------------------------------------------ */

elseif ($_ACT == 'add_group_goods')
{
    $goods_id = empty($_GET['goods_id']) ? 0 : $_GET['goods_id'];
    $price    = empty($_GET['price']) ? 0 : $_GET['price'];
    $pid      = empty($_GET['pid']) ? 0 : intval($_GET['pid']);
    here_add_group_goods($pid, $goods_id, $price);
	exit();
}

/*------------------------------------------------------ */
//-- 删除一个配件
/*------------------------------------------------------ */

elseif ($_ACT == 'drop_group_goods')
{

    $goods_id   = empty($_GET['goods_id'])?0:$_GET['goods_id'];
    $pid        = empty($_GET['pid'])?0:intval($_GET['pid']);

	$goods_id_arr = explode(',',$goods_id);
	$all        = empty($_GET['all'])?'':trim($_GET['all']);

	if ($all == 'yes'){
		$sql = "DELETE FROM " .GROUPGOODS.
				" WHERE parent_id='$pid'";
	}else{
		$sql = "DELETE FROM " .GROUPGOODS.
				" WHERE parent_id='$pid' AND " .db_create_in($goods_id_arr, 'goods_id');
	}
    if ($goods_id == 0)
    {
        $sql .= " AND admin_id = '".$_SESSION['WebUserInfo']['said']."'";
    }
    $db->query($sql);

exit();
}



/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */

elseif ($_ACT == 'batch')
{
	/* 检查权限  */
	admin_priv('goods_list');


    $code = empty($_GET['extension_code'])? '' : trim($_GET['extension_code']);
    /* 取得要操作的商品编号 */
    $goods_id = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;
    $goods_id_array = !empty($_POST['checkboxes']) ? $_POST['checkboxes'] : array();

    //对清仓产品上架操作做如下限制：清仓类的产品，
    //清仓等级的产品（产品等级为活跃有货近期无销售，不活跃有货近期无销售）
    //不允许网站后台手工上架
    //等级为11,12
    //by mashanling on 2014-03-08 13:59:14
    if (isset($_POST['type']) && 'on_sale' == $_POST['type']) {
        require_once('./libs/fun.admin.php');
        disabled_on_sale($goods_id);
    }

	$GoodsSnArrTemp = array();
	$GoodsSnArr = $db->arrQuery("select goods_sn from ".GOODS." WHERE goods_id " . db_create_in($goods_id));
	foreach($GoodsSnArr as $val){
		$GoodsSnArrTemp[] = $val['goods_sn'];
	}
	$goods_sn = implode('，',$GoodsSnArrTemp);


    $BatchStr = '';

    $_TYPE = !empty($_POST['type'])?$_TYPE = $_POST['type']:'';
	if (!empty($_POST['target_cat'])){
		foreach($_POST['target_cat'] as $key => $val){
			if($_POST['target_cat'][$key] == '') unset($_POST['target_cat'][$key]);
		}
	    $_POST['target_cat'] = end($_POST['target_cat']);
	}

    if (isset($_TYPE))
    {
       /* 放入回收站 */
        if ($_TYPE == 'trash')
        {
        	if($field == 'is_delete') $db->delete(CART,"$goods_id = '$_ID'");
			$BatchStr = '商品编号为：'.$goods_sn.'放入了回收站';
            update_goods($goods_id, 'is_delete', '1');
			$db -> update(GOODS, "promote_start_date = 0,promote_end_date = 0,is_promote=0 ", " goods_id = $goods_id "); //产品放入回收站取消促销 2013/10/16 fangxin
        }
        /* 上架 */
        elseif ($_TYPE == 'on_sale')
        {
            $return_message='';
            $url = 'http://www.faout.com/code/api.php';
            $data = "act=check_queue&goods_id_str=$goods_id&website=A";
            //$data = "act=check_queue&goods_id_str=401648,453547&website=G";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
            $contents = curl_exec($ch);
            curl_close($ch);

            $gids=explode(',',$contents);
            foreach ( $gids as $k=>$gid )
            {
                if(in_array($gid,$goods_id_array))
                {
                    $return_message.=','.$gid;
                    $key = array_search($gid, $goods_id_array);
                    unset($goods_id_array[$key]);
                }
            }
            if($return_message)
            {
                $return_message.=" 图片不完整";
                $goods_id = join(',',$goods_id_array);
            }
            //exit;

			$BatchStr = '商品编号为：'.$goods_sn.'上架了。'.$return_message;
            update_goods($goods_id, 'is_on_sale', '1');
        }

        /* 下架 */
        elseif ($_TYPE == 'not_on_sale')
        {
			$BatchStr = '商品编号为：'.$goods_sn.'下架了';
            update_goods($goods_id, 'is_on_sale', '0');
			$db -> update(GOODS, "promote_start_date = 0,promote_end_date = 0,is_promote=0 ", " goods_id = $goods_id "); //产品放入回收站取消促销 2013/10/16 fangxin
        }

        /* 设为精品 */
        elseif ($_TYPE == 'best')
        {
			$BatchStr = '商品编号为：'.$goods_sn.'设为推荐';
            update_goods($goods_id, 'is_best', '1');
        }

        /* 取消精品 */
        elseif ($_TYPE == 'not_best')
        {
			$BatchStr = '商品编号为：'.$goods_sn.'取消推荐';
            update_goods($goods_id, 'is_best', '0');
        }

        /* 设为新品 */
        elseif ($_TYPE == 'new')
        {
			$BatchStr = '商品编号为：'.$goods_sn.'设为新品';
            update_goods($goods_id, 'is_new', '1');
        }

         /* 批量免邮 */
		elseif ($_TYPE == 'to_free_shipping')
		{
				/* 记录日志 */
	 			$BatchStr = '商品编号为：'.$goods_sn.'设置成免邮商品';
	 			update_goods($goods_id, 'is_free_shipping', '1');

		}
			/* 批量不免邮 */
		elseif ($_TYPE == 'to_no_free_shipping')
		{
				/* 记录日志 */
	 			$BatchStr = '商品编号为：'.$goods_sn.'设置成不免邮商品';
	 			update_goods($goods_id, 'is_free_shipping', '0');
		}

        /* 取消新品 */
        elseif ($_TYPE == 'not_new')
        {
			$BatchStr = '商品编号为：'.$goods_sn.'取消新品';
            update_goods($goods_id, 'is_new', '0');
        }


        /* 设为热卖 */
        elseif ($_TYPE == 'hot')
        {
 			$BatchStr = '商品编号为：'.$goods_sn.'设为热卖';
           update_goods($goods_id, 'is_hot', '1');
        }

        /* 取消热销 */
        elseif ($_TYPE == 'not_hot')
        {
 			$BatchStr = '商品编号为：'.$goods_sn.'取消热销';
            update_goods($goods_id, 'is_hot', '0');
        }

        /* 设为明星产品 */
        elseif ($_TYPE == 'superstar')
        {
 			$BatchStr = '商品编号为：'.$goods_sn.'设为明星产品热销';
            update_goods($goods_id, 'is_superstar', '1');
        }

        /* 取消明星产品 */
        elseif ($_TYPE == 'not_superstar')
        {
 			$BatchStr = '商品编号为：'.$goods_sn.'取消明星产品';
            update_goods($goods_id, 'is_superstar', '0');
        }

		//分类推荐
        elseif ($_TYPE == 'fenlei_tuijian')
        {
			$tjArr  = array();
			$tjArr['cat_id']  = empty($_POST['target_cat'])?0:intval($_POST['target_cat']);

            !$tjArr['cat_id'] && sys_msg('请选择分类！', 1, array(), false);
            empty($_POST['checkboxes']) && sys_msg('请选择商品', 1, array(), false);

			if (!empty($_POST['is_best'])) $tjArr['is_best']  = intval($_POST['is_best']);
			if (!empty($_POST['is_new'])) $tjArr['is_new']  = intval($_POST['is_new']);
			if (!empty($_POST['is_hot'])) $tjArr['is_hot']  = intval($_POST['is_hot']);
            if (!empty($_POST['is_super_star'])) $tjArr['is_super_star']  = intval($_POST['is_super_star']);
            1 == count($tjArr) && sys_msg('请勾选推荐类型', 1, array(), false);
			$log_msg = '';
			if (!empty($tjArr['cat_id']) && !empty($_POST['checkboxes'])){
				foreach($_POST['checkboxes'] as $val){
			        $tjArr['goods_id']  = $val;
                    $tjArr['add_date']  = time();
					$sql="select count(*) from ".GOODSTUIJIAN." where goods_id = '".$val."' and cat_id= '".$tjArr['cat_id']."' ";
					if (!$db->getOne($sql)){
						$db->autoExecute(GOODSTUIJIAN, $tjArr);
					}else{
						$db->autoExecute(GOODSTUIJIAN, $tjArr,'UPDATE'," goods_id = '".$val."' and cat_id= '".$tjArr['cat_id']."' ");
					}
				}
			}
 			$BatchStr = '商品编号为：'.$goods_sn.'分类推荐';
        }
		/*设置积分比率*/
        elseif($_TYPE =='jifen_bilv'){
			$setpointrate = $_POST['point_rate'];
			update_goods($goods_id,'point_rate',$setpointrate);
        }
		//批量设置折扣率 fangxin 2013/10/29
        elseif($_TYPE == 'batch_discount_rate'){
			$discount_rate = $_POST['discount_rate'];
			$sql = "SELECT goods_id, shop_price, promote_price FROM ". GOODS ." WHERE goods_id IN(". $goods_id .")";
			$res = $db->arrQuery($sql);
			foreach($res as $key=>$value) {
				if($value['promote_price'] > 0) {
					$shop_price = $value['promote_price'];
				} else {
					$shop_price = $value['shop_price'];
				}
				if($discount_rate > 0 && $shop_price > 0) {
					$market_price = price_format(($shop_price * 100)/(100-$discount_rate), 2);
					$db->query("UPDATE ". GOODS ." SET market_price = ". $market_price .", discount_rate = ". $discount_rate ." WHERE goods_id = ". $value['goods_id'] ."");
				}
			}
        }
        /* 转移到分类 */
 		elseif ($_TYPE == 'move_to') {
            $cat_id = empty($_POST['target_cat']) ? 0 : intval($_POST['target_cat']);
            empty($cat_id) && sys_msg('未选择商品分类，请仔细核对！', 1, array(), false);
            $cat_arr = read_static_cache('category_c_key', 2);
            empty($cat_arr[$cat_id]) && sys_msg('商品分类不存在，请仔细核对！', 1, array(), false);
            $cat_arr         = $cat_arr[$cat_id];//商品分类
        	$pingbi_language = $cat_arr['clang'];//屏蔽语言
        	$is_login        = $cat_arr['is_login'];//是否购买限制
            $BatchStr = '商品编号为：' . $goods_sn . '转移到分类' . $cat_id;
            $sql = 'UPDATE ' . GOODS . " SET cat_id={$cat_id},is_login={$is_login},clang='{$pingbi_language}',last_update=" . gmtime() .",update_user='{$_SESSION['WebUserInfo']['sa_user']}' WHERE goods_id IN({$goods_id})";
            if (!$db->query($sql)) {
                sys_msg('转移失败！', 1, array(), false);
            }
        }

        /* 添加到新分类 */
        elseif ($_TYPE == 'add_to')
        {
			/* 处理扩展分类 */
			foreach($_POST['checkboxes'] as $goods_id_idex){
				//handle_other_cat($goods_id_idex, array_unique($_POST['other_cat']));
				/* 添加新加的分类 */
				//$add_list = array_diff($cat_list, $exist_list, array(0));
				foreach ($_POST['other_cat'] AS $cat_id)
				{
					// 插入记录
					if ($cat_id){
						$sql = "INSERT INTO " . GOODSCAT .
								" (goods_id, cat_id) " .
								"VALUES ('$goods_id_idex', '$cat_id') ON DUPLICATE KEY UPDATE goods_id=goods_id";
						$GLOBALS['db']->query($sql);
					}
				}
			}
 			$BatchStr = '商品编号为：'.$goods_sn.'添加到新分类'.implode(',',$_POST['other_cat']);
        }
        /* 添加属性 */
        elseif ($_TYPE == 'batch_add_attr')
        {
			$type_id = $_POST['type_id'];

			$sql = "SELECT attr_id,attr_values FROM " .ATTR. "  WHERE cat_id='$type_id'";
			$type_attr = $db->arrQuery($sql);

			foreach($_POST['checkboxes'] as $goods_id_idex){
				$goods_id = $goods_id_idex;
				$where = " where goods_id = '$goods_id' ";
				$db->query(" update ".GOODS." as g set goods_type = '$type_id' $where ");

				$sql = "DELETE FROM " .GATTR. " WHERE goods_id = '$goods_id'  ";//and attr_id = '$attr_id'
				$res = $db->query($sql);

				foreach($type_attr as $key => $tw){
					$attr_id = $tw['attr_id'];
						$attr_values = explode("\n", $tw['attr_values']);
						if (!empty($attr_values)){
							foreach ($attr_values AS $opt)
							{
								$opt    = trim(htmlspecialchars($opt));
								$optArr = explode('|',$opt);
								$opt_name = $optArr[0];
								$opt_price = empty($optArr[1])?'':$optArr[1];
								$opt_goods_sn = empty($optArr[2])?'':$optArr[2];

								$sql = "INSERT INTO " .GATTR. " (attr_id, goods_id, attr_value,attr_price,attr_goods_sn) VALUES ('$attr_id', '$goods_id', '$opt_name','$opt_price','$opt_goods_sn')";
								$db->query($sql);
							}
						}
				}

 			$BatchStr = '商品编号为：'.$goods_sn.'添加属性'.$_POST['type_id'];

			}
        }
        /* 还原 */
        elseif ($_TYPE == 'restore')
        {

            update_goods($goods_id, 'is_delete', '0');

            /* 记录日志 */
 			$BatchStr = '商品编号为：'.$goods_sn.'从回收站还原成功';
        }
        /* 删除 */
        elseif ($_TYPE == 'drop')
        {
			//exit;

            delete_goods($goods_id);

 			$BatchStr = '商品编号为：'.$goods_sn.'从回收站清除成功';
        }
        /* 批量促销 */
		elseif ($_TYPE == 'batch_promote')
		{
			admin_priv('goods_batch_promote');  //检查权限
			/* 记录日志 */
			$promote_rate = $_POST['promote_rate'] ? $_POST['promote_rate'] : 0;
 			$beginDate = $_POST['promote_start_date'] ? local_strtotime($_POST['promote_start_date']) : 0;
 			$endDate = $_POST['promote_end_date'] ? local_strtotime($_POST['promote_end_date']) : 0;
 			$sql = "SELECT * FROM " . GOODS . " WHERE goods_id ".db_create_in($goods_id);
 			$goods = $db->arrQuery($sql);
 			//规则：1.促销价要小于市场价;2.市场售价高于本店售价;3.商品导入的市场价为0时，设置此促销价后，将市场价更改为本店售价
 			$updateManager = array(
 										'is_update'=>true,
 										'max_shop_price'=>0,
 										'errorMsg'=>array(),
 										'successMsg'=>array(),
 										'sql'=>''
 								  );
 			foreach ($goods AS $key => $val)
 			{
 				//计算促销价格
	        	$shop_price  = round(($val['chuhuo_price']/HUILV),2);//转成美元
	        	$updateManager['max_shop_price'] = max($updateManager['max_shop_price'], $shop_price);
	        	if($val['is_free_shipping'] == 1){
					$shipping_fee   = get_shipping_fee($shop_price, $val['goods_weight']);		//运费
	        	}else{
	        		$shipping_fee = 0;
	        	}
	        	$val['promote_price'] = empty($promote_rate) ? 0 : $shop_price*$promote_rate+$shipping_fee;		//商品促销价
	        	$val['promote_price']	= round($val['promote_price'],2);
	        	$updateFiled = "";
	        	//如果设置了市场价则更新，否则按原来的市场价计算
	        	$_POST['market_price'] *= 1; //转换数据类型,将0.00|0|null|false等 => 0
	        	if (!empty($_POST['market_price'])){
	        		$updateFiled .= ",market_price='".$_POST['market_price']."'";
	        		$market_price = $_POST['market_price'];
	        	}else {
	        		$market_price = $val['market_price'];
	        	}
	        	//促销价不能大于本店售价
	        	if ($val['promote_price'] > $val['shop_price']) {
	        		$updateManager['is_update'] = false;
	        		$updateManager['errorMsg']["{$val['goods_sn']}"] = 'SKU:'.$val['goods_sn'].'&nbsp;&nbsp;促销价不能大于本店售价(促销价：'.$val['promote_price'].',本店售价：'.$val['shop_price'].')';
	        	}
	        	//市场价不能小于本店售价
	        	if (!empty($_POST['market_price']) && $market_price < $val['shop_price']) {
	        		$updateManager['is_update'] = false;
					$errorStr = '&nbsp;&nbsp;市场价不能小于本店售价(市场价:'.$market_price.',本店售价:'.$val['shop_price'].')';
	        		$updateManager['errorMsg']["{$val['goods_sn']}"] = empty($updateManager['errorMsg']["{$val['goods_sn']}"]) ? 'SKU:'.$val['goods_sn'].','.$errorStr : $updateManager['errorMsg']["{$val['goods_sn']}"].$errorStr;
	        	}
	        	//如果ERP导入产品的市场价为0时,设置此促销价后,将市场价更改为本店售价
	        	if (empty($_POST['market_price']) && $market_price*1==0) {
	        		$updateFiled .= ",market_price=shop_price";
	        	}
	        	//如果没有错误，则设置为更新成功的sql
	        	if (empty($updateManager['errorMsg'][$val['goods_sn']])) {
	        		$updateManager['successMsg']["{$val['goods_sn']}"] = 'SKU:'.$val['goods_sn'].'&nbsp;&nbsp;--可更新(ok)';
					$promote_price = format_price($val['promote_price']); //修改促销价格 fangxin 2013/10/08
	        		$updateManager['sql']["{$val['goods_sn']}"] = "UPDATE ".GOODS." SET is_promote='1',promote_price='".$promote_price."',promote_lv='$promote_rate',promote_start_date='$beginDate',promote_end_date='$endDate'".$updateFiled." WHERE goods_id='".$val['goods_id']."';";
	        	}
 			}
 			//检查是否执行更新
 			if ($updateManager['is_update'] == true) {
 				foreach ($updateManager['sql'] AS $key=>$val)
 				{
 					$db->query($val);
 				}
 			}else {
 				$msgDetail = '提示：该批商品的本店售价最大值-->'.$updateManager['max_shop_price'].'<br>';
 				foreach ($updateManager['successMsg'] AS $key=>$val)
 				{
 					$msgDetail .= '<font color="#339933">'.$val.'</font><br>';
 				}
 				foreach ($updateManager['errorMsg'] AS $key=>$val)
 				{
 					$msgDetail .= $val.'<br>';
 				}
 				sys_msg($msgDetail, 1, array(), false);
 			}
 			$BatchStr = '商品编号为：'.$goods_sn.'设置成促销商品,促销利润率为：'.$promote_rate.'，促销时间为：'.$_POST['promote_start_date'].'-->'.$_POST['promote_end_date'];
		}
		/* 批量不促销 */
		elseif ($_TYPE == 'batch_promote_cancel')
		{
			admin_priv('goods_batch_promote_cancel');  //检查权限
			/* 记录日志 */
 			$BatchStr = '商品编号为：'.$goods_sn.'设置成非促销商品';
 			$beginDate = 0;
 			$endDate = 0;
 			$promote_price = 0;
 			$sql = "UPDATE ".GOODS." SET is_promote=0,promote_lv=0,promote_price={$promote_price},promote_start_date={$beginDate},promote_end_date={$endDate} WHERE goods_id ".db_create_in($goods_id);
 			$GLOBALS['db']->query($sql);
		}

		/* 记录日志 */
		admin_log('', '批量把', $BatchStr);

    }

        $link[0]["name"] = "返回上一页";
        $link[0]["url"] = $_SERVER["HTTP_REFERER"];

    if ($_TYPE == 'drop' || $_TYPE == 'restore')
    {
        $link[1]["name"] = "返回商品回收站";
        $link[1]["url"] = "goods.php?act=trash";
    }
	//creat_count_category_goods_num();    //统计商品个数
    sys_msg("批量操作成功", 0, $link);
}

/*------------------------------------------------------ */
//-- 显示图片
/*------------------------------------------------------ */

elseif ($_ACT == 'show_image')
{

    if (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0)
    {
        $img_url = $_GET['img_url'];
    }
    else
    {
        if (strpos($_GET['img_url'], 'http://') === 0)
        {
            $img_url = $_GET['img_url'];
        }
        else
        {
            $img_url = '../' . $_GET['img_url'];
        }
    }
	echo '<img src="'.$img_url.'" />';
    exit();
}

/*------------------------------------------------------ */
//-- ajax修改商品
/*------------------------------------------------------ */
elseif ($_ACT == 'editinplace')
{
	admin_priv('goods_list');
	$dataArr = explode('||',$_POST['id']);
    $goods_id    = intval($dataArr[0]);
    $goods_field = trim($dataArr[1]);
    $val         = trim($_POST['value']);
	$goods_sn = $db->getOne("select goods_sn from ".GOODS." WHERE goods_id = '$goods_id'");
    $db->update(GOODS," $goods_field = '$val', last_update=" .gmtime().",  update_user = '".$_SESSION["WebUserInfo"]["sa_user"]."'", "  goods_id = '$goods_id'");
	if($goods_field == 'discount_rate') {
		$sql = "SELECT goods_id, market_price, shop_price, promote_price FROM ". GOODS ." WHERE goods_id = ". $goods_id ." LIMIT 1";
		if($res = $db->selectInfo($sql)) {
			if(!empty($res['promote_price'])) {
				$shop_price = $res['promote_price'];
			} else {
				$shop_price = $res['shop_price'];
			}
			$market_price = price_format(($shop_price * 100)/(100-$val), 2);
			if($market_price > 0) {
			    $db->update(GOODS," market_price = '$market_price'", "goods_id = '$goods_id'");
			}
		}
	}
	admin_log('', _EDITSTRING_,'商品:'.$goods_sn.$goods_field.'='.$val);
    echo $val;
	exit();
}


/*------------------------------------------------------ */
//-- 放入回收站
/*------------------------------------------------------ */
elseif ($_ACT == 'remove')
{
    $goods_id = intval($_GET['goods_id']);

    /* 检查权限 */
    admin_priv('goods_list');

    if ($db->update(GOODS," is_delete = 1", ' goods_id='.$goods_id))
    {
    	$db->delete(CART,'goods_id='.$goods_id);
        $goods = $db->selectinfo('select goods_sn,url_title,cat_id from '.GOODS.' where goods_id ='.$goods_id);
		$goods_sn = $goods['goods_sn'];
		$path_dir = ROOT_PATH .GOODS_DIR.$goods['cat_id'].'/'.$goods['url_title'];
		if (file_exists($path_dir)){
			@unlink($path_dir);
		}
        admin_log('', '把商品'.addslashes($goods_sn), '放入回收站了'); // 记录日志
		$db -> update(GOODS, "promote_start_date = 0,promote_end_date = 0,is_promote=0 ", " goods_id = $goods_id "); //产品放入回收站取消促销 2013/10/16 fangxin
		//creat_count_category_goods_num();    //统计商品个数
        echo '1';
        exit;
    }
}

/*------------------------------------------------------ */
//-- 还原回收站中的商品
/*------------------------------------------------------ */

elseif ($_ACT == 'restore_goods')
{
	$goods_id = intval($_GET['goods_id']);
	admin_priv('goods_trash');
	$db->update(GOODS," is_delete = 0, add_time = '" . gmtime() . "'", ' goods_id='.$goods_id);
	$goods_sn = $db->getOne('select goods_sn from '.GOODS.' where goods_id ='.$goods_id);
	admin_log('', '把商品'.addslashes($goods_sn), '从回收站还原到了商品列表'); // 记录日志
	//creat_count_category_goods_num();    //统计商品个数
	echo '1';
	exit;
}

/*------------------------------------------------------ */
//-- 彻底删除商品
/*------------------------------------------------------ */
elseif ($_ACT == 'drop_goods')
{
    // 检查权限
	admin_priv('goods_trash');

    // 取得参数
    $goods_id = intval($_GET['goods_id']);

    /* 取得商品信息 */
    $sql = "SELECT goods_id, goods_sn, is_delete, goods_thumb, " .
                "goods_img, original_img,goods_grid " .
            "FROM " . GOODS .
            " WHERE goods_id = '$goods_id'";
    $goods = $db->selectinfo($sql);
    if (empty($goods))
    {
        exit;
    }

    if ($goods['is_delete'] != 1)
    {
        exit;
    }

    /* 删除商品图片和轮播图片 */
    /*if (!empty($goods['goods_thumb']))
    {
        @unlink('../' . $goods['goods_thumb']);
    }
    if (!empty($goods['goods_img']))
    {
        @unlink('../' . $goods['goods_img']);
    }
    if (!empty($goods['original_img']))
    {
        @unlink('../' . $goods['original_img']);
    }*/

    /* 删除商品 */
    $sql = "DELETE FROM " . GOODS . " WHERE goods_id = '$goods_id'";
    $db->query($sql);

	//删除多语言商品数据 fangxin
	$language_sql = "SELECT * FROM ". Mtemplates_language ." ORDER BY orders ASC";
	$language_res = $db->arrQuery($language_sql);
	foreach($language_res as $value) {
		$language = $value['title_e'];
		$sql = "DELETE FROM " . GOODS . "_$language WHERE goods_id = '$goods_id'";
		echo $sql;
		$db->query($sql);
	}

    /* 删除商品状态表 */
    $sql = "DELETE FROM " . GOODS_STATE . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);

    /* 删除商品扩展表 */
    $sql = "DELETE FROM " . GOODS_EXTEND . " WHERE goods_id = '$goods_id'";
    $GLOBALS['db']->query($sql);

    /* 删除商品阶梯价格表 */
    $sql = "DELETE FROM " . VPRICE .  " WHERE goods_id = '$goods_id'";
    $GLOBALS['db']->query($sql);

    /* 记录日志 */
    admin_log('', _DELSTRING_, '商品：'.addslashes($goods['goods_sn']));

    /* 删除商品相册 */
    $sql = "SELECT img_url, thumb_url, img_original " .
            "FROM " . GGALLERY .
            " WHERE goods_id = '$goods_id'";
    $res = $db->query($sql);
    while ($row = $db->fetchRow($res))
    {
       /* if (!empty($row['img_url']))
        {
            @unlink('../' . $row['img_url']);
        }
        if (!empty($row['thumb_url']))
        {
            @unlink('../' . $row['thumb_url']);
        }
        if (!empty($row['img_original']))
        {
            @unlink('../' . $row['img_original']);
        }*/
       $syn_gallery_image1[]="img_url@@".$row1['img_url']."@@@thumb_url@@".$row1['thumb_url']."@@@img_original@@".$row1['img_original'];
    }
    $syn_gallery_image_ser1=serialize($syn_gallery_image1);

    //删除商品封面和相册图片
    $post_data="goods_thumb=".$goods['goods_thumb']."&goods_grid=".$goods['goods_grid']."&goods_img=".$goods['goods_img']."&original_img=".$goods['original_img']."&syn_gallery_image=$syn_gallery_image_ser1&action=del";
    echo post_image_info(IMG_API_PATH,$post_data);//到图片库删除相册

    $sql = "DELETE FROM " . GGALLERY . " WHERE goods_id = '$goods_id'";
    $db->query($sql);

    $sql = "DELETE FROM " . GROUPGOODS . " WHERE parent_id = '$goods_id'";
    $GLOBALS['db']->query($sql);
    $sql = "DELETE FROM " . GROUPGOODS . " WHERE goods_id = '$goods_id'";
    $GLOBALS['db']->query($sql);

    $sql = "DELETE FROM " . GOODSCAT . " WHERE goods_id = '$goods_id'";
    $GLOBALS['db']->query($sql);

    /* 删除相关表记录 */
    $sql = "DELETE FROM " . COLLECT . " WHERE goods_id = '$goods_id'";
    $db->query($sql);
    $sql = "DELETE FROM " . GATTR . " WHERE goods_id = '$goods_id'";
    $db->query($sql);
    $sql = "DELETE FROM " . COMMENT . " WHERE comment_type = 0 AND id_value = '$goods_id'";
    $db->query($sql);

    //删除商品点击率
    $sql = "DELETE FROM " . GOODS_HITS . " WHERE goods_id = '$goods_id'";
    $GLOBALS['db']->query($sql);

    //删除推荐商品
    $sql = "DELETE FROM " . GOODSTUIJIAN . " WHERE goods_id = '$goods_id'";
    $GLOBALS['db']->query($sql);



    //删除商品咨询表
    $sql = "DELETE FROM " . PRO_INQUIRY . " WHERE goods_id = '$goods_id'";
    $GLOBALS['db']->query($sql);

    //获得商品评论ID
    $sql = "SELECT rid FROM " . REVIEW . " WHERE goods_id = '$goods_id'";
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
    $sql = "DELETE FROM " . CART . " WHERE goods_id = '$goods_id'";
    $GLOBALS['db']->query($sql);

    echo '1';
    exit;
}

/*------------------------------------------------------ */
//-- 搜索商品，仅返回名称及ID
/*------------------------------------------------------ */
elseif ($_ACT == 'get_goods_list')
{
    $filters = $_GET;
    $arr = get_goods_list($filters);
    $opt = array();
	$str = '';
    foreach ($arr AS $key => $val)
    {
		$str .="<option value='".$val['goods_id']."' price='".$val['peijian_price']."' id='".$val['goods_id']."'>".$val['goods_title']."</option>";
    }

	echo $str;
    exit;
}

/*------------------------------------------------------ */
//-- 切换商品类型
/*------------------------------------------------------ */
elseif ($_ACT == 'get_attr')
{
    admin_priv('goods_list');

    $goods_id   = empty($goods_id) ? 0 : intval($goods_id);
    $goods_type = empty($_GET['goods_type']) ? 0 : intval($_GET['goods_type']);

    $content    = build_attr_html($goods_type, $goods_id);

    echo $content;
	exit();
}

/*------------------------------------------------------ */
//-- 删除图片
/*------------------------------------------------------ */
elseif ($_ACT == 'drop_image')
{
    $img_id = empty($_GET['img_id']) ? 0 : intval($_GET['img_id']);
    /* 删除图片文件 */
    $sql = "SELECT img_url, thumb_url, img_original " .
            " FROM " . GGALLERY .
            " WHERE img_id = '$img_id'";
    $row = $GLOBALS['db']->selectinfo($sql);

    if ($row['img_url'] != '' && is_file('../' . $row['img_url']))
    {
        @unlink('../' . $row['img_url']);
    }
    if ($row['thumb_url'] != '' && is_file('../' . $row['thumb_url']))
    {
        @unlink('../' . $row['thumb_url']);
    }
    if ($row['img_original'] != '' && is_file('../' . $row['img_original']))
    {
        @unlink('../' . $row['img_original']);
    }

    /* 删除数据 */
    $sql = "DELETE FROM " . GGALLERY . " WHERE img_id = '$img_id' LIMIT 1";
    if ($GLOBALS['db']->query($sql)) {echo '0';}else{ echo '1';};
	exit();
}
elseif ($_ACT == 'peijian_get_goods_info') {//添加配件，获取商品信息
    $goods_sn = isset($_POST['goods_sn']) ? $_POST['goods_sn'] : '';
    $sql      = 'SELECT goods_id,goods_title,goods_sn,goods_thumb,peijian_price,promote_price,shop_price,promote_start_date,promote_end_date FROM ' . GOODS . " WHERE goods_sn='{$goods_sn}' AND is_delete=0 AND is_on_sale=1 AND goods_number>0";
    $info     = $db->selectinfo($sql);

    if (!empty($info)) {
        $price = bargain_price($info['promote_price'], $info['promote_start_date'], $info['promote_end_date']);
        $info['shop_price']  = $price > 0 ? $price : $info['shop_price'];
		$info['peijian_price'] = round($info['shop_price']*0.9,2);
        $info['goods_thumb'] = get_image_path(false, $info['goods_thumb']);
        $info['name'] = strlen($info['goods_title']) > 70 ? substr($info['goods_title'], 0, 70) . '...' : $info['goods_title'];
        unset($info['promote_price'], $info['promote_start_date'], $info['promote_end_date']);

        echo json_encode(array('success' => !empty($info), 'data' => $info));
    }

    exit();
}

/*------------------------------------------------------ */
//-- 计算促销价
/*------------------------------------------------------ */
elseif ($_ACT == 'jisuan_promote_price')
{
	$goods_id = empty($_GET['goods_id']) ? 0 : intval($_GET['goods_id']);	//商品ID
	$promote_lv = empty($_GET['promote_lv']) ? 0 : floatval ($_GET['promote_lv']);	//商品促销利润率
	$is_free_shipping = empty($_GET['is_free_shipping']) ? 0 : floatval ($_GET['is_free_shipping']);	//商品是否免邮
	$return_array = array();
	if(!$goods_id)
	{
		$return_array['statu'] = 0;
	}
	else
	{
		if($promote_lv < 0)
		{
			$return_array['statu'] = 3;
		}
		else
		{
			$sql = "SELECT * FROM " . GOODS . " WHERE goods_id = '$goods_id'";
	        $goods = $db->selectinfo($sql);
	        if ($goods)
	        {
	        	//计算促销价格
	        	$shop_price     = round(($goods['chuhuo_price']/HUILV),2);//转成美元
	        	if($is_free_shipping == 1)
	        	{
					$shipping_fee   = get_shipping_fee($shop_price, $goods['goods_weight']);		//运费
	        	}
	        	else
	        	{
	        		$shipping_fee = 0;
	        	}
	        	$goods['promote_price'] = empty($promote_lv) ? 0 : $shop_price*$promote_lv+$shipping_fee;		//商品促销价
	        	$return_array['statu'] = 1;
	        	$return_array['promote_price']	= round($goods['promote_price'],2);
	        }
	        else
	        {
	        	$return_array['statu'] = 2;
	        }
		}
	}
	echo json_encode($return_array);
	exit();
}

/*------------------------------------------------------ */
//-- ajax修改配件
/*------------------------------------------------------ */
elseif ($_ACT == 'editpeijian') {
    admin_priv('goods_list');
    $dataArr = explode('||',$_POST['id']);
    $parent_id   =intval($dataArr[0]);
    $goods_id    = intval($dataArr[1]);
    $goods_field = trim($dataArr[2]);
    $val         = trim($_POST['value']);
    $db->query("update eload_group_goods set sort_order=$val where parent_id=$parent_id and goods_id=$goods_id");
    admin_log('', _EDITSTRING_,'商品:'.$goods_id.$goods_field.'='.$val);
    echo $val;
    exit();
}
/*
 * 明星产品
*/
elseif($_ACT == 'super_star'){
	$data = read_static_cache('super_star',1);
	$str = '';
	if(!empty($data)){
		$str = implode(',',$data);
	}
	$Arr['data'] = $str;
	if($_POST){
		$admin_share = isset($_POST['super_star'])?$_POST['super_star']:'';
		$data = explode(',',$admin_share);
		$data = array_filter($data);

		write_static_cache('super_star',$data,1);
		$link[0]['name'] = "返回列表" ;
		$link[0]['url'] ='/eload_admin/goods.php?act=super_star';
		sys_msg('添加成功', 0, $link);
	}
	$_ACT = 'super_star';
}

//刷新产品价格 fangxin 2013/10/08
elseif($_ACT == 'update_goods_price') {
	$sql = "SELECT goods_id, shop_price, promote_price FROM ". GOODS ." WHERE shop_price >= 10 ORDER BY goods_id ASC";
	$res = $db->arrQuery($sql);
	foreach($res as $key=>$value) {
		$goods_id = $value['goods_id'];
		$shop_price = format_price($value['shop_price']);
		$promote_price = format_price($value['promote_price']);
		$sql = "UPDATE ". GOODS ." SET shop_price = '$shop_price', promote_price = '$promote_price' WHERE goods_id = ". $goods_id ."";
		$db->query($sql);
		//更新优惠价格
		$sql = "SELECT * FROM ". VPRICE ." WHERE goods_id = ". $goods_id ." AND volume_number = 1";
		if($res = $db->arrQuery($sql)) {
			$volume_price = format_price($res[0]['volume_price']);
			$sql = "UPDATE ". VPRICE ." SET volume_price = '$volume_price' WHERE goods_id = ". $goods_id ." AND volume_number = 1";
			$db->query($sql);
		}
		$count = $key + 1;
	}
	$link[0]['name'] = "刷新数据".$count."条，返回列表" ;
	$link[0]['url'] ='/eload_admin/goods.php';
	sys_msg('操作成功', 0, $link);
}

/**
 * 检查图片网址是否合法
 *
 * @param string $url 网址
 *
 * @return boolean
 */
function goods_parse_url($url)
{
    $parse_url = @parse_url($url);
    return (!empty($parse_url['scheme']) && !empty($parse_url['host']));
}

$_ACT = $_ACT == 'msg'?'msg':'goods_'.$_ACT;
temp_disp();

/**
 * post 数据到图片库，同步删除
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

/**
 * 添加配件
 *
 * @author       mashanling(msl-138@163.com)
 * @date         2012-07-30 11:23:36
 * @last modify  2012-07-30 11:23:36 by mashanling
 *
 * @param int   $main_goods_id 主商品id
 * @param mixed $goods_id      配件id
 * @param mixed $price         配件价格
 *
 * @return void 无返回值
 */
function here_add_group_goods($main_goods_id, $goods_id, $price) {
    global $db;
    $admin_id   = $_SESSION['WebUserInfo']['said'];
    $goods_id   = is_array($goods_id) ? $goods_id : map_int($goods_id,true);
    $price_arr  = is_array($price) ? $price : explode(',', $price);
    if (!$main_goods_id || !$goods_id) {
        return;
    }
    foreach ($goods_id as $key => $item) {
        $price = floatval($price_arr[$key]);
        $sql   = 'INSERT INTO ' . GROUPGOODS . ' (parent_id,goods_id,goods_price,admin_id)';
        $sql  .= " VALUES ({$main_goods_id},{$item},{$price},{$admin_id})";
        $sql  .= ' ON DUPLICATE KEY UPDATE goods_price=' . $price;
        $db->query($sql);
    }
	exit();
}
?>