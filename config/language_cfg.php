<?php
/*
* Construt user client browser language set
* function used wheter to display special goods or product 
* author ez2peter@163.com 
*/

// Construct language
// array keys combined with a string stored into table goods and catagory eg. 'en,ru,ch,de,pt' 
//header('Content-Type: text/html; charset=utf-8'); 
$langArr = array (
	'en'  =>  '英语',
	'ch'  =>  '中文',
	'ru'  =>  '俄罗斯语',
	'es'  =>  '西班牙语',
	'pt'  =>  '葡萄牙语',
	'nl'  =>  '荷兰语',
	'de'  =>  '德语' ,
	'ar'  =>  '阿拉伯语',
	'fr'  =>  '法语',
	'tr'  =>  '土耳其语',
	'it'  =>  '意大利语',
	'cs'  =>  '捷克语',
	'bg'  =>  '保加利亚语',
	'ms'  =>  '马来西亚语',
	'id'  =>  '印度尼西亚语',
	'fa'  =>  '波斯语'
);

/*foreach ($langArr as $k => $v){
	$langcfg[]= '<input type="checkbox" name="langs[]" value= "'.$k.'">'.$v."&nbsp;&nbsp;&nbsp;&nbsp;\n";
}*/

?>