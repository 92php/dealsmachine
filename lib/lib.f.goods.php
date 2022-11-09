<?php
if (!defined('INI_WEB')){die('访问拒绝');}

/**
 * 商品推荐usort用自定义排序行数
 */
function goods_sort($goods_a, $goods_b)
{
    if ($goods_a['sort_order'] == $goods_b['sort_order']) {
        return 0;
    }
    return ($goods_a['sort_order'] < $goods_b['sort_order']) ? -1 : 1;

}

/**
 * 获得指定分类同级的所有分类以及该分类下的子分类
 *
 * @access  public
 * @param   integer     $cat_id     分类编号
 * @return  array
 */
function get_categories_tree($cat_id = 0)
{
    if ($cat_id > 0)
    {
        $sql = 'SELECT parent_id FROM ' . CATALOG . " WHERE cat_id = '$cat_id'";
        $parent_id = $GLOBALS['db']->getOne($sql);
    }
    else
    {
        $parent_id = 0;
    }

    /*
     判断当前分类中全是是否是底级分类，
     如果是取出底级分类上级分类，
     如果不是取当前分类及其下的子分类
    */
    $sql = 'SELECT count(*) FROM ' . CATALOG . " WHERE parent_id = '$cat_id' AND is_show = 1 ";
    if ($GLOBALS['db']->getOne($sql) || $parent_id == 0)
    {
        /* 获取当前分类及其子分类 */
        $sql = 'SELECT a.cat_id, a.cat_name, a.sort_order AS parent_order, a.cat_id, a.is_show,' .
                    'b.cat_id AS child_id, b.cat_name AS child_name, b.sort_order AS child_order ' .
                'FROM ' . CATALOG . ' AS a ' .
                'LEFT JOIN ' . CATALOG . ' AS b ON b.parent_id = a.cat_id AND b.is_show = 1 ' .
                "WHERE a.parent_id = '$parent_id' ORDER BY parent_order ASC, a.cat_id ASC, child_order ASC";
    }
    else
    {
        /* 获取当前分类及其父分类 */
        $sql = 'SELECT a.cat_id, a.cat_name, b.cat_id AS child_id, b.cat_name AS child_name, b.sort_order, b.is_show ' .
                'FROM ' . CATALOG . ' AS a ' .
                'LEFT JOIN ' . CATALOG . ' AS b ON b.parent_id = a.cat_id AND b.is_show = 1 ' .
                "WHERE b.parent_id = '$parent_id' ORDER BY sort_order ASC";
    }
    $res = $GLOBALS['db']->arrQuery($sql);

    $cat_arr = array();
    foreach ($res AS $row)
    {
        if ($row['is_show'])
        {
            $cat_arr[$row['cat_id']]['id']   = $row['cat_id'];
            $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
           // $cat_arr[$row['cat_id']]['url']  = build_uri('category', array('cid' => $row['cat_id']), $row['cat_name']);

            if ($row['child_id'] != NULL)
            {
                $cat_arr[$row['cat_id']]['children'][$row['child_id']]['id']   = $row['child_id'];
                $cat_arr[$row['cat_id']]['children'][$row['child_id']]['name'] = $row['child_name'];
               // $cat_arr[$row['cat_id']]['children'][$row['child_id']]['url']  = build_uri('category', array('cid' => $row['child_id']), $row['child_name']);
            }
        }
    }

    return $cat_arr;
}

/**
 * 调用当前分类的销售排行榜
 *
 * @access  public
 * @param   string  $cats   查询的分类
 * @return  array
 */
function get_top10($cats = '')
{
    $where = !empty($cats) ? " AND ".get_children($cats): '';

    /* 排行统计的时间
	switch ($GLOBALS['_CFG']['top10_time'])
    {
        case 1: // 一年
            $top10_time = "AND o.order_sn >= '" . date('Ymd', gmtime() - 365 * 86400) . "'";
        break;
        case 2: // 半年
            $top10_time = "AND o.order_sn >= '" . date('Ymd', gmtime() - 180 * 86400) . "'";
        break;
        case 3: // 三个月
            $top10_time = "AND o.order_sn >= '" . date('Ymd', gmtime() - 90 * 86400) . "'";
        break;
        case 4: // 一个月
            $top10_time = "AND o.order_sn >= '" . date('Ymd', gmtime() - 30 * 86400) . "'";
        break;
        default:
  }*/
    $top10_time =  ' AND   og.addtime <= '.gmtime().' and   og.addtime >= '.gmstr2time('-15 day').'  ';


    $sql = 'SELECT g.goods_id, g.goods_title,g.cat_id,g.shop_price, g.goods_img, SUM(og.goods_number) as goods_number ' .
           'FROM ' . GOODS . ' AS g, ' .
                ORDERINFO . ' AS o, ' .
                ODRGOODS . ' AS og ' .
           "WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.is_login = 0 AND g.cat_id not in ('589','588','590') $where $top10_time " ;

    //判断是否启用库存，库存数量是否大于0
    if ($GLOBALS['_CFG']['use_storage'] == 1)
    {
        $sql .= " AND g.goods_number > 0 ";
    }
    $sql .= ' AND og.order_id = o.order_id AND og.goods_id = g.goods_id ' .
           "AND  order_status > 0  and  order_status < 9   " .
           'GROUP BY g.goods_id ORDER BY sum(og.goods_number*goods_price) DESC, g.goods_id DESC LIMIT 9';

    $stime = date('Y-m-d', gmstr2time('-15 day'));
    $etime = date('Y-m-d', gmtime());

	$sql = " SELECT  g.goods_id, g.goods_title,g.cat_id,g.shop_price, g.goods_img,g.url_title, SUM(og.goods_number) AS goods_num, SUM(og.goods_number) AS turnover FROM eload_order_goods AS og, eload_goods AS g, eload_order_info AS oi WHERE og.order_id = oi.order_id and g.goods_id = og.goods_id and oi.order_status > 0 and oi.order_status < 9 AND oi.add_time >= '".gmstr2time($stime)."' AND oi.add_time <= '".gmstr2time($etime)."' and  g.goods_number > 0  AND g.is_login = 0 and  g.shop_price > 30 GROUP BY og.goods_sn ORDER BY turnover DESC LIMIT 7  ";


    $arr = $GLOBALS['db']->arrQuery($sql);
		 //  echo $sql;

    for ($i = 0, $count = count($arr); $i < $count; $i++)
    {
        $arr[$i]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                                    sub_str($arr[$i]['goods_title'], $GLOBALS['_CFG']['goods_name_length']) : $arr[$i]['goods_title'];
        $arr[$i]['url_title'] = get_details_link($arr[$i]['goods_id'],$arr[$i]['url_title']);
        $arr[$i]['goods_img'] = get_image_path($arr[$i]['goods_id'], $arr[$i]['goods_img']);
    }
    return $arr;
}


/**
 * 获得促销商品
 *
 * @access  public
 * @return  array
 */
