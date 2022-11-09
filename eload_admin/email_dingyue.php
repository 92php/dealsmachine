<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
$_ACT = 'list';
$_ID  = '';
$goods_id = 0;
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));


/*------------------------------------------------------ */
//-- 用户帐号列表
/*------------------------------------------------------ */

if ($_ACT == 'list')
{
    admin_priv('email_dingyue');/* 检查权限 */
    $Arr['source'] = array('0'=>'--','1'=>'首页弹窗','2'=>'底部','3'=>'列表','4'=>'注册');
	$Arr['source_selected'] = empty($_GET['source'])?'0': $_GET['source'];

    $Arr['user_type'] = array('0'=>'--','1'=>'已注册','2'=>'未注册');
    $Arr['user_type_selected'] = empty($_GET['user_type'])?'0': $_GET['user_type'];

    $email_list = email_list();
    $Arr['email_list'] =    $email_list['email_list'];

    $sort_flag  = sort_flag($email_list['filter']);
    $Arr[$sort_flag['tag']] = $sort_flag['img'];
	$email_list['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];

    $Arr['filter']       =       $email_list['filter'];
    $Arr['record_count'] = $email_list['record_count'];
	$page=new page(array('total'=>$email_list['record_count'],'perpage'=>$email_list['page_size']));
	$Arr["pagestr"]  = $page->show();
}

/*------------------------------------------------------ */
//-- 批量删除
//add by jim on 2013-12-14
/*------------------------------------------------------ */
elseif ($_ACT == 'batch_remove')
{
    /* 检查权限 */
    admin_priv('email_dingyue');

    if (isset($_POST['checkboxes']))
    {
        $sql = "SELECT email FROM " . Email_list . " WHERE id " . db_create_in($_POST['checkboxes']);
        $col = $db->getCol($sql);

        $emails = implode(',',addslashes_deep($col));
        $count = count($col);
        /* 通过插件来删除用户 */

		$db->delete(Email_list,"id " . db_create_in($_POST['checkboxes']));
        admin_log('', _DELSTRING_, '以下会员订阅：'.$emails);

        $lnk[] = array('name' => "返回列表", 'url'=>'?act=list');
        sys_msg(sprintf('已经成功删除了 %d 个订阅邮件账号。', $count), 0, $lnk,0);
    }
    else
    {
        $lnk[] = array('text' => $_LANG['go_back'], 'href'=>'?act=list');
        sys_msg($_LANG['no_select_user'], 0, $lnk,0);
    }
}

/*------------------------------------------------------ */
//-- 添加会员帐号
/*------------------------------------------------------ */
elseif ($_ACT == 'add')
{
    /* 检查权限 */
    admin_priv('member_add');
    $tag_msg = "添加";
    $user = array(  'sex'           => 0
                    );

    $Arr['form_action']= 'insert';
    $Arr['user']=         $user;

	$rank   = array();
    $ranks = read_static_cache('users_grade', ADMIN_STATIC_CACHE_PATH);
	foreach ($ranks as $k => $val)
	{
		$rank[$k] = $ranks[$k]["grade_name"];
	}
    $Arr['lang']= $_LANG;
    $Arr['special_ranks']= $rank;
    $Arr['tag_msg']= $tag_msg;

}


/**
 *  返回用户列表数据
 *
 * @access  public
 * @param
 *
 * @return void
 */
function email_list()
{
	global $Arr;
    $result = get_filter();
    if ($result === false)
    {
        /* 过滤条件 */



        $filter['keywords'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        $filter['end_time'] = empty($_REQUEST['end_time']) ? '' : trim($_REQUEST['end_time']);
        $filter['start_time'] = empty($_REQUEST['start_time']) ? '' : trim($_REQUEST['start_time']);
        $filter['user_type_selected'] = $Arr['user_type_selected'];
        $filter['source_selected'] = $Arr['source_selected'];

        $filter['sort_by']    = empty($_REQUEST['sort_by'])    ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC'     : trim($_REQUEST['sort_order']);

        $ex_where = ' WHERE 1 ';
        if ($filter['keywords'])
        {
            $ex_where .= " AND (email LIKE '%" . mysql_like_quote($filter['keywords']) ."%' or firstname LIKE '%" . mysql_like_quote($filter['keywords']) ."%')";
        }
        if ($filter['end_time'])
        {
            $ex_where .= " AND addTime<".local_strtotime($filter['end_time']);

        }
        if ($filter['start_time'])
        {
            $ex_where .= " AND addTime>".local_strtotime($filter['start_time']);
        }
        if ($filter['user_type_selected'])
        {
            $ex_where .= " AND isReg={$filter['user_type_selected']}";
        }
        if ($filter['source_selected'])
        {
            $ex_where .= " AND source={$filter['source_selected']}";
        }

        $filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM ".Email_list." " . $ex_where);

        /* 分页大小 */
        $filter = page_and_size($filter);
        $sql = "SELECT  * ".
                " FROM ".Email_list." " . $ex_where .
                " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

        $filter['keywords'] = stripslashes($filter['keywords']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $email_list = $GLOBALS['db']->arrQuery($sql);
    foreach($email_list as &$v){
    	$v['addTime'] =  local_date('Y-m-d H:i', $v['addTime']);
    	$v['email']		= email_disp_process($v['email']);
    }
    $arr = array('email_list' => $email_list, 'filter' => $filter,
        'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);

    return $arr;
}
$_ACT = $_ACT == 'msg'?'msg':'email_dingyue_'.$_ACT;
temp_disp();

?>