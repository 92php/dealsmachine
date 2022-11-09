<?php
/**
 * vote.php				投票管理
 * 
 * @author				mashanling(msl-138@163.com)
 * @date				2011-08-12
 * @last modify         2011-08-22 by mashanling
 */

define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('../lib/class.page.php');
require_once('../lib/param.class.php');

admin_priv('vote_manage');    //检查权限

$Arr['no_records']     = '<span style="color: red">暂无记录！</span>';
$Arr['type_arr']       = array(0 => '单选', 1 => '多选');
$Arr['enable_arr']     = array(0 => '关闭', 1 => '开启');
$Arr['needed_arr']     = array(0 => '否', 1 => '是');
$Arr['other_arr']      = array(0 => '短文本', 1 => '长文本');
$Arr['type_arr']       = array_merge($Arr['type_arr'], $Arr['other_arr']);
$Arr['must_arr']       = array(1 => '必填');
$_ACT                  = Param::get('act');    //操作
$vote                  = new Vote();           //实例化投票类
switch ($_ACT) {
    case 'enable':
        $vote->enable();
        break;
    case 'getTitles':    //根据subject_id获取标题
        $subject_id = Param::get('subject_id', 'int');
        $data_arr   = $vote->getTitles($subject_id);
        echo json_encode($data_arr);
        exit;
        break;
        
    case 'get_tpl':    //获取题组模板
        $tpl_arr = glob(ROOT_PATH . 'temp/skin3/vote/*');
        foreach($tpl_arr as &$item) {
            $filaneme = basename($item);
            $item = '<li>' . $filaneme . '</li>';
        }
        $Arr['html'] = join('', $tpl_arr);
        break;
    
    case 'subject_delete':    //删除投票题组
        admin_priv('vote_subject_add');
        $vote->deleteSubject();
        break;
        
    case 'subject_add':    //增加或编辑投票题组
        admin_priv('vote_subject_add');
        $vote->addSubject();
        break;
        
    case 'subject_update':    //更新投票题组
        admin_priv('vote_subject_add');
        $vote->updateSubject();
        break;
        
    case 'title_list':    //投票标题列表
        admin_priv('vote_title_add');
        $vote->titleList();
        break;
        
    case 'title_add':    //增加或编辑投票标题
        admin_priv('vote_title_add');
        $vote->addTitle();
        break;
        
    case 'title_update':    //更新投票标题
        admin_priv('vote_title_add');
        $vote->updateTitle();
        break;
        
    case 'title_delete':    //删除投票标题
        admin_priv('vote_title_add');
        $vote->deleteTitle();
        break;
        
    case 'option_list':    //投票选项列表
        admin_priv('vote_option_add');
        $vote->optionList();
        break;
        
    case 'option_add':    //增加或编辑投票选项
        admin_priv('vote_option_add');
        $vote->addOption();
        break;
        
    case 'option_update':    //更新投票选项
        admin_priv('vote_option_add');
        $vote->updateOption();
        break;
        
    case 'option_delete':    //删除投票选项
        admin_priv('vote_option_add');
        $vote->deleteOption();
        break;
        
    case 'other':    //删除投票选项
        $vote->other();
        break;
             
    default:    //首页
        admin_priv('vote_subject_list');
        $vote->index();
        break;
}
$_ACT = 'vote/' . ($_ACT ? $_ACT : 'index');
temp_disp();

