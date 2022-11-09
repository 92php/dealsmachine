<?php
/**
 * class.seo_filter_upload_keywords.php     关键字and查询处理类
 *
 * @author                                  mashanling(msl-138@163.com)
 * @date                                    2013-07-15 17:29:52
 * @lastmodify                              $Author: lsj $ $Date: 2013-08-29 15:41:34 +0800 (Thu, 29 Aug 2013) $
 */

require_once(ROOT_PATH . 'lib/seo/class.seo.php');

class SEO_Filter_Upload_Keywords extends SEO {
    /**
     * @var array $_filter_cache 关键字过滤信息,默认null
     */
    private $_filter_cache = null;

    /**
     * @var array $_filter_keywords 屏蔽关键词,默认null
     */
    private $_filter_keywords = null;

    /**
     * @var array $_dan_fu,单复数,默认null
     */
    private $_dan_fu = null;

    /**
     * @var array $_not_search,不参与搜索,默认null
     */
    private $_not_search = null;

    /**
     * @var array $_exclude,扫除搜索,默认null
     */
    private $_exclude = null;

    /**
     * @var object $_sphinx sphinx实例,默认null
     */
    protected $_sphinx = null;


    /**
     * 查询
     *
     * @author          mashanling(msl-138@163.com)
     * @date            2013-07-15 17:30:23
     *
     * @param string $keyword 关键字
     *
     * @return      int|false   如果未包含屏蔽词,返回查询结果数,否则返回false
     */
    protected function _queryKeyword($keyword) {
        $keyword         = str_replace('-', ' ', $keyword);
        $keyword         = preg_replace('/ +/', ' ', $keyword);    //替换连续空格
        $keyword_arr     = explode(' ', $keyword);
        $exclude_keyword = $this->_resetkeywords($keyword_arr, $keyword, $this->_filter_keywords, $this->_not_search, $this->_dan_fu, $this->_exclude);

        if (true === $exclude_keyword) {
            return false;
        }
        $result = $this->_sphinx->Query($this->_buildQuery($keyword_arr, $exclude_keyword), SPH_INDEX_MAIN);


        return $result['total_found'];

    }//end queryKeyword

    /**
     * 构造函数
     *
     * @author              mashanling <msl-138@163.com>
     * @date                2013-07-15 17:37:58
     *
     * @return void 无返回值
     */
    public function __construct() {
        $fliter_key = read_static_cache('fliter_keyword', FRONT_STATIC_CACHE_PATH);
        require_once(LIB_PATH . 'sphinxapi.php');
        $this->_sphinx      = new SphinxClient();    //实例化sphinx
        $this->_sphinx->SetServer(SPH_HOST, SPH_PORT);    //链接sphinx
        $this->_sphinx->SetMatchMode(SPH_MATCH_EXTENDED2);
        $this->_sphinx->SetLimits(0, 1, SPH_MAX_MATCHES);
        $this->_sphinx->SetFilter('is_delete', array(0));//删除
        $this->_filter_cache = read_static_cache($this->_cache_key);
        $this->_filter_keywords = $fliter_key;
        $this->_not_search   = $this->_filter_cache['not_search'];
        $this->_exclude      = array();//$this->_filter_cache['exclude'];
        $this->_dan_fu       = $this->_filter_cache['dan_fu'];
        parent::__construct();
    }

    /**
     * 将用户搜索的满足条件的关键字入库
     *
     * @author          mashanling(msl-138@163.com)
     * @date            2013-07-18 14:00:29
     *
     * @param string $keyword 关键字
     *
     * @return  void    无返回值
     */
    public function insertUserKeyword($keyword) {
        $db         = $this->_getDb();
		$keyword    = trim($keyword);
        $keyword    = stripslashes($keyword);//google抓取，竟然没有addslashes
        $t          = strtolower($keyword);
		$t          = preg_replace("/\s/", " ", $t);
        //搜索词包含下面的词就不保存到词库
        $search_no_abc = read_static_cache('search_no_abc',1);

        if(!empty($search_no_abc)){
            foreach(explode(' ',$t) as $row){
                if(in_array($row,$search_no_abc)){

                    return ;
                }
            }
        }

        if (!$db->getOne('SELECT keyword_id FROM ' . ABCKEYWORD_NEW2 . " WHERE `keyword`='" . addslashes($keyword) . "' LIMIT 1")) {

            if (false === strpos($keyword, ' ') && $GLOBALS['db']->count_info(GOODS, 'goods_id', "`goods_sn`='" . addslashes($keyword) . "'")) {
                return;
            }

            $cat_arr    = read_static_cache('category_c_key', 2);

            foreach($cat_arr as $item) {

                if (strtolower($item['cat_name']) == $t) {
                    return;
                }
            }

            if ($result = $this->upload($keyword, true)) {
                //$result['keyword'] = addslashes(ucwords($keyword));
                $result['keyword'] = addslashes(strtolower($keyword));
                $result['keyword_length'] = strlen($keyword);
                $result['goods_num'] = $result['total'];
                $result['is_user_search'] = 1;
                $result['word_count'] = substr_count($keyword, ' ') + 1;
                //$result['random'] = $this->_getRandom();
                $result['web_title'] = '##### - Best ##### Deals | dealsmachine.com';
                $result['meta_keyword'] = '#####, best #####, dealsmachine.com';
                $result['meta_description'] = 'dealsmachine.com offers best ##### at great prices, browse ##### deals and save big!';
                $result['last_update_time'] = gmtime();
                unset($result['total']);

                if ($db->autoExecute(ABCKEYWORD_NEW2, $result)) {
                    $this->setRelativeKeywords(array($db->insertId() => $result['cat_id']));
                }
            }
        }
    }//end insertUserKeyword

