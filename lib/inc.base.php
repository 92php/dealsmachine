<?php
if (!defined('INI_WEB')){die('访问拒绝');}
//数据库相关
/**
 * 获得指定分类下所有底层分类的ID
 */
function get_children($cat = 0,$ext='g.')
{
    return $ext.'cat_id ' . db_create_in(array_unique(array_merge(array($cat), array_keys(cat_list($cat,true)))));
}

/**
 * 获得指定文章分类下所有底层分类的ID
 *
 * @access  public
 * @param   integer     $cat        指定的分类ID
 *
 * @return void
 */
function get_article_children ($cat = 0)
{
    return db_create_in(array_unique(array_merge(array($cat), array_keys(article_cat_list($cat,true)))), 'cat_id');
}


/**
 * 对 MYSQL LIKE 的内容进行转义
 *
 * @access      public
 * @param       string      string  内容
 * @return      string
 */
function mysql_like_quote($str)
{
    return strtr($str, array("\\\\" => "\\\\\\\\", '_' => '\_', '%' => '\%'));
}

/**
 * 取得商品优惠价格列表
 *
 * @param   string  $goods_id    商品编号
 * @param   string  $price_type  价格类别(0为全店优惠比率，1为商品优惠价格，2为分类优惠比率)
 *
 * @return  优惠价格列表
 */
function get_volume_price_list($goods_id, $price_type = '1')
{
    $volume_price = array();
    $temp_index   = '0';

    $sql = "SELECT `volume_number` , `volume_price`".
           " FROM " .VPRICE. "".
           " WHERE `goods_id` = '" . $goods_id . "' AND `price_type` = '" . $price_type . "' and volume_price > 0 ".
           " ORDER BY cast(`volume_number` as UNSIGNED)";
    $res = $GLOBALS['db']->	arrQuery($sql);

    foreach ($res as $k => $v)
    {
			$volume_price[$temp_index]                 = array();
			$volume_price[$temp_index]['number']       = $v['volume_number'];
			$volume_price[$temp_index]['price']        = $v['volume_price'];
			$volume_price[$temp_index]['format_price'] = $v['volume_price'];//price_format($v['volume_price']);

		if ($v['volume_price'] != '0.01'){
			$temp_index ++;
		}

    }
    return $volume_price;
}

/**
 * 获得指定分类下的所有子分类的数组
 * @return  array

function getChilds($data, $pId)
{
	$tree = '';
	foreach($data as $k => $v)
	{
	   if($v['parent_id'] == $pId)
	   {         //父亲找到儿子
		$v['parent_id'] = getChilds($data, $v['cat_id']);
		$tree[$v['cat_id']] = $v;
		//unset($data[$k]);
	   }
	}
	print_r($tree);
	return $tree;
}
 */
function getChilds($arr,$pid,$deep=0)
{
    static $childs;
    $deep++;
    if(isset($arr) && !empty($arr))
    {
        foreach($arr as $k => $val)
        {
            if($val["parent_id"] == $pid)
            {
                $i = isset($childs)?count($childs):0;
                $childs[$val["cat_id"]]["cat_id"]   = $val["cat_id"];
                $childs[$val["cat_id"]]["parent_id"]  = $pid;
                $childs[$val["cat_id"]]["deep"] = $deep-1;
                getChilds($arr,$val["cat_id"],$deep);
                $i++;
            }
        }
        return $childs;
    }
}


//$pid 父类ID    $$selected 选中ID
//获取分类子类
function get_child_list($pid,$selected='',$cat_priv=''){
	global $cur_lang, $default_lang;
    $select = '';
	if($cur_lang != $default_lang){
		$catArr =  read_static_cache($cur_lang.'_category_c_key',2);
	}else {
		$catArr =  read_static_cache('category_c_key',2);
	}
    foreach ($catArr as $k => $var) {
        if ($catArr[$k]['is_show'] && $catArr[$k]['parent_id'] == $pid) {
            $select .= '<option value="' . $var['cat_id'] . '" ';
            $select .= ($selected == $var['cat_id']) ? " selected='ture'" : '';
            $select .= '>';
            $select .= $var['cat_name'] . '</option>';
        }
    }
    unset($catArr);
    return $select;
}

//$pid 父类ID    $$selected 选中ID
//后台获取分类子类
function get_child_list_bg($pid,$selected='',$cat_priv=''){
	global $cur_lang, $default_lang;
    $select = '';
	$catArr =  read_static_cache('category_c_key',2);
    foreach ($catArr as $k => $var) {
        if ( $catArr[$k]['parent_id'] == $pid) {
            $select .= '<option value="' . $var['cat_id'] . '" ';
            $select .= ($selected == $var['cat_id']) ? " selected='ture'" : '';
            $select .= '>';
            if($catArr[$k]['is_show']){
            	$select .= $var['cat_name'] . '</option>';
            }else{
            	$select .= $var['cat_name'] . '(隐藏)</option>';
            }
        }
    }
    unset($catArr);
    return $select;
}



//取得所有父类ID
function get_parent_id($cid){
	$pids = '';
	$catArr = read_static_cache('category_c_key',2);
	if (isset($catArr[$cid]['parent_id'])){
		$pids .= $catArr[$cid]['parent_id'];
		$npids = get_parent_id($catArr[$cid]['parent_id']);
		if(isset($npids)) $pids .= ','.$npids;
	}
	return $pids;
}

//得到分类选择下拉框
/*function get_lei_select($pid,$tag_name,$tag_id = '',$tag_class = '',$selected='',$attr = '',$tishi = ''){
	$str = '';
	//echo "w";
	$cat_priv= $_SESSION['WebUserInfo']['cat_priv'];//拥有的分类管理权限
	if(empty($cat_priv))$cat_priv='100000';//如果没如就设一个不存在的分类

	$zhong_str = get_child_list($pid,$selected,$cat_priv);
	//echo $cat_priv;
	//print_r($zhong_str);
	if ($zhong_str){
		$str .= ' <select name="'.$tag_name.'" class="'.$tag_class.'" id="'.$tag_id.'" ectype="'.$attr.'">';
		$tishi = ($tishi)?$tishi:'顶级类';
		if ($pid == '0'){$str .= '<option value="0">'.$tishi.'</option>';}else{$str .= '<option value="">请选择...</option>';}
		$str .= $zhong_str;
		$str .= '</select>';
	}
	return $str;
}*/

function get_lei_select($pid, $tag_name, $tag_id = '', $tag_class = '', $selected = '', $attr = '', $tishi = '') {
    $str = '';
    $zhong_str = get_child_list_bg($pid, $selected);
    if ($zhong_str) {
        $str .= ' <select name="' . $tag_name . '" class="' . $tag_class . '" id="' . $tag_id . '" ectype="' . $attr . '">';
        $tishi = ($tishi) ? $tishi : '顶级类';
        if ($pid == '0') {
            $str .= '<option value="0">' . $tishi . '</option>';
        }
        else {
            $str .= '<option value="">请选择...</option>';
        }
        $str .= $zhong_str;
        $str .= '</select>';
    }
    return $str;
}
//过滤没权限的分类
//$catArr 分类数组
//$cat_priv 拥有的权限顶级分类字符串,一般从admin表读取出来
function filter_cat_priv($catArr,$cat_priv){

}

//获取所有商品分类列表
function cat_list_search($selected = 0,$isArr=false,$depth=false,$cat_priv='')
{
	global $cur_lang, $default_lang;
    static $res = NULL;
	global $db,$tree,$cur_lang;
	if(empty($cur_lang))$cur_lang='en';
	$tree=array();
	if($cur_lang != $default_lang){
		$catArr =  read_static_cache($cur_lang.'_category_c_key',2);
	}else {
		$catArr =  read_static_cache('category_c_key',2);
	}

	//分类限制
	$allow_cat_id='';
    if(!empty($cat_priv)){  //获取拥有的所有权限的所有字符串
       $priv_cat_big_arr = explode(',',$cat_priv);
       $category_children = read_static_cache('category_children', 2);    //顶级分类
        foreach ($priv_cat_big_arr as $k=>$v){
        		if(!empty($v))$allow_cat_id.=$v.",";
        		if(!empty($category_children[$v]['children']))array_push($category_children[$v]['children'],$v);
        		if(!empty($category_children[$v]['children']))$allow_cat_id.=implode(',',$category_children[$v]['children']).",";
        }
        $allow_cat_id.="0";
    }
    $allow_cat_id.='0';
	if(!empty($cat_priv)){  //过滤没有权限的分类
		$allow_cat_id_arr = explode(',',$allow_cat_id);
		foreach ($catArr as $k=>$v){
			if(!in_array($v['cat_id'],$allow_cat_id_arr)){
				unset($catArr[$k]);
			}
		}
	}

	if ($isArr) {
		$tree = NULL;
		$tree = getChilds($catArr,$selected);
		if (!is_array($tree)) $tree=array();
		return $tree;
	}else{
		$select = '';
		if ($depth){   //只显示一级
		 if(!empty($catArr)){
			foreach ($catArr as $k => $var)
			{
				if ($catArr[$k]['is_show']){
					if ($catArr[$k]['parent_id'] == '0'){
						$select .= '<option value="' . $var['cat_id'] . '" ';
						$select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
						$select .= '>';
						$select .= $var['cat_name'] . '</option>';
					}else{
						unset($catArr[$k]);
					}
				}
			}
		 }
		}else{
			$catArr = toTree($catArr,$pk='cat_id');
			treetoary($catArr,0,'cat_name');
			$catArr = $tree;
			foreach ($catArr as $var)
			{
				$select .= '<option value="' . $var['cat_id'] . '" ';
				$select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
				$select .= '>';
				$select .= $var['cat_name'] . '</option>';
			}
		}
		return $select;
	}
}

//获取所有商品分类列表
function cat_list($selected = 0,$isArr=false,$depth=false,$cat_priv='')
{
	global $cur_lang, $default_lang;
    static $res = NULL;
	global $db,$tree,$cur_lang;
	if(empty($cur_lang))$cur_lang='en';
	$tree=array();
	//$sql = "SELECT * FROM ".CATALOG." ORDER BY parent_id,sort_order ASC,cat_id ASC";
	//$catArr = $db -> arrQuery($sql);
	//$catArr = read_static_cache('category_c_key',2);
	//if($cur_lang != $default_lang){
		//$catArr =  read_static_cache($cur_lang.'_category_c_key',2);
	//}else {
		$catArr =  read_static_cache('category_c_key',2);
	//}
	//print_r($catArr);
	//分类限制
	$allow_cat_id='';
    if(!empty($cat_priv)){  //获取拥有的所有权限的所有字符串
       $priv_cat_big_arr = explode(',',$cat_priv);
        	//print_r($priv_cat_big_arr);
       $category_children = read_static_cache('category_children', 2);    //顶级分类
        	//print_r($category_children);
        	//exit();
        foreach ($priv_cat_big_arr as $k=>$v){
        		if(!empty($v))$allow_cat_id.=$v.",";
        		if(!empty($category_children[$v]['children']))array_push($category_children[$v]['children'],$v);
        		if(!empty($category_children[$v]['children']))$allow_cat_id.=implode(',',$category_children[$v]['children']).",";
        }
        $allow_cat_id.="0";
    }
    $allow_cat_id.='0';
    //echo $allow_cat_id;
	if(!empty($cat_priv)){  //过滤没有权限的分类
		$allow_cat_id_arr = explode(',',$allow_cat_id);
		//print_r($allow_cat_id_arr);
		foreach ($catArr as $k=>$v){
			if(!in_array($v['cat_id'],$allow_cat_id_arr)){
				unset($catArr[$k]);
			}
		}
	}

	if ($isArr) {
		$tree = NULL;
		$tree = getChilds($catArr,$selected);
		if (!is_array($tree)) $tree=array();
		return $tree;
	}else{
		$select = '';
		if ($depth){   //只显示一级
		 if(!empty($catArr)){
			foreach ($catArr as $k => $var)
			{
				if ($catArr[$k]['is_show']){
					if ($catArr[$k]['parent_id'] == '0'){
						$select .= '<option value="' . $var['cat_id'] . '" ';
						$select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
						$select .= '>';
						$select .= $var['cat_name'] . '</option>';
					}else{
						unset($catArr[$k]);
					}
				}
			}
		 }
		}else{
			$catArr = toTree($catArr,$pk='cat_id');
			treetoary($catArr,0,'cat_name');
			$catArr = $tree;
			foreach ($catArr as $var)
			{
				$select .= '<option value="' . $var['cat_id'] . '" ';
				$select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
				$select .= '>';
				$select .= $var['cat_name'] . '</option>';
			}
		}

		return $select;
	}
}


//获取所有文章分类列表
function article_cat_list($selected = 0,$isArr=false)
{
    static $res = NULL;
	global $db,$tree;

	$tree=array();
	$sql = "SELECT * FROM ".ARTICLECAT." ORDER BY parent_id,sort_order ASC,cat_id ASC";
	$catArr = $db -> arrQuery($sql);
	if ($isArr) {
		$tree = getChilds($catArr,$selected);
		if (!is_array($tree)) $tree=array();
		return $tree;
	}else{
		$catArr = toTree($catArr,$pk='cat_id');
		treetoary($catArr,0,'cat_name');
		$catArr = $tree;
			$select = '';
			foreach ($catArr AS $var)
			{
				$select .= '<option value="' . $var['cat_id'] . '" ';
				$select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
				$select .= '>';
				$select .= $var['cat_name'] . '</option>';
			}

			return $select;
	}
}

/**
 * 获得商品类型的列表
 *
 * @access  public
 * @param   integer     $selected   选定的类型编号
 * @return  string
 */
