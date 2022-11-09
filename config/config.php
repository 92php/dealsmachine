<?php
//定义数据表信息
define('PFIX','eload_');  //定义前缀
define('SHOPCONFIG',PFIX.'shop_config');  //系统权限模块
define('AGROUP',PFIX.'admin_group');  //系统权限模块
define('SADMIN',PFIX.'sys_admin');
define('AACTION',PFIX.'admin_action');
define('ALOGS',PFIX.'sys_log');
define('GOODS_CONVERSION_RATE',PFIX.'goods_conversion_rate');  //产品一个月转化率表
define('CATALOG',PFIX.'category'); //商品模块
define('CATALOG_LANG',PFIX.'category_muti_lang'); //保存分类的多语言信息
define('GOODS',PFIX.'goods');
define('GTYPE',PFIX.'goods_type');
define('ATTR',PFIX.'attribute');
define('GATTR',PFIX.'goods_attr');
define('VPRICE',PFIX.'volume_price');
define('GGALLERY',PFIX.'goods_gallery');   //相册
define('COMMENT',PFIX.'comment');   //相册
define('GROUPGOODS',PFIX.'group_goods');   //商品配件
define('KEYWORDS',PFIX.'keywords');   //关键字
define('ABCKEYWORD',PFIX.'abc_index_keywords');   //abc索引关键字
define('SEARCHENGINE',PFIX.'searchengine');
define('GOODSCAT',PFIX.'goods_cat');
define('GOODSTUIJIAN',PFIX.'goods_tuijian');
define('PCODE',PFIX.'promotion_code');
define('INQUIRY',PFIX.'inquiry');
define('PRO_INQUIRY_PIC',PFIX.'pro_inquiry_pic'); //产品咨询图片
define('GOODS_HITS',PFIX.'goods_hits');
define('GOODS_HITS_TEMP',PFIX.'goods_hits_temp');
define('GOODS_STATE',PFIX.'goods_state');
define('ORDERINFO',PFIX.'order_info'); //订单管理模块
define('CART',PFIX.'cart');
define('ADDR',PFIX.'user_address');
define('COLLECT',PFIX.'collect_goods');
define('ODRGOODS',PFIX.'order_goods');
define('SHIPDETAILS',PFIX.'shipping_details');
define('BILLADDR',PFIX.'user_billing_address');
define('USERS_INFO',PFIX.'users_info');
define('USERS',PFIX.'users');   //会员模块
define('UADDR',PFIX.'user_address');
define('REGION',PFIX.'region');
define('FEEDBACK',PFIX.'feedback');
define('SHIPFEE',PFIX.'shipping_fee');
define('Mtemplates',PFIX.'mail_templates');
define('Mtemplates_language',PFIX.'language');
define('Batch_email_type',PFIX.'batch_email_type');
define('Email_list',PFIX.'email_list');
define('Email_sendlist',PFIX.'email_sendlist');
define('Email_send_history',PFIX.'email_send_history');
define('GIFTS',PFIX.'gifts');

define('DEALS',PFIX.'deals');  //deals模块 2014-6-9
define('DEALS_ITEM',PFIX.'deals_item');
define('DEALS_UPS',PFIX.'deals_ups');


