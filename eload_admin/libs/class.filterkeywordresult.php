<?php
/**
 * class.filterkeywordresult.php     关键字and查询处理类
 *
 * @author                           mashanling(msl-138@163.com)
 * @date                             2012-11-27 14:17:50
 * @last modify                      2013-01-08 09:20:08 by mashanling
 */
class FilterKeywordResult extends FilterKeyword {
    /**
     * 查询
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-11-23 14:37:12
     * @last modify  2012-11-28 09:51:43 by mashanling
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

        $result      = array(
            'yes'  => array(),
            'no'   => array(),
        );
        require_once(ROOT_PATH . 'lib/seo/class.seo_filter_upload_keywords.php');
        $class  = new SEO_Filter_Upload_Keywords();
        foreach ($keyword_arr as $key=>$item) {

            $query_result = $class->upload($item,true);
            $result['yes'][$item] = isset($query_result['total'])?$query_result['total']:0;
        }

        //写日志
        Logger::filename(LOG_FILENAME_PATH);
        trigger_error($_SESSION['WebUserInfo']['real_name'] . var_export($result, true));

        header('Content-Type: application/json; charset=utf-8');
        $result['content'] = join("\n", array_keys($result['yes']));
        exit(json_encode($result));

    }//end query

    /**
     * 生成查询关键字
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-11-23 14:42:38
     * @last modify  2012-11-23 14:42:38 by mashanling
     *
     * @param array $keyword_arr
     * @param string $exclude_keywords
     *
     * @return string 查询关键字
     */
    public function buildQuery($keyword_arr, $exclude_keywords) {

        if (!$keyword_arr) {
            return '搜索不到结果';
        }

        $query = '';

        foreach ($keyword_arr as $v) {
            $v = trim($v);
            //$query .= strlen($v) > 2 ? "*{$v}*" : $v;//长度小于2完全匹配
            $query .= $v;
            $query .= ' ';
        }

        return substr($query, 0, -1) . $exclude_keywords;
    }

    /**
     * 处理查询关键字，包括过滤，屏蔽，单复数处理，搜索men 排除产品名包含 women等
     *
     * @author          mashanling(msl-138@163.com)
     * @date            2012-11-23 14:48:01
     * @last modify     2013-01-08 09:20:20 by mashanling
     *
     * @param array  $keyword_arr      空隔隔开关键字数组
     * @param string $keyword          关键字
     * @param array  $filter_key       屏蔽关键字
     * @param array  $not_search       不参与搜索关键字
     * @param array  $dan_fu_arr       单复数
     * @param array  $exclude          排除搜索关键字
     * @param array  $not_this_start   以此些词开始就不入库
     * @param array  $not_this_end     以此些词结束就不入库
     * @return string 处理后关键字
     */
    public function resetKeywords(&$keyword_arr, $keyword, $filter_key, $not_search, $dan_fu_arr, $exclude,$no_this_start,$no_this_end) {
        foreach ($filter_key as $item) {//屏蔽

            if (preg_match('-\b' . preg_quote($item, '-') . '\b-', $keyword)) {    //包含屏蔽关键字，搜索不到结果
                return true;
            }
        }

        $exclude_keywords = '!(';

        foreach ($keyword_arr as $key => $item) {
             if($key == 0 || $key == count($keyword_arr)-1){ //第一个和最后一个词包含排除词直接返回true
                if($key == 0){
                    if(in_array($item,$no_this_start)){
                        return true;
                    }
                }
                if($key == count($keyword_arr)-1){
                   if(in_array($item,$no_this_end)){
                        return true;
                    }
                }
                //前后是特殊字符的去除
                if(!preg_match("/^[A-Za-z0-9]+$/",$item)){
                    return true;
                }
            }
        }
        foreach($keyword_arr as $key => $item){
            if(in_array($item, $not_search)){//去除不要搜索的词
                unset($keyword_arr[$key]);
            }

            if (isset($dan_fu_arr[$item])) {//复数，搜索单数
                $keyword_arr[$key] = $dan_fu_arr[$item];
            }
            /*
            if ($exclude && isset($exclude[$item])) {//排除处理，直接有对应排除关系
                $loop = $exclude[$item];
            }
            elseif (isset($dan_fu_arr[$item]) && $exclude && isset($exclude[$dan_fu_arr[$item]])) {//复数处理成单数后
                $loop = $exclude[$dan_fu_arr[$item]];
            }
             */
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
    }//end resetKeywords
}