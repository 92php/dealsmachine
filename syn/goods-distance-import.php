<?php
/**
 * goods-distance-import.php erp同步产品
 *
 * @author                   wuwenlong
 * @last modify              2011-10-08 by mashanling
 */
set_time_limit(0);
define('INI_WEB', true);
define('IMG_PATH', 'http://sourceimg02.faout.com/code/syn_img_opt.php');
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once('../lib/lib_goods.php');
require_once('../lib/inc.html.php');
require_once('../lib/syn_public_fun.php');
require_once(ROOT_PATH . 'lib/class.keyword.php');
//file_put_contents('post.txt',var_export($_POST,true));  //写入日志
$_ACT = 'list';
$_ID  = '';
$goods_id = 0;
if (!empty($_REQUEST['act'])) $_ACT   = trim($_REQUEST['act']);
if (!empty($_REQUEST['id'])) $_ID     = intval(trim($_REQUEST['id']));
if (!empty($_REQUEST['goods_id'])) $goods_id     = intval(trim($_REQUEST['goods_id']));
/*------------------------------------------------------ */
//-- 商品列表，商品回收站
/*------------------------------------------------------ */
if ($_ACT == 'insert' || $_ACT == 'update') {
	require_once('../lib/cls_image.php');
	$image     = new cls_image();
	$is_insert = true;    //更新或插入标识符
    $goods_sn  = !empty($_POST['goods_sn']) ? $_POST['goods_sn'] : '';
    $goods_id  = 0;
    if ($goods_sn != '') {    //检查货号是否重复
		$sql = "select goods_id , is_free_shipping from ".GOODS." WHERE  goods_sn = '$goods_sn' ";
		$goods_info = $db->selectinfo($sql);

        if (!empty($goods_info)) {
            $is_insert = false;
			$goods_id= $goods_info['goods_id'];
        }       

    }
    $shop_price   = !empty($_POST['shop_price']) ? $_POST['shop_price'] : 0;
	$chuhuo_price = $shop_price;
	!$shop_price && exit(__FILE__ . __LINE__ . '同步失败，出货价格不能为零！');
    $cat_id       = !empty($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
    $goods_weight = !empty($_POST['goods_weight']) ? $_POST['goods_weight'] * $_POST['weight_unit'] : 0;	//商品重量
    $goods_volume_weight = !empty($_POST['volume_weight']) ? $_POST['volume_weight'] * $_POST['weight_unit'] : 0;	//商品体积重量
	if(empty($goods_volume_weight))$goods_volume_weight = $goods_weight;
	if(empty($goods_weight)){
		echo '重量为零!';
		exit();
	}
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

		$shop_price     = round($shop_price * $rate[0], 2);
		$catArr         = read_static_cache('category_c_key', 2);
		$is_login       = $catArr[$cat_id]['is_login'];
		$clang          = $catArr[$cat_id]['clang'];
		//商品是否免邮
		$is_free_shipping = isset($goods_info['is_free_shipping']) ? $goods_info['is_free_shipping'] : $catArr[$cat_id]['is_free_shipping_cate'];
		if($is_free_shipping){
			$shipping_fee   = get_shipping_fee($shop_price, $goods_weight);			
		}else{
			$shipping_fee = 0 ;
		}		
	}  
	if(empty($shop_price)){
		echo "price can't be zero,pls check<br>";
		if(empty($rate[0])) var_export($fenleiArr);
		echo "<br>";
		if(empty($rate[0])) var_export($cat_id);
		echo "<br>";		
		exit();
	}						
	$url_title          = title_to_url($_POST['goods_name']);
	$url_title_temp     = str_replace('.htm', '', $url_title);
    $market_price       = 0;//get_market_price($shop_price + $shipping_fee  + $zhuijia_price);
    $promote_price      = !empty($_POST['promote_price']) ? floatval($_POST['promote_price'] ) : 0;
    $is_promote         = empty($promote_price) ? 0 : 1;
    $promote_start_date = ($is_promote && !empty($_POST['promote_start_date'])) ? local_strtotime($_POST['promote_start_date']) : 0;
    $promote_end_date   = ($is_promote && !empty($_POST['promote_end_date'])) ? local_strtotime($_POST['promote_end_date']) : 0;
    $is_best            = !empty($_POST['is_best']) ? intval($_POST['is_best']) : 0;
    $is_new             = !empty($_POST['is_new']) ? intval($_POST['is_new']) : 0;
    $is_hot             = !empty($_POST['is_hot']) ? intval($_POST['is_hot']) : 0;
    $is_on_sale         = !empty($_POST['is_on_sale']) ? intval($_POST['is_on_sale'])  : 0;
    $is_add_watter      = !empty($_POST['is_add_watter']) ? intval($_POST['is_add_watter'])  : 0;
	$is_superstar       = !empty($_POST['is_superstar']) ? intval($_POST['is_superstar']) : 0;
    $goods_number       = isset($_POST['goods_number']) ? $_POST['goods_number'] : 99999999;
    $warn_number        = isset($_POST['warn_number']) ? $_POST['warn_number'] : 0;
    $goods_type         = isset($_POST['goods_type']) ? $_POST['goods_type'] : 0;
    $goods_title        = !empty($_POST['goods_title']) ? trim($_POST['goods_title']) : '';
    $goods_name_style   = '+';
    $ever_title         = !empty($_POST['ever_title']) ? trim($_POST['ever_title']) : '';
    $catgory_id         = $cat_id;
    $goods_thumb        =  !empty($_POST['goods_thumb']) ? trim($_POST['goods_thumb']) : '';
    $goods_grid         =  !empty($_POST['goods_grid']) ? trim($_POST['goods_grid']) : '';
    $goods_img          =  !empty($_POST['goods_img']) ? trim($_POST['goods_img']) : '';
    $original_img       =  !empty($_POST['original_img']) ? trim($_POST['original_img']) : '';
	$goods_search_attr	= empty($_POST['goods_attr']) ? '' : ",".implode("-",$_POST['goods_attr']).",";		//商品搜索查找属性（只用户商品分类的属性搜索）
	$goods_search_attr = preg_replace('/\s+/', '_', $goods_search_attr);    //替换连续空格为下划线
	$goods_search_attr = str_replace("/", '^', $goods_search_attr);    //替换斜杠为下划线
	$goods_search_attr = str_replace("(", '{', $goods_search_attr);    //替换（为下划线
	$goods_search_attr = str_replace(")", '}', $goods_search_attr);    //替换）为下划线
	$goods_search_attr = str_replace('-', ", ,", $goods_search_attr);    //把减号（-）替换成属性分割符（, ,）
    $is_new_sn = empty($_POST['is_new_goods']) ? 0 : intval($_POST['is_new_goods']);		//是老商品编码 还是 新商品编码 （0:老商品编码 1：新商品编码）
	$_POST['goods_desc'] = empty($_POST['everbuying_desc']) ? (empty($_POST['goods_desc']) ? '' : $_POST['goods_desc']) : $_POST['everbuying_desc'];
	if(empty($_POST['everbuying_desc'])){
		if(empty($_POST['goods_desc'])){
			die('描述为空.');
		}else{
			if(md5(stripslashes_deep($_POST['goods_desc'])) != $_POST['goods_desc_md5'])
				die('描述不完整.');
		}
	}else{
		if(md5(stripslashes_deep($_POST['everbuying_desc'])) != $_POST['everbuying_desc_md5'])
			die('everbuying描述不完整.');
	}		
	$_POST['goods_desc'] = str_replace('</b><br><br>' , '</b><br>' , $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('</b> <br><br>' , '</b><br>' , $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('<br><br><br>' , '<br><br>' , $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('</B><BR><BR>' , '</B><BR>' , $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('</B> <BR><BR>' , '</B><BR>' , $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('<BR><BR><BR>' , '<BR><BR>' , $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace(array('www.davismicro.com.cn:9000/uploads', 'www.davismicro.com.cn/uploads', '113.106.90.72/uploads', '112.95.238.72/uploads', '113.106.90.72:9000/uploads', '112.95.238.72:9000/uploads'),'des.dealsmachine.com/uploads',$_POST['goods_desc']);
    $_POST['goods_desc'] = str_replace(array('www.davismicro.com:9000/images', 'www.davismicro.com/images'),'des.dealsmachine.com/uploads/images',$_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('<ul><br><br><b>', '<ul><b>', $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('<ul><BR><BR><B>', '<ul><B>', $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('<ul><br><b>', '<ul><b>', $_POST['goods_desc']);
	$_POST['goods_desc'] = str_replace('<ul><BR><B>', '<ul><B>', $_POST['goods_desc']);
    $_POST['goods_brief']="One Year Warranty, PayPal & Credit Card Accepted. Buy $goods_title at wholesale price from aHappyDeal.com";
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
    $syn_gallery_image_ser=serialize($syn_gallery_image);
    $website="ba";
    $goods_img_1     = str_replace("uploads", $website, $goods_img);
    $goods_thumb_1   = str_replace("uploads", $website, $goods_thumb);
    $goods_grid_1    = str_replace("uploads", $website, $goods_grid);
    $original_img_1  = str_replace("uploads", $website, $original_img);

    /* 入库 */
    if ($is_insert) {    		
		$xianshop_price = $shop_price + $shipping_fee  + $zhuijia_price;
		$xianshop_price = format_price($xianshop_price); //修改销售价 fangxin 2013/10/08
		$promote_price  = format_price($promote_price);  //修改促销价 fangxin 2013/10/08
		$sql = "INSERT INTO " . GOODS . " (shipping_fee,peijian_price,goods_name,goods_title, goods_name_style, goods_sn, " .
			"cat_id,  shop_price, market_price,chuhuo_price ,is_promote, promote_price, " .
			"promote_start_date, promote_end_date, goods_img, goods_thumb, original_img, keywords, goods_brief, " .
			"seller_note, goods_weight, goods_number, warn_number,  is_free_shipping, is_best, is_new, is_hot,is_login, " .
			"is_on_sale, goods_desc,add_user,add_time, goods_type,goods_grid,is_new_sn,goods_volume_weight,goods_search_attr,is_superstar)" .
			"VALUES ('$shipping_fee','{$xianshop_price}','$_POST[goods_name]','$goods_title', '$goods_name_style', '$goods_sn', '$catgory_id', " .
			" '$xianshop_price', '$market_price','$chuhuo_price', '$is_promote','$promote_price', ".
			"'$promote_start_date', '$promote_end_date', '$goods_img_1', '$goods_thumb_1', '$original_img_1', ".
			"'$_POST[keywords]', '$_POST[goods_brief]', '', '$goods_weight', '$goods_number',".
			" '$warn_number',  '$is_free_shipping', '$is_best', '$is_new', '$is_hot','$is_login', '$is_on_sale', ".
			" '$_POST[goods_desc]', '$add_user','" . $add_time . "','$goods_type','$goods_grid_1','$is_new_sn',$goods_volume_weight,'$goods_search_attr','$is_superstar')";
    }
    else {
        /* 如果有上传图片，删除原来的商品图 */
        $sql = "SELECT goods_thumb, goods_img, original_img, goods_grid" .
                    " FROM " . GOODS .
                    " WHERE goods_id = '$goods_id'";
        $row = $db->selectinfo($sql);

        //图片库清空相册
        $sql = "SELECT img_url, thumb_url, img_original " .
                " FROM " . GGALLERY ." where  goods_id = '$goods_id' ";
        $arr = $db->arrQuery($sql);
        foreach ($arr as $key => $row1) {
            $syn_gallery_image1[]="img_url@@".$row1['img_url']."@@@thumb_url@@".$row1['thumb_url']."@@@img_original@@".$row1['img_original'];
        }
        @$syn_gallery_image_ser1=serialize($syn_gallery_image1);
        //$post_url="http://localhost/img/code/syn_img_opt.php";
        $post_url = IMG_PATH;
        $post_data="goods_thumb=".$row['goods_thumb']."&goods_grid=".$row['goods_grid']."&goods_img=".$row['goods_img']."&original_img=".$row['original_img']."&syn_gallery_image=$syn_gallery_image_ser1&action=del";
        echo post_image_info($post_url,$post_data);//到图片库删除相册

        $sql = "delete from ".GGALLERY." where goods_id = '$goods_id'";
        $db->query($sql);

        $goods_state = !empty($_POST['goods_state']) ? (intval($_POST['goods_state']) == '1'?'0':0) : 0;


        $sql = "UPDATE " . GOODS . " SET " .
                "goods_name = '$_POST[goods_name]', " .
                "goods_title = '$goods_title', " .
                //"goods_name_style = '$goods_name_style', " .
                //"goods_sn = '$goods_sn', " .
               // "cat_id = '$catgory_id', " .
                //"shop_price = '$shop_price', " .
                //"market_price = '$market_price', " .
                //"update_user = '', " .
                "is_promote = '$is_promote', " .
				"is_superstar = '$is_superstar', " .
                "promote_price = '$promote_price', " .
                //"url_title = '$url_title', " .
                "promote_start_date = '$promote_start_date', " .
                "promote_end_date = '$promote_end_date', ";

        /* 如果有上传图片，需要更新数据库 */
		$sql .= "goods_img = '$goods_img_1', original_img = '$original_img_1', ";
		$sql .= "goods_thumb = '$goods_thumb_1', ";
		$sql .= "goods_grid = '$goods_grid_1', ";
        $sql .= "keywords = '$_POST[keywords]', " .
                "goods_brief = '$_POST[goods_brief]', " .
                "seller_note = '$_POST[seller_note]', " .
                "goods_weight = '$goods_weight'," .
                "goods_volume_weight = '$goods_volume_weight'," .
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
    if($is_insert){
    	$db->insert(GOODS_STATE,'goods_id',$goods_id);  //产品状态表插入		
    }
	
	//多语言 fangxin 2013/07/11
	$lang_sql = "SELECT * FROM " . Mtemplates_language. " WHERE status = 1 ORDER BY orders ASC";
	$lang_arr = $db->arrQuery($lang_sql);
	foreach($lang_arr as $value) {		
		$lang = $value['title_e'];
		$goods_title = $_POST[$lang][0];
		$goods_name  = $_POST[$lang][1];
		$goods_desc  = $_POST[$lang][2];
        $goods_desc_1  = $_POST[$lang][2];
        $goods_desc = str_replace('</b><br><br>','</b><br>',$goods_desc);
        $goods_desc = str_replace('</b> <br><br>','</b><br>',$goods_desc);
        $goods_desc = str_replace('<br><br><br>','<br><br>',$goods_desc);
        $goods_desc = str_replace('</B><BR><BR>','</B><BR>',$goods_desc);
        $goods_desc = str_replace('</B> <BR><BR>','</B><BR>',$goods_desc);
        $goods_desc = str_replace('<BR><BR><BR>','<BR><BR>',$goods_desc);
        $goods_desc = str_replace('www.davismicro.com.cn/uploads','des.dealsmachine.com/uploads',$goods_desc);
        $goods_desc = str_replace('www.davismicro.com/images','des.dealsmachine.com/images',$goods_desc);
        $goods_desc = str_replace('www.davismicro.com.cn:9000/uploads','des.dealsmachine.com/uploads',$goods_desc);
        $goods_desc = str_replace('www.davismicro.com:9000/images','des.dealsmachine.com/images',$goods_desc);
        $goods_desc = str_replace('113.106.90.72:9000/uploads', 'des.dealsmachine.com/uploads', $goods_desc);	//电信
        $goods_desc = str_replace('113.106.90.72/uploads', 'des.dealsmachine.com/uploads', $goods_desc);		//电信
        $goods_desc = str_replace('112.95.238.72:9000/uploads', 'des.dealsmachine.com/uploads', $goods_desc);	//网通
        $goods_desc = str_replace('112.95.238.72/uploads', 'des.dealsmachine.com/uploads', $goods_desc);		//网通
        $goods_desc = str_replace('<ul><br><br><b>','<ul><b>',$goods_desc);
        $goods_desc = str_replace('<ul><BR><BR><B>','<ul><B>',$goods_desc);
        $goods_desc = str_replace('<ul><br><b>','<ul><b>',$goods_desc);
        $goods_desc = str_replace('<ul><BR><B>','<ul><B>',$goods_desc);		
		@$goods_desc_md5 = $_POST[$lang][3];
		@$goods_color = each(json_decode(stripslashes_deep($_POST[$lang][4]),true));
		@$goods_size = each(json_decode(stripslashes_deep($_POST[$lang][5]),true));		
		$update_time = gmtime();
		if(!empty($goods_title) && !empty($goods_name)) {
			if(empty($goods_desc)){
				die($lang . '语言描述为空.');
			}else{
				if(md5(stripslashes_deep($goods_desc_1)) != $goods_desc_md5)
					die($lang . '描述不完整.');
			}			
			$sql = "SELECT goods_id from " . GOODS . "_". $lang ." WHERE  goods_id = ".$goods_id."";
			$goods_info = $db->selectinfo($sql);		
			if (!empty($goods_info)) {
				$sql = "UPDATE " . GOODS . "_".$lang." SET goods_title = '". $goods_title ."', goods_name = '". $goods_name ."', goods_desc = '". $goods_desc ."', update_time = '". $update_time ."' WHERE goods_id = ". $goods_id ."";		
				$db->query($sql);								
			} else {
				$sql = "INSERT INTO " . GOODS . "_".$lang." (goods_id, goods_title, goods_name, goods_desc, update_time)" .
						"VALUES ('$goods_id', '$goods_title', '$goods_name', '$goods_desc', '$update_time')";		
				$db->query($sql);				
			}			
		}
		
		//多语言规格属性（颜色）
		if(!empty($goods_color))
		{
			$sql = "SELECT COUNT(*) FROM " . GOODSATTRLANG . 
					" WHERE attr_id = " . $GLOBALS['public_goods_type_spec_id']['color'] . " AND attr_value = '" .addslashes_deep($goods_color['key']). "' AND lang = '" . $lang ."'";
			$is_goods_color = $db->getOne($sql);
			if(!empty($is_goods_color))
			{
				$sql = "UPDATE " . GOODSATTRLANG . " SET attr_value_lang = '" . addslashes_deep($goods_color['value']) . "' " .
						" WHERE attr_id = " . $GLOBALS['public_goods_type_spec_id']['color'] . " AND attr_value = '" .addslashes_deep($goods_color['key']). "' AND lang = '" . $lang ."'";
			}
			else 
			{
				$sql = "INSERT INTO " . GOODSATTRLANG . " (attr_id, attr_value, attr_value_lang, lang) " . 
						" VALUES ('" . $GLOBALS['public_goods_type_spec_id']['color'] . "', '" . addslashes_deep($goods_color['key']). "', '" . addslashes_deep($goods_color['value']) . "', '".$lang."')";
			}
			$db->query($sql);
		}
		//多语言规格属性（尺寸）
		if(!empty($goods_size))
		{
			$sql = "SELECT COUNT(*) FROM " . GOODSATTRLANG . 
					" WHERE attr_id = " . $GLOBALS['public_goods_type_spec_id']['size'] . " AND attr_value = '" .addslashes_deep($goods_size['key']). "' AND lang = '" . $lang ."'";
			$is_goods_color = $db->getOne($sql);
			if(!empty($is_goods_color))
			{
				$sql = "UPDATE " . GOODSATTRLANG . " SET attr_value_lang = '" . addslashes_deep($goods_size['value']) . "' " .
						" WHERE attr_id = " . $GLOBALS['public_goods_type_spec_id']['color'] . " AND attr_value = '" .addslashes_deep($goods_size['key']). "' AND lang = '" . $lang ."'";
			}
			else 
			{
				$sql = "INSERT INTO " . GOODSATTRLANG . " (attr_id, attr_value, attr_value_lang, lang) " . 
						" VALUES ('" . $GLOBALS['public_goods_type_spec_id']['size'] . "', '" . addslashes_deep($goods_size['key']) . "', '" . addslashes_deep($goods_size['value']) . "', '".$lang."')";
			}
			$db->query($sql);	
		}
		
		unset($goods_title);
		unset($goods_name);
		unset($goods_desc);
		unset($goods_desc_md5);
		unset($goods_color);
		unset($goods_size);
	}		
	
	//$kw = new  keywords();    //生成关键词
	//$kw->create_goods_keyword($goods_id);
	//添加配件
    if($goods_id && $is_insert){
        $peijian_cache = read_static_cache('cat_peijian', 2);

        if (!empty($peijian_cache[$cat_id])) {
            $values = '';

            foreach($peijian_cache[$cat_id] as $item) {
                $values .= ",({$item['goods_id']}, {$item['price']}, {$item['sort_order']}, {$goods_id}, {$item['admin_id']})";
            }

            if ($values) {
                $values = substr($values, 1);
                $db->query('INSERT INTO ' . GROUPGOODS . "(goods_id,goods_price,sort_order,parent_id,admin_id) VALUES {$values} ON DUPLICATE KEY UPDATE goods_price=goods_price");
            }
        }

    }

    //图片库同步添加图片
    //$post_url="http://localhost/img/code/syn_img_opt.php";
    $post_url = IMG_PATH;
    //$post_data="goods_thumb=$goods_thumb&goods_grid=$goods_grid&goods_img=$goods_img&original_img=$original_img&syn_gallery_image=$syn_gallery_image_ser&website=$website&action=add";
	$post_data="goods_thumb=$goods_thumb&goods_grid=$goods_grid&goods_img=$goods_img&original_img=$original_img&syn_gallery_image=$syn_gallery_image_ser&website=$website&action=add&goods_id=$goods_id";
    echo post_image_info($post_url,$post_data);
    if($syn_gallery_image!=''){
        foreach ($syn_gallery_image as $kk => $vv) {
            $temp_str = array();
            $temp_arr = explode('@@@', $vv);
            unset($temp_arr[count($temp_arr)-1]);
            foreach ($temp_arr as $kk2 => $vv2) {
                $temp_sub = explode('@@', $vv2);
                $temp_sub[1]= str_replace("uploads", $website, $temp_sub[1]);
                $temp_str[$temp_sub[0]] = $temp_sub[1];
            }
            $temp_str['goods_id'] = $goods_id;
		    $db->autoExecute(GGALLERY, $temp_str);
        }
	}
	$fenjiArr = explode('|',$fenji);
	$_POST['volume_number'][] = $fenjiArr[0];
	$_POST['volume_number'][] = $fenjiArr[1];
	$_POST['volume_number'][] = $fenjiArr[2];
	$_POST['volume_number'][] = $fenjiArr[3];
	$_POST['volume_price'][]  = format_price($shop_price + $zhuijia_price + $shipping_fee); //修改阶梯价 fangxin 2013/10/08
	$_POST['volume_price'][]  = round(($shop_price*$rate[1])/$rate[0],2)  + $zhuijia_price +$shipping_fee;
	$_POST['volume_price'][]  = round(($shop_price*$rate[2])/$rate[0],2)  + $zhuijia_price +$shipping_fee;
	$_POST['volume_price'][]  = round(($shop_price*$rate[3])/$rate[0],2)  + $zhuijia_price +$shipping_fee;
	if (isset($_POST['volume_number']) && isset($_POST['volume_price']))
	{
		$temp_num = array_count_values($_POST['volume_number']);
		foreach($temp_num as $v) {
			$v > 1 && exit(__FILE__ . __LINE__ . '优惠数量重复！');
		}
		handle_volume_price($goods_id, $_POST['volume_number'], $_POST['volume_price']);
	}


    //商品属性规格（包括：商品颜色，商品尺寸）
	$goods_color = !empty($_POST['goods_color']) ? trim($_POST['goods_color']) : '';		//商品颜色
	$goods_size = !empty($_POST['goods_size']) ? trim($_POST['goods_size']) : '';			//商品尺寸
	$is_show_alone = empty($_POST['is_show_alone']) ? 2 : intval($_POST['is_show_alone']);		//同类商品是否在网站列表页分开显示（商品前7位相同商品）
	//当商品第一次同步过来需要添加商品规格属性
	if($is_insert)
	{
		//同一商品不同规格统一ID号（主要是用于sphinx全文检索分组使用）
		if(empty($is_new_sn))
		{
			$group_goods_id = $goods_id;
		}
		else
		{
			if($is_show_alone == 2)
			{
				$xiangtong_goods_sn = substr($goods_sn,0,7);
				$sql = "SELECT group_goods_id FROM " . GOODS . " WHERE goods_sn LIKE '" . $xiangtong_goods_sn . "%' AND is_show_alone = 2 GROUP BY group_goods_id ORDER BY goods_id ASC ";
				$get_group_goods_id = $db->getOne($sql);
				$group_goods_id = empty($get_group_goods_id) ? $goods_id : $get_group_goods_id;
			}
			else
			{
				$group_goods_id = $goods_id;
			}
		}

		//判断商品分类是否是公共属性分类
		/*$sql = "SELECT goods_type FROM " . GOODS . " WHERE goods_id = " . $goods_id;
		$goods_type = $db->getOne($sql);*/

		//if($goods_type != $GLOBALS['public_goods_type_id'])
		//{
			//更新商品的类型ID
			$sql = "UPDATE " . GOODS . " SET goods_type = " . $GLOBALS['public_goods_type_id'] . ", group_goods_id = " .$group_goods_id .", is_show_alone = " .$is_show_alone. " WHERE goods_id = " . $goods_id;
			$db->query($sql);
		//}

		if(!empty($goods_color) || !empty($goods_size))
		{
			//获得商品公共属性规格参数信息
			$sql = "SELECT attr_id, attr_name, attr_values FROM " . ATTR . " WHERE cat_id = " . $GLOBALS['public_goods_type_id'] . " AND attr_id IN (" . implode(",",$GLOBALS['public_goods_type_spec_id']) . ")";
			$attr_res = $db->query($sql);
	        $attr_list = array();
	        $attr_id_list = array();
	        while ($row = $db->fetchRow($attr_res))
	        {
	        	$attr_name = strtolower($row['attr_name']);
	            $attr_list[$row['attr_id']]['attr_id'] = $row['attr_id'];
	            $attr_list[$row['attr_id']]['attr_name'] = $attr_name;
	            $attr_list[$row['attr_id']]['attr_values'] = explode("\n", $row['attr_values']);
	            $attr_id_list[] =  $row['attr_id'];
	        }

	        if(empty($attr_id_list))
	        {
	        		sys_msg('添加更新商品属性规格失败，还没有设置商品公共属性规格参数信息', 1, array(), false);
	        }

	        //删除商品公共属性规格数据
	        $sql = "DELETE FROM " . GATTR . " WHERE goods_id = " . $goods_id . " AND attr_id " . db_create_in($attr_id_list);
	        $db->query($sql);

	        // 添加新的商品公共属性规格数据
	        foreach ($attr_list as $key => $value)
	        {
	        	if($value['attr_name'] == 'color' && !empty($goods_color))
	        	{
	        		if(!in_array($goods_color,$value['attr_values']))
	        		{
	        			$value['attr_values'][] = $goods_color;
	        			$sql = "UPDATE " . ATTR . " SET attr_values = '" . implode("\n",$value['attr_values']) ."' WHERE attr_id = " . $value['attr_id'];
	        			$db->query($sql);
	        		}
	        		$sql = "INSERT INTO " . GATTR . " (goods_id , attr_id , attr_value) " .
	        	   		   " VALUES ($goods_id , $key , '$goods_color')";
	        	    $db->query($sql);
	        	}
	        	elseif ($value['attr_name'] == 'size' && !empty($goods_size))
	        	{
	        		if(!in_array($goods_size,$value['attr_values']))
	        		{
	        			$value['attr_values'][] = $goods_size;
	        			$sql = "UPDATE " . ATTR . " SET attr_values = '" . implode("\n",$value['attr_values']) ."' WHERE attr_id = " . $value['attr_id'];
	        			$db->query($sql);
	        		}
	        		$sql = "INSERT INTO " . GATTR . " (goods_id , attr_id , attr_value) " .
	        	   		   " VALUES ($goods_id , $key , '$goods_size')";
	        	    $db->query($sql);
	        	}
	        }
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
$_ACT = $_ACT == 'msg'?'msg':'';
if($_ACT)temp_disp();


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
                exit(__FILE__ . __LINE__ . "图片{$filename}同步失败");
            }
            $temp_str[$value . 'data'] = $img_syn_data;
        }

        //加水印
        if ($is_add_watter) {
            $result = $image->add_watermark(ROOT_PATH . $temp_str['img_original'], '', $GLOBALS['_CFG']['watermark'], '', $GLOBALS['_CFG']['watermark_alpha']);
            if (!$result) {
                echo __FILE__ . __LINE__ . '原图' . ROOT_PATH . $temp_str['img_original'] . '加水印失败.' . $image->error_msg;
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
    	    !make_dir($dir) && exit(__FILE__ . __LINE__ . "创建文件夹{$dir}失败，请检查权限");
    	};
	    $filename     = $dir . $goods_thumb_img;
        $img_syn_data = fopen_url($import_url . $value, false, $context);
        file_put_contents($filename, $img_syn_data);
        if (!in_array(check_file_type($filename), array('jpg'))) {
            exit(__FILE__ . __LINE__ . "图片{$filename}同步失败");
        }
        if ($key == 4 && $is_add_watter){    //原图加水印
    		$result = $image->add_watermark($filename, '', $GLOBALS['_CFG']['watermark'], '', $GLOBALS['_CFG']['watermark_alpha']);
            if (!$result) {
                echo __FILE__ . __LINE__ . "原图{$filename}加水印失败." . $image->error_msg;
            }
    	}
    }
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