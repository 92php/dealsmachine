<?php
define('INI_WEB', true);
require_once('../lib/global.php');
require_once('../lib/time.fun.php');
require(ROOT_PATH . 'languages/en/common.php');
require(ROOT_PATH . 'languages/en/shopping_flow.php');
require(ROOT_PATH . 'languages/en/user.php');

/*
require(ROOT_PATH . 'eload_admin/static_cache_files/payment.php');
write_static_cache('payment', $data, ADMIN_STATIC_CACHE_PATH);
var_dump($data);exit;
*/

$admin_cache_path = ROOT_PATH . 'eload_admin/cache_files/';
$front_cache_path = ROOT_PATH . 'data-cache/';

/*require(ROOT_PATH . FRONT_STATIC_CACHE_PATH . 'fliter_keyword.php');
write_static_cache('fliter_keyword', $fliter_key, FRONT_STATIC_CACHE_PATH);

require(ROOT_PATH . FRONT_STATIC_CACHE_PATH . 'goods2cat.php');
write_static_cache('goods2cat', $goods2cat, FRONT_STATIC_CACHE_PATH);

require(ROOT_PATH . FRONT_STATIC_CACHE_PATH . 'replace_keywords.php');
write_static_cache('replace_keywords', $samilar_key, FRONT_STATIC_CACHE_PATH);

foreach(array('fliter_keyword', 'goods2cat', 'replace_keywords') as $item) {
    var_dump(read_static_cache($item, FRONT_STATIC_CACHE_PATH));
}*/

require(ROOT_PATH . ADMIN_STATIC_CACHE_PATH . 'ship_query.php');
write_static_cache('ship_query', $shipping_web, ADMIN_STATIC_CACHE_PATH);
var_dump(read_static_cache('ship_query', ADMIN_STATIC_CACHE_PATH));
EXIT;

if (is_file($filename = ROOT_PATH . 'eload_admin/cache_files/analyze_record.txt')) {//网站统计分析
    write_static_cache('analyze.log', unserialize(file_get_contents($filename)), 2);
}

//read_static_cache($_ACT, 2) => /eload_admin/index.php OK

//read_static_cache($basename, 2); => /eload_admin/crontab/collection_email.php OK
if (is_file(get_cache_filename('collection_email.log', 2))) {
    $content = file_get_contents($filename);
    write_static_cache('collection_email.log', $content, 2);
}

//read_static_cache($cat_id, $this->_category_hot_searches_path); => lib\seo\class.seo_keywords_to_category.php OK
//执行/eload_admin/crontab/seo_crontab.php?_action=keywordsToCategory

//生成分类价格区间
//生成分类销售前10
//后台生成分类
/*read_static_cache($cur_lang . '_category_c_key',2);
read_static_cache($cur_lang.'_category_c_key',2);
read_static_cache($g_cur_lang.'category_c_key',2);
read_static_cache($info['lang'].'_category_c_key',2);
read_static_cache($lang . '_category_c_key',2);*/

//read_static_cache($filename, 3, $data_path); => eload_admin\repeat_buy_stat.php
foreach(Dir::listFiles($admin_cache_path . 'repeat_buy_stat', '.php') as $item) {
    $item = Dir::convertDS($item);

    require($item);
    cache($item, $data);
}

//read_static_cache($is_login_str,2);
read_static_cache('category_login_html', 2);
read_static_cache('category_html', 2);

//read_static_cache($key, $path); => \lib\smartylibs\plugins\function.category_hot_searches.php

//read_static_cache($keyword_id, $dir); => \lib\seo\class.seo.php

//read_static_cache($log_file, 2); => \lib\seo\class.seo.php
read_static_cache('seo_clear_keywords.log', 2);

//read_static_cache($month, $this->_data_cache_path); => \eload_admin\payment_rate.php

