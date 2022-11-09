<?php
$lang_arr = array('ru'=>array('code'=>'ru','code_cn'=>'俄语')); //语种设置
if (!defined('INI_WEB')){die('访问拒绝');}

/**
 * 取得推荐类型列表
 * @return  array   推荐类型列表
 */
function get_intro_list()
{
    return array(
        'is_best'    => $GLOBALS['_LANG']['is_best'],
        'is_new'     => $GLOBALS['_LANG']['is_new'],
        'is_hot'     => $GLOBALS['_LANG']['is_hot'],
        'is_promote' => $GLOBALS['_LANG']['is_promote'],
        'all_type' => $GLOBALS['_LANG']['all_type'],
    );
}

/**
 * 取得重量单位列表
 * @return  array   重量单位列表
 */
function get_unit_list()
{
    return array(
        '0.001' => "克",
        '1'     => "千克",
    );
}


/**
 * 插入或更新商品属性
 * @param   int     $goods_id       商品编号
 * @param   array   $id_list        属性编号数组
 * @param   array   $is_spec_list   是否规格数组 'true' | 'false'
 * @param   array   $value_price_list   属性值数组
 */
function handle_goods_attr($goods_id, $id_list, $is_spec_list, $value_price_list)
{
    /* 循环处理每个属性 */
    foreach ($id_list AS $key => $id)
    {
        $is_spec = $is_spec_list[$key];
        if ($is_spec == 'false')
        {
            $value = $value_price_list[$key];
            $price = '';
        }
        else
        {
            $value_list = array();
            $price_list = array();
            if ($value_price_list[$key])
            {
                $vp_list = explode(chr(13), $value_price_list[$key]);
                foreach ($vp_list AS $v_p)
                {
                    $arr = explode(chr(9), $v_p);
                    $value_list[] = $arr[0];
                    $price_list[] = $arr[1];
                }
            }
            $value = join(chr(13), $value_list);
            $price = join(chr(13), $price_list);
        }

        // 插入或更新记录
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE goods_id = '$goods_id' AND attr_id = '$id'";
        if ($GLOBALS['db']->getOne($sql) > 0)
        {
            $sql = "UPDATE " . $GLOBALS['ecs']->table('goods_attr') . " SET " .
                    "attr_value = '$value', " .
                    "attr_price = '$price' " .
                    "WHERE goods_id = '$goods_id' " .
                    "AND attr_id = '$id' LIMIT 1";
        }
        else
        {
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('goods_attr') . " (goods_id, attr_id, attr_value, attr_price) " .
                    "VALUES ('$goods_id', '$id', '$value', '$price')";
        }
        $GLOBALS['db']->query($sql);
    }
}

/**
 * 保存某商品的会员价格
 * @param   int     $goods_id   商品编号
 * @param   array   $rank_list  等级列表
 * @param   array   $price_list 价格列表
 * @return  void
 */
function handle_member_price($goods_id, $rank_list, $price_list)
{
    /* 循环处理每个会员等级 */
    foreach ($rank_list AS $key => $rank)
    {
        /* 会员等级对应的价格 */
        $price = $price_list[$key];

        // 插入或更新记录
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('member_price') .
               " WHERE goods_id = '$goods_id' AND user_rank = '$rank'";
        if ($GLOBALS['db']->getOne($sql) > 0)
        {
            /* 如果会员价格是小于0则删除原来价格，不是则更新为新的价格 */
            if ($price < 0)
            {
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('member_price') .
                       " WHERE goods_id = '$goods_id' AND user_rank = '$rank' LIMIT 1";
            }
            else
            {
                $sql = "UPDATE " . $GLOBALS['ecs']->table('member_price') .
                       " SET user_price = '$price' " .
                       "WHERE goods_id = '$goods_id' " .
                       "AND user_rank = '$rank' LIMIT 1";
            }
        }
        else
        {
            if ($price == -1)
            {
                $sql = '';
            }
            else
            {
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('member_price') . " (goods_id, user_rank, user_price) " .
                       "VALUES ('$goods_id', '$rank', '$price')";
            }
        }

        if ($sql)
        {
            $GLOBALS['db']->query($sql);
        }
    }
}

/**
 * 保存某商品的扩展分类
 * @param   int     $goods_id   商品编号
 * @param   array   $cat_list   分类编号数组
 * @return  void
 */
function handle_other_cat($goods_id, $cat_list)
{
    /* 查询现有的扩展分类 */
    $sql = "SELECT cat_id FROM " . GOODSCAT .
            " WHERE goods_id = '$goods_id'";
    $exist_list = $GLOBALS['db']->getCol($sql);

    /* 删除不再有的分类 */
    $delete_list = array_diff($exist_list, $cat_list);
    if ($delete_list)
    {
        $sql = "DELETE FROM " . GOODSCAT .
                " WHERE goods_id = '$goods_id' " .
                "AND cat_id " . db_create_in($delete_list);
        $GLOBALS['db']->query($sql);
    }

    /* 添加新加的分类  */
    $add_list = array_diff($cat_list, $exist_list, array(0));
    foreach ($add_list AS $cat_id)
    {
        // 插入记录
		if ($cat_id){
			$sql = "INSERT INTO " . GOODSCAT .
					" (goods_id, cat_id) " .
					"VALUES ('$goods_id', '$cat_id')";
			 $GLOBALS['db']->query($sql);
		}
    }
}

/**
 * 保存某商品的关联商品
 * @param   int     $goods_id
 * @return  void
 */
function handle_link_goods($goods_id)
{
    $sql = "UPDATE " . $GLOBALS['ecs']->table('link_goods') . " SET " .
            " goods_id = '$goods_id' " .
            " WHERE goods_id = '0'" .
            " AND admin_id = '$_SESSION[admin_id]'";
    $GLOBALS['db']->query($sql);

    $sql = "UPDATE " . $GLOBALS['ecs']->table('link_goods') . " SET " .
            " link_goods_id = '$goods_id' " .
            " WHERE link_goods_id = '0'" .
            " AND admin_id = '$_SESSION[admin_id]'";
    $GLOBALS['db']->query($sql);
}

/**
 * 保存某商品的配件
 * @param   int     $goods_id
 * @return  void
 */
function handle_group_goods($goods_id)
{
    $sql = "UPDATE " . GROUPGOODS . " SET " .
            " parent_id = '$goods_id' " .
            " WHERE parent_id = '0'";
    $GLOBALS['db']->query($sql);
}

/**
 * 保存某商品的关联文章
 * @param   int     $goods_id
 * @return  void
 */
function handle_goods_article($goods_id)
{
    $sql = "UPDATE " . $GLOBALS['ecs']->table('goods_article') . " SET " .
            " goods_id = '$goods_id' " .
            " WHERE goods_id = '0'" .
            " AND admin_id = '$_SESSION[admin_id]'";
    $GLOBALS['db']->query($sql);
}

/**
 * 保存某商品的相册图片
 * @param   int     $goods_id
 * @param   array   $image_files
 * @param   array   $image_descs
 * @return  void
 */
