<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once('../lib/lib_goods.php');
require_once('../lib/param.class.php');
require_once(ROOT_PATH . 'lib/syn_public_fun.php');
$_ACT = 'list';
$_ID  = '';
$goods_id = 0;
admin_priv('review_list');    //检查权限

if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));
if (!empty($_GET['goods_id'])) $goods_id = intval(trim($_GET['goods_id']));
$rid = !empty($_GET['rid']) ? $_GET['rid'] : 0;

if (!empty($_POST['checkboxes'])) {
    $val_arr = explode(',', $_POST['checkboxes']);    //批量操作id
    foreach ($val_arr as $v) {
        $v_arr     = explode('|', $v);
        $rid_arr[] = $v_arr[0];    //评论id
        $gid_arr[] = $v_arr[1];    //商品id
    }
    $rid     = join(',', $rid_arr);
}

$pass_value  = ",pass_admin='{$_SESSION['WebUserInfo']['real_name']}',pass_time=" . gmtime();
$v = isset($_SESSION['review_url']) ? $_SESSION['review_url'] : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');

/*在具体产品的评论页面，点击“通过审核”或者不通过，待处理等操作后，评论列表 会自动的更新跳转到下面 未处理的评论. 同理，等 评论列表首页的操作选项*/
//需求就是这样 by mashanling on 2013-04-11 15:13:20
/*if ($v) {
    if (strpos($v, '?')) {

        if (strpos($v, 'status=')) {
            $v = preg_replace('/status=\d+/', 'status=0', $v);
        }
        else {
            $v .= '&status=0';
        }
    }
    else {
        $v .= '?status=0';
    }
}*/

$_SERVER['HTTP_REFERER'] = $v;

/*------------------------------------------------------ */
//--
/*------------------------------------------------------ */

if ($_ACT == 'list' || $_ACT == 'trash') {
    $_SESSION['review_url'] = $_SERVER['REQUEST_URI'];
    $cat_id = 0;
    $Arr['cat_list'] = '';

    if (!empty($_GET['cat_id'])) {
        $cat_id_arr = map_int($_GET['cat_id'], true);

        if ($cat_id_arr) {
            $cat_id = end($cat_id_arr);
            array_unshift($cat_id_arr, 0);

            foreach ($cat_id_arr as $k => $v) {
                $selected = isset($cat_id_arr[$k + 1]) ? $cat_id_arr[$k + 1] : $cat_id;
                $Arr['cat_list'] .=  get_lei_select($v, 'cat_id[]','cat_id','goods_cat', $selected,'','所有分类');
            }
        }
        else {
            $Arr['cat_list'] =  get_lei_select('0','cat_id[]','cat_id','goods_cat','','','所有分类');
        }
    }
    else {

        $Arr['cat_list'] =  get_lei_select('0','cat_id[]','cat_id','goods_cat','','','所有分类');
    }
	$Arr['column_arr'] = array(
	    'g.goods_title'   => '商品标题',
	    'g.goods_sn'      => '商品编号',
	    'g.goods_id'      => '商品id',
	    'u.user_id'       => '用户id',
	    'u.email'		  => '用户邮箱',
	    'r.user_id'       => '评论库',
	    'reviewlib'       => '非评论库',
	    'r.is_top'       => '置顶',
	);

	$Arr['status_arr'] = array(
	    99 => '--状态--',
	    0  => '未审核',
	    1  => '已通过审核',
	    2  => '未通过审核',
	    3  => '等待处理',
	    4  => '有未审核回复',
	);

	$Arr['pic_arr'] = array(
	    99 => '--包含图片--',
	    1  => '包含',
	    0  => '不包含',
	);

	$Arr['video_arr'] = array(
	    99 => '--包含视频--',
	    1  => '包含',
	    0  => '不包含',
	);

	new_get_reviews();
}

