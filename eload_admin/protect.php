<?
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('libs/fun.admin.php');
require_once('lib/common.fun.php');
/* act操作项的初始化 */
$_ACT = 'menulist';
$_ID  = '';
$_PID = '';

if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = trim($_GET['id']);
if (!empty($_GET['pid'])) $_PID   = trim($_GET['pid']);

// 语言
$lang = get_lang();
$Arr['lang_arr'] = $lang;

/*
/+------------------------------------------------------
//-- 管理员安全退出  -------------------------------------
/+------------------------------------------------------
 */
if ($_ACT=='logout') {
    unset($_SESSION["WebUserInfo"]);
	header("location:landing.php");
	exit();
}



/*
/+------------------------------------------------------
//-- 管理菜单添加界面管理
/+------------------------------------------------------
 */
if ($_ACT=='menuadd') {
	admin_priv('menuadd');  //检查权限
	$tag_msg = "添加";
	$parentArr = $db -> select(AACTION,'action_id,action_name',' parent_id = 0','  parent_id,action_order ASC,action_id ASC');
	if ($_ID!=''){
	    $tag_msg = "修改";
		$sql       = "select * from ".AACTION." where action_id = $_ID";
		$menuArr          = $db -> selectInfo($sql);
		$Arr["menuArr"]   = $menuArr;
	}

	$Arr["parentArr"] = $parentArr;
	$Arr["pid"] = $_PID;
	$Arr["url"]       = "?act=input_menu&id=$_ID";
	$Arr["tag_msg"] = $tag_msg;
}
/*
/+------------------------------------------------------
//-- 菜单管理界面
/+------------------------------------------------------
 */
if ($_ACT=='menulist') {
	admin_priv('menulist');  //检查权限
	if ($_ID!=''){if ($db -> delete(AACTION," action_id = $_ID ")) echo '<script>alert("删除成功!"); location.href="?act=menulist";</script>';admin_log($sn = '', _DELSTRING_, "菜单列表ID为 $_ID 的记录");creat_menu();exit();}

	$tree=array();
	//$sql = "SELECT * FROM ".AACTION." ORDER BY parent_id,action_order ASC,action_id ASC";
	//$menuArr = $db -> arrQuery($sql);
	$menuArr = read_static_cache('menu_c',2);
	$menuArr = toTree($menuArr,$pk='action_id');
	treetoary($menuArr,0,'action_name');
	$menuArr = $tree;
	$Arr["menuArr"] = $menuArr;
}

/*
/+------------------------------------------------------
//-- 菜单添加到数据库中
/+------------------------------------------------------
 */
if ($_ACT=='input_menu') {
	admin_priv('menuadd');  //检查权限
	$Arr["url"]= "?act=menulist&id=".$_ID;
	$_POST['parent_id']    = !empty($_POST['parent_id'])    ? intval($_POST['parent_id'])  : 0;
	$_POST['enable']       = !empty($_POST['enable'])    ? intval($_POST['enable'])  : 0;
	$_POST['action_order'] = !empty($_POST['action_order'])    ? intval($_POST['action_order'])  : 0;
	$_POST['action_code']  = !empty($_POST['action_code'])    ? htmlspecialchars(trim($_POST['action_code']))  : '';
	$_POST['action_name']  = !empty($_POST['action_name'])    ? htmlspecialchars(trim($_POST['action_name']))  : '';
	$_POST['action_url']   = !empty($_POST['action_url'])    ? htmlspecialchars(trim($_POST['action_url']))  : '';

	if ($_ID!=''){
			if ($_POST['parent_id'] == $_ID){
				$msg = '修改失败，你不能指定自己为自己的子类';
				$links = array('0'=> array('url'=>'javascript:history.go(-1)','name'=>'返回继续修改'),
							   '1'=> array('url'=>'?act=menulist','name'=>'返回菜单列表'));
			}else{
				if ($db->autoExecute(AACTION,$_POST,'UPDATE'," action_id = $_ID")){
					$msg = '修改成功';admin_log($sn = '', _EDITSTRING_, '系统菜单 '.$_POST['action_name']);
					}else{$msg = '修改失败';}
				$links = array('0'=> array('url'=>'?act=menulist','name'=>'返回菜单列表'),
							   '1'=> array('url'=>'?act=menuadd','name'=>'返回添加菜单'),
							   '2'=> array('url'=>'?act=menuadd&id='.$_ID,'name'=>'还需要修改')
							   );

			}
	}else{
		if ($db->count_info(AACTION,"*"," action_code = '".$_POST['action_code']."'")>=1){
			$msg = '添加失败,菜单编号及名称不能有重复，请更换！';
			$links = array('0'=> array('url'=>'javascript:history.go(-1)','name'=>'返回添加菜单'),
						   '1'=> array('url'=>'?act=menulist','name'=>'返回菜单列表'));
		}else{
			if($db->autoExecute(AACTION,$_POST)){
			$msg = '添加成功';
			admin_log($sn = '', _ADDSTRING_, '系统菜单 '.$_POST['action_name']);
			}else{$msg = '添加失败';}
			$links = array('0'=> array('url'=>'?act=menuadd','name'=>'返回添加菜单'),
						   '1'=> array('url'=>'?act=menulist','name'=>'返回菜单列表'));   //返回地址
		}
	}

	$_ACT = 'msg';
	$Arr["msg"] = $msg;
	$Arr["links"] = $links;
	creat_menu();
}




