<?php
/**
 * 会员中心
*/
if (!defined('INI_WEB')){die('访问拒绝');}
require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');
require_once(ROOT_PATH . 'lib/modules/ipb.php');
require_once(ROOT_PATH . 'lib/class.page.php');
require_once(ROOT_PATH . 'lib/param.class.php');
require_once(ROOT_PATH . 'lib/class.rma.php');
require_once(ROOT_PATH . 'lib/class.function.php');
$user = new ipb($db);
/* 载入语言文件 */
include(ROOT_PATH . 'languages/' .$cur_lang. '/user.php');
global $cur_lang_url, $cur_lang, $default_lang;
$Arr['lang']      =  $_LANG;
$Arr['shop_name'] = $_CFG['shop_name'];
$user_id          = empty($_SESSION['user_id'])?'':$_SESSION['user_id'];
$affiliate        = unserialize($GLOBALS['_CFG']['affiliate']);
$callback         = isset($_GET['jsoncallback']) ? $_GET['jsoncallback'] : '';
$Arr['ArticleCatArr'] = get_foothelp_article();

// 不需要登录的操作或自己验证是否登录（如ajax处理）的act
$not_sign_arr = array('reset_password', 'sign','act_sign','join','win_point','a_join','edit_billing_address','act_orderip','act_edit_password','get_password','password', 'signin', 'add_tag', 'collect', 'return_to_cart', 'logout', 'email_list', 'validate_email', 'send_hash_mail', 'order_query', 'is_joined', 'check_email','queryorder','unsubmail','pointlist');

/* 显示页面的action列表 */
$ui_arr = array('join', 'sign', 'profile', 'order_list', 'order_detail', 'address_list', 'collection_list',
'message_list', 'tag_list', 'get_password', 'reset_password', 'booking_list', 'add_booking', 'account_raply',
'account_deposit', 'account_log', 'account_detail', 'act_account', 'pay', 'default', 'bonus', 'group_buy', 'group_buy_detail', 'affiliate', 'comment_list','validate_email','track_packages', 'transform_points','reserved_points');
/* 未登录处理 */

if($_ACT == 'win_point'){
	$code_number = empty($_GET['code'])?'':$_GET['code'];
	if($code_number !=''){
		check_is_sign(); //检查是否登录
		/*
		//检测是否为新用户
		$sql_user = "SELECT user_id, reg_time FROM ". USERS ." WHERE user_id = ". $_SESSION['user_id'] ." and reg_time > 1382284800 LIMIT 1";
		$res_user = $db->selectInfo($sql_user);
		if(empty($res_user)){
			$url_contect[] = "try another coupon";
			$url_link[]    = DOMAIN_USER."/". $cur_lang_url ."m-users-a-win_point.htm";
			show_message('This coupon is only for new customer', $url_contect,$url_link,'warning');
		}
		*/
		$sql   = "select * from ".POINT_COUPON." where code_number='$code_number'";
		$promo = $db->selectInfo($sql);
		if(empty($promo)){ //不存在
			$url_contect[] = "try another coupon";
			$url_link[]    = DOMAIN_USER."/". $cur_lang_url ."m-users-a-win_point.htm";
			show_message($_LANG['this_coupon_does_not_exist'], $url_contect,$url_link,'warning');
		}
		if($promo['deadline'] < gmtime()){  //已过期
			$url_contect[] = "try the other coupon";
			$url_link[]    = DOMAIN_USER."/". $cur_lang_url ."m-users-a-win_point.htm";
			show_message($_LANG['this_coupon_has_expired'],$url_contect,$url_link,'warning');
		}
		$ip           = real_ip();
		$cookie_users = !empty($_COOKIE['e_users'])&&!is_integer($_COOKIE['e_users'])?intval($_COOKIE['e_users']):0;
		$sql="select count(*) from ".POINT_COUPON_RECORD." where pid='".$promo['pid']."' and (user_id=".$_SESSION['user_id']." or ips='$ip' or user_id in($cookie_users))" ;
		$used_count = $db->getOne($sql);
		if($used_count>0){//已使用过
			$url_contect[] = "Try another coupon";
			$url_link[]    = DOMAIN_USER."/". $cur_lang_url ."m-users-a-win_point.htm";
			$url_contect[] = "Check my points";
			$url_link[]    = DOMAIN_USER."/". $cur_lang_url ."m-users-a-points_record.htm";
			show_message('You have already used this coupon!',$url_contect,$url_link,'warning');
		}
		$note = "Get DM Points from coupon code [".$code_number."]";
		add_point($_SESSION['user_id'],intval($promo['points']),2,$note);
		$A['pid'] = $promo['pid'];
		$A['user_id'] = $_SESSION['user_id'];
		$A['ips'] = $ip;
		$A['points'] = intval($promo['points']);
		$A['adddate'] = gmtime();

		$db->autoExecute(POINT_COUPON_RECORD,$A);
		$db->query('update '.POINT_COUPON.' set use_count=use_count+1 where pid ='.$promo['pid']);
		setcookie('e_users',$_SESSION['user_id'],time()+60*60*24*180);
		$url_contect[] = "Try another coupon";
		$url_link[] = DOMAIN_USER."/". $cur_lang_url ."m-users-a-win_point.htm";
		$url_contect[] = "Check my points";
		$url_link[] = DOMAIN_USER."/". $cur_lang_url ."m-users-a-points_record.htm";
		show_message('Congratulation!',$url_contect,$url_link,'success');
	}
}

if (empty($_SESSION['user_id']))
{
    if (!in_array($_ACT, $not_sign_arr))
    {
        if (in_array($_ACT, $ui_arr))
        {
            $_ACT = 'sign';
        }
        else
        {
            //未登录提交数据。非正常途径提交数据！
			header("Location: ".DOMAIN_USER."/$cur_lang_url"."m-users-a-sign.htm\n");
			exit;

        }
    }
} else {
	//$Arr['avatar'] = $db->getOne('select avatar from '.USERS.' where user_id= "'.$_SESSION['user_id'].'"');
    $uinfo = $db->getAll('select avatar,firstname,user_type from '.USERS.' where user_id= "'.$_SESSION['user_id'].'"');
    $Arr['avatar'] = empty($uinfo[0]['avatar'])?'':(strpos(strtolower($uinfo[0]['avatar']), 'http') !== false ? $uinfo[0]['avatar'] : DOMAIN_IMG.'/'.$uinfo[0]['avatar']);
    $Arr['nickname'] = @$uinfo[0]['firstname'];
	$Arr['is_affiliate'] = @$uinfo[0]['user_type'];
    $Arr['avatar_upload_url'] = 'http://photo.dealsmachine.com/avatar.action';
    $Arr['review_pic_upload_url'] = "http://photo.dealsmachine.com/upload4more.action?avatar={$Arr['avatar']}&nickname={$Arr['nickname']}";
}


//用户中心欢迎页
if ($_ACT == 'index')
{
	if ($_SESSION['firstname'] == '' || $_SESSION['lastname'] == '') {
		header("Location: ".DOMAIN_USER."/$cur_lang_url"."m-users-a-profile.htm\n");
		exit();
	}

	$user_id = $_SESSION['user_id'];
    //成交订单
    $Arr['order_amount']   = $db->GetOne('SELECT count(order_id) FROM ' .ORDERINFO .  " WHERE  order_status BETWEEN 1 and 8  AND user_id = '" . $_SESSION['user_id'] . "' ");
    $Arr['order_amount']   =  (empty($Arr['order_amount']))?0:$Arr['order_amount'];
    //成交金额
    $Arr['amount']   = $db->GetOne('SELECT sum(order_amount)'.' FROM ' .ORDERINFO .  " WHERE  order_status BETWEEN 1 and 8 AND user_id = '" . $_SESSION['user_id'] . "' ");
    $Arr['amount']   =  (empty($Arr['amount']))?0:$Arr['amount'];
    if($Arr['amount']<100){
        $Arr['need_amout'] = 100 - $Arr['amount'];
        $Arr['before']     = 'Pre vip';
    }elseif($Arr['amount']<500){
        $Arr['need_amout'] = 500 - $Arr['amount'];
        $Arr['before']     = 'Vip';
    }elseif($Arr['amount'] < 2000){
        $Arr['need_amout'] = 2000 - $Arr['amount'];
        $Arr['before']     = 'Vip Silver';
    }elseif($Arr['amount']<10000){
        $Arr['need_amout'] = 10000 - $Arr['amount'];
        $Arr['before']     = 'Vip Gold';
    }elseif($Arr['amount']<50000){
        $Arr['need_amout'] = 50000 - $Arr['amount'];
        $Arr['before']     = 'Vip Diamond';
    }
    //总订单数
    $Arr['total_order_amount']   = $db->GetOne('SELECT count(order_sn)'.' FROM ' .ORDERINFO .  " WHERE user_id = '" . $_SESSION['user_id'] . "' ");
    $Arr['total_order_amount']   =  (empty($Arr['total_order_amount']))?0:$Arr['total_order_amount'];
    //用户信息
    include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
    $Arr['profile'] = get_profile($user_id);

    //未使用的代金券
    $sql = "select count(*) from eload_promotion_code where users = '".$_SESSION['email']."' and cishu = 0 and is_applay = 1";
    $Arr['coupon'] = $db->getOne($sql);

	//用户是否为新用户.如果用户己下单并己付款为则为老用户,否则为新用户.
	$order_count = $db->getOne("SELECT COUNT(eo.user_id) AS count FROM ". USERS ." eu LEFT JOIN ". ORDERINFO ." eo ON eu.user_id = eo.user_id WHERE eu.user_id = '" . $_SESSION['user_id'] . "' AND eo.order_status BETWEEN 1 and 8");
	//新用户,生成促销码
	if($order_count == 0) {
		$sql = "SELECT code, users, type, create_time, exp_time FROM ". PCODE ." WHERE users = '". $_SESSION['email'] ."' AND type = 1 LIMIT 1";
		$res = $db->selectInfo($sql);
		if(!empty($res)) {
			$sql_user = "SELECT user_id, reg_time FROM ". USERS ." WHERE email = '". $_SESSION['email'] ."' LIMIT 1";
			$res_user = $db->selectInfo($sql_user);
			if(!empty($res_user)) {
				$user_reg_time = $res_user['reg_time'];
				if($res['create_time'] > $res_user['reg_time']) {
					$Arr['pcode']['code'] = '';
				} else {
					$Arr['promotion_code'] = $res['code'];
					$Arr['promotion_exp_time'] = date('Y-m-d',$res['exp_time']);
				}
			}
		} else {
			$coupon_code = randstr(8);
			$pcodeArr['code']        = $coupon_code;
			$pcodeArr['users']       = $_SESSION['email'];
			$pcodeArr['exp_time']    = time()+3600*24*7;
			$pcodeArr['youhuilv']    = 10;  //优惠10%
			$pcodeArr['goods']       = ''; //针对使用产品，空为不限制
			$pcodeArr['fangshi']     = 1;  //1百分比，2直减
			$pcodeArr['times']       = 1;  //使用次数
			$pcodeArr['is_applay']   = 0;  //0促销码，1代金券
			$pcodeArr['cat_id']      = 0;  //针对使用分类，空为不限制
			$pcodeArr['create_time'] = gmtime();  //开始日期
			$pcodeArr['type']        = 1;  //类别
			$end_date = date("Y-m-d H:i:s", $pcodeArr['exp_time']);
			$db->autoExecute(PCODE, $pcodeArr);
			$Arr['promotion_code'] = $coupon_code;
			$Arr['promotion_exp_time'] = date('Y-m-d',gmtime());
		}
		$user_type = 1;
	} else {
		//老用户不生成促销码
		$Arr['promotion_code'] = '';
		$user_type = 2;
	}
	$pages = isset($_GET['page'])?intval($_GET['page']):1;
	$size = 8;
	//已经购买还没有写评论的产品
	$data = get_no_review_goods($user_id,$pages,$size);
	$Arr['no_review_goods'] = isset($data['list']) ? $data['list'] : array();
	$Arr['goods_count'] = $data['count'];
	$page=new page(array('total' => $data['count'],'perpage'=>$size));
	$Arr["pagestr"]  = $page->show(5);
    $Arr['recommend_product'] = user_recommend_product(8);   //用户推荐产品
    $Arr['user_type'] = $user_type;
    $nav_title = ' &raquo; ' . $_LANG['my_account'];
    $Arr['seo_title'] = $_CFG['shop_name'];
    $Arr['seo_title'] = ' My Account -  My information  - '.$_CFG['shop_name'];
    $Arr['nav_title']  = $nav_title;
    $Arr['action'] = 'index';
    $_ACT = 'users_inc';
}

//显示会员注册界面
if ($_ACT == 'join')
{
    $Arr['act'] = $_ACT;
	$Arr['seo_title'] = $_LANG_SEO['login']['title'];
	$Arr['seo_keywords']   = $_LANG_SEO['login']['keywords'];
	$Arr['seo_description']   = $_LANG_SEO['login']['description'];
}

//上传头像界面
elseif($_ACT=='upload_pic'){
    $_ACT = 'upload_pic';
    $Arr['action'] = 'upload_avatar';
}