elseif ($_ACT == 'view'){
	$goods_id = !empty($_GET['goods_id']) ? $_GET['goods_id'] : 0;
	$rid = !empty($_GET['rid']) ? $_GET['rid'] : 0;
	$goods = get_goods_info($goods_id);
	//print_r($goods);
	$goods['url_title'] = get_details_link($goods['goods_id'],$goods['url_title']);
	$Arr['goods'] = $goods;
	$review=get_reviews($goods_id,$rid);  //评论
	//print_r($review);
	//exit();
	$review['user_all_review_count'] = $db->getOne('select count(*) from '.REVIEW.' where user_id='.$review['review_list'][0]['user_id']);
	$review['user_pass_review_count'] = $db->getOne('select count(*) from '.REVIEW.' where is_pass=1 and user_id='.$review['review_list'][0]['user_id']);
	//
	//exit();

	$Arr['review']  = $review;

}
elseif ($_ACT == 'del'){
    !isset($rid_arr) && exit('参数错误');

    $db->delete(REVIEW, "rid IN ({$rid})");
    //stat_goods_view_stat('SELECT goods_id FROM ' . REVIEW . " WHERE rid IN($rid)");
	admin_log('', '批量删除评论。id：', $rid);
	exit();
}
elseif ($_ACT == 'unpass_review'){
	require_once(ROOT_PATH . 'eload_admin/email_temp/mail_conf.php');
    if (!isset($gid_arr)) {
        $rid_arr = array($rid);
        $gid_arr = array($goods_id);
    }
    foreach ($rid_arr as $k => $v) {
        $rid         = $v;
        $goods_id    = $gid_arr[$k];
        $where       = 'rid=' . $rid;
        $point       = 10;

        $passed_count = $db->count_info(REVIEW, '*', ' goods_id=' .$goods_id);
        $point        = $passed_count <= 5 ? $point * 2 : $point;
        $where       .= ' AND adddate<' . gmtime();//增加自定义时间控制 by mashanling on 17:38 2012-07-20
        $sql          = 'SELECT * FROM ' . REVIEW . ' WHERE ' . $where;
        $rv           = $db->selectInfo($sql);
        $update_value = 'is_pass=2' . $pass_value;

        if ($rv) {
            if ($rv['is_get_point'] == 0) {
                $update_value .= ',is_get_point=1,get_point=' . $point;
                $note          = 'Gained DM Points from review ' . $rid;
                $db->update(REVIEW, $update_value, $where);
                if ($rv['user_id']) {//客人 by mashanling on 2013-03-25 17:02:43		
                    add_point($rv['user_id'], $point, 2, $note);
                    send_pass_notice($rid, $goods_id);
                    $msg = isset($val_arr) ? '' : "<script>alert('不通过审核，该客人帐号收到 {$point} DM points');location.href='{$_SESSION['review_url']}'</script>";
                }
                else {
                    $msg = isset($val_arr) ? '' : "<script>alert('不通过审核，评论库评论，虚拟赠送{$point} DM points');location.href='{$_SESSION['review_url']}'</script>";
                }

                admin_log('', '不通过评论审核。id：', $rid);
            }
            else {
                $msg = isset($val_arr) ? '' : "<script>alert('不通过审核，但该评论之前已经送出积分，故本次不重复赠送客人积分');location.href='{$_SESSION['review_url']}'</script>";
                $db->update(REVIEW, $update_value, $where);
            }
        }
        else {
            $msg = isset($val_arr) ? '' : "<script>alert('该评论为自己人提前所写，时间未到');location.href='{$_SESSION['review_url']}';</script>";
        }
    }
    //stat_goods_view_stat(join(',', $gid_arr));
    exit($msg);

}
elseif ($_ACT == 'disprocess'){
    $db->update(REVIEW, 'is_pass=3' . $pass_value, "rid IN ({$rid})");
	admin_log('', '设评论为等待处理。id： ', $rid);
	//stat_goods_view_stat('SELECT goods_id FROM ' . REVIEW . " WHERE rid IN($rid)");
	isset($rid_arr) && exit('');
	header('Location:' . $_SERVER['HTTP_REFERER']);

}
elseif ($_ACT == 'pass_review'){
	require_once(ROOT_PATH . 'eload_admin/email_temp/mail_conf.php');

    if (!isset($gid_arr)) {
        $rid_arr = array($rid);
        $gid_arr = array($goods_id);
    }

    foreach ($rid_arr as $k => $v) {
        $rid         = $v;
        $goods_id    = $gid_arr[$k];
        $where       = 'rid=' . $rid;
    	$pic_count   = $db->count_info(REVIEW_PIC, '*', $where);
        $video_count = $db->count_info(REVIEW_VIDEO, '*', $where);
        $point       = 10;

        if ($pic_count > 0 && $video_count > 0) {
            $point = 25;
        }
        elseif ($video_count > 0) {
            $point = 25;
        }
        elseif ($pic_count > 0) {
            $point = 20;
        }

        $passed_count = $db->count_info(REVIEW, '*', 'is_pass=1 AND goods_id=' .$goods_id);
        $point        = $passed_count <= 5 ? $point * 2 : $point;
        $where       .= ' AND adddate<' . gmtime();//增加自定义时间控制 by mashanling on 17:38 2012-07-20
        $sql          = 'SELECT * FROM ' . REVIEW . ' WHERE ' . $where;
        $rv           = $db->selectInfo($sql);
        $update_value = 'is_pass=1' . $pass_value;

        if ($rv) {
            if ($rv['is_get_point'] == 0) {
                $update_value .= ',is_get_point=1,get_point=' . $point;
                $note          = 'Gained DM Points from review ' . $rid;
                $db->update(REVIEW, $update_value, $where);
                if ($rv['user_id']) {//客人 by mashanling on 2013-03-25 17:02:43		
                    add_point($rv['user_id'], $point, 2, $note);
                    send_pass_notice($rid, $goods_id);
                    $msg = isset($val_arr) ? '' : "<script>alert('已通过审核，该客人帐号收到 {$point} DM points');location.href='{$_SESSION['review_url']}'</script>";
                }
                else {
                    $msg = isset($val_arr) ? '' : "<script>alert('已通过审核，评论库评论，虚拟赠送{$point} DM points');location.href='{$_SESSION['review_url']}'</script>";
                }

                admin_log('', '通过评论审核。id：', $rid);
            }
            else {
                $msg = isset($val_arr) ? '' : "<script>alert('已通过审核，但该评论之前已经送出积分，故本次不重复赠送客人积分');location.href='{$_SESSION['review_url']}'</script>";
                $db->update(REVIEW, $update_value, $where);
            }
        }
        else {
            $msg = isset($val_arr) ? '' : "<script>alert('该评论为自己人提前所写，时间未到');location.href='{$_SESSION['review_url']}';</script>";
        }
    }
    //stat_goods_view_stat(join(',', $gid_arr));
    exit($msg);
}


