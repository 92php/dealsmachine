<?php
//修改产品接口
define('INI_WEB', true);
define('IMG_PATH', 'http://sourceimg02.faout.com/code/syn_img_opt.php');
set_time_limit(0);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/cls_image.php');
require_once('../lib/syn_public_fun.php');
$content =  var_export($_POST, true);

if('CS0103301' == $_POST['goods_sn']) {
	file_put_contents('../data-cache/post20140614.txt',var_export($_POST,true),FILE_APPEND); //写入日志
	file_put_contents('../logs/post20140614.txt',var_export($_POST,true)); //写入日志
}

/*$_POST = array ( 'keys_code' => 'c71d26cabebad7bce64c28bb6fea6770',
				'goods_name' => 'Exquisite and Dazzling Spaghetti Strap High-Waist Flounce Floral Print Layered Sleeveless Satin Evening Dress For Women',
				'goods_title' => 'Exquisite and Dazzling Spaghetti Strap High-Waist Flounce Floral Print Layered Sleeveless Satin Evening Dress For Women',
				'ever_title' => '',
				'china_title' => '',
				//'goods_sn' => 'YM0430202',
				'goods_sn' => 'CS0020001',
				'retail_goods_sn' => NULL,
				'lowest_sales_number' => '0',
				'goods_weight' => '1.000',
				'volume_weight' => '1.000',
				'goods_state' => '1',
				'is_add_watter' => '1',
				'keywords' => '',
				'seller_note' => '',
				'goods_brief' => '',
				'update_user' => 'csm',
				'last_update' => '1334971747',
				'shiji_price' => '41.00',
				'shop_price' => '46.00',
				'is_syn_price' => '0',
				'goods_color' => 'BLACK',
				'goods_size' => '',
				'is_syn_specification' => '0',
				'is_new_goods' => 1,
				'is_show_alone' => '2',
				'goods_attr' => array (
								0 => 'Resistive Screen',
								1 => 'MTK6516',
								2 => 'Android 2.2',
								3 => '2.2inch',
								4 => 'Tri Cards',
								5 => 'GSM(Dual Band)',
								6 => 'Analog TV',
								7 => 'Wifi',
								8 => 'Bar Phone',
								9 => 'Android Phone' 
								), 
				);*/

$keys_code = empty($_POST['keys_code'])?'':$_POST['keys_code'];
if ($keys_code!=$_CFG['keys_code']){die('Error,key code error');}

$image = new cls_image();
/* 检查货号是否重复 */
if ($_POST['goods_sn'])
{
	$sql = "SELECT COUNT(*) FROM " . GOODS ." WHERE goods_sn = '$_POST[goods_sn]'";
	if ($db->getOne($sql) > 0)
	{
		$is_insert = false;
	}else{
		/* 插入还是更新的标识*/
		echo 'Fails,goods sn is empty!';
		exit;
	}
}else{
}