function goods_type_list($selected)
{
	global $db;
    $sql = 'SELECT cat_id, cat_name FROM ' . GTYPE . ' WHERE enabled = 1';
    $res = $db->query($sql);

    $lst = '';
    while ($row = $db->fetchRow($res))
    {
        $lst .= "<option value='$row[cat_id]'";
        $lst .= ($selected == $row['cat_id']) ? ' selected="true"' : '';
        $lst .= '>' . htmlspecialchars($row['cat_name']). '</option>';
    }

    return $lst;
}


/**
 * 记录管理员的操作内容
 *
 * @access  public
 * @param   string      $sn         数据的唯一值
 * @param   string      $action     操作的类型
 * @param   string      $content    操作的内容
 * @return  void
 */
function admin_log($sn = '', $action, $content)
{   global $db;
    $log_info = $action.$content.' '. $sn;
	$_SESSION["WebUserInfo"]["said"] = empty($_SESSION["WebUserInfo"]["said"])?'0':$_SESSION["WebUserInfo"]["said"];
    $sql = 'INSERT INTO ' . ALOGS . ' (log_time, user_id, log_info, ip_address) ' .
            " VALUES ('" . gmtime() . "', '".$_SESSION["WebUserInfo"]["said"]."', '" . addslashes($log_info) . "', '" . real_ip() . "')";
    $db->query($sql);
}




/**
 * 递归方式的对变量中的特殊字符进行转义
 *
 * @access  public
 * @param   mix     $value
 *
 * @return  mix
 */
function addslashes_deep($value)
{
    if (empty($value))
    {
        return $value;
    }
    else
    {
        return is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value);
    }
}


/**
 * 获取地区列表的函数。
 *
 * @access  public
 * @param   int     $region_id  上级地区id
 * @return  void
 */
function area_list()
{
    $area_arr = read_static_cache('area_key',2);
    return $area_arr;
}

//=============================================
//---系统左边菜单--------------------------------
//=============================================
function creat_menu(){
	global $db,$_CFG;
	$sql = "SELECT * FROM ".AACTION."  ORDER BY parent_id,action_order ASC,action_id ASC";
	$menuArr = $db -> arrQuery($sql);
	$menuArr = toTree($menuArr,$pk='action_id');
	write_static_cache('menu_c', $menuArr,2);
}



//=============================================
//---生成商品分类静态缓存-------------------------
//=============================================
function creat_category(){
	global $db;
	require_once 'lib_goods.php';
	global $lang_arr;

	$catArr = array();//不带key
	$tempArr = array();//带key
	$js_data = array();//分类树

	//多语言 fangxin
	$lang_sql = "SELECT * FROM " . Mtemplates_language. " WHERE status = 1 ORDER BY orders ASC";
	$lang_arr = $db->arrQuery($lang_sql);

	$sql = "SELECT * FROM ".CATALOG." ORDER BY sort_order ASC,cat_id ASC";
	$db->query($sql);
	while (($row = $db->fetchArray()) !== false) {
		$row['link_url'] = creat_nav_url($row['url_title'], $row['cat_id'], !$row['parent_id']);
		$js_data[] = array(
			'id'	    => $row['cat_id'],
			'parent_id' => $row['parent_id'],
			'text'		=> $row['cat_name'],
			'node'      => $row['node'],
		);
		$tempArr[$row['cat_id']] =  $row;
		$catArr[] = $row;
	}
	write_static_cache('category_c', $catArr,2);
	write_static_cache('category_c_key', $tempArr,2);
	admin_create_category_children($tempArr);
	isset($GLOBALS['tree']) && admin_create_category_js($js_data);
	unset($catArr, $tempArr, $js_arr);
	if(!empty($lang_arr)){
		foreach ($lang_arr as $v){
			$sql = "select c.*,l.* from eload_category c inner join eload_category_muti_lang l on c.cat_id = l.cat_id and lang='$v[title_e]' ORDER BY sort_order ASC,c.cat_id ASC";
			$c = $db->arrQuery($sql);
			foreach ($c as $row) {
				$row['link_url'] = creat_nav_url($row['url_title'], $row['cat_id'], !$row['parent_id'],$v['title_e']);
				$row['url_title'] = $v['title_e'].'/'.$row['url_title'];

				$js_data[] = array(
					'id'	    => $row['cat_id'],
					'parent_id' => $row['parent_id'],
					'text'		=> $row['cat_name'],
					'node'      => $row['node'],
				);
				$tempArr[$row['cat_id']] =  $row;
				$catArr[] = $row;
			}
			write_static_cache($v['title_e'].'_category_c', $catArr,2);
			write_static_cache($v['title_e'].'_category_c_key', $tempArr,2);
			//admin_create_category_children($tempArr);
			unset($catArr,  $js_arr);
		}
	}
}



/**
 *
 *
 * @param array $category_arr 所有分类
 * @param int   $cat_id       分类id
 */
function admin_get_childs($category_arr, $cat_id) {
    $child_id = '';

    foreach ($category_arr as $k => $v) {

        if ($v['parent_id'] == $cat_id) {
            $child_id .= $v['cat_id'] . ',' . admin_get_childs($category_arr, $v['cat_id']);
            unset($category_arr[$k]);
        }
    }

    return $child_id;
}

/**
 * 生成分类树js
 *
 * @param array $js_data 分类js数据
 *
 * @return void 无返回值
 */
function admin_create_category_js(&$js_data) {
    global $tree;
    $data = toTree($js_data, 'id', 'parent_id', 'children');
    treetoary($data, 0, '', 'children');
    $data = toTree($tree, 'id', 'parent_id', 'children');
   // print_r($data);
   // exit();
    usort($data, 'compare_category_tree');
    array_walk($data, 'sort_caterory_tree');
    $data = json_encode($data) . ';';
    write_static_cache('all_categories', $data, 2);
}

/**
 * 商品分类树排序，目前只是将无子类的排到最后
 *
 *
 * @param array $item 分类
 *
 * @param array 排序后的分类
 */
function sort_caterory_tree(&$item) {
    if (isset($item['children'])) {
        $item['children'] = sort_caterory_tree($item['children']);
    }
    else {
        empty($item['leaf']) && usort($item, 'compare_category_tree');
    }

    return $item;
}

/**
 * 排序
 *
 * @param array $a 分类1
 * @param array $b 分类2
 *
 * @return int
 */
function compare_category_tree($a, $b) {
    if ($a['leaf'] == $b['leaf']) {
        return 0;
    }

    return $a['leaf'] > $b['leaf'] ? 1 : -1;
}


//前台无限级分类
function getDynamicTree($pId,$depth = 0,$isLimit = true,$selectid = 0)
{
	global $language,$cur_lang, $default_lang;

	if(empty($language) && empty($_COOKIE['WEBF-dan_num'])){
	    $whr_str = "  AND is_login = 0 ";
	}else{
		if(empty($_COOKIE['WEBF-dan_num'])){
			$whr_str = "  AND is_login = 0 ";
			//$whr_str = " AND clang not like '%$language%' ";
		}else{
			$whr_str = "";
		}
	}
	//if($cur_lang !='en')$whr_str .=" and lang='$cur_lang'";
	$limitStr = ($isLimit && $pId)?' limit 15':'';
	$html = '';
	if($cur_lang != $default_lang){
		$sql = "SELECT c.cat_id,c.parent_id,url_title,l.cat_name FROM ".CATALOG." c inner join ".CATALOG_LANG." l on c.cat_id=l.cat_id where parent_id = '".$pId."' and lang = '". $cur_lang ."' and is_show = 1 $whr_str ORDER BY parent_id,sort_order ASC,c.cat_id ASC $limitStr ";
	}else{
		$sql = "SELECT * FROM ".CATALOG." where parent_id = '".$pId."' and is_show = 1 $whr_str ORDER BY parent_id,sort_order ASC,cat_id ASC $limitStr ";
	}
	$catArr = $GLOBALS['db']->arrQuery($sql);
	$ArrNum = $GLOBALS['db']->getOne("SELECT count(*) FROM ".CATALOG." where parent_id = '".$pId."' and is_show = 1 $whr_str");
	foreach($catArr as $k => $v)
	{
		$inStyle = ($v['cat_id'] == '1260')?' class="imstyle"':'';
		$p = empty($v['parent_id'])?true:false;
		$urllink =  creat_nav_url($v['url_title'],$v['cat_id'],$p);
		$styles = (!empty($selectid) && ($v['cat_id'] == $selectid))?' class="selectedstyle"':'';
		$html .= "<li$styles><b></b><a href='".$urllink."'$inStyle>".get_cat_name($v['cat_id'],$v['cat_name'])."</a>";
		if($depth == 0)
		$html .= getDynamicTree($v['cat_id'],$depth,$isLimit,$selectid);
		$html = $html."</li>";
	}

	if($ArrNum>15 && $isLimit && $pId) {
		if(!empty($lang_arr)&&!empty($cur_lang)&&$cur_lang !='en')
			$url_title = $GLOBALS['db']->selectinfo("SELECT c.cat_id,c.parent_id,url_title,l.cat_name FROM ".CATALOG." c inner join ".CATALOG_LANG." l on c.cat_id=l.cat_id where l.cat_id = '".$pId."' and is_show = 1 $whr_str");
		else
			$url_title = $GLOBALS['db']->selectinfo("SELECT cat_id,url_title,cat_name,parent_id FROM ".CATALOG." where cat_id = '".$pId."' and is_show = 1 $whr_str");
		$p = empty($url_title['parent_id'])?true:false;

		$urllink =  creat_nav_url($url_title['url_title'],$pId,$p);
		$html .= "<li><a href=\"".$urllink."\" class='seemore'><b>". $GLOBALS['_LANG']['see_more'] ." ".get_cat_name($url_title['cat_id'],$url_title['cat_name'])." &raquo;</b></a></li>";
	}

	$html = $html ? '<ul>'.$html.'</ul>' : $html ;
	$html = str_replace('<ul><li>','<ul><li class="first_li">',$html);
	return $html;
}

//=============================================
//---生成分类下商品个数静态缓存--------------------
//=============================================
function creat_count_category_goods_num(){
	global $db;
	$res2 = read_static_cache('category_c',2);
	$newres = array();
	foreach($res2 as $v)
	{
		$count = 0;
		$children = get_children($v['cat_id']);
		$count    = get_cagtegory_goods_count($children);
		$newres[$v['cat_id']] = $count;
	}
	write_static_cache('category_goods_num_key', $newres,2);
}

//前台无限级分类
function getTree($data, $pId,$depth = 0,$is_login = false,$isLimit = true,$selectid = 0,$limit_number = 0)
{
	global $cur_lang, $default_lang;
	$whr_str = $is_login?' AND is_login = 0 ':'';
	if($limit_number>0){
		$limitStr = " limit $limit_number";
	}elseif ($isLimit && $pId){
		$limitStr = ' limit 15';
	}else{$limitStr='';}
	$html = '';

	if($cur_lang != $default_lang){
		$sql = "SELECT c.cat_id,c.parent_id,url_title,l.cat_name FROM ".CATALOG." c inner join ".CATALOG_LANG." l on c.cat_id=l.cat_id where parent_id = '".$pId."' and lang = '". $cur_lang ."' and is_show = 1 $whr_str ORDER BY parent_id,sort_order ASC,c.cat_id ASC $limitStr ";
	}else{
		$sql = "SELECT * FROM ".CATALOG." where parent_id = '".$pId."' and is_show = 1 $whr_str ORDER BY parent_id,sort_order ASC,cat_id ASC $limitStr ";
	}

	$catArr = $GLOBALS['db']->arrQuery($sql);
	$ArrNum = $GLOBALS['db']->getOne("SELECT count(*) FROM ".CATALOG." where parent_id = '".$pId."' and is_show = 1 $whr_str");
	foreach($catArr as $k => $v)
	{
		$inStyle = ($v['cat_id'] == '1260')?' class="imstyle"':'';
		$p = false;//empty($v['parent_id'])?true:false;
		$urllink =  creat_nav_url($v['url_title'],$v['cat_id'],$p);
		$styles = (!empty($selectid) && ($v['cat_id'] == $selectid))?' class="selectedstyle"':'';
		$html .= "<li$styles><a href=\"".$urllink."\"$inStyle>".get_cat_name($v['cat_id'],$v['cat_name'])."</a>";
		if($depth == 0)
		$html .= getTree($data, $v['cat_id'],$depth,$is_login,$isLimit,$selectid);
		$html = $html."</li>";
	}

	if($ArrNum>15 && $isLimit && $pId) {
		$url_title = $GLOBALS['db']->selectinfo("SELECT url_title,cat_name,parent_id FROM ".CATALOG." where cat_id = '".$pId."' and is_show = 1 $whr_str");
		$p = empty($url_title['parent_id'])?true:false;
		$urllink =  creat_nav_url($url_title['url_title'],$pId,$p);
		$html .= "<li><a href=\"".$urllink."\" class='seemore'><b>". $GLOBALS['_LANG']['see_more'] ." ".$url_title['cat_name']." &raquo;</b></a></li>";
	}
	return $html ? '<ul>'.$html.'</ul>' : $html ;
}




/**
 * 获得所有扩展分类属于指定分类的所有商品ID
 *
 * @access  public
 * @param   string $cat_id     分类查询字符串
 * @return  string
 */
function get_extension_goods($cats)
{
    static $extension_goods_array = '';
    if ($extension_goods_array !== '')
    {
        return db_create_in($extension_goods_array, 'g.goods_id');
    }
    else
    {
        $sql = 'SELECT goods_id FROM ' . GOODSCAT . " AS g WHERE $cats";
        $extension_goods_array = $GLOBALS['db']->getCol($sql);
        return db_create_in($extension_goods_array, 'g.goods_id');
    }
}




