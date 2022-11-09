<?php
/**
 * param.class.php		参数处理类
 * 
 * @author				mashanling(msl-138@163.com)
 * @date				2011-08-11
 * @last modify			2011-08-11 by mashanling
 */

class Param {
    /**
     * $_GET
     * 
     * @param string 	$name				参数名
     * @param string 	$type				值类型，默认：string，字符串
     * @param bool   	$trim				是否删除值两边空白，默认：true，删除
     * @param bool 		$htmlspecialchars   是否将html标签转化为对应实体，默认：true，转化
     */
    static function get($name, $type = 'string', $trim = true, $htmlspecialchars = true) {
        return self::request($name, 'get', $type, $trim, $htmlspecialchars);
    }
    
    /**
     * $_POST
     * 
     * @param string 	$name				参数名
     * @param string 	$type				值类型，默认：string，字符串
     * @param bool   	$trim				是否删除值两边空白，默认：true，删除
     * @param bool 		$htmlspecialchars   是否将html标签转化为对应实体，默认：true，转化
     */
    static function post($name, $type = 'string', $trim = true, $htmlspecialchars = true) {
        return self::request($name, 'post', $type, $trim, $htmlspecialchars);
    }
    
	/**
     * $_SERVER['argv']通过命令行执行
     * 
     * @param string 	$index				参数序号
     * @param string 	$type				值类型，默认：string，字符串
     * @param bool   	$trim				是否删除值两边空白，默认：true，删除
     * @param bool 		$htmlspecialchars   是否将html标签转化为对应实体，默认：true，转化
     */
    static function cmd($index, $type = 'string', $trim = true, $htmlspecialchars = true) {
        return self::request($index, 'cmd', $type, $trim, $htmlspecialchars);
    }
    
    /**
     * 处理$_GET或$_POST
     * 
     * @param string 	$name				参数名
     * @param string    $method				请求方式，默认：get
     * @param string 	$type				值类型，默认：string，字符串
     * @param bool   	$trim				是否删除值两边空白，默认：true，删除
     * @param bool 		$htmlspecialchars   是否将html标签转化为对应实体，默认：true，转化
     */
    static private function request($name, $method = 'get', $type = 'string', $trim = true, $htmlspecialchars = true) {
        if ($method == 'cmd') {
            $string = isset($_SERVER['argv']) && isset($_SERVER['argv'][$name]) ? $_SERVER['argv'][$name] : '';
        }
        else {
            $method = $method == 'get' ? $_GET : $_POST;
            $string = isset($method[$name]) ? $method[$name] : '';
        }
        $string = isset($method[$name]) ? $method[$name] : '';
        $string = $trim ? trim($string) : $string;
        $string = $htmlspecialchars ? htmlspecialchars($string, ENT_QUOTES) : $string;
        return self::checkType($string, $type);
    }
    
    /**
     * 返回指定类型值
     * 
     * @param string	$string		源字符串
     * @param string	$type		类型，默认：string，字符串
     */
    static private function checkType($string, $type = 'string') {
        switch ($type) {
            case 'int':    //整数
                $string = intval($string);
                break;
                
            default:
                break;
        }
        return $string;
    }
}
?>