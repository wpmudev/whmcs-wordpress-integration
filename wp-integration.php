<?php
/**
* @package WHMCS WordPress Integration
* @author Arnold Bailey
* @since version 1.2.0
* @license GPL2+
*/

if( !empty($_GET['ver'])){
	$ver = $_GET['ver'];
	$key = $_SERVER['HTTP_HOST'] ;

	$cookie = json_decode(rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($ver), MCRYPT_MODE_CBC, md5(md5($key))), "\0") );

	header('p3p: CP=ALL DSP COR PSAa PSDa OUR NOR ONL UNI COM NAV');
	header('Content-Type: text/css');
	setcookie($cookie->name, $cookie->value, $cookie->expires, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httponly);
}
