<?
define('INI_WEB', true);
require_once('../lib/global.php');
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
mysql_query("SET time_zone = '+1:00'");  //调整MYSQL时间，为中国时间

//$mysql_time = mysql_query("SELECT NOW()");
//$mysql_rows = mysql_fetch_row($mysql_time);

//echo $mysql_rows[0];

//print_r($_SESSION);
$isping = '';
if($_SESSION["WebUserInfo"]["sa_user"] == 'haoren'){
     $isping = ' ';//and is_ping = 0
}else{
	 $isping = ' and is_dao = 0 ';
}

$_ACT = 'index';
if (!empty($_GET['act'])) $_ACT = $_GET['act'];


//=============================================
//---框架首页-----------------------------------
//=============================================
if ($_ACT=='index'){
	$Arr["webtitle"]= $_CFG['webtitle'];
}
//清除memcache缓存
elseif($_ACT=='clear_memcache_cache')
{
	//include_once( ROOT_PATH."/config/memcache_config.php");
    include_once( ROOT_PATH."/lib/CacheFactory.class.php");
    $memcache = new Cache();
    $memcache_obj = $memcache ->getInstance('memcache');
    $memcache_obj->flush();
    sys_msg('memcache缓存已经清除成功。');
}

//=============================================
//---系统菜单-----------------------------------
//=============================================
if ($_ACT=='menu'){
	$menuArr = read_static_cache('menu_c',2);
	if ($menuArr!==false){
		foreach($menuArr as $key => $val){
			if (strpos(','.$_SESSION["WebUserInfo"]["group_power"].',',','.$menuArr[$key]["action_code"].',') === false ||( $menuArr[$key]['enable'] == 0))
				unset($menuArr[$key]);
				foreach($val['_child'] as $key2 => $val2){
					if(strpos(','.$_SESSION["WebUserInfo"]["group_power"].',',','.$val['_child'][$key2]["action_code"].',') === false ||( $menuArr[$key]['_child'][$key2]['enable'] == 0))	unset($menuArr[$key]['_child'][$key2]);
				}
		}
	}
	$Arr["menuArr"] = $menuArr;
}

if ($_ACT=='clear_cache'){
    clear_all_files();
    sys_msg('页面缓存已经清除成功。');
}

if ($_ACT=='monthmainchart'){

	$xmlaount = '';
	$xmlnum = '';

	$xmlstr = "<graph caption='About a month Sales Chart' PYAxisName='Amount' SYAxisName='Quantity'
 numberPrefix=' ' showvalues='0'  numDivLines='4' formatNumberScale='0' decimalPrecision='0'
anchorSides='10' anchorRadius='3' anchorBorderColor='009900'>
<categories>";
	 for($i=30;$i>=1;$i--){
		$riqi    =  local_date('d', gmtime() - $i * 24 * 3600);
		$yrdate  =  local_date('Y-m-d', gmtime() - $i * 24 * 3600);
		$xmlstr .= "<category name='".$riqi."' />";

		$amountArr = $db->selectinfo('SELECT SUM(order_amount) as amount,count(*) as num FROM ' . ORDERINFO. " WHERE FROM_UNIXTIME(add_time,'%Y-%m-%d') = '".$yrdate."'  AND order_status > 0 and order_status < 9  $isping ");

		$xmlaount .= "<set value='".$amountArr['amount']."' />";

		$xmlnum .="<set value='".$amountArr['num']."' />";
	}

	$xmlstr .= "</categories><dataset seriesName='Amount' color='AFD8F8' showValues='0'>" . $xmlaount;
	$xmlstr .= "</dataset><dataset seriesName='Order Quantity' color='8BBA00' showValues='0' parentYAxis='S' >" . $xmlnum;
	$xmlstr .= "</dataset></graph>";

	echo $xmlstr;
	exit;
}



