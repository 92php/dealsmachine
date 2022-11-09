<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
$_ACT = 'list';
$_ID  = '';
$goods_id = 0;
if (!empty($_GET['act'])) $_ACT   = trim($_GET['act']);
if (!empty($_GET['id'])) $_ID     = intval(trim($_GET['id']));
if (!empty($_GET['goods_id'])) $goods_id     = intval(trim($_GET['goods_id']));

if ($_ACT == 'list')
{
    admin_priv('goods_tj');

    if (isset($_POST) && !empty($_POST))
    {
        $start_date = $_POST['start_date'];
        $end_date   = $_POST['end_date'];
    }
    else
    {
        $start_date = local_date('Y-m-d', strtotime('-30 day'));
        $end_date   = local_date('Y-m-d');
    }
	
	$Arr['start_date'] = $start_date;
	$Arr['end_date']   = $end_date;
	
	$start_date = strtotime($start_date);
	$end_date = strtotime($end_date);
	
	$sql = "select access_url,sum(visit_times) as times from eload_stats where access_time >= '$start_date' and access_time < '$end_date' group by access_url order by times desc";
	
	$arr = $db->arrQuery($sql);
	foreach ($arr as $key => $row){
		$goods_id = $row['access_url'];
		$sql = "select if(area='[未知IP0801]',ip_address,area) as area,sum(visit_times)  as times from eload_stats where access_time >= '$start_date' and access_time < '$end_date' and access_url = '$goods_id' group by area order by times desc ";
		$arr[$key]['arealist'] = $db->arrQuery($sql);
	}
	$Arr['his_list'] = $arr;
}


$_ACT = $_ACT == 'msg'?'msg':'goods_tj_'.$_ACT;
temp_disp();

?>