function handle_gallery_image($goods_id, $image_files, $image_descs)
{
    /* 是否处理缩略图 */
    $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0)? false : true;
    foreach ($image_descs AS $key => $img_desc)
    {
        /* 是否成功上传 */
        $flag = false;
        if (isset($image_files['error']))
        {
            if ($image_files['error'][$key] == 0)
            {
                $flag = true;
            }
        }
        else
        {
            if ($image_files['tmp_name'][$key] != 'none')
            {
                $flag = true;
            }
        }

        if ($flag)
        {
            // 生成缩略图
            if ($proc_thumb)
            {
                $thumb_url = $GLOBALS['image']->make_thumb($image_files['tmp_name'][$key], $GLOBALS['_CFG']['thumb_width'],  $GLOBALS['_CFG']['thumb_height']);
                $thumb_url = is_string($thumb_url) ? $thumb_url : '';  //100

                $img_url = $GLOBALS['image']->make_thumb($image_files['tmp_name'][$key], $GLOBALS['_CFG']['image_width'],  $GLOBALS['_CFG']['image_height']);
                $img_url = is_string($img_url) ? $img_url : '';        //250

            }

            $upload = array(
                'name' => $image_files['name'][$key],
                'type' => $image_files['type'][$key],
                'tmp_name' => $image_files['tmp_name'][$key],
                'size' => $image_files['size'][$key],
            );
            if (isset($image_files['error']))
            {
                $upload['error'] = $image_files['error'][$key];
            }
            $img_original = $GLOBALS['image']->upload_image($upload);
            if ($img_original === false)
            {
                sys_msg($GLOBALS['image']->error_msg(), 1, array(), false);
            }


            //$img_url = $img_original;

            if (!$proc_thumb)
            {
                $thumb_url = $img_original;
            }
            // 如果服务器支持GD 则添加水印
            if ($proc_thumb && gd_version() > 0)
            {
               // $pos        = strpos(basename($img_original), '.');
               // $newname    = dirname($img_original) . '/' . $GLOBALS['image']->random_filename() . substr(basename($img_original), $pos);
               // copy('../' . $img_original, '../' . $newname);
               // $img_url    = $newname;

                $GLOBALS['image']->add_watermark('../'.$img_original,'',$GLOBALS['_CFG']['watermark'], '', $GLOBALS['_CFG']['watermark_alpha']);
            }

            /* 重新格式化图片名称 */
            $img_original = reformat_image_name('gallery', $goods_id, $img_original, 'source');
            $img_url = reformat_image_name('gallery', $goods_id, $img_url, 'goods');
            $thumb_url = reformat_image_name('gallery_thumb', $goods_id, $thumb_url, 'thumb');
            $sql = "INSERT INTO " . GGALLERY . " (goods_id, img_url, img_desc, thumb_url, img_original) " .
                    "VALUES ('$goods_id', '$img_url', '$img_desc', '$thumb_url', '$img_original')";
            $GLOBALS['db']->query($sql);
        }
    }
}

/**
 * 修改商品某字段值
 * @param   string  $goods_id   商品编号，可以为多个，用 ',' 隔开
 * @param   string  $field      字段名
 * @param   string  $value      字段值
 * @return  bool
 */
function update_goods($goods_id, $field, $value)
{
    if ($goods_id)
    {	$shop_time = '';
        if($field == 'is_on_sale' && $value ==1){//如果是上架更新上架时间
            $GLOBALS['db']->query("update ".GOODS_STATE." set sale_time = '".gmtime()."'WHERE goods_id " . db_create_in($goods_id));
        }
        $sql = "UPDATE " . GOODS .
                " SET $field = '$value' , last_update = '". gmtime() ."' , update_user = '". $_SESSION["WebUserInfo"]["sa_user"] ."'  " .
                "WHERE goods_id " . db_create_in($goods_id);
        return $GLOBALS['db']->query($sql);
    }
    else
    {
        return false;
    }
}

/**
 * 从回收站删除多个商品
 * @param   mix     $goods_id   商品id列表：可以逗号格开，也可以是数组
 * @return  void
 */
function delete_goods($goods_id)
{
    if (empty($goods_id))
    {
        return;
    }

    /* 取得有效商品id */
    $sql = "SELECT DISTINCT goods_id FROM " . GOODS .
            " WHERE goods_id " . db_create_in($goods_id) . " AND is_delete = 1";
    $goods_id = $GLOBALS['db']->getCol($sql);
    if (empty($goods_id))
    {
        return;
    }

    /* 删除商品图片和轮播图片文件 */
    $sql = "SELECT goods_thumb, goods_img,goods_grid, original_img, url_title,cat_id " .
            "FROM " . GOODS .
            " WHERE goods_id " . db_create_in($goods_id);
    $res = $GLOBALS['db']->query($sql);
    while ($goods = $GLOBALS['db']->fetchRow($res))
    {
        /*if (!empty($goods['goods_thumb']))
        {
            @unlink('../' . $goods['goods_thumb']);
        }
        if (!empty($goods['goods_grid']))
        {
            @unlink('../' . $goods['goods_grid']);
        }
        if (!empty($goods['goods_img']))
        {
            @unlink('../' . $goods['goods_img']);
        }
        if (!empty($goods['original_img']))
        {
            @unlink('../' . $goods['original_img']);
        }*/
        
        //删除商品封面和相册图片
        $syn_gallery_image_ser1 = serialize(array());
	    $post_data="goods_thumb=".$goods['goods_thumb']."&goods_grid=".$goods['goods_grid']."&goods_img=".$goods['goods_img']."&original_img=".$goods['original_img']."&syn_gallery_image=$syn_gallery_image_ser1&action=del";
	    echo post_image_info(IMG_API_PATH,$post_data);//到图片库删除相册
        
		$path_dir = ROOT_PATH .GOODS_DIR.$goods['cat_id'].'/'.$goods['url_title'];
		if (file_exists($path_dir)){
			@unlink($path_dir);
		}
		
    }

    /* 删除商品 */
    $sql = "DELETE FROM " . GOODS .
            " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);
    
     /* 删除商品扩展表 */
    //$sql = "DELETE FROM " . GOODS_EXTEND . 
    		" WHERE goods_id " . db_create_in($goods_id);
    //$GLOBALS['db']->query($sql);
    
    /* 删除商品阶梯价格表 */
    $sql = "DELETE FROM " . VPRICE . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);

    /* 删除商品相册的图片文件 */
    $sql = "SELECT img_url, thumb_url, img_original " .
            "FROM " . GGALLERY .
            " WHERE goods_id " . db_create_in($goods_id);
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
       $syn_gallery_image1[]="img_url@@".$row['img_url']."@@@thumb_url@@".$row['thumb_url']."@@@img_original@@".$row['img_original'];
    }
    
    if(!empty($syn_gallery_image1)){
    	$syn_gallery_image_ser1=serialize($syn_gallery_image1);
	    //删除商品封面和相册图片
	    $post_data="syn_gallery_image=$syn_gallery_image_ser1&action=del";
	    echo post_image_info(IMG_API_PATH,$post_data);//到图片库删除相册
    }

    /* 删除商品相册 */
    $sql = "DELETE FROM " . GGALLERY . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);
	
    $sql = "DELETE FROM " . GROUPGOODS . " WHERE parent_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);
    $sql = "DELETE FROM " . GROUPGOODS . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);
	
    $sql = "DELETE FROM " . GOODSCAT . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);
    /* 删除相关表记录 */
    $sql = "DELETE FROM " . COLLECT . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);
    $sql = "DELETE FROM " . GATTR . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);
    $sql = "DELETE FROM " . COMMENT . " WHERE comment_type = 0 AND id_value " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);
    
    //删除商品点击率
    $sql = "DELETE FROM " . GOODS_HITS . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);
    
    //删除推荐商品
    $sql = "DELETE FROM " . GOODSTUIJIAN . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);
    
    //删除专题活动商品
    //$sql = "DELETE FROM " . SPECIAL_GOODS . " WHERE goods_id = '$goods_id'";
    $GLOBALS['db']->query($sql);
    
    //删除商品咨询表
    $sql = "DELETE FROM " . PRO_INQUIRY . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);
    
    //获得商品评论ID
    $sql = "SELECT rid FROM " . REVIEW . " WHERE goods_id " . db_create_in($goods_id);
    $rid = $GLOBALS['db']->getCol($sql);
    //删除商品评论回复表
    $sql = "DELETE FROM " . REVIEW_REPLY . " WHERE rid " . db_create_in($rid);
    $GLOBALS['db']->query($sql);
    //删除商品评论图片表
    $sql = "DELETE FROM " . REVIEW_PIC . " WHERE rid " . db_create_in($rid);
    $GLOBALS['db']->query($sql);
    //删除商品评论视屏表
    $sql = "DELETE FROM " . REVIEW_VIDEO . " WHERE rid " . db_create_in($rid);
    $GLOBALS['db']->query($sql);
    //删除商品评论主表
    $sql = "DELETE FROM " . REVIEW . " WHERE rid " . db_create_in($rid);
    $GLOBALS['db']->query($sql);
    
    //删除购物车中的商品
    $sql = "DELETE FROM " . CART . " WHERE goods_id " .db_create_in($goods_id);
    $GLOBALS['db']->query($sql);

}

