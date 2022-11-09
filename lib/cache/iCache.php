<?php
/**
 * Cache的接口
 * 作者：xyl
 * 时间：Thu Apr 29 09:41:22 CST 2010 09:41:22
 */

interface iCache
{
    //设置
	public function set($key, $val, $expire=0);
	//获得单个值
	public function get($key);
	//获得多个值
	public function mget($key=array());
	//删除
	public function delete($key='');
	//刷新
	public function flush();
}
?>