class Vote {
    private $tb_subject        = VOTE_SUBJECT;
    private $tb_title          = VOTE_TITLE;
    private $tb_option         = VOTE_OPTION;
    private $tb_ip             = VOTE_IP;
    private $tb_other           = VOTE_OTHER;
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
     * 投票首页
     * 
     */
    function index() {
        global $Arr;
        
        $record_count    = Param::get('record_cound', 'int');    //记录总数，第一页不带总数参数，第二页后将带总数
        $record_count    = $record_count > 0 ? $record_count : $this->db->getOne('SELECT COUNT(subject_id) FROM ' . $this->tb_subject);
        
        if (!$record_count) {
            return;
        
        }
        $filter          = array('record_count' => $record_count);
        $filter          = page_and_size($filter);    //分页信息
        $page            = new page(array('total' => $record_count, 'perpage' => $filter['page_size'], 'url' => "vote.php?record_cound={$record_count}"));
    	$Arr['pagestr']  = $page->show();
        $Arr['filter']   = $filter;
        
        $limit           = $filter['start'] . ',' . $filter['page_size'];    //sql limit
        
        $data            = $this->db->select($this->tb_subject, '*', '', 's_order', $limit);
        foreach ($data as &$item) {
            $item['titles'] = $this->db->select($this->tb_title, 'title_id,title', 'subject_id=' . $item['subject_id'], 'enable DESC,s_order,title_id');
        }
        $Arr['data']      = $data;
        }
    
    /**
     * 初始化添加投票题组数据
     * 
     */
    function addSubject() {
        global $Arr;
        $subject_id  = Param::get('subject_id', 'int');
        $Arr['nav']  = ($subject_id ? '编辑' : '添加') . '投票题组';
        $subject_id && ($Arr['data'] = $this->getSubjects($subject_id));
    }
    
    /**
     * 缓存投票题组数据
     * 
     * @access private
     */
    private function writeSubjects() {
        $data_arr  = $this->getSubjects();
        $cache_arr = array();
        foreach ($data_arr as $item) {
            $cache_arr[$item['subject_id']] = $item;
        }
        write_static_cache('vote_subjects', $cache_arr, 2);
    }
    
    /**
     * 获取投票题组信息
     * 
     * @param int $subject_id 题组id
     */
    function getSubjects($subject_id = 0, $get_title = false) {
        $data_arr = $this->db->select($this->tb_subject, '*', $subject_id ? "subject_id={$subject_id}" : '', 's_order,subject_id');
        if ($get_title) {
            foreach ($data_arr as &$item) {
                $item['titles'] = $this->db->select($this->tb_title, 'title_id,title', 'subject_id=' . $item['subject_id'], 's_order,title_id');
            }
        }
        return $data_arr;
    }
    
    /**
     * 判断题组是否已经存在
     * 
     * @param string $subject_name	题组名称
     * @param int    $subject_id    当前题组id
     */
    private function checkSubject($subject_name, $subject_id = 0) {
        $where = "subject='{$subject_name}'" . ($subject_id ? " AND subject_id!={$subject_id}" : '');
        return $this->db->select($this->tb_subject, 'subject_id', $where); 
    }
    
    /**
     * 更新投票题组
     * 
     */
    function updateSubject() {
        $subject    = Param::post('subject');
        $subject_id = Param::post('subject_id', 'int');
        $act        = $subject_id ? '编辑' : '添加';
        
        $flag       = $this->checkSubject($subject, $subject_id);
        
        !empty($flag) && exit("当前题组“{$subject}”已经存在！");
        
        $data_arr = array(
            'subject'	=> $subject,
        	'description'   => Param::post('description', '', false, false),
        	'tpl'       => Param::post('tpl'),
            's_order'   => Param::post('order', 'int'),
            's_limit'   => Param::post('limit', 'int'),
        );
        if ($subject_id) {    //编辑
    		if ($this->db->autoExecute($this->tb_subject, $data_arr, 'UPDATE', 'subject_id=' . $subject_id) !== false){
    		    $msg = '';
    			admin_log('', _EDITSTRING_, '投票题组id:  ' . $subject_id);
    		}
    		else{
    		    $msg = '修改失败';
    		}
    	}
    	else {    //添加
    		$data_arr['create_time'] = gmtime();
    		if ($this->db->autoExecute($this->tb_subject, $data_arr) !== false){
    			$msg = '';
    			admin_log('', _ADDSTRING_, '投票题组: ' . $subject);
    		}
    		else{
    		    $msg = '添加失败';
    		}
    	}
    	$this->writeSubjects();
        exit($msg);
    }
    
