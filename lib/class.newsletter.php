<?php
/**
 * class.newsletter.php     邮件期刊类
 *
 * @author                  mashanling(msl-138@163.com)
 * @date                    2012-07-11 14:14:45
 * @last modify             2012-08-03 10:04:10 by mashanling
 */

class Newsletter {
    private $cache_file;//缓存文件
    private $cache_key = 'newsletter';//缓存key值
    private $html_path;//静态文件路径 by mashanling on 2012-07-31 08:41:16
    private $preview_path;//预览路径
    private $website;//网站域名

    /**
     * 构造函数
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-07-11 14:20:14
     * @last modify  2012-08-03 10:06:23 by mashanling
     *
     * @return void 无返回值
     */
    function __construct() {
        $this->cache_file = ROOT_PATH . 'data-cache/' . $this->cache_key . '.php';
        $this->html_path  = ROOT_PATH . 'html/';
        $this->preview_path = $this->html_path . 'preview/';
        !is_dir($this->html_path) && mkdir($this->html_path, 0755);
//        !is_file($this->cache_file) && write_static_cache($this->cache_key, array());

        if (defined('WEBSITE')) {//E
            $this->website = WEBSITE;
        }
        elseif (defined('YUMI')) {//S
            $this->website = YUMI . '/';
        }
        else {//其它
            $this->website = 'http://' . $_SERVER['HTTP_HOST'] . '/';
        }
    }

    /**
     * 列表
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-07-11 14:22:28
     * @last modify  2012-07-11 14:22:28 by mashanling
     *
     * @return array 期刊数组
     */
    function _list() {
        $data = read_static_cache($this->cache_key);

        return $data ? $data : array();
    }

    /**
     * 期刊信息
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-07-11 16:17:34
     * @last modify  2012-07-31 10:03:32 by mashanling
     *
     * @return mixed 如果期刊存在，返回期刊信息数组，否则返回false
     */
    function info() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($id) {
            $data = $GLOBALS['db']->arrQuery('SELECT * FROM ' . NEWSLETTER . ' WHERE auto_id=' . $id);

            if (!empty($data)) {
                $data[0]['date'] = local_date('Y-m-d', $data[0]['date']);
                return $data[0];
            }
        }

