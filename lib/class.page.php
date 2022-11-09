<?
if (!defined('INI_WEB')){die('访问拒绝');}

/**
   require_once('../lib/class.page.php');
   $page=new page(array('total'=>1000,'perpage'=>20));
   echo 'mode:1<br>'.$page->show();
   echo '<hr>mode:2<br>'.$page->show(2);
   echo '<hr>mode:3<br>'.$page->show(3);
   echo '<hr>mode:4<br>'.$page->show(4);
   开启AJAX：
   $ajaxpage=new page(array('total'=>1000,'perpage'=>20,'ajax'=>'ajax_page','page_name'=>'test'));
   echo 'mode:1<br>'.$ajaxpage->show();
   采用继承自定义分页显示模式：
 */
class page
{
    /**
     * config ,public
     */
    var $page_name="page";//page标签，用来控制url页。比如说xxx.php?page=2中的PB_page
    var $next_page='Next»';//下一页
    var $pre_page='«Prev';//上一页
    var $first_page='«';//首页
    var $last_page='»';//尾页
    var $pre_bar='<<';//上一分页条
    var $next_bar='>>';//下一分页条
    var $go_to='Go to';
    var $format_left=' ';
    var $format_right=' ';
    var $is_ajax=false;//是否支持AJAX分页模式

    /**
     * private
     *
     */
    var $pagebarnum=6;//控制记录条的个数。
    var $totalpage=0;//总页数
    var $ajax_action_name='';//AJAX动作名
    var $nowindex=1;//当前页
    var $url="";//url地址头
    var $offset=0;
    var $perpage;
	var $creat = 0;   //增加静态URL


    /**
     * constructor构造函数
     *
     * @param array $array['total'],$array['perpage'],$array['nowindex'],$array['url'],$array['ajax']...
     */
    function page($array)
    {
        global $_LANG;
        $this->next_page = $_LANG["Page_Next"].'»';//下一页
        $this->pre_page  = '«'.$_LANG["Page_Prev"];//上一页
        $this->go_to     = $_LANG["go_to"];


        if(is_array($array)){
           if(!array_key_exists('total',$array))$this->error(__FUNCTION__,'need a param of total');
           $total=intval($array['total']);
           $perpage=(array_key_exists('perpage',$array))?intval($array['perpage']):10;
           $nowindex=(array_key_exists('nowindex',$array))?intval($array['nowindex']):'';
           $url=(array_key_exists('url',$array))?$array['url']:'';

           if (array_key_exists('pagebarnum', $array)) {
               $this->pagebarnum = $array['pagebarnum'];
           }
        }else{
           $total=$array;
           $perpage=10;
           $nowindex='';
           $url='';
        }
		$this->creat = empty($_GET["creat"])?0:$_GET["creat"];
        if((!is_int($total))||($total<0))$this->error(__FUNCTION__,$total.' is not a positive integer!');
        if((!is_int($perpage))||($perpage<=0))$this->error(__FUNCTION__,$perpage.' is not a positive integer!');
        if(!empty($array['page_name']))$this->set('page_name',$array['page_name']);//设置pagename
        $this->_set_nowindex($nowindex);//设置当前页
        $this->_set_url($url);//设置链接地址
        $this->totalpage=ceil($total/$perpage);
        $this->offset=($this->nowindex-1)*$this->perpage;
        if(!empty($array['ajax']))$this->open_ajax($array['ajax']);//打开AJAX模式
		$this->total = $total;
		$this->perpage = $perpage;
        $this->totalpage = $this->totalpage > 10 && !empty($_GET['is_new_abc']) ? 10 : $this->totalpage;		
    }
    /**
     * 设定类中指定变量名的值，如果改变量不属于这个类，将throw一个exception
     *
     * @param string $var
     * @param string $value
     */
    function set($var,$value)
    {
        if(in_array($var,get_object_vars($this)))
           $this->$var=$value;
        else {
            $this->error(__FUNCTION__,$var." does not belong to PB_Page!");
        }

    }
    /**
     * 打开倒AJAX模式
     *
     * @param string $action 默认ajax触发的动作。
     */
    function open_ajax($action)
    {
        $this->is_ajax=true;
        $this->ajax_action_name=$action;
    }
    /**
     * 获取显示"下一页"的代码
     *
     * @param string $style
     * @return string
     */
    function next_page($style='')
    {
        if($this->nowindex<$this->totalpage){
            return $this->_get_link($this->_get_url($this->nowindex+1),$this->next_page,$style);
        }
            return $this->_get_link($this->_get_url($this->nowindex),$this->next_page,$style);
    }