    /**
     * 删除投票题组
     * 
     */
    function deleteSubject() {
        $subject_id = Param::post('subject_id', 'int');
        if ($subject_id) {
            $where = 'subject_id=' . $subject_id;
            admin_log('', _DELSTRING_, '投票题组id为 ' . $subject_id);
    		$this->db->delete($this->tb_subject, $where);
    		$this->db->delete($this->tb_title, $where);    //删除题组下标题
    		$this->db->delete($this->tb_option, $where);   //删除题组下选项
    		$this->db->delete($this->tb_other, $where);    //删除题组下其它
    		
            $this->writeSubjects();
    		exit;
        }
        exit('删除失败');
    }
    
    /**
     * 投票标题列表
     * 
     */
    function titleList() {
        global $Arr, $_ACT;
        $subject_id        = Param::get('subject_id', 'int');
        $title_id          = Param::get('title_id', 'int');
        $Arr['subject_id'] = $subject_id;
        $Arr['data']       = $this->getTitles($subject_id, 'subject_id', true, true);
        $Arr['subjects']   = read_static_cache('vote_subjects', 2);
    }
    
    /**
     * 
     */
    function getTitles($id, $column = 'subject_id', $get_option = false, $get_counts = false) {
        $field    = '*';
        if ($get_counts) {
            $field .= ",(SELECT sum(vote_counts) FROM {$this->tb_option} WHERE title_id={$this->tb_title}.title_id) AS vote_counts
            		   ,(SELECT count(other_id) FROM {$this->tb_other} WHERE title_id={$this->tb_title}.title_id) AS other_counts";
        }
        $data_arr = $this->db->select($this->tb_title, $field, $column . '=' . $id, 'enable DESC,s_order,title_id');
        if ($get_option) {
            foreach ($data_arr as &$item) {
                $item['total_counts'] = intval($item['vote_counts'] + $item['other_counts']);
                $item['options'] = $this->db->select($this->tb_option, 'option_id,name,vote_counts', 'title_id=' . $item['title_id'], 's_order,option_id');
                /*if ($item.other) {
                    $item['options'][] = array('vote_counts' => $item['other_counts'])
                }*/
            }
        }
        return $data_arr;
    }
    
    /**
     * 初始化添加投票标题数据
     * 
     */
    function addTitle() {
        global $Arr;
        
        $subject_id        = Param::get('subject_id', 'int');
        $title_id          = Param::get('title_id', 'int');
        $Arr['nav']        = ($title_id ? '编辑' : '添加') . '投票标题';
        $Arr['subjects']   = read_static_cache('vote_subjects', 2);
        $Arr['subject_id'] = $subject_id;
        $title_id && ($Arr['data'] = $this->getTitles($title_id, 'title_id'));
    }
    
    /**
     * 更新投票标题
     * 
     */
    function updateTitle() {
        $title      = Param::post('title');
        $title_id   = Param::post('title_id', 'int');
        $subject_id = Param::post('subject_id', 'int');
        
        $flag       = $this->checkTitle($title, $subject_id, $title_id);
        
        !empty($flag) && exit("当前标题“{$title}”已经存在");
        
        $data_arr = array(
            'title'	    => $title,
        	'other_text'=> Param::post('other_text'),
            'subject_id'=> $subject_id,
            's_order'   => Param::post('order', 'int'),
            's_type'    => Param::post('type', 'int'),
            'enable'    => Param::post('enable', 'int'),
            'needed'    => Param::post('needed', 'int'),
        	'other'     => Param::post('other', 'int'),
            'other_type'=> Param::post('other_type', 'int'),
            'other_needed'  => Param::post('other_needed', 'int'),
        );
        if ($title_id) {    //编辑
    		if ($this->db->autoExecute($this->tb_title, $data_arr, 'UPDATE', 'title_id=' . $title_id) !== false){
    			$msg = '';
    			admin_log('', _EDITSTRING_, '投票标题id:  ' . $title_id);
    		}
    		else{
    		    $msg = '修改失败';
    		}
    	}
    	else{    //添加
    		$data_arr['create_time'] = gmtime();
    		if ($this->db->autoExecute($this->tb_title, $data_arr) !== false) {
    			$msg = '';
    			admin_log('', _ADDSTRING_, '投票标题: ' . $title);
    		}
    		else{
    		    $msg = '添加失败';
    		}
    	}
        exit($msg);
    }
    
