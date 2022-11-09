<?php
define('INI_WEB', true);
require_once('lib/global.php');
require_once('lib/time.fun.php');
$email     = $_POST["FTextUserEmail"];
$firstname = $_POST["firstname"];
$lastname  = $_POST["lastname"];
$actions   = $_POST["actions"];
$Pwd2      = $_POST["FTextUserPwd2"];
if($actions=="facebookLogin" && $email!="")
{
      $sql   = 'SELECT user_id,user_rank,firstname,lastname,user_type,email FROM ' . USERS. " WHERE email='$email'";
      $row   = $db->selectInfo($sql);
      if($row['user_id'])//直接登录
      {
            //当前没有登录
			if(empty($_SESSION['user_id'])){
				ChangeSessId();
				update_user_info();
				require_once(ROOT_PATH . 'lib/modules/ipb.php');
				$user = new ipb($db);
				$email = $row['email'];
				$user->set_session($email);
				$user->set_cookie($email);
			}
      }
      else //如果还没有注册，那么自动注册
      {
        /* 插入数据到users表 */
        $Pwd3=substr(md5($Pwd2),8,16);
        $sql = "INSERT INTO ".USERS." (firstname,lastname,password,email,user_type,reg_time,last_ip)
                VALUES ('$firstname','$lastname','$Pwd3', '$email', '2', " . gmtime() . ",'" .  real_ip() . "')";
        $result = $db->query($sql);
		if ($result)
		{
			$user_id = mysql_insert_id();
            $_SESSION['user_id']   = $user_id;
            $_SESSION['email']     = $email;
			$_SESSION['jointimes'] = time();
			ChangeSessId();
			update_user_info();
			$note = "Register successfully and get 10 points";
			add_point($user_id,10,2,$note);
            $hot_goods=get_hot_goods1();
            require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');  //取得模版标题			
			//获得用户信息
			$sql       = "SELECT email, firstname, lang FROM " . USERS . " WHERE user_id = " . $user_id;
			$user_info =  $db->selectinfo($sql);
			$lang      = $user_info['lang'];
			if(!empty($lang)) {
				$mail_subject = $mail_conf[$lang][33];
			} 
			if(empty($mail_subject)) {
				$mail_subject = $mail_conf['en'][33];
			}						
            $Tpl->assign("email",$email);
            $Tpl->assign("pwd",$Pwd2);
			if(empty($firstname)) {
				$firstname     = $_LANG['my_friend'];
				if(empty($firstname)) $firstname ='my friend';				
			}			
            $Tpl->assign("firstname",$firstname);
            $Tpl->assign("lastname",$lastname);
            $Tpl->assign("hot_goods",$hot_goods);
			if(!empty($lang)) {
				$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/'. $lang .'/33.html');		
			} 
			if(empty($mail_body)) {
				$mail_body    = $Tpl->fetch(ROOT_PATH.'eload_admin/email_temp/en/33.html');		
			}			
            $result = exec_send($email,$mail_subject,$mail_body);
        }
      }

}

/**
 * 获得商品详细页中热销商品
 */
function get_hot_goods1()
{
    $sql = 'SELECT goods_id  FROM eload_goods where is_on_sale = 1 AND is_delete = 0 ORDER BY week2sale desc limit 4';
    $result = $GLOBALS['db']->arrQuery($sql);
    shuffle($result);//数组打乱
    $n=count($result);
    $result_str='';
    for( $i = 0; $i < $n; $i ++ )
    {
       $result_str.=$result[$i]['goods_id'].',';
    }
    $result_str=substr($result_str,0,-1);

    $sql = 'SELECT goods_id,cat_id, goods_title,goods_name_style,shop_price,goods_thumb,goods_grid,sort_order,promote_price,promote_start_date,promote_end_date,url_title ' .
          ' FROM ' . GOODS . ' AS g ' .
          " WHERE goods_id IN ($result_str) ORDER BY FIND_IN_SET(goods_id,'$result_str') LIMIT 8";

    $goods_res = $GLOBALS['db']->arrQuery($sql);
    $arr = array();
    foreach ($goods_res as $row){

        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        }
        else
        {
            $promote_price = 0;
        }

        $arr[$row['goods_id']]['goods_title']      = $row['goods_title'];
        $arr[$row['goods_id']]['cat_id']           = $row['cat_id'];
        $arr[$row['goods_id']]['short_name']       = sub_str($row['goods_title'],50);
        $arr[$row['goods_id']]['goods_grid']       = get_image_path($row['goods_id'],$row['goods_grid'], true);
        $arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_title'],$row['goods_name_style']);
        $arr[$row['goods_id']]['shop_price']       = ($promote_price>0)?price_format($promote_price):price_format($row['shop_price']);
        $arr[$row['goods_id']]['url_title']        = get_details_link($row['goods_id'],$row['url_title']);
    }
    return  $arr;
}
?>