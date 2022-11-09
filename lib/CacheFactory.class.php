<?php
/**
 * 缓存工厂类
 * 作者：xyl
 * 时间：Thu Apr 29 10:00:31 CST 2010 10:00:31
 */

class Cache
{
    private static $instance = null;
   
	/**
	 * 生成 Cache 单例
	 * 
	 * @param string $cacheType :自定义缓存类型,为空表示采用系统默认;
	 *                           可选择的类型有:"memcache" "file" "apc" "xcache"
	 * @return cache object
	 */
	static function getInstance($cacheType='')
	{
    	if (null === self::$instance)
    	{
       		$CType = ucfirst($cacheType ? $cacheType : SHOP_CACHE_METHOD);
    		// ucfirst:将字符串第一个字符改大写
    		$cacheObjClass = 'Cache_'.$CType;

    		//加载对应的缓存组件
    		$cacheFile = ROOT_PATH."/lib/cache/".$CType.'.php';
    		if(file_exists($cacheFile)) include_once($cacheFile);
    		/*if(!class_exists($cacheObjClass)) 
    		{
    		    //写日志
    			throw new Exception('缓存类型不存在');
    		}*/
    		self::$instance = new $cacheObjClass();
        }
        return self::$instance;	    
	}	
}
?>