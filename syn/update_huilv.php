<?php
define('INI_WEB', true);
set_time_limit(0);
require_once('../lib/global.php');
require_once('../lib/time.fun.php');
//$arr_short=array("RUB","EUR","GBP","AUD","CHF","HKD","CNY","NZD","CAD","BRL");
$arr_short=array("EUR","CAD","GBP","AUD","RUB","BRL","HUF","ARS","MXN","ILS","UAH","CZK","NZD","INR","BGN","CLP","TRY","NGN","COP");
//获取汇率
function getExchange()
{
    global $arr_short;
	$keyArr = array();
	foreach($arr_short as $val){
		$keyArr[] = $val.'/USD';
	}
	$reArr = array();
	$url = 'http://themoneyconverter.com/rss-feed/USD/rss.xml';
	$result = @file_get_contents($url);
	$xml = simplexml_load_string($result); //读取xml文件
	if(!empty($xml)){
		$obj = $xml->channel->item;
		foreach($obj as $row){
			if (in_array($row->title,$keyArr)){
				$title = str_replace('/USD','',$row->title.'');
				$description = $row->description.'';
				$description =str_replace('1 United States Dollar = ','',$description);
				$description=preg_replace('/[^\d\.]/','',$description);
				$reArr[$title] = $description*1.03;
			}
		}
		$reArr['USD'] = 1;
	}
    return $reArr;
}

//更新汇率
/*-------------创建汇率缓存文件 exchange.php------------------*/
function create_exchange($arr){
    $date_arr = array();
    $date_arr['Rate'] = $arr;
    $date_arr['addDate'] =date('y年m月d日 h:i:s',time());
    write_static_cache('exchange', $date_arr,2);
}
/*-----------create js  创建汇率的js 文件 currency_huilv.js---------------------*/
function create_js_cache($arr){
    global $arr_short;
    $js_str = "var my_array = new Array();\r\n";
    $js_str = $js_str."my_array['USD'] = 1\r\n";
    foreach($arr_short as $key=>$ex_short){
        $js_str = $js_str."my_array['".$ex_short."'] = ".$arr[$ex_short].";\r\n";
    }
    $full_path =ROOT_PATH."/data-cache/currency_huilv.js";
    file_put_contents($full_path,$js_str,LOCK_EX);
    file_put_contents($full_path,$js_str,LOCK_EX);
    file_get_contents('http://www.dealsmachine.com/purge/data-cache/currency_huilv.js');    //清缓存
	file_get_contents('http://purge.faout.com:9090/peal.php?purge_url=http://www.dealsmachine.com/data-cache/currency_huilv.js');    //清缓存
	file_get_contents('http://acloud.faout.com/purge/data-cache/currency_huilv.js');    //清缓存    
}

function save($arr)
{
	create_exchange($arr);
	create_js_cache($arr);
	echo("汇率已更新");
}
$arr=getExchange();print_r($arr);
if($arr){save($arr);}
?>