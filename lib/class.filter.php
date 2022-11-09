<?php
/**
 * 参数验证及过滤类
 *
 * @file            class.filter.php
 * @author          mashanling <msl-138@163.com>
 * @date            2013-12-02 11:34:20
 * @lastmodify      $Date: 2014-02-18 17:30:58 +0800 (周二, 2014-02-18) $ $Author: msl $
 */

class Filter {
    /**
     * @var array $_filter_type 验证过滤类型，string、int、boolean、float、regexp、url、email
     */
    static private $_filter_type = array(
        'string'       => FILTER_SANITIZE_STRING,
        'int'          => FILTER_VALIDATE_INT,
        'boolean'      => FILTER_VALIDATE_BOOLEAN,
        'float'        => FILTER_VALIDATE_FLOAT,
        'regexp'       => FILTER_VALIDATE_REGEXP,
        'url'          => FILTER_VALIDATE_URL,
        'email'        => FILTER_VALIDATE_EMAIL,
        'ip'           => FILTER_VALIDATE_IP,
    );

    /**
     * @var array $_filter_type 外部变量类型，$_GET、$_POST、$_COOKIE、$_SERVER、$_ENV
     */
    static private $_input_type = array(
        INPUT_GET     => INPUT_GET,
        INPUT_POST    => INPUT_POST,
        INPUT_COOKIE  => INPUT_COOKIE,
        INPUT_SERVER  => INPUT_SERVER,
        INPUT_ENV     => INPUT_ENV
    );

    /**
     * 验证url(5.3以下版本Filter验证url有bug，http://abc-def.com验证失败)
     *
     * @param   string  $value 验证值
     *
     * @return string url或空字符串
     */
    static private function _checkUrl($value) {

        if (is_string($value)) {
            $value = trim($value);

            if (false !== strpos($value, '-')) {
                $replace    = 'ABCD9999';
                $value = str_replace('-', $replace, $value);
            }

            if ($value = filter_var($value, FILTER_VALIDATE_URL)) {
                return isset($replace) ? str_replace($replace, '-', $value) : $value;
            }
        }

        return '';
    }

    /**
     * 获取过滤类型
     *
     * @param string $filter_type 过滤类型key
     *
     * @return int|null 如果过滤类型存在，返回过滤类型，否则返回null
     */
    static private function _getFilterType($filter_type) {
        $filter_type = strtolower($filter_type);

        return isset(self::$_filter_type[$filter_type]) ? self::$_filter_type[$filter_type] : null;
    }

    /**
     * 获取外部变量验证类型
     *
     * @param string $input_type 变量类型
     *
     * @return int|null 如果变量类型存在，返回变量类型，否则返回null
     */
    static private function _getInputType($input_type) {
        $input_type = strtolower($input_type);

        return isset(self::$_input_type[$input_type]) ? self::$_input_type[$input_type] : null;
    }

    /**
     * 数组过滤
     *
     * @param string $var_name            参数名
     * @param int $type                请求方法，INPUT_GET|INPUT_POST
     * @param string $filter_type         数组类型。默认array:int=int
     * @param string $default             如果过滤返回null时(不存在，过滤失败)时默认值。默认0
     *
     * @return array 过滤后数组
     */
    static public function _array($var_name, $type = INPUT_POST, $filter_type = 'array:int', $default = 0) {
        $array = self::filterInput($var_name, $filter_type, $type, array('options' => array('default' => $default), 'flags' => FILTER_REQUIRE_ARRAY|FILTER_NULL_ON_FAILURE));

        return $default === $array ? array() : $array;
    }

    /**
     * 魔术方法，当调用方法不存在时，调用self::string方法
     *
     * @param string $method 方法名
     * @param array  $args   参数
     *
     * @return string 过滤后字符串
     */
    static public function __callStatic($method, $args) {
        return call_user_func_array('self::string', $args);
    }

    /**
     * 布尔值过滤
     *
     * @param string $var_name            参数名
     * @param int $type                请求方法，INPUT_GET|INPUT_POST
     * @param string $default             如果过滤返回null时(不存在，过滤失败)默认值。默认false
     *
     * @return string 过滤后的布尔值或默认值
     */
    static public function bool($var_name, $type = INPUT_POST, $default = false) {
        $string = self::filterInput($var_name, 'boolean', $type, array('options' => array('default' => $default)));

        return null === $string ? $default : $string;
    }

    /**
     * email过滤
     *
     * @param string $var_name            参数名
     * @param int $type                请求方法，INPUT_GET|INPUT_POST
     * @param string $default             如果过滤返回null时(不存在，过滤失败)默认值。默认''
     *
     * @return string 过滤后的email或默认值
     */
    static public function email($var_name, $type = INPUT_POST, $default = '') {
        return self::filterInput($var_name, 'email', $type, array('options' => array('default' => $default)));
    }

