<?php
ob_start();
/**
 * 刘念datafeed数据导出
 * */
/*
define('INI_WEB', true);
require_once('config/config.php');              //引入全局文件
require_once('lib/global.php');              //引入全局文件
require_once('lib/time.fun.php');
require_once('lib/cls_image.php');
require_once('lib/syn_public_fun.php');
require_once('lib/csv.class.php');*/
define('INI_WEB', true);
require_once('../lib/global.php');//引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');
require_once('../lib/csv.class.php');
require_once('lib/common.fun.php');

// 语言
$lang = get_lang();
$lang = check_lang_power($lang);
$Arr['lang_arr'] = $lang;
$default_power_lang = check_default_lang_power($lang);
$Arr['default_lang'] = $default_power_lang;

$_ACT = 'list';
if (!empty($_POST['act'])) $_ACT   = trim($_POST['act']);
if ($_ACT == 'list')
{	
	$Arr['orher_cat_list'] = cat_list(0);
	$_ACT = "data_feed";
	$data = read_static_cache('datafeed_filter_sku',1);
	if(!empty($data)){
		$Arr['data'] = implode(',',$data);
	}	
}

if ($_ACT == 'import')
{
    $exchangeArr = read_static_cache('exchange',2);
    $exchangeArr['Rate']['USD'] = 1;
    $currency = $_POST['currency'];
	$type = isset($_POST['type'])?trim($_POST['type']):'excl';
	$typeArray =  read_static_cache('category_c_key',2);
	if($type=="excl"){
		set_time_limit(0);
		flush();
		$fparts = explode('.', $_FILES['excelfile']['name']);
		$file_suffix = strtolower(trim(end($fparts)));
		$mime_type = array('xls'=> array('application/excel','application/vnd.ms-excel','application/msexcel','application/octet-stream','application/kset'));
		if($file_suffix != 'xls' && !in_array($_FILES['excelfile']['type'], $mime_type['xls'])){
		 sys_msg("文件格式错误，此处必须要上传EXCEL文件！", 1, array(), false);
		}				
		require_once '../lib/Excel/reader.php';
		$data = new Spreadsheet_Excel_Reader();
		$data->setOutputEncoding('UTF-8');
		$data->read($_FILES['excelfile']['tmp_name']);
		$keyw   = '';
		$keyw_1 = '';
		foreach($data->sheets[0]['cells'] as $k => $v)
		{
			$key =  trim($v[1]);
			$keyw .= "'".$key."',";
			$keyw_1 .=$key.",";
		}
		$keyw = rtrim($keyw,",");
		$keyw_1 = str_replace("','", ',', rtrim($keyw_1,","));
		$goods_sn = $keyw;				
		$sql = "SELECT g.goods_sn as goods_sn,
		               g.goods_sn as id ,
					   g.goods_title as title ,
					   g.goods_desc as description ,
					   g.market_price as price ,
					   g.goods_id as link ,
					   g.original_img as image_link,
					   ga.attr_value as color ,
					   gg.attr_value as size,
					   CASE when g.is_free_shipping =1 THEN 0 ELSE g.goods_weight END as shipping_weight,
					   g.shop_price as sale_price ,
					   g.goods_grid as additional_image_link ,
					   c.cat_name as product_type,
					   c.level,
					   c.parent_id,
					   g.promote_price,
					   g.promote_start_date,
					   g.promote_end_date,
					   g.cat_id
				FROM eload_goods as g left join eload_category as c on g.cat_id = c.cat_id
					   left join eload_goods_attr as ga on g.goods_id = ga.goods_id and ga.attr_id = 63 left join eload_goods_attr as gg on g.goods_id = gg.goods_id and gg.attr_id = 64
				WHERE g.goods_sn in ($goods_sn) and is_delete = 0 and is_on_sale = 1 and goods_number > 0 order by find_in_set(g.goods_sn,'$keyw_1') ";
		//echo $sql;exit;
		$a=$db->arrQuery($sql);
		//print_r($a);exit;
		empty($a) && exit('无商品');
		$b=array();
		$data = "goods=";
		foreach ($a as $k=>$v)
		{
			$data.=$v['id']."|".$v['image_link']."|".$v['additional_image_link']."@";
		}
		$data = substr($data,0,-1);
		$data .= "&act=check_img";
		$reinfo = post_image_info("http://sourceimg02.faout.com/code/api_check_datafeed.php",$data);
		$garr = '';
		if($reinfo)
		{
			$garr=explode(",", $reinfo);
		}
		foreach ($a as $k=>$v){
			if(is_array($garr) && in_array($v['id'],$garr))
			{
				continue;
			}
			$CURR=$currency;
			if($currency)
			{
				$v['price'] = price_format($v['price']*$exchangeArr['Rate'][$currency],false);
				$v['sale_price'] = price_format($v['sale_price']*$exchangeArr['Rate'][$currency],false);
				$v['promote_price'] = price_format($v['promote_price']*$exchangeArr['Rate'][$currency],false);
				if($currency=='USD' || $currency=='CAD'){$ZONE=-5;$ZONE_1='-0500';}
				if($currency=='GBP' || $currency=='EUR'){$ZONE=0;$ZONE_1='+0000';}
				if($currency=='CNY' || $currency=='HKD'){$ZONE=8;$ZONE_1='+0800';}
				if($currency=='AUD'){$ZONE=10;$ZONE_1='+1000';}
				if($currency=='RUB'){$ZONE=3;$ZONE_1='+0300';}
				if($currency=='NZD'){$ZONE=12;$ZONE_1='+1200';}
				$v['promote_start_date'] = $v['promote_start_date']+3600*$ZONE;
				$v['promote_end_date'] = $v['promote_end_date']+3600*$ZONE;
			}
	
			$cat2='';
			$b[$k]['id'] =$v['id'];
			$b[$k]['title'] = preg_replace('/[^\w\.\(\)]/',' ',$a[$k]['title']);
			$v['description'] = str_replace("\n",' ',$v['description']);
			$v['description'] = str_replace("\r",' ',$v['description']);
			$v['description'] = str_replace("\t",' ',$v['description']);
			$b[$k]['description'] = trim(strip_tags($v['description']));
			$b[$k]['condition'] = 'New';
	
			if($v['promote_price']>0 && time()<$v['promote_end_date'])
			{
				 $b[$k]['price'] = $CURR.$v['price'];
			}
			else
			{
				 $b[$k]['price'] = $CURR.$v['sale_price'];
			}
			$b[$k]['availability'] = 'In Stock';
			$b[$k]['link'] = 'http://www.dealsmachine.com/best_'.$v['link'].'.html?currency=' . $currency;
			$b[$k]['image_link'] = 'http://img.dealsmachine.com/'.str_replace('E','uploads',$v['image_link']);
			$b[$k]['gtin'] = '';
			$b[$k]['mpn'] = $v['id'];
			$b[$k]['brand'] = 'everbuying';
			$b[$k]['google product category'] = 'Electronics > Communications > Telephony > Mobile Phones';
			$b[$k]['gender'] = '';
			$b[$k]['age group'] = '';
			$b[$k]['size'] = $v['size'];
			$b[$k]['color'] = $v['color'];
			$b[$k]['material'] = '';
			$b[$k]['pattern'] = '';
			$b[$k]['item group id'] = '';
			$b[$k]['tax'] = '';
			$b[$k]['shipping'] = '';
			$b[$k]['shipping_weight'] = $v['shipping_weight'].'kg';
			if($v['promote_price']>0 && time()<$v['promote_end_date'])
			{
				$b[$k]['sale_price'] = $CURR.$v['promote_price'];
				$b[$k]['sale price effective date'] = date("Y-m-d",$v['promote_start_date'])."T".date("H:i",$v['promote_start_date']).$ZONE_1."/".date("Y-m-d",$v['promote_end_date'])."T".date("H:i",$v['promote_end_date']).$ZONE_1;
			}
			else
			{
				$b[$k]['sale_price'] = '';
				$b[$k]['sale price effective date'] = '';
			}
		   $b[$k]['additional_image_link'] = 'http://img.dealsmachine.com/'.$v['additional_image_link'];
		   $b[$k]['product_type'] = $v['product_type'];
		}
		if(!empty($b)){
			array_unshift($b, array_keys($b[0]));
		}
		$csvfile = new csvDataFile("../datafeed/datafeed.csv", ",", "w");
		$csvfile->printcsv($b);
		echo '生成成功，下载地址<a href="http://www.dealsmachine.com/datafeed/datafeed.csv?t='.time().'" target="_blank">http://www.dealsmachine.com/datafeed/datafeed.csv</a>';				
	}
	if($type == "category"){
		//过滤设定不需要的产品 fangxin 2013/09/26
		$filter_sku      = read_static_cache('datafeed_filter_sku',1);
		$filter_goods_id = get_goods_id($filter_sku);				
		$cat_array = !empty($_POST['other_cat'])?$_POST['other_cat']:'';
		$limit = !empty($_POST['number'])?intval($_POST['number']):1500;
		$lang = !empty($_POST['lang'])?$_POST['lang']:'';
		if(!empty($cat_array)){
			$catArr = read_static_cache('category_c_key',2);
			foreach($catArr as $row){
				if(in_array($row['parent_id'],$cat_array)){
					$cat_array[] = $row['cat_id'];
				}
			}
			$sql = "SELECT DISTINCT(g.goods_sn), g.goods_sn as id ,";
			if($lang == 'en') {
				$sql .= "g.goods_title as title ,
 						 g.goods_desc as description ,";		
			} else {
				$sql .= "gl.goods_title as title ,
				         gl.goods_desc as description ,";		
			}			
				$sql .=	"g.market_price as price ,
					   g.goods_id as link ,
					   g.original_img as image_link,
					   ga.attr_value as color ,
					   gg.attr_value as size,
					   CASE when g.is_free_shipping =1 THEN 0 ELSE g.goods_weight END as shipping_weight,
					   g.shop_price as sale_price ,
					   g.goods_grid as additional_image_link ,
					   c.cat_name as product_type,
					   c.level,
					   c.parent_id,
					   g.promote_price,
					   g.promote_start_date,
					   g.promote_end_date,
					   g.cat_id FROM eload_goods as g";		   
			//$sql .=	" FROM eload_goods as g"; 	
			if($lang <> 'en') {
				$sql .= " left join eload_goods_". $lang ." as gl on g.goods_id = gl.goods_id";		
			}							
				$sql .=	" left join eload_category as c on g.cat_id = c.cat_id
					    left join eload_goods_attr as ga on g.goods_id = ga.goods_id and ga.attr_id = 63 
					    left join eload_goods_attr as gg on g.goods_id = gg.goods_id and gg.attr_id = 64
					WHERE g.cat_id in (".implode(',',$cat_array).") and is_delete = 0 and is_on_sale = 1 and goods_number > 10 and g.add_time > 1327939200";
				if($lang <> 'en') {
					$sql .= " and gl.goods_title IS NOT NULL";			
				}					
				if(!empty($filter_goods_id)) {
					$sql .= " and g.goods_id not in(". $filter_goods_id .") ";
				}
				$sql .= " order by week2sale desc,click_count desc";	
				if($lang == 'en') {
					$sql .= " limit ".$limit;			
				}						
		}else{
			exit("没有选择分类");
		}	
		$a=$db->arrQuery($sql);
		empty($a) && exit('无商品');
		$b=array();
		$data = "goods=";
		foreach ($a as $k=>$v)
		{
			$data.=$v['id']."|".$v['image_link']."|".$v['additional_image_link']."@";
		}
		$data = substr($data,0,-1);
		$data .= "&act=check_img";
		$reinfo = post_image_info("http://sourceimg02.faout.com/code/api_check_datafeed.php",$data);
		if($reinfo)
		{
			$garr=explode(",", $reinfo);
		}	
		foreach ($a as $k=>$v){
			if(is_array($garr) && in_array($v['id'],$garr))
			{
				continue;
			}		
			$CURR=$currency;
			if($currency)
			{
				$v['price'] = price_format($v['price']*$exchangeArr['Rate'][$currency],false);
				$v['sale_price'] = price_format($v['sale_price']*$exchangeArr['Rate'][$currency],false);
				$v['promote_price'] = price_format($v['promote_price']*$exchangeArr['Rate'][$currency],false);
				if($currency=='USD' || $currency=='CAD'){$ZONE=-5;$ZONE_1='-0500';}
				if($currency=='GBP' || $currency=='EUR'){$ZONE=0;$ZONE_1='+0000';}
				if($currency=='CNY' || $currency=='HKD'){$ZONE=8;$ZONE_1='+0800';}
				if($currency=='AUD'){$ZONE=10;$ZONE_1='+1000';}
				if($currency=='RUB'){$ZONE=3;$ZONE_1='+0300';}
				if($currency=='NZD'){$ZONE=12;$ZONE_1='+1200';}
				$v['promote_start_date'] = $v['promote_start_date']+3600*$ZONE;
				$v['promote_end_date'] = $v['promote_end_date']+3600*$ZONE;
			}
			$cat2='';
			$b[$k]['id'] =$v['id'];
			if($lang == 'en') {
				$b[$k]['title'] = preg_replace('/[^\w\.\(\)]/',' ',$a[$k]['title']);
			} else {
				$b[$k]['title'] = $v['title'];
			}
			$v['description'] = str_replace("\n",' ',$v['description']);
			$v['description'] = str_replace("\r",' ',$v['description']);
			$v['description'] = str_replace("\t",' ',$v['description']);
			$b[$k]['description'] = trim(strip_tags($v['description']));
			$b[$k]['condition'] = 'New';
			if($v['promote_price']>=0 && time()<$v['promote_end_date'])
			{
				$b[$k]['price'] = $CURR.$v['promote_price'];
			}
			else
			{
				 $b[$k]['price'] = $CURR.$v['sale_price'];
			}
			$b[$k]['availability'] = 'In Stock';
			if($lang <> 'en') {
				$lang_url = $lang . '/';
			} else {
				$lang_url = '';
			}
			$b[$k]['link'] = 'http://www.dealsmachine.com/'. $lang_url .'best_'.$v['link'].'.html?currency='. $currency .'';
			$b[$k]['image_link'] = 'http://img.dealsmachine.com/'.str_replace('A','uploads',$v['image_link']);
			$b[$k]['gtin'] = '';
			$b[$k]['mpn'] = $v['id'];
			$b[$k]['brand'] = 'dealsmachine';
			$b[$k]['google_product_category'] = 'Electronics > Communications > Telephony > Mobile Phones';
			$b[$k]['gender'] = '';
			$b[$k]['age_group'] = '';
			$b[$k]['size'] = $v['size'];
			$b[$k]['color'] = check_attr('color', $v['color'], '', $lang);
			$b[$k]['material'] = '';
			$b[$k]['pattern'] = '';
			$b[$k]['item_group_id'] = '';
			$b[$k]['tax'] = '';
			$b[$k]['shipping'] = '';
			$b[$k]['shipping_weight'] = $v['shipping_weight'].'kg';
			if($v['promote_price']>0 && time()<$v['promote_end_date'])
			{
				$b[$k]['sale_price'] = $CURR.$v['promote_price'];
				$b[$k]['sale_price_effective_date'] = date("Y-m-d",$v['promote_start_date'])."T".date("H:i",$v['promote_start_date']).$ZONE_1."/".date("Y-m-d",$v['promote_end_date'])."T".date("H:i",$v['promote_end_date']).$ZONE_1;
			}
			else
			{
				$b[$k]['sale_price'] = '';
				$b[$k]['sale_price_effective_date'] = '';
			}
			$b[$k]['additional_image_link'] = 'http://img.dealsmachine.com/'.$v['additional_image_link'];
			$str=get_parent_id($v['cat_id']);
			$str =$v['cat_id'].",".$str;
			$cat_id_arr = explode("," , $str);
			unset($cat_id_arr[count($cat_id_arr)-1]);
			unset($cat_id_arr[count($cat_id_arr)-1]);
	
			$cat_id_arr = array_reverse($cat_id_arr);
			$catname="";
			for( $i = 0, $n = count($cat_id_arr); $i < $n; $i ++ )
			{
			   if($i==2) break;
			   $catname .= $typeArray[$cat_id_arr[$i]]['cat_name']." > ";
			}
			$catname=substr($catname,0,-3);        
			if($lang == 'en') {
				$b[$k]['product_type'] = $catname;	
			} else {					
				$b[$k]['product_type'] = check_attr('product_type', '', $v['cat_id'], $lang);
				unset($nav_title);
			}
		}
		//array_unshift($b, array_keys($b[0]));
	
		//fangxin
		require(LIB_PATH . 'phpExcel/PHPExcel.php');
		require(LIB_PATH . 'phpExcel/PHPExcel/Writer/Excel5.php');
		$obj_excel  = new PHPExcel();
		$obj_writer = new PHPExcel_Writer_Excel5($obj_excel);
		$obj_excel->setActiveSheetIndex(0);
		$obj_sheet = $obj_excel->getActiveSheet();
		//$obj_sheet->getColumnDimension('A')->setWidth(30);
		$obj_sheet->setCellValue('A1', 'id');
		$obj_sheet->setCellValue('B1', 'title');
		$obj_sheet->setCellValue('C1', 'description');
		$obj_sheet->setCellValue('D1', 'condition');
		$obj_sheet->setCellValue('E1', 'price');
		$obj_sheet->setCellValue('F1', 'availability');
		$obj_sheet->setCellValue('G1', 'link');
		$obj_sheet->setCellValue('H1', 'image_link');
		$obj_sheet->setCellValue('I1', 'gtin');
		$obj_sheet->setCellValue('J1', 'mpn');
		$obj_sheet->setCellValue('K1', 'brand');
		$obj_sheet->setCellValue('L1', 'google product category');
		$obj_sheet->setCellValue('M1', 'gender');
		$obj_sheet->setCellValue('N1', 'age group');
		$obj_sheet->setCellValue('O1', 'size');
		$obj_sheet->setCellValue('P1', 'color');
		$obj_sheet->setCellValue('Q1', 'material');
		$obj_sheet->setCellValue('R1', 'pattern');
		$obj_sheet->setCellValue('S1', 'item group id');
		$obj_sheet->setCellValue('T1', 'tax');
		$obj_sheet->setCellValue('U1', 'shipping');
		$obj_sheet->setCellValue('V1', 'shipping_weight');
		$obj_sheet->setCellValue('W1', 'sale_price');
		$obj_sheet->setCellValue('X1', 'sale price effective date');
		$obj_sheet->setCellValue('Y1', 'additional_image_link');
		$obj_sheet->setCellValue('Z1', 'product_type');
		$num    = 1;
		foreach($b as $key=>$value) {
			$num++;
			$obj_sheet->setCellValue('A' . $num, $value['id']);
			$obj_sheet->setCellValue('B' . $num, $value['title']);
			$obj_sheet->setCellValue('C' . $num, $value['description']);
			$obj_sheet->setCellValue('D' . $num, $value['condition']);
			$obj_sheet->setCellValue('E' . $num, $value['price']);
			$obj_sheet->setCellValue('F' . $num, $value['availability']);
			$obj_sheet->setCellValue('G' . $num, $value['link']);
			$obj_sheet->setCellValue('H' . $num, $value['image_link']);
			$obj_sheet->setCellValue('I' . $num, $value['gtin']);
			$obj_sheet->setCellValue('J' . $num, $value['mpn']);
			$obj_sheet->setCellValue('K' . $num, $value['brand']);
			$obj_sheet->setCellValue('L' . $num, $value['google_product_category']);
			$obj_sheet->setCellValue('M' . $num, $value['gender']);
			$obj_sheet->setCellValue('N' . $num, $value['age_group']);
			$obj_sheet->setCellValue('O' . $num, $value['size']);
			$obj_sheet->setCellValue('P' . $num, $value['color']);
			$obj_sheet->setCellValue('Q' . $num, $value['material']);
			$obj_sheet->setCellValue('R' . $num, $value['pattern']);
			$obj_sheet->setCellValue('S' . $num, $value['item_group_id']);
			$obj_sheet->setCellValue('T' . $num, $value['tax']);
			$obj_sheet->setCellValue('U' . $num, $value['shipping']);
			$obj_sheet->setCellValue('V' . $num, $value['shipping_weight']);
			$obj_sheet->setCellValue('W' . $num, $value['sale_price']);
			$obj_sheet->setCellValue('X' . $num, $value['sale_price_effective_date']);
			$obj_sheet->setCellValue('Y' . $num, $value['additional_image_link']);
			$obj_sheet->setCellValue('Z' . $num, $value['product_type']);
		}
		$filename = "datafeed.xls";
		header('Content-Type: application/force-download');
		header('Content-Type: application/octet-stream');
		header('Content-Type: application/download');
		header('Content-Disposition:inline;filename="' . $filename . '"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: no-cache');
		$obj_writer->save('php://output');					
	}
		
    //$csvfile = new csvDataFile("../datafeed.csv", ",", "w");
    //$csvfile->printcsv($b);
    //echo '生成成功，下载地址<a href="http://www.ahf05.com/datafeed.csv?t='.time().'" target="_blank">http://www.dealsmachine.com/datafeed.csv</a>';
    exit();
}
if($_ACT == 'sharesale'){
		$cat_array = !empty($_POST['other_cat'])?$_POST['other_cat']:'';
		$limit = !empty($_POST['number'])?intval($_POST['number']):1500;
		$exchangeArr = read_static_cache('exchange',2);
		$typeArray =  read_static_cache('category_c_key',2);
		$exchangeArr['Rate']['USD'] = 1;
		$currency = $_POST['currency'];
		if(!empty($cat_array)){
			$catArr = read_static_cache('category_c_key',2);
			foreach($catArr as $row){
				if(in_array($row['parent_id'],$cat_array)){
					$cat_array[] = $row['cat_id'];
				}
			}
			$sql = "SELECT g.goods_sn as id ,
					   g.goods_title as title ,
					   g.goods_desc as description ,
					   g.market_price as price ,
					   g.goods_id as link ,
					   g.original_img as image_link,
					   g.shop_price as sale_price ,
					   g.goods_grid as additional_image_link ,
					   c.cat_name as product_type,
					   c.level,
					   c.parent_id,
					   g.promote_price,
					   g.promote_start_date,
					   g.promote_end_date,
					   g.cat_id
				FROM eload_goods as g left join eload_category as c on g.cat_id = c.cat_id
				WHERE g.cat_id in (".implode(',',$cat_array).") and is_delete = 0 and is_on_sale = 1 and goods_number > 0 and g.add_time > 1327939200 order by week2sale desc,click_count desc limit ".$limit;
		}else{
			exit("没有选择分类");
		}		
    $a=$db->arrQuery($sql);
    empty($a) && exit('无商品');
    $b=array();
	
    $data = "goods=";
    foreach ($a as $k=>$v)
    {
        $data.=$v['id']."|".$v['image_link']."|".$v['additional_image_link']."@";
    }
    $data = substr($data,0,-1);
    $data .= "&act=check_img";
    $reinfo = post_image_info("http://sourceimg02.faout.com/code/api_check_datafeed.php",$data);
    if($reinfo)
    {
        $garr=explode(",", $reinfo);
    }	
	
    foreach ($a as $k=>$v){
        if(is_array($garr) && in_array($v['id'],$garr))
        {
            continue;
        }		
        $CURR=$currency;
        if($currency)
        {
            $v['price'] = price_format($v['price']*$exchangeArr['Rate'][$currency],false);
            $v['sale_price'] = price_format($v['sale_price']*$exchangeArr['Rate'][$currency],false);
            $v['promote_price'] = price_format($v['promote_price']*$exchangeArr['Rate'][$currency],false);

            if($currency=='USD' || $currency=='CAD'){$ZONE=-5;$ZONE_1='-0500';}
            if($currency=='GBP' || $currency=='EUR'){$ZONE=0;$ZONE_1='+0000';}
            if($currency=='CNY' || $currency=='HKD'){$ZONE=8;$ZONE_1='+0800';}
            if($currency=='AUD'){$ZONE=10;$ZONE_1='+1000';}
            if($currency=='RUB'){$ZONE=3;$ZONE_1='+0300';}
            if($currency=='NZD'){$ZONE=12;$ZONE_1='+1200';}
            $v['promote_start_date'] = $v['promote_start_date']+3600*$ZONE;
            $v['promote_end_date'] = $v['promote_end_date']+3600*$ZONE;
        }

        $cat2='';
        $b[$k]['id'] =$v['id'];
        $b[$k]['title'] = preg_replace('/[^\w\.\(\)]/',' ',$a[$k]['title']);
        $v['description'] = str_replace("\n",' ',$v['description']);
        $v['description'] = str_replace("\r",' ',$v['description']);
        $v['description'] = str_replace("\t",' ',$v['description']);
        $b[$k]['description'] = trim(strip_tags($v['description']));
        if($v['promote_price']>0 && time()<$v['promote_end_date'])
        {
             $b[$k]['price'] = $CURR.$v['promote_price'];
        }
        else
        {
             $b[$k]['price'] = $CURR.$v['sale_price'];
        }
		$b[$k]['link'] = 'http://www.dealsmachine.com/best_'.$v['link'].'.html';
        $b[$k]['image_link'] = 'http://img.dealsmachine.com/'.$v['image_link'];
        $str=get_parent_id($v['cat_id']);
        $str =$v['cat_id'].",".$str;
        $cat_id_arr = explode("," , $str);
        unset($cat_id_arr[count($cat_id_arr)-1]);
        unset($cat_id_arr[count($cat_id_arr)-1]);
        $cat_id_arr = array_reverse($cat_id_arr);
        $catname="";
        for( $i = 0, $n = count($cat_id_arr); $i < $n; $i ++ )
        {
           if($i==2) break;
           $catname.=$typeArray[$cat_id_arr[$i]]['cat_name']." > ";
        }
       $catname=substr($catname,0,-3);
       $b[$k]['product_type'] = $catname;
        /*unset($a[$k]['parent_id']);
        unset($a[$k]['level']);
        unset($a[$k]['promote_price']);
        unset($a[$k]['promote_start_date']);
        unset($a[$k]['promote_end_date']);*/
    }
    array_unshift($b, array_keys($b[0]));
    $csvfile = new csvDataFile("../datafeed/sharesale.csv", ",", "w");
    $csvfile->printcsv($b);
    echo '生成成功，下载地址<a href="http://www.dealsmachine.com/datafeed/sharesale.csv?t='.time().'" target="_blank">http://www.dealsmachine.com/datafeed/sharesale.csv</a>';
    exit();
}

