<?
//网站发送邮件记录
define('INI_WEB', true);
require_once('../lib/global.php');  
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
admin_priv('email_web_send_log');  //检查权限

/* act操作项的初始化 */
$_ACT = 'email_web_send_log_list';
$_ID  = '';
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = trim($_GET['id']);
$Arr['template_list'] = array(
	0 => '--邮件ID--',
	17  => '用户付款之后',
	18  => '物流发货之后',
	43  => '退款后',
);
/*------------------------------------------------------ */
//-- 获取所有日志列表
/*------------------------------------------------------ */
if('email_web_send_log_list' == $_ACT)
{		
    $template_id = !empty($_GET['template_list'])?intval($_GET['template_list']):0;
    $keywords = !empty($_GET['keywords'])?$_GET['keywords']:'';
	$Arr["template_id"] = $template_id;
	$Arr["keywords"] = $keywords;
	$data['template_id'] = $template_id;
	$data['keywords'] = $keywords;
    $listdb = get_sendlist($data);
    $Arr['listdb']  =  $listdb['listdb'];
    $page=new page(array('total'=>$listdb['record_count'],'perpage'=>$listdb['page_size'])); 
	$Arr["pagestr"]  = $page->show();
}

function get_sendlist($data)
{	
	$sql_count = "SELECT count(*) FROM " . Email_send_history . " e LEFT JOIN " . Mtemplates . " m ON e.template_id = m.template_id WHERE 1";
	if(!empty($data['template_id'])) {
		$sql_count .= ' AND e.template_id = '. $data['template_id'] .'';
	}
	if(!empty($data['keywords'])) {
		$sql_count .= " AND email = '". $data['keywords'] ."'";
	}	
	$filter['record_count'] = $GLOBALS['db']->getOne($sql_count);	
	/* 分页大小 */
	$filter = page_and_size($filter);	
	$whr = " where email_type_id = '$tid' ";
	if(0 == $data['template_id']) $whr = '';	
	/* 查询 */
	$sql = "SELECT e.id, e.email, e.pri, e.state, e.template_id, FROM_UNIXTIME(e.last_send) AS last_send, m.template_subject, m.is_html FROM " . Email_send_history . " e LEFT JOIN " . Mtemplates . " m ON e.template_id = m.template_id WHERE 1";
	if(!empty($data['template_id'])) {
		$sql .= ' AND e.template_id = '. $data['template_id'] .'';
	}
	if(!empty($data['keywords'])) {
		$sql .= " AND e.email = '". $data['keywords'] ."'";
	}
	$sql .=	" ORDER BY id DESC" .
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


$_ACT = "email_web_send_log_list";
temp_disp();
?>