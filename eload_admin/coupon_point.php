<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
admin_priv('promotion_manage');  //检查权限

$_ACT = 'list';
$_ID  = '';

if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));

/*------------------------------------------------------ */
//-- 管理界面
/*------------------------------------------------------ */
if ($_ACT == 'list')
{
    $pcode_list = pcode_list();
    $Arr["pcode_list"]  =  $pcode_list['type'];
    $Arr["filter"]  =     $pcode_list['filter'];
	$page=new page(array('total'=>$pcode_list['record_count'],'perpage'=>$pcode_list['page_size']));
	$Arr["pagestr"]  = $page->show();
	
}


/*------------------------------------------------------ */
//-- 添加促销码
/*------------------------------------------------------ */

elseif ($_ACT == 'save')
{

	//$url = "?act=insert&id=$_ID";
	$code_number = empty($_POST['code_number'])?'':$_POST['code_number'];


	$pid = empty($_GET['pid'])?0:$_GET['pid'];
	//	echo $pid;

	$Arr['code_number'] = $code_number;
	$Arr['points'] = empty($_POST['points'])?0:intval($_POST['points']);
	$Arr['adddate'] = gmtime();
	$Arr['deadline'] = strtotime($_POST['deadline']);
	//	print_r($Arr);
	//die();
	if(empty($Arr['code_number'])||empty($Arr['points'])||empty($Arr['adddate'])||empty($Arr['deadline']))
	{
		echo "<script>alert('请填写完整');location.href='".$_SERVER['HTTP_REFERER']."'</script>";
		exit();
	}
	echo 000;
	if($pid){  //更新
		if($db->autoExecute(POINT_COUPON,$Arr,'UPDATE',"pid=$pid")){
			admin_log($sn = '', _EDITSTRING_, '积分优惠券 '.$code_number);
		}
		
		echo "<script>alert('更新成功');location.href='".$_SERVER['HTTP_REFERER']."'</script>";
		exit();
	}else {//新增
		if($db->autoExecute(POINT_COUPON,$Arr)){
			admin_log($sn = '', _ADDSTRING_, '积分优惠券 '.$code_number);
		}
		echo "<script>alert('添加成功');location.href='coupon_point.php'</script>";
		exit();
	}

	//$Arr['code_number'] = $_GET['code_number'];
}

elseif ($_ACT == 'remove')
{

	$pid = empty($_GET['pid'])?0:$_GET['pid'];
	//echo $pid;
	if($db->delete(POINT_COUPON,"pid=$pid")){
		admin_log($sn='', _DELSTRING_, '积分优惠券：'.$pid);
		echo "<script>alert('删除成功');location.href='".$_SERVER['HTTP_REFERER']."'</script>";
		exit();;
	}
	
	
	//$url = "?act=insert&id=$_ID";

	//$Arr['code_number'] = $_GET['code_number'];
}
function pcode_list()
{
	global $db;
	/* 记录总数以及页数 */
	$filter['record_count'] = 0 ;
	$filter['record_count'] = $db->count_info(POINT_COUPON,"*","");
	$filter = page_and_size($filter);
	/* 查询记录 */
	
	$sql = "SELECT * ".
	   "FROM ". POINT_COUPON. " order by pid desc limit $filter[start],$filter[page_size]";
	   
	$all = $db->arrQuery($sql);
	
	foreach($all as $key => $val){
		$all[$key]['deadline'] = local_date('Y-m-d', $all[$key]['deadline']);
		$all[$key]['adddate'] = local_date('Y-m-d', $all[$key]['adddate']);
		$all[$key]['email']		= email_disp_process($val['email']);
	}	
    return array('type' => $all, 'filter' => $filter, 'page_size'=> $filter['page_size'], 'record_count' => $filter['record_count']);
}

$_ACT = $_ACT == 'msg'?'msg':'coupon_point_'.$_ACT;
temp_disp();

?>
