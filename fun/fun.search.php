<?php
/**
 * fn.search.php            搜索结果页
 * @author                  jim(158642560@qq.com)
 */
!defined('INI_WEB') && exit('Access Denied');
require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'lib/time.fun.php');
require_once(ROOT_PATH . 'lib/param.class.php');
global $default_lang;
$_time         = microtime(true);
$stime         = date('Ymd', gmstr2time('-15 day'));      //开始时间 New Arrvial
$etime         = date('Ymd', gmstr2time('-0 day'));       //结束时间 New Arrvial
$cookie_time   = time() + 3600 * 2;                       //cookie保存时间
$_MDL          = 'category';                              //模板
$nav_date      = array();                                 //时间导航，New Arrvial可用
$keyword       = Param::get('k');                         //查询关键字
$keyword       = deal_keyword($keyword);                  //特殊字符处理
$keyword_arr   = explode(' ', strtolower($keyword));
$cat_id        = Param::get('category', 'int');           //分类id
$min_price     = Param::get('min_price', 'float');        //起始价格
$min_price     = $min_price > 0 ? $min_price : '';
$max_price     = Param::get('max_price', 'float');        //结束价格
$max_price      = $max_price > 0 ? $max_price : '';
$goods_type    = Param::get('goods_type', 'int');
$sc_ds         = Param::get('sc_ds', 'int');              //是否搜索简要说明
$pro           = Param::get('pro');                       //栏目
$outstock      = Param::get('outstock');                  //outstock
$date          = Param::get('date');                      //时间
$search_type   = 0;
if(isset($_POST['k'])){
	$search_type = 1;
}
//客户是否找到要找的产品进行投票
$act = empty($_GET['a'])?'':$_GET['a'];
if($act == 'vote'){
	$k = $keyword;
	$v = empty($_GET['v'])?'':trim($_GET['v']);
	if(!$k||!$v)exit();
	if($v=='n'){
		$db->autoReplace(KEYWORDS, array('date' => local_date('Y-m-d'),'searchengine' => $_CFG["shop_name"], 'keyword' => trim($k),'count'=>1, 'not_found_by_user' => 1), array('not_found_by_user' => 1));
	}elseif ($v=='y'){
		$db->autoReplace(KEYWORDS, array('date' => local_date('Y-m-d'),'searchengine' => $_CFG["shop_name"], 'keyword' => trim($k), 'count'=>1,'found_by_user' => 1), array('found_by_user' => 1));
	}
	exit();
}
if(!IS_LOCAL)open_cdn_cache();//开启页面CDN缓存
//seo 跳转 换标题等
if ($keyword_arr) {
	$data = read_static_cache('seo_keyword_to_url', 2);
	if ($cur_lang == $default_lang&&$data && array_key_exists($v = join(' ', $keyword_arr), $data)) {
		$url = $data[$v]['url'];
		redirect_url($url, 301);
	}
}
$_CFG['order'] = array (
    'new'      => '@id DESC',      //对应前台 New Arrival
    'hot'      => 'week2sale DESC',   //对应前台 Hot
	'conversion' => 'conversion_rate2 DESC,week2sale DESC,@id DESC',
    'reviews'  => 'reviews DESC',       //对应前 Reviews
    'lowprice' => 'shop_price ASC',     //对应前 Lowest Price
    'highprice'  => 'shop_price desc'//对应前 high Price first
);
$default_display_type = 'g';
if($pro == 'new') $default_display_type ='g';
$listper          = '20|40|60';
$gridper          = '32|48|72';
$layoutpage['l']  = explode('|', $listper);
$layoutpage['g']  = explode('|', $gridper);
$is_search = 1 ; //控制左侧分类导航
$sortby    = !empty($_GET['sortby'])?$_GET['sortby']:(!empty($_COOKIE['porder'])?$_COOKIE['porder']:'hot');//排序KEY值
$sortby    = !empty($_CFG['order'][$sortby])?$sortby:'hot'; //检查是否存在排序；
if(!empty($_GET['sortby'])){
	//setcookie('porder', $sortby, $cookie_time,'/',COOKIESDIAMON);  //保存排列方式 列表、网格
	//$_COOKIE['porder'] = $_GET['sortby'];
}
$order = $sortby;
$Arr['odr']=$sortby;
$display          = Param::get('display');    //显示方式
if(empty($display)){
	if(!empty($_COOKIE['layout'])){
		$display = $_COOKIE['layout'];
	}
	else{
		$display = $default_display_type;
	}
}
$display          = in_array($display, array('l', 'g')) ? $display : $default_display_type;
$is_24h_ship          = Param::get('24h_ship');    //显示方式
$is_24h_ship          = !empty($is_24h_ship) ? $is_24h_ship : (isset($_COOKIE['is_24h_ship']) ? $_COOKIE['is_24h_ship'] : 'f');
$freeship         = !empty($_GET['freeship'])?$_GET['freeship']:'f';    //显示方式
$Arr['24h_ship'] = $is_24h_ship;
switch ($pro) {    //不同栏目不同排序
    case 'new':    //New Arrival
    	$_CFG['order']['new'] = 'newordervalue ASC, sale_time DESC';
        $order    = !empty($_GET['sortby']) ? $_GET['sortby'] : (!empty($_COOKIE['NewOrder']) ? $_COOKIE['NewOrder'] : 'new');
        $Arr['odr'] = $order;
        $order    = !empty($_CFG['order'][$order]) ? $order : 'new';
        $odr      = $_CFG['order'][$order];
        $ur_here  = $_LANG["new_arrival"]; //'New Arrival';
        if(!empty($_GET['date']) && is_numeric($_GET['date'])){
        	$ur_here .=' » '.sprintf('%s/%s/%s', substr($_GET['date'], 4, 2), substr($_GET['date'], 6), substr($_GET['date'], 0, 4));
        }
		$nav_title      = $_LANG["new_arrival"];
		$nav_title_url  = "&raquo; " . $nav_title;
        $Arr['new_flag'] = '_s';
        $Arr['new_products_s'] = '_s';
        $sort       = '';
		$is_search  = 0 ; //控制左侧分类导航
		$shop_title = $_LANG['new_arrival'] . ' - ' . $_LANG['china_wholesale_ahappydeal'];
		//meta设置
		$seo_title = $_LANG_SEO['new_arrival']['title'];
		$seo_keywords = $_LANG_SEO['new_arrival']['keywords'];
		$seo_description = $_LANG_SEO['new_arrival']['description'];
        break;
    case 'hot':    //hot
        $order    = !empty($_GET['sortby']) ? $_GET['sortby']  : (!empty($_COOKIE['HotOrder']) ? $_COOKIE['HotOrder'] : 'hot');
        $order    = !empty($_CFG['order'][$order]) ? $order : 'hot';
        $Arr['odr'] = $order;
        $odr        = $_CFG['order'][$order];
        $ur_here    = '';
        $nav_title      = $_LANG["hot_products"];
		$nav_title_url  = "&raquo; " . $nav_title;
        $Arr['hot_flag']       = '_s';
        $Arr['hot_products_s'] = '_s';
        $sort       = '';
		$shop_title = $_LANG['hot_products'] . ' - ' . $_LANG['china_wholesale_ahappydeal'];
		//meta设置
		$seo_title = $_LANG_SEO['hot_goods']['title'];
		$seo_keywords = $_LANG_SEO['hot_goods']['keywords'];
		$seo_description = $_LANG_SEO['hot_goods']['description'];
        break;
    case 'freeship':    //hot
        $order    = !empty($order) ? $order : (!empty($_COOKIE['porder']) ? $_COOKIE['porder'] : 'hot');
        $order    = !empty($_CFG['order'][$order]) ? $order : 'hot';
        $odr      = $_CFG['order'][$order];
        $ur_here  = '';
        $nav_title      = $_LANG["Free_Shipping_products"];
		$nav_title_url  = "&raquo; " . $nav_title;
        $sort       = '';
        $shop_title = $_LANG['china_wholesale_cheap'];
        break;
    default:
        $order    = !empty($order) ? $order : (!empty($_COOKIE['porder']) ? $_COOKIE['porder'] : 'hot');
        $order    = !empty($_CFG['order'][$order]) ? $order : 'hot';
        $odr      = $_CFG['order'][$order];
        $ur_here  = '';
        $sort     = '';
		$search_display = 1; //如果是搜索页面，控制前端某些版块是否显示
		//meta设置
		$seo_title = str_replace(array('"','##keywords'), array("'",$keyword), $_LANG_SEO['search']['title']);
		$seo_keywords = $_LANG_SEO['goods']['keywords'];
		$seo_description = str_replace(array('"','##keywords'), array("'",$keyword), $_LANG_SEO['search']['description']);
        break;
}
$_GET['pro']= $pro;
if(empty($order)) $sortby = $order;
$order      = $_CFG['order'][$order];
$page       = Param::get('page', 'int');    //当前页
$page       = $page > 0 ? $page : 1;
$newpage    = empty($_GET['newpage'])?'':$_GET['newpage'];
if($newpage) {
	$page         = $newpage;
	$_GET['page'] = $page;
}
$page_size  = Param::get('page_size', 'int');
$page_size  = $page_size > 0 ? $page_size : (isset($_COOKIE['page_num']) ? $_COOKIE['page_num'] : $layoutpage[$display][1]);
$page_size  = $page_size > 0 ? intval($page_size) : 1;
if($search_display == 1) {
	$page_size = 36;
}
$total      = SPH_MAX_MATCHES;    //限制显示数
$pages      = ceil($total / $page_size);    //最大页数
$page       = $page > $pages ? $pages : $page;
$offset     = ($page - 1) * $page_size;    //偏移量
$t = !empty($_GET['t'])?$_GET['t']:'';     //搜索类型 ，客户 搜索或abc等
//页面缓存id
sprintf('%X', crc32("{$cat_id}-{$keyword}-{$display}-{$odr}-{$page}-{$page_size}-{$cur_lang}-{$max_price}-{$min_price}-{$pro}"));
$Tpl->caching = Param::get('c')&&!Param::get("k")?true:false; //使用缓存
$Tpl->caching =false;
if (!$Tpl->is_cached($_MDL . '.htm', $my_cache_id)) {
	$Arr['left_seo_recommened'] = get_recommend_goods_new('search_recommend_products_left'); //自动推荐产品 fangxin 2014/1/20
    require_once(ROOT_PATH . 'fun/fun.public.php');
    //require_once(ROOT_PATH . 'lib/class.page.php');
	require_once(ROOT_PATH . 'lib/class.page_new.php');
    require_once(ROOT_PATH . 'lib/lib.f.goods.php');
    require_once(ROOT_PATH . 'lib/sphinxapi.php');
    if ($_ACT == 'advanced_search') { //高级搜索
        $Arr['all_cat_list'] = cat_list();
        $Arr['action']       = 'form';
        $Arr['use_storage']  = $_CFG['use_storage'];
        $Arr['nav_title']    = ' &raquo; ' . $_LANG['Advanced_Search'];
    }
    else {    //搜索结果
        $Arr['action']       = 'form';
        if (Param::get('action') == 'form') {
            /* 要显示高级搜索栏 */
            $adv_value['keywords']  = $keyword;
            $adv_value['min_price'] = $min_price;
            $adv_value['max_price'] = $max_price;
            $adv_value['category']  = $cat_id;
            $Arr['adv_val']         =  $adv_value;
            $Arr['all_cat_list']    = cat_list($cat_id);
            $Arr['use_storage']     = $_CFG['use_storage'];
        }
		if($cur_lang != $default_lang){
			$category_arr =  read_static_cache($cur_lang.'_category_c_key',2);
		}else {
			$category_arr =  read_static_cache('category_c_key',2);
		}
        $cl = new SphinxClient(); //实例化sphinx
        $cl->SetFilter('cat_id', array(191),true);//过滤电子烟
		$cl->SetConnectTimeout(2);
		$cl->SetServer(SPH_HOST,SPH_PORT); //链接sphinx

		//多语言搜索 fangxin 2013/07/09
		$sph_index_main = SPH_INDEX_MAIN;
		if($cur_lang != $default_lang) {
			$sph_index_main .= '_' . $cur_lang;
		}
		$index = $sph_index_main;    //索引名称
		/*
		test sphinx fangxin
		$_query ="*fangxin*";
		$c2 = new SphinxClient();
		$c2->SetServer(SPH_HOST, SPH_PORT);
		$c2->SetConnectTimeout(2);
		$c2->SetLimits(0, 10, 10, 0);
		$c2->SetMatchMode(SPH_MATCH_EXTENDED2);	     // 匹配模式
		$result2 = $c2->Query($_query, $index);
		*/
        $cl->SetFilter('is_alone_sale', array(1));    //单卖
        if($is_24h_ship == 't') $cl->SetFilter('is_24h_ship', array(1));    //24 小时发货
        if($freeship == 't') $cl->SetFilter('is_free_shipping', array(1));    //free shipping
        $cl->SetFilter('goods_thumb', array(1));      //略缩图
        'hot' == $pro && $cl->SetFilterRange('add_time', gmtime() - 60 * 86400, gmtime());    //热卖，60天内
        $pingbi_arr = array();                        //屏蔽
        empty($_COOKIE['WEBF-dan_num']) && ($pingbi_arr = array(0));    //只显示不需要购买才可见产品
		$exclude_keywords = '!(';
        $is_pingbi = here_reset_keyword($keyword, $keyword_arr, $exclude_keywords); //处理关键字
		$is_pingbi && $cl->SetFilter('is_login', array(99));    //屏蔽关键字,is_login不存在
        if ($pro == 'new' ) {    //New Arrvial
        	if($pro == 'new' ){

	            $date = intval(str_replace('-', '', $date));
	            $cl->SetFilterRange('sale_date', $stime, $etime);    //15天内
	            $cl->SetGroupDistinct('group_goods_id');    //distinct
	            $cl->SetGroupBy('sale_date', SPH_GROUPBY_ATTR);    //分组
	            $cl->AddQuery('', $index);    //添加时间段查询，附空查询
	            $new_cat_arr   = array();
	            $_time         = microtime(true);
	            $cl->SetGroupBy('group_goods_id', SPH_GROUPBY_ATTR);
        	}
           if($date){
           		reset_sphinx_filter($cl,'sale_date');
           		$cl->SetFilter('sale_date', array($date));    //具体日期
           }
            $k = 1;
            $category_children = read_static_cache('category_children', 2);    //顶级分类
            foreach ($category_children as $key => $val) {
                if ($val['is_show'] && (!$val['is_login'] || $val['is_login'] && !empty($_COOKIE['WEBF-dan_num']))) {
                    if($pro == 'hot')
                		$category_arr[$key]['url_title'] = '/pt-hot-c-' . $key . '.html';
                	else{
                		if($date){
                			$category_arr[$key]['url_title'] = "/pt-new-date-$date-c-" . $key . '.html';
                		}else {
                			$category_arr[$key]['url_title'] = '/pt-new-c-' . $key . '.html';
                		}
                	}
                    reset_sphinx_filter($cl, 'cat_id');    //重置cat_id
                    array_push($val['children'], $key); //把本级类ID加到下级类数组里
                    $cl->SetFilter('cat_id', $val['children']);
                    $cl->AddQuery('', $index);
                    $new_cat_arr[$k] = $category_arr[$key];    //key值索引，循环结果时一一对应
                    $k++;
                }
            }
        }
		$keyword_s = str_replace(array('phones','tablets','pumpkins','android cell phone','android tv cell phone','iphone5','iphone4'),array('phone','tablet','pumpkin','android smartphone','Android TV Smartphone','iphone 5','iphone 4'),strtolower($keyword));  //等效关键词
		$query    = here_build_query($keyword, $keyword_arr, $exclude_keywords, 'and'); //fangxin
		if($keyword !='' and  $t != 'seo'){
			$k = 0;
			$category_children = read_static_cache('category_children', 2); //顶级分类
				foreach ($category_children as $key => $val) {
					if ($val['is_show'] && (!$val['is_login'] || $val['is_login'] && !empty($_COOKIE['WEBF-dan_num']))) {
						$category_arr[$key]['url_title'] = '/c-'.$key.'-wholesale-'.title_to_url($keyword).'.html';
						reset_sphinx_filter($cl, 'cat_id');    //重置cat_id
						array_push($val['children'], $key);
						$cl->SetFilter('cat_id', $val['children']);
						$cl->AddQuery($query, $index);
						$new_cat_arr[$k] = $category_arr[$key];    //key值索引，循环结果时一一对应
						$k++;
					}
			   }
			   reset_sphinx_filter($cl, 'cat_id');
		}
		if($keyword !='' and  $t != 'seo'){
		$k = 0;
		$category_children = read_static_cache('category_children', 2);    //顶级分类
			foreach ($category_children as $key => $val) {
				if ($val['is_show'] && (!$val['is_login'] || $val['is_login'] && !empty($_COOKIE['WEBF-dan_num']))) {
					$category_arr[$key]['url_title'] = '/c-'.$key.'-wholesale-'.title_to_url($keyword).'.html';
					reset_sphinx_filter($cl, 'cat_id');    //重置cat_id
					array_push($val['children'], $key);
					$cl->SetFilter('cat_id', $val['children']);
					$cl->AddQuery($query, $index);
					$new_cat_arr[$k] = $category_arr[$key];    //key值索引，循环结果时一一对应
					$k++;
				}
		   }
		   reset_sphinx_filter($cl, 'cat_id');
		}
		$pro == 'best' && $cl->SetFilter('is_best', array(1));    //热卖
		$pro == 'freeship' && $cl->SetFilter('is_free_shipping', array(1));    //热卖
		!empty($outstock) && $cl->SetFilter('goods_number', array(1));    //库存
		!empty($min_price) && !empty($max_price) && $cl->SetFilterFloatRange('shop_price', $min_price, $max_price);
		$cl->SetLimits(intval($offset), $page_size,SPH_MAX_MATCHES);    //limit
		reset_sphinx_filter($cl, 'cat_id');    //重置cat_id
		$cl->SetFilter('cat_id', array(191),true);//过滤电子烟
		if ($cat_id){    //分类
			$cat_str = getChildArr($category_arr, $cat_id);
			$cat_arr = explode(',', $cat_str . $cat_id);
			$cl->SetFilter ('cat_id', $cat_arr);
		}
		$sort = 'goods_number DESC,' . $order . ',add_date DESC';
		//判断是普通搜索 还是 abc关键词搜索
		require(ROOT_PATH . 'lib/seo/class.seo_filter_upload_keywords.php');
		$class  = new SEO_Filter_Upload_Keywords();
		if ($search_type == 1)
		{
			//ABC词入库
			if (1 == $page) {
				$class->insertUserKeyword($keyword);
			}
		}
		if(empty($pro) && $keyword ) {
            $cl->SetFieldWeights(array('goods_title' => 2));
            $page > 10 && header('HTTP/1.0 404 Not Found'); //by mashanling on 2013-07-12 09:11:40
			// /Wholesale-page-10.html,... by mashanling on 2013-05-14 10:43:56
            if (false !== strpos($keyword, 'page ') && preg_match('#^page \d+$#', $keyword)) {
                redirect_url('/', 301);
            }
            reset_sphinx_filter($cl, 'is_on_sale');
            $cl->SetFilter ('is_delete', array(2), true); //过滤is_delete=2,侵权产品
            $is_new_abc = true;
            $_GET['is_new_abc'] = true;//分页调到
            $Arr['no_order']     = true;
            $is_abc              = true;
            $sort                = 'goods_number DESC,@weight DESC,week2sale DESC';  //abc搜索使用相关度排序
            $page_size           = 36;
            $pages               = ceil($total / $page_size);    //最大页数
			$db_abc = get_db('abc');
            $abc_keyword_info_sql = 'SELECT * FROM ' . ABCKEYWORD_NEW2 . " WHERE `keyword`='" . addslashes(stripslashes($keyword)) . "' LIMIT 1";
            $abc_keyword_info     = $db_abc->arrQuery($abc_keyword_info_sql);
            $abc_keyword_info     = $abc_keyword_info ? $abc_keyword_info[0] : array();
			if ($abc_keyword_info) {
                if ($Arr['related_cat_keywords_arr'] = $class->getAbcRelativeCache($abc_keyword_info['keyword_id'])) {
                    $Arr['related_cat_keywords'] = '<ul>';
                    foreach($Arr['related_cat_keywords_arr'] as $key=>$value) {
                    	preg_match_all('/<a.*?(?: |\\t|\\r|\\n)?href=[\'"]?(.+?)[\'"]?(?:(?: |\\t|\\r|\\n)+.*?)?>(.+?)<\/a.*?>/sim',$value,$matches_related);
                    	$Arr['related_cat_keywords'] .= '<li><a href="'. $matches_related[1][0] .'">'. ucwords($matches_related[2][0]) .'</a></li>';
                    }
                    $Arr['related_cat_keywords'] .= '</ul>';
                }
				$link = creat_nav_url($category_arr[$abc_keyword_info['cat_id']]['url_title'],$abc_keyword_info['cat_id']);
				if(!empty($category_arr[$abc_keyword_info['cat_id']]['cat_name'])) {
					$category_url = ' &raquo;&nbsp;<a href="'.$link.'" title="'.$category_arr[$abc_keyword_info['cat_id']]['cat_name'].'">'.$category_arr[$abc_keyword_info['cat_id']]['cat_name']."</a>: ";
					$relate_cat_id = $abc_keyword_info['cat_id'];
				} else {
					$category_url = '';
				}
			}
			if(empty($abc_keyword_info)
			|| (count($Arr['related_cat_keywords']) == 0 && is_array($Arr['related_cat_keywords']))
			|| (count($Arr['related_cat_keywords']) == 1 && empty($Arr['related_cat_keywords']))
			|| (count($Arr['related_cat_keywords']) == 0 && empty($Arr['related_cat_keywords']))
			) {
				$Arr['related_cat_keywords'] = get_related_cat_keywords($category_arr, 8);
			}
			if(count($Arr['related_cat_keywords']) < 4) {
				$Arr['related_cat_keywords_1'] = get_related_cat_keywords($category_arr, 4);
			}
		}
		$cl->SetMatchMode(SPH_MATCH_EXTENDED); //普通搜索用扩展模式
        $cl->SetSortMode(SPH_MATCH_EXTENDED, $sort); //排序
        $cl->SetGroupBy('group_goods_id', SPH_GROUPBY_ATTR, $sort); //分组
		$query    = keyword_Singular_plural($query); //单复数处理
		$cl->SetFilter('cat_id', array(191),true);//过滤电子烟
        $cl->AddQuery($query, $index); //添加关键字查询
        $result    = $cl->RunQueries(); //执行查询	 result
        $goods_ids = '0';
        $count     = 0;
        $firstnum  = 0;
        $lastnum   = 0;
        if ($result === false) {    //查询出错
            $error      = $cl->GetLastError();
            $Arr['sql'] = var_export($error, true);
        }
        else {
            if ($pro == 'new') {
                if (!empty($result[0]['matches'])) {    //时间段
                    foreach ($result[0]['matches'] as $item) {
                        $date       = $item['attrs']['sale_date'];
                        $nav_date[] = array('adate' => $date, 'dnum' => $item['attrs']['@distinct'], 'date' => sprintf('%s/%s/%s', substr($date, 4, 2), substr($date, 6), substr($date, 0, 4)));
                    }
                }
            }
            if(!empty($new_cat_arr)){
	            foreach ($new_cat_arr as $key => $item) {    //分类统计数量
					if(!empty($result[$key])) {
	                   $new_cat_arr[$key]['cnum'] =  $result[$key]['total_found'];
	                   $new_cat_arr[$key]['cat_name'] = get_cat_name($new_cat_arr[$key]['cat_id'],$new_cat_arr[$key]['cat_name']);
	                   if(empty($new_cat_arr[$key]['cnum'])){
	                   	unset($new_cat_arr[$key]);
	                   }
					}
	            }
				if(!empty($new_cat_arr)) {
					foreach($new_cat_arr as $key => $value) {
						$url_title = str_replace("//","/",str_replace(".html", "/", $value['url_title']));
						$new_cat_arr[$key]['url_title'] =  $url_title;
					}
				}
	            $Arr['ByCatNewArr'] = $new_cat_arr;
            }
            $result = array_pop($result);
            if ($result['total_found'] < 36 && !empty($keyword) && !empty($query)) {
                $_query = str_replace(' ', '|', $query);
                if (empty($result['matches'])) {
                    $cl->SetLimits(0, 36*10, SPH_MAX_MATCHES);
                    $result = $cl->Query($_query, SPH_INDEX_MAIN);
          			if(!empty($result['matches'])){
                    	$id_arr = array_rand($result['matches'],$result['total_found']>36?36:$result['total_found']);
	                    foreach ($result['matches'] as $k=>$v){
	                    	if(is_array($id_arr)&&!in_array($k,$id_arr)){
	                    		unset($result['matches'][$k]);
	                    	}
	                    }
	                    if($result['total_found']>36)$result['total_found']=36;
	                    $result2 = true;
          			}
                }
                else {
					reset_sphinx_filter($cl, 'cat_id');
                    !empty($abc_keyword_info['cat_id']) && $cl->SetFilter('cat_id', array($abc_keyword_info['cat_id']));
                    $cl->SetLimits(0, intval(36 - $result['total']), SPH_MAX_MATCHES);
                    $cl->SetFilter('@id', array_keys($result['matches']), true);
                    $result2 = $cl->Query($_query, SPH_INDEX_MAIN);
                    if (!empty($result2['matches'])) {
                        $result['matches'] += $result2['matches'];
                    }
                }
            }
            if (!empty($result['matches'])) {    //匹配结果
                $goods_ids = join(',', array_keys($result['matches']));    //匹配id
                $count     = min($total, $result['total_found']);
                $max_page  = ceil($count / $page_size);
                $page      = min($page, $max_page);
                $firstnum  = ($page - 1) * $page_size + 1;
                $lastnum   = $page * $page_size;
                $lastnum   = min($lastnum, $count);
                $Arr['sql'] = $result['total_found'];
                if ($keyword) {
                    reset_sphinx_filter($cl, 'cat_id');
                    reset_sphinx_filter($cl, '@id');
                    $cl->SetLimits(0, 1000);
                    $cl->SetArrayResult(true);
                    $cl->SetGroupBy('cat_id', SPH_GROUPBY_ATTR, '@count DESC');    //分组
                    $group = $cl->Query($query, SPH_INDEX_MAIN);
                    if (!empty($group['matches'])) {
                        $top_cat_arr = array();
                        $matches_cat_arr = array();
                        foreach($group['matches'] as $k => $v) {
                            $_cat_id = $v['attrs']['@groupby'];
							if(!empty($category_arr[$_cat_id])) {
                            	$_cat_info = $category_arr[$_cat_id];
							}
                            list($_top_id,) = explode(',', $_cat_info['node']);
                            if ($k < 4) {
								$link_url = str_replace(array('c-','.html','www.bef.com','a05.everbuying.net'), array('c_','/','www.dmf01.com','www.dealsmachine.com'), $_cat_info['link_url']);
                                $top_cat_arr[] = '<a href="' . $link_url . '">' . $_cat_info['cat_name'] . '</a>';
                            }
                            if ($cat_id != $_top_id && !isset($matches_cat_arr[$_top_id])) {
                                $_cat_info = $category_arr[$_top_id];
                                $matches_cat_arr[$_top_id] = array('url' => '/affordable/' . title_to_url($_GET['k'], '\.') . '/' . $_top_id . '/', 'name' => $_cat_info['cat_name']);
                            }
                        }
                        if (count($matches_cat_arr) > 1 || $cat_id) {
                            $Arr['matches_cat_arr']  = $matches_cat_arr;
                        }
                    }
                }
            }
            elseif (!empty($keyword)) {
                if(empty($pro)) { //by mashanling on 2013-07-17 09:03:51
                    header('HTTP/1.0 404 Not Found');
                }
                get_cache_best_goods(4);
            }
            if (!empty($Arr['related_cat_keywords'])) {//相关再处理 by mashanling on 2013-04-09 15:24:44
                $t = 'dealsmachine.com offers best ' . $keyword . ' at great prices. dealsmachine has a wide selection of ';
                $v = str_replace(array('<ul><li>', '</li></ul>'), '', $Arr['related_cat_keywords']);
                $v = explode('</li><li>', $v);
               	$n = 4;
                $v1 = array_slice($v, 0, $n);
                $v2 = array_slice($v, $n);
				if(!empty($Arr['related_cat_keywords_1'])) {
					$v_1 = str_replace(array('<ul><li>', '</li></ul>'), '', $Arr['related_cat_keywords_1']);
					$v_1 = explode('</li><li>', $v_1);
				}
                $t .= join(', ', $v1) . ". Best deals on " . join(', ', empty($top_cat_arr) ? $v_1 : $top_cat_arr) . '.';
                $Arr['related_cat_keywords'] = $t;
            }
            //记录搜索情况以便统计 by Jim on 9-5
            $u_str = '';
            if($count == 0){
            	$u_str .=",'not_found' =>1";
            }elseif($page>1 && !empty($_GET['k'])){
				$canonical_uri = get_search_url($_GET['k']);
				$Arr['canonical_uri'] = DOMAIN . $canonical_uri; //SEO 把重点指向第一页 by jim
            }elseif(isset($is_abc) && empty($_POST['k'])){
				preg_match('/\/([^\/]+\.[a-z]+)[^\/]*$/', $_SERVER['REDIRECT_URL'], $match);
		        $file_str = explode('.', $match[1]);
            	if(is_numeric($file_str[0])) {
            		$canonical_uri = get_search_url($_GET['k']);
            		$Arr['canonical_uri'] = DOMAIN . $canonical_uri; //SEO 把重点指向第一页 by jim
            	}
            }
            if ((strpos($_SERVER['REQUEST_URI'], '/buy-cheap') === false) && trim($keyword) && strlen(trim($keyword))<100 && $page == 1){		//只统计第一次查询结果
            		if($count==0){
						$db->autoReplace(KEYWORDS, array('date' => local_date('Y-m-d'),
						'searchengine' => $_CFG["shop_name"], 'keyword' => trim($keyword), 'count' => 1,'not_found'=>1), array('count' => 1,'not_found'=>1));
            		}else {
  						$db->autoReplace(KEYWORDS, array('date' => local_date('Y-m-d'),
						'searchengine' => $_CFG["shop_name"], 'keyword' => trim($keyword), 'count' => 1), array('count' => 1));
            		}
			}
            if ($pro == 'hot' && $count > 232) {    //热卖商品展示条数限制
                $count = 232;
            }
        }
        unset($cl, $result);
        $sql = 'SELECT is_24h_ship,presale_date_from,presale_date_to,is_delete,is_on_sale,g.goods_id,g.goods_id AS goods_id_attr,goods_title,market_price,goods_name,goods_sn,is_new_sn,shop_price,goods_weight,cat_id,is_free_shipping,is_promote,is_best,is_hot,shop_price AS org_price,url_title,promote_price,promote_start_date,promote_end_date,goods_thumb,goods_grid,goods_img,goods_brief,goods_type,IF(is_delete=1 OR is_on_sale=0, 0, goods_number) AS goods_number FROM ' . GOODS .' g left join '.GOODS_STATE.' s on g.goods_id=s.goods_id '. "  WHERE g.goods_id IN({$goods_ids}) ORDER BY FIND_IN_SET(g.goods_id, '{$goods_ids}')";
        $data = $db->arrQuery($sql);
        $arr          = array();
        $keyword_arr  = array_map(create_function('$v', ' $v = "/($v)/i"; return $v;'), $keyword_arr);
        $super_star  = read_static_cache('super_star',1);    //后台推荐明显产品
        foreach ($data as $row) {
            if ($row['promote_price'] > 0) {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            }
            else {
                $promote_price = 0;
            }
            $goods_id                          = $row['goods_id'];
            $arr[$goods_id]['goods_id']        = $goods_id;
            $goods_title                       = $row['goods_title'];
            $goods_brief                       = strip_tags($row['goods_brief']);
            $goods_brief                       = $goods_brief ? sub_str($goods_brief, 110) : '';
            $arr[$goods_id]['goods_full_title']= $row['goods_title'];
            if (strpos($row['goods_name'], ',') !== false) {
                $row['goods_name'] = explode(',', $row['goods_name']);
                $row['goods_name'] = $row['goods_name'][0];
            }
            $arr[$goods_id]['goods_title'] = sub_str($goods_title, 60, '...');
            $arr[$goods_id]['goods_brief'] = $goods_brief;
            $arr[$goods_id]['is_24h_ship'] = $row['is_24h_ship'];
	        if($row['presale_date_from']){
				if($row['presale_date_from'] > gmtime()){
					$arr[$goods_id]['presale_date_from'] =$row['presale_date_from'];
				}else {
					$arr[$goods_id]['presale_date_from'] = '';
				}
			}
	        if($row['presale_date_to']){
				if($row['presale_date_to'] > gmtime()){
					$arr[$goods_id]['presale_date_to'] =$row['presale_date_to'];
				}else {
					$arr[$goods_id]['presale_date_to'] = '';
				}
			}
            if(!empty($_GET['k'])) $_GET['k'] = HtmlEncode($_GET['k']);
            $cat_name                          = empty($category_arr[$row['cat_id']]['cat_name']) ? '' : $category_arr[$row['cat_id']]['cat_name'];
            $big_cat                           = empty($category_arr[$row['cat_id']]['parent_id']) ? true : false;
            $arr[$goods_id]['cat_name']        = $cat_name;
            $arr[$goods_id]['cat_url']         = empty($category_arr[$row['cat_id']]) ? '' : creat_nav_url($category_arr[$row['cat_id']]['url_title'], $row['cat_id'], $big_cat);
            $arr[$goods_id]['type']            = $row['goods_type'];
            $arr[$goods_id]['cat_id']          = $row['cat_id'];
            $arr[$goods_id]['goods_sn']        = $row['goods_sn'];
            $arr[$goods_id]['goods_number']    = $row['goods_number'];
            $arr[$goods_id]['goods_weight']    = formated_weight($row['goods_weight']);
            $arr[$goods_id]['market_price']    = price_format($row['market_price']);
            $arr[$goods_id]['is_promote']      = ($promote_price > 0) ? $row['is_promote'] : 0;
            $arr[$goods_id]['promote_price']   = ($promote_price > 0) ? price_format($promote_price) : '';
            $arr[$goods_id]['promote_zhekou']  = ($promote_price > 0 && $row['market_price']>0) ? round(($row['market_price'] - $promote_price) / $row['market_price'], 2) * 100 : '';
            $arr[$goods_id]['shop_price']      = ($promote_price > 0) ? price_format($promote_price) : price_format($row['shop_price']);
            $arr[$goods_id]['review']          = get_review_rate($goods_id);
            $arr[$goods_id]['goods_thumb']     = get_image_path($goods_id, $row['goods_thumb'], true);
            $arr[$goods_id]['goods_img']       = get_image_path($goods_id, $row['goods_img']);
            $arr[$goods_id]['goods_grid']      = get_image_path($goods_id, $row['goods_grid']);
            $arr[$goods_id]['is_free_shipping']= $row['is_free_shipping'];
            $arr[$goods_id]['saveprice']       = price_format($row['market_price'] - $row['shop_price']);
            $arr[$goods_id]['saveperce']       = ($row['market_price'] == 0 || is_null($row['market_price'])) ? '0' : price_format(($row['market_price'] - $row['shop_price']) / $row['market_price']) * 100;
            $arr[$goods_id]['url_title']       = get_details_link($goods_id, $row['url_title']);
            if($super_star && in_array($row['goods_sn'],$super_star)){
                $arr[$goods_id]['is_super_star'] = 1;
            }

        }//end while
        if(empty($arr)){
			here_get_top_goods(8);
		}

		// 多语言 fangxin 2013/07/05
		if($cur_lang != $default_lang) {
			if(is_array($arr)) {
				foreach($arr as $key=>$value) {
					$sql = 'SELECT g.*' .
							' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
							" WHERE g.goods_id = '$key'";
					if($row_lang = $GLOBALS['db']->selectinfo($sql)) {
						$arr[$key]['goods_title'] = $row_lang['goods_title'];
					}
				}
			}
		}

        $Arr['collect_info_lang'] = $_LANG['COLLECT_INFO'];
        $Arr['collect_info_lang_json'] = json_encode($_LANG['COLLECT_INFO']);
        $cat_arr = array();
        foreach($category_arr as $v) {
            if (!$v['parent_id'] && $v['is_show']) {
                $cat_arr[$v['cat_id']] = $v['cat_name'];
            }
        }
        $cat_arr[0] = 'Others';
        $Arr['cat_arr'] = $cat_arr;
        if($display=='l' && !empty($arr) && $cur_lang == $default_lang) $Arr['NewArrivalArr'] = get_right_new_arrival('','');
        $Arr['goods_list']      = $arr;
        $Arr['category']        = $cat_id;
        $Arr['search_keywords'] = stripslashes($keyword);
        $Arr['min_price']       = $min_price;
        $Arr['max_price']       = $max_price;
		if($m != 'search') {
			$Arr['cat_id']      = isset($relate_cat_id)?$relate_cat_id:'';
		}
		if($m == 'search' && empty($pro)) {
			$Arr['module']      = 'search';
		}
        $kk                     = stripslashes($keyword);
		if(empty($kk)) {
			$kk = stripslashes($nav_title);
		}
        if(empty($nav_title)){
	        if ($ur_here == '') {
				$nav_title_url = ' : <h1>' . ucwords($kk) . '</h1>';
	        }
	        else {
	            $nav_title_url = ' &raquo;  ' . $ur_here . ' ';
	        }
        }
    	$url_para ='?'; // 附带参数
        $in_word = '';
        $pro == 'hot' && $in_word = ', ' . $_LANG['china_mobile_phone'];
        $Arr['display']    = $display;
        $Arr['freeship']   = $freeship;
        $Arr['sortby']     = $sortby;
        $Arr['cat_name']   = $kk;
        $Arr['seo_title']         = !empty($shop_title)?$shop_title."":"$kk $in_word - " . $_LANG['china_wholesale_ahappydeal'];
        $Arr['page_size']          = $page_size;
        $Arr['kk']                 = $kk;
        $Arr['layoutpage']         = $layoutpage[$display];
        $Arr['total']              = $count;
        $Arr['firstnum']           = $firstnum;
        $Arr['lastnum']            = $lastnum;
        $Arr['page']               = $page;
        $Arr['most_searched_tags'] = explode(',', $_CFG['most_searched_tags']);
        $Arr['his_list']           = insert_history();
        if ((!isset($is_new_abc) || $Arr['page'] < 11) && !isset($result2)) {
			$page = new page(array('total' => $count, 'perpage' => $page_size));
			$Arr['pagestr'] = $page->show(5);
        }
        $Arr['left_polices']       = get_top_article_list(); //置顶文章
        $Arr['nav_title']          = $nav_title_url;
        $Arr['nav_date']           = $nav_date;
		//搜索关键词链接带参数的指向原地址 fangxin 2013/11/15
		if(!empty($_SERVER["REDIRECT_QUERY_STRING"]) && !isset($Arr['canonical_uri'])){
			$cononical = WEBSITE;
			$cononical = substr($cononical,0,(strlen($cononical)-1));
			$Arr['canonical_uri'] = $cononical . $_SERVER["REDIRECT_URL"];
		}
		//meta设置
		$Arr['seo_title'] = $seo_title;
		$Arr['seo_keywords'] = $seo_keywords;
		$Arr['seo_description'] = $seo_description;
    }//end 搜索结果
	$Arr['is_search'] = $is_search ; //控制左侧分类导航
    if($cur_lang !='en')$Arr['left_catArr'] = getDynamicTree(0);
	$Arr['page'] = 'search';
	$Arr['search_display'] = $search_display;
	//谷歌再营销
	$currency = get_currency();
	$google_tag_params = array(
		'prodid' => "''",
		'pagetype' => "'searchresults'",
		'totalvalue' => "''",
		'currency' => "'". $currency['currency'] ."'",
		'pcat' => "''"
	);
	$Arr['google_tag_params'] = $google_tag_params;

} //缓存结束


