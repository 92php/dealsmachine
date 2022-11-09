<?php
if (!defined('INI_WEB')){die('访问拒绝');}
//公共函数库
//POWER BY WU WENLONG  2009-4-29

#============================================================================
# 变数过滤，使 $_GET 、 $_POST 、$q->record 等变量更安全   // 如果PHP.ini 配置文件启用魔术变量的话，就
#----------------------------------------------------------------------------
function varFilter(&$fStr) {
	if (is_array($fStr)) {
		foreach ( $fStr AS $_arrykey => $_arryval ) {
			if ( is_string($_arryval) ) {
				$fStr["$_arrykey"] = trim($fStr["$_arrykey"]);						// 去除左右两端空格
				$fStr["$_arrykey"] = htmlspecialchars($fStr["$_arrykey"]);			// 将特殊字元转成 HTML 格式
				$fStr["$_arrykey"] = str_replace("javascript", "javascript ", $fStr["$_arrykey"]);	// 禁止 javascript
			}else if (is_array($_arryval)){
				$fStr["$_arrykey"] = varFilter($_arryval);
			}
		}
	} else {
		$fStr = trim($fStr);									// 去除左右两端空格
		$fStr = htmlspecialchars($fStr);						// 将特殊字元转成 HTML 格式
		$fStr = str_replace("javascript", "javascript ", $fStr);// 禁止 javascript
	}
	return $fStr;
}

#============================================================================
# 恢复被过滤的变数
#----------------------------------------------------------------------------
function varResume (& $fStr) {
	if (is_array($fStr)) {
		foreach ( $fStr AS $_arrykey => $_arryval ) {
			if ( is_string($_arryval) ) {
				$fStr["$_arrykey"] = str_replace("&quot;", "\"", $fStr);
				$fStr["$_arrykey"] = str_replace("&lt;", "<", $fStr);
				$fStr["$_arrykey"] = str_replace("&gt;", ">", $fStr);
				$fStr["$_arrykey"] = str_replace("&raquo;", ">", $fStr);
				$fStr["$_arrykey"] = str_replace("&amp;", "&", $fStr);
				$fStr["$_arrykey"] = str_replace("javascript ", "javascript", $fStr);
			}else if (is_array($_arryval)){
				$fStr["$_arrykey"] = varResume($_arryval);
			}
		}
	} else {
		$fStr = str_replace("&quot;", "\"", $fStr);
		$fStr = str_replace("&lt;", "<", $fStr);
		$fStr = str_replace("&gt;", ">", $fStr);
		$fStr = str_replace("&raquo;", ">", $fStr);
		$fStr = str_replace("&amp;", "&", $fStr);
		$fStr = str_replace("javascript ", "javascript", $fStr);
	}
	return $fStr;
}

function HtmlEncode($fString)
{
 if($fString!="")
 {
    $fString = str_replace( '>', '&gt;',$fString);
    $fString = str_replace( '<', '&lt;',$fString);
    //$fString = str_replace( chr(32), '&nbsp;',$fString);
    $fString = str_replace( chr(13), ' ',$fString);
    $fString = str_replace( chr(10) & chr(10), '<BR>',$fString);
    $fString = str_replace( chr(10), '<BR>',$fString);
 }
    return $fString;
}

function HtmlDecode($fString)
{
 if($fString!="")
 {
    $fString = str_replace( '&gt;','>' ,$fString);
    $fString = str_replace( '&lt;','<' ,$fString);
    $fString = str_replace( '&nbsp;',chr(32), $fString);
    //$fString = str_replace( ' ',chr(13), $fString);
    $fString = str_replace( '<BR>',chr(10) & chr(10), $fString);
    $fString = str_replace( '<BR>',chr(10), $fString);
 }
    return $fString;
}


//公共输出模板函数
function temp_disp(){
	global $Arr,$Tpl,$_ACT,$my_cache_id,$_BEGINTIME;
	$_ENDTIME = microtime(TRUE);
	$Arr['exec_time'] = round(($_ENDTIME - $_BEGINTIME),4);
		if(count($Arr)>0){
			foreach( $Arr as $key => $value ){
				$Tpl->assign( $key, $value );
			}
		}
	//echo SMARTY_TMPL."$_ACT.htm";
	if(file_exists(SMARTY_TMPL."$_ACT.htm")){$Tpl->display("$_ACT.htm",$my_cache_id);}else{echo "模板文件 $_ACT.htm 不存在！";}
}

/*================================================
------- 树数组转换成普通数组
-------$list Array
-------$level 深度
-------$ppx  需要加工的字段
================================================
*/
function treetoary($list,$level=0,$ppx='', $children = '_child'){
	  global $tree;
	  $xu = 0;
	  foreach($list as $key=>$val)
	  {
		 if ($ppx!=''){
			$tmp_str=' ';

			if ($level == 0) $tmp_str = " ";
			if ($level >0 ){
				for ($xu = 1; $xu < $level;$xu++){
				       $tmp_str.= "&nbsp;&nbsp;│";
				}
				$tmp_str.= "&nbsp;&nbsp;├ ";
			}

			$val[$ppx]=$tmp_str.$val[$ppx];
		 }
		if(!array_key_exists($children,$val)) {
		   array_push($tree,$val);
		}else{
			$tmp_ary = $val[$children];
			unset($val[$children]);
		   array_push($tree,$val);
		 if ($ppx!=''){
		   treetoary($tmp_ary,$level+1,$ppx, $children);
		 }else{
		   treetoary($tmp_ary,$level+1, '', $children);
		 }
		}
	  }
	  return;
}

/*================================================
------- 普通数组转换成树数组
-------$list Array
-------$pk   子ID
-------$pid  父类ID
================================================
*/
function toTree($list=null, $pk='cat_id',$pid = 'parent_id',$child = '_child')
    {
        // 创建Tree
        $tree = array();
        if(is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $_key = is_object($data)?$data->$pk:$data[$pk];
                $list[$key]['leaf'] = true;
                $refer[$_key] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = is_object($data)?$data->$pid:$data[$pid];
                if ($parentId) {
                    if (isset($refer[$parentId])) {
                        $refer[$parentId]['leaf'] = false;
                        $parent =& $refer[$parentId];
                        $parent[$child][] =& $list[$key];
                    }
                } else {
                    $tree[] =& $list[$key];
                }
            }
        }
        return $tree;
}

/**
 * 字符串截取，支持中文和其他编码
 * @static
 * @access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * @return string
 */
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true)
{
	if($suffix)
		$suffixStr = "…";
	else
		$suffixStr = "";
    if(function_exists("mb_substr"))
        return mb_substr($str, $start, $length, $charset).$suffixStr;
    elseif(function_exists('iconv_substr')) {
        return iconv_substr($str,$start,$length,$charset).$suffixStr;
    }
    $re['utf-8']   =  "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312']  =  "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk']     =  "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5']    =  "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);
    $slice = join("",array_slice($match[0], $start, $length));
    return $slice.$suffixStr;
}


/**
 * 截取UTF-8编码下字符串的函数
 * @param   string      $str        被截取的字符串
 * @param   int         $length     截取的长度
 * @param   bool        $append     是否附加省略号
 * @return  string
 */
function sub_str($str, $length = 0, $append = true)
{
    $str = trim($str);
    $strlength = strlen($str);
    if ($length == 0 || $length >= $strlength)
    {
        return $str;
    }
    elseif ($length < 0)
    {
        $length = $strlength + $length;
        if ($length < 0)
        {
            $length = $strlength;
        }
    }
    if (function_exists('mb_substr'))
    {
        $newstr = mb_substr($str, 0, $length, "utf-8");
    }
    elseif (function_exists('iconv_substr'))
    {
        $newstr = iconv_substr($str, 0, $length,"utf-8");
    }
    else
    {
        $newstr = substr($str, 0, $length);
    }
    if ($append && $str != $newstr)
    {
        $newstr .= '...';
    }
    return $newstr;
}


/**
 * 产生随机字串，可用来自动生成密码 默认长度6位 字母和数字混合
 * @param string $len 长度
 * @param string $type 字串类型
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符
 * @return string
 */
function rand_string($len=6,$type='',$addChars='') {
    $str ='';
    switch($type) {
        case 0:
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.$addChars;
            break;
        case 1:
            $chars= str_repeat('0123456789',3);
            break;
        case 2:
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ'.$addChars;
            break;
        case 3:
            $chars='abcdefghijklmnopqrstuvwxyz'.$addChars;
            break;
        default :
            // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
            $chars='ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'.$addChars;
            break;
    }
    if($len>10 ) {//位数过长重复字符串一定次数
        $chars= $type==1? str_repeat($chars,$len) : str_repeat($chars,5);
    }
    if($type!=4) {
        $chars   =   str_shuffle($chars);
        $str     =   substr($chars,0,$len);
    }else{
        // 中文随机字
        for($i=0;$i<$len;$i++){
          $str.= msubstr($chars, floor(mt_rand(0,mb_strlen($chars,'utf-8')-1)),1);
        }
    }
    return $str;
}

/**
 * 获取登录验证码 默认为4位数字
 * @param string $fmode 文件名
 * @return string
 */
function build_verify ($length=4,$mode=1) {
    return rand_string($length,$mode);
}

/* 获得用户的真实IP地址
 * @access  public
 * @return  string
 */
function real_ip()
{
    static $realip = NULL;

    if ($realip !== NULL)
    {
        return $realip;
    }
    if (isset($_SERVER))
    {
        if(isset($_SERVER['HTTP_TRUE_CLIENT_IP']))			//自定义的保存客户IP变量
        {
        	$realip = $_SERVER['HTTP_TRUE_CLIENT_IP'];
        }
    	elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
            foreach ($arr AS $ip)
            {
                $ip = trim($ip);
                if ($ip != 'unknown')
                {
                    $realip = $ip;
                    break;
                }
            }
        }
        elseif (isset($_SERVER['HTTP_CLIENT_IP']))
        {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        }
        else
        {
            if (isset($_SERVER['REMOTE_ADDR']))
            {
                $realip = $_SERVER['REMOTE_ADDR'];
            }
            else
            {
                $realip = '0.0.0.0';
            }
        }
    }
    else
    {
        if(getenv('HTTP_TRUE_CLIENT_IP'))		//自定义的保存客户IP变量
        {
        	$realip = getenv('HTTP_TRUE_CLIENT_IP');
        }
    	elseif (getenv('HTTP_X_FORWARDED_FOR'))
        {
            $realip = getenv('HTTP_X_FORWARDED_FOR');
        }
        elseif (getenv('HTTP_CLIENT_IP'))
        {
            $realip = getenv('HTTP_CLIENT_IP');
        }
        else
        {
            $realip = getenv('REMOTE_ADDR');
        }
    }
    preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
    $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
    return $realip;
}

