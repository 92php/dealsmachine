<?php
/**
 * class.seo_clear_keywords.php     清理abc词库
 *
 * @author                          mashanling <msl-138@163.com>
 * @date                            2013-07-15 11:50:10
 * @lastmodify                      $Author: msl $ $Date: 2013-08-15 11:24:14 +0800 (Thu, 15 Aug 2013) $
 */

require_once(ROOT_PATH . 'lib/seo/class.seo.php');
require_once(ROOT_PATH . 'lib/seo/class.seo_filter_upload_keywords.php');

class SEO_Clear_Keywords extends SEO_Filter_Upload_Keywords {
    /**
     * 清理
     *
     * @author          mashanling(msl-138@163.com)
     * @date            2013-07-15 18:29:41
     *
     * @return  void    无返回值
     */
    public function clear() {
/*
        $db = $this->_getDb();
        $data = $db->arrQuery('SELECT keyword_id FROM ' . ABCKEYWORD_NEW2);

        foreach($data as $item) {
            $db->update(ABCKEYWORD_NEW2, 'random=' . $this->_getRandom(), 'keyword_id=' . $item['keyword_id']);
        }

        exit();
*/
        $cat_arr        = read_static_cache('category_c_key', 2);

        foreach($cat_arr as $cat_id => $item) {
            $cat_arr[$cat_id] = strtolower($item['cat_name']);
        }

        $time_start     = microtime(true);
        $db             = $this->_getDb();
        $log_file       = basename(__FILE__, '.php') . '.log';
        $offset         = intval(read_static_cache($log_file, 2));
        $page_size      = IS_LOCAL ? 20 : 1000;
        $no_goods_id    = '';
        $no_goods_count = '';
        $keyword_arr    = $db->arrQuery('SELECT keyword_id,keyword,is_preserve FROM ' . ABCKEYWORD_NEW2 . " ORDER BY keyword_id LIMIT {$offset},{$page_size}");
        $update_attr    = array();//$sphinx->UpdateAttributes()
        $relative_arr   = array();

        foreach ($keyword_arr as $item) {
            $keyword    = strtolower($item['keyword']);
            $keyword_id = $item['keyword_id'];

            if (in_array($keyword, $cat_arr)) {//等于分类名
                $result = null;
            }
            else {
                $result = $this->upload($keyword);
            }

            if (0 === $result) {
                $this->log($this->_sphinx->GetLastError());
                exit;
            }
            elseif ($result) {//结果
				if(!empty($result['top_cat_id'])) {
					$res_top_cat_id = $result['top_cat_id'];
				} else {
					$res_top_cat_id = 0;
				}
                $update     = "goods_num={$result['total']},cat_id={$result['cat_id']},top_cat_id={$res_top_cat_id}";
                //$update    .= ',word_count=' . (substr_count($item['keyword'], ' ') + 1);
                //$update    .= 'random=' . $this->_getRandom();
                $update    .= ',last_update_time=' . gmtime();
                $db->update(ABCKEYWORD_NEW2, $update, 'keyword_id=' . $keyword_id);
                $relative_arr[$keyword_id] = $result['cat_id'];
                $update_attr[] = $keyword_id;
            }
            elseif(!$item['is_preserve']) {
                $no_goods_count++;
                $no_goods_id .= ',' . $item['keyword_id'];
            }
        }

        write_static_cache($log_file, $keyword_arr ? $offset + $page_size : 0, 2);

        if ($no_goods_id) {
            $no_goods_id = substr($no_goods_id, 1);
            $this->deleteAbcRelativeCache($no_goods_id);
            //$db->delete(ABCKEYWORD_NEW2, "keyword_id IN({$no_goods_id})");
        }

        $relative_arr && $this->setRelativeKeywords($relative_arr);
        $update_attr && $this->_updateAttributeDeleted($update_attr);

        $this->log("{$offset},{$page_size},({$no_goods_count})" . $no_goods_id, $time_start);
    }//end clear
}