/*
/+------------------------------------------------------
//-- 系统组添加界面
/+------------------------------------------------------
 */
if ($_ACT=='groupadd') {
	admin_priv('groupadd');  //检查权限
	$tag_msg = "添加";
	if ($_ID!=''){
		$tag_msg = "修改";
		$sql       = "select * from ".AGROUP." where group_id = $_ID";
		$groupArr          = $db -> selectInfo($sql);
		if(strpos($groupArr['group_power'], 'lang_en')) {
			$Arr['default_lang'] = 1;
		} else {
			$Arr['default_lang'] = 0;
		}
		$Arr["infoArr"]   = $groupArr;
	}

	$tree=array();
	$sql = "SELECT * FROM ".AACTION." ORDER BY parent_id,action_order ASC,action_id ASC";
	$menuArr = $db -> arrQuery($sql);
	$menuArr = toTree($menuArr,$pk='action_id');


	$Arr["url"]       = "?act=input_group&id=$_ID";
	$Arr["tag_msg"] = $tag_msg;
	$Arr["menuArr"] = $menuArr;
}

/*
/+------------------------------------------------------
//-- 系统组入数据库
/+------------------------------------------------------
 */
if ($_ACT=='input_group') {
	admin_priv('groupadd');  //检查权限
	$_VAL['group_name']  = !empty($_POST['group_name'])    ? htmlspecialchars(trim($_POST['group_name']))  : '';
	$_VAL['group_desc']  = !empty($_POST['group_desc'])    ? htmlspecialchars(trim($_POST['group_desc']))  : '';
	$_VAL['group_power']  = !empty($_POST['group_power'])    ? $_POST['group_power']  : array();
	$_VAL['group_power']  = implode (",",$_VAL['group_power']);

	if ($_ID!=''){
			if ($db->autoExecute(AGROUP,$_VAL,'UPDATE'," group_id = $_ID")){
			$msg = '修改成功';
			admin_log($sn = '', _EDITSTRING_, '系统组 '.$_VAL['group_name']);
			}else{$msg = '修改失败';}
				$links = array('0'=> array('url'=>'?act=grouplist','name'=>'返回系统组列表'),
							   '1'=> array('url'=>'?act=groupadd','name'=>'返回添加系统组'),
							   '2'=> array('url'=>'?act=groupadd&id='.$_ID,'name'=>'还需要修改'));

	}else{
		if ($db->count_info(AGROUP,"*"," group_name = '".$_VAL['group_name']."'")>=1){
			$msg = '添加失败,系统组名称不能有重复，请更换！';
			$links = array('0'=> array('url'=>'javascript:history.go(-1)','name'=>'返回添加系统组'),
						   '1'=> array('url'=>'?act=grouplist','name'=>'返回系统组列表'));
		}else{
			if($db->autoExecute(AGROUP,$_VAL)){
				$msg = '添加成功';
				admin_log($sn = '', _ADDSTRING_, '系统组 '.$_VAL['group_name']);
				}else{$msg = '添加失败';}
			$links = array('0'=> array('url'=>'?act=groupadd','name'=>'返回添加系统组'),
						   '1'=> array('url'=>'?act=grouplist','name'=>'返回系统组列表'));   //返回地址
		}
	}


	$_ACT = 'msg';
	$Arr["msg"] = $msg;
	$Arr["links"] = $links;
	creat_admin();
}

