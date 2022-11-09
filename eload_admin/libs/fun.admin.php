<?php

//=============================================
//---生成系统管理员缓存---------------------------
//=============================================
function creat_admin(){
	global $db,$_CFG;
	$sql = "select a.*,b.group_power,b.group_name from ".SADMIN." as a left join ".AGROUP." as b on a.group_id=b.group_id ORDER BY a.said ASC";
	$adminArr = $db -> arrQuery($sql);
	$adminList = array();
	foreach($adminArr as $val)
	{
		$adminList[$val["said"]] = $val;
		$adminList[$val["said"]]["sa_user"] = md5($val["sa_user"].$_CFG["keys_code"]);
	}
	write_static_cache('land', $adminList,2);
}



/*
 * 指定等级产品禁止在网站上架
 * 11  => '活跃有货近期无销售',
 * 12  => '不活跃有货近期无销售'
 *
 * @author          mashanling <msl-138@163.com>
 * @date            2014-03-08 14:15:33
 *
 * @param string|int $goods_id 商品id
 * @param bool $is_ajax true为异步提交
 *
 * @return void 无返回值
 */
function disabled_on_sale($goods_id, $is_ajax = false) {
    $table          = defined('GOODS_EXTEND') ? GOODS_EXTEND : GOODS_STATE;
    $disabled_grade = '11,12';

    if ($disabled_on_sale = $GLOBALS['db']->arrQuery('SELECT goods_id FROM ' . $table . ' WHERE goods_id ' . db_create_in($goods_id) . ' AND goods_grade IN(' . $disabled_grade . ')')) {
        $id = '';

        foreach($disabled_on_sale as $item) {
            $id .= ',' . $item['goods_id'];
        }

        $msg = '商品' . substr($id, 1) . '为活跃有货近期无销售 或 不活跃有货近期无销售商品,禁止在网站上架';

        if ($is_ajax) {
            exit($msg);
        }
        else {
            sys_msg($msg);
        }
    }
}//end disabled_on_sale