/**
 * 判断管理员对某一个操作是否有权限。
 * 根据当前对应的action_code，然后再和用户session里面的action_list做匹配，以此来决定是否可以继续执行。
 * @param     string    $priv_str    操作对应的priv_str
 * @param     string    $msg_type       返回的类型
 * @return true/false
 */
function admin_priv($priv_str)
{   global $_ACT,$Arr;
    if (strpos(',' . $_SESSION['WebUserInfo']['group_power'] . ',', ',' . $priv_str . ',') === false)
    {
        $links = array('0' => array('name' => "返回上一页",'url' => 'javascript:history.back(-1)'));
		$msg   = "很抱歉，您没有权限，请联系网站管理员！";
		$Arr["msg"] = $msg;
		$Arr["links"] = $links;
		$_ACT = 'msg';
		temp_disp();
        exit();
    }
    else
    {
        return true;
    }
}

/**
 * 判断管理员对某一个操作是否有权限。
 *
 * 根据当前对应的action_code，然后再和用户session里面的action_list做匹配，以此来决定是否可以继续执行。
 * @param     string    $_str    操作对应的priv_str
 * @param     string    $msg_type       返回的类型
 * @return true/false
 */
function have_priv($priv_str)
{   global $_ACT,$Arr;
    if (strpos(',' . $_SESSION['WebUserInfo']['group_power'] . ',', ',' . $priv_str . ',') === false)
    {
		return false;
    }
    else
    {
        return true;
    }
}


/**
 * 根据过滤条件获得排序的标记
 *
 * @access  public
 * @param   array   $filter
 * @return  array
 */
function sort_flag($filter)
{
    $flag['tag']            = 'sort_' . preg_replace('/^.*\./', '', $filter['sort_by']);
    $flag['tag_sort_order'] = 'sort_order_' . preg_replace('/^.*\./', '', $filter['sort_by']);
    $flag['sort_order']     = $filter['sort_order'] == "DESC" ? 'ASC' : 'DESC';
    $flag['img']            = '<img src="/temp/skin1/images/' . ($filter['sort_order'] == "DESC" ? 'sort_desc.gif' : 'sort_asc.gif') . '"/>';
    return $flag;
}

/**
 * 分页的信息加入条件的数组
 *
 * @access  public
 * @return  array
 */
function page_and_size($filter)
{
    if (isset($_GET['page_size']) && intval($_GET['page_size']) > 0)
    {
        $filter['page_size'] = intval($_GET['page_size']);
    }
    elseif (isset($_COOKIE['WEB']['page_size']) && intval($_COOKIE['WEB']['page_size']) > 0)
    {
        $filter['page_size'] = intval($_COOKIE['WEB']['page_size']);
    }
    else
    {
        $filter['page_size'] = _PAGESIZE_;
    }

    /* 每页显示 */
    $filter['page'] = (empty($_GET['page']) || intval($_GET['page']) <= 0) ? 1 : intval($_GET['page']);

    /* page 总数 */
    $filter['page_count'] = (!empty($filter['record_count']) && $filter['record_count'] > 0) ? ceil($filter['record_count'] / $filter['page_size']) : 1;
    /* 边界处理 */
    if ($filter['page'] > $filter['page_count'])
    {
        $filter['page'] = $filter['page_count'];
    }

    $filter['start'] = ($filter['page'] - 1) * $filter['page_size'];
    return $filter;
}




/**
 * 创建像这样的查询: "IN('a','b')";
 *
 * @access   public
 * @param    mix      $item_list      列表数组或字符串
 * @param    string   $field_name     字段名称
 *
 * @return   void
 */
function db_create_in($item_list, $field_name = '')
{
    if (empty($item_list))
    {
        return $field_name . " IN ('') ";
    }
    else
    {
        if (!is_array($item_list))
        {
            $item_list = explode(',', $item_list);
        }
        $item_list = array_unique($item_list);
        $item_list_tmp = '';
        foreach ($item_list AS $item)
        {
            if ($item !== '')
            {
                $item_list_tmp .= $item_list_tmp ? ",'$item'" : "'$item'";
            }
        }
        if (empty($item_list_tmp))
        {
            return $field_name . " IN ('') ";
        }
        else
        {
            return $field_name . ' IN (' . $item_list_tmp . ') ';
        }
    }
}






/**
 * 保存过滤条件
 * @param   array   $filter     过滤条件
 * @param   string  $sql        查询语句
 * @param   string  $param_str  参数字符串，由list函数的参数组成
 */
function set_filter($filter, $sql, $param_str = '')
{
    $filterfile = basename(PHP_SELF, '.php');
    if ($param_str)
    {
        $filterfile .= $param_str;
    }
    setcookie('WEB[lastfilterfile]', sprintf('%X', crc32($filterfile)), time() + 600);
    setcookie('WEB[lastfilter]',     urlencode(serialize($filter)), time() + 600);
    //setcookie('WEB[lastfiltersql]',  urlencode($sql), time() + 600);
}

/**
 * 取得上次的过滤条件
 * @param   string  $param_str  参数字符串，由list函数的参数组成
 * @return  如果有，返回array('filter' => $filter, 'sql' => $sql)；否则返回false
 */
function get_filter($param_str = '')
{
    $filterfile = basename(PHP_SELF, '.php');
    if ($param_str)
    {
        $filterfile .= $param_str;
    }
    if (isset($_GET['uselastfilter']) && isset($_COOKIE['WEB']['lastfilterfile'])
        && $_COOKIE['WEB']['lastfilterfile'] == sprintf('%X', crc32($filterfile)))
    {
        return array(
            'filter' => unserialize(urldecode($_COOKIE['WEB']['lastfilter'])),
            'sql'    => urldecode($_COOKIE['WEB']['lastfiltersql'])
        );
    }
    else
    {
        return false;
    }
}



/**
 * 获得服务器上的 GD 版本
 *
 * @access      public
 * @return      int         可能的值为0，1，2
 */
function gd_version()
{
    include_once(ROOT_PATH . 'lib/cls_image.php');

    return cls_image::gd_version();
}




/**
 * 系统提示信息
 *
 * @access      public
 * @param       string      msg_detail      消息内容
 * @param       int         msg_type        消息类型， 0消息，1错误，2询问
 * @param       array       links           可选的链接
 * @param       boolen      $auto_redirect  是否需要自动跳转
 * @return      void
 */
function sys_msg($msg_detail, $msg_type = 0, $links = array(), $auto_redirect = true)
{
	global $_ACT,$Arr;
    if (count($links) == 0)
    {
        $links[0]['name'] = "返回上一页";
        $links[0]['url'] = 'javascript:history.go(-1)';
    }

	$_ACT = 'msg';
	$Arr["msg"] = $msg_detail;
	$Arr["links"] = $links;
	$Arr["auto_redirect"] = $auto_redirect;
	temp_disp();
    exit;
}



/**
 * 检查目标文件夹是否存在，如果不存在则自动创建该目录
 *
 * @access      public
 * @param       string      folder     目录路径。不能使用相对于网站根目录的URL
 *
 * @return      bool
 */
function make_dir($folder)
{
    $reval = false;

    if (!file_exists($folder))
    {
        /* 如果目录不存在则尝试创建该目录 */
        @umask(0);

        /* 将目录路径拆分成数组 */
        preg_match_all('/([^\/]*)\/?/i', $folder, $atmp);

        /* 如果第一个字符为/则当作物理路径处理 */
        $base = ($atmp[0][0] == '/') ? '/' : '';

        /* 遍历包含路径信息的数组 */
        foreach ($atmp[1] AS $val)
        {
            if ('' != $val)
            {
                $base .= $val;

                if ('..' == $val || '.' == $val)
                {
                    /* 如果目录为.或者..则直接补/继续下一个循环 */
                    $base .= '/';

                    continue;
                }
            }
            else
            {
                continue;
            }

            $base .= '/';

            if (!file_exists($base))
            {
                /* 尝试创建目录，如果创建失败则继续循环 */
                if (@mkdir(rtrim($base, '/'), 0777))
                {
                    @chmod($base, 0777);
                    $reval = true;
                }
            }
        }
    }
    else
    {
        /* 路径已经存在。返回该路径是不是一个目录 */
        $reval = is_dir($folder);
    }

    clearstatcache();

    return $reval;
}




/**
 * 检查文件类型
 *
 * @access      public
 * @param       string      filename            文件名
 * @param       string      realname            真实文件名
 * @param       string      limit_ext_types     允许的文件类型
 * @return      string
 */
