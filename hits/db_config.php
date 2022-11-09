<?php
define('IS_LOCAL'       , false);             //是否本地环境
define('DB_HOST'        , '127.0.0.1');       //通过外网服务器连，使用内部地址
define('DB_NAME'        , 'ahappydeal');      //数据库名称
define('DB_USER'        , 'httptga');         //数据库用户名
define('DB_PWD'         , 'jL1qfmvocC42E');   //数据库密码

define('PFIX'           , 'eload_');                //定义前缀
define('GOODS_HITS'     , PFIX . 'goods_hits');     //商品点击统计明细表
define('GOODS_HITS_TEMP', PFIX . 'goods_hits_temp');//商品点击统计临时表
define('GOODS_DIGG'     , PFIX . 'goods_digg');     //商品digg表
define('GOODS'          , PFIX . 'goods');          //商品digg表
define('REVIEWLIB_GOODS', PFIX . 'reviewlib_goods');//评论库商品表 by mashanling on 2013-02-06 10:14:28