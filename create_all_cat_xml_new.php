<?
/**
 * 生成google地图XML文件（最新使用）
 * */
set_time_limit(0);
define('INI_WEB', true);
$_BEGINTIME = microtime(TRUE);
require('lib/global.php');    
require(ROOT_PATH.'lib/time.fun.php');
$currency=array('US'=>'USD','AU'=>'AUD','CA'=>'CAD','UK'=>'GBP');
$exchangeArr = read_static_cache('exchange',2);
require(ROOT_PATH.'class_name_array.php');
$slave_db = get_slave_db();
$catArr   = read_static_cache('category_c_key',2);
foreach($cat_duiying as $key=>$value)
{
        $k_arr = array_keys($value);
        $cat_ids_str = implode(',',$k_arr);
        $str = '<?xml version="1.0" encoding="UTF-8" ?>
        <rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
        <channel>
        <title>Best Deals Online - Cool Gadgets and Electronics Deals | dealsmachine</title>
        <description>dealsmachine.com offers cool gadgets and electronics at great prices, browse cell phones, tablet pcs, computers, gadgets and other electronics deals and save big!</description>
        <link>http://www.dealsmachine.com</link> ';

        //获得商品信息
        $sql = "select
        g.goods_title,
        g.goods_desc,
        g.goods_grid as additional_image_link ,
        g.original_img as image_link,
        g.goods_id,
        g.goods_sn,
        g.market_price as price,
        g.shop_price as sale_price ,
        g.promote_price,
        g.promote_start_date,
        g.promote_end_date,
        g.cat_id,
        g.goods_number,
        g.goods_weight,
        g.goods_name,
        g.url_title,
		g.add_time,
        g.is_free_shipping
        from ".GOODS." as g
        WHERE  g.is_on_sale=1 AND g.is_delete=0 AND g.goods_number > 0 AND g.cat_id IN($cat_ids_str) AND g.add_time > 1327939200";
		$sql_week2sale = $sql . " ORDER BY g.week2sale DESC";
        $arr = $slave_db->arrQuery($sql_week2sale);	
			
		$sql_order_update = $sql . " ORDER BY g.add_time DESC LIMIT 40";       
		$arr_update = $slave_db->arrQuery($sql_order_update);
		
		$sql_order_recommend = "SELECT eg.goods_id, egc.* FROM eload_goods eg LEFT JOIN eload_goods_conversion_rate egc ON eg.goods_id = egc.goods_id ORDER BY egc.conversion_rate DESC LIMIT 40";
		$arr_recommend = $slave_db->arrQuery($sql_order_recommend);

        $data="goods=";
        foreach ($arr as $k=>$v)
        {
            $data.=$v['goods_sn']."|".$v['image_link']."|".$v['additional_image_link']."@";
        }
        $data=substr($data,0,-1);
        $data.="&act=check_img";
		$reinfo = post_image_info("http://a.faout.com/code/api_check_datafeed.php",$data);
        if($reinfo)
        {
            $garr=explode(",", $reinfo);
        }

        $i=0;
        foreach ($arr as $key1 => $row)
        {
            $i++;
            if(is_array($garr) && in_array($row['id'],$garr))
            {
                continue;
            }

            if($currency[$key])
            {
                $shop_price = $row['sale_price'];
                $row['price'] = price_format($row['price']*$exchangeArr['Rate'][$currency[$key]],false);
                $row['sale_price'] = price_format($row['sale_price']*$exchangeArr['Rate'][$currency[$key]],false);
                $row['promote_price'] = price_format($row['promote_price']*$exchangeArr['Rate'][$currency[$key]],false);
                if($currency[$key]=='USD' || $currency[$key]=='CAD'){$ZONE=-5;$ZONE_1='-0500';}
                if($currency[$key]=='GBP' || $currency[$key]=='EUR'){$ZONE=0;$ZONE_1='+0000';}
                if($currency[$key]=='AUD'){$ZONE=10;$ZONE_1='+1000';}
                $row['promote_start_date'] = $row['promote_start_date']+3600*$ZONE;
                $row['promote_end_date'] = $row['promote_end_date']+3600*$ZONE;
            }
            if($row['promote_price']>0 && time()<$row['promote_end_date'])
            {
                 $goods_price = $currency[$key].$row['price'];
            }
            else
            {
                 $goods_price = $currency[$key].$row['sale_price'];
            }
			
			$goods_id = $row['goods_id'];
            $cat_id   = $row['cat_id'];
            $sku      = $row['goods_sn'];
			//产品标题
			$goods_title = $value[$cat_id]["title"] . ' ' .  $row['goods_title'];
            $goods_title = str_replace(array('&','>','<',"'",'"','Free','Shipping'),array('&amp;','&gt;','&lt;','&apos;','&quot;','',''),$goods_title);
            $goods_title = substr(preg_replace('/[\x80-\xff]+/',"",$goods_title), 0, 70);   //去掉双字节字符
			//描述
            $description = varResume($row['goods_desc']);
            $description = str_replace("½",'1/2',$description);
            $description = str_replace("¾",'3/4',$description);
            $description = str_replace("¼",'1/4',$description);
            $description = str_replace("\n",' ',$description);
            $description = str_replace("\r",' ',$description);
            $description = str_replace("\t",' ',$description);
            preg_match("/\<table (.*?)\>(.*?)\<\/table\>/i",$description,$newcode);
            $description = str_replace($newcode[0],' ',$description);
            $description = trim(strip_tags($description));
            $description = 'dealsmachine ' . $goods_title . ' ' . htmlspecialchars($description);
			//主图
            $goods_img = 'http://cloud.faout.com/'.str_replace('S/','uploads/',$row['image_link']);
			//链接
            $link = get_details_link($row['goods_id'],$row['url_title']);
            $nav_title = '';
            if($cat_id!="" && !empty($catArr[$cat_id]["parent_id"])) $nav_title = getpath($catArr,$catArr[$cat_id]["parent_id"]);
            $cat_name = empty($catArr[$cat_id]["cat_name"])?'':$catArr[$cat_id]["cat_name"];
            $cat_name = str_replace(array('&','>','<',"'",'"'),array('&amp;','&gt;','&lt;','&apos;','&quot;'),$cat_name);
            $nav_title = $nav_title.$cat_name;
			$nav_title = str_replace(array('&','>','<',"'",'"'),array('&amp;','&gt;','&lt;','&apos;','&quot;'),$value[$cat_id]['cat_name']); //产品所在目录
			//GOOD_PRODUCT_CATEGORY
			$google_product_category = !empty($cat_duiying[$key][$cat_id]['google_product_category'])?$cat_duiying[$key][$cat_id]['google_product_category']:''; 
			$google_product_category = str_replace(array('&','>','<',"'",'"'), array('&amp;','&gt;','&lt;','&apos;','&quot;'), $google_product_category);

            //获得商品的颜色信息
            $goods_color = '';
            $sql = "select attr_value from ".GATTR." WHERE attr_id IN(8,2,5,6) AND goods_id = " .$row['goods_id']." GROUP BY goods_id";
            $goods_color = $db->getOne($sql);
            $goods_color = empty($goods_color) ? 'As The Picture' : $goods_color;		//商品颜色
            if(preg_match("/&/",$goods_color))		//过滤更改包含有&符号的
            {
                $goods_color = 'As The Picture';
            }

            //获得商品的尺寸信息
            $goods_size = '';
            $sql = "select attr_value from ".GATTR." WHERE attr_id IN(7,1,3,4) AND goods_id = " .$row['goods_id']." GROUP BY goods_id ";
            $goods_size = $db->getOne($sql);
            $goods_size = empty($goods_size) ? '' : in_array($goods_size,array('XS','XXS','S','M','L','XL','XXL','XXXL','XXXXL','XXXXXL' )) ? $goods_size : 'M';

			$color = get_color();
			$str .= "\n\n<item>\n".'<g:id>'.$sku.$key.'</g:id> '."\n";
            $str .= '<g:title>'.$goods_title.'</g:title> '."\n";
            $str .= '<g:description>'.$description.'</g:description> '."\n";
			if(1 == check_title($goods_title, 'Refurbished')) {
				$condition = 'Refurbished';	
			} else {
				$condition = 'New';
			}
            $str .= '<g:condition>'. $condition .'</g:condition>'."\n";
            $str .= '<g:price>'.$goods_price.'</g:price> '."\n";
            $str .= '<g:availability>In Stock</g:availability> '."\n";
            $str .= '<g:link>'.$link.'</g:link> '."\n";
            $str .= '<g:image_link>'.$goods_img.'</g:image_link> '."\n";
			$str .= '<g:gtin></g:gtin> '."\n";
	        $str .= '<g:mpn>'.$sku.$key.'</g:mpn> '."\n";
			$str .= '<g:brand>dealsmachine</g:brand>'."\n";  //品牌
		    $str .= '<g:google_product_category>'.$google_product_category.'</g:google_product_category> '."\n";
	        $str .= '<g:gender></g:gender> '."\n";			//性别
			$str .= '<g:age_group></g:age_group> '."\n";			//年龄组
			$str .= '<g:color>'.$goods_color.'</g:color> '."\n";			//颜色
			if($goods_size)
			{
				$str .= '<g:size>'.$goods_size.'</g:size> '."\n";			//尺寸
			}
            $str .= '<g:material></g:material> '."\n";//空白
            $str .= '<g:pattern></g:pattern> '."\n";//空白
            $str .= '<g:item_group_id></g:item_group_id> '."\n";//空白
            $str .= '<g:tax></g:tax> '."\n";//空白
            $str .= '<g:shipping></g:shipping> '."\n";//空白
            $str .= '<g:shipping_weight>'.$row['goods_weight'].' kg</g:shipping_weight> '."\n"; 
            if($row['promote_price']>0 && time()<$row['promote_end_date'])
            {
                 $sale_price_effective_date = date("Y-m-d",$row['promote_start_date'])."T".date("H:i",$row['promote_start_date']).$ZONE_1."/".date("Y-m-d",$row['promote_end_date'])."T".date("H:i",$row['promote_end_date']).$ZONE_1;
                 $str .= '<g:sale_price>'.$currency[$key].$row['promote_price'].'</g:sale_price> '."\n";
		         $str .= '<g:sale_price_effective_date>'.$sale_price_effective_date.'</g:sale_price_effective_date> '."\n";
            }
            else
            {
                 $str .= '<g:sale_price></g:sale_price> '."\n";
		         $str .= '<g:sale_price_effective_date></g:sale_price_effective_date> '."\n";
            }
            $additional_image_link = 'http://cloud.faout.com/'.$row['additional_image_link'];
            $str .= '<g:additional_image_link>'.$additional_image_link.'</g:additional_image_link>'."\n";
		    $str .= '<g:product_type>'.$nav_title.'</g:product_type>'."\n";
			if(in_array($cat_id, array(249,181,270,248,182,167,188,505,183,630,1820,1753,1738,1818,504,1780,1781,1779))) {
				$adwords_grouping = 'cell phone';
			}elseif(in_array($cat_id, array(598,453,1819,1747,1624,1755,1815,432,1716))) {
				$adwords_grouping = 'tablet pc';
			}elseif(in_array($cat_id, array(421,1720))) {
				$adwords_grouping = 'notebook';
			}elseif(in_array($cat_id, array(152))) {
				$adwords_grouping = 'cameras';
			}elseif(in_array($cat_id, array(410))) {
				$adwords_grouping = 'camcorders';
			}
            $str .= '<g:adwords_grouping>'.$adwords_grouping.'</g:adwords_grouping>'."\n"; //分类
            if($i <= 50)
            {
                $str .= '<g:adwords_labels>best selling</g:adwords_labels>,'."\n";
            }

			if(1 == check_product_attr($goods_id, $arr_recommend)) {
				$str .= '<g:adwords_labels>recommended</g:adwords_labels>,'."\n";
			}
			
			if(1 == check_product_attr($goods_id, $arr_update)) {
				$str .= '<g:adwords_labels>new arrival</g:adwords_labels>,'."\n";
			}			

            if($row['promote_price']>0 && time()<$row['promote_end_date'])
            {
                $str .= '<g:adwords_labels>promotion</g:adwords_labels>,'."\n";
            }
			
            if($row['is_free_shipping'])
            {
                $str .= '<g:adwords_labels>free shipping</g:adwords_labels>,'."\n";
            }

            if($shop_price < 100)
            {
                $str .= '<g:adwords_labels>under 100</g:adwords_labels>,'."\n";
            }
			
            if($shop_price < 50)
            {
                $str .= '<g:adwords_labels>under 50</g:adwords_labels>,'."\n";
            }

            if(in_array($cat_id,array(249,181,270,248,182,167,188,505,183,630)))
            {
                $str .= '<g:adwords_labels>phablet</g:adwords_labels>,'."\n";
            }

            if(in_array($cat_id,array(453,1819,1747,1624,421)))
            {
                $str .= '<g:adwords_labels>phone tablet</g:adwords_labels>,'."\n";
            }			

			$str .= '</item>'."\n";
    }
    $str .= '</channel>'."\n";
    $str .= '</rss>'."\n";
	$filename_path = ROOT_PATH.'datafeed/datafeed-xml-'.$key.'.xml';
	file_put_contents($filename_path,$str);
	echo $key . "总计：".$i." 个产品。<br>";
}
exit;

