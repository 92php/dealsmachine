<?php
/**
 * goods-distance-import.php erp同步产品
 * 
 * @author                   wuwenlong
 * @last modify              2011-10-08 by mashanling
 */

set_time_limit(0);
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once('../lib/lib_goods.php');
require_once('../lib/inc.html.php');
require_once('../lib/syn_public_fun.php');

$_ACT = 'list';
$_ID  = '';
$goods_id = 0;
if (!empty($_REQUEST['act'])) $_ACT   = trim($_REQUEST['act']);
if (!empty($_REQUEST['id'])) $_ID     = intval(trim($_REQUEST['id']));
if (!empty($_REQUEST['goods_id'])) $goods_id     = intval(trim($_REQUEST['goods_id']));
/*------------------------------------------------------ */
//-- 商品列表，商品回收站
/*------------------------------------------------------ */

if ($_ACT == 'list' || $_ACT == 'trash')
{
    $cat_id = empty($_GET['cat_id']) ? 0 : intval($_GET['cat_id']);
    $code   = empty($_GET['extension_code']) ? '' : trim($_GET['extension_code']);

    $handler_list = array();

    if ($_ACT == 'list' && isset($handler_list[$code]))
    {
        $Arr['add_handler'] =      $handler_list[$code];
    }

    /* 模板赋值 */
    $Arr['cat_list'] =     cat_list($cat_id);
    //$Arr['intro_list'] =   get_intro_list();
    $Arr['list_type'] =    $_ACT == 'list' ? 'goods' : 'trash';
    $Arr['use_storage'] =  empty($_CFG['use_storage']) ? 0 : 1;

    $goods_list = goods_list($_ACT == 'list' ? 0 : 1, ($_ACT == 'list') ? (($code == '') ? 1 : 0) : -1);
    $Arr['goods_list'] =   $goods_list['goods'];
	
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
    
	$tag_msg="添加";
    $is_add = $_ACT == 'add'; // 添加还是编辑的标识
    $is_copy = $_ACT == 'copy'; //是否复制
    $code = empty($_GET['extension_code']) ? '' : trim($_GET['extension_code']);
	if($goods_id!=''){
		if ($is_copy){
			$url = "?act=insert&goods_id=0";
		}else{
			$url = "?act=update&goods_id=$goods_id";
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
            'is_on_sale'    => '1',
            'is_free_shipping' => '1',
            'other_cat'     => array(), // 扩展分类
            'goods_type'    => 0,       // 商品类型
            'shop_price'    => 0,
            'promote_price' => 0,
            'market_price'  => 0,
            'goods_number'  => $_CFG['default_storage'],
            'warn_number'   => 1,
            'promote_start_date' => local_date('Y-m-d'),
            'promote_end_date'   => local_date('Y-m-d', local_strtotime('+1 month')),
            'goods_weight'  => 0,
        );
        $img_list = array();
        if ($code != '')
        {
            $goods['goods_number'] = 0;
        }

    }
    else
    {
        /* 商品信息 */
        $sql = "SELECT * FROM " . GOODS . " WHERE goods_id = '$goods_id'";
        $goods = $db->selectinfo($sql);
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
                'goods_type'    => 0,       // 商品类型
                'shop_price'    => 0,
                'promote_price' => 0,
                'market_price'  => 0,
                'goods_number'  => 1,
                'warn_number'   => 1,
                'promote_start_date' => local_date('Y-m-d'),
                'promote_end_date'   => local_date('Y-m-d', gmstr2tome('+1 month')),
                'goods_weight'  => 0,
            );
        }

        /* 根据商品重量的单位重新计算 */
        if ($goods['goods_weight'] > 0)
        {
            $goods['goods_weight_by_unit'] = ($goods['goods_weight'] >= 1) ? $goods['goods_weight'] : ($goods['goods_weight'] / 0.001);
        }

        if (!empty($goods['goods_brief']))
        {
            //$goods['goods_brief'] = trim_right($goods['goods_brief']);
            $goods['goods_brief'] = $goods['goods_brief'];
        }
        if (!empty($goods['keywords']))
        {
            //$goods['keywords']    = trim_right($goods['keywords']);
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
            $goods['promote_start_date'] = local_date('Y-m-d', $goods['promote_start_date']);
            $goods['promote_end_date'] = local_date('Y-m-d', $goods['promote_end_date']);
        }

        /* 如果是复制商品，处理 */
        if ($_ACT == 'copy')
        {
            // 商品信息
            $goods['goods_id'] = 0;
            $goods['goods_sn'] = '';
            $goods['goods_name'] = '';
            $goods['goods_img'] = '';
            $goods['goods_thumb'] = '';
            $goods['original_img'] = '';

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
                $gallery_img[$key]['thumb_url'] = get_image_path($gallery_img['goods_id'], $gallery_img['img_original'], true, 'gallery');
            }
        }
        else
        {
            foreach ($img_list as $key => $gallery_img)
            {
                $gallery_img[$key]['thumb_url'] = '../' . (empty($gallery_img['thumb_url']) ? $gallery_img['img_url'] : $gallery_img['thumb_url']);
            }
        }
    }
    /* 拆分商品名称样式 */
    $goods_name_style = explode('+', empty($goods['goods_name_style']) ? '+' : $goods['goods_name_style']);

    /* 模板赋值 */
    $Arr['lang'] = array('strong' => '加粗', 'em' => '斜体', 'u' => '下划线', 'strike' => '删除线');
    $Arr['code'] =    $code;
    $Arr['goods'] = $goods;
    $Arr['goods_name_color'] = $goods_name_style[0];
    $Arr['goods_name_style'] = $goods_name_style[1];
    $Arr['cat_list'] = cat_list($goods['cat_id']);
    $Arr['unit_list'] = get_unit_list();
    $Arr['weight_unit'] = $is_add ? '1' : ($goods['goods_weight'] >= 1 ? '1' : '0.001');
    //$Arr['cfg'] = $_CFG;
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
    $Arr['price_great'] = $_CFG['price_grade'];
	if (empty($volume_price_list))
    {
		 if ($is_add){
			foreach ($price_great as $k => $v){
				$volume_price_list[$k] = array('number'=>'','price'=>'');
			}
			
		 }else{
			$volume_price_list = array('0'=>array('number'=>'','price'=>''));
		 }
    }
    $Arr['volume_price_list'] = $volume_price_list;
	$Arr['tag_msg'] = $tag_msg;
	$Arr['url'] = $url;
	$Arr['cfg'] = $_CFG;
	$_ACT = 'add';

    /* 显示商品信息页面 */
}

