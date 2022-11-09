<?php
//字符配置
$ar		= array();
//$ar[]	= explode("/",getcwd());      // Linux system
//$ar[] = explode("\\",getcwd());      // Window system

$path = getcwd();
$temp_path_Arr = pathinfo($path);
$root_path_Arr = pathinfo(ROOT_PATH);
$path = $temp_path_Arr['basename'];
$path = ($root_path_Arr['basename'] == $path)?'':$path .= '/';

//define('COOKIESDIAMON','www.ahf05.com');
define('COOKIESDIAMON','www.dealsmachine_v.com');
define('FRONT_DATA_CACHE_PATH'       , ROOT_PATH . 'data-cache/');    //前台缓存路径
define('CATEGORY_DATA_CACHE_PATH'    , ROOT_PATH . 'data-cache/category_data_cache/');    //分类数据缓存路
define('ADMIN_PATH'                  , ROOT_PATH . 'eload_admin/');    //后台路径
define('LIB_PATH'                    , ROOT_PATH . 'lib/');            //fun路径
define('FUN_PATH'                    , ROOT_PATH . 'fun/');            //lib路径
define('CRONTAB_LOG_PATH'            , ADMIN_PATH . 'crontab/log/');   //定时任务日志路径

//exit();
$_MODEL = array('index','users','search','category','flow','goods','article','comment','sitemap','page_not_found','abcindex','price_list','price_details','dalei','inquiry','promotion','cemails','gallery','special_offer'); //模块
$_ATION = array('index','join','add','del','mod','sign','adr','check_email','a_join','logout','act_sign','add_to_cart','cart','drop_goods','update_cart','consignee','checkout','drop_to_collect','done','profile','edit_profile','edit_password','address_list','drop_consignee','edit_address','collection_list','delete_collection','order_list','order_detail','comment_list','message_list','act_add_message','del_msg','del_cmt','fails','search','features','products','wholesalers','payok','queryorder','advanced_search','send_pwd_email','email_list','exp_checkout','unsubmail','apply_code','Is_Apply'); //操作


$is_ssl = false;
$home_url = '';
$home_url2 = '';

//define('CDN_API_PATH','http://a.faout.com/CDNPURGE/clear_cdn_api.php');		//CDN缓存清除接口地址
//define('CDN_CLEAR_URL_PATH','http://www.everbuying.tv');		//CDN缓存清除接口地址

define('WEBSITE'                     , "http://{$_SERVER['HTTP_HOST']}/");     //网站地址

define('IMGCACHE_URL'                ,'/temp/' .SKIN );     //css,images路径
//define('IMGCACHE_URL'                ,  'http://cloud4.faout.com/imagecache/A/');     //css,images路径
//define('JSCACHE_URL'                 , IMGCACHE_URL);     //js路径
define('JSCACHE_URL'                 , '/');     //js路径

//define('IMGCACHE_PATH'               , 'http://cloud.faout.com/');     //cdn图片路径
//define('IMG_API_PATH'                , 'http://www.faout.com/code/syn_img_opt.php');     //中转服务器地址
define('WEBSITE2'                    , "http://{$_SERVER['HTTP_HOST']}");     //网站地址
define('MAIN_DOMAIN'                 , 'www.dealsmachine_v.com');		   //域名
define('DOMAIN_IMG'                 , 'www.dealsmachine_v.com');		   //域名

define('FRONT_STATIC_CACHE_PATH'     , 'static_data-cache/');   //前台静态缓存文件路径
define('ADMIN_STATIC_CACHE_PATH'     , 'eload_admin/static_cache_files/');   //后台静态缓存文件路径