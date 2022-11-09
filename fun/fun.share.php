<?php
require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');
require_once(ROOT_PATH .'lib/lib.f.goods.php');
require_once(ROOT_PATH . 'lib/class.page.php');
$Tpl->caching = false;
$_ACT = isset($_GET['a'])?$_GET['a']:'index';
$my_cache_id = $cur_lang ;
$my_cache_id = sprintf('%X', crc32($my_cache_id));
if (!$Tpl->is_cached($_MDL.'.htm', $my_cache_id)) {
//获取分享产品
    if ($_ACT == 'index') {
        $Arr['goods_info'] = getShare();

        $Arr['Winner'] = getWinner();
        $Arr['review'] = getReview();
        $Arr['top_review'] = getTopReview();
        $page_size = 10;
        $total = $db->getOne("select count(*) from eload_share_review where review_id = 0 and top = 0");
        $page         = new page(array('total' => $total, 'perpage' => $page_size));
        $pagestr      = $page->show(7);
		$pagestr = str_replace('href','data-href',$pagestr);
		$pagestr = str_replace('<a ','<a href="javascript:void(0)" class="page_down" ',$pagestr);	
		$Arr['pagestr'] = str_replace('/m-share-a-ajax_reivew','/m-share-a-ajax_reivew-page-',$pagestr);
		$Arr['pagestr'] = str_replace('/m-share','/m-share-a-ajax_reivew-page-',$pagestr);

		$winer_total = $db->getOne("select count(*) from ".SHARE_WINNER);
        $pages         = new page(array('total' => $winer_total, 'perpage' => $page_size,'pagebarnum'=>6));
        $winner_pagestr      = $pages->show(7,'share');
		$winner_pagestr = str_replace('href','data-href',$winner_pagestr);
		$winner_pagestr = str_replace('<a ','<a href="javascript:void(0)" class="winner_page_down" ',$winner_pagestr);
		$Arr['winner_pagestr'] = str_replace('/m-share','/m-share-a-ajax_winner-page-',$winner_pagestr);
        $Arr['seo_title'] = 'Join dealsmachine share and win';
        $Arr['seo_keywords']   = 'Join dealsmachine share and win activity, you will have the chance to win the item you share，one day one winner';
        $Arr['seo_description']   = 'Join dealsmachine share and win activity, you will have the chance to win the item you share，one day one winner';

    }
    if ($_ACT == 'add_link_auto')  //保存链接
    {
        $user_id = empty($_SESSION['user_id'])?'':$_SESSION['user_id'];
        if ($user_id == '') {
            echo "Please<a href='/m-users-a-sign.htm'>Sign in</a>";
            exit;
        }
        //$linkid = empty($_GET["id"])?'':intval($_GET["id"]);
        $pid = empty($_GET["pid"])?'':$_GET["pid"];
        if(!$pid)empty($_POST["pid"])?'':$_POST["pid"];
        if(!$pid) {
            echo "product info error";
            exit();
        }
        $sql= "select goods_id,goods_name,goods_title,goods_thumb,goods_sn,url_title from ".GOODS ." where goods_id=$pid";
        $goods = $db->selectInfo($sql);
        if(!$goods) {
            echo "product info error";
            exit();
        }
        if($link_id=$db->getOne("select link_id from ".WJ_SHARE." where goods_sn ='".$goods['goods_sn']."' and user_id = ".$user_id)) {

            $link_id = $link_id;

        }else {
            $link_name = addslashes($goods['goods_name']);
            $link_text = addslashes($goods['goods_title']);
            $link_url  = get_details_link($goods['goods_id'],$goods['url_title']);
            $img       = get_image_path(false, $goods['goods_thumb']);
            $link["link_name"] = $link_name;
            $link["link_text"] = $link_text;
            $link["pid"]=$pid;
            $link["link_url"] = $link_url;
            $link["adddate"] = gmtime();
            $link["last_modify"] = gmtime();
            $link["img"]=$img;
            $link["user_id"] = 40298;
            $db->autoExecute(WJ_LINK, $link);
            $link_id = $db->insertId();
            $fb_link["user_id"] = $user_id;
            $fb_link['fb_uid']  = isset($_COOKIE['fbuid'])?$_COOKIE['fbuid']:0;
            $fb_link['goods_sn'] = $goods['goods_sn'];
            $fb_link['email'] = $_COOKIE['fb_email']?trim($_COOKIE['fb_email']):'';
            $fb_link['link_id'] = $link_id;
            $db->autoExecute(WJ_SHARE, $fb_link);
        }
        $sql= "select * from ".WJ_LINK ." where pid=$pid and id =$link_id ";
        $link_info = $db->selectInfo($sql);
        if($link_info) {
        //添加到分享扩展表
        //$str.="<b>http://".$_SERVER['SERVER_NAME']."/m-webad-a-r-lid-".$link_info['id'].".htm</b>";
            /*if (preg_match ('/\?/', $link_info['link_url'])) {
                $str=$link_info['link_url'] ."&lkid=" . $link_info['id'];
            }
            else {
                $str=$link_info['link_url'] ."?lkid=" . $link_info['id'];
            }*/
            $s   = false === strpos($link_info['link_url'], '#') ? '#' : '';
            $str = $link_info['link_url'] . $s . 'lkid=' . $link_info['id'];
            ajaxReturn($str);
        }
        exit();
    }
    if($_ACT == "add_review") {
        $review = array();
        $review['user_id']=$user_id = empty($_SESSION['user_id'])?'':$_SESSION['user_id'];
        check_is_sign();//检查是否登录
        if($user_id && isset($_POST['contents'])) {
            $review['contents'] = isset($_POST['contents'])?HtmlEncode(trim($_POST['contents'])):'';
            $review['review_id'] = isset($_POST['review_id'])?intval($_POST['review_id']):0;
            $review['add_time'] = gmtime();
            $db->autoExecute("eload_share_review",$review);
            $url_link = '/m-share.htm';
            $url_contect = "Click here to go back to ";
            show_message('success, thanks for you review',$url_contect,$url_link,'info');
        }
    }
 if($_ACT == "add_reply") {
        $review = array();
        $review['user_id']=$user_id = empty($_SESSION['user_id'])?'':$_SESSION['user_id'];
        check_is_sign();//检查是否登录
        if($user_id && isset($_POST['contents'])) {
            $review['contents'] = isset($_POST['contents'])?HtmlEncode(trim($_POST['contents'])):'';
            $review['review_id'] = isset($_POST['review_id'])?intval($_POST['review_id']):0;
            $review['add_time'] = gmtime();
            $db->autoExecute("eload_share_review",$review);
            echo "success";exit;
        }
        exit("error");
    }
  if($_ACT == 'del_reviww') {
        $review_id = $_POST['review_id']?intval($_POST['review_id']):'';
        if($review_id) {
            $db->query("delete from eload_share_review where id = '".$review_id."' or review_id = '".$review_id."'");
            admin_log("",'',"删除FB分享评论成功");
            echo "删除成功";
        }else {
            echo "删除失败没有选择评论";
        }
        exit;
    }
   if($_ACT == 'ajax_reivew') {
		/*
			ajax请求返回评论
		*/
        $page = isset($_GET['page'])?intval($_GET['page']):1;
        $total = $db->getOne("select count(*) from eload_share_review where review_id = 0");
        $page = $page<1?1:$page;
        $page = $page>($total_page=($total/10)==0?($total/10):($total/10)+1)?$total_page:$page;
        $size = 10;
        $start = ($page-1) * $size;
        $sql = "select r.*,u.firstname from eload_share_review as r left join ".USERS." as u on r.user_id = u.user_id where review_id = 0 order by add_time desc limit ".$start.",".$size;
		$page                = new page(array('total' => $total, 'perpage' => $size,'nowindex'=>$page));
        $pagestr      = $page->show(7);
		$pagestr = str_replace('href','data-href',$pagestr);
		$pagestr = str_replace('<a ','<a href="javascript:void(0)" class="page_down" ',$pagestr);
		$pagestr = str_replace('/m-share-a-ajax_reivew','/m-share-a-ajax_reivew-page-',$pagestr);
		$pagestr = str_replace('/m-share','/m-share-a-ajax_reivew-page-',$pagestr);		
        $data = $db->arrQuery($sql);
        $str = '<div id="review_content">';
        if(!empty($data)) {
            $reply = array();
            foreach ($data as $key=>$row) {
                $avatar = IMGCACHE_URL.'ximages/62.gif';
                $reply = $db->arrQuery("select  r.*,u.firstname from eload_share_review as r left join ".USERS." as u on r.user_id = u.user_id where review_id = '".$row['Id']."'");
				$del = '';
				$del = isset($_SESSION['WebUserInfo'])?'<span class="del_reply"><a href="javascript:void(0)" onclick="is_top('.$row['Id'].')">置顶</a></span><span class="del_reply"><a href="javascript:void(0)" onclick="del_reply('.$row['Id'].')">删除</a></span>':'';
                $count = count($reply)>0?'('.count($reply).")":'';

                $str .= '<div class="questions">'.$del.'<div class="questions_l"><img src="'.$avatar.'" width="60" height="60" border="0"></div>
							<div class="questions_r">
							<div class="questions_r_q">
								By <strong>'.$row['firstname'].'</strong> '.date('M-d/y H:i:s',$row['add_time']).'<br>
								<strong>'.$row['contents'].'</strong>
							</div>
							<a rel="nofollow" href="javascript:void(0)" style="color:#000; text-decoration:underline" class="reply" rid="'.$row['Id'].'">Reply'.$count .'</a>';
				$str .='<div class="review_reply" style="display:none">';
                   
				if ($reply) {
                    foreach ($reply as $reply) {
                        $re_avatar = IMGCACHE_URL.'ximages/62.gif';
						$del = isset($_SESSION['WebUserInfo'])?'<span class="del_reply"><a href="javascript:void(0)" onclick="del_reply('.$reply['Id'].')">删除</a></span>':'';
                        $str .='<div class="questions_r_r">
									<div class="questions_l"><img src="'.$re_avatar.'" width="60" height="60" border="0"></div>
									<div class="questions_r">
										<strong>'.$reply['firstname'].': </strong>'.$reply['contents'].' <br>'
                       . date("M-d/y H:i:s",$reply['add_time'])
                        .'</div><div class="clear0"></div></div>';
                        unset($re_avatar);
                    }
                } 
				$str .='<div class="re_reply"></div></div></div><div class="clear0"></div></div>';
                    unset($reply);
					unset($avatar);
                }
                $str .= '</div>';
                echo  json_encode(array('str'=>$str,'page'=>$pagestr));
            }else {

                echo '';
            }
            exit;
        }

    }
	if($_ACT == 'ajax_winner'){
		$page = isset($_GET['page'])?intval($_GET['page']):1;
        $total = $db->getOne("select count(*) from eload_share_winner ");
        $page = $page<1?1:$page;
        $page = $page>($total_page=($total/10)==0?($total/10):($total/10)+1)?$total_page:$page;
        $size = 10;
        $start = ($page-1) * $size;
		$page                = new page(array('total' => $total, 'perpage' => $size,'nowindex'=>$page,'pagebarnum'=>6));
        $pagestr      = $page->show(7,'share');
		$pagestr = str_replace('href','data-href',$pagestr);
		$pagestr = str_replace('<a ','<a href="javascript:void(0)" class="winner_page_down" ',$pagestr);
		$pagestr = str_replace('/m-share-a-ajax_winner','/m-share-a-ajax_winner-page-',$pagestr);
		$pagestr = str_replace('/m-share','/m-share-a-ajax_winner-page-',$pagestr);		
		$sql = "select s.*,g.goods_thumb,g.url_title,g.goods_title,g.goods_id from ".SHARE_WINNER." as s left join ".GOODS." as g on g.goods_sn = s.goods_sn  order by id desc limit ".$start.",".$size;
        $info = $db->arrQuery($sql);
		$str = '';
		if(!empty($info)){
			foreach($info as $row){
				$row['adddate'] = date('M-d',$row['add_time']);
                $row['fbuid'] = $row['fb_uid'];
				if($row['goods_id']){
					$row['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
					$row['url_title'] = get_details_link($row['goods_id'], $row['url_title']);
				}else{
					$row['goods_thumb'] = IMGCACHE_URL."ximages/fb/points.gif";
				}
				$row['email'] = hideStr($row['email'],0,4,2);
				$row['avatar'] = $row['fb_uid']?'<a href="https://www.facebook.com/profile.php?id='.$row['fb_uid'].'" target="_blank"><img src="http://graph.facebook.com/'.$row['fb_uid'].'/picture?type=large" width="60" height="60" border="0"></a>':'<img src="/temp/skin3/ximages/pinit_avatar.gif" width="60" height="60" border="0">';
				$str .= '<li><p class="fbr_user">'.$row['avatar'].'</p>
				<p class="fbr_text"><strong>'.$row['adddate'].'</strong><br>'.$row['email'].'</p>
				<p class="fbr_img"><a href="'.$row['url_title'].'"><img src="'.$row['goods_thumb'].'" width="60" height="60" border="0"></a></p>';
				if ($row['goods_id']){
					$str .='<p class="fbr_icon"></p>';
				}
				$str .= '</li>';
				
				unset($row['email']);
				unset($row['adddate']);
				unset($row['fbuid']);
				unset($row['goods_thumb']);
				unset($row['url_title']);
				unset($row['aratar']);
			}
			$str .= '<li class="winner_page"><div class="pages winner_pages"></div></li>';
			echo  json_encode(array('str'=>$str,'page'=>$pagestr));
		}else{
			echo '';
		}
		exit;
		
	}
//评论置顶
if($_ACT == 'is_top') {
    $review_id = $_POST['review_id']?intval($_POST['review_id']):'';
    if($review_id) {
        $status = $db->getOne("select top from eload_share_review where id = '".$review_id."'");
        if($status) {
            $db->query("update eload_share_review set top = 0 where id = '".$review_id."'");
        }else {
            $db->query("update eload_share_review set top = 1 where id = '".$review_id."'");
        }
        admin_log("",'',"删除FB分享评论成功");
        echo "成功";
    }else {
        echo "没有选择评论";
    }
    exit;

}
    function getShare() {
		$time = gmtime() + 8*3600; //当前北京时间 +8
		$w=date( "w ",$time);
		//本周星期一时间戳
		$monday = $w==1?mktime(0, 0, 0, date("m",strtotime("Monday")), date("d",strtotime("Monday")), date("Y",strtotime("Monday"))):mktime(0, 0, 0, date("m",strtotime("next Monday")), date("d",strtotime("next Monday")), date("Y",strtotime("next Monday")));
		$last_monday = mktime(0, 0, 0, date("m",strtotime("last Monday")), date("d",strtotime("last Monday")), date("Y",strtotime("last Monday")));	
		//当前时间大于周一10点结束时间取下周一
		if($w==1 ){
			if(date("H",$time)<15 && 10<=date("H",$time))
			{
				$monday	= mktime(0, 0, 0, date("m",strtotime("next Monday")), date("d",strtotime("next Monday")), date("Y",strtotime("next Monday")))+7*3600*24;
			}
			else 
			{
				$monday	= mktime(0, 0, 0, date("m",strtotime("next Monday")), date("d",strtotime("next Monday")), date("Y",strtotime("next Monday")));
			}
			if(date("H",$time)<10){

				$last_monday = mktime(0, 0, 0, date("m",strtotime("Monday")), date("d",strtotime("Monday")), date("Y",strtotime("Monday")))-7*3600*24;
			}else{
				$last_monday = mktime(0, 0, 0, date("m",strtotime("Monday")), date("d",strtotime("Monday")), date("Y",strtotime("Monday")));
			}
		}
		elseif($w<3 && $w != 0){
			if($w ==2 && date("H",$time)>14){
				$last_monday = mktime(0, 0, 0, date("m",strtotime("last Monday")), date("d",strtotime("last Monday")), date("Y",strtotime("last Monday")));
			}else{
				$last_monday = mktime(0, 0, 0, date("m",strtotime("Monday")), date("d",strtotime("Monday")), date("Y",strtotime("Monday")));
			}
		}
		else{
			$last_monday = mktime(0, 0, 0, date("m",strtotime("last Monday")), date("d",strtotime("last Monday")), date("Y",strtotime("last Monday")));
		}
		//清除点击率
		if($w ==1 && $time-$monday == (16*3600)){
			$data = array();
			write_static_cache('share_click',$data,1);
		}
		$last_monday = $last_monday+2*3600; //只能加2个小时
		$monday = $monday+10*3600;
		/*本周星期日时间戳
		$sunday = $w==0?mktime(0, 0, 0, date("m",strtotime("Sunday")), date("d",strtotime("Sunday")), date("Y",strtotime("Sunday"))):mktime(0, 0, 0, date("m",strtotime("next Sunday")), date("d",strtotime("next Sunday")), date("Y",strtotime("next Sunday")));
		$sunday = $sunday+24*3599;
		*/
        $goods_info = array();
        $admin_share = read_static_cache('admin_share',1);
		$goods_sn = array();
		$limit = 20;
		if(!empty($admin_share)){
			$limit = 20-count($admin_share);
			foreach($admin_share as $key=>$val){
				$goods_sn[] = $key;
			}
		}
		$where = '';
		$not_where = "";
		if(!empty($goods_sn)){
			$where = " g.goods_sn in ('".implode("','",$goods_sn)."')";
			$not_where = "and id in (select link_id from ".WJ_SHARE." where goods_sn not in ('".implode("','",$goods_sn)."'))";
			
		}
         $sql = "select count(w.id) as num,id,w.adddate,g.goods_sn,g.goods_number,g.goods_thumb,g.goods_img,g.shop_price,g.is_free_shipping,g.url_title,g.market_price,g.goods_title,g.goods_id from eload_wj_link as w left join eload_wj_share as s on w.id=s.link_id left join ".GOODS." as g on g.goods_sn = s.goods_sn where adddate between '".$last_monday."' and '". $monday."'  and s.goods_sn !='' ".$not_where." group by link_url order by num desc limit $limit";
		$wj_info = $GLOBALS['db']->arrQuery($sql);  //取出当天分享最多的产品链接
		
		$click = read_static_cache('share_click',1);
		$goods_info = array();
		if($click){
			rsort($click);
		}
        if(!empty($wj_info)) {
            foreach($wj_info as $key=>$row) {
				$click_num = isset($click[$key])?$click[$key]:0;
                $goods_info[$key]['share_number'] = $row['num']+$click_num;
                $goods_info[$key]['left_time'] = $monday-$time;
				$goods_info[$key]['goods_number']  = ($GLOBALS['_CFG']['use_storage'] == 1) ? $row['goods_number'] : '';
				$goods_info[$key]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
				$goods_info[$key]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img'], true);
				$goods_info[$key]['shop_price'] = $row['shop_price'];	
				$goods_info[$key]['is_free_shipping'] = $row['is_free_shipping'];
				$goods_info[$key]['url_title']  = get_details_link($row['goods_id'],$row['url_title']);
				$goods_info[$key]['market_price'] = $row['market_price'];
				$goods_info[$key]['goods_title']  = $row['goods_title'];
				$goods_info[$key]['goods_id']   = $row['goods_id'];
				$goods_info[$key]['goods_sn']   = $row['goods_sn'];
				unset($click_num);
				
            }
			if(!empty($where)){
				$admin_goods = $GLOBALS['db']->arrQuery('SELECT g.goods_sn,g.goods_number,g.goods_thumb,g.goods_img,g.shop_price,g.is_free_shipping,g.url_title,g.market_price,g.goods_title,g.goods_id' .' FROM ' . GOODS . ' AS g where '.$where);
				if($admin_goods){
						foreach($admin_goods as $row){				
							$admin_info[$row['goods_sn']][$row['goods_sn']]['left_time']  = $monday-$time;
							$admin_info[$row['goods_sn']][$row['goods_sn']]['goods_number']  = ($GLOBALS['_CFG']['use_storage'] == 1) ? $row['goods_number'] : '';
							$admin_info[$row['goods_sn']][$row['goods_sn']]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
							$admin_info[$row['goods_sn']][$row['goods_sn']]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img'], true);
							$admin_info[$row['goods_sn']][$row['goods_sn']]['is_free_shipping'] = $row['is_free_shipping'];
							$admin_info[$row['goods_sn']][$row['goods_sn']]['shop_price'] = $row['shop_price'];
							$admin_info[$row['goods_sn']][$row['goods_sn']]['url_title']  = get_details_link($row['goods_id'],$row['url_title']);
							$admin_info[$row['goods_sn']][$row['goods_sn']]['market_price'] = $row['market_price'];
							$admin_info[$row['goods_sn']][$row['goods_sn']]['goods_title']  = $row['goods_title'];
							$admin_info[$row['goods_sn']][$row['goods_sn']]['goods_id']   = $row['goods_id'];
							$admin_info[$row['goods_sn']][$row['goods_sn']]['goods_sn']   = $row['goods_sn'];
						}
				}
			}
            if (!empty($admin_info)) {
				//数组排序
				if(!empty($admin_info) && !empty($admin_share)){
					$count = count($goods_info);
					foreach($admin_share as $kew=>$li){
						if($li>$count){
							$li = $count;
						}
						array_splice($goods_info,$li,0,$admin_info[$kew]);
						if($li == 0){
							$share_number = $goods_info[$li+1]['share_number']+32;
						}elseif($li == 19 || $li >= $count){
							$share_number = $goods_info[$li-1]['share_number'];
						}else{
							$share_number = rand($goods_info[$li+1]['share_number'],$goods_info[$li-1]['share_number']);
						}
						$goods_info[$li]['share_number'] = $share_number;
						unset($share_number);
					}
				}
               
            }
			$goods_info = array_filter($goods_info);
			return $goods_info;
        }else {
            return '';
        }

    }
    //获取当前分享最多的用户
    function getWinner() {
        global $db;
        $sql = "select s.*,g.goods_thumb,g.url_title,g.goods_title,g.goods_id from ".SHARE_WINNER." as s left join ".GOODS." as g on g.goods_sn = s.goods_sn  order by add_time desc,id desc limit 10";
        $info = $db->arrQuery($sql);

        if($info) {
            foreach($info as $key=>$row) {
                $info[$key]['adddate'] = date('M-d',$row['add_time']);
                $info[$key]['fbuid'] = $row['fb_uid'];
				if($row['goods_id']){
					$info[$key]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
					$info[$key]['url_title'] = get_details_link($row['goods_id'], $row['url_title']);
				}else{
					$info[$key]['goods_thumb'] = IMGCACHE_URL."ximages/fb/points.gif";
				}
				$info[$key]['email'] = hideStr($row['email'],0,4,2);
            }
            return $info ;
        }else {

            return false;
        }
    }

    //获取评论

    function getReview() {
        global $db;
        $sql = "select r.*,u.firstname from eload_share_review as r left join ".USERS." as u on r.user_id = u.user_id where review_id = 0 and top=0 order by add_time desc limit 10";//取最新的10条评论
        $data = $db->arrQuery($sql);
        if(!empty($data)) {
            $reply = array();
            foreach ($data as $key=>$row) {
                $reply = $db->arrQuery("select  r.*,u.firstname from eload_share_review as r left join ".USERS." as u on r.user_id = u.user_id where review_id = '".$row['Id']."'");
                if(!empty($reply)) {
                    foreach($reply as $keys=>$val) {
                        $reply[$keys]['add_time'] = date("M-d/y H:i:s",$val['add_time']);
						$reply[$keys]['contents'] = str_replace('\\', '', stripslashes($val['contents']));
                }
                $data[$key]['reply'] = $reply;
            }
            $data[$key]['count'] = isset($data[$key]['reply'])?count($data[$key]['reply']):0;
            $data[$key]['add_time'] = date("M-d/y H:i:s",$row['add_time']);
			$data[$key]['contents'] = str_replace('\\', '', stripslashes($row['contents']));
            unset($reply);
        }
        return $data;
    }else {

        return '';
    }
}
//获取置顶评论

function getTopReview() {
    global $db ;
    $sql = "select r.*,u.firstname from eload_share_review as r left join ".USERS." as u on r.user_id = u.user_id where review_id = 0 and top =1 order by add_time desc ";//取最新的10条评论
    $data = $db->arrQuery($sql);
    if(!empty($data)) {
        $reply = array();
        foreach ($data as $key=>$row) {
            $reply = $db->arrQuery("select  r.*,u.firstname from eload_share_review as r left join ".USERS." as u on r.user_id = u.user_id where review_id = '".$row['Id']."'");
            if(!empty($reply)) {
                foreach($reply as $keys=>$val) {
                    $reply[$keys]['add_time'] = date("M-d/y H:i:s",$val['add_time']);
					$reply[$keys]['contents'] = str_replace('\\', '', stripslashes($val['contents']));
                }
                $data[$key]['reply'] = $reply;
            }
            $data[$key]['count'] = isset($data[$key]['reply'])?count($data[$key]['reply']):0;
            $data[$key]['add_time'] = date("M-d/y H:i:s",$row['add_time']);
			$data[$key]['contents'] = str_replace('\\', '', stripslashes($row['contents']));
            unset($reply);
        }
        return $data;
    }else {

        return '';
    }
}
/**
 +----------------------------------------------------------
 * 将一个字符串部分字符用*替代隐藏
 +----------------------------------------------------------
 * @param string    $string   待转换的字符串
 * @param int       $bengin   起始位置，从0开始计数，当$type=4时，表示左侧保留长度
 * @param int       $len      需要转换成*的字符个数，当$type=4时，表示右侧保留长度
 * @param int       $type     转换类型：0，从左向右隐藏；1，从右向左隐藏；2，从指定字符位置分割前由右向左隐藏；3，从指定字符位置分割后由左向右隐藏；4，保留首末指定字符串
 * @param string    $glue     分割符
 +----------------------------------------------------------
 * @return string   处理后的字符串
 +----------------------------------------------------------
 */
function hideStr($string, $bengin=0, $len = 4, $type = 0, $glue = "@") {
    if (empty($string))
        return false;
    $array = array();
    if ($type == 0 || $type == 1 || $type == 4) {
        $strlen = $length = mb_strlen($string);
        while ($strlen) {
            $array[] = mb_substr($string, 0, 1, "utf8");
            $string = mb_substr($string, 1, $strlen, "utf8");
            $strlen = mb_strlen($string);
        }
    }
    if ($type == 0) {
        for ($i = $bengin; $i < ($bengin + $len); $i++) {
            if (isset($array[$i]))
                $array[$i] = "*";
        }
        $string = implode("", $array);
    }else if ($type == 1) {
            $array = array_reverse($array);
            for ($i = $bengin; $i < ($bengin + $len); $i++) {
                if (isset($array[$i]))
                    $array[$i] = "*";
            }
            $string = implode("", array_reverse($array));
        }else if ($type == 2) {
            $array = explode($glue, $string);
            $array[0] = hideStr($array[0], $bengin, $len, 1);
            $string = implode($glue, $array);
        } else if ($type == 3) {
            $array = explode($glue, $string);
            $array[1] = hideStr($array[1], $bengin, $len, 0);
            $string = implode($glue, $array);
        } else if ($type == 4) {
            $left = $bengin;
            $right = $len;
            $tem = array();
            for ($i = 0; $i < ($length - $right); $i++) {
                if (isset($array[$i]))
                    $tem[] = $i >= $left ? "*" : $array[$i];
            }
            $array = array_chunk(array_reverse($array), $right);
            $array = array_reverse($array[0]);
            for ($i = 0; $i < $right; $i++) {
                $tem[] = $array[$i];
            }
            $string = implode("", $tem);
        }
        return $string;
    }

?>