    /**
     * 获取显示“上一页”的代码
     *
     * @param string $style
     * @return string
     */
    function pre_page($style='')
    {
        if($this->nowindex>1){
            return $this->_get_link($this->_get_url($this->nowindex-1),$this->pre_page,$style);
        }
            return $this->_get_link($this->_get_url(1),$this->pre_page,$style);
    }

    /**
     * 获取显示“首页”的代码
     *
     * @return string
     */
    function first_page()
    {
        if($this->nowindex==1){
        return $this->_get_link($this->_get_url(1),$this->first_page);
        }
        return $this->_get_link($this->_get_url(1),$this->first_page);
    }

    /**
     * 获取显示“尾页”的代码
     *
     * @return string
     */
    function last_page()
    {
        if(($this->nowindex==$this->totalpage) || ($this->totalpage == 0)){
             return $this->_get_link($this->_get_url($this->totalpage),$this->last_page);
       }else{
            return $this->_get_link($this->_get_url($this->totalpage),$this->last_page);
		}
    }

   //abc 专用
   function nowbar2($style='',$nowindex_style='')
    {

    	$this->totalpage =10;
        $plus=ceil($this->pagebarnum/2);
        if($this->pagebarnum-$plus+$this->nowindex>$this->totalpage)$plus=($this->pagebarnum-$this->totalpage+$this->nowindex);
        $begin=$this->nowindex-$plus+1;
        $begin=($begin>=1)?$begin:1;
        $return='';
        if($style=='share' && $this->nowindex>=4){
            $return .= '<a '.$style.' href="'.$this->_get_url(1).'" >1</a>';
            $return .= '<span  >..</span>';
        }
        for($i=$begin;$i<$begin+$this->pagebarnum;$i++)
        {
            if($i<=$this->totalpage){
                if($i!=$this->nowindex)
                   $return.=$this->_get_link($this->_get_url($i),$i,$style);
                else
                    $return.=$this->_get_text('<span class="current">'.$i.'</span>');
            }else{
                break;
            }
            $return.="\n";
        }
        if($style=='share' && $this->totalpage>5){
            $total_page = $this->totalpage;
            $return .= '<span  >..</span>';
            $return .= '<a '.$style.' href="'.$this->_get_url($total_page).'" >'.$total_page.'</a>';
        }
        unset($begin);
        return $return;
    }    
    function nowbar($style='',$nowindex_style='')
    {

        $plus=ceil($this->pagebarnum/2);
        //if($this->pagebarnum-$plus+$this->nowindex>$this->totalpage)$plus=($this->pagebarnum-$this->totalpage+$this->nowindex);
        $begin=$this->nowindex-$plus+2;
        $begin=($begin>=1)?$begin:1;
        $return='';
		if($style=='share' && $this->nowindex>=4){
            $return .= '<a '.$style.' href="'.$this->_get_url(1).'" >1</a>';
            $return .= '<span  >..</span>';
        }
        for($i=$begin;$i<$begin-3+$this->pagebarnum;$i++)
        {
            if($i<=$this->totalpage){
                if($i!=$this->nowindex)
                   $return.=$this->_get_text($this->_get_link($this->_get_url($i),$i));
                else
                   // $return.=$this->_get_text('<a class="r" href="'.$this->_get_url($i).'">'.$i.'</a>');
                    $return.=$this->_get_text('<span class="current">'.$i.'</span>');
            }else{
                break;
            }
            $return.="\n";
        }
		if($style=='share' && $this->totalpage>5){
            $total_page = $this->totalpage;
            $return .= '<span  >..</span>';
            $return .= '<a '.$style.' href="'.$this->_get_url($total_page).'" >'.$total_page.'</a>';
        }
        unset($begin);
        return $return;
    }

