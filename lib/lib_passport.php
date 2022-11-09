<?php
/**
 * 用户帐号相关函数库
*/

if (!defined('INI_WEB')){die('访问拒绝');}

/**
 * 用户注册，登录函数
 *
 * @access  public
 * @param   string       $email          注册用户名
 * @param   string       $password          用户密码
 * @param   string       $email             注册email
 * @param   array        $other             注册的其他信息
 *
 * @return  bool         $bool
 */
function register( $email,$password)
{
	global $db;
	$user = new ipb($db);
	$err = '';
    /* 检查email */
    if (empty($email))
    {
        $err .= 'email is empty';
    }
    else
    {
        if (!is_email($email))
        {
           $err .= htmlspecialchars($email) .' is invalid';
        }
    }

    if ($err != '')
    {
        return false;
    }

    if (!$user->add_user($email, $password, $email))
    {
        if ($user->error == ERR_USERNAME_EXISTS)
        {
            $err .= $email.' email exist';
        }
        else
        {
            $err .= 'UNKNOWN ERROR!' ;
        }
		if ($err != '')
		{
			echo $err;
		}
        return false;
    }
    else
    {
		
		//广告联盟的推广ID
		$wj_linkid  = empty($_COOKIE['linkid'])?0:intval($_COOKIE['linkid']);	
		//echo "11:".$wj_linkid;
		//exit;
        $user->set_session($email);
        $user->set_cookie($email);					 
		if ($wj_linkid){
			$sql="update ".WJ_LINK." set reg_count=reg_count+1 where id='".$wj_linkid."'";
			 $GLOBALS['db']->query($sql);
			 
			$sql = "UPDATE " .USERS. " SET wj_linkid='".$wj_linkid."'  WHERE user_id = '" . $_SESSION['user_id'] . "'";
			$GLOBALS['db']->query($sql);
		}
	

        return true;
    }
}

/**
 *
 *
 * @access  public
 * @param
 *
 * @return void
 */
function logout()
{
/* todo */
}

/**
 *  将指定user_id的密码修改为new_password。可以通过旧密码和验证字串验证修改。
 *
 * @access  public
 * @param   int     $user_id        用户ID
 * @param   string  $new_password   用户新密码
 * @param   string  $old_password   用户旧密码
 * @param   string  $code           验证码（md5($user_id . md5($password))）
 *
 * @return  boolen  $bool
 */
function edit_password($user_id, $old_password, $new_password='', $code ='')
{
    if (empty($user_id)) $err->add($GLOBALS['_LANG']['not_login']);

    if ($user->edit_password($user_id, $old_password, $new_password, $code))
    {
        return true;
    }
    else
    {
        $err->add($GLOBALS['_LANG']['edit_password_failure']);

        return false;
    }
}

/**
 *  会员找回密码时，对输入的用户名和邮件地址匹配
 *
 * @access  public
 * @param   string  $user_name    用户帐号
 * @param   string  $email        用户Email
 *
 * @return  boolen
 */
function check_userinfo($user_name, $email)
{
    if (empty($user_name) || empty($email))
    {
        ecs_header("Location: user.php?act=get_password\n");

        exit;
    }

    /* 检测用户名和邮件地址是否匹配 */
    $user_info = $user->check_pwd_info($user_name, $email);
    if (!empty($user_info))
    {
        return $user_info;
    }
    else
    {
        return false;
    }
}

/**
 *  用户进行密码找回操作时，发送一封确认邮件
 *
 * @access  public
 * @param   string  $uid          用户ID
 * @param   string  $user_name    用户帐号
 * @param   string  $email        用户Email
 * @param   string  $code         key
 *
 * @return  boolen  $result;
 */
function send_pwd_email($uid, $user_name, $email, $code)
{
    if (empty($uid) || empty($user_name) || empty($email) || empty($code))
    {
        ecs_header("Location: user.php?act=get_password\n");

        exit;
    }

    /* 设置重置邮件模板所需要的内容信息 */
    $template    = get_mail_template('send_password');
    $reset_email = $GLOBALS['ecs']->url() . 'user.php?act=get_password&uid=' . $uid . '&code=' . $code;

    $GLOBALS['smarty']->assign('user_name',   $user_name);
    $GLOBALS['smarty']->assign('reset_email', $reset_email);
    $GLOBALS['smarty']->assign('shop_name',   $GLOBALS['_CFG']['shop_name']);
    $GLOBALS['smarty']->assign('send_date',   date('Y-m-d'));
    $GLOBALS['smarty']->assign('sent_date',   date('Y-m-d'));

    $content = $GLOBALS['smarty']->fetch('str:' . $template['template_content']);

    /* 发送确认重置密码的确认邮件 */
    if (send_mail($user_name, $email, $template['template_subject'], $content, $template['is_html']))
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 *  发送激活验证邮件
 *
 * @access  public
 * @param   int     $user_id        用户ID
 *
 * @return boolen
 */
function send_regiter_hash ($user_id)
{
    /* 设置验证邮件模板所需要的内容信息 */
    $template    = get_mail_template('register_validate');
    $hash = register_hash('encode', $user_id);
    $validate_email = $GLOBALS['ecs']->url() . 'user.php?act=validate_email&hash=' . $hash;

    $sql = "SELECT user_name, email FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$user_id'";
    $row = $GLOBALS['db']->getRow($sql);

    $GLOBALS['smarty']->assign('user_name',         $row['user_name']);
    $GLOBALS['smarty']->assign('validate_email',    $validate_email);
    $GLOBALS['smarty']->assign('shop_name',         $GLOBALS['_CFG']['shop_name']);
    $GLOBALS['smarty']->assign('send_date',         date($GLOBALS['_CFG']['date_format']));

    $content = $GLOBALS['smarty']->fetch('str:' . $template['template_content']);

    /* 发送确认重置密码的确认邮件 */
    if (send_mail($row['user_name'], $row['email'], $template['template_subject'], $content, $template['is_html']))
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 *  生成邮件验证hash
 *
 * @access  public
 * @param
 *
 * @return void
 */
function register_hash ($operation, $key)
{
    if ($operation == 'encode')
    {
        $user_id = intval($key);
        $sql = "SELECT reg_time ".
               " FROM " . $GLOBALS['ecs'] ->table('users').
               " WHERE user_id = '$user_id' LIMIT 1";
        $reg_time = $GLOBALS['db']->getOne($sql);

        $hash = substr(md5($user_id . $GLOBALS['_CFG']['hash_code'] . $reg_time), 16, 4);

        return base64_encode($user_id . ',' . $hash);
    }
    else
    {
        $hash = base64_decode(trim($key));
        $row = explode(',', $hash);
        if (count($row) != 2)
        {
            return 0;
        }
        $user_id = intval($row[0]);
        $salt = trim($row[1]);

        if ($user_id <= 0 || strlen($salt) != 4)
        {
            return 0;
        }

        $sql = "SELECT reg_time ".
               " FROM " . $GLOBALS['ecs'] ->table('users').
               " WHERE user_id = '$user_id' LIMIT 1";
        $reg_time = $GLOBALS['db']->getOne($sql);

        $pre_salt = substr(md5($user_id . $GLOBALS['_CFG']['hash_code'] . $reg_time), 16, 4);

        if ($pre_salt == $salt)
        {
            return $user_id;
        }
        else
        {
            return 0;
        }
    }
}

?>