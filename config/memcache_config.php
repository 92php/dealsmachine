<?php
/**
 * 缓存配置文件
 *
 * */


//////////////////////////////////////////////////////////////////////////
//
//               缓存Cache常量配置
//
//////////////////////////////////////////////////////////////////////////

//memcache主机
define('SHOP_MEMCACHE_HOSTS',serialize(array(

/*'memcache1'=> array('host'    =>'10.20.113.198',
                    'port'    =>'11211',
                   ),*/
'memcache1'=> array('host'    =>'127.0.0.1',
                   'port'    =>'11212',
                 ),

//'memcache2'=> array('host'    =>'192.168.1.251',
//                    'port'    =>'112112',
//                   ),
)));

define('SHOP_CACHE_ENABLE'   ,1);      //是否开启缓存

// 默认的expire时间，单位为秒
define('SHOP_MEM_EXPIRE',0); //常规缓存时间
define('SHOP_MEM_PCONNECT',false);//memcache是否常连接
define('SHOP_CACHE_METHOD'   ,'memcache'); //缓存类型,可选有: memcache file apc xcache

// 缓存订单id详细情前缀
define('SHOP_CACHE_FILE_NAME','SHOP_CACHE_FILE_NAME:');
?>
