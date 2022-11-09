<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
admin_priv('area_manage');


$_ACT = 'list';
$_ID  = '';
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));

/*------------------------------------------------------ */
//-- 列出某地区下的所有地区列表
/*------------------------------------------------------ */
if ($_ACT == 'list')
{
    /* 获取地区列表 */
    $region_arr = area_list();
    $Arr['region_arr'] =   $region_arr;
}

/*------------------------------------------------------ */
//-- 添加新的地区
/*------------------------------------------------------ */

elseif ($_ACT == 'add')
{
    $region_name    = trim($_POST['region_name']);
    $region_code    = trim($_POST['region_code']);
    $sql = "SELECT count(*) from " . REGION. " where region_name = '$region_name'";
	$re_num = $db->getOne($sql);
	if ($re_num>0){
		$msg = '添加失败,'.$region_name.'已经存在，请更换！';
        sys_msg($msg);
	}
	
    $sql = "INSERT INTO " . REGION. " (region_name,region_code) VALUES ('$region_name','$region_code')";
    if ($db->query($sql)){
		creat_area();
        admin_log('', _ADDSTRING_,'地区 '.$region_name);
		header("Location:area_manage.php");
		exit();
    }
}
/*------------------------------------------------------ */
//-- 添加新的地区
/*------------------------------------------------------ */

elseif ($_ACT == 'add_province')
{	$region_arr = area_list();
    $province    = trim($_POST['province']);
	$country_id     = trim($_POST['country']);
	$region_code    = $region_arr[$country_id]['region_code'];
    $sql = "SELECT count(*) from eload_province where province = '$province'";
	$re_num = $db->getOne($sql);
	if ($re_num>0){
		$msg = '添加失败,'.$province.'已经存在，请更换！';
        sys_msg($msg);
	}
	
    $sql = "INSERT INTO eload_province (country_id,region_code,province) VALUES ('$country_id','$region_code','$province')";
    if ($db->query($sql)){
		creat_area();
        admin_log('', _ADDSTRING_,'地区 '.$province);
		header("Location:area_manage.php");
		exit();
    }
}
/*------------------------------------------------------ */
//-- 编辑地区
/*------------------------------------------------------ */

elseif ($_ACT == 'edit')
{
    $Col_Value    = trim($_POST['value']);
	$conArr = explode('|',$_POST['id']);
    $id = $conArr[0];
    $colName = $conArr[1];
    $sql = "UPDATE  " . REGION. " SET $colName = '$Col_Value' WHERE region_id = '$id'";
    if ($db->query($sql)){
        admin_log('', _EDITSTRING_,'地区 '.$Col_Value);
		creat_area();
		echo $Col_Value;
		exit();
    }
}
/*------------------------------------------------------ */
//-- 编辑州
/*------------------------------------------------------ */

elseif ($_ACT == 'edit_state')
{
    $Col_Value    = trim($_POST['value']);
	$conArr = explode('|',$_POST['id']);
    $id = $conArr[0];
    $colName = $conArr[1];
	if($colName == 'country_id'){
		$sql = "UPDATE  eload_province SET country_id = '$Col_Value' WHERE province = '$id'";
	}else{
		$sql = "UPDATE  eload_province SET province = '$Col_Value' WHERE province = '$id'";
	}
    if ($db->query($sql)){
        admin_log('', _EDITSTRING_,'州 '.$Col_Value);
		creat_area();
		echo $Col_Value;
		exit();
    }
}

/*------------------------------------------------------ */
//-- 删除区域
/*------------------------------------------------------ */
elseif ($_ACT == 'drop')
{
    $id = intval($_REQUEST['id']);
    $sql = "SELECT * FROM " . REGION . " WHERE region_id = '$id'";
    $region = $db->selectinfo($sql);
   
   $db->delete(REGION," region_id = '$id'");
	admin_log('', _DELSTRING_, '地区 '.addslashes($region['region_name']));
	creat_area();
	header("Location:area_manage.php");
	exit();
}
elseif($_ACT=='drop_state'){
	$state = empty($_GET['name'])?'':trim($_GET['name']);
	$db->query("delete from eload_province where province = '".$state."'");
	admin_log('', _DELSTRING_, '地区 '.addslashes($state));
	creat_area();
	header("Location:area_manage.php");
	exit();
}
$_ACT = $_ACT == 'msg'?'msg':'area_'.$_ACT;
temp_disp();





?>