//==================================================华丽分割线==================================================
/**
 * 取得所有所子类id，并返回数组
 *
 * @param array $category_arr 所有分类
 * @param int   $cat_id       分类id
 */
function getChildArr($category_arr, $cat_id) {
    $cat_idArr = '';

    foreach ($category_arr as $kk => $vv) {

        if ($vv['parent_id'] == $cat_id) {
            $cat_idArr .= $vv['cat_id'] . ',' . getChildArr($category_arr, $vv['cat_id']);
        }
    }

    return $cat_idArr;
}

/**
 * 重置sphinx过滤条件
 *
 * @param object  $cl   sphinx实例
 * @param string  $attr 过滤key，即SetFilter(key, value)中的key
 */
function reset_sphinx_filter(&$cl, $attr) {;
    foreach ($cl->_filters as $key => $item) {

        if (!empty($item['attr']) && $item['attr'] == $attr) {
            unset($cl->_filters[$key]);
            break;
        }

    }

}

/**
 * SEO推荐的产品
 * Enter description here ...
 * @param unknown_type $max_num
 */
function get_seo_recommened($max_num = 10)
{
	global $cur_lang, $default_lang;
	$sql = 'SELECT * FROM eload_activity WHERE id=1';
	$activity_info = $GLOBALS['db']->selectinfo($sql);
	if(!empty($activity_info['act_goods_list'])){
        $goods_list_sn = "'".str_replace(',',"','",$activity_info['act_goods_list'])."'";
        $sql = 'SELECT s.is_24h_ship,g.goods_number,s.sold,g.goods_id,goods_sn,cat_id, goods_title,original_img ,goods_img,goods_name_style,is_free_shipping,shop_price,promote_price,promote_start_date, promote_end_date,goods_thumb,market_price,goods_grid,sort_order,url_title ' .

        ' FROM ' . GOODS . ' AS g left join ' .GOODS_STATE.' s on g.goods_id=s.goods_id '.
       " WHERE is_on_sale = 1 AND is_alone_sale = 1 AND goods_sn in($goods_list_sn) AND is_delete = 0 ";

	   if(!empty($goods_list_sn))$sql .=" ORDER BY  FIND_IN_SET(g.goods_sn, '".str_replace("'",'',  $goods_list_sn)."')";
	      $sql .=" LIMIT $max_num";
		$goods_res = $GLOBALS['db']->arrQuery($sql);
	    if(!$goods_res)return false;
	    $arr = array();
	 	foreach($goods_res as $row)
	    {
	        if ($row['promote_price'] > 0)
	        {
	            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
	        }
	        else
	        {
	            $promote_price = 0;
	        }
			$isbig = '';
	        $arr[$row['goods_id']]['goods_title']        = $row['goods_title'];
	        $arr[$row['goods_id']]['is_free_shipping']   = $row['is_free_shipping'];
	        $arr[$row['goods_id']]['short_name']         = sub_str($row['goods_title'], 48, '...');
	        $arr[$row['goods_id']]['goods_thumb']        = get_image_path($row['goods_id'], $row['goods_thumb'], true);
	        $arr[$row['goods_id']]['shop_price']         = ($promote_price>0)?price_format($promote_price):price_format($row['shop_price']);
	        $arr[$row['goods_id']]['url_title']           = get_details_link($row['goods_id'],$row['url_title']);
	        if ($row['promote_price'] > 0)
	        {
	            $arr[$row['goods_id']]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
	            $arr[$row['goods_id']]['formated_promote_price'] = price_format($arr[$row['goods_id']]['promote_price']);
	        }
	        else
	        {
	            $arr[$row['goods_id']]['promote_price'] = 0;
	        }
	    }
	}else{
		return false;
	}

	// 多语言 fangxin 2013/07/05
	if($cur_lang != $default_lang) {
		if(is_array($arr)) {
			foreach($arr as $key=>$value) {
				$sql = 'SELECT g.*' .
						' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
						" WHERE g.goods_id = '$key'";
				if($row_language = $GLOBALS['db']->selectinfo($sql)) {
					$arr[$key]['goods_title'] = $row_language['goods_title'];
					$arr[$key]['short_name'] = sub_str($row_language['goods_title'], 70);
				}
			}
		}
	}
	return $arr;
}


