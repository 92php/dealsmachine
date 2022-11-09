<!--#include file="inc/conn.asp"-->
<!--#include file="inc/myfunction.asp"-->
<!--#include file="inc/config.asp"-->
<!--#include file="inc/class_fun.asp"-->
<%
 'dtmStartTime   =   Timer 
'set rs_class1 = server.CreateObject("adodb.recordset")
''rs_class1.open "select anclassid,anclass from sh_sort where is_mini=0 or is_mini is null  order by anclassidorder",conn,3,1

'set rs_class2 = server.CreateObject("adodb.recordset")
'rs_class2.open "select nclassid,nclass,anclassid from sh_sort2 where  nclassid in(select distinct(nclassid) from sh_chanpin where able=1) order by nclassidorder",conn,3,1


set rs_new = conn.execute("select  top 6 * from (select top 50 bookname,bookid,shichangjia,bookpic from sh_chanpin,sh_sort where sh_sort.anclassid=sh_chanpin.anclassid and sh_sort.is_mini=1 and able=1 order by adddate desc)a order by newid()")
set rs_recent_order=conn.execute("select a.*,bookname from  (select top 100 bookid,country,province   from sh_action order by actionid desc)a,sh_chanpin where sh_chanpin.bookid=a.bookid order by newid()")
'set rs_freeship = conn.execute("select top 8 bookname,bookid,shichangjia,bookpic from sh_chanpin where isfreeship = 1 and is_show_home=1 order by newid()")

set rs_mini_class =server.CreateObject("adodb.recordset")
rs_mini_class.open ("select anclass,anclassid from sh_sort  where is_mini=1  order by anclassidorder asc"),conn,2,1

	'Response.Write( "Time-consuming "&formatnumber(dtmExecuteTime,2)&"s")  
	
	'--------------------------------------'

%>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="x-ua-compatible" content="ie=7" />
<%if en_lock=1 then%>
<script type="text/javascript" src="http://us02.lockview.cn/Js/lockview.js?uid=LK5249364"></script>
<%end if%>
<title>China Electronics Wholesale,China Phone,Dropship From China,MP3 player, MP4 player, CECT P168, CECT I9, China Copy,  MP4 player wholesale|Wholesale MP3|wholesale MP4 players|China MP3 player|China MP4 player|MP4  player|cheap MP3 player|cheap MP4|MP3 player wholesale|buy from China| DavisMicro</title>
<META name="keywords" content="<%=seo_keywords%>" >
<META name="description" content="<%=seo_desc%>" >
<LINK href="css/header.css" type="text/css" rel="stylesheet">
<LINK href="css/index.css" type="text/css" rel="stylesheet">
<LINK href="css/bottom.css" type="text/css" rel="stylesheet">
<link href="css/_proClassList.css" rel="stylesheet" type="text/css">
<link href="css/mn_proClassList.css" rel="stylesheet" type="text/css">
<link href="images/mn/image_switch.css" rel="stylesheet" type="text/css">
<script src="/js/getcookie.js"></script>
<style type="text/css">
#left_menu {
	position:absolute;
	left:0;
	top:30px;
	display:none;
	z-index:2009
}
</style>

</head>
<body>
<!--#include file="header.asp"-->
<div class="clear"></div>
<div id="sub_nav_wrap" >
  <!-- ####menu### -->
  <div style="z-index:100;" onmouseover="document.getElementById('left_menu').style.display='block';" onmouseout="document.getElementById('left_menu').style.display='none';">
    <div class="sub_nav_menu"  ><img src="images/sub_nanv_bg.jpg" /></div>
    <!--#include file="inc/Category.htm"-->
  </div>
  <!-- ####menu### -->
  <div class="sub_nav_title">
    <div class="subtitle"><a href="/">Home</a>&nbsp;»&nbsp;Mini Gadgets</div>
  </div>
