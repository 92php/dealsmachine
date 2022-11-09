<?php
/**
 * php日志
 *
 * @file                phplog.php
 * @author              mashanling <msl-138@163.com>
 * @date                2013-12-02 15:49:56
 * @lastmodify          $Date: 2014-03-14 17:40:58 +0800 (周五, 2014-03-14) $ $Author: msl $
 */

define('INI_WEB', true);
require(dirname(dirname(__FILE__)) . '/lib/global.php');
require(ROOT_PATH . 'lib/is_loging.php');
require(ROOT_PATH . 'lib/time.fun.php');

$action = Filter::string('action', INPUT_GET, 'list');
$phplog = new Phplog();
$_ACT   = 'phplog/' . $action;

if ('list' == $action) {
    $files = $phplog->getFiles();

    $Arr['path_arr'] = $files['paths'];
    $Arr['file_arr'] = $files['files'];
    $Arr['path'] = $files['path'];
}
elseif ('view' == $action) {
    $Arr['content'] = $phplog->getLogContent();
}

else {
    $log = Logger::filename(__METHOD__, __LINE__, LOG_INVALID_PARAM);
    trigger_error($log . '请求方法不存在“' . $action . '”');

    sys_msg('非法访问', 1, array(array('name' => '返回', 'url' => 'javascript:history.back(-1)')), false);
}

temp_disp();

class Phplog {
    /**
     * @var object $_directory 目录处理实例
     */
    private $_directory;

    /**
     * @var string $_filename 当前文件名
     */
    private $_filename;

    /**
     * @var string $_php_error_path 默认php日志路径
     */
    private $_php_error_path;

    /**
     * @var string $_php_error_filename 默认php日志文件名
     */
    private $_php_error_filename;

    /**
     * 按时间倒序文件
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-26 15:44:38
     *
     * @param array $a 文件a
     * @param array $b 文件b
     *
     * @return int -1,0,1
     */
    private function _cmp($a, $b) {
        static $sort    = null;
        static $order   = null;
        static $array   = null;

        if (null === $sort) {
            $sort   = Filter::string('sort', INPUT_GET, 'time');//排序字段
            $sort   = in_array($sort, array('time', 'size', 'filename')) ? $sort : 'time';
            $order  = Filter::string('order', INPUT_GET, 'DESC');//排序
            $order  = 'DESC' == strtoupper($order) ? 'DESC' : 'ASC';
            $array  = 'ASC' == $order ? array(1, -1) : array(-1, 1);
        }

        if (isset($a['is_file']) && isset($b['is_file'])) {//都是文件
            return $a[$sort] > $b[$sort] ? $array[0] : $array[1];
        }
        elseif (isset($a['is_file']) && !isset($b['is_file'])) {//$a文件,$b文件夹,文件夹在前,文件在后
            return $array[1];
        }
        elseif (!isset($a['is_file']) && isset($b['is_file'])) {//$a文件夹,$b文件
            return $array[0];
        }
        else {//都是文件夹
            return $a['filename'] > $b['filename'] ? $array[0] : $array[1];
        }

        return 0;
    }//end _cmp

    /**
     * 禁止路径
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-26 15:45:33
     *
     * @param string $path 待检测路径
     *
     * @return void 无返回值
     */
    private function _denyDirectory($path) {

        if (false !== strpos($path, '..')) {
            $log    = Logger::filename(__METHOD__, __LINE__, $this->_filename . '.error');
            trigger_error('日志路径使用了相对路径“' . $path . '”');

            sys_msg('日志路径不能使用相对路径', 1, array(array('name' => '返回', 'url' => 'javascript:history.back(-1)')), false);
        }
    }

    /**
     * 获取目录处理实例
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-12-02 19:51:27
     *
     * @return object 目录处理实例
     */
    private function _getDirectory() {

        if (!$this->_directory) {
            $this->_directory = new Dir();
        }

        return $this->_directory;
    }

    /**
     * 日志目录
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-12-02 19:45:59
     *
     * @param string $path 当前路径,如2014/05/15/
     *
     * @return array 日志目录
     */
    private function _getPathArr($path = null) {
        $data       = array(
            DS      => DS
        );

        //自定义日志路径
        if (is_dir($dir = LOG_PATH . local_date('Y/md'))) {
            $dir_arr = $this->_getDirectory()->listDirs($dir, true);
        }
        else {
            $dir_arr = array();
        }

        for($i = 1; $i < 10; ++$i) {//只列出最新10天日志路径

            if (is_dir($dir = LOG_PATH . local_date('Y/md', strtotime("-{$i} days")))) {
                $dir_arr = array_merge($dir_arr, $this->_getDirectory()->listDirs($dir, true));
            }
        }

        $log_path   = $this->_getDirectory()->convertDS(LOG_PATH);

        foreach($dir_arr as $spl_file_info) {
            $pathname = $spl_file_info->getPathname();
            $pathname = str_replace($log_path, '', $this->_getDirectory()->convertDS($pathname)) . DS;
            $data[$pathname] = $pathname;
        }

        if ($path && !isset($data[$path])) {
            $data[$path] = $path;
        }

        unset($dir_arr);

        if (!IS_LOCAL) {//php默认日志路径
            $path       = basename($this->_php_error_path) . DS;// php_error/
            $data[$path] = $path;

            $dir_arr    = $this->_getDirectory()->listDirs($this->_php_error_path);
            $log_path   = $this->_getDirectory()->convertDS(dirname($this->_php_error_path)) . DS;

            foreach($dir_arr as $spl_file_info) {//apache默认日志
                $pathname = $spl_file_info->getPathname();
                $pathname = str_replace($log_path, '', $this->_getDirectory()->convertDS($pathname)) . DS;
                $data[$pathname] = $pathname;
            }
        }

        arsort($data);

        return $data;
    }//end _getPathArr

