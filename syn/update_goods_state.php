<?php
/**
 * 更新各种产品状态
 *$act 需要更新的action　
 *@example 
 * by Jim on 2012-3-20
 */
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/cls_image.php');
require_once('../lib/syn_public_fun.php');
$keys_code = empty($_REQUEST['keys_code'])?'':$_REQUEST['keys_code'];
if ($keys_code!=$_CFG['keys_code']){die('Error,key code error');}
set_time_limit(0);
ob_start();
$act = $_REQUEST['act'];
$goods_sn = empty($_REQUEST['goods_sn'])?'':$_REQUEST['goods_sn'];  //产品编码串
$cls = new update_pro_state;
switch ($act){
	case '24hship':   //24小时发货
		if($cls->_24h_ship($goods_sn)){
			echo 'success!';
		}else {
			echo '更新失败';
		}
		break;
	case 'goods_grade':
		if($cls->_goods_grade()){
			echo 'success!';
		}else {
			echo '更新失败';
		}
		break;
}

class update_pro_state{
	protected $per_step_number        = 3000; // 每一次处理多少个编码
	/**
	 * 更新24小时发货状态
	 *
	 * @param string $goods_sn 产品编码串
	 */
	function _24h_ship($goods_sn){
		global $db;
		if(!$goods_sn)return false;
		
		$goods_sn = $this->prepare_goods_sn($goods_sn);
		$goods_sn_arr = $this->split_goods_sn($goods_sn,3000);
		if($goods_sn_arr){
			
			$sql = 'update '.GOODS_STATE ." set is_24h_ship = 0";
			$db->query($sql);	
			foreach ($goods_sn_arr as $v){
				if($v){
					$sql = "update ".GOODS_STATE." s inner join ".GOODS." g on g.goods_id =s.goods_id  set s.is_24h_ship =1 where g.goods_sn in($v)";				//echo $sql."\n";
					$db->query($sql);
					$cat_ids = get_children('1627');  //美国仓
					if($cat_ids){
						$sql = "update ".GOODS_STATE." s inner join ".GOODS." g on g.goods_id =s.goods_id  set s.is_24h_ship =1 where $cat_ids";
						$db->query($sql);
					}
				}	
			}
			
		}
		return true;
	}
	
	/**
	 * 更新24小时发货状态
	 *
	 * @param string $goods_sn 产品编码串
	 */
	function _goods_grade(){
		$goods_grade_arr    = read_static_cache('goods_grade_arr', 1);//产品等级缓存	
		unset($goods_grade_arr[0]);//干掉 0 => '产品等级'	
		$result             = false;	
		foreach ($goods_grade_arr as $i => $v) {	
			if (!empty($_REQUEST['s' . $i])) {
				$result = true;
				$this->_goods_grade_do($_REQUEST['s' . $i], $i);
			}
		}	
		return $result;	
	}
		
	
	function _goods_grade_do($goods_sn,$state){
		global $db;
	
		$goods_sn = $this->prepare_goods_sn($goods_sn);
		$goods_sn_arr = $this->split_goods_sn($goods_sn,$this->per_step_number);
		if($goods_sn_arr){
			foreach ($goods_sn_arr as $v){
				if($v){
					$sql = "update ".GOODS_STATE." s inner join ".GOODS." g on g.goods_id =s.goods_id  set s.goods_grade =$state where g.goods_sn in($v)";			//echo $sql."\n";
					$db->query($sql);			
				}
			}
		}		
	}
	
	/**
	 * 把较长的goods_sn分成较短的goods_sn 数组
	 *
	 * @param string $goods_sn  由逗号分开的产品编码字符串
	 * @param int $each_number　每个数组保存的多少个产品编码
	 * @return $arr_goods_sub
	 */
	function split_goods_sn($goods_sn,$each_number=3000){
		if(!$goods_sn)return false;
		$arr_goods = explode(',',$goods_sn);
		$arr_goods_sub = array(); //保存按数量要求截断的产品编码
		foreach ($arr_goods as $k=>$v){
			$m = ceil($k/($each_number*1.0))-1;
			if(empty($arr_goods_sub[$m]))$arr_goods_sub[$m]='';
			$arr_goods_sub[$m] .= $v.',';
		}
		foreach ($arr_goods_sub as $k=>$v){
			if($v&&substr($v,-1,1) ==',')$arr_goods_sub[$k] = substr($v,0,-1);  //去掉末位的逗号
		}
		//print_r($arr_goods_sub);
		return $arr_goods_sub;
	}
	/**
	 * 把产品编码字符串可以放到in条件里的'',''形式
	 *
	 * @param string $goods_sn 产品编码字符串
	 */
	function prepare_goods_sn($goods_sn =''){
		$goods_sn = preg_replace("/\s+/",'',$goods_sn);
		$goods_sn = str_replace(',',"','",$goods_sn);
		$goods_sn ="'$goods_sn'";
		return $goods_sn;
	}	
}



?>