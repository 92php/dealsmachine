<?php
/**
 * class.seo.php        seo abc词库处理类
 *
 * @author              mashanling <msl-138@163.com>
 * @date                2013-07-15 11:49:41
 * @lastmodify          $Author: lsj $ $Date: 2013-08-29 15:41:34 +0800 (Thu, 29 Aug 2013) $
 */
class SEO {
    /**
     * @var string $_cache_key 关键字过滤信息缓存文件名
     */
    protected $_cache_key = 'filter_search_keywords_cache';

    /**
     * @var string $_abc_categorynum_key 分类关键字显示个数缓存文件名
     */
    protected $_abc_categorynum_key = 'abc_categorynum';

    /**
     * @var string $_abc_cache_path abc相关缓存路径
     */
    protected $_abc_cache_path = null;

    /**
     * @var int $_random_max 随机数,mediumint最大16777215
     */
    protected $_random_max = 16777215;

    /**
     * @var string $_abc_query_index abc查询索引名称
     */
    protected $_abc_query_index = null;

    /**
     * @var string $_sph_host sphinx主机
     */
    //protected $_sph_host = SPH_HOST_ABC;

    /**
     * @var string $_sph_port sphinx端口
     */
    //protected $_sph_port = SPH_PORT_ABC;

    /**
     * @var string $_sph_index_main 主索引名称
     */
   // protected $_sph_index_main = SPH_INDEX_ABC_MAIN;

    /**
     * @var string $_sph_index_delta 增量索引名称
     */
    //protected $_sph_index_delta = SPH_INDEX_ABC_DELTA;

    /**
     * 生成查询关键字
     *
     * @author          mashanling(msl-138@163.com)
     * @date            2013-07-15 17:27:34
     *
     * @param   array       $keyword_arr            关键字数组
     * @param   string      $exclude_keywords       排除关键字
     * @param   string      $match_mode             完全匹配
     *
     * @return string 查询关键字
     */
    protected function _buildQuery($keyword_arr, $exclude_keywords, $match_mode = 'and') {

        if (!$keyword_arr) {
            return '搜索不到结果';
        }

        $match_mode = 'and' == $match_mode ? ' ' : '|';
        $query      = '';

        foreach ($keyword_arr as $v) {
            $v = trim($v);
            //$query .= strlen($v) > 2 ? "*{$v}*" : $v;//长度小于2完全匹配
            $query .= $v;
            $query .= $match_mode;
        }

        return substr($query, 0, -1) . $exclude_keywords;
    }

    /**
     * 获取abc sphinx实例
     *
     * @author          mashanling <msl-138@163.com>
     * @date           2013-07-24 11:14:28
     *
     * @return object   sphinx实例
     */
    protected function _getAbcSphinx() {
        static $cl = null;

        if (null === $cl) {

            require_once(LIB_PATH . 'sphinxapi.php');

            $cl = new SphinxClient();    //实例化sphinx
            $cl->SetServer(SPH_HOST_ABC, SPH_PORT_ABC);    //链接sphinx
            $cl->SetConnectTimeout(2);    //设置超时
            $cl->SetMatchMode(SPH_MATCH_EXTENDED);
            $cl->SetSortMode(SPH_SORT_EXTENDED, '@random');
        }

        return $cl;
    }

    /**
     * 获取数据库实例
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-07-15 17:13:23
     *
     * @return object   数据库实例
     */
    protected function _getDb() {
        static $db = null;

        if (!$db) {
            $db = get_db('abc');
        }

        return $db;
    }

    /**
     * 获取随机数
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-07-24 10:04:43
     *
     * @return int   随机数
     */
    protected function _getRandom() {
        return rand(0, $this->_random_max);
    }