//修改用户头像
elseif ($_ACT == 'update_avatar') {
    $avatar = empty($_POST['avatar'])?'':trim($_POST['avatar']);
    $result = FALSE;
    if($avatar && !empty($_SESSION['user_id'])){
		//第一次添加头像送10积分
		$old = $db->getOne("select avatar from ".USERS." where user_id = '". $_SESSION['user_id'] ."'");
		if(empty($old)) {
			$note = $_LANG['10_dm_points_time'];
			add_point($user_id,10,2,$note);
		}
        $sql = "UPDATE ".USERS." SET avatar='{$avatar}' WHERE user_id=".$_SESSION['user_id'];
        $db->query($sql);
        $result = TRUE;
    }
    echo json_encode(array('status'=>$result));exit;
}
//修改邮箱地址
elseif ($_ACT == 'edit_email') {
    $result        = array('success' => 1);
    $email         = Param::post('email', 'string', true, false);//邮箱地址
    $confirm_code  = Param::post('confirm_code', 'string', true, false);//验证码
    $email_is_same = strtolower($email) == strtolower($_SESSION['email']);
    $firstname     = str_replace('&nbsp;', '', $_SESSION['firstname']);
    if(empty($firstname)) {
        $firstname = $_LANG['my_friend'];
    }
    if (!empty($email)) {
        if (!$email_is_same && $user->check_email($email)) {
            $result = array('success' => 0, 'error' => "<strong>{$email}</strong> is already in used");
        }
        elseif (!$confirm_code) {//验证码为空，即需要发送验证码邮件
            $confirm_code  = md5(rand_string());
            $sql           = 'SELECT template_subject,template_content FROM ' . Mtemplates . ' WHERE template_id=' . (IS_LOCAL ? 44 : 44);
            $mail_temp_arr = $db->selectinfo($sql);
            $mail_subject  = $mail_temp_arr['template_subject'];
            $mail_temp     = varResume($mail_temp_arr['template_content']);
			//多语言邮件模板 fangxin 2013/07/18
			if($cur_lang != $default_lang) {
				$template_id     = $mail_temp_arr['template_id'];
				$sql = 'SELECT m.*' .
					   ' FROM ' . Mtemplates . '_' . $cur_lang .' AS m' .
					   " WHERE m.template_id = '". (IS_LOCAL ? 44 : 44) ."'";
				if($row_mail = $GLOBALS['db']->selectinfo($sql)) {
					$mail_subject = $row_mail['template_subject'];
					$mail_temp    = varResume($row_mail['template_content']);
				}
			}
            $mail_temp     = str_replace(array('{$email}', '@confirm_code'), array($_SESSION['email'], $confirm_code), $mail_temp);
            if((!$email_is_same || $db->count_info(USERS, 'user_id', "user_id={$user_id} AND is_validated=0")) && exec_send2($email, $mail_subject, $mail_temp)) {//发送验证码成功
                if (!$db->getOne('SELECT user_id FROM ' . USERS_INFO . " WHERE user_id={$_SESSION['user_id']}")) {//用户信息
            	    $db->autoExecute(USERS_INFO, array('user_id' => $_SESSION['user_id'], 'confirm_code' => $confirm_code));
            	}
            	else {
            	    $db->autoExecute(USERS_INFO, array('confirm_code' => $confirm_code), 'update', 'user_id=' . $_SESSION['user_id']);
            	}

                $result['confirm_code'] = 1;
            }
            elseif (!$email_is_same) {//发送失败
                $result = array('success' => 0, 'error' => 'Edit Failed! Please try again!');
            }
        }
        else {
            if ($db->getOne('SELECT user_id FROM ' . USERS_INFO . " WHERE user_id={$_SESSION['user_id']} AND confirm_code='{$confirm_code}'")) {//验证码正确
                //修改邮箱，发送邮件ID: 50
                if($email <> $_SESSION['email']) {
                    $sql           = 'SELECT template_subject,template_content FROM ' . Mtemplates . ' WHERE template_id=' . (IS_LOCAL ? 50 : 50);
                    $mail_temp_arr = $db->selectinfo($sql);
                    $mail_subject  = $mail_temp_arr['template_subject'];
                    $mail_temp     = varResume($mail_temp_arr['template_content']);
                    if($cur_lang != $default_lang) {
                        $template_id     = $mail_temp_arr['template_id'];
                        $sql = 'SELECT m.*' .
                               ' FROM ' . Mtemplates . '_' . $cur_lang .' AS m' .
                               " WHERE m.template_id = '". (IS_LOCAL ? 50 : 50) ."'";
                        if($row_mail = $GLOBALS['db']->selectinfo($sql)) {
                            $mail_subject = $row_mail['template_subject'];
                            $mail_temp    = varResume($row_mail['template_content']);
                        }
                    }
                    $mail_temp     = str_replace(array('{$firstname}', '{$email}', '@old_email', '@new_email'), array($firstname, $_SESSION['email'], $_SESSION['email'], $email), $mail_temp);
                    if($_SESSION['email'] && $mail_subject && $mail_temp) {
                        exec_send2($_SESSION['email'], $mail_subject, $mail_temp);
                    }
                }

        	    if ($db->count_info(USERS, 'user_id', "user_id={$user_id} AND is_validated=0")) {
        	        add_point($user_id, 10, 2, '10 points for verifying registered email');
        	    }

        	    $db->update(USERS, "is_validated=1,email='{$email}'", 'user_id=' . $_SESSION['user_id']);//修改用户表
        	    $db->update(CART, "session_id='{$email}'", "session_id='{$_SESSION['email']}'");//修改购物车
        	    $db->query('UPDATE ' . PCODE . " SET users=REPLACE(users, '{$_SESSION['email']}', '{$email}') WHERE FIND_IN_SET('{$_SESSION['email']}', users)");//修改促销码
        	    //Func::crontab_log('fun.user.php', "{$_SESSION['email']}({$_SESSION['user_id']}) => {$email}, afftected " . PCODE .  ': ' . $db->affectedRows() . PHP_EOL);
        	    $_SESSION['email'] = $email;
        	}
        	else {//验证码错误
        	    $result = array('success' => 0, 'confirm_code' => 1, 'error' => "The verification code doesn't match");
        	}
        }
    }
    exit(json_encode($result));
}

//从邮件点击验证链接
elseif ($_ACT == 'confirm_from_email') {
    $user_id = Param::get('id', 'int'); //用户id
    $key     = Param::get('key', 'string', true, false);//验证码
    $back    = Param::get('back', 'string', false, true);//返回
    if ($back == 'cj') {//抽奖
        if ($db->getOne('SELECT user_id FROM ' . USERS_INFO . " WHERE user_id={$user_id} AND confirm_code='{$key}'")) { //验证码正确
            if ($db->count_info(USERS, 'user_id', "user_id={$user_id} AND is_validated=0")) {
                $db->update(USERS, 'is_validated=1', 'user_id=' . $user_id); //修改用户表
                add_point($user_id, 10, 2, '10 points for verifying registered email');
    	    }
            header('Location: '.DOMAIN.'/'. $cur_lang_url .'lucky.htm');
        }
        else {
            $url_contect[] = 'Return to home';
            $url_link[] = DOMAIN.'/';
            show_message('Confirm failed', $url_contect, $url_link, 'warning');
        }
    }
    exit();
}

/* 注册会员的处理 */
elseif ($_ACT == 'a_join')
{
	include_once('lib/lib_passport.php');
	$email    = isset($_POST['email1']) ? trim($_POST['email1']) : '';
	$password = isset($_POST['password']) ? trim($_POST['password']) : '';
	$ver      = !empty($_POST['verifcode'])?$_POST['verifcode']:'';
	$newsletter_register = empty($_POST['newsletter_register']) ? 0 : intval($_POST['newsletter_register']);
	if (strlen($email) < 5)
	{
		echo 'Error, email enter at least 5 characters.';
		exit();
	}
	if (strlen($email) > 60)
	{
		echo 'Error, email enter at most 50 characters.';
		exit();
	}

	if (strlen($password) < 5)
	{
		echo 'Error, password enter at least 5 characters.';
		exit();
	}
	if (strlen($password) > 40)
	{
		echo 'Error, password enter at most 50 characters.';
		exit();
	}
	if (register($email,$password) !== false)
	{
		$_SESSION['jointimes'] = time();
		ChangeSessId();
		update_user_info();
		$result = send_email($email, 13);
		$sql = "select user_id from ".USERS." where email='$email'";
		$user_id = $db->getOne($sql);
		$note = $_LANG['register_successfully'];
		if($user_id) add_point($user_id,10,2,$note);
		//邮件订阅
		if(!empty($newsletter_register))
		{
			$ck = $db->selectinfo("SELECT * FROM " . Email_list . " WHERE email = '$email'");
			if (empty($ck))
			{
				$firstname = '';
				$hash = substr(md5(time()), 1, 10);
				$Arr['hash'] = $hash;
				$Arr['email'] = $email;
				$now = gmtime();
				$sql = "INSERT INTO " . Email_list . " (email,stat, hash,firstname,source,addTime,isReg) VALUES ('$email', 0, '$hash','$firstname','4',$now,0)";
				$db->query($sql);
				//订阅成功邮件
				require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');  //取得模版标题
				$email_temp_id = 53;
				foreach( $Arr as $key => $value ){
					$Tpl->assign( $key, $value );
				}
				$mail_subject = $mail_conf[$cur_lang][$email_temp_id];
				$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $cur_lang . '/' . $email_temp_id.'.html');
				exec_send2($email,$mail_subject,$mail_body);
			}
		}
		update_user_lang($email, $cur_lang); // 更新用户语言 fangxin 2013/07/23
		echo 'ok';
		exit();
	 }
		exit();
}

/* 验证用户邮箱地址是否被注册 */
elseif($_ACT == 'check_email')
{
    $email = trim($_REQUEST['email1']);
    if ($user->check_email($email))
    {
        echo 'false';
    }else{
        echo 'true';
    }
	exit();
}