elseif ($_ACT == 'show_reply'){
	$yid=empty($_GET['yid'])?0:$_GET['yid'];
	$sql = "update ".REVIEW_REPLY." set is_pass = 1 where yid=$yid";
	$db->query($sql);
	admin_log('', '显示评论回复', $yid);
	Header("Location:".$_SERVER["HTTP_REFERER"]);
}
elseif ($_ACT == 'hide_reply'){
	$yid=empty($_GET['yid'])?0:$_GET['yid'];
	$sql = "update ".REVIEW_REPLY." set is_pass = 2 where yid=$yid";

	$db->query($sql);
	admin_log('', '不显示评论回复', $yid);
	Header("Location:".$_SERVER["HTTP_REFERER"]);
}
elseif ($_ACT == 'del_reply')
{
	$yid=empty($_GET['yid'])?0:$_GET['yid'];
	$sql = "delete from ".REVIEW_REPLY." where yid=$yid";
	$db->query($sql);
	admin_log('', '删除评论回复', $yid);
	Header("Location:".$_SERVER["HTTP_REFERER"]);
}
//评论置顶
elseif ($_ACT == 'zhiding'){
    $db->update(REVIEW, 'is_top=1' . $pass_value, "rid IN ({$rid})");
	admin_log('', '设置评论置顶。id： ', $rid);
	isset($rid_arr) && exit('');
	header('Location:' . $_SERVER['HTTP_REFERER']);
}
//取消评论置顶
elseif ($_ACT == 'no_zhiding'){
    $db->update(REVIEW, 'is_top=0' . $pass_value, "rid IN ({$rid})");
	admin_log('', '取消评论置顶。id： ', $rid);
	isset($rid_arr) && exit('');
	header('Location:' . $_SERVER['HTTP_REFERER']);
}
//修改评论赞数量
elseif ($_ACT == 'edit_helpful_yes')
{
	$review_id = $_REQUEST['id'];
	$new_num = $_REQUEST['value'];
	$sql = "SELECT helpful_yes FROM " . REVIEW ." WHERE rid = " . $review_id;
	$old_num = $GLOBALS['db']->getOne($sql);
	if($old_num >= $new_num)
	{
		echo '修改的数量不能小于现在的数量'; 
	}
	else 
	{
		 $db->update(REVIEW, 'helpful_yes = ' . $new_num , "rid = " . $review_id);
		 $diff_num = $new_num - $old_num;
		 $add_time = gmtime();
		 for($i=1;$i<=$diff_num;$i++)
		 {
		 	$db->insert(REVIEW_HELPFUL, 'rid,user_id,review_helpful_type,add_time', "$review_id,0,0,$add_time");
		 }
		 echo $new_num;
	}
	exit();
}
//编辑评论
elseif ($_ACT == 'edit'){
	$goods_id = !empty($_GET['goods_id']) ? $_GET['goods_id'] : 0;
	$rid = !empty($_GET['rid']) ? $_GET['rid'] : 0;
	if($_POST)
	{
		$review['subject'] = !empty($_POST['subject'])?HtmlEncode(trim($_POST['subject'])):'';
		$review['pros'] = !empty($_POST['pros'])?HtmlEncode(trim($_POST['pros'])):'';
		$review['cons'] = !empty($_POST['cons'])?HtmlEncode(trim($_POST['cons'])):'';
		$review['other_thoughts'] = !empty($_POST['other_thoughts'])?HtmlEncode(trim($_POST['other_thoughts'])):'';
		if(empty($review['subject']))
		{
			sys_msg("评论标题不能为空！", 1, array(), false);
		}
		if(empty($review['pros']))
		{
			sys_msg("评论pros不能为空！", 1, array(), false);
		}
		if(empty($review['cons']))
		{
			sys_msg("评论cons不能为空！", 1, array(), false);
		}
		$sql = "UPDATE " . REVIEW . " SET subject = '" . $review['subject'] . "' , pros = '" . $review['pros'] ."', cons = '". $review['cons'] ."', other_thoughts = '" . $review['other_thoughts'] ."' WHERE rid = " . $rid . " AND goods_id = " . $goods_id;
		$db ->query($sql);

		admin_log('',_EDITSTRING_ , '评论ID：'.$rid);

        $links[0]['name'] = "评论列表";
        $links[0]['url'] = $_SERVER['HTTP_REFERER'];
		$links[1]['name'] = "评论详情页";
        $links[1]['url'] = 'review.php?act=view&rid='.$rid.'&goods_id='.$goods_id;
		$links[2]['name'] = "继续编辑";
        $links[2]['url'] = 'review.php?act=edit&rid='.$rid.'&goods_id='.$goods_id;
		sys_msg("评论修改成功！", 1, $links, true);
	}
	$review=get_reviews($goods_id,$rid);  //评论
	$Arr['review'] = $review;
	$Arr['goods_id']  = $goods_id;
	$Arr['rid']  = $rid;

}
//删除评论图片、视频
elseif ($_ACT == 'delete_media')
{
	$id=empty($_GET['id'])?0:$_GET['id'];
    $type=empty($_GET['type'])?0:$_GET['type'];
    if($type=='img' && $id)
    {
	    $img = $db->arrQuery("select paths from ".REVIEW_PIC." where  id=$id ");
		if(!empty($img)){
			foreach($img as $row){
				@unlink(ROOT_PATH.$row['paths']);
			}
		}
		$sql = "delete from ".REVIEW_PIC." where id=$id";
        $db->query($sql);
	    admin_log('', '删除评论图片', $id);
    }
    if($type=='video' && $id)
    {	
	    $sql = "delete from ".REVIEW_VIDEO." where id=$id";
        $db->query($sql);
	    admin_log('', '删除评论视频', $id);
    }
	echo "<script>history.go(-1)</script>";
	//Header("Location:".$_SERVER["HTTP_REFERER"]);
    EXIT;
}