/**
 * 无产品时,获取推荐产品 即上架2个月的产品，且2个月销售数量在4个以上的有库存的产品
 *
 * @author          mashanling <msl-138@163.com>
 * @date            2013-05-24 15:39:01
 *
 * @param int $num 个数.默认8
 *
 * @return void 无返回值
 */
function here_get_top_goods($num = 8)
{
	global $db, $Arr, $cur_lang, $default_lang;
    $filename = 'search_no_goods_cache_'. $cur_lang;
    if ($data = read_static_cache($filename)) {//读缓存

        if ($data['time'] > gmtime() - 10800) {//缓存三小时
            return $Arr['best_goods'] = $data['data'];
        }
    }
    $now    = gmtime();
    $sql    = 'SELECT COUNT(og.goods_number) AS sole_nums,g.goods_id,g.is_free_shipping,g.goods_img,g.goods_title,g.url_title,g.goods_thumb,g.market_price,g.shop_price,g.goods_name_style,g.promote_price,g.promote_start_date,g.promote_end_date';
    $sql   .= ' FROM ' . GOODS . ' AS g ';
    $sql   .= ' JOIN ' . ODRGOODS . ' AS og ON og.goods_id=g.goods_id';
    $sql   .= ' JOIN ' . ORDERINFO . ' AS o ON o.order_id=og.order_id WHERE';
    $sql   .= IS_LOCAL ? ' g.goods_id>115653' : ' o.add_time>' . ($now - 86400 * 2);
    $sql   .= ' AND g.is_delete=0 AND g.is_on_sale=1 AND g.goods_number>0';
    $sql   .= ' GROUP BY g.goods_id ORDER BY sole_nums LIMIT ' . $num;
    $data      = array();
    $db->query($sql);

    while($row = $db->fetchArray()) {
        $goods_id = $row['goods_id'];
        $data[$goods_id]['goods_id']     = $row['goods_id'];
        $data[$goods_id]['goods_title']     = $row['goods_title'];
        $data[$goods_id]['goods_img']       = get_image_path(false, $row['goods_img']);
        $data[$goods_id]['goods_thumb']     = get_image_path(false, $row['goods_thumb']);
        $data[$goods_id]['url_title']       = get_details_link($goods_id, $row['url_title']);
        $data[$goods_id]['market_price']    = price_format($row['market_price']);
        $data[$goods_id]['is_free_shipping']    = $row['is_free_shipping'];

        $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        $data[$goods_id]['shop_price']      = price_format($promote_price > 0 ? $promote_price : $row['shop_price']);
        $data[$goods_id]['zhekou'] = ($row['promote_price'] == 0||$row['market_price'] == 0 || is_null($row['market_price']))?'0': price_format(($row['market_price'] - $row['promote_price'])/$row['market_price'])*100;
    }

	// 多语言 fangxin 2013/07/05
	if($cur_lang != $default_lang) {
		if(is_array($data)) {
			foreach($data as $key=>$value) {
				$sql = 'SELECT g.*' .
						' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
						" WHERE g.goods_id = '$key'";
				if($row_language = $GLOBALS['db']->selectinfo($sql)) {
					$data[$key]['goods_title'] = $row_language['goods_title'];
					$data[$key]['short_name'] = sub_str($row_language['goods_title'], $len);
				}
			}
		}
	}
    write_static_cache($filename, array('data' => $data, 'time' => gmtime()));//写缓存
    $Arr['best_goods'] = $data;
}//end here_get_top_goods