define('WJ_LINK',PFIX.'wj_link');   //网站联盟模块
define('WJ_IP',PFIX.'wj_visitor_ip');
define('POINT_RECORD',PFIX.'point_record'); //积分记录
define('POINT_COUPON',PFIX.'promo_point'); //积分优惠
define('POINT_COUPON_RECORD',PFIX.'pp_use_record'); //积分记录
define('REVIEW',PFIX.'review'); //评论
define('REVIEW_PIC',PFIX.'review_pic'); //评论图片
define('REVIEW_VIDEO',PFIX.'review_video'); //评论视频
define('REVIEW_REPLY',PFIX.'review_reply'); //评论回复
define('PRO_INQUIRY',PFIX.'pro_inquiry'); //产品咨询
define('REVIEWLIB_GOODS'		, PFIX . 'reviewlib_goods');			//评论库商品表 by mashanling on 2013-02-06 10:14:28
define('REVIEW_HELPFUL',PFIX.'review_helpful'); //评论评价表
define('REVIEW_HELPFUL_WINTER',PFIX.'review_helpful_winter');	//评论评价表获奖列表
/* 投票表配置 */
define('VOTE_SUBJECT'    , PFIX . 'vote_subject');    //主题表
define('VOTE_TITLE'      , PFIX . 'vote_title');      //标题表
define('VOTE_OPTION'     , PFIX . 'vote_option');     //选项表
define('VOTE_OTHER'      , PFIX . 'vote_other');      //其它表
define('VOTE_IP'         , PFIX . 'vote_ip');         //ip表
/* 网站分析表配置 */
define('ANALYZE_CUSTOMERS_ACTION', PFIX . 'analyze_customers_action');    //客户行为分析表
define('ANALYZE_CUSTOMERS'   , PFIX . 'analyze_customers');    //客户分析表
define('ANALYZE_ORDERS'      , PFIX . 'analyze_orders');       //订单分析表
define('ANALYZE_SALES'       , PFIX . 'analyze_sales');        //销售分析表
define('ANALYZE_COUNTRY'   , PFIX . 'analyze_country');        //国家销售分析表
define('COLLECT_INFO'   , PFIX . 'collect_info');        //用户收集表 by mashanling on 2013-05-28 14:58:24
define('HUILV',5.9);
define('ARTICLECAT',PFIX.'article_cat');   //文章模块
define('ARTICLE',PFIX.'article');   //
define('SHIPPING',PFIX.'shipping');  //配送方式
define('PAYMENT',PFIX.'payment');  //支付方式
define('FEIFA','No data, Do not illegal to operate!');
define('_PAGESIZE_',20);             //列表页分页一页个数
define('_ADDSTRING_',"添加了");      //用于日志记录
define('_EDITSTRING_',"编辑了");     //用于日志记录
define('_DELSTRING_',"删除了");      //用于日志记录
//商品属性查找
define('SEARCH_TEMPLATE'	, PFIX . 'search_template');		//属性查找模板表
define('SEARCH_ATTR'		, PFIX . 'search_attr');			//属性查找值表
//退换货表配置 by mashanling on 2012-10-31 10:40:44
define('RMA_ORDER'          , PFIX . 'rma_order');              //订单表
define('RMA_PRODUCT'        , PFIX . 'rma_order_product');      //商品表
define('RMA_MSG'            , PFIX . 'rma_msg');                //留言表
define('RMA_TRACKING_NUMBER', PFIX . 'rma_tracking_number');    //跟踪号 by mashanling on 2012-08-25 15:37:16
define('NEWSLETTER', PFIX . 'newsletter');                      //邮件期刊表
define('EC_CHARSET', 'utf-8');
define('IMAGE_DIR', 'uploads');                                 //定义上传图片路径
define('ARTICLE_DIR', 'articles/');                             //文章详细页面存放目录。
define('GOODS_DIR', 'Wholesales/');                             //商品详细页面存放目录。
define('URLSUFFIX', '.html');                                   //定义前台文件后缀
define('ORDER_PAYMENT_EMAIL',PFIX.'order_payment_send_email');  //订单催款邮件记录表
define('WJ_SHARE'		, PFIX . 'wj_share');					//facebook分享统计扩展表
define('SHARE_WINNER'		, PFIX . 'share_winner');					//facebook分享中奖表

define('GOODSATTRLANG', PFIX . 'goods_attr_language');          //商品规格属性多语言表

define('MAIL_AUTO_TEMP',PFIX.'mail_auto_temp');  //自助邮件模板表
define('MAIL_AUTO_TEMP_EXTEND',PFIX.'mail_auto_temp_extend');  //自助邮件模板扩展表
define('MAIL_AUTO_TEMP_CATEGORY',PFIX.'mail_auto_temp_category');  //自助邮件模板扩展表
define('MEM_CACHE', PFIX . 'memcache');//memcache缓存数据表

if (isset($_SERVER['PHP_SELF'])){
    define('PHP_SELF', $_SERVER['PHP_SELF']);
}else{
    define('PHP_SELF', $_SERVER['SCRIPT_NAME']);
}