if ($_ACT=='yearchart'){

	$xmlaount = '';
	$xmlnum = '';
	//$now_date = local_strtotime(local_date('Y-m')); //当月开始时间
	$now_date = gmtime();
	$time_start = microtime(true);
	$xmlstr =  "<graph caption='Year Sales Chart' PYAxisName='Amount' SYAxisName='Quantity'
 numberPrefix=' ' showvalues='0'  numDivLines='4' formatNumberScale='0' decimalPrecision='0'
anchorSides='10' anchorRadius='3' anchorBorderColor='009900'>
<categories>";
    $filename = 'cache_files/' . $_ACT . '.php';

	if (!$amountArr = read_static_cache($_ACT, 2)) {//缓存3小时 by mashanling on 2012-08-17 09:34:13
        if(!IS_LOCAL)$db = get_slave_db();
    //$db->query('SET time_zone=' . MYSQL_CN_TINEZONE);
    	$sql = "SELECT SUM(order_amount) as amount,count(*) as num ,FROM_UNIXTIME(add_time,'%Y-%m') as add_month,FROM_UNIXTIME(add_time,'%m') as `date`,add_time FROM " . ORDERINFO. "
        WHERE  order_status > 0 and order_status < 9 AND add_time < $now_date
        group by add_month order by add_month DESC limit 25";
        $amountArr = $db->arrQuery($sql);
        krsort($amountArr);

        $cache_filename = get_cache_filename($_ACT, 2);
        cache($cache_filename, $amountArr, false, 10800);
    	//write_static_cache($_ACT, $amountArr, 2);
	}

	if (!empty($amountArr)){
		foreach($amountArr as $key => $val){
			$xmlstr   .= "<category name='".$val['date']."' />";
			$xmlaount .= "<set value='".$val['amount']."' />";
			$xmlnum   .= "<set value='".$val['num']."' />";
		}
	}

	$xmlstr .="</categories><dataset seriesName='Amount' color='AFD8F8' showValues='0'>".$xmlaount;
	$xmlstr .="</dataset><dataset seriesName='Order Quantity' color='8BBA00' showValues='0' parentYAxis='S' >".$xmlnum;
	$xmlstr .= "</dataset></graph>";
	echo $xmlstr;
	require('../lib/class.function.php');

    Logger::filename('index-yearchart.php');
    trigger_error($_SESSION['WebUserInfo']['real_name']);
	exit;
}