function get_promote_goods($cats = '')
{
    $time = gmtime();
    $order_type = 0;

    /* 取得促销lbi的数量限制 */
    $num = get_library_number("recommend_promotion");
    $sql = 'SELECT g.goods_id, g.goods_title, g.goods_name_style, g.market_price, g.shop_price AS org_price, g.promote_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
                "promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, goods_img, b.brand_name, " .
                "g.is_best, g.is_new, g.is_hot, g.is_promote, RAND() AS rnd " .
            'FROM ' . GOODS . ' AS g ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON b.brand_id = g.brand_id ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ' .
            " AND g.is_promote = 1 AND promote_start_date <= '$time' AND promote_end_date >= '$time' ";
    $sql .= $order_type == 0 ? ' ORDER BY g.sort_order' : ' ORDER BY rnd';
    $sql .= " LIMIT $num ";
    $result = $GLOBALS['db']->arrQuery($sql);

    $goods = array();
    foreach ($result AS $idx => $row)
    {
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
        }
        else
        {
            $goods[$idx]['promote_price'] = '';
        }

        $goods[$idx]['id']           = $row['goods_id'];
        $goods[$idx]['name']         = $row['goods_title'];
        $goods[$idx]['brief']        = $row['goods_brief'];
        $goods[$idx]['brand_name']   = $row['brand_name'];
        $goods[$idx]['goods_style_name']   = add_style($row['goods_title'],$row['goods_name_style']);
        $goods[$idx]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_title'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_title'];
        $goods[$idx]['short_style_name']   = add_style($goods[$idx]['short_name'],$row['goods_name_style']);
        $goods[$idx]['market_price'] = price_format($row['market_price']);
        $goods[$idx]['shop_price']   = price_format($row['shop_price']);
        $goods[$idx]['thumb']        = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $goods[$idx]['goods_img']    = get_image_path($row['goods_id'], $row['goods_img']);
        $goods[$idx]['url']          = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_title']);
    }

    return $goods;
}

/**
 * 获得指定分类下的推荐商品
 *
 * @access  public
 * @param   string      $type       推荐类型，可以是 best, new, hot, promote
 * @param   string      $cats       分类的ID
 * @param   integer     $brand      品牌的ID
 * @param   integer     $min        商品价格下限
 * @param   integer     $max        商品价格上限
 * @param   string      $ext        商品扩展查询
 * @return  array
 */
function get_category_recommend_goods($type = '', $cats = '', $brand = 0, $min =0,  $max = 0, $ext='')
{
    $brand_where = ($brand > 0) ? " AND g.brand_id = '$brand'" : '';

    $price_where = ($min > 0) ? " AND g.shop_price >= $min " : '';
    $price_where .= ($max > 0) ? " AND g.shop_price <= $max " : '';

    $sql =  'SELECT g.goods_id, g.goods_title, g.goods_name_style, g.market_price, g.shop_price AS org_price, g.promote_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
                'promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, goods_img, b.brand_name ' .
            'FROM ' . GOODS . ' AS g ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON b.brand_id = g.brand_id ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ' . $brand_where . $price_where . $ext;
    $num = 0;
    $type2lib = array('best'=>'recommend_best', 'new'=>'recommend_new', 'hot'=>'recommend_hot', 'promote'=>'recommend_promotion');
    $num = get_library_number($type2lib[$type]);

    switch ($type)
    {
        case 'best':
            $sql .= ' AND is_best = 1';
            break;
        case 'new':
            $sql .= ' AND is_new = 1';
            break;
        case 'hot':
            $sql .= ' AND is_hot = 1';
            break;
        case 'promote':
            $time = gmtime();
            $sql .= " AND is_promote = 1 AND promote_start_date <= '$time' AND promote_end_date >= '$time'";
            break;
    }

    if (!empty($cats))
    {
        $sql .= " AND (" . $cats . " OR " . get_extension_goods($cats) .")";
    }
    $order_type = $GLOBALS['_CFG']['recommend_order'];
    $sql .= ($order_type == 0) ? ' ORDER BY g.sort_order, g.last_update DESC' : ' ORDER BY RAND()';
    $res = $GLOBALS['db']->selectLimit($sql, $num);

    $idx = 0;
    $goods = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
        }
        else
        {
            $goods[$idx]['promote_price'] = '';
        }

        $goods[$idx]['id']           = $row['goods_id'];
        $goods[$idx]['name']         = $row['goods_title'];
        $goods[$idx]['brief']        = $row['goods_brief'];
        $goods[$idx]['brand_name']   = $row['brand_name'];
        $goods[$idx]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                                       sub_str($row['goods_title'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_title'];
        $goods[$idx]['market_price'] = price_format($row['market_price']);
        $goods[$idx]['shop_price']   = price_format($row['shop_price']);
        $goods[$idx]['thumb']        = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $goods[$idx]['goods_img']    = get_image_path($row['goods_id'], $row['goods_img']);
        $goods[$idx]['url']          = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_title']);

        $goods[$idx]['short_style_name'] = add_style($goods[$idx]['short_name'], $row['goods_name_style']);
        $idx++;
    }

    return $goods;
}

/**
 * 获得商品的详细信息
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  void
 */
function get_goods_info($goods_id)
{
	global $cur_lang, $default_lang;
    $time = gmtime();
    $sql = 'SELECT g.*,s.*, c.measure_unit ' .
            'FROM ' . GOODS . ' AS g ' .
        	'LEFT JOIN ' . CATALOG . ' AS c ON g.cat_id = c.cat_id ' .
            'LEFT JOIN ' . GOODS_STATE . ' AS s '.
                'ON s.goods_id = g.goods_id ' .
            "WHERE g.goods_id = '$goods_id'  " .//AND g.is_delete = 0
            " GROUP BY g.goods_id";
    $row = $GLOBALS['db']->selectinfo($sql);

   // $sql = "select * from ";


    if ($row !== false)
    {
        /* 用户评论级别取整 */
        //$row['comment_rank']  = ceil($row['comment_rank']) == 0 ? 5 : ceil($row['comment_rank']);

        /* 修正促销价格 */
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);

            if ($row['promote_end_date'] <= gmtime()) {//促销时间过期 by mashanling on 2014-02-20 09:45:11
                $GLOBALS['db']->update(GOODS, 'is_promote=0,promote_price=0,promote_start_date=0,promote_end_date=0', 'goods_id=' . $row['goods_id']);
            }
        }
        else
        {
            $promote_price = 0;
        }


        $row['shop_price'] =  ($promote_price > 0) ? price_format($promote_price) : price_format($row['shop_price']);

		//判断团购是否过期
		$row['is_groupbuy'] = (!empty($row['is_groupbuy']) && $row['groupbuy_start_date'] < gmtime() && $row['groupbuy_end_date'] > gmtime() )?1:0;

        /* 获得商品的销售价格 */
        $row['market_price']        = price_format((!$row['is_groupbuy'])?$row['market_price']:$row['shop_price']);

		if($row['is_groupbuy']){
			$group_price = get_groupbuy_price($row);
			$row['shop_price'] = $group_price?$group_price:$row['shop_price'];
		}



		$row['promote_price'] =  $promote_price;
        $row['shop_price_formated'] = price_format($row['shop_price']);

        /* 处理商品水印图片 */
        $watermark_img = '';

        if ($promote_price != 0)
        {
            $watermark_img = "watermark_promote";
        }
        elseif ($row['is_new'] != 0)
        {
            $watermark_img = "watermark_new";
        }
        elseif ($row['is_best'] != 0)
        {
            $watermark_img = "watermark_best";
        }
        elseif ($row['is_hot'] != 0)
        {
            $watermark_img = 'watermark_hot';
        }

        if ($watermark_img != '')
        {
            $row['watermark_img'] =  $watermark_img;
        }

        $row['promote_price_org'] =  $promote_price;
        $row['promote_price'] =  price_format($promote_price);

        /* 修正重量显示 */
        $row['goods_weight']  = formated_weight($row['goods_weight']);

        /* 修正上架时间显示 */
        $row['add_time']      = local_date($GLOBALS['_CFG']['date_format'], $row['add_time']);

        /* 促销时间倒计时 */
        $time = gmtime();
        if ($time >= $row['promote_start_date'] && $time <= $row['promote_end_date'])
        {
             $row['gmt_end_time']  = $row['promote_end_date'];
        }
        else
        {
            $row['gmt_end_time'] = 0;
        }

        /* 是否显示商品库存数量 */
        $row['goods_number']  = ($GLOBALS['_CFG']['use_storage'] == 1) ? $row['goods_number'] : '';

        /* 修正商品图片 */
        $row['goods_grid']   = get_image_path($goods_id, $row['goods_grid']);
        $row['goods_img']   = get_image_path($goods_id, $row['goods_img']);
        $row['goods_thumb'] = get_image_path($goods_id, $row['goods_thumb'], true);
        $row['original_img'] = get_image_path($goods_id, $row['original_img']);
		$row['cur_lang_goods_title'] = 1;
		$goods_desc = $row['goods_desc'];
		$is_superstar = preg_match("/superstar.css/", $goods_desc);
		if(1 == $is_superstar || 1 == $row['is_superstar']) {
			$row['is_superstar'] = 1;
		}
		// 多语言 fangxin 2013/07/05
		if($cur_lang != $default_lang) {
			$sql = 'SELECT g.*' .
					' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
					" WHERE g.goods_id = '$goods_id'";
			if($row_language = $GLOBALS['db']->selectinfo($sql)) {
				$row['goods_title'] = $row_language['goods_title'];
				$row['goods_name']  = $row_language['goods_name'];
				$row['goods_brief'] = $row_language['goods_brief'];
				$row['keywords']    = $row_language['keywords'];
				$row['goods_desc']  = $row_language['goods_desc'];
				$row['seller_note'] = $row_language['seller_note'];
				$row['cur_lang_goods_title'] = 1;
			} else {
				$row['cur_lang_goods_title'] = 0;
			}
		}
        return $row;
    }
    else
    {
        return false;
    }
}

