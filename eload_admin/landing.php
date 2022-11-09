<?
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');   
require_once('libs/fun.admin.php');


//登陆次数限制
define('LIMITTIMES',50);
/* act操作项的初始化 */
$_ACT = 'login';
if (!empty($_GET['act'])) $_ACT = trim($_GET['act']);
/*
/+------------------------------------------------------
 *-- 验证登录
/+------------------------------------------------------
 */
//
//unset($_SESSION['login_times']);
$_SESSION['login_times'] = !empty($_SESSION['login_times'])?trim($_SESSION['login_times']):0;

if ($_ACT=='signin') {
	$_VAL = array();
	$userkey = '';
	$AUArr= array();
	$login_msg = "失败";
	$_SESSION["WebUserInfo"]["said"] = 0;
	
	//读取硬件信息
	$_VAL['username']        = !empty($_POST['username'])          ? trim($_POST['username'])  : '';
	$_VAL['password']        = !empty($_POST['password'])          ? trim($_POST['password'])  : '';
	$_VAL['verifyNo']        = !empty($_POST['verifyNo'])          ? trim($_POST['verifyNo'])  : '';
	$_VAL['verifyNo']        = implode(array_reverse(str_split($_VAL['verifyNo'],1))); //倒序验证码
    if ($_SESSION['login_times'] > LIMITTIMES){
		if ($_SESSION['verify'] != md5(trim($_VAL['verifyNo']))){
			$msg = '验证码错误!';
		   $_SESSION['login_times']++;
			echo $msg;
			exit();
		}
	}else{
	    unset($_VAL['verifyNo']);
	}
	
	foreach($_VAL as $k => $v){	if (($v=='')||(empty($v))) die(FEIFA); }
	
	//网卡信息
	$_VAL['mac_address']     = !empty($_POST['mac_address']) ? trim($_POST['mac_address'])  : '';
	
	
	$adminArr = read_static_cache('land',2);
	if ($adminArr===false){
	   $msg = '帐号或密码错误!';
	   $_SESSION['login_times']++;
	}else{
		foreach($adminArr as $k => $val){
			if(trim($adminArr[$k]["sa_user"])==md5($_VAL['username'].$_CFG["keys_code"])){
				$userkey = $k;
			}
		}
		if ($userkey==''){
		   $msg = '帐号或密码错误!';
		   $_SESSION['login_times']++;
		}else{
			
			
			$update_add = '';
			if($adminArr[$userkey]['is_check_hd']){
				//网卡信息值是不是为空
				if(!$_VAL['mac_address']){
					echo '读取电脑硬件信息失败，请检查你的浏览器设置。';
					exit;
				}				
				
				if(empty($adminArr[$userkey]["mac_address"])){
					
					$update_add = "  `mac_address` = '".$_VAL['mac_address']."' ";
					$db -> update(SADMIN, " $update_add ", " sa_user ='".$_VAL['username']."'");
					creat_admin();
					$adminArr[$userkey]["mac_address"] = $_VAL['mac_address'];					
				}else{
					if(trim($adminArr[$userkey]["mac_address"]) != $_VAL['mac_address']){
						echo '电脑未授权，无法登陆，请联系技术部';
						exit;
					}
				}
				
				
				
			}	
			
			if(trim($adminArr[$userkey]["sa_pswd"])==md5($_VAL['password'].$_CFG["keys_code"])){
            
                if (!check_password($_VAL['password'])) {//密码不符合规范 by mashanling on 2013-04-25 11:32:44
                    $caches = read_static_cache($v = 'reset_password', 2);//强制修改密码用户
                    $caches = $caches ? $caches : array();

                    if (!isset($caches[$userkey])) {//只允许登陆一次，下次必须修改密码才可登陆
                        $_SESSION[$v] = true;
                        $caches[$userkey] = true;
                        write_static_cache($v, $caches, 2);
                        //e_log($adminArr[$userkey]['real_name'] . "({$_VAL['password']})密码不符合规范，强制修改");
                    }
                    else {
                        //e_log($adminArr[$userkey]['real_name'] . "({$_VAL['password']})密码不符合规范，未修改就登陆");
                        exit('您的密码不符合规范，请联系技术部');
                    }
                }
                
				$_SESSION["WebUserInfo"]            = $adminArr[$userkey];
				$_SESSION["WebUserInfo"]["sa_user"] = $_VAL['username'];
				unset($_SESSION["verify"]);
				unset($_SESSION['login_times']);
				$db -> update(SADMIN, " logins = logins+1,last_time='".gmtime()."',last_ip = '".real_ip()."' ", " sa_user ='".$_VAL['username']."'");
				$login_msg = "成功";
				$msg = '登陆成功,请稍等，系统正在转向... 剩下<span id="num">3</span>秒';
				
			}else{
				$_SESSION['login_times']++;
				$msg = '帐号或密码错误!';
			}
		}
	}
	echo $msg;
	admin_log('', '后台登陆：', '用户名:'.$_VAL['username'].',登陆'.$login_msg);   // 记录管理员操作
	exit();
}

$Arr['login_times'] = $_SESSION['login_times'];
$Arr['limit_times'] = LIMITTIMES;


temp_disp();
?>