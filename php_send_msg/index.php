<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>php短信接口</title>
</head>

<body>

<?php

/*根据参数，生成调用URL,因为是测试例子，不对变量为空的情况做处理*/

$http = "http://interface.85521.com/interface_sms_url.ajax"; // 接口文件地址
$LoginName = ""; // 短信帐号
$pass = ""; // 短信帐号密码
$Code = ""; // 业务代码，缺省为空
$subid = ""; // 子号，缺省为空
$sender = ""; // 发送号码
$receivers = ""; // 接收号码，多个手机号以分号 ; 分隔
$setTimer = ""; // 定时发送时间，格式：YYYY-MM-DD HH:MM ，即时发送则不需要
$message = ""; // 短信内容
$cmd = ""; // 操作命令，发送短信值为send，查询回复值为query，查询帐号短信余额为remain
$allowForward = ""; // 转发支持，缺省为0
$system = ""; // 应用平台，缺省为空(windows)，其他平台值：unix或linux

$url = $http."?LoginName=".$LoginName."&pass=".$pass."&Code=".$Code."&subid=".$subid."&sender=".$sender."&receivers=".$receivers."&setTimer=".$setTimer."&message=".urlencode($message)."&cmd=".$cmd."&allowForward=".$allowForward."&system=".$system;

/* windows平台调用接口形式：MSXML，发送请求*/
// ----------------------------------------------------------------------------
$objHttpRequest=new COM("MSXML2.ServerXMLHTTP");
if (is_null($objHttpRequest)) {
echo "create Msxl2.ServerXMLHTTP error";
exit(); 
}
$objHttpRequest->open("GET",$url,False); 
$objHttpRequest->send();
if ($objHttpRequest->status <> 200) {
/*返回错误*/
echo "open Request error";
exit();
}
/*获取返回的信息*/
$retMsg=$objHttpRequest->responseText;
$objHttpRequest=NULL;
// ----------------------------------------------------------------------------


/* 通用调用用接口形式，(其他平台linux、unix)*/
// ----------------------------------------------------------------------------
//$file = $http."?LoginName=".$LoginName."&pass=".$pass."&Code=".$Code."&subid=".$subid."&sender=".$sender."&receivers=".$receivers."&setTimer=".$setTimer."&message=".urlencode($message)."&cmd=".$cmd."&allowForward=".$allowForward."&system=".$system;
//$fp = fopen($file,"r");
//$retMsg = fgetss($fp); // $retMsg = fgetss($fp,1024);
//fclose($fp);
// ----------------------------------------------------------------------------


/* 根据返回的信息（$retMsg，形式如下）获取相应的内容，参数含义请参考“短信接口参数说明”
windows平台返回形式如下：

<?xml version="1.0" encoding="utf-8" ?> 
- <SendSmsReport>
- <Summary>
<Success /> 
<Fail /> 
<RetCode>-6</RetCode> 
<SmsRemain /> 
<ErrPhones /> 
</Summary>
</SendSmsReport>

其他平台返回形式如下：

[SendSmsReport]
[Summary]
[Success][/Success]
[Fail][/Fail]
[RetCode]-6[/RetCode]
[SmsRemain][/SmsRemain]
[ErrPhones][/ErrPhones]
[/Summary]
[/SendSmsReport]

*/
?>


</body>
</html>