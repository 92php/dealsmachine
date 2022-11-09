<?php

define('INI_WEB', true);
@ini_set('memory_limit','1024M');
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/lib.f.goods.php');
require_once('lang/sitemap.php');
$Arr['lang'] = $_LANG;
/* 检查权限 */
admin_priv('sitemap');

$_ACT = !empty($_GET['act'])?$_GET['act']:'form';

if ($_ACT == 'form')
{
    /*------------------------------------------------------ */
    //-- 设置更新频率
    /*------------------------------------------------------ */
    $config = unserialize($_CFG['sitemap']);
    $Arr['config']=           $config;
    $Arr['ur_here']=          'Sitemap';
    $Arr['arr_changefreq']=  array(1,0.9,0.8,0.7,0.6,0.5,0.4,0.3,0.2,0.1);
    $_ACT = 'sitemap';
}
else
{
    /*------------------------------------------------------ */
    //-- 生成站点地图
    /*------------------------------------------------------ */
   // include_once('libs/cls_phpzip.php');
    include_once('libs/cls_google_sitemap.php');
    $domain = 'http://www.bestafford.com/';
    $today  = local_date('Y-m-d');
    $sm     =& new google_sitemap();
    $smi    =& new google_sitemap_item($domain, $today, $_GET['homepage_changefreq'], $_GET['homepage_priority']);
    $sm->add_item($smi);
	
	
	$tag_list     = !empty($_GET['tag'])?$_GET['tag']:'';
	
	$page         = empty($_GET['page'])?0:intval($_GET['page']);
	if($tag_list=='1'){
		$pernum       = 49999;
		//$total_re = $db->getOne("SELECT count(*) FROM " . ABCKEYWORD);
		//$total_page   = ceil($total_re/$pernum);
		//$start        = ($page-1) * $pernum;
		$total_page =0;
		if($page>$total_page) {
				$goods_sql = "SELECT goods_id,goods_title,url_title,cat_id FROM " .GOODS. " WHERE is_delete = 0 and is_on_sale =1";
				$sitemap_index_head = '<?xml version="1.0" encoding="UTF-8"?>
				<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd">'."\n";
				
				//$body  = "<sitemap><loc>".$domain."sitemap-index.xml.gz</loc></sitemap>\n";
				$body  = "<sitemap><loc>".$domain."sitemap-allcategories.xml.gz</loc></sitemap>\n";
				
				
					//BEGIN:商品	
				$goods_count = $db->getOne("SELECT count(*) FROM " .GOODS. " WHERE is_delete = 0 and is_on_sale =1")	;
				$res = $db->arrQuery("SELECT goods_id,url_title FROM " .GOODS. " WHERE is_delete = 0 and is_on_sale =1");
	
				$p=1;
				$i=0;
				$sm     = &new google_sitemap();
		    	$smi    = &new google_sitemap_item($domain, $today, $_GET['homepage_changefreq'], $_GET['homepage_priority']);		   		
				foreach ($res as $k=>$v){	
					$i +=1;									
					$smi = &new google_sitemap_item(get_details_link($v['goods_id'],$v['url_title']), $today,$_GET['content_changefreq'], $_GET['content_priority']);
					$sm->add_item($smi);
					
					//if($p<ceil($i/$pernum)){
						$p = ceil($i/$pernum);
						if($i == $goods_count || $p != ceil(($i+1)/$pernum)){
							$body  .= "<sitemap><loc>".$domain."sitemap-products-set-$p.xml.gz</loc></sitemap>\n";
							$sm_file = "../sitemap-products-set-$p.xml";
							$sm->build($sm_file);
							file_get_contents('http://purge.faout.com:9090/peal.php?purge_url=http://www.bestafford.com/' . str_replace('../', '', $sm_file) . '.gz');    //清缓存
							file_get_contents('http://acloud.faout.com/purge/'. str_replace('../', '', $sm_file) . '.gz');    //清缓存/' . str_replace('../', '', $sm_file) . '.gz');    //清缓存
						}
						//unset($sm_g);
						//unset($smi_g);
					//}
				}
					//END:商品
				
				for($i=1;$i<=$total_page;$i++){
					$body .= '<sitemap><loc>'.$domain.'sitemap-tag-'.$i.".xml.gz</loc></sitemap>\n";
				}
				$foot = '</sitemapindex>';			
				
				$sitmap = $sitemap_index_head.$body.$foot;
				file_put_contents('../sitemap.xml',$sitmap);
				    //清缓存
				file_get_contents('http://acloud.faout.com/purge/'. 'sitemap.xml');    //清缓存
				file_get_contents('http://purge.faout.com:9090/peal.php?purge_url=http://www.ahappdeal.com/sitemap.xml');
				/* 商品分类 */
				$typeArray =  read_static_cache('category_c_key',2);
				
				//$sql = "SELECT cat_id,url_title,parent_id FROM " .CATALOG. " ORDER BY parent_id";
				//$res = $db->query($sql);
			
				$sm     = new google_sitemap();
		    	$smi    = new google_sitemap_item($domain, $today, $_GET['homepage_changefreq'], $_GET['homepage_priority']);
				foreach ($typeArray as $row)
				{	
					if($row['is_show']&&!$row['is_login']){
		
						$smi =& new google_sitemap_item($domain.$row['url_title'], $today,
						$_GET['category_changefreq'], $_GET['category_priority']);
						$sm->add_item($smi);
					}
				}
				$sm_file = '../sitemap-allcategories.xml';
				$sm->build($sm_file);
				//商品分类 end
				file_get_contents('http://purge.faout.com:9090/peal.php?purge_url=http://www.bestafford.com/' . str_replace('../', '', $sm_file) . '.gz');    //清缓存
				file_get_contents('http://acloud.faout.com/purge/'. str_replace('../', '', $sm_file) . '.gz');    //清缓存
				$links[0]['name'] = '返回上一页';
				$links[0]['url'] = 'sitemap.php';
				sys_msg(sprintf($_LANG['generate_success'],$domain.'sitemap.xml'),0,$links);
			}
	//exit;
	//关键字
		/*
		$sql = "SELECT keyword FROM " . ABCKEYWORD ." ORDER BY keyword LIMIT $start ,$pernum";
		$res = $db->arrQuery($sql);
		$arr = array();
		foreach ($res as $k => $row)
		{
			$match = array();
			preg_match_all("/[0-9a-zA-Z#\.+]{1,}/",$row['keyword'],$match);
			$url_str = implode('-',$match[0]);
			$url_str = htmlspecialchars($url_str);
			$smi =& new google_sitemap_item($domain . 'wholesales-'.$url_str.'.html', $today,$_GET['content_changefreq'], $_GET['content_priority']);
			$sm->add_item($smi);
		}
		
		*/
		$total_num      = count($sm->items);
		$sm_file = '../sitemap-tag-'.$page.'.xml';
		
	}else{
		$typeArray =  read_static_cache('category_c_key',2);
		/* 商品分类 */
		
		foreach ($typeArray as $row)
		{
			
			if($row['is_show']&&!$row['is_login']){

				$smi =& new google_sitemap_item($row['url_title'], $today,
				$_GET['category_changefreq'], $_GET['category_priority']);
				$sm->add_item($smi);
			}
			
			
		}
	
		/* 商品 */
		$sql = "SELECT goods_id,goods_title,url_title,cat_id FROM " .GOODS. " WHERE is_delete = 0";
		$res = $db->query($sql);
	
		
		while ($row = $db->fetchRow($res))
		{
			$smi =& new google_sitemap_item(get_details_link($row['goods_id'],$row['url_title']), $today,$_GET['content_changefreq'], $_GET['content_priority']);
			$sm->add_item($smi);
		}
		
		//abc索引
		/*
		$abcArr = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0','1','2','3','4','5','6','7','8','9');
		 foreach ($abcArr as $v)
		{
			$smi =& new google_sitemap_item($domain . 'producttag/'.$v.'/', $today,$_GET['content_changefreq'], $_GET['content_priority']);
			$sm->add_item($smi);
		}
		*/
		$total_num      = count($sm->items);
		
		$sm_file = '../sitemap-index.xml';
	}
	$sm->build($sm_file);
	
		file_get_contents('http://purge.faout.com:9090/peal.php?purge_url=http://www.bestafford.com/' . str_replace('../', '', $sm_file) . '.gz');    //清缓存

		file_get_contents('http://acloud.faout.com/purge/'. str_replace('../', '', $sm_file) . '.gz');    //清缓存
	echo $sm_file.'生成成功。共生成连接'.$total_num.'条';
	$page++;
	echo '<script>window.location.href="?act=creat&homepage_priority='.$_GET['homepage_priority'].'&homepage_changefreq='.$_GET['homepage_changefreq'].'&category_priority='.$_GET['category_priority'].'&category_changefreq='.$_GET['category_changefreq'].'&content_priority='.$_GET['content_priority'].'&content_changefreq='.$_GET['content_changefreq'].'&tag=1&page='.$page.'";</script>';
	
}

temp_disp();

?>