function check_file_type($filename, $realname = '', $limit_ext_types = '')
{
    if ($realname)
    {
        $extname = strtolower(substr($realname, strrpos($realname, '.') + 1));
    }
    else
    {
        $extname = strtolower(substr($filename, strrpos($filename, '.') + 1));
    }

    if ($limit_ext_types && stristr($limit_ext_types, '|' . $extname . '|') === false)
    {
        return '';
    }

    $str = $format = '';

    $file = @fopen($filename, 'rb');
    if ($file)
    {
        $str = @fread($file, 0x400); // 读取前 1024 个字节
        @fclose($file);
    }
    else
    {
        if (stristr($filename, ROOT_PATH) === false)
        {
            if ($extname == 'jpg' || $extname == 'jpeg' || $extname == 'gif' || $extname == 'png' || $extname == 'doc' ||
                $extname == 'xls' || $extname == 'txt'  || $extname == 'zip' || $extname == 'rar' || $extname == 'ppt' ||
                $extname == 'pdf' || $extname == 'rm'   || $extname == 'mid' || $extname == 'wav' || $extname == 'bmp' ||
                $extname == 'swf' || $extname == 'chm'  || $extname == 'sql' || $extname == 'cert')
            {
                $format = $extname;
            }
        }
        else
        {
            return '';
        }
    }

    if ($format == '' && strlen($str) >= 2 )
    {
        if (substr($str, 0, 4) == 'MThd' && $extname != 'txt')
        {
            $format = 'mid';
        }
        elseif (substr($str, 0, 4) == 'RIFF' && $extname == 'wav')
        {
            $format = 'wav';
        }
        elseif (substr($str ,0, 3) == "\xFF\xD8\xFF")
        {
            $format = 'jpg';
        }
        elseif (substr($str ,0, 4) == 'GIF8' && $extname != 'txt')
        {
            $format = 'gif';
        }
        elseif (substr($str ,0, 8) == "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A")
        {
            $format = 'png';
        }
        elseif (substr($str ,0, 2) == 'BM' && $extname != 'txt')
        {
            $format = 'bmp';
        }
        elseif ((substr($str ,0, 3) == 'CWS' || substr($str ,0, 3) == 'FWS') && $extname != 'txt')
        {
            $format = 'swf';
        }
        elseif (substr($str ,0, 4) == "\xD0\xCF\x11\xE0")
        {   // D0CF11E == DOCFILE == Microsoft Office Document
            if (substr($str,0x200,4) == "\xEC\xA5\xC1\x00" || $extname == 'doc')
            {
                $format = 'doc';
            }
            elseif (substr($str,0x200,2) == "\x09\x08" || $extname == 'xls')
            {
                $format = 'xls';
            } elseif (substr($str,0x200,4) == "\xFD\xFF\xFF\xFF" || $extname == 'ppt')
            {
                $format = 'ppt';
            }
        } elseif (substr($str ,0, 4) == "PK\x03\x04")
        {
            $format = 'zip';
        } elseif (substr($str ,0, 4) == 'Rar!' && $extname != 'txt')
        {
            $format = 'rar';
        } elseif (substr($str ,0, 4) == "\x25PDF")
        {
            $format = 'pdf';
        } elseif (substr($str ,0, 3) == "\x30\x82\x0A")
        {
            $format = 'cert';
        } elseif (substr($str ,0, 4) == 'ITSF' && $extname != 'txt')
        {
            $format = 'chm';
        } elseif (substr($str ,0, 4) == "\x2ERMF")
        {
            $format = 'rm';
        } elseif ($extname == 'sql')
        {
            $format = 'sql';
        } elseif ($extname == 'txt')
        {
            $format = 'txt';
        }
    }

    if ($limit_ext_types && stristr($limit_ext_types, '|' . $format . '|') === false)
    {
        $format = '';
    }

    return $format;
}


/**
 * 将上传文件转移到指定位置
 *
 * @param string $file_name
 * @param string $target_name
 * @return blog
 */
function move_upload_file($file_name, $target_name = '')
{
    if (function_exists("move_uploaded_file"))
    {
        if (move_uploaded_file($file_name, $target_name))
        {
            @chmod($target_name,0755);
            return true;
        }
        else if (copy($file_name, $target_name))
        {
            @chmod($target_name,0755);
            return true;
        }
    }
    elseif (copy($file_name, $target_name))
    {
        @chmod($target_name,0755);
        return true;
    }
    return false;
}




/**
 * 读取缓存
 *
 * @param string $cache_name 缓存名称,通常为键名,不包括.php
 * @param string|int $type 缓存路径类型,1:data-cache/; 2:eload_admin/cache_files/; 3: 自定义路径为$default; 否则自定义路径
 * @param mixed $default 当type=3时为缓存路径,否则为缓存不存在时默认值
 *
 * @return mixed 缓存存在,返回缓存内容,否则false
 */