/* 用户登录界面 */
elseif ($_ACT == 'sign')
{
    if (empty($back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
        $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
    } else {
        $back_act = ''. $cur_lang_url .'m-users.htm';
    }
	$Arr['seo_title'] = $_LANG_SEO['sign']['title'];
	$Arr['seo_keywords']   = $_LANG_SEO['sign']['keywords'];
	$Arr['seo_description']   = $_LANG_SEO['sign']['description'];
}

/* 处理会员的登录 */
elseif ($_ACT == 'act_sign')
{
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    if ($user->login($email, $password))
    {
		ChangeSessId();
		update_user_info();
		//登陆日志
		$sql = "INSERT INTO eload_user_login_log (user_id,login_time) VALUES ('".$_SESSION['user_id']."','".gmtime()."')";
		$db->query($sql);
		update_user_lang($email, $cur_lang); // 更新用户语言 fangxin 2013/07/23
        echo 'Successfully sign';
		exit();
    }else{
       // $_SESSION['sign_fail'] ++ ;
        echo $_LANG['signin_failed'];
		exit();
    }
}

//登出
elseif ($_ACT == 'logout')
{
	$back_act = empty($GLOBALS['_SERVER']['HTTP_REFERER']) ? '/' : $GLOBALS['_SERVER']['HTTP_REFERER'];
    $user->logout();
    show_message($_LANG['You_have_successfully_quit'] , array($_LANG['Return_to_the_previous_page'],$_LANG['Return_to_Home']), array($back_act, '/'),'success');
}

//用户资料页面
elseif ($_ACT == 'profile')
{
    include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
    $Arr['profile'] = get_profile($user_id);
	$Arr['action'] = 'profile';
    $_ACT = 'users_inc';
    $nav_title = ' &raquo;  '.$_LANG['wj_profile'];
    $Arr['seo_title'] = $_LANG['my_account'] .' -  '.$_LANG['wj_profile'].'  - '.$_CFG['shop_name'];
	$Arr['nav_title']  = $nav_title;
}

//修改个人资料的处理
elseif ($_ACT == 'edit_profile')
{
    include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
    $msn = trim($_GET['other']['msn']);
    $firstname = trim($_GET['firstname']);
    $lastname = trim($_GET['lastname']);
    $mobile_phone = trim($_GET['other']['phone']);
    if ($lastname =='' || $firstname==''){
         show_message($_LANG['passport_js']['lastname_and_firstname']);
	}
    if (!empty($msn) && !is_email($msn))
    {
         show_message($_LANG['passport_js']['msn_invalid']);
    }
    if (!empty($mobile_phone) && (!preg_match( '/^[\d|\_|\-|\s]+$/', $mobile_phone )))
    {
        show_message($_LANG['passport_js']['mobile_phone_invalid']);
    }
    $profile  = array(
		'user_id'  => $user_id,
		'firstname' => htmlspecialchars($firstname),
		'lastname' =>  htmlspecialchars($lastname),
		'sex'      => isset($_GET['sex'])   ? intval($_GET['sex']) : 0,
		'other'=>isset($_GET['other']) ? $_GET['other'] : array()
        );
    if (edit_profile($profile))
    {
		$_SESSION['firstname']  = $profile['firstname'];
		$_SESSION['lastname']  = $profile['lastname'];
        show_message($_LANG['edit_profile_success'], $_LANG['profile_lnk'], ''. $cur_lang_url .'m-users.htm', 'success');
    }
    else
    {
		$msg = $_LANG['edit_profile_failed'];
        show_message($msg, '', '', 'warning');
    }
    $nav_title = ' &raquo;  '.$_LANG['profile'];
    $Arr['seo_title'] = $_LANG['my_account'] .' -  '.$_LANG['profile'].'  - '.$_CFG['shop_name'];
	$Arr['nav_title']  = $nav_title;
}

/* 密码找回-->修改密码界面 */
elseif ($_ACT == 'get_password')
{
    include_once(ROOT_PATH . 'lib/lib_passport.php');
    if (isset($_GET['code']) && isset($_GET['uid'])) //从邮件处获得的act
    {
        $code = trim($_GET['code']);
        $uid  = intval($_GET['uid']);
        /* 判断链接的合法性 */
        $user_info = $user->get_profile_by_id($uid);
        if (empty($user_info) || ($user_info && md5($user_info['user_id'] . $_CFG['hash_code'] . $user_info['reg_time']) != $code))
        {
            show_message($_LANG['parm_error'], $_LANG['back_home_lnk'], './', 'info');
        }
        $smarty->assign('uid',    $uid);
        $smarty->assign('code',   $code);
        $smarty->assign('action', 'reset_password');
        $smarty->display('user_passport.dwt');
    }
    else
    {
        //显示用户名和email表单
        $smarty->display('user_passport.dwt');
    }
}

/* 修改会员密码 */
elseif ($_ACT == 'edit_password')
{
    include_once(ROOT_PATH . 'lib/lib_passport.php');

    $old_password = isset($_POST['old_password']) ? trim($_POST['old_password']) : '';
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $comfirm_password = isset($_POST['comfirm_password']) ? trim($_POST['comfirm_password']) : '';

    if (strlen($new_password) < 6)
    {
        show_message($_LANG['passport_js']['password_shorter']);
    }
    if ($new_password != $comfirm_password)
    {
        show_message($_LANG['passport_js']['confirm_password_invalid']);
    }

    if (($_SESSION['user_id']>0 && $_SESSION['user_id'] == $user_id && $user->check_user($_SESSION['email'], $old_password)))
    {
        if ($user->edit_user(array('email'=> $_SESSION['email'] , 'old_password'=>$old_password, 'password'=>$new_password)))
        {
            $user->logout();
            show_message($_LANG['edit_password_success'], $_LANG['resign_lnk'], ''. $cur_lang_url .'m-users-a-sign.htm', 'success');
        }
        else
        {
            show_message($_LANG['edit_password_failure'], $_LANG['back_page_up'], '', 'warning');
        }
    }
    else
    {
        show_message($_LANG['edit_password_failure'], $_LANG['back_page_up'], '', 'warning');
    }

}


/* 查看订单列表 */
elseif ($_ACT == 'order_list')
{
    include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
    $record_count = $db->getOne("SELECT COUNT(*) FROM " .ORDERINFO. " WHERE user_id = '$user_id'");
    $size = 15;
	$_GET['page'] = empty($_GET['page'])?1:intval($_GET['page']);
	$page_count = ceil($record_count/$size);
	if ($_GET['page'] > $page_count ) $_GET['page'] = $page_count;
	if ($_GET['page'] < 1 ) $_GET['page'] = 1;
	$start = ($_GET['page'] - 1) * $size;
	$_GET['x'] = '2';
	$page=new page(array('total' => $record_count,'perpage'=>$size));
	$Arr["pagestr"]  = $page->show(5);
    $orders = get_user_orders($user_id,$size,$start);
    $Arr['orders'] = $orders;
	$Arr['action'] = $_ACT;
    $_ACT = 'users_inc';
    $nav_title = ' &raquo;  '.$_LANG['label_order'];
    $Arr['seo_title'] = $_LANG['my_account'] . ' -  '.$_LANG['label_order'].'  - '.$_CFG['shop_name'];
	$Arr['nav_title']  = $nav_title;
	$Arr['seo_title'] = $_LANG_SEO['order_history']['title'];
	$Arr['seo_keywords']   = $_LANG_SEO['order_history']['keywords'];
	$Arr['seo_description']   = $_LANG_SEO['order_history']['description'];
}

/* 查看订单详情 */
elseif ($_ACT == 'order_detail')
{
    include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
	require_once(ROOT_PATH . 'languages/' .$cur_lang. '/shopping_flow.php');
    include_once(ROOT_PATH . 'lib/lib.f.order.php');
    include_once(ROOT_PATH . 'lib/lib_clips.php');

    $order_id = $_GET['order_id'];
    //搜索订单
    if(strlen($order_id)>10)
    {
       $sql="SELECT order_id FROM ".ORDERINFO." WHERE order_sn='$order_id'";
       $order_id=$db->getOne($sql);
    }
    $order_id = !empty($order_id) ? intval($order_id) : 0;
    $zhuangtai = isset($_POST['status']) ? intval($_POST['status']) : '';

	if ($zhuangtai != '' ){
		$sql = " order_status = '".$zhuangtai."' ";  //收货状态
		if ($zhuangtai == 1){//付款状态
			$sql .= " , pay_time ='".gmtime()."' ";
		}
		$db->update(ORDERINFO,$sql," order_id = '".$order_id."'");
	}


    /* 订单详情 */
    $order = get_order_detail($order_id, $user_id);
    if($order ==1){
        show_message($_LANG['orders_you_are_looking'], array($_LANG['my_account'], $_LANG['go_to_shopping']), array('/'. $cur_lang_url .'m-users.htm', './'), 'info');
    }elseif($order == 2){
        show_message($_LANG['you_are_not_allowed'],array($_LANG['my_account'], $_LANG['go_to_shopping']), array('/'. $cur_lang_url .'m-users.htm', './'), 'info');
    }
    $area_Arr = read_static_cache('area_key',2);
    $order['country'] = empty($area_Arr[$order['country']])?'':$area_Arr[$order['country']]['region_name'];

    if ($order === false)
    {
        $err->show($_LANG['back_home_lnk'], './');
        exit;
    }

    /* 订单商品 */
    $goods_list = order_goods($order_id);
	//汇率
	$order_currency = $order['order_currency'];
    $exchange_rate_arr  = read_static_cache('exchange',2);
	$exchange_rate = $exchange_rate_arr['Rate'][$order_currency];

    foreach ($goods_list AS $key => $value)
    {
		$urlfile = get_details_link($value['goods_id']);
		if($exchange_rate) {
			$market_price = $value['market_price'] * $exchange_rate;
			$goods_price  = $value['goods_price'] * $exchange_rate;
			$subtotal     = $value['subtotal'] * $exchange_rate;
		} else {
			$market_price = $value['market_price'];
			$goods_price  = $value['goods_price'];
			$subtotal     = $value['subtotal'];
		}
        $goods_list[$key]['market_price'] = price_format($market_price, false);
        $goods_list[$key]['goods_price']  = price_format($goods_price, false);
        $goods_list[$key]['subtotal']     = price_format($subtotal, false);
    }

     /* 设置能否修改使用余额数 */
    if ($order['order_amount'] > 0)
    {
        if ($order['order_status'] == 0)
        {
            $user = user_info($order['user_id']);
        }
    }

    /* 未发货，未付款时允许更换支付方式 */
    if ($order['order_amount'] > 0 && $order['order_status'] == 0)
    {
        $payment_list = available_payment_list(false, 0, true);
        $payment_list = $payment_list[$order['pay_id']];
        $Arr['payment_list'] = $payment_list;
    }

    $jump = empty($_GET['jump']) ? '' : trim($_GET['jump']);	////邮件邀请付款标识参数
    //把订单标示成邮件邀请付款
    if(!empty($jump) && $order['email_pay_invite'] == 0)
    {
    	$db->update(ORDERINFO,' email_pay_invite = 1 '," order_id = '".$order_id."'");
    }

    $Arr['order'] =  $order;
    $Arr['goods_list'] = $goods_list;

	$Arr['action'] = $_ACT;
    $_ACT = 'users_inc';
    $nav_title = ' &raquo;  '.$_LANG['label_order'];
    $Arr['seo_title'] = $_LANG['my_account'] . ' -  '.$_LANG['label_order'].'  - '.$_CFG['shop_name'];
    $Arr['nav_title']  = $nav_title;
}
/* 查询订单详情 */
elseif ($_ACT == 'queryorder')
{
    include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
    include_once(ROOT_PATH . 'lib/lib.f.order.php');
    include_once(ROOT_PATH . 'lib/lib_clips.php');
    $order_sn = (isset($_GET['n']) && (strlen(trim($_GET['n']))>15 || strlen(trim($_GET['n']))<22)) ? htmlspecialchars(trim($_GET['n'])) : 0;
	$order_sn = trim(urldecode($order_sn));
    $sql = "select order_id from ".ORDERINFO." where order_sn = '$order_sn' ";
    $order_id = $db->getOne($sql);
    if ($order_id === false){
		echo 'No record!';
        exit;    }
    /* 订单详情 */
    $order = get_order_detail($order_id, 0);
    if(empty($_GET['type'])) {
        $order['formated_shipping_fee'] = $order['formated_order_amount'] - $order['yuan_goods_amount'] - $order['Need_Traking_number'] - $order['formated_insure_fee'];
        $order['formated_shipping_fee'] = $order['formated_shipping_fee']*100<0 ? 0 : $order['formated_shipping_fee'];
        $order['formated_goods_amount'] = $order['yuan_goods_amount'];
        $order['formated_order_amount'] = $order['yuan_goods_amount'] + $order['formated_shipping_fee'] + $order['Need_Traking_number'] + $order['formated_insure_fee'];
    }
    $Arr['type'] = isset($_GET['type']) && !empty($_GET['type']) ? 1 : 0;
    if($order == 1){
        show_message($_LANG['orders_you_are_looking'], array($_LANG['my_account'], $_LANG['go_to_shopping']), array('/'. $cur_lang_url .''. $cur_lang_url .'m-users.htm', './'), 'info');
    }elseif($order == 2){
        show_message($_LANG['you_are_not_allowed'], array($_LANG['my_account'], $_LANG['go_to_shopping']), array('/'. $cur_lang_url .'m-users.htm', './'), 'info');
    }

    $area_Arr = read_static_cache('area_key',2);
    $order['country'] = $area_Arr[$order['country']]['region_name'];


    /* 订单商品 */
    $goods_list = order_goods($order_id);
    foreach ($goods_list AS $key => $value)
    {
	$urlfile = get_details_link($value['goods_id']);
        $goods_list[$key]['market_price'] = price_format($value['market_price'], false);
        $goods_list[$key]['goods_price']  = price_format($value['goods_price'], false);
        $goods_list[$key]['subtotal']     = price_format($value['subtotal'], false);
    }

    if ($order['order_amount'] > 0 && $order['order_status'] == 0)
    {
        $payment_list = available_payment_list(false, 0, true);
        $payment_list = $payment_list[$order['pay_id']];
        $Arr['payment_list'] = $payment_list;
    }
    $Arr['order'] =  $order;
    $Arr['goods_list'] = $goods_list;
}




/* 帐单地址列表界面*/
elseif ($_ACT == 'billing_address_list')
{
    include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
    include_once(ROOT_PATH . 'languages/' .$cur_lang. '/shopping_flow.php');

    $Arr['country_list'] = area_list();
	 //根据国家选取州
    $area = read_static_cache('area_key',2);
    foreach($area as $key=>$row) {
        $areas[$key]['state'] = $row['state'];
        $areas[$key]['region_name'] = $row['region_name'];
        $areas[$key]['region_code'] = $row['region_code'];
        $areas[$key]['code'] = $row['code'];
    }
    $Arr['country_json'] = str_replace("'",'`',json_encode($areas));
    /* 获得用户所有的收货人信息 */
    $bill_addr_info  = $db->selectinfo("SELECT * FROM eload_user_billing_address WHERE user_id='".$_SESSION['user_id']."'");
    if(!empty($bill_addr_info)){
        if(!empty($area[$bill_addr_info['country']]['state']))$bill_addr_info['states'] = $area[$bill_addr_info['country']]['state'];
        if(!empty($bill_addr_info['states'])&&!in_array($bill_addr_info['province'],$bill_addr_info['states'])){
              $bill_addr_info['province_not_in'] = 1;
        }
    }
    $Arr['bill_addr_info'] = $bill_addr_info;



        //赋值于模板
    $Arr['real_goods_count'] = 1;
    $Arr['shop_country'] =     $_CFG['shop_country'];
    //$Arr['address'] =          $address_id;
    $Arr['currency_format'] =  $_CFG['currency_format'];
	$Arr['action'] = $_ACT;
    $_ACT = 'users_inc';
    $nav_title = ' &raquo;  '.$_LANG['billing_addr_info'];
    $Arr['seo_title'] = $_LANG['my_account'] . ' -  '.$_LANG['billing_addr_info'].'  - '.$_CFG['shop_name'];
	$Arr['nav_title']  = $nav_title;
}

/* 添加/编辑帐单地址的处理 */
elseif ($_ACT == 'edit_billing_address')
{
    include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
    include_once(ROOT_PATH . 'languages/' .$cur_lang. '/shopping_flow.php');
     if(strlen(trim($_POST['firstname']))>35 || strlen(trim($_POST['firstname']).trim($_POST['lastname']))>35 || strlen(trim($_POST['country']))>130 || strlen(trim($_POST['province']))>40 || strlen(trim($_POST['city']))>35 || strlen(trim($_POST['addressline1']))>35 || strlen(trim($_POST['addressline2']))>35 || strlen(trim($_POST['zipcode']))>10 || strlen(trim($_POST['tel']))>15  ) {

        show_message($_LANG['sorry_the_billing_address'], '', $_SERVER['HTTP_REFERER'], 'warning');

    }
    $address = array(
        'user_id'    => $user_id,
        'address_id' => intval($_POST['address_id']),
        'country'    => isset($_POST['country'])   ? trim($_POST['country'])  : '',
        'province'   => isset($_POST['province'])  ? trim($_POST['province']) : '',
        'city'       => isset($_POST['city'])      ? trim($_POST['city'])     : '',
        'addressline1'    => isset($_POST['addressline1'])   ? trim($_POST['addressline1'])    : '',
        'addressline2'    => isset($_POST['addressline2'])   ? trim($_POST['addressline2'])    : '',
        'firstname'  => isset($_POST['firstname']) ? trim($_POST['firstname'])  : '',
        'lastname'  => isset($_POST['lastname']) ? trim($_POST['lastname'])  : '',
        'tel'        => isset($_POST['tel'])       ? make_semiangle(trim($_POST['tel'])) : '',
        'zipcode'       => isset($_POST['zipcode'])       ? make_semiangle(trim($_POST['zipcode'])) : '',
		'code'          => empty($_POST['code'])       ? '' : $_POST['code'],
        );

    if (update_billing_address($address))
    {
        show_message($_LANG['edit_bill_address_success'], $_LANG['address_list_lnk'], ''. $cur_lang_url .'m-users-a-billing_address_list.htm', 'success');
    }
}

/* 收货地址列表界面*/
elseif ($_ACT == 'address_list')
{
    include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
    include_once(ROOT_PATH . 'languages/' .$cur_lang. '/shopping_flow.php');

    $Arr['country_list'] = area_list();
    /* 获得用户所有的收货人信息 */
    $consignee_list = get_consignee_list($_SESSION['user_id']);
    $area = read_static_cache('area_key',2);
        foreach($area as $key=>$row) {
            $areas[$key]['state'] = $row['state'];
            $areas[$key]['region_name'] = $row['region_name'];
            $areas[$key]['region_code'] = $row['region_code'];
            $areas[$key]['code'] = $row['code'];
        }
    $Arr['country_json'] = str_replace("'",'`',json_encode($areas));
    /* 获得用户所有的收货人信息 */
    $consignee_list = get_consignee_list($_SESSION['user_id']);
    foreach($consignee_list as $key=>$row){
        if(!empty($row['country'])){
            $consignee_list[$key]['states'] = $area[$row['country']]['state'];
            if(isset($row['province'])){
            	if(!empty($area[$row['country']]['state'])){
	                if(!in_array($row['province'],$area[$row['country']]['state'])){
	                    $consignee_list[$key]['province_not_in'] = 1;
	                }
            	}
            }
        }
    }
    if (count($consignee_list) < 5 && $_SESSION['user_id'] > 0)
    {
        /* 如果用户收货人信息的总数小于5 则增加一个新的收货人信息 */
      $consignee_list[] = array('country' => '213', 'email' => isset($_SESSION['email']) ? $_SESSION['email'] : '');
    }

    $Arr['consignee_list'] = $consignee_list;

    //取得国家列表，如果有收货人列表
    foreach ($consignee_list AS $region_id => $consignee)
    {
        $consignee['country']  = isset($consignee['country'])  ? intval($consignee['country'])  : 0;
    }

    /* 获取默认收货ID */
    $address_id  = $db->getOne("SELECT address_id FROM " . USERS. " WHERE user_id='$user_id'");

    //赋值于模板
    $Arr['real_goods_count'] = 1;
    $Arr['shop_country'] =     $_CFG['shop_country'];
    $Arr['address'] =          $address_id;
    $Arr['currency_format'] =  $_CFG['currency_format'];
    $Arr['action'] = $_ACT;
    $_ACT = 'users_inc';
    $nav_title = ' &raquo;  '.$_LANG['consignee_info'];
    $Arr['seo_title'] = $_LANG['my_account'] . ' -  '.$_LANG['consignee_info'].'  - '.$_CFG['shop_name'];
    $Arr['nav_title']  = $nav_title;
}

/* 添加/编辑收货地址的处理 */
elseif ($_ACT == 'edit_address')
{
    include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
    include_once(ROOT_PATH . 'languages/' .$cur_lang. '/shopping_flow.php');

    $address = array(
        'user_id'    => $user_id,
        'address_id' => intval($_POST['address_id']),
        'country'    => isset($_POST['country'])   ? intval($_POST['country'])  : 0,
        'province'   => isset($_POST['province'])  ? trim($_POST['province']) : '',
        'city'       => isset($_POST['city'])      ? trim($_POST['city'])     : '',
        'addressline1'    => isset($_POST['addressline1'])   ? trim($_POST['addressline1'])    : '',
        'addressline2'    => isset($_POST['addressline2'])   ? trim($_POST['addressline2'])    : '',
        'firstname'  => isset($_POST['firstname']) ? trim($_POST['firstname'])  : '',
        'lastname'  => isset($_POST['lastname']) ? trim($_POST['lastname'])  : '',
        'email'      => isset($_POST['email'])     ? trim($_POST['email'])      : '',
        'tel'        => isset($_POST['tel'])       ? make_semiangle(trim($_POST['tel'])) : '',
        'zipcode'       => isset($_POST['zipcode'])       ? make_semiangle(trim($_POST['zipcode'])) : '',
        'code'          => empty($_POST['code'])       ? '' : $_POST['code'],
        );

    if (update_address($address))
    {
        show_message($_LANG['edit_address_success'], $_LANG['address_list_lnk'], ''. $cur_lang_url .'m-users-a-address_list.htm', 'success');
    }
}

/* 删除收货地址 */
elseif ($_ACT == 'drop_consignee')
{
    include_once('lib/lib.f.transaction.php');

    $consignee_id = intval($_GET['id']);

    if (drop_consignee($consignee_id))
    {
        header("Location: ".DOMAIN_USER."/". $cur_lang_url ."m-users-a-address_list.htm\n");
        exit;
    }
    else
    {
        show_message($_LANG['del_address_false']);
    }
}

/* 显示收藏商品列表 */
elseif ($_ACT == 'collection_list')
{
    include_once(ROOT_PATH . 'lib/lib_clips.php');
    $record_count = $db->getOne("SELECT COUNT(*) FROM " .COLLECT. " WHERE user_id='$user_id' ORDER BY add_time DESC");
    $size = 15;
	$_GET['page'] = empty($_GET['page'])?1:intval($_GET['page']);
	$page_count = ceil($record_count/$size);
	if ($_GET['page'] > $page_count ) $_GET['page'] = $page_count;
	if ($_GET['page'] < 1 ) $_GET['page'] = 1;
	$start = ($_GET['page'] - 1) * $size;
	$_GET['x'] = '2';
	$page=new page(array('total' => $record_count,'perpage'=>$size));
	$Arr["pagestr"]    = $page->show(5);
    $od                = empty($_GET['od'])?1:intval($_GET['od']);
    $Arr['goods_list'] = get_collection_goods($user_id, $size, $start, $od);
	$Arr["od"]         = $od;
    $Arr['user_id'] =  $user_id;
    $Arr['action'] = $_ACT;
    $_ACT = 'users_inc';
    $nav_title = ' &raquo;  '.$_LANG['label_collection'];
    $Arr['seo_title'] = $_LANG['label_collection'].' - '.$_CFG['shop_name'];
    $Arr['nav_title']  = $nav_title;
	$Arr['seo_title'] = $_LANG_SEO['favorites']['title'];
	$Arr['seo_keywords']   = $_LANG_SEO['favorites']['keywords'];
	$Arr['seo_description']   = $_LANG_SEO['favorites']['description'];
}

/* 删除收藏的商品 */
elseif ($_ACT == 'delete_collection')
{
    include_once(ROOT_PATH . 'lib/lib_clips.php');
    $collection_id = isset($_GET['collection_id']) ? $_GET['collection_id'] : 0;

    //增加批量删除判断 by mashanling on 2013-02-25 11:06:54
    if (false === strpos($collection_id, ',')) {//单个
        $collection_id = intval($collection_id);
    }
    else {//批量
        $collection_id = map_int($collection_id);
    }

    if ($collection_id > 0)
    {
        $db->query('DELETE FROM ' .COLLECT. " WHERE rec_id IN ($collection_id) AND user_id ='$user_id'" );
    }
    header("Location: ".DOMAIN_USER."/". $cur_lang_url ."m-users-a-collection_list.htm\n");
    exit;
}

/* 显示留言列表 */
elseif ($_ACT == 'message_list')
{
    include_once(ROOT_PATH . 'lib/lib_clips.php');

    $pages = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $order_id = empty($_GET['order_id']) ? 0 : intval($_GET['order_id']);
    $order_info = array();

    /* 获取用户留言的数量 */
    if ($order_id)
    {
        $sql = "SELECT COUNT(*) FROM " .FEEDBACK.
                " WHERE parent_id = 0 AND order_id = '$order_id'";
        $order_info = $db->selectinfo("SELECT * FROM " . ORDERINFO . " WHERE order_id = '$order_id'");
        $order_info['url'] = 'user.php?act=order_detail&order_id=' . $order_id;
    }
    else
    {
        $sql = "SELECT COUNT(*) FROM " .FEEDBACK.
           " WHERE parent_id = 0 AND user_id = '$user_id' AND user_name = '" . $_SESSION['user_name'] . "' AND order_id=0";
    }

    $record_count = $db->getOne($sql);
    $act = array('act' => $_ACT);
    $size = 5;
	$start = ($pages - 1)*$size;
	$_GET['x'] = '2';
	$page=new page(array('total' => $record_count,'perpage'=>$size));
	$Arr["pager"]  = $page->show(5);

    if ($order_id != '')
    {
        $act['order_id'] = $order_id;
    }

    $Arr['message_list'] = get_message_list($user_id, $_SESSION['email'], $size, $start, $order_id);
    $Arr['order_info'] = $order_info;
	$Arr['action'] = $_ACT;
    $_ACT = 'users_clips';
    $nav_title = ' &raquo;  '.$_LANG['label_message'];
    $Arr['seo_title'] = $_LANG['my_account'] . ' -  '.$_LANG['label_message'].'  - '.$_CFG['shop_name'];
	$Arr['nav_title']  = $nav_title;
}

/* 显示评论列表 */
elseif ($_ACT == 'comment_list')
{
    include_once(ROOT_PATH . 'lib/lib_clips.php');

    $pages = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    /* 获取用户留言的数量 */
    $sql = "SELECT COUNT(*) FROM " .COMMENT.
           " WHERE parent_id = 0 AND user_id = '$user_id'";
    $record_count = $db->getOne($sql);
	$size = 5;
	$_GET['x'] = '2';
	$page=new page(array('total' => $record_count,'perpage'=>$size));
	$Arr["pager"]  = $page->show(5);
    $start = ($pages - 1)* $size;
    $Arr['comment_list'] =  get_comment_list($user_id, $size,$start);

	$Arr['action'] = $_ACT;
    $_ACT = 'users_clips';
    $nav_title = ' &raquo;  '.$_LANG['label_comment'];
    $Arr['seo_title'] = $_LANG['my_account'] . ' -  '.$_LANG['label_comment'].'  - '.$_CFG['shop_name'];
	$Arr['nav_title']  = $nav_title;
}

/* 添加我的留言 */
elseif ($_ACT == 'act_add_message')
{
    include_once(ROOT_PATH . 'lib/lib_clips.php');

    $message = array(
        'user_id'     => $user_id,
        'user_name'   => addslashes($_SESSION['firstname'].' '.$_SESSION['lastname']),
        'user_email'  => $_SESSION['email'],
        'err'         => '',
        'msg_type'    => isset($_POST['msg_type']) ? intval($_POST['msg_type'])     : 0,
        'msg_title'   => isset($_POST['msg_title']) ? trim($_POST['msg_title'])     : '',
        'msg_content' => isset($_POST['msg_content']) ? trim($_POST['msg_content']) : '',
        'order_id'=>empty($_POST['order_id']) ? 0 : intval($_POST['order_id']),
        'upload'      => (isset($_FILES['message_img']['error']) && $_FILES['message_img']['error'] == 0) || (!isset($_FILES['message_img']['error']) && isset($_FILES['message_img']['tmp_name']) && $_FILES['message_img']['tmp_name'] != 'none')
         ? $_FILES['message_img'] : array()
     );
    if (add_message($message))
    {
        show_message($_LANG['add_message_success'], $_LANG['message_list_lnk'], '/'. $cur_lang_url .'m-users-a-message_list-order_id-'.$message['order_id'].'.htm', 'success');//-order_id-'. $message['order_id'],'info'.'.htm');
    }
    else
    {
        show_message($err,$_LANG['back_page_up']);
    }
}

/* 标签云列表 */
elseif ($_ACT == 'tag_list')
{
    include_once(ROOT_PATH . 'lib/lib_clips.php');

    $good_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    $smarty->assign('tags',      get_user_tags($user_id));
    $smarty->assign('tags_from', 'user');
    $smarty->display('user_clips.dwt');
}

/* 删除标签云的处理 */
elseif ($_ACT == 'act_del_tag')
{
    include_once(ROOT_PATH . 'lib/lib_clips.php');

    $tag_words = isset($_GET['tag_words']) ? trim($_GET['tag_words']) : '';
    delete_tag($tag_words, $user_id);

    ecs_header("Location: ".DOMAIN_USER."/". $cur_lang_url ."user.php?act=tag_list\n");
    exit;

}

/* 显示缺货登记列表 */
elseif ($_ACT == 'booking_list')
{
    include_once(ROOT_PATH . 'lib/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    /* 获取缺货登记的数量 */
    $sql = "SELECT COUNT(*) " .
            "FROM " .$ecs->table('booking_goods'). " AS bg, " .
                     $ecs->table('goods') . " AS g " .
            "WHERE bg.goods_id = g.goods_id AND user_id = '$user_id'";
    $record_count = $db->getOne($sql);
    $pager = get_pager('user.php', array('act' => $_ACT), $record_count, $page);

    $smarty->assign('booking_list', get_booking_list($user_id, $pager['size'], $pager['start']));
    $smarty->assign('pager',        $pager);
    $smarty->display('user_clips.dwt');
}
/* 添加缺货登记页面 */
elseif ($_ACT == 'add_booking')
{
    include_once(ROOT_PATH . 'lib/lib_clips.php');

    $goods_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($goods_id == 0)
    {
        show_message($_LANG['no_goods_id'], $_LANG['back_page_up'], '', 'error');
    }

    $smarty->assign('info', get_goodsinfo($goods_id));
    $smarty->display('user_clips.dwt');
}

/* 添加缺货登记的处理 */
elseif ($_ACT == 'act_add_booking')
{
    include_once(ROOT_PATH . 'lib/lib_clips.php');

    $booking = array(
        'goods_id'     => isset($_POST['id'])      ? intval($_POST['id'])     : 0,
        'goods_amount' => isset($_POST['number'])  ? intval($_POST['number']) : 0,
        'desc'         => isset($_POST['desc'])    ? trim($_POST['desc'])     : '',
        'linkman'      => isset($_POST['linkman']) ? trim($_POST['linkman'])  : '',
        'email'        => isset($_POST['email'])   ? trim($_POST['email'])    : '',
        'tel'          => isset($_POST['tel'])     ? trim($_POST['tel'])      : '',
        'booking_id'   => isset($_POST['rec_id'])  ? intval($_POST['rec_id']) : 0
    );

    // 查看此商品是否已经登记过
    $rec_id = get_booking_rec($user_id, $booking['goods_id']);
    if ($rec_id > 0)
    {
        show_message($_LANG['booking_rec_exist'], $_LANG['back_page_up'], '', 'error');
    }

    if (add_booking($booking))
    {
        show_message($_LANG['booking_success'], $_LANG['back_booking_list'], DOMAIN_USER.'user.php?act=booking_list', 'success');
    }
    else
    {
        $err->show($_LANG['booking_list_lnk'], DOMAIN_USER.'/user.php?act=booking_list');
    }
}

/* 删除缺货登记 */
elseif ($_ACT == 'act_del_booking')
{
    include_once(ROOT_PATH . 'lib/lib_clips.php');

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id == 0 || $user_id == 0)
    {
        header("Location: ".DOMAIN_USER."/". $cur_lang_url ."user.php?act=booking_list\n");
        exit;
    }

    $result = delete_booking($id, $user_id);
    if ($result)
    {
        header("Location: ".DOMAIN_USER."/". $cur_lang_url ."user.php?act=booking_list\n");
        exit;
    }
}

/* 确认收货 */
elseif ($_ACT == 'affirm_received')
{
    include_once(ROOT_PATH . 'lib/lib.f.transaction.php');

    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    if (affirm_received($order_id, $user_id))
    {
        ecs_header("Location: ".DOMAIN_USER."/". $cur_lang_url ."user.php?act=order_list\n");
        exit;
    }
    else
    {
        $err->show($_LANG['order_list_lnk'], '/'. $cur_lang_url .'user.php?act=order_list');
    }
}

/* 会员退款申请界面 */
elseif ($_ACT == 'account_raply')
{
    $smarty->display('user_transaction.dwt');
}

/* 会员预付款界面 */
elseif ($_ACT == 'account_deposit')
{
    include_once(ROOT_PATH . 'lib/lib_clips.php');

    $surplus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $account    = get_surplus_info($surplus_id);

    $smarty->assign('payment', get_online_payment_list(false));
    $smarty->assign('order',   $account);
    $smarty->display('user_transaction.dwt');
}

/* 会员账目明细界面 */
elseif ($_ACT == 'account_detail')
{
    include_once(ROOT_PATH . 'lib/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $account_type = 'user_money';

    /* 获取记录条数 */
    $sql = "SELECT COUNT(*) FROM " .$ecs->table('account_log').
           " WHERE user_id = '$user_id'" .
           " AND $account_type <> 0 ";
    $record_count = $db->getOne($sql);

    //分页函数
    $pager = get_pager('user.php', array('act' => $_ACT), $record_count, $page);

    //获取剩余余额
    $surplus_amount = get_user_surplus($user_id);
    if (empty($surplus_amount))
    {
        $surplus_amount = 0;
    }

    //获取余额记录
    $account_log = array();
    $sql = "SELECT * FROM " . $ecs->table('account_log') .
           " WHERE user_id = '$user_id'" .
           " AND $account_type <> 0 " .
           " ORDER BY log_id DESC";
    $res = $GLOBALS['db']->selectLimit($sql, $pager['size'], $pager['start']);
    while ($row = $db->fetchRow($res))
    {
        $row['change_time'] = local_date($_CFG['date_format'], $row['change_time']);
        $row['type'] = $row[$account_type] > 0 ? $_LANG['account_inc'] : $_LANG['account_dec'];
        $row['user_money'] = price_format(abs($row['user_money']), false);
        $row['frozen_money'] = price_format(abs($row['frozen_money']), false);
        $row['rank_points'] = abs($row['rank_points']);
        $row['pay_points'] = abs($row['pay_points']);
        $row['short_change_desc'] = sub_str($row['change_desc'], 60);
        $row['amount'] = $row[$account_type];
        $account_log[] = $row;
    }

    //模板赋值
    $smarty->assign('surplus_amount', price_format($surplus_amount, false));
    $smarty->assign('account_log',    $account_log);
    $smarty->assign('pager',          $pager);
    $smarty->display('user_transaction.dwt');
}

/* 会员充值和提现申请记录 */
elseif ($_ACT == 'account_log')
{
    include_once(ROOT_PATH . 'lib/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    /* 获取记录条数 */
    $sql = "SELECT COUNT(*) FROM " .$ecs->table('user_account').
           " WHERE user_id = '$user_id'" .
           " AND process_type " . db_create_in(array(SURPLUS_SAVE, SURPLUS_RETURN));
    $record_count = $db->getOne($sql);

    //分页函数
    $pager = get_pager('user.php', array('act' => $_ACT), $record_count, $page);

    //获取剩余余额
    $surplus_amount = get_user_surplus($user_id);
    if (empty($surplus_amount))
    {
        $surplus_amount = 0;
    }

    //获取余额记录
    $account_log = get_account_log($user_id, $pager['size'], $pager['start']);

    //模板赋值
    $smarty->assign('surplus_amount', price_format($surplus_amount, false));
    $smarty->assign('account_log',    $account_log);
    $smarty->assign('pager',          $pager);
    $smarty->display('user_transaction.dwt');
}

/* 对会员余额申请的处理 */
elseif ($_ACT == 'act_account')
{
    include_once(ROOT_PATH . 'lib/lib_clips.php');
    include_once(ROOT_PATH . 'lib/lib_order.php');
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    if ($amount <= 0)
    {
        show_message($_LANG['amount_gt_zero']);
    }

    /* 变量初始化 */
    $surplus = array(
            'user_id'      => $user_id,
            'rec_id'       => !empty($_POST['rec_id'])      ? intval($_POST['rec_id'])       : 0,
            'process_type' => isset($_POST['surplus_type']) ? intval($_POST['surplus_type']) : 0,
            'payment_id'   => isset($_POST['payment_id'])   ? intval($_POST['payment_id'])   : 0,
            'user_note'    => isset($_POST['user_note'])    ? trim($_POST['user_note'])      : '',
            'amount'       => $amount
    );

    /* 退款申请的处理 */
    if ($surplus['process_type'] == 1)
    {
        /* 判断是否有足够的余额的进行退款的操作 */
        $sur_amount = get_user_surplus($user_id);
        if ($amount > $sur_amount)
        {
            $content = $_LANG['surplus_amount_error'];
            show_message($content, $_LANG['back_page_up'], '', 'info');
        }

        //插入会员账目明细
        $amount = '-'.$amount;
        $surplus['payment'] = '';
        $surplus['rec_id']  = insert_user_account($surplus, $amount);

        /* 如果成功提交 */
        if ($surplus['rec_id'] > 0)
        {
            $content = $_LANG['surplus_appl_submit'];
            show_message($content, $_LANG['back_account_log'], 'user.php?act=account_log', 'info');
        }
        else
        {
            $content = $_LANG['process_false'];
            show_message($content, $_LANG['back_page_up'], '', 'info');
        }
    }
    /* 如果是会员预付款，跳转到下一步，进行线上支付的操作 */
    else
    {
        if ($surplus['payment_id'] <= 0)
        {
            show_message($_LANG['select_payment_pls']);
        }

        include_once(ROOT_PATH .'lib/lib_payment.php');

        //获取支付方式名称
        $payment_info = array();
        $payment_info = payment_info($surplus['payment_id']);
        $surplus['payment'] = $payment_info['pay_name'];

        if ($surplus['rec_id'] > 0)
        {
            //更新会员账目明细
            $surplus['rec_id'] = update_user_account($surplus);
        }
        else
        {
            //插入会员账目明细
            $surplus['rec_id'] = insert_user_account($surplus, $amount);
        }

        //取得支付信息，生成支付代码
        $payment = unserialize_config($payment_info['pay_config']);

        //生成伪订单号, 不足的时候补0
        $order = array();
        $order['order_sn']       = $surplus['rec_id'];
        $order['user_name']      = $_SESSION['user_name'];
        $order['surplus_amount'] = $amount;

        //计算支付手续费用
        $payment_info['pay_fee'] = pay_fee($surplus['payment_id'], $order['surplus_amount'], 0);

        //计算此次预付款需要支付的总金额
        $order['order_amount']   = $amount + $payment_info['pay_fee'];

        //记录支付log
        $order['log_id'] = insert_pay_log($surplus['rec_id'], $order['order_amount'], $type=PAY_SURPLUS, 0);

        /* 调用相应的支付方式文件 */
        include_once(ROOT_PATH . 'lib/modules/payment/' . $payment_info['pay_code'] . '.php');

        /* 取得在线支付方式的支付按钮 */
        $pay_obj = new $payment_info['pay_code'];
        $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);

        /* 模板赋值 */
        $smarty->assign('payment', $payment_info);
        $smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
        $smarty->assign('amount',  price_format($amount, false));
        $smarty->assign('order',   $order);
        $smarty->display('user_transaction.dwt');
    }
}

/* 删除会员余额 */
elseif ($_ACT == 'cancel')
{
    include_once(ROOT_PATH . 'lib/lib_clips.php');

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id == 0 || $user_id == 0)
    {
        ecs_header("Location: ".DOMAIN_USER."/". $cur_lang_url ."user.php?act=account_log\n");
        exit;
    }

    $result = del_user_account($id, $user_id);
    if ($result)
    {
        ecs_header("Location: ".DOMAIN_USER."/". $cur_lang_url ."user.php?act=account_log\n");
        exit;
    }
}

/* 会员通过帐目明细列表进行再付款的操作 */
elseif ($_ACT == 'pay')
{
    include_once(ROOT_PATH . 'lib/lib_clips.php');
    include_once(ROOT_PATH . 'lib/lib_payment.php');
    include_once(ROOT_PATH . 'lib/lib_order.php');

    //变量初始化
    $surplus_id = isset($_GET['id'])  ? intval($_GET['id'])  : 0;
    $payment_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;

    if ($surplus_id == 0)
    {
        ecs_header("Location: ".DOMAIN_USER."/". $cur_lang_url ."user.php?act=account_log\n");
        exit;
    }

    //如果原来的支付方式已禁用或者已删除, 重新选择支付方式
    if ($payment_id == 0)
    {
        ecs_header("Location: ".DOMAIN_USER."/". $cur_lang_url ."user.php?act=account_deposit&id=".$surplus_id."\n");
        exit;
    }

    //获取单条会员帐目信息
    $order = array();
    $order = get_surplus_info($surplus_id);

    //支付方式的信息
    $payment_info = array();
    $payment_info = payment_info($payment_id);

    /* 如果当前支付方式没有被禁用，进行支付的操作 */
    if (!empty($payment_info))
    {
        //取得支付信息，生成支付代码
        $payment = unserialize_config($payment_info['pay_config']);

        //生成伪订单号
        $order['order_sn'] = $surplus_id;

        //获取需要支付的log_id
        $order['log_id'] = get_paylog_id($surplus_id, $pay_type = PAY_SURPLUS);

        $order['user_name']      = $_SESSION['user_name'];
        $order['surplus_amount'] = $order['amount'];

        //计算支付手续费用
        $payment_info['pay_fee'] = pay_fee($payment_id, $order['surplus_amount'], 0);

        //计算此次预付款需要支付的总金额
        $order['order_amount']   = $order['surplus_amount'] + $payment_info['pay_fee'];

        //如果支付费用改变了，也要相应的更改pay_log表的order_amount
        $order_amount = $db->getOne("SELECT order_amount FROM " .$ecs->table('pay_log')." WHERE log_id = '$order[log_id]'");
        if ($order_amount <> $order['order_amount'])
        {
            $db->query("UPDATE " .$ecs->table('pay_log').
                       " SET order_amount = '$order[order_amount]' WHERE log_id = '$order[log_id]'");
        }

        /* 调用相应的支付方式文件 */
        include_once(ROOT_PATH . 'lib/modules/payment/' . $payment_info['pay_code'] . '.php');

        /* 取得在线支付方式的支付按钮 */
        $pay_obj = new $payment_info['pay_code'];
        $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);

        /* 模板赋值 */
        $smarty->assign('payment', $payment_info);
        $smarty->assign('order',   $order);
        $smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
        $smarty->assign('amount',  price_format($order['surplus_amount'], false));
        $smarty->assign('action',  'act_account');
        $smarty->display('user_transaction.dwt');
    }
    /* 重新选择支付方式 */
    else
    {
        include_once(ROOT_PATH . 'lib/lib_clips.php');

        $smarty->assign('payment', get_online_payment_list());
        $smarty->assign('order',   $order);
        $smarty->assign('action',  'account_deposit');
        $smarty->display('user_transaction.dwt');
    }
}

