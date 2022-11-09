
<?php      
set_time_limit(0);
define('INI_WEB', true);
$_BEGINTIME = microtime(TRUE);
require('../lib/global.php');              //引入全局文件

$pernum = 1;
$page   = empty($_GET['page'])?1:intval($_GET['page']);

$total_record = $db->getOne("SELECT count(goods_id) FROM eload_goods  ");
$total_page   = ceil($total_record/$pernum);
$start        = ($page - 1) * $pernum;
if($page>$total_page) {echo '翻译完成';exit;}

$sql = "select goods_id from  eload_goods LIMIT $start ,$pernum";
$Arr = $db->arrQuery($sql);
foreach ($Arr as $row){
	
      $gframe_url = 'http://translate.google.com.hk/translate?hl=zh-CN&ie=UTF8&prev=_t&sl=en&tl=ru&u=http://www.milantrend.com/index.php%3Fm%3Dgoods_desc%26id%3D'.$row['goods_id'];
      $gframe_cur = get_fopen_url($gframe_url);

	$target_url = get_tag_data($gframe_cur,'marginheight=0><frame src="/','" name=c><noframes><script');
	
	$target_url =  'http://translate.google.com.hk/'.$target_url;
	$target_url =  str_replace('&amp;','&',$target_url);
	$g_surce = get_fopen_url($target_url);
	
	$surce_url = get_tag_data($g_surce,'content="0;URL=','"></head><body');
	$surce_url =  str_replace('&amp;','&',$surce_url);
	
	$rush_desc = get_fopen_url($surce_url);
	$rush_desc = ecs_iconv('GBK', 'UTF8', $rush_desc);
	
	$rush_desc = get_tag_data($rush_desc,'</iframe>','<script>_addload');
	
    $rush_desc = preg_replace('#<span class="google-src-text"[^>]*>(.*?)</span>#is', "", $rush_desc);
	
	$rush_desc = str_replace('<span onmouseover="_tipon(this)" onmouseout="_tipoff()">','',$rush_desc);
    $rush_desc = preg_replace("#<a[^>]*>(.*?)</a>#is", "$1", $rush_desc);
	
	echo $rush_desc;

	$rush_desc = varFilter($rush_desc);
	if(strpos($rush_desc,'Page Not Found')===false){
		//$sql = "update eload_goods set goods_desc = '".$rush_desc."' where goods_id = '".$row['goods_id']."'";
		//$db->query($sql);
	}
    

}
exit;
$page++;
echo "<META HTTP-EQUIV='Refresh' Content='3;URL=?page=".$page."'>";
exit;



//获取指定标记中的内容
function get_tag_data($str, $start, $end){
        if ( $start == '' || $end == '' ){
               return;
        }
        $str = explode($start, $str);
        $str = explode($end, $str[1]);
        return $str[0];
}





function get_fopen_url($url) 
{ 
	$ch = curl_init();
	
	//curl_setopt($ch, CURLOPT_COOKIEJAR, "E:/cookie.txt");
	//curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; FDM; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; Alexa Toolbar)");
	curl_setopt($ch, CURLOPT_URL, $url);
	
	curl_setopt($ch, CURLOPT_REFERER, "http://translate.google.com/");   
	curl_setopt($ch, CURLOPT_VERBOSE, 1);	
	curl_setopt($ch,CURLOPT_COOKIE,'PHPSESSID=fqdqdmcpr5100k4k28qc2g3034');

	//turning off the server and peer verification(TrustManager Concept).
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
//	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
//	
 	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
//	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt ($ch, CURLOPT_TIMEOUT, 1000);
	
	//curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);
	$response = curl_exec($ch);

	curl_close($ch);
	return $response;
	
}


?>

