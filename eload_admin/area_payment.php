<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
admin_priv('area_payment');


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
	$payArr     = read_static_cache('payment', ADMIN_STATIC_CACHE_PATH);
    $Arr['region_arr'] =   $region_arr;
    $Arr['payArr'] =   $payArr;

    //print_r($region_arr);
}


/*------------------------------------------------------ */
//-- 编辑地区支付方式
/*------------------------------------------------------ */

elseif ($_ACT == 'payment')
{
    $val    = trim($_GET['val']);
	$sql    = "SELECT payment FROM " . REGION. "  WHERE region_id = '$_ID'";
	$payArr = $db-> selectinfo($sql);
	if (trim($payArr["payment"])=='')
	{
		$payment = ",$val";
	}
	else
	{
		if (strpos(','.$payArr["payment"],','.$val) === false)
		{
			$payment = $payArr["payment"].','.$val;
		}
		else
		{
			$payment = str_replace(','.$val,'',$payArr["payment"]);
		}

	}
    $sql = "UPDATE  " . REGION. " SET payment = '$payment' WHERE region_id = '$_ID'";
    if ($db->query($sql)){
        admin_log('', _EDITSTRING_,'地区支付方式 '.$payment);
		creat_area();
		exit();
    }
}

$_ACT = $_ACT == 'msg'?'msg':'area_payment_'.$_ACT;
temp_disp();
?>