//create_all_cat_xml_new.php 生成datafeed过滤不需要的SKU产品 2013-09-25 fangxin
if($_ACT == 'filter_sku'){
	if($_POST){
		$sku = isset($_POST['not_search_sku'])?$_POST['not_search_sku']:'';
		$data = explode(',',$sku);
		$data = array_filter($data);
		$goods_info = array();
		foreach($data as $row){
			$goods_info[] = $row;
		}
		write_static_cache('datafeed_filter_sku',$goods_info,1);
		$link[0]['name'] = "返回列表" ;
		$link[0]['url'] ='/eload_admin/data_feed.php';
		sys_msg('操作成功', 0, $link);
	}
	$_ACT = "list";
	temp_disp();
	break;
}

function post_image_info($url,$data)
{
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
	$contents = curl_exec($ch);
	curl_close($ch);
    return $contents;
}

function get_goods_id($data) {
	global $db;
	if($data) {
		foreach($data as $key=>$value) {
			$sql = "SELECT * FROM ". GOODS ." WHERE goods_sn = '". $value ."'";
			$res = $db->getOne($sql);
			if(!empty($res)) {
				$goods_id .= $res . ',';
			}
		}
		if(!empty($goods_id)) {
			$goods_id = substr($goods_id, 0, (strlen($goods_id)-1));
		}
		return $goods_id;
	}
}

