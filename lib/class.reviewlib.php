<?php
/**
 * class.reviewlib.php      评论库
 *
 * @author                  mashanling <msl-138@163.com>
 * @date                    2013-02-01 15:04:55
 * @lastmodify              $Author: msl $ $Date: 2013-09-21 13:45:07 +0800 (周六, 21 九月 2013) $
 */

!defined('INI_WEB') && exit('Access Denied!');

class ReviewLib {
    /**
     * @var int $_hits2review_min 小点击量对应评论数。默认50=>1
     */
    private $_hits2review_min = 50;
    /**
     * @var int $_hits2review_max 大点击量对应评论数。默认200=>1
     */
    private $_hits2review_max = 200;
    /**
     * @var bool $_is_test true测试
     */
    private $_is_test;
    /**
     * @var string $_auth_key 认证码
     */
    private $_auth_key;
    /**
     * @var string $_site_name 网站名称
     */
    private $_site_name;

    /**
     * @var bool $_is_local true本地开发环境
     */
    private $_is_local;

    /**
     * @var string $_syn_url 同步地址
     */
    private $_syn_url;

    /**
     * @var object $_db_slave db从服务器
     */
    private $_db_slave;

    /**
     * 入库,出库过滤
     1. 好评单词pro超过10个单词量才进来 （避免只有几个good，very good几个单词且没意义的评论进入后台评论库中，耽误客服审核时间）
2. 审核通过的评论才进来（避免三颗星以下的进来）
3. 有负面词语的不进来
负面词语:poor quality, poor, slow, long time delivery, short, shorter, small, not suitable,
 unsuitable, broken, don’t like, terrible, not receive, scam, bad, bad service
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-09-21 10:01:36
     *
     * @param array $data 评论数据
     *
     * @return bool true通过,否则false
     */
    private function _check($data) {

        if (substr_count($data['pros'], ' ') < 9) {//好评单词pro超过10个单词量才进来
            return false;
        }

        $rate_arr = array('rate_price', 'rate_easyuse', 'rate_quality', 'rate_usefulness','rate_overall');

        foreach($rate_arr as $item) {//避免三颗星以下的进来

            if ($data[$item] < 3) {
                return false;
            }
        }

        $regexp  = '/\bpoor|poor|short|shorter|not suitable|unsuitable';
        $regexp .= '|broken|don\'t like|terrible|not receive|scam|bad service\b/i';

        //有负面词语的不进来
        if (preg_match($regexp, $data['pros'] . ' ' . $data['cons'] . ' ' . $data['suggestions'])) {
            return false;
        }

        return true;
    }//end _check

    /**
     * curl同步评论
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-02-01 15:57:07
     *
     * @param string $data 经http_build_query后评论数据
     *
     * @return mixed ERP返回结果
     */
    private function _execCurl($data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_syn_url . 'SetSingleReview');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result  = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * 获取同步评论
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-02-01 17:02:56
     *
     * @return array 评论数据
     */
     private function _getSynData() {
        global $db;

        $data = array();
        $sql  = 'SELECT is_to_erp AS is_syn,\'' . $this->_site_name . '\' AS site_name,r.rid,r.subject,r.pros,r.cons,r.rate_price,r.rate_easyuse,r.rate_quality,r.rate_usefulness,r.rate_overall,r.nickname,r.adddate,r.other_thoughts AS suggestions,g.goods_sn  FROM ' . REVIEW . ' AS r JOIN ' . GOODS . ' AS g ON r.goods_id=g.goods_id' . ($this->_is_local ? '' : ' WHERE r.is_to_erp=0 AND r.is_pass=1') . ' ORDER BY rid LIMIT 20';
        $db->query($sql);

        while ($row = $db->fetchArray()) {

            if ($this->_check($row)) {
                $row['adddate'] = local_date('Y-m-d H:i:s', $row['adddate']);
                $data[$row['rid']] = $row;
            }
        }

        return $data;
    }