</div>
<div class="clear">&nbsp;</div>
<div id="contentwrap">
  <div id="leftside">
    
    <div class="Policeswrap">
      <div class="centerbar190mn"> Mini Gadgets </div>
      <DIV class="menu33">
        <UL>
			<!--#include file="inc/mini_category.htm"-->
        </UL>
      </DIV>
    </div>
	
	<div class="clear"></div>	
	<div class="ad_pic"><img src="images/mn/1901.jpg"  /></div>
	<div class="clear"></div>
	<div class="ad_pic"><img src="images/mn/1902.jpg"  /></div>
	<div class="clear"></div>
	<div class="ad_pic"><img src="images/mn/1903.jpg"  /></div>	
	
    <div class="clear"></div>
 
    <div class="newprduct">
      <div class="leftbar"></div>
      <div class="centerbar190">
        <div class="h3title"> New Arrival </div>
        <div class="more"></div>
      </div>
      <div class="rightbar"></div>
      <div class="newprductw">
        <ul>
          <%while not rs_new.eof%>
          <li>
            <div class="npic"><a href="/products/product_<%=rs_new("bookid")%>.htm"><img alt="<%=rs_new("bookname")%>" src="<%=getPic(rs_new("bookpic"),"80")%>" /></a></div>
            <div class="ntitle"> <a title="<%=rs_new("bookname")%>" href="/products/product_<%=rs_new("bookid")%>.htm"><%=myleft(rs_new("bookname"),50)%></a> <br />
              <span t_type='price' USD='<%=rs_new("shichangjia")%>'>US $<%=formatnumber(rs_new("shichangjia"),2,-1)%></span> </div>
          </li>
          <%
		  
		  rs_new.movenext
		  wend
		  %>
        </ul>
      </div>
    </div>
    
    <div class="clear"></div>
	
		<div class="topsale">
          <div class="leftbar"></div>
          <div class="centerbar195">Recent Orders</div>
          <div class="rightbar"></div>
          <div class="rorders" style="height:310px; overflow:hidden">
             <ul>
              <%while not rs_recent_order.eof%>
              <li ><a href="/products/product_<%=rs_recent_order("bookid")%>.htm"><%=myleft(rs_recent_order("bookname"),38)%></a><br />
                ship to : <%=rs_recent_order("province")%>,<%=rs_recent_order("country")%></li>
              <%
	rs_recent_order.movenext