    /**
     * 
     * 
     */
    private function checkTitle($title, $subject_id, $title_id = 0) {
        $where = "title='{$title}' AND subject_id={$subject_id}" . ($title_id ? " AND title_id!={$title_id}" : '');
        return $this->db->select($this->tb_title, 'subject_id', $where); 
    }
    
    /**
     * 删除投票标题
     * 
     */
    function deleteTitle() {
        $title_id = Param::post('title_id', 'int');
        if ($title_id) {
            $where = 'title_id=' . $title_id;
            admin_log('', _DELSTRING_, '投票标题id为 ' . $title_id);
    		$this->db->delete($this->tb_title, $where);
    		$this->db->delete($this->tb_option, $where);    //删除标题下选项
    		$this->db->delete($this->tb_other, $where);     //删除标题下其它
    		
    		exit;
        }
        exit('删除失败');
    }
    
	/**
     * 投票标题列表
     * 
     */
    function optionList() {
        global $Arr, $_ACT;
        $subject_id        = Param::get('subject_id', 'int');
        $title_id          = Param::get('title_id', 'int');
        $Arr['subject_id'] = $subject_id;
        $Arr['title_id']   = $title_id;
        $Arr['data']       = $title_id ? $this->getOptions($title_id, 'title_id') : $this->getOptions($subject_id, 'subject_id');
        $Arr['title_arr']  = $this->getTitles($title_id, 'title_id');
        $Arr['subjects']   = read_static_cache('vote_subjects', 2);
    }
    
    /**
     * 获取选项信息
     * 
     */
    private function getOptions($id = 0, $column = 'option_id') {
        return $this->db->arrQuery("SELECT {$this->tb_option}.*,title.title FROM {$this->tb_option}, {$this->tb_title} AS title WHERE {$this->tb_option}.title_id=title.title_id AND {$this->tb_option}.{$column}={$id} ORDER BY {$this->tb_option}.s_order,option_id");
    }
    
    /**
     * 初始化添加投票标题数据
     * 
     */
    function addOption() {
        global $Arr;
        
        $subject_id        = Param::get('subject_id', 'int');
        $title_id          = Param::get('title_id', 'int');
        $option_id         = Param::get('option_id', 'int');
        $Arr['nav']        = ($option_id ? '编辑' : '添加') . '投票选项';
        $Arr['subjects']   = read_static_cache('vote_subjects', 2);
        $Arr['subject_id'] = $subject_id;
        $Arr['title_id']   = $title_id;
        $option_id && ($Arr['data'] = $this->getOptions($option_id));
    }
    
