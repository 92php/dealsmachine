<?php

/**
 * class.analyze_customers_action.php      网站客户行为分析类
 * 
 * @author                                 mashanling(msl-138@163.com)
 * @date                                   2011-08-30
 * @last modify                            2011-09-07 by mashanling
 */
!defined('INI_WEB') && exit('Access denied!');

class AnalyzeCustomersAction extends Analyze {

    public $key_record = 'lastSetAnalyzeCustomersAction';
    public $table = ANALYZE_CUSTOMERS_ACTION;
    public $name = '客户行为';

    /**
     * 获取当天客户行为统计数据
     * 
     * @param int $unixtime 当天unix gmt时间戳
     * 
     */
    static function getData($unixtime) {
        $promotion_num = self::getPromotionNums($unixtime);
        $data = array(
            'review_nums' => self::getNewReivewGoodsNum($unixtime), //评论商品个数
            'user_review_nums' => self::_getNewUserReivewGoodsNum($unixtime), //用户评论商品的个数
            'extradb_review_nums' => self::_getNewExtraDbReivewGoodsNum($unixtime), //评论库评论商品的个数
            'inquiry_nums' => self::getProInquiryGoodsNum($unixtime), //咨询商品个数
            'groupbuy_nums' => self::getGroupBuyNums($unixtime), //团购购买次数
            'point_nums' => self::getPointRecordNums($unixtime), //积分使用次数
            'promotion_nums' => $promotion_num[0], //折扣券使用次数
            'coupon_nums' => $promotion_num[1], //代金券使用次数
            'helpful_yes_nums' => self::getHelpfulYesNums($unixtime), //评论赞YES
            'helpful_no_nums' => self::getHelpfulNoNums($unixtime), //评论赞NO
        );
        return $data;
    }

    /**
     * 获取团购购买个数
     * 
     * @param int $unixtime 当天unix gmt时间戳
     */
    static private function getGroupBuyNums($unixtime) {
        //return self::getTodayNums(ODRGOODS, 'addtime', $unixtime, ' AND is_groupbuy=1');
        return self::getTodayNums(ODRGOODS . ' AS g JOIN ' . ORDERINFO . ' AS o ON o.order_id=g.order_id', 'o.add_time', $unixtime, ' AND o.order_status BETWEEN 1 AND 8 AND g.is_groupbuy=1');
    }

    /**
     * 产品评论个数
     * 
     * @param int $unixtime 当天unix gmt时间戳
     */
    static private function getNewReivewGoodsNum($unixtime) {
        return self::getTodayNums(REVIEW, 'adddate', $unixtime, '', 'goods_id');
    }

    /**
     * 产品-用户评论个数
     * @param Int $unixtime,当天UNIX/GM时间戳
     * 
     */
    static private function _getNewUserReivewGoodsNum($unixtime) {
        return self::getTodayNums(REVIEW, 'adddate', $unixtime, 'AND user_id!=0', 'goods_id');
    }

    /**
     * 产品-评论库评论个数
     * @param Int $unixtime,当天UNIX/GM时间戳
     * 
     */
    static private function _getNewExtraDbReivewGoodsNum($unixtime) {
        return self::getTodayNums(REVIEW, 'adddate', $unixtime, 'AND user_id=0', 'goods_id');
    }

    /**
     * 产品咨询个数
     * 
     * @param int $unixtime 当天unix gmt时间戳
     */
    static private function getProInquiryGoodsNum($unixtime) {
        return self::getTodayNums(PRO_INQUIRY, 'adddate', $unixtime, '', 'goods_id');
    }

    /**
     * 获取积分使用次数
     * 
     * @param int $unixtime 当天unix gmt时间戳
     */
    static private function getPointRecordNums($unixtime) {
        return self::getTodayNums(ORDERINFO, 'add_time', $unixtime, ' AND used_point>0');
    }

    /**
     * 折扣券、代金券使用次数
     * 
     * @param int $unixtime 当天unix gmt时间戳
     */
    static private function getPromotionNums($unixtime) {
        $sql = 'SELECT c.is_applay FROM ' . ORDERINFO . ' AS o JOIN ' . PCODE . ' AS c ON o.promotion_code=c.code WHERE ' . self::getTimeWhere('o.add_time', $unixtime) . " AND promotion_code!=''";
        $data = $GLOBALS['db']->arrQuery($sql);
        $array = array(
            0 => 0, //折扣券
            1 => 0, //代金券
        );
        foreach ($data as $v) {
            $v['is_applay'] ? $array[1]++ : $array[0]++;
        }
        return $array;
    }

    /**
     * 获取评论赞YES次数
     * 
     * @param int $unixtime 当天unix gmt时间戳
     */
    static private function getHelpfulYesNums($unixtime) {
        $sql = "SELECT COUNT(*) FROM " . REVIEW_HELPFUL . " WHERE review_helpful_type = 0 AND user_id != 0 AND " . self::getTimeWhere('add_time', $unixtime);
        return $GLOBALS['db']->getOne($sql);
    }

    /**
     * 获取评论赞NO次数
     * 
     * @param int $unixtime 当天unix gmt时间戳
     */
    static private function getHelpfulNoNums($unixtime) {
        $sql = "SELECT COUNT(*) FROM " . REVIEW_HELPFUL . " WHERE review_helpful_type = 1 AND user_id != 0 AND " . self::getTimeWhere('add_time', $unixtime);
        return $GLOBALS['db']->getOne($sql);
    }

}

?>