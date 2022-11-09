<?php
if (!defined('INI_WEB')){die('访问拒绝');}

class ipb
{
    var $charset        = 'UTF8';
    var $user_table     = USERS;
    var $field_id       = 'user_id';
    var $field_name     = 'email';
    var $field_pass     = 'password';
    var $field_email    = 'email';
    var $field_gender   = 'sex';
    var $field_reg_date = 'reg_time';
    var $field_firstname = 'firstname';
    var $field_lastname = 'lastname';
    var $field_paypal_account = 'paypal_account';
    var $field_introduction = 'introduction';
    var $field_user_type = 'user_type';
    var $field_admin_note = 'admin_note';

    var $field_affiliates_apply_time = 'affiliates_apply_time';
	var $field_affiliates_pass_time = 'affiliates_pass_time';
	var $field_bbs_id = 'bbs_id';
    var $field_bbs_profile = 'bbs_profile';	

    var $error          = 0;
    var $db;
	
    var $cookie_domain  = COOKIESDIAMON;

    var $cookie_path    = '/';
    /*------------------------------------------------------ */
    function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function ipb($cfg)
    {
        $this->field_id = 'id';
		$this->field_id = 'affiliates_apply_time';
		$this->field_id = 'affiliates_pass_time';
        $this->field_name = 'email';
        $this->field_admin_note = 'admin_note';
        $this->field_gender = 'NULL';
        $this->field_bday = 'NULL';
        $this->field_firstname = 'firstname';
        $this->field_lastname = 'lastname';
        $this->field_pass = 'NULL';
        $this->field_reg_date = 'joined';
        $this->user_table = 'members';
		$this->user_table = 'members';
		$this->user_table = 'paypal_account';  //用户paypal账号
		$this->user_table = 'introduction';    //用户自我简介
		$this->user_table = 'bbs_profile';    //用户自我简介
		$this->user_table = 'bbs_id';    //用户自我简介
		$this->user_type = 'user_type';        //网站推广的用户的类型  ,0 为未申请,1为未通过,7为已通过
        /* 检查数据表是否存在 */
        $sql = "SHOW TABLES LIKE '" . $this->prefix . "%'";

        $exist_tables = $this->db->getCol($sql);

        if (empty($exist_tables) || (!in_array($this->prefix.$this->user_table, $exist_tables)))
        {
            $this->error = 2;
            /* 缺少数据表 */
            return false;
        }
    }

    /**
     *  检查指定用户是否存在及密码是否正确
     *
     * @access  public
     * @param   string  $email   用户名
     *
     * @return  int
     */
    function check_user($email, $password = null)
    {
		$post_email = $email;
        if ($password === null)
        {

		$sql = "SELECT " . $this->field_id .
			   " FROM " . $this->user_table.
			   " WHERE " . $this->field_name . "='" . $post_email . "'";

		return $this->db->getOne($sql);
		
		}
		else
        {
            $sql = "SELECT " . $this->field_id .
                   " FROM " . $this->user_table.
                   " WHERE " . $this->field_name . "='" . $post_email . "' AND " . $this->field_pass . " ='" . $this->compile_password(array('password'=>$password)) . "'";
            return  $this->db->getOne($sql);
        }
		
    }

    /**
     *  添加一个新用户
     *
     * @access  public
     * @param
     *
     * @return int
     */
    function add_user($email, $password,  $gender = -1, $bday = 0, $reg_date=0, $md5password='')
    {
        if ($this->check_user($email) > 0)
        {
            $this->error = ERR_USERNAME_EXISTS;

            return false;
        }
        /* 检查email是否重复 */
        $sql = "SELECT " . $this->field_id .
               " FROM " . $this->user_table.
               " WHERE " . $this->field_email . " = '$email'";
        if ($this->db->getOne($sql, true) > 0)
        {
            $this->error = ERR_EMAIL_EXISTS;

            return false;
        }

        if ($this->charset != 'UTF8')
        {
            $post_email = ecs_iconv('UTF8', $this->charset, $email);
        }
        else
        {
            $post_email = $email;
        }
		
		$password = $this->compile_password(array('password'=>$password));
        /* 插入数据到users表 */
        $sql = "INSERT INTO ".$this->user_table." ( `password`, `email`, `reg_time`,`last_ip`)
                VALUES ( '$password', '$email', " . gmtime() . ",'" .  real_ip() . "')";
		
        $result = $this->db->query($sql);

        return true;
   }
   
