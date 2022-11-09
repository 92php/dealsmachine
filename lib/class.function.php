<?php
/**
 * class.function.php	通用函数类，因为函数很多，不知道有没有定义，故新函数都放在此类里
 *
 * @author				mashanling(msl-138@163.com)
 * @date				2011-11-18
 * @last modify			2012-02-25 08:42:22 by mashanling
 */

class Func {

    /**
     * unset($_SESSION[$name])
     *
     * @param string $name 名称，多个名称以','隔开
     *
     * @return void 无返回值
     */
    static function unset_session($name) {
        $name = explode(',', $name);

        foreach ($name as $v) {
            $v = trim($v);
            if (isset($_SESSION[$v])) {
                unset($_SESSION[$v]);
            }
        }
    }

    /**
     * 格式化字节大小
     *
     * @param int $filesize  大小，单位：字节
     * @param int $precision 小数点精度，默认：2
     *
     * @return string 格式化后大小
     */
    static function format_size($filesize, $precision = 2) {
        if ($filesize >= 1073741824) {
    	    $filesize = round($filesize / 1073741824 * 100) / 100;
    	    $unit     = 'GB';
    	}
    	elseif ($filesize >= 1048576) {
    		$filesize = round($filesize / 1048576 * 100) / 100 ;
    	    $unit     = 'MB';
    	}
    	elseif($filesize >= 1024) {
    		$filesize = round($filesize / 1024* 100) / 100;
            $unit = 'KB';
        }
        else {
            $filesize = $filesize;
            $unit = 'Bytes';
        }
        return sprintf('%.2f', $filesize) . ' ' . $unit;
    }

    /**
     * 开启缓冲输出
     *
     * @return void 无返回值
     */
    static function ob_start() {
        while (ob_get_level() != 0) {
            ob_end_clean();
        }
        ob_start();
    }

    /**
     * 记录定时任务日志
     *
     * @param string $filename   定时任务文件名
     * @param string $log        日志内容
     * @param float  $time_start 开始时间，microtime(true)
     */
    static function crontab_log($filename = '', $log = PHP_EOL, $time_start = false) {
        Logger::filename(LOG_FILENAME_PATH);

        if ($time_start) {
            G('a', $time_start);
            $log = sprintf(LOG_STRONG_FORMAT, G('a', 'b')) . PHP_EOL . $log;
        }

        trigger_error($log);
    }

