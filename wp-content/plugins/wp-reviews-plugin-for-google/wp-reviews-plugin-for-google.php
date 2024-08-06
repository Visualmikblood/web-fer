<?php
/*
Plugin Name: Widgets for Google Reviews
Plugin Title: Widgets for Google Reviews Plugin
Plugin URI: https://wordpress.org/plugins/wp-reviews-plugin-for-google/
Description: Embed Google reviews fast and easily into your WordPress site. Increase SEO, trust and sales using Google reviews.
Tags: google, google places reviews, reviews, widget, google business, review, testimonial, testimonials, slider, rating, google my business, customer review
Author: Trustindex.io <support@trustindex.io>
Author URI: https://www.trustindex.io/
Contributors: trustindex
License: GPLv2 or later
Version: 11.3
Text Domain: wp-reviews-plugin-for-google
Domain Path: /languages/
Donate link: https://www.trustindex.io/prices/
*/
/*
Copyright 2019 Trustindex Kft (email: support@trustindex.io)
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
require_once plugin_dir_path( __FILE__ ) . 'trustindex-plugin.class.php';
$trustindex_pm_google = new TrustindexPlugin_google("google", __FILE__, "11.3", "Widgets for Google Reviews", "Google");
register_activation_hook(__FILE__, [ $trustindex_pm_google, 'activate' ]);
register_deactivation_hook(__FILE__, [ $trustindex_pm_google, 'deactivate' ]);
add_action('admin_menu', [ $trustindex_pm_google, 'add_setting_menu' ], 10);
add_filter('plugin_action_links', [ $trustindex_pm_google, 'add_plugin_action_links' ], 10, 2);
add_filter('plugin_row_meta', [ $trustindex_pm_google, 'add_plugin_meta_links' ], 10, 2);
if (!function_exists('register_block_type')) {
add_action('widgets_init', [ $trustindex_pm_google, 'init_widget' ]);
add_action('widgets_init', [ $trustindex_pm_google, 'register_widget' ]);
}
if (is_file($trustindex_pm_google->getCssFile())) {
add_action('init', function() {
global $trustindex_pm_google;
if (!isset($trustindex_pm_google) || is_null($trustindex_pm_google)) {
require_once plugin_dir_path( __FILE__ ) . 'trustindex-plugin.class.php';
$trustindex_pm_google = new TrustindexPlugin_google("google", __FILE__, "11.3", "Widgets for Google Reviews", "Google");
}
$path = wp_upload_dir()['baseurl'] .'/'. $trustindex_pm_google->getCssFile(true);
if (is_ssl()) {
$path = str_replace('http://', 'https://', $path);
}
wp_register_style('ti-widget-css-google', $path, [], filemtime($trustindex_pm_google->getCssFile()));
});
}
if (!function_exists('ti_exclude_js')) {
function ti_exclude_js($list) {
$list []= 'trustindex.io';
return $list;
}
}
add_filter('rocket_exclude_js', 'ti_exclude_js');
add_filter('litespeed_optimize_js_excludes', 'ti_exclude_js');
if (!function_exists('ti_exclude_inline_js')) {
function ti_exclude_inline_js($list) {
$list []= 'Trustindex.init_pager';
return $list;
}
}
add_filter('rocket_excluded_inline_js_content', 'ti_exclude_inline_js');
add_action('init', [ $trustindex_pm_google, 'init_shortcode' ]);
add_filter('script_loader_tag', function($tag, $handle) {
if (strpos($tag, 'trustindex.io/loader.js') !== false && strpos($tag, 'defer async') === false) {
$tag = str_replace(' src', ' defer async src', $tag);
}
return $tag;
}, 10, 2);
add_action('init', [ $trustindex_pm_google, 'register_tinymce_features' ]);
add_action('init', [ $trustindex_pm_google, 'output_buffer' ]);
add_action('wp_ajax_list_trustindex_widgets', [ $trustindex_pm_google, 'list_trustindex_widgets_ajax' ]);
add_action('admin_enqueue_scripts', [ $trustindex_pm_google, 'trustindex_add_scripts' ]);
add_action('rest_api_init', [ $trustindex_pm_google, 'init_restapi' ]);
if (class_exists('Woocommerce') && !class_exists('TrustindexCollectorPlugin') && !function_exists('ti_woocommerce_notice')) {
function ti_woocommerce_notice() {
$wcNotification = get_option('trustindex-wc-notification', time() - 1);
if ($wcNotification == 'hide' || (int)$wcNotification > time()) {
return;
}
?>
<div class="notice notice-warning is-dismissible" style="margin: 5px 0 15px">
<p><strong><?php echo TrustindexPlugin_google::___("Download our new <a href='%s' target='_blank'>%s</a> plugin and get features for free!", [ 'https://wordpress.org/plugins/customer-reviews-collector-for-woocommerce/', TrustindexPlugin_google::___('Customer Reviews Collector for WooCommerce') ]); ?></strong></p>
<ul style="list-style-type: disc; margin-left: 10px; padding-left: 15px">
<li><?php echo TrustindexPlugin_google::___('Send unlimited review invitations for free'); ?></li>
<li><?php echo TrustindexPlugin_google::___('E-mail templates are fully customizable'); ?></li>
<li><?php echo TrustindexPlugin_google::___('Collect reviews on 100+ review platforms (Google, Facebook, Yelp, etc.)'); ?></li>
</ul>
<p>
<a href="<?php echo admin_url("admin.php?page=wp-reviews-plugin-for-google/settings.php&wc_notification=open"); ?>" target="_blank" class="trustindex-rateus" style="text-decoration: none">
<button class="button button-primary"><?php echo TrustindexPlugin_google::___("Download plugin"); ?></button>
</a>
<a href="<?php echo admin_url("admin.php?page=wp-reviews-plugin-for-google/settings.php&wc_notification=hide"); ?>" class="trustindex-rateus" style="text-decoration: none">
<button class="button button-secondary"><?php echo TrustindexPlugin_google::___("Do not remind me again"); ?></button>
</a>
</p>
</div>
<?php
}
add_action('admin_notices', 'ti_woocommerce_notice');
}
add_action('plugins_loaded', [ $trustindex_pm_google, 'plugin_loaded' ]);
add_action('wp_ajax_nopriv_'. $trustindex_pm_google->get_webhook_action(), $trustindex_pm_google->get_webhook_action());
add_action('wp_ajax_'. $trustindex_pm_google->get_webhook_action(), $trustindex_pm_google->get_webhook_action());
function trustindex_reviews_hook_google()
{
global $trustindex_pm_google;
global $wpdb;
$token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : "";
if (isset($_POST['test']) && $token === get_option($trustindex_pm_google->get_option_name('review-download-token'))) {
echo $token;
exit;
}
$ourToken = $trustindex_pm_google->is_review_download_in_progress();
if (!$ourToken) {
$ourToken = get_option($trustindex_pm_google->get_option_name('review-download-token'));
}
try {
if (!$token || $ourToken !== $token) {
throw new Exception('Token invalid');
}
if (!$trustindex_pm_google->is_noreg_linked() || !$trustindex_pm_google->is_table_exists('reviews')) {
throw new Exception('Platform not connected');
}
$name = 'Unknown source';
if (isset($_POST['error']) && $_POST['error']) {
update_option($trustindex_pm_google->get_option_name('review-download-inprogress'), 'error', false);
}
else {
if (isset($_POST['details'])) {
$trustindex_pm_google->save_details($_POST['details']);
$trustindex_pm_google->save_reviews(isset($_POST['reviews']) ? $_POST['reviews'] : []);
}
delete_option($trustindex_pm_google->get_option_name('review-download-inprogress'));
delete_option($trustindex_pm_google->get_option_name('review-manual-download'));
}
update_option($trustindex_pm_google->get_option_name('download-timestamp'), time() + (86400 * 10), false);
$trustindex_pm_google->setNotificationParam('review-download-available', 'do-check', true);
$isConnecting = get_option($trustindex_pm_google->get_option_name('review-download-is-connecting'));
if (!$isConnecting && !$trustindex_pm_google->getNotificationParam('review-download-finished', 'hidden')) {
$trustindex_pm_google->setNotificationParam('review-download-finished', 'active', true);
$trustindex_pm_google->setNotificationParam('review-download-available', 'active', false);
}
delete_option($trustindex_pm_google->get_option_name('review-download-is-connecting'));
if (!$isConnecting) {
try {
$email = $trustindex_pm_google->getNotificationParam('review-download-finished', 'email', get_option('admin_email'));
if ($email) {
$subject = 'Google Reviews Downloaded';
$message = '
<p>Great news.</p>
<p><strong>Your new Google reviews have been downloaded.</p>
<table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate !important;border-radius: 3px;background-color: #2AA8D7;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
<tbody>
<tr>
<td align="center" valign="middle" style="font-family: Arial;font-size: 16px;padding: 12px 20px;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
<a title="Reply with ChatGPT! »" href="'. admin_url('admin.php') .'?page='. urlencode($trustindex_pm_google->get_plugin_slug() .'/settings.php') .'&tab=my-reviews" target="_blank" style="font-weight: bold;letter-spacing: normal;line-height: 100%;text-align: center;text-decoration: none;color: #FFFFFF;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;display: block;">Reply with ChatGPT! »</a>
</td>
</tr>
</tbody>
</table>
';
$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
wp_mail($email, $subject, $message, $headers, [ '' ]);
}
}
catch(Exception $e) { }
}
echo $ourToken;
}
catch(Exception $e) {
echo 'Error in WP: '. $e->getMessage();
}
exit;
}
add_action('admin_notices', function() {
global $trustindex_pm_google;
$notifications = get_option($trustindex_pm_google->get_option_name('notifications'), []);
foreach ([
'not-using-no-connection',
'not-using-no-widget',
'review-download-available',
'review-download-finished',
'rate-us'
] as $type) {
if (!$trustindex_pm_google->isNotificationActive($type, $notifications)) {
continue;
}
echo '
<div class="notice notice-warning is-dismissible trustindex-notification-row'. ($type === 'rate-us' ? ' trustindex-popup' : '') .'" data-close-url="'. admin_url('admin.php?page=wp-reviews-plugin-for-google/settings.php&notification='. $type .'&action=close') .'">
<p>'. $trustindex_pm_google->getNotificationText($type) .'<p>';
if ($type === 'rate-us') {
echo '
<a href="'. admin_url('admin.php?page=wp-reviews-plugin-for-google/settings.php&notification='. $type .'&action=open') .'" class="ti-close-notification" target="_blank">
<button class="button ti-button-primary button-primary">'. TrustindexPlugin_google::___('Sure, you deserve it') .'</button>
</a>
<a href="'. admin_url('admin.php?page=wp-reviews-plugin-for-google/settings.php&notification='. $type .'&action=later') .'" class="ti-remind-later">
<button class="button ti-button-default button-secondary">'. TrustindexPlugin_google::___('Maybe later') .'</button>
</a>
<a href="'. admin_url('admin.php?page=wp-reviews-plugin-for-google/settings.php&notification='. $type .'&action=hide') .'" class="ti-hide-notification">
<button class="button ti-button-default button-secondary" style="float: right">'. TrustindexPlugin_google::___('Do not remind me again') .'</button>
</a>
';
}
else {
echo '
<a href="'. admin_url('admin.php?page=wp-reviews-plugin-for-google/settings.php&notification='. $type .'&action=open') .'">
<button class="button button-primary">'. $trustindex_pm_google->getNotificationButtonText($type) .'</button>
</a>';
}
if ($type === 'not-using-no-widget') {
echo '
<a href="'. admin_url('admin.php?page=wp-reviews-plugin-for-google/settings.php&notification='. $type .'&action=later') .'" class="ti-remind-later" style="margin-left: 5px">
'. TrustindexPlugin_google::___('Remind me later') .'
</a>';
}
echo '
</p>
</div>';
}
});
?>