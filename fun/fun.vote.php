<?
/**
 * vote.php				投票文件
 * 
 * @author				mashanling(msl-138@163.com)
 * @date				2011-08-16
 * @last modify         2011-08-25 by mashanling
 */
!defined('INI_WEB') && exit('Access denied!');

require(ROOT_PATH . 'fun/fun.global.php');
require(ROOT_PATH . 'lib/param.class.php');

$shop_title     = '';
$nav_title      = '';
$nav_key        = '';
$shop_key       = '';
$subject_id     = Param::get('sid', 'int');    //题组id
$tpl            = Param::get('tpl');           //投票模板
$tpl            = $tpl ? $tpl : 'default';
$_MDL           = 'vote/' . $tpl;
$vote           = new Vote();                  //实例化投票类

switch ($_ACT) {
    case 'vote':    //投票
        if (empty($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'dealsmachine') === false) {
            exit('Access denied!');
        }
        $result = $vote->doVote();
        echo $result === true ? '' : $result;
        exit;
    break;
    
    default:
        if (!$Tpl->is_cached($_MDL . '.htm', $my_cache_id)) {
            $data = $vote->getSubjets($subject_id, '*', true);
            if (empty($data)) {
                $_MDL = 'page_not_found';
            }
            else {
                $Arr['data'] = $data[0];
                $Arr['type_arr'] = array ( 0 => 'box', 1 => 'box', 2 => 'text', 3 => 'text', );
                $Arr['subject_id'] = $subject_id;
                $Arr['seo_title'] =  'survey_dealsmachine.com';
            }
        }
    break;
}

class Vote {
    private $tb_subject        = VOTE_SUBJECT;
    private $tb_title          = VOTE_TITLE;
    private $tb_option         = VOTE_OPTION;
    private $tb_ip             = VOTE_IP;
    private $tb_other          = VOTE_OTHER;
    private $vote_arr          = false;
    private $db                = null;
    
    /**
     * 构造函数
     * 
     */
    function __construct() {
        global $db;
        $this->db = $db; 
    }
    
    /**
     * 获取投票题组
     * 
     * @param int		 $subject_id	题组id
     * @param sting		 $field			选取字段，默认：*
     * @param bool		 $get_titles	是否获取标题及选项，默认：false，不获取
     */
    function getSubjets($subject_id, $field = '*', $get_titles = false) {
        $data_arr = $this->db->select($this->tb_subject, $field, "subject_id={$subject_id}");
        if (!empty($data_arr) && $get_titles) {    //获取标题及选项
            foreach ($data_arr as &$item) {
                $item['titles'] = $this->getTitles(false, $item['subject_id'], '*', true);
            }
        }
        return $data_arr;
    }
    
    /**
     * 获取投票标题
     * 
     * @param int		 $title_id		标题id
     * @param int		 $subject_id	题组id
     * @param string	 $field			选取字段，默认：*
     * @param bool		 $get_options   是否获取选项，默认：false，不获取
     */
    function getTitles($title_id, $subject_id, $field = '*', $get_options = false) {
        $where = $subject_id ? 'subject_id=' . $subject_id : 'title_id=' . $title_id;
        $data_arr = $this->db->select($this->tb_title, $field, $where . ' AND enable=1', 's_order,title_id');
        if (!empty($data_arr) && $get_options) {    //获取标题及选项
            foreach ($data_arr as &$item) {
                $item['options'] = $this->getOptions(false, $item['title_id'], '*', true);
            }
        }
        return $data_arr;
    }
    
    /**
     * 获取投票选项
     * 
     * @param int		 $option_id
     * @param int		 $title_id		标题id
     * @param string	 $field			选取字段，默认：*
     */
    function getOptions($option_id, $title_id, $field = '*') {
        $where = $title_id ? 'title_id=' . $title_id : 'option_id=' . $option_id;
        return $this->db->select($this->tb_option, $field, $where . ' AND enable=1', 's_order,option_id');
    }
    
    /**
     * 投票
     * 
     */
    function doVote() {
        $data_arr    =  $_POST;
        $ip          = sprintf('%u', ip2long(real_ip()));
        $subject_id  = intval($data_arr['sid']);
        if (!$subject_id) {
            return 'sorry, invalid submited';
        }
        $subject_info = read_static_cache('vote_subjects', 2);
        if (empty($subject_info) && empty($subject_info[$subject_id])) {
            return 'sorry, subject is not exists';
        }
        $subject_info = $subject_info[$subject_id];
        $limit_time   = $subject_info['s_limit'];
        if ($limit_time && !$this->checkVoteIp($ip, $subject_id, $limit_time)) {    //投票时间限制
            return "I'm sorry, you have voted winthin {$limit_time} hours";
        }
        
        $option_id   = $data_arr['id'];
        $option_id_arr = explode(',', $option_id);
        if (!empty($option_id_arr)) {
            $sql_option  = "UPDATE {$this->tb_option} SET vote_counts=vote_counts+1 WHERE " . db_create_in($option_id, 'option_id');
            $this->db->query($sql_option);    //更新投票选项投票数
        }
        
        unset($data_arr['sid'], $data_arr['id']);
        
        foreach ($data_arr as $key => $item) {
            $title_id = intval(substr($key, 3));
            $content  = trim(htmlspecialchars($item));
            if ($title_id) {
                $insert   = true;
                if ($content == '' && $this->isNeeded($title_id)) {
                    $insert = false;
                }
                $data     = array('title_id' => $title_id, 'subject_id' => $subject_id, 'content' => $content, 'vote_time' => gmtime());
                $insert && $this->db->autoExecute($this->tb_other, $data);    //处理其它或文本框
            }
        }
        $this->setVoteIp($ip, $subject_id);
        $limit_time && ($_SESSION['vote_ip'] = gmtime());
        return true;
    }
    
