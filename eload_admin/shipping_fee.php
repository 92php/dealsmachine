<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
admin_priv('shipping_fee');
require_once('../lib/class.page.php');

$_ACT = 'list';
$_ID  = '';
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);

/*------------------------------------------------------ */
//-- 列出某地区下的所有地区列表
/*------------------------------------------------------ */
if ($_ACT == 'list')
{
    /* 获取地区列表 */
	$filter = array();
	$_return = array();
    $region_arr = area_list();
	
/*	foreach ($region_arr as $k => $row){
		$sql = "select count(id) from ".SHIPFEE." WHERE region_id = '$k'";
		if ($db->getone($sql)==0){
			$sql = "insert into `eload_shipping_fee` (org_fee,org_xu_fee,org_tran_fee,org_tran_xu_fee,exp_fee,exp_xu_fee,free_exp_fee,free_sta_fee,region_id) VALUES (27,10,27,10,30,10,14,10,'$k')";
			$db->query($sql);
		}
		
	}
	exit;
*/	$new_area = array();
    $filter['record_count'] = count($region_arr);
	$filter = page_and_size($filter);
	foreach ($region_arr as $row){
		$new_area[] = $row;
	}
	
	unset($region_arr);
	$region_arr =array();
	
	 $start    = $filter['start'];
	 $pagesize = $filter['page_size'];
	 
	 $end = ($start+$pagesize);
	 $end = $end >$filter['record_count']? $filter['record_count']:$end;
	 
	 for($i=$start;$i<$end ;$i++){
	  array_push($_return,$new_area[$i]);
	 }
     

	foreach ($_return as $row){
		$region_arr[$row['region_id']] = $row;
	}
	 
	$payArr     = read_static_cache('shipping_fee',2);
	
	$_DEFAULT = array (
		'org_fee' => '0.00',
		'org_xu_fee' => '0.00',
		'org_tran_fee' => '0.00',
		'org_tran_xu_fee' => '0.00',
		'exp_fee' => '0.00',
		'exp_xu_fee' => '0.00',
		'free_exp_fee' => '0.00',
		'free_sta_fee' => '0.00',
		'registered_mail' => '0',
		'regular_mail' => '0',
  	);
	
    foreach($region_arr as $k =>$v){
		if (empty($payArr[$k])){
			$region_arr[$k]['fee'] = $_DEFAULT;
		}else{
			$region_arr[$k]['fee'] = $payArr[$k];
		}
	}
    $Arr['region_arr'] =   $region_arr;
	
	$page=new page(array('total'=>$filter['record_count'],'perpage'=>$pagesize));
	$Arr["pagestr"]  = $page->show();
}


/*------------------------------------------------------ */
//-- 编辑地区支付方式
/*------------------------------------------------------ */

elseif ($_ACT == 'edit')
{
	$value = empty($_POST['value'])?0:floatval($_POST['value']);
	$idArr = empty($_POST['id'])?'0||0':trim($_POST['id']);
	$idArr = explode('||',$idArr);
	$rid   = $idArr[0];
	$field = $idArr[1];
	if ($field == '0') exit();
	
	$sql = "select id from ".SHIPFEE." WHERE region_id = '$rid'";
	if ($db->getone($sql)>0){
		$sql = "UPDATE  " . SHIPFEE. " SET $field = '$value' WHERE region_id = '$rid'";
		$db->query($sql);
        admin_log('', _EDITSTRING_,'地区配送运费 地区ID为'.$rid);
		creat_shippin_fee();
	}else{
		$sql = "INSERT INTO  " . SHIPFEE. " ($field,region_id) VALUES ('$value','$rid')";
		$db->query($sql);
        admin_log('', _ADDSTRING_,'地区配送运费 地区ID为'.$rid);
		creat_shippin_fee();
	}
	echo price_format($value);
	exit();
}

$_ACT = $_ACT == 'msg'?'msg':'shipping_fee_'.$_ACT;
temp_disp();


function creat_shippin_fee(){
	global $db;
    $ship_arr = array();
    $sql = 'SELECT * FROM ' . SHIPFEE. "  ORDER BY region_id";
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $ship_arr[$row["region_id"]] = $row;
    }
	write_static_cache('shipping_fee', $ship_arr,2);
}


?>