if ($_ACT=='main'){

	if (isset($_SESSION['reset_password'])) {//强制修改密码 by mashanling on 2013-04-25 11:31:32
        header('Location: protect.php?act=modify_my_password');
        exit;
    }

  strpos($_SESSION['WebUserInfo']['group_power'], ',index,') === false && exit('您好！' . $_SESSION['WebUserInfo']['real_name']);
    $time            = local_strtotime(local_date('Y-m-d'));
    $yesterday_time  = $time - 86400;
    $week_time       = $time - 86400 * 7;
    $today_num       = 0;
    $today_pay_num   = 0;
    $today_amount    = 0.00;
    $today_pay_amount= 0.00;
    $today_pay_gailv = 0.00;

    $yesterday_num       = 0;
    $yesterday_pay_num   = 0;
    $yesterday_amount    = 0.00;
    $yesterday_pay_amount= 0.00;
    $yesterday_pay_gailv = 0.00;

    $week_num       = 0;
    $week_pay_num   = 0;
    $week_amount    = 0.00;
    $week_pay_amount= 0.00;
    $week_pay_gailv = 0.00;

    $finished_num   = 0;
    $await_ship_num = 0;
    $await_pay_num  = 0;

    $order = array();
    $order['yes'] = 'yes';

    $info = $db->select(ORDERINFO, 'add_time,order_amount,order_status', 'add_time>' . $week_time);
    $i    = 0;
    $time_start = microtime(true);
    foreach ($info as $item) {
        $add_time = $item['add_time'];
        $status   = $item['order_status'];
        $amount   = $item['order_amount'];
        $payed    = $status > 0 && $status < 9;


        if ($add_time > $time) {//今天
            $today_amount += $amount;
            $today_num++;

            if ($payed) {
                $today_pay_amount += $amount;
                $today_pay_num++;
            }
        }
        elseif($add_time > $yesterday_time) {//昨天
            $yesterday_amount += $amount;
            $yesterday_num++;

            if ($payed) {
                $yesterday_pay_amount += $amount;
                $yesterday_pay_num++;
            }
        }

        if($add_time > $week_time) {
            $week_amount += $amount;
            $week_num++;

            if ($payed) {
                $week_pay_amount += $amount;
                $week_pay_num++;
            }
        }

        /*if ($payed) {
            $finished_num++;
        }
        elseif ($status == 1) {
            $await_ship_num++;
        }
        elseif ($status == 0) {
            $await_pay_num++;
        }*/

    }

    if ($today_num) {
        $today_pay_gailv = round(($today_pay_num / $today_num) * 100, 2);
    }

    if ($yesterday_num) {
        $yesterday_pay_gailv = round(($yesterday_pay_num / $yesterday_num) * 100, 2);
    }

    if ($week_num) {
        $week_pay_gailv = round(($week_pay_num / $week_num) * 100, 2);
    }

    unset($info);
    $order['today_num']         = $today_num;
    $order['today_amount']      = $today_amount;
    $order['today_pay_num']     = $today_pay_num;
    $order['today_pay_amount']  = $today_pay_amount;
    $order['today_pay_gailv']   = $today_pay_gailv . '%';

    $order['yesday_num']         = $yesterday_num;
    $order['yesday_amount']      = $yesterday_amount;
    $order['yesday_pay_num']     = $yesterday_pay_num;
    $order['yesday_pay_amount']  = $yesterday_pay_amount;
    $order['yesday_pay_gailv']   = $yesterday_pay_gailv . '%';

    $order['week_num']         = $week_num;
    $order['week_amount']      = $week_amount;
    $order['week_pay_num']     = $week_pay_num;
    $order['week_pay_amount']  = $week_pay_amount;
    $order['week_pay_gailv']   = $week_pay_gailv . '%';

    /* 已完成的订单 */
    $order['finished'] = $db->GetOne('SELECT COUNT(*) FROM ' . ORDERINFO . " WHERE order_status > 0 and order_status < 9 ");
    $status['finished'] = 4;
    /* 待发货的订单： */
    $order['await_ship'] = $db->GetOne('SELECT COUNT(*)' . ' FROM ' . ORDERINFO . " WHERE  order_status = 1 ");
    $status['await_ship'] = 2;
    /* 待付款的订单： */
    $order['await_pay'] = $db->GetOne('SELECT COUNT(*)' . ' FROM ' . ORDERINFO . " WHERE  order_status = 0  ");
    $status['await_pay'] = 0;
    $order['stats'] = $db->selectinfo('SELECT COUNT(*) AS oCount, IFNULL(SUM(order_amount), 0) AS oAmount' . ' FROM ' . ORDERINFO);
    $Arr['order'] = $order;
    $Arr['status'] = $status;
    /* 商品信息 */
    $goods['total'] = $db->GetOne('SELECT COUNT(*) FROM ' . GOODS . ' WHERE is_delete = 0 ');
    $goods['new'] = 0;
    $goods['best'] = 0;
    $goods['hot'] = $db->GetOne('SELECT COUNT(*) FROM ' . GOODS . ' WHERE is_delete = 0 AND is_hot = 1 ');
    $time = gmtime();
    //  $goods['promote'] = $db->GetOne('SELECT COUNT(*) FROM ' .GOODS.' WHERE is_delete = 0 AND promote_price>0' .  " AND promote_start_date <= '$time' AND promote_end_date >= '$time' ");
    /* 缺货商品 */
    if ($_CFG['use_storage']) {
        $sql = 'SELECT COUNT(*) FROM ' . GOODS . ' WHERE is_delete = 0 AND goods_number <= warn_number ';
        $goods['warn'] = $db->GetOne($sql);
    }
    else {
        $goods['warn'] = 0;
    }
    $Arr['goods'] = $goods;
    /* 最近反馈 */
    $sql = "SELECT COUNT(*) from eload_pro_inquiry where is_pass =0 " ;
    $Arr['feedback_number']= $db->GetOne($sql);

    /* 未审核评论 */

    $Arr['comment_number']= $db->getOne('SELECT COUNT(*) FROM ' . REVIEW .
    ' WHERE is_pass = 0' );
    //$mysql_ver = $db->version();   // 获得 MySQL 版本
//$Arr['ecs_lang']=  $_CFG['lang'];
}