/**
 * 获得分类下的商品总数
 *
 * @access  public
 * @param   string     $cat_id
 * @return  integer
 */
function get_cagtegory_goods_count($children, $min = 0, $max = 0, $ext='')
{
    $where  = "g.is_on_sale = 1 and g.is_alone_sale = 1  AND g.is_delete = 0 AND ($children  OR " . get_extension_goods($children) . " )  and g.is_alone_sale = 1  ";


    if ($min > 0)
    {
        $where .= " AND g.shop_price >= $min ";
    }

    if ($max > 0)
    {
        $where .= " AND g.shop_price <= $max ";
    }

    /* 返回商品总数 */
    $sql = 'SELECT g.goods_id FROM ' . GOODS . " AS g left join ".GOODS_STATE." s on g.goods_id = s.goods_id WHERE $where $ext";
    $goods_id_array = $GLOBALS['db']->getCol($sql);
	$count = $GLOBALS['db']->numRows();
	return $count;
    //return $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . GOODS . " AS g WHERE $where $ext");
}

/**
 * 获得指定的规格的价格
 *
 * @access  public
 * @param   mix     $spec   规格ID的数组或者逗号分隔的字符串
 * @return  void
 */
function spec_price($spec)
{
    if (!empty($spec))
    {
        $where = db_create_in($spec, 'goods_attr_id');
        $sql = 'SELECT SUM(attr_price) AS attr_price FROM ' . GATTR . " WHERE $where";
        $price = floatval($GLOBALS['db']->getOne($sql));
    }
    else
    {
        $price = 0;
    }

    return $price;
}


function get_groupbuyer($goods_id){
	if (!empty($goods_id)){
		$goods_count_cart  =$GLOBALS['db']->getOne("select count(session_id) from eload_cart where is_groupbuy = 1 and goods_id = '".$goods_id."' ");
		$goods_count_order =$GLOBALS['db']->getOne("select count(*) from eload_order_goods where is_groupbuy = 1 and goods_id = '".$goods_id."' ");
		return $goods_count_cart+$goods_count_order;
	}else{
		return 0;
	}
}


/**
 * 取得商品最终使用价格
 *
 * @param   string  $goods_id      商品编号
 * @param   string  $goods_num     购买数量
 * @param   boolean $is_spec_price 是否加入规格价格
 * @param   mix     $spec          规格ID的数组或者逗号分隔的字符串
 *
 * @return  商品最终购买价格
 */
function get_final_price($goods_id, $goods_num = '1', $is_spec_price = false, $spec = array(), $main_goods_id = 0)
{
	global $db;
    $final_price   = '0'; //商品最终购买价格
    $volume_price  = '0'; //商品优惠价格
    $promote_price = '0'; //商品促销价格
    $user_price    = '0'; //商品会员价格
	$price_arr     = array(); //
	$pcode_goods = !empty($_SESSION['pcode_goods'])?$_SESSION['pcode_goods']:'';
    if ($main_goods_id) {
        return $db->getOne('SELECT goods_price FROM ' . GROUPGOODS . " WHERE parent_id={$main_goods_id} AND goods_id={$goods_id}");
    }

	    //取得商品优惠价格列表
	    $price_list   = get_volume_price_list($goods_id, '1');

	    if (!empty($price_list))
	    {
	        foreach ($price_list as $value)
	        {
	            if ($goods_num >= intval($value['number']))
	            {
	                $volume_price = $value['price'];
	            }
				$price_arr[] = $value['price'];
	        }
	    }

	    //取得商品促销价格列表
	    /* 取得商品信息 */
	    $sql = "SELECT g.gifts_id,g.goods_sn,g.promote_price, g.promote_start_date,g.cat_id ,g.promote_end_date,g.shop_price,g.goods_id,g.is_groupbuy,g.groupbuy_price,g.groupbuy_final_price,g.groupbuy_people_first_number,g.groupbuy_people_final_number,g.groupbuy_start_date,g.groupbuy_end_date ".
	           " FROM " .GOODS. " AS g ".
	           " WHERE g.goods_id = '" . $goods_id . "'";

	    $goods = $GLOBALS['db']->selectinfo($sql);
		$goods_cat_id = $goods['cat_id'];
		if($goods['gifts_id']){
			$gifts = read_static_cache('gifts_c_key',2);
			if(!empty($gifts[$goods['gifts_id']])) return 0;
		}
		$pcode = empty($_GET['pcode']) ? (empty($_SESSION['pcode_code']) ? '' : $_SESSION['pcode_code']) : htmlspecialchars(trim($_GET['pcode']));
		$pcode = empty($_SESSION['dropshipping_code']) ? $pcode : $_SESSION['dropshipping_code'];
		if ($pcode) { //是否有促削过来
			$sql = 'SELECT * FROM ' . PCODE . " WHERE code='{$pcode}'";

			$pcode_arr = $GLOBALS['db']->selectinfo($sql);

			if (!empty($pcode_arr)) {
				$code_cat_id   = $pcode_arr['cat_id'];
				if($code_cat_id)//获得当前商品的包括当前分类ID和所有下级分类ID
				{
					$childs_array = array();
					$typeArray =  read_static_cache('category_c_key',2);
					$childs_array = getChilds($typeArray,$code_cat_id);
					if(is_array($childs_array))$childs_array = array_keys($childs_array);
					$childs_array[]= $code_cat_id;
				}
			}
		}
	if(!empty($_SESSION['pcode_lv'])&&empty($pcode_goods)&&empty($childs_array) || !empty($pcode_goods)&&strpos($_SESSION['pcode_goods'], $goods['goods_sn']) !== false || !empty($childs_array)&&in_array($goods_cat_id, $childs_array)){
			$final_price=$goods['shop_price'];//使用优惠券除了团购产品,一律用原价
	}else{


	    /* 计算商品的促销价格 */
	    if ($goods['promote_price'] > 0)
	    {
	        $promote_price = bargain_price($goods['promote_price'], $goods['promote_start_date'], $goods['promote_end_date']);
	    }
	    else
	    {
	        $promote_price = 0;
	    }



		$goods['shop_price'] = ($promote_price > 0)?$promote_price:$goods['shop_price'];



	    //取得商品会员价格列表
	    $user_price    = $goods['shop_price'];

	    //比较商品的促销价格，会员价格，优惠价格
	    if (empty($volume_price) && empty($promote_price))
	    {
	        //如果优惠价格，促销价格都为空则取会员价格
	        $final_price = $user_price;
	    }
		elseif (!empty($_SESSION['user_rank']) && $_SESSION['user_rank'] == 'VIP Member')
		{
			//$final_price = min($price_arr); //如果是VIP会员则取最底价格
			//if(!empty($price_arr))
			$final_price = ($promote_price > 0)?$promote_price:empty($price_arr) ? $user_price : min($price_arr);
		}
	    elseif (!empty($volume_price) && empty($promote_price))
	    {
	        //如果优惠价格为空时不参加这个比较。
	        $final_price = min($volume_price, $user_price);
	    }
	    elseif (empty($volume_price) && !empty($promote_price))
	    {
	        //如果促销价格为空时不参加这个比较。
	        $final_price = min($promote_price, $user_price);
	    }
	    elseif (!empty($volume_price) && !empty($promote_price))
	    {
	        //取促销价格，会员价格，优惠价格最小值
	        $final_price =  ($promote_price > 0)?$promote_price:min($volume_price, $promote_price, $user_price);
	   }
	    else
	    {
	        $final_price = $user_price;
	    }

		$group_price = get_groupbuy_price($goods);
		$group_price = floatval($group_price);
		$final_price = $group_price?$group_price:$final_price;
	}

		    //如果需要加入规格价格
	if ($is_spec_price)
	{
	    if (!empty($spec))
	    {
	           $spec_price    = spec_price($spec);
	            $final_price += $spec_price;
	    }
	}

    //返回商品最终购买价格
    return $final_price;
}


function get_groupbuy_price($goods){
	$final_price = false;
	$now_time = gmtime();
	//判断团购是否过期
	$goods['is_groupbuy'] = (!empty($goods['is_groupbuy']) && $goods['groupbuy_start_date'] < $now_time && $goods['groupbuy_end_date'] > $now_time )?1:0;
	if($goods['is_groupbuy']){
		//$buyers = get_groupbuyer($goods['goods_id']);
		$final_price = $goods['groupbuy_price'];
	}
	return $final_price;
}

/*
function get_recommend_goods_sn($cat_id=59, $limit=2) {
	$typeArray =  read_static_cache('category_c_key',2);
	require_once(ROOT_PATH . 'lib/class.function.php');
	$cat_ids = Func::get_category_children_ids($typeArray, $cat_id);
	$cat_ids = substr($cat_ids,0,(strlen($cat_ids)-1));
	$sql = "SELECT g.goods_id,g.cat_id,g.goods_sn,g.add_time,gcr.conversion_rate
	FROM eload_goods g left join eload_category gc ON g.cat_id = gc.cat_id
	LEFT JOIN eload_goods_conversion_rate gcr ON g.goods_id = gcr.goods_id
	WHERE UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL -230 DAY)) < g.add_time
	AND g.is_on_sale = 1
	AND g.cat_id IN(".$cat_ids.")
	ORDER BY gcr.conversion_rate DESC LIMIT ". $limit ."";
	//echo $sql;exit;
	$res = $GLOBALS['db']->arrQuery($sql);
	if(is_array($res)) {
		foreach($res as $key=>$value) {
			$goods_list_sn .= "'". $value['goods_sn'] . "',";
		}
		$goods_list_sn = substr($goods_list_sn,0,(strlen($goods_list_sn)-1));
	}
	return $goods_list_sn;
}


function get_hot_goods_sn($cat_id=59, $limit=2) {
	$typeArray =  read_static_cache('category_c_key',2);
	require_once(ROOT_PATH . 'lib/class.function.php');
	$cat_ids = Func::get_category_children_ids($typeArray, $cat_id);
	$cat_ids = substr($cat_ids,0,(strlen($cat_ids)-1));
	if(count($cat_ids) > 5) {
		foreach($cat_ids as $key=>$value) {
			if($key > 6) {
				unset($cat_ids[$key]);
			}
		}
	}
	if(!empty($cat_ids)) {
		$sql = "SELECT g.goods_id,g.goods_sn,g.cat_id,gh.hitnum FROM eload_goods g
		LEFT JOIN eload_goods_hits gh ON g.goods_id = gh.goods_id
		WHERE g.is_on_sale = 1 AND g.cat_id IN(".$cat_ids.")
		ORDER BY gh.hitnum DESC LIMIT 1";
		$res = $GLOBALS['db']->arrQuery($sql);
		if($res) {
			$goods_list_sn = "'" . $res[0]['goods_sn'] . "'";
		}
	}
	return $goods_list_sn;
}
*/

/**
 * 获得推荐商品
 *
 * @access  public
 * @param   string      $type       推荐类型，可以是 best, new, hot
 * @return  array
 */
function get_recommend_goods($type = 'new', $limt = 25,$len = 50,$isdatu=false)
{
	global $cur_lang, $default_lang;
    if (!in_array($type, array('new', 'special','free_shipping','promote', 'daily'))) {
        return array();
    }
	//初始化数据
	switch ($type){
		case 'new':
			$activeid = 3;
			break;
		case 'special':
			$activeid = 1;
			break;
		//每日推荐
		case 'daily':
			$sql = 'SELECT g.goods_id,g.cat_id,g.goods_sn,g.add_time,g.is_promote,g.promote_end_date,gr.is_pass FROM eload_goods g LEFT JOIN eload_review gr ON g.goods_id = gr.goods_id WHERE UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL -230 DAY)) < g.add_time AND g.is_promote = 1 AND g.promote_end_date >= UNIX_TIMESTAMP(NOW()) AND gr.is_pass = 1 GROUP BY g.goods_id LIMIT 100';
			$res = $GLOBALS['db']->arrQuery($sql);
			$goods_list_sn = "'". $res[rand(0,count($res))]['goods_sn'] ."'";
			break;
	}
	if(!empty($goods_list_sn)) {
		$where  = " AND goods_sn IN(".$goods_list_sn.")";
	}
	$sql = 'SELECT g.goods_id,cat_id, goods_title,goods_name_style,is_free_shipping,shop_price,g.market_price,goods_thumb,goods_grid,sort_order,promote_price,promote_start_date,promote_end_date,url_title,is_superstar ' .
	   ' FROM ' . GOODS . ' AS g left join ' .GOODS_STATE.' s on g.goods_id=s.goods_id'.
	   ' WHERE is_on_sale = 1 AND is_alone_sale = 1  and is_login =0  AND is_delete = 0  '.$where.' '.
	   " LIMIT $limt";
	$goods_res = $GLOBALS['db']->arrQuery($sql);
	$arr = array();
	foreach ($goods_res as $row)
	{
		if ($row['promote_price'] > 0)
		{
			$promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
		}
		else
		{
			$promote_price = 0;
		}
		$arr[$row['goods_id']]['goods_title']       = $row['goods_title'];
		$arr[$row['goods_id']]['goods_id']       = $row['goods_id'];
		$arr[$row['goods_id']]['cat_id']           = $row['cat_id'];
		$arr[$row['goods_id']]['is_free_shipping'] = $row['is_free_shipping'];
		$arr[$row['goods_id']]['short_name']       = sub_str($row['goods_title'],$len);
		$arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'], $isdatu?$row['goods_grid']:$row['goods_thumb'], true);
		$arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_title'],$row['goods_name_style']);
		$arr[$row['goods_id']]['shop_price']       = ($promote_price>0)?price_format($promote_price):price_format($row['shop_price']);
		$arr[$row['goods_id']]['url_title']        = get_details_link($row['goods_id'],$row['url_title']);
		$arr[$row['goods_id']]['market_price']     = $row['market_price'];
		$arr[$row['goods_id']]['is_superstar']     = $row['is_superstar'];
        $arr[$row['goods_id']]['promote_zhekou']   = $row['promote_price'] > 0 && $row['market_price'] > 0 ? round(($row['market_price'] - $row['promote_price']) / $row['market_price'], 2) * 100 : '';
	}
	// 多语言 fangxin 2013/07/05
	if($cur_lang != $default_lang) {
		if(is_array($arr)) {
			foreach($arr as $key=>$value) {
				$sql = 'SELECT g.*' .
						' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
						" WHERE g.goods_id = '$key'";
				if($row_lang = $GLOBALS['db']->selectinfo($sql)) {
					$arr[$key]['goods_title'] = $row_lang['goods_title'];
					$arr[$key]['short_name']  = sub_str($row_lang['goods_title'], $len);
				}
			}
		}
	}
	return  $arr;
}

