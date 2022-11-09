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
require_once(ROOT_PATH . 'lib/lib.f.goods.php');
global $cur_lang_url;

$goods_id = !empty($_GET['goods_id'])?intval($_GET['goods_id']):0;
$user_id  = !empty($_SESSION['user_id'])?intval($_SESSION['user_id']):0;
if($cur_lang !='en'){
	$catArr =  read_static_cache($cur_lang.'_category_c_key',2);
}else {
	$catArr =  read_static_cache('category_c_key',2);
}
$Arr['left_catArr']  = getDynamicTree(0,1);
$Arr['shop_name']    = $_CFG['shop_name'];
$act                 = empty($_GET['a'])?'':$_GET['a'];
$Arr['act']          = $act;
$Arr['lang']         = $_LANG;
$page                = empty($_GET['page'])?'1':intval($_GET['page']);

function get_youtube_code($target_str,$pattern) {
	preg_match($pattern, $target_str, $result);
	if(!empty($result[1]))
		return $result[1];
	elseif(!empty($result[0]))
		return $result[0];
	else{
		return $target_str;
	}
}

$Tpl->caching = false;        //使用缓存
switch ($act){

case 'show_video_pic':
	$video_code = empty($_GET['code'])?'':$_GET['code'];
	$url="http://img.youtube.com/vi/$video_code/default.jpg";
	echo fopen_url($url);
	exit();
	break;

case 'write_review':
	/*
     * 检查用户是否已经登录
     * 如果用户已经登录了则检查是否有默认的收货地址
     * 如果没有登录则跳转到登录和注册页面
     */
	if($goods_id == 0){
		echo "<script>alert('". $_LANG['product_have_not_been_found'] ."');history.back()</script>";
		break;
	}
	check_is_sign();//检查是否登录
	check_is_bought($user_id,$goods_id);//检查是否买过该产品
	check_is_reviewed($user_id,$goods_id);//检查是否评论过该产品
	$goods               = get_goods_info($goods_id);
	check_is_avail_goods($goods);
	$goods['url_title']  = get_details_link($goods['goods_id'],$goods['url_title']);
	$Arr['goods']        = $goods;
	$top_guide           = $_LANG['Write_a_review'];//'Write a review';
	$Arr["shop_title"]   = $top_guide.'-'.$Arr['shop_name'] ;
	$Arr['nickname']     = empty($_SESSION["firstname"])?'':$_SESSION["firstname"];
	break;

case 'save_review':
	check_is_sign();//检查是否登录
	if($goods_id == 0){
		echo "<script>alert('". $_LANG['product_have_not_been_found'] ."');history.back()</script>";
		break;
	}
	check_is_reviewed($user_id,$goods_id);//检查是否评论过该产品
	$goods       = get_goods_info($goods_id);
	//获取图片 fangxin 2013-12-25
	$pics = empty($_POST['pics'])?'':trim($_POST['pics']);
	$err_msg     = "";
	$review['goods_id']  = $goods_id;
	$review['subject']   = !empty($_POST['subject'])?HtmlEncode(trim($_POST['subject'])):'';
	$review['user_id']   = $user_id;
	$review['nickname']  = !empty($_POST['nickname'])?trim($_POST['nickname']):'';
	if($review['nickname'] != '' && strpos($review['nickname'],"@") !== false){
		$n                  = explode('@',$review['nickname']);
		$review['nickname'] = $n[0];
	}
	$review['rate_price']      = !empty($_POST['rate_price'])?trim($_POST['rate_price']):'';
	$review['rate_easyuse']    = !empty($_POST['rate_easyuse'])?trim($_POST['rate_easyuse']):'';
	$review['rate_quality']    = !empty($_POST['rate_quality'])?trim($_POST['rate_quality']):'';
	$review['rate_usefulness'] = !empty($_POST['rate_usefulness'])?trim($_POST['rate_usefulness']):'';
	$review['rate_overall']    = !empty($_POST['rate_overall'])?trim($_POST['rate_overall']):'';
    $review['is_pass']         = $review['rate_overall'] > 3 ? 0 : 3;
	$review['pros']            = !empty($_POST['pros'])?HtmlEncode(trim($_POST['pros'])):'';
	$review['cons']            = !empty($_POST['cons'])?HtmlEncode(trim($_POST['cons'])):'';
	$review['other_thoughts']  = !empty($_POST['other_thoughts'])?HtmlEncode(trim($_POST['other_thoughts'])):'';
	$review['ips']             = real_ip();
	$now                       = gmtime();
	//自己人自定义评论时间 by mashanling on 17:42 2012-07-20
	if(!empty($_SESSION['WebUserInfo']) && isset($_POST['adddate']) && local_strtotime($_POST['adddate'])){
		$review['adddate'] =  local_strtotime($_POST['adddate']);
	}else{
		$review['adddate'] =  $now;
	}

	$review['addtime_real'] = $now;
	$video_title            = !empty($_POST['video_title'])?trim($_POST['video_title']):'';
	$video_url              = !empty($_POST['video_url'])?trim($_POST['video_url']):'';


	if(!empty($_SESSION["WebUserInfo"]["said"])&&gmtime()>=$review['adddate']){
		$review['is_pass'] = 1;
	}

	if($goods_id==0){
		echo "<script>alert('". $_LANG['product_have_not_been_found'] ."');history.back()</script>";
		exit();
	}
	if($review['subject'] == '' || $review['nickname'] == '' || $review['rate_overall'] == '' || $review['pros'] == '' || $review['cons'] == ''){
		echo "<script>alert('". $_LANG['the_fields_with'] ."');history.back()</script>";
		exit();
	}
	$statusArr=$db->autoExecute(REVIEW,$review);
	$rid  = $db->insertId();
	//保存图片 fangxin 2013-12-25
	$is_upload_pic = $is_upload_video = 0;	//自己分评论是否有上次图片或者视频用于积分赠送计算
	$pics_arr = explode(',', $pics);
	foreach ($pics_arr as $key => $pic) {
    	if(empty($pic)){
    		continue;
    	}
    	$pathinfo = pathinfo($pic);
    	$filename = explode('_', $pathinfo['filename']);
        $filename = $filename[0];
        $thumb_pic = "{$pathinfo['dirname']}/thumb/{$filename}_thumb.{$pathinfo['extension']}";
		$review_pic['paths'] = $pic;
		$review_pic['thumb_paths'] = $thumb_pic;
		$review_pic['goods_id']	= $goods_id;
		$review_pic['adddate']	= gmtime();
		$review_pic['rid']	= $rid;
		$statusArr=$db->autoExecute(REVIEW_PIC,$review_pic);
		$is_upload_pic      = 1;		//自己人评论送积分
		unset($review_pic);
	}

	if(is_array($_POST['caption'])) {
		foreach($_POST['caption'] AS $key =>$cap){   //保存图片
			   $upload = array(
					'name'     => $_FILES['pic']['name'][$key],
					'type'     => $_FILES['pic']['type'][$key],
					'tmp_name' => $_FILES['pic']['tmp_name'][$key],
					'size'     => $_FILES['pic']['size'][$key],
					'error'    => $_FILES['pic']['error'][$key],
				);
				$original_img = $image->upload_image($upload,'review_upload');
				$thumb        = $image->make_thumb( $original_img,80,0,"uploads/review_upload/thumb/");  //生成缩略图
				$pic          = array();
				if($original_img){
					$pic['paths']       = $original_img;
					$pic['thumb_paths'] = $thumb;
					$pic['caption']	    = $cap;
					$pic['goods_id']	= $goods_id;
					$pic['adddate']	    = gmtime();
					$pic['rid']	        = $rid;
					$statusArr          = $db->autoExecute(REVIEW_PIC,$pic);
					$is_upload_pic      = 1;		//自己人评论送积分
				}
		 }
	 }
     if(!empty($video_title)){
     	$video['caption']  = HtmlEncode($video_title);
     	$pattern           = "/.*youtube.com\/watch\?v=(\w+)/i";
     	$video['paths']    = get_youtube_code($video_url,$pattern);
     	$video['rid']      = $rid;
     	$video['goods_id'] = $goods_id;
     	$video['adddate']  = gmtime();
     	$statusArr         = $db->autoExecute(REVIEW_VIDEO,$video);
     	$is_upload_video   = 1;		//自己人评论送积分
     }
     if (!empty($_SESSION['WebUserInfo']) && $rid) {//自己人评论送积分
     	$point       = 10;
	    if ($is_upload_pic > 0 && $is_upload_video > 0) {
            $point   = 25;
        }
        elseif ($is_upload_video > 0) {
            $point   = 25;
        }
        elseif ($is_upload_pic > 0) {
            $point   = 20;
        }
        $passed_count = $db->count_info(REVIEW, '*', 'is_pass=1 AND goods_id=' .$goods_id);
        $point        = $passed_count <= 5 ? $point * 2 : $point;
        $update_value = 'is_get_point=1,get_point=' . $point;
        $where        = 'rid=' . $rid;
        $db->update(REVIEW, $update_value, $where);
	}
	$url = "/". $cur_lang_url ."m-review-a-review_ok-goods_id-$goods_id.htm";
	header("Location: $url");
	exit();
	break;

case 'review_ok':
	$top_guide          = $_LANG['your_review_has'];
	$Arr["shop_title"]  = $top_guide.'-'.$Arr['shop_name'] ;
	$goods              = get_goods_info($goods_id);
	$goods['url_title'] = get_details_link($goods['goods_id'],$goods['url_title']);
	$Arr['goods']       = $goods;
	break;

case 'write_inquiry':
	check_is_sign();
	//$Arr['type_arr']    = read_static_cache('product_inquiry_type', FRONT_STATIC_CACHE_PATH);
	$Arr['type_arr']    = $_LANG['review_select'];

	$top_guide          = $_LANG['submit_an_inquiry'];
	$Arr["shop_title"]  = $top_guide.'-'.$Arr['shop_name'] ;
	$goods              = get_goods_info($goods_id);
	$goods['url_title'] = get_details_link($goods['goods_id'],$goods['url_title']);
	$Arr['goods']       = $goods;
	$Arr['nickname']    = empty($_SESSION["firstname"])?'':$_SESSION["firstname"];
	break;

case 'save_inquiry':
	check_is_sign();
    $now = gmtime();
    $type_arr                = read_static_cache('product_inquiry_type', FRONT_STATIC_CACHE_PATH);
	$inquiry                 = array();
	$inquiry['goods_id']     = $goods_id;
	$inquiry['user_id']      = $user_id;
	$inquiry['nickname']     = !empty($_POST['i_nickname'])?trim($_POST['i_nickname']):'';
	if($inquiry['nickname'] !=''&&strpos($inquiry['nickname'],"@")!==false){
		$n                   = explode('@',$inquiry['nickname']);
		$inquiry['nickname'] = $n[0];
	}
	$inquiry['i_content']    = !empty($_POST['i_content'])?trim($_POST['i_content']):'';
	$inquiry['adddate']      = $now;
	$inquiry['ips']          = real_ip();
	$inquiry['type']         = isset($_POST['type']) ? intval($_POST['type']) : 0;
	$inquiry['type']         = isset($type_arr[$inquiry['type']]) ? $inquiry['type'] : 0;
	$inquiry['type']         = IS_LOCAL ? $inquiry['type'] : 1;
	$inquiry['lang']         = empty($_POST['lang'])? $default_lang : trim($_POST['lang']);
	if($goods_id == 0){
		echo "<script>alert('". $_LANG['product_have_not_been_found'] ."');history.back()</script>";
		exit();
	}
	if($inquiry['i_content']==''||$inquiry['nickname']=='' || !$inquiry['type']){
		echo "<script>alert('". $_LANG['the_fields_with'] ."');history.back()</script>";
		exit();
	}
    if (!empty($_FILES)) { //检查上传的图片
        require_once(ROOT_PATH . 'lib/cls_image.php');
        $image   = new cls_image();
        $err_msg = '';
        $err_arr = array('first', 'second', 'third');
        foreach ($_FILES['pic']['error'] as $key => $value) {
            if (0 == $value) {
                if (!$image->check_img_type($_FILES['pic']['type'][$key])) {
                    $err_msg .= "". $_LANG['the'] ." {$err_arr[$key]} ". $_LANG['image_format_is_error'] ."" . '\n';
                }
                else {
                    $_FILES['pic']['uploaded'][] = array(
                        'name'      => $_FILES['pic']['name'][$key],
                        'type'      => $_FILES['pic']['type'][$key],
                        'tmp_name'  => $_FILES['pic']['tmp_name'][$key],
                        'size'      => $_FILES['pic']['size'][$key],
                        'error'     => $value,
                        'caption'   => empty($_POST['caption'][$key]) ? '' : HtmlEncode($_POST['caption'][$key]),
                    );
                }
            }
            elseif (1 == $value  || 2 == $value) {
                $err_msg.= "". $_LANG['the'] ." {$err[$key]} ". $_LANG['image_must_be_below_2M'] ."" . '\n';
            }
        }
        $err_msg && exit("<script>alert('$err_msg');history.back();</script>");//有问题，后退
    }
    $db->autoExecute(PRO_INQUIRY,$inquiry);
    $id = $db->insertId();
    if ($id && !empty($_FILES['pic']['uploaded'])) {
        foreach ($_FILES['pic']['uploaded'] as $key => $upload){   //保存图片
            $original_img   = $image->upload_image($upload, 'review_upload');
            $thumb          = $image->make_thumb( $original_img, 80,0, 'uploads/review_upload/thumb/');  //生成缩略图
            $pic            = array();
            if($original_img){
                $pic['paths'] = $original_img;
                $pic['thumb_paths'] = $thumb;
                $pic['caption']	= $upload['caption'];
                $pic['adddate']	= $now;
                $pic['rid']	= $id;
                $pic['goods_id'] = $goods_id;
                $db->autoExecute(PRO_INQUIRY_PIC, $pic);
            }
        }
    }
	$url = "/".$cur_lang_url."m-review-a-inquiry_ok-goods_id-$goods_id.htm";
	header("Location: $url");
	exit();
	break;

case 'inquiry_ok':
	$top_guide = $_LANG['your_inquiry'];
	$goods = get_goods_info($goods_id);
	$goods['url_title'] = get_details_link($goods['goods_id'],$goods['url_title']);
	$Arr['goods'] = $goods;
	break;

case 'view_review':
	$top_guide = $_LANG['customer_reviews'];
	$Arr["shop_title"] = $top_guide.'-'.$Arr['shop_name'] ;
	$goods = get_goods_info($goods_id);
	$goods['url_title'] = get_details_link($goods['goods_id'],$goods['url_title']);
	$Arr['goods'] = $goods;
	if(empty($goods)){
		echo $_LANG['item_do_not_exist'];
		exit();
	}
	$same_cat_goods  = get_hot_product_by_cat_id($goods['cat_id'],'');
	$Arr['same_cat_goods'] = $same_cat_goods;
	if(empty($_GET['page'])) $_GET['page']=1;
    $size = 12;
    $review=get_review($goods_id,$_GET['page'],$size);  //评论
    $record_count = $review['review_count'];
	$_GET['page'] = empty($_GET['page'])?1:intval($_GET['page']);
	$page_count = ceil($record_count/$size);
	if ($_GET['page'] > $page_count ) $_GET['page'] = $page_count;
	if ($_GET['page'] < 1 ) $_GET['page'] = 1;
	$start = ($_GET['page'] - 1) * $size;
	$Arr['danqian_url'] = $_SERVER['PATH_INFO'];	//当前URL
	$Arr['page'] = $page;
	$Arr['review'] = $review;
    $page=new page(array('total' => $record_count,'perpage'=>$size));
	$Arr["pagestr"]  = $page->show(5);
	break;

case 'view_inquiry':
	$top_guide             = $_LANG['customer_inquiries'];
	$Arr["shop_title"]     = $top_guide.'-'.$Arr['shop_name'] ;
	$goods                 = get_goods_info($goods_id);
	$goods['url_title']    = get_details_link($goods['goods_id'],$goods['url_title']);
	$Arr['goods']          = $goods;
	$same_cat_goods        = get_same_cat_goods($goods['cat_id'],'');
	$Arr['same_cat_goods'] = $same_cat_goods;
	if(empty($_GET['page'])) $_GET['page'] = 1;
    $size                  = 12;
    $inquiry               = get_inquiry($goods_id,$_GET['page'],$size);  //评论
    $inquiry_count         = $inquiry['inquiry_count'];
	$_GET['page']          = empty($_GET['page'])?1:intval($_GET['page']);
	$page_count            = ceil($inquiry_count/$size);
	if ($_GET['page'] > $page_count ) $_GET['page'] = $page_count;
	if ($_GET['page'] < 1 ) $_GET['page']           = 1;
	$start                 = ($_GET['page'] - 1) * $size;
	$Arr['inquiry']        = $inquiry;
    $page                  = new page(array('total' => $inquiry_count,'perpage'=>$size));
	$Arr["pagestr"]        = $page->show(5);
	break;
case 'save_review_reply':
	$top_guide          = $_LANG['your_review_has'];
	$goods              = get_goods_info($goods_id);
	$goods['url_title'] = get_details_link($goods['goods_id'],$goods['url_title']);
	$re_content         = empty($_POST['re_content'])?'':$_POST['re_content'];
	$re_nickname        = empty($_POST['re_nickname'])?'':$_POST['re_nickname'];
	$rid                = empty($_POST['rid'])?0:$_POST['rid'];
	if($rid == 0){
		echo $_LANG['review_infomation_miss'];
		exit();
	}
	if($re_content==''||$re_nickname==''){
		echo $_LANG['the_fields_with'];
		exit();
	}
	$reply['re_content'] = HtmlEncode($re_content);
	$reply['re_nickname'] = $re_nickname;

	if($reply['re_nickname'] !=''&&strpos($reply['re_nickname'],"@")!==false){
		$n=explode('@',$reply['re_nickname']);
		$reply['nickname'] = $n[0];
	}

	$reply['user_id'] = $user_id;
	$reply['ips'] = real_ip();
	$reply['rid'] = $rid;
	$reply['adddate'] = gmtime();
	$db->autoExecute(REVIEW_REPLY,$reply);
	echo "success";
	exit();
	break;

case 'review_helpful_num':
	//检查是否已经登录
	if ($user_id == 0)
    {
    	echo 'no_login';
    	exit();
    }

	$help_type = empty($_GET['help_type'])?0:intval($_GET['help_type']);
	$review_id = empty($_GET['review_id'])?0:intval($_GET['review_id']);
	if(empty($review_id))
	{
		echo 0;
	}
	else
	{
		if($user_id == 538586)
		{
			$is_ok = 0;
		}
		else
		{
			//判断用户是否已经评价过
			$search_time = gmtime() - 24*60*60;
			$sql = "SELECT COUNT(*) FROM " . REVIEW_HELPFUL ." WHERE rid = " . $review_id . " AND user_id = " . $user_id . " AND add_time >= " . $search_time;
			$is_ok = $GLOBALS['db']->getOne($sql);
		}
		if(!$is_ok)
		{
			//更新评价数
			if($help_type == 0)
			{
				$sql_insert = "UPDATE " . REVIEW ." SET helpful_yes = helpful_yes + 1 WHERE rid = " . $review_id;
				$sql_select = "SELECT helpful_yes FROM " . REVIEW ." WHERE rid = " . $review_id;
			}
			else
			{
				$sql_insert = "UPDATE " . REVIEW ." SET helpful_no = helpful_no + 1 WHERE rid = " . $review_id;
				$sql_select = "SELECT helpful_no FROM " . REVIEW ." WHERE rid = " . $review_id;
			}
			$GLOBALS['db']->query($sql_insert);
			//记录用户是否已经评论过
			$sql = "INSERT INTO " .REVIEW_HELPFUL. " (rid,user_id,review_helpful_type,add_time) VALUES(" .$review_id.",".$user_id.",".$help_type.",".gmtime().")";
			$GLOBALS['db']->query($sql);
			//计算用户当前评价次数，如果小于10次，则赠送一个积分
			$date       = explode(" ",local_date('Y-m-d H:i:s',gmtime()));
			list($year,$month,$day) = explode("-",$date[0]);
			$start_time = local_mktime(0,0,0,$month,$day,$year);
			$end_time   = local_mktime(23,59,59,$month,$day,$year);
			$sql        = "SELECT COUNT(*) FROM " . REVIEW_HELPFUL . " WHERE user_id = " . $user_id . " AND add_time >= " . $start_time . " AND add_time <= " . $end_time;
			if($GLOBALS['db']->getOne($sql) <= 10)
			{
				//赠送积分
				$note = $_LANG['gained_1_dm_points'];
				add_point($user_id,1,2,$note);
			}

			//获得当前评论的评价信息
			$review_help_num = $GLOBALS['db']->getOne($sql_select);
			echo $review_help_num;
		}
		else
		{
			echo -1;
		}
	}
	exit();
	break;

}
$Arr['top_guide'] = $top_guide;

