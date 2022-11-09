<!--#include file="inc/conn.asp"-->
<!--#include file="inc/config.asp"-->
<!--#include file="inc/myfunction.asp"-->
<%
                 

id=request("id")
if not isnumeric(id) or id="" then
	response.Write("System Error.")
	response.End()
end  if


sql="select * from sh_chanpin where bookid ="&id
set rs = server.CreateObject("adodb.recordset")
rs.open sql,conn,1,1

if rs.eof then
	response.Redirect("/")
	'response.Redirect("http://www.chinanewfashion.com/404.html")
end if

sql="select top 5 * from (select top 20  bookid ,bookname,bookpic,shichangjia from sh_chanpin  where able=1 and kucun>0 and anclassid = "&rs("anclassid")&" and bookid<>"&rs("bookid")&"  order by bookid)a  order by newid()"
'response.Write(sql)
set rs_ralated=conn.execute(sql)
set rs_best = conn.execute("select top 20 bookid,bookpic,shichangjia,bookname from sh_chanpin where anclassid="&rs("anclassid")&"  order by bestbook desc")

if trim(rs("keywords"))<>"" then
	seo_keywords=rs("keywords")
end if
if rs("brief")<>"" then
	seo_desc = rs("brief")
end if

sql="select top 5 * from (select top 20 bookid,bookname,shichangjia,bookpic from sh_chanpin where able=1 and kucun>0 and anclassid="&rs("anclassid")&" order by newid())a"
set rs_hot=conn.execute(sql)


'-----------------------T_listCategory begin-------------'
	T_listCategory="<a href='/'>Home</a>&nbsp; » &nbsp;"
  	theanclassid=rs("anclassid")
  	set rs2=server.createobject("adodb.recordset")
	rs2.open "select * from sh_sort where anclassid="&rs("anclassid"),conn,1,1
	if rs2.recordcount>0 then
		T_listCategory=T_listCategory&"<a href='/"&class_link(rs2("anclass"))&"-st1-sid"&rs2("anclassid")&".html'  >"&rs2("anclass")&"</a>&nbsp; » &nbsp;"
	end if
	rs2.close
	set rs2=server.createobject("adodb.recordset")
	rs2.open "select * from sh_sort2 where nclassid="&rs("nclassid"),conn,1,1
	if rs2.recordcount>0 then
		T_listCategory=T_listCategory&"<a href='/"&class_link(rs2("nclass"))&"-st2-sid"&rs("nclassid")&".html' >"&rs2("nclass")&"</a>"
	end if
	rs2.close
	set rs2=nothing	
'-----------------------T_listCategory end-------------'

'-----------------------T_available begin--------------'
if rs("kucun")>0 or rs("able")=0 then
      T_available=""	  
	  T_available=T_available&"Available"
else
      T_available=T_available&"Sorry,out of stock now"
end if
'-----------------------T_available end---------------'





%>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Buy China  wholesale - <%=rs("bookname")%></title>
<META content="<%=seo_keywords%>" name="keywords"/>
<META content="<%=seo_desc%>" name="Description"/>
<LINK href="/css/header.css" type="text/css" rel="stylesheet">
<LINK href="/css/index.css" type="text/css" rel="stylesheet">
<LINK href="/css/bottom.css" type="text/css" rel="stylesheet">
<link href="/css/_proClassList.css" rel="stylesheet" type="text/css">
<link href="/css/hidden_menu.css" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="/css/jquery.lightbox-0.5.css" media="screen" />
<script language="javascript" src="/js/highslide1.js" type="text/javascript"></script>
<script type="text/javascript" src="/js/GetCookie.js"></script>
<script type="text/javascript" src="/js/rating.js"></script>
<script type="text/javascript" src="/js/get_price.js"></script>
<script type="text/javascript" src="/js/jquery-1.3.pack.js" ></script>