/**
 * 获得推荐商品
 *
 * @access  public
 * @param   string      $type       推荐类型，可以是 best, new, hot
 * @return  array
 */
function get_recommend_goods_new($type = 'recommended_deals',$cat_id=0)
{
    if (!in_array($type, array('recommended_deals', 'hot_deals','cat_recommend_products_top','search_recommend_products_left'))) {
        return array();
    }
	$data = read_static_cache('week_goods',1);
	switch ($type){
		case 'recommended_deals':
			$arr = $data['recommended_deals'];
			break;
		case 'hot_deals':
			$arr = $data['hot_deals'];
			break;
		case 'cat_recommend_products_top':
			$arr = $data['cat_recommend_products_top'][$cat_id];
			break;
		case 'search_recommend_products_left':
			$arr = $data['search_recommend_products_left'];
			break;
	}
	return  $arr;
}

//=============================================
//---生成地区缓存---------------------------
//=============================================
function creat_area(){
	global $db,$_CFG;
    $area_arr = array();

    $sql = 'SELECT * FROM ' . REGION. "  ORDER BY region_name";
    $res = $GLOBALS['db']->arrQuery($sql);
    foreach($res as $key=>$row){
        $area[$row['region_id']]=$row;
        $state = $GLOBALS['db']->getOne("select group_concat(province) as state from  eload_province where country_id = $row[region_id]");
        if($state){
            $states = explode(',',$state);
            sort($states,SORT_STRING);
            $area[$row['region_id']]['state'] = $states;
        }else{
            $area[$row['region_id']]['state'] = '';
        }
        if(empty($row['code'])){
            $area[$row['region_id']]['code'] = '';
        }
    }


	write_static_cache('area_key', $area,2);
}

//更改SESSID为用户的EMAIL，用来保存购物车中的物品
function ChangeSessId(){
	global $db;
	$sql = "UPDATE ".CART." SET session_id = '".$_SESSION['email']."' WHERE session_id = '".$_COOKIE['PHPSESSID']."' ";
	$db ->query($sql);


	$sql = "select min(rec_id) as rec_id,goods_sn,goods_attr_id,sum(goods_number) as tnum,goods_price from ".CART." where  session_id = '".$_SESSION['email']."'  group by goods_sn,goods_attr_id,goods_price  having count(*) > 1";
	$goods = $db->arrQuery($sql);
	foreach($goods as $val){


		$sql = "delete from ".CART." where goods_sn = '".$val['goods_sn']."' and rec_id <> '".$val['rec_id']."' and session_id = '".$_SESSION['email']."' ";
		$db ->query($sql);  //删除重复

		if ($val['goods_price'] != '0.01'){
			$sql = "update ".CART." set goods_number = '".$val['tnum']."' where  rec_id = '".$val['rec_id']."' and session_id = '".$_SESSION['email']."' ";
			$db ->query($sql); //更新数量
		}

	}

/*	print_r($goods);
	$sql = "select * from ".CART." where session_id = '".$_SESSION['email']."'";
	$goods = $db->arrQuery($sql);
	print_r($goods);
	exit;
*/	return true;
}


/**
 * 查询评论内容
 *
 * @access  public
 * @params  integer     $id
 * @params  integer     $type
 * @params  integer     $page
 * @return  array
 */
function assign_comment($id, $type, $pages = 1)
{
	global $_CFG;
    /* 取得评论列表 */
    $count = $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' .COMMENT.
           " WHERE id_value = '$id' AND comment_type = '$type' AND status = 1 AND parent_id = 0");
    $size  = !empty($GLOBALS['_CFG']['comments_number']) ? $GLOBALS['_CFG']['comments_number'] : 5;
    $page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;
    $sql = 'SELECT * FROM ' . COMMENT .
            " WHERE id_value = '$id' AND comment_type = '$type' AND status = 1 AND parent_id = 0".
            " ORDER BY comment_id DESC limit ".($pages-1) * $size.",$size";
    $res = $GLOBALS['db']->arrQuery($sql);
    $arr = array();
    $ids = '';
    foreach ($res as $row)
    {
        $ids .= $ids ? ",$row[comment_id]" : $row['comment_id'];
        $arr[$row['comment_id']]['id']       = $row['comment_id'];
        $arr[$row['comment_id']]['email']    = $row['email'];
        $arr[$row['comment_id']]['nickname'] = $row['nickname'];
        $arr[$row['comment_id']]['content']  = str_replace('\r\n', '<br />', htmlspecialchars($row['content']));
        $arr[$row['comment_id']]['content']  = str_replace('\n', '<br />', $arr[$row['comment_id']]['content']);
        $arr[$row['comment_id']]['rank']     = $row['comment_rank'];
        $arr[$row['comment_id']]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
    }
    /* 取得已有回复的评论 */
    if ($ids)
    {
        $sql = 'SELECT * FROM ' . COMMENT .
                " WHERE parent_id IN( $ids )";
        $res = $GLOBALS['db']->arrQuery($sql);
        foreach ($res  as $row)
        {
            $arr[$row['parent_id']]['re_content']  = str_replace('\n', '<br />', htmlspecialchars($row['content']));
            $arr[$row['parent_id']]['re_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
            $arr[$row['parent_id']]['re_email']    = $row['email'];
            $arr[$row['parent_id']]['re_nickname'] = $row['nickname'];
        }
    }

	$page = new page(array('total' => $count,'perpage'=>$size,'ajax'=>'ajax_page'));
	$pager  = $page->show(5);

    $cmt = array('comments' => $arr, 'pager' => $pager);

    return $cmt;
}




/**
 * 获得置顶文章列表
 * @return  array
 */
function get_top_article_list()
{
    /* 获得文章的信息 */
    $sql = "SELECT title,url_title,article_id,link ".
            "FROM " .ARTICLE. "  ".
            "WHERE is_open = 1 AND article_type = '1' order by sort_order desc,article_id desc ";
    $arr = $GLOBALS['db']->arrQuery($sql);
    return $arr;
}




/**
 * 更新用户SESSION,COOKIE及登录时间、登录次数。
 *
 * @access  public
 * @return  void
 */
function update_user_info()
{
    if (!$_SESSION['user_id'])
    {
        return false;
    }

    /* 查询会员信息 */
    $time = date('Y-m-d');
    $sql = 'SELECT  u.user_rank,u.firstname,u.lastname,u.visit_count, '.
            '  u.last_login, u.last_ip,u.user_type,u.email '.
            ' FROM ' .USERS. ' AS u ' .
            " WHERE u.user_id = '".$_SESSION['user_id']."'";
    if ($row = $GLOBALS['db']->selectinfo($sql))
    {
        /* 更新SESSION */
        $_SESSION['firstname']   = $row['firstname'];
        $_SESSION['lastname']    = $row['lastname'];
		$_SESSION['email']       = $row['email'];

		$sql = "select count(*) from ".ORDERINFO." where user_id='".$_SESSION['user_id']."'  AND order_status > 0 and order_status < 9 ";
		$dan_num = $GLOBALS['db']->getOne($sql);
		setcookie("WEBF-dan_num", $dan_num, gmtime()+3600*24*30*12, '/', COOKIESDIAMON);
		setcookie("WEBF-firstname", $row['firstname'], gmtime(), '/', COOKIESDIAMON);
		setcookie("WEBF-lastname", $row['lastname'], gmtime(), '/', COOKIESDIAMON);
		setcookie("usertype", $row['user_type'], gmtime(), '/', COOKIESDIAMON); //判断是否为广告联盟用户

        $_SESSION['last_time']   = $row['last_login'];
        $_SESSION['last_ip']     = $row['last_ip'];
        $_SESSION['visit_count']     = $row['visit_count'];
        $_SESSION['login_fail']  = 0;

		$user_rank = read_static_cache('users_grade', ADMIN_STATIC_CACHE_PATH);
		$_SESSION['user_rank'] = $user_rank[$row['user_rank']]['grade_en_name'];
		$_SESSION['discount']  = 1;

    }

    /* 更新登录时间，登录次数及登录ip */
    $sql = "UPDATE " .USERS. " SET".
           " visit_count = visit_count + 1,is_sync_to_mailsys = 0, ".
           " last_ip = '" .real_ip(). "',".
           " last_login = '" .gmtime(). "' ".
           " WHERE user_id = '" . $_SESSION['user_id'] . "'";
    $GLOBALS['db']->query($sql);
}


/**
 * 调用浏览历史
 *
 * @access  public
 * @return  string
 */
function insert_history()
{
   global $cur_lang, $default_lang;
   $str = '';
   $res = array();
    if (!empty($_COOKIE['WEB-history']))
    {
        $where = db_create_in($_COOKIE['WEB-history'], 'goods_id');
        $sql   = 'SELECT goods_id,url_title,cat_id,goods_thumb,shop_price,promote_price,promote_start_date,promote_end_date, goods_title FROM ' . GOODS .
                " WHERE $where AND is_on_sale = 1 AND  is_delete = 0 order by find_in_set(goods_id,'".$_COOKIE['WEB-history']."')";
        $arr = $GLOBALS['db']->arrQuery($sql);
        foreach ($arr as $row)
        {
			if ($row['promote_price'] > 0)
			{
				$promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
			}
			else
			{
				$promote_price = 0;
			}
			$row['shop_price'] = ($promote_price>0)?price_format($promote_price):price_format($row['shop_price']);
            $res[$row['goods_id']] = $row;
            $res[$row['goods_id']]['short_name'] = sub_str($row['goods_title'],30);
            $res[$row['goods_id']]['url_title']  = get_details_link($row['goods_id'],$row['url_title']);
            $res[$row['goods_id']]['goods_thumb']  = get_image_path($row['goods_id'],$row['goods_thumb']);
        }
    }
	// 多语言 fangxin 2013/07/05
	if($cur_lang != $default_lang) {
		if(is_array($res)) {
			foreach($res as $key=>$value) {
				$sql = 'SELECT g.*' .
						' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
						" WHERE g.goods_id = '$key'";
				if($row_language = $GLOBALS['db']->selectinfo($sql)) {
					$res[$key]['goods_title'] = $row_language['goods_title'];
					$res[$key]['short_name'] = sub_str($row_language['goods_title'],30);
				}
			}
		}
	}
    return $res;
}

/**
 * 处理上传文件，并返回上传图片名(上传失败时返回图片名为空）
 *
 * @access  public
 * @param array     $upload     $_FILES 数组
 * @param array     $type       图片所属类别，即data目录下的文件夹名
 *
 * @return string               上传图片名
 */
function upload_file($upload, $type)
{
	global $err;
    if (!empty($upload['tmp_name']))
    {
        $ftype = check_file_type($upload['tmp_name'], $upload['name'], '|png|jpg|jpeg|gif|doc|xls|txt|zip|ppt|pdf|rar|');
        if (!empty($ftype))
        {
            $name = date('Ymd');
            for ($i = 0; $i < 6; $i++)
            {
                $name .= chr(mt_rand(97, 122));
            }

            $name = $_SESSION['user_id'] . '_' . $name . '.' . $ftype;

            $target = ROOT_PATH . IMAGE_DIR . '/' . $type . '/' . $name;
            if (!move_upload_file($upload['tmp_name'], $target))
            {
                $err .= $GLOBALS['_LANG']['upload_file_error'];
                return false;
            }
            else
            {
                return $name;
            }
        }
        else
        {
            $err .= $GLOBALS['_LANG']['upload_file_type'];
            return false;
        }
    }
    else
    {
        $GLOBALS['err']->add($GLOBALS['_LANG']['upload_file_error']);
        return false;
    }
}



/**
 * 邮件发送
 *
 * @param: $name[string]        接收人姓名
 * @param: $email[string]       接收人邮件地址
 * @param: $subject[string]     邮件标题
 * @param: $content[string]     邮件内容
 * @param: $type[int]           0 普通邮件， 1 HTML邮件
 * @param: $notification[bool]  true 要求回执， false 不用回执
 *
 * @return boolean
 */