if ($_ACT=='show_month_html'){

	$daynum = !empty($_GET['day'])?intval($_GET['day']):30;
	$xmlaount = '';
	$xmlnum = '';

	$xmlstr = '<table width="100%" border="1" cellspacing="0" cellpadding="0">
	<tr>
    <td>日期</td>
    <td>总数</td>
    <td>已付款数</td>
    <td>付款数概率</td>
    <td>总金额</td>
    <td>已付款金额</td>
  </tr>
';
	 for($i=$daynum;$i>=1;$i--){
		$riqi    =  local_date('d', gmtime() - $i * 24 * 3600);
		$yrdate  =  local_date('Y-m-d', gmtime() - $i * 24 * 3600);

		$payedArr = $db->selectinfo('SELECT SUM(order_amount) as amount,count(*) as num FROM ' . ORDERINFO. " WHERE FROM_UNIXTIME(add_time,'%Y-%m-%d') = '".$yrdate."'  AND order_status > 0 and order_status < 9 ");
		$allArr = $db->selectinfo('SELECT SUM(order_amount) as amount,count(*) as num FROM ' . ORDERINFO. " WHERE FROM_UNIXTIME(add_time,'%Y-%m-%d') = '".$yrdate."' ");

		$padpder = !empty($allArr['num'])?(round($payedArr['num']/$allArr['num'],4))*100:0;

		$xmlstr .= "<tr>";
		$xmlstr .= "<td>".$yrdate."</td>";
		$xmlstr .= "<td>".$allArr['num']."</td>";
		$xmlstr .= "<td>".$payedArr['num']."</td>";
		$xmlstr .= "<td>".$padpder." %</td>";
		$xmlstr .= "<td>".$allArr['amount']."</td>";
		$xmlstr .= "<td>".$payedArr['amount']."</td>";
		$xmlstr .= "</tr>";
	}

	$xmlstr .= "</table>";

	echo $xmlstr;
	exit;
}



//除0.01 订单统计

if ($_ACT=='mc'){

	$xmlaount = '';
	$xmlnum = '';

	$xmlstr = "";

	$sql = "select FROM_UNIXTIME(add_time,'%Y-%m') AS mon from " . ORDERINFO. "   group by mon order by mon ASC";
	//echo $sql;
//	exit;
	$monArr = $db->arrQuery($sql);

	foreach($monArr as $key => $mon){

		$xmlstr .=  $mon['mon'].":";
		$amountArr = $db->selectinfo('SELECT SUM(order_amount) as amount,count(*) as num FROM ' . ORDERINFO. " WHERE FROM_UNIXTIME(add_time,'%Y-%m') = '".$mon['mon']."'  AND order_status > 0 and order_status < 9  and  order_amount <> 0.01 $isping and is_dao = 0 ");

		$xmlstr .= " 金额： ".$amountArr['amount']."  ";

		$xmlstr .=" 数量：".$amountArr['num']." <br> ";

	}

	$xmlstr .= "";

	echo $xmlstr;
	exit;
}



