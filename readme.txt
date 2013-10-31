=== WHMCS WordPress Integration ===
Contributors: Arnold Bailey (Incsub)
Tags: WHMCS, hosting, support, billing, integration
Requires at least: 3.2
Tested up to: 3.6.1
Stable tag: 1.2.0

WHMCS WordPress Integration is a plugin for displaying the Client area of WHMCS in Wordpress. The WHMCS screens take on the styling of the Wordpress theme used.

== Description ==

WHMCS WordPress Integration is a plugin for displaying the Client area of WHMCS in Wordpress. The WHMCS screens take on the styling of the Wordpress theme used. Or you can apply your own custom styling via CSS.

WHMCS WordPress Integration allows your customers to interact through Wordpress with WHMCS giving a seamless experience for WHMCS Billing, Ordering, Trouble Tickets, Domain Manegment and many other WHMCS features.

== Installation ==

1. Upload the 'whmcs-wordpress-integration' folder to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the WHMCS Integration settings menu and configure the plugin options.
4. Create a Wordpress page that will be your portal to WHMCS. 
5. Add the [wcp_content] shortcode to the page to display the main WHMCS content area.
6. You can add the other Shortcodes listed on the WHMCS Integration Settings page to you Sidebars or other pages to display other WHMCS information

The Endpoint Slug is the slug that signals that the following page is to be pulled from the WHMCS site. It defaults to “whmcsportal”. The permalinks will look like.

http://wpwhmcs.com/whmcsportal/clientarea.php/

You can change the slug to whatever you like to avoid interfering with other pages but like all slugs it should contain Only lowercase alphanumeric and the hyphen.

=Cookie Syncing=

In order to be able to download protected files from WHMCS Downloads or Knowledgebase sections you will need to install a small helper file called

“wp-integration.php”

from the plugin to the root of your WHMCS installation.

== Frequently Asked Questions ==

To change the styling add your CSS to the 'whmcs-portal.css' file in '/wp-content/whmcs-wordpress-integration/css'.
NOTE: Make a backup of your CSS file as it will be overwritten on update!

== Change Log ==

See separate changelog.txt