function send_mail($name, $email, $subject, $content, $type = 0, $notification=false)
{
    /* 如果邮件编码不是EC_CHARSET，创建字符集转换对象，转换编码 */
    if ($GLOBALS['_CFG']['mail_charset'] != EC_CHARSET)
    {
        $name      = eload_iconv(EC_CHARSET, $GLOBALS['_CFG']['mail_charset'], $name);
        $subject   = eload_iconv(EC_CHARSET, $GLOBALS['_CFG']['mail_charset'], $subject);
        $content   = eload_iconv(EC_CHARSET, $GLOBALS['_CFG']['mail_charset'], $content);
        $GLOBALS['_CFG']['email_shop_name'] = eload_iconv(EC_CHARSET, $GLOBALS['_CFG']['mail_charset'], $GLOBALS['_CFG']['email_shop_name']);
    }
    $charset   = $GLOBALS['_CFG']['mail_charset'];
    /**
     * 使用mail函数发送邮件
     */
    if ($GLOBALS['_CFG']['mail_service'] == 0 && function_exists('mail'))
    {
        /* 邮件的头部信息 */
        $content_type = ($type == 0) ? 'Content-Type: text/plain; charset=' . $charset : 'Content-Type: text/html; charset=' . $charset;
        $headers = array();
        $headers[] = 'From: "' . '=?' . $charset . '?B?' . base64_encode($GLOBALS['_CFG']['email_shop_name']) . '?='.'" <' . $GLOBALS['_CFG']['smtp_mail'] . '>';
        $headers[] = $content_type . '; format=flowed';
        if ($notification)
        {
            $headers[] = 'Disposition-Notification-To: ' . '=?' . $charset . '?B?' . base64_encode($GLOBALS['_CFG']['email_shop_name']) . '?='.'" <' . $GLOBALS['_CFG']['smtp_mail'] . '>';
        }

        $res = @mail($email, '=?' . $charset . '?B?' . base64_encode($subject) . '?=', $content, implode("\r\n", $headers));

        if (!$res)
        {
            //echo '邮件发送失败';
            return false;
        }
        else
        {
            return true;
        }
    }
    /**
     * 使用smtp服务发送邮件
     */
    else
    {

		$smtpserver = "k2smtpout.secureserver.net";//SMTP服务器
		$smtpserverport =25;//SMTP服务器端口
		$smtpusermail = "server@dealsmachine.com";//SMTP服务器的用户邮箱
		$smtpemailto = 'qngb3@163.com';
		$smtpuser = "server";//SMTP服务器的用户帐号
		$smtppass = "e6ta7996";//SMTP服务器的用户密码

		$mailsubject = $subject;//邮件主题
		$mailbody = $content;//邮件内容

		$mailtype = ($type == 1) ? "HTML":"TXT";//邮件格式（HTML/TXT）,TXT为文本邮件
		$smtp = new smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);//这里面的一个true是表示使用身份验证,否则不使用身份验证.
		$smtp->debug = true;//是否显示发送的调试信息
		$aa = $smtp->sendmail($smtpemailto, $smtpusermail, $mailsubject, $mailbody, $mailtype);
    }
}


function eload_iconv($source_lang, $target_lang, $source_string = '')
{
    static $chs = NULL;

    /* 如果字符串为空或者字符串不需要转换，直接返回 */
    if ($source_lang == $target_lang || $source_string == '' || preg_match("/[\x80-\xFF]+/", $source_string) == 0)
    {
        return $source_string;
    }

    if ($chs === NULL)
    {
        require_once(ROOT_PATH . 'lib/cls_iconv.php');
        $chs = new Chinese(ROOT_PATH);
    }

    return $chs->Convert($source_lang, $target_lang, $source_string);
}


/**
 * 取得商品列表：用于把商品添加到组合、关联类、赠品类
 * @param   object  $filters    过滤条件
 */
function get_goods_list($filter)
{
	$where = ' WHERE is_delete = 0 ';

	$goods_sn = $filter['keyword'];

	$goods_sn = str_replace(' ',"",$goods_sn);
	$goods_sn = str_replace(',',"','",$goods_sn);

	$goods_sn = "'$goods_sn'";


	$where .= !empty($filter['cat_id']) && intval($filter['cat_id']) > 0 ? '  AND  ' . get_children(intval($filter['cat_id'])) : '';
    $where .= !empty($filter['keyword'])? " AND (goods_title like '%" . trim($filter['keyword']) ."%') or goods_sn in($goods_sn)": " ";
    /* 取得数据 */
    $sql = 'SELECT goods_id, goods_title, shop_price,peijian_price '.
           'FROM ' . GOODS . ' AS g ' . $where .
           'LIMIT 40';
  // echo $sql;
  // exit();
    $row = $GLOBALS['db']->arrQuery($sql);
    return $row;
}

/*-----------------作用：生成'配送方式'的缓存文件 shipping_method.php----------------------*/
function create_shipping_cache(){
	$sql = "select ship_id,ship_name,ship_save,ship_desc,ship_order as sort_order from ".SHIPPING." where enable=1";
	$arr_ship = $GLOBALS['db']->arrQuery($sql);
	$arr_ship_an = array();
	$ships=",";
	foreach($arr_ship as $k => $ship){
		$arr_ship_an[$k+1] = $ship;
		$arr_ship_an[$k+1]['id'] = $k+1;
		//$ships=$ships.strval($ship["ship_id"]).",";

	}
	//$GLOBALS['db']->update(SHIPPING,"shipping='".$ships."'","region_id=".);
	write_static_cache("shipping_method",$arr_ship_an, ADMIN_STATIC_CACHE_PATH);
	return;
}
/*--------------------------------------------------------------------------------------*/

/*-----------------作用：生成'支付方式'的缓存文件 payment.php----------------------*/
function create_payment_cache(){
	$sql = "select pay_id,pay_code,pay_name,pay_brief as pay_shuoming,pay_desc,pay_logo as logo,pay_order as sort_order,enable as enabled from ".PAYMENT;
	$arr_pay = $GLOBALS['db']->arrQuery($sql);
	$arr_pay_an = array();
	$pays=",";
	foreach($arr_pay as $k => $pay){
		$arr_pay_an[$pay['pay_code']] = $pay;
		//$ships=$ships.strval($ship["ship_id"]).",";
	}
	//$GLOBALS['db']->update(SHIPPING,"shipping='".$ships."'","region_id=".);
	write_static_cache("payment",$arr_pay_an, ADMIN_STATIC_CACHE_PATH);
	return;
}
/*--------------------------------------------------------------------------------------*/






//左边站内最新填加的产品
function newprocuts($limit = 15,$len = 60 ,$children = '',$is_suiji = false)
{
	global $db, $cur_lang, $default_lang;
	$sql = '   AND g.is_login = 0  AND g.goods_number > 0 ';
	if ($children != '') $sql .= " AND $children ";
	if ($is_suiji){
		//$sql = 'SELECT g.goods_id,g.cat_id, g.goods_title,g.goods_name_style,g.is_free_shipping,g.shop_price,g.goods_thumb,g.sort_order,g.url_title  ' .
		//   ' FROM ' . GOODS . ' AS g, (select goods_id  from '.ODRGOODS.' WHERE addtime <= '.gmtime().' and  addtime >= '.gmstr2time('-1 month').' group by goods_id order by sum(goods_price * goods_number) desc limit 10) as o ' .
	//	   ' WHERE g.goods_id = o.goods_id and g.is_on_sale = 1  AND g.is_alone_sale = 1  AND g.is_delete = 0 '.$sql.' '.
	//	   ' ORDER BY RAND() LIMIT '.$limit;

		   //抽取一个月的销售量
		 $sql = " SELECT g.goods_id,g.cat_id, g.goods_title,g.goods_name_style,g.is_free_shipping,g.shop_price,g.promote_price,g.promote_start_date,g.promote_end_date,g.point_rate,g.goods_thumb,g.sort_order,g.url_title  "  .
			' FROM ' . GOODS . ' AS g '.
		   ' WHERE g.is_on_sale = 1 AND g.goods_number > 0  AND g.is_alone_sale = 1 AND g.is_delete = 0 '.$sql.' '.
		   " ORDER BY week2sale desc,g.click_count desc,g.goods_id desc LIMIT ".$limit;
	}else{
		$sql = 'SELECT g.goods_id,g.cat_id, g.goods_title,g.url_title,g.goods_name_style,g.is_free_shipping,g.shop_price,g.promote_price,g.promote_start_date,g.promote_end_date,g.point_rate,g.goods_thumb,g.sort_order ' .
		   ' FROM ' . GOODS . ' AS g ' .
		   ' WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1  AND g.is_delete = 0  '.$sql.' '.
		   ' ORDER BY g.goods_id DESC LIMIT '.$limit;
	}
	$goods_res = $db->arrQuery($sql);
	$arr = array();
	foreach ($goods_res as $row){
		$arr[$row['goods_id']]['goods_title']       = $row['goods_title'];
		//if (!empty($row['zongjin']))
		//$arr[$row['goods_id']]['zongjin']           = $row['zongjin'];
		$arr[$row['goods_id']]['is_free_shipping'] = $row['is_free_shipping'];
		$arr[$row['goods_id']]['short_name']       = sub_str($row['goods_title'],60);
		$arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb'], true);
		$arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_title'],$row['goods_name_style']);
		if($row['promote_price'] > 0) {
			if(bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date'])) {
				$arr[$row['goods_id']]['shop_price']   = $row['promote_price'];
			} else {
				$arr[$row['goods_id']]['shop_price']   = $row['shop_price'];
			}
		} else {
			$arr[$row['goods_id']]['shop_price']       = price_format($row['shop_price']);
		}
		$arr[$row['goods_id']]['url_title']        = get_details_link($row['goods_id'],$row['url_title']);
	}

	// 多语言 fangxin 2013/07/05
	if($cur_lang != $default_lang) {
		if(is_array($arr)) {
			foreach($arr as $key=>$value) {
				$sql = 'SELECT g.*' .
						' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
						" WHERE g.goods_id = '". $key ."'";
				if($row_lang = $GLOBALS['db']->selectinfo($sql)) {
					$arr[$key]['short_name']  = sub_str($row_lang['goods_title'], 60);
				}
			}
		}
	}

	return $arr;
}

function get_powerful($date_table){

	if ($_SESSION["WebUserInfo"]["group_id"] == 3){  //判断权限，3为产品开发员
		$power_str = ' AND ';
		switch ($date_table){
			case GOODS:
				$power_str .= " add_user ='".$_SESSION["WebUserInfo"]["sa_user"]."'";
			break;

			case STORAGE:
				$power_str .= " add_user ='".$_SESSION["WebUserInfo"]["sa_user"]."'";
			break;

			case USERS:
				$power_str .= " add_user ='".$_SESSION["WebUserInfo"]["sa_user"]."'";
			break;
		}
	}else{
		$power_str = '  ';
	}

	return $power_str;
}

//------函数 add_point------------
//------@$user_id  用户 id
//------$point  float 要加或减的积分数,整数时为增加积分,负数时为减积分,
//------$point_type  int 积分类型 1 处理中积分,2为可用积分
//------$note 字符串,是说明积分用途或来源的备注

function add_point($user_id,$points,$point_type=2,$note)   //积分加减
{

	if($points!=0.01){
		$points=ceil($points);

	}else{
		$points=0;
	}
	//echo $points;
	//echo $point_type;
	if(is_numeric($points) && is_numeric($user_id)){

		//echo $points;
		//set rs_temp=server.CreateObject("adodb.recordset")
		//rs_temp.open "select * from sh_user where username ='"&username&"'",conn,1,2
		if($point_type == 1){//  'pending point  处理中积分
			//$pending_point = $GLOBALS['db']->getOne()
			$sql = "select pending_point from ".USERS." where user_id = $user_id";
			$pending_point = $GLOBALS['db']->getOne($sql);
			$pending_point = $pending_point + $points;
			if ($points<0 && $pending_point<$points)$points=$avaid_point;  //如果要减的积分大于现有的处理中积分,就只减完现有的积分
			$sql = "update ".USERS." set pending_point = $pending_point where user_id = $user_id";
			$GLOBALS['db']->query($sql);
			//$sql = "update ".USERS." set pending_point = pending_point +($points) where user_id = $user_id";
			//$GLOBALS['db']->query($sql);
		}
		elseif($point_type == 2){// 'Available point

		//	$sql = "update ".USERS." set avaid_point = avaid_point +($point) where user_id = $user_id";
			$sql = "select avaid_point from ".USERS." where user_id = $user_id";
			$avaid_point = $GLOBALS['db']->getOne($sql);
			if ($points<0 && $avaid_point<$points)$points=$avaid_point;  //如果要减的积分大于可用积分,就只减完可用积分
			//echo $points;
			$avaid_point = $avaid_point + $points;
			$sql = "update ".USERS." set avaid_point = $avaid_point where user_id = $user_id";
			$GLOBALS['db']->query($sql);
			if($points>0)
				add_point_record($user_id,$points,0,$avaid_point,$note);
			elseif($points<0){
				add_point_record($user_id,0,abs($points),$avaid_point,$note);
				///echo (abs($point));
			}
		}
	}
	//exit();
}

function recent_order($limit=50){
	//$sql ="select distinct(goods_id)from ".ODRGOODS." order by add_time desc limit 50"

	$sql = "select distinct(g.goods_title),g.goods_id,o.city,r.region_name from ".ORDERINFO." o,".ODRGOODS." og,".GOODS." g,".REGION." r where o.order_id=og.order_id and og.goods_id=g.goods_id and o.country=r.region_id order by o.add_time desc";
	if($limit>0)$sql.=" limit $limit ";
	//echo $sql;
	$goods_res = $GLOBALS['db']->arrQuery($sql);
}

// 增加积分变化记录
//$income 收获积分数,$outgo 减掉的积分数,$balance,积分余额
function add_point_record($user_id,$income,$outgo,$balance,$note){
	if(!is_numeric($income))$income =0 ;
	if(!is_numeric($outgo)) $outgo =0 ;
	$date = gmtime();
	$sql="insert into ".POINT_RECORD."(user_id,adddate,income,outgo,balance,note)values('$user_id','$date','$income','$outgo','$balance','$note')";
	$GLOBALS['db']->query($sql);
}