/* 添加标签(ajax) */
elseif ($_ACT == 'add_tag')
{
    include_once('lib/cls_json.php');
    include_once('lib/lib_clips.php');

    $result = array('error' => 0, 'message' => '', 'content' => '');
    $id     = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $tag    = isset($_POST['tag']) ? json_str_iconv(trim($_POST['tag'])) : '';

    if ($user_id == 0)
    {
        /* 用户没有登录 */
        $result['error']   = 1;
        $result['message'] = $_LANG['tag_anonymous'];
    }
    else
    {
        add_tag($id, $tag); // 添加tag
        clear_cache_files('goods'); // 删除缓存

        /* 重新获得该商品的所有缓存 */
        $arr = get_tags($id);

        foreach ($arr AS $row)
        {
            $result['content'][] = array('word' => htmlspecialchars($row['tag_words']), 'count' => $row['tag_count']);
        }
    }

    $json = new JSON;

    echo $json->encode($result);
    exit;
}

/* 添加收藏商品(ajax) */
elseif ($_ACT == 'collect')
{
    include_once(ROOT_PATH .'lib/cls_json.php');
    $json = new JSON();
    $result = array('error' => 0, 'message' => '');
    $goods_id = $_GET['id'];

    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['sign_please'];
        die($json->encode($result));
    }
    else
    {
        /* 检查是否已经存在于用户的收藏夹 */
        $sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('collect_goods') .
            " WHERE user_id='$_SESSION[user_id]' AND goods_id = '$goods_id'";
        if ($GLOBALS['db']->GetOne($sql) > 0)
        {
            $result['error'] = 1;
            $result['message'] = $GLOBALS['_LANG']['collect_existed'];
            die($json->encode($result));
        }
        else
        {
            $time = gmtime();
            $sql = "INSERT INTO " .$GLOBALS['ecs']->table('collect_goods'). " (user_id, goods_id, add_time)" .
                    "VALUES ('$_SESSION[user_id]', '$goods_id', '$time')";

            if ($GLOBALS['db']->query($sql) === false)
            {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['db']->errorMsg();
                die($json->encode($result));
            }
            else
            {
                $result['error'] = 0;
                $result['message'] = $GLOBALS['_LANG']['collect_success'];
                die($json->encode($result));
            }
        }
    }
}

