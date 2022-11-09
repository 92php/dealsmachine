<?php
/*
提示：如果需要群发多帐号用英文逗号(,)分隔(86159..,86159..)，消息内容不支持中文，当您输入中文的时候系统会自动转换为拼音(为了区分是拼音，拼音首字母大写)，保证消息能正常发送(输入"我是旋风"短信内容为"Wo Shi Xuan Feng")
所需PHP扩展：curl  。字符编码均为GBK(考虑拼音转换)
*/
//引入拼音转换需要文件
include("libs/pinyin.php");
include "libs/googlevoice.class.php";
#设置google voice 帐号密码
$username="username@gmail.com";
$password="password";
//接受号码，多号码用","分隔(86159..,86159..)
$sendto ="8615900000000,8613400000000";
//消息内容
$message = "I am from heqee.com";
/*
转换中文为拼音程序自动识别中文进行替换,此功能个人认为纯属娱乐,可以根据实际情况决定是否添加.
*/
//$message = zh2pinyin($message);
//实例化(必须)
$sms = new GoogleVoice($username,$password);
//返回结果
//Text sent to 86134...
echo $sms ->send($sendto,$message);
?>