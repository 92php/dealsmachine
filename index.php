<?php

define('INI_WEB', true);
ini_set('session.bug_compat_warn', 0);

require ("fun/inc.main.php");

$App = new App();

$App->run();

/*$str=md5('admin123');
echo $str;*/


?>