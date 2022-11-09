<?
/**
 * fun.abcindex.php         abc索引关键字
 *
 * @author                  wuwenlong mashanling(msl-138@163.com)
 * @date
 * @last modify             2012-07-23 11:07:17 by mashanling
 */
require_once(ROOT_PATH . 'fun/fun.global.php');
require_once(ROOT_PATH . 'fun/fun.public.php');
require_once(ROOT_PATH . 'lib/lib.f.goods.php');
require_once(ROOT_PATH . 'lib/class.page.php');
require_once(ROOT_PATH . 'lib/seo/class.seo_front_abc_list.php');
$is_new = strpos($_SERVER['REQUEST_URI'], '/cheap-') !== false;//是否新链接，新链接样式/cheap-
$k      = !isset($_GET['k']) ? 'A' : htmlspecialchars($_GET['k']);
//0,1,2,3,4,...跳到0-9
is_numeric($k) && redirect_url('/cheap-product-0-9.html', 301);
$page = empty($_GET['page']) ? 1 : intval($_GET['page']);
!in_array($k, $abcArr) &&  redirect_url();
$class = new SEO_Front_Abc_List();
$k = '';
$class->listAction($k);
$page = empty($_GET['page']) ? 1 : intval($_GET['page']);
$Arr['seo_title'] = $_LANG_SEO['top_search']['title'];
$Arr['seo_keywords'] = $_LANG_SEO['top_search']['keywords'];
$Arr['seo_description'] = $_LANG_SEO['top_search']['description'];
$Arr['k'] = $k;