    /**
     * 处理查询关键字，包括过滤，屏蔽，单复数处理，搜索men 排除产品名包含 women等
     *
     * @author              mashanling(msl-138@163.com)
     * @date                2013-07-15 17:28:19
     *
     * @param   array   $keyword_arr      空隔隔开关键字数组
     * @param   string  $keyword          关键字
     * @param   array   $filter_key       屏蔽关键字
     * @param   array   $not_search       不参与搜索关键字
     * @param   array   $dan_fu_arr       单复数
     * @param   array   $exclude          排除搜索关键字
     *
     * @return string 处理后关键字
     */
    protected function _resetKeywords(&$keyword_arr, $keyword, $filter_key, $not_search, $dan_fu_arr, $exclude) {
        $keyword        = strtolower($keyword);
        $keyword_arr    = array_map('strtolower', $keyword_arr);

        foreach ($filter_key as $item) {//屏蔽

            if (preg_match('-\b' . preg_quote($item, '-') . '\b-', $keyword)) {    //包含屏蔽关键字，搜索不到结果
                return true;
            }
        }

        $exclude_keywords = '!(';

        foreach ($keyword_arr as $key => $item) {

            if(in_array($item, $not_search)){//去除不要搜索的词
                unset($keyword_arr[$key]);
            }

//            if (isset($dan_fu_arr[$item])) {//复数，搜索单数
//                $keyword_arr[$key] = $dan_fu_arr[$item];
//            }

            if ($exclude && isset($exclude[$item])) {//排除处理，直接有对应排除关系
                $loop = $exclude[$item];
            }
//            elseif (isset($dan_fu_arr[$item]) && $exclude && isset($exclude[$dan_fu_arr[$item]])) {//复数处理成单数后
//                $loop = $exclude[$dan_fu_arr[$item]];
//            }

            if(isset($loop)){//排除，如搜索 men 排除 产品名包含 women 产品

                foreach($loop as $v) {
                    $exclude_keywords .= $v . '|';
                }

            }
        }

        $exclude_keywords = substr($exclude_keywords, 0, -1);

        if(strlen($exclude_keywords) > 2){
            $exclude_keywords  = ' @goods_title ' . $exclude_keywords;
            $exclude_keywords .= ')';
        }
        else {
            $exclude_keywords = '';
        }

        return $exclude_keywords;
    }//end _resetKeywords

    /**
     * 更新索引is_delete属性为1
     *
     * @author              mashanling <msl-138@163.com>
     * @date                2013-07-24 10:51:39
     *
     * @keyword_id  int|array $keyword_id
     *
     * @return void 无返回值
     */
    protected function _updateAttributeDeleted($keyword_id) {

        if (!$keyword_id) {
            return;
        }

        require_once(LIB_PATH . 'sphinxapi.php');

        $keyword_id = is_array($keyword_id) ? $keyword_id : explode(',', $keyword_id);
        $attr       = array();

        foreach($keyword_id as $item) {
            $attr[$item] = array(1);
        }

        $cl = $this->_getAbcSphinx();

        if (-1 == $cl->UpdateAttributes(SPH_INDEX_ABC_MAIN, array('is_delete'), $attr)) {
            $_GET['_action'] = 'UpdateAttributes.error';
            $this->log(__METHOD__ . $cl->GetLastError());
        }
    }

    /**
     * 构造函数
     *
     * @author              mashanling <msl-138@163.com>
     * @date                2013-07-15 11:34:25
     *
     * @return void 无返回值
     */
    public function __construct() {
        $this->_abc_cache_path = ROOT_PATH . 'data-cache/abc_keywords/';
        $this->_abc_query_index = SPH_INDEX_ABC_MAIN . ',' . SPH_INDEX_ABC_DELTA;
    }

    /**
     * 魔术方法__get,获取属性值
     *
     * @author              mashanling <msl-138@163.com>
     * @date                2013-07-19 15:05:08
     *
     * @param   string  $property 属性
     *
     * @return mixed 属性值
     */
    public function __get($property) {
        return property_exists($this, $property) ? $this->$property : null;
    }

    /**
     * 删除abc相关词缓存
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-02-26 14:03:59
     *
     * @param int|array     $keyword_id         关键字id
     * @param bool          $delete_keyword     true删除关键字,默认true
     *
     * @return void 无返回值
     */
    public function deleteAbcRelativeCache($keyword_id, $delete_keyword = true) {

        if (is_array($keyword_id)) {
            $keyword_id_arr = $keyword_id;
            $keyword_id     = join(',', $keyword_id);
        }
        else {
            $keyword_id_arr = explode(',', $keyword_id);
        }

        foreach($keyword_id_arr as $v) {
            $dir = $this->getSubNDir($v);

            //is_file($f = $dir . $v . '.php') && unlink($f);

            write_static_cache($v, null, $dir);
        }

        if ($delete_keyword) {
            $db     = $this->_getDb();
            $where  = "IN({$keyword_id})";
            $db->delete(ABCKEYWORD_NEW2, 'keyword_id ' . $where);
            $db->delete(ABCKEYWORD_RELATIVE2, 'keyword_id ' . $where);
            $db->delete(ABCKEYWORD_RELATIVE2, 'relative_id ' . $where);
        }
    }//end deleteAbcRelativeCache

