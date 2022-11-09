<?php
/**
 * class.filterkeyword.php    关键字过滤处理类
 *
 * @author                    mashanling(msl-138@163.com)
 * @date                      2012-11-27 13:43:38
 * @last modify                 2013-8-28  by Jim
 */

class FilterKeyword {
    protected $cache_key = 'filter_search_keywords_cache';

    /**
     * 分割排除搜索关键字成数组
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-11-22 11:03:29
     * @last modify  2012-11-22 11:03:29 by mashanling
     *
     * @param string $data 排除搜索关键字
     *
     * @return array 排除搜索关键字成数组
     */
    public function explodeExclude($data) {
        $data_arr = array();
        foreach (explode("\n", $data) as $v) {
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
     * @last modify  2013-8-28  by jim
     *
     * @param string $data 单复数关键字
     *
     * @return array 单复数关键字成数组
     */
    public function explodeDanfu($data) {
        $data_arr = array();
        foreach (explode("\n", $data) as $v) {
            $v = trim($v);
            if (!empty($v)) {
                $data_arr[] = $v;
            }
        }
        return $data_arr;
    }

    /**
     * 获取数据
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-11-27 13:39:14
     * @last modify  2012-11-27 13:39:14 by mashanling
     *
     * @return array 数据
     */
    public function getData() {
        $data = read_static_cache($this->cache_key);
        $data['not_search'] = join(',', $data['not_search']);
		$data['no_this_start'] = join(',', $data['no_this_start']);
		$data['no_this_end'] = join(',', $data['no_this_end']);
        $data['exclude'] = $this->joinExclude($data['exclude']);
        $data['dan_fu'] = $this->joinDanfu($data['dan_fu']);
        return $data;
    }

    /**
     * 设置缓存数据
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-11-27 13:39:40
     * @last modify  2012-11-28 09:47:54 by mashanling
     *
     * @param array $data 缓存数据
     *
     * @return void 无返回值
     */
    public function setData($data) {
        //写日志
        require_once(ROOT_PATH . 'lib/class.function.php');

        Logger::filename(LOG_FILENAME_PATH);
        trigger_error($_SESSION['WebUserInfo']['real_name']);

        write_static_cache($this->cache_key, $data);
    }

    /**
     * 将单复数搜索关键字数组转化成字符串
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-11-22 11:03:29
     * @last modify  2012-11-22 11:03:29 by mashanling
     *
     * @param array $data 单复数搜索关键字数组
     *
     * @return string
     */
    public function joinDanfu($data) {
        $result = '';
        foreach ($data as $k => $v) {
            $result .=  $v . "\n";
        }
        return $result;
    }

    /**
     * 将排除搜索关键字数组转化成字符串
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-11-22 11:03:29
     * @last modify  2012-11-22 11:03:29 by mashanling
     *
     * @param array $data 排除搜索关键字数组
     *
     * @return string
     */
    public function joinExclude($data) {
        $result = '';
        foreach ($data as $k => $v) {
            $result .= $k . '=>' . join(',', $v) . "\n";
        }
        return $result;
    }
}