/* 删除留言 */
elseif ($_ACT == 'del_msg')
{
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $order_id = empty($_GET['order_id']) ? 0 : intval($_GET['order_id']);

    if ($id > 0)
    {
        $sql = 'SELECT user_id, message_img FROM ' .FEEDBACK. " WHERE msg_id = '$id' LIMIT 1";
        $row = $db->selectinfo($sql);
        if ($row && $row['user_id'] == $user_id)
        {
            /* 验证通过，删除留言，回复，及相应文件 */
            if ($row['message_img'])
            {
                @unlink(ROOT_PATH . DATA_DIR . '/feedbackimg/'. $row['message_img']);
            }
            $sql = "DELETE FROM " .FEEDBACK. " WHERE msg_id = '$id' OR parent_id = '$id'";
            $db->query($sql);
        }
    }
    header("Location: ".DOMAIN_USER."/". $cur_lang_url ."m-users-a-message_list-order_id-$order_id.htm\n");
    exit;
}

/* 删除评论 */
elseif ($_ACT == 'del_cmt')
{
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id > 0)
    {
        $sql = "DELETE FROM " .COMMENT. " WHERE comment_id = '$id' AND user_id = '$user_id'";
        $db->query($sql);
    }
    header("Location: ".DOMAIN_USER."/$cur_lang_url"."m-users-a-comment_list.htm\n");
    exit;
}

/* 合并订单 */
elseif ($_ACT == 'merge_order')
{
    include_once(ROOT_PATH .'lib/lib.f.transaction.php');
    include_once(ROOT_PATH .'lib/lib_order.php');
    $from_order = isset($_POST['from_order']) ? trim($_POST['from_order']) : '';
    $to_order   = isset($_POST['to_order']) ? trim($_POST['to_order']) : '';
    if (merge_user_order($from_order, $to_order, $user_id))
    {
        show_message($_LANG['merge_order_success'],$_LANG['order_list_lnk'],'user.php?act=order_list', 'success');
    }
    else
    {
        $err->show($_LANG['order_list_lnk']);
    }
}
/* 将指定订单中商品添加到购物车 */
elseif ($_ACT == 'return_to_cart')
{
    include_once(ROOT_PATH .'lib/cls_json.php');
    include_once(ROOT_PATH .'lib/lib.f.transaction.php');
    $json = new JSON();

    $result = array('error' => 0, 'message' => '', 'content' => '');
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if ($order_id == 0)
    {
        $result['error']   = 1;
        $result['message'] = $_LANG['order_id_empty'];
        die($json->encode($result));
    }

    if ($user_id == 0)
    {
        /* 用户没有登录 */
        $result['error']   = 1;
        $result['message'] = $_LANG['sign_please'];
        die($json->encode($result));
    }

    /* 检查订单是否属于该用户 */
    $order_user = $db->getOne("SELECT user_id FROM " .ORDERINFO. " WHERE order_id = '$order_id'");
    if (empty($order_user))
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['order_exist'];
        die($json->encode($result));
    }
    else
    {
        if ($order_user != $user_id)
        {
            $result['error'] = 1;
            $result['message'] = $_LANG['no_priv'];
            die($json->encode($result));
        }
    }

    $message = return_to_cart($order_id);

    if ($message === true)
    {
        $result['error'] = 0;
        $result['message'] = $_LANG['return_to_cart_success'];
        die($json->encode($result));
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['order_exist'];
        die($json->encode($result));
    }

}