function read_static_cache($cache_name, $type = 1, $default = false) {
    global $cur_lang, $default_lang;

    static $result = array();

    $old_cache_name = $cache_name;

    if ('en_category_c_key' == $cache_name) {//无en_category_c_key by mashanling on 2014-06-13 10:29:32
        $cache_name = 'category_c_key';
    }

    //多语言
    $is_multi_language = $cur_lang && $default_lang && $cur_lang != $default_lang;

    if ($is_multi_language && in_array($cache_name, array('category_c', 'category_html', 'js_category_html', 'category_c_key', 'payment', 'shipping_method'))) {
        $cache_name = $cache_name . '_' . $cur_lang;
	}

    if ($is_multi_language && in_array($old_cache_name, array('payment', 'shipping_method')) ) {
        $cache_file_path = get_cache_filename($old_cache_name, $type, $default);
    }
    else {
        $cache_file_path = get_cache_filename($cache_name, $type, $default);
    }

    $cache_key = md5($cache_file_path); //以文件名作为key,防止同文件名不同路径冲突 by mashanling on 2013-08-20 14:16:31

    if (array_key_exists($cache_key, $result)) {
        return $result[$cache_key];
    }

    //读取memcach缓存
    if(SHOP_CACHE_ENABLE) {
        $memcache_obj = get_memcache();
	    $result[$cache_key] = $memcache_obj->get($cache_file_path);	//获取memcache缓存数据

	    if(empty($result[$cache_key])) {
            //读取数据库
            $crc32  = sprintf('%u', crc32(str_replace(ROOT_PATH, '', $cache_file_path)));
            $db     = $GLOBALS['db'];

            if (DB_HOST != $db->Host) {//确保写入主数据库
                $db = new MySql(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
            }

            if ($data = $db->arrQuery('SELECT `content`,is_compress FROM ' . MEM_CACHE . ' WHERE mid=' . $crc32)) {
                $data       = $data[0];
                $content    = $data['content'];

                if ($data['is_compress']) {
                    $content = gzuncompress($content);
                }

                $result[$cache_key] = unserialize($content);

                cache($cache_file_path, $result[$cache_key], false);
            }
            elseif (is_file($cache_file_path)) {//特殊文件,如payment.php,shipping_method.php
		        include_once($cache_file_path);

                //管理员缓存解密 by mashanling on 2013-12-02 15:28:20
                if ('land' == $cache_name && 2 == $type && ENCRYPT_TYPE_FALSE != ENCRYPT_ADMIN) {
                    $data = unserialize(encrypt($data, DECRYPT, ENCRYPT_ADMIN));
                }

                if (!isset($data)) {
                    $data = false;
                }

                cache($cache_file_path, $data);

                $result[$cache_key] = $data;

                if (false === $result[$cache_key]) {//读取文件,写日志观察一段时间
                    Logger::filename('nocache.getfile.error');
                    trigger_error($cache_file_path);
                }
		    }
            //abc相关词
            elseif (false !== strpos($cache_file_path, 'data-cache/abc_keywords/') && false === strpos($cache_file_path, 'category_hot_searches')) {

                $keyword_id = basename($cache_file_path, '.php');
                require_once(ROOT_PATH . 'lib/seo/class.seo.php');
                $class = new SEO();
                $result[$cache_key] = $class->setRelativeKeywords($keyword_id);

                return $result[$cache_key];
            }
            else {
                $result[$cache_key] = $default;
            }
	   }
    }
    elseif (is_file($cache_file_path)) {
        include_once($cache_file_path);

        //管理员缓存解密 by mashanling on 2013-12-02 15:28:20
        if ('land' == $cache_name && 2 == $type && ENCRYPT_TYPE_FALSE != ENCRYPT_ADMIN) {
            $data = unserialize(encrypt($data, DECRYPT, ENCRYPT_ADMIN));
        }

        $result[$cache_key] = $data;
    }
    else {
        $result[$cache_key] = $default;
    }

    if ($default === $result[$cache_key]) {//无缓存,写日志观察一段时间
        Logger::filename('nocache.error');
        trigger_error($cache_file_path);
    }

    return $result[$cache_key];
}//end read_static_cache

/**
 * 写缓存
 *
 * @param string $cache_name 缓存名称,通常为键名,不包括.php
 * @param mixed $caches 缓存内容
 * @param int|string $type 缓存路径类型,1:data-cache/; 2:eload_admin/cache_files/; 3: 自定义路径为$custom_path; 否则自定义路径
 * @param string $custom_path 当type=3时为缓存路径
 *
 * @return void 无返回值
 */
function write_static_cache($cache_name, $caches, $type = 1, $custom_path = '') {
    $cache_file_path  = get_cache_filename($cache_name, $type, $custom_path);

    //管理员缓存加密 by mashanling on 2013-12-02 15:28:20
    if ('land' == $cache_name && 2 == $type && ENCRYPT_TYPE_FALSE != ENCRYPT_ADMIN) {
        $memcache_cache = $caches;
        $caches         = encrypt(serialize($caches), ENCRYPT, ENCRYPT_ADMIN);
    }

    if (null === $caches) {//删除
        return cache($cache_file_path, null);
    }

    if (!SHOP_CACHE_ENABLE) {//非memcache,写文件缓存

        if (!is_dir($dir = dirname($cache_file_path))) {
            mkdir($dir, 0755, true);
        }

        $content = "<?php\n//后台自动生成，最后生成时间 ".date('Y-m-d H:i:s', time()-date('Z')+28800)."\r\n";
        $content .= "\$data = " . var_export($caches, true) . ";\r\n";
        $content .= "?>";

        file_put_contents($cache_file_path, $content, LOCK_EX);
    }

    cache($cache_file_path, isset($memcache_cache) ? $memcache_cache : $caches);
}//end write_static_cache

/*
 * 读取 或 设置缓存数据
 *
 * @author          mrmsl <msl-138@163.com>
 * @date            2014-06-05 10:32:53
 *
 * @param string $filename 文件名,绝对路径
 * @param mixed $data 缓存数据
 * @param bool $cache2db true将缓存同时写入数据库中
 * @param int $expires 过期时间,单位: 秒
 *
 * @return mixed 读取时,返回缓存内容,否则true
*/
function cache($filename, $data, $cache2db = true, $expires = 0) {
    static $cache = array();

    if (false === strpos($filename, '.php')) {//只传缓存文件名
        $filename = get_cache_filename($filename, 1);
    }
    elseif (false === strpos($filename, ROOT_PATH)) {
        $filename = ROOT_PATH . $filename;
    }

    $cache_key = md5($filename);

    if (SHOP_CACHE_ENABLE) {
        $memcache = get_memcache();
    }

    //abc相关词
    if (false !== strpos($filename, 'data-cache/abc_keywords/') && false === strpos($filename, 'category_hot_searches')) {
        $cache2db = false;
    }

    if ('' === $data) {//读取

        if (!isset($cache[$cache_key])) {
            $cache[$cache_key] = $memcache->get($filename);
        }

        return $cache[$cache_key];
    }
    elseif (null === $data) {//删除
        unset($cache[$cache_key]);

        if (isset($memcache)) {
            $memcache->delete($filename);
        }

        if ($cache2db) {

            $db = $GLOBALS['db'];

            if (DB_HOST != $db->Host) {//确保写入主数据库
                $db = new MySql(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
            }

            $db->delete(MEM_CACHE, 'mid=' . sprintf('%u', crc32(str_replace(ROOT_PATH, '', $filename))));
        }

        return true;
    }
    elseif (isset($memcache)) {
        $memcache->set($filename, $data, $expires);
    }

    $cache[$cache_key] = $data;

    if ($cache2db && false !== $data) {
        $filename       = str_replace(ROOT_PATH, '', $filename);
        $crc32          = sprintf('%u', crc32($filename));
        $content        = serialize($data);
        $content_length = strlen($content);

        if ($content_length > 10000) {//长度大于1万,压缩
            $content            = gzcompress($content);
            $gzcompress_length  = strlen($content);
            $is_compress        = 1;
        }
        else {
            $gzcompress_length      = 0;
            $is_compress            = 0;
        }

        $now        = time() - date('Z');
        $content    = addslashes($content);

        if ($expires) {
            $expires = $now + $expires;
        }

        $db         = $GLOBALS['db'];

        if (DB_HOST != $db->Host) {//确保写入主数据库
            $db = new MySql(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
        }

        if ($db->count_info(MEM_CACHE, '*', $where = 'mid=' . $crc32)) {
            $result = $db->update(MEM_CACHE, "update_time={$now},content_length={$content_length},gzcompress_length={$gzcompress_length},content='{$content}',is_compress={$is_compress},expire_time=" . $expires, $where);
        }
        else {
            $result = $db->insert(MEM_CACHE, '`mid`,`filename`,`content`,update_time,is_compress,expire_time,content_length,gzcompress_length', "{$crc32},'{$filename}','{$content}',{$now},{$is_compress},{$expires},{$content_length},{$gzcompress_length}");
        }
    }

    return true;
}//end cache

/**
 * 获取绝对路径的缓存文件名
 *
 * @author          mrmsl <msl-138@163.com>
 * @date            2014-06-09 09:33:30
 *
 * @see read_static_cache
 *
 * @return string 绝对路径的缓存文件名
 */
function get_cache_filename($cache_name, $type, $custom_path = null) {

    if (1 == $type) {//前台缓存
        $path = 'data-cache/';
    }
    else if (2 == $type) {//后台缓存
        $path = 'eload_admin/cache_files/';
    }
    //兼容sammydress  by mashanling on 2013-12-27 19:21:35
    elseif (3 == $type && $custom_path) {//write_static_cache($cache_name, $caches,$type=1,$custom_path='')
        $path = $custom_path;
    }
    else {//新增自定义路径 by mashanling on 2013-01-17 11:09:39
        $path = $type;
    }

    if ('/' != substr($path, -1)) {
        $path .= '/';
    }

    if (false === strpos($path, ROOT_PATH)) {
        $path = ROOT_PATH . $path;
    }

    return $path . $cache_name . '.php';
}//end get_cache_filename

/**
 * 获取memcache实例
 *
 * @author          mrmsl <msl-138@163.com>
 * @date            2014-06-17 08:59:36
 *
 * @return object memcache实例
 */
function get_memcache() {
    static $memcache = null;

    if (null === $memcache) {
        require_once(ROOT_PATH . '/lib/CacheFactory.class.php');
        $memcache = new Cache();
        $memcache = $memcache ->getInstance('memcache');
        $memcache->setCompressThreshold(10000, 0.2);
    }

    return $memcache;
}

/*
文件名替换
*/
function title_to_url($title)
{
	$url_title_temp = '';
	$title=strtolower($title);
	preg_match_all("/[0-9a-zA-Z\.]{1,}/",$title,$match);
	$url_title_temp = join('-',$match[0]);
	$title = $url_title_temp;
	return $title;
}


/*
分类名称替换
*/
function title_2_url($title)
{
	$url_title_temp = '';
	preg_match_all("/[0-9a-zA-Z\.]{1,}/",$title,$match);
	$url_title_temp = join(' ',$match[0]);
	$title = $url_title_temp;
	return $title;
}



/**
* 获得友好的URL访问
*
* @accesspublic
* @return array
*/
function getQueryString()
{
	 $leiflag = false;
	 $str_temp = '';
	if (!empty($_SERVER['HTTP_X_REWRITE_URL']))
	{
	   $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
	}
	$_url_arr = array();
	$_url_arr = explode('.htm',$_SERVER['REQUEST_URI']);
	$_SGETS = empty($_url_arr[0])?'':$_url_arr[0];
	$_SGETS = substr($_SGETS,1);
	if ( strpos($_SGETS,'category-name')>0) $leiflag = true; //如果是分类
	$_SGETS = explode("-",$_SGETS);
	$_SLEN = count($_SGETS);
         if ($leiflag){
			for($i=3;$i<$_SLEN;$i++){
				if ($_SGETS[$i]=='page') break;
				if ($str_temp == ''){
					$str_temp = $_SGETS[$i];
				}else{
					$str_temp .= '-'.$_SGETS[$i];
				}
				unset($_SGETS[$i]);
			}
			$_SGETS[3] = $str_temp;
		}
	$_SGET = $_GET;
    foreach($_SGETS as $k => $v){
        if(!empty($_SGETS[$k]) && !empty($_SGETS[$k+1])) $_SGET[$_SGETS[$k]] = $_SGETS[$k+1];
    }
	if(strpos($_SERVER['REQUEST_URI'],'/affordable-') !== false&&!empty($_SGET['m'])){
		if ($_SGET['m'] != 'abcindex' && $_SGET['m'] != 'category' && $_SGET['m'] != 'dalei')
			$_SGET['m'] = 'search';
	}
	return $_SGET;
}

/**
* 生成链接URL
*
* @accesspublic
* @param array $arr
* @return string
*/
function setUrl($arr)
{
	$queryString='';
	foreach($arr as $k=> $v)
	{
		$queryString.=$k.'-'.$v.'-';
	}
	$queryString = substr($queryString,0,-1);
	$queryString.= URLSUFFIX;
	return $queryString;
}

/**
 * 格式化商品价格
 *
 * @access  public
 * @param   float   $price  商品价格
 * @return  string
 */
function price_format($price, $change_price = true)
{
    if ($change_price && defined('ECS_ADMIN') === false)
    {
        switch ($GLOBALS['_CFG']['price_format'])
        {
            case 0:
                $price = number_format($price, 2, '.', '');
                break;
            case 1: // 保留不为 0 的尾数
                $price = preg_replace('/(.*)(\\.)([0-9]*?)0+$/', '\1\2\3', number_format($price, 2, '.', ''));

                if (substr($price, -1) == '.')
                {
                    $price = substr($price, 0, -1);
                }
                break;
            case 2: // 不四舍五入，保留1位
                $price = substr(number_format($price, 2, '.', ''), 0, -1);
                break;
            case 3: // 直接取整
                $price = intval($price);
                break;
            case 4: // 四舍五入，保留 1 位
                $price = number_format($price, 1, '.', '');
                break;
            case 5: // 先四舍五入，不保留小数
                $price = round($price);
                break;
        }
    }
    else
    {
        $price = number_format($price, 2, '.', '');
    }
    return sprintf($GLOBALS['_CFG']['currency_format'], $price);
}

/**
 * 重新获得商品图片与商品相册的地址
 *
 * @param int $goods_id 商品ID
 * @param string $image 原商品相册图片地址
 * @param boolean $thumb 是否为缩略图
 * @param string $call 调用方法(商品图片还是商品相册)
 * @param boolean $del 是否删除图片
 *
 * @return string   $url
 */
function get_image_path($goods_id, $image='', $thumb=false, $call='goods', $del=false)
{
    $url = empty($image) ? $GLOBALS['_CFG']['no_picture'] : IMGCACHE_PATH . $image;
    return $url;
}

//daohao
function getNavTitle($typeArray,$Parent){
	global $nav_title, $cur_lang, $default_lang;
	if($Parent !=0){
		if($cur_lang == $default_lang) {
			$lang = '';
		} else {
			$lang =  $cur_lang . '/';
		}
		foreach($typeArray as $keys =>$row){
			$isparent = false;
			if($row["cat_id"]==$Parent){
				if($row["parent_id"]==0)$isparent = true;
				$thisurl =  $row['url_title'];
				$nav_title = " &raquo; <a href='/$thisurl' >".get_cat_name($row['cat_id'],$row["cat_name"])."</a>".$nav_title;
				if($row["parent_id"]!=0)getNavTitle($typeArray,$row["parent_id"]);
			}
		}
	}
	return $nav_title;
}

//生成谷歌营销代码使用导航
function getGoogleNavTitle($typeArray,$Parent){
	global $google_nav_title, $cur_lang, $default_lang;
	if($Parent !=0){
		if($cur_lang == $default_lang) {
			$lang = '';
		} else {
			$lang =  $cur_lang . '/';
		}
		foreach($typeArray as $keys =>$row){
			$isparent = false;
			if($row["cat_id"]==$Parent){
				if($row["parent_id"]==0)$isparent = true;
				$thisurl =  $row['url_title'];
				$google_nav_title = get_cat_name($row['cat_id'],$row["cat_name"])." > ".$google_nav_title;
				if($row["parent_id"]!=0)getGoogleNavTitle($typeArray,$row["parent_id"]);
			}
		}
	}
	return $google_nav_title;
}

function creat_nav_url($url_title,$cat_id,$big_cat = false,$lang=''){
	global $cur_lang_url;
	$cur_lang_url_t =$cur_lang_url;
	$link_key ="-c-";
	if(!empty($lang)&&$lang!='en')$cur_lang_url_t ="$lang/";
	return empty($url_title)?$link_key.$cat_id.'.html':DOMAIN.'/'.$cur_lang_url_t.$url_title;
}

//前台商品详细链接
function get_details_link($goods_id,$url_title='',$goods_attr_id = ''){
	global $cur_lang_url;
	$url_title = $url_title?title_to_url($url_title):'';
	$goods_attr = $goods_attr_id?'-a-'.$goods_attr_id:'';
	return DOMAIN.'/'.$cur_lang_url.'best_'.$goods_id.$goods_attr.'.html';
}

//多语言商品详细链接
function get_details_link_lang($goods_id, $lang){
	if(!empty($goods_id) && !empty($lang)) {
		return DOMAIN.'/'. $lang .'/best_'. $goods_id .'.html';
	}
}

//前台分类名称
function get_cat_name($cat_id,$cat_name_en=''){
	global $cur_lang,$cur_lang_cat_arr,$cat_key_Arr;
	if(empty($cur_lang_cat_arr))$cur_lang_cat_arr = read_static_cache($cur_lang.'_category_c_key',2);
	if(empty($cur_lang_cat_arr)||empty($cur_lang)||$cur_lang == 'en'||empty($cur_lang_cat_arr["$cat_id"])){
		if(!empty($cat_name_en)){
			return  $cat_name_en;
		}
		else{
			if(empty($cat_key_Arr))$cat_key_Arr = read_static_cache($cur_lang.'_category_c_key',2);
			return $cat_key_Arr["$cat_id"]['cat_name'];
		}
	}else {
		return $cur_lang_cat_arr["$cat_id"]['cat_name'];
	}
}

//分类标题
function getTitle($typeArray,$Parent){
	global $shop_title;
	if($Parent !=0){
		foreach($typeArray as $keys =>$row){
			if($row["cat_id"]==$Parent){
					$shop_title .= ' - '.get_cat_name($row["cat_id"],$row["cat_name"]);
				if($row["parent_id"]!=0)getTitle($typeArray,$row["parent_id"]);
			}
		}
	}
	return $shop_title;
}

/**
 * 将对象成员变量或者数组的特殊字符进行转义
 *
 * @access   public
 * @param    mix        $obj      对象或者数组
 * @author   Xuan Yan
 *
 * @return   mix                  对象或者数组
 */
function addslashes_deep_obj($obj)
{
    if (is_object($obj) == true)
    {
        foreach ($obj AS $key => $val)
        {
            $obj->$key = addslashes_deep($val);
        }
    }
    else
    {
        $obj = addslashes_deep($obj);
    }
    return $obj;
}

/**
 * 递归方式的对变量中的特殊字符去除转义
 *
 * @access  public
 * @param   mix     $value
 *
 * @return  mix
 */
function stripslashes_deep($value)
{
    if (empty($value))
    {
        return $value;
    }
    else
    {
        return is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
    }
}

/**
 * 判断某个商品是否正在特价促销期
 *
 * @access  public
 * @param   float   $price      促销价格
 * @param   string  $start      促销开始日期
 * @param   string  $end        促销结束日期
 * @return  float   如果还在促销期则返回促销价，否则返回0
 */
function bargain_price($price, $start, $end)
{
    if ($price == 0)
    {
        return 0;
    }
    else
    {
        $time = gmtime();

        if ($time >= $start && $time <= $end)
        {
        	//echo "$time,$start,$end<br>";
            return $price;
        }
        else
        {
            return 0;
        }
    }
}



/**
 * 验证输入的邮件地址是否合法
 *
 * @access  public
 * @param   string      $email      需要验证的邮件地址
 *
 * @return bool
 */
function is_email($user_email)
{
    $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
    if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false)
    {
        if (preg_match($chars, $user_email))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    else
    {
        return false;
    }
}


/**
 * 显示一个提示信息
 *
 * @access  public
 * @param   string  $content
 * @param   string  $link
 * @param   string  $href
 * @param   string  $type               信息类型：warning, error, info
 * @param   string  $auto_redirect      是否自动跳转
 * @return  void
 */
function show_message($content, $links = '', $hrefs = '', $type = 'info', $auto_redirect = false)
{
	global  $_ACT,$Arr,$_CFG;
    $msg['content'] = $content;
    if (is_array($links) && is_array($hrefs))
    {
        if (!empty($links) && count($links) == count($hrefs))
        {
            foreach($links as $key =>$val)
            {
                $msg['url_info'][$val] = $hrefs[$key];
            }
            $msg['back_url'] = $hrefs['0'];
        }
    }
    else
    {
        $link   = empty($links) ? $GLOBALS['_LANG']['back_up_page'] : $links;
        $href    = empty($hrefs) ? 'javascript:history.back(-1)'       : $hrefs;
        $msg['url_info'][$link] = $href;
        $msg['back_url'] = $href;
    }

    $msg['type'] = $type;
    //$position = assign_ur_here(0, $GLOBALS['_LANG']['sys_msg']);
    $Arr['shop_title'] = 'System Information - '.$_CFG['shop_name'];   // 页面标题
   // $Arr['smarty']->assign('ur_here',    $position['ur_here']); // 当前位置


    $Arr['auto_redirect'] = $auto_redirect ;
    $Arr['message'] = $msg;
    $_ACT = 'msg';
    temp_disp();
	exit();
}

/**
 *  将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
 *
 * @access  public
 * @param   string       $str         待转换字串
 *
 * @return  string       $str         处理后字串
 */
function make_semiangle($str)
{
    $arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
                 '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
                 'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
                 'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
                 'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
                 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
                 'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
                 'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
                 'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
                 'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
                 'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
                 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
                 'ｙ' => 'y', 'ｚ' => 'z',
                 '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[',
                 '】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']',
                 '‘' => '[', '’' => ']', '｛' => '{', '｝' => '}', '《' => '<',
                 '》' => '>',
                 '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
                 '：' => ':', '。' => '.', '、' => ',', '，' => '.', '、' => '.',
                 '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',
                 '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"',
                 '　' => ' ');

    return strtr($str, $arr);
}


/**
 * 格式化重量：小于1千克用克表示，否则用千克表示
 * @param   float   $weight     重量
 * @return  string  格式化后的重量
 */
function formated_weight($weight)
{
    $weight = round(floatval($weight), 3);
    if ($weight > 0)
    {
       /// if ($weight < 1)
        //{
            /* 小于1千克，用克表示 */
            //return intval($weight * 1000) . ' g';
       // }
       // else
      //  {
           /* 大于1千克，用千克表示 */
            return $weight . ' kg';
      //  }
    }
    else
    {
        return 0;
    }
}

/**
 * 添加商品名样式
 * @param   string     $goods_title     商品名称
 * @param   string     $style          样式参数
 * @return  string
 */
function add_style($goods_title, $style)
{
    $goods_style_name = $goods_title;

    $arr   = explode('+', $style);

    $font_color     = !empty($arr[0]) ? $arr[0] : '';
    $font_style = !empty($arr[1]) ? $arr[1] : '';

    if ($font_color!='')
    {
        $goods_style_name = '<font color=' . $font_color . '>' . $goods_style_name . '</font>';
    }
    if ($font_style != '')
    {
        $goods_style_name = '<' . $font_style .'>' . $goods_style_name . '</' . $font_style . '>';
    }
    return $goods_style_name;
}

/**
 * 统计访问信息
 *
 * @access  public
 * @return  void
 */
function visit_stats()
{

    /* 来源 */
    if (!empty($_SERVER['HTTP_REFERER']) && strlen($_SERVER['HTTP_REFERER']) > 9)
    {
        $pos = strpos($_SERVER['HTTP_REFERER'], '/', 9);
        if ($pos !== false)
        {
            $domain = substr($_SERVER['HTTP_REFERER'], 0, $pos);
            $path   = substr($_SERVER['HTTP_REFERER'], $pos);

            /* 来源关键字 */
            if (!empty($domain) && !empty($path))
            {
               // save_searchengine_keyword($domain, $path);
            }
        }
    }

}

/**
 * 保存搜索引擎关键字
 *
 * @access  public
 * @return  void
 */
function save_searchengine_keyword($domain, $path)
{
    if (strpos($domain, 'google.com.tw') !== false && preg_match('/q=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'GOOGLE TAIWAN';
        $keywords = urldecode($regs[1]); // google taiwan
    }
    if (strpos($domain, 'google.cn') !== false && preg_match('/q=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'GOOGLE CHINA';
        $keywords = urldecode($regs[1]); // google china
    }
    if (strpos($domain, 'google.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'GOOGLE';
        $keywords = urldecode($regs[1]); // google
    }
    elseif (strpos($domain, 'baidu.') !== false && preg_match('/wd=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'BAIDU';
        $keywords = urldecode($regs[1]); // baidu
    }
    elseif (strpos($domain, 'baidu.') !== false && preg_match('/word=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'BAIDU';
        $keywords = urldecode($regs[1]); // baidu
    }
    elseif (strpos($domain, '114.vnet.cn') !== false && preg_match('/kw=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'CT114';
        $keywords = urldecode($regs[1]); // ct114
    }
    elseif (strpos($domain, 'iask.com') !== false && preg_match('/k=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'IASK';
        $keywords = urldecode($regs[1]); // iask
    }
    elseif (strpos($domain, 'soso.com') !== false && preg_match('/w=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'SOSO';
        $keywords = urldecode($regs[1]); // soso
    }
    elseif (strpos($domain, 'sogou.com') !== false && preg_match('/query=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'SOGOU';
        $keywords = urldecode($regs[1]); // sogou
    }
    elseif (strpos($domain, 'so.163.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'NETEASE';
        $keywords = urldecode($regs[1]); // netease
    }
    elseif (strpos($domain, 'yodao.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'YODAO';
        $keywords = urldecode($regs[1]); // yodao
    }
    elseif (strpos($domain, 'zhongsou.com') !== false && preg_match('/word=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'ZHONGSOU';
        $keywords = urldecode($regs[1]); // zhongsou
    }
    elseif (strpos($domain, 'search.tom.com') !== false && preg_match('/w=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'TOM';
        $keywords = urldecode($regs[1]); // tom
    }
    elseif (strpos($domain, 'live.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'MSLIVE';
        $keywords = urldecode($regs[1]); // MSLIVE
    }
    elseif (strpos($domain, 'tw.search.yahoo.com') !== false && preg_match('/p=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'YAHOO TAIWAN';
        $keywords = urldecode($regs[1]); // yahoo taiwan
    }
    elseif (strpos($domain, 'cn.yahoo.') !== false && preg_match('/p=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'YAHOO CHINA';
        $keywords = urldecode($regs[1]); // yahoo china
    }
    elseif (strpos($domain, 'yahoo.') !== false && preg_match('/p=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'YAHOO';
        $keywords = urldecode($regs[1]); // yahoo
    }
    elseif (strpos($domain, 'msn.com.tw') !== false && preg_match('/q=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'MSN TAIWAN';
        $keywords = urldecode($regs[1]); // msn taiwan
    }
    elseif (strpos($domain, 'msn.com.cn') !== false && preg_match('/q=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'MSN CHINA';
        $keywords = urldecode($regs[1]); // msn china
    }
    elseif (strpos($domain, 'msn.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs))
    {
        $searchengine = 'MSN';
        $keywords = urldecode($regs[1]); // msn
    }

    if (!empty($keywords))
    {
        $gb_search = array('YAHOO CHINA', 'TOM', 'ZHONGSOU', 'NETEASE', 'SOGOU', 'SOSO', 'IASK', 'CT114', 'BAIDU');
        if (EC_CHARSET == 'utf-8' && in_array($searchengine, $gb_search))
        {
            $keywords = ecs_iconv('GBK', 'UTF8', $keywords);
        }
        if (EC_CHARSET == 'gbk' && !in_array($searchengine, $gb_search))
        {
            $keywords = ecs_iconv('UTF8', 'GBK', $keywords);
        }

		//if (strlen(trim($keywords))<100){
			//$GLOBALS['db']->autoReplace(KEYWORDS, array('date' => local_date('Y-m-d'), 'searchengine' => $searchengine, 'keyword' => addslashes($keywords), 'count' => 1), array('count' => 1));

			//$GLOBALS['db']->autoReplace(ABCKEYWORD, array('keyword' => trim($keywords), 'goods_num' => 0, 'count' => 1), array('count' => 1));
		//}

    }
}


function ecs_iconv($source_lang, $target_lang, $source_string = '')
{
    static $chs = NULL;

    /* 如果字符串为空或者字符串不需要转换，直接返回 */
    if ($source_lang == $target_lang || $source_string == '' || preg_match("/[\x80-\xFF]+/", $source_string) == 0)
    {
        return $source_string;
    }

    if ($chs === NULL)
    {
        require_once(ROOT_PATH . 'lib/cls_iconv.php');
        $chs = new Chinese(ROOT_PATH);
    }

    return $chs->Convert($source_lang, $target_lang, $source_string);
}

//发邮件
function send_email($email,$id = 13,$order_info = false,$password = ''){
	global $db, $_CFG, $cur_lang, $default_lang;
	$sql = "select template_id, template_subject,template_content  from ".Mtemplates." where template_id = '".$id."'";
	$mail_temp_arr = $db->selectinfo($sql);
	$mail_subject     = $mail_temp_arr['template_subject'];
	$mail_temp        = varResume($mail_temp_arr['template_content']);
	$template_id      = $mail_temp_arr['template_id'];
	// 多语言 fangxin 2013/07/05
	$sql       = "SELECT user_id, email, lang FROM ". USERS ." WHERE email = '". $email ."'";
	$user_info = $GLOBALS['db']->selectinfo($sql);
	$lang      = empty($cur_lang) ? (empty($user_info['lang']) ? $default_lang : $user_info['lang']) : $cur_lang;
	if($lang == $default_lang) {
		$sql = 'SELECT m.*' .
			   ' FROM ' . Mtemplates .' AS m' .
			   " WHERE m.template_id = '$template_id'";
	} else {
		$sql = 'SELECT m.*' .
			   ' FROM ' . Mtemplates . '_' . $lang .' AS m' .
			   " WHERE m.template_id = '$template_id'";
	}
	if($row_mail = $GLOBALS['db']->selectinfo($sql)) {
		$mail_subject = $row_mail['template_subject'];
		$mail_temp    = varResume($row_mail['template_content']);
	}

	$mail_temp        = str_replace('%7B$email%7D',md5($email),$mail_temp);
	if ($password != '')$password = ' '.$email.', password is '.$password.' and is ';
	$mail_temp        = str_replace('{$password}',$password,$mail_temp);
	switch($cur_lang) {
		case 'fr':
			$first_name = 'mon ami';
			break;
		case 'ru':
			$first_name = 'мой друг';
			break;
		case 'es':
			$first_name = 'mi amigo';
			break;
		case 'de':
			$first_name = 'mein freund';
			break;
		case 'pt':
			$first_name = 'meu amigo';
			break;
		default:
			$first_name = 'my friend';
			break;
	}
	if ($mail_subject == '')
		$mail_subject = 'Welcome to '.$_CFG['shop_name'];
	if (!$order_info){
		$mail_temp        = str_replace('{$firstname}',$first_name,$mail_temp);
		$mail_temp        = str_replace('{$email}',$email,$mail_temp);
	}else{
		$first_name = isset($order_info['firstname'])?$order_info['firstname']:'';
		$mail_temp_firstname = str_replace("&nbsp;", "", $first_name);
		$order_info['firstname'] = empty($mail_temp_firstname)?$first_name:$mail_temp_firstname;
		$order_info['order_no'] = empty($order_info['order_no'])?'':$order_info['order_no'];
		$order_info['order_id'] = empty($order_info['order_id'])?'':$order_info['order_id'];
		$order_info['Tracking_web'] = empty($order_info['Tracking_web'])?'':$order_info['Tracking_web'];
		$order_info['Tracking_NO'] = empty($order_info['Tracking_NO'])?'':$order_info['Tracking_NO'];

		$mail_temp        = str_replace('{$email}',$email,$mail_temp);
		$mail_temp        = str_replace('{$firstname}',$order_info['firstname'],$mail_temp);
		$mail_temp        = str_replace('{$firstname}',$order_info['firstname'],$mail_temp);
		$mail_temp        = str_replace('{$order_no}',$order_info['order_no'],$mail_temp);
		$mail_temp        = str_replace('$order_id$',$order_info['order_id'],$mail_temp);
		$mail_temp        = str_replace('$Tracking_web$',$order_info['Tracking_web'],$mail_temp);
		$mail_temp        = str_replace('$Tracking_NO$',$order_info['Tracking_NO'],$mail_temp);
		$mail_subject     = str_replace('{$order_no}',$order_info['order_no'],$mail_subject);
	}
	return exec_send2($email,$mail_subject,$mail_temp);
}


function exec_send($email,$mail_subject,$mail_body){
	global $_CFG, $_LANG;
	$From_mail        = 'support@Dealsmachine.com';
	if(empty($mail_subject))return ;
	require_once(ROOT_PATH.'Rmail/Rmail.php');
	$mail = new Rmail();
	$mail->setFrom('"Dealsmachine.com" <'.$From_mail.'>');
	$mail->setSubject($mail_subject);
	$mail->setHTML($mail_body);
	$mail->setHTMLCharset('utf-8');
	$mail->setHeadCharset('utf-8');
	if (defined('IS_LOCAL') ? IS_LOCAL : isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
	    $mail->setsmtpParams('192.168.3.3', 25, null, true, 'powerjj', 'jj4321');
	}
	else {
		$mail->setSMTPParams('localhost', 25, null, true, 'server', 'NhAkcHUo');//这里需要配置SMTP
	}
    $type="smtp";
	return $mail->send(array($email), $type);
}


/*
 * 发送邮件第二通道
 */
function exec_send2($email,$mail_subject,$mail_body){
	global $_CFG, $_LANG;
	$From_mail        = 'support@Dealsmachine.com';
	if(empty($mail_subject))return ;
	require_once(ROOT_PATH.'Rmail/Rmail.php');
	$mail = new Rmail();
	$mail->setFrom('"Dealsmachine.com" <'.$From_mail.'>');
	$mail->setSubject($mail_subject);
	$mail->setHTML($mail_body);
	$mail->setHTMLCharset('utf-8');
	$mail->setHeadCharset('utf-8');
	$mail->setSMTPParams('smtp.sendgrid.net', 25, null, true, 'support@ahappydeal.com', 'InwYRMMnZrlAQ');//这里需要配置SMTP
    $type="smtp";
	return $mail->send(array($email), $type);
	/*
	//暂时使用local发送 2015-05-22 PM
	global $_CFG, $_LANG;
	$From_mail        = 'support@Dealsmachine.com';
	if(empty($mail_subject))return ;
	require_once(ROOT_PATH.'Rmail/Rmail.php');
	$mail = new Rmail();
	$mail->setFrom('"Dealsmachine.com" <'.$From_mail.'>');
	$mail->setSubject($mail_subject);
	$mail->setHTML($mail_body);
	$mail->setHTMLCharset('utf-8');
	$mail->setHeadCharset('utf-8');
	$mail->setSMTPParams('localhost', 25, null, true, 'server', 'NhAkcHUo');//这里需要配置SMTP
    $type="smtp";
	return $mail->send(array($email), $type);
	*/
}

/**
    获取远程文件内容
    @param $url 文件http地址
*/
function fopen_url($url)
{
/*        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
	    curl_setopt($curl_handle, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curl_handle, CURLOPT_FAILONERROR,1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Trackback Spam Check');
        $file_content = curl_exec($curl_handle);
        curl_close($curl_handle);
    return $file_content;
*/
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_TIMEOUT, 1000);
	$file_contents = curl_exec($ch);
	curl_close($ch);
	return $file_contents;

}

function get_url_parameters($_TarArr,$_expArr = array()){
	$re_str = '';
	foreach($_TarArr as $key => $val){
		if(is_array($_TarArr[$key])){
			$re_str .= get_url_parameters($_TarArr[$key],$_expArr);
		}else{
			if(!in_array($key,$_expArr))
			$re_str .= '&'.$key.'='.$val;
		}
	}
	//&id=123&odr=pp
	//echo $re_str;
	return $re_str;
}

//从数组中提取指定的键名的值转换成字符串
//输出　如 "'1','2','3'" 的字符串
//
function arr2str($arr,$key){
	$str="";
	if(!empty($arr)&&is_array($arr)&&!empty($key)){

		$i=0;
		foreach ($arr as $k=>$v){
			//foreach ($v as $vv)
			$str.="'".$v[$key]."'";
			$i++;
			if($i<count($arr)){
				$str.=",";
			}
		}
	}else {
		$str="";
	}
	return $str;
}


//返回一个用$field来分组的数组
//@$arr ：数组
//@$field 下标的键名
function fetch_id($arr,$field){
	if(!empty($arr)&&is_array($arr)){
		$ref= array();
		$m="";
		foreach ($arr as $k=>$v){
			$ref[$v[$field]][] = $v ;
		}
	}else {
		$ref=array();
	}
	return $ref;

}



if (!function_exists('execute_time')) {
    /**
     * 返回执行时间，单位：秒
     *
     * @param int        $time_start    开始时间，默认：$GLOBALS['_BEGINTIME']
     * @param string     $unit          显示时间文字，默认空
     * @param int        $precision     小数点精度，默认：6
     */
    function execute_time($time_start = false, $unit = 's', $precision = 6) {
        $time_end = microtime(true);
        return round($time_end - ($time_start ? $time_start : $GLOBALS['_BEGINTIME']), $precision) . $unit;
    }
}

//把关键词转换成搜索的URL地址
function key_to_search_link($ks){
	if(!empty($ks)){
		//$ks=preg_replace('/[^\w\,]/','-',$ks);
		//$ks=preg_replace('/\-{2,}/','-',$ks);
		$arr=explode(',',$ks);
		$s="";
		foreach ($arr as  $k=>$v){
			$v=preg_replace('/[^\w\.]/','-',$v);
			$v=str_replace('amp','-',$v);
			$v=preg_replace('/\-{2,}/','-',$v);
			$s.="<p><a href='".DOMAIN."/Wholesale-$v.html'>$arr[$k]</a></p>,";

		}
		return $s;
	}



}

     /**
     * 获取指定分类最顶级父类id
     *
     * @param  int $cat_id 分类id
     *
     * @return int 最顶级父类id
     */
     function get_category_top_parent_id($cat_id) {
        $cat_arr = read_static_cache('category_children', 2);    //顶级分类
		$parent_id = $cat_id;
        if (isset($cat_arr[$cat_id])) {
            $parent_id = $cat_id;
        }
        else {

            foreach ($cat_arr as $k => $v) {    //查找最顶级parent_id

                if (in_array($cat_id, $v['children'])) {
                    $parent_id = $k;
                    break;
                }

            }
        }

        unset($cat_arr);

        return $parent_id;
    }

    /**
 * 连接从数据库
 *
 */
function get_slave_db() {
    $db = new MySql(DB_HOST_SLAVE, DB_USER_SLAVE, DB_PWD_SLAVE, DB_NAME_SLAVE);
    return $db;
}
/**
 * 清除购物车的优惠券
 * Enter description here ...
 */
function clearCoupon(){
	if(!empty($_SESSION['pcode_lv']))unset($_SESSION['pcode_lv']);
	if(!empty($_SESSION['pcode_code']))unset($_SESSION['pcode_code']);
	if(!empty($_SESSION['pcode']))unset($_SESSION['pcode']);
	if(!empty($_SESSION['pcode_goods']))unset($_SESSION['pcode_goods']);
	if(!empty($_SESSION['pcode_fangshi']))unset($_SESSION['pcode_fangshi']);
	if(!empty($_SESSION['total_price']))unset($_SESSION['total_price']);
}

/**
 * 开启页面CDN缓存函数
 */
function open_cdn_cache()
{
	header('Pragma: public');
	header('Cache-Control: public, max-age=86400');
	//header('Expires: ' . gmdate('D, d M Y H:i:s', time() - date('Z') + 86400) . ' GMT');
	header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 6*3600) . ' GMT');
}

/**
 * post 数据CDN服务器，清除页面CDN缓存
 * parm $url post地址
 * parm $data post数据
 */
function post_purge_cache($url,$data)
{
	//return true;
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
	$contents = curl_exec($ch);
	curl_close($ch);

    return $contents;
}

//前台分类名称
function get_groupbuy_cat_name($cat_id,$cat_name_en=''){
	global $cur_lang,$cur_lang_cat_arr,$cat_key_Arr;
	if($cur_lang == 'en') {
		$g_cur_lang = '';
	} else {
		$g_cur_lang = $cur_lang . '_';
	}
	if(empty($cur_lang_cat_arr))$cur_lang_cat_arr = read_static_cache($g_cur_lang.'category_c_key',2);
	if(empty($cur_lang_cat_arr)||empty($g_cur_lang)||$g_cur_lang == 'en'||empty($cur_lang_cat_arr["$cat_id"])){
		if(!empty($cat_name_en)){
			return  $cat_name_en;
		}
		else{
			if(empty($cat_key_Arr))$cat_key_Arr = read_static_cache($g_cur_lang.'category_c_key',2);
			return $cat_key_Arr["$cat_id"]['cat_name'];
		}
	}else {
		return $cur_lang_cat_arr["$cat_id"]['cat_name'];
	}

}

/**
 * 获取数据库实例
 *
 * @author          mashanling <msl-138@163.com>
 * @date            2013-07-24 11:31:41
 *
 * @param   string  $key    数据库标识key
 *
 * @return object   数据库实例
 */
function get_db($key = 'main') {

    if (IS_LOCAL || 'main' == $key) {
        return $GLOBALS['db'];
    }

    static $db = array();

    if (!isset($db[$key])) {

        switch ($key) {

            case 'abc'://abc
                $db[$key] = new MySql(DB_HOST_ABC, DB_USER_ABC, DB_PWD_ABC, DB_NAME_ABC);
                break;
        }
    }

    return $db[$key];
}//end get_db

/*
文件名替换
*/
function title_to_url_search($title, $extra = '')
{
    $url_title_temp = '';
    preg_match_all("/[0-9a-zA-Z{$extra}]{1,}/",$title,$match);
    $url_title_temp = join('-',$match[0]);
    $title = $url_title_temp;
    return $title;
}

/**
 * 获取关键字搜索链接 2012-06-04 10:24:27 by mashanling
 *
 * @param string $keyword     关键字
 * @param bool   $include_dot true包括.。默认true
 *
 * @return string 该关键字搜索链接
 */
function get_search_url($keyword, $include_dot = true) {
    //return '/Wholesale-' . title_to_url_search(ucwords($keyword), $include_dot ? '\.' : '') . '.html';
	return '/affordable-' . title_to_url_search(strtolower($keyword), $include_dot ? '\.' : '') . '/';
}

/**
 * 处理单复数,同义词，近义词
 * @param $keyword_str  关键词
 * Jim liang 2013-5-17
 *
 */
function keyword_Singular_plural($keyword_str){
    $samilar_key = read_static_cache('replace_keywords', FRONT_STATIC_CACHE_PATH);

	$data = read_static_cache('filter_search_keywords_cache');
	if(empty($data['dan_fu']))return $keyword_str;
	$samilar_key = $data['dan_fu'];
	foreach ($samilar_key as $v){
		//$v ='GT-N7100,GT-N7108,GALAXY Note2';
		$v = strtolower($v);
		$v = deal_keyword ($v);
		if(empty($v))continue;
		$v = preg_quote($v);
		$v1 = str_replace(',', '|', $v);
		$v = str_replace(',', ')|(', $v);
		$v ="($v)";

		if (preg_match("/\b$v\b/", $keyword_str)) {
			$keyword_str = preg_replace("/\b($v1)\b/", "$v","$keyword_str");
			//break;
		}

	}

	return $keyword_str;
}

/**
 * deal_keyword
 */
function deal_keyword($keyword) {
	$keyword       = str_replace(array('-', '_'), array(' ', '.'), $keyword);
	$keyword       = str_replace(array('drop ship', 'china','China','wholesale','cheap'),'', $keyword);
	$keyword       = str_replace(array('(', ')', '{', '}', '*', '+', '?', '!', '#', '^', '=', '%', "'", '"', ';', '<', '>', '\\', '/'), '', $keyword);
	$keyword       = preg_replace('/\s+/', ' ', $keyword);
	$keyword       = trim($keyword);
	return $keyword;
}

/**
 * 页脚帮助文档 2013-10-10 AM by fangxin
 *
 * @return string
 */
//
function get_foothelp_article() {
	global $db, $cur_lang, $default_lang;
	$sql = "select cat_name,cat_id from ".ARTICLECAT." where parent_id = 13 and cat_id in(24,25,26,27,28) ORDER BY sort_order,cat_id";
	$ArticleCatArr = $db -> arrQuery($sql);
	foreach ($ArticleCatArr as $k => $v){
		//多语语 fangxin 2013/07/17
		if($cur_lang != $default_lang) {
			$sql = "SELECT * FROM eload_article_cat_muti_lang WHERE cat_id = ". $v['cat_id'] ." AND lang ='". $cur_lang ."'";
			$lang_res = $db->selectInfo($sql);
			if($lang_res) {
				$ArticleCatArr[$k]["cat_name"] = $lang_res['cat_name'];
			}
		}
		//end
		$ArticleCatArr[$k]["_childlist"] = get_article_list($v['cat_id']);
	}
	return $ArticleCatArr;
	//$Arr['ArticleCatArr'] = $ArticleCatArr;
}





/**
 * session管理函数
 *
 * @author          mashanling <msl-138@163.com>
 * @date            2013-12-03 14:10:57
 *
 * @param mixed $name  session名称或session初始配置项
 * @param mixed $value session值。默认''
 * @param mixed $default 默认值。默认null
 *
 * @return mixed
 */
function session($name, $value = '', $default = null) {
    $prefix = '';//session前缀

    if ('' === $value) {

        if (null === $name) {//清空session

            if ($prefix) {
                unset($_SESSION[$prefix]);
            }
            else {
                $_SESSION = array();
            }
        }
        elseif ($prefix) {//获取session

            if (strpos($name, '.')) {
                list($a, $b) = explode('.', $name);
                return isset($_SESSION[$prefix][$a][$b]) ? $_SESSION[$prefix][$a][$b] : $default;
            }

            return isset($_SESSION[$prefix][$name]) ? $_SESSION[$prefix][$name] : $default;
        }
        else {

            if (strpos($name, '.')) {
                list($a, $b) = explode('.', $name);
                return isset($_SESSION[$a][$b]) ? $_SESSION[$a][$b] : $default;
            }

            return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
        }
    }//end if ('' === $value)
    elseif (null === $value) { //删除session

        if ($prefix) {
            unset($_SESSION[$prefix][$name]);
        }
        else {
            unset($_SESSION[$name]);
        }
    }
    else {//设置session
        if ($prefix) {

            if (!isset($_SESSION[$prefix])) {
                $_SESSION[$prefix] = array();
            }

            $_SESSION[$prefix][$name] = $value;
        }
        else {
            $_SESSION[$name] = $value;
        }
    }
}//end session

/**
 * 动态获取或设置值
 *
 * @author          mashanling <msl-138@163.com>
 * @date            2013-10-23 09:16:50
 *
 * @param mixed $name    配置名或配置数组，默认null
 * @param mixed $value   配置值，默认null
 * @param mixed $default 默认值，默认null
 *
 * @return mixed
 */
function C($name = null, $value = null, $default = null) {
    static $_config = array();

    if (empty($name)) {//无参数时获取所有
        return $_config;
    }

    if (is_string($name)) {//优先执行设置获取或赋值

        if (false === strpos($name, '.')) {
            $name = strtolower($name);

            if (null === $value) {
                return isset($_config[$name]) ? $_config[$name] : $default;
            }

            $_config[$name] = $value;

            return null;
        }

        //二维数组设置和获取支持
        $name    = explode('.', $name);
        $name[0] = strtolower($name[0]);

        if (null === $value) {
            return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : $default;
        }

        $_config[$name[0]][$name[1]] = $value;

        return null;
    }

    if (is_array($name)) {//批量设置
        return $_config = array_merge($_config, array_change_key_case($name));
    }
}//end C

/**
 *
 * 加解密字符串函数
 *
 * @author          mashanling <msl-138@163.com>
 * @date            2013-11-28 14:10:59
 *
 * @param string $str       字符串
 * @param string $type      ENCRYPT加密,DECRYPT解密
 * @param string $encrypt_type 加密类型
 *
 * @return string 经加密或解密后字符串
 */
function encrypt($str, $type = ENCRYPT, $encrypt_type = null) {
    static $encrypt = null;

    if (null === $encrypt_type) {
        $encrypt_type = ENCRYPT_TYPE_DEFAULT;
    }

    if (null === $encrypt) {
        $encrypt = new Encrypt();
    }

    $encrypt->_encrypt_type = $encrypt_type;

    if (ENCRYPT == $type) {
        return $encrypt->encode('' === $str ? ENCRYPT_EMPTY_STRING : $str, $encrypt_type);
    }
    else {
        $decode = $encrypt->decode($str, $encrypt_type);

        return ENCRYPT_EMPTY_STRING == $decode ? '' : $decode;
    }
}//end encrypt

/**
 * 自定义错误处理
 *
 * @author          mashanling <msl-138@163.com>
 * @date            2013-11-06 15:05:58
 *
 * @param int    $errno   错误号
 * @param string $errstr  错误信息
 * @param string $errfile 错误文件
 * @param int    $errline 错误文件行号
 * @param mixed  $vars    用户变量。默认''
 *
 * @return void 无返回值
 */
function error_handler($errno, $errstr, $errfile, $errline, $vars = '') {
    static $e = null;

    if (null === $e) {
        $e = Error::getInstance();
    }

    $e->errorHandler($errno, $errstr, $errfile, $errline, $vars);
}//end error_handler

/**
 * 自定义异常处理
 *
 * @author          mashanling <msl-138@163.com>
 * @date            2013-11-06 15:03:12
 *
 * @param object $e 异常
 *
 * @return void 无返回值
 */
function exception_handler($e) {
    $message = $e->__toString();

    error_handler(E_SYS_EXCEPTION, $e->getMessage(), $e->getFile(), $e->getLine(), '__' . $e->getTraceAsString());
}

/**
 *
 * register_shutdown_function脚本终止前回调函数
 *
 * @author          mashanling <msl-138@163.com>
 * @date            2013-11-06 15:06:07
 *
 * @return void 无返回值
 */
function fatal_error() {

    if ($limit_time = C('log_slowload')) {//记录页面执行时间
        G('start_time', $GLOBALS['_BEGINTIME']);

        $execute_time = G('start_time', 'end_time');

        if ($execute_time > $limit_time) {
            $error_log = sprintf(LOG_STRONG_FORMAT, $execute_time);

            G('start_mem', $GLOBALS['_BEGINMEM']);
            G('end_mem', memory_get_usage());

            $error_log .= PHP_EOL . '开始内存: ' . sprintf(LOG_STRONG_FORMAT, format_size(G('start_mem', null)));
            $error_log .= PHP_EOL . '结束内存: ' . sprintf(LOG_STRONG_FORMAT, format_size(G('end_mem', null)));
            $error_log .= PHP_EOL . '使用内存: ' . sprintf(LOG_STRONG_FORMAT, format_size(G('start_mem', 'end_mem')));
            $error_log .= PHP_EOL . '内存峰值: ' . sprintf(LOG_STRONG_FORMAT, format_size(memory_get_peak_usage()));

            if (function_exists('sys_getloadavg')) {//记录负载
                $error_log .= PHP_EOL . '系统负载: ' . var_export(sys_getloadavg(), true);
            }

            Logger::filename(LOG_SLOWLOAD);
            trigger_error($error_log);

        }
    }

    Logger::save();

    if ($e = error_get_last()) {
        error_handler($e['type'], $e['message'], $e['file'], $e['line']);
    }
}//end fatal_error

/**
 * 记录和统计时间
 *
 * @author          mashanling <msl-138@163.com>
 * @date            2013-11-06 15:12:25
 *
 * @param string $start 开始标识符
 * @param mixed  $end   结束标识符或结束时间。默认''
 * @param int    $dec   小数点精度。默认4
 *
 * @return mixed
 */
function G($start, $end = '', $dec = 4) {
    static $_info = array();

    if (is_numeric($end)) {//记录时间
        $_info[$start] = $end;
    }
    elseif (!empty($end)) {//统计时间

        if (!isset($_info[$end])) {
            $_info[$end] = microtime(true);
        }

        return number_format(($_info[$end] - $_info[$start]), $dec, '.', '');
    }
    elseif (null === $end) {//获取记录
        return $_info[$start];
    }
    else {//记录时间
        $_info[$start] = microtime(true);
    }
}

/**
 * 自动加载类库
 *
 * @author          mashanling <msl-138@163.com>
 * @date            2013-12-02 09:55:55
 *
 * @param string $class 类名
 *
 * @todo 更多路径下类自动加载
 *
 * @return bool true加载成功,否则false
 */
function autoload($class) {
    $autoload = C('autoload');

    if ($autoload && isset($autoload[$class])) {
        return require_once($autoload[$class]);
    }

    $filename = 'class.' . strtolower($class) . '.php';

    if (is_file($file = ROOT_PATH . 'lib/' . $filename)) {//lib目录下自动加载
        return require_once($file);
    }
    //todo 更多路径下类自动加载

    return false;
}

/**
 * 启动
 *
 * @author         mashanling <msl-138@163.com>
 * @date           2013-12-02 09:54:46
 *
 * @return void 无返回值
 */
function bootstrap() {
    require(ROOT_PATH . 'lib/class.error.php');
    require(ROOT_PATH . 'lib/class.logger.php');

    set_error_handler('error_handler');
    set_exception_handler('exception_handler');
    register_shutdown_function('fatal_error');

    spl_autoload_register('autoload');
    error_reporting(E_ALL);//错误报告

    if (DEBUG) {//本地开发环境
        ini_set('display_errors', 1);//显示错误
    }
    else {
        ini_set('display_errors', 0);
    }

    C('log_sql', IS_LOCAL);//本地环境下，默认记录sql
    C('log_slowload', SLOW_LOAD_TIME);  //页面执行时间开关
}//end bootstrap



/**
 * 格式化字节大小
 *
 * @author          mashanling <msl-138@163.com>
 * @date            2013-12-03 08:20:27
 *
 * @param int $filesize  文件大小，单位：字节
 * @param int $precision 小数点数。默认2
 *
 * @return string 带单位的文件大小
 */
function format_size($filesize, $precision = 2) {
    if ($filesize >= 1073741824) {
        $filesize = round($filesize / 1073741824 * 100) / 100;
        $unit     = 'GB';
    }
    elseif ($filesize >= 1048576) {
        $filesize = round($filesize / 1048576 * 100) / 100 ;
        $unit     = 'MB';
    }
    elseif($filesize >= 1024) {
        $filesize = round($filesize / 1024 * 100) / 100;
        $unit     = 'KB';
    }
    else {
        $filesize = $filesize;
        $unit     = 'Bytes';
    }

    return sprintf('%.' . $precision . 'f', $filesize) . ' ' . $unit;;
}

/**
 * 增加发邮件记录
 *
 * @param unknown_type $order
 */
function add_mail_log($data){
	global $db;
	if($data) {
		$db->autoExecute(Email_send_history,$data,'INSERT');  //记录发件历史
	}
}