	//全显示
    function nowbar_all($style='',$nowindex_style='')
    {

        $return='';
        for($i=1;$i<$this->totalpage;$i++)
        {
            if($i<=$this->totalpage){
                if($i!=$this->nowindex)
                   $return.=$this->_get_text($this->_get_link($this->_get_url($i),$i));
                else
                   $return.=$this->_get_text('<a class="r" href="'.$this->_get_url($i).'">'.$i.'</a>');
            }else{
                break;
            }
            $return.="\n";
        }
        return $return;
    }



    /**
     * 获取显示跳转按钮的代码
     *
     * @return string
     */
    function select($style='')
    {
    	global $cur_lang;
    	$this_url = $this->url;
    	if(!empty($cur_lang)&&$cur_lang!='en')$this_url = "/$cur_lang$this_url";
	   if($this->is_ajax){
			 //$return='<select name="PB_Page_Select" onchange="javascript:'.$this->ajax_action_name.'(\''.$this->url.'\'+this.options[this.selectedIndex].value);">';
			 $return='<select id="PB_Page_Select" atr="'.$this_url.'"  class="'.$style.'">';
       }else{
			if (strpos(realpath('./'),'eload_admin') === false && empty($_GET['x'])){;
				$return='<select id="PB_Page_Select"  onchange="window.location.href=this.options[this.selectedIndex].value" class="'.$style.'">';
			}else{
				$return='<select id="PB_Page_Select"  onchange="window.location.href=this.options[this.selectedIndex].value" class="'.$style.'">';
			}
        }
		$page_num = 40;

        $plus=ceil($page_num/2);
        if($page_num-$plus+$this->nowindex>$this->totalpage) $plus=($page_num-$this->totalpage+$this->nowindex);
        $begin=$this->nowindex-$plus+1;
        $begin=($begin>=1)?$begin:1;

		if ( $begin > 2 ) $return.='<option value="'.$this->_get_url(1).'">1</option>';

        for($i=$begin;$i<=$begin+$page_num;$i++)
        {
			 if($i<=$this->totalpage){
					if($i==$this->nowindex){
						$return.='<option value="'.$this->_get_url($i).'" selected>'.$i.'</option>';
					}else{
						$return.='<option value="'.$this->_get_url($i).'">'.$i.'</option>';
					}
			 }else{
				 break;
			 }
        }

		if ( $this->totalpage > $page_num ) $return.='<option value="'.$this->_get_url($i).'">'.$this->totalpage.'</option>';

        unset($i);
        $return.='</select>';
        return $return;
    }

    /**
     * 获取mysql 语句中limit需要的值
     *
     * @return string
     */
    function offset()
    {
        return $this->offset;
    }

