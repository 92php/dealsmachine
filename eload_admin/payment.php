<?php
define('INI_WEB', true);
$payment_list = "";
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
admin_priv('payment');  //检查权限

/* act操作项的初始化 */
$_ACT = 'payment_list';
$_ID  = '';
if(!empty($_GET['act'])) $_ACT=$_GET['act'];
if(!empty($_GET['id'])) $_ID=$_GET['id'];


switch ($_ACT){
	case 'payment_list':                             
		if ($_ID!=''){    //删除支付方式 

			$sql = 'select * from '.PAYMENT.' where pay_id = '.$_ID;

			$payments = $db->arrQuery($sql);
			$payments = $payments[0];

			$payment = ','.$payments['pay_code'];
			
			if($db-> delete(PAYMENT," pay_id=$_ID "))
			{
				//记录操作
				
				admin_log('', _DELSTRING_, '支付方式：'.$payments["pay_name"]);
				
				//删除数据库中的对应payment
				$db->update(REGION,"payment=replace(payment,'$payment','')");
				creat_area();//生成area_key.php 文件
				/*更新缓存里的payment.php文件*/
				create_payment_cache();
				
				header("location:payment.php");
				exit();
			}
		}
		$sql = "select * from ".PAYMENT;
		//echo $sql;
		$arr_pay = $db->arrQuery($sql);
		$Arr["payArr"] = $arr_pay;
		//print_r($arr_pay);
		break;
	case 'payment_add':  
		if($_ID != ''){            //转到编辑
			$tag_msg = "修改";
			$sql = "select * from ".PAYMENT." where pay_id=".$_ID ;
			$pay = $db->selectInfo($sql);
			$Arr["pay"] = $pay;
			$Arr["tag_msg"] = $tag_msg;
			
			//exit();
		}
		else{           //添加界面
			$tag_msg = "添加";
			$Arr["tag_msg"] = $tag_msg;
		}
		break;
	case 'update':
		$field = array();
		$field["pay_code"] = empty($_POST["pay_code"])?'':$_POST["pay_code"];
		$field["pay_name"] = empty($_POST["pay_name"])?'':$_POST["pay_name"];
		$field["pay_brief"] = empty($_POST["pay_brief"])?'':$_POST["pay_brief"];
		$field["pay_logo"] = empty($_POST["pay_logo"])?'':$_POST["pay_logo"];
		$field["pay_desc"] = empty($_POST["pay_desc"])?'':$_POST["pay_desc"];
		$field["pay_order"] = $_POST["pay_order"] ==''?'':$_POST["pay_order"];
		//die($_POST["pay_order"]);
		$field["enable"] = $_POST["enable"];	
		if($_ID !=''){   //保存修改
		
		     //取出原来的payment
			$sql = 'select * from '.PAYMENT.' where pay_id='.$_ID;
			$payments = $db->arrQuery($sql);
			$payments = $payments[0];
			
			
	
			
			$payment = $db->
			//修改数据库中REGION表的payment
			$sql = 'select * from '.PAYMENT.' where pay_id='.$_ID;

			$payments = $db->arrQuery($sql);
			$payments = $payments[0];
			$payment = ','.$payments['pay_code'];
			$payment_n=','.$field["pay_code"];
			
			if ($payment != $payment_n){   //如果支付编码有改动
				//die("$payment<br/>$payment_n" );
				//die("1");
				$db->update(REGION,"payment=replace(payment,'$payment','$payment_n')");//修改REGION表
				creat_area();  //重新生成area_key.php缓存文件
			}
			$db->autoExecute(PAYMENT, $field, 'UPDATE', " pay_id = '$_ID'");
			
			/* 记录管理员操作 */
			admin_log('', _EDITSTRING_, '支付方式：'.$field["pay_name"]);
			
			/*更新缓存payment.php文件*/
			create_payment_cache();
			
			/* 提示信息 */
			$links[0]['name']    = "返回支付方式列表";
			$links[0]['url']    = 'payment.php' ;
			$links[1]['name']    = "还需要修改";
			$links[1]['url']    = 'javascript:history.back()';
			sys_msg(sprintf("修改成功", htmlspecialchars(stripslashes($field["pay_name"]))), 0, $links);
		}
		
		else{
			//插入新支付方式
			$db->autoExecute(PAYMENT, $field);
			/* 记录管理员操作 */
			admin_log('', _ADDSTRING_, '支付方式：'.$field["pay_name"]);
			/*更新缓存payment.php文件*/
			create_payment_cache();
		
			/* 提示信息 */
			$links[0]['name']    = "返回支付方式列表";
			$links[0]['url']    = 'payment.php' ;
			$links[1]['name']    = "还需要添加支付方式";
			$links[1]['url']    = 'javascript:history.back()';
			sys_msg($field["pay_name"].",已添加", 0, $links);
			
		}
		break;
}
		$_ACT = 'payment_list';

temp_disp();
?>