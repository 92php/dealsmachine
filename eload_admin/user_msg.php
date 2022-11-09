<?php

define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
require_once('lang/user_msg.php');

/* 权限判断 */
admin_priv('user_msg');
$Arr['lang'] = $_LANG;

$_ACT = 'list_all';
$_ID  = '';
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));


/*------------------------------------------------------ */
//-- 发送留言
/*------------------------------------------------------ */
if ($_ACT=='add')
{
    $user_id = empty($_GET['user_id']) ? 0 : intval($_GET['user_id']);
    $order_id = empty($_GET['order_id']) ? 0 : intval($_GET['order_id']);
    $order_sn = $db->getOne("SELECT order_sn FROM "  . $ecs->table('order_info') . " WHERE order_id = '$order_id'");

    /* 获取关于订单所有信息 */
    $sql = "SELECT msg_id, user_name, msg_title, msg_type, msg_time, msg_content".
           " FROM " . FEEDBACK.
           " WHERE user_id ='$user_id' AND order_id = '$order_id'";

    $msg_list = $db->arrQuery($sql);
    foreach($msg_list as $key=>$val)
    {
        $msg_list[$key]['msg_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['msg_time']);
    }

    assign_query_info();
    $smarty->assign('ur_here',      sprintf($_LANG['msg_for_order'], $order_sn));
    $smarty->assign('action_link',  array('text'=>$_LANG['order_detail'], 'href'=>'order.php?act=info&order_id=' . $order_id));
    $smarty->assign('msg_list', $msg_list);
    $smarty->assign('order_id', $_GET['order_id']);
    $smarty->assign('user_id',  $_GET['user_id']);
    $smarty->display('msg_add.htm');
}

if ($_ACT=='insert')
{
    $sql = "INSERT INTO " . FEEDBACK . "(parent_id, user_id, user_name, user_email, msg_title, msg_type, msg_content, msg_time, message_img, order_id)" .
            " VALUES (0, '$_POST[user_id]', '$_SESSION[admin_name]', ' ', ".
            " '$_POST[msg_title]', 5, '$_POST[msg_content]', '" . gmtime() . "', '', '$_POST[order_id]')";

    $db->query($sql);

    header("Location: user_msg.php?act=add&order_id=$_POST[order_id]&user_id=$_POST[user_id]\n");
    exit;
}

if ($_ACT == 'remove_msg')
{
    $msg_id = empty($_GET['msg_id']) ? 0 : intval($_GET['msg_id']);
    $order_id = empty($_GET['order_id']) ? 0 : intval($_GET['order_id']);
    $user_id = empty($_GET['user_id']) ? 0 : intval($_GET['user_id']);
    $sql = "SELECT user_id, order_id, message_img FROM " . FEEDBACK . " WHERE msg_id='$msg_id'";
    $row = $db->getRow($sql);
    if ($row)
    {
        if ($row['user_id'] == $user_id && $row['order_id'] == $order_id)
        {
            if ($row['message_img'])
            {
                @unlink(ROOT_PATH. DATA_DIR . '/feedbackimg/' . $row['message_img']);
            }
            $sql = "DELETE FROM " . FEEDBACK . " WHERE msg_id=$msg_id LIMIT 1";
            $db->query($sql);
        }
    }

   // header("Location: user_msg.php?act=add&order_id=$_GET[order_id]&user_id=$_GET[user_id]\n");
    exit;
}
/*------------------------------------------------------ */
//-- 更新留言的状态为显示或者禁止
/*------------------------------------------------------ */
if ($_ACT == 'check')
{
    if ($_REQUEST['check'] == 'allow')
    {
        /* 允许留言显示 */
        $sql = "UPDATE " .FEEDBACK. " SET msg_status = 1 WHERE msg_id = '$_REQUEST[id]'";
        $db->query($sql);

        /* 清除缓存 */
        //clear_cache_files();

        header("Location: user_msg.php?act=view&id=$_REQUEST[id]\n");
        exit;
    }
    else
    {
        /* 禁止留言显示 */
        $sql = "UPDATE " .FEEDBACK. " SET msg_status = 0 WHERE msg_id = '$_REQUEST[id]'";
        $db->query($sql);

        /* 清除缓存 */
        clear_cache_files();

        header("Location: user_msg.php?act=view&id=$_REQUEST[id]\n");
        exit;
    }
}
/*------------------------------------------------------ */
//-- 列出所有留言
/*------------------------------------------------------ */
if ($_ACT=='list_all')
{
    $msg_list = msg_list();
    $Arr['msg_list'] =     $msg_list['msg_list'];
    $sort_flag  = sort_flag($msg_list['filter']);
    $Arr[$sort_flag['tag']] = $sort_flag['img'];
	$msg_list['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];
    $Arr['filter'] = $msg_list['filter'];
	
    $page=new page(array('total'=>$msg_list['record_count'],'perpage'=>$msg_list['page_size'])); 
	$Arr["pagestr"]  = $page->show();
    $_ACT = 'msg_list';
}

/*------------------------------------------------------ */
//-- ajax 删除留言
/*------------------------------------------------------ */
elseif ($_ACT == 'remove')
{
    $msg_id = intval($_REQUEST['id']);
	$sql = "SELECT * FROM ".FEEDBACK." WHERE msg_id = $msg_id";
	$msgArr = $db->selectinfo($sql);
	$img = $msgArr["message_img"];
	$msg_title = $msgArr["msg_title"];
	/* 删除图片 */
	if (!empty($img))
	{
		 @unlink(ROOT_PATH. IMAGE_DIR . '/feedbackimg/'.$img);
	}
	$sql = "DELETE FROM " . FEEDBACK . " WHERE parent_id = '$msg_id' LIMIT 1";
	$db->query($sql);
	$sql = "DELETE FROM " . FEEDBACK . " WHERE msg_id = '$msg_id' LIMIT 1";
	$db->query($sql);

	admin_log(addslashes($msg_title), _DELSTRING_, 'id 为 '.$msg_id);
   // $url = 'user_msg.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
   /// header("Location: $url\n");
	exit;
/*------------------------------------------------------ */
//-- 回复留言
/*------------------------------------------------------ */
}
elseif ($_ACT=='view')
{

    $Arr['msg'] = get_feedback_detail(intval($_REQUEST['id']));
	
    $_ACT = 'msg_info';
}
elseif ($_ACT=='action')
{

    if (empty($_REQUEST['parent_id']))
    {
        $sql = "INSERT INTO ".FEEDBACK." (msg_title, msg_time, user_id, user_name , ".
                    "user_email, parent_id, msg_content) ".
                "VALUES ('reply', '".gmtime()."', '".$_SESSION['WebUserInfo']['said']."', ".
                    "'".$_CFG['shop_name']."', '".$_CFG['service_email']."', ".
                    "'".$_REQUEST['msg_id']."', '".$_POST['msg_content']."') ";
        $db->query($sql);
        header("Location: ?act=view&id=".$_REQUEST['msg_id']);
        exit;

    }
    else
    {
        $sql = "UPDATE ".FEEDBACK." SET user_email = '".$_POST['user_email']."', msg_content='".$_POST['msg_content']."', msg_time = '".gmtime()."' WHERE msg_id = '".$_REQUEST['parent_id']."'";
        $db->query($sql);
        header("Location: ?act=view&id=".$_REQUEST['msg_id']."\n");
        exit;
    }
}

/*------------------------------------------------------ */
//-- 删除会员上传的文件
/*------------------------------------------------------ */
elseif ($_ACT == 'drop_file')
{

    /* 删除上传的文件 */
    $file = $_GET['file'];
    @unlink('../' . DATA_DIR . '/feedbackimg/'.$file);

    /* 更新数据库 */
    $db->query("UPDATE ".FEEDBACK." SET message_img = '' WHERE msg_id = '$_GET[id]'");

    header("Location: user_msg.php?act=view&amp;id=".$_GET['id']."\n");
    exit;
}

/**
 *
 *
 * @access  public
 * @param
 *
 * @return void
 */
function msg_list()
{
    /* 过滤条件 */
    $filter['keywords']   = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
    $filter['msg_type']   = isset($_REQUEST['msg_type']) ? intval($_REQUEST['msg_type']) : -1;
    $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'f.msg_id' : trim($_REQUEST['sort_by']);
    $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

    $where = '';
    if ($filter['keywords'])
    {
        $where .= " AND f.msg_title LIKE '%" . mysql_like_quote($filter['keywords']) . "%' ";
    }
    if ($filter['msg_type'] != -1)
    {
        $where .= " AND f.msg_type = '$filter[msg_type]' ";
    }

    $sql = "SELECT count(*) FROM " .FEEDBACK. " AS f" .
           " WHERE parent_id = '0' " . $where;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    /* 分页大小 */
    $filter = page_and_size($filter);

    $sql = "SELECT f.msg_id, f.user_name, f.msg_title, f.msg_type, f.order_id, f.msg_status, f.msg_time, f.msg_area, COUNT(r.msg_id) AS reply " .
            "FROM " . FEEDBACK . " AS f ".
            "LEFT JOIN " . FEEDBACK . " AS r ON r.parent_id=f.msg_id ".
            "WHERE f.parent_id = 0 $where " .
            "GROUP BY f.msg_id ".
            "ORDER by $filter[sort_by] $filter[sort_order] ".
            "LIMIT " . $filter['start'] . ', ' . $filter['page_size'];

    $msg_list = $GLOBALS['db']->arrQuery($sql);
    foreach ($msg_list AS $key => $value)
    {   if($value['order_id'] > 0)
        {
            $msg_list[$key]['order_sn'] = $GLOBALS['db']->getOne("SELECT order_sn FROM " . ORDERINFO ." WHERE order_id= " .$value['order_id']);
        }
        $msg_list[$key]['msg_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['msg_time']);
        $msg_list[$key]['msg_type'] = $GLOBALS['_LANG']['type'][$value['msg_type']];
    }
    $filter['keywords'] = stripslashes($filter['keywords']);
    $arr = array('msg_list' => $msg_list, 'filter' => $filter, 'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);

    return $arr;
}

/**
 * 获得留言的详细信息
 *
 * @param   integer $id
 *
 * @return  array
 */
function get_feedback_detail($id)
{
    global $ecs, $db, $_CFG;

    $sql = "SELECT T1.*, T2.msg_id AS reply_id, T2.user_name  AS reply_name,  ".
                "T2.msg_content AS reply_content , T2.msg_time AS reply_time, T2.user_name AS reply_name ".
            "FROM ".FEEDBACK. " AS T1 ".
            "LEFT JOIN ".FEEDBACK. " AS T2 ON T2.parent_id=T1.msg_id ".
            "WHERE T1.msg_id = '$id'";
    $msg = $db->selectinfo($sql);

    if ($msg)
    {
        $msg['msg_time']   = local_date($_CFG['time_format'], $msg['msg_time']);
        $msg['reply_time'] = local_date($_CFG['time_format'], $msg['reply_time']);
    }

    return $msg;
}

temp_disp();
?>