<?php
/**
 * class.rma.php            RMA 退换货处理类
 *
 * @author                  mashanling(msl-138@163.com)
 * @date                    2012-02-27 16:19:20
 * @last modify             2012-11-02 15:50:16 by mashanling
 */

!defined('INI_WEB') && exit('Access Denied!');
class RMA {
    const WEB_FLAG = 'A';
    const RMA_SOURCE = 'dealsmachine';

    /**
     * RMA记录下拉框
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-07-25 15:40:59
     * @last modify  2012-07-25 15:40:59 by mashanling
     *
     * @return array 下拉框数组
     */
    static function getRMAOptions() {
        global $db;

        $sql  = 'SELECT o.id,o.rma_number,COUNT(m.msg_id) AS msg_count FROM ' . RMA_ORDER . ' AS o LEFT JOIN ' . RMA_MSG . " AS m ON m.rma_order_id=o.id WHERE o.user_id={$_SESSION['user_id']} GROUP BY o.id DESC";
        $list = $db->ArrQuery($sql);
        $data = array();

        foreach ($list as $row) {
            $unread = self::getRMAMsgUnreadCount($row['id']);
            $data[$row['id']] = $row['rma_number'] .  "({$unread}/{$row['msg_count']})";
        }

        return $data;
    }

    /**
     * 获取RMA记录
     *
     * @param int    $page_size 每页大小
     *
     * @return mixed 如果有记录，返回数组(array(0 => 总数, 1 => rma信息))，否则返回false
     */
    static function getRMARecord($page_size = 10) {
        global $db, $_CFG, $Arr;

        $where   = 'o.user_id=' . $_SESSION['user_id'];
        $keyword = isset($_GET['keyword']) ? urldecode(trim($_GET['keyword'])) : '';
        $where  .= $keyword ? " AND (o.order_sn LIKE '%{$keyword}%' OR rma_number LIKE '%{$keyword}%')" : '';
        $time    = empty($_GET['time']) ? 0 : 1;
        $where  .= $time ? ' AND apply_time>' . (gmtime() - 30 * 86400) : '';

        $Arr['time']     = $time;
        $Arr['keyword']  = $keyword;

        $count = $db->count_info(RMA_ORDER . ' AS o', 'id', $where);

        if ($count < 1) {
            return false;
        }

        $rma_data     = read_static_cache('rma_data',FRONT_STATIC_CACHE_PATH);
        $type_arr     = $rma_data['type'];
        $status_arr   = $rma_data['status'];
        $page         = empty($_GET['page']) ? 1 : intval($_GET['page']);
        $page_count   = ceil($count / $page_size);
    	$page         = min($page, $page_count);
    	$page         = max($page, 1);
    	$offset       = $page_size * ($page - 1);

        $sql      = 'SELECT oi.order_id,id,rma_number,o.order_sn,apply_time,apply_type,status,status AS status_int,results,COUNT(m.msg_id) AS msg_count FROM ' . RMA_ORDER . ' AS o LEFT JOIN ' . RMA_MSG . ' AS m ON m.rma_order_id=o.id AND m.is_read=0 JOIN ' . ORDERINFO . " AS oi ON o.order_sn=oi.order_sn WHERE {$where} GROUP BY id DESC LIMIT {$offset},{$page_size}";
        $data_arr = $db->arrQuery($sql);
        //exit($sql);

        $data = array();

        foreach ($data_arr as $row) {
            $status            = $row['status'];
            $results           = $row['results'];
            $row['apply_time'] = local_date($_CFG['AM_time_format'], $row['apply_time']);
            $row['apply_type'] = $type_arr[$row['apply_type']];
            $row['status_text']= $status_arr[$status];
            $row['results']    = $status == 8 && isset($type_arr[$results]) ? $type_arr[$results] : '--';

//            if ($row['msg_count'] > 0) {//最后回复大于30天,转至support
                $last_reply = $db->getOne('SELECT add_time FROM ' . RMA_MSG . ' WHERE rma_order_id=' . $row['id'] . ' AND user_id=0 ORDER BY add_time DESC LIMIT 1');

                if ($last_reply && $last_reply < gmtime() - 86400 * 30) {
                    $row['status_int'] = 8;
                }
  //          }

            $data[]            = $row;
        }

        return array($count, $data);
    }