    /**
     * 提交变量过滤
     *
     * @param string $var_name    参数名
     * @param string $filter_type 过滤类型。默认string
     * @param string $input_type  请求方法，INPUT_GET|INPUT_POST
     * @param array  $options     过滤选项。默认array()
     *
     * @return mixed 过滤成功，返回过滤后的值，否则返回false或设置的默认值
     */
    static public function filterInput($var_name, $filter_type = 'string', $input_type = INPUT_GET, $options = array()) {

        if (strpos($filter_type, ':')) {//array:int,int型数组
            list($type, $filter_type) = explode(':', $filter_type);
        }
        else {
            $type = $filter_type;
        }

        $default = $options['options']['default'];
        unset($options['options']['default']);

        if (__GET || INPUT_GET == $input_type) {//调试模式下，支持通过$_GET获取数据
            $get        = true;
            $input_type = INPUT_GET;
        }

        $input_type     = self::_getInputType($input_type);
        $filter_type    = self::_getFilterType($filter_type);

        if (null === $input_type || null === $filter_type) {
            return $default;
        }

        /*filter_input返回值:
        Value of the requested variable on success, FALSE if the filter fails, or NULL if the variable_name variable is not set.
        If the flag FILTER_NULL_ON_FAILURE is used, it returns FALSE if the variable is not set and NULL if the filter fails.
        */
        $result = filter_input($input_type, $var_name, $filter_type, array_merge(array('flags' => FILTER_NULL_ON_FAILURE), $options));

        if (false === $result) {//未设置
            return $default;
        }
        //数组null值,说明数据类型不正确
        elseif (null !== $result && 'array' == $type && array_filter($result, 'is_null')){
            $result = null;
        }

        if (null === $result || 'string' == $type) {

            if (isset($get)) {
                $value = empty($_GET[$var_name]) ? null : $_GET[$var_name];
            }
            else {
                $value = empty($_POST[$var_name]) ? null : $_POST[$var_name];
            }

            if ($value && (null === $result || false !== strpos($value, '<') && $result != $value)) {//数据检测不通过

                if (null === $result) {
                    $args       = func_get_arg(1);
                    $error_log  = sprintf('数据未通过Filter“%s”类型检测：%s=%s', $args, $var_name, stripslashes(var_export($value, true)));
                }
                else {
                    $error_log = '数据不匹配：' . stripslashes(var_export($value, true));
                }

                Logger::filename(LOG_FILTER_ERROR);
                trigger_error($error_log);
            }
        }

        return null === $result ? $default : $result;
    }//end filterInput

    /**
     * 变量过滤
     *
     * @param string $var         变量名
     * @param string $filter_type 过滤类型。默认string
     * @param array  $options     过滤选项。默认null
     *
     * @return mixed 过滤成功，返回过滤后的值，否则返回false
     */
    static public function filterVar($var, $filter_type = 'string', $options = null) {

        //5.3以下版本验证url有bug，http://abc-def.com验证失败
        if ('url' == $filter_type && version_compare(PHP_VERSION, '5.3', '<')) {
             return self::_checkUrl($var);
        }

        $filter_type = self::_getFilterType($filter_type);

        return filter_var($var, $filter_type, $options);
    }

    /**
     * 浮点数过滤
     *
     * @param string $var_name            参数名
     * @param int $type                请求方法，INPUT_GET|INPUT_POST
     * @param string $default             如果过滤返回null时(不存在，过滤失败)默认值。默认0.00
     * @param int $precision              精度,默认2
     *
     * @return string 过滤后的浮点数或默认值
     */
    static public function float($var_name, $type = INPUT_POST, $default = 0.00, $precision = 2) {
        $num = self::filterInput($var_name, 'float', $type, array('options' => array('default' => $default)));

        return number_format($num, $precision, '.', '');
    }

    /**
     * 整数过滤
     *
     * @param string $var_name            参数名
     * @param int $type                请求方法，INPUT_GET|INPUT_POST
     * @param string $default             如果过滤返回null时(不存在，过滤失败)默认值。默认0
     *
     * @return string 过滤后的整数或默认值
     */
    static public function int($var_name, $type = INPUT_POST, $default = 0) {
        return self::filterInput($var_name, 'int', $type, array('options' => array('default' => $default)));
    }

    /**
     * ip过滤
     *
     * @param string $var_name            参数名
     * @param int $type                请求方法，INPUT_GET|INPUT_POST
     * @param string $default             如果过滤返回null时(不存在，过滤失败)默认值。默认''
     *
     * @return string 过滤后的ip或默认值
     */
    static public function ip($var_name, $type = INPUT_POST, $default = '') {
        return self::filterInput($var_name, 'ip', $type, array('options' => array('default' => $default)));
    }

    /**
     * map_int转化成数字类型
     *
     * @param string $var_name           参数名
     * @param int $type               请求方法，INPUT_GET|INPUT_POST
     * @param bool   $return_array       true返回数组。默认false，返回字符串
     * @param mixed  $exclude            排除值。默认0
     *
     * @return array 过滤后数组
     */
    static public function mapInt($var_name, $type = INPUT_POST, $return_array = false, $exclude = 0) {
        $result = self::filterInput($var_name, 'string', $type, array('options' => array('default' => ''), 'flags' => FILTER_FLAG_NO_ENCODE_QUOTES|FILTER_NULL_ON_FAILURE));

        return map_int($result, $return_array, $exclude);
    }

