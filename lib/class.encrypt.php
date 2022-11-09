<?php
/**
 * 加解密类
 *
 * @file                    class.encrypt.php
 * @author                  mashanling <msl-138@163.com>
 * @date                    2013-11-28 14:03:28
 * @lastmodify              $Date: 2014-02-20 11:06:28 +0800 (周四, 2014-02-20) $ $Author: msl $
 */

class Encrypt {
    /**
     * @var string $_encrypt_key 密钥
     */
    private $_encrypt_key;

    /**
     * @var string $_encrypt_type 加密类型
     */
    private $_encrypt_type;

    /**
     * @var string $_mcrypt_cipher 加密算法
     */
    private $_mcrypt_cipher = MCRYPT_RIJNDAEL_256;

    /**
     * @var string $_mcrypt_mode 加密模式
     */
    private $_mcrypt_mode = MCRYPT_MODE_ECB;

    /**
     * @var object $_iv mcrypt向量
     */
    private $_iv;

    /**
     * @var int $_iv_size mcrypt向量大小
     */
    private $_iv_size;

    /**
     * base64加解密字符串
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-30 13:40:18
     *
     * @param string $string 待加解密字符串
     * @param string $type 类型,ENCRYPT加密,DECRYPT解密
     *
     * @return string 加解密后的字符串
     */
    private function _base64($string, $type) {

        if (ENCRYPT == $type) {//加密
            return base64_encode($string) . base64_encode($this->_encrypt_key);
        }
        else {//解密
            return base64_decode(substr($string, 0, -strlen(base64_encode($this->_encrypt_key))));
        }
    }

    /**
     * gz压缩加解密字符串
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-30 13:54:17
     *
     * @param string $string 待加解密字符串
     * @param string $type 类型,ENCRYPT加密,DECRYPT解密
     *
     * @return string 加解密后的字符串
     */
    private function _gzcompress($string, $type) {

        if (ENCRYPT == $type) {//加密
            return gzcompress(serialize($string));
        }
        else {//解密
            return unserialize(gzuncompress($string));
        }
    }

    /**
     * hash密钥
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-29 13:43:45
     *
     * @return string md5后的密钥
     */
    private function _hashEncryptKey() {
        return md5($this->_encrypt_key);
    }

    /**
     * 初始化mcrypt算法
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-29 14:20:33
     *
     * @return void 无返回值
     */
    private function _initMcrypt() {

        if (!$this->_iv) {
            $this->_iv_size    = mcrypt_get_iv_size($this->_mcrypt_cipher, $this->_mcrypt_mode);
            $this->_iv         = mcrypt_create_iv($this->_iv_size, MCRYPT_RAND);
        }
    }

    /**
     * mcrypt加解密字符串
     *
     * @author          mashanling <msl-138@163.com>
     * @date            22013-11-30 17:49:48
     *
     * @param string $string 待加解密字符串
     * @param string $type 类型,ENCRYPT加密,DECRYPT解密
     *
     * @return string 加解密后的字符串
     */
    private function _mcrypt($string, $type) {
        $this->_initMcrypt();

        if (ENCRYPT == $type) {//加密
            return mcrypt_encrypt($this->_mcrypt_cipher, $this->_hashEncryptKey(), $string, $this->_mcrypt_mode, $this->_iv);
        }
        else {//解密
            return trim(mcrypt_decrypt($this->_mcrypt_cipher, $this->_hashEncryptKey(), $string, $this->_mcrypt_mode, $this->_iv));
        }
    }

    /**
     * serialize序列化加解密字符串
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-30 13:54:17
     *
     * @param string $string 待加解密字符串
     * @param string $type 类型,ENCRYPT加密,DECRYPT解密
     *
     * @return string 加解密后的字符串
     */
    private function _serialize($string, $type) {

        if (ENCRYPT == $type) {//加密
            return serialize($string);
        }
        else {//解密
            return unserialize($string);
        }
    }

    /**
     * xor异或加解密字符串
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-29 14:33:20
     *
     * @param string $string 待加解密字符串
     * @param string $type 类型,ENCRYPT加密,DECRYPT解密
     *
     * @return string 加解密后的字符串
     */
    private function _xor($string, $type) {

        if (DECRYPT == $type) {//解密
            $string = base64_decode($string);
        }

        $key    = $this->_hashEncryptKey();
        $len    = strlen($key);
        $code   = '';

        for ($i = 0, $n = strlen($string); $i < $n; $i++) {
            $k = $i % $len;
            $code .= $string[$i] ^ $key[$k];
        }

        return DECRYPT == $type ? $code : base64_encode($code);
    }

    /**
     * 构造函数
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-29 13:35:21
     *
     * @param string $encrypt_key 密钥
     * @param string $encrypt_type 加密类型
     *
     * @param void 无返回值
     */
    public function __construct($encrypt_key = ENCRYPT_KEY, $encrypt_type = ENCRYPT_TYPE_DEFAULT) {
        $this->_encrypt_key = $encrypt_key;
        $this->_encrypt_type = $encrypt_type;
    }

    /**
     * 魔术方法__set
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-29 14:53:28
     *
     * @param string $name 属性名
     * @param string $value 属性值
     *
     * @return void 无返回值
     */
    public function __set($name, $value) {
        $this->$name = $value;
    }

    /**
     * 解密字符串
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-29 13:36:50
     *
     * @param string $string 待解密字符串
     * @param string $encrypt_type 加密类型，类型说明参见配置文件
     *
     * @param {string} 解密后的字符串
     */
    public function decode($string, $encrypt_type = null) {

        if (null === $encrypt_type) {
            $encrypt_type = $this->_encrypt_type;
        }

        switch($encrypt_type) {
            case ENCRYPT_TYPE_MCRYPT:
                return $this->_mcrypt($string, DECRYPT);

            case ENCRYPT_TYPE_MCRYPT2:
                return $this->_mcrypt(base64_decode($string), DECRYPT);

            case ENCRYPT_TYPE_XOR:
                return $this->_xor($string, DECRYPT);

            case ENCRYPT_TYPE_SERIALIZE:
                return $this->_serialize($string, DECRYPT);

            case ENCRYPT_TYPE_GZCOMPRESS:
                return $this->_gzcompress($string, DECRYPT);

            case ENCRYPT_TYPE_BASE64:
                return $this->_base64($string, DECRYPT);

            case ENCRYPT_TYPE_FALSE:
            default:
                return $string;
        }
    }//end decode

    /**
     * 加密字符串
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-29 14:03:12
     *
     * @param string $string 待加密字符串
     * @param string $encrypt_type 加密类型，类型说明参见配置文件
     *
     * @param {string} 加密后的字符串
     */
    public function encode($string, $encrypt_type = null) {

        if (null === $encrypt_type) {
            $encrypt_type = $this->_encrypt_type;
        }

        switch($encrypt_type) {
            case ENCRYPT_TYPE_MCRYPT:
                return $this->_mcrypt($string, ENCRYPT);

            case ENCRYPT_TYPE_MCRYPT2:
                return base64_encode($this->_mcrypt($string, ENCRYPT));

            case ENCRYPT_TYPE_XOR:
                return $this->_xor($string, ENCRYPT);

            case ENCRYPT_TYPE_SERIALIZE:
                return $this->_serialize($string, ENCRYPT);

            case ENCRYPT_TYPE_GZCOMPRESS:
                return $this->_gzcompress($string, ENCRYPT);

            case ENCRYPT_TYPE_BASE64:
                return $this->_base64($string, ENCRYPT);

            case ENCRYPT_TYPE_FALSE:
            default:
                return $string;
        }
    }//end encode
}