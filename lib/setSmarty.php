<?
require_once(ROOT_PATH . "lib/smartylibs/Smarty.class.php");
$Tpl =   new Smarty();
$Tpl->template_dir		= SMARTY_TMPL;
$Tpl->compile_dir		= SMARTY_TMPL_C;
$Tpl->cache_dir			= SMARTY_TMPL_CACHE;
//$Tpl->left_delimiter	= '<!--#';
//$Tpl->right_delimiter	= '-->';
//$Tpl->debugging = true;
$Tpl->caching = false;
//$Tpl->caching = empty($_GET['c'])?true:false;        //使用缓存
$Tpl->cache_lifetime = 3600;

//$Tpl->compile_check = true;  //编译检查变量
$Tpl->assign('imgcache_url', IMGCACHE_URL);
$Tpl->assign('jscache_url', JSCACHE_URL);
$Tpl->assign('website_url', WEBSITE);
$Tpl->assign('is_ssl', $is_ssl);
$Tpl->assign('home_url', $home_url);
$Tpl->assign('home_url2', $home_url2);

function smarty_block_dynamic($param, $content, &$smarty) {
 return $content;
}
$Tpl->register_block('dynamic', 'smarty_block_dynamic', false);

/**
 * 在smarty模板里将GMT时间戳格式化为用户自定义时区日期
 * 
 * @param mixed $param 参数
 */
function smarty_local_date($param) {
    extract($param);
    return local_date(empty($format) ? $GLOBALS['_CFG']['time_format'] : $format, empty($time) ? gmtime() : $time);
}
$Tpl->register_function('smarty_local_date', 'smarty_local_date');    //注册格式化本地时间函数
//$my_cache_id = '|';
//(!empty($_GET['id']) && strlen($_GET['id'])<8)?intval($_GET['id']):(!empty($_GET['pro']) && in_array($_GET['pro'],array('new','hot','best'))?trim($_GET['pro']):'new');

?>