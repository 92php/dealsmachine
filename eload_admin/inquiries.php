<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once('../lib/param.class.php');
require_once('../lib/lib_goods.php');
require_once(ROOT_PATH . 'lib/syn_public_fun.php');
require_once('lib/common.fun.php');
$_ACT = 'list';
$_ID  = '';
$goods_id = 0;

// 语言
$lang = get_lang();
$lang = check_lang_power($lang);
$Arr['lang_arr'] = $lang;
$default_power_lang = check_default_lang_power($lang);
$Arr['default_lang'] = $default_power_lang;

if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));
if (!empty($_GET['goods_id'])) $goods_id = intval(trim($_GET['goods_id']));
$rid = !empty($_GET['rid']) ? $_GET['rid'] : 0;

/*------------------------------------------------------ */
//--
/*------------------------------------------------------ */

if ($_ACT == 'list' || $_ACT == 'trash')
{
    admin_priv('inquiry_list');

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
	    'g.goods_sn'      => '商品编码',
	    'g.goods_id'      => '商品id',
		'i.user_id'       => '用户id',
	    'i.i_content'     => '咨询内容',
	);

	$Arr['status_arr'] = array(
	    '' => '--状态--',
	    0  => '未审核',
	    1  => '显示的咨询',
	    2  => '不显示的咨询',
	);

	$Arr['type_arr'] = read_static_cache('product_inquiry_type', FRONT_STATIC_CACHE_PATH);
	array_unshift($Arr['type_arr'], '--咨询类型--');

	$filter =array();
	$filter=page_and_size($filter)	;
	$size = $filter['page_size'];
	$keyword   = Param::get('keyword');
	$status    = Param::get('is_pass');
	$type    = intval(Param::get('type'));
	$start_date= Param::get('start_date');
	$end_date  = Param::get('end_date');
	$start_date2 = Param::get('start_date2');
	$end_date2 = Param::get('end_date2');
	$pass_admin = Param::get('pass_admin');
	$column    = Param::get('column');
	$cur_lang   = Param::get('lang');
	$where     = '';
	if ($keyword != '') {    //关键字
	    $where .= in_array($column, array('g.goods_id', '.user_id')) ? " AND {$column}=" . intval($keyword) : " AND {$column} LIKE '%{$keyword}%'";
	}
	if ($status !== '') {    //状态
	    $status = intval($status);
	    $where .= ' AND i.is_pass=' . $status;
	}
	if ($type) {    //咨询类型
	    $where .= ' AND i.type=' . $type;
	}
	if ('' !== $pass_admin) {    //回复人
	    $where .= " AND i.pass_admin='{$pass_admin}'";
	}
	if ($cat_id) {//分类id
		$cat_ids = new_get_children($cat_id);
		$where .= " AND g.cat_id IN({$cat_ids})";
	}
    if($cur_lang)
    {
    	$where .= " AND i.lang='{$cur_lang}'";
    }
	$start_date != '' && ($where .= ' AND i.adddate>' . local_strtotime($start_date));
	$end_date != '' && ($where .= ' AND i.adddate<' . local_strtotime($end_date));
	$start_date2 != '' && ($where .= ' AND i.pass_time>' . local_strtotime($start_date2));
	$end_date2 != '' && ($where .= ' AND i.pass_time<' . local_strtotime($end_date2));
    $Arr['keyword']  = $keyword;
    $Arr['column']   = $column;
    $Arr['status']   = $status;
    $Arr['type']   = $type;
    $Arr['start_date'] = $start_date;
    $Arr['end_date'] = $end_date;
    $Arr['start_date2'] = $start_date2;
    $Arr['end_date2'] = $end_date2;
    $Arr['pass_admin'] = $pass_admin;
	$_GET['page'] = empty($_GET['page'])?1:intval($_GET['page']);
	$Arr['inquiry']=get_inquiry($where);  //产品咨询
    $page=new page(array('total' => $Arr['inquiry']['record_count'],'perpage'=>$size));
	$Arr["pagestr"]  = $page->show(1);
}

/*------------------------------------------------------ */
//-- 添加新商品 编辑商品
/*------------------------------------------------------ */