    /**
     * 通过sphinx获得分类下的商品
     *
     * @param  array  $matches sphinx查询匹配结果集
     * @param  array  $cat_arr 所有分类信息
	 * @param  array  $cat_id  分类id
	 * @param  string $order   排序
	 *
     * @return array 商品信息
     */
    static function sphinx_get_goods(&$matches, &$cat_arr, $cat_id, $order = 'hot') {
    	if(empty($matches))return array();
        $goods_ids = join(',', array_keys($matches));
       // echo count($matches);
        //$odr       = $order != 'hot' && $order != 'conversion' ? " FIND_IN_SET(g.goods_id, '{$goods_ids}')" : 'IF(goods_number=0, 0, 1) DESC,IF(g.promote_start_date<UNIX_TIMESTAMP() AND g.promote_end_date>UNIX_TIMESTAMP(), 1, 0) DESC, IF(t.is_best=1, 1, 0) DESC, IF(t.is_new=1, 1, 0) DESC, IF(t.is_hot=1, 1, 0) DESC, week2sale DESC, sort_order, add_time DESC';
        //$odr       = $order == 'conversion' ? str_replace('week2sale DESC', 'c.conversion_rate DESC,week2sale DESC', $odr) : $odr;
        $odr       = " FIND_IN_SET(g.goods_id, '{$goods_ids}')";
        $sql       = 'SELECT  s.is_24h_ship,g.presale_date_from,g.presale_date_to,g.goods_id,g.is_superstar,g.cat_id,goods_title,goods_name_style,goods_name,goods_sn,market_price,goods_weight,is_free_shipping,url_title,goods_grid,if ((goods_number>0 and is_on_sale =1),goods_number,0) as goods_number,shop_price AS org_price,shop_price,promote_price,goods_type,promote_price,promote_start_date,promote_end_date,goods_brief,goods_thumb,goods_img,t.is_hot,t.is_new,t.is_best FROM ' . GOODS . ' AS g left join '.GOODS_STATE .' as s on s.goods_id=g.goods_id LEFT JOIN '.GOODS_CONVERSION_RATE .' AS c on c.goods_id=g.goods_id  LEFT JOIN ' . GOODSTUIJIAN . ' AS t ON g.goods_id=t.goods_id  ' . ($cat_id ? " AND t.cat_id={$cat_id}" : '') . " WHERE `g`.`is_alone_sale` = 1 and g.goods_id IN({$goods_ids}) ORDER BY {$odr}";
        //echo $sql;
       // exit();
        $super_star  = read_static_cache('super_star',1);    //后台推荐明显产品
        $db        = $GLOBALS['db'];
        $query     = $db->query($sql);
        $arr       = array();
        $return_arr = array('out_of_stock'=>1);		//默认显示out of stock,当前页有一个商品为in stock时，整页都不显示out of stock  by xyl 2013-04-08

        while (($row = $db->fetchArray()) !== false) {
        	//print_r($row);
        	//exit();
            $goods_id      = $row['goods_id'];
            $match_attrs   = $matches[$goods_id]['attrs'];

            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $promote_price = price_format($promote_price);

            $arr[$goods_id]['goods_id']    = $goods_id;
            $arr[$goods_id]['goods_title'] = sub_str($row['goods_title'],60,'...');

            if (strpos($row['goods_name'], ',') !== false) {
                $row['goods_name'] = explode(',', $row['goods_name']);
                $row['goods_name'] = $row['goods_name'][0];
            }

					if($row['presale_date_from']){
						if($row['presale_date_from'] > gmtime()){
							$arr[$row['goods_id']]['presale_date_from'] =$row['presale_date_from'];

							//$arr[$goods_id]['presale_date_from'] = local_date($GLOBALS['_CFG']['date_format'], $arr[$goods_id]['presale_date_from']);
						}else {
							$arr[$row['goods_id']]['presale_date_from'] = '';
						}
					}
				 if($row['presale_date_to']){

						if($row['presale_date_to'] > gmtime()){
							$arr[$row['goods_id']]['presale_date_to'] =$row['presale_date_to'];

							//$arr[$goods_id]['presale_date_to'] = local_date($GLOBALS['_CFG']['date_format'], $arr[$goods_id]['presale_date_to']);
						}else {
							$arr[$row['goods_id']]['presale_date_to'] = '';
						}
				}


            $review_count   = $match_attrs['reviews'];
            $arr[$goods_id]['review'] = array('review_count' => $review_count, 'avg_rate_img' => showRate(5));

            $cat_name = empty($cat_arr[$row['cat_id']]['cat_name']) ? '' : $cat_arr[$row['cat_id']]['cat_name'];
            $big_cat  = empty($cat_arr[$row['cat_id']]['parent_id']) ? true : false;

            $arr[$goods_id]['cat_name']     = $cat_name;
            $arr[$goods_id]['cat_url']      = creat_nav_url(empty($cat_arr[$row['cat_id']]) ? '' : $cat_arr[$row['cat_id']]['url_title'], $row['cat_id'], $big_cat);
            $arr[$goods_id]['is_24h_ship'] = $row['is_24h_ship'];
            $arr[$goods_id]['name']         = $row['goods_title'];
            $arr[$goods_id]['goods_name']   = $row['goods_name']; //型号
            $arr[$goods_id]['goods_sn']     = $row['goods_sn'];
            $arr[$goods_id]['goods_full_title'] = $row['goods_title'];
            $row['goods_brief']             = ' Wholesale ' . $row['goods_title'] . '!';
            if($super_star && in_array($row['goods_sn'],$super_star)){
                $arr[$goods_id]['is_super_star'] = 1;
            }
            $_cat_id                        = $row['cat_id'];

            $arr[$goods_id]['cat_id']       = $_cat_id;

            if ($order == 'hot') {
                $is_hot  = $row['is_hot'];
                $is_new  = $row['is_new'];
                $is_best = $row['is_best'];
            }
            else {
                $is_hot  = 0;
                $is_new  = 0;
                $is_best = 0;
            }

            $arr[$goods_id]['is_hot']       = $is_hot;
            $arr[$goods_id]['is_best']      = $is_best;
            $arr[$goods_id]['is_new']       = $is_new;
            $arr[$goods_id]['is_promote']   = $promote_price > 0 && $order == 'hot' ? 1 : 0;
            $arr[$goods_id]['goods_number'] = $row['goods_number'];
			$arr[$goods_id]['is_superstar'] = $row['is_superstar'];
            if($return_arr['out_of_stock'] == 1 && $arr[$row['goods_id']]['goods_number'])		//默认显示out of stock,当前页有一个商品为in stock时，整页都不显示out of stock  by xyl 2013-04-08
	        {
	        	$return_arr['out_of_stock'] = 0;
	        }
            $arr[$goods_id]['goods_brief']  = sub_str($row['goods_brief'], 110);
            $arr[$goods_id]['goods_weight'] = formated_weight($row['goods_weight']);
            $arr[$goods_id]['goods_style_name'] = add_style($row['goods_title'], $row['goods_name_style']);
            $arr[$goods_id]['market_price'] = price_format($row['market_price']);
            $arr[$goods_id]['type']         = $row['goods_type'];
            $arr[$goods_id]['promote_price']= $promote_price > 0 ? $promote_price : '';
            $arr[$goods_id]['promote_zhekou'] = $promote_price > 0 && $row['market_price'] > 0 ? round(($row['market_price'] - $promote_price) / $row['market_price'], 2) * 100 : '';
            $arr[$goods_id]['shop_price']  = $promote_price > 0 ? $promote_price : price_format($row['shop_price']);
            $arr[$goods_id]['goods_thumb'] = get_image_path($goods_id, $row['goods_thumb'], true);
            $arr[$goods_id]['goods_img']   = get_image_path($goods_id, $row['goods_img']);
            $arr[$goods_id]['goods_grid']  = get_image_path($goods_id, $row['goods_grid']);
            $arr[$goods_id]['is_free_shipping'] = $row['is_free_shipping'];
            $arr[$goods_id]['saveprice']   = price_format($row['market_price'] - $arr[$goods_id]['shop_price']);
            $arr[$goods_id]['saveperce']   = ($row['market_price'] == 0 || is_null($row['market_price'])) ? '0' : price_format(($row['market_price'] - $arr[$goods_id]['shop_price']) / $row['market_price']) * 100;
            $arr[$goods_id]['url_title']   = get_details_link($goods_id, $row['url_title']);
        }
        //print_r($arr);
        $return_arr['goods_list'] = $arr;
    	return $return_arr;
    }//end sphinx_get_goods

