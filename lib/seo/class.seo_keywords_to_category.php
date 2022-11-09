<?php
/**
 * class.seo_keywords_to_category.php       将符合条件的关键字加入分类popular searches
 *
 * @author                                  mashanling <msl-138@163.com>
 * @date                                    2013-07-18 15:04:12
 * @lastmodify                              $Author: msl $ $Date: 2013-08-20 14:31:03 +0800 (Tue, 20 Aug 2013) $
 */

require_once(ROOT_PATH . 'lib/seo/class.seo.php');

class SEO_Keywords_To_Category extends SEO {
    /**
     * 构造函数
     *
     * @author              mashanling <msl-138@163.com>
     * @date                2013-07-19 15:00:04
     *
     * @return void 无返回值
     */
    public function __construct() {
        parent::__construct();
        $this->_category_hot_searches_path = $this->_abc_cache_path . 'category_hot_searches/';
    }

    /**
     * 缓存
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2013-07-19 15:15:19
     *
     * @param int|array   $cat_id 分类id串
     * @param array     $data   缓存数组
     *
     * @return mixed 如果$data=null,返回cat_id IN($cat_id)的关键字数组,否则null
     */
    public function cache($cat_id = null, $data = null) {

        if (is_array($data)) {
            write_static_cache($cat_id, $data, $this->_category_hot_searches_path);
        }
        else {
            $where = ' WHERE keyword_length BETWEEN 6 AND 25 AND word_count BETWEEN 1 AND 3 AND goods_num >=3';

            if (null === $cat_id) {//全部缓存,定时任务调用
                $sql = 'SELECT GROUP_CONCAT(keyword) AS keyword,cat_id FROM ' . ABCKEYWORD_NEW2 . $where . ' GROUP BY cat_id';
            }
            else {//指定分类,页面程序调用
                $where .= " AND cat_id IN({$cat_id})";
                $sql = 'SELECT keyword,cat_id FROM ' . ABCKEYWORD_NEW2 . $where;
            }

            $db     = $this->_getDb();
            $db->query($sql);

            if (null === $cat_id) {

                //memcache不支持查询key,若需要,可查询mysql再delete by mashanling on 2014-06-09 10:24:57
                //foreach(glob($this->_category_hot_searches_path . '*[0-9].php') as $item) {
                    //unlink($item);
                //}

                while($row = $db->fetchArray()) {
                    write_static_cache($row['cat_id'], array_filter(explode(',', $row['keyword'])), $this->_category_hot_searches_path);
                }
            }
            else {
                $data = array();

                while($row = $db->fetchArray()) {
                    $data[] = $row['keyword'];
                }

                return $data;
            }
        }
    }//end cache

    /**
     * 继承获取父类热门搜索关键字
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2013-07-19 10:24:31
     *
     * @param int   $cat_id 分类id
     * @param int   $num    个数,默认null=10
     * @param array $data   关键字数组
     *
     * @return array 关键字数组
     */
    public function getCategoryHotSearches($cat_id, $num = null, $data = array()) {

        $cat_arr    = read_static_cache('category_c_key', 2);

        if (!isset($cat_arr[$cat_id])) {
            return '';
        }

        $num        = $num ? $num : 10;
        $cat_info   = $cat_arr[$cat_id];
        $cache      = read_static_cache($cat_id, $this->_category_hot_searches_path);
        $return     = array();

        if ($cache) {

            $count  = count($cache);

            if ($count > $num) {
                shuffle($cache);
                $cache = array_slice($cache, 0, $num);
            }

            foreach($cache as $keyword) {
                $data[] = $keyword;
            }

            if ($count < $num && $cat_info['parent_id']) {//不足,父级补充
                $data = $this->getCategoryHotSearches($cat_info['parent_id'], $num - $count, $data);

            }
        }
        elseif($cat_info['parent_id']) {
            $data = $this->getCategoryHotSearches($cat_info['parent_id'], $num, $data);
        }
        else {//一级类
            $cache = $this->cache(new_get_children($cat_id));

            if ($cache) {
                $count  = count($cache);

                if ($count > $num) {
                    shuffle($cache);
                    $cache = array_slice($cache, 0, $num);
                }

                foreach($cache as $keyword) {
                    $data[] = $keyword;
                }
            }
        }

        return $data;
    }//end getCategoryHotSearches
}