$GLOBALS['public_goods_type_id'] = 38;			                //商品公共属性类型ID（商品规格属性同步使用的商品类型ID）
$GLOBALS['public_goods_type_spec_id'] = array(	                //商品公共属性类型作物规格属性的ID
											'color' => 63,
											'size' => 64,
										);
// sphinx
//define('SPH_HOST'          , '192.168.3.242');                   //sphinx 主机
//define('SPH_PORT'          , 9314);                             //sphinx 端口
//sphinx 现网
//define('SPH_HOST'          , '50.97.75.165');                   //sphinx 主机
//define('SPH_HOST'          , '184.173.114.244');                //sphinx 备用主机
//define('SPH_PORT'          , 8312);                             //sphinx 端口
//define('SPH_INDEX_MAIN'    , 'ahappydeal');                     //sphinx 默认语言主查询索引名称
//define('SPH_MAX_MATCHES'   , 2500);                             //sphinx 最大匹配数，不能超过sphinx.conf里面设置的max_matches

// define('DOMAIN_USER', 'http://auser.everbuying.net');        //用户模块域名
// define('DOMAIN_CART' , 'http://acart.everbuying.net');       //购物车模块域名
//define('DOMAIN' , 'http://www.ahf05.com');
//define('DOMAIN_USER', 'http://www.ahf05.com');                  //用户模块域名
//define('DOMAIN_CART' , 'http://www.ahf05.com');                 //购物车模块域名
define('DOMAIN' , 'http://www.dealsmachine_v.com');
define('DOMAIN_USER', 'http://www.dealsmachine_v.com');                  //用户模块域名
define('DOMAIN_CART' , 'http://www.dealsmachine_v.com');                 //购物车模块域名
$Arr['DOMAIN'] = DOMAIN;
$Arr['DOMAIN_USER'] = DOMAIN_USER;
$Arr['DOMAIN_CART'] = DOMAIN_CART;
$Arr['COOKIESDIAMON'] = COOKIESDIAMON;


define('CAT_KEYWORDS',         PFIX.'cat_keywords');
define('SEARCHED_KEYWORDS',    PFIX.'searched_keywords');
define('ABCKEYWORD_NEW2',      PFIX.'abc_index_keywords_new2');
define('ABCKEYWORD_RELATIVE2', PFIX.'abc_keywords_relative2');
//专题活动表配置
define('SPECIAL'          , PFIX . 'special');            //专题表
define('SPECIAL_POSITION' , PFIX . 'special_position');   //专题板块表
define('SPECIAL_GOODS'    , PFIX . 'special_goods');      //专题商品表
//测试机ABC
//define('SPH_HOST_ABC'               , '192.168.3.242');      //主机
//define('SPH_PORT_ABC'               , 9314);                //端口
//现网ABC
//define('SPH_HOST_ABC'               , '50.97.75.173');      //主机现网
//define('SPH_PORT_ABC'               , 9312);                //端口

define('SPH_INDEX_ABC_MAIN'         , 'ahappydeal_keywords');       //主索引名称
define('SPH_INDEX_ABC_DELTA'        , 'ahappydeal_keywords_delta'); //增量索引名称



//==============安全性版本增加配置=============
//环境变量
define('ENV'                , 'TEST');           //环境变量，DEV(开发), TEST(测试), SERVER(正式)
define('DEBUG'              , 'DEV' == ENV);    //调试
define('IS_LOCAL'           , 'SERVER' != ENV); //本地环境
define('__GET'              , isset($_GET['__get']) && DEBUG);//调试模式下，通过$_GET获取_POST数据

//==============安全设置start==================
//加解密设置
define('ENCRYPT_KEY'                , 'bKhkWrZkMYpmRXJWavRVT49mejdTST1U');//安全密钥
define('ENCRYPT_EMPTY_STRING'       , '###empty_string###');//加密空字符串

//加解密标识
define('ENCRYPT'                    , 0);//加密
define('DECRYPT'                    , 1);//解密