    /**
     * 取得指定分类所有子类id
     *
     * @param  array $category_arr 所有分类
     * @param  int   $cat_id       分类id
     *
     * @return string 所有子类id
     */
    static function get_category_children_ids(&$category_arr, $cat_id) {
        $ids = '';

        foreach ($category_arr as $v) {
            if ($v['parent_id'] == $cat_id) {
                $ids .= $v['cat_id'] . ',' . self::get_category_children_ids($category_arr, $v['cat_id']);
            }
        }

        return $ids;
    }

    /**
     * 获取分类信息，适用于前端需要判断分类是否屏蔽
     *
     * @param  array  $type_arr 所有分类
     * @param  int    $cat_id   当前分类id
     * @param  string $blang    浏览器首选语言
     *
     * @return mixed 如果分类存在且通过屏蔽设置，返回该类信息，否则返回false
     */
    static function get_category_info(&$type_arr, $cat_id, $blang) {

        if (!isset($type_arr[$cat_id])) {    //分类不存在
            return false;
        }

        $cat_info = $type_arr[$cat_id];    //当前分类信息

        if ($cat_info['is_show'] == 0) {    //分类在前台不显示
            return false;
        }

        if ($cat_info['is_login'] == 1) {    //如果这一类需要登陆

            if (empty($blang) && empty($_COOKIE['WEBF-dan_num'])) {
                return false;
            }
            else {

                if (stripos($cat_info['clang'], $blang) !== false && empty($_COOKIE['WEBF-dan_num'])) {
                    return false;
                }
            }
        }

        return $cat_info;
    }