$goods_id = 0;
$cat_id= 0;
$yuan_shop_price = 0;
$goods_sn   = !empty($_POST['goods_sn']) ? $_POST['goods_sn'] : '';
if ($goods_sn!=''){
	$sql = "select goods_id,cat_id,shop_price,is_free_shipping,promote_lv,promote_price from ".GOODS." WHERE  goods_sn = '$goods_sn' ";
	$goods_info = $db->selectinfo($sql);
	$goods_id   = $goods_info['goods_id'];
	$cat_id     = $goods_info['cat_id'];
	$yuan_shop_price = $goods_info['shop_price'];
    $is_free_shipping = $goods_info['is_free_shipping'];
    $promote_lv = $goods_info['promote_lv'];
    $promote_price = $goods_info['promote_price'];
}
/* 处理商品数据 */
$shop_price = !empty($_POST['shop_price']) ? floatval($_POST['shop_price']) : 0;
$chuhuo_price = $shop_price;
if(!$shop_price) die('同步失败，出货价格不能为零！');
$promote_price = !empty($_POST['promote_price']) ? floatval($_POST['promote_price'] ) : 0;
$goods_weight = $_POST['goods_weight']*1000 > 0 ? floatval($_POST['goods_weight'] ): 0;	//商品重量
$goods_volume_weight = $_POST['volume_weight']*1000 > 0 ? floatval($_POST['volume_weight']) : $goods_weight;	//商品体积重量
$goods_state = !empty($_POST['goods_state']) ? intval($_POST['goods_state'])  : 1;
$is_syn_price = !empty($_POST['is_syn_price']) ? intval($_POST['is_syn_price'])  : 0;
$is_syn_desc = !empty($_POST['is_syn_desc']) ? intval($_POST['is_syn_desc'])  : 0;
if($is_syn_desc){
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
}
$is_add_watter = !empty($_POST['is_add_watter']) ? intval($_POST['is_add_watter'])  : 0;
$goods_title  =  !empty($_POST['goods_title'])?trim($_POST['goods_title']) : '';
$ever_title  =  !empty($_POST['ever_title'])?trim($_POST['ever_title']) : '';
$goods_name_style ='+';
$goods_thumb  =  !empty($_POST['goods_thumb'])?trim($_POST['goods_thumb']) : '';
$goods_grid   =  !empty($_POST['goods_grid'])?trim($_POST['goods_grid']) : '';
$goods_img    =  !empty($_POST['goods_img'])?trim($_POST['goods_img']) : '';
$original_img =  !empty($_POST['original_img'])?trim($_POST['original_img']) : '';
$_POST['goods_desc'] = empty($_POST['everbuying_desc']) ? (empty($_POST['goods_desc']) ? '' : $_POST['goods_desc']) : $_POST['everbuying_desc'];
$_POST['goods_desc'] = str_replace('</b><br><br>','</b><br>',$_POST['goods_desc']);
$_POST['goods_desc'] = str_replace('</b> <br><br>','</b><br>',$_POST['goods_desc']);
$_POST['goods_desc'] = str_replace('<br><br><br>','<br><br>',$_POST['goods_desc']);
$_POST['goods_desc'] = str_replace('</B><BR><BR>','</B><BR>',$_POST['goods_desc']);
$_POST['goods_desc'] = str_replace('</B> <BR><BR>','</B><BR>',$_POST['goods_desc']);
$_POST['goods_desc'] = str_replace('<BR><BR><BR>','<BR><BR>',$_POST['goods_desc']);
$_POST['goods_desc'] = str_replace('www.davismicro.com.cn:9000/uploads','des.dealsmachine.com/uploads',$_POST['goods_desc']);
$_POST['goods_desc'] = str_replace('www.davismicro.com:9000/images','des.dealsmachine.com/uploads/images',$_POST['goods_desc']);
$_POST['goods_desc'] = str_replace('www.davismicro.com.cn/uploads','des.dealsmachine.com/uploads',$_POST['goods_desc']);
$_POST['goods_desc'] = str_replace('www.davismicro.com/images','des.dealsmachine.com/uploads/images',$_POST['goods_desc']);
$_POST['goods_desc'] = str_replace('<ul><br><br><b>','<ul><b>',$_POST['goods_desc']);
$_POST['goods_desc'] = str_replace('<ul><BR><BR><B>','<ul><B>',$_POST['goods_desc']);
$_POST['goods_desc'] = str_replace('<ul><br><b>','<ul><b>',$_POST['goods_desc']);
$_POST['goods_desc'] = str_replace('<ul><BR><B>','<ul><B>',$_POST['goods_desc']);
$is_new_sn = empty($_POST['is_new_goods']) ? 0 : intval($_POST['is_new_goods']);		//是老商品编码 还是 新商品编码 （0:老商品编码 1：新商品编码）
$is_show_alone = empty($_POST['is_show_alone']) ? 2 : intval($_POST['is_show_alone']);	//同类商品是否在网站列表页分开显示（商品前7位相同商品）（1：单独显示 2：不单独显示）
$goods_search_attr	= empty($_POST['goods_attr']) ? '' : ",".implode("-",$_POST['goods_attr']).",";		//商品搜索查找属性（只用户商品分类的属性搜索）
$goods_search_attr = preg_replace('/\s+/', '_', $goods_search_attr);    //替换连续空格为下划线
$goods_search_attr = str_replace("/", '^', $goods_search_attr);    //替换斜杠为下划线
$goods_search_attr = str_replace("(", '{', $goods_search_attr);    //替换（为下划线
$goods_search_attr = str_replace(")", '}', $goods_search_attr);    //替换）为下划线
$goods_search_attr = str_replace('-', ", ,", $goods_search_attr);    //把减号（-）替换成属性分割符（, ,）
if(empty($goods_volume_weight))$goods_volume_weight = $goods_weight;
if(empty($goods_weight)){
	echo '重量为零!';
	exit();
}
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
		$sql = "SELECT group_goods_id FROM " . GOODS . " WHERE goods_sn LIKE '" . $xiangtong_goods_sn . "%' AND is_show_alone = 2  GROUP BY group_goods_id ORDER BY goods_id ASC ";
		$get_group_goods_id = $db->getOne($sql);
		$group_goods_id = empty($get_group_goods_id) ? $goods_id : $get_group_goods_id;
	}
	else 
	{
		$group_goods_id = $goods_id;
	}
}

