<?php
/**
 * class.seo_crontab.php        seo定时任务处理类
 *
 * @author                      mashanling <msl-138@163.com>
 * @date                        2013-07-22 08:59:51
 * @lastmodify                  $Author: msl $ $Date: 2013-08-09 14:23:19 +0800 (Fri, 09 Aug 2013) $
 */
class SEO_Crontab {
    /**
     * 构造函数
     *
     * @author              mashanling <msl-138@163.com>
     * @date                2013-07-22 09:06:14
     *
     * @return void 无返回值
     */
    public function __construct() {
        $method     = isset($_GET['_action']) ? $_GET['_action'] : '';
        $method    .= 'Action';

        if (method_exists($this, $method)) {
            $this->$method();
        }
        else {
            exit('调用方法不存在');
        }
    }

    /**
     * 清理abc词库
     *
     * @author              mashanling <msl-138@163.com>
     * @date                2013-07-22 09:12:21
     *
     * @return void 无返回值
     */
    public function clearKeywordsAction() {
        require(ROOT_PATH . 'lib/seo/class.seo_clear_keywords.php');

        $class = new SEO_Clear_Keywords();
        $class->clear();
    }

    /**
     * 将符合条件的关键字加入分类popular searches
     *
     * @author              mashanling <msl-138@163.com>
     * @date                2013-07-22 09:06:14
     *
     * @return void 无返回值
     */
    public function keywordsToCategoryAction() {
        require(ROOT_PATH . 'lib/seo/class.seo_keywords_to_category.php');

        $time_start = microtime(true);
        $class      = new SEO_Keywords_To_Category();
        $class->cache();
        $class->log('ok', $time_start);
    }
}