    /**
     * 判断当输入内容为空的时候是否入库
     * 
     * @param unknown_type $title_id
     */
    function isNeeded($title_id) {
        $data = $this->db->selectInfo("SELECT needed,s_type FROM {$this->tb_title} WHERE title_id={$title_id}");
        if ($data['s_type'] == 0 || $data['s_type'] == 1) {    //单选/多选 要写入其它
            return false;
        }
        return $data['needed'] ? false : true;
    }
    
    /**
     * 判断ip在指定时间内是否已经投过票
     * 
     * @param int	$ip				经ip2long后的ip地址
     * @param int	$subject_id		题组id
     * @param int	$limit_time     限制时间
     */
    function checkVoteIp($ip, $subject_id, $limit_time) {
        $time  = gmtime() - $limit_time * 3600;
        if (!empty($_SESSION['vote_ip']) && $_SESSION['vote_ip'] > $time) {
            return false;
        }
        
        $count = $this->db->getOne("SELECT COUNT(subject_id) FROM {$this->tb_ip} WHERE ip={$ip} AND subject_id={$subject_id} AND vote_time>{$time}");
        return $count ? false : true;
    }
    
    /**
     * 投票ip入库
     * 
     * @param int	$ip				经ip2long后的ip地址
     * @param int	$subject_id		题组id
     */
    function setVoteIp($ip, $subject_id) {
        $this->db->autoExecute($this->tb_ip, array('ip' => $ip, 'subject_id' => $subject_id, 'vote_time' => gmtime()));
        $this->db->query("UPDATE {$this->tb_subject} SET vote_counts=vote_counts+1 WHERE subject_id={$subject_id}");    //更新题组投票总人数
    }
}

/**
 * 在smarty模板里注册选项函数
 * 
 * @param mixed $param 参数
 */
function smarty_set_option($param) {
    extract($param);
    $html       = empty($br) ? '<br />' : $br;
    $s_type     = $title_arr['s_type'];
    $temp       = "-{$title_arr['title_id']}";//-{$title_arr['subject_id']}
    $other      = $title_arr['other'];
    $other_type = $title_arr['other_type'];
    $other_text = $title_arr['other_text'];
    $other_text == '' && ($other_text = 'other');
    switch ($s_type) {
        case 1:    //多选
            foreach ($option_arr as $item) {
                $html .= sprintf('<label><input type="checkbox" name="cb%s[]" value="%d" />%s</label><br />', $temp, $item['option_id'], $item['name']);
            };
            if ($other) {
                $html .= sprintf('<label><input type="checkbox" name="cb%s[]" value="0" />%s</label><br />', $temp, $other_text);
                $html .= set_vote_input($other_type, $temp, '', $title_arr['other_needed']);
            }
        break;
        
        case 2:    //text输入框
            $html .= set_vote_input(0, $temp, '', $title_arr['needed']);
            break;
            
        case 3:    //textarea输入框
            $html .= set_vote_input(1, $temp, '', $title_arr['needed']);
            break;
        
        default:   //单选
            foreach ($option_arr as $item) {
                $html .= sprintf('<label><input type="radio" name="rb%s" value="%d" />%s</label><br />', $temp, $item['option_id'], $item['name']);
            };
            if ($other) {
                $html .= sprintf('<label><input type="radio" name="rb%s" value="0" />%s</label><br />', $temp, $other_text);
                $html .= set_vote_input($other_type, $temp, '', $title_arr['other_needed']);
            }
            break;
    }
    return $html;
}

/**
 * 设置文本输入框
 * 
 * @param int		 $type		类型（0：text; 1：textarea）
 * @param string	 $name		输入框name属性
 * @param string	 $id		输入框id属性
 * @param bool		 $needed	是否必填
 */
function set_vote_input($type = 0, $name, $id = '', $needed = false) {
    $id     = $id ? " id={$id}" : '';
    $needed = $needed ? '<span style="color: red" class="span-needed">*</span>' : '';
    if ($type == 0) {
        return sprintf('<input type="text" size="50" name="txt%s"%s />', $name, $id) . $needed;
    }
    elseif ($type == 1) {
        return sprintf('<textarea name="txt%s"%s rows="5" cols="50"></textarea>', $name, $id) . $needed;
    }
    return '';
}
$Tpl->register_function('smarty_set_option', 'smarty_set_option');    //注册函数
?>  