/*
/+------------------------------------------------------
//-- 系统组列表
/+------------------------------------------------------
 */
if ($_ACT=='grouplist') {
	admin_priv('grouplist');  //检查权限
	if ($_ID!=''){if ($db -> delete(AGROUP," group_id = $_ID "))
	$db -> delete(SADMIN," group_id = $_ID ");//删除该组下的管理员
	echo '<script>alert("删除成功!"); location.href="?act=grouplist";</script>';
	admin_log($sn = '', _DELSTRING_, "系统组列表ID为 $_ID 的记录");
	creat_admin();
	exit();}
	$sql = "SELECT * FROM ".AGROUP." ORDER BY group_id ASC";
	$groupArr = $db -> arrQuery($sql);
	$Arr["groupArr"] = $groupArr;
}

/*
/+------------------------------------------------------
//-- 管理员列表
/+------------------------------------------------------
 */
if ($_ACT=='adminlist') {
	admin_priv('adminlist');  //检查权限
	if ($_ID!=''){if ($db -> delete(SADMIN," said = $_ID ")) echo '<script>alert("删除成功!"); location.href="?act=adminlist";</script>';
	admin_log($sn = '', _DELSTRING_, "管理员列表ID为 $_ID 的记录");
	creat_admin();
	exit();}
	$sql = "SELECT a.*,b.group_name FROM ".SADMIN." as a left join  ".AGROUP." as b  on a.group_id = b.group_id ORDER BY said ASC";
	$adminArr = $db -> arrQuery($sql);
	foreach ($adminArr as $k => $v){
		$adminArr[$k]["last_time"] = local_date($GLOBALS['_CFG']['time_format'], $adminArr[$k]['last_time']);
	}

	$Arr["adminArr"] = $adminArr;
}



/*
/+------------------------------------------------------
//-- 管理员添加界面
/+------------------------------------------------------
 */
if ($_ACT=='adminadd') {
	admin_priv('adminadd');  //检查权限
	$tag_msg = "添加";
	$adminArr= array();
    $groupArr = $db -> select(AGROUP,'group_id,group_name');  //系统组

	if ($_ID!=''){
		$tag_msg = "修改";
		$sql       = "select * from ".SADMIN." where said = $_ID";
		$adminArr         = $db -> selectInfo($sql);
		$Arr["infoArr"]   = $adminArr;
	}

	$Arr["url"]       = "?act=input_admin&id=$_ID";
	$Arr["tag_msg"] = $tag_msg;
	$Arr["adminArr"] = $adminArr;
	$Arr["groupArr"] = $groupArr;
}


/*
/+------------------------------------------------------
//-- 管理员修改自己的密码
/+------------------------------------------------------
 */
if ($_ACT == 'modify_my_password'){
	admin_priv('modify_my_password');
	$tag_msg = "修改密码";

	$sql       = "select * from ".SADMIN." where said = ".$_SESSION['WebUserInfo']['said'];
	$adminArr         = $db -> selectInfo($sql);
	$Arr["adminArr"]   = $adminArr;
	$Arr["tag_msg"] = $tag_msg;
	$Arr["url"]       = "?act=update_password";
}


