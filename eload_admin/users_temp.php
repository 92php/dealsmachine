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


/*------------------------------------------------------ */
//-- 用户帐号列表
/*------------------------------------------------------ */

if ($_ACT == 'list')
{
    admin_priv('users_temp');/* 检查权限 */
	
	$rank   = array();
	$ranks = include("cache_files/users_grade.php");
	foreach ($ranks as $k => $val)
	{
		$rank[$k] = $ranks[$k]["grade_name"];
	}
	
    $user_list = user_list();
    $Arr['user_list'] =    $user_list['user_list'];
	
    $sort_flag  = sort_flag($user_list['filter']);
    $Arr[$sort_flag['tag']] = $sort_flag['img'];
	$user_list['filter'][$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];
	
    $Arr['filter']       =       $user_list['filter'];
    $Arr['record_count'] = $user_list['record_count'];
    $Arr['user_ranks']   = $rank;
	$page=new page(array('total'=>$user_list['record_count'],'perpage'=>$user_list['page_size']));
	$Arr["pagestr"]  = $page->show();
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
	$ranks = include("cache_files/users_grade.php");
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
    $db->autoExecute('eload_users_temp', $other, 'UPDATE', "email = '$email'");

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
    $sql = "SELECT user_id,email, sex, user_rank , msn,phone  FROM 'eload_users_temp'  WHERE user_id='$_GET[id]'";
    $row = $db->selectinfo($sql);
    if ($row)
    {
        $user['user_id']        = $row['user_id'];
        $user['sex']            = $row['sex'];
        $user['user_rank']      = $row['user_rank'];
        $user['msn']            = $row['msn'];
        $user['email']          = $row['email'];
        $user['phone']          = $row['phone'];
    }
    else
    {
        $user['sex']            = 0;
     }
	 
	$rank   = array();
	$ranks = include("cache_files/users_grade.php");
	foreach ($ranks as $k => $val)
	{
		$rank[$k] = $ranks[$k]["grade_name"];
	}
	 
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
    foreach ($_POST['other'] as $key=>$val)
    {
        if (!empty($val))
        {
            $other[$key] = htmlspecialchars(trim($val));
        }
    }
    $db->autoExecute('eload_users_temp', $other, 'UPDATE', "email = '$email'");

    /* 记录管理员操作 */
    admin_log('', _EDITSTRING_, '会员：'.$email);

    /* 提示信息 */
    $links[0]['name']    = "返回会员列表";
    $links[0]['url']    = 'users.php?act=list&' ;
    $links[1]['name']    = "还需要修改";
    $links[1]['url']    = 'javascript:history.back()';

    sys_msg($_LANG['update_success'], 0, $links);

}

/*------------------------------------------------------ */
//-- 批量删除会员帐号
/*------------------------------------------------------ */

elseif ($_ACT == 'batch_remove')
{
    /* 检查权限 */
    admin_priv('member_add');

    if (isset($_POST['checkboxes']))
    {
        $sql = "SELECT email FROM eload_users_temp WHERE user_id " . db_create_in($_POST['checkboxes']);
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
            $db->query("UPDATE `eload_users_temp` SET email = '$email' WHERE user_id = '$id'");
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

    $sql = "SELECT email FROM eload_users_temp WHERE user_id = '$id'";
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
//-- 删除会员帐号
/*------------------------------------------------------ */

elseif ($_ACT == 'remove')
{
    /* 检查权限 */
    admin_priv('member_add');

    $sql = "SELECT email FROM eload_users_temp WHERE user_id = '" . $_GET['id'] . "'";
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

    $sql = "UPDATE eload_users_temp SET parent_id = 0 WHERE user_id = '" . $_GET['id'] . "'";
    $db->query($sql);

    /* 记录管理员操作 */
    $sql = "SELECT email FROM eload_users_temp WHERE user_id = '" . $_GET['id'] . "'";
    $email = $db->getOne($sql);
    admin_log(addslashes($email), 'edit', 'users');

    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
    sys_msg(sprintf($_LANG['update_success'], $email), 0, $link);
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
        $filter['rank'] = empty($_REQUEST['user_rank']) ? 0 : intval($_REQUEST['user_rank']);

        $filter['sort_by']    = empty($_REQUEST['sort_by'])    ? 'user_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC'     : trim($_REQUEST['sort_order']);

        $ex_where = ' WHERE 1 ';
        if ($filter['keywords'])
        {
            $ex_where .= " AND email LIKE '%" . mysql_like_quote($filter['keywords']) ."%'";
        }

        $filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM eload_users_temp " . $ex_where);

        /* 分页大小 */
        $filter = page_and_size($filter);
        $sql = "SELECT user_id, firstname,lastname, email, is_validated, reg_time ,last_login ,visit_count,last_ip ,user_rank".
                " FROM eload_users_temp" . $ex_where .
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

    $user_list = $GLOBALS['db']->arrQuery($sql);

    $user_rank = include('cache_files/users_grade.php');
    $count = count($user_list);
    for ($i=0; $i<$count; $i++)
    {
        $user_list[$i]['reg_time'] = local_date($GLOBALS['_CFG']['time_format'], $user_list[$i]['reg_time']);
        $user_list[$i]['last_login'] = local_date($GLOBALS['_CFG']['time_format'], $user_list[$i]['last_login']);
        $user_list[$i]['user_rank'] = $user_rank[$user_list[$i]['user_rank']]['grade_name'];
    }

    $arr = array('user_list' => $user_list, 'filter' => $filter,
        'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);

    return $arr;
}
$_ACT = $_ACT == 'msg'?'msg':'users_'.$_ACT;
temp_disp();

?>