//根据订单状态来把有效积分数增加到用户帐户上
function act_caculate_point($new_state,$old_state,$user_id,$points,$order_sn){
	$points = ceil($points);
	switch ($old_state){
		//订单原来状态：未到款,已到款，取消，已退款
		case 0:
		case 1:
		case 10:
		case 11:
			//更新为备货
			if($new_state == 2){
				//add_point($user_id,$points,1,$note);  //增加处理中积分
			}elseif ($new_state == 3){  //改成已发货
				$note = "Gained DM Points from $order_sn";
				//西联(WesternUnion)付款与己使用积分订单不再送积分
				if(check_give_order_point($order_sn)) {
					add_point($user_id,$points,2,$note); //增加可用积分
				}
			}
			break;
		//原来状态是备货中
		case 2:
			if ($new_state == 3){  //改成已发货
				$note = "Gained DM Points from $order_sn";
				//西联(WesternUnion)付款与己使用积分订单不再送积分
				if(check_give_order_point($order_sn)) {
					add_point($user_id,$points,2,$note); //增加可用积分
				}
				//add_point($user_id,-$points,1,''); //减处理中积分
			}elseif ($new_state !=2){
				//add_point($user_id,-$points,1,''); //减处理中积分
			}
			break;
		//原来状态是已发货
		case 3:
			if ($new_state == 0 || $new_state == 1 || $new_state == 10 || $new_state == 11){  //改成未付款,已取消或已退款
				$note = "order #$order_sn have been cancelled";
				//西联(WesternUnion)付款与己使用积分订单不再送积分
				if(check_give_order_point($order_sn)) {
					add_point($user_id,-$points,2,$note) ; //减available积分
				}
			}elseif ($new_state == 2  )//改成备货中
			{
				$note = "order #$order_sn  was changed from [shiped out] to [Processing]";
				//西联(WesternUnion)付款与己使用积分订单不再送积分
				if(check_give_order_point($order_sn)) {
					add_point($user_id,-$points,2,$note) ; //减available积分
				}
			}
			break;
	}
}

/*
 检测订单是否赠送积分
 1、西联付款方式不赠送积分。
 2、订单己使用积分不再赠送积分。
 * @author       fangxin
 * @date         2014-02-24 PM
 */
function check_give_order_point($order_sn) {
	global $db;
	if(!empty($order_sn)) {
		$sql = "SELECT order_id,order_sn,pay_id,pay_name,used_point FROM ". ORDERINFO ." WHERE order_sn='". $order_sn ."' LIMIT 1";
		if($res = $db->selectInfo($sql)) {
			if('WesternUnion' == $res['pay_id'] || 0 < $res['used_point']) {
				return FALSE;
			}
		}
	}
	return TRUE;
}

//获取指定用户的可用积分
function get_avail_point($user_id){
	//if($user_id)
		global $db;
		$avail_point = $db->getOne("select avaid_point from ".USERS." where user_id = ".$user_id);

	return $avail_point;
}

//计算积分可以兑换的金额
function calculate_point_money($points){
	$point_arr  = array('20'=>1,'25'=>1.5,'50'=>2.5,'100'=>4,'200'=>7,'300'=>9,'500'=>15,'1000'=>30,'1500'=>45);
	if(array_key_exists($points, $point_arr)){
		return $point_arr["$points"];
	}else{
		return 0;
	}
}

//返回 用户的积分信息
//$goods_sum :产品总金额
//$user_id  用户ＩＤ
function get_point_info($user_id,$goods_sum){
    	global $_CFG;
    	$point_arr  = array('20'=>1,'25'=>1.5,'50'=>2.5,'100'=>4,'200'=>7,'300'=>9,'500'=>15,'1000'=>30,'1500'=>45);
	    $avail_point = get_avail_point($user_id);
		//echo $avail_point;
		//$goods_sum = $goods_sum*0.3;

		foreach ($point_arr as $k=>$v){
			if($goods_sum*0.3 > $v &&$avail_point>=$k){
				$use_point_max = $k;
				$point_money_max = $v;
				//unset($point_arr[$k]);
			}
		}
	    $point['point_money_max'] = empty($point_money_max)?0:$point_money_max;//最多可以兑换的金额
	    $point['use_point_max'] = empty($use_point_max)?0:$use_point_max;  //最多使用的积分数
	    $point['avail_point'] = empty($avail_point)?0:$avail_point; //用户账号里的可用积分
	    return $point;
}

//检查是否登录
function check_is_sign(){
	global $cur_lang_url, $cur_lang, $default_lang;
	$user_id =empty($_SESSION['user_id'])?0:$_SESSION['user_id'];
	if ($user_id == 0)
    {
		if($cur_lang != $default_lang) {
			$ref = '/' . $cur_lang . urlencode($_SERVER["REQUEST_URI"]);
		} else {
			$ref = urlencode($_SERVER["REQUEST_URI"]);
		}
        header("Location: ".DOMAIN_USER."/".$cur_lang_url."m-users-a-sign.htm?ref=".$ref);
        exit;
    }
}


/**
 * 获得商品详细页中热销商品
 */
function get_hot_goods($cat, $limt=5)
{
	global $cur_lang, $default_lang;
	$arr = array();
	if(empty($cat))return $arr;
    $sql = 'SELECT goods_id  FROM eload_goods where is_on_sale = 1 and is_alone_sale = 1 AND is_delete = 0 AND cat_id='.$cat.' ORDER BY week2sale desc limit 50';
    $result = $GLOBALS['db']->arrQuery($sql);
    shuffle($result);//数组打乱
    $n=count($result);
    $result_str='';
    for( $i = 0; $i < $n; $i ++ )
    {
       $result_str.=$result[$i]['goods_id'].',';
    }
    $result_str=substr($result_str,0,-1);
	if(empty($result_str))return $arr;
    $sql = 'SELECT goods_id,cat_id, goods_title,goods_name_style,shop_price,goods_thumb,goods_grid,sort_order,promote_price,promote_start_date,promote_end_date,url_title ' .
          ' FROM ' . GOODS . ' AS g ' .
          " WHERE goods_id IN ($result_str) ORDER BY FIND_IN_SET(goods_id,'$result_str') LIMIT $limt";

    $goods_res = $GLOBALS['db']->arrQuery($sql);
    $arr = array();
    foreach ($goods_res as $row){
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        }
        else
        {
            $promote_price = 0;
        }

        $arr[$row['goods_id']]['goods_title']       = $row['goods_title'];
        $arr[$row['goods_id']]['cat_id']           = $row['cat_id'];
        $arr[$row['goods_id']]['short_name']       = sub_str($row['goods_title'],50);
        $arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'],$row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_title'],$row['goods_name_style']);
        $arr[$row['goods_id']]['shop_price']       = ($promote_price>0)?price_format($promote_price):price_format($row['shop_price']);
        $arr[$row['goods_id']]['url_title']        = get_details_link($row['goods_id'],$row['url_title']);
    }
	// 多语言 fangxin 2013/07/05
	if($cur_lang != $default_lang) {
		if(is_array($arr)) {
			foreach($arr as $key=>$value) {
				$sql = 'SELECT g.*' .
						' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
						" WHERE g.goods_id = '$key'";
				if($row_language = $GLOBALS['db']->selectinfo($sql)) {
					$arr[$key]['goods_title'] = $row_language['goods_title'];
					$arr[$key]['short_name'] = sub_str($row_language['goods_title'],50);
				}
			}
		}
	}
    return  $arr;
}
/**
 * 获得商品详细页中相关商品
 */
function get_relate_goods($cat, $limt=8)
{
	global $cur_lang, $default_lang;
	$arr = array();
	if(empty($cat))return $arr;
    $sql = 'SELECT goods_id  FROM eload_goods where is_on_sale = 1 and is_alone_sale = 1 AND  is_delete = 0 and gifts_id=0 AND cat_id!='.$cat.' ORDER BY week2sale desc limit 50';
    $result = $GLOBALS['db']->arrQuery($sql);
    shuffle($result);//数组打乱
    $n=count($result);
    $result_str='';
    for( $i = 0; $i < $n; $i ++ )
    {
       $result_str.=$result[$i]['goods_id'].',';
    }
    $result_str=substr($result_str,0,-1);

    $sql = 'SELECT goods_id,cat_id, goods_title,goods_name_style,shop_price,goods_thumb,goods_grid,sort_order,promote_price,promote_start_date,promote_end_date,url_title ' .
          ' FROM ' . GOODS . ' AS g ' .
          " WHERE gifts_id=0 and goods_id IN ($result_str) ORDER BY FIND_IN_SET(goods_id,'$result_str') LIMIT $limt";

    $goods_res = $GLOBALS['db']->arrQuery($sql);
    $arr = array();
    foreach ($goods_res as $row){

        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        }
        else
        {
            $promote_price = 0;
        }

        $arr[$row['goods_id']]['goods_title']       = $row['goods_title'];
        $arr[$row['goods_id']]['cat_id']           = $row['cat_id'];
        $arr[$row['goods_id']]['short_name']       = sub_str($row['goods_title'],50);
        $arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_title'],$row['goods_name_style']);
        $arr[$row['goods_id']]['shop_price']       = ($promote_price>0)?price_format($promote_price):price_format($row['shop_price']);
        $arr[$row['goods_id']]['url_title']        = get_details_link($row['goods_id'],$row['url_title']);
    }
	// 多语言 fangxin 2013/07/05
	if($cur_lang != $default_lang) {
		if(is_array($arr)) {
			foreach($arr as $key=>$value) {
				$sql = 'SELECT g.*' .
						' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
						" WHERE g.goods_id = '$key'";
				if($row_language = $GLOBALS['db']->selectinfo($sql)) {
					$arr[$key]['goods_title'] = $row_language['goods_title'];
					$arr[$key]['short_name'] = sub_str($row_language['goods_title'],50);
				}
			}
		}
	}

    return  $arr;
}

//获取产品参与的活动列表
function get_join_activity_list($goods_sn,$type=1)
{
    global $db;

	$sql="SELECT * FROM eload_activity WHERE act_goods_list like '%".$goods_sn."%' and type=$type ORDER BY id DESC";
    $activity_arr=$db->arrQuery($sql);

	return $activity_arr;
}

//获取活动列表
function get_activity_list($str,$type)
{
    global $db;
    $arr=explode(',',$str);
    array_shift($arr);
    array_pop($arr);

	$sql="SELECT * FROM eload_activity WHERE type=".$type." ORDER BY id DESC";
    $activity_arr=$db->arrQuery($sql);
    $n=count($activity_arr);
    for( $i = 0; $i < $n; $i ++ )
    {
         $checked="";
         for( $ii = 0, $m = count($arr); $ii < $m; $ii ++ )
         {
             if ( $activity_arr[$i]["id"]==$arr[$ii] )
             {
                 $checked="checked";
                 break;
             }
         }
         $activity_arr[$i]["checked"]=$checked;
    }

	return $activity_arr;
}

/**
 * 创建一级分类及其所有子分类
 *
 * @param array $category_arr 所有分类
 */
function admin_create_category_children($category_arr) {
    $child_id     = array();

    foreach ($category_arr as $k => $v) {

        if (empty($v['parent_id'])) {
            $_children    = explode(',', admin_get_childs($category_arr, $v['cat_id']));
            array_pop($_children);
            $child_id[$k] = array('is_show' => $v['is_show'], 'is_login' => $v['is_login'], 'children' => $_children);
        }
    }

    write_static_cache('category_children', $child_id, 2);
}


/**
 * 获得指定的分类的文章列表
 *
 * @access  private
 * @param   integer     $cat_id
 * @return  array
 */
function get_article_list($cat_id, $key='', $limit=0)
{
	global $cur_lang, $default_lang;
    $limit=empty($limit)?5:$limit;
	$sql = '';
	if ($key!=''){
		$sql = " AND (title like '%".$key."%' or content like '%".$key."%') ";
	}else{
		$sql = "AND cat_id = '$cat_id'";
	}
    //* 获得文章的信息
    $sql = "SELECT title,url_title,article_id,link,site_link ".
        	    "FROM " .ARTICLE. "  ".
            	"WHERE is_open = 1  $sql  order by article_id limit $limit";
	if(!empty($key) && $cur_lang != $default_lang) {
		$sql = "SELECT a.article_id, a.title as title, a.url_title, a.link, a.site_link, al.title as title_lang, al.content as content_lang FROM ". ARTICLE ." a LEFT JOIN eload_article_muti_lang al ON a.article_id = al.article_id WHERE a.is_open = 1 AND al.lang = '". $cur_lang ."' AND (al.title like '%".$key."%' or al.content like '%".$key."%') ORDER BY article_id limit $limit";
	}
    $arr = $GLOBALS['db']->arrQuery($sql);
	//多语言
	if(!empty($arr) && $cur_lang != $default_lang) {
		foreach($arr as $key => $value) {
			$sql = "SELECT article_id, title, link FROM eload_article_muti_lang WHERE article_id = ". $value['article_id'] ." AND lang = '". $cur_lang ."'";
			$lang_res = $GLOBALS['db']->selectInfo($sql);
			$title = '';
			$link  = '';
			if(!empty($lang_res)) {
				$title = $lang_res['title'];
				$link  = $lang_res['link'];
				$arr[$key]['title'] = $title;
				if(!empty($link)) {
					$arr[$key]['link']  = $link;
				}
			}
		}
	}
    return $arr;
}