/* 保存订单详情收货地址 */
elseif ($_ACT == 'save_order_address')
{
    include_once(ROOT_PATH .'lib/lib.f.transaction.php');

    $address = array(
        'consignee' => isset($_POST['consignee']) ? trim($_POST['consignee'])  : '',
        'email'     => isset($_POST['email'])     ? trim($_POST['email'])      : '',
        'address'   => isset($_POST['address'])   ? trim($_POST['address'])    : '',
        'zipcode'   => isset($_POST['zipcode'])   ? make_semiangle(trim($_POST['zipcode'])) : '',
        'tel'       => isset($_POST['tel'])       ? trim($_POST['tel'])        : '',
        'mobile'    => isset($_POST['mobile'])    ? trim($_POST['mobile'])     : '',
        'sign_building' => isset($_POST['sign_building']) ? trim($_POST['sign_building']) : '',
        'best_time' => isset($_POST['best_time']) ? trim($_POST['best_time'])  : '',
        'order_id'  => isset($_POST['order_id'])  ? intval($_POST['order_id']) : 0
        );
    if (save_order_address($address, $user_id))
    {
        ecs_header('Location: ".DOMAIN_USER."/'. $cur_lang_url .'user.php?act=order_detail&order_id=' .$address['order_id']. "\n");
        exit;
    }
    else
    {
        $err->show($_LANG['order_list_lnk'], '/'. $cur_lang_url .'user.php?act=order_list');
    }
}
//首页邮件订阅ajax操做和验证操作
elseif ($_ACT =='email_list')
{
    $job = $_GET['job'];
	$info = array();
    if($job == 'add' || $job == 'del')
    {
        if(isset($_SESSION['last_email_query']))
        {
            if(time() - $_SESSION['last_email_query'] <= 30)
            {
            	$info['type'] = 1;
            	$info['info'] = $_LANG['order_query_toofast'];
                ajaxReturn($info,'json');
            }
        }
        $_SESSION['last_email_query'] = time();
    }
    $email = trim($_GET['email']);
    $firstname = isset($_GET['firstname']) ? trim($_GET['firstname']) : '';
    $email = htmlspecialchars($email);
    $firstname = htmlspecialchars($firstname);
    if (!is_email($email))
    {
    	$info['type'] = 1;
        $info['info'] = sprintf($_LANG['email_invalid'], $email);
        ajaxReturn($info,'json');
    }
    $ck = $db->selectinfo("SELECT * FROM " . Email_list . " WHERE email = '$email'");
    if ($job == 'add')
    {
        if (empty($ck))
        {
        	$sql = "select user_id from ".USERS." where email='$email'";
			$user_id = $db->getOne($sql);
			$isReg =1;//是否注册会员
			if($user_id <= 0)
			{
	        	//创建会员
	        	$isReg =2;//是否注册会员
				$password = rand_string(8);
				include_once('lib/lib_passport.php');
				if (register($email,$password) !== false)
				{
					//注册用户
					$_SESSION['jointimes'] = time();
					ChangeSessId();
					update_user_info();
			      	add_point($_SESSION['user_id'],10, 2, $_LANG['register_successfully']);
			      	//Logger::filename('fun.users.add_point');
                    //trigger_error($_SESSION['email'] . "({$_SESSION['user_id']})");
			      	$Arr['password'] = '<p>By the way, your account name is your email address, and your password is: <strong style="color: #F00">'. $password .'</strong>.</p>';
				}
			}
			//邮件订阅
			$source = empty($_GET['source'])?0:$_GET['source'];
            $hash = substr(md5(time()), 1, 10);
            $now= gmtime();
            $sql = "INSERT INTO " . Email_list . " (email, stat, hash,firstname,source,addTime,isReg) VALUES ('$email', 0, '$hash','$firstname',$source,$now,$isReg)";
            $db->query($sql);
            $Arr['hash'] = $hash;
            $Arr['email'] = $email;
            $info['type'] = 0;
        	$info['info'] = $_LANG['thank_you_for'];
			//订阅成功邮件
        	require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');  //取得模版标题
			$email_temp_id = 53;
			foreach( $Arr as $key => $value ){
				$Tpl->assign( $key, $value );
			}
			$mail_subject = $mail_conf[$cur_lang][$email_temp_id];
			$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $cur_lang . '/' .$email_temp_id.'.html');
			exec_send2($email,$mail_subject,$mail_body);
        }
        elseif ($ck['stat'] == 1)
        {
        	$info['type'] = 1;
        	$info['info'] = sprintf($_LANG['you_have_already_subscribed'], $email);
        }
        else
        {
            $hash = substr(md5(time()),1 , 10);
            $sql = "UPDATE " . Email_list . " SET hash = '$hash' WHERE email = '$email'";
            $db->query($sql);
            $Arr['hash'] = $hash;
            $Arr['email'] = $email;
            $info['type'] = 1;
        	$info['info'] = $_LANG['email_re_check'];
            //订阅成功邮件
        	require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');  //取得模版标题
			$email_temp_id = 53;
			foreach( $Arr as $key => $value ){
				$Tpl->assign( $key, $value );
			}
			$mail_subject = $mail_conf[$cur_lang][$email_temp_id];
			$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/' . $cur_lang . '/' .$email_temp_id.'.html');
			exec_send2($email,$mail_subject,$mail_body);
        }
        ajaxReturn($info,'json');
    }
    elseif ($job == 'del')
    {
        if (empty($ck))
        {
            $info = sprintf($_LANG['email_notin_list'], $email);
        }
        elseif ($ck['stat'] == 1)
        {
            $hash = substr(md5(time()),1,10);
            $sql = "UPDATE " . Email_list . " SET hash = '$hash' WHERE email = '$email'";
            $db->query($sql);
            $info = $_LANG['email_check'];

            $url = $ecs->url() . "user.php?act=email_list&job=del_check&hash=$hash&email=$email";
            exec_send2($email, $_LANG['check_mail'], sprintf($_LANG['check_mail_content'], $email, $_CFG['shop_name'], $url, $url, $_CFG['shop_name'], local_date('Y-m-d')));
        }
        else
        {
            $info = $_LANG['email_not_alive'];
        }
        die($info);
    }
    elseif ($job == 'add_check')
    {
        if (!(empty($ck)) && $_GET['hash'] == $ck['hash'] && $ck['stat'] == 0)
        {
            $youhuilv_arr = array(0=>'10-1',1=>'20-2',2=>'30-3',3=>'50-5',4=>'90-9',5=>'100-10',6=>'200-20');
            $sql = "UPDATE " . Email_list . " SET stat = 1 WHERE email = '$email'";
            $db->query($sql);
            //生成促销码
			$pcodeArr['users']       = $email;
		    $pcodeArr['create_time'] = gmtime() ;
		    $pcodeArr['exp_time']    = gmtime()+24*3600*30;
		    //$pcodeArr['youhuilv']    = "50-5";
		    $pcodeArr['goods']       = '';
		    $pcodeArr['fangshi']     = 2;
		    $pcodeArr['times']       = 1;
            $pcodeArr['is_applay']   = 0; //0为促销码,1为优惠券
            $pcodeArr['source']      = 'Newsletter Subscription';
			for($i=0;$i<7;$i++){
                $pcodeArr['youhuilv'] = $youhuilv_arr[$i];
				$error_no = 0;
				do
				{
					$pcodeArr['code']   = rand_keys(8);
					$db->autoExecute(PCODE, $pcodeArr);
					$error_no = $db->Errno;
					if ($error_no > 0 && $error_no != 1062)
					{
						die($GLOBALS['db']->errorMsg());
					}
				}
				while ($error_no == 1062); //如果是订单号重复则重新提交数据
			}
        }
        $user_id =empty($_SESSION['user_id'])?0:$_SESSION['user_id'];
		if($user_id == 0)
	    {
			$ref = empty($_GET['ref']) ? '/'. $cur_lang .'/m-ucoupon-a-couponlist.htm' : trim($_GET['ref']);	//订阅成功跳转
			header("Location: " . DOMAIN_USER ."/". $cur_lang ."/m-users-a-sign.htm?ref=".$ref);
			//$ref = '/'. $cur_lang .'/m-ucoupon-a-couponlist.htm';
			//header("Location: " . DOMAIN_USER ."/". $cur_lang ."/m-users-a-sign.htm?ref=".$ref);
	    }
	    else
	    {
			$ref = empty($_GET['ref']) ? '/'. $cur_lang .'/m-ucoupon-a-couponlist.htm' : trim($_GET['ref']);	//订阅成功跳转
			header("Location: " . DOMAIN_USER . "/" .$ref);
			//header("Location: " . DOMAIN_USER . "/". $cur_lang ."/m-ucoupon-a-couponlist.htm");
	    }
        exit();
    }
    elseif ($job == 'del_check')
    {
        if (empty($ck))
        {
            $info = sprintf($_LANG['email_invalid'], $email);
        }
        elseif ($ck['stat'] == 1)
        {
            if ($_GET['hash'] == $ck['hash'])
            {
                //$sql = "DELETE FROM " . Email_list .  " WHERE email = '$email'";
                $sql = "UPDATE " . Email_list .  " SET stat = 2 WHERE email = '$email'";
                $db->query($sql);
                $info = $_LANG['email_canceled'];
            }
            else
            {
                $info = $_LANG['hash_wrong'];
            }
        }
        else
        {
            $info = $_LANG['email_not_alive'];
        }
        show_message($info, $_LANG['back_home_lnk'], './');
    }
}
//退邮件
elseif ($_ACT == 'unsubmail')
{
	$email = empty($_GET['e'])?'':htmlspecialchars(trim($_GET['e']));
	$biaoshi = 'info';
	if ($email){
		$ck = $db->selectinfo("SELECT email FROM " . USERS . " WHERE email = '$email'");
		if (!empty($ck['email'])){
			$sql = "update ".USERS." set is_unsub = '1' where  email = '$ck[email]'";
			$db->query($sql);
			$info = 'E-mail: '.$email.' has been unsubscribed. ';
			$biaoshi = 'success';
		}else{
			$info = 'E-mail does not exist!';
		}
	}else{
		$info = 'Email is empty!';
	}
   show_message($info, $_LANG['back_home_lnk'], './',$biaoshi);
}
/* ajax 发送验证邮件 */
elseif ($_ACT == 'send_hash_mail')
{
    include_once(ROOT_PATH .'lib/cls_json.php');
    include_once(ROOT_PATH .'lib/lib_passport.php');
    $json = new JSON();

    $result = array('error' => 0, 'message' => '', 'content' => '');

    if ($user_id == 0)
    {
        /* 用户没有登录 */
        $result['error']   = 1;
        $result['message'] = $_LANG['sign_please'];
        die($json->encode($result));
    }

    if (send_regiter_hash($user_id))
    {
        /* 用户没有登录 */
        $result['message'] = $_LANG['validate_mail_ok'];
        die($json->encode($result));
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = $GLOBALS['err']->last_message();
    }

    die($json->encode($result));
}
elseif ($_ACT == 'points_record')
{
    include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
    $point_record = read_static_cache('point_record', FRONT_STATIC_CACHE_PATH);
    $user = $db->selectInfo("SELECT * FROM " .USERS. " WHERE user_id = '$user_id'");
    $record_count = $db->getOne("SELECT COUNT(*) FROM " .POINT_RECORD. " WHERE user_id = '$user_id'");
    $size = 20;
	$_GET['page'] = empty($_GET['page'])?1:intval($_GET['page']);
	$page_count = ceil($record_count/$size);
	if ($_GET['page'] > $page_count ) $_GET['page'] = $page_count;
	if ($_GET['page'] < 1 ) $_GET['page'] = 1;
	$start = ($_GET['page'] - 1) * $size;
	$_GET['x'] = '2';
	$page=new page(array('total' => $record_count,'perpage'=>$size));
	$Arr["pagestr"]  = $page->show(5);
    $records = get_point_records($user_id,$size,$start);
	//多语言 fangxin 2013-11-07
	if(!empty($records)) {
		foreach($records as $k=>$v) {
			foreach($point_record['en'] as $key=>$value) {
				preg_match('/'.$value.'/i', $v['note'], $matches);
				if(!empty($matches[0])) {
					if(!empty($point_record['translate'][$key])) {
						$queer = 'order';
						$records[$k]['note'] = str_ireplace($value,$point_record['translate'][$key],$v['note']);
						preg_match('/'. $queer .'/i', $records[$k]['note'], $mat);
						if(!empty($mat[0])) {
							$records[$k]['note'] = str_ireplace($queer, $_LANG['order'], $records[$k]['note']);
						}
						break;
					}
				}
			}
		}
	}
    $Arr['user'] = $user;
    $Arr['points_record'] = $records;
	$Arr['action'] = $_ACT;
    $_ACT = 'users_inc';
    $nav_title = ' &raquo;  '.$_LANG['label_Point'];
    $Arr['seo_title'] = $_LANG['my_account'] . ' -  '.$_LANG['point_name'].'  - '.$_CFG['shop_name'];
	$Arr['nav_title']  = $nav_title;
}
elseif ($_ACT == 'pointlist')
{
    include_once(ROOT_PATH . 'lib/lib.f.transaction.php');

	$user_id = empty($_GET['user_id'])?0:intval($_GET['user_id']);
    $record_count = $db->getOne("SELECT COUNT(*) FROM " .POINT_RECORD. " WHERE user_id = '$user_id'");

    $size = 20;
	$_GET['page'] = empty($_GET['page'])?1:intval($_GET['page']);
	$page_count = ceil($record_count/$size);
	if ($_GET['page'] > $page_count ) $_GET['page'] = $page_count;
	if ($_GET['page'] < 1 ) $_GET['page'] = 1;
	$start = ($_GET['page'] - 1) * $size;

	$_GET['x'] = '2';
	$page=new page(array('total' => $record_count,'perpage'=>$size));
	$Arr["pagestr"]  = $page->show(5);

    $records = get_point_records($user_id,$size,$start);

    $Arr['user'] = $user;
    $Arr['points_record'] = $records;
	$Arr['action'] = $_ACT;
    $nav_title = ' &raquo;  '.$_LANG['label_order'];
    $Arr['seo_title'] = $_LANG['my_account'] . ' - '.$_LANG['point_name'].'  - '.$_CFG['shop_name'];
	$Arr['nav_title']  = $nav_title;
    $_ACT = 'queryorder';
}