elseif ($_ACT == 'del'){
	$iid = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;
	$sql = "delete from ".PRO_INQUIRY." where iid in(".$iid.")";
	$db->query($sql);
	$BatchStr = " id 为：$iid";
	admin_log('', '批量删除产品咨询', $BatchStr);

	$link[0]["name"] = "返回上一页";
    $link[0]["url"] = $_SERVER["HTTP_REFERER"];
    $link[1]["name"] = "返回列表";
    $link[1]["url"] = "inquiries.php";
	sys_msg("批量操作成功", 0, $link);
}
elseif ($_ACT == 'view'){
	$goods_id = !empty($_GET['goods_id']) ? $_GET['goods_id'] : 0;
	$iid = !empty($_GET['iid']) ? $_GET['iid'] : 0;
	$goods = get_goods_info($goods_id);
	$goods['url_title'] = get_details_link($goods['goods_id'],$goods['url_title']);
	$Arr['goods'] = $goods;
	$inquiry=get_one_inquiry($goods_id,$iid);  //取指定的产品咨询
	$Arr['inquiry']  = $inquiry;
}

elseif ($_ACT == 'save'){
	$goods_id = !empty($_GET['goods_id']) ? $_GET['goods_id'] : 0;
	$iid = !empty($_GET['iid']) ? $_GET['iid'] : 0;
	$reply = !empty($_POST['reply']) ? $_POST['reply'] : '';
	$is_pass = !empty($_POST['is_pass']) ? $_POST['is_pass'] : 0;
	//$inquiry=get_one_inquiry($goods_id,$iid);  //取指定的产品咨询
	$inquiry['reply'] = HtmlEncode($reply);
	$inquiry['is_pass'] = $is_pass;
	if ($reply) {
	    $inquiry['pass_time'] = gmtime();
	    $inquiry['pass_admin'] = $_SESSION['WebUserInfo']['real_name'];
	}
	$db->autoExecute(PRO_INQUIRY,$inquiry,'UPDATE',"iid=$iid");
	$Arr['inquiry']  = $inquiry;

	//发送咨询回复邮件
	require_once(ROOT_PATH . 'eload_admin/email_temp/mail_conf.php');
	send_inquiries_email($iid,$goods_id);

	admin_log('', '审核产品咨询ＩＤ', $iid);
	echo "<script>history.go(-2)</script>";
	exit();

}
elseif ($_ACT == 'unpass_review'){
	$sql = "update ".REVIEW." SET is_pass=2 where rid=".$rid;
	$db->query($sql);
	//header($_SERVER["HTTP_REFERER"]);
	admin_log('', '不通过评论审核ＩＤ', $rid);
	Header("Location:".$_SERVER["HTTP_REFERER"]);

}
elseif ($_ACT == 'pass_review'){

	$pic_count=$db->getOne("select count(*) from ".REVIEW_PIC." where  rid=$rid");
	$video_count=$db->getOne("select count(*) from ".REVIEW_VIDEO." where   rid=$rid");
	$point=5;

	if($pic_count>0&$video_count>0){
		$point=15;
	}elseif ($video_count>0)
	{
		$point=15;
	}elseif ($pic_count>0){
		$point=10;
	}

	$sql = "select count(*) from ".REVIEW." where is_pass=1 and goodS_id=$goods_id";
	$passed_count=$db->getOne($sql);
	if($passed_count<=5){
		$point=$point*2;
	}

	$sql = "select * from ".REVIEW." where rid=".$rid;
	$rv=$db->selectInfo($sql);
	$sql = "update ".REVIEW." SET is_pass=1,is_get_point=1 where rid=".$rid;
	$db->query($sql);
	if($rv['is_get_point'] ==0){
		$note="Gain Points from review ID:".$rid;
		add_point($rv['user_id'],$point,2,$note);
		echo "<script>alert('已通过审核，该客人帐号收到 $point points');history.go(-2)</script>";
		exit();
	}else {
		echo "<script>alert('已通过审核，但该评论之前已经送出积分，故本次不重复赠送客人积分');history.go(-2)</script>";
		exit();
	}

	$sql = "update ".REVIEW." SET is_pass=1,is_get_point=1 where rid=".$rid;
	admin_log('', '通过评论审核ＩＤ', $rid);
	Header("Location:".$_SERVER["HTTP_REFERER"]);
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
	$sql = "update ".REVIEW_REPLY." set is_pass = 0 where yid=$yid";

	$db->query($sql);
	admin_log('', '不显示评论回复', $yid);
	Header("Location:".$_SERVER["HTTP_REFERER"]);
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
        $row['comment_rank']  = ceil($row['comment_rank']) == 0 ? 5 : ceil($row['comment_rank']);

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
        $row['goods_grid']   = get_image_path($goods_id, $row['goods_grid']);
        $row['goods_thumb'] = get_image_path($goods_id, $row['goods_thumb'], true);

        return $row;
    }
    else
    {
        return false;
    }
}

