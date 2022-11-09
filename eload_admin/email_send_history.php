<?php
define('INI_WEB', true);
$payment_list = "";
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
admin_priv('email_send_history');  //检查权限

/* act操作项的初始化 */
$_ACT = 'list';
$_ID  = '';
if(!empty($_GET['act'])) $_ACT=$_GET['act'];
if(!empty($_GET['id'])) $_ID=$_GET['id'];

if($_ACT == 'list'){
	
	$tid = empty($_GET['tid'])?0:intval($_GET['tid']);
	$sql = 'SELECT * FROM '.Batch_email_type.' ORDER BY id DESC';
	$email_type_arr = $db->arrQuery($sql);
	foreach($email_type_arr as $k => $row){
		if ($tid == 0){$tid = $row['id'];}
		$email_type_arr[$k]['creat_time'] = local_date($GLOBALS['_CFG']['time_format'], $email_type_arr[$k]['creat_time']);
		$email_type_arr[$k]['start_time'] = local_date($GLOBALS['_CFG']['date_format'], $email_type_arr[$k]['start_time']);
		$email_type_arr[$k]['end_time']   = local_date($GLOBALS['_CFG']['date_format'], $email_type_arr[$k]['end_time']);
	}
	
	
	$Arr['email_type_arr'] = $email_type_arr ;
	
    
    $listdb = get_sendlist($tid);
    $Arr['listdb']  =  $listdb['listdb'];
    $sort_flag  = sort_flag($listdb['filter']);
    $Arr[$sort_flag['tag']] = $sort_flag['img'];
	$listdb['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];
    $Arr['filter']  =  $listdb['filter'];




    $page=new page(array('total'=>$listdb['record_count'],'perpage'=>$listdb['page_size'])); 
	$Arr["pagestr"]  = $page->show();
	
}

elseif ($_ACT == 'del')
{
    $id = (int)$_REQUEST['id'];
    $sql = "DELETE FROM " . Email_send_history . " WHERE id = '$id' LIMIT 1";
    $db->query($sql);
	header("Location: ?act=list");
    exit;
}
//清空所有的邮件群发邮件历史记录
elseif ($_ACT == 'remove_all_history')
{
    $sql = "DELETE FROM " . Email_send_history . "";  //清除历史记录；
    $db->query($sql);
	
    $sql = "DELETE FROM " . Batch_email_type . "";   //删除搜索类型
    $db->query($sql);
	header("Location: ?act=list");
    exit;
}

/*------------------------------------------------------ */
//-- 批量删除
/*------------------------------------------------------ */

elseif ($_ACT == 'batch_remove')
{
    /* 检查权限 */
    if (isset($_POST['checkboxes']))
    {
        $sql = "DELETE FROM " . Email_send_history . " WHERE id " . db_create_in($_POST['checkboxes']);
        $db->query($sql);

        $links[] = array('name' => '返回历史列表', 'url' => '?act=list');
        sys_msg('删除成功', 0, $links);
    }
    else
    {
        $links[] = array('name' => '返回历史列表', 'url' => '?act=list');
        sys_msg('你没有选择邮件！', 0, $links);
    }
}




function get_sendlist($tid = 0)
{
	$filter['sort_by']      = empty($_GET['sort_by']) ? 'pri' : trim($_GET['sort_by']);
	$filter['sort_order']   = empty($_GET['sort_order']) ? 'DESC' : trim($_GET['sort_order']);
	
	$sql = "SELECT count(*) FROM " . Email_send_history . " e LEFT JOIN " . Mtemplates . " m ON e.template_id = m.template_id";
	$filter['record_count'] = $GLOBALS['db']->getOne($sql);
	
	/* 分页大小 */
	$filter = page_and_size($filter);
	
	$whr = " where email_type_id = '$tid' ";
	if ($tid == 0 ) $whr = '';
	
	/* 查询 */
	$sql = "SELECT e.id, e.email, e.pri, e.state, FROM_UNIXTIME(e.last_send) AS last_send, m.template_subject, m.is_html FROM " . Email_send_history . " e LEFT JOIN " . Mtemplates . " m ON e.template_id = m.template_id" .
		" $whr ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
	   " LIMIT " . $filter['start'] . ",$filter[page_size]";
	set_filter($filter, $sql);
    $listdb = $GLOBALS['db']->arrQuery($sql);
    if($listdb)
    {
    	foreach($listdb as $k=>$v)
    	{
    		$listdb[$k]['email']	= email_disp_process($v['email']);
    	}
    }
	
    $arr = array('listdb' => $listdb, 'filter' => $filter, 'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);

    return $arr;
}




$_ACT = $_ACT == 'msg'?'msg':'email_send_history_'.$_ACT;
temp_disp();
?>