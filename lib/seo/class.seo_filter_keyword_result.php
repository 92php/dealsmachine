<?php
/**
 * class.seo_filter_keyword_result.php      后台关键字and查询处理类
 *
 * @author                                  mashanling <msl-138@163.com>
 * @date                                    2013-07-15 17:26:23
 * @lastmodify                              $Author: msl $ $Date: 2013-08-09 14:23:19 +0800 (Fri, 09 Aug 2013) $
 */

require_once(ROOT_PATH . 'lib/seo/class.seo.php');

class SEO_Filter_Keyword_Result extends SEO {
    /**
     * 查询
     *
     * @author          mashanling(msl-138@163.com)
     * @date            2013-07-15 17:28:51
     *
     * @return void 无返回值
     */
    public function query() {
        $time_start  = microtime(true);
        $keywords    = isset($_POST['keywords']) ? strtolower(stripslashes(trim($_POST['keywords']))) : '';//关键字

        !$keywords && exit('关键字为空');

        $keywords    = str_replace('-', ' ', $keywords);
        $keywords    = preg_replace('/ +/', ' ', $keywords);    //替换连续空格
        $keyword_arr = explode("\n", $keywords);
        $not_search  = isset($_POST['not_search']) ? strtolower(stripslashes(trim($_POST['not_search']))) : '';//不参与搜索
        $not_search  = strpos($not_search, ' ') !== false ? preg_replace('/ +/', '', $not_search) : $not_search;

        $dan_fu      = isset($_POST['dan_fu']) ? strtolower(stripslashes(trim($_POST['dan_fu']))) : '';//单复数
        $dan_fu      = strpos($dan_fu, ' ') !== false ? preg_replace('/ +/', '', $dan_fu) : $dan_fu;

        $exclude     = isset($_POST['exclude']) ? strtolower(stripslashes(trim($_POST['exclude']))) : '';//排除
        $exclude     = strpos($exclude, ' ') !== false ? preg_replace('/ +/', '', $exclude) : $exclude;
        $cache       = read_static_cache($this->_cache_key);
        $not_search  = $not_search ? explode(',', $not_search) : ($cache['not_search'] ? $cache['not_search'] : array());
        $exclude     = $exclude ? $this->explodeExclude($exclude) : $cache['exclude'];
        $dan_fu      = $dan_fu ? $this->explodeDanfu($dan_fu) : $cache['dan_fu'];
        $size        = 30;
        $loops       = ceil(count($keyword_arr) / $size);
        $result      = array(
            'yes'  => array(),
            'no'   => array(),
        );

        $fliter_key = read_static_cache('fliter_keyword', FRONT_STATIC_CACHE_PATH);
        require(LIB_PATH . 'sphinxapi.php');

        for ($i = 0; $i < $loops; $i++) {
            $offset  = $i * $size;
            $arr     = array_slice($keyword_arr, $offset, $size);    //当前执行分类
            $cl      = new SphinxClient();    //实例化sphinx
            $cl->SetServer(SPH_HOST, SPH_PORT);    //链接sphinx
            $cl->SetMatchMode(SPH_MATCH_EXTENDED2);
            $cl->SetLimits(0, 1);
            $n = 0;
            $query_arr = array();

            foreach ($arr as $v) {

                if ($v = trim($v)) {
                    $v_arr = explode(' ', $v);
                    $exclude_keywords = $this->_resetKeywords($v_arr, $v, $fliter_key, $not_search, $dan_fu, $exclude);
                    $query = $exclude_keywords === true ? '搜索不到结果' : $this->_buildQuery($v_arr, $exclude_keywords);
                    $cl->AddQuery($query, SPH_INDEX_MAIN);
                    $query_arr[$n] = $v;
                    $n++;
                }
            }

            $query_result = $cl->RunQueries();

            if ($query_result === false) {
                exit(json_encode(array('success' => false, 'msg' => '查询出错，错误信息\n' . $cl->GetLastError())));
            }

            foreach ($query_result as $k => $v) {
                if (empty($v['total_found'])) {
                    $result['no'][] = $query_arr[$k];
                }
                else {
                    $result['yes'][$query_arr[$k]] = $v['total_found'];
                }
            }
        }

        $this->log($_SESSION['WebUserInfo']['real_name'] . var_export($result, true), $time_start);

        header('Content-Type: application/json; charset=utf-8');
        $result['content'] = join("\n", array_keys($result['yes']));
        exit(json_encode($result));

    }//end query
}