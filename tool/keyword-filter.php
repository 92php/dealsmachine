<?php
/*删除abc数据库里结果为空的关键词

*/
set_time_limit(0);
$page  = empty($_GET['page'])?0:$_GET['page'];
$p_len = empty($_GET['p_len'])?100:$_GET['p_len'];
$start = $page*$p_len;

define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once(ROOT_PATH . 'lib/sphinxapi.php');

$sql = "select * from eload_kw where id between $start and $start+$p_len";
echo $sql;
$arr = $db->arrQuery($sql);
$del_key ="";
$del_count=0;
foreach ($arr as  $k=>$v ){
	$cl = new SphinxClient();    //实例化sphinx
	$cl->SetConnectTimeout(2);
		//$cl->SetServer('208.109.108.59', 9312);    //链接sphinx
		//$cl->SetServer('184.173.114.244',9312);    //链接sphinx
	$cl->SetServer(SPH_HOST,SPH_PORT);    //链接sphinx
	$cl->SetMatchMode(SPH_MATCH_ANY);
	$sort ='goods_number DESC,@weight DESC,week2sale DESC';
	$cl->SetSortMode(SPH_SORT_EXTENDED, $sort);    //排序
	$keyword = $v['keyword'];
    $_query    = preg_match('/\s+/', $keyword) ? str_replace(' ', '* *', $keyword) : $keyword;
    $_query    = $keyword == '' ? '' : "*{$_query}*";	
	
	$result = $cl->Query($_query, SPH_INDEX_MAIN);    //添加时间段查询，附空查询
	if($result['total_found']==0){
		$db->delete('eload_kw',"id=$v[id]");
		$del_count +=1;
		$del_key .="$v[keyword],";
	}
	
	//echo $keyword;

	
}
$page +=1;
echo "del keyword:$del_key,now process page $page";
	//exit();
echo "<script>window.location.href='?page=$page'</script>"
?>
