<?php
define('INI_WEB', true);
$cur_lang ='en';
require('../lib/global.php');
//获取所有商品分类列表,不显示隐藏分类
function cat_list_display($selected = 0,$isArr=false,$depth=false,$cat_priv='')
{
	global $cur_lang, $default_lang;
    static $res = NULL;
	global $db,$tree,$cur_lang;
	if(empty($cur_lang))$cur_lang='en';
	$tree=array();
	$catArr =  read_static_cache('category_c_key',2);
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

	foreach($catArr as $k=>$v) {
		if($v['is_show'] == 0) {
			unset($catArr[$k]);
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

echo '<select id="china_syn_cat" name="china_syn_cat"><option value="">请选择...</option>'.cat_list_display().'</select>';
//echo '<select id="china_syn_cat" name="china_syn_cat"><option value="">请选择...</option>'.cat_list().'</select>';
?>