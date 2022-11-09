<?php
/**
 * function.category_hot_searches.php   获取分类热门搜索关键词
 *
 * @package                             Smarty
 * @subpackage                          plugins
 * @author                              mashanling(msl-138@163.com)
 * @date                                2013-07-19 11:53:11
 * @lastmodify                          $Author: chenll $ $Date: 2013-08-26 10:37:24 +0800 (Mon, 26 Aug 2013) $
 */

/**
 * 获取分类热门搜索关键词
 *
 * @param array  $params    自定义参数
 * @param object $smarty    smarty实例
 *
 * @return string 分类热门搜索关键词
 */
function smarty_function_category_hot_searches($params, &$smarty) {

    if (empty($params['cat_id']) || !is_numeric($params['cat_id'])) {
        return false;
    }

    require_once(ROOT_PATH . 'lib/seo/class.seo_keywords_to_category.php');

    $class  = new SEO_Keywords_To_Category();
    $cat_id = $params['cat_id'];
    $path   = $class->_category_hot_searches_path;
    $num    = isset($params['num']) ? intval($params['num']) : 10;
    $num    = $num ? $num : 10;
    $key    = $cat_id . '-num-' . $num . '.cache';

    if (!isset($params['nocache']) && ($content = read_static_cache($key, $path))) {
        //$content = read_static_cache($key, $path);
    }
    else {
        $data       = $class->getCategoryHotSearches($cat_id, $num);
        $count      = count($data);
        $cat_arr    = read_static_cache('category_c_key', 2);
        $parent_id  = $cat_arr[$cat_id]['parent_id'];

        if ($count < $num){
            $remain     = $num - $count;
            if($parent_id){  //不是顶级分类取平级分类
                foreach($cat_arr as $k => $v) {
                    if ($v['parent_id'] == $parent_id) {
                        $children[] = $v['cat_id']; //取出所有平级分类
                    }

                }
            }else{  //如果是顶级分类取下级分类
               $children = new_get_children($cat_id,false,true);
            }
            if(!empty($children)) {
                shuffle($children);

                $path = $class->_category_hot_searches_path;

                foreach($children as $row) {
                    $cache = read_static_cache($row, $path);
                    if( !empty($cache)) {
                       foreach($cache as $keyword){
                           $data[] = $keyword;
                           $remain--;
                           if($remain ==0){
                               break 2;
                           }
                       }

                    }
                }
            }
        }
        $content = '';
        foreach($data as $keyword) {
            $content .= '<a href="' . get_search_url($keyword) . '">' . ucwords($keyword) . '</a>, ';
        }
        $content = substr($content, 0, -2);

        if (!isset($params['nocache'])) {
            cache($path . $key . '.php', $content, false, isset($params['cache_time']) ? $params['cache_time'] : 86400);
            //write_static_cache($key, $content, $path);
        }
    }

    if (isset($params['assign'])) {
        $smarty->assign($params['assign'], $content);
    }
    else {
        return $content;
    }
}