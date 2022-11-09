<?php
/**
 * sync-user-to-mailsys.php
 * 传送用户资料到邮件系统
 * @author jim 2013-5-59
 * 
 */

define('INI_WEB', true);
set_time_limit(0);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');


$site_name = "dealsmachine.com";  //站点名称

//$site_name = $_SERVER['HTTP_HOST'];//站点名称 eg :dealsmachine.com

$sql = "select user_id from eload_users where is_sync_to_mailsys = 0 limit 2000";  //一次最多同步1000个未同步用户
$user = $db->arrQuery($sql);


if(empty($user))exit('没有需要同步的用户');
$user_ids = ''; //用户id 字符串   eg '10,12,13'
for($i=0;$i<count($user);$i++){    //拼接用户 id
	$user_ids .= "{$user[$i]['user_id']}";
	if($i<(count($user)-1))$user_ids .= ","; 
}


if($user_ids){
	$sql = "SELECT u.user_id,u.email as email, replace(u.firstname,',',' ') AS `name`,r.region_code AS country,last_login,reg_time as reg_date  FROM eload_users AS u 
	LEFT JOIN eload_user_address AS a ON u.address_id = a.address_id 
	LEFT JOIN eload_region AS r ON a.country = r.region_id   where u.user_id in($user_ids) ";
	$user_arr = $db->arrQuery($sql);
	
	$post_data = "keys_code={$_CFG['keys_code']}&site={$site_name}&user_info=".urlencode(serialize($user_arr));//$_CFG['keys_code'] 为内部验证码
	
	//exit($post_data);
	$post_url = "http://mx.egocdn.com/syn/save_user.php";
	$return_data = curl_post($post_url,$post_data);  //post 用户资料到邮件系统
	//exit($return_data);
	if(strpos($return_data, 'success')!==FALSE){
		$sql  = "update ".USERS." set is_sync_to_mailsys = 1 where user_id in($user_ids)";  //成功则更新用户同步标识
		$db->query($sql);
		echo "success:$return_data";
	}else{
		echo "false:{$return_data}";
	}
	
}

/**
 * post 数据到指定URL
 * $post eg : "a=1&c=yy"
 */
function curl_post($url,$post){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}


?>