$fenleiArr      = get_zhuijia_price_and_fenlei_bili($cat_id,$chuhuo_price);  //根据出货价取出相应的比例，追加价格，个数分级
$grade          = $fenleiArr['bili'];   //比例 1.27|1.25|1.24|1.23
$fenji          = $fenleiArr['grade'];  //比例 1|2---9|10-49|50---max
$zhuijia_price  = round(($fenleiArr['zhuijia_price']/HUILV),2); //5
$rate = explode('|',$grade);
//转成美元
$shop_price = round(($shop_price/HUILV),2);
if(empty($shop_price)){
	echo "price can't be zero,pls check";
	exit();
}
$shipping_fee=0;
//如果是免运费,+平邮价格
if($is_free_shipping==1)
{
    $shipping_fee = get_shipping_fee($shop_price,$goods_weight);
}
$cost_shop_price = $shop_price;//成本价（美元）
$shop_price = round($shop_price * $rate[0],2);  //加追加价格
$import_url = 'http://usimg.davismicro.com.cn/';
if(!empty($_POST['is_syn_pic'])){
	$opts = array(
		'http'=>array(
		'method'=>'GET',
		'timeout'=>60,
		)
	);
	$context = stream_context_create($opts);
	$syn_gallery_image = empty($_POST['syn_gallery_image'])?'':$_POST['syn_gallery_image'];
    $syn_gallery_image_ser=serialize($syn_gallery_image);
    $website="ba";
    $goods_img_1     = str_replace("uploads", $website, $goods_img);
    $goods_thumb_1   = str_replace("uploads", $website, $goods_thumb);
    $goods_grid_1    = str_replace("uploads", $website, $goods_grid);
    $original_img_1  = str_replace("uploads", $website, $original_img);
    /* 如果有上传图片，删除原来的商品图 */
    $sql = "SELECT goods_thumb, goods_img, original_img, goods_grid,goods_number,promote_lv,promote_price" .
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
    $syn_gallery_image_ser1=serialize($syn_gallery_image1);
    //$post_url="http://localhost/img/code/syn_img_opt.php";
    //$post_url="http://www.faout.com/code/syn_img_opt.php";
    $post_url = IMG_PATH;
    $post_data="goods_thumb=".$row['goods_thumb']."&goods_grid=".$row['goods_grid']."&goods_img=".$row['goods_img']."&original_img=".$row['original_img']."&syn_gallery_image=$syn_gallery_image_ser1&action=del";
    echo post_image_info($post_url,$post_data);//到图片库删除相册
    $sql = "delete from ".GGALLERY." where goods_id = '$goods_id'";
    $db->query($sql);
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
	
    //图片库同步添加图片
    //$post_url="http://localhost/img/code/syn_img_opt.php";
    //$post_url="http://www.faout.com/code/syn_img_opt.php";   
    $post_url = IMG_PATH; 							  
	$post_data="goods_thumb=$goods_thumb&goods_grid=$goods_grid&goods_img=$goods_img&original_img=$original_img&syn_gallery_image=$syn_gallery_image_ser&website=$website&action=add&goods_id=$goods_id";
    echo post_image_info($post_url,$post_data);	
}