    /**
     * 判断商品申请数是否已经大于商品购买数
     *
     * @param string $order_sn 订单号
     * @param string $goods_sn 商品编码
     * @param int    $num      本次申请个数
     *
     * @return bool 申请数已经大于商品购买数，返回true，否则返回fasle
     */
    static function applied($order_sn, $goods_sn, $num) {
        global $db;

        $sql_1 = 'SELECT SUM(a.goods_number) FROM ' . RMA_PRODUCT . ' AS a JOIN ' . RMA_ORDER . ' AS b ON a.rma_order_id=b.id JOIN ' . ORDERINFO . " AS c ON b.order_sn=c.order_sn WHERE b.user_id={$_SESSION['user_id']} AND c.order_sn='{$order_sn}' AND a.goods_sn='{$goods_sn}'";
        $sql_2 = 'SELECT goods_number FROM ' . ODRGOODS . ' AS og JOIN ' . ORDERINFO . " AS o ON og.order_id=o.order_id WHERE o.order_sn='{$order_sn}' AND og.goods_sn='{$goods_sn}'";

        return $db->getOne($sql_1) + $num > $db->getOne($sql_2);
    }

	/**
     * 获取RMA信息
     *
     * @param int $id rma_order_id
     *
     * @return array RMA信息
     */
    static function getRMAInfo($id) {
        global $db;

        $rma_data     = read_static_cache('rma_data',FRONT_STATIC_CACHE_PATH);
        $reason_arr   = $rma_data['reason'];

        $data = array();
        $sql  = 'SELECT o.is_return,o.status,o.rma_number,g.*,o.order_sn,oi.address FROM ' . RMA_ORDER . ' AS o JOIN ' . RMA_PRODUCT . ' AS g ON o.id=g.rma_order_id JOIN ' . ORDERINFO . " AS oi ON oi.order_sn=o.order_sn WHERE o.user_id={$_SESSION['user_id']} AND o.id=$id";
        $db->query($sql);

        while ($row = $db->fetchArray()) {
            $last_reply = $db->getOne('SELECT add_time FROM ' . RMA_MSG . ' WHERE rma_order_id=' . $row['rma_order_id'] . ' AND user_id=0 ORDER BY add_time DESC LIMIT 1');

            if ($last_reply && $last_reply < gmtime() - 86400 * 30) {
                $row['status'] = 8;
            }

            $row['reason'] = $reason_arr[$row['reason']];
            $data[] = $row;
        }

        return $data;
    }

    /**
     * 获取未读留言数
     *
     * @param int $id rma_order_id
     *
     * @return int 未留言数
     */
    static function getRMAMsgUnreadCount($id = 0) {
        global $db;

        $data   = array();
        $unread = 0;
        $sql    = 'SELECT COUNT(m.msg_id) FROM ' . RMA_MSG . ' AS m JOIN ' . RMA_ORDER . " AS o ON o.id=m.rma_order_id WHERE o.user_id={$_SESSION['user_id']} AND m.is_read=0" . ($id ? ' AND o.id=' . $id : '');

        return $db->getOne($sql);
    }

    static function updateMsgRead($id) {
        $sql = 'UPDATE ' . RMA_MSG . ' AS m, ' . RMA_ORDER . ' AS o SET m.is_read=1 WHERE m.rma_order_id=o.id AND o.user_id=' . $_SESSION['user_id'] . ($id ? ' AND o.id=' . $id : '');
        $GLOBALS['db']->query($sql);
    }

    /**
     * 获取留言
     *
     * @param int $id rma_order_id
     *
     * @return array 留言信息
     */
    static function getRMAMsg($id  = 0) {
        global $db, $_CFG;

        $format = $_CFG['AM_time_format'];
        $data   = array();
        $sql    = 'SELECT m.*,o.rma_number,o.status,u.firstname,u.lastname FROM ' . RMA_MSG . ' AS m JOIN ' . RMA_ORDER . ' AS o ON o.id=m.rma_order_id JOIN ' . USERS . " AS u ON u.user_id=o.user_id WHERE o.user_id={$_SESSION['user_id']} " . ($id ? ' AND o.id=' . $id : ' AND m.is_read=0') . ' ORDER BY m.add_time DESC';
        $db->query($sql);

        while ($row = $db->fetchArray()) {
            $username = $row['firstname'] . ' ' . $row['lastname'];
            $row['add_time'] = local_date($format, $row['add_time']);
            $row['user']     = $row['user_id'] ? (trim($username) ? $username : 'Yourself') : 'Customer Service';
            $data[] = $row;
        }

        return $data;
    }