function send_pass_notice($rid, $goods_id){
	global $db,$Tpl,$_CFG, $mail_conf, $cur_lang;
	$goods=get_goods_info($goods_id);
	$goods['url_title'] = get_details_link($goods['goods_id'],$goods['url_title']);
	$review=get_reviews($goods_id,$rid);  //评论
	if(is_array($review)){
		$Arr['review'] =$review;
	}
	else {
		$Arr['review'] = '';
	}
	$Arr['goods'] = $goods;
	
	$sql = "SELECT u.email, u.lang, u.user_id, u.firstname FROM ".REVIEW." r,".USERS." u WHERE r.user_id = u.user_id AND rid=$rid";
	$user_info = $db->selectinfo($sql);
	$email     = $user_info['email'];
	$lang      = $user_info['lang'];	
	$firstname = $user_info['firstname'];	
	
	if(empty($lang)) $lang = 'en';
	if($lang == 'en') $firstname = 'my friend';
	if($lang == 'fr') $firstname = 'mon ami';
	if($lang == 'ru') $firstname = 'мой друг';
	$Arr['firstname'] = $firstname;
	
	foreach( $Arr as $key => $value ){
		$Tpl->assign($key, $value );
	}

	if(!empty($lang)) {
		$mail_subject = $mail_conf[$lang][23];
		$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $lang .'/23.html');
	} 
	if(empty($mail_subject)) {
		$mail_subject = $mail_conf['en'][23];
		$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/en/23.html');			
	}	
	$mail_subject = str_replace('{$get_point}',$review['review_list'][0]['get_point'],$mail_subject);
	if($mail_subject&&$email&&$mail_body){	
		//exec_send($email,$mail_subject,$mail_body); //取消评论审核后发送邮件功能 2014/02/14 fangxin
	}
}


