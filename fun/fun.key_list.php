<?

/*
+----------------------------------* 网站地图+----------------------------------*/

$id    = empty($_GET['id'])?'0':$_GET['id'];
if(!$id||!is_int($id)){
	//header("Location: /$cur_lang_url"."m-page_not_found.htm");
}



	$table  ='eload_seo_link';
	require_once(ROOT_PATH . 'fun/fun.global.php');
	require_once(ROOT_PATH . 'fun/fun.public.php');
	require_once(ROOT_PATH . 'lib/lib.f.goods.php');
	require_once(ROOT_PATH . 'lib/class.page.php');
	
	
	
	$link_info = $db->selectInfo("select * from $table where id= $id");
	$cat_link_arr = array();
	$keywords_link_arr = array();
	
	if(!empty($link_info['category_link'])){
		$links_arr = explode("\n",$link_info['category_link']);
		foreach ($links_arr as $v){
			$link_arr = explode(";", $v);
			if(is_array($link_arr)){
				$cat_link_arr[] = $link_arr; 
			}
		}
		
	}
	if(!empty($link_info['keywords_link'])){
	$links_arr = explode("\n",$link_info['keywords_link']);
	foreach ($links_arr as $v){
			$link_arr = explode(";", $v);
			if(is_array($link_arr)){
				$keywords_link_arr[] = $link_arr; 
			}
		}

	}
	$Arr['cat_link_arr'] = $cat_link_arr;
	$Arr['seo_title'] = $link_info['title'];
	$Arr['keywords_link_arr'] = $keywords_link_arr;



?>  