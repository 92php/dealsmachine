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

$help = '�����������ҪȺ�����ʺ���Ӣ�Ķ���(,)�ָ�(86159..,86159..)����Ϣ���ݲ�֧�����ģ������������ĵ�ʱ��ϵͳ���Զ�ת��Ϊƴ��(Ϊ��������ƴ����ƴ������ĸ��д)����֤��Ϣ����������(����"��������"��������Ϊ"Wo Shi Xuan Feng")<br>�и��ཨ������ϵ QQ:85431993<br>E-mail:threesky@gmail.com';

if(!in_array($demo,array("send","code","help"))){exit("��������!");}


#����google voice �ʺ����� 
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