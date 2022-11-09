<?php //页面公共数据
$top_search_cat_id = !empty($_REQUEST['category'])?intval($_REQUEST['category']):'';
$Arr['cat_list']   = cat_list_search($top_search_cat_id,false,true); //search catalog
$abcArr        = range('A', 'Z');
$abcArr[]      = '0-9';
$Arr['abcArr'] =  $abcArr;
$Arr['top_search_keywords'] = read_static_cache('hot_search_keywords');
?>