        return false;
    }

    /**
     * 保存
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-07-11 15:30:00
     * @last modify  2012-08-03 10:17:24 by mashanling
     *
     * @return bool 保存成功，返回true。否则返回false
     */
    function save() {
        $data = $_POST;
        $data['date'] = local_strtotime($data['date']);
        $auto_id = intval($data['auto_id']);
        $msg     = $auto_id ? '修改' : '添加';

        //上传图片处理 by mashanling on 2012-08-03 10:23:31
        (!$auto_id && (empty($_FILES['img']) || $_FILES['img']['error'] == 4)) && sys_msg('请上传图片', 1, array(), false);

        if ($_FILES['img']['error'] == 0) {//上传图片
            require_once(ROOT_PATH . 'lib/cls_image.php');
    	    $image = new cls_image();
            $img   = $image->upload_image($_FILES['img']);//上传

            $img === false && sys_msg($image->error_msg(), 1, array(), false);

            $data['img'] = $this->website . $img;
        }

        unset($data['auto_id']);

        $result = $auto_id ? $GLOBALS['db']->autoExecute(NEWSLETTER, $data, 'UPDATE', 'auto_id=' . $auto_id) : $GLOBALS['db']->autoExecute(NEWSLETTER, $data);

        !$result && sys_msg($msg . '失败', 1, array(), false);

        $result && $this->cache();
        $this->buildHtml($_POST['date'], $data['title'], $data['body']);

        $links[0]['name'] = '返回上一页';
    	$links[0]['url'] = 'javascript: history.back()';

        $links[1]['name'] = '返回邮件期刊列表';
    	$links[1]['url'] = 'newsletter.php';
    	unset($_POST['body']);//干掉body，节省空间 by mashanling on 2012-07-31 14:18:39
    	admin_log('', '', '添加/编辑邮件期刊' . stripslashes(var_export($_POST, true)));
        sys_msg($msg . '成功', 0, $links);
    }

    /**
     * 生成预览
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-07-31 11:24:01
     * @last modify  2012-07-31 14:00:31 by mashanling
     *
     * @return void 无返回值
     */
    function preview() {
        !is_dir($this->preview_path) && mkdir($this->preview_path, 0755);

        $date    = $_POST['date'];
        $title   = $_POST['title'];
        $content = $_POST['body'];
        $url     = $this->buildHtml($date, $title, $content, true);

        exit('预览地址: <a style="text-decoration: underline;" href="' . $url . '" target="_blank">' . $url . '</a>');
    }

    /**
     * 生成期刊静态页
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-07-31 14:06:04
     * @last modify  2012-08-03 10:15:44 by mashanling
     *
     * @param string $date       日期
     * @param string $title      标题
     * @param string $content    body html代码
     * @param bool   $is_preview 是否预览,默认false,不是
     *
     * @return string 期刊地址
     */
    private function buildHtml($date, $title, $content, $is_preview = false) {
        $tpl     = file_get_contents(str_replace('eload_admin/', '', SMARTY_TMPL) . 'tpl_newsletter.htm');
        $html    = str_replace(array('{$title}', '{$content}'), array(stripslashes($title), stripslashes($content)), $tpl);
        $filename= 'newsletter-' . $date . '.htm' . ($is_preview ? '' : 'l');
        file_put_contents(($is_preview ? $this->preview_path : $this->html_path) . $filename, $html);

        //清缓存 by mashanling on 2012-08-02 14:28:41
        if (!$is_preview && !strpos($this->website, 'everbuying.net')) {
            ob_start();

            if (strpos($this->website, 'sammydress.com')) {//S by mashanling on 2012-08-03 10:16:08
                file_get_contents('http://cloud10.faout.com/purge/html/' . $filename);
                post_purge_cache(CDN_API_PATH,"purge_url=" . CDN_CLEAR_URL_PATH .'/html/' . $filename);
            }

            ob_end_clean();
        }

        return $this->website . 'html/' . ($is_preview ? 'preview/' : '') . $filename;
    }

    /**
     * 删除期刊
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-07-11 15:50:08
     * @last modify  2012-08-03 14:07:31 by mashanling
     *
     * @return void 无返回值
     */
    function delete() {
        $ids = isset($_POST['ids']) ? $_POST['ids'] : '';
        $ids = map_int($ids, true);

        if ($ids) {
            admin_log('', '', _DELSTRING_ . '邮件期刊' . $ids);
            $GLOBALS['db']->delete(NEWSLETTER, 'auto_id IN(' . join(',', $ids) . ')');

            $cache = $this->_list();
            foreach ($cache as $id => $item) {//删除静态文件及上传图片 by mashanling on 2012-08-03 12:10:18

                if (in_array($id, $ids)) {
                    $filename = str_replace($this->website, ROOT_PATH, $item['url']);//静态文件
                    file_exists($filename) && unlink($filename);

                    $filename = str_replace($this->website, ROOT_PATH, $item['img']);//上传图片
                    file_exists($filename) && unlink($filename);

                    unset($cache[$id]);
                }
            }

            write_static_cache($this->cache_key, $cache);
        }
    }

    /**
     * 设置缓存
     *
     * @author       mashanling(msl-138@163.com)
     * @date         2012-07-11 15:30:39
     * @last modify  2012-07-31 14:14:56 by mashanling
     *
     * @return void 无返回值
     */
    function cache() {
        global $db;

        $data = array();
        $db->query('SELECT * FROM ' . NEWSLETTER . ' ORDER BY `date` DESC');

        while ($row = $db->fetchArray()) {
            unset($row['body']);
            $row['date'] = local_date('Y-m-d', $row['date']);
            $row['url'] = $this->website . 'html/newsletter-' . $row['date'] . '.html';
            $data[$row['auto_id']] = $row;
        }

        write_static_cache($this->cache_key, $data);
    }
}