/**
 * 处理查询关键字，包括过滤，屏蔽，单复数处理，搜索men 排除产品名包含 women等
 *
 * @author       mashanling(msl-138@163.com)
 * @date         2012-11-17 09:17:14
 * @last modify  2012-11-23 08:59:58 by mashanling
 *
 * @param string $keyword          关键字
 * @param array  $keyword_arr      空隔隔开关键字数组
 * @param string $exclude_keywords 排除产品名关键字
 *
 * @return bool 如果包含屏蔽字，返回true，否则返回false
 */
function here_reset_keyword($keyword, &$keyword_arr, &$exclude_keywords) {
    if ($keyword != '') {
        $_keyword     = strtolower(stripslashes($_REQUEST['k']));
        $_keyword     = str_replace('-', ' ', $_keyword);
        $_keyword     = preg_replace('/\s+/', ' ', $_keyword);    //替换连续空格
        $fliter_key = read_static_cache('fliter_keyword', FRONT_STATIC_CACHE_PATH);
		$filter_keywords = read_static_cache('filter_search_keywords_cache');
		$guolv = !empty($filter_keywords['exclude']) ? $filter_keywords['exclude'] : array();//搜索排除
		//不搜索的词
		$not_search = !empty($filter_keywords['not_search']) ? $filter_keywords['not_search'] : array();
		//单复数
		$dan_fu_arr = !empty($filter_keywords['dan_fu']) ? $filter_keywords['dan_fu'] : array();
		//$not_search_copyright = !empty($filter_keywords['not_search_copyright']) ? $filter_keywords['not_search_copyright'] : array();
		foreach ($fliter_key as $item) {//屏蔽
			if (preg_match('-\b' . preg_quote($item, '-') . '\b-', $_keyword)) {    //包含屏蔽关键字，搜索不到结果
				return true;//$pingbi_arr = array(99);    //is_login=99是不存在的
				//Func::crontab_log('fun.search.php', "{$_keyword}[{$item}]" . PHP_EOL);
				break;
			}
		}
		foreach ($keyword_arr as $key => $item) {
			if(in_array($item, $not_search)) {//去除不要搜索的词
				unset($keyword_arr[$key]);
			}
			/*
			if(in_array($item, $not_search_copyright)) {//去除侵权词
				unset($keyword_arr[$key]);
			}
			*/
			if (isset($dan_fu_arr[$item])) { //复数，单数搜索
				//$keyword_arr[$key] = $dan_fu_arr[$item];
			}
			if (isset($guolv[$item])) {//排除处理，直接有对应排除关系
				$loop = $guolv[$item];
			}
			elseif (isset($dan_fu_arr[$item]) && isset($guolv[$dan_fu_arr[$item]])) {//复数处理成单数后
				$loop = $guolv[$dan_fu_arr[$item]];
			}
			if(isset($loop)) {//排除，如搜索 men 排除 产品名包含 women 产品
				foreach($loop as $v) {
					$exclude_keywords .= $v . '|';
				}
			}
		}
		$exclude_keywords = substr($exclude_keywords, 0, -1);
		if(strlen($exclude_keywords) > 2) {
			$exclude_keywords  = ' @goods_title ' . $exclude_keywords;
			$exclude_keywords .= ')';
		}
		else {
			$exclude_keywords = '';
		}
    }//end if $keyword
    return false;
}//end here_reset_keyword