     /**
     * 获取指定分类最顶级父类id
     *
     * @param  int $cat_id 分类id
     *
     * @return int 最顶级父类id
     */
    static function get_category_top_parent_id($cat_id) {
        $cat_arr = read_static_cache('category_children', 2);    //顶级分类

        if (isset($cat_arr[$cat_id])) {
            $parent_id = $cat_id;
        }
        else {

            foreach ($cat_arr as $k => $v) {    //查找最顶级parent_id

                if (in_array($cat_id, $v['children'])) {
                    $parent_id = $k;
                    break;
                }

            }
        }

        unset($cat_arr);

        return $parent_id;
    }

    /**
     * 禁止访问
     *
     * @return void 无返回值
     */
    static function forbid() {
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        if (self::forbid_au($referer)) {

            $info    = array(
                'HTTP_HOST'       => $_SERVER['HTTP_HOST'],
                'HTTP_USER_AGENT' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                'REMOTE_ADDR'     => function_exists('real_ip') ? real_ip() : $_SERVER['REMOTE_ADDR'],
                'QUERY_STRING'    => $_SERVER['QUERY_STRING'],
                'REQUEST_URI'     => $_SERVER['REQUEST_URI'],
                'HTTP_REFERER'    => $referer,
                'REQUEST_TIME'    => date('Y-m-d H:i:s', time() - date('Z') + 28800),
            );
            file_put_contents(CRONTAB_LOG_PATH . 'forbid-' . date('Ymd', time() - date('Z') + 28800) . '.log', var_export($info, true), FILE_APPEND);
            header('Location: http://www.dealsmachine.com/');
            exit;
        }
    }

    /**
     * 屏蔽http://www.dealsmachine.com.au/
     *
     * @param string $referer 来路
     *
     * @return void 无返回值
     */
    private function forbid_au($referer) {
        $ssaid = empty($_GET['SSAID']) ? 0 : intval($_GET['SSAID']);
        return $ssaid == 405815 || stripos('http://www.shareasale.com/r.cfm?u=405815', $referer) !== false ? true : false;
    }

    /**
     * 重置sphinx过滤条件
     *
     * @param  object  $cl   sphinx实例
     * @param  string  $attr 过滤key，即SetFilter(key, value)中的key
     *
     * @return void 无返回值
     */
    static function reset_sphinx_filter(&$cl, $attr) {

        foreach ($cl->_filters as $key => $item) {

            if (!empty($item['attr']) && $item['attr'] == $attr) {
                unset($cl->_filters[$key]);
                break;
            }

        }
    }

    /**
     * 删除商品
     *
     * @param string $goods_ids 商品id
     *
     * @return void 无返回值
     */
    static function delete_goods($goods_ids) {
        global $db;

        empty($goods_ids) && exit();

        $time_start = microtime(true);
        $where      = "goods_id IN({$goods_ids})";

        $db->query('INSERT INTO ' . DELETE_GOODS . ' SELECT * FROM ' . GOODS . " WHERE {$where} ON DUPLICATE KEY UPDATE " . DELETE_GOODS . '.goods_id=' . DELETE_GOODS . '.goods_id');

        self::delete_goods_img($goods_ids);//删除商品图片
        self::delete_goods_gallery($goods_ids);//删除商品相册


        $tables    = array(//关联表
            GOODS,           //主商品表
            CART,            //购物车
            GGALLERY,        //商品相册
            COLLECT,         //收藏的商品
            GATTR,           //商品属性
            GOODS_DIGG,      //商品digg表
            GOODSCAT,        //扩展分类
            REVIEW_STAT,     //商品评论统计
            GOODSTUIJIAN,    //商品推荐
            GROUPGOODS,      //商品配件
            INQUIRY,         //咨询
            PRO_INQUIRY,     //产品咨询
            REVIEW_PIC,      //评论图片
            REVIEW_VIDEO,    //评论视频
            SPECIAL_GOODS,   //专题商品
        );

        foreach ($tables as $table) {
            echo 'delete table ' . $table;
            $db->delete($table, $where);
            echo ',ok. affected: ', $db->affectedRows(), '<br />';
        }


        echo 'delete table ' . GROUPGOODS;
        $db->delete(GROUPGOODS, "parent_id IN({$goods_ids})");
        echo ',ok. affected: ', $db->affectedRows(), '<br />';

        echo 'delete table ' . COMMENT;
        $db->delete(COMMENT, "id_value IN({$goods_ids})");
        echo ',ok. affected: ', $db->affectedRows(), '<br />';

        echo 'delete table ' . VPRICE;
        $db->delete(VPRICE, $where . ' AND price_type IN(1, 6)');//阶梯价
        echo ',ok. affected: ', $db->affectedRows(), '<br />';

        Logger::filename(LOG_FILENAME_PATH);
        trigger_error(sprintf(LOG_STRONG_FORMAT, G('start_time', 'end_time')) . PHP_EOL . $goods_ids);
    }//end delete_goods