    /**
     * 获取订单信息，包括订单商品
     *
     * @param string $order_sn 订单号
     *
     * @return array 订单信息
     */
    static function getOrderInfo($order_sn) {
        global $db;

        $time = gmtime();
        $data = array();
        $sql  = 'SELECT o.order_status,o.shipping_fee+o.free_shipping_fee AS shipping_fee,o.order_amount,o.goods_amount,o.insure_fee,o.Need_Traking_number,o.address,o.address1,o.address2,o.consignee,o.email,o.tel,o.country,o.province,o.city,o.zipcode,g.rec_id,g.goods_name,g.goods_id,g.goods_sn,g.goods_number,g.goods_price FROM '. ORDERINFO . ' AS o JOIN ' . ODRGOODS . " AS g ON o.order_id=g.order_id WHERE o.user_id={$_SESSION['user_id']} AND o.order_sn='{$order_sn}'";
        $sql .= IS_LOCAL || !empty($_SESSION['WebUserInfo']['sa_user']) ? '' : ' AND order_status=3 AND o.add_time>' . ($time - 86400 * 365) . ' AND o.add_time<' . ($time - 86400 * 7);
        $db->query($sql);

        while ($row = $db->fetchArray()) {
            $row['subtotal'] = price_format($row['goods_number'] * $row['goods_price'], false);
            $data[] = $row;
        }

        return $data;
    }

    /**
     * 获取同步RMA信息
     *
     * @return array RMA信息
     */
    static private function getSynData() {
        global $db;

        $data = array();
        $sql  = 'SELECT a.* FROM ' . RMA_ORDER . ' AS a JOIN ' . ORDERINFO . ' AS o ON a.order_sn=o.order_sn' . (IS_LOCAL ? '' : ' WHERE a.is_to_erp=0 AND o.order_status=3') . ' LIMIT 20';
        $arr  = $db->arrQuery($sql);

        foreach ($arr as $row) {
            $data[] = array(
                'RMA_number'     => $row['rma_number'],    //rma号
                'order_number'   => $row['order_sn'],      //订单号
                //'RMA_source'     => $row['rma_source'],    //来源
                'RMA_source'     => 'ahappydeal',    //来源
                'apply_date'     => $row['apply_date'],    //申请日期
                'dispose'        => $row['apply_type'],    //申请类型
                'accessories_1'  => $row['attachment1'],   //附件1
                'accessories_2'  => $row['attachment2'],   //附件2
                'accessories_3'  => $row['attachment3'],   //附件3
                'Email'          => $row['email'],         //收货人email
                'consignee'      => $row['consignee'],     //收货人姓名
                'address_1'      => $row['address_1'],     //收货人地址1
                'address_2'      => $row['address_2'],     //收货人地址2
                'city'           => $row['city'],          //收货人地市
                'province'       => $row['province'],      //收货人省/洲
                'postalcode'     => $row['postalcode'],    //收货人邮编
                'nation'         => $row['nation'],        //收货人国家
                'phone'	         => $row['phone'],         //收货人电话
                'product_arr'    => self::getSynProductData($row['id']),   //具体商品退换货信息
            );
        }

        return $data;
    }

    /**
     * 获取同步留言
     *
     */
    static function getSynDataMsg() {
        global $db;

        $sql    = "SELECT m.msg_id AS auto_id,m.add_date AS amend_time,m.content AS message,o.RMA_number,'" . self::RMA_SOURCE . "' AS RMA_source,IFNULL(CONCAT(u.firstname, ' ', u.lastname), u.email) AS amend_author FROM " . RMA_MSG . ' AS m JOIN ' . RMA_ORDER . ' AS o ON o.id=m.rma_order_id JOIN ' . USERS . ' AS u ON u.user_id=o.user_id' . (IS_LOCAL ? '' : ' WHERE m.is_to_erp=0 AND o.is_to_erp=1 LIMIT 20');
        return $db->arrQuery($sql);
    }

    /**
     * 获取同步跟踪号数据
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-08-29 15:21:03
     * @last modify  2012-08-29 15:21:03 by mashanling
     *
     * @return array 跟踪号数组
     */
    static function getSynDataTrackingNumber() {
        global $db;

        $sql = 'SELECT a.tracking_number,a.rma_order_id,b.rma_number AS RMA_number FROM ' . RMA_TRACKING_NUMBER . ' AS a JOIN ' . RMA_ORDER . ' AS b ON a.rma_order_id=b.id' . (IS_LOCAL ? '' : ' WHERE a.is_to_erp=0 AND b.is_return=1');
        return $db->arrQuery($sql);
    }

