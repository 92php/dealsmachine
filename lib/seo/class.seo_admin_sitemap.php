<?php
/**
 * class.seo_admin_sitemap.php      生成站点地图类
 *
 * @author                          mashanling <msl-138@163.com>
 * @date                            2013-08-10 13:58:27
 * @lastmodify                      $Author: msl $ $Date: 2013-08-29 15:43:17 +0800 (周四, 2013-08-29) $
 */

require_once(ROOT_PATH . 'lib/seo/class.seo.php');
require_once(ROOT_PATH.'eload_admin/libs/cls_google_sitemap.php');
class SEO_Admin_Sitemap extends SEO {
    /**
     * @var bool $_is_mobile 手机站标识
     */
    private $_is_mobile = false;

    /**
     * @var string $_mobile_key 手机站额外key
     */
    private $_mobile_key = '.m';

    /**
     * @var string $_log_file 日志文件
     */
    private $_log_file = 'admin_sitemap';

    /**
     * @var string $_build_log 生成日志
     */
    private $_build_log = '';

    /**
     * @var int $_max_num 每个sitemap文件记录数
     */
    private $_max_num = 10000;

    /**
     * @var int $_pernum 每次查询数
     */
    private $_pernum = 2500;

    /**
     * @var int $_total_keyword 关键字总数
     */
    private $_total_keyword = 0;

    /**
     * @var int $_total_product 商品总数
     */
    private $_total_product = 0;

    /*
     * 生成sitemap首页
     */
    public function _buildMobileIndex(){
        $sitemap_index_head = '<?xml version="1.0" encoding="UTF-8"?>
    			    <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd">'."\n";
        $zip = new ZipArchive();
        $zip->open(ROOT_PATH . 'sitemap/sitemap.all.gz', ZIPARCHIVE::CREATE|ZIPARCHIVE::OVERWRITE);
        $zip->addFile($v = ROOT_PATH . 'sitemap/sitemap-index' . $this->_mobile_key . '.xml.gz', basename($v));
		$body  = '<sitemap><loc>' . MOBILE_URL . 'sitemap/sitemap-index' . $this->_mobile_key . ".xml.gz</loc>\n<mobile:mobile/></sitemap>\n";
		$total_page = ceil($this->_total_product / $this->_max_num);
        for($i = 1 ; $i <= $total_page; $i++){
            $body .= '<sitemap><loc>' . MOBILE_URL . 'sitemap/sitemap-product-' . $i . $this->_mobile_key . ".xml.gz</loc>\n<mobile:mobile/></sitemap>\n";
            $zip->addFile($v = ROOT_PATH  . 'sitemap/sitemap-product-'. $i . $this->_mobile_key . '.xml.gz', basename($v));
        }
        $foot = '</sitemapindex>';
        $sitmap = $sitemap_index_head.$body.$foot;
        file_put_contents($v = ROOT_PATH  . 'sitemap/sitemap' . $this->_mobile_key . '.xml',$sitmap);
        $zip->addFile($v, basename($v));
        clear_cdn_cache('sitemap/sitemap' . $this->_mobile_key . '.xml');    //清缓存
    }


    /**
     * 获取信息
     *
     * @author              mashanling <msl-138@163.com>
     * @date                2013-08-10 14:26:33
     *
     * @param string $key 键
     *
     * @return mixed 如果$key=null,返回整个信息,否则返回$key健值
     */
    private function _getInfo($key = null) {
        $info   = read_static_cache($this->_log_file, 2);
        if (false === $info) {
            $info = array(
                'frequency' => array(
                    'homepage_changefreq'   => 'hourly',
                    'homepage_priority'     => '0.9',
                    'category_changefreq'   => 'hourly',
                    'category_priority'     => '0.8',
                    'content_changefreq'    => 'weekly',
                    'content_priority'      => '0.7',
					'lang'                  => 'en',
                ),
                'build_info'    => '',
            );
            //手机站
            $info['frequency' . $this->_mobile_key] = $info['frequency'];
            $info['build_info' . $this->_mobile_key] = $info['build_info'];
            $this->_setInfo(null, $info);
        }
        if (null === $key) {
            return $info;
        }
        else {
            return $info[$key . ($this->_is_mobile ? $this->_mobile_key : '')];
        }
    }//end _getInfo