    /**
     * 删除用户
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function remove_user($id)
    {
        $post_id = $id;

            $sql = "SELECT user_id FROM "  . USERS . " WHERE ";
            $sql .= (is_array($post_id)) ? db_create_in($post_id, 'email') : "email='". $post_id . "' LIMIT 1";
            $col = $this->db->getCol($sql);

            if ($col)
            {
                $sql = "DELETE FROM " . USERS . " WHERE " . db_create_in($col, 'user_id'); //删除用户
                $this->db->query($sql);
                /* 删除用户订单 */
                $sql = "SELECT order_id FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE " . db_create_in($col, 'user_id');
                $this->db->query($sql);
                $col_order_id = $this->db->getCol($sql);
                if ($col_order_id)
                {
                    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE " . db_create_in($col_order_id, 'order_id');
                    $this->db->query($sql);
                    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('order_goods') . " WHERE " . db_create_in($col_order_id, 'order_id');
                    $this->db->query($sql);
                }

                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('booking_goods') . " WHERE " . db_create_in($col, 'user_id'); //删除用户
                $this->db->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('collect_goods') . " WHERE " . db_create_in($col, 'user_id'); //删除会员收藏商品
                $this->db->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('feedback') . " WHERE " . db_create_in($col, 'user_id'); //删除用户留言
                $this->db->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('user_address') . " WHERE " . db_create_in($col, 'user_id'); //删除用户地址
                $this->db->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('user_account') . " WHERE " . db_create_in($col, 'user_id'); //删除用户帐号金额
                $this->db->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('tag') . " WHERE " . db_create_in($col, 'user_id'); //删除用户标记
                $this->db->query($sql);
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('account_log') . " WHERE " . db_create_in($col, 'user_id'); //删除用户日志
                $this->db->query($sql);
            }

    }
	
	
	
    /**
     *  编辑用户信息($password, $email, $gender, $bday)
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function edit_user($cfg)
    {
        if (empty($cfg['email']))
        {
            return false;
        }
        else
        {
            $cfg['post_email'] = $cfg['email'];

        }

        $values = array();
        if (!empty($cfg['password']) && empty($cfg['md5password']))
        {
            $cfg['md5password'] = substr(md5($cfg['password']),8,16);//md5($cfg['password']);
        }
        if ((!empty($cfg['md5password'])) && $this->field_pass != 'NULL')
        {
            $values[] = $this->field_pass . "='" . $this->compile_password(array('md5password'=>$cfg['md5password'])) . "'";
        }

        if ((!empty($cfg['email'])) && $this->field_email != 'NULL')
        {
            /* 检查email是否重复 */
            $sql = "SELECT " . $this->field_id .
                   " FROM " . $this->user_table.
                   " WHERE " . $this->field_email . " = '$cfg[email]' ".
                   " AND " . $this->field_name . " != '$cfg[post_email]'";
            if ($this->db->getOne($sql, true) > 0)
            {
                $this->error = ERR_EMAIL_EXISTS;

                return false;
            }
            // 检查是否为新E-mail
            $sql = "SELECT count(*)" .
                   " FROM " . $this->user_table.
                   " WHERE " . $this->field_email . " = '$cfg[email]' ";
            if($this->db->getOne($sql, true) == 0)
            {
                // 新的E-mail
                $sql = "UPDATE " . $this->user_table . " SET is_validated = 0 WHERE email = '$cfg[post_email]'";
                $this->db->query($sql);
            }
            $values[] = $this->field_email . "='". $cfg['email'] . "'";
        }

        if (isset($cfg['gender']) && $this->field_gender != 'NULL')
        {
            $values[] = $this->field_gender . "='" . $cfg['gender'] . "'";
        }

        if ((!empty($cfg['firstname'])) && $this->field_firstname != 'NULL')
        {
            $values[] = $this->field_firstname . "='" . $cfg['firstname'] . "'";
        }
        if ((!empty($cfg['lastname'])) && $this->field_lastname != 'NULL')
        {
            $values[] = $this->field_lastname . "='" . $cfg['lastname'] . "'";
        }
        
        if ((!empty($cfg['introduction'])) && $this->field_introduction != 'NULL')
        {
            $values[] = $this->field_introduction . "='" . $cfg['introduction'] . "'";
        }
        if ((!empty($cfg['paypal_account'])) && $this->field_paypal_account != 'NULL')
        {
            $values[] = $this->field_paypal_account . "='" . $cfg['paypal_account'] . "'";
        }
        if ((!empty($cfg['bbs_profile'])) && $this->field_bbs_profile != 'NULL')
        {
            $values[] = $this->field_bbs_profile . "='" . $cfg['bbs_profile'] . "'";
        }
        if ((!empty($cfg['bbs_id'])) && $this->field_bbs_id != 'NULL')
        {
            $values[] = $this->field_bbs_id . "='" . $cfg['bbs_id'] . "'";
        }		
        if ((!empty($cfg['user_type'])) && $this->field_user_type != 'NULL')
        {
            $values[] = $this->field_user_type . "='" . $cfg['user_type'] . "'";
        }
        if ((!empty($cfg['admin_note'])) && $this->field_user_type != 'NULL')
        {
            $values[] = $this->field_admin_note . "='" . $cfg['admin_note'] . "'";
        }
        if ((!empty($cfg['affiliates_apply_time'])) && $this->field_affiliates_apply_time != 'NULL')
        {
            $values[] = $this->field_affiliates_apply_time . "='" . $cfg['affiliates_apply_time'] . "'";
        }
        if ((!empty($cfg['affiliates_pass_time'])) && $this->field_affiliates_pass_time != 'NULL')
        {
            $values[] = $this->field_affiliates_pass_time . "='" . $cfg['affiliates_pass_time'] . "'";
        }				
		
        if ($values)
        {
            $sql = "UPDATE " . $this->user_table.
                   " SET " . implode(', ', $values).
                   " WHERE " . $this->field_name . "='" . $cfg['post_email'] . "' LIMIT 1";

            $this->db->query($sql);
        }

        return true;
    }
	
    /**
     *  检查指定邮箱是否存在
     *
     * @access  public
     * @param   string  $email   用户邮箱
     *
     * @return  boolean
     */
    function check_email($email)
    {
        if (!empty($email))
        {
          /* 检查email是否重复 */
            $sql = "SELECT " . $this->field_id .
                       " FROM " . $this->user_table.
                       " WHERE " . $this->field_email . " = '$email' ";
            if ($this->db->getOne($sql, true) > 0)
            {
                return true;
            }
            return false;
        }
    }
	
	
	
	
	
    /**
     *  设置cookie
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function set_cookie($email='')
    {
        if (empty($email))
        {
            /* 摧毁cookie */
            $time = time() - 3600;
            setcookie('WEBF-user_id',  '', $time, $this->cookie_path, $this->cookie_domain);
            setcookie('WEBF-email', '', $time, $this->cookie_path, $this->cookie_domain);
           // setcookie('WEBF-dan_num', '', $time, $this->cookie_path, $this->cookie_domain);
            setcookie('WEBF-firstname', '', $time, $this->cookie_path, $this->cookie_domain);
            setcookie('WEBF-lastname', '', $time, $this->cookie_path, $this->cookie_domain);
            setcookie('PHPSESSID', '', $time, $this->cookie_path, $this->cookie_domain);
            setcookie('usertype', '', $time, $this->cookie_path, $this->cookie_domain);
            
        }
        else
        {
            /* 设置cookie */
            $time = time() - date('Z'); //time() + 3600 * 24 * 30;

            setcookie("WEBF-email", $email, $time, $this->cookie_path, $this->cookie_domain);
            $sql = "SELECT user_id, password,firstname,lastname,user_type FROM " . USERS . " WHERE email='$email' LIMIT 1";
            $row = $GLOBALS['db']->selectinfo($sql);
            if ($row)
            {
                setcookie("WEBF-user_id", $row['user_id'], $time, $this->cookie_path, $this->cookie_domain);
                setcookie("WEBF-firstname", $row['firstname'], $time, $this->cookie_path, $this->cookie_domain);
                setcookie("WEBF-lastname", $row['lastname'], $time, $this->cookie_path, $this->cookie_domain);
				setcookie("usertype", $row['user_type'], $time, $this->cookie_path, $this->cookie_domain); //判断是否为广告联盟用户
				
				$sql = "select count(*) from ".ORDERINFO." where user_id='".$row['user_id']."'  AND order_status > 0 and order_status < 9 ";

				$dan_num = $GLOBALS['db']->getOne($sql);

				setcookie("WEBF-dan_num", $dan_num, $time+3600*24*30*12,$this->cookie_path, $this->cookie_domain);
				//file_put_contents(dirname(__FILE__).'/record_user.txt',$this->cookie_domain);
				//echo dirname(__FILE__);
				//exit;
				// setcookie("WEBF[password]", $row['password'], $time, $this->cookie_path, $this->cookie_domain);
            }
        }
    }

    /**
     *  设置指定用户SESSION
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function set_session ($email='')
    {
        if (empty($email))
        {
                $_SESSION['user_id']   = NULL;
                $_SESSION['email']     = NULL;
                $_SESSION['firstname']     = NULL;
                if (isset($_SESSION['flow_consignee'])) {
                    unset($_SESSION['flow_consignee']);
                }
        }
        else
        {
            $sql = "SELECT user_id, password, email FROM " . USERS . " WHERE email='$email' LIMIT 1";
            $row = $GLOBALS['db']->selectinfo($sql);

            if ($row)
            {
                $_SESSION['user_id']   = $row['user_id'];
                $_SESSION['email']     = $row['email'];
            }
        }
    }
	
	
    function logout ()
    {
        $this->set_cookie(); //清除cookie
        $this->set_session(); //清除session
    }




    /**
     *  编译密码函数
     *
     * @access  public
     * @param   array   $cfg 包含参数为 $password, $md5password, $salt, $type
     *
     * @return void
     */
    function compile_password ($cfg)
    {
       if (isset($cfg['password']))
       {
            $cfg['md5password'] = substr(md5($cfg['password']),8,16);//md5($cfg['password']);
       }
       if (empty($cfg['type']))
       {
            $cfg['type'] = 1;
       }
       return $cfg['md5password'];

    }


	
 /**
     *  用户登录函数
     *
     * @access  public
     * @param   string  $username
     * @param   string  $password
     *
     * @return void
     */
    function login($username, $password)
    {
    	if(empty($password)){
            return false;
        }
        if ($this->check_user($username, $password) > 0)
        {
            $this->set_session($username);
            $this->set_cookie($username);

            return true;
        }
        else
        {
            return false;
        }
    }}?>