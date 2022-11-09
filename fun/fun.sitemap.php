<?
/*
+----------------------------------
* 网站地图
+----------------------------------
*/
global $default_lang;
$cat_id = empty($_GET['id'])?0:intval($_GET['id']);
$shop_title = '';
$nav_title  = '';
$nav_key  =  '';
$shop_key = '';
if (!$Tpl->is_cached($_MDL.'.htm', $my_cache_id))
{
	require_once(ROOT_PATH . 'fun/fun.global.php');
	require_once(ROOT_PATH . 'fun/fun.public.php');
	require_once(ROOT_PATH . 'lib/lib.f.goods.php');
	require_once(ROOT_PATH . 'lib/class.page.php');
	
	if ($cat_id != 0){
		if($cur_lang != $default_lang){
			$typeArray =  read_static_cache($cur_lang.'_category_c_key',2);
		}else {
			$typeArray =  read_static_cache('category_c_key',2);
		}		
		if (empty($typeArray[$cat_id])){
		   header("Location: /$cur_lang_url"."m-page_not_found.htm");
		   exit;
		}
		$nav_title = getsitmapNavTitle($typeArray,$typeArray[$cat_id]["parent_id"]);
		$nav_title = $nav_title.' &raquo; <label class="a_class">'.$typeArray[$cat_id]["cat_name"].'</label>';
		$shop_title = getTitle($typeArray,$typeArray[$cat_id]["parent_id"]);
		$nav_key  = $typeArray[$cat_id]['cat_name'];
	}

	if($cur_lang != $default_lang){
		$catArr =  read_static_cache($cur_lang.'_category_c_key',2);
	}else {
		$catArr =  read_static_cache('category_c_key',2);
	}		
	if ($_ACT == 'index')
	{
		$nav_key = $nav_key.' '.$_LANG['Sitemap_Index'];
		//$left_catArr = getmaptree($catArr, $cat_id,1,true);
		
	}else{
	$disp = 0;
		switch ($_ACT){
			case 'search';
				$nav_key = $nav_key .' - Hot Searches';
				$nav_title = $nav_title . '  Searches ';
				$disp = 0;
				
				
				$pernum       = 10;
				$page         = empty($_GET['page'])?1:intval($_GET['page']);
				$total_record = $db->getOne("SELECT count(goods_id) FROM " . GOODS ." WHERE  cat_id = '$cat_id' AND is_on_sale = 1 AND is_delete = 0");
				$total_page   = ceil($total_record/$pernum);
				$start        = ($page - 1) * $pernum;
				if($page>$total_page) $page = $total_page;
				
				$sql = "select keywords from ".GOODS." where cat_id = '$cat_id' AND is_on_sale = 1 AND is_delete = 0 order by goods_id desc LIMIT  $start ,$pernum";
				$goods_arr = $db->arrQuery($sql);
				$goods_str = '';
				foreach($goods_arr as $val){
					$goods_str .= $val['keywords'].',';
				}
				$goods_str_arr = explode(',',$goods_str);
				$Arr['goodskey'] = $goods_str_arr;
				$Arr['total_record'] = $total_record;
				$page=new page(array('total'=>$total_record,'perpage'=>$pernum)); 
				$Arr["pagestr"]  = $page->show(5);
				
				
			break;
			
			case 'features';
				$nav_key = $nav_key .' - Features';
				$nav_title = $nav_title . '  Features ';
				$disp = 1;
				
				
				
				
			break;
			
			case 'products';
				$nav_key = $nav_key .' - Hot Products';
				$nav_title = $nav_title . ' Products ';
				$disp = 2;
	
	
				$pernum       = 20;
				$page         = empty($_GET['page'])?1:intval($_GET['page']);
				$total_record = $db->getOne("SELECT count(goods_id) FROM " . GOODS ." WHERE  cat_id = '$cat_id' AND is_on_sale = 1 AND is_delete = 0");
				$total_page   = ceil($total_record/$pernum);
				$start        = ($page - 1) * $pernum;
				if($page>$total_page) $page = $total_page;
	
				$sql = "select goods_title,goods_id,goods_thumb,goods_name,cat_id,url_title from ".GOODS." where cat_id = '$cat_id' AND is_on_sale = 1 AND is_delete = 0 order by goods_id desc LIMIT  $start ,$pernum";
				
				$goods_arr = $db->arrQuery($sql);
				$goods_str = '';
				foreach($goods_arr as $k => $row){
					$goods_arr[$k]['url_title']  = get_details_link($row['goods_id'],$row['url_title']);
					$goods_arr[$k]['goods_thumb']  = get_image_path($row['goods_id'], $row['goods_thumb'], true);
					if (strpos($row['goods_name'],',') !== false){
						$row['goods_name'] = explode(',',$row['goods_name']);
						$row['goods_name'] =  $row['goods_name'][0];
					}
					
					$goods_arr[$k]['pro'] = get_properties($row['goods_id'],$row['goods_name'],$typeArray[$row['cat_id']]['cat_name']) ;
				}
				$Arr['goods'] = $goods_arr;
				$Arr['total_record'] = $total_record;
				
				$page=new page(array('total'=>$total_record,'perpage'=>$pernum)); 
				$Arr["pagestr"]  = $page->show(5);
				
				
				
			break;
			
			case 'wholesalers';
				$Arr['desc'] = $nav_key;
				$nav_key = $nav_key .' - wholesalers';
				$nav_title = $nav_title . '  wholesalers ';
				$disp = 3;
			break;
		}
		
		
		
		$lei_name = $typeArray[$cat_id]["cat_name"];
		$lei_url = $typeArray[$cat_id]["url_title"];
		$lei_url = "/$lei_url-$cat_id-sitemap-Top";
		
		$left_catArr =  array('0' => array('text' => 'Top '.$lei_name.' Searches','href' => "$lei_url-Searches.html"),
							  '1' => array('text' => 'Top '.$lei_name.' Features','href' => "$lei_url-Features.html"),		   
							  '2' => array('text' => 'Top '.$lei_name.' Products','href' => "$lei_url-Wholesalers-Products.html"),		   
							  '3' => array('text' => 'Top '.$lei_name.' Wholesalers','href' => "$lei_url-Wholesalers.html"),	   
							  );
		$Arr['disp']   = $disp;
	}
	$Arr['left_catArr']   = getDynamicTree(0);
	//$Arr['left_catArr']   = $left_catArr;
	$Arr['catArr']   = getmaptree($catArr, $cat_id);
	$Arr['nav_title']  =  $nav_title;
	$Arr['nav_key']  =  $nav_key;
	
	
	if ($nav_key!=''){
		$shop_key =  $nav_key.' - ';
		$nav_key =  $nav_key.' , ';
	}
	
	$Arr['seo_title'] = $shop_key.'Sitemap - '.$_CFG['shop_title'];
	$Arr['seo_keywords'] = $nav_key.'Sitemap ,'.$_CFG['shop_keywords'];
	$Arr['seo_description'] = $nav_key.'Sitemap ,'.$_CFG['shop_desc'];
	$Arr['shop_name']  =  $_CFG['shop_name'];
	$Arr['is_sitemap'] = 1;
	
}