    /**
     * 设置信息
     *
     * @author              mashanling <msl-138@163.com>
     * @date                2013-08-10 14:26:33
     *
     * @param string $key  键
     * @param mixed  $data 值
     *
     * @return void 无返回值
     */
    private function _setInfo($key, $data) {
        if ($key) {
            $info = $this->_getInfo();
            $info[$key . ($this->_is_mobile ? $this->_mobile_key : '')] = $data;
        }
        else {
            $info = $data;
        }
        write_static_cache($this->_log_file, $info, 2);
    }

    /**
     * 构造函数
     *
     * @author              mashanling <msl-138@163.com>
     * @date                2013-08-10 14:05:22
     *
     * @param   string  $action 操作
     *
     * @return void 无返回值
     */
    public function __construct($action) {
        parent::__construct();
        $this->_is_mobile = isset($_GET['mobile']);
        $method = $action . 'Action';
        if (method_exists($this, $method)) {
            $this->$method();
        }
        else {
            exit('调用方法不存在');
        }
    }

    /**
     * 生成站点地图首页
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-08-10 14:07:36
     *
     * @return void 无返回值
     */
    public function indexAction() {
        if (!empty($_POST)) {
            $info = $this->_getInfo('frequency');
            foreach($info as $k => $v) {
                if (!empty($_POST[$k])) {
                    $info[$k] = $_POST[$k];
                }
            }
            $this->_setInfo('frequency', $info);
        }
        else {
            global $Arr;
            $Arr['config']  = $this->_getInfo('frequency');;
            $Arr['info'] = $this->_getInfo('build_info');
            $Arr['ur_here'] = 'Sitemap';
            $Arr['arr_changefreq'] =  array(1, 0.9, 0.8, 0.7, 0.6, 0.5, 0.4, 0.3, 0.2, 0.1);
            $Arr['mobile'] = $this->_is_mobile ? 1 : '';
            if ($this->_is_mobile) {
                $Arr['mobile_name'] = '手机';
            }
        }
    }//end indexAction

    public function _build_sitemapAction(){
        $time_start = microtime(true);
		$info = $this->_getInfo('frequency');    //取得设置缓存
        $info['lang'] = 'en'; //暂时不生成多语言  fangxin 2014-05-30 PM
		if($info['lang'] != 'en') {		
			$this->_build_category_sitemap_lang();			
			$this->_build_goods_sitemap_lang();				
			$this->_build_index_lang();			
		} else {
			$this->_build_category_sitemap();
			$this->_build_goods_sitemap();	
			if ($this->_is_mobile) {
				$this->_buildMobileIndex();
			}
			else {
				$this->_build_abc_sitemap();
				$this->_build_index();
			}
			$time_end = microtime(true);
			$this->_build_log .= '生成完毕,用时' . number_format($time_end - $time_start, 6) . ' 秒. ';
	
			if (empty($_SESSION['WebUserInfo']['real_name'])) {
				$this->_build_log .= ' by  定时任务';
				$this->_setInfo('build_info', local_date('Y-m-d H:i:s') . ' by 定时任务');
			}
			else {
				$this->_build_log .=  ' by ' . $_SESSION['WebUserInfo']['real_name'];
				$this->_setInfo('build_info', local_date('Y-m-d H:i:s') . ' by ' . $_SESSION['WebUserInfo']['real_name']);
				admin_log('', '更新了网站地图', '');
			}				
		}
		$this->log($this->_build_log);
    }
    /*
     * 生成abc词sitemap
     */
    public function _build_abc_sitemap(){
        $time_start = microtime(true);
        $sphinx     = $this->_getAbcSphinx();
        $sphinx->SetFilter('is_delete', array(0));
        $sphinx->SetLimits(0, 1, 1);
        $re = $sphinx->query('',$this->_abc_query_index);
        if (empty($re['total_found'])) {
            return;
        }
        $this->_total_keyword      = $re['total_found'];
        $min_id     = 0;
        $total_page = ceil($this->_total_keyword / $this->_max_num);
        $info       = $this->_getInfo('frequency');    //取得设置缓存
        $today      = local_date('Y-m-d');
        $sm         = new google_sitemap();
        $sphinx->SetSortMode(SPH_SORT_EXTENDED, '@id ASC');    //排序
        $sphinx->SetLimits(0, $this->_pernum, $this->_pernum);
        $sphinx->SetArrayResult(true);
        for($i = 1; $i <= $total_page; $i++){
            $smi = new google_sitemap_item(WEBSITE, $today, $info['homepage_changefreq'], $info['homepage_priority']);
            $sm->add_item($smi);
            for($k = 1; $k < 5; $k++){
                $sphinx->ResetFilters();
                $sphinx->SetFilter('is_delete', array(0));
                $sphinx->SetFilterRange('@id', $min_id + 1, 1000000);
                $result = $sphinx->query('', $this->_abc_query_index);    //执行查询
                if(!empty($result['matches'])){
                    foreach($result['matches'] as $v ){
                        $smi = new google_sitemap_item(WEBSITE2 . get_search_url($v['attrs']['keyword']), $today,$info['content_changefreq'], $info['content_priority']);
                        $sm->add_item($smi);
                    }
                    $last_array = array_pop($result['matches']);
                    $min_id     = $last_array['id'];
                }
            }
           $sm_file = ROOT_PATH . 'sitemap/sitemap-keywords-' . $i . '.xml';
           $sm->build($sm_file);
        }
        unset($sm, $smi);
        $time_end = microtime(true);
        $this->_build_log .= 'abc关键字(' . $this->_total_keyword . '),用时' . number_format($time_end - $time_start, 6) . ' 秒. ';
    }