$sql = "UPDATE " . GOODS . " SET " .
	"goods_name = '$_POST[goods_name]', " .
	"goods_title = '$goods_title', " ;

/* 如果有图片需要同步，需要更新数据库 */
if(!empty($_POST['is_syn_pic'])){
	$sql .= "goods_img = '$goods_img_1', original_img = '$original_img_1', ";
	$sql .= "goods_thumb = '$goods_thumb_1', ";
	$sql .= "goods_grid = '$goods_grid_1', ";
}

//是否需要同步更新价格	
$xianshop_price = $shop_price + $shipping_fee  + $zhuijia_price;
if (($xianshop_price != $yuan_shop_price) && $is_syn_price){
	$market_price = get_market_price($xianshop_price);
	$xianshop_price = format_price($xianshop_price);  //修改销售价 fangxin 2013/10/08
	$sql .= " shop_price = '$xianshop_price', chuhuo_price = '$chuhuo_price', ";
	if(!empty($promote_lv)){
		$promote_price = round($cost_shop_price*$promote_lv+$shipping_fee,2);
		$promote_price = format_price($promote_price); //修改促销价 fangxin 2013/10/08
		$sql .= " promote_price =$promote_price,";
	}
}
$goods = $db->selectInfo("select * from ".GOODS." where goods_sn='".$_POST['goods_sn']."' limit 1");

//是否需要同步更新详细
if ($is_syn_desc){
	if(empty($_POST['goods_desc'])){
		echo 'description is empty';
		exit();
	}
	$sql .= " goods_desc = '$_POST[goods_desc]', ";
}
$keywords='';	
if(!empty($_POST['keywords'])){
	$model_arr = explode(',',$_POST['keywords']);
	foreach ($model_arr as $k=>$v){
		$keywords.= "$v,wholesale $v,cheap $v,drop ship,$v,";
	}
	$_POST['keywords'] = $keywords;
}
$_POST['goods_brief'] = "One Year Warranty, PayPal & Credit Card Accepted. Buy $goods_title at wholesale price from aHappyDeal.com";
$sql .= "seller_note  = '$_POST[seller_note]', " .
		"goods_weight = '$goods_weight'," .
		"goods_volume_weight ='$goods_volume_weight',";
		//"is_on_sale = '$is_on_sale', " .	
		if(1 == $_POST['is_superstar'])	{
			$sql .= " is_superstar = '$_POST[is_superstar]', ";		
		}
		if(0 == $_POST['is_superstar'])	{
			$sql .= " is_superstar = '$_POST[is_superstar]', ";		
		}
$sql .= "update_user    = '$_POST[update_user]', ".
		"last_update    = '". gmtime() ."',  ".
		"group_goods_id = '$group_goods_id', ".
		"is_show_alone  = '$is_show_alone', " .
		"goods_search_attr = '$goods_search_attr' " .
		"WHERE goods_id = '$goods_id' LIMIT 1";
$db->query($sql);  

/* 商品编号 */
$goods_id = $is_insert ? $db->insertId() : $goods_id;