/**
 * 发送咨询回复邮件
 *
 * @param 	$iid	咨询ID
 * @param 	$goods_id	咨询商品ID
 * */
function send_inquiries_email($iid,$goods_id){
    if ('.e.com' == COOKIESDIAMON) {
        return;
    }
	global $db, $Tpl, $_CFG, $mail_conf, $cur_lang, $default_lang;;
	$goods=get_goods_info($goods_id);
	$goods['url_title'] = get_details_link($goods['goods_id'],$goods['url_title']);

	$inquiries=get_one_inquiry($goods_id,$iid);  //取指定的产品咨询

	if(is_array($inquiries)){
		$Arr['inquiries'] =$inquiries;
		$Arr['firstname'] = empty($inquiries['inquiry_list'][0]['firstname']) ? 'my friend' : $inquiries['inquiry_list'][0]['firstname'];
		$Arr['i_content'] = $inquiries['inquiry_list'][0]['i_content'];
		$Arr['reply'] = $inquiries['inquiry_list'][0]['reply'];
	}
	else {
		$Arr['inquiries'] = '';
	}
	$Arr['goods'] =$goods;

	//同级分类2周销量排行前4的商品
	$sql = "SELECT g.* , gs.is_24h_ship FROM " . GOODS . " AS g LEFT JOIN " . GOODS_STATE . " AS gs ON g.goods_id = gs.goods_id " .
			" WHERE g.cat_id = " . $goods['cat_id'] . " AND g.goods_number >0 AND g.is_on_sale = 1 AND g.is_delete = 0 AND g.goods_id != " . $goods_id .
			" ORDER BY g.week2sale DESC , g.goods_id DESC LIMIT 4";
	$goods_list= $db->arrQuery($sql);
	foreach ($goods_list as $key => $value)
	{
		$promote_price = bargain_price($value['promote_price'], $value['promote_start_date'], $value['promote_end_date']);
        $promote_price = price_format($promote_price);

		$value['shop_price']  = $promote_price > 0 ? $promote_price : price_format($value['shop_price']);
        $value['goods_thumb'] = get_image_path($value['goods_id'], $value['goods_thumb'], true);
        $value['goods_img']   = get_image_path($value['goods_id'], $value['goods_img']);
        $value['goods_grid']  = get_image_path($value['goods_id'], $value['goods_grid']);
		$value['url_title'] = get_details_link($value['goods_id'],$value['url_title']);
		$Arr['goods_list'][$key] = $value;
	}
	$Tpl->assign($Arr);
	$email = $inquiries['inquiry_list'][0]['email'];

	//获得用户信息 fangxin 2013/07/18
	$sql = "SELECT email, firstname, lang FROM " . USERS . " WHERE email = '" . $email . "'";
	$user_info =  $db->selectinfo($sql);
	$lang      = $user_info['lang'];
	if(!empty($lang)) {
		$mail_subject = $mail_conf[$lang][34];
		$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $lang .'/34.html');
	}
	if(empty($mail_subject)) {
		$mail_subject = $mail_conf['en'][34];
		$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/en/34.html');
	}

	if($mail_subject&&$email&&$mail_body){
		exec_send($email,$mail_subject,$mail_body);
	}
}

$_ACT = $_ACT == 'msg'?'msg':'inquiries_'.$_ACT;
temp_disp();

/**
 * 时间轴，即显示为 刚刚、5分钟前、3小时前、昨天10:23、前天15：26等
 *
 * @author              mashanling(msl-138@163.com)
 * @date                2013-05-16 15:09:18
 *
 * @param int $time unix时间戳
 *
 * @return string 格式化显示的时间
 */
function time_axis($time) {
    $today  = local_strtotime(local_date('Y-m-d'));
    $now    = gmtime();
    $diff   = $now - $time;

    if ($diff < 60) {
        return '刚刚';
    }
    else if ($diff < 3600) {
        return floor($diff / 60) . ' 分钟前';
    }
    else if ($diff < 86400) {
        return floor($diff / 3600) . ' 小时前';
    }

    $v = $today - local_strtotime(local_date('Y-m-d', $time));

    if ($v < 86400 * 3) {
        return ($v < 86400 * 2 ? '昨天' : '前天') . local_date(' H:i:s', $time);
    }

    return local_date('Y-m-d H:i:s', $time);
}