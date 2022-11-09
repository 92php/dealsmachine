<?php
/**
 * class.Mail_Auto_Temp.php          自动生成邮件后台处理类
 *
 * @author                      lchen
 * @date                        2013/12/3
 * @lastmodify                  2013/12/3
 */


class Mail_Auto_Temp {
    private $db;
    private $slave_db;

    /**
     * 构造函数
     *
     * @author              lchen
     * @date                2013/12/3
     *
     * @param   string  $action 操作
     *
     * @return void 无返回值
     */
    public function __construct($action) {

        $method = $action . 'Action';
        $this->db = $GLOBALS['db'];
        $this->slave_db = get_slave_db();
        if (false !== strpos($method, '_')) {
            $method = str_replace('_', '', $method);
        }

        if (method_exists($this, $method)) {
            $this->$method();
        }
        else {
            exit('调用方法不存在');
        }
    }

	/*
	 *邮件列表
	*/
    public function listAction() {
        require(ROOT_PATH . 'lib/class.page.php');
        $size = 24;
        $page = isset($_GET['page'])?intval($_GET['page']):1;
        $page = max(1,$page);
        $start = ($page-1)*$size;
        $total = $this->slave_db->getOne("select count(*) from ".MAIL_AUTO_TEMP);
        $sql = "SELECT * from ".MAIL_AUTO_TEMP." order by id desc limit ".$start.','.$size;
        $list = $this->slave_db->arrQuery($sql);
        $now = gmtime();
        foreach($list as $key=>$row) {
            $list[$key]['url'] = WEBSITE."mail/".date('Y',$now)."/".$row['name'].".html";
        }
        $page   = new page(array('total' => $total , 'perpage' => $size));
        $GLOBALS['Arr']['data'] = $list;
        $Arr['pagestr'] = $page->show();
        $GLOBALS['Arr']['pagestr']=$Arr['pagestr'];

    }

	/*
	*	修改
	*/
    public function addAction() {
        $id = isset($_GET['id'])?intval($_GET['id']):'';
        $info = array();
        if(!empty ($id)) {
            $sql = "SELECT * FROM ".MAIL_AUTO_TEMP." WHERE id ='".$id."'";
            $info = $this->slave_db->selectInfo($sql);
            $goods_list = $this->slave_db->arrQuery("SELECT * FROM ".MAIL_AUTO_TEMP_EXTEND." WHERE mid ='".$id."' order by sortby desc, id asc");
            $info['goods_list'] = $goods_list;
            $sql = "SELECT * FROM " . MAIL_AUTO_TEMP_CATEGORY . " WHERE mid = '$id' order by sort_by desc, id asc";
            $cat_info = $this->slave_db->arrQuery($sql);
            $other_cat_list = array();
            $other_cat = array();
            foreach ($cat_info as $n => $row) {
               $other_cat[$n]['cat_id'] = $row['cat_id'];
               $other_cat_list[$n]['list'] = cat_list($row['cat_id']);
               $other_cat_list[$n]['url'] = $row['url'];
               $other_cat_list[$n]['sort_by'] = $row['sort_by'];
               $other_cat_list[$n]['id'] = $row['id'];
               $other_cat_list[$n]['cat_name'] = $row['cat_name'];

            }
            $GLOBALS['Arr']['other_cat_list'] =  $other_cat_list;
        }
            /* 模板赋值 */
        $GLOBALS['Arr']['orher_cat_list'] = cat_list(0);
        $GLOBALS['Arr']['list'] = $info;

    }