/**
 * 获得指定同类商品
 *
 * @param   str
 * @return  array
 */
function get_same_cat_goods($cat_id,$is_rand = '')
{
    $sql = 'SELECT g.goods_id, g.goods_title, g.goods_thumb,g. url_title, g.goods_grid,g.goods_number,g.cat_id, g.shop_price AS org_price, ' .
           'g.shop_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
           ' FROM ' . GOODS . ' AS g   ' .
           "WHERE  g.is_on_sale = 1 and g.is_login = 0 and g.is_alone_sale = 1 and g.is_delete = 0 and goods_number>0 AND g.cat_id = '$cat_id' ".
           " order by week2sale desc limit 10";
    $res = $GLOBALS['db']->query($sql);
    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $arr[$row['goods_id']]['cat_id']     = $row['cat_id'];
		$arr[$row['goods_id']]['goods_number']     = $row['goods_number'];
		$arr[$row['goods_id']]['goods_id']     = $row['goods_id'];
        $arr[$row['goods_id']]['cat_id']     = $row['cat_id'];
        $arr[$row['goods_id']]['goods_title']   = $row['goods_title'];
        $arr[$row['goods_id']]['short_name']   = sub_str($row['goods_title'],30);
        $arr[$row['goods_id']]['goods_thumb']  = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_grid']    = get_image_path($row['goods_id'], $row['goods_grid']);
        $arr[$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
        $arr[$row['goods_id']]['url_title']           = get_details_link($row['goods_id'],$row['url_title']);
        if ($row['promote_price'] > 0)
        {
            $arr[$row['goods_id']]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $arr[$row['goods_id']]['formated_promote_price'] = price_format($arr[$row['goods_id']]['promote_price']);
        }
        else
        {
            $arr[$row['goods_id']]['promote_price'] = 0;
        }
    }
    return $arr;
}