    /**
     * 上传关键字
     *
     * @author          mashanling(msl-138@163.com)
     * @date            2013-07-17 14:35:48
     *
     * @param string $keyword 关键字
     * @param bool   $check_length true检测关键字长度,默认false
     * @param string $match_mode   匹配方式,默认and,完全匹配
     *
     * @return      mixed    false无结果，否则数组array('total' => total, 'cat_id' => $cat_id)
     */
    public function upload($keyword, $check_length = false, $match_mode = 'and') {

    	//$keyword = trim($keyword);
        if ($check_length) {
            $len = strlen($keyword);

            if ($len < 6 || $len > 40) {
                return false;
            }
        }


          /**
     	* 设置过滤不符合规则的关键词
     	*
     	* @author          jim 2013-9-9
     	* @date            2013-02-26 14:03:59
     	* */

        $filter_search_keywords_cache = read_static_cache('filter_search_keywords_cache');
        $no_this_start = $filter_search_keywords_cache['no_this_start'];
        $no_this_end = $filter_search_keywords_cache['no_this_end'];
		$keyword = strtolower(trim($keyword));
        if(!empty($no_this_start)){
            foreach($no_this_start as $v){
            	//if (preg_match("/\b$v/i", $keyword)&& strpos($keyword, 'a line') ===false){ //找到以禁止词开始的，不入库,a line 除外
            	if (0 === strpos($keyword, $v . ' ') && 0 !== strpos($keyword, 'a line')) {
            		//echo $keyword.'m1'."<br>";
            		return false;
            	}

            }

        }

        if (!preg_match('/^[a-z0-9]/i', $keyword)) { //以非字母开始，不入库
           // echo "+$keyword+".'m2'."<br>";
          	return false;
        }
        if (!preg_match('/[a-z0-9]$/i', $keyword)) { //以非字母结束，不入库
            //echo "+$keyword+".'m3'."<br>";
          	return false;
        }
        if (!preg_match('/^[\w\s\-\.\'"]+$/', $keyword)){ //含有非指定字符的，不入库
            //echo $keyword.'m4'."<br>";
            return false;
        }


        if(!empty($no_this_end)){  //找到以禁止词结束，不入库
            foreach($no_this_end as $v){
            	if (preg_match("/\b$v$/i", $keyword)){ //找到以禁止词开始的，不入库,a line 除外
            		return false;
            	}
            }

        }

		$cat_arr  = read_static_cache('category_c_key', 2);    //所有分类

        foreach ($cat_arr as $v){
        	$cat_name = strtolower($v['cat_name']);
        	if($keyword == $cat_name
        	||$keyword == 'cheap '.$cat_name
        	||$keyword == 'best '.$cat_name){
        		//echo 'm5';
        		return false;
        	}
        }


        static $first = null;

        if (null === $first) {
            //$this->_sphinx->SetSortMode(SPH_SORT_EXTENDED, 'goods_number DESC,@weight DESC,week2sale DESC');
            $this->_sphinx->SetGroupBy('group_goods_id', SPH_GROUPBY_ATTR, 'goods_number DESC,@weight DESC,week2sale DESC');
            $this->_sphinx->SetArrayResult(true);
            $this->_sphinx->SetSelect('cat_id');
        }

        $keyword_arr    = explode(' ', $keyword);
        $keyword_arr    = array_filter($keyword_arr);

        if (!$keyword_arr) {
            return false;
        }

        $exclude_keyword    = $this->_resetkeywords($keyword_arr, $keyword, $this->_filter_keywords, $this->_not_search, $this->_dan_fu, $this->_exclude);

        if (true === $exclude_keyword) {
           return false;
        }

        $keyword    = $this->_buildQuery($keyword_arr, $exclude_keyword, $match_mode);
        $query      = $this->_sphinx->EscapeString($keyword);

        if ('and' != $match_mode) {
            $query = str_replace('\|', '|', $query);
        }

        $query = keyword_Singular_plural($query); //单复数以及近义词

        $result     = $this->_sphinx->Query($query, SPH_INDEX_MAIN);

        if (false === $result) {
            return 0;
        }
        elseif (empty($result['matches'])) {
            return false;
        }

        $total  = $result['total_found'];
        $this->_sphinx->SetGroupBy('cat_id', SPH_GROUPBY_ATTR, '@count DESC');
        $result = $this->_sphinx->Query($query, SPH_INDEX_MAIN);

        if (false === $result) {
            return 0;
        }

        /*foreach($result['matches'] as $goods_id => $item) {//测试
            $result['matches'][$goods_id] = $item['attrs']['@groupby'] . ',' . $item['attrs']['@count'];
        }*/

        $cat_id     = $result['matches'][0]['attrs']['cat_id'];
        $cat_arr    = read_static_cache('category_c_key', 2);

        if (isset($cat_arr[$cat_id])) {
            $node_arr   = explode(',', $cat_arr[$cat_id]['node']);
            $top_cat_id = $node_arr[0];
        }
        else {
            $top_cat_id = 0;
        }

        $result     = array('total' => $total, 'cat_id' => $cat_id, 'top_cat_id' => $top_cat_id);

        //var_dump($result);exit;

        return $result;
    }//end upload

}