	/*
	*	保存模板
	*/
    public function saveAction() {
        $typeArray =  read_static_cache('category_c_key',2);		//此处只取原语言分类进行判断	
        $id = isset($_GET['id'])?intval($_GET['id']):'';
        $goods_sn = isset($_POST['goods_sn'])?$_POST['goods_sn']:array();
        $sortby = isset($_POST['sortby'])?$_POST['sortby']:array();
        $url = isset($_POST['url'])?$_POST['url']:array();
        $sort_by = isset($_POST['sort_by'])?$_POST['sort_by']:array();
        $cat_name = isset($_POST['cat_name'])?$_POST['cat_name']:array();
        $thumb = isset($_POST['thumb'])?$_POST['thumb']:array();
        $list['banner_a'] = isset($_POST['banner_a'])?trim($_POST['banner_a']):''; //主banner
        $list['name'] = isset($_POST['name'])?trim($_POST['name']):'';
        $list['banner_b'] = isset($_POST['banner_b'])?trim($_POST['banner_b']):'';//底部banner
        $list['banner_c'] = isset($_POST['banner_c'])?trim($_POST['banner_c']):'';//底部第二个banner
        $list['banner_d'] = isset($_POST['banner_d'])?trim($_POST['banner_d']):'';//分类banner
        $list['title'] = isset($_POST['title'])?trim($_POST['title']):'';//分类第二个banner
        $list['coupon']   = isset($_POST['coupon'])?trim($_POST['coupon']):"";     //底部coupon
        $list['enddate']  = isset($_POST['enddate'])?trim($_POST['enddate']):'';   //coupon结束时间
        $list['zhekou']  = isset($_POST['zhekou'])?trim($_POST['zhekou']):'';   //coupon结束时间
        if(!empty($id)) {
            $this->db->autoExecute(MAIL_AUTO_TEMP, $list,'UPDATE','id = '.$id);
            $this->db->query("DELETE FROM " .MAIL_AUTO_TEMP_EXTEND." where mid = '".$id."'");
            $this->db->query("DELETE FROM " .MAIL_AUTO_TEMP_CATEGORY." where mid = '".$id."'");
            // 修改不更新
            if(!empty($goods_sn)) {
                foreach($goods_sn as $key=>$row) {
                    if(!empty($row)) {
                        $info['goods_sn'] = $row;
                        $info['sortby'] = isset($sortby[$key])?intval($sortby[$key]):0;
                        $info['thumb'] = isset($thumb[$key])?trim($thumb[$key]):'';
                        $info['mid'] = $id;
                        $this->db->autoExecute(MAIL_AUTO_TEMP_EXTEND,$info,'INSERT','');
                        unset($info);
                    }
                }
            }
             /* 处理扩展分类 */
            if (!empty($_POST['other_cat'])) {
                $cat_Arr = array();
                foreach($_POST['other_cat'] as $k => $val) {
                    
                    if(!empty($val)){
                        $info['cat_id'] =  $val;
                        $info['sort_by'] = isset($sort_by[$k])?intval($sort_by[$k]):0;
                        $info['url'] = !empty($url[$k])?trim($url[$k]):creat_nav_url($typeArray[$val]["url_title"],$val);
                        $info['cat_name'] = isset($cat_name[$k])?trim($cat_name[$k]):'';
                        $info['mid'] = $id;
                        $this->db->autoExecute(MAIL_AUTO_TEMP_CATEGORY,$info,'INSERT','');
                        unset($info);
                    }
                }
            }
            $msg ="修改成功";
        }else {
            $this->db->autoExecute(MAIL_AUTO_TEMP, $list,'INSERT','');
            $insert_id = $this->db->insertId();
            if(!empty($goods_sn)) {
                foreach($goods_sn as $key=>$row) {
                    $info['goods_sn'] = $row;
                    $info['sortby'] = isset($sortby[$key])?intval($sortby[$key]):0;
                    $info['thumb'] = isset($thumb[$key])?trim($thumb[$key]):'';
                    $info['mid'] = $insert_id;
                    $this->db->autoExecute(MAIL_AUTO_TEMP_EXTEND,$info,'INSERT','');
                    unset($info);
                }
            }
             /* 处理扩展分类 */
            if (!empty($_POST['other_cat'])) {
                $cat_Arr = array();
                foreach($_POST['other_cat'] as $k => $val) {

                    if(!empty($val)){
                        $info['cat_id'] =  $val;
                        $info['sort_by'] = isset($sort_by[$k])?intval($sort_by[$k]):0;
                        $info['url'] = !empty($url[$k])?trim($url[$k]):creat_nav_url($typeArray[$val]["url_title"],$val);
                        $info['cat_name'] = isset($cat_name[$k])?trim($cat_name[$k]):'';
                        $info['mid'] = $insert_id;
                        $this->db->autoExecute(MAIL_AUTO_TEMP_CATEGORY,$info,'INSERT','');
                        unset($info);
                    }
                }
            }
            $msg ="添加成功";
        }
        $url =  WEBSITE."m-mail_auto_temp-id-".($id?$id:$insert_id);
        echo "<script type='text/javascript'>window.open('".$url."')</script>";
        $link[0]['name'] = '返回列表';
        $link[0]['url'] = 'mail_auto_temp.php?act=list';
		$link[1]['name'] = '预览';
        $link[1]['url'] = $url;
        sys_msg($msg,0, $link);
    }