/**
 * 为某商品生成唯一的货号
 * @param   int     $goods_id   商品编号
 * @return  string  唯一的货号
 */
function generate_goods_sn($goods_id)
{
    $goods_sn = $GLOBALS['_CFG']['sn_prefix'] . str_repeat('0', 6 - strlen($goods_id)) . $goods_id;

    $sql = "SELECT goods_sn FROM " . GOODS .
            " WHERE goods_sn LIKE '" . mysql_like_quote($goods_sn) . "%' AND goods_id <> '$goods_id' " .
            " ORDER BY LENGTH(goods_sn) DESC";
    $sn_list = $GLOBALS['db']->getCol($sql);
    if (in_array($goods_sn, $sn_list))
    {
        $max = pow(10, strlen($sn_list[0]) - strlen($goods_sn) + 1) - 1;
        $new_sn = $goods_sn . mt_rand(0, $max);
        while (in_array($new_sn, $sn_list))
        {
            $new_sn = $goods_sn . mt_rand(0, $max);
        }
        $goods_sn = $new_sn;
    }

    return $goods_sn;
}

/**
 * 取得通用属性和某分类的属性，以及某商品的属性值
 * @param   int     $cat_id     分类编号
 * @param   int     $goods_id   商品编号
 * @return  array   规格与属性列表
 */
function get_attr_list($cat_id, $goods_id = 0)
{
    if (empty($cat_id))
    {
        $sql = "SELECT cat_id FROM " .GTYPE. "  WHERE enabled = 1 LIMIT 1 ";
        $cat_id = $GLOBALS['db']->getOne($sql);
    }

    // 查询属性值及商品的属性值
    $sql = "SELECT a.attr_id, a.attr_name, a.attr_input_type, a.attr_type, a.attr_values, v.attr_value, v.attr_price ".
            "FROM " .ATTR. " AS a ".
            "LEFT JOIN " .GATTR. " AS v ".
            "ON v.attr_id = a.attr_id AND v.goods_id = '$goods_id' ".
            "WHERE a.cat_id = " . intval($cat_id) ." OR a.cat_id = 0 ".
            "ORDER BY a.sort_order, a.attr_type, a.attr_id, v.attr_price, v.goods_attr_id";
    $row = $GLOBALS['db']->arrQuery($sql);

    return $row;
}

/**
 * 根据属性数组创建属性的表单
 *
 * @access  public
 * @param   int     $cat_id     分类编号
 * @param   int     $goods_id   商品编号
 * @return  string
 */
function build_attr_html($cat_id, $goods_id = 0)
{
    $attr = get_attr_list($cat_id, $goods_id);
    $html = '<table width="100%" id="attrTable">';
    $spec = 0;

    foreach ($attr AS $key => $val)
    {
        $html .= "<tr><td class='label'>";
        if ($val['attr_type'] == 1 || $val['attr_type'] == 2)
        {
            $html .= ($spec != $val['attr_id']) ?
                "<a href='javascript:;' onclick='addSpec(this)'>[+]</a>" :
                "<a href='javascript:;' onclick='removeSpec(this)'>[-]</a>";
            $spec = $val['attr_id'];
        };

        $html .= "$val[attr_name] ： </td><td><input type='hidden' name='attr_id_list[]' value='$val[attr_id]' />";

        if ($val['attr_input_type'] == 0)
        {
            $html .= '<input name="attr_value_list[]" type="text" value="' .htmlspecialchars($val['attr_value']). '" size="40" /> ';
        }
        elseif ($val['attr_input_type'] == 2)
        {
            $html .= '<textarea name="attr_value_list[]" rows="3" cols="40">' .htmlspecialchars($val['attr_value']). '</textarea>';
        }
        else
        {
            $html .= '<select name="attr_value_list[]">';
            $html .= '<option value=""> 请选择...</option>';

            $attr_values = explode("\n", $val['attr_values']);

            foreach ($attr_values AS $opt)
            {
                $opt    = trim(htmlspecialchars($opt));
				$optArr = explode('|',$opt);
				$opt_name = $optArr[0];
				$opt_price = empty($optArr[1])?'':$optArr[1];

                $html   .= ($val['attr_value'] != $opt_name) ?
                    '<option value="' . $opt_name . '">' . $opt_name . '</option>' :
                    '<option value="' . $opt_name . '" selected="selected">' . $opt_name . '</option>';
            }
            $html .= '</select> ';
        }

        $html .= ($val['attr_type'] == 1 || $val['attr_type'] == 2) ?
            '属性价格  <input type="text" name="attr_price_list[]" value="' . $val['attr_price'] . '" size="5" maxlength="10" />' :
            ' <input type="hidden" name="attr_price_list[]" value="0" />';

        $html .= '</td></tr>';
    }

    $html .= '</table>';

    return $html;
}