    /**
     * 获取abc相关词缓存
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-02-26 14:03:59
     *
     * @param int|array $keyword_id 关键字id
     *
     * @return void 无返回值
     */
    public function getAbcRelativeCache($keyword_id) {
        $dir = $this->getSubNDir($keyword_id);

        return read_static_cache($keyword_id, $dir);
    }

    /**
     * 取字符串前N位作为目录
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-02-26 13:57:16
     *
     * @param string    $str    待取字符串
     * @param int       $n      N位,默认3
     * @param string    $base   基路径。默认null=$this->_abc_cache_path
     *
     * @return string 路径
     */
    public function getSubNDir($str, $n = 3, $base = null) {
        $dir  = null === $base ? $this->_abc_cache_path : $base;
        $dir .= strlen($str) > $n ? substr($str, 0, $n) : $str;
        $dir .= '/';

        !is_dir($dir) && mkdir($dir, 0755, true);

        return $dir;
    }

    /**
     * 写日志
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-07-15 11:39:08
     *
     * @param   string      $log            日志内容
     * @param   float       $time_start     开始时间.默认false
     * @param   string      $log_file       日志文件.默认null=basename($_SERVER['SCRIPT_NAME'])
     *
     * @return void 无返回值
     */
    public function log($log, $time_start = false, $log_file = null) {

        if (function_exists('e_log')) {

            if (null === $log_file) {
                $log_file = isset($_GET['_action']) ? 'seo_' . $_GET['_action'] : basename($_SERVER['SCRIPT_NAME']);
            }

            e_log($log, $log_file, $time_start);
        }
    }

    /**
     * 设置相关词
     *
     * @author          mashanling(msl-138@163.com)
     * @date            2013-07-17 16:27:26
     *
     * @param   array   keyword_id_arr     关键字数组
     *
     * @return array 相关词缓存
     */
    public function setRelativeKeywords($keyword_id_arr) {

        if (is_numeric($keyword_id_arr)) {
            $keyword_id_arr = $this->_getDb()->arrQuery('SELECT keyword_id,cat_id FROM ' . ABCKEYWORD_NEW2 . ' WHERE keyword_id=' . $keyword_id_arr, null, 'keyword_id');
        }

        if (!$keyword_id_arr) {
            return array();
        }

        $db     = $this->_getDb();
        $sphinx = $this->_getAbcSphinx();
        $sphinx->SetLimits(0, 8);
        $sphinx->SetSelect('is_delete');
        $values     = array();

        foreach($keyword_id_arr as $keyword_id => $cat_id) {//循环所有关键字
            $sphinx->ResetFilters();
            $sphinx->SetFilter('cat_id', array(intval($cat_id)));
            $sphinx->SetFilter('@id', array($keyword_id), true);
            $result = $sphinx->Query('', $this->_abc_query_index);

            if(isset($result['matches'])) {

                foreach($result['matches'] as $k => $v) {
                    $values[] = "({$keyword_id},{$k})";
                }
            }
        }

        $keyword_id = join(',', array_keys($keyword_id_arr));

        $db->delete(ABCKEYWORD_RELATIVE2, "keyword_id IN({$keyword_id})");
        $values && $db->query('INSERT INTO ' . ABCKEYWORD_RELATIVE2 . '(keyword_id,relative_id) VALUES' . join(',', $values));
        $this->deleteAbcRelativeCache($keyword_id, false);

        return $this->setAbcRelativeCache($keyword_id);
    }//end setRelativeKeywords

    /**
     * 设置abc相关词缓存
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-02-26 14:03:59
     *
     * @param int|array $keyword_id 关键字id
     *
     * @return array 相关词缓存
     */
    public function setAbcRelativeCache($keyword_id) {
        $db         = $this->_getDb();
        $keyword_id = is_array($keyword_id) ? $keyword_id  : explode(',', $keyword_id);
        $data       = array();

        foreach($keyword_id as $v) {
            $data       = array();
            $db->query('SELECT a.keyword FROM ' . ABCKEYWORD_NEW2 . ' AS a JOIN ' . ABCKEYWORD_RELATIVE2 . ' AS b ON a.keyword_id=b.relative_id WHERE b.keyword_id=' . $v);

            while($row = $db->fetchArray()) {
                $data[] = '<li><a href="' . get_search_url($row['keyword']) . '">' . $row['keyword'] . '</a></li>';
            }

            write_static_cache($v, $data, $this->getSubNDir($v));
        }

        return $data;
    }
}