//多语言 fangxin 2013/07/10
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
    $goods_desc = str_replace('www.davismicro.com.cn:9000/uploads','des.dealsmachine.com/uploads',$goods_desc);
    $goods_desc = str_replace('www.davismicro.com:9000/images','des.dealsmachine.com/images',$goods_desc);
    $goods_desc = str_replace('www.davismicro.com.cn/uploads','des.dealsmachine.com/uploads',$goods_desc);
    $goods_desc = str_replace('www.davismicro.com/images','des.dealsmachine.com/images',$goods_desc);
    $goods_desc = str_replace('113.106.90.72:9000/uploads', 'des.dealsmachine.com/uploads', $goods_desc);	//电信
    $goods_desc = str_replace('113.106.90.72/uploads', 'des.dealsmachine.com/uploads', $goods_desc);		//电信
    $goods_desc = str_replace('112.95.238.72:9000/uploads', 'des.dealsmachine.com/uploads', $goods_desc);	//网通
    $goods_desc = str_replace('112.95.238.72/uploads', 'des.dealsmachine.com/uploads', $goods_desc);		//网通
    $goods_desc = str_replace('<ul><br><br><b>','<ul><b>',$goods_desc);
    $goods_desc = str_replace('<ul><BR><BR><B>','<ul><B>',$goods_desc);
    $goods_desc = str_replace('<ul><br><b>','<ul><b>',$goods_desc);
    $goods_desc = str_replace('<ul><BR><B>','<ul><B>',$goods_desc);	
	$goods_desc_md5 = $_POST[$lang][3];
	$goods_color = is_array(json_decode(stripslashes_deep($_POST[$lang][4]),true))?each(json_decode(stripslashes_deep($_POST[$lang][4]),true)):'';
	$goods_size = is_array(json_decode(stripslashes_deep($_POST[$lang][5]),true))?each(json_decode(stripslashes_deep($_POST[$lang][5]),true)):'';	    		
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
			$sql = "UPDATE " . GOODS . "_".$lang." SET goods_title = '". $goods_title ."', goods_name = '". $goods_name ."', goods_desc = '". $goods_desc ."' WHERE goods_id = ". $goods_id ."";		
		} else {
			$sql = "INSERT INTO " . GOODS . "_".$lang." (goods_id, goods_title, goods_name, goods_desc, update_time)" .
					"VALUES ('$goods_id', '$goods_title', '$goods_name', '$update_time')";					
		}	
		$db->query($sql);							
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
					" VALUES ('" . $GLOBALS['public_goods_type_spec_id']['size'] . "', '" . addslashes_deep($goods_size['key']). "', '" . addslashes_deep($goods_size['value']) . "', '".$lang."')";
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

//商品属性规格（包括：商品颜色，商品尺寸）
$goods_color = !empty($_POST['goods_color']) ? trim($_POST['goods_color']) : '';		//商品颜色
$goods_size = !empty($_POST['goods_size']) ? trim($_POST['goods_size']) : '';			//商品尺寸
$is_syn_specification = !empty($_POST['is_syn_specification']) ? intval($_POST['is_syn_specification']) : 0; 		//是否更新商品属性

if(!empty($is_syn_specification))
{
	//判断商品分类是否是公共属性分类
	$sql = "SELECT goods_type FROM " . GOODS . " WHERE goods_id = " . $goods_id;
	$goods_type = $db->getOne($sql);
	if($goods_type != $GLOBALS['public_goods_type_id'])
	{
		//更新商品的类型ID
		$sql = "UPDATE " . GOODS . " SET goods_type = " . $GLOBALS['public_goods_type_id'] . " WHERE goods_id = " . $goods_id;
		$db->query($sql);
	}
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
            $attr_list[$row['attr_id']]['attr_values'] = $attr_values = explode("\n", $row['attr_values']);
            $attr_id_list[] =  $row['attr_id'];
        }

        if(empty($attr_id_list))
        {
        		echo ('添加更新商品属性规格失败，还没有设置商品公共属性规格参数信息');
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

$first_price = empty($first_price)?'':$first_price;
if (($first_price != $yuan_shop_price) && $is_syn_price){
	$fenjiArr = explode('|',$fenji);
	$_POST['volume_number'][] = $fenjiArr[0];
	$_POST['volume_number'][] = $fenjiArr[1];
	$_POST['volume_number'][] = $fenjiArr[2];
	$_POST['volume_number'][] = $fenjiArr[3];
	$_POST['volume_price'][]  = format_price($shop_price  + $zhuijia_price + $shipping_fee); //修改阶梯价 fangxin 2013/10/08
	$_POST['volume_price'][]  = round(($shop_price*$rate[1])/$rate[0],2) + $zhuijia_price + $shipping_fee;
	$_POST['volume_price'][]  = round(($shop_price*$rate[2])/$rate[0],2) + $zhuijia_price + $shipping_fee;
	$_POST['volume_price'][]  = round(($shop_price*$rate[3])/$rate[0],2) + $zhuijia_price + $shipping_fee;
	if (isset($_POST['volume_number']) && isset($_POST['volume_price']))
	{
		$temp_num = array_count_values($_POST['volume_number']);
		foreach($temp_num as $v) {
			$v > 1 && exit('优惠数量重复！');
		}
		handle_volume_price($goods_id, $_POST['volume_number'], $_POST['volume_price']);
	}
}
echo 'success!';

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