function get_goods_info($goods_id)
{
    $time = gmtime();
    $sql = 'SELECT g.*, c.measure_unit, ' .
                'IFNULL(AVG(r.comment_rank), 0) AS comment_rank ' .
            'FROM ' . GOODS . ' AS g ' .
            'LEFT JOIN ' . CATALOG . ' AS c ON g.cat_id = c.cat_id ' .
            'LEFT JOIN ' . COMMENT . ' AS r '.
                'ON r.id_value = g.goods_id AND comment_type = 0 AND r.parent_id = 0 AND r.status = 1 ' .
            "WHERE g.goods_id = '$goods_id'  " .//AND g.is_delete = 0
            "GROUP BY g.goods_id";
    $row = $GLOBALS['db']->selectinfo($sql);



    if ($row !== false)
    {
        /* 用户评论级别取整 */
        //$row['comment_rank']  = ceil($row['comment_rank']) == 0 ? 5 : ceil($row['comment_rank']);

        /* 获得商品的销售价格 */
        $row['market_price']        = price_format($row['market_price']);
        $row['shop_price_formated'] = price_format($row['shop_price']);

        /* 修正促销价格 */
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        }
        else
        {
            $promote_price = 0;
        }

        /* 处理商品水印图片 */
        $watermark_img = '';

        if ($promote_price != 0)
        {
            $watermark_img = "watermark_promote";
        }
        elseif ($row['is_new'] != 0)
        {
            $watermark_img = "watermark_new";
        }
        elseif ($row['is_best'] != 0)
        {
            $watermark_img = "watermark_best";
        }
        elseif ($row['is_hot'] != 0)
        {
            $watermark_img = 'watermark_hot';
        }

        if ($watermark_img != '')
        {
            $row['watermark_img'] =  $watermark_img;
        }

        $row['promote_price_org'] =  $promote_price;
        $row['promote_price'] =  price_format($promote_price);
        $row['goods_grid'] =  get_image_path($row['goods_id'],$row['goods_grid']);


        /* 修正重量显示 */
        $row['goods_weight']  = formated_weight($row['goods_weight']);

        /* 修正上架时间显示 */
        $row['add_time']      = local_date($GLOBALS['_CFG']['date_format'], $row['add_time']);

        /* 促销时间倒计时 */
        $time = gmtime();
        if ($time >= $row['promote_start_date'] && $time <= $row['promote_end_date'])
        {
             $row['gmt_end_time']  = $row['promote_end_date'];
        }
        else
        {
            $row['gmt_end_time'] = 0;
        }

        /* 是否显示商品库存数量 */
        $row['goods_number']  = ($GLOBALS['_CFG']['use_storage'] == 1) ? $row['goods_number'] : '';

        /* 修正商品图片 */
        $row['goods_img']   = get_image_path($goods_id, $row['goods_img']);
        $row['goods_thumb'] = get_image_path($goods_id, $row['goods_thumb'], true);

        return $row;
    }
    else
    {
        return false;
    }
}