    /**
     * 获取待从ERP获取评论商品sku
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-02-02 14:39:32
     * @lastmodify      2013-02-19 10:56:37 by mashanling
     *
     * @return array 商品数据
     */
     private function _getSN() {
        //$this->_db_slave->delete(REVIEWLIB_GOODS, 'add_time<' . (gmtime() - 86400 * 60));//干掉60天前产品
        $data = array();
        $id   = isset($_GET['id']) ? map_int($_GET['id']) : 0;

        if ($id) {//指定商品id
            $where = " WHERE r.goods_id IN({$id})";
        }
        else {
            $where = $this->_is_local ? '' : ' WHERE r.hits>=' . $this->_hits2review_min . ' AND g.goods_number>0 AND g.is_on_sale=1 AND g.is_delete=0';
        }

        $sql  = 'SELECT g.goods_sn,r.goods_id FROM ' . REVIEWLIB_GOODS . ' AS r JOIN ' . GOODS . " AS g ON r.goods_id=g.goods_id {$where}";
        $data = $this->_db_slave->arrQuery($sql);

        if ($id) {//指定商品id
            return $data;
        }

        if (!$data) {
            return array();
        }

        //ORDER BY RAND() => array_rand 效率高 by mashanling on 2013-09-24 10:19:17
        $rand   = array_rand($data, 20);
        $rand   = is_array($rand) ? $rand : array($rand);
        $return = array();

        foreach($rand as $v) {
            $return[] = $data[$v];
        }

        return $return;
    }//end _getSN

    /**
     * 写日志
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-02-01 15:49:38
     *
     * @return void 无返回值
     */
    private function _log($log, $time_start = false) {

        if (function_exists('e_log')) {//everbuying
            return e_log($log, '', $time_start);
        }

        $ip   = '[' . real_ip() . ']';
        $fmt  = 'H:i:s: ';
        $log  = $log ? (function_exists('local_date') ? local_date($fmt) : date($fmt, time() - date('Z') + 28800)) . $ip . $log : $log;
        $log .= ' ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'];
        $log .= $time_start ? ', 用时' . execute_time($time_start) : '';
        $dir  = ROOT_PATH . 'eload_admin/crontab/log/' . (function_exists('local_date') ? local_date('Y/md/') : date('Y/md/', time() - date('Z') + 28800));

        !is_dir($dir) && mkdir($dir, 0755, true);

        $filename = $_SERVER['SCRIPT_NAME'];
        $filename = $dir . basename($filename) . '.log';

        if (is_file($filename) && filesize($filename) > 1024 * 500) {//大于500KB，备份 by mashanling on 2012-11-28 09:52:45
            rename($filename, $dir . basename($filename, '.log') . date('_His') . '.log');
        }

