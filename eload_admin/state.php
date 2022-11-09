<?
//更改状态的功能
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/time.fun.php');
require_once('libs/fun.admin.php');

/* act操作项的初始化 */
$_ACT     = 'menu';
$_ID      = '';
$state    = 0;
$field    = 'enable';
$id_field = "action_id";
$tt       = AACTION;



if (!empty($_GET['act'])) $_ACT         = trim($_GET['act']);   //model
if (!empty($_GET['did'])) $_ID          = trim($_GET['did']);   //id
if (!empty($_GET['state'])) $state      = trim($_GET['state']);  //state
if (!empty($_GET['field'])) $field      = trim($_GET['field']);  //filed
if (!empty($_GET['id_field']))$id_field = trim($_GET['id_field']);  //id字段

if (empty($_ID)) die('error');

switch  ($_ACT){
       case 'menu':
		   $tt = AACTION;   //更改菜单状态
		   break;
	   case 'cat':
		   $tt = CATALOG;     //商品分类是否显示
		   break;
	   case 'goods_type':
		   $tt = GTYPE;     //更改商品类型状态
		   break;
	   case 'goods':
		   $tt = GOODS;     //更改商品状态

           if($field=='is_on_sale' && $state == '0')//上架的时候去图片库检查图片是否完整
           {
                //对清仓产品上架操作做如下限制：清仓类的产品，
                //清仓等级的产品（产品等级为活跃有货近期无销售，不活跃有货近期无销售）
                //不允许网站后台手工上架
                //等级为11,12
                //by mashanling on 2014-03-08 13:59:14
                disabled_on_sale($_ID, true);

                $url = 'http://www.faout.com/code/api.php';
                $data = "act=check_queue&goods_id_str=$_ID&website=A";
                //$data = "act=check_queue&goods_id_str=145943&website=E";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
                $contents = curl_exec($ch);
                curl_close($ch);

                if($contents>0)
                {
                    echo '2';
                    exit();
                }
				$db->query("update ".GOODS_STATE." set sale_time ='".gmtime()."' where goods_id=" . $_ID);//更新上架时间
           }
		   break;
	   case 'users':
		   $tt = USERS;     //更改会员是否通过验证。或锁定
		   break;
	   case 'article':
		   $tt = ARTICLE;     //文章是否显示
		   break;
	   case 'shipping':    //配送是否可用
			$tt = SHIPPING;
			break;
	   case 'payment':    //配送是否可用
			$tt = PAYMENT;
			break;
	   case 'admin':    //管理员
			$tt = SADMIN;
			break;

	   case 'newsletter'://邮件期刊 by mashanling on 2012-08-03 13:28:27
			$tt = NEWSLETTER;
			break;

}
($state == '1')?$state = 0:$state = 1;
if('eload_goods' == $tt) {
	$db -> update($tt, "promote_start_date = 0,promote_end_date = 0,is_promote=0 ", " $id_field = $_ID "); //产品下架取消促销 2013/10/16 fangxin
}
if($db -> update($tt, "$field = '$state' ", " $id_field = $_ID "))	echo $state;

if(($_ACT == 'cat')){
	include(ROOT_PATH.'config/language_cfg.php');
	if($state == '1'){
		$keystr = implode(',',array_keys($langArr));
	}else{
		$keystr = '';
	}
	$children = get_children($_ID);
	$db -> update(CATALOG.' as g ', "$field = '$state',clang = '$keystr' ", " $children ");
	//if($field == 'is_show')$db -> update(GOODS.' as g ', "is_on_sale = '$state',clang = '$keystr' ", "  $children ");
	//if($field == 'is_login')$db -> update(GOODS.' as g ', "is_login = '$state'", "  $children ");
	admin_log('', _EDITSTRING_, '分类ID为'.$_ID.'字段：'.$field.'改成了'.$state);
}



switch  ($_ACT){
       case 'menu':
		   creat_menu();
		   break;
       case 'goods':
			$goods_sn = $db->getOne("select goods_sn from ".GOODS." WHERE goods_id = '$_ID'");

			admin_log('', _EDITSTRING_,'商品:'.$goods_sn.' '.$field.'='.$state);
		   break;
	   case 'cat':
		   creat_category();
		   break;
	   case 'shipping':
	   		/*更新shipping_method.php文件*/
			create_shipping_cache();
    		/* 记录管理员操作 */
			$var = admin_log('', _EDITSTRING_, '配送方式ID为'.$_ID.'的状态');
	   		break;
	   case 'payment':
			$sql = 'select * from '.PAYMENT.' where pay_id='.$_ID;

			$payments = $db->arrQuery($sql);
			$payments = $payments[0];

			$payment = ','.$payments['pay_code'];

			//删除数据库中的对应payment
			//die("payment=replace(payment,'$payment','')");
			$db->update(REGION,"payment=replace(payment,'$payment','')");

			create_payment_cache();/*更新shipping_method.php文件*/

			creat_area();//生成area_key.php 文件

			$var = admin_log('', _EDITSTRING_, '付款方式ID为'.$_ID.'的状态');	/* 记录管理员操作 */
			break;

	   case 'admin':    //管理员
			creat_admin();
			$var = admin_log('', _EDITSTRING_, '管理员ID为'.$_ID.'的电脑授权状态');
			break;

        case 'newsletter'://邮件期刊 by mashanling on 2012-08-03 14:05:19
	        admin_log('', _EDITSTRING_, "邮件期刊{$_ID}的{$field}改成了{$state}");
			require(ROOT_PATH . 'lib/class.newsletter.php');
			$newsletter = new Newsletter();
			$newsletter->cache();
			break;
}


?>