//多语言属性转换
function check_attr($type, $attr, $cat_id, $lang = 'en') {
	if(!empty($type)) {
		switch($type) {
			case 'product_type':
				$catArr   = read_static_cache($lang . '_category_c_key',2);
				$category_curmbs = getpath($catArr, $catArr[$cat_id]["parent_id"], '');				
				$current_category = get_current_category($catArr, $cat_id);
				$category = $category_curmbs . $current_category;					
				return $category;					
				break;			
			case 'color':
				$data = array(
					'fr' => array(
						'PINK'=>'ROSE', 
						'YELLOW'=>'JAUNE', 
						'WHITE'=>'BLANC', 
						'BLACK'=>'BLACK', 
						'SILVER'=>'SILVER', 
						'BLUE'=>'BLEU', 
						'YELLOW'=>'JAUNE', 
						'GREEN'=>'GREEN', 
						'ROSE MADDER'=>'GARANCE ROSE', 
						'PURPLE'=>'PURPLE', 
						'RED'=>'RED', 
						'EARTHY'=>'TERREUSE', 
						'ASSIGN RANDOM COLORS'=>'ATTRIBUER DES COULEURS AU HASARD', 
						'ORANGE'=>'ORANGE', 
						'RED WITH BLUE'=>'ROUGE BLEU', 
						'AZURE'=>'AZURE', 
						'TRANSPARENT'=>'TRANSPARENT'
					),
					'ru' => array(
						'PINK'=>'PINK', 
						'YELLOW'=>'ЖОЎТЫ', 
						'WHITE'=>'WHITE', 
						'BLACK'=>'ЧОРНЫ', 
						'SILVER'=>'SILVER', 
						'BLUE'=>'Сіні', 
						'YELLOW'=>'ЖОЎТЫ', 
						'GREEN'=>'GREEN', 
						'ROSE MADDER'=>'ружа Марена', 
						'PURPLE'=>'PURPLE', 
						'RED'=>'RED', 
						'EARTHY'=>'зямлістыя', 
						'ASSIGN RANDOM COLORS'=>'ASSIGN ВЫПАДКОВЫЯ ЦВЕТЫ', 
						'ORANGE'=>'ORANGE', 
						'RED WITH BLUE'=>'Чырвоны з Сіні', 
						'AZURE'=>'AZURE', 
						'TRANSPARENT'=>'Празрыстыя'
					),
					'es' => array(
						'PINK'=>'PINK', 
						'YELLOW'=>'AMARILLO', 
						'WHITE'=>'BLANCO', 
						'BLACK'=>'NEGRO', 
						'SILVER'=>'SILVER', 
						'BLUE'=>'BLUE', 
						'YELLOW'=>'AMARILLO', 
						'GREEN'=>'GREEN', 
						'ROSE MADDER'=>'ROSE MADDER', 
						'PURPLE'=>'PURPLE', 
						'RED'=>'RED', 
						'EARTHY'=>'TERROSA', 
						'ASSIGN RANDOM COLORS'=>'ASSIGN RANDOM COLORS', 
						'ORANGE'=>'NARANJA', 
						'RED WITH BLUE'=>'RED CON AZUL', 
						'AZURE'=>'AZURE', 
						'TRANSPARENT'=>'TRANSPARENTE'
					),										
				);
				if(empty($data)) {
					$result = '';
				} else {
					$result = isset($data[$lang])?$data[$lang][$attr]:$attr;
				}				
				return $result;
				break;
		}
	}
}

function getpath($typeArray, $Parent){
	global $nav_title;
	if($Parent !=0){
		foreach($typeArray as $keys =>$row){
			if($row["cat_id"]==$Parent){
			$row["cat_name"] = str_replace(array('&','>','<',"'",'"','»'),array('&amp;','&gt;','&lt;','&apos;','&quot;','&raquo;'),$row["cat_name"]);
				$nav_title = $row["cat_name"]." > ".$nav_title;
				if($row["parent_id"]!=0)getpath($typeArray, $row["parent_id"]);
			}
		}
	}
	return $nav_title;
}

function get_current_category($typeArray, $cat_id) {
	if(!empty($typeArray)) {
		foreach($typeArray as $keys=>$row){
			if($row["cat_id"] == $cat_id){
				$category = $row['cat_name'];
			}
		}	
	}
	return $category;
}

$_ACT = $_ACT == 'msg'?'msg':$_ACT;
temp_disp();
?>