/**
 * 获得指定商品相关的商品
 *
 * @access  public
 * @param   integer $goods_id
 * @return  array
 */
function get_linked_goods($goods_id)
{
    $sql = "SELECT lg.link_goods_id AS goods_id, g.goods_title, lg.is_double " .
            "FROM " . $GLOBALS['ecs']->table('link_goods') . " AS lg, " .
                GOODS . " AS g " .
            "WHERE lg.goods_id = '$goods_id' " .
            "AND lg.link_goods_id = g.goods_id ";
    if ($goods_id == 0)
    {
        $sql .= " AND lg.admin_id = '$_SESSION[admin_id]'";
    }
    $row = $GLOBALS['db']->getAll($sql);

    foreach ($row AS $key => $val)
    {
        $linked_type = $val['is_double'] == 0 ? $GLOBALS['_LANG']['single'] : $GLOBALS['_LANG']['double'];

        $row[$key]['goods_title'] = $val['goods_title'] . " -- [$linked_type]";

        unset($row[$key]['is_double']);
    }

    return $row;
}

/**
 * 获得指定商品的配件
 *
 * @access  public
 * @param   integer $goods_id
 * @return  array
 */
function get_group_goods($goods_id)
{
    $sql = "SELECT gg.goods_id, CONCAT(g.goods_title, ' -- [', gg.goods_price, ']') AS goods_title " .
            "FROM " . GROUPGOODS . " AS gg, " .
                GOODS . " AS g " .
            "WHERE gg.parent_id = '$goods_id' " .
            "AND gg.goods_id = g.goods_id ";
    if ($goods_id == 0)
    {
        $sql .= " AND gg.admin_id = '$_SESSION[WebUserInfo][said]'";
    }
    $row = $GLOBALS['db']->arrQuery($sql);

    return $row;
}

/**
 * 获得商品的关联文章
 *
 * @access  public
 * @param   integer $goods_id
 * @return  array
 */
function get_goods_articles($goods_id)
{
    $sql = "SELECT g.article_id, a.title " .
            "FROM " .$GLOBALS['ecs']->table('goods_article') . " AS g, " .
                $GLOBALS['ecs']->table('article') . " AS a " .
            "WHERE g.goods_id = '$goods_id' " .
            "AND g.article_id = a.article_id ";
    if ($goods_id == 0)
    {
        $sql .= " AND g.admin_id = '$_SESSION[admin_id]'";
    }
    $row = $GLOBALS['db']->getAll($sql);

    return $row;
}

/**
 * 获得商品列表
 *
 * @access  public
 * @params  integer $isdelete
 * @params  integer $real_goods
 * @return  array
 */