if ($_ACT == 'update_password'){
	admin_priv('modify_my_password');
	$_VAL['old_pswd']  = !empty($_POST['old_pswd'])    ? htmlspecialchars(trim($_POST['old_pswd']))  : '';
	$_VAL['sa_pswd']  = !empty($_POST['sa_pswd'])    ? htmlspecialchars(trim($_POST['sa_pswd']))  : '';
	$_VAL['sa_pswd2']  = !empty($_POST['sa_pswd2'])    ? htmlspecialchars(trim($_POST['sa_pswd2']))  : '';
	foreach($_VAL as $k => $v){	if (($v=='')||(empty($v))) die(FEIFA); }
	if ($_VAL['sa_pswd'] != $_VAL['sa_pswd2'])
	    sys_msg("确认密码和新密码不一样，请重新输入！", 1, array(), false);

if (!check_password($_VAL['sa_pswd'])) {//密码不符合规范 by mashanling on 2013-04-25 13:57:21
    sys_msg('密码不符合规范，请重新输入', 1);
}

	$_VAL['sa_pswd']  = md5($_VAL['sa_pswd'].$_CFG["keys_code"]);
	$_VAL['old_pswd'] = md5($_VAL['old_pswd'].$_CFG["keys_code"]);

    if($old_password_arr = read_static_cache('old_password_arr.20131121', 2)) {//密码不可与上次相同
        $admin_id   = $_SESSION['WebUserInfo']['said'];

        if (isset($old_password_arr[$admin_id]) && $_VAL['sa_pswd'] == $old_password_arr[$admin_id]) {

            if (function_exists('e_log')) {
                e_log($_SESSION['WebUserInfo']['real_name'] . ' 修改的密码与上次使用的密码相同');
            }

            sys_msg('为了系统安全，您不可使用与上次相同的密码！', 1, array(), false);
        }
    }

	$sql       = "select sa_pswd from ".SADMIN." where said = ".$_SESSION['WebUserInfo']['said'];
	$old_pswd  = $db -> getOne($sql);

	if ($_VAL['old_pswd'] == $old_pswd){
		$sql  = "update ".SADMIN." set sa_pswd ='$_VAL[sa_pswd]' where said = ".$_SESSION['WebUserInfo']['said'];
		$db -> query($sql);
        if (isset($_SESSION['reset_password'])) {
		    unset($_SESSION['reset_password']);
	    //e_log($_SESSION['WebUserInfo']['real_name'] . '已修改不规范密码');
		}
	    creat_admin();
		$_SESSION['WebUserInfo'] = NULL;
		sys_msg("密码修改成功，下次记得用新密码登录！", 1, array(), false);

	}else{
		sys_msg("原密码输入错误，请重新输入！", 1, array(), false);
	}
}

/*
/+------------------------------------------------------
//-- 清理硬件授权信息
/+------------------------------------------------------
 */
if($_ACT=='admin_hd_clear'){
	    $said = empty($_GET['id'])?0:intval($_GET['id']);
		$update_add = "  `mac_address` = NULL ";
		$db -> update(SADMIN, " $update_add ", " said ='".$said."'");
		creat_admin();
	    sys_msg("清理成功，重新登陆即可完成重新授权！", 1, array(), false);
}



/*
/+------------------------------------------------------
//-- 管理员入数据库
/+------------------------------------------------------
 */
if ($_ACT=='input_admin') {
	admin_priv('adminadd');  //检查权限
	$_VAL['sa_user']  = !empty($_POST['sa_user'])    ? htmlspecialchars(trim($_POST['sa_user']))  : '';
	$_VAL['sa_pswd']  = !empty($_POST['sa_pswd'])    ? htmlspecialchars(trim($_POST['sa_pswd']))  : '';
	$_VAL['real_name']  = !empty($_POST['real_name'])    ? htmlspecialchars(trim($_POST['real_name']))  : '';
	$_VAL['group_id']  = !empty($_POST['group_id'])    ? intval(trim($_POST['group_id']))  : '';

	$adminArr = read_static_cache('land',2);
	foreach($adminArr as $k => $val){
		if(trim($adminArr[$k]["sa_user"])==md5($_VAL['sa_user'].$_CFG["keys_code"])){
			if ($k!=$_ID)
			sys_msg("操作失败，帐号是有重复，请仔细核对！", 1, array(), false);
		}
	}

if ($_VAL['sa_pswd'] && !check_password($_VAL['sa_pswd'])) {//密码不符合规范 by mashanling on 2013-04-25 13:57:16
    sys_msg('密码不符合规范，请重新输入', 1);
}
	if ($_VAL['sa_pswd']==''){
		unset($_VAL['sa_pswd']);
	}else{
		$_VAL['sa_pswd'] = md5($_VAL['sa_pswd'].$_CFG["keys_code"]);
	}


	if ($_ID!=''){
			if ($db->autoExecute(SADMIN,$_VAL,'UPDATE'," said = $_ID")){
				$msg = '修改成功';
				admin_log($sn = '', _EDITSTRING_, '管理员 '.$_VAL['sa_user']."的帐号");
				}else{$msg = '修改失败';}
				$links = array('0'=> array('url'=>'?act=adminlist','name'=>'返回管理员列表'),
							   '1'=> array('url'=>'?act=adminadd','name'=>'返回添加管理员'),
							   '2'=> array('url'=>'?act=adminadd&id='.$_ID,'name'=>'还需要修改'));
	}else{
	$_VAL['add_time']  = time();

		if ($db->count_info(SADMIN,"*"," sa_user = '".$_VAL['sa_user']."'")>=1){
			$msg = '添加失败,管理员帐号不能有重复，请更换！';
			$links = array('0'=> array('url'=>'javascript:history.go(-1)','name'=>'返回添加管理员'),
						   '1'=> array('url'=>'?act=adminlist','name'=>'返回管理员列表'));
		}else{
			if($db->autoExecute(SADMIN,$_VAL)){
				$msg = '添加成功';
				admin_log($sn = '', _ADDSTRING_, '管理员 '.$_VAL['sa_user']."的帐号");
			}else{$msg = '添加失败';}
			$links = array('0'=> array('url'=>'?act=adminadd','name'=>'返回添加管理员'),
						   '1'=> array('url'=>'?act=adminlist','name'=>'返回管理员列表'));   //返回地址
		}
	}


	$_ACT = 'msg';
	$Arr["msg"] = $msg;
	$Arr["links"] = $links;
	creat_admin();
}