function getsitmapNavTitle($typeArray,$Parent){
	global $nav_title;
	if($Parent !=0){
		foreach($typeArray as $keys =>$row){
			if($row["cat_id"]==$Parent){
				$thisurl = "/sitemap-index-".$row['cat_id'].".html";
				$nav_title = " &raquo; <a href='$thisurl' title='$row[url_title] sitemap'>".$row["cat_name"]." sitemap</a>".$nav_title;
				if($row["parent_id"]!=0)getNavTitle($typeArray,$row["parent_id"]);
			}
		}
	}
	return $nav_title;
}


function getmaptree($data, $pId,$depth = 2,$urlflag = false){
	$html = '';
   // $typeArray =  read_static_cache('category_c_key',2);
	foreach($data as $k => $v)
	{
		if($v['parent_id'] == $pId)
		{
			//$newres = read_static_cache('category_goods_num_key',2);($num) 
			//$num = isset($newres[$v['cat_id']])?$newres[$v['cat_id']]:0;
			if ($v['is_show'] == 1){
				$url = sitemap_url($v['url_title'],$v['cat_id']);
				$urltitle = '';
				if ($urlflag) {$url = "/sitemap-index-".$v['cat_id'].".html";$urltitle = 'Sitemap';}
				
				$sitemapurl = "/sitemap-index-".$v['cat_id'].".html";
				
				//判断是否有子类, 没有子类则显示下列连接
				$pk = 0;
				foreach($data as $x => $y){
					if($y['parent_id'] == $v['cat_id'])  $pk = $x;
				}
				if ($pk == 0 && $depth != 2) {
					$url = $v['url_title'];
					$urltitle = 'Top Searches';
				}
				if($pk == 0)$sitemapurl = "/$v[url_title]-".$v['cat_id']."-sitemap-Top-Searches.html";
				
				
				 
				$html .= "<li><a href='/".$v['url_title']."' title = '".$v['cat_name']." $urltitle'>".$v['cat_name']." </a>  ";
				if ($depth ==2)	{
					//$html .=  " <a href='$sitemapurl' class='bluelink' title='".$v['cat_name']." Sitemap'>Sitemap</a> ";
					$html .= getmaptree($data, $v['cat_id']);
				}
				$html = $html."</li>";
			}
		}
	}
	return $html ? '<ul>'.$html.'</ul>' : $html ;
}
//导航分类连接，适应静态生成和动态连接两种
function sitemap_url($url_title,$cat_id){
	$thisurl = '/China-wholesale-'.$url_title.'-c-'.$cat_id.'.html';
	return $thisurl;
}


?>  