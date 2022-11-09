<?php
/**
 * rma_data.php             RMA退换货数据
 *
 * @author                  mashanling(msl-138@163.com)
 * @date                    2012-02-29 15:37:35
 * @last modify             2012-08-22 09:52:40 by mashanling
 */

$data = array(
    'reason' => array(//原因
        1 => 'Defective product',  //产品质量问题
        2 => 'Accessories do not work',  //产品配件问题
        3 => 'Received wrong items',  //发货发错
        4 => 'Items missing from the order',  //发货少发
        5 => 'Products damaged during transit (broken items received)',  //运输破损
        6 => 'Item not received',  //运输未收到货
        7 => 'Product is significantly different from website description',  //产品描述不符
        8 => 'Not satisfied with the product quality',  //客户不满意质量
        9 => 'Other issues',//其他
    ),
    'type' => array(//申请类型
        1 => 'replacement',  //换货
        2 => 'refund',  //退款
        3 => 'coupon code /discount',  //发货发错
    ),
    'status' => array(//处理结果
        1 => 'waitting for processing',//待处理
        2 => 'being processed',//处理中
        8 => 'processed',//已处理
        9 => 'cancel',//取消
    ),
)
?>