    /**
     * 构造函数
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-12-10 10:54:52
     *
     * @return void 无返回值
     */
    public function __construct() {
        $this->_filename = basename(__FILE__, '.php');

        if (!IS_LOCAL) {
            $this->_php_error_filename = ini_get('error_log');//    /home/wwwroot/php_error/php.log
            $this->_php_error_path = dirname($this->_php_error_filename) . DS;//        /home/wwwroot/php_error/
        }

        admin_priv($this->_filename);//检查权限
    }

    /**
     * 获取文件列表
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-27 13:40:20
     *
     * @return array 文件列表
     */
    public function getFiles() {
        $path       = Filter::string('path', INPUT_GET);
        $path_arr   = $this->_getPathArr($path);

        if (isset($path_arr[$path])) {
        }
        elseif ($path) {
            $this->_denyDirectory($path);
        }
        elseif (isset($path_arr[$path = local_date('Y/md/')])) {//当前日期,如2013/12/11/
        }
        else {
            $path = current($path_arr);
        }

        if (IS_LOCAL) {
            $log_path = LOG_PATH . $path;
        }
        else {

            if (false === strpos($path, basename($this->_php_error_path))) {//php_error
                $log_path  = LOG_PATH . $path;
            }
            else {
                $log_path       = dirname($this->_php_error_path) . DS . $path;
                $is_php_error   = true;
            }
        }

        if (!is_dir($log_path)) {
            $log = Logger::filename(__METHOD__, __LINE__, $this->_filename . '.error');
            trigger_error('日志路径不存在“' . $log_path . '”');

            sys_msg('日志路径不存在', 1, array(array('name' => '返回', 'url' => 'javascript:history.back(-1)')), false);
        }

        $iterator   = $this->_getDirectory()->listDir($log_path);//文件列表

        if (isset($is_php_error)) {
            $log_path   = $this->_getDirectory()->convertDS(dirname($this->_php_error_path) . DS);
        }
        else {
            $log_path   = $this->_getDirectory()->convertDS(LOG_PATH);
        }

        $file_arr   = array();
        $suffix_len = strlen(LOG_FILE_SUFFIX);
        $diff_time  = date('Z');

        foreach ($iterator as $spl_file_info) {
            $filename = str_replace($log_path, '', $this->_getDirectory()->convertDS($spl_file_info->getPathname()));//替换文件名前缀路径

            if ($spl_file_info->isFile()) {//文件
                //$is_php_error = false !== strpos($filename, $this->_php_error_path);

                if (empty($is_php_error) || LOG_FILE_SUFFIX == substr($filename, -$suffix_len)) {
                    $size = $spl_file_info->getSize();
                    $file_arr[] = array(
                        'filename'	    => $filename,
                        'time'          => local_date('Y-m-d H:i:s', $spl_file_info->getMTime() - $diff_time),
                        'size'          => $size,
                        'size_format'   => format_size($size),
                        'error'         => false !== strpos($filename, '.error'),
                        'is_file'       => true
                    );
                }
            }
            else {//文件夹
                $file_arr[] = array(
                    'filename'	    => $filename . DS,
                    'time'          => '--',
                    'size'          => '--',
                    'size_format'   => '--',
                );
            }
        }

        unset($iterator);
        usort($file_arr, array($this, '_cmp'));//按时间倒序

        return array('paths' => $path_arr, 'files' => $file_arr, 'path' => $path);
    }//end getFiles

    /**
     * 查看文件
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-27 15:41:57
     *
     * @return void 无返回值
     */
    public function getLogContent() {
        $filename   = trim(Filter::string('filename', INPUT_GET), DS);//文件名
        $GLOBALS['Arr']['filename'] = $filename;

        $this->_denyDirectory($filename);

        if (IS_LOCAL || false === strpos($filename, basename($this->_php_error_path))) {
            $pathname   = LOG_PATH . $filename;
        }
        else {//默认php日志路径
            $pathname   = dirname($this->_php_error_path) . DS . $filename;
        }

        if (is_file($pathname)) {

            if (($size = filesize($pathname)) > 819200) {//800KB
                $log = '查看日志文件大小超出800KB限制，当前文件大小：' . format_size($size);
                Logger::filename($this->_filename . '.error');
                trigger_error($log);

                sys_msg($log . '，请使用ftp查看', 1, array(array('name' => '返回', 'url' => 'javascript:history.back(-1)')), false);
            }

            return json_encode(htmlspecialchars(trim(file_get_contents($pathname))));
        }
        else {//文件不存在
            $log = Logger::filename(__METHOD__, __LINE__, $this->_filename . '.error');
            trigger_error('日志文件不存在“' . $pathname . '”');

            sys_msg('日志文件不存在', 1, array(array('name' => '返回', 'url' => 'javascript:history.back(-1)')), false);
        }
    }//end getLogContent
}