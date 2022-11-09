<?php
define('INI_WEB', true);

require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');

admin_priv('shipping');  //检查权限
/* act操作项的初始化 */
$_ACT = 'shipping_list';
$_ID  = '';
if(!empty($_GET['act'])) $_ACT=$_GET['act'];
if(!empty($_GET['id'])) $_ID=$_GET['id'];





if($_ACT == 'shipping_list'){
	if ($_ID!=''){    //删除shipping 
		if($db-> delete(SHIPPING," ship_id=$_ID "))
		{
			//记录操作
			admin_log('', _DELSTRING_, '配送方式：'.$field["ship_name"]);
			/*更新shipping_method.php文件*/
			create_shipping_cache();
			
			header("location:shipping.php");
			exit();
        }
	}
	$sql = "select * from ".SHIPPING;
	$arr_ship = $db->arrQuery($sql);
	$Arr["shipArr"] = $arr_ship;

   
}
/*------------------------------------------------------------------*/
//-----配送　添加或修改界面
/*------------------------------------------------------------------*/

if($_ACT == 'shipping_add'){
	if($_ID != ''){            //转到编辑
		$tag_msg = "修改";
		$sql = "select * from ".SHIPPING." where ship_id=".$_ID ;
		$ship = $db->selectInfo($sql);
		$Arr["ship"] = $ship;
		$Arr["tag_msg"] = $tag_msg;
		//exit();
	}
	else{           //添加界面
		$tag_msg = "添加";
		$Arr["tag_msg"] = $tag_msg;
	}
	
	
}

/*------------------------------------------------------------------*/
//-----保存　添加或修改配送　到数据库
/*------------------------------------------------------------------*/
if($_ACT =='update'){
	$field = array();
	$field["ship_code"] = empty($_POST["ship_code"])?'':$_POST["ship_code"];
	$field["ship_name"] = empty($_POST["ship_name"])?'':$_POST["ship_name"];
	$field["ship_desc"] = empty($_POST["ship_desc"])?'':$_POST["ship_desc"];
	$field["ship_order"] = empty($_POST["ship_order"])?'':$_POST["ship_order"];
	$field["ship_save"] = empty($_POST["ship_save"])?'':$_POST["ship_save"];
	$field["enable"] = empty($_POST["enable"])?'0':$_POST["enable"];	
	if($_ID !=''){   //保存修改
		$db->autoExecute(SHIPPING, $field, 'UPDATE', " ship_id = '$_ID'");

    	/* 记录管理员操作 */
		admin_log('', _EDITSTRING_, '配送方式：'.$field["ship_name"]);
		
		/*更新shipping_method.php文件*/
		create_shipping_cache();
		
		/* 提示信息 */
		$links[0]['name']    = "返回配送方式列表";
		$links[0]['url']    = 'shipping.php' ;
		$links[1]['name']    = "还需要修改";
		$links[1]['url']    = 'javascript:history.back()';
		sys_msg(sprintf("修改成功", htmlspecialchars(stripslashes($field["ship_name"]))), 0, $links);
	}
	
	else{
		//插入新配送方式
		$db->autoExecute(SHIPPING, $field);
    	/* 记录管理员操作 */
		admin_log('', _ADDSTRING_, '配送方式：'.$field["ship_name"]);
		/*更新shipping_method.php文件*/
		create_shipping_cache();
	
		/* 提示信息 */
		$links[0]['name']    = "返回配送方式列表";
		$links[0]['url']    = 'shipping.php' ;
		$links[1]['name']    = "还需要添加配送方式";
		$links[1]['url']    = 'javascript:history.back()';
		sys_msg($field["ship_name"].",已添加", 0, $links);
		
	}
	
	//header("location:shipping.php");
	//exit();
	
}
	
temp_disp();	
?>