    /*
     * 生成产品sitemap
     */
    public function _build_goods_sitemap(){
        $time_start = microtime(true);
        require_once(ROOT_PATH . 'lib/sphinxapi.php');
        $cl          = new SphinxClient();    //实例化sphinx
        $cl->SetServer(SPH_HOST, SPH_PORT);    //链接sphinx
        $cl->SetLimits(0, 1, 1);
        //$cl->SetSelect('goods_title,url_title,group_goods_id');
        $cl->SetGroupBy('group_goods_id', SPH_GROUPBY_ATTR, 'group_goods_id ASC');    //group_goods_id分组
        $cl->SetFilter('is_delete', array(0));
        $cl->SetFilter('is_on_sale', array(1));
        $cl->SetFilter('goods_number', array(0), true);
        $re = $cl->query('', SPH_INDEX_MAIN);
        if (empty($re['total_found'])) {
            return;
        }
        $this->_total_product = $re['total_found'];
        $total_page     = ceil($this->_total_product / $this->_max_num);
        $info           = $this->_getInfo('frequency');    //取得设置缓存
        $today          = local_date('Y-m-d');
        $min_id         = 0;
        $cl->SetLimits(0, $this->_pernum, $this->_pernum);    //limit
        $cl->SetSortMode(SPH_SORT_EXTENDED, 'group_goods_id ASC');    //排序
        $cl->SetArrayResult(true);
        $sm = new google_sitemap();
        for($i = 1; $i <= $total_page; $i++){
            $smi = new google_sitemap_item($this->_is_mobile ? MOBILE_URL : WEBSITE, $today, $info['homepage_changefreq'], $info['homepage_priority'], $this->_is_mobile);
            $sm->add_item($smi);
            for($k = 1; $k < 5; $k++){
                $cl->ResetFilters();
                $cl->SetFilter('is_delete', array(0));
                $cl->SetFilter('is_on_sale', array(1));
                $cl->SetFilter('goods_number', array(0), true);
                $cl->SetFilterRange('group_goods_id', $min_id + 1, 1000000);
                $result = $cl->query('', SPH_INDEX_MAIN);    //添加关键字查询
                if(!empty($result['matches'])){
                    if ($this->_is_mobile) {
                        foreach($result['matches'] as $row ){
                            $smi = new google_sitemap_item(MOBILE_URL . 'item_detail/' . $row['id'] . '-' . title_to_url($row['attrs']['goods_title']), $today,$info['content_changefreq'], $info['content_priority'], true);
                            $sm->add_item($smi);
                        }
                    }
                    else {
                        foreach($result['matches'] as $row ){
							$smi = new google_sitemap_item(get_details_link($row['id'],$row['attrs']['url_title']), $today,$info['content_changefreq'], $info['content_priority']);
                            $sm->add_item($smi);
                        }
                    }
                    $last_array = array_pop($result['matches']);
                    $min_id     = $last_array['attrs']['group_goods_id'];
                }
            }
            $sm_file = ROOT_PATH . 'sitemap/sitemap-product-' . $i . ($this->_is_mobile ? $this->_mobile_key : '') .'.xml';
            $sm->build($sm_file);
            clear_cdn_cache(str_replace(ROOT_PATH, '', $sm_file) . '.gz');    //清缓存
        }
        unset($sm, $smi);
        $time_end = microtime(true);
        $this->_build_log .= '商品(' . $this->_total_product . '),用时' . number_format($time_end - $time_start, 6) . ' 秒. ';
    }