       /*
            * 删除邮件产品
       */
    public function removeAction() {
        $id = isset($_GET['id'])?intval($_GET['id']):'';
        if(!empty($id)) {
            $sql = "DELETE FROM ".MAIL_AUTO_TEMP_EXTEND." where id ='".$id."'";
            $this->db->query($sql);
            echo "删除成功";
        }else {
            echo "删除失败";
        }
        exit;
    }
           /*
            * 删除邮件产品
       */
    public function removecatAction() {
        $id = isset($_GET['id'])?intval($_GET['id']):'';
        if(!empty($id)) {
            $sql = "DELETE FROM ".MAIL_AUTO_TEMP_CATEGORY." where id ='".$id."'";
            $this->db->query($sql);
            echo "删除成功";
        }else {
            echo "删除失败";
        }
        exit;
    }
       /*
        * 预览
        */
    public function seeAction() {

        $id = isset($_GET['id'])?intval($_GET['id']):'';
        $data = array();
        $goods_info = array();
        $typeArray =  read_static_cache('category_c_key',2);		//此处只取原语言分类进行判断
        if(!empty($id)) {
            $data = $this->slave_db->selectInfo("SELECT * FROM ".MAIL_AUTO_TEMP." where id = '".$id."'");
            $data['banner_a']   = htmlspecialchars_decode($data['banner_a']);
            $data['banner_b']   = htmlspecialchars_decode($data['banner_b']);
            $data['banner_c']   = htmlspecialchars_decode($data['banner_c']);
            $data['banner_d']   = htmlspecialchars_decode($data['banner_d']);
            $now = gmtime();
            $data['file_name'] = WEBSITE."mail/".date('Y',$now)."/".$data['name'].".html";
            $goods_info = $this->slave_db->arrQuery("SELECT m.*,g.goods_id,g.shop_price,g.url_title,g.market_price,g.goods_title,g.promote_price,promote_start_date,promote_end_date,goods_img,goods_grid FROM ".MAIL_AUTO_TEMP_EXTEND." as m left join ".GOODS." as g on m.goods_sn = g.goods_sn where mid = '".$id."' order by sortby desc,id asc");
            $cat_info   = $this->slave_db->arrQuery("select * from ".MAIL_AUTO_TEMP_CATEGORY." where mid = '".$id."' order by sort_by desc,id asc");
            if(!empty($cat_info)){
                foreach($cat_info as $key=>$row){
                    $cat_info[$key]['cat_name'] = !empty($row['cat_name'])?$row['cat_name']:$typeArray[$row['cat_id']]['cat_name'];
                }
            }
            if(!empty($goods_info)) {
                foreach($goods_info as $key=>$row) {
                    $goods_id = $row['goods_id'];
                    $goods_info[$key]['goods_img']   = get_image_path($goods_id, $row['goods_img']);
                    $goods_info[$key]['goods_grid']   = get_image_path($goods_id, $row['goods_grid']);
                    $goods_info[$key]['shirt_title']   = Mail_Auto_Temp::cutTitle($row['goods_title']);
                    if ($row['promote_price'] > 0) {
                        $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                    }
                    else {
                        $promote_price = 0;
                    }
                    $goods_info[$key]['shop_price'] =  ($promote_price > 0) ? price_format($promote_price) : price_format($row['shop_price']);
                    $goods_info[$key]['url_title']  = get_details_link($goods_id,$row['url_title'],'',true);

                }
            }
        }
        $GLOBALS['Arr']['cat_info'] = $cat_info;
        $GLOBALS['Arr']['list'] = $data;
        $GLOBALS['Arr']['goods_info'] = $goods_info;
    }
       /*
        * 保存html文件
        */
    public function savehtmlAction() {
        $id = isset($_GET['id'])?intval($_GET['id']):'';
        if($id) {
            $url = WEBSITE."m-mail_auto_temp-id-".$id;
            $content   = file_get_contents($url);
            $name = $this->slave_db->getOne("SELECT  name FROM ".MAIL_AUTO_TEMP." WHERE id = '".$id."'");
            $now = gmtime();
            $file_name = ROOT_PATH."mail/".date('Y',$now)."/".$name.".html";
            $cache_file_path = ROOT_PATH."mail/".date('Y',$now);
            !is_dir($cache_file_path) && mkdir($cache_file_path, 0755, true);
            file_put_contents($file_name,$content);
            echo WEBSITE."mail/".date('Y',$now)."/".$name.".html";exit;
        }
    }
       /*
        * 删除邮件
        */
    public function deleteAction() {
        $id = isset($_GET['id'])?intval($_GET['id']):'';
        if(!empty($id)) {
            $sql = "DELETE FROM ".MAIL_AUTO_TEMP." where id = '".$id."'";
            $this->db->query($sql);
            $sql = "DELETE FROM ".MAIL_AUTO_TEMP_EXTEND." where mid = '".$id."'";
            $this->db->query($sql);
            $sql = "DELETE FROM ".MAIL_AUTO_TEMP_CATEGORY." where mid = '".$id."'";
            $this->db->query($sql);
            $msg = "删除成功";
        }else {
            $msg = "删除失败";
        }
        $link[0]['name'] = '返回列表';
        $link[0]['url'] = '?act=list';
        sys_msg($msg,0, $link);
    }
    /*
     * 复制邮件
     */
    public function copyAction(){
        $id = isset($_GET['id'])?intval($_GET['id']):'';
        if(!empty($id)){
            $mail = $this->db->selectInfo("select * from ".MAIL_AUTO_TEMP." where id = '".$id."'");
            $mail['name'] .= '_copy';
            unset($mail['id']);
            $this->db->autoExecute(MAIL_AUTO_TEMP,$mail,'INSERT','');
            $insert_id = $this->db->insertId();
            $goods_info = $this->db->arrQuery("select * from ".MAIL_AUTO_TEMP_EXTEND." where mid = '".$id."' order by  sortby desc ,id asc ");
            $category_info = $this->db->arrQuery("select * from ".MAIL_AUTO_TEMP_CATEGORY." where mid ='".$id."' order by  sort_by desc ,id asc ");
            if(!empty($goods_info)){
                foreach($goods_info as $row){
                    unset($row['id']);
                    $row['mid'] = $insert_id;
                    $this->db->autoExecute(MAIL_AUTO_TEMP_EXTEND,$row,'INSERT','');
                }
            }
            if(!empty($category_info)){
                foreach($category_info as $val){
                    unset($val['id']);
                    $val['mid'] = $insert_id;
                    $this->db->autoExecute(MAIL_AUTO_TEMP_CATEGORY,$val,'INSERT','');
                }
            }
             $msg = "复制成功";
        }else{
            $msg = "复制失败";
        }
        $link[0]['name'] = '返回列表';
        $link[0]['url'] = '?act=list';
        sys_msg($msg,0, $link);
    }
    /*
     * 截断标题
     */
  public function cutTitle($str, $len=63, $tail = "..."){
        $length                = strlen($str);
        $lentail        = strlen($tail);
        $result                = "";
        if($length > $len){
        $len = $len - $lentail;
                for($i = 0;$i < $len;$i ++){
                        if(ord($str[$i]) < 127){
                                $result .= $str[$i];
                        }else{
                                $result .= $str[$i];
                                ++ $i;
                                $result .= $str[$i];
                        }
                }
                $result = strlen($result) > $len ? substr($result, 0, -2) . $tail : $result . $tail;
        }else{
                $result = $str;
        }
        return $result;
}
}