    /**
     * 控制分页显示风格（你可以增加相应的风格）
     *
     * @param int $mode
     * @return string
     */
    function show($mode=1,$style='')
    {
        switch ($mode)
        {
            case '1': //后台样式
				$this->next_page='下一页';
				$this->pre_page='上一页';
				$this->first_page='首页';
				$this->last_page='尾页';
               return  $this->__PerStr() . $this->first_page().' '.$this->pre_page().' '.$this->nowbar('','red').' '.$this->next_page().' '.$this->last_page().' 第'.$this->select().'页';
                break;

            case '2':  //ABC索引样式
                return  '<p class="listspan">'.$this->nowbar_all('','red').'</p>';
                break;
            case '3':
                $this->next_page='下一页';
                $this->pre_page='上一页';
                $this->first_page='首页';
                $this->last_page='尾页';
                return $this->first_page().$this->pre_page().$this->next_page().$this->last_page();
                break;
            case '4':
                $this->next_page='Next&raquo;';
                $this->pre_page='&laquo;Prev';
                return $this->pre_page().$this->nowbar().$this->next_page();
                break;

            case '5': //skin2产品列表样式

               return   '<p class="listspan">'.$this->first_page().' '.$this->pre_page().' '.$this->nowbar().' '.$this->next_page().' '.$this->last_page().'</p> <p>'.$this->go_to.':</p><p class="gotoselect">'.$this->select('lie_sls').'</p>';
                break;
            case '6':   //skin1的产品列表样式
                return   $this->first_page('p_first','p_first2').' '.$this->pre_page('p_per','p_per2').' '.$this->nowbar('p_index','p_now').' '.$this->next_page('p_next','p_next2').' '.$this->last_page('p_last','p_last2').' <span style="float:left;"> <span style="float:left; line-height:20px; height:20px;">Go to:</span>'.$this->select('lie_sls').'</span>';
                break;
            case '7':
                $this->next_page='&raquo;';
                $this->pre_page='&laquo;';
                return $this->pre_page($style).$this->nowbar($style).$this->next_page($style);
                break;
            case 'new_abc'://新abc索引
                $this->next_page='Next&raquo;';
                $this->pre_page='&laquo;Prev';
                return $this->nowbar2();
                break;   
            case 'omit'://缩略形式,如1 ... 5 6 7 ... 26 下一页
                return $this->_getHtmlModeOmit();
                break;            
        }




    }





/*----------------private function (私有方法)-----------------------------------------------------------*/
    /**
     * 设置url头地址
     * @param: String $url
     * @return boolean
     */
    function _set_url($url="")
    {
    	global  $cur_lang_url;
	    $_SERVER['REQUEST_URI'] = empty($_SERVER['HTTP_X_REWRITE_URL'])?(empty($_SERVER['REQUEST_URI'])?'':$_SERVER['REQUEST_URI']):$_SERVER['HTTP_X_REWRITE_URL'];
		//apache user   $_SERVER['REQUEST_URI'] iis use  $_SERVER['HTTP_X_REWRITE_URL'];
        if(!empty($url)){
            //手动设置

            $this->url=$url.((stristr($url,'?'))?'&':'?').$this->page_name."=";
        }else{
            //自动获取
            if(empty($_SERVER['QUERY_STRING'])){
                //不存在QUERY_STRING时
                $this->url=$_SERVER['REQUEST_URI']."?".$this->page_name."=";
            }else{
            	
                if(stristr($_SERVER['QUERY_STRING'],$this->page_name.'=')){
               
                    //地址存在页面参数
                    $this->url=str_replace($this->page_name.'='.$this->nowindex,'',$_SERVER['REQUEST_URI']);
         
                    $last=$this->url[strlen($this->url)-1];
                
                    if($last=='?'||$last=='&'){
                        $this->url.=$this->page_name."=";
                    }else{
                        $this->url.='&'.$this->page_name."=";
                    }
                }else{
                	
				    $linkfu = (strpos($_SERVER['REQUEST_URI'],'?')===false)?'?':'&';
                    $this->url=$_SERVER['REQUEST_URI'].$linkfu.$this->page_name.'=';
                    
                    
                }
            }
        }


        
        
		if (strpos(realpath('./'),'eload_admin') === false && empty($_GET['x'])){;
			$this->url = '/'.substr($this->url,1, (strpos($this->url, '.htm')-1)).'-page';
			if (strpos($this->url,'page-')>0){
				$this->url = substr($this->url,0, (strpos($this->url, 'page-')-1)).'-page';
			}
		}
		

    }

    /**
     * 设置当前页面
     *
     */
    function _set_nowindex($nowindex)
    {
        if(empty($nowindex)){
            //系统获取
            if(isset($_GET[$this->page_name])){
                $this->nowindex=intval($_GET[$this->page_name]);
            }
        }else{
            //手动设置
            $this->nowindex=intval($nowindex);
        }
    }

