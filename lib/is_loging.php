<?
$_SESSION["WebUserInfo"] = empty($_SESSION["WebUserInfo"])?'':$_SESSION["WebUserInfo"];
if (empty($_SESSION["WebUserInfo"])) go_login();
$adminArr = read_static_cache('land',2);

if ($adminArr === false)go_login();
$sa_user     = md5($_SESSION["WebUserInfo"]["sa_user"].$_CFG["keys_code"]);
$sa_pswd     = $_SESSION["WebUserInfo"]["sa_pswd"];
$group_power = $_SESSION["WebUserInfo"]["group_power"];
$said        = $_SESSION["WebUserInfo"]["said"];
$mac_address = $_SESSION["WebUserInfo"]["mac_address"];//empty($_SESSION["WebUserInfo"]["mac_address"])?'':

if ($sa_user     != $adminArr[$said]["sa_user"])    {  go_login();}
if ($sa_pswd     != $adminArr[$said]["sa_pswd"])    {  go_login();}
if ($group_power != $adminArr[$said]["group_power"]){  go_login();}

//if($adminArr[$said]["is_check_hd"])
if ($mac_address != $adminArr[$said]["mac_address"]) {  go_login();}


function go_login(){
	
	//print_r($_SESSION);
   // exit(); 
	//unset($_SESSION);
echo '<META HTTP-EQUIV="pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Cache-Control" CONTENT="no-cache, must-revalidate">
<META HTTP-EQUIV="expires" CONTENT="Wed, 26 Feb 1997 08:21:57 GMT">
';
echo '<script>top.location.href="landing.php";</script>';
exit(); 
}


$Tpl->caching = false; 
//$Tpl->caching = 2;
//$Tpl->cache_lifetime = 30;
//$my_cache_id = join('-',$_GET);
//echo $my_cache_id;
?>