    /*
     * 生成分类和abc词sitemap
     */
    public function _build_category_sitemap(){
        $time_start = microtime(true);
        $info   = $this->_getInfo('frequency');    //取得设置缓存
        $today  = local_date('Y-m-d');
        $sm     = new google_sitemap();
        $smi    = new google_sitemap_item($this->_is_mobile ? MOBILE_URL : WEBSITE, $today, $info['homepage_changefreq'], $info['homepage_priority'], $this->_is_mobile);
        $sm->add_item($smi);
        $typeArray =  read_static_cache('category_c_key',2);
        foreach ($typeArray as $row) {
            if($row['is_show'] && !$row['is_login']) {
                if ($this->_is_mobile) {
                    $url = MOBILE_URL . 'categories/' . $row['cat_id'] . '-' . title_to_url($row['cat_name']) . '#!1';
                }
                else {
					$url = $row['link_url'];
                }
                $smi = new google_sitemap_item($url, $today,$info['category_changefreq'], $info['category_priority'], $this->_is_mobile);
                $sm->add_item($smi);
            }
        }
        /*
        if (!$this->_is_mobile) {
            //abc索引
            $abcArr = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0-9');
            foreach ($abcArr as $v) {
                $smi = new google_sitemap_item(WEBSITE . 'cheap-product-'.$v.'.html', $today,$info['content_changefreq'], $info['content_priority']);
                $sm->add_item($smi);
            }
        }
        */
        $sm_file = ROOT_PATH . 'sitemap/sitemap-index' . ($this->_is_mobile ? $this->_mobile_key : '') . '.xml';
        $sm->build($sm_file);
        clear_cdn_cache(str_replace(ROOT_PATH, '', $sm_file) . '.gz');    //清缓存
        unset($sm, $smi);
        $time_end = microtime(true);
        $this->_build_log .= '分类及ABC索引,用时' . number_format($time_end - $time_start, 6) . ' 秒. ';
    }
	
    /*
     * 生成sitemap首页
     */
    public function _build_index(){
        $sitemap_index_head = '<?xml version="1.0" encoding="UTF-8"?>
			<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd">'."\n";
        $body  = "<sitemap><loc>".WEBSITE."sitemap/sitemap-index.xml.gz</loc></sitemap>\n";
        $total_page   = ceil($this->_total_product / $this->_max_num);
        for($i=1;$i<=$total_page;$i++) {
            $body .= '<sitemap><loc>'.WEBSITE.'sitemap/sitemap-product-'.$i.".xml.gz</loc></sitemap>\n";
        }
        $total_page   = ceil($this->_total_keyword / $this->_max_num);
        for($i=1;$i<=$total_page;$i++) {
            $body .= '<sitemap><loc>'.WEBSITE.'sitemap/sitemap-keywords-'.$i.".xml.gz</loc></sitemap>\n";
        }
        $foot = '</sitemapindex>';
        $sitmap = $sitemap_index_head.$body.$foot;
        file_put_contents(ROOT_PATH . 'sitemap/sitemap.xml', $sitmap);
        clear_cdn_cache('sitemap/sitemap.xml');    //清缓存
    }
	
    //生成多语言sitemap首页
    public function _build_index_lang(){
		$info           = $this->_getInfo('frequency');    //取得设置缓存
        $sitemap_index_head = '<?xml version="1.0" encoding="UTF-8"?>
			<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd">'."\n";
        $body  = "<sitemap><loc>".WEBSITE."sitemap/sitemap-index-". $info['lang'] .".xml.gz</loc></sitemap>\n";
        $total_page   = ceil($this->_total_product / $this->_max_num);
        for($i=1;$i<=$total_page;$i++) {
            $body .= '<sitemap><loc>'.WEBSITE.'sitemap/sitemap-product-'. $info['lang'] ."-". $i .".xml.gz</loc></sitemap>\n";
        }
        $foot = '</sitemapindex>';
        $sitmap = $sitemap_index_head.$body.$foot;
        file_put_contents(ROOT_PATH . 'sitemap/sitemap-'. $info['lang'] .'.xml', $sitmap);
        clear_cdn_cache('sitemap/sitemap-'. $info['lang'] .'.xml');    //清缓存
    }
		
