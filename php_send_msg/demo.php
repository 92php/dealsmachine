<?php
/*
��ʾ�������ҪȺ�����ʺ���Ӣ�Ķ���(,)�ָ�(86159..,86159..)����Ϣ���ݲ�֧�����ģ������������ĵ�ʱ��ϵͳ���Զ�ת��Ϊƴ��(Ϊ��������ƴ����ƴ������ĸ��д)����֤��Ϣ����������(����"��������"��������Ϊ"Wo Shi Xuan Feng")
����PHP��չ��curl  ���ַ������ΪGBK(����ƴ��ת��)
*/
//����ƴ��ת����Ҫ�ļ�
include("libs/pinyin.php");
include "libs/googlevoice.class.php";
#����google voice �ʺ�����
$username="username@gmail.com";
$password="password";
//���ܺ��룬�������","�ָ�(86159..,86159..)
$sendto ="8615900000000,8613400000000";
//��Ϣ����
$message = "I am from heqee.com";
/*
ת������Ϊƴ�������Զ�ʶ�����Ľ����滻,�˹��ܸ�����Ϊ��������,���Ը���ʵ����������Ƿ����.
*/
//$message = zh2pinyin($message);
//ʵ����(����)
$sms = new GoogleVoice($username,$password);
//���ؽ��
//Text sent to 86134...
echo $sms ->send($sendto,$message);
?>