    /**
     * 获取页及总页数
     *
     * @param int   $count     总数
     * @param mixed $page      当前页或变量名。默认page
     * @param mixed $page_size 每页大小或变量名。默认page_size
     *
     * @return array 包含了当前页、总页数、偏移量数组
     */
    static public function page($count, $page = 'page', $page_size = 'page_size') {
        $page_size      = is_int($page_size) ? $page_size : self::int($page_size, INPUT_GET, PAGE_SIZE);
        $total_page     = ceil($count / $page_size);
        $origin_page    = is_int($page) ? $page : self::int($page, INPUT_GET, 1);
        $page           = $origin_page < 1 ? 1 : $origin_page;
        $page           = $page > $total_page ? $total_page : $page;
        $page           = $page < 1 ? 1 : $page;
        $limit          = ($page - 1) * $page_size . ',' . $page_size;

        return array(
            'origin_page'   => $origin_page,
            'page'          => $page,
            'total_page'    => $total_page,
            'limit'         => $limit
        );
    }//end page

    /**
     * 用$_GET或$_POST处理字符串请求变量
     *
     * @param string $var_name      参数名
     * @param int $type          请求方法，INPUT_GET|INPUT_POST
     * @param bool   $trim          true使用trim()去除两边空白。默认true
     * @param bool   $stripslashes  true stripslashes内容。默认false
     * @param string $default       请求变量不存在或返回空值时的默认值。默认''
     *
     * @return string 变量值
     */
    static public function raw($var_name, $type = INPUT_POST, $trim = true, $stripslashes = false, $default = '') {

        if (__GET || INPUT_GET == $type) {//调试模式下，支持通过$_GET获取数据

            if (!isset($_GET[$var_name])) {
                return $default;
            }

            $value = $_GET[$var_name];
        }
        else {

            if(!isset($_POST[$var_name])) {
                return $default;
            }

            $value = $_POST[$var_name];
        }

        if (!is_string($value)) {//非字符串
            $error_log  = '非字符串数据: ' . var_export($value, true);

            Logger::filename(LOG_FILTER_ERROR);
            trigger_error($error_log);

            return $default;
        }

        if ($trim) {
            $value = trim($value);
        }

        if ($stripslashes) {
            $value  = stripslashes($value);
        }

        return '' === $value ? $default : $value;
    }//end raw

    /**
     * 正则过滤
     *
     * @param string $var_name            参数名
     * @param string $regexp              正则表达式
     * @param int $type                请求方法，INPUT_GET|INPUT_POST
     * @param string $default             如果过滤返回null时(不存在，过滤失败)默认值。默认''
     *
     * @return string 过滤后的字符串或默认值
     */
    static public function regexp($var_name, $regexp, $type = INPUT_POST, $default = '') {
        return self::filterInput($var_name, 'regexp', $type, array('options' => array('default' => $default, 'regexp' => $regexp)));
    }

    /**
     * 字符串过滤
     *
     * @param string $var_name            参数名
     * @param int $type                请求方法，INPUT_GET|INPUT_POST
     * @param string $default             如果过滤返回空值时（不存在，过滤失败，返回空字符串）默认值。默认''
     * @param bool   $trim                true trim字符串。默认true
     *
     * @return string 过滤后的字符串或默认值
     */
    static public function string($var_name, $type = INPUT_POST, $default = '', $trim = true) {
        $string = self::filterInput($var_name, 'string', $type, array('options' => array('default' => $default), 'flags' => FILTER_FLAG_NO_ENCODE_QUOTES|FILTER_NULL_ON_FAILURE));

        if ($trim) {
            $string = trim($string);
        }

        return '' === $string ? $default : $string;
    }

    /**
     * url过滤
     *
     * @param string $var_name            参数名
     * @param int $type                请求方法，INPUT_GET|INPUT_POST
     * @param string $default             如果过滤返回null时(不存在，过滤失败)默认值。默认''
     *
     * @return string 过滤后的url或默认值
     */
    static public function url($var_name, $type = INPUT_POST, $default = '') {

        //5.3以下版本验证url有bug，http://abc-def.com验证失败
        if (version_compare(PHP_VERSION, '5.3', '<')) {

            if (__GET || INPUT_GET == $type) {

                if (!isset($_GET[$var_name])) {
                    return false;
                }

                $value = $_GET[$var_name];
            }
            elseif(!isset($_POST[$var_name])) {
                return false;
            }
            else {
                $value = $_POST[$var_name];
            }

            return self::_checkUrl($value);
        }
        else {
            return self::filterInput($var_name, 'url', $type, array('options' => array('default' => $default)));
        }
    }//end url
}