//返回列表的左边new arrivals 产品数组
function get_right_new_arrival($all_pro_count,$children){
	if($all_pro_count>=20)
		$sel_new_pro_count = 15;
	elseif($all_pro_count>=15)
		$sel_new_pro_count = 13;
	else
		$sel_new_pro_count=8;
	//echo $all_pro_count;
	$where = "  g.is_on_sale = 1   AND  ".
            "g.is_delete = 0 and g.is_alone_sale = 1";
    if($children)$where.="  AND ($children or " . get_extension_goods($children) . ")";


	$sql ="select  goods_title,shop_price,goods_thumb,goods_id from ".GOODS." as g  WHERE   $where ORDER BY goods_id DESC limit $sel_new_pro_count";
	//echo $sql;
	//exit();
	$goods =$GLOBALS['db']->arrQuery($sql);
	foreach ($goods as $k=>$v){
		$goods[$k]['goods_url'] = get_details_link($goods[$k]['goods_id']);
		$goods[$k]['shop_price'] = price_format($goods[$k]['shop_price']);
		$goods[$k]['short_goods_title'] = price_format($goods[$k]['shop_price']);
        $goods[$k]['goods_thumb'] = get_image_path($goods[$k]['goods_id'], $goods[$k]['goods_thumb']);
	}
	return $goods;
}

/**
 * 返回整形数组或字符串，POST主键时，一般不会过滤，直接IN($id)或=$id，此时，人为的修改: 1) OR (1=1，
 * 本来是UPDATE ... WHERE IN(1)，此刻为 IN(1) OR (1=1)永为真
 *
 * @param mixed $string       待转换字符串或数组
 * @param bool  $return_array 是否返回数组，默认false，返回字符串
 * @param bool  $include_zero 是否包含0，默认false，不包含
 *
 * @return mixed 如果$return_array为true，返回整数数组，否则返回用半角逗号隔开的字符串
 */
function map_int($string, $return_array = false, $include_zero = false) {
    $array = is_array($string) ? $string : explode(',', $string);
    $array = array_map('intval', $array);
    $array = $include_zero ? $array : array_filter($array, 'intval');
    return $return_array ? $array : join(',', $array);
}


/**
 * 从分类销售排行随机取产品
 *
 * @param int    $num           个数，默认5
 * @param string $smarty_assign smarty->assign，默认best_goods
 */
function get_cache_best_goods($num = 5, $smarty_assign = 'best_goods') {
    require_once(ROOT_PATH . 'lib/time.fun.php');

	global $db;

    $goods  = array();
    $sql    = 'SELECT content FROM ' . MEM_CACHE . " WHERE filename LIKE 'data-cache/category_data_cache/%'";
    $sql   .= " AND filename LIKE '%top_goods.php%'";
    $sql   .= " AND update_time>" . (gmtime() - 86400 * 2);
    $data   = $GLOBALS['db']->arrQuery($sql);

    if ($data) {
        shuffle($data);

        foreach ($data as $item) {

            if (count($goods) == $num) {
                break;
            }

            if ($item = unserialize($item['content'])) {
                shuffle($item);
                $goods[] = array_pop($item);
            }
        }
    }

	if ($smarty_assign) {
	    $GLOBALS['Arr'][$smarty_assign] = $goods;
	}
	else {
	    return $goods;
	}
}

function new_get_children($cat_id, $include_self = true, $return_array = false) {
    $cat_arr      = read_static_cache('category_c_key', 2);
    $cat_info     = $cat_arr[$cat_id];

    if (!$cat_info['parent_id']) {//涓�骇绫�
        $cat_arr      = read_static_cache('category_children', 2);
        $children_ids = $cat_arr[$cat_id]['children'];
        $include_self && array_unshift($children_ids, $cat_id);

        return $return_array ? $children_ids : join(',', $children_ids);
    }

    $cat_node     = $cat_info['node'];
    $cat_level    = $cat_info['level'];
    $children_ids = $include_self ? $cat_id : '';

    foreach ($cat_arr as $k => $v) {

        if ($v['level'] > $cat_level &&!empty($v['node'])&&!empty($cat_node)&& strpos($v['node'], $cat_node) === 0 && $k != $cat_id) {
            $children_ids .= ',' . $k;
        }
    }

    $children_ids = trim($children_ids, ',');

    return $return_array ? explode(',', $children_ids) : $children_ids;
}

//=============================================
//---生成赠品静态缓存-------------------------
//=============================================
function create_gifts(){
	global $db;
	$sql = "SELECT * FROM ".GIFTS." ORDER BY need_money  ASC";
	$db->query($sql);
	$catArr = array();//不带key
	$tempArr = array();//带key
	$js_data = array();//分类树



	while (($row = $db->fetchArray()) !== false) {
	    //ow['link_url'] = creat_nav_url($row['url_title'], $row['cat_id'], !$row['parent_id']);

		$tempArr[$row['gifts_id']] =  $row;
	    $catArr[] = $row;
	}

	write_static_cache('gifts_c', $catArr,2);
	write_static_cache('gifts_c_key', $tempArr,2);
	//admin_create_category_children($tempArr);
	//print_r($js_data);
	//exit();
	//isset($GLOBALS['tree']) && admin_create_category_js($js_data);
	unset($catArr, $tempArr, $js_arr);
}


/**
 * 下载文件
 *
 * @author       mashanling(msl-138@163.com)
 * @date         2012-08-25 13:55:16
 * @last modify  2012-11-01 09:43:02 by mashanling
 *
 * @param string $filepath 文件路径
 * @param string $filename 文件名
 *
 * @return void 无返回值
 */
function download_file($filepath, $filename = '') {
	$filename = $filename ? $filename : basename($filepath);
    $filetype = explode('.', $filename);
	$filetype = array_pop($filetype);
	$filesize = sprintf('%u', filesize($filepath));
	header('Pragma: public');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Cache-Control: pre-check=0, post-check=0, max-age=0');
	header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	header('Content-length: ' . $filesize);
    ob_end_clean();
	readfile($filepath);
	exit();
}


/**
 * 重定向
 *
 * @param string $url 重定向地址，默认/m-page_not_found.htm
 *
 * @return void 无返回值
 */
function redirect_url($url = '/m-page_not_found.htm', $status_code = false) {

    if ($url == '/m-page_not_found.htm') {//保持链接地址不变 by mashanling on 2012-07-10 14:42:52
        header('HTTP/1.0 404 Not Found');
        global $Arr, $_ACT, $_CFG;
        require_once(ROOT_PATH . 'fun/fun.global.php');
    	require_once(ROOT_PATH . 'fun/fun.public.php');
    	require_once(ROOT_PATH . 'lib/lib.f.goods.php');

    	$Arr['shop_title'] = 'Page Not Found - '.$_CFG['shop_title'];
    	$Arr['keywords'] = 'Page Not Found , '.$_CFG['shop_keywords'];
    	$Arr['cat_desc'] = 'Page Not Found , '.$_CFG['shop_desc'];
		$Arr['is_nofollow'] = '1';
    	$is_login_str = empty($_COOKIE['WEBF-dan_num']) ? 'category_login_html' : 'category_html';
    	$Arr['left_catArr']  = read_static_cache($is_login_str,2);

    	get_cache_best_goods();
    	$_ACT = 'page_not_found';
    	temp_disp();
    	exit;
    }

    if ($status_code) {

        switch ($status_code) {
            case 301:
                header('HTTP/1.0 301 Moved Permanently');
                break;

            case 404:
                header('HTTP/1.0 404 Not Found');
                break;
        }
    }

    header('Location: ' . $url);
    exit();
}//end redirect_url

/**
 * 验证密码是否符合规范。即1、密码长度不能少于6位 2、密码不能全数字，必须含有一位字母
 *
 * @author          mashanling <msl-138@163.com>
 * @date            2013-04-25 11:14:44
 *
 * @param string $password 验证密码
 *
 * @return bool true验证成功，否则false
 */
function check_password($password) {

    if (strlen($password) < 6 || preg_match('/^\d+$/i', $password)) {
        return false;
    }

    return true;
}





/**
 * 获取邮件模板商品
 *
 * @author          mashanling <msl-138@163.com>
 * @date            2013-06-14 08:56:57
 *
 * @param int   $template_id 模板id
 * @param array $mail_conf   模板标题,默认null,读取缓存
 *
 * @return array 商品数组
 */
function get_mail_template_goods($template_id, $mail_conf = null) {
    global $db;

    null === $mail_conf && require(ROOT_PATH . 'eload_admin/email_temp/mail_conf.php');

    $mail_template_goods = read_static_cache('mail_template_goods', 2);
    $mail_template_goods = $mail_template_goods ? $mail_template_goods : array();

    if (empty($mail_template_goods[$template_id]) || $mail_template_goods[$template_id]['time'] < gmtime() - 86400) {
        $goods_sn   = read_static_cache('mail_template', 2);
        $goods      = array();

        if (!empty($goods_sn[$template_id]['goods_sn'])) {
            $goods = get_mail_template_goods_data($goods_sn[$template_id]['goods_sn']);
        }

        $mail_template_goods[$template_id] = array('data' => $goods, 'time' => gmtime());
        write_static_cache('mail_template_goods', $mail_template_goods, 2);
    }

    return $mail_template_goods[$template_id]['data'];
}//end get_mail_template_goods

/**
 * 获取邮件模板指定编码商品
 *
 * @author          mashanling <msl-138@163.com>
 * @date            2013-06-15 09:37:46
 *
 * @param string   $goods_sn 商品编码,半角逗号隔开
 *
 * @return array 商品数组
 */
function get_mail_template_goods_data($goods_sn) {
    global $db, $cur_lang, $default_lang;;
    $goods    = array();
    $goods_sn = "'" . str_replace(',', "','", $goods_sn) . "'";
    $db->query($sql = 'SELECT goods_id,goods_title,url_title,goods_name,goods_img,shop_price,market_price,promote_price,promote_start_date,promote_end_date FROM ' . GOODS . " WHERE goods_sn IN({$goods_sn}) AND is_delete=0 AND is_on_sale=1 AND goods_number>0");

    while($row = $db->fetchArray()) {
        $price = $row['promote_price'] > 0 ? bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']) : 0;
        $goods[] = array(
            'shop_price'    => $price > 0 ? $price : $row['shop_price'],
            'market_price'  => $row['market_price'],
            'goods_title'   => $row['goods_name'] ? $row['goods_name'] : $row['goods_title'],
            'link_url'      => get_details_link($row['goods_id'], $row['url_title'], '', true),
            'goods_img'     => get_image_path(false, $row['goods_img']),
			'goods_id'      => $row['goods_id']
        );
    }
	// 多语言 fangxin 2013/07/05
	if($cur_lang != $default_lang) {
		if(is_array($goods)) {
			foreach($goods as $key=>$value) {
				$goods_id = $value['goods_id'];
				$sql = 'SELECT g.*' .
						' FROM ' . GOODS . '_' . $cur_lang .' AS g' .
						" WHERE g.goods_id = '$goods_id'";
				if($row_lang = $GLOBALS['db']->selectinfo($sql)) {
					$goods[$key]['goods_title'] = $row_lang['goods_name'] ? $row_lang['goods_name'] : $row_lang['goods_title'];
				}
			}
		}
	}

    return $goods;
}//end get_mail_template_goods_data

// 更新用户语言 fangxin 2013/07/22
function update_user_lang($email, $lang='en') {
	global $db;
	if(!empty($email)) {
		$sql_u = "SELECT user_id, email FROM ". USERS ." WHERE email='". $email ."'";
		$res = $db->selectInfo($sql_u);
		if($res) {
			$sql = "UPDATE ". USERS ." SET lang = '". $lang ."' WHERE email='". $email ."' LIMIT 1";
			$db->query($sql);
		}
	}
}

//前台页类目页顶级类导航 fangxin 2013/08/16
function getDynamicTreeTop($pId,$depth = 0,$isLimit = true,$selectid = 0)
{
	global $language,$cur_lang, $default_lang;
	if(empty($language) && empty($_COOKIE['WEBF-dan_num'])){
	    $whr_str = "  AND is_login = 0 ";
	}else{
		if(empty($_COOKIE['WEBF-dan_num'])){
			$whr_str = "  AND is_login = 0 ";
		}else{
			$whr_str = "";
		}
	}
	$limitStr = ($isLimit && $pId)?' limit 15':'';
	$html = '';
	if($cur_lang != $default_lang){
		$sql = "SELECT c.cat_id,c.parent_id,url_title,l.cat_name FROM ".CATALOG." c inner join ".CATALOG_LANG." l on c.cat_id=l.cat_id where parent_id = '".$pId."' and lang = '". $cur_lang ."' and is_show = 1 $whr_str ORDER BY parent_id,sort_order ASC,c.cat_id ASC $limitStr ";
	}else{
		$sql = "SELECT * FROM ".CATALOG." where parent_id = '".$pId."' and is_show = 1 $whr_str ORDER BY parent_id,sort_order ASC,cat_id ASC $limitStr ";
	}
	$catArr = $GLOBALS['db']->arrQuery($sql);
	$ArrNum = $GLOBALS['db']->getOne("SELECT count(*) FROM ".CATALOG." where parent_id = '".$pId."' and is_show = 1 $whr_str");
	foreach($catArr as $k => $v)
	{
		$inStyle = ($v['cat_id'] == '1260')?' class="imstyle"':'';
		$p = empty($v['parent_id'])?true:false;
		$urllink =  creat_nav_url($v['url_title'],$v['cat_id'],$p);
		if($k < 1) $css = "first_li";
		$html .= "<li data-cat=". $v['cat_id'] ." class='cat_js_litem ". $css ."'><a href='".$urllink."'$inStyle>".get_cat_name($v['cat_id'],$v['cat_name'])."</a><ul class=\"cat_sub_item\"></ul>";
		$html = $html."</li>";
		unset($k);
	}
	$html = $html ? '<ul>'.$html.'</ul>' : $html ;
	$html = str_replace('<ul><li>','<ul><li class="first_li">',$html);
	return $html;
}