wend
%>
            </ul>

          </div>

    </div>
	
  </div>
  <div id="main_ganen">
  <div class="add796">
  
  
  
  
  
  <div class="container bannerwrap" id="idTransformView2">
        <ul class="slider slider2" id="idSlider2">
          <li id="i_1"><a href="/products/product_7460.htm" title="Hero G3 Smart Phone"><img alt="Hero G3 Smart Phone" src="images/mn/796ad.jpg" border="0"/></a></li>
          <li id="i_2"><a href="/products/product_4138.htm" title="i9 3GS phone"><img alt="i9 3GS phone" src="images/mn/796ad.jpg" border="0"/></a></li>
          <li id="i_3"><a href="/Electronic-Gadgets-st1-sid72.html" title="Electronic Cigarette Health Smoke"><img src="images/mn/796ad.jpg" border="0" alt="Electronic Cigarette Health Smoke"/></a></li>

        </ul>
        <ul class="num" id="idNum2">
          <li>1</li>
          <li>2</li>
          <li>3</li>
        </ul>
      </div>
  
  
  
  
  
  
  </div>
  <div class="clear"></div>
  <div class="mnnav7">
	<div class="mnnav11"><a href="#">1.99 Bargains</a></div>
	<div class="mnnav22">&nbsp;</div>
	</div>
  <div class="clear"></div>
  <div class="mnpro77">
	<ul>
	          
	<li class="mn10">
	<p class="mn10077" style="position:relative;"><a href="/products/product_13638.htm"><img src="images/mn/100100.gif" alt="USB Power Adapter Charger for  iPod iPhone 3G with Green Dot"/></a><em>$1.99</em></p>
	<p class="mn100mc"><a href="/products/product_13638.htm">USB Power Adapter Charger for  iPod iPhone 3G with Green Dot</a></p>
	<p class="mn100jg"><span usd="3.11" t_type="price" style="float: left;margin-top: 5px">US $3.11</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li>
	<p class="mn10077"><a href="/products/product_13520.htm"><img src="../bookpic/20104/Color-Hard-Back-Case-Cover-for-Apple-iPhone-3G-3GS-Yellow-thumb-G-2497.jpg" alt="Color Hard Back Case Cover for Apple iPhone 3G 3GS"/></a></p>
	<p class="mn100mc"><a href="/products/product_13520.htm">Color Hard Back Case Cover for Apple iPhone 3G 3GS</a></p>
	<p class="mn100jg"><span usd="2.05" t_type="price" style="float: left;margin-top: 5px">US $2.05</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	<li>
	<p class="mn10077"><a href="/products/product_13520.htm"><img src="../bookpic/20104/Color-Hard-Back-Case-Cover-for-Apple-iPhone-3G-3GS-Yellow-thumb-G-2497.jpg" alt="Color Hard Back Case Cover for Apple iPhone 3G 3GS"/></a></p>
	<p class="mn100mc"><a href="/products/product_13520.htm">Color Hard Back Case Cover for Apple iPhone 3G 3GS</a></p>
	<p class="mn100jg"><span usd="2.05" t_type="price" style="float: left;margin-top: 5px">US $2.05</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	<li>
	<p class="mn10077"><a href="/products/product_13438.htm"><img src="../bookpic/20104/Dock-Charger-Data-for-iPhone-3G-3GS-White-thumb-G-2515.jpg" alt="Dock Charger Data for iPhone 3G/3GS"/></a></p>
	<p class="mn100mc"><a href="/products/product_13438.htm">Dock Charger Data for iPhone 3G/3GS</a></p>
	<p class="mn100jg"><span usd="4.61" t_type="price" style="float: left;margin-top: 5px">US $4.61</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li>
	<p class="mn10077"><a href="/products/product_13594.htm"><img src="../bookpic/20105/Universal-i-smartphone-SIM-Unlock-Card-thumb-G-23066.jpg" alt="Universal i-smartphone SIM Unlock Card"/></a></p>
	<p class="mn100mc"><a href="/products/product_13594.htm">Universal i-smartphone SIM Unlock Card</a></p>
	<p class="mn100jg"><span usd="4.14" t_type="price" style="float: left;margin-top: 5px">US $4.14</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li>
	<p class="mn10077"><a href="/products/product_13625.htm"><img src="../bookpic/20104/Armband-Bag-Case-for-iPhone-3G-Black-thumb-G-2382.jpg" alt="Armband Bag Case for iPhone 3G"/></a></p>
	<p class="mn100mc"><a href="/products/product_13625.htm">Armband Bag Case for iPhone 3G</a></p>
	<p class="mn100jg"><span usd="4.61" t_type="price" style="float: left;">US $4.61</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li class="mn10">
	<p class="mn10077"><a href="/products/product_13589.htm"><img src="../bookpic/20104/Diamond-Pattern-Silicone-Case-for-iPhone-3G-Transparent-thumb-G-2343.jpg" alt="Diamond Pattern Silicone Case for iPhone 3G"/></a></p>
	<p class="mn100mc"><a href="/products/product_13589.htm">Diamond Pattern Silicone Case for iPhone 3G</a></p>
	<p class="mn100jg"><span usd="1.24" t_type="price" style="float: left;">US $1.24</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	<li>
	<p class="mn10077"><a href="/products/product_13520.htm"><img src="../bookpic/20104/Color-Hard-Back-Case-Cover-for-Apple-iPhone-3G-3GS-Yellow-thumb-G-2497.jpg" alt="Color Hard Back Case Cover for Apple iPhone 3G 3GS"/></a></p>
	<p class="mn100mc"><a href="/products/product_13520.htm">Color Hard Back Case Cover for Apple iPhone 3G 3GS</a></p>
	<p class="mn100jg"><span usd="2.05" t_type="price" style="float: left;">US $2.05</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	<li>
	<p class="mn10077"><a href="/products/product_13254.htm"><img src="../bookpic/20104/Dock-Charger-Data-for-iPhone-3G-3GS-Black-thumb-G-2736.jpg" alt="Lightweight Dock Charger Data for iPhone 3G/3GS"/></a></p>
	<p class="mn100mc"><a href="/products/product_13254.htm">Lightweight Dock Charger Data for iPhone 3G/3GS</a></p>
	<p class="mn100jg"><span usd="4.61" t_type="price" style="float: left;">US $4.61</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li>
	<p class="mn10077"><a href="/products/product_13941.htm"><img src="../bookpic/20104/Wallet-Leather-Case-Cover-for-iPod-Touch-iTouch-Black-thumb-G-2820.jpg" alt="Wallet Leather Case Cover for iPod Touch iTouch"/></a></p>
	<p class="mn100mc"><a href="/products/product_13941.htm">Wallet Leather Case Cover for iPod Touch iTouch</a></p>
	<p class="mn100jg"><span usd="3.95" t_type="price" style="float: left;">US $3.95</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li>
	<p class="mn10077"><a href="/products/product_13644.htm"><img src="../bookpic/20104/Stereo-Sound-Iphone-3G-Earphone-w-Microphone-White-thumb-G-2308.jpg" alt="Stereo Sound Iphone 3G Earphone w Microphone"/></a></p>
	<p class="mn100mc"><a href="/products/product_13644.htm">Stereo Sound Iphone 3G Earphone w Microphone</a></p>
	<p class="mn100jg"><span usd="3.04" t_type="price" style="float: left;">US $3.04</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li>
	<p class="mn10077"><a href="/products/product_13595.htm"><img src="../bookpic/20104/Portable-Bronzing-Hard-Back-Case-Cover-for-iPhone-3G-3GS-Silver-thumb-G-23067.jpg" alt="Portable Bronzing Hard Back Case Cover for iPhone 3G/3GS"/></a></p>
	<p class="mn100mc"><a href="/products/product_13595.htm">Portable Bronzing Hard Back Case Cover for iPhone 3G/3GS</a></p>
	<p class="mn100jg"><span usd="1.75" t_type="price" style="float: left;">US $1.75</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
		
	</ul>
	</div>
  
  
  
  <div class="clear"></div>  
    <div class="mnnav8">
	<div class="mnnav11"><a href="#">Ipad Iphone Accessories</a></div>
	<div class="mnnav22"><a href="#"><img border="0" alt="more" src="images/mn/mmr.gif"/></a></div>
	</div>
  <div class="clear"></div>
 <div class="mnpro7">
	<ul>
	          
	<li class="mn10 bg">
	<p class="mn100"><a href="/products/product_13418.htm"><img src="../bookpic/20104/Color-Hard-Back-Case-Cover-for-Apple-iPhone-3G-3GS-Orange-thumb-G-2599.jpg" alt="Color Hard Back Case Cover for Apple iPhone 3G 3GS"/></a></p>
	<p class="mn100mc"><a href="/products/product_13418.htm">Color Hard Back Case Cover for Apple iPhone 3G 3GS</a></p>
	<p class="mn100jg"><span usd="2.05" t_type="price" style="float: left;margin-top:5px;">US $2.05</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li class="bg">
	<p class="mn100"><a href="/products/product_13644.htm"><img src="../bookpic/20104/Stereo-Sound-Iphone-3G-Earphone-w-Microphone-White-thumb-G-2308.jpg" alt="Stereo Sound Iphone 3G Earphone w Microphone"/></a></p>
	<p class="mn100mc"><a href="/products/product_13644.htm">Stereo Sound Iphone 3G Earphone w Microphone</a></p>
	<p class="mn100jg"><span usd="3.04" t_type="price" style="float: left;margin-top:5px;">US $3.04</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li class="bg">
	<p class="mn100"><a href="/products/product_13673.htm"><img src="../bookpic/20104/Hard-Plastic-Cover-Skin-Case-with-Leopard-Image-for-iPhone-3G-thumb-G-2337.jpg" alt="Hard Plastic Cover/ Skin Case with Leopard Image for iPhone 3G"/></a></p>
	<p class="mn100mc"><a href="/products/product_13673.htm">Hard Plastic Cover/ Skin Case with Leopard Image for iPhone 3G</a></p>
	<p class="mn100jg"><span usd="1.85" t_type="price" style="float: left;margin-top:5px;">US $1.85</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li class="bg">
	<p class="mn100"><a href="/products/product_13638.htm"><img src="../bookpic/20104/USB-Power-Adapter-Charger-for-iPod-iPhone-3G-with-Green-Dot-thumb-G-2302.jpg" alt="USB Power Adapter Charger for  iPod iPhone 3G with Green Dot"/></a></p>
	<p class="mn100mc"><a href="/products/product_13638.htm">USB Power Adapter Charger for  iPod iPhone 3G with Green Dot</a></p>
	<p class="mn100jg"><span usd="3.11" t_type="price" style="float: left;margin-top:5px;">US $3.11</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li class="bg">
	<p class="mn100"><a href="/products/product_13591.htm"><img src="../bookpic/20104/Silicone-Skin-Case-Cover-for-Apple-iPhone-Black-thumb-G-2345.jpg" alt="Silicone Skin Case Cover for Apple iPhone"/></a></p>
	<p class="mn100mc"><a href="/products/product_13591.htm">Silicone Skin Case Cover for Apple iPhone</a></p>
	<p class="mn100jg"><span usd="1.7" t_type="price" style="float: left;margin-top:5px;">US $1.70</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
		<li class="bg">
	<p class="mn100"><a href="/products/product_13591.htm"><img src="../bookpic/20104/Silicone-Skin-Case-Cover-for-Apple-iPhone-Black-thumb-G-2345.jpg" alt="Silicone Skin Case Cover for Apple iPhone"/></a></p>
	<p class="mn100mc"><a href="/products/product_13591.htm">Silicone Skin Case Cover for Apple iPhone</a></p>
	<p class="mn100jg"><span usd="1.7" t_type="price" style="float: left; margin-top:5px;">US $1.70</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li class="mn10">
	<p class="mn100"><a href="/products/product_13222.htm"><img src="../bookpic/20104/Clear-Shield-Touch-Screen-Shell-Cover-for-iPhone-3G-Black-thumb-G-2805.jpg" alt="High Quality Clear Shield Touch Screen Shell Cover for iPhone 3G"/></a></p>
	<p class="mn100mc"><a href="/products/product_13222.htm">High Quality Clear Shield Touch Screen Shell Cover for iPhone 3G</a></p>
	<p class="mn100jg"><span usd="4.36" t_type="price" style="float: left;">US $4.36</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li>
	<p class="mn100"><a href="/products/product_13254.htm"><img src="../bookpic/20104/Dock-Charger-Data-for-iPhone-3G-3GS-Black-thumb-G-2736.jpg" alt="Lightweight Dock Charger Data for iPhone 3G/3GS"/></a></p>
	<p class="mn100mc"><a href="/products/product_13254.htm">Lightweight Dock Charger Data for iPhone 3G/3GS</a></p>
	<p class="mn100jg"><span usd="4.61" t_type="price" style="float: left;">US $4.61</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li>
	<p class="mn100"><a href="/products/product_13632.htm"><img src="../bookpic/20104/Silicone-Faceplate-Cover-Skin-Case-for-3G-Phone-White-thumb-G-2389.jpg" alt="Silicone Faceplate Cover/ Skin Case for 3G Phone"/></a></p>
	<p class="mn100mc"><a href="/products/product_13632.htm">Silicone Faceplate Cover/ Skin Case for 3G Phone</a></p>
	<p class="mn100jg"><span usd="2.11" t_type="price" style="float: left;">US $2.11</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li>
	<p class="mn100"><a href="/products/product_13625.htm"><img src="../bookpic/20104/Armband-Bag-Case-for-iPhone-3G-Black-thumb-G-2382.jpg" alt="Armband Bag Case for iPhone 3G"/></a></p>
	<p class="mn100mc"><a href="/products/product_13625.htm">Armband Bag Case for iPhone 3G</a></p>
	<p class="mn100jg"><span usd="4.61" t_type="price" style="float: left;">US $4.61</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li>
	<p class="mn100"><a href="/products/product_13189.htm"><img src="../bookpic/20104/Hard-Plastic-Cover-Skin-Case-with-Flower-Image-for-iPhone-3G-White-thumb-G-2771.jpg" alt="Hard Plastic Cover/ Skin Case with Flower Image is Molded Perfect-fit for iPhone 3G"/></a></p>
	<p class="mn100mc"><a href="/products/product_13189.htm">Hard Plastic Cover/ Skin Case with Flower Image is Molded Perfect-fit for iPhone 3G</a></p>
	<p class="mn100jg"><span usd="1.85" t_type="price" style="float: left;">US $1.85</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
		<li>
	<p class="mn100"><a href="/products/product_13591.htm"><img src="../bookpic/20104/Silicone-Skin-Case-Cover-for-Apple-iPhone-Black-thumb-G-2345.jpg" alt="Silicone Skin Case Cover for Apple iPhone"/></a></p>
	<p class="mn100mc"><a href="/products/product_13591.htm">Silicone Skin Case Cover for Apple iPhone</a></p>
	<p class="mn100jg"><span usd="1.7" t_type="price" style="float: left;">US $1.70</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li class="mn10">
	<p class="mn100"><a href="/products/product_13517.htm"><img src="../bookpic/20104/Telescope-for-Mobile-Phone-3G-Silver-thumb-G-2494.jpg" alt="Telescope for Mobile Phone 3G"/></a></p>
	<p class="mn100mc"><a href="/products/product_13517.htm">Telescope for Mobile Phone 3G</a></p>
	<p class="mn100jg"><span usd="11.64" t_type="price" style="float: left;">US $11.64</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li>
	<p class="mn100"><a href="/products/product_13438.htm"><img src="../bookpic/20104/Dock-Charger-Data-for-iPhone-3G-3GS-White-thumb-G-2515.jpg" alt="Dock Charger Data for iPhone 3G/3GS"/></a></p>
	<p class="mn100mc"><a href="/products/product_13438.htm">Dock Charger Data for iPhone 3G/3GS</a></p>
	<p class="mn100jg"><span usd="4.61" t_type="price" style="float: left;">US $4.61</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li>
	<p class="mn100"><a href="/products/product_13176.htm"><img src="../bookpic/20104/Crystal-Hard-Case-Skin-Cover-for-Apple-iPad-thumb-G-24493.jpg" alt="Crystal Hard Case Skin Cover for Apple iPad"/></a></p>
	<p class="mn100mc"><a href="/products/product_13176.htm">Crystal Hard Case Skin Cover for Apple iPad</a></p>
	<p class="mn100jg"><span usd="6.42" t_type="price" style="float: left;">US $6.42</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li>
	<p class="mn100"><a href="/products/product_13588.htm"><img src="../bookpic/20104/Hard-Plastic-Cover-Skin-Case-with-Heart-Image-for-iPhone-3G-Black-thumb-G-2342.jpg" alt="Hard Plastic Cover/ Skin Case with Heart Image for iPhone 3G"/></a></p>
	<p class="mn100mc"><a href="/products/product_13588.htm">Hard Plastic Cover/ Skin Case with Heart Image for iPhone 3G</a></p>
	<p class="mn100jg"><span usd="1.77" t_type="price" style="float: left;">US $1.77</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
	<li>
	<p class="mn100"><a href="/products/product_13441.htm"><img src="../bookpic/20104/Touch-Screen-for-iPhone-3GS-Black-thumb-G-2518.jpg" alt="Touch Screen for iPhone 3GS"/></a></p>
	<p class="mn100mc"><a href="/products/product_13441.htm">Touch Screen for iPhone 3GS</a></p>
	<p class="mn100jg"><span usd="18" t_type="price" style="float: left;">US $18.00</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	
		<li>
	<p class="mn100"><a href="/products/product_13591.htm"><img src="../bookpic/20104/Silicone-Skin-Case-Cover-for-Apple-iPhone-Black-thumb-G-2345.jpg" alt="Silicone Skin Case Cover for Apple iPhone"/></a></p>
	<p class="mn100mc"><a href="/products/product_13591.htm">Silicone Skin Case Cover for Apple iPhone</a></p>
	<p class="mn100jg"><span usd="1.7" t_type="price" style="float: left;">US $1.70</span><span style="width: 26px; float: right; margin-top: 5px;"><img alt="Free Shipping" src="images/mn/free.gif"/></span></p>
	</li>
	</ul>
	</div> 
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
 
    <div class="mainr11">
	<div class="mn595x">
	
      <%
