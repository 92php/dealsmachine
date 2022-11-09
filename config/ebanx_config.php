<?php
/**
 * ebanx 付款的配置文件
 * @author Jim 2014-1-7
 */

$ebanx_config = array();

/** test environment***/
$ebanx_config['integration_key'] = '794cbd45177354567b712d65f1f35764a7f462b839a787161c6aa187ca2793fcbca2549f853d2bf5614c6ffad8328199cffc';
$ebanx_config['base_url']   = "https://www.ebanx.com/test/";

/** production environment 
$ebanx_config['integration_key'] = '2a548d5623afc35b9386b3885c8ead1f19fae6eea6781e6763d452683def92281879d442f344741533682461236437abb57b';
$ebanx_config['base_url']   = "https://www.ebanx.com/pay/";
*/

?>