</head>
<body>
<!--#include file="header.asp"-->
<div class="clear"></div>
<div class="carousel">
    <div><a class="prev" href="#">&nbsp;</a></div>
  <div class="jCarouselLite">
      <ul>
        <%while not rs_best.eof%>
        <li>
          <p><a href="/products/product_<%=rs_best("bookid")%>.htm"><img src="../<%=rs_best("bookpic")%>" alt="<%=rs_best("bookname")%>"  /></a></p>
          <p><span t_type='price' usd='<%=rs_best("shichangjia")%>'><%=formatnumber(rs_best("shichangjia"),2,-1)%> USD</span></p>
        </li>
        <%
				rs_best.movenext
			wend%>
      </ul>
  </div>
<div><a class="next" href="#">&nbsp;</a></div>
    
</div>
<div class="clear1"></div>
<div id="sub_nav_wrap" >
<!-- ####menu### -->
  <div onmouseover="document.getElementById('left_menu').style.display='block'" onmouseout="document.getElementById('left_menu').style.display='none'">
    <div class="sub_nav_menu" ><img src="/images/sub_nanv_bg.jpg" /></div>
	<!--#include file="inc/Category.htm"-->	        
  </div>
<!-- ####menu### -->  
  <div class="sub_nav_title">
    <div class="subtitle"><%=T_listCategory%></div>
</div>
</div>

<div class="clear1"></div>
<div id="addproductwrap" id="gallery">
  <div class="carybar"></div>
  <div class="addpw">
    <div class="apw_1" >
      <div class="add_p">
        <div class="add_pic" ><a href="../<%=rs("zhuang")%>" title="<%=rs("bookname")%>"  onclick="return false;"><img alt="<%=rs("bookname")%>" style='cursor:url(/images/zoomin.cur),pointer; outline: none'  id="mid_pic" src="<%=getpic(rs("zhuang"),250)%>" /></a></div>
      </div>

      <div class="add_view" >
        <p><a href="/<%=rs("zhuang")%>" ref="0" target=_blank name="a_mid_pic"  onclick="return hs.expand(this)" >View Original Picture</a></p>
      </div>
      <div class="add_lp" >
        <p class="add_pp" id="view_small_pic">
        <%
		a=0
		for i=1 to 5
			if rs("pic"&i)<>"" and not isNull(rs("pic"&i))  then	
				a=a+1	
		%>
        <a href="<%=rs("pic"&i)%>" target=_blank ref="1" title="<%=rs("bookname")%>"    ><img alt="<%=rs("bookname")%>"   mid_pic="<%=getpic(rs("pic"&i),250)%>" big_pic="<%=rs("pic"&i)%>"  src="<%=getpic(rs("pic"&i),50)%>" border="0" /></a>
              
         <%
		   end if
		 next
		 		 if a>0 and  a<5 then%>
         	<a href="<%=rs("zhuang")%>" target=_blank  ref="0" ><img alt="<%=rs("bookname")%>"  mid_pic="<%=getpic(rs("zhuang"),250)%>" big_pic="<%=rs("zhuang")%>"  src="<%=getpic(rs("bookpic"),50)%>" border="0" /></a>
        <%end if%>
		 
        </p>
      </div>
    </div>  
    <div class="apw_2">
      <p class="add_title"></p>
      <h1><%=rs("bookname")%></h1>
      <p style="font-size:9px;"><%if rs("codes")<>"" then response.Write("["&rs("codes")&"]")%></p>
      <p class="add_text"> <strong>Status :</strong> <%=T_available%> <br />
        <strong>Warranty :</strong> One Year <br />
        <strong>Rating:</strong> <img src="/images/xx.gif" />
