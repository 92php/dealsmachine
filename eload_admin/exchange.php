<?php

define('INI_WEB', true);
$payment_list = "";
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
admin_priv('exchange');  //检查权限


//$arr_short=array("EUR","GBP","AUD","CHF","HKD","CNY","NZD","CAD");
$arr_short=array("EUR","CAD","GBP","AUD","RUB","BRL","HUF","ARS","MXN","ILS","UAH","CZK","NZD","INR","BGN","CLP","TRY","NGN","COP");
if(isset($_GET["act"]) && $_GET["act"] == 'getExchange'){
	$json="[";
	function getRate($short){
		global $json;
		//$url = 'http://www.google.cn/search?hl=zh-CN&newwindow=1&q=1USD%3D%3F'.$short; //加元
		//$url ='http://www.google.com.hk/search?hl=en&q=1usd%3D%3F'.$short;
        $url ='http://www.xe.com/currencyconverter/convert/?Amount=1&From=USD&To='.$short;
		$lines_array = file($url); 
		$lines_string = implode('', $lines_array); 
		eregi("(.*)", $lines_string, $head); 
		//preg_match("/美元(\s)*=(\s)*[0-9\.]+/i",$head[0],$result);
		
		//preg_match("/1\sU\.S\.\sdollar\s=(\s)[0-9\.]+/i",$head[0],$result);
        preg_match("/1&nbsp;USD&nbsp;=&nbsp;[0-9\,\.]+/i",$head[0],$result);
		$str=$result[0];
		$str=str_replace('1&nbsp;USD&nbsp;=&nbsp;', '', $str);
		$str=preg_replace('/[^\d\.]/','',$str);
		$json=$json."{'title':"."'$short',";
		$json=$json."'value'".":'".($str*1.03)."'},";//$json=$json."'value'".":'$str'},";
	}
	//$arr_short=array("EUR","GBP","AUD","CHF","HKD","CNY","NZD","CAD");
	//echo(count($arr_short));
	//die;
	foreach($arr_short as $key=>$short){
		getRate($short);
	}
	//for($a=0;$a < count($arr_short);$a++){
		//echo($arr_short[a]);
	//	 getRate($arr_short[a]);	
	//}
	
	$json=$json."{'title':'USD','value':'1'}]";
	
	die($json);

}


if(isset($_GET["act"]) && $_GET["act"] == 'save'){
	/*-------------创建汇率缓存文件 exchange.php------------------*/
	function create_exchange(){
		//global $db,$_CFG;
		$rate_arr = array();
		unset($_POST['button']);
		$date_arr = array();
		$date_arr['Rate'] = $_POST;
		$date_arr['addDate'] =date('y年m月d日 h:i:s',time());
		write_static_cache('exchange', $date_arr,2);
	}
	create_exchange();
	
	/*-----------create js  创建汇率的js 文件 currency_huilv.js---------------------*/
	function create_js_cache(){
		global $arr_short;
		$js_str = "var my_array = new Array();\r\n";
		$js_str = $js_str."my_array['USD'] = 1\r\n";
		foreach($arr_short as $key=>$ex_short){
		//foreach($arr_short as $key =>$ex_short){
			$js_str = $js_str."my_array['".$ex_short."'] = ".$_POST[$ex_short].";\r\n";
		}
		$full_path =ROOT_PATH."/data-cache/currency_huilv.js";
		//die($js_str);
		file_put_contents($full_path,$js_str,LOCK_EX);
		
        file_get_contents('http://www.bestafford.com/purge/data-cache/currency_huilv.js');    //清缓存
        file_get_contents('http://purge.faout.com:9090/peal.php?purge_url=http://www.bestafford.com/data-cache/currency_huilv.js');    //清缓存
        file_get_contents('http://acloud.faout.com/purge/data-cache/currency_huilv.js');    //清缓存  
	}
	
	create_js_cache();
	echo('<script>alert("汇率已更新");window.location.href="exchange.php"</script>');
}

$_ACT = 'exchange';
$rate_arr     = read_static_cache('exchange',2);
//print_r($rate_arr);
//die;
$Arr['Rate'] = $rate_arr['Rate'];
$Arr['addDate'] = $rate_arr['addDate'];
temp_disp();

?>