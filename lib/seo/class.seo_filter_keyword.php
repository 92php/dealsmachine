<?php
/**
 * class.seo_filter_keyword.php     后台关键字过滤处理类
 *
 * @author                          mashanling <msl-138@163.com>
 * @date                            2013-07-15 17:21:02
 * @lastmodify                      $Author: msl $ $Date: 2013-08-09 14:23:19 +0800 (Fri, 09 Aug 2013) $
 */

require_once(ROOT_PATH . 'lib/seo/class.seo.php');

class SEO_Filter_Keyword extends SEO {
    /**
     * 分割排除搜索关键字成数组
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-11-22 11:03:29
     *
     * @param string $data 排除搜索关键字
     *
     * @return array 排除搜索关键字成数组
     */
    public function explodeExclude($data) {
        $data_arr = array();

        foreach (explode(PHP_EOL, $data) as $v) {
            $v = trim($v);
            $arr = explode('=>', $v);

            if (isset($arr[1])) {
                $data_arr[$arr[0]] = explode(',', $arr[1]);
            }
        }

        return $data_arr;
    }

    /**
     * 分割单复数关键字成数组
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-11-22 11:03:29
     *
     * @param string $data 单复数关键字
     *
     * @return array 单复数关键字成数组
     */
    public function explodeDanfu($data) {
        $data_arr = array();

        foreach (explode(PHP_EOL, $data) as $v) {
            $v = trim($v);
            $arr = explode('=>', $v);

            if (isset($arr[1])) {
                $data_arr[$arr[0]] = $arr[1];
            }
        }

        return $data_arr;
    }

    /**
     * 获取数据
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-11-27 13:39:14
     *
     * @return array 数据
     */
    public function getData() {
        $data = read_static_cache($this->_cache_key);
        $data['not_search'] = join(',', $data['not_search']);
        $data['exclude'] = $this->joinExclude($data['exclude']);
        $data['dan_fu'] = $this->joinDanfu($data['dan_fu']);

        return $data;
    }

    /**
     * 设置缓存数据
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-11-27 13:39:40
     *
     * @param array $data 缓存数据
     *
     * @return void 无返回值
     */
    public function setData($data) {
        $this->log($_SESSION['WebUserInfo']['real_name'] . var_export($data, true));

        write_static_cache($this->_cache_key, $data);
    }

    /**
     * 将单复数搜索关键字数组转化成字符串
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-11-22 11:03:29
     *
     * @param array $data 单复数搜索关键字数组
     *
     * @return string
     */
    public function joinDanfu($data) {
        $result = '';

        foreach ($data as $k => $v) {
            $result .= $k . '=>' . $v . PHP_EOL;
        }

        return $result;
    }

    /**
     * 将排除搜索关键字数组转化成字符串
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-11-22 11:03:29
     *
     * @param array $data 排除搜索关键字数组
     *
     * @return string
     */
    public function joinExclude($data) {
        $result = '';

        foreach ($data as $k => $v) {
            $result .= $k . '=>' . join(',', $v) . PHP_EOL;
        }

        return $result;
    }
}