/*------------------------------------------------------ */
//-- 插入商品 更新商品
/*------------------------------------------------------ */

elseif ($_ACT == 'insert' || $_ACT == 'update') {
	require_once('../lib/cls_image.php');
	$image     = new cls_image();
	$is_insert = true;    //更新或插入标识符
    $goods_sn  = !empty($_POST['goods_sn']) ? $_POST['goods_sn'] : '';
    $goods_id  = 0;
    
    if ($goods_sn != '') {    //检查货号是否重复
        $sql   = 'SELECT goods_id FROM ' . GOODS . " WHERE goods_sn='{$goods_sn}'";
        $data  = $db->getOne($sql);
        
        if (!empty($data)) {
            $is_insert = false;
            $goods_id  = $data;
        }
        
    }

    $shop_price   = !empty($_POST['shop_price']) ? $_POST['shop_price'] : 0;
	$chuhuo_price = $shop_price;
	!$shop_price && exit('同步失败，出货价格不能为零！');
	
    $cat_id       = !empty($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
    $goods_weight = !empty($_POST['goods_weight']) ? $_POST['goods_weight'] * $_POST['weight_unit'] : 0;
	
	$gongshijiage = 0;
    $is_login     = 0;
	$shipping_fee = 0;
	$isMiniBuy    = false;
	$second_gongshijiage = 0;
	
	if ($cat_id){
		$fenleiArr      = get_zhuijia_price_and_fenlei_bili($cat_id, $chuhuo_price);  //根据出货价取出相应的比例，追加价格，个数分级
		$grade          = $fenleiArr['bili'];   //比例 1.27|1.25|1.24|1.23
		$fenji          = $fenleiArr['grade'];  //比例 1|2---9|10-49|50---max
		$zhuijia_price  = round(($fenleiArr['zhuijia_price'] / HUILV),2); //5		
		$rate           = explode('|',$grade);
		$shop_price     = round(($shop_price/HUILV),2);//转成美元
		$shipping_fee   = get_shipping_fee($shop_price, $goods_weight);
		$shop_price     = round($shop_price * $rate[0], 2);
		$catArr         = read_static_cache('category_c_key', 2);
		$is_login       = $catArr[$cat_id]['is_login'];
		$clang          = $catArr[$cat_id]['clang'];
	}
	
	$url_title          = title_to_url($_POST['goods_name']);
	$url_title_temp     = str_replace('.htm', '', $url_title);	
    $market_price       = get_market_price($shop_price + $shipping_fee  + $zhuijia_price);
    $promote_price      = !empty($_POST['promote_price']) ? floatval($_POST['promote_price'] ) : 0;
    $is_promote         = empty($promote_price) ? 0 : 1;
    $promote_start_date = ($is_promote && !empty($_POST['promote_start_date'])) ? local_strtotime($_POST['promote_start_date']) : 0;
    $promote_end_date   = ($is_promote && !empty($_POST['promote_end_date'])) ? local_strtotime($_POST['promote_end_date']) : 0;
    $is_free_shipping   = 1;
    $is_best            = !empty($_POST['is_best']) ? intval($_POST['is_best']) : 0;
    $is_new             = !empty($_POST['is_new']) ? intval($_POST['is_new']) : 0;
    $is_hot             = !empty($_POST['is_hot']) ? intval($_POST['is_hot']) : 0;
    $is_on_sale         = !empty($_POST['is_on_sale']) ? intval($_POST['is_on_sale'])  : 0;
    $is_add_watter      = !empty($_POST['is_add_watter']) ? intval($_POST['is_add_watter'])  : 0;
    $goods_number       = isset($_POST['goods_number']) ? $_POST['goods_number'] : 99999999;
    $warn_number        = isset($_POST['warn_number']) ? $_POST['warn_number'] : 0;
    $goods_type         = isset($_POST['goods_type']) ? $_POST['goods_type'] : 0;
    $goods_title        = !empty($_POST['goods_title']) ? trim($_POST['goods_title']) : '';
    $goods_name_style   = '+';
    $ever_title         = !empty($_POST['ever_title']) ? trim($_POST['ever_title']) : '';
	$ever_title != '' && ($goods_title  = $ever_title);
    $catgory_id         = $cat_id;
    
    $goods_thumb        =  !empty($_POST['goods_thumb']) ? trim($_POST['goods_thumb']) : '';
    $goods_grid         =  !empty($_POST['goods_grid']) ? trim($_POST['goods_grid']) : '';
    $goods_img          =  !empty($_POST['goods_img']) ? trim($_POST['goods_img']) : '';
    $original_img       =  !empty($_POST['original_img']) ? trim($_POST['original_img']) : '';
    
	$_POST['goods_desc'] = empty($_POST['ahappydeal_desc']) ? $_POST['goods_desc'] : $_POST['ahappydeal_desc'];
	$_POST['goods_desc'] = str_replace('</b><br><br>' , '</b><br>' , $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('</b> <br><br>' , '</b><br>' , $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('<br><br><br>' , '<br><br>' , $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('</B><BR><BR>' , '</B><BR>' , $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('</B> <BR><BR>' , '</B><BR>' , $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('<BR><BR><BR>' , '<BR><BR>' , $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('www.davismicro.com.cn:9000/uploads', 'img.bestafford.com', $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('www.davismicro.com:9000/images', 'img.bestafford.com/images', $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('www.davismicro.com.cn/uploads', 'img.bestafford.com', $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('www.davismicro.com/images', 'img.bestafford.com/images', $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('<ul><br><br><b>', '<ul><b>', $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('<ul><BR><BR><B>', '<ul><B>', $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('<ul><br><b>', '<ul><b>', $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('<ul><BR><B>', '<ul><B>', $_POST['goods_desc']);
	
	$add_user            =  !empty($_POST['add_user'])?trim($_POST['add_user']) : '';
    $add_time            =  gmtime();
	$import_url          = 'http://usimg.davismicro.com.cn/';
    $opts = array(    //接收相册
        'http' => array(
            'method' => 'GET', 
            'timeout' => 60
        )
    );
    $context = stream_context_create($opts);
    
	$syn_gallery_image   = empty($_POST['syn_gallery_image']) ? '' : $_POST['syn_gallery_image'];
	syn_check_original_img();
    !empty($syn_gallery_image) && ($gallery_img_data = syn_get_gallery_data($syn_gallery_image));
		
    /* 入库 */
    if ($is_insert) {
			$xianshop_price = $shop_price + $shipping_fee  + $zhuijia_price;

	$sql = "INSERT INTO " . GOODS . " (goods_name,goods_title, goods_name_style, goods_sn, " .
			"cat_id,  shop_price, market_price,chuhuo_price ,is_promote, promote_price, " .
			"promote_start_date, promote_end_date, goods_img, goods_thumb, original_img, keywords, goods_brief, " .
			"seller_note, goods_weight, goods_number, warn_number,  is_free_shipping, is_best, is_new, is_hot,is_login, " .
			"is_on_sale, goods_desc,add_user,add_time, goods_type,goods_grid)" .
		"VALUES ('$_POST[goods_name]','$goods_title', '$goods_name_style', '$goods_sn', '$catgory_id', " .
			" '$xianshop_price', '$market_price','$chuhuo_price', '$is_promote','$promote_price', ".
			"'$promote_start_date', '$promote_end_date', '$goods_img', '$goods_thumb', '$original_img', ".
			"'$_POST[keywords]', '$_POST[goods_brief]', '', '$goods_weight', '$goods_number',".
			" '$warn_number',  '$is_free_shipping', '$is_best', '$is_new', '$is_hot','$is_login', '$is_on_sale', ".
			" '$_POST[goods_desc]', '$add_user','" . $add_time . "','$goods_type','$goods_grid')";
    }
    else {
        /* 如果有上传图片，删除原来的商品图 */
        $sql = "SELECT goods_thumb, goods_img, original_img, goods_grid" .
                    " FROM " . GOODS .
                    " WHERE goods_id = '$goods_id'";
        $row = $db->selectinfo($sql);
        if ($goods_img    != $row['goods_img'])     @unlink(ROOT_PATH . $row['goods_img']);
        if ($goods_thumb  != $row['goods_thumb'])   @unlink(ROOT_PATH . $row['goods_thumb']);
        if ($goods_grid   != $row['goods_grid'])    @unlink(ROOT_PATH . $row['goods_grid']);
        if ($original_img != $row['original_img'])  @unlink(ROOT_PATH . $row['original_img']);
        $goods_state = !empty($_POST['goods_state']) ? (intval($_POST['goods_state']) == '1'?'0':0) : 0;


        $sql = "UPDATE " . GOODS . " SET " .
                "goods_name = '$_POST[goods_name]', " .
                "goods_title = '$goods_title', " .
                //"goods_name_style = '$goods_name_style', " .
                //"goods_sn = '$goods_sn', " .
                //"cat_id = '$catgory_id', " .
                //"shop_price = '$shop_price', " .
                //"market_price = '$market_price', " .
                //"update_user = '', " .
                "is_promote = '$is_promote', " .
                "promote_price = '$promote_price', " .
                //"url_title = '$url_title', " .
                "promote_start_date = '$promote_start_date', " .
                "promote_end_date = '$promote_end_date', ";

        /* 如果有上传图片，需要更新数据库 */
		$sql .= "goods_img = '$goods_img', original_img = '$original_img', ";
		$sql .= "goods_thumb = '$goods_thumb', ";
		$sql .= "goods_grid = '$goods_grid', ";
        $sql .= "keywords = '$_POST[keywords]', " .
                "goods_brief = '$_POST[goods_brief]', " .
                "seller_note = '$_POST[seller_note]', " .
                "goods_weight = '$goods_weight'," .
                "goods_number = '$goods_number', " .
                " is_login = '$is_login', " .
                //"warn_number = '$warn_number', " .
                //"add_user = '$add_user', " .
                //"add_time = '$add_time', " .
                //"is_best = '$is_best', " .
                //"is_free_shipping = '$is_free_shipping', " .
                //"is_new = '$is_new', " .
               // "is_hot = '$is_hot', " .
               // "is_on_sale = '$goods_state', " .
                "goods_desc = '$_POST[goods_desc]' " .
                //"last_update = '". gmtime() ."', ".
               // "goods_type = '$goods_type' " .
                "WHERE goods_id = '$goods_id' LIMIT 1";
    }
   $db->query($sql);
   
    /* 商品编号 */
    $goods_id = $is_insert ? $db->insertId() : $goods_id;
	
	if($syn_gallery_image!=''){
		
		 if (!$is_insert){
			 
			$sql = "SELECT img_url, thumb_url, img_original " .
					" FROM " . GGALLERY ." where  goods_id = '$goods_id' ";
			$arr = $db->arrQuery($sql);
		    foreach ($arr as $key => $row) {
                if ($row['img_url'] != '' && is_file('../' . $row['img_url'])) {
                    @unlink('../' . $row['img_url']);
                }
                if ($row['thumb_url'] != '' && is_file('../' . $row['thumb_url'])) {
                    @unlink('../' . $row['thumb_url']);
                }
                if ($row['img_original'] != '' && is_file('../' . $row['img_original'])) {
                    @unlink('../' . $row['img_original']);
                }
            }
			 $sql = "delete from ".GGALLERY." where goods_id = '$goods_id'";
			 $db->query($sql);
		 }
		
		foreach ($gallery_img_data as $value) {
		    $value['goods_id'] = $goods_id;
		    $db->autoExecute(GGALLERY, $value);
		}
	}



if ($is_insert){
	
	$fenjiArr = explode('|',$fenji);
	$_POST['volume_number'][] = $fenjiArr[0]; 
	$_POST['volume_number'][] = $fenjiArr[1]; 
	$_POST['volume_number'][] = $fenjiArr[2]; 
	$_POST['volume_number'][] = $fenjiArr[3]; 
    
	$_POST['volume_price'][]  = $shop_price + $shipping_fee  + $zhuijia_price ; 
	$_POST['volume_price'][]  = round(($shop_price*$rate[1])/$rate[0],2)  + $zhuijia_price + $shipping_fee; 
	$_POST['volume_price'][]  = round(($shop_price*$rate[2])/$rate[0],2)  + $zhuijia_price + $shipping_fee; 
	$_POST['volume_price'][]  = round(($shop_price*$rate[3])/$rate[0],2)  + $zhuijia_price + $shipping_fee; 
	
	if (isset($_POST['volume_number']) && isset($_POST['volume_price']))
	{
		$temp_num = array_count_values($_POST['volume_number']);
		foreach($temp_num as $v) {
			$v > 1 && exit('优惠数量重复！');
		}
		handle_volume_price($goods_id, $_POST['volume_number'], $_POST['volume_price']);
	}
}


  $sql  = "select count(*) from eload_goods where goods_id = '$goods_id'";
  if($db->getOne($sql)){
      echo $is_insert ? '同步添加success' : '同步修改success';
  }else{
      echo '网络超时，请稍后重试';
  }


	//handle_title_keyword($_POST['goods_title']); //处理关键字，加入到ABC索引里

	exit();
}

/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */

elseif ($_ACT == 'batch')
{
	/* 检查权限 */
	
	
    $code = empty($_GET['extension_code'])? '' : trim($_GET['extension_code']);
    /* 取得要操作的商品编号 */
    $goods_id = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;
    $_TYPE = !empty($_POST['type'])?$_TYPE = $_POST['type']:'';
    if (isset($_TYPE))
    {
        /* 放入回收站 */
        if ($_TYPE == 'trash')
        {
			
/*			$sql = "SELECT goods_thumb, goods_img, original_img,goods_id, url_title,cat_id " .
					"FROM " . GOODS .
					" WHERE goods_id " . db_create_in($goods_id);
			$res = $GLOBALS['db']->arrQuery($sql);
			foreach ($res as $row)
			{
                action_html($row,'del');
			}*/
			

            update_goods($goods_id, 'is_delete', '1');

            /* 记录日志 */
            admin_log('', '批量把', '商品列表ID为：'.$goods_id."放入了回收站");
        }
        /* 上架 */
        elseif ($_TYPE == 'on_sale')
        {
            update_goods($goods_id, 'is_on_sale', '1');
			/*$sql = "SELECT goods_thumb, goods_img, original_img,goods_id, url_title,cat_id " .
					"FROM " . GOODS .
					" WHERE goods_id " . db_create_in($goods_id);
			$res = $GLOBALS['db']->arrQuery($sql);
			foreach ($res as $row)
			{
                action_html($row,'creat');
			}*/
        }

        /* 下架 */
        elseif ($_TYPE == 'not_on_sale')
        {
            update_goods($goods_id, 'is_on_sale', '0');
		/*	$sql = "SELECT goods_thumb, goods_img, original_img,goods_id, url_title,cat_id " .
					"FROM " . GOODS .
					" WHERE goods_id " . db_create_in($goods_id);
			$res = $GLOBALS['db']->arrQuery($sql);
			foreach ($res as $row)
			{
                action_html($row,'del');
			}*/
        }

        /* 设为精品 */
        elseif ($_TYPE == 'best')
        {
            update_goods($goods_id, 'is_best', '1');
        }

        /* 取消精品 */
        elseif ($_TYPE == 'not_best')
        {
            update_goods($goods_id, 'is_best', '0');
        }

        /* 设为新品 */
        elseif ($_TYPE == 'new')
        {
            update_goods($goods_id, 'is_new', '1');
        }

        /* 取消新品 */
        elseif ($_TYPE == 'not_new')
        {
            update_goods($goods_id, 'is_new', '0');
        }

        /* 设为热销 */
        elseif ($_TYPE == 'hot')
        {
            update_goods($goods_id, 'is_hot', '1');
        }

        /* 取消热销 */
        elseif ($_TYPE == 'not_hot')
        {
            update_goods($goods_id, 'is_hot', '0');
        }

        /* 转移到分类 */
        elseif ($_TYPE == 'move_to')
        {
            update_goods($goods_id, 'cat_id', $_POST['target_cat']);
        }
        /* 还原 */
        elseif ($_TYPE == 'restore')
        {

            update_goods($goods_id, 'is_delete', '0');

            /* 记录日志 */
            admin_log('', 'batch_restore', 'goods');
        }
        /* 删除 */
        elseif ($_TYPE == 'drop')
        {

            delete_goods($goods_id);

            /* 记录日志 */
            admin_log('', 'batch_remove', 'goods');
        }
    }

        $link[0]["name"] = "返回商品列表";
        $link[0]["url"] = "goods.php";

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
	
	$dataArr = explode('||',$_POST['id']);

    $goods_id    = intval($dataArr[0]);
    $goods_field = trim($dataArr[1]);
    $val         = trim($_POST['value']);
    $db->update(GOODS," $goods_field = '$val', last_update=" .gmtime().",  update_user = '".$_SESSION["WebUserInfo"]["sa_user"]."'", "  goods_id = '$goods_id'");
	admin_log('', _EDITSTRING_,'商品ID为 '.$goods_id);
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
    

    if ($db->update(GOODS," is_delete = 1", ' goods_id='.$goods_id))
    {
        $goods = $db->selectinfo('select goods_name,url_title,cat_id from '.GOODS.' where goods_id ='.$goods_id);
		$goods_name = $goods['goods_name'];
		$path_dir = ROOT_PATH .GOODS_DIR.$goods['cat_id'].'/'.$goods['url_title'];
		if (file_exists($path_dir)){
			@unlink($path_dir);
		}
        admin_log('', '把商品'.addslashes($goods_name), '放入回收站了'); // 记录日志
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
	$goods_name = $db->getOne('select goods_name from '.GOODS.' where goods_id ='.$goods_id);
	admin_log('', '把商品'.addslashes($goods_name), '从回收站还原到了商品列表'); // 记录日志
	creat_count_category_goods_num();    //统计商品个数
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
    $sql = "SELECT goods_id, goods_name, is_delete, goods_thumb, " .
                "goods_img, original_img " .
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
    if (!empty($goods['goods_thumb']))
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
    }
    /* 删除商品 */
    $sql = "DELETE FROM " . GOODS . " WHERE goods_id = '$goods_id'";
    $db->query($sql);

    /* 记录日志 */
    admin_log('', _DELSTRING_, '商品：'.addslashes($goods['goods_name']));

    /* 删除商品相册 */
    $sql = "SELECT img_url, thumb_url, img_original " .
            "FROM " . GGALLERY .
            " WHERE goods_id = '$goods_id'";
    $res = $db->query($sql);
    while ($row = $db->fetchRow($res))
    {
        if (!empty($row['img_url']))
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
        }
    }

    $sql = "DELETE FROM " . GGALLERY . " WHERE goods_id = '$goods_id'";
    $db->query($sql);

    /* 删除相关表记录 */
    $sql = "DELETE FROM " . COLLECT . " WHERE goods_id = '$goods_id'";
    $db->query($sql);
    $sql = "DELETE FROM " . GATTR . " WHERE goods_id = '$goods_id'";
    $db->query($sql);
    $sql = "DELETE FROM " . COMMENT . " WHERE comment_type = 0 AND id_value = '$goods_id'";
    $db->query($sql);

    echo '1';
    exit;
}

/*------------------------------------------------------ */
//-- 切换商品类型
/*------------------------------------------------------ */
elseif ($_ACT == 'get_attr')
{
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
 * 返回相册数据
 * 
 * @param array $syn_gallery_image 只知道数组，不知道内容
 */
function syn_get_gallery_data($syn_gallery_image) {
    global $image, $context, $is_add_watter, $import_url;
    
    $data = array();
    $temp = array('img_url', 'thumb_url', 'img_original');
    
    foreach ($syn_gallery_image as $kk => $vv) {
        $temp_str = array();
        $temp_arr = explode('@@@', $vv);
        
        foreach ($temp_arr as $kk2 => $vv2) {
            $temp_sub = explode('@@', $vv2);
            $temp_str[$temp_sub[0]] = $temp_sub[1];
        }
        
        foreach ($temp as $value) {
            $img_syn_data = fopen_url($import_url . $temp_str[$value], false, $context);
            $filename     = ROOT_PATH . $temp_str[$value];
            file_put_contents($filename, $img_syn_data);
            
            if (!in_array(check_file_type($filename), array('jpg'))) {
                exit("图片{$filename}同步失败");
            }
        }
        
        //加水印
        if ($is_add_watter) {
            $result = $image->add_watermark(ROOT_PATH . $temp_str['img_original'], '', $GLOBALS['_CFG']['watermark'], '', $GLOBALS['_CFG']['watermark_alpha']);
            
            if (!$result) {
                echo '原图' . ROOT_PATH . $temp_str['img_original'] . '加水印失败.' . $image->error_msg;
            }
        }
        
        $data[] = $temp_str;
    }
    
    return $data;
}

/**
 * 检查大图
 * 
 */
function syn_check_original_img() {
    global $image, $is_add_watter, $context, $import_url, $goods_thumb, $goods_grid, $goods_img, $original_img;
    
    $img_arr = array(
        1 => $goods_thumb, 
        2 => $goods_grid, 
        3 => $goods_img, 
        4 => $original_img
    );
    
    foreach ($img_arr as $key => $value) {
        
        if (empty($value)) {
            continue;
        }
        
        $dir_arr             = explode('/', $value);
    	$goods_thumb_img     = $dir_arr[count($dir_arr)-1];
    	unset($dir_arr[count($dir_arr)-1]);
    	$dir                 = ROOT_PATH . join('/', $dir_arr) . '/';
    	
    	if (!is_dir($dir)) {
    	    !make_dir($dir) && exit("创建文件夹{$dir}失败，请检查权限");
    	};
    	
	    $filename     = $dir . $goods_thumb_img;
        $img_syn_data = fopen_url($import_url . $value, false, $context);
        file_put_contents($filename, $img_syn_data);
	    
        if (!in_array(check_file_type($filename), array('jpg'))) {
            exit("图片{$filename}同步失败");
        }
        
        if ($key == 4 && $is_add_watter){    //原图加水印
    		$result = $image->add_watermark($filename, '', $GLOBALS['_CFG']['watermark'], '', $GLOBALS['_CFG']['watermark_alpha']);
    		
            if (!$result) {
                echo "原图{$filename}加水印失败." . $image->error_msg;
            }
    	}
    	
    }
}
?>