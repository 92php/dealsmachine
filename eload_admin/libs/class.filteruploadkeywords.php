<?php
/**
 * class.filteruploadkeyword.php    关键字and查询处理类
 *
 * @author                           mashanling(msl-138@163.com)
 * @date                             2012-11-27 14:17:50
 * @last modify                      2013-01-12 11:21:40 by mashanling
 */
class FilterUploadkeywords extends FilterKeywordResult {
    private $_sphinx, $_filter_cache, $_filter_keywords, $_dan_fu, $_not_search, $_exclude;

    public function __construct() {
        $fliter_key = read_static_cache('fliter_keyword', FRONT_STATIC_CACHE_PATH);
        require(LIB_PATH . 'sphinxapi.php');
        $this->_sphinx      = new SphinxClient();    //实例化sphinx
        $this->_sphinx->SetServer(SPH_HOST, SPH_PORT);    //链接sphinx
        $this->_sphinx->SetMatchMode(SPH_MATCH_EXTENDED2);
        $this->_sphinx->SetLimits(0, 1);
        $this->_filter_cache = read_static_cache($this->cache_key);
        $this->_filter_keywords = $fliter_key;
        $this->_not_search   = $this->_filter_cache['not_search'];
        $this->_exclude      = array();//$this->_filter_cache['exclude'];
        $this->_dan_fu       = $this->_filter_cache['dan_fu'];

    }

    /**
     * 查询
     *
     * @author          mashanling(msl-138@163.com)
     * @date            2013-01-04 10:21:05
     * @last modify     2013-01-12 11:21:36 by mashanling
     *
     * @access public
     *
     * @param string $keyword 关键字
     *
     * @return bool 查询到产品，返回true，否则返回false
     */
    public function queryKeyword($keyword) {
        $keyword         = strtolower($keyword);
        $keyword         = str_replace('-', ' ', $keyword);
        $keyword         = preg_replace('/ +/', ' ', $keyword);    //替换连续空格
        $keyword_arr     = explode(' ', $keyword);
        $exclude_keyword = $this->resetkeywords($keyword_arr, $keyword, $this->_filter_keywords, $this->_not_search, $this->_dan_fu, $this->_exclude);

        if (true === $exclude_keyword) {
            return false;
        }

        $result = $this->_sphinx->Query($this->buildQuery($keyword_arr, $exclude_keyword), SPH_INDEX_MAIN);

        return $result['total_found'];

    }//end query


}