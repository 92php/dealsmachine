<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
admin_priv('area_shipping');


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
	$payArr     = read_static_cache('shipping_method', ADMIN_STATIC_CACHE_PATH);

    $Arr['region_arr'] =   $region_arr;
    $Arr['payArr'] =   $payArr;
}


/*------------------------------------------------------ */
//-- 编辑地区支付方式
/*------------------------------------------------------ */

elseif ($_ACT == 'shipping')
{
    $val    = trim($_GET['val']);
	$sql    = "SELECT shipping FROM " . REGION. "  WHERE region_id = '$_ID'";
	$payArr = $db-> selectinfo($sql);
	if (trim($payArr["shipping"])=='')
	{
		$shipping = ",$val";
	}
	else
	{
		if (strpos(','.$payArr["shipping"],','.$val) === false)
		{
			$shipping = $payArr["shipping"].','.$val;
		}
		else
		{
			$shipping = str_replace(','.$val,'',$payArr["shipping"]);
		}

	}
    $sql = "UPDATE  " . REGION. " SET shipping = '$shipping' WHERE region_id = '$_ID'";
    if ($db->query($sql)){
        admin_log('', _EDITSTRING_,'地区配送方式 '.$shipping);
		creat_area();
		exit();
    }
}

$_ACT = $_ACT == 'msg'?'msg':'area_shipping_'.$_ACT;
temp_disp();

?>