    /**
     * 为指定的页面返回地址值
     *
     * @param int $pageno
     * @return string $url
     */
    function _get_url($pageno=1)
    {
        global $cur_lang_url,$cur_lang;
        $sortby = empty($_GET['sortby'])?'':$_GET['sortby'];
        $_24h_ship = empty($_GET['24h_ship'])?'':$_GET['24h_ship'];
        $freeship = empty($_GET['freeship'])?'':$_GET['freeship'];
        $display = empty($_GET['display'])?'':$_GET['display'];
        $page_size = empty($_GET['page_size'])?'':$_GET['page_size'];
        $url_para='?';
        if($sortby){
            $url_para .="sortby=$sortby";
        }
        if($_24h_ship){
            if($url_para == '?'){
                $url_para .="24h_ship=$_24h_ship";
            }else{
                $url_para .="&24h_ship=$_24h_ship";
            }
        }
        if($display){
            if($url_para == '?'){
                $url_para .="display=$display";
            }else{
                $url_para .="&display=$display";
            }
        }
        if($freeship){
            if($url_para == '?'){
                $url_para .="freeship=$freeship";
            }else{
                $url_para .="&freeship=$freeship";
            }
        }           
        if($page_size){
            if($url_para == '?'){
                $url_para .="page_size=$page_size";
            }else{
                $url_para .="&page_size=$page_size";
            }
        }
        if (strpos(realpath('./'),'eload_admin') === false  && empty($_GET['x'])){
            if($pageno == 1){
                $u= $this->url;
                if(strpos($_SERVER['REQUEST_URI'],'producttag') !== false){
                    return str_replace('-page','',$this->url);//return $this->url;
                }else {
                    $u = str_replace('-page','',$u);//return $this->url;
                    $u = preg_replace("/\/(\d+)/","/",$u);
                }
            }else{  
                $this->url = str_replace('-page','',$this->url);              
                $this->url = preg_replace("/\/(\d+)/","/",$this->url);
                $u= $this->url.$pageno.URLSUFFIX;
            }               
            if($url_para !='?'){
                $u .= "$url_para";
            }                  
            $u = "$u";
            return $u;
        }else{
            if($cur_lang_url&&strpos($this->url,"$cur_lang_url") ===false){
                return "/".$this->url.$pageno;
            }
            else {
                return $this->url.$pageno;
            }
        }
    } 

    /**
     * 获取分页显示文字，比如说默认情况下_get_text('<a href="">1</a>')将返回[<a href="">1</a>]
     *
     * @param String $str
     * @return string $url
     */
    function _get_text($str)
    {
        return $this->format_left.$str.$this->format_right;
    }

    /**
      * 获取链接地址
    */
    function _get_link($url,$text,$style=''){
    	
        $style=(empty($style))?'':'class="'.$style.'"';
        if($this->is_ajax){
            //如果是使用AJAX模式
            return '<a '.$style.' href="javascript:;" atr="'.$url.'">'.$text.'</a>';
           //return '<a '.$style.' href="javascript:'.$this->ajax_action_name.'(\''.$url.'\')">'.$text.'</a>';
        }else{
            return '<a '.$style.' href="'.$url.'" >'.$text.'</a>';

        }
    }
    /**
      * 出错处理方式
    */
    function error($function,$errormsg)
    {
        die('Error in file <b>'.__FILE__.'</b> ,Function <b>'.$function.'()</b> :'.$errormsg);
    }

    /**
      * 设置每页多少个
    */
    function __PerStr()
    {
	    return '总计'.$this->total.'个记录  共'.$this->totalpage.'页  每页：<input atr="'.$this->url.$this->nowindex.'" type="text" size="2" id="pageSize" value="'.$this->perpage.'" title = "按回车键提交所设置的每页的记录数"/> ';
    }
    
