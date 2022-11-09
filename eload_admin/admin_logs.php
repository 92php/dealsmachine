<?
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
//usleep(1000000 * .5);

admin_priv('loglist');  //检查权限

/* act操作项的初始化 */
$_ACT = 'loglist';
$_ID  = '';

if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = trim($_GET['id']);

/*------------------------------------------------------ */
//-- 获取所有日志列表
/*------------------------------------------------------ */
if ($_ACT == 'loglist')
{

    $user_id   = !empty($_GET['id'])       ? intval($_GET['id']) : 0;
    $admin_ip  = !empty($_GET['ip'])       ? $_GET['ip']         : '';
    $log_date  = !empty($_GET['log_date']) ? $_GET['log_date']   : '';

    /* 查询IP地址列表 */
    $ip_list = array();
    $sql = "SELECT DISTINCT ip_address FROM ".ALOGS;
	$ip_list = $db -> arrQuery($sql);
	$Arr["ip_list"] = $ip_list;

    $log_list = get_admin_logs();
	
	$Arr["log_list"]     = $log_list['list'];
	$Arr["list"]         = $log_list['list'];

    $sort_flag           = sort_flag($log_list['filter']);
	$Arr[$sort_flag['tag']] = $sort_flag['img'];
	$log_list['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];
	$Arr["filter"]       = $log_list['filter'];
	//$page=new page(array('total'=>1000,'perpage'=>20,'ajax'=>'ajax_page','page_name'=>'test')); 
	$page=new page(array('total'=>$log_list['record_count'],'perpage'=>$log_list['page_size']));
	$Arr["pagestr"]  = $page->show();
}

/*------------------------------------------------------ */
//-- 排序、分页、查询 ------------------------------------
/*------------------------------------------------------ */
elseif ($_ACT == 'query')
{
    $log_list = get_admin_logs();

    $smarty->assign('log_list',        $log_list['list']);
    $smarty->assign('filter',          $log_list['filter']);
    $smarty->assign('record_count',    $log_list['record_count']);
    $smarty->assign('page_count',      $log_list['page_count']);

    $sort_flag  = sort_flag($log_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
    
    make_json_result($smarty->fetch('admin_logs.htm'), '',
    array('filter' => $log_list['filter'], 'page_count' => $log_list['page_count']));
}

/*------------------------------------------------------ */
//-- 批量删除日志记录 
/*------------------------------------------------------ */
if ($_ACT == 'batch_drop')
{

    $drop_type_date = isset($_POST['drop_type_date']) ? $_POST['drop_type_date'] : '';

    /* 按日期删除日志 */
    if ($drop_type_date)
    {
        if ($_POST['log_date'] == '0')
        {
            ecs_header("Location: admin_logs.php\n");
            exit;
        }
        elseif ($_POST['log_date'] > '0')
        {
            $where = " WHERE 1 ";
            switch ($_POST['log_date'])
            {
                case '1':
                    $a_week = gmtime()-(3600 * 24 * 7);
                    $where .= " AND log_time <= '".$a_week."'";
                    break;
                case '2':
                    $a_month = gmtime()-(3600 * 24 * 30);
                    $where .= " AND log_time <= '".$a_month."'";
                    break;
                case '3':
                    $three_month = gmtime()-(3600 * 24 * 90);
                    $where .= " AND log_time <= '".$three_month."'";
                    break;
                case '4':
                    $half_year = gmtime()-(3600 * 24 * 180);
                    $where .= " AND log_time <= '".$half_year."'";
                    break;
                case '5':
                    $a_year = gmtime()-(3600 * 24 * 365);
                    $where .= " AND log_time <= '".$a_year."'";
                    break;
            }
            $sql = "DELETE FROM " .ALOGS.$where;
            $res = $db->query($sql);
            if ($res)
            {
                admin_log('','按时间'._DELSTRING_, '后台管理日志');

                $link[] = array('name' => '返回日志列表', 'url' => 'admin_logs.php');
                sys_msg('删除成功', 1, $link);
            }
        }
    }
    /* 如果不是按日期来删除, 就按ID删除日志 */
    else
    {
        $count = 0;
        foreach ($_POST['checkboxes'] AS $key => $id)
        {
            $sql = "DELETE FROM " .ALOGS. " WHERE log_id = '$id'";
            $result = $db->query($sql);

            $count++;
        }
        if ($result)
        {
            admin_log('', '', sprintf('批量删除了 %d 个日志记录', $count));

            $link[] = array('name' => '返回日志列表', 'url' => 'admin_logs.php');
            sys_msg(sprintf('成功删除了 %d 个日志记录', $count), 0, $link);
        }
    }
}

/* 获取管理员操作记录 */
function get_admin_logs()
{   global $db;

    $user_id  = !empty($_GET['id']) ? intval($_GET['id']) : 0;
    $admin_ip = !empty($_GET['ip']) ? $_GET['ip']         : '';

    $filter = array();
    $filter['sort_by']      = empty($_GET['sort_by']) ? 'al.log_id' : trim($_GET['sort_by']);
    $filter['sort_order'] = empty($_GET['sort_order']) ? 'DESC' : trim($_GET['sort_order']);
    $filter['keyword'] = empty($_GET['keyword']) ? '' : trim($_GET['keyword']);

    //查询条件
    $where = " 1 ";
    if (!empty($user_id))
    {
        $where .= " AND al.user_id = '$user_id' ";
    }
    elseif (!empty($admin_ip))
    {
        $where .= " AND al.ip_address = '$admin_ip' ";
    }
	
	if ($filter['keyword']){
        $where .= " AND al.log_info like '%".$filter['keyword']."%' ";
	}
	
	

    /* 获得总记录数据 */
    $filter['record_count'] = $db->count_info(' ' .ALOGS. ' AS al ',"*"," $where");
    $filter = page_and_size($filter);
	
    /* 获取管理员日志记录 */
    $list  = array();
    $sql   = 'SELECT al.*, u.real_name FROM ' .ALOGS. ' AS al '.
            'LEFT JOIN ' .SADMIN. ' AS u ON u.said = al.user_id  where '.
            $where .' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'] ;
    $list  = $db->selectLimit($sql, $filter['page_size'], $filter['start']);
	foreach ($list as $k => $v){
		$list[$k]["log_time"] = local_date($GLOBALS['_CFG']['time_format'], $list[$k]['log_time']);
	}
	
	
    return array('list' => $list, 'filter' => $filter,'record_count' => $filter['record_count'],'page_size'=> $filter['page_size']);
}

$_ACT = "loglist";
temp_disp();
?>