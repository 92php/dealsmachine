<?php
/**
 * class.seo_front_abc_list.php     前台abc处理类
 *
 * @author                          mashanling <msl-138@163.com>
 * @date                            2013-07-24 11:46:55
 * @lastmodify                      $Author: msl $ $Date: 2013-08-06 15:34:11 +0800 (Tue, 06 Aug 2013) $
 */

require_once(ROOT_PATH . 'lib/seo/class.seo.php');

class SEO_Front_Abc_List extends SEO {
    /**
     * @var int $_out_max 最大导出关键字个数
     */
    private $_out_max = 5000;

     /**
     * @var string $_in_out_cache_key 最大导出关键字个数
     */
    private $_in_out_cache_key = 'new_abc_index_keyword';

    /**
     * 获取sphinx filter
     *
     * @author              mashanling <msl-138@163.com>
     * @date                2013-07-25 10:42:36
     *
     * @return array filter过滤数组array('cat_id' => 分类id, 'word_count' => 单词数, 'num' => 读取个数)
     */
    private function _getFilter() {
        $cache  = read_static_cache($this->_abc_categorynum_key, 2);
       // print_r($cache);
        $filter = array();

        foreach($cache as $cat_id => $num_arr) {

            if ($num_arr) {

                foreach($num_arr as $k => $v) {

                    if ($v > 0) {
                        $filter[] = array('cat_id' => $cat_id, 'word_count' => $k + 1, 'num' => $v > 1000 ? 1000 : $v);
                    }
                }
            }
        }

        return $filter;
    }

    /**
     * 列表
     *
     * @author              mashanling <msl-138@163.com>
     * @date                2013-07-24 11:52:20
     *
     * @param   string  $k  关键字首字母
     *
     * @return void 无返回值
     */
    public function listAction($k) {
        global $Arr;

        $filter = $this->_getFilter();
        $sphinx = $this->_getAbcSphinx();

        $Arr['k'] = $k;

        if ('0-9' == $k) {//0-9打头
            $k  = join('|', range(0, 9));
        }

        $page_size  = 88;
        $result     = array();
		if(!empty($k)) {
        	$query      = '@first_letter ' . $k;
		} else {
			$query = '';
		}
        $i          = 0;
        $chunk      = array_chunk($filter, 30, true);//最多支持32个查询,30个一批

        //测试
        /*$a = microtime(true);
        $result = $sphinx->Query('ABC', SPH_INDEX_MAIN);

        if (isset($result['matches'])) {
            var_dump($result['matches']);
            unset($result['matches']);
        }

        var_dump($result, $sphinx->GetLastError());

        exit;*/

        //print_r($chunk);

        foreach($chunk as $item) {

            foreach($item as $v) {
                $num        = intval($v['num']);
                //$page_size += $num;
                $sphinx->SetFilter('top_cat_id', array(intval($v['cat_id'])));
                $sphinx->SetFilter('word_count', array(intval($v['word_count'])));
                $sphinx->SetLimits(0, $num);
                $sphinx->AddQuery($query, $this->_abc_query_index);
                $sphinx->ResetFilters();
            }

            $result[] = $sphinx->RunQueries();
            //print_r( $result);exit;
        }
        //var_dump(microtime(true) - $a);//测试
        $keyword_arr = array();
        foreach($result as $item) {
            foreach($item as $v) {
                if (!empty($v['matches'])) {
                    foreach ($v['matches'] as $info) {
                        $keyword_arr[] = ucwords($info['attrs']['keyword']);
                    }
                }
            }
        }
        if (count($keyword_arr) > $page_size) {
            shuffle($keyword_arr);
            $keyword_arr = array_slice($keyword_arr, 0, $page_size);
        }
        $Arr['keyword_arr'] = $keyword_arr;
        $result = $sphinx->Query($query, $this->_abc_query_index);
        if ($result && $result['total_found']) {
            $_GET['is_new_abc'] = true;//分页调到
            $page = new page(array('total' => $result['total_found'], 'perpage' => $page_size, 'pagebarnum' => 10));
            $Arr['pagestr'] = $page->show('new_abc');
        }
    }//end listAction
}