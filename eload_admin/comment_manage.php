<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
admin_priv('comment_manage');/* 检查权限 */
$_ACT = 'list';
$_ID  = '';
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));



/*------------------------------------------------------ */
//-- 获取没有回复的评论列表
/*------------------------------------------------------ */
if ($_ACT == 'list')
{
    $list = get_comment_list();
    $Arr['comment_list'] = $list['item'];
    $sort_flag  = sort_flag($list['filter']);
    $Arr[$sort_flag['tag']] = $sort_flag['img'];
	$list['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];
	
    $Arr['filter'] = $list['filter'];

    $page=new page(array('total'=>$list['record_count'],'perpage'=>$list['page_size'])); 
	$Arr["pagestr"]  = $page->show();
}

/*------------------------------------------------------ */
//-- 回复用户评论(同时查看评论详情)
/*------------------------------------------------------ */
if ($_ACT=='reply')
{
    /* 检查权限 */
    

    $comment_info = array();
    $reply_info   = array();
    $id_value     = array();

    /* 获取评论详细信息并进行字符处理 */
    $sql = "SELECT * FROM " .COMMENT. " WHERE comment_id = '$_REQUEST[id]'";
    $comment_info = $db->selectinfo($sql);
    $comment_info['content']  = nl2br(htmlspecialchars($comment_info['content']));
    $comment_info['add_time'] = local_date($_CFG['time_format'], $comment_info['add_time']);



    /* 获得评论回复内容 */
    $sql = "SELECT * FROM ".COMMENT. " WHERE parent_id = '$_REQUEST[id]'";
    $reply_info = $db->selectinfo($sql);

    if (empty($reply_info))
    {
        $reply_info['content']  = '';
        $reply_info['add_time'] = '';
    }
    else
    {
        $reply_info['content']  = nl2br(htmlspecialchars($reply_info['content']));
        $reply_info['add_time'] = local_date($_CFG['time_format'], $reply_info['add_time']);
    }

    /* 取得评论的对象(文章或者商品) */
    if ($comment_info['comment_type'] == 0)
    {
        $sql = "SELECT goods_name FROM ".GOODS.
               " WHERE goods_id = '$comment_info[id_value]'";
        $id_value = $db->getOne($sql);
    }
    else
    {
        $sql = "SELECT title FROM ".ARTICLE.
               " WHERE article_id='$comment_info[id_value]'";
        $id_value = $db->getOne($sql);
    }

    /* 模板赋值 */
    $Arr['msg']           =          $comment_info; //评论信息
    $Arr['reply_info']    =   $reply_info;   //回复的内容
    $Arr['id_value']      =      $id_value;  //评论的对象
    $Arr['shop_name']     =     $_CFG['shop_name'];  
	$Arr['service_email'] =  $_CFG['service_email'];  
   $_ACT = 'info';
    //$Arr('comment_info.htm');
}
/*------------------------------------------------------ */
//-- 处理 回复用户评论
/*------------------------------------------------------ */
if ($_ACT=='action')
{
    

    /* 获取IP地址 */
    $ip     = real_ip();

    /* 获得评论是否有回复 */
    $sql = "SELECT comment_id, content, parent_id FROM ".COMMENT.
           " WHERE parent_id = '$_REQUEST[comment_id]'";
    $reply_info = $db->selectinfo($sql);

    if (!empty($reply_info['content']))
    {
        /* 更新回复的内容 */
        $sql = "UPDATE ".COMMENT." SET ".
               "email     = '$_POST[email]', ".
               "nickname = '$_POST[nickname]', ".
               "content   = '$_POST[content]', ".
               "add_time  =  '" . gmtime() . "', ".
               "ip_address= '$ip', ".
               "status    = 0".
               " WHERE comment_id = '".$reply_info['comment_id']."'";
    }
    else
    {
        /* 插入回复的评论内容 */
        $sql = "INSERT INTO ".COMMENT." (comment_type, id_value, email, nickname , ".
                    "content, add_time, ip_address, status, parent_id) ".
               "VALUES('$_POST[comment_type]', '$_POST[id_value]','$_POST[email]', " .
                    "'$_SESSION[admin_name]','$_POST[content]','" . gmtime() . "', '$ip', '0', '$_POST[comment_id]')";
    }
    $db->query($sql);

    /* 更新当前的评论状态为已回复并且可以显示此条评论 */
    $sql = "UPDATE " .COMMENT. " SET status = 1 WHERE comment_id = '$_POST[comment_id]'";
    $db->query($sql);
    /* 记录管理员操作 */
    admin_log('', _EDITSTRING_, '评论：'.$reply_info['comment_id']);

    header("Location: comment_manage.php?act=reply&id=$_REQUEST[comment_id]\n");
    exit;
}
/*------------------------------------------------------ */
//-- 更新评论的状态为显示或者禁止
/*------------------------------------------------------ */
if ($_ACT == 'check')
{
    if ($_REQUEST['check'] == 'allow')
    {
        /* 允许评论显示 */
        $sql = "UPDATE " .COMMENT. " SET status = 1 WHERE comment_id = '$_REQUEST[id]'";
        $db->query($sql);
        header("Location: comment_manage.php?act=reply&id=$_REQUEST[id]\n");
        exit;
    }
    else
    {
        /* 禁止评论显示 */
        $sql = "UPDATE " .COMMENT. " SET status = 0 WHERE comment_id = '$_REQUEST[id]'";
        $db->query($sql);

        header("Location: comment_manage.php?act=reply&id=$_REQUEST[id]\n");
        exit;
    }
}

/*------------------------------------------------------ */
//-- 删除某一条评论
/*------------------------------------------------------ */
elseif ($_ACT == 'remove')
{
    $id = intval($_GET['id']);
    $sql = "DELETE FROM " .COMMENT. " WHERE comment_id = '$id'";
    $res = $db->query($sql);
    if ($res)
    {
        $db->query("DELETE FROM " .COMMENT. " WHERE parent_id = '$id'");
    }

    admin_log('', 'remove', 'ads');

    $url = 'comment_manage.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
    header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 批量删除用户评论
/*------------------------------------------------------ */
if ($_ACT == 'batch')
{
    
    $action = isset($_POST['sel_action']) ? trim($_POST['sel_action']) : 'deny';
    if (isset($_POST['checkboxes']))
    {
        switch ($action)
        {
            case 'remove':
				$sql = "DELETE FROM " . COMMENT . " WHERE " . db_create_in($_POST['checkboxes'], 'comment_id');
                $db->query($sql);
                $db->query("DELETE FROM " . COMMENT . " WHERE " . db_create_in($_POST['checkboxes'], 'parent_id'));
                break;

           case 'allow' :
			   $sql="UPDATE " . COMMENT . " SET status = 1  WHERE " . db_create_in($_POST['checkboxes'], 'comment_id');
               $db->query($sql);
               break;

           case 'deny' :
               $db->query("UPDATE " . COMMENT . " SET status = 0  WHERE " . db_create_in($_POST['checkboxes'], 'comment_id'));
               break;

           default :
               break;
        }

        $action = ($action == 'remove') ? _DELSTRING_ : _EDITSTRING_;
        admin_log('', $action, db_create_in($_POST['checkboxes'], 'comment_id'));

        $link[] = array('name' => '返回评论列表', 'url' => 'comment_manage.php?act=list');
        sys_msg(sprintf('批量操作成功', count($_POST['checkboxes'])), 0, $link);
    }
    else
    {
        /* 提示信息 */
        $link[] = array('name' => '返回评论列表', 'url' => 'comment_manage.php?act=list');
        sys_msg('请选择你要操作的评论', 0, $link);
    }
}

/**
 * 获取评论列表
 * @access  public
 * @return  array
 */
function get_comment_list()
{
	global $_CFG;
    /* 查询条件 */
    $filter['keywords']     = empty($_REQUEST['keywords']) ? 0 : trim($_REQUEST['keywords']);
    $filter['sort_by']      = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
    $filter['sort_order']   = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

    $where = (!empty($filter['keywords'])) ? " AND content LIKE '%" . mysql_like_quote($filter['keywords']) . "%' " : '';

    $sql = "SELECT count(*) FROM " .COMMENT. " WHERE parent_id = 0 $where";
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    /* 分页大小 */
    $filter = page_and_size($filter);

    /* 获取评论数据 */
    $arr  = array();
    $sql  = "SELECT * FROM " .COMMENT. " WHERE parent_id = 0 $where " .
            " ORDER BY $filter[sort_by] $filter[sort_order] ".
            " LIMIT ". $filter['start'] .", $filter[page_size]";
    $res  = $GLOBALS['db']->arrQuery($sql);

    foreach($res as $row)
    {
        $sql = ($row['comment_type'] == 0) ?
            "SELECT goods_name FROM " .GOODS. " WHERE goods_id='$row[id_value]'" :
            "SELECT title FROM ".ARTICLE. " WHERE article_id='$row[id_value]'";
        $row['title'] = $GLOBALS['db']->getOne($sql);
        $row['add_time'] = local_date($_CFG['time_format'],$row['add_time']);
        $arr[] = $row;
    }
    $filter['keywords'] = stripslashes($filter['keywords']);
    $arr = array('item' => $arr, 'filter' => $filter, 'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);

    return $arr;
}



$_ACT = $_ACT == 'msg'?'msg':'comment_'.$_ACT;
temp_disp();

?>