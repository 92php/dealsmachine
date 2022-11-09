<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once(ROOT_PATH . 'lib/modules/ipb.php');
require_once('lang/users.php');
$users = new ipb($db);
$_ACT  = 'list';
$_ID   = '';
$goods_id = 0;
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));
$user_type = array(0=>'未申请',1=>'已申请',2=>'审核通过',3=>'未通过');

/*------------------------------------------------------ */
//-- 用户帐号列表
/*------------------------------------------------------ */

if ($_ACT == 'list')
{
    admin_priv('affiliate_users_list');/* 检查权限 */
    if(empty($_GET['user_type']));//$_GET['user_type']=3;
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
	$Arr['user_type'] = $user_type;//array(1=>'已申请',2=>'审核通过',3=>'未通过');
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
    admin_priv('affiliate_users_list');
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

elseif ($_ACT == 'save'){//保存用户信息
	admin_priv('affiliate_users_list');
	$user_id = !empty($_POST['id'])?$_POST['id']:'0';
	if(!$user_id)sys_msg('找不到该会员');
	$user_type = !empty($_POST['user_type'])?$_POST['user_type']:0;
	$com_rate = !empty($_POST['com_rate'])?$_POST['com_rate']:0;
	$admin_note = !empty($_POST['admin_note'])?$_POST['admin_note']:''; //备注
	$bbs_profile = !empty($_POST['bbs_profile'])?$_POST['bbs_profile']:'';
	$bbs_id = !empty($_POST['bbs_id'])?$_POST['bbs_id']:'';
	$introduction = !empty($_POST['introduction'])?$_POST['introduction']:'';//用户自我介绍
	$paypal_account = !empty($_POST['paypal_account'])?$_POST['paypal_account']:'';
	$user['user_type']=$user_type;
	$user['com_rate']=$com_rate;
	$user['bbs_profile']=$bbs_profile;
	$user['bbs_id']=$bbs_id;
	$user['introduction']=$introduction;
	$user['paypal_account']=$paypal_account;
	$user['admin_note']=$admin_note;
	$user_info = $db->selectInfo("select user_type,email from ".USERS." where user_id='$user_id'");
	if(!empty($user_info) && $user_info['user_type'] != 2 && $user_type == 2){ //如果之前是还没通过的，现在改为通过就发信通知客人
		require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');  //取得模版标题
		$sql = "insert into eload_wj_link(link_name,link_text,link_url,img,user_id) select link_name,link_text,link_url,img,$user_id from eload_wj_link,eload_users where eload_wj_link.user_id=eload_users.user_id and email='link@davismicro.com'";
		$db->query($sql);
		//用户信息
		$lang = $user_info['lang'];
		if(!empty($lang)) {
			$mail_subject = $mail_conf[$lang][30];
			$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $lang .'/30.html');
		}
		if(empty($mail_subject)) {
			$mail_subject = $mail_conf['en'][30];
			$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/en/30.html');
		}
		$mail_subject = str_replace('$site_name',$_SERVER['HTTP_HOST'],$mail_subject);
		$mail_body    = str_replace('{$site_name}',$_SERVER['HTTP_HOST'],$mail_body);
		$user['affiliates_pass_time'] = gmtime();
		exec_send($user_info['email'],$mail_subject,$mail_body);
		$note = "Affiliate program passed";
		add_point($pointArr['user_id'],10,2,$note);
	}
	if($db->autoExecute(USERS,$user,'UPDATE',"user_id=$user_id")){
		sys_msg('修改成功');
	}else {
		sys_msg('修改失败');
	}
	exit();

}
/*------------------------------------------------------ */
//-- 添加会员帐号
/*------------------------------------------------------ */


/*------------------------------------------------------ */
//-- 编辑用户帐号
/*------------------------------------------------------ */

elseif ($_ACT == 'edit')
{
    /* 检查权限 */
    admin_priv('affiliate_users_list');
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
        $user['avaid_point']    = $row['avaid_point'];
        $user['bbs_profile']    = $row['bbs_profile'];
         $user['admin_note']         = $row['admin_note'];

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
        $filter['sort_by']    = empty($_REQUEST['sort_by'])    ? 'affiliates_apply_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC'     : trim($_REQUEST['sort_order']);
        $ex_where = ' WHERE user_type>0 ';
        if ($filter['keywords'])
        {
            $ex_where .= " AND email LIKE '%" . mysql_like_quote($filter['keywords']) ."%'";
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
        $sql = "SELECT user_id,user_type,admin_note, firstname,lastname, email, is_validated,is_need_chknum, reg_time ,last_login ,visit_count,last_ip ,user_rank,affiliates_apply_time,affiliates_pass_time,admin_note ".
                " FROM " . USERS . $ex_where .
                " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

        $filter['keywords'] = stripslashes($filter['keywords']);
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
        $user_list[$i]['affiliates_apply_time'] = $user_list[$i]['affiliates_apply_time']?local_date($GLOBALS['_CFG']['time_format'], $user_list[$i]['affiliates_apply_time']):'';
         $user_list[$i]['affiliates_pass_time'] = $user_list[$i]['affiliates_pass_time']?local_date($GLOBALS['_CFG']['time_format'], $user_list[$i]['affiliates_pass_time']):'';
    	$user_list[$i]['email']		= email_disp_process($user_list[$i]['email']);
    }
    $arr = array('user_list' => $user_list, 'filter' => $filter,
        'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);
    return $arr;
}
$_ACT = $_ACT == 'msg'?'msg':'affiliate_users_'.$_ACT;
temp_disp();

?>