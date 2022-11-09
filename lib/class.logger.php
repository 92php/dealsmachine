<?php
/**
 * class.logger.php         日志处理类
 *
 * @author                  mashanling <msl-138@163.com>
 * @date                    2013-11-06 14:52:51
 * @lastmodify              $Date: 2014-02-20 11:06:28 +0800 (周四, 2014-02-20) $ $Author: msl $
 */

class Logger {
    /**
     * @var array $log 日志信息
     */
    static public $log    = array();

    /**
     * 格式化时间
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-06 14:53:42
     *
     * @param string $format 时间格式
     *
     * @return string 格式化的时间
     */
    static private function _date($format = 'Y-m-d H:i:s') {

        if (function_exists('local_date')) {
            return local_date($format);
        }
        else {
            $now = time() - date('Z') + 28800;
            return date($format, $now);
        }
    }

    /**
     * 获取日志文件名 或 方法名及行数
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-12-10 16:49:35
     *
     * @param string $method 方法名
     * @param string $line   行数
     * @param string $log_filename 日志文件名,默认null
     *
     * @return string 日志文件名 或 方法名及行数
     */
    static public function filename($method = null, $line = null, $log_filename = null) {

        if (null === $line) {//无方法,仅传日志文件名
            $log_filename = $method;
        }

        if ($log_filename) {

            if (LOG_FILENAME_PATH === $log_filename) {//自动获取日志文件名: 请求目录+执行脚本
                $log_filename = str_replace(array('/', '.php'), array('.', ''), substr($_SERVER['SCRIPT_NAME'], 1));
            }
            elseif (LOG_FILENAME_FILE === $log_filename) {//执行脚本
                $log_filename = basename($_SERVER['SCRIPT_NAME'], '.php');
            }

            C('log_filename', $log_filename);
        }

        return $line ? "{$method}：{$line}，" : $log_filename;
    }//end filename

    /**
     * 获取日志文件内容
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-12-09 09:33:57
     *
     * @param string $filename 日志文件名
     *
     * @return string|null 日志文件存在，返回日志内容，否则null
     */
    static public function get($filename) {

        if (is_file($filename = LOG_PATH . $filename . LOG_STATIC_SUFFIX)) {
            return file_get_contents($filename);
        }
        else {
            return null;
        }
    }

    /**
     * 日志内容不叠加方式写日志
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-12-09 09:33:57
     *
     * @param string $filename 日志文件名
     * @param string $message  日志内容
     *
     * @return void 无返回值
     */
    static public function put($filename, $message) {
        file_put_contents(LOG_PATH . $filename . LOG_STATIC_SUFFIX, $message, LOCK_EX);
    }

    /**
     * 记录日志
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-06 14:53:42
     *
     * @param string $message  日志内容
     * @param string $filename 日志文件名，如果提供，将写当前日志内容至该文件。默认''
     *
     * @return void 无返回值
     */
    static public function record($message, $filename = '') {

        if ($filename) {
            self::write($message, $filename);
        }
        else {
            self::$log[] = $message;
        }
    }

    /**
     * 保存记录日志
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-06 14:53:50
     *
     * @return void 无返回值
     */
    static public function save() {

        if (self::$log) {
            self::write();
            self::$log = array();
        }
    }

    /**
     * 写日志
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-06 14:53:58
     *
     * @param string $message       日志信息。默认''，取已保存日志
     * @param string $destination   写入目标。默认''，日志路径+Y/md/+文件名
     * @param int    $type          日志记录方式。默认''，取C('LOG_TYPE')
     * @param string $extra         额外信息。默认''
     *
     * @return void 无返回值
     */
    static public function write($message = '', $destination = '', $type = 3, $extra = '') {
        $log  = $message ? $message : join(PHP_EOL, self::$log);

        if (!$log) {
            return;
        }

        $log      .= PHP_EOL . LOG_SEPARATOR . PHP_EOL;

        if (!defined('TODAY_LOG_PATH')) {
            $path = false === strpos(REQUEST_URI, '/eload_admin/') ? '' : 'admin/';
            $path = LOG_PATH . self::_date('Y/md/') . $path;

            if (!is_dir($path) && !mkdir($path, 0770, true)) {//创建目录失败,还原日志目录
                $path = LOG_PATH;
            }

            define('TODAY_LOG_PATH', $path);

        }

        if (false !== strpos(REQUEST_URI, 'kan_server.php')) {//手机错误
            $destination = 'mobile';
        }

        if ($destination) {

            if (strpos($destination, DS)) {//再一层目录,如gsc/error
                $path_arr = pathinfo($destination);

                if (!is_dir($path = TODAY_LOG_PATH . $path_arr['dirname']) && !mkdir($path, 0770, true)) {//创建目录失败,还原到文件名
                    $destination = $path_arr['filename'];
                }
            }

            $destination        = TODAY_LOG_PATH . $destination . LOG_FILE_SUFFIX;
        }
        elseif (IS_LOCAL) {
            $destination        = 'php';
            $destination        = TODAY_LOG_PATH . $destination . LOG_FILE_SUFFIX;
        }
        else {//php错误,写至../php_error/目录
            $destination = ini_get('error_log');//默认文件名

            if ($destination && is_writable(dirname($destination))) {
                $backup_date_format = '-Ymd_His';
            }
            else {
                $destination = TODAY_LOG_PATH . 'php' . LOG_FILE_SUFFIX;
            }
        }


        //检测日志文件大小，超过配置大小则备份日志文件重新生成
        if (is_file($destination) && filesize($destination) > LOG_FILESIZE) {//500KB
            $path_arr = pathinfo($destination);
            rename($destination, $path_arr['dirname'] . DS . $path_arr['filename'] . self::_date(isset($backup_date_format) ? $backup_date_format : '_His') . LOG_FILE_SUFFIX);
        }

        //调试模式，输出php错误
        if (DEBUG &&
            (preg_match('/^PHP\s[A-Z]/m', $log) || strpos($log, 'Smarty error')) &&
            !IS_AJAX && !isset($_GET['jsoncallback']))
        {
            echo nl2br($log);
        }

        error_log($log, $type, $destination, $extra);
    }//end write
}