//加密类型
define('ENCRYPT_TYPE_FALSE'         , 0);//不加密
define('ENCRYPT_TYPE_MCRYPT'        , 1);//使用mcrypt扩展加密，加密后乱码，不可读
define('ENCRYPT_TYPE_MCRYPT2'       , 2);//mcrypt+base64加密，加密后可读
define('ENCRYPT_TYPE_XOR'           , 3);//异或加密
define('ENCRYPT_TYPE_BASE64'        , 4);//base64加密
define('ENCRYPT_TYPE_GZCOMPRESS'    , 5);//gzcompress压缩，压缩后乱码，不可读
define('ENCRYPT_TYPE_SERIALIZE'     , 6);//序列化
define('ENCRYPT_TYPE_DEFAULT'       , ENCRYPT_TYPE_MCRYPT2);//默认加密类型

//缓存加密设置
define('ENCRYPT_ADMIN'              , ENCRYPT_TYPE_FALSE);    //true加密管理员缓存

//数据库帐号密码加密
//使用判断定义,兼容数据库帐号密码加密与否
!defined('ENCRYPT_DB')          && define('ENCRYPT_DB'         , ENCRYPT_TYPE_FALSE);

//==============安全设置end==================

//==============日志设置start================
//日志设置
define('LOG_PATH'           , ROOT_PATH . 'logs/'); //日志目录
define('SLOW_QUERY_TIME'    , 2);                   //数据库慢查询时间，单位：秒，0不记录
define('SLOW_LOAD_TIME'     , 10);                  //记录页面执行时间开关，单位：秒，0不记录
define('E_SYS_EXCEPTION'    , 'E_SYS_EXCEPTION');   //异常
define('LOG_FILESIZE'       , 512000);              //日志文件大小，单位：byte
define('LOG_FILE_SUFFIX'    , '.log');              //日志文件后缀
define('LOG_STATIC_SUFFIX'  , '.txt');              //静态日志缓存文件后缀，通常为通过Logger::put及Logger::get读写的日志文件
define('LOG_STRONG_FORMAT'  , '[b]%s[/b]');         //日志加粗格式
define('LOG_SEPARATOR'      , '$@!#');              //日志分割符,可根据此值分割日志,倒序内容
define('LOG_FILENAME_PATH'  , 1);                   //自动获取日志文件名: 请求目录+当前执行脚本str_replace(array('/', '.php'), array('.', ''), substr($_SERVER['SCRIPT_NAME'], 1));
define('LOG_FILENAME_FILE'  , 2);                   //自动获取日志文件名: 当前执行脚本basename($_SERVER['SCRIPT_NAME'], '.php')

//日志类型
define('LOG_ALL'                    , 'all');               //全部日志
define('LOG_SQL'                    , 'sql');               //sql
define('LOG_SQL_ERROR'              , 'sql.error');         //sql错误
define('LOG_SYSTEM_ERROR'           , 'system.error');      //系统错误
define('LOG_UPLOAD_ERROR'           , 'upload.error');      //上传文件错误
define('LOG_EMAIL_FAILURE'          , 'email.error');       //邮件发送错误
define('LOG_FILTER_ERROR'           , 'filter.error');      //Filter类过滤值失败
define('LOG_USER_ERROR'             , 'user.error');        //自定义用户错误
define('LOG_INVALID_PARAM'          , 'invalid.param');     //非法参数
define('LOG_INVALID_REQUEST'        , 'invalid.request');   //非法请求
define('LOG_SLOWQUERY'              , 'slowquery');         //慢查询
define('LOG_SLOWLOAD'               , 'slowload');          //页面执行时间超出指定时间
//==============日志设置end================

//其它设置
define('DS'                 , '/');//路径分割符
define('REQUEST_METHOD'     , isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'empty');            //请求方法
define('REFERER_PAGER'      , empty($_SERVER['HTTP_REFERER']) ? '' : urldecode($_SERVER['HTTP_REFERER']));          //来路页面
define('IS_AJAX'            , isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'XMLHttpRequest' == $_SERVER['HTTP_X_REQUESTED_WITH']);//ajax请求

//请求uri
if (isset($_SERVER['REQUEST_URI'])) {
    define('REQUEST_URI', urldecode($_SERVER['REQUEST_URI']));
}
else {
    /**
     * @ignore
     */
    define('REQUEST_URI', 'empty');
}
define('ORDERPAYPALINFO',PFIX.'order_paypal_info'); //订单使用paypal的paypal付款信息