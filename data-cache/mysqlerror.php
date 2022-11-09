<?php
 return array (
  1146 => 
  array (
    'errortime' => 1366808422,
    'errorcont' => ' Database error : On www.ahappydeal.com : Query error:select hot_search,parent_id from CATALOG where cat_id=95
MySQL error is (MySQL return error message): Table \'ahappydeal.CATALOG\' doesn\'t exist
MySQL error code is (Error number): 1146
(date): 2013-04-23 Tuesday 23:00:22
(Visitors IP):209.107.207.80 (url): http://www.ahappydeal.com/Size_7+inch~CPUM_MTK6573~AO_Android+1.6~CPUB_Intel~CPUM_D2500~CPUB_Actions/notebook-tablet-pc-c-95.html
(referer url): 
',
  ),
  1064 => 
  array (
    'errortime' => 1369734837,
    'errorcont' => ' Database error : On www.ahappydeal.com : Query error:SELECT COUNT(og.goods_number) AS sole_nums,g.goods_id,g.is_free_shipping,g.goods_img,g.goods_title,g.url_title,g.goods_thumb,g.market_price,g.shop_price,g.goods_name_style,g.promote_price,g.promote_start_date,g.promote_end_date FROM eload_goods AS g  JOIN eload_order_goods AS og ON og.goods_id=g.goods_id JOIN eload_order_info AS o ON o.order_id=og.order_id WHERE AND g.is_delete=0 AND g.is_on_sale=1 AND g.goods_number>0 GROUP BY g.goods_id ORDER BY sole_nums LIMIT 8
MySQL error is (MySQL return error message): You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'AND g.is_delete=0 AND g.is_on_sale=1 AND g.goods_number>0 GROUP BY g.goods_id OR\' at line 1
MySQL error code is (Error number): 1064
(date): 2013-05-27 Monday 19:53:57
(Visitors IP):64.145.92.141 (url): http://www.ahappydeal.com/Wholesale-hdmi-cablehttp:/www.ahappydeal.com/hobbies-toys-c-103-page-36.html
(referer url): 
',
  ),
  1136 => 
  array (
    'errortime' => 1367830791,
    'errorcont' => ' Database error : On www.ahappydeal.com : Query error:INSERT INTO eload_delete_goods SELECT * FROM eload_order_goods WHERE order_id=771897
MySQL error is (MySQL return error message): Column count doesn\'t match value count at row 1
MySQL error code is (Error number): 1136
(date): 2013-05-05 Sunday 18:59:51
(Visitors IP):165.254.29.116 (url): http://www.ahappydeal.com/eload_admin/order.php?act=batch_operate_post&order_id=D1305051858177773&operation=remove&action_note=
(referer url): http://www.ahappydeal.com/eload_admin/order.php?act=list&order_sn=D1305051858177773&consignee=&order_status=-1
',
  ),
  1054 => 
  array (
    'errortime' => 1368109818,
    'errorcont' => ' Database error : On www.ahappydeal.com : Query error:update eload_review set helpful_yes = 修改的数量不能小于现在的数量 where rid = 54202
MySQL error is (MySQL return error message): Unknown column \'修改的数量不能小于现在的数量\' in \'field list\'
MySQL error code is (Error number): 1054
(date): 2013-05-09 Thursday 00:30:18
(Visitors IP):72.246.55.22 (url): http://www.ahappydeal.com/eload_admin/review.php?act=edit_helpful_yes
(referer url): /eload_admin/review.php?cat_id%5B%5D=0&column=g.goods_sn&keyword=EGS0939&pass_admin=&start_date2=&end_date2=&status=99&has_pic=99&has_video=99&start_date=&end_date=&record_count=0
',
  ),
  0 => 
  array (
    'errortime' => 1365753721,
    'errorcont' => ' Database error : On www.ahappydeal.com : Sorry, due to line fault, temporarily unable to browse, we are dealing with.
MySQL error is (MySQL return error message): 
MySQL error code is (Error number): 
(date): 2013-04-11 Thursday 18:02:01
(Visitors IP):209.170.117.14 (url): http://www.ahappydeal.com/syn/syn_reviewlib.php?act=all
(referer url): 
',
  ),
  1062 => 
  array (
    'errortime' => 1368036654,
    'errorcont' => ' Database error : On www.ahappydeal.com : Query error:INSERT INTO eload_volume_price (price_type, goods_id, volume_number, volume_price) VALUES (\'1\', \'38286\', \'1\', \'2.03254237288\')
MySQL error is (MySQL return error message): Duplicate entry \'1-38286-1\' for key \'PRIMARY\'
MySQL error code is (Error number): 1062
(date): 2013-05-08 Wednesday 04:10:54
(Visitors IP):113.106.90.52 (url): http://184.173.114.242/syn/syn_price.php
(referer url): 
',
  ),
);
?>