/**
 * 获得商品的属性和规格
 *
 * @access  public
 * @param   integer $goods_id
 * @return  array
 */
function get_goods_properties($goods_id)
{
	global $cur_lang, $default_lang;
    /* 对属性进行重新排序和分组 */
    //$sql = "SELECT attr_group ".
            //"FROM " . GTYPE . " AS gt, " . GOODS . " AS g ".
           // "WHERE g.goods_id='$goods_id' AND gt.cat_id=g.goods_type";
    //$grp = $GLOBALS['db']->getOne($sql);

    if (!empty($grp))
    {
        $groups = explode("\n", strtr($grp, "\r", ''));
    }

    /* 获得商品的规格 */
    if($cur_lang != $default_lang) {			//多语言
    	$sql = "SELECT a.attr_id, a.attr_name,a.isnes, a.attr_type, ".
                "g.goods_attr_id, g.attr_value, g.attr_price, ga.attr_value_lang " .
            'FROM ' .GATTR . ' AS g ' .
            'LEFT JOIN ' . ATTR . ' AS a ON a.attr_id = g.attr_id ' .
            " LEFT JOIN " . GOODSATTRLANG . " AS ga ON ga.attr_id = g.attr_id AND ga.attr_value = g.attr_value AND ga.lang = '" . $cur_lang . "' " .
            "WHERE g.goods_id = '$goods_id'   AND a.disp = 1  " .
            'GROUP BY g.attr_id , a.attr_name, g.attr_value ORDER BY a.sort_order, g.goods_attr_id,g.attr_price ';
    }
    else
    {
    $sql = "SELECT a.attr_id, a.attr_name,a.isnes, a.attr_type, ".
                "g.goods_attr_id, g.attr_value, g.attr_price " .
            'FROM ' .GATTR . ' AS g ' .
            'LEFT JOIN ' . ATTR . ' AS a ON a.attr_id = g.attr_id ' .
            "WHERE g.goods_id = '$goods_id'   AND a.disp = 1  " .
            'GROUP BY g.attr_id , a.attr_name, g.attr_value ORDER BY a.sort_order, g.goods_attr_id,g.attr_price ';
    }
    $res = $GLOBALS['db']->arrQuery($sql);

    $arr['pro'] = array();     // 属性
    $arr['spe'] = array();     // 规格
    $arr['lnk'] = array();     // 关联的属性
    $arr['key'] = array();     // 关联的属性

    foreach ($res AS $row)
    {
        if ($row['attr_type'] == 0)
        {
            $group = $GLOBALS['_LANG']['goods_attr'];

            $arr['pro'][$group][$row['attr_id']]['name']  = $row['attr_name'];
            $arr['pro'][$group][$row['attr_id']]['value'] = $row['attr_value'];
        }
        else
        {
        	if( $row['attr_id'] != $GLOBALS['public_goods_type_spec_id']['color'] && $row['attr_id'] != $GLOBALS['public_goods_type_spec_id']['size'])
        	{
        		$arr['spe'][$row['attr_id']]['is_public'] = 0;
        	}
        	else
        	{
        		$arr['spe'][$row['attr_id']]['is_public'] = 1;

        		if($cur_lang != $default_lang) {			//多语言
        			$row['attr_value'] = empty($row['attr_value_lang']) ? $row['attr_value'] : $row['attr_value_lang'];
        		}
        	}
            $arr['spe'][$row['attr_id']]['attr_type'] = $row['attr_type'];
            $arr['spe'][$row['attr_id']]['name']     = $row['attr_name'];
            $arr['spe'][$row['attr_id']]['isnes']     = $row['isnes'];
            $arr['spe'][$row['attr_id']]['values'][] = array(
                                                        'label'        => $row['attr_value'],
                                                        'price'        => $row['attr_price'],
                                                        'format_price' => price_format(abs($row['attr_price'])),
														'id'           => $row['goods_attr_id'],);

            if (!in_array($row['attr_id'],$arr['key'])) $arr['key'][] = $row['attr_id'];


        }

    }
    return $arr;
}



/**
 * 获得属性相同的商品
 *
 * @access  public
 * @param   array   $attr   // 包含了属性名称,ID的数组
 * @return  array
 */
