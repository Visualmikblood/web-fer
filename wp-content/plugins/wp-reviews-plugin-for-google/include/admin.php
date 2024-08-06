<?php
if (!current_user_can('edit_pages')) {
die('The account you are logged in to does not have permission to access this page.');
}
if (isset($_GET['test_proxy'])) {
check_admin_referer('ti-test-proxy');
delete_option($pluginManagerInstance->get_option_name('proxy-check'));
header('Location: admin.php?page=' . sanitize_text_field($_GET['page']) .'&tab=' . sanitize_text_field($_GET['tab']));
exit;
}
if (isset($_GET['notification'])) {
$type = sanitize_text_field($_GET['notification']);
switch (sanitize_text_field($_GET['action'])) {
case 'later':
$pluginManagerInstance->setNotificationParam($type, 'timestamp', time() + (14 * 86400));
break;
case 'close':
if ($type !== 'rate-us') {
$pluginManagerInstance->setNotificationParam($type, 'active', false);
}
break;
case 'open':
$pluginManagerInstance->setNotificationParam($type, 'active', false);
if ($type === 'rate-us') {
header('Location: https://wordpress.org/support/plugin/'. $pluginManagerInstance->get_plugin_slug() . '/reviews/?rate=5#new-post');
exit;
}
if (function_exists('trustindexNotificationOpenRedirect')) {
trustindexNotificationOpenRedirect($type);
}
break;
case 'hide':
$pluginManagerInstance->setNotificationParam($type, 'hidden', true);
header('Location: admin.php?page=' . sanitize_text_field($_GET['page']) .'&tab=advanced');
break;
case 'unhide':
$pluginManagerInstance->setNotificationParam($type, 'hidden', false);
header('Location: admin.php?page=' . sanitize_text_field($_GET['page']) .'&tab=advanced');
break;
}
exit;
}
if (isset($_REQUEST['command']) && $_REQUEST['command'] === 'rate-us-feedback') {
check_admin_referer('ti-rate-us');
$text = isset($_POST['text']) ? trim(wp_kses_post(stripslashes($_POST['text']))) : "";
$email = isset($_POST['email']) ? trim(sanitize_text_field($_POST['email'])) : "";
$star = isset($_REQUEST['star']) ? (int)$_REQUEST['star'] : 1;
update_option($pluginManagerInstance->get_option_name('rate-us-feedback'), $star, false);
if ($star > 3) {
header('Location: https://wordpress.org/support/plugin/'. $pluginManagerInstance->get_plugin_slug() . '/reviews/?rate='. $star .'#new-post');
}
else {
wp_mail('support@trustindex.io', 'Feedback from '. $pluginNameForEmails .' plugin', "We received a <strong>$star star</strong> feedback about the $pluginNameForEmails plugin from $email:<br /><br />$text", [
'From: '. $email,
'Content-Type: text/html; charset=UTF-8'
]);
}
exit;
}
$httpBlocked = false;
if (defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL) {
if (!defined('WP_ACCESSIBLE_HOSTS') || strpos(WP_ACCESSIBLE_HOSTS, '*.trustindex.io') === FALSE) {
$httpBlocked = true;
}
}
$proxy = new WP_HTTP_Proxy();
$proxyCheck = true;
if ($proxy->is_enabled()) {
$optName = $pluginManagerInstance->get_option_name('proxy-check');
$dbData = get_option($optName, "");
if (!$dbData) {
$response = wp_remote_post("https://admin.trustindex.io/" . 'api/userCheckLoggedIn', [
'timeout' => '30',
'redirection' => '5',
'blocking' => true
]);
if (is_wp_error($response)) {
$proxyCheck = $response->get_error_message();
update_option($optName, $response->get_error_message(), false);
}
else {
update_option($optName, 1, false);
}
}
else {
if ($dbData !== '1') {
$proxyCheck = $dbData;
}
}
}
$tabs = $pluginManagerInstance->getPluginTabs();
$selectedTab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : null;
if (!$selectedTab || !in_array($selectedTab, array_column($tabs, 'slug'))) {
$selectedTab = $tabs[0]['slug'];
}
?>
<?php if (isset($assetCheckJs) && isset($assetCheckCssFile)): ?>
<div id="ti-assets-error" class="notice notice-warning" style="display: none; margin-left: 0; margin-right: 0; padding-bottom: 9px">
<p>
<?php echo $pluginManager::___('For some reason, the <strong>CSS</strong> file required to run the plugin was not loaded.<br />One of your plugins is probably causing the problem.'); ?>
</p>
</div>
<script type="text/javascript">
window.onload = function() {
let notLoaded = [];
let loadedCount = 0;
let jsFiles = [
<?php foreach ($assetCheckJs as $id => $file): ?>
{
url: '<?php echo $pluginManagerInstance->get_plugin_file_url($file); ?>',
id: '<?php echo $id; ?>'
},
<?php endforeach; ?>
];
let addElement = function(type, url, callback) {
let element = document.createElement(type);
if (type === 'script') {
element.type = 'text/javascript';
element.src = url;
}
else {
element.type = 'text/css';
element.rel = 'stylesheet';
element.href = url;
element.id = '<?php echo $assetCheckCssId; ?>-css';
}
document.head.appendChild(element);
element.addEventListener('load', function() { callback(true); });
element.addEventListener('error', function() { callback(false); });
};
let isCSSExists = function() {
let link = document.getElementById('<?php echo $assetCheckCssId; ?>-css');
return link && Boolean(link.sheet);
};
let isJSExists = function(id) {
return typeof TrustindexJsLoaded !== 'undefined' && typeof TrustindexJsLoaded[ id ] !== 'undefined';
};
let process = function() {
if (loadedCount < jsFiles.length + 1) {
return false;
}
if (notLoaded.length) {
document.getElementById('trustindex-plugin-settings-page').remove();
let warningBox = document.getElementById('ti-assets-error');
if (warningBox) {
warningBox.style.display = 'block';
warningBox.querySelector('p strong').innerHTML = notLoaded.join(', ');
}
}
}
if (!isCSSExists()) {
addElement('link', '<?php echo $pluginManagerInstance->get_plugin_file_url($assetCheckCssFile); ?>', function(success) {
loadedCount++;
if (!success) {
notLoaded.push('CSS');
}
process();
});
}
else {
loadedCount++;
}
jsFiles.forEach(function(js) {
if (!isJSExists(js.id)) {
addElement('script', js.url, function(success) {
loadedCount++;
if (!success) {
if (notLoaded.indexOf('JS') === -1) {
notLoaded.push('JS');
}
}
process();
});
}
else {
loadedCount++;
}
});
};
</script>
<?php endif; ?>
<div id="trustindex-plugin-settings-page" class="ti-plugin-wrapper ti-toggle-opacity">
<div class="ti-header-nav">
<?php foreach ($tabs as $tab): ?>
<a
class="ti-nav-item<?php if ($selectedTab === $tab['slug']): ?> ti-active<?php endif; ?><?php if ($tab['place'] === 'right'): ?> ti-right<?php endif; ?>"
href="<?php echo esc_url(admin_url('admin.php?page='. esc_attr($_GET['page']) .'&tab='. esc_attr($tab['slug']))); ?>"
>
<?php echo esc_html($tab['name']); ?>
<?php if (isset($newBadgeTabs) && in_array($tab['slug'], $newBadgeTabs)): ?>
<span class="ti-new-badge"><?php echo $pluginManager::___('new'); ?></span>
<?php endif; ?>
</a>
<?php endforeach; ?>
<a href="https://www.trustindex.io/ti-redirect.php?a=sys&c=<?php echo $logoCampaignId; ?>" target="_blank" title="Trustindex" class="ti-logo">
<img src="<?php echo $pluginManagerInstance->get_plugin_file_url($logoFile); ?>" />
</a>
</div>
<?php if ($httpBlocked): ?>
<div class="ti-box ti-notice-error ti-mb-1">
<p>
<?php echo $pluginManager::___('Your site cannot download our widget templates, because of your server settings not allowing that:'); ?><br /><a href="https://wordpress.org/support/article/editing-wp-config-php/#block-external-url-requests" target="_blank">https://wordpress.org/support/article/editing-wp-config-php/#block-external-url-requests</a><br /><br />
<strong><?php echo $pluginManager::___('Solution'); ?></strong><br />
<?php echo $pluginManager::___('a) You should define <strong>WP_HTTP_BLOCK_EXTERNAL</strong> as false'); ?><br />
<?php echo $pluginManager::___("b) or you should add Trustindex as an <strong>WP_ACCESSIBLE_HOSTS</strong>: \"*.trustindex.io\""); ?><br />
</p>
</div>
<?php endif; ?>
<?php if ($proxyCheck !== TRUE): ?>
<div class="ti-box ti-notice-error ti-mb-1">
<p>
<?php echo $pluginManager::___('It seems you are using a proxy for HTTP requests but after a test request it returned a following error:'); ?><br />
<strong><?php echo $proxyCheck; ?></strong><br /><br />
<?php echo $pluginManager::___('Therefore, our plugin might not work properly. Please, contact your hosting support, they can resolve this easily.'); ?>
</p>
<a href="<?php echo wp_nonce_url('?page='. esc_attr($_GET['page']) .'&tab='. esc_attr($_GET['tab']) .'&test_proxy', 'ti-test-proxy'); ?>" class="ti-btn ti-btn-loading-on-click"><?php echo $pluginManager::___('Test again') ;?></a>
</div>
<?php endif; ?>
<?php if (!isset($noContainerElementTabs) || !in_array($selectedTab, $noContainerElementTabs)): ?>
<div class="ti-container" id="tab-<?php echo esc_attr($selectedTab); ?>">
<?php include(plugin_dir_path(__FILE__) . '../tabs' . DIRECTORY_SEPARATOR . $selectedTab . '.php'); ?>
</div>
<?php else: ?>
<?php include(plugin_dir_path(__FILE__) . '../tabs' . DIRECTORY_SEPARATOR . $selectedTab . '.php'); ?>
<?php endif; ?>
</div>
<div id="ti-loading">
<div class="ti-loading-effect">
<div></div>
<div></div>
<div></div>
</div>
</div>