	/**
     * 获取RMA具体退换货信息
     *
     * @param int $id id
     *
     * @return array RMA具体退换货信息
     */
    static private function getSynProductData($id) {
        global $db;

        $data = array();
        $sql  = 'SELECT o.rma_number,g.* FROM ' . RMA_ORDER . ' AS o JOIN ' . RMA_PRODUCT . " AS g ON o.id=g.rma_order_id WHERE o.id=$id";
        $db->query($sql);

        while ($row = $db->fetchArray()) {
            $data[] = array(
                'RMA_order_id' => $row['rma_number'],    //rma号
                'product_code' => $row['goods_sn'],      //商品编码
                'product_num'  => $row['goods_number'],  //商品数量
                'product_name' => $row['goods_title'],   //商品标题
                'untread_cause'=> $row['reason'],        //原因
                'depict'       => $row['description'],   //具体描述
            );
        }

        return $data;
    }

    /**
     * 导数据到ERP
     *
     * @param string $url 请求地址
     *
     * @return mixed 执行成功，返回true，否则返回false
     */
    static function execSyn($url, $data_type = 'rma') {
        global $db;

        $rma_no     = '';
        $time_start = microtime(true);

        switch ($data_type) {
            case 'rma'://rma
                $data = self::getSynData();
                $update_sql = 'UPDATE ' . RMA_ORDER .  ' SET is_to_erp=1 WHERE rma_number IN(@where)';
                break;

            case 'msg'://留言
                $data = self::getSynDataMsg();
                $update_sql = 'UPDATE ' . RMA_MSG .  ' SET is_to_erp=1 WHERE msg_id IN(@where)';
                break;

            case 'tracking_number'://跟踪号
                $data = self::getSynDataTrackingNumber();
                $update_sql = 'UPDATE ' . RMA_TRACKING_NUMBER .  ' SET is_to_erp=1 WHERE rma_order_id IN(@where)';
                break;
        }

        empty($data) && exit('no data');

        $data_return = self::execCUrl($url, 'RMA_data=' . urlencode(serialize(addslashes_deep($data))));//exit($data_return);
//var_export($data_return);exit;
        $return      = unserialize($data_return);

        if (!is_array($return)) {

            if (class_exists('Logger', false)) {
                Logger::filename(LOG_FILENAME_PATH);
                trigger_error('ERROR ' . $data_type . var_export($data_return, true) . var_export($data, true));
            }

            return false;
        }

        foreach ($return as $item) {

            $rma_no .= ",'{$item}'";
        }

        if ($rma_no) {
            $rma_no = substr($rma_no, 1);

            $db->query(str_replace('@where', $rma_no, $update_sql));
        }

        if (class_exists('Logger', false)) {
            Logger::filename(LOG_FILENAME_PATH);
            trigger_error($data_type . var_export($data_return, true) . var_export($data, true));
        }

        unset($return, $data_return, $data);

        return true;
    }

    /**
     * 更新状态处理状态
     *
     * @param array $data 数据
     *
     * @return array 包含成功更新的RMA号数组
     */
    static function updateStatus($data) {
        global $db;

        $return = array();

        if (!is_array($data)) {

            if (class_exists('Logger', false)) {
                Logger::filename(LOG_FILENAME_PATH);
                trigger_error('ERROR updateStatus ' . var_export($data, true));
            }

            return $return;
        }

        $rma_data     = read_static_cache('rma_data',FRONT_STATIC_CACHE_PATH);
        $status_arr   = $rma_data['status'];

        foreach ($data as $item) {
            $rma_number = $item['RMA_number'];
            $status     = $item['state'];
            $status     = array_key_exists($status, $status_arr) ? $status : 2;

            $result = $db->update(RMA_ORDER, "status={$status},results=" . intval($item['results']) . ',solve_time=' . local_strtotime($item['time']), "rma_number='{$rma_number}'");

            if ($result) {
                $return[] = isset($item['syn_id']) ? $item['syn_id'] : $rma_number;
            }
        }

        if (class_exists('Logger', false)) {
            Logger::filename(LOG_FILENAME_PATH);
            trigger_error('updateStatus ' . var_export($data, true));
        }

        return $return;
    }