<%if rs("isfreeship") then%><br /><img alt="Free Shipping" src="/images/freeshipping.gif" /><%end if%></p>
      <p class="add_table"></p>
      <table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#CCCCCC" style="font-size:11px;">
        <tr>
          <td width="51%" height="22" align="center" bgcolor="#f3f3f3"><strong>Quantity Range</strong></td>
          <td width="49%" align="center" bgcolor="#f3f3f3"><strong>Price</strong></td>
        </tr>
        <tr>
          <td height="22" align="left" bgcolor="#FFFFFF">&nbsp;1--5 </td>
          <th align="center" bgcolor="#FFFFFF"><span t_type='price' USD='<%=rs("shichangjia")%>'><%=formatnumber(rs("shichangjia"),2,-1)%> USD</span></th>
        </tr>
        <tr>
          <td height="22" align="left" bgcolor="#FFFFFF">&nbsp;6--20</td>
          <th align="center" bgcolor="#FFFFFF"><span t_type='price' USD='<%=rs("huiyuanjia")%>'><%=formatnumber(rs("huiyuanjia"),2,-1)%> USD</span></th>
        </tr>
        <tr>
          <td height="22" align="left" bgcolor="#FFFFFF">&nbsp;21--50</td>
          <th align="center" bgcolor="#FFFFFF">
         
			<script language="javascript">

					if((GetCookie("shopzhiwang","username")==null ||GetCookie("shopzhiwang","username")=="" )&&(GetCookie("shopzhiwang","shjianame")==""||GetCookie("shopzhiwang","shjianame")==null))
						{document.write("<a href='/login.asp' class='Home_feaproname'>Sign in</a>");}
						else
						{document.write(" <span t_type='price' USD='<%=rs("vipjia")%>'><%=formatnumber(rs("vipjia"),2,-1)%> USD</span>");}
					</script>          
         </th>
        </tr>
        <tr>
          <td height="22" align="left" bgcolor="#FFFFFF">&nbsp;50--max</td>
          <th align="center" bgcolor="#FFFFFF">
          
			<script language="javascript">
						if((GetCookie("shopzhiwang","username")==null ||GetCookie("shopzhiwang","username")=="" )&&(GetCookie("shopzhiwang","shjianame")==""||GetCookie("shopzhiwang","shjianame")==null))
						{document.write("<a href='/login.asp' class='Home_feaproname'>Sign in</a>");}
						else
						{document.write("<span t_type='price' USD='<%=rs("pifajia")%>'><%=formatnumber(rs("pifajia"),2,-1)%> USD</span>");}
					</script>          
          </th>
        </tr>
      </table>
	  <p class="favoic"><a ref="0" href="/wholesaleinquiry.asp?id=<%=id%>"><img src="/images/whosale1.gif" border="0" /></a></p>
    </div>
    <div class="apw_3">
      <div class="add_bg">
      	<form name="cart" action="/shopcart.asp" method="get">
        <input type="hidden" name="id" value="<%=id%>" />
        <input type="hidden" name="action" value="add" />
        <p class="add_price" ><span class="price" id="cart_price" t_type='price' USD='<%=rs("shichangjia")%>'>Loading...</span></p>
        <p class="qua">Quantity: </p>
        <p class="add-jj"><span><img id="cart_reduce" src="/images/jian.gif" /></span><span>
          <input name="quantity" id="quantity" type="text"  value="1" size="8" onkeyup="numbers(this);"/>
        </span><span><img id="cart_add" src="/images/jia.gif" /></span></p>
        <p class="qua1" id="items_total"> Loading...</p>
        <p class="add_btn">
          <input alt="Add to cart" type="image" src="/images/add.jpg"/>
        </p>
        </form>
      </div>
      <div class="add_bg2">
	  <a href="/favorites.asp?act=add&id=<%=id%>" ref="0"><img src="/images/addtof.gif" /></a><br />
	  <a href="/support/ShippingHandling.htm" ref="0"><img src="/images/shopping.gif" border="0" /></a><br />
        <a href="/support/PaymentMethods.htm" ref="0"><img src="/images/payment.gif" border="0" /></a><br />
		<a href="/support/contactus.htm" ref="0"><img src="/images/cotact.gif" border="0" /></a><br />
