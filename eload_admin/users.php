<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once(ROOT_PATH . 'lib/modules/ipb.php');
require_once('lang/users.php');
$users = new ipb($db);

$_ACT = 'list';
$_ID  = '';
$goods_id = 0;
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));
$user_type = array(0=>'未申请',1=>'已申请',2=>'审核通过',3=>'未通过');

/*------------------------------------------------------ */
//-- 用户帐号列表
/*------------------------------------------------------ */

if ($_ACT == 'list')
{
    admin_priv('member_list');/* 检查权限 */

	$rank   = array();
    $ranks = read_static_cache('users_grade', ADMIN_STATIC_CACHE_PATH);
	foreach ($ranks as $k => $val)
	{
		$rank[$k] = $ranks[$k]["grade_name"];
	}

    $user_list = user_list();
    $Arr['user_list'] =    $user_list['user_list'];

    $sort_flag  = sort_flag($user_list['filter']);
    $Arr[$sort_flag['tag']] = $sort_flag['img'];
	$user_list['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];

    $Arr['edit_url']  =  get_url_parameters($_GET,array('act','id'));
    $Arr['title_url'] =  get_url_parameters($_GET,array('sort_order','sort_by'));

	$Arr['user_type'] = array(0=>'未申请',1=>'已申请',2=>'审核通过',3=>'未通过');
	$Arr['user_leixing'] = array(0=>'普通用户',1=>'Affiliate用户');

    $Arr['filter']       =       $user_list['filter'];
    $Arr['record_count'] = $user_list['record_count'];
    $Arr['user_ranks']   = $rank;
	$page=new page(array('total'=>$user_list['record_count'],'perpage'=>$user_list['page_size']));
	$Arr["pagestr"]  = $page->show();
	$Arr['start_date'] = $Arr['filter']['start_date'];
	$Arr['end_date'] = $Arr['filter']['end_date'];
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
	//$user['com_rate']       = 0.05;

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

/*------------------------------------------------------ */
//-- 添加会员帐号
/*------------------------------------------------------ */
elseif ($_ACT == 'insert')
{
    /* 检查权限 */
    admin_priv('member_add');
    $password = empty($_POST['password']) ? '' : trim($_POST['password']);
    $phone = empty($_POST['phone']) ? '' : trim($_POST['phone']);
    $msn = empty($_POST['msn']) ? '' : trim($_POST['msn']);
    $email = empty($_POST['email']) ? '' : trim($_POST['email']);
    $sex = empty($_POST['sex']) ? 0 : intval($_POST['sex']);
    $sex = in_array($sex, array(0, 1, 2)) ? $sex : 0;
    $rank = empty($_POST['user_rank']) ? 0 : intval($_POST['user_rank']);
    $avaid_point = empty($_POST['avaid_point']) ? 0 : intval($_POST['avaid_point']);

    if (!$users->add_user($email, $password))
    {
        /* 插入会员数据失败 */
        if ($users->error == ERR_INVALID_USERNAME)
        {
            $msg = $_LANG['username_invalid'];
        }
        elseif ($users->error == ERR_USERNAME_NOT_ALLOW)
        {
            $msg = $_LANG['username_not_allow'];
        }
        elseif ($users->error == ERR_USERNAME_EXISTS)
        {
            $msg = $_LANG['username_exists'];
        }
        elseif ($users->error == ERR_INVALID_EMAIL)
        {
            $msg = $_LANG['email_invalid'];
        }
        elseif ($users->error == ERR_EMAIL_NOT_ALLOW)
        {
            $msg = $_LANG['email_not_allow'];
        }
        elseif ($users->error == ERR_EMAIL_EXISTS)
        {
            $msg = $_LANG['email_exists'];
        }
        else
        {
            //die('Error:'.$users->error_msg());
        }
        sys_msg($msg, 1);
    }

    /* 更新会员的其它信息 */
    $other =  array();
    $other['user_rank']  = $rank;
    $other['sex']        = $sex;

    foreach ($_POST['other'] as $key=>$val)
    {
        if (!empty($val))
        {
            $other[$key] = htmlspecialchars(trim($val));
        }
    }
    $db->autoExecute(USERS, $other, 'UPDATE', "email = '$email'");

    /* 记录管理员操作 */
    admin_log('', _ADDSTRING_, '会员：'.$_POST['username']);

    /* 提示信息 */
    $link[] = array('name' => "返回会员列表", 'url'=>'users.php?act=list');
    sys_msg(sprintf("添加成功", htmlspecialchars(stripslashes($_POST['username']))), 0, $link);

}

/*------------------------------------------------------ */
//-- 编辑用户帐号
/*------------------------------------------------------ */

elseif ($_ACT == 'edit')
{
    /* 检查权限 */
    admin_priv('member_add');
    $tag_msg = "修改";
    $sql = "SELECT *  FROM " .USERS. "  WHERE user_id='$_GET[id]'";
    $row = $db->selectinfo($sql);
    if ($row)
    {
        $user['user_id']        = $row['user_id'];
        $user['sex']            = $row['sex'];
        $user['user_rank']      = $row['user_rank'];
        $user['msn']            = $row['msn'];
        $user['email']          = $row['email'];
        $user['phone']          = $row['phone'];
        $user['user_type']      = $row['user_type'];
        $user['paypal_account'] = $row['paypal_account'];
        $user['introduction']   = $row['introduction'];
        $user['com_rate']       = $row['com_rate'];
        $user['avaid_point']   = $row['avaid_point'];

    }
    else
    {
        $user['sex']            = 0;
     }

	$rank   = array();
    $ranks = read_static_cache('users_grade', ADMIN_STATIC_CACHE_PATH);
	foreach ($ranks as $k => $val)
	{
		$rank[$k] = $ranks[$k]["grade_name"];
	}

    $Arr['edit_url']  =  get_url_parameters($_GET,array('act'));

	$Arr['user_type'] = $user_type;
    $Arr['form_action'] = 'insert';
    $Arr['user']        = $user;
    $Arr['form_action'] = 'update';
    $Arr['special_ranks']= $rank;
    $Arr['lang']= $_LANG;
	$_ACT = "add";
}

/*------------------------------------------------------ */
//-- 更新用户帐号
/*------------------------------------------------------ */

elseif ($_ACT == 'update')
{
    /* 检查权限 */
    admin_priv('member_add');

    $password = empty($_POST['password']) ? '' : trim($_POST['password']);
    $email = empty($_POST['email']) ? '' : trim($_POST['email']);
    $sex = empty($_POST['sex']) ? 0 : intval($_POST['sex']);
    $sex = in_array($sex, array(0, 1, 2)) ? $sex : 0;
    $rank = empty($_POST['user_rank']) ? 0 : intval($_POST['user_rank']);
	$avaid_point = empty($_POST['avaid_point']) ? 0 : intval($_POST['avaid_point']);

    if (!$users->edit_user(array('password'=>$password, 'email'=>$email, 'gender'=>$sex), 1))
    {
        if ($users->error == ERR_EMAIL_EXISTS)
        {
            $msg = $_LANG['email_exists'];
        }
        else
        {
            $msg = $_LANG['edit_user_failed'];
        }
        sys_msg($msg, 1);
    }

    /* 更新会员的其它信息 */
    $other =  array();
    $other['user_rank'] = $rank;
    $other['avaid_point']        = $avaid_point;

    foreach ($_POST['other'] as $key=>$val)
    {
        if (!empty($val))
        {
            $other[$key] = htmlspecialchars(trim($val));
        }
    }



	//积分有变动
	$yuanArr = $db->selectinfo("select avaid_point,user_id from eload_users where email = '".$email."'");
	if ($yuanArr['avaid_point'] != $other['avaid_point'])
	{
		$piont_cha = $other['avaid_point'] - $yuanArr['avaid_point'];
		$note = empty($_POST['add_point_desc'])?'':$_POST['add_point_desc'];
		if($yuanArr['user_id']) add_point($yuanArr['user_id'],$piont_cha,2,$note);
	}

	//$other['user_type']      = $_POST['user_type'];
	$other['paypal_account'] = $_POST['paypal_account'];
	$other['introduction']   = $_POST['introduction'];
	$other['email']   = $_POST['introduction'];

	if(!empty($_POST['com_rate']))
    $other['com_rate']       = $_POST['com_rate'];
    $other['email']       = $_POST['email'];

    $db->autoExecute(USERS, $other, 'UPDATE', "user_id = '$_ID'");

    /* 记录管理员操作 */
    admin_log('', _EDITSTRING_, '会员：'.$email);

    /* 提示信息 */

	$edit_url = get_url_parameters($_GET,array('act'));

    $links[0]['name']    = "返回会员列表";
    $links[0]['url']    = 'users.php?act=list'.$edit_url ;
    $links[1]['name']    = "还需要修改";
    $links[1]['url']    = 'javascript:history.back()';

    sys_msg($_LANG['update_success'], 0, $links);

}

/*------------------------------------------------------ */
//-- 批量删除会员帐号
/*------------------------------------------------------ */

elseif ($_ACT == 'batch_remove')
{
	//不可删除用户 by fangxin on 2013-12-20 15:38:48
    $lnk[] = array('text' => '返回', 'href'=>'users.php?act=list');
    sys_msg('不可删除用户', 0, $lnk);

    /* 检查权限 */
    admin_priv('member_add');

    if (isset($_POST['checkboxes']))
    {
        $sql = "SELECT email FROM " . USERS . " WHERE user_id " . db_create_in($_POST['checkboxes']);
        $col = $db->getCol($sql);

        $emails = implode(',',addslashes_deep($col));
        $count = count($col);
        /* 通过插件来删除用户 */
        $users->remove_user($col);

        admin_log('', _DELSTRING_, '以下会员：'.$emails);

        $lnk[] = array('name' => "返回会员列表", 'url'=>'users.php?act=list');
        sys_msg(sprintf($_LANG['batch_remove_success'], $count), 0, $lnk);
    }
    else
    {
        $lnk[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
        sys_msg($_LANG['no_select_user'], 0, $lnk);
    }
}

/* 编辑用户名 */
elseif ($_ACT == 'edit_username')
{
    /* 检查权限 */
    check_authz_json('users_manage');

    $email = empty($_REQUEST['val']) ? '' : json_str_iconv(trim($_REQUEST['val']));
    $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

    if ($id == 0)
    {
        make_json_error('NO USER ID');
        return;
    }

    if ($email == '')
    {
        make_json_error($GLOBALS['_LANG']['username_empty']);
        return;
    }

    $users =& init_users();

    if ($users->edit_user($id, $email))
    {
        if ($_CFG['integrate_code'] != 'ecshop')
        {
            /* 更新商城会员表 */
            $db->query('UPDATE ' .USERS. " SET email = '$email' WHERE user_id = '$id'");
        }

        admin_log(addslashes($email), 'edit', 'users');
        make_json_result(stripcslashes($email));
    }
    else
    {
        $msg = ($users->error == ERR_USERNAME_EXISTS) ? $GLOBALS['_LANG']['username_exists'] : $GLOBALS['_LANG']['edit_user_failed'];
        make_json_error($msg);
    }
}

/*------------------------------------------------------ */
//-- 编辑email
/*------------------------------------------------------ */
elseif ($_ACT == 'edit_email')
{
    /* 检查权限 */
    check_authz_json('users_manage');

    $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
    $email = empty($_REQUEST['val']) ? '' : json_str_iconv(trim($_REQUEST['val']));

    $users =& init_users();

    $sql = "SELECT email FROM " . USERS . " WHERE user_id = '$id'";
    $email = $db->getOne($sql);


    if (is_email($email))
    {
        if ($users->edit_user(array('username'=>$email, 'email'=>$email)))
        {
            admin_log(addslashes($email), 'edit', 'users');

            make_json_result(stripcslashes($email));
        }
        else
        {
            $msg = ($users->error == ERR_EMAIL_EXISTS) ? $GLOBALS['_LANG']['email_exists'] : $GLOBALS['_LANG']['edit_user_failed'];
            make_json_error($msg);
        }
    }
    else
    {
        make_json_error($GLOBALS['_LANG']['invalid_email']);
    }
}

/*------------------------------------------------------ */
//-- 查看会员积分
/*------------------------------------------------------ */

elseif ($_ACT == 'ebpoint')
{
    admin_priv('member_add');
    include_once(ROOT_PATH . 'lib/lib.f.transaction.php');

	$user_id = empty($_GET['id'])?0:intval($_GET['id']);
	$start_date = empty($_GET['start_date']) ? '' :local_strtotime($_GET['start_date']);    //时间--开始
    $end_date = empty($_GET['end_date']) ? '' :local_strtotime($_GET['end_date']);          //时间--结束

    $where ='';
    if($start_date){
    	$where .=" and adddate>'$start_date' ";
    }
    if($end_date){
    	$where .=" and adddate<'$end_date' ";
    }
    $record_count = $db->getOne("SELECT COUNT(*) FROM " .POINT_RECORD. " WHERE user_id = '$user_id' $where ");

    $size = 20;
	$_GET['page'] = empty($_GET['page'])?1:intval($_GET['page']);
	$page_count = ceil($record_count/$size);
	if ($_GET['page'] > $page_count ) $_GET['page'] = $page_count;
	if ($_GET['page'] < 1 ) $_GET['page'] = 1;
	$start = ($_GET['page'] - 1) * $size;

	$_GET['x'] = '2';
	$page=new page(array('total' => $record_count,'perpage'=>$size));
	$Arr["pagestr"]  = $page->show();
    $records = get_point_records($user_id,$size,$start,$where);

    $Arr['points_record'] = $records;
	$Arr['action'] = $_ACT;
    $_ACT = 'point_list';

}
/*------------------------------------------------------ */
//-- 删除会员帐号
/*------------------------------------------------------ */

elseif ($_ACT == 'remove')
{
	//不可删除用户 by fangxin on 2013-12-20 15:38:48
    $lnk[] = array('text' => '返回', 'href'=>'users.php?act=list');
    sys_msg('不可删除用户', 0, $lnk);

    /* 检查权限 */
    admin_priv('member_add');

    $sql = "SELECT email FROM " . USERS . " WHERE user_id = '" . $_GET['id'] . "'";
    $email = $db->getOne($sql);
    /* 通过插件来删除用户 */
    $users->remove_user($email); //已经删除用户所有数据

    /* 记录管理员操作 */
    admin_log(addslashes($email), 'remove', 'users');

    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
    sys_msg(sprintf($_LANG['remove_success'], $email), 0, $link);
}

/*------------------------------------------------------ */
//--  收货地址查看
/*------------------------------------------------------ */
elseif ($_ACT == 'address_list')
{
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $sql = "SELECT  *  FROM " .UADDR. " ".
           " WHERE user_id='$id'";
    $address = $db->arrQuery($sql);
	$area_Arr = read_static_cache('area_key',2);
	foreach ($address as $k => $v){
		$address[$k]["country_name"] = $area_Arr[$v['country']]['region_name'];
		$address[$k]["email"] = email_disp_process($v['email']);
	}


	$Arr['address'] = $address;
}

/*------------------------------------------------------ */
//-- 脱离推荐关系
/*------------------------------------------------------ */

elseif ($_ACT == 'remove_parent')
{
    /* 检查权限 */
    admin_priv('users_manage');

    $sql = "UPDATE " . USERS . " SET parent_id = 0 WHERE user_id = '" . $_GET['id'] . "'";
    $db->query($sql);

    /* 记录管理员操作 */
    $sql = "SELECT email FROM " . USERS . " WHERE user_id = '" . $_GET['id'] . "'";
    $email = $db->getOne($sql);
    admin_log(addslashes($email), 'edit', 'users');

    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
    sys_msg(sprintf($_LANG['update_success'], $email), 0, $link);
}

if ($_ACT == 'point_stat') //积分统计
{

	//$_ACT = 'users_point_lists';
    admin_priv('point_stat');/* 检查权限 */

	$rank   = array();
    $ranks = read_static_cache('users_grade', ADMIN_STATIC_CACHE_PATH);
	foreach ($ranks as $k => $val)
	{
		$rank[$k] = $ranks[$k]["grade_name"];
	}

    $user_list = point_stat($where);
    //print_r($user_list);




    $Arr['user_list'] =    $user_list['user_list'];

    $sort_flag  = sort_flag($user_list['filter']);
    $Arr[$sort_flag['tag']] = $sort_flag['img'];
	$user_list['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];

    $Arr['edit_url']  =  get_url_parameters($_GET,array('act','id'));
    $Arr['title_url'] =  get_url_parameters($_GET,array('sort_order','sort_by'));

	//$Arr['user_type'] = array(0=>'未申请',1=>'已申请',2=>'审核通过',3=>'未通过');
	$Arr['user_leixing'] = array(0=>'普通用户',1=>'Affiliate用户');

    $Arr['filter']       =       $user_list['filter'];
    $Arr['record_count'] = $user_list['record_count'];
    $Arr['user_ranks']   = $rank;
   // print_r($user_list);
	$page=new page(array('total'=>$user_list['record_count'],'perpage'=>$user_list['page_size']));
	$Arr["pagestr"]  = $page->show();
	$Arr['start_date'] = $Arr['start_date'];
	$Arr['end_date'] = $Arr['end_date'];
	$Arr['users'] = $users;

	//$_ACT = $_ACT == 'msg'?'msg':'';
	//temp_disp();
	//exit();
}
/**
 * 返回积分统计信息
 * @where 条件语句
 */
function point_stat($where){
 /* 过滤条件 */
 		global $db;
 	 //exit();
        $filter['keywords'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
		$filter['start_date'] = empty($_REQUEST['start_date']) ? '' : $_REQUEST['start_date'];    //注册时间--开始
        $filter['end_date'] = empty($_REQUEST['end_date']) ? '' :$_REQUEST['end_date'];          //注册时间--结束
        $filter['user_id'] = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
        $filter['sort_by']    = empty($_REQUEST['sort_by'])    ? 'income' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC'     : trim($_REQUEST['sort_order']);

        $ex_where = ' WHERE 1 ';
        if ($filter['keywords'])        $ex_where .= " AND email LIKE '%" . mysql_like_quote($filter['keywords']) ."%'";
        if ($filter['rank']!='')        $ex_where .= " AND user_rank = '" . $filter['rank'] ."'";
        if ($filter['user_type']!='')   $ex_where .= " AND user_type = '" . $filter['user_type'] ."'";

        if ($filter['end_date'])$ex_where .= " AND r.adddate  <= '" . local_strtotime($filter['end_date']) ."'";
        if ($filter['start_date'])$ex_where .= " AND r.adddate  >= '" . local_strtotime($filter['start_date']) ."'";

        $filter['record_count'] = $GLOBALS['db']->getOne("SELECT count(*)".
                " FROM " . USERS .' u join '.POINT_RECORD.' r on  u.user_id=r.user_id '. $ex_where .' group by u.user_id ');
		$filter['record_count'] = $filter['record_count']?$filter['record_count']:0;

	    if(!$filter['record_count']){
			//return ;
		}

        /* 分页大小 */

        $filter = page_and_size($filter);
        //print_r($filter);
        if($filter['sort_by'] ){
        	//$filter['sort_by'] = '(select count(*) from `eload_review` where  is_pass = 1 and goods_id =  g.goods_id)'
        }


        $sql = "SELECT u.user_id,sum(income) as income,sum(outgo) as outgo,avaid_point,user_type, firstname,lastname, u.email, is_validated,is_need_chknum, reg_time ,last_login ,visit_count,last_ip ,user_rank".
                " FROM " . USERS .' u join '.POINT_RECORD.' r on  u.user_id=r.user_id '. $ex_where .' group by user_id '.
                " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];
		//echo $sql;
        $user = $db->arrQuery($sql);
        $user_id = arr2str($user,'user_id');
       //return $user;

        if(!$user_id)  $ex_where = " where user_id in($user_id)";


        foreach ($user as $k=>$v){

        	$user[$k]['reg_time'] = local_date($GLOBALS['_CFG']['time_format'], $user[$k]['reg_time']);
        	$user[$k]['email']		= email_disp_process($v['email']);
        }
        //$sql = 'select user_id,sum(income) as income,sum(outgo) as outgo from '.POINT_RECORD." $ex_where group by user_id";
       // $point_arr = $db->arrQuery($sql);
       // $point_arr = fetch_id($point_arr,'user_id');
      //  print_r($point_arr);

        //$user['record_count'] = $filter['record_count'] ;
        //print_r($user);
        $filter['keywords'] = stripslashes($filter['keywords']);

        $arr = array('user_list' => $user, 'filter' => $filter,
        'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);

        return $arr;

}





/**
 *  返回用户列表数据
 *
 * @access  public
 * @param
 *
 * @return void
 */
function user_list()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 过滤条件 */
        $filter['keywords'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        $filter['rank'] = !isset($_REQUEST['user_rank']) ? '' : $_REQUEST['user_rank'];
        $filter['user_type'] = !isset($_REQUEST['user_type']) ? '' : intval($_REQUEST['user_type']);
        $filter['user_leixing'] = !isset($_REQUEST['user_leixing']) ? '' : intval($_REQUEST['user_leixing']);
		$filter['start_date'] = empty($_REQUEST['start_date']) ? '' : $_REQUEST['start_date'];    //注册时间--开始
        $filter['end_date'] = empty($_REQUEST['end_date']) ? '' : $_REQUEST['end_date'];          //注册时间--结束

        $filter['user_id'] = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);


		//查询团购会员
        $filter['goods_id'] = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
        $filter['groupdealflag'] = empty($_REQUEST['groupdealflag']) ? 0 : intval($_REQUEST['groupdealflag']);



        $filter['sort_by']    = empty($_REQUEST['sort_by'])    ? 'user_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC'     : trim($_REQUEST['sort_order']);

        $ex_where = ' WHERE 1 ';
        if ($filter['keywords'])
        {
        	if(!have_priv('member_list_all')){
            	$ex_where .= " AND email LIKE '%" . mysql_like_quote($filter['keywords']) ."%'";
        	}ELSE{
        		$ex_where .= " AND email = '" . mysql_like_quote($filter['keywords']) ."'";
        	}
        }

        if(!have_priv('member_list_all')&&empty($filter['keywords'])&&empty($filter['user_id'])){  //没有查看全部用户的权限
	 		$ex_where .= " AND 1=2";
        }
        if ($filter['rank']!='')
        {
            $ex_where .= " AND user_rank = '" . $filter['rank'] ."'";
        }

        if ($filter['user_type']!='')
        {
            $ex_where .= " AND user_type = '" . $filter['user_type'] ."'";
        }
		//查团购会员
		if ($filter['goods_id']){
			$buyerArr = array();
			$allbur = array();


			$sql = "select session_id from eload_cart where is_groupbuy = 1 and session_id like '%@%' and goods_id = '".$filter['goods_id']."'";

			$buyerArr =  $GLOBALS['db']->arrQuery($sql);
			foreach($buyerArr as $eee){
				$allbur[] = $eee['session_id'];
			}

			if($filter['groupdealflag']){

				$sql = "select e.email from eload_order_goods as g,eload_order_info as o,eload_users as e where g.is_groupbuy = 1 and g.goods_id = '".$filter['goods_id']."' and g.order_id = o.order_id and o.user_id = e.user_id";
				$buyerArr =  $GLOBALS['db']->arrQuery($sql);
				foreach($buyerArr as $eee){
					$allbur[] = $eee['email'];
				}

			}
			$ex_where .= db_create_in($allbur, ' and email');
		}
        if ($filter['user_leixing'] !='')
        {
			if ($filter['user_leixing']== '1' ){
                $ex_where .= " AND user_type > '0'";
			}else if($filter['user_leixing'] == '0'){
                $ex_where .= " AND user_type = '0' ";
				//var_dump($filter['user_leixing']);
			}
        }
        if ($filter['start_date'] != ''){    //注册时间--开始
            $ex_where .= ' AND reg_time > ' . local_strtotime($filter['start_date']);
        }

        if ($filter['end_date'] != ''){    //注册时间--结束
            $ex_where .= ' AND reg_time <= ' . local_strtotime($filter['end_date']);
        }

        if ($filter['user_id'])
        {
            $ex_where .= " AND user_id = '" . $filter['user_id'] ."'";
        }

        $filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . USERS . $ex_where);

        /* 分页大小 */
        $filter = page_and_size($filter);
        $sql = "SELECT user_id,user_type, firstname,lastname, email, is_validated,is_need_chknum, reg_time ,last_login ,visit_count,last_ip ,user_rank".
                " FROM " . USERS . $ex_where .
                " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

        $filter['keywords'] = stripslashes($filter['keywords']);
       // set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $user_list = $GLOBALS['db']->arrQuery($sql);

    $user_rank = read_static_cache('users_grade', ADMIN_STATIC_CACHE_PATH);
    $count = count($user_list);
    for ($i=0; $i<$count; $i++)
    {
        $user_list[$i]['reg_time'] = local_date($GLOBALS['_CFG']['time_format'], $user_list[$i]['reg_time']);
        $user_list[$i]['last_login'] = local_date($GLOBALS['_CFG']['time_format'], $user_list[$i]['last_login']);
        $user_list[$i]['user_rank'] = $user_rank[$user_list[$i]['user_rank']]['grade_name'];
        $user_list[$i]['email']		= email_disp_process($user_list[$i]['email']);
    }

    $arr = array('user_list' => $user_list, 'filter' => $filter,
        'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);

    return $arr;
}
$_ACT = $_ACT == 'msg'?'msg':'users_'.$_ACT;
temp_disp();

?>