if ($_ACT == 'admin_cat_priv') {    //系统管理员商品分类权限 编辑页面
    admin_priv('adminadd');
    $cat = read_static_cache('category_c_key',2);
    $big_cat=array();
    foreach ($cat as $k=>$v){
    	if($v['parent_id'] == 0){
    		$big_cat[]=$v;
    	}
    }
    //echo $_ID;
    $sql = "select * from ".SADMIN." where said =$_ID";
    $admin_info=$db->selectInfo($sql);
    //print_r($admin_info);
    $Arr['big_cat']=$big_cat;
    $Arr['admin_id'] =  $_ID;
    $Arr["admin_info"] = $admin_info;
    //print_r($big_cat);
    //exit();
}

if($_ACT == 'save_admin_cat_priv'){
	admin_priv('adminadd');
	$cat_id = empty($_POST['cat_id'])?'':$_POST['cat_id'];
	$cat_priv_str=$cat_id?implode(',',$cat_id):'';
	$admin_id  = intval($_POST['admin_id']);
 	//print_r($cat_id);
 	//$admin_info = $db-selectInfo();
	$sql = "update ".SADMIN." SET cat_priv='".$cat_priv_str."' where said=$admin_id";
	$db->query($sql);
	admin_log('', _EDITSTRING_, "管理员 {$admin_arr[$admin_id]['real_name']} 商品分类权限{$priv}");
	creat_admin();
	$msg =  "修改成功";
	$links = array('0'=> array('url'=>'?act=adminlist','name'=>'返回管理员列表'),
				   '1'=> array('url'=>'?act=admin_cat_priv&id='.$admin_id,'name'=>'还需要修改'));
	$_ACT = 'msg';
	$Arr["msg"] = $msg;
	$Arr["links"] = $links;

	//echo $sql;
    //exit();
}


if ($_ACT == 'save_admin_category_priv') {    //修改系统管理员商品分类权限

    admin_priv('adminadd');
    $admin_id   = Param::post('admin_id', 'int');
    $priv_all   = Param::post('category_priv_all', 'int');
    $priv       = isset($_POST['category_priv']) ? $_POST['category_priv'] : '';    //分类权限
    $priv       = $priv_all ? '' : implode(',', $priv);
    $sql        = 'UPDATE ' . SADMIN . " SET category_priv='{$priv}' WHERE said={$admin_id}";
    $db->query($sql);

    $admin_arr  = read_static_cache('land', 2);
    admin_log('', _EDITSTRING_, "管理员 {$admin_arr[$admin_id]['real_name']} 商品分类权限{$priv}");
    creat_admin();
    exit;
}


temp_disp();
?>