if (is_file($filename = $admin_cache_path . 'payment_rate_data/payment_rate_stat.log')) {
    write_static_cache('payment_rate_stat.log', file_get_contents($filename), $admin_cache_path . 'payment_rate_data/');
}

//read_static_cache($this->_abc_categorynum_key, 2);
read_static_cache('abc_categorynum', 2);

//read_static_cache($this->_in_out_cache_key . 'in', 2);
read_static_cache('new_abc_index_keyword.in', 2);

//read_static_cache($this->_in_out_cache_key . 'out', 2);
read_static_cache('new_abc_index_keyword.out', 2);

//read_static_cache($this->_insert_data_cache_filename, 3, $this->_data_cache_path); => \eload_admin\repeat_buy_stat.php
read_static_cache('insert_data', 3, $admin_cache_path . 'repeat_buy_stat/');

//read_static_cache($this->_letter, $this->_cache_dir); => \eload_admin\abclist_tuijian.php
$range = range('A', 'Z');//A-Z
$range[] = '0-9';//0-9
foreach($range as $k => $v) {
    read_static_cache($v, $admin_cache_path . 'abclist_tuijianci/');
}

//read_static_cache(local_date('Ym', local_strtotime($start_date)) . '.country.shipping.data', $this->_data_cache_path); => \eload_admin\payment_rate.php
foreach(glob($admin_cache_path . 'payment_rate_data/*.php') as $item) {
    read_static_cache(basename($item, '.php'), $admin_cache_path . 'payment_rate_data/');
}

//read_static_cache($this->_log_file, 2); => \lib\seo\class.seo_admin_sitemap.php
read_static_cache('admin_sitemap', 2);

//read_static_cache($this->filename, 2); => \eload_admin\seo_keyword_to_url.php
read_static_cache('seo_keyword_to_url', 2);

read_static_cache($v = 'reset_password', 2);
read_static_cache('admin_share',1);
read_static_cache('area_key',2);
read_static_cache('cat_peijian', 2);
read_static_cache('category_c',2);
read_static_cache('category_c_key',2);
read_static_cache('category_c_key',2);//var_export(getChilds($catArr, 0));
//read_static_cache('category_c_key_'.$lang,2);
read_static_cache('category_children', 2);
read_static_cache('category_goods_num_key',2);
read_static_cache('country_currency', FRONT_STATIC_CACHE_PATH);
read_static_cache('datafeed_filter_sku',1);
read_static_cache('exchange',2);
read_static_cache('gifts_c_key',2);
read_static_cache('goods_grade_arr', 1);
read_static_cache('gsc_attrs', 1);
read_static_cache('land',2);
read_static_cache('lang_country',2);
read_static_cache('mail_template', 2);
read_static_cache('mail_template_goods', 2);
read_static_cache('menu_c',2);
//read_static_cache('old_password_arr.' . $date, 2);
//read_static_cache('old_password_arr.20131121', 2);
read_static_cache('payment', ADMIN_STATIC_CACHE_PATH);
read_static_cache('product_inquiry_type', FRONT_STATIC_CACHE_PATH);
read_static_cache('qinquan.log',2);
read_static_cache('redirect301cat',1);
read_static_cache('redirect301goods',1);
read_static_cache('rma_data',FRONT_STATIC_CACHE_PATH);
read_static_cache('search_attr_template',2);
read_static_cache('search_no_abc',1);
read_static_cache('seo_abc_keyword_to_url', 2);
read_static_cache('seo_keyword_get_info', 2);
read_static_cache('seo_keyword_to_url', 2);
read_static_cache('share_click',1);
read_static_cache('shipping_fee',2);
read_static_cache('shipping_method', ADMIN_STATIC_CACHE_PATH);
read_static_cache('special_arr', 2);
read_static_cache('super_star',1);
read_static_cache('users_grade', ADMIN_STATIC_CACHE_PATH);
read_static_cache('vote_subjects', 2);
//read_static_cache('website_info',2);
read_static_cache('week_goods',1);