<?php
/**
 * class.error.php          错误处理类
 *
 * @author                  mashanling <msl-138@163.com>
 * @date                    2013-11-06 14:43:58
 * @lastmodify              $Date: 2014-02-20 11:06:28 +0800 (周四, 2014-02-20) $ $Author: msl $
 */

class Error {
    /**
     * @var array $_quit_arr 致命错误号及错误信息对应关系
     */
    private $_quit_arr = array(
        E_ERROR              => 'PHP Error',
        E_PARSE              => 'PHP Parsing Error',
        E_CORE_ERROR         => 'PHP Core Error',
        E_CORE_WARNING       => 'PHP Core Warning',
        E_COMPILE_ERROR      => 'PHP Compile Error',
        E_RECOVERABLE_ERROR  => 'PHP Catchable Fatal Error',
        E_SYS_EXCEPTION     => 'PHP Uncaught Exception',
    );

    /**
     * @var array $_quit_arr 错误号及错误信息对应关系
     */
    private $_error_arr = array(
        E_NOTICE             => 'PHP Notice',
        E_WARNING            => 'PHP Warning',
        E_COMPILE_WARNING    => 'PHP Compile Warning',
        E_STRICT             => 'PHP Strict standards',

        E_USER_ERROR         => 'User Error',
        E_USER_WARNING       => 'User Warning',
        E_USER_NOTICE        => 'User Notice',
    );

    /**
     * @var object $_instance 本类实例
     */
    static private $_instance = null;

    /**
     * 格式化时间
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-12-02 09:14:54
     *
     * @param string $format 时间格式
     *
     * @return string 格式化的时间
     */
    private function _date($format = 'Y-m-d H:i:s') {

        if (function_exists('local_date')) {
            return local_date($format);
        }
        else {
            $now = time() - date('Z') + 28800;
            return date($format, $now);
        }
    }

    /**
     * 获取实例
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-01 12:37:52
     *
     * @return object 本类实例
     */
    static public function getInstance() {

        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * 错误处理
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-06 14:46:03
     *
     * @param mixed  $errstr  错误信息
     * @param string $errfile 错误文件。默认''
     * @param int    $errline 错误文件行号。默认''
     * @param mixed  $trace   跟踪轨迹。默认''
     * @param string $error_log 错误日志。默认''
     *
     * @return void 无返回值
     */
    static public function halt($errstr, $errfile = '', $errline = '', $trace = '', $error_log = '') {

        if ($errfile) {
            $e = array(
                'message' => $errstr,
                'file'    => $errfile,
                'line'    => $errline,
                'trace'   => $trace,
            );
        }
        elseif ($errstr instanceof Exception) {
            $e = array(
                'message' => $errstr->getMessage(),
                'file'    => $errstr->getLine(),
                'line'    => $errstr->getLine(),
                'trace'   => $trace->getTraceAsString(),
            );
        }
        elseif (is_string($errstr)) {
            $trace        = debug_backtrace();
            $shift        = $trace[0];
            $e['message'] = $errstr;
            $e['file']    = $shift['file'];
            $e['line']    = $shift['line'];
        }
        else {
            $e = $errstr;
        }

        if (empty($e['trace'])) {
            ob_start();
            debug_print_backtrace();

            $e['trace'] = ob_get_clean();
        }

        $error_log .= $e['trace'] . PHP_EOL;
        Logger::record($error_log, 'system.error');//系统错误日志

        if (DEBUG) {
            $msg = $e['message'] . ' in file ' . $e['file'] . ' on line ' . $e['line'];
            echo $msg;
        }

        exit();
    }//end halt

    /**
     * 构造函数
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-06 14:46:23
     *
     * @return void 无返回值
     */
    private function __construct() {
        $this->_error_arr = $this->_quit_arr + $this->_error_arr;

        if (defined('E_USER_DEPRECATED')) {
            $this->_error_arr[E_USER_DEPRECATED] = 'PHP E_USER_DEPRECATED';
        }

        if (defined('E_DEPRECATED')) {
            $this->_error_arr[E_DEPRECATED] = 'PHP E_DEPRECATED';
        }
    }

    /**
     * 自定义错误处理
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-06 14:46:33
     *
     * @param int    $errno   错误号
     * @param string $errstr  错误信息
     * @param string $errfile 错误文件
     * @param int    $errline 错误文件行号
     * @param mixed  $vars    用户变量。默认''
     *
     * @return void 无返回值
     */
    public function errorHandler($errno, $errstr, $errfile, $errline, $vars = '') {

        /*if (!IS_LOCAL && E_NOTICE == $errno) {
            return;
        }*/

        //@抑制错误
        if (0 === error_reporting()
            && 0 === strpos(str_replace('\\', '/', $errfile), str_replace('\\', '/', SMARTY_DIR))) {

            return false;
        }

        //干掉smarty模板PHP Notice/PHP Warning
        if (false !== strpos($errfile, '.htm.php')
            && 0 === strpos(str_replace('\\', '/', $errfile), str_replace('\\', '/', SMARTY_TMPL_C))
            && (E_NOTICE == $errno || E_WARNING == $error)) {
            return;
        }

        if (E_STRICT === $errno) {
            return;
        }

        $log_filename = C('log_filename');
        C('log_filename', false);

        list($usec, $sec) = explode(' ', microtime());

        $error  = '[' . $this->_date('Y-m-d H:i:s') . substr($usec, 1, 5) . '] ';
        $error .= ' [' . real_ip() . '] ';
        $error .= REQUEST_METHOD . ' ' . REQUEST_URI;
        $error .= REFERER_PAGER ? '(' . REFERER_PAGER . ')' : '';
        $error .= PHP_EOL;
        $error .= "in {$errfile} on line {$errline}" . PHP_EOL;
        $error .= $this->_error_arr[$errno] . "[$errno]：";
        $error .= $errstr . PHP_EOL;

        if (isset($this->_quit_arr[$errno])) {

            if ($vars && is_string($vars) && 0 === strpos($vars, '__')) {//exception_handler触发
                $trace = substr($vars, 2);
            }
            else {
                $trace = '';
            }

            $this->halt($errstr, $errfile, $errline, $trace, $error);
        }
        else {
            Logger::record($error, $log_filename);
        }
    }//end errorHandler
}