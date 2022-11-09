<?php
/**
 * class.seo_admin.php          seo后台处理类
 *
 * @author                      mashanling <msl-138@163.com>
 * @date                        2013-07-24 08:47:43
 * @lastmodify                  $Author: xyl $ $Date: 2013-08-26 13:40:50 +0800 (Mon, 26 Aug 2013) $
 */

require_once(ROOT_PATH . 'lib/seo/class.seo.php');

class SEO_Admin extends SEO {
    /**
     * @var int $_out_max 最大导出关键字个数
     */
    private $_out_max = 5000;

     /**
     * @var string $_in_out_cache_key 最大导出关键字个数
     */
    private $_in_out_cache_key = 'new_abc_index_keyword';

    /**
     * 构造函数
     *
     * @author              mashanling <msl-138@163.com>
     * @date                2013-07-22 09:06:14
     *
     * @param   string  $action 操作
     *
     * @return void 无返回值
     */
    public function __construct($action) {
        parent::__construct();

        $method = $action . 'Action';

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

    /**
     * abc分类关键字显示个数
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-07-24 16:42:47
     *
     * @return void 无返回值
     */
    public function categoryNumAction() {
        $cache      = read_static_cache($this->_abc_categorynum_key, 2);
        $cat_arr    = read_static_cache('category_c_key', 2);

        if (false === $cache) {
            $cache       = array();

            foreach($cat_arr as $cat_id => $item) {

                if (!$item['parent_id'] && $item['is_show']) {
                    $cache[$cat_id] = array_fill(0, 5, 10);
                }
            }

            write_static_cache($this->_abc_categorynum_key, $cache, 2);
        }

        $GLOBALS['Arr']['cat_arr'] = $cat_arr;
        $GLOBALS['Arr']['cache'] = $cache;
    }

    /**
     * 删除关键字
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-07-24 08:59:14
     *
     * @return void 无返回值
     */
    public function deleteAction() {
        $keyword_id = isset($_POST['keyword_id']) ? map_int(trim($_POST['keyword_id']), true) : false;

        !$keyword_id && exit('非法操作');

        $keyword_id && $this->deleteAbcRelativeCache($keyword_id);
        $this->_updateAttributeDeleted($keyword_id);
        admin_log('', '', '批量删除了' . count($keyword_id) . '个ABC索引记录');
        exit();
    }

    /**
     * 编辑/添加关键字
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-07-24 08:51:33
     *
     * @return void 无返回值
     */
    public function editAction() {
        global $Arr;

        $db         = $this->_getDb();
        $keyword_id = isset($_GET['keyword_id']) ? $_GET['keyword_id'] : '';
        $msg        = '添加';

        if (false !== strpos($keyword_id, ',')) {//批量修改
            $keyword_id = map_int($keyword_id);

            !$keyword_id && sys_msg('非法参数', 1, array(array('name' => '返回' . $msg, 'url' => 'javascript:history.back(-1)')), false);

            $id      = '';
            $keyword = '';
            $sql     = 'SELECT keyword FROM ' . ABCKEYWORD_NEW2 . " WHERE keyword_id IN({$keyword_id})";
            $db->query($sql);

            while ($row = $db->fetchArray()) {
                $keyword .= ',' . $row['keyword'];
            }

            $Arr['data'] = array('keyword' => substr($keyword, 1), 'keyword_id' => $keyword_id);
        }
        elseif ($keyword_id = intval($keyword_id)) {
            if ($data = $db->arrQuery('SELECT * FROM ' . ABCKEYWORD_NEW2 . ' WHERE keyword_id=' . $keyword_id)) {

                $related_cat_keywords = $db->getCol('SELECT a.keyword FROM ' . ABCKEYWORD_NEW2 . ' AS a JOIN ' . ABCKEYWORD_RELATIVE2 . ' AS b ON a.keyword_id=b.relative_id WHERE b.keyword_id=' . $keyword_id);
                $Arr['data'] = $data[0];
                $cat_arr = read_static_cache('category_c_key', 2);
                $Arr['data']['cat_name'] = isset($cat_arr[$Arr['data']['cat_id']]) ? $cat_arr[$Arr['data']['cat_id']]['cat_name'] : '';
                $Arr['data']['related_cat_keywords'] = $related_cat_keywords ? join(PHP_EOL, $related_cat_keywords) : '';
                $Arr['msg'] = '编辑';
            }
        }

        $Arr['msg'] = $msg;
    }//end editAction

    /**
     * 导出关键字
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2012-07-04 17:25:55
     * @last modify  2012-07-04 17:25:55 by mashanling
     *
     * @return void 无返回值
     */
    public function exportAction() {
        $db         = $this->_getDb();
        $time       = microtime(true);
        $start_id   = (isset($_POST['start_id']) ? intval($_POST['start_id']) : 0)-1;
        $end_id     = isset($_POST['end_id']) ? intval($_POST['end_id']) : 0;

        if (!$start_id || !$end_id || $start_id > $end_id || ($end_id - $start_id) > $this->_out_max) {
            sys_msg('请正确填写开始与结束id！', 1, array(array('name' => '返回', 'url' => 'javascript:history.back(-1)')), false);
        }

        require(LIB_PATH . 'phpExcel/PHPExcel.php');
        require(LIB_PATH . 'phpExcel/PHPExcel/Writer/Excel5.php');

        $obj_excel  = new PHPExcel();
        $obj_writer = new PHPExcel_Writer_Excel5($obj_excel);
        $obj_excel->setActiveSheetIndex(0);
        $obj_sheet = $obj_excel->getActiveSheet();
        $obj_sheet->getColumnDimension('A')->setWidth(30);
        $obj_sheet->getColumnDimension('B')->setWidth(40);
        $obj_sheet->getColumnDimension('C')->setWidth(40);
        $obj_sheet->getColumnDimension('D')->setWidth(40);
        $obj_sheet->getColumnDimension('E')->setWidth(40);
        $obj_sheet->getColumnDimension('F')->setWidth(20);
        $obj_sheet->setCellValue('A1', '关键字');
        $obj_sheet->setCellValue('B1', '链接地址');
        $obj_sheet->setCellValue('C1', '网站标题');
        $obj_sheet->setCellValue('D1', 'meta_关键字');
        $obj_sheet->setCellValue('E1', 'meta_描述');
        $obj_sheet->setCellValue('F1', '产品数');

        $num    = 1;
        //$sql    = 'SELECT `keyword`,web_title,meta_keyword,meta_description,description,goods_num FROM ' . ABCKEYWORD_NEW2 . " WHERE keyword_id BETWEEN {$start_id} AND {$end_id}";
        $sql    = 'SELECT `keyword`,web_title,meta_keyword,meta_description,description,goods_num FROM ' . ABCKEYWORD_NEW2 . " ORDER BY keyword_id ASC LIMIT $start_id, 5000";
        $db->query($sql);
        while ($row = $db->fetchArray()) {
            $num++;
            $obj_sheet->setCellValue('A' . $num, $row['keyword']);
            $obj_sheet->setCellValue('B' . $num, WEBSITE2 . get_search_url($row['keyword']));
            $obj_sheet->setCellValue('C' . $num, $row['web_title']);
            $obj_sheet->setCellValue('D' . $num, $row['meta_keyword']);
            $obj_sheet->setCellValue('E' . $num, $row['meta_description']);
            $obj_sheet->setCellValue('F' . $num, $row['goods_num']);
        }

        $filename = "abc_keyword-{$start_id}-{$end_id}.xls";
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');
        header('Content-Disposition:inline;filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        $obj_writer->save('php://output');
        $num--;
        write_static_cache($this->_in_out_cache_key . 'out', array('start_id' => $start_id, 'end_id' => $end_id, 'time' => local_date('Y-m-d H:i:s'), 'num' => $num), 2);
        $log = "导出ABC关键字{$start_id}至{$end_id}，共{$num}个";
        admin_log('', '', $log);
        $this->log($log, $time);
        exit();
    }//end exportAction

    /**
     * 选择对应的关键字导出
     *
     * @author       mashanling <msl-138@163.com>
     * @date         2012-07-04 17:25:55
     * @last modify  2012-07-04 17:25:55 by mashanling
     *
     * @return void 无返回值
     */
    public function exportselectAction() {
        $db         = $this->_getDb();
        $time       = microtime(true);
        $keyword_id = isset($_GET['keyword_id']) ? $_GET['keyword_id'] : '';

        if (!$keyword_id) {
            sys_msg('请选择对应的导出关键词！', 1, array(array('name' => '返回', 'url' => 'javascript:history.back(-1)')), false);
        }

        require(LIB_PATH . 'phpExcel/PHPExcel.php');
        require(LIB_PATH . 'phpExcel/PHPExcel/Writer/Excel5.php');

        $obj_excel  = new PHPExcel();
        $obj_writer = new PHPExcel_Writer_Excel5($obj_excel);
        $obj_excel->setActiveSheetIndex(0);
        $obj_sheet = $obj_excel->getActiveSheet();
        $obj_sheet->getColumnDimension('A')->setWidth(30);
        $obj_sheet->getColumnDimension('B')->setWidth(40);
        $obj_sheet->getColumnDimension('C')->setWidth(40);
        $obj_sheet->getColumnDimension('D')->setWidth(40);
        $obj_sheet->getColumnDimension('E')->setWidth(40);
        $obj_sheet->getColumnDimension('F')->setWidth(20);
        $obj_sheet->setCellValue('A1', '关键字');
        $obj_sheet->setCellValue('B1', '链接地址');
        $obj_sheet->setCellValue('C1', '网站标题');
        $obj_sheet->setCellValue('D1', 'meta_关键字');
        $obj_sheet->setCellValue('E1', 'meta_描述');
        $obj_sheet->setCellValue('F1', '产品数');

        $num    = 1;
        $sql    = 'SELECT `keyword`,web_title,meta_keyword,meta_description,description,goods_num FROM ' . ABCKEYWORD_NEW2 . " WHERE keyword_id IN (" . $keyword_id . ")";
         $db->query($sql);

        while ($row = $db->fetchArray()) {
            $num++;
            $obj_sheet->setCellValue('A' . $num, $row['keyword']);
            $obj_sheet->setCellValue('B' . $num, WEBSITE2 . get_search_url($row['keyword']));
            $obj_sheet->setCellValue('C' . $num, $row['web_title']);
            $obj_sheet->setCellValue('D' . $num, $row['meta_keyword']);
            $obj_sheet->setCellValue('E' . $num, $row['meta_description']);
            $obj_sheet->setCellValue('F' . $num, $row['goods_num']);
        }

        $filename = "abc_keyword-select-export.xls";
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');
        header('Content-Disposition:inline;filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        $obj_writer->save('php://output');
        $num--;
        write_static_cache($this->_in_out_cache_key . 'out', array('keyword_id' => $keyword_id, 'time' => local_date('Y-m-d H:i:s'), 'num' => $num), 2);
        $log = "导出ABC关键字{$keyword_id}，共{$num}个";
        admin_log('', '', $log);
        $this->log($log, $time);
        exit();
    }//end exportAction

    /**
     * 导入关键字
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-07-24 09:02:41
     *
     * @return void 无返回值
     */
    public function importAction() {
        require(ROOT_PATH . 'lib/class.upload.php');
        $time       = microtime(true);
        $db         = $this->_getDb();
        //$desc_arr   = include(ROOT_PATH . 'data-cache/new_abc_keywords_desc.php');//关键字随机描述
        //$desc_num   = count($desc_arr) - 1;
        $opt        = array(
                'size_limit'   => 1024 * 10,
                'allow_extension' => array('xls'),
                'upload_dir'      => ROOT_PATH . 'eload_admin/upexcel/'
        );

        $upload = new Upload($opt);

        empty($_FILES['file']) && sys_msg('请上传您要导入的关键字文件！', 1, array(array('name' => '返回', 'url' => 'javascript:history.back(-1)')), false);

        $result = $upload->execute($_FILES['file']);
        //file_put_contents('result.txt', '');//测试
        if (is_array($result)) {

            require(LIB_PATH . 'phpExcel/PHPExcel.php');
            $excel_reader = new PHPExcel_Reader_Excel5();
            $filename     = $result['pathname'];

            if (!$excel_reader->canRead($filename)) {
                unlink($filename);
                admin_log('', '', '导入ABC关键字，上传错误的文件类型: ' . $result['filename']);
                sys_msg('不能正确读取您上传的文件，请检查文件格式是否正确！', 1, array(array('name' => '返回', 'url' => 'javascript:history.back(-1)')), false);
            }

            $field_arr = array(
                'A' => 'keyword',
                'B' => 'web_title',
                'C' => 'meta_keyword',
                'D' => 'meta_description',
            );

            $excel         = $excel_reader->load($filename);
            $sheet         = $excel->getSheet(0);
            $all_row       = $sheet->getHighestRow();
            $keys          = array_keys($field_arr);
            $all_column    = array_pop($keys);
            $is_preserve   = isset($_POST['is_preserve']) ? 1 : 0;//无条件保留
            $success       = 0;
            $failure       = 0;
            $duplicate     = 0;
            $no_results    = 0;
            $log           = '';

            if ($all_row < 2) {
                unlink($filename);
                sys_msg('文件没有数据！', 1, array(array('name' => '返回', 'url' => 'javascript:history.back(-1)')), false);
            }

            require(ROOT_PATH . 'lib/seo/class.seo_filter_upload_keywords.php');
            $class = new SEO_Filter_Upload_Keywords();
            $relative_arr = array();

            for ($row = 2; $row <= $all_row; $row++) {
                $data = array();

                for ($column = 'A'; $column <= $all_column; $column++) {
                    $data[$field_arr[$column]] = trim($sheet->getCell($column . $row)->getValue());
                }

                if (!array_filter($data)) {
                    continue;
                }

                /*preg_match_all('/([0-9a-zA-Z\.]+)/', $data['keyword'], $matches);

                if (!empty($matches[1])) {
                    $data['keyword'] = join(' ', $matches[1]);

                    if (false !== strpos($data['keyword'], '..')) {
                        $data['keyword'] = preg_replace('/\.+/', '.', $data['keyword']);
                    }
                }*/

                $data['keyword_length'] = strlen($data['keyword']);

                if (!$is_preserve) {

                    if ($data['keyword_length'] < 6 || $data['keyword_length'] > 40) {
                        $failure++;
                        $log .= $data['keyword'] . '长度不满足 6<=长度<=40' . PHP_EOL;
                        continue;
                    }
                }

                $result = $class->upload($data['keyword']);

                //测试
                //file_put_contents('result.txt', var_export($result, true), FILE_APPEND);
                //continue;

                if (!$result) {
                    $no_results++;

                    if ($is_preserve) {
                        $result = array(
                            'cat_id'        => 0,
                            'top_cat_id'    => 0,
                            'total'         => 0,
                        );
                    }
                    else {
                        continue;
                    }
                }

                //$data['keyword'] = ucwords($data['keyword']);
				$data['keyword'] = strtolower($data['keyword']);
                $data = addslashes_deep($data);
                $data['is_preserve'] = $is_preserve;
                $data['cat_id'] = $result['cat_id'];
                $data['top_cat_id'] = $result['top_cat_id'];
                $data['goods_num'] = $result['total'];
                $data['word_count'] = substr_count($data['keyword'], ' ') + 1;
                //$data['description'] = addslashes($desc_arr[rand(0, $desc_num)]['description']);
                $data['description'] = '';		//关键词描述暂时不绑定（xiaoyulong 2013-08-21 陈朝夏提交需求）
                //$data['random'] = $this->_getRandom();
                $data['last_update_time'] = gmtime();
                $keyword_info = $db->arrQuery('SELECT keyword_id,keyword,cat_id FROM ' . ABCKEYWORD_NEW2 . " WHERE`keyword`='{$data['keyword']}'");

                if ($keyword_info) {
                    $duplicate++;
                    $keyword_id = $keyword_info[0]['keyword_id'];

                    if ($db->autoExecute(ABCKEYWORD_NEW2, $data, 'UPDATE', '`keyword_id`=' . $keyword_id)) {

                        if ($data['cat_id']) {
                            $relative_arr[$keyword_id] = $data['cat_id'];
                        }

                        $success++;
                    }
                    else {
                        $failure++;
                        $log .= $data['keyword'] . PHP_EOL;
                    }
                }
                elseif ($db->autoExecute(ABCKEYWORD_NEW2, $data)) {
                    $relative_arr[$db->insertId()] = $data['cat_id'];
                    $success++;
                }
                else {
                    $failure++;
                    $log .= $data['keyword'] . PHP_EOL;
                }
            }
        }
        else {
            admin_log('', '', '导入ABC关键字，上传错误: ' . $result);
            sys_msg($result, 1, array(array('name' => '返回', 'url' => 'javascript:history.back(-1)')));
        }

        $relative_arr && $class->setRelativeKeywords($relative_arr);

        write_static_cache($this->_in_out_cache_key . 'in', array('success' => $success, 'failure' => $failure, 'duplicate' => $duplicate, 'no_result' => $no_results, 'time' => local_date('Y-m-d H:i:s')), 2);

        unlink($filename);

        $log = "上传关键字成功，成功{$success}，失败{$failure}，重复{$duplicate}，无结果{$no_results}<br />" . ($log ? nl2br($log) : '');

        admin_log('', '', $log);
        function_exists('e_log') && e_log($log, $time);
    //exit;
        if ($failure) {
            echo '<a href="javascript:history.back(-1)">返回</a><br />' . $log;
        }
        else {
            sys_msg($log, 0, array(array('name' => '返回', 'url' => 'javascript:history.back(-1)')), false);
        }

        exit();
    }//end importAction

    /**
     * 导入
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-07-24 09:16:53
     *
     * @return void 无返回值
     */
    public function inAction() {
        $data   = read_static_cache($this->_in_out_cache_key . 'in', 2);
        $data   = false === $data ? array( 'time'=> '从未导入', 'success' => 0, 'failure' => 0, 'duplicate' => 0, 'no_result' => 0) : $data;
        $GLOBALS['Arr']['data'] = $data;
    }

    /**
     * abc关键字列表
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-07-24 08:45:44
     *
     * @return void 无返回值
     */
    public function listAction() {
        global $Arr;
        require(ROOT_PATH . 'lib/class.page.php');

        $db = $this->_getDb();

        $Arr['img_arr'] = array('no', 'yes');
        $Arr['match_arr'] = array(//匹配模式
            'eq'   => '全匹配',
            'leq'  => '左匹配',
            'req'  => '右匹配',
            'like' => '模糊匹配',
        );
        $Arr['preserve_arr'] = array(//无条件保留
            99  => '==无条件保留==',
            0   => '否',
            1   => '是',
        );

        $keyword     = isset($_GET['keyword']) && $_GET['keyword'] !== '' ? trim($_GET['keyword']) : '';
        $is_preserve = isset($_GET['is_preserve']) ? intval($_GET['is_preserve']) : 99;
        $cat_id      = isset($_GET['cat_id']) ? map_int($_GET['cat_id']) : 0;
        $match_mode  = isset($_GET['match_mode']) ? $_GET['match_mode'] : 'eq';//匹配模式
        $where       = '';

        if ($keyword) {
            switch ($match_mode) {
                case 'leq'://左
                    $where = "keyword LIKE '{$keyword}%'";
                    break;

                case 'req'://右
                    $where = "keyword LIKE '%{$keyword}'";
                    break;

                case 'like'://模糊
                    $where = "keyword LIKE '%{$keyword}%'";
                    break;

                default://完全
                    $where = "keyword='{$keyword}'";
                    break;
            }
        }

        if (99 != $is_preserve) {
            $where .= ($where ? ' AND ' : '') . 'is_preserve=' . $is_preserve;
        }

        if ($cat_id) {//分类
            $Arr['cat_id'] = $cat_id;
            $Arr['cat_name'] = isset($_GET['cat_name']) ? stripslashes($_GET['cat_name']) : '';
            $where .= ($where ? ' AND ' : '') . " cat_id IN({$cat_id})";
        }

        $total  = $db->count_info(ABCKEYWORD_NEW2 . ' AS a ', '`keyword_id`', $where);
        $filter = page_and_size(array('record_count' =>  $total));

        $filter['sort_by'] 		= empty($_REQUEST['sort_by']) ? 'keyword_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] 	= empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $order_str 				= " ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order'];

        if ($total) {
            $sql    = 'SELECT keyword_id,goods_num,`keyword`,is_preserve,cat_id FROM ' . ABCKEYWORD_NEW2 . ($where ? ' WHERE ' . $where : '') . $order_str;
            $list   = $db->selectLimit($sql, $filter['page_size'], $filter['start']);
            $page   = new page(array('total' => $total , 'perpage' => $filter['page_size']));

            $Arr['abc_list'] = $list;
            $Arr['pagestr'] = $page->show();
        }

        $Arr['keyword'] = $keyword;
        $Arr['cat_arr'] = read_static_cache('category_c_key', 2);
        $Arr['page_size'] = $filter['page_size'];
        $Arr['is_preserve'] = $is_preserve;
        $Arr['match_mode'] = $match_mode;

        $sort_flag  = sort_flag($filter);
	    $Arr[$sort_flag['tag']] = $sort_flag['img'];
		$filter[$sort_flag['tag_sort_order']] = $sort_flag['sort_order'];
	    $Arr['filter'] =   $filter;
    }//end abcListAction

    /**
     * 导出
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-07-24 09:16:53
     *
     * @return void 无返回值
     */
    public function outAction() {
        $data   = read_static_cache($this->_in_out_cache_key . 'out', 2);
        $data   = false === $data ? array( 'time'=> '从未导出', 'start_id' => 0, 'end_id' => 0, 'num' => 0) : $data;
        $data['value_start_id'] = $data['end_id'] + 1;
        $data['value_end_id']   = $data['end_id'] + $this->_out_max;
        $GLOBALS['Arr']['data'] = $data;
    }

    /**
     * 保存关键字
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-07-24 08:55:09
     *
     * @return void 无返回值
     */
    public function saveAction() {
        $db             = $this->_getDb();
        $keyword_id     = isset($_POST['keyword_id']) ? $_POST['keyword_id'] : 0;
        $keyword        = isset($_POST['keyword']) ? $_POST['keyword'] : '';

        $data['web_title']   = isset($_POST['web_title']) ? trim($_POST['web_title']) : '';
        $data['meta_keyword']   = isset($_POST['meta_keyword']) ? trim($_POST['meta_keyword']) : '';
        $data['meta_description'] = isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '';
        $data['description'] = isset($_POST['description']) ? trim($_POST['description']) : '';
        $related_cat_keywords = isset($_POST['related_cat_keywords']) ? trim(stripslashes($_POST['related_cat_keywords'])) : '';

        if ($related_cat_keywords) {
            $related_cat_keywords = explode(PHP_EOL, $related_cat_keywords);

            foreach ($related_cat_keywords as $k => $item) {
                //$item = trim(ucwords($item)); //首字母大写
				$item = trim(strtolower($item));
                if (!$item) {
                    unset($related_cat_keywords[$k]);
                    continue;
                }
                elseif (!$related_cat_keywords[$k] = $db->getOne('SELECT keyword_id FROM . ' . ABCKEYWORD_NEW2 . " WHERE keyword='" . addslashes($item) . "'")) {
                    sys_msg('相关词Related Tags: ' . $item . ' 不存在', 1, array(array('name' => '返回', 'url' => 'javascript:history.back(-1)')), false);
                }
            }
        }

        if (false === strpos($keyword_id, ',')) {//编辑关键字

            $keyword === '' && sys_msg('关键字不能为空！', 1, array(array('name' => '返回' . $msg, 'url' => 'javascript:history.back(-1)')), false);
            $db->count_info(ABCKEYWORD_NEW2, 'keyword_id', "`keyword`='{$keyword}' AND keyword_id!='{$keyword_id}'") && sys_msg('存在相同关键字！', 1, array(array('name' => '返回' . $msg, 'url' => 'javascript:history.back(-1)')), false);
            $data['keyword'] = $keyword;
            $data['word_count'] = substr_count($keyword, ' ') + 1;
        }
        else {
            $keyword_id = map_int($keyword_id);
            !$keyword_id && sys_msg('非法参数', 1, array(array('name' => '返回' . $msg, 'url' => 'javascript:history.back(-1)')), false);

            $data = array_filter($data);//过滤空字段，不作修改
        }

        $data['cat_id'] = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : -1;

        if (-1 == $data['cat_id']) {//
            unset($data['cat_id']);
        }
        elseif (10000 == $data['cat_id']) {
            $data['cat_id'] = 0;
        }

        $data['is_preserve'] = isset($_POST['is_preserve']) ? 1 : 0;
        //$data['random'] = $this->_getRandom();
        $data['last_update_time'] = gmtime();

        $msg = $keyword_id ? '修改' : '添加';
        $db->autoExecute(ABCKEYWORD_NEW2, $data, $keyword_id ? 'UPDATE' : 'INSERT', $keyword_id ? "keyword_id IN({$keyword_id})" : '');

        $keyword_id && $this->_updateAttributeDeleted($keyword_id);

        if ($related_cat_keywords) {
            $related_cat_keywords = array_unique($related_cat_keywords);

            if ($keyword_id) {
                $db->delete(ABCKEYWORD_RELATIVE2, "keyword_id IN({$keyword_id})");

                foreach ($related_cat_keywords as $v) {
                    $db->query('INSERT INTO ' . ABCKEYWORD_RELATIVE2 . "(keyword_id,relative_id) SELECT keyword_id,{$v} FROM " . ABCKEYWORD_NEW2 . " WHERE keyword_id IN({$keyword_id})");
                }

                $set_relative_keyword_id = $keyword_id;
            }
            elseif ($insert_id = $db->insertId()) {

                foreach ($related_cat_keywords as $v) {
                    $db->query('INSERT INTO ' . ABCKEYWORD_RELATIVE2 . "(keyword_id,relative_id) VALUES({$insert_id}, {$v}})");
                }

                $set_relative_keyword_id = $insert_id;
            }

            if (!empty($set_relative_keyword_id)) {
                require_once(ROOT_PATH . 'lib/seo/class.seo.php');
                $class = new SEO();
                $class->setAbcRelativeCache($set_relative_keyword_id);
            }
        }

        admin_log('', '', $msg . "ABC关键字{$keyword}({$keyword_id})");
        sys_msg($msg . '成功', 0, array(array('name' => '返回ABC索引列表', 'url' => 'javascript:history.go(-2)')));
    }//end saveAction



    /**
     * 保存abc分类关键字显示个数
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-07-25 10:08:11
     *
     * @return void 无返回值
     */
    public function saveCategoryNumAction() {

        if (empty($_POST['num']) || !is_array($_POST['num'])) {
            exit('非法数据');
        }

        $cache      = array();

        foreach($_POST['num'] as $cat_id => $num) {
            $cache[$cat_id] = map_int($num, true, true);
        }

        write_static_cache($this->_abc_categorynum_key, $cache, 2);

        exit();
    }
}