function get_same_attribute_goods($attr)
{
    $lnk = array();

    if (!empty($attr))
    {
        foreach ($attr['lnk'] AS $key => $val)
        {
            $lnk[$key]['title'] = sprintf($GLOBALS['_LANG']['same_attrbiute_goods'], $val['name'], $val['value']);

            /* 查找符合条件的商品 */
            $sql = 'SELECT g.goods_id, g.goods_title, g.goods_thumb, g.goods_img, g.shop_price AS org_price, ' .
                        "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
                        'g.market_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
                    'FROM ' . GOODS . ' AS g ' .
                    'LEFT JOIN ' . $GLOBALS['ecs']->table('goods_attr') . ' as a ON g.goods_id = a.goods_id ' .
                    "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                        "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
                    "WHERE a.attr_id = '$key' AND a.attr_value = '$val[value]' AND g.goods_id <> '$_REQUEST[id]' " .
                    'LIMIT ' . $GLOBALS['_CFG']['attr_related_number'];
            $res = $GLOBALS['db']->arrQuery($sql);

            foreach ($res AS $row)
            {
                $lnk[$key]['goods'][$row['goods_id']]['goods_id']      = $row['goods_id'];
                $lnk[$key]['goods'][$row['goods_id']]['goods_title']    = $row['goods_title'];
                $lnk[$key]['goods'][$row['goods_id']]['short_name']    = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                    sub_str($row['goods_title'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_title'];
                $lnk[$key]['goods'][$row['goods_id']]['goods_img']     = (empty($row['goods_img'])) ? $GLOBALS['_CFG']['no_picture'] : $row['goods_img'];
                $lnk[$key]['goods'][$row['goods_id']]['market_price']  = price_format($row['market_price']);
                $lnk[$key]['goods'][$row['goods_id']]['shop_price']    = price_format($row['shop_price']);
                $lnk[$key]['goods'][$row['goods_id']]['promote_price'] = bargain_price($row['promote_price'],
                    $row['promote_start_date'], $row['promote_end_date']);
                $lnk[$key]['goods'][$row['goods_id']]['url']           = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_title']);
            }
        }
    }

    return $lnk;
}

/**
 * 获得指定商品的相册
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  array
 */
function get_goods_gallery($goods_id)
{
    $sql = 'SELECT img_id, img_url, thumb_url,img_original, img_desc' .
        ' FROM ' . GGALLERY .
        " WHERE goods_id = '$goods_id' order by img_id desc";
    $row = $GLOBALS['db']->arrQuery($sql);
    /* 格式化相册图片路径 */
    foreach($row as $key => $gallery_img)
    {
        $row[$key]['img_url'] = get_image_path($goods_id, $gallery_img['img_url'], false, 'gallery');
        $row[$key]['img_original'] = get_image_path($goods_id, $gallery_img['img_original'], false, 'gallery');
        $row[$key]['thumb_url'] = get_image_path($goods_id, $gallery_img['thumb_url'], true, 'gallery');
    }
    return $row;
}

/**
 * 获得指定分类下的商品
 *
 * @access  public
 * @param   integer     $cat_id     分类ID
 * @param   integer     $num        数量
 * @param   string      $from       来自web/wap的调用
 * @return  array
 */
function assign_cat_goods($cat_id, $num = 0, $from = 'web')
{
    $children = get_children($cat_id);

    $sql = 'SELECT g.goods_id, g.goods_title, g.market_price, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
               'g.promote_price, promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img ' .
            "FROM " . GOODS . ' AS g '.
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND '.
                'g.is_delete = 0 AND (' . $children . 'OR ' . get_extension_goods($children) . ') ' .
            'ORDER BY g.sort_order, g.goods_id DESC';
    if ($num > 0)
    {
        $sql .= ' LIMIT ' . $num;
    }
    $res = $GLOBALS['db']->arrQuery($sql);

    $goods = array();
    foreach ($res AS $idx => $row)
    {
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
        }
        else
        {
            $goods[$idx]['promote_price'] = '';
        }

        $goods[$idx]['id']           = $row['goods_id'];
        $goods[$idx]['name']         = $row['goods_title'];
        $goods[$idx]['brief']        = $row['goods_brief'];
        $goods[$idx]['market_price'] = price_format($row['market_price']);
        $goods[$idx]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                                        sub_str($row['goods_title'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_title'];
        $goods[$idx]['shop_price']   = price_format($row['shop_price']);
        $goods[$idx]['thumb']        = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $goods[$idx]['goods_img']    = get_image_path($row['goods_id'], $row['goods_img']);
        $goods[$idx]['url']          = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_title']);
    }

    if ($from == 'web')
    {
        $GLOBALS['smarty']->assign('cat_goods_' . $cat_id, $goods);
    }
    elseif ($from == 'wap')
    {
        $cat['goods'] = $goods;
    }

    /* 分类信息 */
    $sql = 'SELECT cat_name FROM ' . CATALOG . " WHERE cat_id = '$cat_id'";
    $cat['name'] = $GLOBALS['db']->getOne($sql);
    $cat['url']  = build_uri('category', array('cid' => $cat_id), $cat['name']);
    $cat['id']   = $cat_id;

    return $cat;
}

/**
 * 获得指定的品牌下的商品
 *
 * @access  public
 * @param   integer     $brand_id       品牌的ID
 * @param   integer     $num            数量
 * @param   integer     $cat_id         分类编号
 * @return  void
 */
function assign_brand_goods($brand_id, $num = 0, $cat_id = 0)
{
    $sql =  'SELECT g.goods_id, g.goods_title, g.market_price, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
                'g.promote_price, g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img ' .
            'FROM ' . GOODS . ' AS g ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            "WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.brand_id = '$brand_id'";

    if ($cat_id > 0)
    {
        $sql .= get_children($cat_id);
    }

    $sql .= ' ORDER BY g.sort_order, g.goods_id DESC';
    if ($num > 0)
    {
        $res = $GLOBALS['db']->selectLimit($sql, $num);
    }
    else
    {
        $res = $GLOBALS['db']->query($sql);
    }

    $idx = 0;
    $goods = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        }
        else
        {
            $promote_price = 0;
        }

        $goods[$idx]['id']            = $row['goods_id'];
        $goods[$idx]['name']          = $row['goods_title'];
        $goods[$idx]['short_name']    = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
            sub_str($row['goods_title'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_title'];
        $goods[$idx]['market_price']  = $row['market_price'];
        $goods[$idx]['shop_price']    = price_format($row['shop_price']);
        $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
        $goods[$idx]['brief']         = $row['goods_brief'];
        $goods[$idx]['thumb']         = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $goods[$idx]['goods_img']     = get_image_path($row['goods_id'], $row['goods_img']);
        $goods[$idx]['url']           = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_title']);

        $idx++;
    }

    /* 分类信息 */
    $sql = 'SELECT brand_name FROM ' . $GLOBALS['ecs']->table('brand') . " WHERE brand_id = '$brand_id'";

    $brand['id']   = $brand_id;
    $brand['name'] = $GLOBALS['db']->getOne($sql);
    $brand['url']  = build_uri('brand', array('bid' => $brand_id), $brand['name']);

    $brand_goods = array('brand' => $brand, 'goods' => $goods);

    return $brand_goods;
}



/**
 * 取得商品信息
 * @param   int     $goods_id   商品id
 * @return  array
 */
function goods_info($goods_id)
{
    $sql = "SELECT g.*, b.brand_name " .
            "FROM " . GOODS . " AS g " .
                "LEFT JOIN " . $GLOBALS['ecs']->table('brand') . " AS b ON g.brand_id = b.brand_id " .
            "WHERE g.goods_id = '$goods_id'";
    $row = $GLOBALS['db']->getRow($sql);
    if (!empty($row))
    {
        /* 修正重量显示 */
        $row['goods_weight'] = formated_weight($row['goods_weight']);

        /* 修正图片 */
        $row['goods_img'] = get_image_path($goods_id, $row['goods_img']);
    }

    return $row;
}

/**
 * 取得优惠活动信息
 * @param   int     $act_id     活动id
 * @return  array
 */
function favourable_info($act_id)
{
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('favourable_activity') .
            " WHERE act_id = '$act_id'";
    $row = $GLOBALS['db']->getRow($sql);
    if (!empty($row))
    {
        $row['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['start_time']);
        $row['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['end_time']);
        $row['formated_min_amount'] = price_format($row['min_amount']);
        $row['formated_max_amount'] = price_format($row['max_amount']);
        $row['gift'] = unserialize($row['gift']);
        if ($row['act_type'] == FAT_GOODS)
        {
            $row['act_type_ext'] = round($row['act_type_ext']);
        }
    }

    return $row;
}

/**
 * 批发信息
 * @param   int     $act_id     活动id
 * @return  array
 */
function wholesale_info($act_id)
{
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('wholesale') .
            " WHERE act_id = '$act_id'";
    $row = $GLOBALS['db']->getRow($sql);
    if (!empty($row))
    {
        $row['price_list'] = unserialize($row['prices']);
    }

    return $row;
}

/**
 * 取得商品属性
 * @param   int     $goods_id   商品id
 * @return  array
 */
function get_goods_attr($goods_id)
{
    $attr_list = array();
    $sql = "SELECT a.attr_id, a.attr_name " .
            "FROM " . GOODS . " AS g, " . $GLOBALS['ecs']->table('attribute') . " AS a " .
            "WHERE g.goods_id = '$goods_id' " .
            "AND g.goods_type = a.cat_id " .
            "AND a.attr_type = 1";
    $attr_id_list = $GLOBALS['db']->getCol($sql);
    $res = $GLOBALS['db']->query($sql);
    while ($attr = $GLOBALS['db']->fetchRow($res))
    {
        if (defined('ECS_ADMIN'))
        {
            $attr['goods_attr_list'] = array(0 => $GLOBALS['_LANG']['select_please']);
        }
        else
        {
            $attr['goods_attr_list'] = array();
        }
        $attr_list[$attr['attr_id']] = $attr;
    }

    $sql = "SELECT attr_id, goods_attr_id, attr_value " .
            "FROM " . $GLOBALS['ecs']->table('goods_attr') .
            " WHERE goods_id = '$goods_id' " .
            "AND attr_id " . db_create_in($attr_id_list);
    $res = $GLOBALS['db']->query($sql);
    while ($goods_attr = $GLOBALS['db']->fetchRow($res))
    {
        $attr_list[$goods_attr['attr_id']]['goods_attr_list'][$goods_attr['goods_attr_id']] = $goods_attr['attr_value'];
    }

    return $attr_list;
}

/**
 * 获得购物车中商品的配件
 *
 * @access  public
 * @param   array     $goods_list
 * @return  array
 */
function get_goods_fittings($goods_list = array())
{
	global $cur_lang, $default_lang;
    $temp_index = 0;
    $arr        = array();

   $sql = 'SELECT gg.parent_id,g.shop_price,g.cat_id,g.is_alone_sale,g.original_img, ggg.goods_title AS parent_name, gg.goods_id, gg.goods_price, g.goods_title,g.url_title, g.goods_thumb, g.goods_img, g.shop_price AS org_price  ' .
            ' FROM ' . GROUPGOODS . ' AS gg ' .
            'LEFT JOIN ' . GOODS . ' AS g ON g.goods_id = gg.goods_id ' .
            "LEFT JOIN " . GOODS . " AS ggg ON ggg.goods_id = gg.parent_id ".
            "WHERE gg.parent_id " . db_create_in($goods_list) . " AND g.is_delete = 0 AND g.is_on_sale = 1 ".
            "ORDER BY gg.goods_price asc, gg.sort_order desc,gg.parent_id, gg.goods_id";

    $res = $GLOBALS['db']->arrQuery($sql);

    foreach ($res as $row)
    {
        $arr[$temp_index]['parent_id']         = $row['parent_id'];//配件的基本件ID
        $arr[$temp_index]['is_alone_sale']     = $row['is_alone_sale'];//配件的基本件ID
        $arr[$temp_index]['parent_name']       = $row['parent_name'];//配件的基本件的名称
        $arr[$temp_index]['parent_short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
            sub_str($row['parent_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['parent_name'];//配件的基本件显示的名称
        $arr[$temp_index]['goods_id']          = $row['goods_id'];//配件的商品ID
        $arr[$temp_index]['goods_title']        = $row['goods_title'];//配件的名称
        $arr[$temp_index]['short_name']        = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
            sub_str($row['goods_title'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_title'];//配件显示的名称
        $arr[$temp_index]['fittings_price']    = price_format($row['goods_price']);//配件价格
        $arr[$temp_index]['shop_price']        = price_format($row['shop_price']);//配件原价格
        $arr[$temp_index]['goods_thumb']       = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$temp_index]['original_img']       = get_image_path($row['goods_id'], $row['original_img'], true);
        $arr[$temp_index]['goods_img']         = get_image_path($row['goods_id'], $row['goods_img']);
	    $arr[$temp_index]['url_title']         = get_details_link($row['goods_id'],$row['url_title']);
        $temp_index ++;
    }
	// 多语言 fangxin 2013/07/05
	if($cur_lang != $default_lang) {
		if(is_array($arr)) {
			foreach($arr as $key=>$value) {
				$sql = 'SELECT g.*' .
						' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
						" WHERE g.goods_id = '". $value['goods_id'] ."'";
				if($row_lang = $GLOBALS['db']->selectinfo($sql)) {
					$arr[$key]['goods_title']  = $row_lang['goods_title'];
				}
			}
		}
	}
    return $arr;
}



function get_index_order(){
	global $db, $cur_lang, $default_lang;
	//$one_mon = gmstr2time("300 days");
	/* 查询订单 */
	//$sql = "select goods_id from " . ORDERINFO . " where order by order_id desc limit 30";
	//$limId = $db->getOne($sql);
	//$limId = $limId - 20;

    $sql = "SELECT o.country,o.province,u.goods_id,g.goods_name,g.goods_thumb,g.shop_price as goods_price,g.url_title,o.order_id " .
			" FROM " . ORDERINFO . " AS o, " .
			ODRGOODS. " AS u,  ".
			GOODS. " AS g where u.order_id=o.order_id and g.goods_id=u.goods_id and g.is_login = 0 and g.goods_number>0 and o.order_status  > 0 and  o.order_status  < 9 and o.add_time>unix_timestamp(now())-60*60*24*5".
			" Group by o.country,o.province  ORDER BY o.order_id desc ".
			" limit 20";
	$order_list = $GLOBALS['db']->arrQuery($sql);
	$region_arr = area_list();
	foreach($order_list as $key => $row){
		$order_list[$key]['url_title']  = get_details_link($row['goods_id'],$row['url_title']);
		$order_list[$key]['goods_name'] = sub_str($row['goods_name'], 40);
		$order_list[$key]['goods_full_name'] = $row['goods_name'];
		$order_list[$key]['country']   =  empty($region_arr[$row['country']])?'':$region_arr[$row['country']]['region_name'];
		$order_list[$key]['goods_thumb']   =  get_image_path($row['goods_id'], $row['goods_thumb'], true);
	}

	// 多语言 fangxin 2013/07/05
	if($cur_lang != $default_lang) {
		if(is_array($order_list)) {
			foreach($order_list as $key=>$value) {
				$sql = 'SELECT g.*' .
						' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
						" WHERE g.goods_id = '". $value['goods_id'] ."'";
				if($row_lang = $GLOBALS['db']->selectinfo($sql)) {
					$order_list[$key]['goods_name']  = sub_str($row_lang['goods_title'], 40);
					$order_list[$key]['goods_full_name']  = $row_lang['goods_title'];
				}
			}
		}
	}

	return $order_list;
}




function get_price_nav_arr($children,$cat_id,$typeArray){
	    global $db;
		$where_str = "g.is_on_sale = 1  AND g.is_delete = 0 AND ($children or " . get_extension_goods($children) . ")  and g.is_alone_sale = 1 ";
		$sql = "SELECT g.shop_price FROM ".GOODS." as g where $where_str group by shop_price order by shop_price";
		//echo $sql;
		$Price_Arr = $db->arrQuery($sql);
		//print_r($Price_Arr);
		$price_num = count($Price_Arr);
		$looptimes = 6;
		if ($price_num<2)
		   $looptimes = 2;
		$pp = ceil($price_num/5);
		$format_arr = array();
		if (!empty($Price_Arr)){
			for ($i=1;$i<$looptimes;$i++){
				$Price1 = empty($Price_Arr[($i-1)*$pp]['shop_price'])?$Price_Arr[$price_num-1]['shop_price']:$Price_Arr[($i-1)*$pp]['shop_price'];
				$Price2 = empty($Price_Arr[$i*$pp]['shop_price'])?$Price_Arr[$price_num-1]['shop_price']:$Price_Arr[$i*$pp]['shop_price'];
				if ($Price1 != $Price2) {
					if ($i == 1 )$Price1 = ($Price1-1)<0?0.01:$Price1-1;
					$Price1 = round($Price1,2);
					$Price2 = round($Price2,2);
					$format_arr[$i] = array('0'=>$Price1,'1'=>$Price2);
					$format_arr[$i]['url'] = '/'.title_to_url($typeArray[$cat_id]["cat_name"]).'-'.$cat_id.'-'.$Price1.'-'.$Price2.'-Wholesale.html';
					$format_arr[$i]['cat_name'] =  $typeArray[$cat_id]["cat_name"];;

				}
			}
		}
	return $format_arr;
}

//价格区间 fangxin 2013/08/13
function get_price_nav_arr_att($children = false,$cat_id,$typeArray = false,$search_goods_attr='',$p_num=0){

        if ($children === false) {//通过缓存获取分类价格区间by mashanling on 2011-11-18
            //$cat_data_file = CATEGORY_DATA_CACHE_PATH . $cat_id . '/price_group.php';
            //$price_group   = file_exists($cat_data_file) ? include($cat_data_file) : false;
            $price_group = read_static_cache('price_group', CATEGORY_DATA_CACHE_PATH . $cat_id);
            if(is_array($price_group))
            {
                foreach ($price_group as $key => $value)
                {
                    if($search_goods_attr && $key == $p_num)
                    {
                        $price_group[$key]['url'] = '/'.$search_goods_attr.creat_nav_url($typeArray[$cat_id]['url_title'], $typeArray[$cat_id]["cat_id"], !$typeArray[$cat_id]['parent_id']);
                    }
                    elseif ($search_goods_attr && $key != $p_num)
                    {
                        $price_group[$key]['url'] =  '/'.$search_goods_attr.$value['url'];
                    }
                    elseif(!$search_goods_attr && $key == $p_num)
                    {
                        $price_group[$key]['url'] =  creat_nav_url($typeArray[$cat_id]['url_title'], $typeArray[$cat_id]["cat_id"], !$typeArray[$cat_id]['parent_id']);
                    }
                    else
                    {
                        $price_group[$key]['url'] =  $value['url'];
                    }
                }
            }
            else
            {
                $price_group = array();
            }

            return $price_group;
        }

        global $db;
        $where_str = "g.is_on_sale = 1  AND g.is_delete = 0 AND ($children or " . get_extension_goods($children) . ")  and g.is_alone_sale = 1 ";
        $sql = "SELECT g.shop_price FROM ".GOODS." as g where $where_str group by shop_price order by shop_price";
        //echo $sql;
        $Price_Arr = $db->arrQuery($sql);
        //print_r($Price_Arr);
        $price_num = count($Price_Arr);
        $looptimes = 6;
        if ($price_num<2)
           $looptimes = 2;
        $pp = ceil($price_num/5);
        $format_arr = array();
        if (!empty($Price_Arr)){
            for ($i=1;$i<$looptimes;$i++){
                $Price1 = empty($Price_Arr[($i-1)*$pp]['shop_price'])?$Price_Arr[$price_num-1]['shop_price']:$Price_Arr[($i-1)*$pp]['shop_price'];
                $Price2 = empty($Price_Arr[$i*$pp]['shop_price'])?$Price_Arr[$price_num-1]['shop_price']:$Price_Arr[$i*$pp]['shop_price'];
                if($i>1){
                                        $price1 = intval($price1)+0.99;
                                }
                                $price2 = intval($price2)+0.99;
                                if ($Price1 != $Price2) {
                    if ($i == 1 )$Price1 = ($Price1-1)<0?0.01:$Price1-1;
                    $Price1 = round($Price1,2);
                    $Price2 = round($Price2,2);
                    $format_arr[$i] = array('0'=>$Price1,'1'=>$Price2);
                    $format_arr[$i]['url'] = '/'.title_to_url($typeArray[$cat_id]["cat_name"]).'-'.$cat_id.'-Wholesale.html';
                    $format_arr[$i]['cat_name'] =  $typeArray[$cat_id]["cat_name"];;

                }
            }
        }
    return $format_arr;
}

//通过价格区段获取最高价和最低价
function get_max_price($cat_id,$p_num=0) {
//通过缓存获取分类价格区间by mashanling on 2011-11-18
    //$cat_data_file = CATEGORY_DATA_CACHE_PATH . $cat_id . '/price_group.php';
    //$price_group   = file_exists($cat_data_file) ? include($cat_data_file) : false;
    $price_group = read_static_cache('price_group', CATEGORY_DATA_CACHE_PATH . $cat_id);
    $price = array();
    if(is_array($price_group)) {
       $price['price_max'] = $price_group[$p_num][1];
       $price['price_min'] = $price_group[$p_num][0];
    }
    else {
        $price = array();
    }
    return $price;
}

//查属性
function get_properties($goods_id , $goods_name = '' , $cat_name= '' ){
	global $db;
	$PArr = array();
	$arr  = array();
    $sql = "SELECT a.attr_id, a.attr_name,a.attr_type, ".
                "g.goods_attr_id, g.attr_value, g.attr_price " .
            'FROM ' .GATTR . ' AS g ' .
            'LEFT JOIN ' . ATTR . ' AS a ON a.attr_id = g.attr_id ' .
            "WHERE g.goods_id = '$goods_id' " .
            'ORDER BY a.sort_order, g.attr_value';
	$PArr = $db->arrQuery($sql);
	foreach($PArr as $row){
			$linkurl = $goods_name?$row['attr_value'].' '.$goods_name:'';
            $arr[$row['attr_id']]['attr_type'] = $row['attr_type'];
            $arr[$row['attr_id']]['name']     = $row['attr_name'];
            $arr[$row['attr_id']]['values'][] = array(
													'label'        => $row['attr_value'],
													'price'        => $row['attr_price'],
													'format_price' => price_format(abs($row['attr_price']), false),
													'id'           => $row['goods_attr_id'],
													'url'          => get_details_link($goods_id,$linkurl,$row['goods_attr_id']));
	}
	return $arr;
}

//评论星级 对应的图标
function showRate($rate){
	if(!empty($rate)&&is_numeric($rate)){
		if(($rate*10)%10 == 0)
			return intval($rate);
		else
			return intval($rate)."_5";
	}
}

//取得指定产品ＩＤ的评论打分
function get_review_rate($goods_id){
	global $db;
	$sql = "select count(*) as review_count,sum(rate_overall) as review_rate from ".REVIEW.' where is_pass=1 and adddate < '.gmtime().' and goods_id='.$goods_id;
	$review_stat= $db->selectInfo($sql);
	$review['review_count'] = $review_stat['review_count'];
	$review['review_rate'] = round($review_stat['review_rate'],2);
	if($review['review_count']>0){
		$review['avg_rate'] = round($review['review_rate']/$review['review_count'],1);
		$review['avg_rate'] = number_format($review['avg_rate'],1);

		//echo $review['avg_rate'];
	}else{
		$review['avg_rate'] = 0;
	}

	$review['avg_rate_img'] = showRate($review['avg_rate']);
	return $review;
}



//取得指定多个产品ＩＤ的评论打分
function get_mult_goods_review_rate($goods_ids){
	global $db;
	if(empty($goods_ids))return ;
	$sql = "select count(*) as review_count,sum(rate_overall) as review_rate from ".REVIEW." where is_pass=1 and adddate < '.gmtime().' and goods_id in($goods_ids) group by goods_id  ";
	$review_stat= $db->selectInfo($sql);
	$review['review_count'] = $review_stat['review_count'];
	$review['review_rate'] = round($review_stat['review_rate'],2);
	if($review['review_count']>0){
		$review['avg_rate'] = round($review['review_rate']/$review['review_count'],1);
		$review['avg_rate'] = number_format($review['avg_rate'],1);

		//echo $review['avg_rate'];
	}else{
		$review['avg_rate'] = 0;
	}

	$review['avg_rate_img'] = showRate($review['avg_rate']);
	return $review;
}



//取指定产品ID的评论
function get_review($goods_id,$page=1,$page_size=3,$where=''){
	global $db;
	$review =array();
	$from_row = ($page-1)*$page_size;

	$sql = "select count(*) as review_count,sum(rate_overall) as review_rate from ".REVIEW.' where is_pass=1 and adddate < '.gmtime().' and goods_id='.$goods_id;
	$review_stat= $db->selectInfo($sql);
	$review['review_count'] = $review_stat['review_count'];
	$review['review_rate'] = round($review_stat['review_rate'],2);
	if($review['review_count']>0){
		$review['avg_rate'] = round($review['review_rate']/$review['review_count'],1);
		$review['avg_rate'] = number_format($review['avg_rate'],1);
		//echo $review['avg_rate'];
	}else{
		$review['avg_rate'] = 0;
	}

	$review['avg_rate_img'] = showRate($review['avg_rate']);
	if($where)
	{
		$sql = 'select  * from '.REVIEW .' where is_pass=1  and adddate < '.gmtime().' and goods_id='.$goods_id." and ". $where ." order by is_top desc, adddate desc limit $from_row,$page_size";
	}
	else
	{
		$sql = 'select  * from '.REVIEW .' where is_pass=1 and adddate < '.gmtime().' and goods_id='.$goods_id." order by is_top desc, adddate desc limit $from_row,$page_size";
	}
	$review_list = $db->arrQuery($sql);
	foreach ($review_list as $k=>$v){
		$review_list[$k]['adddate']=local_date('M-d/Y h:i:s',$review_list[$k]['adddate']);
		$review_list[$k]['subject'] = str_replace('\\', '', stripslashes($review_list[$k]['subject']));
		$review_list[$k]['pros']    = str_replace('\\', '', stripslashes($review_list[$k]['pros']));
		$review_list[$k]['cons']    = str_replace('\\', '', stripslashes($review_list[$k]['cons']));
		$review_list[$k]['other_thoughts'] = str_replace('\\', '', stripslashes($review_list[$k]['other_thoughts']));
		$sql = 'select * from '.REVIEW_PIC.' WHERE rid ='.$v['rid'];
		$review_list[$k]['pic'] = $db->arrQuery($sql);
		if(count($review_list[$k]['pic'])==0) $review_list[$k]['pic']="";
		$sql = 'select * from '.REVIEW_VIDEO.' WHERE rid ='.$v['rid'];
		$review_list[$k]['video'] = $db->arrQuery($sql);
		if(count($review_list[$k]['video'])==0) $review_list[$k]['video']="";
		$sql = 'select * from '.REVIEW_REPLY.' WHERE is_pass=1 and rid ='.$v['rid'];
		$review_list[$k]['reply'] = $db->arrQuery($sql);
		if(count($review_list[$k]['reply'])==0) $review_list[$k]['reply']="";
	}
	$review['review_list'] = $review_list;
	return $review;
}

//取指定ID的评论
function get_inquiry($goods_id,$page=1,$page_size=5){
	global $db;
	$inquiry = array();
	$from_row = ($page-1)*$page_size;
	$sql = "select count(*) as inquiry_count from ".PRO_INQUIRY.' where is_pass=1 and goods_id='.$goods_id;
	$inquiry_stat= $db->selectInfo($sql);
	$inquiry['inquiry_count'] = $inquiry_stat['inquiry_count'];
	//$review['review_rate'] = $review_stat['review_rate'];
	$sql = 'select  * from '.PRO_INQUIRY .' where is_pass=1 and goods_id='.$goods_id." order by adddate desc limit $from_row,$page_size  ";
	$inquiry_list = $db->arrQuery($sql);
	foreach ($inquiry_list as $k=>$v){
		$inquiry_list[$k]['adddate']=local_date('M-d/Y h:i:s',$inquiry_list[$k]['adddate']);
	}
	$inquiry['inquiry_list'] = $inquiry_list;
	return $inquiry;

}
/**
 * 获得指定
 *
 * @param   $goods_id  要排除的产品列表
 * @return  array
 */
function get_hot_product_by_cat_id($cat_id,$is_rand = '',$limit_count=5,$goods_id=0)
{

	if($cat_id){
	    $sql = 'SELECT g.goods_id, g.goods_title, g.goods_thumb,g.goods_number,g.url_title, g.goods_grid,g.cat_id, g.shop_price AS org_price, ' .
	                'g.shop_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
	            ' FROM ' . GOODS . ' AS g   ' .
	            "WHERE  g.is_on_sale = 1 and g.is_login = 0 and g.is_alone_sale = 1 and g.is_delete = 0 AND g.cat_id in('$cat_id') and g.goods_id not in($goods_id)".
	            " order by $is_rand if( goods_number =0, 0, 1 ) DESC, sale_count desc,sort_order,click_count desc,goods_id desc LIMIT $limit_count";


	    $res = $GLOBALS['db']->query($sql);
	    $arr = array();
	    while ($row = $GLOBALS['db']->fetchRow($res))
	    {
	        $arr[$row['goods_id']]['cat_id']        = $row['cat_id'];
	        $arr[$row['goods_id']]['cat_id']        = $row['cat_id'];
	        $arr[$row['goods_id']]['goods_title']   = $row['goods_title'];
	        $arr[$row['goods_id']]['goods_number']  = $row['goods_number'];
	        $arr[$row['goods_id']]['short_name']    = sub_str($row['goods_title'],30);
	        $arr[$row['goods_id']]['goods_thumb']   = get_image_path($row['goods_id'], $row['goods_thumb'], true);
	        $arr[$row['goods_id']]['goods_grid']    = get_image_path($row['goods_id'], $row['goods_grid']);
			$arr[$row['goods_id']]['promote_price'] = price_format($row['promote_price']);
	        $arr[$row['goods_id']]['shop_price']    = price_format($row['shop_price']);
 	        $arr[$row['goods_id']]['url_title']     = get_details_link($row['goods_id'],$row['url_title']);
	        if ($row['promote_price'] > 0)
	        {
	            $arr[$row['goods_id']]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
	            $arr[$row['goods_id']]['formated_promote_price'] = price_format($arr[$row['goods_id']]['promote_price']);
	        }
	        else
	        {
	            $arr[$row['goods_id']]['promote_price'] = 0;
	        }
	    }
	}else {
		$arr='';
	}
    return $arr;
}

/**
 * 获取属性查找信息
 *
 * @param   $typeArray  	商品分类信息
 * @param   $cat_id   		当前分类ID
 * @param   $price_min  	价格区间段最小价格
 * @param   $price_max  	价格区间段最大价格
 * @param   $p_num  		第几个价格区间段
 * @param   $search_info    商品属性查找参数数组
 * @return  array
 */
function get_search_attr_info($typeArray,$cat_id,$price_min,$price_max,$p_num,$search_info)
{
	$template_attr_info = array();

	//获取模板信息
	$template_info_arr_all = read_static_cache('search_attr_template',2);	//查找模板信息
	$template_info = $template_info_arr_all[$typeArray[$cat_id]['template_id']];
	$i = 0;
	$k = 0;

	//是否显示所有的查找模板 还是 最多显示前5个查找模板
	$template_attr_info['all_display_template'] = 0 ;		//默认不显示所有的查找模板
	if(empty($template_info['attr_list']))return ;
	foreach ($template_info['attr_list'] as $key => $value)
	{
		$template_attr_info['search_templae'][$value['search_attr_id']] = $value;

		//获得清除某一个查找模板的所有查找属性的URL
		$template_attr_info['search_templae'][$value['search_attr_id']]['see_all_display'] = 0 ;		//不显示查找模板的所有查找属性按扭
		$template_attr_info['search_templae'][$value['search_attr_id']]['num'] = $k ;		//第几个查找模板
		$search_goods_attr_key_temp = $search_info['search_split'];
		if(!empty($search_info['search_goods_attr_key'][$value['search_attr_brief']]))
		{
			$search_goods_attr_key_temp = array_diff($search_goods_attr_key_temp,$search_info['search_goods_attr_key'][$value['search_attr_brief']]);
			$template_attr_info['search_templae'][$value['search_attr_id']]['see_all_display'] = 1;	//不显示查找模板的所有查找属性按扭（当前模板有选择了查找属性，显示此按扭）
		}

		$see_all_temp = empty($search_goods_attr_key_temp) ? '' : '/'.implode('~',$search_goods_attr_key_temp);

		/*if($price_max>0 && $price_min>0)
		{
			$template_attr_info['search_templae'][$value['search_attr_id']]['see_all'] = $see_all_temp.'/'.title_to_url($typeArray[$cat_id]["cat_name"]).'-'.$cat_id.'-'.$price_min.'-'.$price_max.'-Wholesale.html?price_num='.$p_num;
		}
		else
		{*/
			$template_attr_info['search_templae'][$value['search_attr_id']]['see_all'] = $see_all_temp.'/'.title_to_url($typeArray[$cat_id]["cat_name"]).'-c-'.$cat_id.'.html';
		//}

		foreach ($value['search_value'] as $key1=>$value2)
		{
			$template_attr_info['search_templae'][$value['search_attr_id']]['search_key'][$key1]['keyword'] = $value2;	//查询词
			$value3 = str_replace("/","^",$value2);		//实际查找值

			//是否已经有添加查找
			if(!empty($search_info['search_goods_attr'][$value['search_attr_brief']]) && in_array($value3,$search_info['search_goods_attr'][$value['search_attr_brief']]))
			{
				$template_attr_info['search_templae'][$value['search_attr_id']]['search_key'][$key1]['style'] = 1;	//已经添加查找
				$current_word = $value['search_attr_brief'].'_'.$value3;							//当前查询词
				$search_split_str = implode('~',array_diff( $search_info['search_split'],array($current_word)));		//去除已经添加的查询词
				$search_goods_attr_url = $search_split_str;

				// 是否展开显示所有查找属性
				if($template_attr_info['all_display_template'] ==0 && $k >=5)
				{
					$template_attr_info['all_display_template'] =1;
				}
			}
			else
			{
				$template_attr_info['search_templae'][$value['search_attr_id']]['search_key'][$key1]['style'] = 0;	//还没有添加查找
				$search_goods_attr_url = empty($search_info['search_split'])? $value['search_attr_brief'].'_'.$value3 : implode('~',$search_info['search_split']).'~'.$value['search_attr_brief'].'_'.$value2;
			}

			$search_goods_attr_url = empty($search_goods_attr_url) ? '' : str_replace(" ","+",$search_goods_attr_url);
			$search_goods_attr_url = empty($search_goods_attr_url) ? '' : str_replace("/","^",$search_goods_attr_url);
			$search_goods_attr_url = empty($search_goods_attr_url) ? '' : '/'. $search_goods_attr_url;

			/*if($price_max>0 && $price_min>0)
			{
				$template_attr_info['search_templae'][$value['search_attr_id']]['search_key'][$key1]['url'] = $search_goods_attr_url.'/'.title_to_url($typeArray[$cat_id]["cat_name"]).'-'.$cat_id.'-'.$price_min.'-'.$price_max.'-Wholesale.html?price_num='.$p_num;
			}
			else
			{*/
				$template_attr_info['search_templae'][$value['search_attr_id']]['search_key'][$key1]['url'] = $search_goods_attr_url.'/'.title_to_url($typeArray[$cat_id]["cat_name"]).'-c-'.$cat_id.'.html';
			//}

			if(!empty($search_info['search_goods_attr'][$value['search_attr_brief']]) && in_array($value3,$search_info['search_goods_attr'][$value['search_attr_brief']]))
			{
				$template_attr_info['select_value'][$i]['keyword'] = $value2;
				$template_attr_info['select_value'][$i]['url'] = $template_attr_info['search_templae'][$value['search_attr_id']]['search_key'][$key1]['url'];
				$i++;
			}
		}
		$k++;
	}
	return $template_attr_info;
}
/**
 * Enter description here...
 *
 * @param unknown_type $cat_id
 * @param unknown_type $is_rand
 * @param unknown_type $limit_count
 * @param unknown_type $goods_id
 * @return unknown
 */
function get_gifts_list_cart($cart_goods='',$available_amount=0,$limit_count = 20)
{
	$gifts = read_static_cache('gifts_c_key',2);  //赠品类别
	if(empty($cart_goods)) $cart_goods = cart_goods_g();
	if(empty($cart_goods))return false;
	//print_r($cart_goods);
	//if(empty($available_amount))$available_amount = get_cart_available_amount($cart_goods);

	$gifts_in_cart = gifts_in_cart($cart_goods);
	if($gifts_in_cart>0) return false;




	$last_gift_goods_id = empty($_SESSION['last_gift_goods_id'])?0:$_SESSION['last_gift_goods_id'];
	    $sql = 'SELECT g.goods_id,g.gifts_id,g.goods_name, g.goods_title, g.goods_thumb,g.goods_number,g.url_title, g.goods_grid,g.cat_id, g.shop_price AS org_price, ' .
	                'g.shop_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
	            ' FROM ' . GOODS . ' AS g inner join  ' .GIFTS.' f on g.gifts_id = f.gifts_id '.
	            "WHERE  g.is_on_sale = 1 and g.is_login = 0 and g.is_alone_sale = 1 and g.is_delete = 0  and g.gifts_id in(select gifts_id from ".GIFTS." where 1=1";
	     if(!empty($available_amount))$sql .=" and need_money <=$available_amount ";
	    //if(!empty($gift_goods_id))$sql .=" and goods_id<>$gift_goods_id";

	   	 $sql .=") order by if( goods_id =$last_gift_goods_id, 1, 0 ) DESC";
	     if(empty($available_amount))$sql .=",need_money desc";
	     $sql .=",week2sale desc LIMIT $limit_count";


	    $res = $GLOBALS['db']->query($sql);
	    $arr = array();
	    while ($row = $GLOBALS['db']->fetchRow($res))
	    {
	        $arr[$row['goods_id']]['gifts_name']     = $row['gifts_id']&&!empty($gifts[$row['gifts_id']])?$gifts[$row['gifts_id']]['gifts_name']:'';
	        $arr[$row['goods_id']]['goods_title']   = $row['goods_title'];
	        $arr[$row['goods_id']]['goods_name']   = $row['goods_name'];
	        $arr[$row['goods_id']]['goods_id']   = $row['goods_id'];
	        $arr[$row['goods_id']]['goods_number']   = $row['goods_number'];
	        $arr[$row['goods_id']]['short_name']   = sub_str($row['goods_title'],30);
	        $arr[$row['goods_id']]['goods_thumb']  = get_image_path($row['goods_id'], $row['goods_thumb'], true);
	        $arr[$row['goods_id']]['goods_grid']    = get_image_path($row['goods_id'], $row['goods_grid']);
	        $arr[$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
	       // $arr[$row['goods_id']]['shop_price']   = price_format($row['shop_price']);
	       $arr[$row['goods_id']]['url_title']           = get_details_link($row['goods_id'],$row['url_title']);

	        if ($row['promote_price'] > 0)
	        {
	            $arr[$row['goods_id']]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
	            $arr[$row['goods_id']]['formated_promote_price'] = price_format($arr[$row['goods_id']]['promote_price']);
	        }
	        else
	        {
	            $arr[$row['goods_id']]['promote_price'] = 0;
	        }
	    }

//print_r($arr);
    return $arr;
}

//
?>