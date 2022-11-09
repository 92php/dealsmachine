<?php
/**
 * 生成关键词
 *
 */
class keywords{
	var $arr_keyword;//生成的关键词数
	var $y=0;  //入库的关键词数

	//var $input_
	
/**
 * 创建产品的abc关键词
 *
 * @param string $goods_ids 产品id,eg:505,100
 * @return 录入关键词数
 */
function create_goods_keyword($goods_ids){
	global $db;
	if(!$goods_ids)return ;
	
	$sql = "select * from ".CATALOG . " where parent_id=0";
	$b_cat = $db->arrQuery($sql);  //顶级分类
	$b_cat = fetch_id($b_cat,'cat_id'); //带key的顶级分类
	
	$sql = "select goods_title,goods_brief,goods_id,goods_name,cat_id from ".GOODS." where goods_id in ($goods_ids)";
	$goods_arr = $db -> arrQuery($sql);
	//echo  $sql;
	$str ="";
	foreach ($goods_arr as $val){
		//echo $val['goods_id']."fdsfds";
		
		$model_arr=$this->Model_keyword($val['goods_name'],$val['cat_id'],$b_cat);
		$str .= implode(',',$model_arr);
	    preg_match_all("/[0-9a-zA-Z\.\#+]{1,}/",$val['goods_title'],$match); //提取字母和数字
		$str1 = implode(',',$this->split_word($match[0],1,$val['goods_id']));
		$str2 = implode(',',$this->split_word($match[0],2,$val['goods_id']));
		$str3 = implode(',',$this->split_word($match[0],3,$val['goods_id']));
		$str  .= $str1.',';
		$str  .= $str2.',';
		$str  .= $str3.',';


	}
	$sql = "update eload_goods set is_create_keyword = 1 where goods_id in ($goods_ids)";
	$db->query($sql);
	$total_keyArr = explode(',',$str);
	$this->arr_keyword = count($total_keyArr);
	//echo "array length:".count($total_keyArr)."<br>";
	
	$y = 0;
	foreach($total_keyArr as $k => $v){
		$keyw =  trim($v);
		$keyw =  trim($keyw);
		if (($keyw!='') && (strlen($keyw)<150)){
			// echo $keyw.'<br>';
		$keyw =  addslashes(trim($keyw));
		$sql = "select count(*) from ".ABCKEYWORD." where  keyword like '".$keyw."'";
		//echo $sql.'<br>';
			if($db->getOne($sql)==0){
				$goods_num = 0;//$db->getOne($sql);
				$data   = array();
				$data['keyword']   = trim($keyw);
				$data['goods_num'] = $goods_num;
				if(!empty($data['keyword']) )$db->autoExecute(ABCKEYWORD,$data);
				$y++;
			}
		}
	}
	$this->y=$y;
	//return $y;
}


/**
 * 根据型号生成关键词
 *
 * @param string $goods_name  产品型号
 * @param int $cat_id　　　　　产品分类ID
 * @return array $key_arr     关键词数组
 */
function Model_keyword($goods_name,$cat_id,$b_cat){

	
	//print_r($b_cat);
	//exit();
		
	if(!$goods_name)return array();//没写型号就直接返回空数组
	$goods_name_arr = explode(',',$goods_name);
	
	
	$key_arr = $goods_name_arr;
	$parent_cat_id = $this->get_top_parent_id($cat_id);
	if(!$parent_cat_id)return $key_arr;
	
	if(empty($b_cat[$parent_cat_id][0]['add_word']))return $key_arr;
	
	$add_word = $b_cat[$parent_cat_id][0]['add_word'];	

	if(!empty($add_word)){
		$add_word_arr = explode("\n",$add_word);
		
		foreach ($add_word_arr as $k=>$v){
			if(!empty($v)){
				$word_sub_arr = explode(";",$v);
				foreach ($goods_name_arr as $m){
					preg_match_all("/[0-9a-zA-Z\.\#+]{1,}/",$m,$arr); //提取字母和数字
					
					foreach ($arr[0] as $k1=>$v1){
						if($k1 > 4){  //超过５个单词就截断
							unset($arr[0][$k1]);
						}
					}
					//print_r($goods_name_arr);
					//exit();
					$m = implode(' ',$arr[0]);
					//echo $m." <br>";	
					//exit();
					if(!empty($word_sub_arr[0])&&!empty($word_sub_arr[1])){//前后缀
						//echo $word_sub_arr[0].' '.$m.' '.$word_sub_arr[1];
						$key_arr[] = $word_sub_arr[0].' '.$m.' '.$word_sub_arr[1];
					}elseif (!empty($word_sub_arr[0])&&empty($word_sub_arr[1])){//前缀
						$key_arr[] = $word_sub_arr[0].' '.$m;
					}elseif (empty($word_sub_arr[0])&&!empty($word_sub_arr[1])){//后缀
						$key_arr[] =$m.' '.$word_sub_arr[1];
						//echo $key_arr[] =$m.' '.$word_sub_arr[1];
					}
				}
			}
		}
	}

	return $key_arr;
}


/**
 * 分词
 *
 * @param array $arr  产品标题数组
 * @param unknown_type $times　　几个词连在一起
 * @return unknown
 */
function split_word($arr,$times = 2){
	$temp_arr = array();
	foreach($arr as $k => $v){
		switch ($times){
			case 3:
				if(empty($arr[$k+2])){
					$temp_arr[] = $arr[$k];
				}else{
					$temp_arr[] = $arr[$k].' '.$arr[$k+1].' '.$arr[$k+2];
					//$temp_arr[] = $arr[$k].' '.$arr[$k+2].' '.$arr[$k+1];
					//$temp_arr[] = $arr[$k+1].' '.$arr[$k].' '.$arr[$k+2];
					//$temp_arr[] = $arr[$k+1].' '.$arr[$k+2].' '.$arr[$k];
					//$temp_arr[] = $arr[$k+2].' '.$arr[$k].' '.$arr[$k+1];
					//$temp_arr[] = $arr[$k+2].' '.$arr[$k+1].' '.$arr[$k];
				}
			break;
			
			case 4:
				if(empty($arr[$k+3])){
					$temp_arr[] = $arr[$k];
				}else{
					$temp_arr[] = $arr[$k].' '.$arr[$k+1].' '.$arr[$k+2].' '.$arr[$k+3];
					$temp_arr[] = $arr[$k].' '.$arr[$k+1].' '.$arr[$k+3].' '.$arr[$k+2];
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
				}
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
/**
 * 返回顶级分类ＩＤ
 *
 * @param int $cid 分类ＩＤ
 * @return 顶级分类ＩＤ
 */
function get_top_parent_id($cid){
	global $cur_lang, $default_lang;
	$pids = $cid;
	if($cur_lang != $default_lang){
		$catArr =  read_static_cache($cur_lang.'_category_c_key',2);
	}else {
		$catArr =  read_static_cache('category_c_key',2);
	}	
	if (isset($catArr[$cid]['parent_id'])){
		if($catArr[$cid]['parent_id'])$pid = $catArr[$cid]['parent_id'];
		$npids = $this->get_top_parent_id($catArr[$cid]['parent_id']);
		if($npids) $pids =$npids;
	}

	return $pids;
}
}
?>