    //生成多语言分类sitemap
    public function _build_category_sitemap_lang(){
        $time_start = microtime(true);
        $info   = $this->_getInfo('frequency');    //取得设置缓存
        $today  = local_date('Y-m-d');
        $sm     = new google_sitemap();
        $smi    = new google_sitemap_item(WEBSITE, $today, $info['homepage_changefreq'], $info['homepage_priority'], $this->_is_mobile);
        $sm->add_item($smi);
        $typeArray =  read_static_cache($info['lang'].'_category_c_key',2);
        foreach ($typeArray as $row) {
            if($row['is_show'] && !$row['is_login']) {
				$url = $row['link_url'];
                $smi = new google_sitemap_item($url, $today,$info['category_changefreq'], $info['category_priority'], $this->_is_mobile);
                $sm->add_item($smi);
            }
        }
        $sm_file = ROOT_PATH . 'sitemap/sitemap-index-' . $info['lang'] . '.xml';
		$sm->build($sm_file);
        clear_cdn_cache(str_replace(ROOT_PATH, '', $sm_file) . '.gz');    //清缓存
        unset($sm, $smi);
        $time_end = microtime(true);
        $this->_build_log .= '多语言分类索引,用时' . number_format($time_end - $time_start, 6) . ' 秒. ';
    }	
	
    //生成多语言产品sitemap
    public function _build_goods_sitemap_lang(){
        $time_start = microtime(true);
		$info = $this->_getInfo('frequency');    //取得设置缓存
        require_once(ROOT_PATH . 'lib/sphinxapi.php');
        $cl          = new SphinxClient();    //实例化sphinx
        $cl->SetServer(SPH_HOST, SPH_PORT);    //链接sphinx
        $cl->SetLimits(0, 1, 1);
        $cl->SetGroupBy('group_goods_id', SPH_GROUPBY_ATTR, 'group_goods_id ASC');    //group_goods_id分组
        $cl->SetFilter('is_delete', array(0));
        $cl->SetFilter('is_on_sale', array(1));
        $cl->SetFilter('goods_number', array(0), true);	
        $re = $cl->query('', SPH_INDEX_MAIN . '_' . $info['lang']);
        if (empty($re['total_found'])) {
            return;
        }
        $this->_total_product = $re['total_found'];
        $total_page     = ceil($this->_total_product / $this->_max_num);        
        $today          = local_date('Y-m-d');
        $min_id         = 0;
        $cl->SetLimits(0, $this->_pernum, $this->_pernum);    //limit
        $cl->SetSortMode(SPH_SORT_EXTENDED, 'group_goods_id ASC');    //排序
        $cl->SetArrayResult(true);
        $sm = new google_sitemap();
        for($i = 1; $i <= $total_page; $i++){
            $smi = new google_sitemap_item(WEBSITE, $today, $info['homepage_changefreq'], $info['homepage_priority'], $this->_is_mobile);
            $sm->add_item($smi);
            for($k = 1; $k < 5; $k++){
                $cl->ResetFilters();
                $cl->SetFilter('is_delete', array(0));
                $cl->SetFilter('is_on_sale', array(1));
                $cl->SetFilter('goods_number', array(0), true);
                $cl->SetFilterRange('group_goods_id', $min_id + 1, 1000000);
                $result = $cl->query('', SPH_INDEX_MAIN . '_' . $info['lang']);    //添加关键字查询
                if(!empty($result['matches'])){
					foreach($result['matches'] as $row ){
						$smi = new google_sitemap_item(get_details_link_lang($row['id'], $info['lang']), $today,$info['content_changefreq'], $info['content_priority']);
						$sm->add_item($smi);
					}
                    $last_array = array_pop($result['matches']);
                    $min_id     = $last_array['attrs']['group_goods_id'];
                }
            }
            $sm_file = ROOT_PATH . 'sitemap/sitemap-product-'. $info['lang'] .'-'. $i .'.xml';
            $sm->build($sm_file);
            clear_cdn_cache(str_replace(ROOT_PATH, '', $sm_file) . '.gz');    //清缓存
        }
        unset($sm, $smi);
        $time_end = microtime(true);
        $this->_build_log .= '商品(' . $this->_total_product . '),用时' . number_format($time_end - $time_start, 6) . ' 秒. ';
    }
		
}