    /**
      * ABCLIST页
    */
    function __PerABCStr()
    {
	    return 'page '.$this->nowindex.' of '.$this->totalpage.'&nbsp;&nbsp;';
    }
    /**
     * 前后缩略分页模式,如: 上一页 1 ... 5 6 7 ... 26 下一页
     *
     * @author          mrmsl <msl-138@163.com>
     * @date            2013-10-14 14:54:20
     *
     * @return string 分页html
     */
    private function _getHtmlModeOmit() {

        if ($this->totalpage < 2) {
            return '';
        }

        $html           = '<p class="listspan">';
        $prev_page      = $this->nowindex - 1;//上页
        $next_page      = $this->nowindex + 1 > $this->totalpage ? $this->totalpage : $this->nowindex + 1;//下页
        $pages          = $this->_omit_show_pages_num - 2;//中间显示页数-2
        $pages_side     = floor($pages / 2); //当前页左右两边显示数,如显示7,则当前页左右各有2个,如当前7 => 1 ... 5 6 7 8 9 ... 26

        if (0 == $pages % 2) {//偶数个,左边显示数-1;
            $offset_left = $pages_side - 1;
        }
        else {
            $offset_left = $pages_side;
        }

        $offset_right   = $pages_side;//当前页右侧显示数

        if (1 == $this->nowindex) {//第一页
            $html .= $this->_get_text('<span class="text prev">' . $this->pre_page . '</span>');
            $html .= $this->_get_text('<span class="current">1</span>');
        }
        else {
            $html .= $this->pre_page();
            $html .= $this->_get_link($this->_get_url(1), 1);
        }

        if ($this->totalpage <= $this->_omit_show_pages_num) {//总页数小于显示页数,如共5页,显示7个,则全部显示
            $from = 2;
            $to   = $this->totalpage - 1;
        }
        else {
            $from  = $this->nowindex - $offset_left;//中间开始页数
            $to    = $this->nowindex + $offset_right;//中间结束页数

            //如共6,显示5,当前2 => 1 2 3 4 ... 6
            if ($to < $pages + 1) {
                $from = 2;
                $to   = $pages + 1;
            }
            else {//如共6,显示5,当前5 => 1 ... 3 4 5 6
                $from = $this->totalpage - $pages < $from ? $this->totalpage - $pages : $from;
                $from = $from > 1 ? $from : 2;
                $to   = $to > $this->totalpage - 1 ? $this->totalpage - 1 : $to;//最多至最后页-1
            }

            $html .= $from > 3 ? $this->_get_text('<span class="text">...</span>') : '';


            //处理间隔相差1情况,如
            //1 ... 3 4 6 ... 10 => 1 2 3 4 ... 10
            if (3 == $from) {
                $from = 2;
            }

            //1 ... 6 7 8 ... 10 => 1 ... 6 7 8 9 10
            if (2 == $this->totalpage - $to) {
                $to += 1;
            }
        }

        for ($i = $from; $i <= $to; $i++) {
            $html .= $i == $this->nowindex ? $this->_get_text('<span class="current">' . $i . '</span>') : $this->_get_link($this->_get_url($i), $i);
        }

        $html .= $to > $this->totalpage - 3 ? '' : $this->_get_text('<span class="text">...</span>');

        if ($this->nowindex == $this->totalpage) {
            $html .= $this->_get_text('<span class="current">' . $this->totalpage . '</span>');
            $html .= $this->_get_text('<span class="text">' . $this->next_page . '</span>');
        }
        else {
            //倒数第四页后或总页数小于显示数
            if ($this->totalpage <= $this->_omit_show_pages_num || $this->totalpage - $this->nowindex < 4) {
                $html .= $this->_get_link($this->_get_url($this->totalpage), $this->totalpage);
            }
            else {
                $html .= '<span class="disabled">' . $this->totalpage . '</span>';
            }

            $html .= $this->next_page('next');
        }

        return $html . '</p>';
    }//end _getHtmlModeOmit
    
    
    

}
?>