function goods_list($is_delete, $real_goods=1)
{
    /* 过滤条件 */
    $param_str = '-' . $is_delete . '-' . $real_goods;
    $result = get_filter($param_str);
    if ($result === false)
    {
        $day = getdate();
        $today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);
        $filter['cat_id']           = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
        $filter['intro_type']       = empty($_REQUEST['intro_type']) ? '' : trim($_REQUEST['intro_type']);
        $filter['is_promote']       = empty($_REQUEST['is_promote']) ? 0 : intval($_REQUEST['is_promote']);
        $filter['stock_warning']    = empty($_REQUEST['stock_warning']) ? 0 : intval($_REQUEST['stock_warning']);
        $filter['brand_id']         = empty($_REQUEST['brand_id']) ? 0 : intval($_REQUEST['brand_id']);
        $filter['keyword']          = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        $filter['is_24h_ship']      = empty($_GET['is_24h_ship']) ? 0 :$_GET['is_24h_ship'];
    	$filter['goods_grade']      = empty($_GET['goods_grade']) ? 0 :$_GET['goods_grade'];
        if($filter['intro_type']    == 'is_groupbuy'&&empty($_REQUEST['sort_by']))$_REQUEST['sort_by']='groupbuy_end_date';
        $filter['sort_by']          = empty($_REQUEST['sort_by']) ? 'g.goods_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order']       = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['extension_code']   = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
        $filter['is_delete']        = $is_delete;
        $filter['real_goods']       = $real_goods;
        $filter['activity']         = empty($_REQUEST['activity']) ? 0 : intval($_REQUEST['activity']);
		$filter['is_groupbuy']      =  empty($_REQUEST['is_groupbuy']) ? 0 : intval($_REQUEST['is_groupbuy']);
		$filter['start_date']       = empty($_REQUEST['start_date']) ? 0 : gmstr2time($_REQUEST['start_date']);
        $filter['end_date']         = empty($_REQUEST['end_date']) ? 0 : gmstr2time($_REQUEST['end_date']);
		$filter['date_type']        = empty($_REQUEST['date_type'])?0:intval($_REQUEST['date_type']);//搜索时间类型 0 为添加时间 1为上架时间
        $children = $filter['cat_id'] > 0 ? get_children($filter['cat_id']):'';
        if($filter['intro_type']=='is_gifts')$filter['sort_by']='gifts_id';
        $where_zuhe = $children ? " AND (" . $children.' ) ' : '';
        $where_cat = $filter['cat_id'] > 0 ? " AND " . $children : '';
        $where = '';
        /* 推荐类型 */

        switch ($filter['intro_type'])
        {
            case 'is_free_shipping':
                $where .= " AND is_free_shipping=1";
                 break;
            case 'is_best':
                $where .= " AND is_best=1";
                 break;
           case 'on_sale':
                $where .= " AND is_on_sale=1";
                break;
            case 'not_on_sale':
                $where .= " AND is_on_sale=0";
                break;
            case 'is_hot':
                $where .= ' AND is_hot=1';
				break;
            case 'is_bighot':
                $where .= ' AND is_bighot=1';
                break;
            case 'is_new':
                $where .= ' AND is_new=1';
                break;
            case 'is_promote':
                $where .= " AND is_promote = 1 AND promote_price > 0 AND promote_start_date <= '$today' AND promote_end_date >= '$today'";
                break;
            case 'all_type';
                $where .= " AND (is_best=1 OR is_hot=1 OR is_new=1 OR (is_promote = 1 AND promote_price > 0 AND promote_start_date <= '" . $today . "' AND promote_end_date >= '" . $today . "'))";
				break;
            case 'is_direct_sale_off';
                $where .= ' AND is_direct_sale_off=1';
                break;
            case 'is_groupbuy';
                $where .= " AND is_groupbuy ='1'  ";
                break;
            case 'is_gifts';
                $where .= " AND gifts_id > 0 ";
                break;				
            case 'is_pre_sale';
                $where .= " AND presale_date_from >0  ";
                $filter['sort_by'] = 'presale_date_from';
                $filter['sort_order'] = 'asc';
                break;
            case 'direct_sale_bulk';
            		$sql_str='select goods_id from '.VPRICE.' where (volume_number+0)=5';
					$arr_number = $GLOBALS['db']->arrQuery($sql_str);
						$goods_id_str="0";
						foreach ($arr_number as $row){
							$goods_id_str.=",".$row['goods_id'];
						}
                $where .= ' and g.goods_id in('.$goods_id_str.')';
                break;
			case 'is_superstar';
                $where .= " AND is_superstar ='1'  ";
                break;				
        }


        /* 库存警告 */
        if ($filter['stock_warning'])
        {
            $where .= ' AND goods_number <= warn_number ';
        }
		 //产品添加时间
        if ($filter['start_date'] && $filter['end_date']) {
            if($filter['date_type']==1) {
                $where .= " AND (s.sale_time BETWEEN '" . $filter['start_date'] . "' AND '" . $filter['end_date'] . "') ";
            }else {
                $where .= " AND (g.add_time BETWEEN '" . $filter['start_date'] . "' AND '" . $filter['end_date'] . "') ";
            }
        }

        //活动搜索
        if ($filter['activity'])
        {
            $sql = 'SELECT * FROM eload_activity WHERE id='.$filter['activity'];
			$activity_info = $GLOBALS['db']->selectinfo($sql);
			
			if($activity_info['type'] != 2){
				
	   			if(!empty($activity_info['act_goods_list'])){
		        	$goods_list_sn = "'".str_replace(',',"','",$activity_info['act_goods_list'])."'";						
		            $where .= " AND g.goods_sn in($goods_list_sn)  ";
	   			}else{
	   				$where .= " AND activity_list LIKE '%,".$filter['activity'].",%'  ";
	   			}
			} else{  	
        	
           		 $where .= " AND activity_list LIKE '%,".$filter['activity'].",%'  ";
			}
        }

        $cat_priv= $_SESSION['WebUserInfo']['cat_priv'];//拥有的分类管理权限
        $allow_cat_id='';
        if(!empty($cat_priv)){
        	$priv_cat_big_arr = explode(',',$cat_priv);
        	$category_children = read_static_cache('category_children', 2);    //顶级分类
        	foreach ($priv_cat_big_arr as $k=>$v){
        		$allow_cat_id.=$v.",";
        		if(!empty($category_children[$v]['children']))array_push($category_children[$v]['children'],$v);
        		if(!empty($category_children[$v]['children']))$allow_cat_id.=implode(',',$category_children[$v]['children']).",";
        	}
        	$allow_cat_id.="0";
        }
        $allow_cat_id.='0';
		$where .= " AND g.cat_id in($allow_cat_id)";
        /* 品牌 */
        if ($filter['brand_id'])
        {
            $where .= " AND brand_id='$filter[brand_id]'";
        }        
        
        //24小时发货
         if ($filter['is_24h_ship'])
        {
            $where .= " AND is_24h_ship=1";
        }
        
        //产品等级
        if ($filter['goods_grade'])
        {
            $where .= " AND s.goods_grade='$filter[goods_grade]'";
        }               

        /* 扩展 */
        if ($filter['extension_code'])
        {
            $where .= " AND extension_code='$filter[extension_code]'";
        }

         if ($filter['extension_code'])
        {
            $where .= " AND extension_code='$filter[extension_code]'";
        }

        /* 关键字 */
        if (!empty($filter['keyword']))
        {
        	$goods_sn = $filter['keyword'];
        	$goods_sn = preg_replace('/\s/i','',$goods_sn);
        	$goods_sn = str_replace(',',"','",$goods_sn);
        	$goods_sn="'$goods_sn'";
            $where .= " AND (goods_sn in($goods_sn) OR goods_title LIKE '%" . mysql_like_quote($filter['keyword']) . "%')";
        }

        /* 记录总数 */
        $sql = "SELECT COUNT(*) FROM " .GOODS. " AS g left join ".GOODS_STATE." s on g.goods_id=s.goods_id WHERE is_delete='$is_delete' $where $where_zuhe";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

		if($where_cat){
			$sql = "SELECT COUNT(*) FROM " .GOODS. " AS g left join ".GOODS_STATE." s on g.goods_id=s.goods_id WHERE is_delete='$is_delete' $where $where_cat";
        	$filter['zhucat_count'] = $GLOBALS['db']->getOne($sql);
		   	$filter['kuozhan_count'] = $filter['record_count'] - $filter['zhucat_count'];
		}

        /* 分页大小 */
        $filter = page_and_size($filter);

        $sql = "SELECT s.*,presale_date_from,g.goods_id,g.gifts_id, goods_title,promote_lv,promote_end_date,goods_thumb,click_count,sale_count,goods_sn, shop_price, is_on_sale, is_best, is_new, is_hot,point_rate, sort_order, goods_number, add_time,add_user,last_update,update_user,discount_rate, " .
                    " (promote_price > 0 AND promote_start_date <= '$today' AND promote_end_date >= '$today') AS is_promote ".
                    " FROM " . GOODS . " AS g left join ".GOODS_STATE." s on g.goods_id=s.goods_id WHERE is_delete='$is_delete' $where $where_zuhe " .
                    " ORDER BY $filter[sort_by] $filter[sort_order] ".
                    " LIMIT " . $filter['start'] . ",$filter[page_size]";

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql, $param_str);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    if(!empty($_GET['is_test']))echo $sql;
    $rss = $GLOBALS['db']->arrQuery($sql);
    $gifts = read_static_cache('gifts_c_key',2);
	foreach($rss as $k => $row){
		$rss[$k]["goods_title"] = varResume($row['goods_title']);
		$rss[$k]["goods_thumb"] = get_image_path($row['goods_id'],$row['goods_thumb']);
		$rss[$k]["promote_end_date"] = $row['promote_end_date']?substr(local_date($GLOBALS['_CFG']['time_format'], $row['promote_end_date']),2,16):0;		
		$rss[$k]["is_presale_over"] = $rss[$k]["presale_date_from"]&&$rss[$k]["presale_date_from"]<(gmtime()-24*3600)?1:0;		
		$rss[$k]["presale_date_from"] = $row['presale_date_from']?local_date($GLOBALS['_CFG']['time_format'], $row['presale_date_from']):0;		
		$rss[$k]["add_time"] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
		$rss[$k]["is_new_modify"] = !empty($row['last_update'])?gmtime()-$row['last_update']<3600*12?1:0:0;
		$rss[$k]["last_update"] = local_date($GLOBALS['_CFG']['time_format'], $row['last_update']);
		$rss[$k]["updatesign"] = (time() - $row['last_update'])>=3600*24?0:1;     	
    	if(!empty($gifts[$row['gifts_id']])){
    			$rss[$k]['gifts_info'] = $gifts[$row['gifts_id']];
    	}else   $rss[$k]['gifts_info']= array();        
	}

    return array('goods' => $rss, 'filter' => $filter, 'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);
}

