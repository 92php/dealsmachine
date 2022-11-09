<?

/*
+----------------------------------* 显示新品链接（SEO用）+----------------------------------*/

$id    = empty($_GET['id'])?'0':$_GET['id'];




	$table  ='eload_seo_link';
	require_once(ROOT_PATH . 'fun/fun.global.php');
	require_once(ROOT_PATH . 'fun/fun.public.php');
	require_once(ROOT_PATH . 'lib/lib.f.goods.php');
	require_once(ROOT_PATH . 'lib/class.page.php');
	
	
	
	require_once(ROOT_PATH . 'lib/param.class.php');
$cat_id     = Param::get('cat_id');


$html       = '';
$days = Param::get('days');
$test = Param::get('test');
$type = Param::get('type');
$limit = Param::get('limit');
if(!$cat_id) $cat_id = 'all';
if(!$limit) $limit = 200;
if($cat_id == 'all'){
		$links = array();
        $sql      = 'SELECT goods_id,goods_title,url_title FROM ' . GOODS . ' WHERE  is_delete=0 AND is_on_sale=1 AND goods_number>0 ';
       
        $t = gmtime()-$days*3600*24;
        if($days) $sql .= " and add_time >$t";
        $sql .= " order by goods_id desc";
        if($limit) $sql .=" limit $limit";
        if($test) echo $sql;
        $db->query($sql);
        
        while (($row = $db->fetchArray()) !== false) {
        	$links[]= array('url_title' => get_details_link($row['goods_id'], $row['url_title']),'goods_title'=> $row['goods_title']);
		}
		
		$Arr['links'] = $links;
		$Arr['seo_title'] = "New arrivals - dealsmachine.com";

}

?>  