function check_is_bought($user_id,$goods_id){
	global $db, $_LANG;
	$sql   = "select count(*) from ".ODRGOODS." g,".ORDERINFO." i where (order_status between 1 and 9) and  g.order_id=i.order_id and goods_id =$goods_id and user_id=$user_id";
	$count = $db->getOne($sql);
	if($count == 0){
		if(empty($_SESSION["WebUserInfo"]["said"])){
			$url_contect[] = $_LANG['back_to_the_product']; //"Click here to go back to the product";
			$url_link[]    = get_details_link($goods_id);
			show_message($_LANG['back_to_write_a_review'],$url_contect,$url_link,'warning');
			exit();
		}
	}
}

function check_is_avail_goods($goods){
	global $db, $_LANG;
	if(empty($goods)) return "goods is empty";
	if($goods['is_on_sale']==0 || $goods['is_delete']==1){
			$url_contect[] = "Click here to go back to the product";
			$url_link[]    = get_details_link($goods['goods_id']);
			show_message($_LANG['sorry_this_product'],$url_contect,$url_link,'warning');
			exit();
	}
}

function check_is_reviewed($user_id,$goods_id){
	global $db, $_LANG;
	$sql   = "select count(*) from ".REVIEW." where goods_id =$goods_id and user_id=$user_id";
	$count = $db->getOne($sql);
	if($count>0){
		if(empty($_SESSION["WebUserInfo"]["said"])){
			$url_contect[] = "Click here to go back to the product";
			$url_link[]    = get_details_link($goods_id);
			show_message($_LANG['you_have_already_writen'],$url_contect,$url_link,'warning');
			exit();
		}
	}
}
?>