/**
 * 格式化商品图片名称（按目录存储）
 *
 */
function reformat_image_name($type, $goods_id, $source_img, $position='')
{
	global $url_title_temp;
	//如果是相册，则加上随机数字，以免重复
	if (strpos($type,'gallery')===false){
		$rand_name = $url_title_temp;
	}else{
		$rand_name = $url_title_temp.gmtime().sprintf("%03d", mt_rand(1,999));
	}
    $img_ext = substr($source_img, strrpos($source_img, '.'));
    $dir = 'images';
    if (defined('IMAGE_DIR')){ $dir = IMAGE_DIR;  }
    $sub_dir = date('Ym', gmtime());
    if (!make_dir(ROOT_PATH.$dir.'/'.$sub_dir))
    {
        return false;
    }
    if (!make_dir(ROOT_PATH.$dir.'/'.$sub_dir.'/source-img')){
        return false;
    }
    if (!make_dir(ROOT_PATH.$dir.'/'.$sub_dir.'/cat-img')){
        return false;
    }
    if (!make_dir(ROOT_PATH.$dir.'/'.$sub_dir.'/goods-img')){
        return false;
    }
    if (!make_dir(ROOT_PATH.$dir.'/'.$sub_dir.'/thumb-img')){
        return false;
    }
    if (!make_dir(ROOT_PATH.$dir.'/'.$sub_dir.'/grid-img')){
        return false;
    }
    switch($type)
    {
        case 'goods':
            $img_name =  $rand_name. '-G-' .$goods_id ;
            break;
        case 'goods_mid':
            $img_name =  $rand_name. '-G-mid-' .$goods_id ;
            break;
        case 'goods_thumb':
            $img_name =  $rand_name. '-thumb-G-' .$goods_id ;
            break;
        case 'grid_thumb':
            $img_name =  $rand_name. '-grid-G-' . $goods_id;
            break;
        case 'gallery':
            $img_name =  $rand_name  . '-P-' . $goods_id;
            break;
        case 'gallery_thumb':
            $img_name =  $rand_name. '-thumb-P-' . $goods_id;
            break;
    }
    if ($position == 'source')
    {
        if (move_image_file(ROOT_PATH.$source_img, ROOT_PATH.$dir.'/'.$sub_dir.'/source-img/'.$img_name.$img_ext))
        {
            return $dir.'/'.$sub_dir.'/source-img/'.$img_name.$img_ext;
        }
    }
    elseif ($position == 'thumb')
    {
        if (move_image_file(ROOT_PATH.$source_img, ROOT_PATH.$dir.'/'.$sub_dir.'/thumb-img/'.$img_name.$img_ext))
        {
            return $dir.'/'.$sub_dir.'/thumb-img/'.$img_name.$img_ext;
        }
    }
    elseif ($position == 'cat-img')
    {
        if (move_image_file(ROOT_PATH.$source_img, ROOT_PATH.$dir.'/'.$sub_dir.'/cat-img/'.$img_name.$img_ext))
        {
            return $dir.'/'.$sub_dir.'/cat-img/'.$img_name.$img_ext;
        }
    }
    elseif ($position == 'grid')
    {
        if (move_image_file(ROOT_PATH.$source_img, ROOT_PATH.$dir.'/'.$sub_dir.'/grid-img/'.$img_name.$img_ext))
        {
            return $dir.'/'.$sub_dir.'/grid-img/'.$img_name.$img_ext;
        }
    }
    else
    {
        if (move_image_file(ROOT_PATH.$source_img, ROOT_PATH.$dir.'/'.$sub_dir.'/goods-img/'.$img_name.$img_ext))
        {
            return $dir.'/'.$sub_dir.'/goods-img/'.$img_name.$img_ext;
        }
    }
    return false;
}

function move_image_file($source, $dest)
{
    if (@copy($source, $dest))
    {
        @unlink($source);
        return true;
    }
    return false;
}

//分词
function split_word($arr,$times = 2){
	$temp_arr = array();
	foreach($arr as $k => $v){
		switch ($times){
			case 3:
				if(empty($arr[$k+2])){
					$temp_arr[] = $arr[$k];
				}else{
					$temp_arr[] = $arr[$k].' '.$arr[$k+1].' '.$arr[$k+2];
				}
			break;

			case 4:
				if(empty($arr[$k+3])){
					$temp_arr[] = $arr[$k];
				}else{
					$temp_arr[] = $arr[$k].' '.$arr[$k+1].' '.$arr[$k+2].' '.$arr[$k+3];
/*					$temp_arr[] = $arr[$k].' '.$arr[$k+1].' '.$arr[$k+3].' '.$arr[$k+2];
					$temp_arr[] = $arr[$k].' '.$arr[$k+2].' '.$arr[$k+1].' '.$arr[$k+3];
					$temp_arr[] = $arr[$k].' '.$arr[$k+2].' '.$arr[$k+3].' '.$arr[$k+1];
					$temp_arr[] = $arr[$k].' '.$arr[$k+3].' '.$arr[$k+2].' '.$arr[$k+1];
					$temp_arr[] = $arr[$k].' '.$arr[$k+3].' '.$arr[$k+1].' '.$arr[$k+2];

					$temp_arr[] = $arr[$k+1].' '.$arr[$k].' '.$arr[$k+2].' '.$arr[$k+3];
					$temp_arr[] = $arr[$k+1].' '.$arr[$k].' '.$arr[$k+3].' '.$arr[$k+2];
					$temp_arr[] = $arr[$k+1].' '.$arr[$k+2].' '.$arr[$k].' '.$arr[$k+3];
					$temp_arr[] = $arr[$k+1].' '.$arr[$k+2].' '.$arr[$k+3].' '.$arr[$k];
					$temp_arr[] = $arr[$k+1].' '.$arr[$k+3].' '.$arr[$k].' '.$arr[$k+2];
					$temp_arr[] = $arr[$k+1].' '.$arr[$k+3].' '.$arr[$k+2].' '.$arr[$k];

					$temp_arr[] = $arr[$k+2].' '.$arr[$k].' '.$arr[$k+1].' '.$arr[$k+3];
					$temp_arr[] = $arr[$k+2].' '.$arr[$k].' '.$arr[$k+3].' '.$arr[$k+1];
					$temp_arr[] = $arr[$k+2].' '.$arr[$k+1].' '.$arr[$k].' '.$arr[$k+3];
					$temp_arr[] = $arr[$k+2].' '.$arr[$k+1].' '.$arr[$k+3].' '.$arr[$k];
					$temp_arr[] = $arr[$k+2].' '.$arr[$k+3].' '.$arr[$k+1].' '.$arr[$k];
					$temp_arr[] = $arr[$k+2].' '.$arr[$k+3].' '.$arr[$k].' '.$arr[$k+1];

					$temp_arr[] = $arr[$k+3].' '.$arr[$k].' '.$arr[$k+1].' '.$arr[$k+2];
					$temp_arr[] = $arr[$k+3].' '.$arr[$k].' '.$arr[$k+2].' '.$arr[$k+1];
					$temp_arr[] = $arr[$k+3].' '.$arr[$k+1].' '.$arr[$k].' '.$arr[$k+2];
					$temp_arr[] = $arr[$k+3].' '.$arr[$k+1].' '.$arr[$k+2].' '.$arr[$k];
					$temp_arr[] = $arr[$k+3].' '.$arr[$k+2].' '.$arr[$k+1].' '.$arr[$k];
					$temp_arr[] = $arr[$k+3].' '.$arr[$k+2].' '.$arr[$k].' '.$arr[$k+1];
*/				}
			break;

			case 5:
				if(empty($arr[$k+4])){
					$temp_arr[] = $arr[$k];
				}else{
					$temp_arr[] = $arr[$k].' '.$arr[$k+1].' '.$arr[$k+2].' '.$arr[$k+3].' '.$arr[$k+4];
				}
			break;

			case 1:
					$temp_arr[] = $arr[$k];
					$temp_arr[] = 'china '.$arr[$k];
					$temp_arr[] = 'chinese '.$arr[$k];
					$temp_arr[] = 'buy '.$arr[$k];
					$temp_arr[] = 'cheapest '.$arr[$k];
					$temp_arr[] = 'cect '.$arr[$k];
					$temp_arr[] = 'mini '.$arr[$k];
					$temp_arr[] = 'dropship '.$arr[$k];
					$temp_arr[] = 'high quality '.$arr[$k];
					$temp_arr[] = 'black '.$arr[$k];
					$temp_arr[] = 'white '.$arr[$k];
					$temp_arr[] = $arr[$k].' china';
					$temp_arr[] = $arr[$k].' chinese';
					$temp_arr[] = $arr[$k].' wholesale';
					$temp_arr[] = $arr[$k].' cheap';
					$temp_arr[] = $arr[$k].' buy';
					$temp_arr[] = $arr[$k].' supplier';
					$temp_arr[] = $arr[$k].' wholesaler';
					$temp_arr[] = $arr[$k].' store';
					$temp_arr[] = $arr[$k].' free shipping';
					$temp_arr[] = 'cect '.$arr[$k].' touch';
			break;

			default:
				if(empty($arr[$k+1])){
					$temp_arr[] = $arr[$k];
				}else{
					$temp_arr[] = $arr[$k].' '.$arr[$k+1];
					$temp_arr[] = $arr[$k+1].' '.$arr[$k];
				}
			break;
		}
	}

	return $temp_arr;
}