    /**
     * 删除商品评论
     *
     * @param string $goods_ids 商品id
     *
     * @return void 无返回值
     */
    static function delete_goods_review($goods_ids) {
        global $db;

        $sql = 'SELECT rid FROM ' . REVIEW . " WHERE goods_id IN({$goods_ids})";
        $rid = $db->getCol($sql);

        echo 'delete table ' . REVIEW;
        $db->delete(REVIEW, $where);//商品评论
        echo ',ok. affected: ', $db->affectedRows(), '<br />';

        if (!empty($rid)) {
            $rids   = implode(',', $rid);
            $tables = array(REVIEW_REPLY, REVIEW_PIC, REVIEW_VIDEO);
            $where  = "rid IN({$rids})";

            foreach ($tables as $table) {
                echo 'delete table ' . $table;
                $db->delete($table, $where);
                echo ',ok. affected: ', $db->affectedRows(), '<br />';
            }
        }
    }

    /**
     * 删除商品相册
     *
     * @param string $goods_ids 商品id
     *
     * @return void 无返回值
     */
    static function delete_goods_gallery($goods_ids) {
        global $db;

        $photo       = 'img_url,thumb_url,img_original';    //商品相册
        $photo_arr   = explode(',', $photo);
        $gallery_img = array();
        $sql         = "SELECT {$photo} FROM " . GGALLERY . " WHERE goods_id IN({$goods_ids})";
        $res         = $db->query($sql);
        $gallery_img = array();

        while ($row = $db->fetchRow($res)) {
            $gallery_img[] = "img_url@@{$row['img_url']}@@@thumb_url@@{$row['thumb_url']}@@@img_original@@{$row['img_original']}";

            foreach ($photo_arr as $item) {    //删除图片
                $file = ROOT_PATH . str_replace('E/', 'uploads/', $row[$item]);

                if (is_file($file)) {
                    unlink($file);
                    echo 'delete ', $file, '<br />';
                }
            }
        }

        if (!empty($gallery_img)) {//删除商品封面和相册图片
            $syn_gallery_image_ser1 = serialize($gallery_img);
            $post_data = "syn_gallery_image={$syn_gallery_image_ser1}&action=del";
            echo 'delete remote gallery', self::post_image_info(IMG_API_PATH, $post_data), '<br />'; //到图片库删除相册
        }
    }//end delete_goods_gallery

    /**
     * 删除商品图片
     *
     * @param string $goods_ids 商品id
     *
     * @return void 无返回值
     */
    static function delete_goods_img($goods_ids) {
        global $db;

        $img      = 'goods_thumb,goods_img,original_img,goods_grid';    //商品图片
        $img_arr  = explode(',', $img);
        $sql      = "SELECT {$img} FROM " . GOODS . " WHERE goods_id IN({$goods_ids})";
        $res      = $db->query($sql);

        $syn_gallery_image_ser1 = serialize(array());

        while ($goods = $db->fetchRow($res)) {//删除商品图片
            $post_data = "goods_thumb={$goods['goods_thumb']}&goods_grid={$goods['goods_grid']}&goods_img={$goods['goods_img']}&original_img={$goods['original_img']}&syn_gallery_image={$syn_gallery_image_ser1}&action=del";

            echo 'delete remote img', self::post_image_info(IMG_API_PATH, $post_data), '<br />'; //到图片服务器删除图片

            foreach ($img_arr as $item) {    //删除图片
                $file = ROOT_PATH . str_replace('E/', 'uploads/', $goods[$item]);

                if (is_file($file)) {
                    unlink($file);
                    echo 'delete ', $file, '<br />';
                }
            }
        }
    }//end delete_goods_img

    /**
     * post 数据到图片库，同步添加
     *
     * parm string $url  post地址
     * parm string $data post数据
     *
     * @return string 二进制图片数据内容
     */
    static function post_image_info($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
        $contents = curl_exec($ch);
        curl_close($ch);

        return $contents;
    }
}
?>