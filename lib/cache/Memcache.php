<?php
/**
 * MEMECACHE类
 * 作者：xyl
 * 时间：Wed Apr 28 14:25:08 CST 2010 14:25:08
 */

require_once(ROOT_PATH.'/lib/cache/iCache.php');

class Cache_Memcache implements iCache
{
	private $memcache;

	private function _key($key)
	{
	    return md5(WEBSITE . $key);
	}

	public function __construct(){
		$this->memcache = new Memcache;
		$servers = unserialize(SHOP_MEMCACHE_HOSTS);
		foreach ($servers as $server) {
			$this->memcache->addServer($server['host'],$server['port'],SHOP_MEM_PCONNECT);
		}
	}

	public function set($key, $val, $expire=SHOP_MEM_EXPIRE, $flags = MEMCACHE_COMPRESSED){
		if( !SHOP_CACHE_ENABLE )return false;
		return $this->memcache->set($this->_key($key) , $val, $flags, $expire);
	}

	public function get($key){
	    if( !SHOP_CACHE_ENABLE ) return false;
		return $this->memcache->get($this->_key($key));
	}

	//根据一组键得到多个值
    public function mget($key=array())
    {
        if (!SHOP_CACHE_ENABLE) return false;
        //得到对应关系
        $array = $desKey = array();
        $key = is_array($key) ? $key : explode(',',$key);
        foreach ($key as $k=>$v)
        {
            $_k = $this->_key($v);
            $desKey[$k] = $_k;
            $array[$_k] = $v;
        }
        //返回对应关系
        $result = array();
        $valList = $this->memcache->get($desKey);
		if (is_array($valList) && $valList)
	    {
			foreach ($valList as $k=>$vlaue)
			{
				$result[$array[$k]] = $vlaue;
			}
		}
        return $result;
    }

	public function delete($key=''){
		return $this->memcache->delete($this->_key($key));
	}

	public function flush(){
		return $this->memcache->flush();
	}
	public function setCompressThreshold($max_value,$Compress_lv){
		$this->memcache->setCompressThreshold($max_value,$Compress_lv);
	}
}
?>