//自动录入关键字

function handle_title_keyword($goods_title){
	global $db;
    $str = '';

	if ($goods_title == '' ) return false;
    preg_match_all("/[0-9a-zA-Z.#+]{1,}/",$goods_title,$match); //提取字母和数字

	$str1 = implode(',',split_word($match[0],1));
	$str2 = implode(',',split_word($match[0],2));
	$str3 = implode(',',split_word($match[0],3));
	$str4 = implode(',',split_word($match[0],4));
	$str5 = implode(',',split_word($match[0],5));

	if (strpos($str,$str1.',') === false){
		$str  .= $str1.',';
	}
	if (strpos($str,$str2.',') === false){
		$str  .= $str2.',';
	}
	if (strpos($str,$str3.',') === false){
		$str  .= $str3.',';
	}
	if (strpos($str,$str4.',') === false){
		$str  .= $str4.',';
	}
	if (strpos($str,$str5.',') === false){
		$str  .= $str5.',';
	}

	$total_keyArr = explode(',',$str);
	foreach($total_keyArr as $k => $v){
		$keyw =  trim($v);
		$keyw =  trim($keyw);
		if (($keyw!='') && (strlen($keyw)<150)){
		$keyw =  addslashes(trim($keyw));
		$sql = "select count(*) from ".ABCKEYWORD." where  keyword like '".$keyw."'";
			if($db->getOne($sql)==0){
				$sql = "select count(*) from ".GOODS." where is_delete = 0 and (goods_title like '%".$keyw."%' or goods_brief  like '%".$keyw."%') order by goods_id desc";
				$goods_num = $db->getOne($sql);
				$data   = array();
				$data['keyword']   = $keyw;
				$data['goods_num'] = $goods_num;
				$db->autoExecute(ABCKEYWORD,$data);
			}
		}
	}
}

function get_review($wheresql)
{
    /* 过滤条件 */
	$day = getdate();
	$today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);
	$filter['keyword']          = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
	$filter['sort_by']          = empty($_REQUEST['sort_by']) ? 'rid' : trim($_REQUEST['sort_by']);
	$filter['sort_order']       = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
	$where = 'r.goods_id=g.goods_id';
	
	/* 推荐类型 */
	$filter['record_count'] = $GLOBALS['db']->getOne("select count(*) from ".REVIEW." r,".GOODS." g where ".$where);
	
	/* 分页大小 */
	$filter = page_and_size($filter);

	$sql = "SELECT g.goods_id, g.goods_title,g.goods_sn, g.goods_thumb,r.*  " .
				" FROM " . GOODS . " AS g,".REVIEW." as r WHERE  $where  ".$wheresql .
				" ORDER BY $filter[sort_by] $filter[sort_order] ".
				" LIMIT " . $filter['start'] . ",$filter[page_size]";

	$filter['keyword'] = stripslashes($filter['keyword']);
    $rss = $GLOBALS['db']->arrQuery($sql);
	foreach ($rss as $k=>$v){
		$rss[$k]['adddate']=local_date('Y-m-d h:i:s',$rss[$k]['adddate']);
		$rss[$k]['status']=rw_state($rss[$k]['is_pass']);
	}

    return array('review_list' => $rss, 'filter' => $filter, 'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);
}

function get_review_list($page=1,$page_size=20){
	global $db;
	$from_row = ($page-1)*$page_size;
	$sql = 'select  * from '.REVIEW ." r,".GOODS." g where r.goods_id=g.goods_id order by adddate desc limit  $from_row,$page_size";
	$review_list = $db->arrQuery($sql);
	foreach ($review_list as $k=>$v){
		$review_list[$k]['adddate']=local_date('Y-m-d h:i:s',$review_list[$k]['adddate']);
		$review_list[$k]['status']=rw_state($review_list[$k]['is_pass']);
	}
	$review_list;
	return $review_list;
}

function showRate($rate){
	if(!empty($rate)&&is_numeric($rate)){
		if(($rate*10)%10 == 0)
			return "/temp/skin3/images/stars/".intval($rate)."s.gif";
		else
			return "/temp/skin3/images/stars/".intval($rate)."5s.gif";
	}

}

function rw_state($state){
	switch (intval($state)){
		case 1:
			return "<font style='color:#066'>已通过</font>";
			break;
		case 2:
			return "<font style='color:#C00'>不通过</font>";
		case 3:
			return "<font style='color:#C00'>待处理</font>";
		default:
			return "<font style='color:#F60'>未审核</font>";
	}
}
function inquiry_state($state){
	switch (intval($state)){
		case 1:
			return "<font style='color:#066'>显示</font>";
			break;
		case 2:
			return "<font style='color:#C00'>不显示</font>";
		default:
			return "<font style='color:#F60'>未审核</font>";
	}
}