//删除订单
elseif ($_ACT == 'delete_order')
{
	include_once(ROOT_PATH . 'lib/lib.f.transaction.php');
	$order_id = empty($_POST['id'])?0:intval($_POST['id']);
	if(!$order_id){
		exit();
	}
	$sql = "select used_point,order_sn,order_id,order_status from eload_order_info where order_status =0 and order_id ='$order_id' and user_id='$user_id'";
	$order = $db->selectInfo($sql);
	if(empty($order))exit();
	$used_points = $order['used_point'];
	$order_id = $order['order_id'];
	$order_sn = $order['order_sn'];
	if(!$order_id){
		exit();
	}

	if($used_points){
		add_point($user_id,$used_points,2,"Order $order_sn was deleted");
	}
	$db->delete('eload_order_info',"order_id ='$order_id' and $user_id='$user_id'");
	$db->delete('eload_order_goods',"order_id ='$order_id'");
	$msg = $_LANG['deleted_successfully'];
	if($used_points){
		$msg .=" $msg $used_points return to your account.";
	}
	echo $msg;
	exit();
	//$user_id = empty($_GET['user_id'])?0:intval($_GET['user_id']);
}

//针对未付款的订单做取消操作 fangxin 2014-02-24 AM
elseif ($_ACT == 'cancel_order') {
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    $reason_id = isset($_GET['reason_id']) ? intval($_GET['reason_id']) : 0;
    if($order_id) {
        $order_info = $db->selectinfo("SELECT order_sn,used_point FROM " . ORDERINFO . " WHERE order_id=$order_id AND user_id='".$_SESSION['user_id']."' AND order_status=0");
        $sql = " order_status = '11' ";  //取消状态
        if(!empty($order_info)) {
            if($db->update(ORDERINFO,$sql," order_id = '".$order_id."'")) {
                //如果订单使用了积分，则返还
                $order_info['used_point'] > 0 && add_point($user_id, $order_info['used_point'], 2,"Order " . $order_info['order_sn'] . " have been cancelled");
                //记录
                $db->query("INSERT INTO eload_order_cancel_record SET order_id=$order_id, reason_id=$reason_id, add_time=".gmtime());
            }
        }
    }
    header("Location: " . DOMAIN_USER . "/" . $cur_lang_url . "m-users-a-order_list.htm");
    exit;
}

elseif ($_ACT == 'rma_apply') {//RMA Application
    //$Arr['rma_data'] = include(ROOT_PATH . 'data-cache/rma_data.php');
    $Arr['rma_data'] = read_static_cache('rma_data',FRONT_STATIC_CACHE_PATH);
    $order_sn = isset($_GET['order_sn']) ? trim($_GET['order_sn']) : '';

    if ($order_sn) {
        $order_info = RMA::getOrderInfo($order_sn);

        if ($order_info) {
            $Arr['order_info'] = $order_info;
        }
        else {
            $Arr['error'] = $_LANG['rma_input_valid_order_no'];
        }
    }

    $Arr['country_list'] = area_list();

    $nav_title     = ' &raquo; ' . $_LANG['rma_application'];
    $Arr['seo_title'] = $_LANG['my_account']  . '  -  ' . $_LANG['rma_application'] . '  - '.$_CFG['shop_name'];
    $Arr['nav_title']  = $nav_title;
    $Arr['action'] = $_ACT;
    $_ACT = 'users_inc';
    $Arr['order_sn'] = $order_sn;
}
elseif ($_ACT == 'rma_list') {//RMA list

    if (isset($_SESSION['rma_msg'])) {
        $Arr['rma_msg']  = $_SESSION['rma_msg'];
        unset($_SESSION['rma_msg']);
    }

    $Arr['time_arr'] = array(0 => $_LANG['rma_all_rma'], 1 => $_LANG['rma_recent_month_rma']);

    $page_size = 10;
    $data      = RMA::getRMARecord($page_size);

    if ($data) {
        $Arr['rma_record'] = $data[1];
        $page = new page(array('total' => $data[0], 'perpage' => $page_size));
        $Arr['pagestr'] = $page->show(5);
    }

    $nav_title     = ' &raquo; ' . $_LANG['rma_record'];
    $Arr['seo_title'] = $_LANG['my_account']  . ' -  ' . $_LANG['rma_record'] . '  - '.$_CFG['shop_name'];
    $Arr['nav_title']  = $nav_title;
    $Arr['action'] = $_ACT;
    $_ACT = 'users_inc';
}
elseif ($_ACT == 'rma_info') {//RMA info
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id) {
        $data = RMA::getRMAInfo($id);

        if ($data) {
            $Arr['rma_info']   = $data;
            $Arr['unread']     = RMA::getRMAMsgUnreadCount($id);
            $Arr['tracking_number'] = RMA::getRMATrackingNumber($id);
        }
    }

    $nav_title     = ' &raquo; ' . $_LANG['rma_details'];
    $Arr['seo_title'] = $_LANG['my_account']  .  ' -  ' . $_LANG['rma_record'] . '  - '.$_CFG['shop_name'];
    $Arr['nav_title']  = $nav_title;
    $Arr['action'] = $_ACT;
    $_ACT = 'users_inc';
}
elseif ($_ACT == 'rma_submit') {//RMA提交
    !isset($_SERVER['HTTP_REFERER']) && exit('Access Denied!');

    require(ROOT_PATH . 'lib/class.upload.php');

    $error = '';

    if (!empty($_FILES)) {

        $opt = array(
            'size_limit'      => 200,
            'allow_extension' => array('jpg'),
            'upload_dir'      => ROOT_PATH . 'uploads/rma_upload/' . date('Ymd/'),
        );
        $upload   = new Upload($opt);
        $file_arr = array(1 => '', 2 => '', 3 => '');

        foreach (array($_FILES['file1'], $_FILES['file2'], $_FILES['file3']) as $k => $file) { //检查上传的图片
            $index = $k + 1;

            if (!empty($file['tmp_name'])) {

                $result = $upload->execute($file);

                if (is_array($result)) {
                    $has_upload = true;
                    $file_arr[$index] = $result['pathname'];
                }
                else {

                    foreach ($file_arr as $name) {
                        file_exists($name) && unlink($name);
                    }

                    $error .= sprintf($_LANG['rma_file_upload_failed'], $index);
                }
            }
        }//end foreach

        !empty($error) && show_message($error, array('Go Back'), array($_SERVER['HTTP_REFERER']), 'warning');

    }//end if $_FILES

    if (empty($_POST['num']) || empty($_POST['reason']) || empty($_POST['goods_sn']) || empty($_POST['goods_title'])) {
        show_message('Submit Failed!', array('Go Back'), array($_SERVER['HTTP_REFERER']));
    }

    $order_sn   = $_POST['order_sn'];//订单号
    $area       = area_list();
    $coutry     = $area[intval($_POST['country'])]['region_code'];
    $rma_order  = array(
        'user_id'	     => $_SESSION['user_id'],//用户id
        'rma_source'	 => 'dealsmachine',//RMA来源
        'rma_number'     => RMA::generate_rma_no(),//rma号
    	'order_sn'       => $order_sn,//订单号
        'apply_date'     => local_date('Y-m-d'),//申请日期，ERP要时间格式
        'apply_time'     => gmtime(),//申请时间
        'apply_type'     => intval($_POST['apply_type']),//申请类型
        'email'          => $_POST['email'],//收货人email
        'consignee'      => $_POST['consignee'],//收货人姓名
        'address_1'      => $_POST['address1'],//收货人地址1
        'address_2'      => $_POST['address2'],//收货人地址2
        'city'           => $_POST['city'],//收货人城市
        'province'       => $_POST['province'],//收货人省/洲
        'nation'         => $coutry,//收货人国家
        'postalcode'     => $_POST['zipcode'],//收货人邮编
        'phone'          => $_POST['phone'],//收货人电话
        'attachment1'    => empty($file_arr[1]) ? '' : WEBSITE . str_replace(ROOT_PATH, '', $file_arr[1]),//附件1
        'attachment2'    => empty($file_arr[2]) ? '' : WEBSITE . str_replace(ROOT_PATH, '', $file_arr[2]),//附件2
        'attachment3'    => empty($file_arr[3]) ? '' : WEBSITE . str_replace(ROOT_PATH, '', $file_arr[3]),//附件3
    );

    $db->autoExecute(RMA_ORDER, $rma_order);

    $insert_id = $db->insertId();

    if ($insert_id < 1) {
        show_message($_LANG['rma_apply_failed'], array('Go Back'), array($_SERVER['HTTP_REFERER']));
    }

    $num_arr      = $_POST['num'];//数量
    $reason       = $_POST['reason'];//原因
    $goods_sn     = $_POST['goods_sn'];//产品编码
    $goods_title  = $_POST['goods_title'];//产品标题
    $desc         = $_POST['desc'];//详情

    foreach ($num_arr as $k => $value) {
        $sn   = $goods_sn[$k];
        $data = array(
            'rma_order_id'     => $insert_id,
			//'order_sn'         => $order_sn,//订单号
            'goods_sn'         => $sn,
            'goods_title'	   => $goods_title[$k],
            'goods_number'	   => $value,
            'description'	   => $desc[$k],
            'reason'   		   => $reason[$k],
        );

        //Damaged during shipping /broken items received需要上传图片 by mashanling on 2012-08-14 16:19:46
        if (($reason[$k] == 3 || $reason[$k] == 5) && !isset($has_upload)) {
            //$rma_arr = include(ROOT_PATH . 'data-cache/rma_data.php');
            $rma_arr = read_static_cache('rma_data',FRONT_STATIC_CACHE_PATH);
            $db->delete(RMA_ORDER, 'id=' . $insert_id);
            $db->delete(RMA_PRODUCT, 'rma_order_id=' . $insert_id);
            show_message($sn . '. ' . sprintf($_LANG['rma_must_upload_attachment'], $rma_arr['reason'][3], $rma_arr['reason'][5]), array('Go Back'), array($_SERVER['HTTP_REFERER']));
        }

        if (RMA::applied($order_sn, $sn, $value)) {//已经申请过
            $db->delete(RMA_ORDER, 'id=' . $insert_id);
            $db->delete(RMA_PRODUCT, 'rma_order_id=' . $insert_id);
            show_message($sn . '. ' . $_LANG['rma_apply_exceed'], array('Go Back'), array($_SERVER['HTTP_REFERER']));
        }

        $db->autoExecute(RMA_PRODUCT, $data);
    }

    $_SESSION['rma_msg'] = sprintf($_LANG['rma_apply_successfully'], $rma_order['rma_number']);
    header('Location: '.DOMAIN_USER.'/'. $cur_lang_url .'m-users-a-rma_list.htm');
    exit;
}//end if rma_submit
elseif ($_ACT == 'rma_msg') {//RMA msg

    if (isset($_SESSION['rma_msg'])) {
        $Arr['rma_msg']  = $_SESSION['rma_msg'];
        unset($_SESSION['rma_msg']);
    }

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    //if ($id) {

        if ($id && ($data = RMA::getRMAMsg($id))) {
            $Arr['rma_msg_list']   = $data;
            $Arr['rma_number']     = $data[0]['rma_number'];

            $last_reply = $db->getOne('SELECT add_time FROM ' . RMA_MSG . ' WHERE rma_order_id=' . $id . ' AND user_id=0 ORDER BY add_time DESC LIMIT 1');

            if ($data[0]['status'] > 7 || $last_reply && $last_reply < gmtime() - 86400 * 30) {
                //e_log($_SESSION['user_id']);
                $Arr['rma_status'] = 8;
            }

            RMA::updateMsgRead($id);//设为已读
        }
        elseif (!$id) {
            $rma_options = RMA::getRMAOptions();
            $Arr['rma_options'] = $rma_options;
        }
        else {
            $Arr['rma_number']     = $db->getOne('SELECT rma_number FROM ' . RMA_ORDER . ' WHERE id=' . $id);
        }
    //}

    $nav_title     = ' &raquo; ' . $_LANG['rma_message'];
    $Arr['seo_title'] = $_LANG['my_account'] . ' -  ' . $_LANG['rma_message'] . '  - '.$_CFG['shop_name'];
    $Arr['nav_title']  = $nav_title;
    $Arr['action'] = $_ACT;
    $Arr['rma_order_id'] = $id;
    $_ACT = 'users_inc';
}
elseif ($_ACT == 'rma_msg_submit') {//提交留言
    !isset($_SERVER['HTTP_REFERER']) && exit('Access Denied!');
    $referer     = $_SERVER['HTTP_REFERER'];
    $rma_number  = isset($_POST['rma_number']) ? $_POST['rma_number'] : '';
    $id          = isset($_POST['rma_order_id']) ? intval($_POST['rma_order_id']) : 0;
    $count       = $db->count_info(RMA_ORDER, 'id', "id={$id} AND rma_number='{$rma_number}'");

    (!$id || empty($count)) && show_message('Submit Failed', array('Go Back'), array($referer));

    $content = isset($_POST['content']) ? $_POST['content'] : 0;

    !$content && show_message($_LANG['rma_enter_content'], array('Go Back'), array($referer));

    $date = local_date('Y-m-d H:i:s');
    $time = gmtime();

    if ($db->insert(RMA_MSG, 'user_id,rma_order_id,add_date,add_time,content,is_read', "{$user_id},{$id},'{$date}',{$time},'{$content}',1")) {
        $_SESSION['rma_msg'] = 'Submit Successfully!';
        header("Location: ".DOMAIN_USER."/". $cur_lang_url ."m-users-a-rma_msg-id-{$id}.htm");
    }
    else {
        show_message($_LANG['SUBMIT_FAILED'], array('Go Back'), array($referer));
    }
    exit();
}
elseif ($_ACT == 'rma_download_address') {//下载退货地址 by mashanling on 2012-08-25 15:15:44
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id && RMA::checkRMAIsReturned($id)) {
        download_file(ROOT_PATH . 'uploads/sample/Return_document.docx');
    }

    exit();
}
/* 更新订单的付款IP地址 */
elseif ($_ACT == 'act_orderip')
{
    $order_sn = isset($_POST['order_sn']) ? trim($_POST['order_sn']) : '';
	$item_number = isset($_POST['item_number']) ? trim($_POST['item_number']) : '';
	//$orderid = isset($_POST['orderid']) ? trim($_POST['orderid']) : '';
	if(empty($order_sn) || $order_sn == 'undefined'){
		if(!empty($item_number) && $item_number != 'undefined'){
			$sql = "update ".ORDERINFO." set pay_ip='".real_ip()."' where user_id='".$_SESSION['user_id']."' and order_sn='".$item_number."'";
			$db->query($sql);
		}
	}else{
		$order_sn = str_replace('Order:#', '', $order_sn);
		$sql = "update ".ORDERINFO." set pay_ip='".real_ip()."' where user_id='".$_SESSION['user_id']."' and order_sn='".$order_sn."'";
		$db->query($sql);
	}
	exit();
}
elseif ($_ACT == 'rma_tracking_number_submit') {//跟踪号
    $tracking_number = isset($_POST['tracking_number']) ? trim($_POST['tracking_number']) : '';
    $rma_order_id    = isset($_POST['rma_order_id']) ? intval($_POST['rma_order_id']) : 0;

    if ($tracking_number && $rma_order_id && RMA::checkRMAIsReturned($rma_order_id)) {
        if ($db->query('REPLACE INTO ' . RMA_TRACKING_NUMBER . " VALUE({$rma_order_id},0,'{$tracking_number}')")) {
            exit(json_encode(array('success' => true)));
        }
    }

    exit();
}