if ($_ACT=='user'){

	$xmlaount = '';
	$xmlnum = '';
	$monnum = !empty($_GET['mon'])?intval($_GET['mon']):5;

	$xmlstr = "";

	$sql = "select FROM_UNIXTIME(add_time,'%Y-%m') AS mon from " . ORDERINFO. " group by mon order by mon ASC";
	//echo $sql;
//	exit;
	$monArr = $db->arrQuery($sql);

	foreach($monArr as $key => $mon){
		$yrdate = $mon['mon'];
		$xmlstr .=  $yrdate.":";


		$sql = "SELECT count( * ) AS dan  FROM eload_order_info  WHERE order_status >0  AND order_status <9 and FROM_UNIXTIME(add_time,'%Y-%m') = '".$yrdate."' GROUP BY user_id HAVING dan > 0";
		//echo $sql.'<br>';
		$amountArr = $db->getOne("select count(*) from ( SELECT count( * ) AS dan  FROM eload_order_info  WHERE order_status >0  AND order_status <9 and FROM_UNIXTIME(add_time,'%Y-%m') = '".$yrdate."' GROUP BY user_id HAVING dan > 0 ) a");


		$xmlstr .=" 成功交易一单以上的用户数量：".$amountArr." <br> ";

	}

	$xmlstr .= "";

	echo $xmlstr;
	exit;
}



if ($_ACT=='newuser'){

	$xmlaount = '';
	$xmlnum = '';
	$monnum = !empty($_GET['mon'])?intval($_GET['mon']):5;

	$xmlstr = "";

	$sql = "select FROM_UNIXTIME(reg_time,'%Y-%m') AS mon from " . USERS. " group by mon order by mon ASC";
	//echo $sql;
//	exit;
	$monArr = $db->arrQuery($sql);

	foreach($monArr as $key => $mon){
		$yrdate = $mon['mon'];
		$xmlstr .=  $yrdate.":";

		$amountArr = $db->getOne("select count(*) from ".USERS ."  WHERE FROM_UNIXTIME(reg_time,'%Y-%m') = '".$yrdate."' and last_login > 0");


		$xmlstr .=" 注册用户数量：".$amountArr." <br> ";

	}

	$xmlstr .= "";

	echo $xmlstr;
	exit;
}

if ($_ACT=='tempuser'){

	$xmlaount = '';
	$xmlnum = '';
	$monnum = !empty($_GET['mon'])?intval($_GET['mon']):5;

	$xmlstr = "";

	$sql = "select FROM_UNIXTIME(reg_time,'%Y-%m') AS mon from  eload_users_temp group by mon order by mon ASC";
	//echo $sql;
//	exit;
	$monArr = $db->arrQuery($sql);

	foreach($monArr as $key => $mon){
		$yrdate = $mon['mon'];
		$xmlstr .=  $yrdate.":";

		$amountArr = $db->getOne("select count(*) from  eload_users_temp WHERE FROM_UNIXTIME(reg_time,'%Y-%m') = '".$yrdate."' and last_login > 0");


		$xmlstr .=" 注册用户数量：".$amountArr." <br> ";

	}

	$xmlstr .= "";

	echo $xmlstr;
	exit;
}

/**
 *  清除指定后缀的模板缓存或编译文件
 *
 * @access  public
 * @param  bool       $is_cache  是否清除缓存还是清出编译文件
 * @param  string     $ext       需要删除的文件名，不包含后缀
 *
 * @return int        返回清除的文件个数
 */
function clear_tpl_files($ext = '')
{
    $dirs = array();
	$dirs[] = ROOT_PATH.'temp_cache/skin2/' ;

    $str_len = strlen($ext);
    $count   = 0;
    foreach ($dirs AS $dir)
    {
        $folder = @opendir($dir);

        if ($folder === false)
        {
            continue;
        }

        while ($file = readdir($folder))
        {
            if ($file == '.' || $file == '..' || $file == 'index.htm' || $file == 'index.html')
            {
                continue;
            }
            if (is_file($dir . $file))
            {
				if (@unlink($dir . $file))
				{
					$count++;
				}
            }
        }
        closedir($folder);
    }

    return $count;
}


/**
 * 清除模版编译和缓存文件
 *
 * @access  public
 * @param   mix     $ext    模版文件名后缀
 * @return  void
 */
function clear_all_files($ext = '')
{
    return clear_tpl_files($ext);
}










temp_disp();
?>