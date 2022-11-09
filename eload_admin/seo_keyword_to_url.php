<?php
/**
 * seo_keyword_to_url.php       关键词跳转到指定url
 *
 * @author                      mashanling(msl-138@163.com)
 * @date                        2012-12-07 10:53:59
 * @last modify                 2012-12-07 10:54:01 by mashanling
 */

define('INI_WEB', true);
require_once('../lib/global.php');//引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/class.page.php');
require_once('../lib/time.fun.php');

$class = new SEO();

//admin_priv($filename);    //检查权限

$_ACT          = isset($_GET['act']) ? $_GET['act'] : 'list';    //操作

switch ($_ACT) {
    case 'save':    //保存
        $class->save();
        break;

    case 'delete'://删除
        $class->delete();
        break;

    default://列表
        $class->_list();
        break;
}

class SEO {
    private $filename;//文件名

    /**
     * 获取数据
     *
     * @last modify 2012-12-07 14:05:28 by mashanling
     *
     * @access private
     *
     * @return array 缓存数据
     */
    private function getData() {
        $data = read_static_cache($this->filename, 2);

        return $data ? $data : array();
    }

    /**
     * 写数据
     *
     * @last modify 2012-12-07 14:58:16 by mashanling
     *
     * @access private
     *
     * @param array $data
     *
     * @return mixed write_static_cache()结果
     */
    private function setData($data) {
        return write_static_cache($this->filename, $data, 2);
    }

    /**
     * 架构函数
     *
     * @last modify 2012-12-07 10:57:10 by mashanling
     *
     * @access public
     *
     * @return void 无返回值
     */
    public function __construct() {
        $this->filename = basename(__FILE__ , '.php');
        admin_priv($this->filename);//检查权限
    }

    /**
     * 列表
     *
     * @last modify 2012-12-07 14:00:31 by mashanling
     *
     * @access public
     *
     * @return void 无返回值
     */
    public function _list() {
        global $Arr, $_ACT;

        $_ACT = $this->filename;
        $Arr['data'] = $this->getData();
        temp_disp();
    }

    /**
     * 删除
     *
     * @last modify 2012-12-07 15:37:53 by mashanling
     *
     * @access public
     *
     * @return void 无返回值
     */
    public function delete() {
        $ids = empty($_POST['ids']) ? '' : $_POST['ids'];

        !$ids && exit('非法数据');

        $ids  = map_int($ids, true);

        $data = $this->getData();

        foreach ($data as $k => $v) {

            if (in_array($v['id'], $ids)) {
                unset($data[$k]);
            }
        }

        $this->setData($data);
    }

    /**
     * 保存
     *
     * @last modify 2012-12-07 14:00:52 by mashanling
     *
     * @access public
     *
     * @return void 无返回值
     */
    public function save() {
        $id      = empty($_POST['id']) ? '' : intval($_POST['id']);//id
        $keyword = empty($_POST['keyword']) ? '' : strtolower(trim($_POST['keyword']));//关键字
        $url     = empty($_POST['url']) ? '' : trim($_POST['url']);//链接
        $data    = $this->getData();

        if (!$keyword || !$url) {
            exit('关键字或url为空');
        }

        if (is_numeric($url)) {//分类id
            $cat_arr = read_static_cache('category_c_key', 2);

            !isset($cat_arr[$url]) && exit('分类不存在');

            $url = WEBSITE2 . $cat_arr[$url]['link_url'];
        }
        elseif (0 !== strpos($url, $v = 'http://')) {//绝对路径
            $url = $v . $url;
        }

        if ($id) {//更新
            foreach ($data as $k => $v) {

                if ($v['id'] == $id) {

                    if ($k == $keyword) {//关键字不变
                        $data[$k]['url'] = $url;
                    }
                    else {//关键字发生变化
                        unset($data[$k]);
                        $data[$keyword] = array(
                            'id'  => gmtime(),
                            'url' => $url,
                        );
                    }

                    break;
                }
            }
        }
        else {
            $data[$keyword] = array(
                'id'  => gmtime(),
                'url' => $url,
            );
        }

        ksort($data);

        $this->setData($data);
    }//end save
}