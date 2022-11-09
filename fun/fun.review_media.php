<?
/*
+----------------------------------
* 评论
+----------------------------------
*/
if (!defined('INI_WEB')){die('Access denied');}
require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');
require_once(ROOT_PATH . 'lib/modules/ipb.php');
require_once(ROOT_PATH . 'lib/class.page.php');
require_once(ROOT_PATH . 'lib/lib.f.goods.php');
require_once(ROOT_PATH . 'lib/cls_image.php');

//print_r($lang);

	
$goods_id = !empty($_GET['goods_id'])?intval($_GET['goods_id']):0;
//$user_id  = !empty($_SESSION['user_id'])?intval($_SESSION['user_id']):0;
$mid      = !empty($_GET['mid'])?intval($_GET['mid']):0;
$t      = !empty($_GET['t'])?intval($_GET['t']):0;
$act      = !empty($_GET['a'])?intval($_GET['a']):'';
//die($goods_id);

$is_login_str = 'category_login_html';
if (!empty($_COOKIE['WEBF-dan_num'])) $is_login_str = 'category_html';

$catArr = read_static_cache('category_c_key',2);
$Arr['left_catArr']  = read_static_cache($is_login_str,2);

	

$Arr["shop_title"] = "Review";


$act = empty($_GET['a'])?'':$_GET['a'];
$Arr['act'] = $act;
$Arr['lang']   =  $_LANG;

//print_r($_LANG);
$Arr['shop_name'] = $_CFG['shop_name'];

$Tpl->caching = false;        //使用缓存

if($goods_id==0){
	echo "<script>alert('Product have not been found');history.back()</script>";
	exit();
}
if($mid == "" || !is_numeric($mid)){
	//echo ("<script>alert('media did not found');window.close();");
	//exit();
}

switch ($act){

case 'view_image':
	$sql = "select * from ".REVIEW_PIC." p,".REVIEW." r where p.rid=r.rid and r.is_pass=1 and r.goods_id=".$goods_id."  order by p.rid desc";
	$arr_pic = $db->arrQuery($sql);
	//echo 321;
	//print_r($arr_pic);
	$goods = get_goods_info($goods_id);
	$goods['url_title'] = get_details_link($goods['goods_id'],$goods['url_title']);
	///print_r($goods);
	$Arr['goods']  = $goods;
	$Arr['arr_pic']  = $arr_pic;	
	
	$c_path ='';
	$c_caption='';
	$c_up_by ='';
	foreach ($arr_pic as $k=>$v){
		
			$c_path = $v['paths'];
			$c_caption = $v['caption']; 
			$c_up_by = $v['nickname'];
			if($v['id'] == $mid){ 				
				break;
			}
	}
	$Arr['c_path']= $c_path;
	$Arr['c_caption']= $c_caption;
	$Arr['c_up_by']= $c_up_by;
	
	//$top_guide = 'Write a review';
	$Arr['nickname']= empty($_SESSION["firstname"])?'':$_SESSION["firstname"];
	break;

case 'view_video':
	$sql = "select * from ".REVIEW_VIDEO." p,".REVIEW." r where p.rid=r.rid and r.is_pass=1 and r.goods_id=".$goods_id."  order by p.rid desc";
	$arr_video = $db->arrQuery($sql);
	//echo 321;
	//print_r($arr_pic);
	$goods = get_goods_info($goods_id);
	$goods['url_title'] = get_details_link($goods['goods_id'],$goods['url_title']);
	///print_r($goods);
	$Arr['goods']  = $goods;
	$Arr['arr_video']  = $arr_video;	
	
	$c_path ='';
	$c_caption='';
	$c_up_by ='';
	foreach ($arr_video as $k=>$v){
		
			$c_path = $v['paths'];
			$c_caption = $v['caption']; 
			$c_up_by = $v['nickname'];
			if($v['id'] == $mid){ 				
				break;
			}
	}
	$Arr['c_path']= $c_path;
	$Arr['c_caption']= $c_caption;
	$Arr['c_up_by']= $c_up_by;
	
	//$top_guide = 'Write a review';
	$Arr['nickname']= empty($_SESSION["firstname"])?'':$_SESSION["firstname"];
	break;
}
//$Arr['top_guide'] = $top_guide;	

?>  