    /**
     * 更新投票标题
     * 
     */
    function updateOption() {
        $name       = Param::post('name');
        $title_id   = Param::post('title_id', 'int');
        $subject_id = Param::post('subject_id', 'int');
        $option_id  = Param::post('option_id', 'int');
        $act        = $title_id ? '编辑' : '添加';
        
        $flag       = $this->checkOption($name, $title_id, $option_id);
        
        !empty($flag) && exit("当前选项“{$name}”已经存在");
        
        $data_arr = array(
            'title_id'  => $title_id,
            'subject_id'=> $subject_id,
            'name'      => $name,
            's_order'   => Param::post('order', 'int'),
            'enable'    => Param::post('enable', 'int'),
            'vote_counts' => Param::post('vote_counts', 'int')
        );
        if ($option_id) {    //编辑
    		if ($this->db->autoExecute($this->tb_option, $data_arr, 'UPDATE', 'option_id=' . $option_id) !== false){
    			$msg = '';
    			admin_log('', _EDITSTRING_, '投票选项id:  ' . $option_id);
    		}
    		else{
    		    $msg = '修改失败';
    		}
    	}
    	else{    //添加
    		$data_arr['create_time'] = gmtime();
    		if ($this->db->autoExecute($this->tb_option, $data_arr) !== false){
    			$msg = '';
    			admin_log('', _ADDSTRING_, '投票选项: ' . $name);
    		}
    		else{
    		    $msg = '添加失败';
    		}
    	}
        exit($msg);
    }
    
    /**
     * 
     */
    private function checkOption($name, $title_id, $option_id = 0) {
        $where = "name='{$name}' AND title_id={$title_id}" . ($option_id ? " AND option_id!={$option_id}" : '');
        return $this->db->select($this->tb_option, 'option_id', $where); 
    }
    
    /**
     * 删除投票标题
     * 
     */
    function deleteOption() {
        $option_id = Param::post('option_id', 'int');
        if ($option_id) {
            $where = 'option_id=' . $option_id;
    		$this->db->delete($this->tb_option, $where);
            admin_log('', _DELSTRING_, '投票选项id为 ' . $option_id);
    		
    		exit;
        }
        exit('删除失败');
    }
    
    /**
     * 设置字段为1或0
     * 
     */
    function enable() {
        $table_arr = array(
            'title'	    => array('name' => $this->tb_title, 'enable' => 'enable', 'needed' => 'needed', 'primary_key' => 'title_id'),
        	'option'	=> array('name' => $this->tb_option, 'enable' => 'enable', 'primary_key' => 'option_id'),
        );
        $table          = $table_arr[Param::post('table')];
        $column         = $table[Param::post('column')];
        $primary_key    = $table['primary_key'];
        $value          = Param::post('value', 'int');
        $id             = Param::post('id', 'int');
        $sql            = "UPDATE {$table['name']} SET {$column}={$value} WHERE {$primary_key}={$id}";
        exit($this->db->query($sql) === false ? 'failure' : '');
    }
    
    function other() {
        global $Arr;
        
        $subject_id = Param::get('subject_id', 'int');
        $title_id   = Param::get('title_id', 'int');
        $where      = $subject_id ? "{$this->tb_other}.subject_id={$subject_id}" : "{$this->tb_other}.title_id={$title_id}";
        
        $record_count    = Param::get('record_cound', 'int');    //记录总数，第一页不带总数参数，第二页后将带总数
        $record_count    = $record_count > 0 ? $record_count : $this->db->getOne("SELECT COUNT(subject_id) FROM {$this->tb_other} WHERE {$where}");
        
        if (!$record_count) {
            return;
        
        }
        $filter          = array('record_count' => $record_count);
        $filter          = page_and_size($filter);    //分页信息
        $page            = new page(array('total' => $record_count, 'perpage' => $filter['page_size'], 'url' => "vote.php?act=other&amp;record_cound={$record_count}&amp;subject_id={$subject_id}&amp;title_id={$title_id}"));
    	$Arr['pagestr']  = $page->show();
        $Arr['filter']   = $filter;
        
        $limit           = $filter['start'] . ',' . $filter['page_size'];    //sql limit
        
        $sql = "SELECT {$this->tb_other}.*,{$this->tb_title}.title FROM {$this->tb_other}, {$this->tb_title} WHERE {$this->tb_other}.title_id={$this->tb_title}.title_id AND {$where} ORDER BY {$this->tb_other}.vote_time DESC LIMIT {$limit}";
        $Arr['data']     = $this->db->arrQuery($sql);
        $Arr['subjects'] = read_static_cache('vote_subjects', 2);
    }
}
?>