        file_put_contents($filename, $log . PHP_EOL, FILE_APPEND);
    }

    /**
     * 构造函数
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-02-01 15:14:00
     *
     * @return void 无返回值
     */
    public function __construct() {
        $this->_is_test = isset($_GET['test']);
        $this->_site_name = substr(COOKIESDIAMON, 1);//COOKIESDIAMON = .everbuying.com
        $this->_is_local = 'dealsmachine.com' == $this->_site_name ? false : true;
        $hostname = $this->_is_local ? 'http://192.168.3.4:999' : 'http://www.davismicro.com.cn:9000';
        $this->_syn_url = $hostname . '/stock_admin/reviews.php?act=';
        $this->_db_slave = $this->_is_local ? $GLOBALS['db'] : get_slave_db();
        //$this->_syn_url = 'http://v2.e.com/syn/syn_reviewlib.php?act=';
    }

    /**
     * 给指定商品添加一条评论
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-02-01 15:24:42
     *
     * @return void 无返回值
     */
    public function addReviews() {
        global $db;

        $id         = array();
        $time_start = microtime(true);
        $goods_sn   = $this->_getSN();
        $log        = '';

        if (!$goods_sn) {
            echo 'addReviews no data';
            return;
        }

        $data       = file_get_contents($this->_syn_url . 'GetReviews&goods_sn=' . serialize($goods_sn));
        $return     = unserialize($data);

        if (!is_array($return)) {
            $this->_log($data, $time_start);
            return false;
        }

        if (empty($return)) {//获取不到评论,点击也要减,否则,下次还是这些产品 by mashanling on 2013-05-02 14:24:39
            $ids = '';

            foreach ($goods_sn as $item) {
                $ids .= ',' . $item['goods_id'];
            }

            $ids = substr($ids, 1);
            $this->_log('no reviews' . var_export($goods_sn, true));
            $this->_db_slave->update(REVIEWLIB_GOODS, "hits=hits-IF(hits>={$this->_hits2review_max},{$this->_hits2review_max},{$this->_hits2review_min})", "goods_id IN({$ids})");//更新评论库点击量

            return;
        }

        foreach ($return as $item) {
            //将评论中everbuying sammydress 替换为dealsmachine
            $item['other_thoughts'] = isset($item['suggestions']) ? str_ireplace(array('everbuying','sammydress'),array('dealsmachine','everbuying'),$item['suggestions']) : '';
            $item['pros'] = isset($item['pros']) ? str_ireplace(array('everbuying','sammydress'),array('dealsmachine','everbuying'),$item['pros']) : '';
            $item['cons'] = isset($item['cons']) ? str_ireplace(array('everbuying','sammydress'),array('dealsmachine','everbuying'),$item['cons']) : '';
            $item['subject'] = isset($item['subject']) ? str_ireplace(array('everbuying','sammydress'),array('dealsmachine','everbuying'),$item['subject']) : '';            
            if (is_numeric($item) || !$this->_check($item)) {//未取到评论 by mrmsl on 2013-02-19 11:26:02
                is_numeric($item) && $this->_db_slave->update(REVIEWLIB_GOODS, "hits=hits-IF(hits>={$this->_hits2review_max},{$this->_hits2review_max},{$this->_hits2review_min})", "goods_id={$item}");//更新评论库点击量
                continue;
            }
            elseif (isset($item['id'])) {
                $id[$item['id']] = $item['id'];
            }

            $item['user_id'] = 0;
            $item['adddate'] = gmtime() - rand(-7200, 7200);
            $item['addtime_real'] = $item['adddate'];
            $item['is_to_erp'] = 1;
            $item['is_pass'] = 0;
            unset($item['site_name'], $item['goods_sn'], $item['goods_url'], $item['suggestions'], $item['cat_id'], $item['is_syn'], $item['cat_name']);
            $item = addslashes_deep($item);

            if (!$this->_is_test && isset($item['subject']) && trim($item['subject']) && !$db->count_info(REVIEW, 'rid', "goods_id={$item['goods_id']} AND subject='{$item['subject']}'") && $db->autoExecute(REVIEW, $item)) {
                $this->_db_slave->update(REVIEWLIB_GOODS, "hits=hits-IF(hits>={$this->_hits2review_max},{$this->_hits2review_max},{$this->_hits2review_min})", "goods_id={$item['goods_id']}");//更新评论库点击量
            }
            elseif (isset($item['id'])) {
                unset($id[$item['id']]);
            }
        }

        var_dump($id);
        $this->_log(var_export($id, true));

        return true;
    }//end addReviews

    /**
     * 导数据到ERP
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-02-01 15:22:22
     *
     * @return true执行成功，否则false
     */
    public function execSyn() {
        global $db;

        $time_start = microtime(true);
        $data       = $this->_getSynData();

        if (!$data) {
            echo 'execSyn no data';
            return;
        }

        $data_return = $this->_execCurl('data=' . urlencode(serialize(addslashes_deep($data))) . '&key=' . $GLOBALS['_CFG']['keys_code']);
        $return      = unserialize($data_return);//array(评论id1,评论id2,...)

        if ($this->_is_test) {
            var_dump($return);
            return;
        }


        if (!$return) {//unserialize出错
            $this->_log('error' . var_export($data_return, true) . var_export($data, true), $time_start);
            var_dump($return, $data_return);
            return false;
        }

        $review_id = join(',', $return);//成功同步评论id

        $db->update(REVIEW, 'is_to_erp=1', "rid IN({$review_id})");
        $this->_log(var_export($data, true), $time_start);

        unset($return, $data_return, $data);

        return true;
    }//end execSyn
}