//前台类目页多级分类子类 fangxin 2013/08/16
function createDynamicTreeSub($pId, $lang='en')
{
	$limitStr = "LIMIT 15";
	$whr_str = "  AND is_login = 0 ";
	$sql = "SELECT * FROM ".CATALOG." where parent_id = '".$pId."' and is_show = 1 $whr_str ORDER BY parent_id,sort_order ASC,cat_id ASC $limitStr ";
	$catArr = $GLOBALS['db']->arrQuery($sql);
	$ArrNum = $GLOBALS['db']->getOne("SELECT count(*) FROM ".CATALOG." where parent_id = '".$pId."' and is_show = 1 $whr_str");
	if($lang != 'en') {
		$cur_lang_url = $lang . '/';
	} else {
		$cur_lang_url = '';
	}
	foreach($catArr as $k => $v)
	{
		@$html .= "<div id=\"nav-".$v['cat_id']."\">";
		//一层
		if(!empty($lang) && $lang != 'en') {
			$sql = "SELECT c.cat_id,c.parent_id,url_title,l.cat_name FROM ".CATALOG." c inner join ".CATALOG_LANG." l on c.cat_id=l.cat_id where parent_id = '".$v['cat_id']."' and lang = '". $lang ."' and is_show = 1 $whr_str ORDER BY parent_id,sort_order ASC,c.cat_id ASC $limitStr ";
		}else{
			$sql = "SELECT * FROM ".CATALOG." where parent_id = '".$v['cat_id']."' AND is_show = 1 $whr_str ORDER BY parent_id,sort_order ASC,cat_id ASC $limitStr ";
		}
		$catSubArr = $GLOBALS['db']->arrQuery($sql);
		if(count($catSubArr) > 0) {
			foreach($catSubArr as $key=>$value) {
				//二层
				if($key < 1) @$css = "class=\"first_li\"";
				$p = empty($value['parent_id'])?true:false;
				$urllink =  creat_nav_url($cur_lang_url . $value['url_title'], $value['cat_id'], $p);
				@$html .= "<li $css><a href=\"". $urllink."\">".$value['cat_name']."";
				$arrNum = $GLOBALS['db']->getOne("SELECT count(*) FROM ".CATALOG." where parent_id = '".$value['cat_id']."' and is_show = 1 $whr_str");
				if($arrNum > 0) {
					$html .= '<img style="border:0;" class="rightarrowclass" src="/temp/skin3/images/styleimg/leftmenu_icon.gif">';
				}
				unset($arrNum);
				$html .= "</a>";
				unset($css);
				if(!empty($lang) && $lang != 'en') {
					$sql = "SELECT c.cat_id,c.parent_id,url_title,l.cat_name FROM ".CATALOG." c inner join ".CATALOG_LANG." l on c.cat_id=l.cat_id where parent_id = '".$value['cat_id']."' and lang = '". $lang ."' and is_show = 1 $whr_str ORDER BY parent_id,sort_order ASC,c.cat_id ASC $limitStr ";
				}else{
					$sql = "SELECT * FROM ".CATALOG." where parent_id = '".$value['cat_id']."' AND is_show = 1 $whr_str ORDER BY parent_id,sort_order ASC,cat_id ASC $limitStr ";
				}
				$catSubTreeArr = $GLOBALS['db']->arrQuery($sql);
				if(count($catSubTreeArr) > 1) {
					$html .= "<ul>";
					foreach($catSubTreeArr as $key => $value) {
						//三层
						if($key < 1) @$css = "class=\"first_li\"";
						$p = empty($value['parent_id'])?true:false;
						@$html .= "<li $css><a href=\"".$urllink."\">".$value['cat_name']."";
						$arrNum = $GLOBALS['db']->getOne("SELECT count(*) FROM ".CATALOG." where parent_id = '".$value['cat_id']."' and is_show = 1 $whr_str");
						if($arrNum > 0) {
							$html .= '<img style="border:0;" class="rightarrowclass" src="/temp/skin3/images/styleimg/leftmenu_icon.gif">';
						}
						unset($arrNum);
						$html .= "</a>";
						unset($css);
						if(!empty($lang) && $lang != 'en') {
							$sql = "SELECT c.cat_id,c.parent_id,url_title,l.cat_name FROM ".CATALOG." c inner join ".CATALOG_LANG." l on c.cat_id=l.cat_id where parent_id = '".$value['cat_id']."' and lang = '". $lang ."' and is_show = 1 $whr_str ORDER BY parent_id,sort_order ASC,c.cat_id ASC $limitStr ";
						}else{
							$sql = "SELECT * FROM ".CATALOG." where parent_id = '".$value['cat_id']."' AND is_show = 1 $whr_str ORDER BY parent_id,sort_order ASC,cat_id ASC $limitStr ";
						}
						$catSubFourArr = $GLOBALS['db']->arrQuery($sql);
						if(count($catSubFourArr) > 1) {
							$html .= "<ul>";
							foreach($catSubFourArr as $key => $value) {
								//四层
								if($key < 1) @$css = "class=\"first_li\"";
								$p = empty($value['parent_id'])?true:false;
								$urllink =  creat_nav_url($cur_lang_url . $value['url_title'],$value['cat_id'],$p);
								@$html .= "<li $css><a href=\"".$urllink."\">".$value['cat_name']."</a>";
								unset($css);
							}
							$html .= "</ul>";
							unset($css);
						}
						unset($css);
					}
					$html .= "</ul>";
				}
				$html .= "</li>";

			}
		}
		$html .= "</div>";
	}
	file_put_contents(ROOT_PATH . "/data-cache/cat_category_". $lang .".htm", $html);
	//return $html;
}

//前台一级分类 fangxin 2013/08/16
function createDynamicGoodsCategory($pId, $lang='en')
{
	$limitStr = "LIMIT 15";
	$whr_str = "  AND is_login = 0 ";
	if(!empty($lang) && $lang != 'en') {
		$sql = "SELECT c.cat_id,c.parent_id,url_title,l.cat_name FROM ".CATALOG." c inner join ".CATALOG_LANG." l on c.cat_id=l.cat_id where parent_id = '".$pId."' and lang = '". $lang ."' and is_show = 1 $whr_str ORDER BY parent_id,sort_order ASC,c.cat_id ASC $limitStr ";
	}else{
		$sql = "SELECT * FROM ".CATALOG." where parent_id = '".$pId."' and is_show = 1 $whr_str ORDER BY parent_id,sort_order ASC,cat_id ASC $limitStr ";
	}
	$catArr = $GLOBALS['db']->arrQuery($sql);
	$html = '<ul style="display:block;">';
	foreach($catArr as $key => $value)
	{
		$p = empty($value['parent_id'])?true:false;
		$urllink =  creat_nav_url($value['url_title'],$value['cat_id'],$p);
		@$html .= "<li $css><a href=\"".$urllink."\">".$value['cat_name']."</a></li>";
	}
	$html .= "</ul>";
	file_put_contents(ROOT_PATH . "/data-cache/goods_category_". $lang .".htm", $html);
	//return $html;
}


/**
 * 清理cdn缓存
 *
 * @author       mashanling(msl-138@163.com)
 * @date         2012-10-04 17:22:34
 * @last modify  2012-10-29 10:20:07 by mashanling
 *
 * @param string $filename 清理的文件名
 *
 * @return void 无返回值
 */
function clear_cdn_cache($filename) {
    if (!$filename || IS_LOCAL) {
        return;
    }

    if (strpos($filename, ',')) {//多文件名

        foreach (explode(',', $filename) as $v) {
            clear_cdn_cache($v);
        }
    }
    else {
        if (strpos($filename, '/') !== 0) {
            $filename = '/' . $filename;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, 'http://cloud9.faout.com/purge' . $filename);
        curl_exec($ch);

        curl_setopt($ch, CURLOPT_URL, CDN_API_PATH . $filename);
        curl_exec($ch);
        curl_close($ch);
    }
}//end clear_cdn_cache

/**
 * ajax输出结果
 *
 * @author       mashanling(msl-138@163.com)
 * @date         2012-12-01 17:13:31
 * @last modify  2012-12-01 17:13:31 by mashanling
 *
 * @param string $data 输出数据
 * @param string $type 输出类型，json
 *
 * @return void 无返回值
 */
function ajaxReturn($data = '', $type = 'html') {

    if (isset($_GET['jsoncallback'])) {//jsonp格式
        $type     = 'json';
        $callback = $_GET['jsoncallback'];
    }

    switch (strtolower($type)) {
        case 'json': //返回JSON数据格式到客户端 包含状态信息
            header('Content-Type: application/json; charset=utf-8');
            $data = is_array($data) ? $data : array('data' => $data);
            $data = json_encode($data);

            if (isset($callback)) {//jsonp格式
                $data = $callback . '(' . $data . ')';
            }

            exit($data);
        break;

        default:
            header('Content-Type: text/html; charset=utf-8');
            exit($data);
    }
}

function rand_keys($length)
{
	$key = '';
	$pattern='1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
	for($i=0;$i<$length;$i++)
	{
	   $key .= $pattern{mt_rand(0,35)};    //生成php随机数
	}
	return $key;
}

//浏览器语言对应币种 fangxin 2014-02-28 PM
function get_currency() {
	$country_currency = read_static_cache('country_currency', FRONT_STATIC_CACHE_PATH);
	$browser_temp_lang = explode(",",$_SERVER['HTTP_ACCEPT_LANGUAGE']);
	$browser_arr = explode("-",$browser_temp_lang[0]);
	if(!empty($browser_arr[1])) {
		$browser_lang = $browser_arr[1];
	} else {
		$browser_lang = $browser_arr[0];
	}
	if('en' == $browser_lang || 'gb' == $browser_lang) {$browser_lang = 'UK';}
	$browser_lang = strtoupper($browser_lang);
	$currency = isset($country_currency[$browser_lang])?$country_currency[$browser_lang]:'';
	$data = array('lang'=>$browser_lang, 'currency'=>$currency);
	return $data;
}

//获取所有商品分类列表,只取审核通过数据
function cat_list_is_show($selected = 0,$isArr=false,$depth=false,$cat_priv='')
{
	global $cur_lang, $default_lang;
    static $res = NULL;
	global $db,$tree,$cur_lang;
	if(empty($cur_lang))$cur_lang='en';
	$tree=array();
	$catArr =  read_static_cache('category_c_key',2);
	//过滤不显示分类
	foreach($catArr as $key=>$value) {
		if(!$value['is_show']) unset($catArr[$key]);
	}
	//分类限制
	$allow_cat_id='';
    if(!empty($cat_priv)){  //获取拥有的所有权限的所有字符串
        $priv_cat_big_arr = explode(',',$cat_priv);
        $category_children = read_static_cache('category_children', 2);    //顶级分类
		foreach ($priv_cat_big_arr as $k=>$v){
        		if(!empty($v))$allow_cat_id.=$v.",";
        		if(!empty($category_children[$v]['children']))array_push($category_children[$v]['children'],$v);
        		if(!empty($category_children[$v]['children']))$allow_cat_id.=implode(',',$category_children[$v]['children']).",";
        }
        $allow_cat_id.="0";
    }
    $allow_cat_id.='0';
	if(!empty($cat_priv)){  //过滤没有权限的分类
		$allow_cat_id_arr = explode(',',$allow_cat_id);
		foreach ($catArr as $k=>$v){
			if(!in_array($v['cat_id'],$allow_cat_id_arr)){
				unset($catArr[$k]);
			}
		}
	}

	if ($isArr) {
		$tree = NULL;
		$tree = getChilds($catArr,$selected);
		if (!is_array($tree)) $tree=array();
		return $tree;
	}else{
		$select = '';
		if ($depth){   //只显示一级
		 if(!empty($catArr)){
			foreach ($catArr as $k => $var)
			{
				if ($catArr[$k]['is_show']){
					if ($catArr[$k]['parent_id'] == '0'){
						$select .= '<option value="' . $var['cat_id'] . '" ';
						$select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
						$select .= '>';
						$select .= $var['cat_name'] . '</option>';
					}else{
						unset($catArr[$k]);
					}
				}
			}
		 }
		}else{
			$catArr = toTree($catArr,$pk='cat_id');
			treetoary($catArr,0,'cat_name');
			$catArr = $tree;
			foreach ($catArr as $var)
			{
				$select .= '<option value="' . $var['cat_id'] . '" ';
				$select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
				$select .= '>';
				$select .= $var['cat_name'] . '</option>';
			}
		}
		return $select;
	}
}


/**
 * email显示处理
 * Jim on 2014-5-8
 */

function email_disp_process($email){
    //$email = decrypt_email($email);

	if(false !== strpos($email, '@')){
		$email  =  '*'.substr($email,1);
		$email_arr = explode('@',$email);
		return($email_arr[0]."****");
	}else{
		return $email;
	}
};

/**
 * 检查是否已经有发email
 * @email email地址
 * @param $temp_id  模板ＩＤ
 * @param $erp_record_id ERP自增ＩＤ
 * Jim on 2014-5-14
 */
function check_have_send_mail($email='',$temp_id=0,$erp_record_id=0){
	global  $db;
	$where ='1';
	if(is_email($email)){
		$where .= " and email = '{$email}'";
	}
	if($temp_id){
		$where .= " and template_id = '{$temp_id}'";
	}
	if($erp_recorde_id){
		$where .= " and erp_record_id = '{$erp_record_id}'";
	}
	if($where === '1')return false;
	if($db->getone("select count(1) from ".Email_send_history ." where $where" )){
		return true;
	}else{
		return false;
	}
};

function vp($data,$ty=1)
{
	echo '<pre>';
	print_r($data);

	if($ty==1)
	{
		exit();
	}else{

	}

}