function getpath($typeArray,$Parent){
	global $nav_title;
	if($Parent !=0){
		foreach($typeArray as $keys =>$row){
			if($row["cat_id"]==$Parent){
			$row["cat_name"] = str_replace(array('&','>','<',"'",'"','»'),array('&amp;','&gt;','&lt;','&apos;','&quot;','&raquo;'),$row["cat_name"]);
				$nav_title = $row["cat_name"]." > ".$nav_title;
				if($row["parent_id"]!=0)getpath($typeArray,$row["parent_id"]);
			}
		}
	}
	return $nav_title;
}

function get_color(){
	$color_str = 'Black , White , Red , Silver , Pink , Brown , Grey , Golden , Blue , Green , Champagne , purple , Black with red , Black with silver , White with soft pink , Titanium with bright blue , Titanium black , Front silver,back black , Silver-white , Black with Golden Side , Black with Yello , Gold with brown , Black with Metallic Grey , Silver with chrome highlights';
	$color_arr = explode(',',$color_str);
    return $color_arr[rand(0, 24)];
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

function get_google_product_category($data) {
	$result = '';
	if(!empty($date)) {
		if(strpos($data, 'camera')) {
			$result = 'camera';
		} 
		if(strpos($data, 'camcorder')) {
			$result = 'camcorder';
		} 	
	}
	return $result;
}

function check_product_attr($goods_id, $data) {
	if(!empty($goods_id) && !empty($data)) {
		foreach($data as $key=>$value) {
			if($goods_id == $value['goods_id']) {
				return 1;
			}
		} 
		return 0;
	}
}

function check_title($data, $title) {
	if(!empty($data) && !empty($title)) {
		if(strpos(strtolower($data), $title) || strpos($data, $title)) {
			return 1;
		} else {
			return 0;
		}
	}
}

?>