/**
 * 生成sphinx 搜索条件
 *  abc搜索 by mashanling on 2012-10-16 09:52:39
 * 1、增加所有产品的类名到商品关键字
 1）现有产品批量更新（增加而不是替换）
 2）新产品系统自动增加类名到商品关键字。
 2、搜索词拆分词后排除and, the , or ,with，by, against, either, so, why，as, for, on, in, at这些词不参与搜索匹配。
 3、当搜索词拆分词长度小于等于两个时，不模糊匹配，规则为完全匹配。
 *
 * @author       mashanling(msl-138@163.com)
 * @date         2012-11-17 09:27:17
 * @last modify  2013-01-08 09:26:45 by mashanling
 *
 * @param string $keyword          关键字
 * @param array  $keyword_arr      空隔隔开关键字数组
 * @param string $exclude_keywords 排除产品名关键字
 *
 * @return void 无返回值
 */
function here_build_query($keyword, $keyword_arr, $exclude_keywords, $match_mode) {
    $match_mode = 'and' == $match_mode ? ' ' : '|';
    $query = '';
    if ($keyword) {
        foreach ($keyword_arr as $v) {
            //$query .= strlen($v) > 2 ? "*{$v}*" : $v;//长度小于2完全匹配
            $query .= $v . $match_mode;
        }
        if ($query = substr($query, 0, -1)) {
            //$query .= $exclude_keywords;
        }
        else {
            $query  = '过滤关键字后关键字为空';//以防为空
        }
    }
    return $query;
}

