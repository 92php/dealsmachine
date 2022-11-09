<?php
/*
'===========================================================
'Copyright (c) 2009-10 HeQee Studio All Rights Reserved.
'Author:HeQee Studio
'Created Date:2009-11-7
'Support:http://www.heqee.com
'QQ/E-Mail:85431993/threesky@gmail.com
'===========================================================
*/
header("Content-type: text/html; charset=gb2312");
include("libs/pinyin.php");
include "libs/googlevoice.class.php";

$demo = $_GET[demo];
$sendto = trim($_GET[sendto]);
$message = $_GET[message];

$help = '帮助：如果需要群发多帐号用英文逗号(,)分隔(86159..,86159..)，消息内容不支持中文，当您输入中文的时候系统会自动转换为拼音(为了区分是拼音，拼音首字母大写)，保证消息能正常发送(输入"我是旋风"短信内容为"Wo Shi Xuan Feng")<br>有更多建议请联系 QQ:85431993<br>E-mail:threesky@gmail.com';

if(!in_array($demo,array("send","code","help"))){exit("参数错误!");}


#设置google voice 帐号密码 
$username="";
$password="";

switch($demo){
	case "send":
		$message = zh2pinyin($message);
		$sms = new GoogleVoice($username,$password);
		echo   $sms ->send($sendto,$message);
	break;
	case "code":
		echo highlight_file("demo.php",TRUE);
	break;
	case "help":
		echo $help;
	break;
}


?>