//取指定ID的评论
function get_reviews($goods_id,$rid){
	global $db;
	$review =array();
	$sql = "select count(*) as review_count,sum(rate_overall) as review_rate from ".REVIEW.' where is_pass=1 and goods_id='.$goods_id;
	$review_stat= $db->selectInfo($sql);
	$review['review_count'] = $review_stat['review_count'];
	$review['review_rate'] = round($review_stat['review_rate'],2);
	if($review['review_count']>0){
		$review['avg_rate'] = round($review['review_rate']/$review['review_count'],1);
		$review['avg_rate'] = number_format($review['avg_rate'],1);
	}else{
		$review['avg_rate'] = 0;
	}

	$review['avg_rate_img'] = showRate($review['avg_rate']);
	$sql = 'select  * from '.REVIEW .' where rid='.$rid;
	$review_list = $db->arrQuery($sql);
	foreach ($review_list as $k=>$v){
		$review_list[$k]['email']=$db->getOne("select email from ".USERS ." where user_id=".$v['user_id']);
		$review_list[$k]['adddate']=local_date('M-d/Y h:i:s',$review_list[$k]['adddate']);
		$review_list[$k]['addtime_real']=local_date('M-d/Y h:i:s',$review_list[$k]['addtime_real']);
		$sql = 'select * from '.REVIEW_PIC.' WHERE rid ='.$v['rid'];
		$review_list[$k]['pic'] = $db->arrQuery($sql);
		$review_list[$k]['status']=rw_state($review_list[$k]['is_pass']);
		if(count($review_list[$k]['pic'])==0) $review_list[$k]['pic']="";
		$sql = 'select * from '.REVIEW_VIDEO.' WHERE rid ='.$v['rid'];
		$review_list[$k]['video'] = $db->arrQuery($sql);
		if(count($review_list[$k]['video'])==0) $review_list[$k]['video']="";
		$sql = 'select * from '.REVIEW_REPLY.' WHERE rid ='.$v['rid'];
		 $reply= $db->arrQuery($sql);
		foreach ($reply as $k1=>$v1){
			$reply[$k1]['adddate'] = local_date('M-d/Y h:i:s',$reply[$k1]['adddate']);

		}
		$review_list[$k]['reply'] =$reply;
	}
	$review['review_list'] = $review_list;
	return $review;

}

//取指定id的咨询
function get_one_inquiry($goods_id,$iid){
	global $db;
	$inquiry =array();
	$sql = "select count(*) as inquiry_count from ".PRO_INQUIRY.' where is_pass=1 and goods_id='.$goods_id;
	$inquiry_stat= $db->selectInfo($sql);
	$inquiry['inquiry_count'] = $inquiry_stat['inquiry_count'];
	$sql = 'select  * from '.PRO_INQUIRY .' where iid='.$iid;
	$inquiry_list = $db->arrQuery($sql);
	foreach ($inquiry_list as $k=>$v){
		if(!empty($v['user_id']))$inquiry_list[$k]['email']=$db->getOne("select email from ".USERS ." where user_id=".$v['user_id']);
		if(!empty($inquiry_list[$k]['adddate']))$inquiry_list[$k]['adddate']=local_date('Y-m-d h:i:s',$inquiry_list[$k]['adddate']);
		if(!empty($inquiry_list[$k]['reply']))$inquiry_list[$k]['reply'] = HtmlDecode($inquiry_list[$k]['reply']);
		$inquiry_list[$k]['pic'] = $db->arrQuery('select * from '.PRO_INQUIRY_PIC." where rid=".$inquiry_list[$k]['iid']);
		$inquiry_list[$k]['email']	= email_disp_process($inquiry_list[$k]['email']);
	}

	$inquiry['inquiry_list'] = $inquiry_list;
	return $inquiry;

}

function get_inquiry($wheresql)
{
    /* 过滤条件 */
	$day = getdate();
	$today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);
	$filter['sort_by']          = empty($_REQUEST['sort_by']) ? 'iid' : trim($_REQUEST['sort_by']);
	$filter['sort_order']       = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
	$where = 'i.goods_id=g.goods_id';
	/* 推荐类型 */
	
	/* 关键字 */
	if (!empty($filter['keyword']))
	{
		$where .= " AND (goods_sn LIKE '%" . mysql_like_quote($filter['keyword']) . "%')";
	}
	/* 记录总数 */
	$filter['record_count'] = $GLOBALS['db']->getOne($count_sql = "select count(*) from ".PRO_INQUIRY." i,".GOODS." g where ".$where.$wheresql);
	
	/* 分页大小 */
	$filter = page_and_size($filter);
	
	$sql = "SELECT g.goods_id, g.goods_title,g.goods_sn, g.goods_thumb,i.*  " .
				" FROM " . GOODS . " AS g,".PRO_INQUIRY." as i WHERE  $where  " .$wheresql.
				" ORDER BY $filter[sort_by] $filter[sort_order] ".
				" LIMIT " . $filter['start'] . ",$filter[page_size]";

    $rss = $GLOBALS['db']->arrQuery($sql);
	foreach ($rss as $k=>$v){
		$rss[$k]['adddate']=local_date('Y-m-d h:i:s',$rss[$k]['adddate']);
		$rss[$k]['pass_time']=local_date('Y-m-d h:i:s',$rss[$k]['pass_time']);
		$rss[$k]['status']=inquiry_state($rss[$k]['is_pass']);
		$rss[$k]['goods_thumb']=get_image_path($rss[$k]['goods_id'],$rss[$k]['goods_thumb']);
	}

	if (strpos($wheresql, 'i.adddate')) {//未审（50），已显示（233），未显示（20） 需求 by mashanling on 2013-05-16 15:50:36	
		$type = -1;
		$num  = array();	
		if (strpos($wheresql, 'i.is_pass')) {
			$count_sql = preg_replace('#AND i\.is_pass=(\d+)#', '', $count_sql);
		}	
		$count_sql  = str_replace('count(*)', 'COUNT(i.iid) AS count,i.is_pass', $count_sql);
		$count_sql .= ' GROUP BY is_pass';
		$data       = array();	
		$GLOBALS['db']->query($count_sql);	
		while ($row = $GLOBALS['db']->fetchArray()) {
			$data[$row['is_pass']] = $row['count'];
		}	
		foreach($GLOBALS['Arr']['status_arr'] as $k => $v) {	
			if ('' !== $k) {
				$GLOBALS['Arr']['status_arr'][$k] .= '(' . (isset($data[$k]) ? $data[$k] : 0) . ')';
			}
		}
	}

    return array('inquiry_list' => $rss, 'filter' => $filter, 'page_size' => $filter['page_size'], 'record_count' => $filter['record_count']);
}

?>