elseif ($_ACT == 'reserved_points') {//年底保留积分
	if (!$db->getOne('SELECT user_id FROM ' . USERS_INFO . " WHERE user_id={$_SESSION['user_id']}")) {
	    $db->autoExecute(USERS_INFO, array('user_id' => $_SESSION['user_id'], 'is_reserved_points' => 1));
	}
	else {
	    $db->autoExecute(USERS_INFO, array('is_reserved_points' => 1), 'update', 'user_id=' . $_SESSION['user_id']);
	}
    show_message($_LANG['your_points_have'], array('Return to get double DM Points', 'Return to Home'), array('/m-promotion-active-183_184_185_186.html', '/'),'success');
}

//重置密码 by mashanling on 2014-01-11 09:53:35
elseif('reset_password' == $_ACT) {
    $step   = isset($_GET['step']) ? intval($_GET['step']) : 1;
    $Arr['step'] = $step;

    if (2 == $step) {//验证邮箱并发送邮件
        $email      = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';
        $verifycode = isset($_POST['verifycode']) && is_string($_POST['verifycode']) ? trim($_POST['verifycode']) : '';

        if (!is_email($email)) {
            $error = sprintf($_LANG['email_invalid'], $email);
        }
        elseif (md5($verifycode) != $_SESSION['verify']) {
            $error = $_LANG['Verification_code_you_entered_is_incorrect'];
            $_SESSION['verify'] = null;
        }
        elseif (!$user_info = $db->selectInfo('SELECT user_id,firstname FROM ' . USERS . " WHERE email='" . $email . "'")) {
            $error = $_LANG['username_no_email'];
        }

        $_SESSION['verify'] = null;

        if (empty($user_info)) {
            show_message($error , array($_LANG['Return_to_the_previous_page'],$_LANG['Return_to_Home']), array($_SERVER['HTTP_REFERER'], '/'), 'warning');
        }

        require(ROOT_PATH . 'eload_admin/email_temp/mail_conf.php');  //取得模版标题

        $user_domain    = DOMAIN_USER . '/';
        $email_temp_id  = 52;
        $mail_subject   = $mail_conf[$cur_lang][$email_temp_id];
        $verifycode     = md5(rand_string(16)) . '_' . gmtime();
		if(empty($user_info['firstname'])) {
			$firstname = $_LANG['my_friend'];
		} else {
			$firstname = $user_info['firstname'];
		}
        $Tpl->assign(array(
            'email'     => $email,
            'firstname' => $firstname,
            'url'       => $user_domain . $cur_lang_url . 'm-users-a-reset_password-step-3-code-' . md5($verifycode) . $user_info['user_id'] . '.html',
        ));
        $mail_body      = $Tpl->fetch(ROOT_PATH . 'eload_admin/email_temp/' . $cur_lang . '/' . $email_temp_id . '.html');

        $pos = strpos($email, '@');
        $Tpl->assign('email', substr_replace($email, str_repeat('*', $pos - 2), 1, $pos - 2));//axxxxxxb@abc.com

        if (exec_send2($email, $mail_subject, $mail_body)) {
            $db->update(USERS, "reset_password_verifycode='{$verifycode}'", 'user_id=' . $user_info['user_id']);

        }
        else {
            show_message($_LANG['fail_send_password'] , array($_LANG['Return_to_the_previous_page'],$_LANG['Return_to_Home']), array($_SERVER['HTTP_REFERER'], '/'), 'warning');
        }
    }//end step2
    elseif (3 == $step) {//验证从邮箱中点击过来链接
        $verifycode = isset($_GET['code']) && is_string($_GET['code']) ? stripslashes(trim($_GET['code'])) : '';

        if (!$verifycode) {
            show_message($_LANG['verifycode_incorrect'], array($_LANG['Return_to_the_previous_page'],$_LANG['Return_to_Home']), array($_SERVER['HTTP_REFERER'], '/'), 'warning');
        }

        //前32位验证码，之后为用户id
        $code       = substr($verifycode, 0, 32);
        $user_id    = substr($verifycode, 32);
        $field      = 'reset_password_verifycode';

        if (!$user_info = $db->selectInfo($sql = 'SELECT user_id,email FROM ' . USERS . " WHERE user_id=" . intval($user_id) . " AND md5({$field})='" . addslashes($code) . "'")) {
            show_message($_LANG['verifycode_incorrect'], array($_LANG['Return_to_the_previous_page'],$_LANG['Return_to_Home']), array($_SERVER['HTTP_REFERER'], '/'), 'warning');
        }
        else {
            $_SESSION[$field] = array($verifycode, strtolower($user_info['email']));
            $Arr['verifycode'] = $verifycode;
        }
    }
    elseif (4 == $step) {//修改密码
        $email              = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';
        $password           = isset($_POST['password']) && is_string($_POST['password']) ? trim($_POST['password']) : '';
        $confirm_password   = isset($_POST['confirm_password']) && is_string($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
        $verifycode         = isset($_POST['verifycode']) && is_string($_POST['verifycode']) ? trim($_POST['verifycode']) : '';

        if (!is_email($email)) {
            show_message(sprintf($_LANG['email_invalid'], $email));
        }
        elseif (strlen($password) < 6) {
            show_message($_LANG['passport_js']['password_shorter']);
        }
        elseif ($password != $confirm_password) {
            show_message($_LANG['passport_js']['confirm_password_invalid']);
        }
        elseif (!$verifycode || !isset($_SESSION[$field = 'reset_password_verifycode']) || $verifycode != $_SESSION[$field][0] || strtolower($email) != $_SESSION[$field][1]) {
            show_message($_LANG['verifycode_unmatch_email'], array($_LANG['Return_to_the_previous_page'],$_LANG['Return_to_Home']), array($_SERVER['HTTP_REFERER'], '/'), 'warning');
        }

        $password = substr(md5($password), 8, 16);//A密码取最后16位
        $db->update(USERS, "{$field}='', password='{$password}'", 'user_id=' . intval(substr($verifycode, 32)));
        $_SESSION[$field] = null;
        show_message($_LANG['edit_password_success'], $_LANG['resign_lnk'], 'm-users-a-sign.htm', 'success');
    }
	$Arr['seo_title'] = $_LANG_SEO['reset_password']['title'];
	$Arr['seo_keywords'] = $_LANG_SEO['reset_password']['keywords'];
	$Arr['seo_description'] = $_LANG_SEO['reset_password']['description'];
}//end reset_password

if($_ACT == 'join'){
   $_ACT = 'sign';
}

/*用户中心里订单支付*/
elseif($_ACT == 'payment_order')
{
    include_once(ROOT_PATH . 'lib/lib.f.order.php');
    global $API_Endpoint, $version, $API_UserName, $API_Password, $API_Signature,$PAYPAL_URL;
    global $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
    global $gv_ApiErrorURL;
    global $sBNCode;
    $order_sn = empty($_GET['order_sn'])?'':$_GET['order_sn'];
    $pay_step = 'checkout';
    if (!empty($order_sn)){
        $sql = "SELECT order_id ,order_sn, goods_amount , order_amount, shipping_fee, insure_fee, Need_Traking_number, city, province, country, point_money FROM ".ORDERINFO." WHERE order_sn = '".$order_sn."' ";
        $order = $db->selectinfo($sql);//print_r($order);
    }
    $area_list = area_list();
    $order['country_str'] = $area_list[$order['country_id']]['region_name'];
    $order['country_code'] = $area_list[$order['country_id']]['region_code'];
    $cart_goods['goods_list']=order_goods($order['order_id']);//print_r($cart_goods);
    $finalPaymentAmount = $order['order_amount'] ;
    $_SESSION["Payment_Amount"] = $finalPaymentAmount ;
    $_SESSION["orderno"] = $order['order_sn'] ;
    require_once ("expresscheckout.php");
    exit;
}

$_MDL = $_ACT;

/*
 * 获取用户中心推荐产品
 */
function user_recommend_product($sum=8) {
    require_once(ROOT_PATH . 'lib/sphinxapi.php');
    global $db;
    $goods_info = array();
    $arr = array();
    if (!empty($_COOKIE['browserHistories'])) {
        $history = explode('EOT', $_COOKIE['browserHistories']);
        $goods_id = array();
        foreach($history as $row) {
            if(count($goods_id)<2) {
                preg_match('/.+-p-(\d+)\.htm|product-(\d+)\.htm|/',$row,$contents);
                if(isset($contents[2])||isset($contents[1])){
                  $goods_id[] = !empty($contents[2])?$contents[2]:$contents[1];
                }
            }
        }
        if(!empty($goods_id)) {
            $goods_id = array_filter($goods_id);
            $cat_id = $db->arrQuery("select cat_id from ".GOODS." where goods_id in (".implode(',',$goods_id).")");
            foreach($cat_id as $val) {
                $category[] = $val['cat_id'];
            }
        }
    }
    $data = array();
    if(!empty($category)) {
		if($category[0] == $category[1]) {
            $sql = 'SELECT s.is_24h_ship,goods_number,is_on_sale,g.goods_id,goods_title,market_price,shop_price,cat_id,url_title,promote_price,goods_thumb,goods_grid,goods_img,is_promote FROM ' . GOODS . " g left join ".GOODS_STATE." s on g.goods_id=s.goods_id  WHERE  cat_id = ".$category[0]." order by week2sale desc limit 8";
            $data = $db->arrQuery($sql);
		} else {
			$count = $sum/count($category);
			$limit = " limit ".$count;
			foreach($category as $val) {
				$sql = 'SELECT s.is_24h_ship,goods_number,is_on_sale,g.goods_id,goods_title,market_price,shop_price,cat_id,url_title,promote_price,goods_thumb,goods_grid,goods_img,is_promote FROM ' . GOODS . " g left join ".GOODS_STATE." s on g.goods_id=s.goods_id  WHERE  cat_id = ".$val." order by week2sale desc ".$limit;
				$goods_info = $db->arrQuery($sql);
				$data = array_merge($data,$goods_info);
			}
		}

    }else {
            $times = gmtime()-60*24*3600; //两个月内添加的产品
            $sql = 'SELECT s.is_24h_ship,goods_number,is_on_sale,g.goods_id,goods_title,market_price,shop_price,cat_id,url_title,promote_price,goods_thumb,goods_grid,goods_img,is_promote FROM ' . GOODS . " g left join ".GOODS_STATE." s on g.goods_id=s.goods_id where add_time >'".$times."' order by week2sale desc limit 200";
            $goods_info = $db->arrQuery($sql);
            shuffle($goods_info);
            $data = array_slice($goods_info,0,8);

    }
    foreach($data as $row) {
        $goods_id = $row['goods_id'];
        $promote_price = $row['promote_price'];
        $arr[$goods_id]['goods_thumb']     = get_image_path($goods_id, $row['goods_thumb'], true);
        $arr[$goods_id]['goods_img']       = get_image_path($goods_id, $row['goods_img']);
        $arr[$goods_id]['goods_grid']      = get_image_path($goods_id, $row['goods_grid']);
        $arr[$goods_id]['url_title']       = get_details_link($goods_id, $row['url_title']);
        $arr[$goods_id]['goods_title']     = $row['goods_title'];
        $arr[$goods_id]['shop_price']      = ($promote_price > 0 && $row['is_promote']) ? price_format($promote_price) : price_format($row['shop_price']);
    }
    return  $arr;
}

/*
 * 剪裁头像
 * $thumb_image_name 新图片名称
 * $image            原始图片名称
 * $width            宽度
 * $height           高度
 * $start_width     开始的x位置
 * $start_height    开始的y位置
 * $scale           比例
 */
function resizeThumbnailImage($thumb_image_name, $image, $width, $height, $start_width, $start_height, $scale) {
    list($imagewidth, $imageheight, $imageType) = getimagesize($image);
    $imageType = image_type_to_mime_type($imageType);

    $newImageWidth = ceil($width * $scale);
    $newImageHeight = ceil($height * $scale);
    $newImage = imagecreatetruecolor($newImageWidth,$newImageHeight);
    switch($imageType) {
        case "image/gif":
            $source=imagecreatefromgif($image);
            break;
        case "image/pjpeg":
        case "image/jpeg":
        case "image/jpg":
            $source=imagecreatefromjpeg($image);
            break;
        case "image/png":
        case "image/x-png":
            $source=imagecreatefrompng($image);
            break;
    }
    imagecopyresampled($newImage,$source,0,0,$start_width,$start_height,$newImageWidth,$newImageHeight,$width,$height);
    switch($imageType) {
        case "image/gif":
            imagegif($newImage,$thumb_image_name);
            break;
        case "image/pjpeg":
        case "image/jpeg":
        case "image/jpg":
            imagejpeg($newImage,$thumb_image_name,90);
            break;
        case "image/png":
        case "image/x-png":
            imagepng($newImage,$thumb_image_name);
            break;
    }
    chmod($thumb_image_name, 0777);
    return $thumb_image_name;
}

?>
