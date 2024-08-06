<?php
defined('ABSPATH') or die('No script kiddies please!');
$pluginManager = 'TrustindexPlugin_google';
$pluginManagerInstance = $trustindex_pm_google;
$pluginNameForEmails = 'Google';
$newBadgeTabs = [];
if (get_option($pluginManagerInstance->get_option_name('widget-setted-up'), 0) && !get_option($pluginManagerInstance->get_option_name('reply-generated'), 0)) {
$newBadgeTabs []= 'my-reviews';
}
$noContainerElementTabs = [ 'free-widget-configurator' ];
$logoCampaignId = 'wp-google-l';
$logoFile = 'static/img/trustindex.svg';
$assetCheckJs = [
'common' => 'static/js/admin-page-settings-common.js',
'connect' => 'static/js/admin-page-settings-connect.js'
];
if (in_array($pluginManagerInstance->shortname, [ 'google', 'facebook' ])) {
$assetCheckJs['unique'] = 'static/js/admin-page-settings.js';
}
$assetCheckCssId = 'trustindex_settings_style_google';
$assetCheckCssFile = 'static/css/admin-page-settings.css';
function trustindexNotificationOpenRedirect($type)
{
$tab = 'free-widget-configurator';
if (in_array($type, [ 'review-download-available', 'review-download-finished' ])) {
$tab = 'my_reviews';
}
header('Location: admin.php?page=' . sanitize_text_field($_GET['page']) .'&tab='. $tab);
}
if (isset($_GET['wc_notification'])) {
$mode = sanitize_text_field($_GET['wc_notification']);
$dbValue = 'hide';
if ($mode === 'later') {
$dbValue = $time = time() + (30 * 86400);
}
update_option('trustindex-wc-notification', $dbValue, false);
if ($mode === 'open') {
header('Location: https://wordpress.org/plugins/customer-reviews-collector-for-woocommerce/');
exit;
}
echo '<script type="text/javascript">self.close();</script>';
exit;
}
include(plugin_dir_path(__FILE__) . 'include' . DIRECTORY_SEPARATOR . 'admin.php');
?>
