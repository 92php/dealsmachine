var Browser = new Object();

Browser.isMozilla = (typeof document.implementation != 'undefined') && (typeof document.implementation.createDocument != 'undefined') && (typeof HTMLDocument != 'undefined');
Browser.isIE = window.ActiveXObject ? true : false;
Browser.isFirefox = (navigator.userAgent.toLowerCase().indexOf("firefox") != - 1);
Browser.isSafari = (navigator.userAgent.toLowerCase().indexOf("safari") != - 1);
Browser.isOpera = (navigator.userAgent.toLowerCase().indexOf("opera") != - 1);

var Utils = new Object();

Utils.htmlEncode = function(text)
{
  return text.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

Utils.trim = function( text )
{
  if (typeof(text) == "string")
  {
    return text.replace(/^\s*|\s*$/g, "");
  }
  else
  {
    return text;
  }
}

Utils.isEmpty = function( val )
{
  switch (typeof(val))
  {
    case 'string':
      return Utils.trim(val).length == 0 ? true : false;
      break;
    case 'number':
      return val == 0;
      break;
    case 'object':
      return val == null;
      break;
    case 'array':
      return val.length == 0;
      break;
    default:
      return true;
  }
}

Utils.isNumber = function(val)
{
  var reg = /^[\d|\.|,]+$/;
  return reg.test(val);
}

Utils.isInt = function(val)
{
  if (val == "")
  {
    return false;
  }
  var reg = /\D+/;
  return !reg.test(val);
}

Utils.isEmail = function( email )
{
  var reg1 = /([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)/;

  return reg1.test( email );
}

Utils.isTel = function ( tel )
{
  var reg = /^[\d|\-|\s|\_]+$/; //只允许使用数字-空格等

  return reg.test( tel );
}

Utils.fixEvent = function(e)
{
  var evt = (typeof e == "undefined") ? window.event : e;
  return evt;
}

Utils.srcElement = function(e)
{
  if (typeof e == "undefined") e = window.event;
  var src = document.all ? e.srcElement : e.target;

  return src;
}

Utils.isTime = function(val)
{
  var reg = /^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/;

  return reg.test(val);
}

Utils.x = function(e)
{ //当前鼠标X坐标
    return Browser.isIE?event.x + document.documentElement.scrollLeft - 2:e.pageX;
}

Utils.y = function(e)
{ //当前鼠标Y坐标
    return Browser.isIE?event.y + document.documentElement.scrollTop - 2:e.pageY;
}

Utils.request = function(url, item)
{
	var sValue=url.match(new RegExp("[\?\&]"+item+"=([^\&]*)(\&?)","i"));
	return sValue?sValue[1]:sValue;
}

Utils.$ = function(name)
{
    return document.getElementById(name);
}

function rowindex(tr)
{
  if (Browser.isIE)
  {
    return tr.rowIndex;
  }
  else
  {
    table = tr.parentNode.parentNode;
    for (i = 0; i < table.rows.length; i ++ )
    {
      if (table.rows[i] == tr)
      {
        return i;
      }
    }
  }
}

document.getCookie = function(sName)
{
  // cookies are separated by semicolons
  var aCookie = document.cookie.split("; ");
  for (var i=0; i < aCookie.length; i++)
  {
    // a name/value pair (a crumb) is separated by an equal sign
    var aCrumb = aCookie[i].split("=");
    if (sName == aCrumb[0])
      return decodeURIComponent(aCrumb[1]);
  }

  // a cookie with the requested name does not exist
  return null;
}

document.setCookie = function(sName, sValue, sExpires)
{
  var sCookie = sName + "=" + encodeURIComponent(sValue);
  if (sExpires != null)
  {
    sCookie += "; expires=" + sExpires;
  }

  document.cookie = sCookie;
}

document.removeCookie = function(sName,sValue)
{
  document.cookie = sName + "=; expires=Fri, 31 Dec 1999 23:59:59 GMT;";
}

function getPosition(o)
{
    var t = o.offsetTop;
    var l = o.offsetLeft;
    while(o = o.offsetParent)
    {
        t += o.offsetTop;
        l += o.offsetLeft;
    }
    var pos = {top:t,left:l};
    return pos;
}

function cleanWhitespace(element)
{
  var element = element;
  for (var i = 0; i < element.childNodes.length; i++) {
   var node = element.childNodes[i];
   if (node.nodeType == 3 && !/\S/.test(node.nodeValue))
     element.removeChild(node);
   }
}









/**
 * 标签上鼠标移动事件的处理函数
 * @return
 */
document.getElementById("tabbar-div").onmouseover = function(e)
{
  var obj = Utils.srcElement(e);

  if (obj.className == "tab-back")
  {
    obj.className = "tab-hover";
  }
}

document.getElementById("tabbar-div").onmouseout = function(e)
{
  var obj = Utils.srcElement(e);

  if (obj.className == "tab-hover")
  {
    obj.className = "tab-back";
  }
}

/**
 * 处理点击标签的事件的函数
 * @param : e  FireFox 事件句柄
 *
 * @return
 */
document.getElementById("tabbar-div").onclick = function(e)
{
  var obj = Utils.srcElement(e);

  if (obj.className == "tab-front" || obj.className == '' || obj.tagName.toLowerCase() != 'span')
  {
    return;
  }
  else
  {
    objTable = obj.id.substring(0, obj.id.lastIndexOf("-")) + "-table";

    var tables = document.getElementsByTagName("table");
    var spans  = document.getElementsByTagName("span");

    for (i = 0; i < tables.length; i ++ )
    {
      if (tables[i].id == objTable)
      {
        tables[i].style.display = (Browser.isIE) ? "block" : "table";
      }
      else
      {
        var tblId = tables[i].id.match(/-table$/);

        if (tblId == "-table")
        {
          tables[i].style.display = "none";
        }
      }
    }

    for (i = 0; spans.length; i ++ )
    {
      if (spans[i].className == "tab-front")
      {
        spans[i].className = "tab-back";
        obj.className = "tab-front";
        break;
      }
    }
  }
}