/**
 * 将####替换为相应关键字
 *
 * @author       mashanling
 * @date         2012-07-03 15:34:52
 * @last modify  2013-04-20 17:36:38 by mashanling
 *
 * @param string $str     待替换字符串
 * @param string $keyword 关键字
 * @param bool   $strong  true加粗。默认false
 *
 * @return 替换后的字符串
 */
function here_replace_keyword($str, $keyword, $strong = false) {
    $str = str_replace('#####', $strong ? '<strong>' . $keyword . '</strong>' : $keyword, $str);
    return remote_double(str_replace('####', $strong ? '<strong>' . $keyword . '</strong>' : $keyword, $str));
}

/*
 * 去掉两个连续的重复词
 */
function remote_double($keyword) {
    $re   =   '/\b([a-z]+) +\1\b/i';
    return preg_replace($re,'$1',$keyword);

}

/*随机取分类*/
function get_related_cat_keywords($category_arr, $len = 4) {
	$relate_cat_id = array_rand($category_arr,$len);
	$related_cat_keywords = '';
	$related_cat_keywords .= '<ul>';
	foreach($relate_cat_id as $key=>$value) {
		$url = str_replace(array('c-','.html','www.bef.com','a05.everbuying.net'), array('c_','/','www.dmf01.com','www.dealsmachine.com'), $category_arr[$value]['link_url']);
		$related_cat_keywords .= "<li><a href=\"". $url ."\">". $category_arr[$value]['cat_name'] ."</a></li>";
	}
	$related_cat_keywords .= '</ul>';
	return $related_cat_keywords;
}