<?
if (!defined('INI_WEB')){die('访问拒绝');}


function action_html($arr,$action = 'creat'){
	global $_CFG;
	//生成
	if ($action == 'creat'){
		if(!empty($arr['goods_id'])){ 
			$catArr = read_static_cache('category_c_key',2);
			$cat_folad = empty($catArr[$arr['cat_id']]['url_title'])?'':$catArr[$arr['cat_id']]['url_title'].'/';
			$id = intval($arr['goods_id']);
			$pages = 'goods';
			$dir = GOODS_DIR;
		}elseif(!empty($arr['article_id'])){ 
			$id = intval($arr['article_id']);
			$pages = 'article';
			$dir = ARTICLE_DIR;
			$cat_folad = empty($arr['cat_id'])?'':$arr['cat_id'].'/';
		}
		
		$url_title = $arr['url_title'];
		
		
		

		$url = $_CFG['creat_html_domain']."m-$pages-id-$id.htm";
		$content = file_get_contents($url);
		$path_dir = ROOT_PATH .$dir.$cat_folad;
		
		/* 如果目标目录不存在，则创建它 */
		if (!file_exists($path_dir)){
			if (!make_dir($path_dir)){sys_msg('目录不能写，请检查读写权限', 1, array(), false);return false;}}
		$file_path = $path_dir.$url_title;
		file_put_contents($file_path,$content);
	}
	
	//删除
	if ($action == 'del'){
		
		$catArr = read_static_cache('category_c_key',2);
		$cat_folad = empty($catArr[$arr['cat_id']]['url_title'])?'':$catArr[$arr['cat_id']]['url_title'].'/';
		$path_dir = ROOT_PATH .GOODS_DIR.$cat_folad.$arr['url_title'];
		if (file_exists($path_dir)){
			@unlink($path_dir);
		}
	}
}
?>