i=1
while not rs_mini_class.eof%>
	
	<div class="mnnav<%response.Write(i mod 5) %>">
	<div class="mnnav11"><a  href="<%=class_link(rs_mini_class("anclass"))%>-st1-sid<%=rs_mini_class("anclassid")%>.html"><%=rs_mini_class("anclass")%></a></div>
	<div class="mnnav22"><a href="<%=class_link(rs_mini_class("anclass"))%>-st1-sid<%=rs_mini_class("anclassid")%>.html"><img src="images/mn/moremn.gif" alt="more" border="0"/></a></div>
	</div>
	<div class="clear"></div>
	<div class="mnpro">
	<ul>
	          <%
		  'response.End()
'		  	best_count=conn.execute("select count(*) from sh_chanpin where bestbook=1 and anclassid="&rs_mini_class("anclassid"))(0)
'		  	if best_count>=8 then
'				sql="select top 12 * from (select top "&best_count&" bookid,bookname,shichangjia,isfreeship,zhuang,pic_grid,bookpic, case when bestbook=1 then 1 else 0 end as s from sh_chanpin where anclassid="&rs_mini_class("anclassid") &" order by s desc)a  order by newid()"
'				set rs_pro=conn.execute(sql)
'			else
'				sql="select  * from (select top 12 bookid,bookname,shichangjia,pic_grid,bookpic,isfreeship, case when bestbook=1 then 1 else 0 end as s from sh_chanpin where anclassid="&rs_mini_class("anclassid") &" order by s desc)a order by newid()"
'
'				set rs_pro=conn.execute( sql)
'			end if
			sql="select top 15 * from (select top 30 bookid,bookname,shichangjia,isfreeship,zhuang,pic_grid,bookpic from sh_chanpin where able=1 and kucun>0 and anclassid="&rs_mini_class("anclassid") &" order by view_sort asc)a  order by newid()"
			set rs_pro=conn.execute(sql)
			ii=0
			while not rs_pro.eof
			ii=ii+1
			
			%>
	<li <%if ii mod 5 =1 then response.Write(" class='mn10'")%>>
	<p class="mn100"><a href="/products/product_<%=rs_pro("bookid")%>.htm"><img alt="<%=rs_pro("bookname")%>" src="<%=rs_pro("bookpic")%>" /></a></p>
	<p class="mn100mc"><a href="/products/product_<%=rs_pro("bookid")%>.htm"><%=myleft(rs_pro("bookname"),100)%></a></p>
	<p class="mn100jg"><span style="float:left" t_type='price'  USD='<%=rs_pro("shichangjia")%>'>US $<%=formatnumber(rs_pro("shichangjia"),2,-1)%></span><%if rs_pro("isfreeship")=1 then%><span style="width:26px; float:right; margin-top:5px;"><img src="images/mn/free.gif" alt="Free Shipping" /></span><%end if%></p>
	</li>
	<%
                rs_pro.movenext
            wend%>
	</ul>
	</div>
      <%
	 i=i+1
   rs_mini_class.movenext
wend

%>
		
	
	</div>
	
	
	
	
	
	
	
	
	
	
	
	

    </div>
	
	
	
	
	
    
  </div>
</div>
<div class="clear"></div>
<!--#include file="footer.asp"-->



</body>
</html>
<script language='javascript' type='text/javascript' src="/images/mn/image_switch.js" ></script>
<script type="text/javascript" src="/js/jquery-1.3.pack.js"></script>


<script src="/js/jquery.jcarousellite.js" type="text/javascript"></script>
