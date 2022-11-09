<?php
global $_LANG;
$data = array (
  'PayPal' =>
  array (
    'pay_id' => '11',
    'pay_code' => 'PayPal',
    'pay_name' => 'PayPal',
    'pay_shuoming' => $_LANG['payment_PayPal'],
    'logo' => '/temp/skin3/images/styleimg/paypal.jpg',
    'pay_desc' => '<img height="80" alt="Solution Graphics" src="/temp/skin3/images/styleimg/paypalShow.gif" width="280" border="0"/>',
    'sort_order' => '1',
    'enabled' => '1',
  ),
  'GoogleCheckout' =>
  array (
    'pay_id' => '12',
    'pay_code' => 'GoogleCheckout',
    'pay_name' => 'Google Checkout',
    'pay_shuoming' => '',
    'pay_desc' => $_LANG['googlecheckout_pay_desc'],
    'logo' => '/temp/skin2/ximages/Gcheckout_s.gif',
    'sort_order' => '2',
    'enabled' => '1',
  ),
  'webcollect' =>
  array (
    'pay_id' => '16',
    'pay_code' => 'webcollect',
    'pay_name' => 'Credit Card',
    'pay_shuoming' => $_LANG['webcollect_pay_shuoming'],
    'pay_desc' => $_LANG['webcollect_pay_desc'],
    'logo' => '/temp/skin3/images/styleimg/visa.gif',
    'sort_order' => '4',
    'enabled' => '1',
	'used_currencies' => 'USD,EUR,AUD', 
  ),   
  'CreditCard' =>
  array (
    'pay_id' => '13',
    'pay_code' => 'CreditCard',
    'pay_name' => 'Credit card via PayPal',
    'pay_shuoming' => $_LANG['creditcard_pay_shuoming'],
    'pay_desc' => '',
    'logo' => '/temp/skin3/images/styleimg/paypal.gif',
    'sort_order' => '3',
    'enabled' => '1',
  ),  
  'WesternUnion' =>
  array (
    'pay_id' => '14',
    'pay_code' => 'WesternUnion',
    'pay_name' => 'Western Union',
    'pay_shuoming' => $_LANG['payment_WesternUnion'],
    'pay_desc' => $_LANG['payment_WesternUnion_desc'],
    'logo' => '/temp/skin3/images/styleimg/western-union.jpg',
    'sort_order' => '5',
    'enabled' => '1',
  ),
  'WiredTransfer' =>
  array (
    'pay_id' => '15',
    'pay_code' => 'WiredTransfer',
    'pay_name' => 'Wired Transfer',
    'pay_shuoming' => $_LANG['payment_WiredTransfer'],
    'pay_desc' => $_LANG['payment_WiredTransfer_desc'],
    'logo' => '/temp/skin3/images/styleimg/wire.gif',
    //1000美金以上by mashanling on 2013-09-24 14:23:13
    'pay_desc_gt1000' => $_LANG['pay_desc_gt1000'],
    //1000美金以下by mashanling on 2013-09-24 14:23:45
    'pay_desc_lt1000' => $_LANG['pay_desc_lt1000'],	
    'sort_order' => '6',
    'enabled' => '1',
  ),
  'webmoney' =>
  array (
    'pay_id' => '20',
    'pay_code' => 'webmoney',
    'pay_name' => 'webmoney',
    'pay_shuoming' => $_LANG['webmoney_pay_shuoming'],
    'pay_desc' => '',
    'logo' => 'http://cloud4.faout.com/imagecache/A/images/webmoney.jpg',
    'sort_order' => '7',
    'enabled' => '1',
  )  ,
  'DirectDebit' =>
  array (
    'pay_id' => '21',
    'pay_code' => 'DirectDebit',
    'pay_name' => 'Direct Debit',
    'pay_shuoming' => $_LANG['directdebit_pay_shuoming'],
    'pay_desc' => '',
    'logo' => 'http://cloud4.faout.com/imagecache/A/images/directdebit.gif',
    'sort_order' => '8',
    'enabled' => '1',
  )  ,
  'BankTransfer' =>
  array (
    'pay_id' => '22',
    'pay_code' => 'BankTransfer',
    'pay_name' => 'Bank Transfer',
    'pay_shuoming' => $_LANG['banktransfer_pay_shuoming'],
    'pay_desc' => '',
    'logo' => '/temp/skin3/images/styleimg/bank_transfer.jpg',
    'sort_order' => '9',
    'enabled' => '1',
	'used_currencies' => 'USD,EUR,AUD',  
  )  ,
  
  'boletoBancario' =>
  array (
    'pay_id' => '24',
    'pay_code' => 'boletoBancario',
    'pay_name' => 'boleto Bancario',
    'pay_shuoming' => 'Pay by Boleto Bancario via Ebanx',
    'pay_desc' => '',
    'logo' => 'http://cloud4.faout.com/imagecache/A/images/boleto2.jpg',
    'sort_order' => '9',
    'enabled' => '1',
	'used_currencies' => 'USD,BRL'
  ),   //banx boleto Bancario
);
?>