<a href="mailto:"><img src="/images/tell.gif" border="0" ref="0" /></a></div>
    </div>
  </div>
</div>
<div class="clear"></div>
<div id="addcontentwrap" class="gallery">
  <div class="addcontent_l">
    <div class="add_ptitle">
    Product Details
    </div>
    <div class="add_pdw">
      <div class="carybar"></div>
      <div class="add_pdcontent"><%=rs("bookcontent")%></div>
    </div>
    <div class="clear"></div>
    <div class="add_ptitle">
     Product Reviews
    </div>
    <div class="add_pdw">
    
      <div class="carybar"></div>
      <div class="reviews">
      	<form  action="/ReviewsSave.asp" method="post" >
			<input type="hidden" name="id" value="<%=id%>">  
            <input type="hidden" id="rates" name="pingji" value="4"/> 
           	<input type="hidden"  name="act" value="save"/>     
        <table id="tb_review" width="100%" border="0" cellspacing="0" cellpadding="3">
          <tr>
            <td height="38" colspan="2" align="left">Tell us what you think about this item. Write a comment on this product and share your opinion with other people. Please make sure that your review focus on this item.</td>
          </tr>
          <tr>
            <th width="12%"  align="right" valign="top"><span>*</span> Nickname :</th>
            <td width="88%" align="left"><div><input name="pinglunname" id="name" type="text"  value="" size="40"/></div><div id="name_tips" class="tips"> </div></td>
          </tr>
          <tr>
            <th align="right"><span>*</span> E_Mail :</th>
            <td align="left">
            	<div class="left">
                    <input id="email" name="email" type="text" size="40" >
                </div>
                <div id="email_tips" class="tips"></div>
            </td>
          </tr>
          <tr>
            <th  align="right">Rating : </th>
            <td id="rate_td">
               <img id="rate1"  src="/images/icon_star_2.gif" onMouseOver="rate_mouseover(1)" onMouseOut="rate_mouseout(1)" onClick="rate_chick(1)"><img id="rate2" src="/images/icon_star_2.gif" onMouseOver="rate_mouseover(2)" onMouseOut="rate_mouseout(2)" onClick="rate_chick(2)"><img id="rate3"  src="/images/icon_star_2.gif" onMouseOver="rate_mouseover(3)" onMouseOut="rate_mouseout(3)" onClick="rate_chick(3)"><img id="rate4" src="/images/icon_star_2.gif" onMouseOver="rate_mouseover(4)" onMouseOut="rate_mouseout(4)" onClick="rate_chick(4)"><img id="rate5" src="/images/icon_star_1.gif" onMouseOver="rate_mouseover(5)" onMouseOut="rate_mouseout(5)" onClick="rate_chick(5)">&nbsp;&nbsp;
               <label id="rating_tips" ></label>
            </td>
          </tr>
          <tr>
            <th  align="right"><span>*</span>Title :</th>
            <td align="left" nowrap="nowrap"><input name="pingluntitle" id="title" type="text" maxlength="100" size="50" /></td>
          </tr>
          <tr>
            <th align="right" valign="top"><span>*</span> Review :</th>
            <td align="left">
                <textarea   name="pingluncontent" id="reviewcontent"  onpropertychange="checkMaxInput(this,500)" oninput="checkMaxInput(this,500)" onpaste="checkMaxInput(this,500)" onKeyUp="checkMaxInput(this,500)"   maxlength="500"   style="width:400px; height:10em;"></textarea>
                <div id="content_tips" style="color:red;"></div>
                <div style="color:#333;font-size:11px; text-align:left;padding-right:4px;">Word count: <span id='summarytip' class="red">0</span>/500 &nbsp;characters</div>
                <div style="width:500px; font-size:11px; color:gray;"> - Enter between 4 to 500 characters. English only. 
                - We will reply to you in 36 hours</div>              
            </td>
          </tr>

          <tr>
            <td height="58" align="left">&nbsp;</td>
            <td align="left"><input type="image" name="submit" src="/images/rew.jpg" onClick="return checkallInfo()"></td>
          </tr>
        </table>
        </form>
      </div>
      
    </div>
  </div>
  <script type="text/javascript" src="/js/reviewCheck.js"></script>
  <div class="addcontent_r">
    <div class="newprduct">
      <div class="leftbar"></div>
      <div class="centerbar190">
        <div class="h3title">
       Hot goods
        </div>
      </div>
      <div class="rightbar"></div>
      <div class="newprductw">
        <ul>
        <% while not rs_hot.eof%>
          <li>
            <div class="npic"><a ref="0" href="/products/product_<%=rs_hot("bookid")%>.htm"><img alt="<%=rs_hot("bookname")%>" src="<%=getpic(rs_hot("bookpic"),"80")%>" /></a></div>
            <div class="ntitle">
              <h2><a href="/products/product_<%=rs_hot("bookid")%>.htm" title="Wholesale <%=rs_hot("bookname")%>"><%=myleft(rs_hot("bookname"),50)%></a></h2>
              <br />
              <span t_type='price' USD='<%=rs_hot("shichangjia")%>'><%=formatnumber(rs_hot("shichangjia"),2,-1)%> USD</span></div>
          </li>
		<% rs_hot.movenext
			wend	
		%>	
        </ul>
      </div>
    </div>
	<div class="clear"></div>
	<div class="newprduct">
      <div class="leftbar"></div>
      <div class="centerbar190">
        <div class="h3title">
         Related goods
        </div>
      </div>
      <div class="rightbar"></div>
      <div class="newprductw">
        <ul>
        <% while not rs_ralated.eof%>
          <li>
            <div class="npic"><a ref="0" href="/products/product_<%=rs_ralated("bookid")%>.htm"><img alt="<%=rs_ralated("bookname")%>" src="<%=getpic(rs_ralated("bookpic"),"80")%>" /></a></div>
            <div class="ntitle">
              <h2><a ref="0" href="/products/product_<%=rs_ralated("bookid")%>.htm" title="Wholesale <%=rs_ralated("bookname")%>"><%=myleft(rs_ralated("bookname"),50)%></a></h2>
              <br />
              <span t_type='price' USD='<%=rs_ralated("shichangjia")%>'><%=formatnumber(rs_ralated("shichangjia"),2,-1)%> USD</span></div>
          </li>
		<% rs_ralated.movenext
			wend	
		%>	
        </ul>
      </div>
    </div>
	
    <div class="clear"></div>
    <div class="Policeswrap">
      <div class="leftbar"></div>
      <div class="centerbar190">
       History
      </div>
      <div class="rightbar"></div>
      <div class="newprductw">
        <ul>
        <script src="/js/exchange_rate.js"></script>
		<script type="text/javascript" src="/js/change_price.js"></script>
       	  <script type="text/javascript" language="javascript"  src='/recent.asp'></script>
        </ul>
      </div>
    </div>
  </div>
</div>
<div class="clear"></div>

<div class="add_keywords"><strong>Keywords :</strong> <%=key_to_link(trim(rs("keywords")))%></div>

<script type="text/javascript">
	var price1=<%=rs("shichangjia")%>;
	var price2=<%=rs("huiyuanjia")%>;
	var price3=<%=rs("vipjia")%>;
	var price4=<%=rs("pifajia")%>;
</script>
<script type="text/javascript" src="/js/jquery.lightbox-0.5.min.js"></script>

<script type="text/javascript">
    $(function() {
        $('#gallery a').lightBox();
    });
	var img1=new Image();
	img1.src='/images/next.ani';
	var img2=new Image();
	img1.src='/images/pre.ani';
</script>
<script src="/js/jquery.jcarousellite.js" type="text/javascript"></script>
<script type="text/javascript" language="javascript"  src="/js/showitem.js"></script>
<!--#include file="footer.asp"--> 
</body>
</html>