	/**
     * 更新是否退回状态
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-08-01 16:55:45
     * @last modify  2012-08-24 15:23:12 by mashanling
     *
     * @param array $data 数据
     *
     * @return array 包含成功更新的RMA号数组
     */
    static function updateIsReturn($data) {
        global $db;

        $return = array();

        if (!is_array($data)) {

            if (class_exists('Logger', false)) {
                Logger::filename(LOG_FILENAME_PATH);
                trigger_error('ERROR updateIsReturn ' . var_export($data, true));
            }

            return $return;
        }

        foreach ($data as $item) {
            $rma_number = $item['RMA_number'];
            $status     = isset($item['status']) ? intval($item['status']) : 1;

            $result = $db->update(RMA_ORDER, 'is_return=' . $status, "rma_number='{$rma_number}'");

            if ($result) {
                $return[] = $rma_number;
            }
        }

        if (class_exists('Logger', false)) {
            Logger::filename(LOG_FILENAME_PATH);
            trigger_error('updateIsReturn ' . var_export($data, true));
        }

        return $return;
    }

    /**
     * 检查指定RMA是否为退回状态
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-08-25 15:33:32
     * @last modify  2012-08-28 15:08:37 by mashanling
     *
     * @param int $id rma_order_id
     *
     * @return bool 退回状态，返回true，否则返回false
     */
    static function checkRMAIsReturned($id) {
        return IS_LOCAL || !empty($_SESSION['WebUserInfo']['sa_user']) ? true : $GLOBALS['db']->getOne('SELECT user_id FROM ' . RMA_ORDER . " WHERE id={$id} AND user_id={$_SESSION['user_id']} AND is_return=1");
    }

    /**
     * 获取RMA跟踪号
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-08-25 15:33:32
     * @last modify  2012-08-28 15:08:37 by mashanling
     *
     * @param int $id rma_order_id
     *
     * @return string RMA跟踪号
     */
    static function getRMATrackingNumber($id) {
        return $GLOBALS['db']->getOne('SELECT a.tracking_number FROM ' . RMA_TRACKING_NUMBER . ' AS a JOIN ' . RMA_ORDER . " AS b ON a.rma_order_id=b.id WHERE a.rma_order_id={$id} AND b.user_id=" . $_SESSION['user_id'] .  (IS_LOCAL || !empty($_SESSION['WebUserInfo']['sa_user']) ? '' : ' AND b.is_return=1'));
    }

	/**
     * ERP同步留言入库
     *
     * @param array $data 数据
     *
     * @return array 包含成功入库的ERP自增id
     */
    static function execERPMsg($data) {
        global $db, $mail_content, $mail_subject;

        $return = array();

        if (!is_array($data)) {

            if (class_exists('Logger', false)) {
                Logger::filename(LOG_FILENAME_PATH);
                trigger_error('ERROR execERPMsg' . var_export($data, true));
            }

            return $return;
        }

        foreach ($data as &$item) {
            $time       = local_strtotime($item['time']);
            $rma_number = $item['RMA_number'];
            $info       = $db->selectinfo('SELECT o.id,u.email FROM ' . RMA_ORDER . ' AS o JOIN ' . USERS . " AS u ON u.user_id=o.user_id WHERE o.rma_number='{$rma_number}'");
            //$id = 13;
            if (!empty($info) && $db->insert(RMA_MSG, 'is_to_erp,rma_order_id,add_time,content,add_date', "1,{$info['id']},{$time},'{$item['content']}','{$item['time']}'")) {
                $content  = str_replace(array('@rma_number', '@rma_id', '@email'), array($rma_number, $info['id'], $_SESSION['email']), $mail_content);
                $item['email'] = var_export($info, true) . (string)exec_send($info['email'], $mail_subject, $content);
                $return[] = $item['auto_id'];
            }
        }

        if (class_exists('Logger', false)) {
            Logger::filename(LOG_FILENAME_PATH);
            trigger_error('execRMAMsg ' . var_export($data, true));
        }

        return $return;
    }

    /**
     * 执行curl提交
     *
     * @param string $url  url地址
     * @param string $data post数据
     *
     * @return mixed 如果成功执行，返回接收结果，否则返回false
     */
    static private function execCUrl($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result  = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * 生成rma号
     *
     * @param string $web_flag 网站标识
     *
     * @return string rma号
     */
    static function generate_rma_no($web_flag = '') {
        $web_flag = $web_flag ? $web_flag : self::WEB_FLAG;
        return $web_flag . 'RMA' . date('YmdHis') . mt_rand(1000, 9999) . 'W';
    }
}
?>