$_ACT = $_ACT == 'msg'?'msg':'review_'.$_ACT;
temp_disp();

/**
 * 获取评论
 *
 */
function new_get_reviews() {
    global $Arr, $db;


	$filter    = page_and_size(array())	;
	$size      = $filter['page_size'];
	$keyword   = Param::get('keyword');
	$status    = Param::get('status');
	$start_date= Param::get('start_date');
	$end_date  = Param::get('end_date');
	$column    = Param::get('column');
	$has_pic   = Param::get('has_pic');
	$has_video = Param::get('has_video');
	$start_date2 = Param::get('start_date2');
	$end_date2 = Param::get('end_date2');
	$pass_admin = Param::get('pass_admin');
	$exclude_self = Param::get('exclude_self', 'int');//排除自已人提前写的评论 by mashanling on 2012-07-20 15:38:49
	$where     = $exclude_self ? ' WHERE r.adddate<' . gmtime(): 'WHERE 1';
	if ('r.user_id' == $column) {
		$where .= ' AND r.user_id=0';
	}
	elseif ('reviewlib' == $column) {
		$where .= ' AND r.user_id>0';
	}
	elseif ('r.is_top' == $column) {
		$where .= ' AND r.is_top=1';
	}
	elseif ($keyword !== '') {    //关键字
		$where .= in_array($column, array('g.goods_id', 'u.user_id', 'r.user_id')) ? " AND {$column}=" . intval($keyword) : " AND {$column} LIKE '%{$keyword}%'";
	}


if ($pass_admin !== '') {    //关键字
	$where .= " AND r.pass_admin='{$pass_admin}'";
}
if (!empty($GLOBALS['cat_id'])) {
	$cat_ids = new_get_children($GLOBALS['cat_id']);
	$where .= " AND g.cat_id IN({$cat_ids})";
}
if ($status !== '') {    //状态
	    $status = intval($status);
	    if ($status != 99) {
	        $where .= $status == 4 ? ' AND r.rid IN(SELECT rid FROM ' . REVIEW_REPLY . ' WHERE is_pass=0)' : ' AND r.is_pass=' . $status;
	    }
	}
	if ($has_pic !== '' && 99 != ($has_pic = intval($has_pic))) {    //图片
	    $where .= ' AND ' . ($has_pic ? '' : ' NOT ') . ' EXISTS(SELECT rid FROM ' . REVIEW_PIC . ' WHERE rid=r.rid)';
	}
	if ($has_video !== '' && 99 != ($has_video = intval($has_video))) {    //图片
	    $where .= ' AND ' . ($has_video ? '' : ' NOT ') . ' EXISTS(SELECT rid FROM ' . REVIEW_VIDEO . ' WHERE rid=r.rid)';
	}
	$start_date != '' && ($where .= ' AND r.addtime_real>' . local_strtotime($start_date));
	$end_date != '' && ($where .= ' AND r.addtime_real<' . local_strtotime($end_date));
	$start_date2 != '' && ($where .= ' AND r.pass_time>' . local_strtotime($start_date2));
	$end_date2 != '' && ($where .= ' AND r.pass_time<' . local_strtotime($end_date2));
    //echo (strtotime('2011-09-07'));exit;
    $Arr['keyword']  = $keyword;
    $Arr['column']   = $column;
    $Arr['status']   = $status;
    $Arr['has_pic']   = $has_pic;
    $Arr['has_video']   = $has_video;
    $Arr['start_date'] = $start_date;
    $Arr['end_date'] = $end_date;
    $Arr['start_date2'] = $start_date2;
    $Arr['end_date2'] = $end_date2;
    $Arr['exclude_self'] = $exclude_self;
    $Arr['pass_admin'] = $pass_admin;
	$fields          = 'r.*,g.goods_id,g.goods_sn,g.goods_title,g.goods_thumb,g.is_on_sale,u.email';
	$table           = REVIEW . ' AS r JOIN ' . GOODS . ' AS g ON r.goods_id=g.goods_id LEFT JOIN ' . USERS . ' AS u ON r.user_id=u.user_id ';
    $record_count    = Param::get('record_cound', 'int');    //记录总数，第一页不带总数参数，第二页后将带总数
    $sql             = 'SELECT COUNT(r.user_id) FROM ' . $table . $where;
    $record_count    = $db->getOne($sql);
    if (!$record_count) {
        return;
    }
    $filter          = array('record_count' => $record_count);
    $filter          = page_and_size($filter);    //分页信息
    $page            = new page(array(
        'total'   => $record_count,
        'perpage' => $filter['page_size'],
        //'url'     => "review.php?record_cound={$record_count}&amp;status={$status}&amp;column={$column}&amp;keyword={$keyword}&amp;start_date={$start_date}&amp;end_date={$end_date}"
        )
    );
	$Arr['pagestr']  = $page->show();
    $Arr['filter']   = $filter;
    $limit           =  ' LIMIT ' . $filter['start'] . ',' . $filter['page_size'];    //sql limit
    $sql             = "SELECT {$fields} FROM " . $table . $where . ' ORDER BY r.rid DESC ' . $limit;
    $db->query($sql);
    $data            = array();
    while (($row = $db->fetchArray()) !== false) {
        $row['goods_thumb']  = get_image_path(false, $row['goods_thumb']);
        $row['eariler'] = gmtime() > $row['adddate'] ? '' : '<span style="color: red">[提前]</span>';
        if($row['adddate'] - $row['addtime_real'] <600){
        	$row['addtime_real'] = $row['adddate'];
        }
        $row['pass_time'] = local_date('Y-m-d H:i:s', $row['pass_time']);
		$row['pros'] = str_replace('\\', '', stripslashes($row['pros']));
		$row['cons'] = str_replace('\\', '', stripslashes($row['cons']));
		$row['other_thoughts'] = str_replace('\\', '', stripslashes($row['other_thoughts']));
		$row['email']	= email_disp_process($row['email']);
        $data[] = $row;
    }

    $Arr['data']     = $data;
}//end new_get_reviews

/**
 * 统计商品评论
 *
 * @param string $goods_id 商品id
 *
 * @return void 无返回值
 */
function stat_goods_view_stat($goods_id) {
    global $db;
    $db->delete(REVIEW_STAT, "goods_id IN($goods_id)");
    $sql = 'INSERT INTO ' . REVIEW_STAT . '(goods_id,review_count,review_rate) SELECT goods_id,COUNT(*),IFNULL(SUM(rate_overall), 0) FROM ' . REVIEW . " WHERE is_pass=1 AND goods_id IN($goods_id) GROUP BY goods_id";
    $db->query($sql);
}
?>