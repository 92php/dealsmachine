<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require('../lib/time.fun.php');  
$uploadfile = 'E.xls';
require_once '../lib/Excel/reader.php';
$data = new Spreadsheet_Excel_Reader();
$data->setOutputEncoding('CP936');
$data->read($uploadfile);
$Arr = $data->sheets[0]['cells'];
unset($data);

$page   = empty($_GET['page'])?1:intval($_GET['page']);
$pernum = 20;
$total = count($Arr);
$total_page = ceil($total/$pernum);                                    //zong ye shu
$start      = ($page - 1) * $pernum + 1;
$end = $start + $pernum;

if (empty($Arr[$start])) {echo '完成';exit;}

for($i=$start;$i<$end;$i++){
	
	
  if (empty($Arr[$i])) {echo '完成';exit;}
	
	$_Arr['shipping_name'] = get_wuliu_method($Arr[$i][2]);
	$_Arr['shipping_no']   = $Arr[$i][2];
	$_Arr['order_sn']      = $Arr[$i][1];
	$_Arr['add_time']      = local_strtotime(str_replace('/','-',$Arr[$i][3]));
	
	//echo local_date($GLOBALS['_CFG']['time_format'],$_Arr['add_time']);
	
	$_Arr['demo']          = '';
	
	$sql = " delete from eload_shipping_details where shipping_no = '".$_Arr['shipping_no']."'";
	$db->query($sql);
	
	$sql = "select order_id,pay_time from ".ORDERINFO." where order_sn='".$_Arr['order_sn']."'";
	$odrArr = $db->selectinfo($sql);
	
	if (!empty($odrArr['order_id'])){
		$sql = "select count(*) from ".SHIPDETAILS." where shipping_no = '".$_Arr['shipping_no']."' and order_sn = '".$_Arr['order_sn']."' ";
		if (!$db->getOne($sql)){
		   $db->autoExecute(SHIPDETAILS, $_Arr);
			$sql = " order_status = '3' "; 
			$db->update(ORDERINFO,$sql," order_sn = '".$_Arr['order_sn']."'");
			echo $_Arr['order_sn'].' OK <br>';
		}
	}else{
		echo 'Order_id bu c z<br>';
	}
	
  
  
}

unset($Arr);


$page++;
echo "<META HTTP-EQUIV='Refresh' Content='1;URL=?page=".$page."'>";
exit;





function get_wuliu_method($wuliu_sn){
	if(empty($wuliu_sn)) return false;
	$code1 = strtoupper(substr($wuliu_sn,0,1));
	$code2 = strtoupper(substr($wuliu_sn,0,2));
	if($code1 == 'E'){
		return 'EMS';
	}

	if($code1 == 'H'){
		return 'UPS';
	}
	
	if($code2 == 'RT'){
		return 'HongKongPost';
	}elseif ($code2 == 'RF'){
		return 'SingPost';
	//中国小包挂号
	}elseif ($code2 == 'RR'){
		return 'ChinaPost';
	}
	
	if (strlen($wuliu_sn) == 12 || strlen($wuliu_sn) == 16 ){
		return 'Fedex';
	}elseif(strlen($wuliu_sn) == 10){
		return 'DHL';
	}elseif(strlen($wuliu_sn) == 11){
		return 'HongKongPost';
	}elseif (strlen($wuliu_sn) == 18){
		return 'ec-firstclass';
	}
}









?>

