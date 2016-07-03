<?php
/*
Plugin Name: WHMCS WordPress Integration
Plugin URI: http://premium.wpmudev.org/project/whmcs-wordpress-integration/
Description: This plugin allows remote control of WHMCS from Wordpress. Now with pretty permalinks.
Author: WPMU DEV
Author Uri: http://premium.wpmudev.org/
Text Domain: wcp
Domain Path: languages
Version: 1.4.3
Network: false
WDP ID: 263
*/

/*  Copyright 2013-2014  Incsub  (http://incsub.com)

Author - Arnold Bailey
Contributors - Jose Jaureguiberry

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if(!function_exists('curl_init'))
exit( __('<h3 style="color: #c00;">The WHMCS WordPress Integration plugin requires the PHP Curl extensions.</h3>', WHMCS_TEXT_DOMAIN) );

if(!function_exists('mb_get_info'))
exit( __('<h3 style="color: #c00;">The WHMCS WordPress Integration plugin requires the PHP mbstring extensions.</h3>', WHMCS_TEXT_DOMAIN) );

if(!function_exists('mcrypt_encrypt'))
exit( __('<h3 style="color: #c00;">The WHMCS WordPress Integration plugin requires the PHP mcrypt extensions.</h3>', WHMCS_TEXT_DOMAIN) );

define('WHMCS_INTEGRATION_VERSION','1.4.3');
define('WHMCS_SETTINGS_NAME','wcp_settings');
define('WHMCS_TEMPLATE_OPTION','whmcs_template');
define('WHMCS_TEXT_DOMAIN','wcp');
define('WHMCS_INTEGRATION_URL', plugin_dir_url(__FILE__) );
define('WHMCS_INTEGRATION_DIR', plugin_dir_path(__FILE__) );
define('WHMCS_INTEGRATION_CACHE_URL', plugin_dir_url(__FILE__) . 'cache/');
define('WHMCS_INTEGRATION_CACHE_DIR', plugin_dir_path(__FILE__) . 'cache/');
define('WHMCS_INTEGRATION_COOKIE', 'WP_WHMCS');
if(!defined('WHMCS_LOAD_BOOTSTRAP') ) define('WHMCS_LOAD_BOOTSTRAP', true);
if(!defined('WHMCS_LOAD_STYLES') ) define('WHMCS_LOAD_STYLES', true);

if(!defined('CURL_SSLVERSION_DEFAULT') ) define('CURL_SSLVERSION_DEFAULT', 0);
if(!defined('CURL_SSLVERSION_TLSv1') ) define('CURL_SSLVERSION_TLSv1', 1);
if(!defined('CURL_SSLVERSION_SSLv2') ) define('CURL_SSLVERSION_SSLv2', 2);
if(!defined('CURL_SSLVERSION_SSLv3') ) define('CURL_SSLVERSION_SSLv3', 3);

include_once(plugin_dir_path(__FILE__) .'includes/helpers/whmcs-utils.php');
include_once(plugin_dir_path(__FILE__) .'includes/helpers/url_to_absolute.php');
include_once(plugin_dir_path(__FILE__) .'includes/http/class-whmcs-http-curl.php');
include_once(plugin_dir_path(__FILE__) .'includes/http/class-whmcs-http-streams.php');
include_once(plugin_dir_path(__FILE__) .'includes/class-whmcs-wordpress-integration.php');

/* -------------------- WPMU DEV Dashboard Notice -------------------- */
global $wpmudev_notices;
$wpmudev_notices[] = array( 'id'=> 263,
'name'=> 'WHMCS WordPress Integration',
'screens' => array(
'toplevel_page_wcp-settings',
) );

include_once(plugin_dir_path(__FILE__) .'dash-notice/wpmudev-dash-notification.php');

add_filter('widget_text', 'do_shortcode'); // Allows use of shortcodes in widgets

$WHMCS_Wordpress_Integration = new WHMCS_Wordpress_Integration();