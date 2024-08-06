<?php
defined('ABSPATH') or die('No script kiddies please!');
$ti_command = isset($_REQUEST['command']) ? sanitize_text_field($_REQUEST['command']) : null;
$ti_command_list = [
'save-page',
'delete-page',
'save-style',
'save-filter',
'save-set',
'save-language',
'save-dateformat',
'save-options',
'save-align',
'save-review-text-mode',
'save-amp-notice-hide',
'review-manual-download'
];
if (!in_array($ti_command, $ti_command_list)) {
$ti_command = null;
}
function trustindex_plugin_connect_page($pageDetails = null, $defaultSettings = true, $reviewDownload = false)
{
global $pluginManager;
global $pluginManagerInstance;
global $wpdb;
if (!$pageDetails) {
return false;
}
if ($pageDetails['name']) {
$pageDetails['name'] = json_encode($pageDetails['name']);
}
$pluginManagerInstance->setNotificationParam('not-using-no-connection', 'active', false);
$pluginManagerInstance->setNotificationParam('not-using-no-widget', 'active', true);
$pluginManagerInstance->setNotificationParam('not-using-no-widget', 'timestamp', time() + (2 * 3600));
$tableName = $pluginManagerInstance->get_tablename('reviews');
$wpdb->query('TRUNCATE `'. $tableName .'`');
$reviews = null;
if (isset($pageDetails['reviews'])) {
$reviews = $pageDetails['reviews'];
unset($pageDetails['reviews']);
}
$requestId = null;
if (isset($pageDetails['request_id'])) {
$requestId = $pageDetails['request_id'];
unset($pageDetails['request_id']);
}
else if (isset($_REQUEST['review_request_id'])) {
$requestId = $_REQUEST['review_request_id'];
}
if ($requestId) {
update_option($pluginManagerInstance->get_option_name('review-download-request-id'), $requestId, false);
}
$manualDownload = 0;
if (isset($pageDetails['manual_download'])) {
$manualDownload = (int)$pageDetails['manual_download'];
unset($pageDetails['manual_download']);
}
else if (isset($_REQUEST['manual_download'])) {
$manualDownload = (int)$_REQUEST['manual_download'];
}
delete_option($pluginManagerInstance->get_option_name('review-download-token'));
if ($reviewDownload) {
update_option($pluginManagerInstance->get_option_name('review-download-inprogress'), $reviewDownload, false);
update_option($pluginManagerInstance->get_option_name('review-manual-download'), $manualDownload, false);
update_option($pluginManagerInstance->get_option_name('review-download-is-connecting'), 1, false);
}
else {
delete_option($pluginManagerInstance->get_option_name('review-download-inprogress'));
delete_option($pluginManagerInstance->get_option_name('review-manual-download'));
delete_option($pluginManagerInstance->get_option_name('review-download-is-connecting'));
}
if (is_array($reviews)) {
foreach ($reviews as $row) {
$date = isset($row['created_at']) ? $row['created_at'] : (isset($row['date']) ? $row['date'] : '');
$wpdb->insert($tableName, [
'user' => $row['reviewer']['name'],
'user_photo' => $row['reviewer']['avatar_url'],
'text' => $row['text'],
'rating' => $row['rating'] ? $row['rating'] : 5,
'date' => substr($date, 0, 10),
'reviewId' => isset($row['id']) ? $row['id'] : null,
'reply' => isset($row['reply']) ? $row['reply'] : ""
]);
}
if ($pluginManagerInstance->shortname === 'facebook' || count($reviews) == $pageDetails['rating_number'] || count($reviews) === 10) {
$timestamp = time() + (86400 * 10);
if (isset($pageDetails['timestamp'])) {
$timestamp = $pageDetails['timestamp'];
unset($pageDetails['timestamp']);
}
update_option($pluginManagerInstance->get_option_name('download-timestamp'), $timestamp, false);
delete_option($pluginManagerInstance->get_option_name('review-download-inprogress'));
delete_option($pluginManagerInstance->get_option_name('review-manual-download'));
update_option($pluginManagerInstance->get_option_name('review-download-modal'), 0, false);
}
}
update_option($pluginManagerInstance->get_option_name('page-details'), $pageDetails, false);
$GLOBALS['wp_object_cache']->delete( $pluginManagerInstance->get_option_name('page-details'), 'options' );
if ($defaultSettings) {
$lang = strtolower(substr(get_locale(), 0, 2));
if (!isset($pluginManager::$widget_languages[ $lang ])) {
$lang = 'en';
}
update_option($pluginManagerInstance->get_option_name('lang'), $lang, false);
header('Location: admin.php?page=' . sanitize_text_field($_GET['page']) .'&tab=free-widget-configurator');
}
else {
$pluginManagerInstance->noreg_save_css(true);
}
}
function trustindex_plugin_disconnect_page($settingsDelete = true)
{
global $pluginManagerInstance;
global $wpdb;
$pluginManagerInstance->delete_async_request();
delete_option($pluginManagerInstance->get_option_name('review-download-inprogress'));
delete_option($pluginManagerInstance->get_option_name('review-download-request-id'));
delete_option($pluginManagerInstance->get_option_name('review-manual-download'));
delete_option($pluginManagerInstance->get_option_name('page-details'));
delete_option($pluginManagerInstance->get_option_name('review-content'));
delete_option($pluginManagerInstance->get_option_name('css-content'));
if (is_file($pluginManagerInstance->getCssFile())) {
unlink($pluginManagerInstance->getCssFile());
}
if ($settingsDelete) {
delete_option($pluginManagerInstance->get_option_name('style-id'));
delete_option($pluginManagerInstance->get_option_name('scss-set'));
delete_option($pluginManagerInstance->get_option_name('filter'));
delete_option($pluginManagerInstance->get_option_name('lang'));
delete_option($pluginManagerInstance->get_option_name('dateformat'));
delete_option($pluginManagerInstance->get_option_name('no-rating-text'));
delete_option($pluginManagerInstance->get_option_name('verified-icon'));
delete_option($pluginManagerInstance->get_option_name('enable-animation'));
delete_option($pluginManagerInstance->get_option_name('show-arrows'));
delete_option($pluginManagerInstance->get_option_name('show-header-button'));
delete_option($pluginManagerInstance->get_option_name('reviews-load-more'));
delete_option($pluginManagerInstance->get_option_name('show-reviewers-photo'));
delete_option($pluginManagerInstance->get_option_name('widget-setted-up'));
}
$wpdb->query('TRUNCATE `'. $pluginManagerInstance->get_tablename('reviews') .'`');
$pluginManagerInstance->setNotificationParam('not-using-no-connection', 'active', true);
$pluginManagerInstance->setNotificationParam('not-using-no-connection', 'timestamp', time() + 86400);
$pluginManagerInstance->setNotificationParam('not-using-no-widget', 'active', false);
}
function trustindex_plugin_change_step($step = 5)
{
global $pluginManagerInstance;
if ($step < 5) {
$optionsToDelete = [
'widget-setted-up',
'align',
'review-text-mode',
'verified-icon',
'enable-animation',
'no-rating-text',
'disable-font',
'show-reviewers-photo',
'show-logos',
'show-stars',
'footer-filter-text'
];
foreach ($optionsToDelete as $name) {
delete_option($pluginManagerInstance->get_option_name($name));
}
}
if ($step < 4) {
delete_option($pluginManagerInstance->get_option_name('scss-set'));
}
if ($step < 3) {
delete_option($pluginManagerInstance->get_option_name('style-id'));
}
if ($step < 2) {
trustindex_plugin_disconnect_page();
}
}
if ($ti_command === 'save-page') {
check_admin_referer('ti-save-page');
$pageDetails = isset($_POST['page_details']) ? json_decode(stripcslashes($_POST['page_details']), true) : null;
$reviewDownload = isset($_POST['review_download']) ? sanitize_text_field($_POST['review_download']) : 0;
trustindex_plugin_connect_page($pageDetails, true, $reviewDownload);
header('Location: admin.php?page=' . sanitize_text_field($_GET['page']) .'&tab=free-widget-configurator');
exit;
}
else if ($ti_command === 'delete-page') {
check_admin_referer('ti-delete-page');
trustindex_plugin_disconnect_page();
header('Location: admin.php?page='. sanitize_text_field($_GET['page']) .'&tab=free-widget-configurator');
exit;
}
else if ($ti_command === 'save-style') {
check_admin_referer('ti-save-style');
$styleId = (int)$_REQUEST['style_id'];
update_option($pluginManagerInstance->get_option_name('style-id'), $styleId, false);
delete_option($pluginManagerInstance->get_option_name('review-content'));
trustindex_plugin_change_step(3);
if (in_array($styleId, [ 17, 21, 52, 53 ])) {
$pluginManagerInstance->noreg_save_css();
}
if (isset($_GET['style_id'])) {
header('Location: admin.php?page='. sanitize_text_field($_GET['page']) .'&tab=free-widget-configurator');
}
exit;
}
else if ($ti_command === 'save-set') {
check_admin_referer('ti-save-set');
update_option($pluginManagerInstance->get_option_name('scss-set'), sanitize_text_field($_REQUEST['set_id']), false);
trustindex_plugin_change_step(4);
$pluginManagerInstance->noreg_save_css(true);
if (isset($_GET['set_id'])) {
header('Location: admin.php?page='. sanitize_text_field($_GET['page']) .'&tab=free-widget-configurator');
}
exit;
}
else if ($ti_command === 'save-filter') {
check_admin_referer('ti-save-filter');
$filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : null;
$filter = json_decode(stripcslashes($filter), true);
update_option($pluginManagerInstance->get_option_name('filter'), $filter, false);
exit;
}
else if ($ti_command === 'save-language') {
check_admin_referer('ti-save-language');
update_option($pluginManagerInstance->get_option_name('lang'), sanitize_text_field($_POST['lang']), false);
delete_option($pluginManagerInstance->get_option_name('review-content'));
exit;
}
else if ($ti_command === 'save-dateformat') {
check_admin_referer('ti-save-dateformat');
update_option($pluginManagerInstance->get_option_name('dateformat'), sanitize_text_field($_POST['dateformat']), false);
exit;
}
else if ($ti_command === 'save-options') {
check_admin_referer('ti-save-options');
$r = 0;
if (isset($_POST['verified-icon'])) {
$r = sanitize_text_field($_POST['verified-icon']);
}
update_option($pluginManagerInstance->get_option_name('verified-icon'), $r, false);
$r = 1;
if (isset($_POST['enable-animation'])) {
$r = sanitize_text_field($_POST['enable-animation']);
}
update_option($pluginManagerInstance->get_option_name('enable-animation'), $r, false);
$r = 1;
if (isset($_POST['show-arrows'])) {
$r = sanitize_text_field($_POST['show-arrows']);
}
update_option($pluginManagerInstance->get_option_name('show-arrows'), $r, false);
$r = 1;
if (isset($_POST['show-header-button'])) {
$r = sanitize_text_field($_POST['show-header-button']);
}
update_option($pluginManagerInstance->get_option_name('show-header-button'), $r, false);
$r = 1;
if (isset($_POST['reviews-load-more'])) {
$r = sanitize_text_field($_POST['reviews-load-more']);
}
update_option($pluginManagerInstance->get_option_name('reviews-load-more'), $r, false);
$r = 1;
if (isset($_POST['show-reviewers-photo'])) {
$r = sanitize_text_field($_POST['show-reviewers-photo']);
}
update_option($pluginManagerInstance->get_option_name('show-reviewers-photo'), $r, false);
$r = 0;
if (isset($_POST['no-rating-text'])) {
$r = sanitize_text_field($_POST['no-rating-text']);
}
update_option($pluginManagerInstance->get_option_name('no-rating-text'), $r, false);
$r = 0;
if (isset($_POST['disable-font'])) {
$r = sanitize_text_field($_POST['disable-font']);
}
update_option($pluginManagerInstance->get_option_name('disable-font'), $r, false);
$r = 1;
if (isset($_POST['show-logos'])) {
$r = sanitize_text_field($_POST['show-logos']);
}
update_option($pluginManagerInstance->get_option_name('show-logos'), $r, false);
$r = 1;
if (isset($_POST['show-stars'])) {
$r = sanitize_text_field($_POST['show-stars']);
}
update_option($pluginManagerInstance->get_option_name('show-stars'), $r, false);
$r = 0;
if (isset($_POST['footer-filter-text'])) {
$r = sanitize_text_field($_POST['footer-filter-text']);
}
update_option($pluginManagerInstance->get_option_name('footer-filter-text'), $r, false);
delete_option($pluginManagerInstance->get_option_name('review-content'));
$pluginManagerInstance->noreg_save_css(true);
exit;
}
else if ($ti_command === 'save-align') {
check_admin_referer('ti-save-align');
update_option($pluginManagerInstance->get_option_name('align'), sanitize_text_field($_POST['align']), false);
$pluginManagerInstance->noreg_save_css(true);
exit;
}
else if ($ti_command === 'save-review-text-mode') {
check_admin_referer('ti-save-review-text-mode');
update_option($pluginManagerInstance->get_option_name('review-text-mode'), sanitize_text_field($_POST['review_text_mode']), false);
$pluginManagerInstance->noreg_save_css(true);
exit;
}
else if ($ti_command === 'save-amp-notice-hide') {
update_option($pluginManagerInstance->get_option_name('amp-hidden-notification'), 1, false);
exit;
}
else if ($ti_command === 'review-manual-download') {
check_admin_referer('ti-download-reviews');
$response = wp_remote_post('https://admin.trustindex.io/source/wordpressPageRequest', [
'body' => [ 'id' => get_option($pluginManagerInstance->get_option_name('review-download-request-id')) ],
'timeout' => '30',
'redirection' => '5',
'blocking' => true
]);
if (is_wp_error($response)) {
$wp_query->set_404();
status_header(404);
}
else {
$json = json_decode(wp_remote_retrieve_body($response), true);
if (isset($json['error']) && $json['error']) {
update_option($pluginManagerInstance->get_option_name('review-download-inprogress'), 'error', false);
}
else if (isset($json['details'])) {
$pluginManagerInstance->save_details($json['details']);
$pluginManagerInstance->save_reviews(isset($json['reviews']) ? $json['reviews'] : []);
delete_option($pluginManagerInstance->get_option_name('review-download-token'));
delete_option($pluginManagerInstance->get_option_name('review-download-inprogress'));
delete_option($pluginManagerInstance->get_option_name('review-manual-download'));
update_option($pluginManagerInstance->get_option_name('download-timestamp'), time() + (86400 * 10), false);
}
else {
$wp_query->set_404();
status_header(404);
}
}
exit;
}
if (isset($_GET['recreate'])) {
check_admin_referer('ti-recreate');
$pluginManagerInstance->uninstall();
$pluginManagerInstance->activate();
header('Location: admin.php?page=' . sanitize_text_field($_GET['page']) .'&tab=free-widget-configurator');
exit;
}
if (isset($_GET['setup_widget'])) {
check_admin_referer('ti-setup-widget');
update_option($pluginManagerInstance->get_option_name('widget-setted-up'), 1, false);
header('Location: admin.php?page=' . sanitize_text_field($_GET['page']) .'&tab=free-widget-configurator');
}
$reviews = [];
if ($pluginManagerInstance->is_noreg_linked()) {
$reviews = $wpdb->get_results('SELECT * FROM `'. $pluginManagerInstance->get_tablename('reviews') .'` ORDER BY date DESC');
}
$isReviewDownloadInProgress = $pluginManagerInstance->is_review_download_in_progress();
$styleId = get_option($pluginManagerInstance->get_option_name('style-id'));
$scssSet = get_option($pluginManagerInstance->get_option_name('scss-set'));
$lang = get_option($pluginManagerInstance->get_option_name('lang'), 'en');
$dateformat = get_option($pluginManagerInstance->get_option_name('dateformat'), 'Y-m-d');
$noRatingText = get_option($pluginManagerInstance->get_option_name('no-rating-text'), $pluginManagerInstance->get_default_no_rating_text($styleId, $scssSet));
$filter = get_option($pluginManagerInstance->get_option_name('filter'), $pluginManagerInstance->get_widget_default_filter());
$verifiedIcon = get_option($pluginManagerInstance->get_option_name('verified-icon'), 0);
$enableAnimation = get_option($pluginManagerInstance->get_option_name('enable-animation'), 1);
$showArrows = get_option($pluginManagerInstance->get_option_name('show-arrows'), 1);
$showHeaderButton = get_option($pluginManagerInstance->get_option_name('show-header-button'), 1);
$reviewsLoadMore = get_option($pluginManagerInstance->get_option_name('reviews-load-more'), 1);
$widgetSettedUp = get_option($pluginManagerInstance->get_option_name('widget-setted-up'), 0);
$disableFont = get_option($pluginManagerInstance->get_option_name('disable-font'), 0);
$align = get_option($pluginManagerInstance->get_option_name('align'), in_array($styleId, [ 36, 37, 38, 39 ]) ? 'center' : 'left');
$reviewTextMode = get_option($pluginManagerInstance->get_option_name('review-text-mode'), 'readmore');
$footerFilterText = get_option($pluginManagerInstance->get_option_name('footer-filter-text'), 0);
$scssSetTmp = $scssSet ? $scssSet : 'light-background';
$showReviewersPhoto = get_option($pluginManagerInstance->get_option_name('show-reviewers-photo'), $pluginManager::$widget_styles[ $scssSetTmp ]['reviewer-photo'] ? 1 : 0);
$showLogos = get_option($pluginManagerInstance->get_option_name('show-logos'), $pluginManager::$widget_styles[ $scssSetTmp ]['hide-logos'] ? 0 : 1);
$showStars = get_option($pluginManagerInstance->get_option_name('show-stars'), $pluginManager::$widget_styles[ $scssSetTmp ]['hide-stars'] ? 0 : 1);
if (!$pluginManagerInstance->is_noreg_linked()) {
$styleId = null;
$scssSet = null;
$widgetSettedUp = null;
}
wp_enqueue_style('trustindex-widget-preview-css', 'https://cdn.trustindex.io/assets/ti-preview-box.css');
$example = 'HairPalace';
$exampleUrl = null;
switch ('google') {
case 'airbnb':
$exampleUrl = 'https://www.airbnb.com/rooms/2861469';
break;
case 'amazon':
$exampleUrl = 'https://www.amazon.com/sp?seller=A2VE8XCDXE9M4H';
break;
case 'arukereso':
$exampleUrl = 'https://www.arukereso.hu/stores/media-markt-online-s66489';
break;
case 'booking':
$exampleUrl = 'https://www.booking.com/hotel/us/four-seasons-san-francisco.html';
break;
case 'capterra':
$exampleUrl = 'https://www.capterra.com/p/192416/MicroStation';
break;
case 'ebay':
$exampleUrl = 'https://www.ebay.com/fdbk/feedback_profile/scarhead1';
break;
case 'foursquare':
$exampleUrl = 'https://foursquare.com/v/lands-end-lookout/4f839a12e4b049ff96c6b29a';
break;
case 'hotels':
$exampleUrl = 'https://www.hotels.com/ho108742';
break;
case 'opentable':
$exampleUrl = 'https://www.opentable.com/r/historic-johns-grill-san-francisco';
break;
case 'szallashu':
$exampleUrl = 'https://revngo.com/ramada-by-wyndham-city-center-hotel-budapest';
break;
case 'thumbtack':
$exampleUrl = 'https://www.thumbtack.com/ca/san-francisco/handyman/steve-switchenko-installations-handyman-services/service/246750705829561442';
break;
case 'tripadvisor':
$exampleUrl = 'https://www.tripadvisor.com/Restaurant_Review-g186338-d5122082-Reviews-Alexander_The_Great-London_England.html';
break;
case 'trustpilot':
$exampleUrl = 'https://www.trustpilot.com/review/generalitravelinsurance.com';
break;
case 'expedia':
$exampleUrl = 'https://www.expedia.com/London-Hotels-The-Hayden-Pub-Rooms.h39457643.Hotel-Information';
break;
case 'yelp':
$exampleUrl = 'https://www.yelp.ie/biz/the-iveagh-gardens-dublin-2';
break;
case 'zillow':
$exampleUrl = 'https://www.zillow.com/profile/NealandNealTeam/#reviews';
break;
case 'sourceForge':
$exampleUrl = 'https://sourceforge.net/software/product/EngageBay/';
break;
case 'wordpressPlugin':
$exampleUrl = 'https://wordpress.org/plugins/wp-reviews-plugin-for-google/';
break;
}
?>
<?php
$stepUrl = '?page='. $_GET['page'] .'&tab=free-widget-configurator&step=%step%';
$stepList = [
$pluginManager::___('Connect %s platform', [ 'Google' ]),
$pluginManager::___('Select Layout'),
$pluginManager::___('Select Style'),
$pluginManager::___('Set up widget'),
$pluginManager::___('Insert code')
];
$stepDone = 0;
$stepCurrent = isset($_GET['step']) ? (int)sanitize_text_field($_GET['step']) : 0;
if ($stepCurrent === 3 && in_array($styleId, [ 17, 21, 52, 53 ])) {
$stepCurrent = 4;
}
if ($widgetSettedUp) {
$stepDone = 4;
}
else if ($scssSet) {
$stepDone = 3;
}
else if ($styleId) {
$stepDone = 2;
}
else if ($pluginManagerInstance->is_noreg_linked()) {
$stepDone = 1;
}
if (!$stepCurrent) {
$stepCurrent = $stepDone + 1;
}
include(plugin_dir_path(__FILE__) . '../include/step-list.php');
?>
<div class="ti-container<?php if ($stepCurrent < 5): ?> ti-narrow-page<?php endif; ?>">
<?php if ($pluginManagerInstance->is_trustindex_connected()): ?>
<div class="ti-notice ti-notice-warning">
<p>
<?php echo $pluginManager::___("You have connected your Trustindex account, so you can find premium functionality under the \"%s\" tab. You no longer need this tab unless you choose the limited but forever free mode.", [ 'Trustindex admin' ]); ?>
</p>
</div>
<?php endif; ?>
<?php if ($isReviewDownloadInProgress === 'error'): ?>
<div class="ti-box ti-notice-error">
<p>
<?php echo $pluginManager::___('While downloading the reviews, we noticed that your connected page is not found.<br />If it really exists, please contact us to resolve the issue or try connect it again.'); ?><br />
</p>
</div>
<?php elseif ($isReviewDownloadInProgress && ($pluginManagerInstance->is_review_manual_download() || !in_array($pluginManagerInstance->shortname, [ 'facebook', 'google' ]))): ?>
<div class="ti-notice ti-notice-warning">
<p>
<?php echo $pluginManager::___('Your reviews are being downloaded.'); ?>
<?php if (!in_array($pluginManagerInstance->shortname, [ 'facebook', 'google' ])): ?>
<?php echo ' ' . $pluginManager::___('This process should only take a few minutes.'); ?>
<?php endif; ?>
<?php if (!count($reviews)): ?>
<br />
<?php echo $pluginManager::___('While you wait, you can start the widget setup with some review templates.'); ?>
<?php endif; ?>
<?php if ($pluginManagerInstance->is_review_manual_download()): ?>
<br />
<a href="#" id="review-manual-download" data-nonce="<?php echo wp_create_nonce('ti-download-reviews'); ?>" class="ti-btn ti-btn-sm ti-tooltip ti-toggle-tooltip" style="margin-top: 5px">
<?php echo $pluginManager::___('Manual download'); ?>
<span class="ti-tooltip-message">
<?php echo $pluginManager::___('Your reviews are being downloaded.'); ?>
<?php if (!in_array($pluginManagerInstance->shortname, [ 'facebook', 'google' ])): ?>
<?php echo ' ' . $pluginManager::___('This process should only take a few minutes.'); ?>
<?php endif; ?>
</span>
</a>
<?php endif; ?>
</p>
</div>
<?php endif; ?>
<?php if ($pluginManager::is_amp_active() && !get_option($pluginManagerInstance->get_option_name('amp-hidden-notification'), 0)): ?>
<div class="ti-notice ti-notice-warning is-dismissible">
<p>
<?php echo $pluginManager::___('Free plugin features are unavailable with AMP plugin.'); ?>
<?php if ($pluginManagerInstance->is_trustindex_connected()): ?>
 <a href="?page=<?php echo esc_attr($_GET['page']); ?>&tab=setup_trustindex_join">Trustindex admin</a>
<?php else: ?>
 <a href="https://www.trustindex.io/ti-redirect.php?a=sys&c=wp-amp" target="_blank"><?php echo $pluginManager::___('Try premium features (like AMP) for free'); ?></a>
<?php endif; ?>
</p>
<button type="button" class="notice-dismiss" data-command="save-amp-notice-hide"></button>
</div>
<?php endif; ?>
<?php if ($stepCurrent === 1): ?>
<h1 class="ti-header-title"><?php echo $pluginManager::___('Connect %s platform', [ 'Google' ]); ?></h1>
<?php if ($pluginManagerInstance->is_noreg_linked()): ?>
<?php $pageDetails = $pluginManagerInstance->getPageDetails(); ?>
<div class="ti-source-box">
<?php if (isset($pageDetails['avatar_url'])): ?>
<img src="<?php echo esc_url($pageDetails['avatar_url']); ?>" />
<?php endif; ?>
<div class="ti-source-info">
<?php if (isset($pageDetails['name']) && $pageDetails['name']): ?>
<strong><?php echo esc_html($pageDetails['name']); ?></strong><br />
<?php endif; ?>
<?php if (isset($pageDetails['address']) && $pageDetails['address']): ?>
<?php echo esc_html($pageDetails['address']); ?><br />
<?php endif; ?>
<a href="<?php echo esc_url($pluginManagerInstance->getPageUrl()); ?>" target="_blank"><?php echo esc_url($pluginManagerInstance->getPageUrl()); ?></a>
</div>
<a href="<?php echo wp_nonce_url('?page='. esc_attr($_GET['page']) .'&tab=free-widget-configurator&command=delete-page', 'ti-delete-page'); ?>" class="ti-btn ti-btn-primary ti-btn-loading-on-click"><?php echo $pluginManager::___('Disconnect'); ?></a>
</div>
<?php else: ?>
<div class="ti-box">
<form method="post" action="" data-platform="google" id="ti-connect-platform-form">
<?php wp_nonce_field('ti-save-page'); ?>
<input type="hidden" name="command" value="save-page" />
<input type="hidden" name="page_details" required="required" id="ti-noreg-page-details" value="" />
<?php
$reviewDownloadToken = get_option($pluginManagerInstance->get_option_name('review-download-token'));
if (!$reviewDownloadToken) {
$reviewDownloadToken = wp_create_nonce('ti-noreg-connect-token');
update_option($pluginManagerInstance->get_option_name('review-download-token'), $reviewDownloadToken, false);
}
?>
<input type="hidden" id="ti-noreg-connect-token" name="ti-noreg-connect-token" value="<?php echo $reviewDownloadToken; ?>" />
<input type="hidden" id="ti-noreg-webhook-url" value="<?php echo $pluginManagerInstance->get_webhook_url(); ?>" />
<input type="hidden" id="ti-noreg-email" value="<?php echo get_option('admin_email'); ?>" />
<input type="hidden" id="ti-noreg-version" value="11.3" />
<input type="hidden" id="ti-noreg-review-download" name="review_download" value="0" />
<input type="hidden" id="ti-noreg-review-request-id" name="review_request_id" value="" />
<input type="hidden" id="ti-noreg-manual-download" name="manual_download" value=0 />
<input type="hidden" id="ti-noreg-page-id" value="" />
<div class="ti-notice ti-notice-info ti-d-none" id="ti-connect-info">
<p><?php echo $pluginManager::___("A popup window should be appear! Please, go to there and continue the steps! (If there is no popup window, you can check the the browser's popup blocker)"); ?></p>
</div>
<?php if (in_array($pluginManagerInstance->shortname, [ 'facebook', 'google' ])): ?>
<a href="#" class="ti-btn btn-connect-public"><?php echo $pluginManager::___('Connect'); ?></a>
<?php else: ?>
<?php
$labelText = $pluginManager::___('%s Business URL', [ "Google" ]);
$infoText = $pluginManager::___("Type your business/company's URL and select from the list");
$errorText = $pluginManager::___('Please add your URL again: this is not a valid %s page.', [ "Google" ]);
$placeholder = $pluginManager::___('e.g.:') . ' ' . esc_attr($exampleUrl);
if (in_array($pluginManagerInstance->shortname, [ 'alibaba', 'aliexpress' ])) {
$labelText = $pluginManager::___('Google Seller Profile URL');
$errorText = $pluginManager::___('Please add your URL again: this is not a valid %s page.', [ $pluginManager::___('Google Seller Profile') ]);
}
if ($pluginManagerInstance->shortname === 'amazon') {
$placeholder = $pluginManager::___('Seller Profile URL, Author Profile URL, Product Page URL, Kindle Product URL');
$infoText = $pluginManager::___('e.g.:') .' <a href="https://www.amazon.it/sp?seller=A2J0MITA2KJVTV" target="_blank">https://www.amazon.it/sp?seller=A2J0MITA2KJVTV</a>,
<a href="https://www.amazon.com/stores/Stephanie-Grisham/author/B09GSWTRF9" target="_blank">https://www.amazon.com/stores/Stephanie-Grisham/author/B09GSWTRF9</a>, <br />
<a href="https://www.amazon.com/gp/product/B09792ZGYZ" target="_blank">https://www.amazon.com/gp/product/B09792ZGYZ</a>,
<a href="https://www.amazon.com/Castle-Air-Donald-Westlake-ebook/dp/B088QLNJFZ" target="_blank">https://www.amazon.com/Castle-Air-Donald-Westlake-ebook/dp/B088QLNJFZ</a>';
$errorText = $pluginManager::___('Please add your URL again: this is not a valid %s page.', [ $pluginManager::___('Google Seller Profile') ]);
}
if ($pluginManagerInstance->shortname === 'ebay') {
$infoText = $pluginManager::___('e.g.:') .' <a href="https://www.ebay.com/fdbk/feedback_profile/scarhead1" target="_blank">https://www.ebay.com/fdbk/feedback_profile/scarhead1</a>';
}
if ($pluginManagerInstance->shortname === 'wordpressPlugin') {
$labelText = $pluginManager::___('Your plugin URL on %s', [ "Google" ]);
$infoText = $pluginManager::___("Type your plugins' URL and select from the list");
}
?>
<div class="ti-notice ti-notice-error ti-d-none" id="ti-connect-error">
<p><?php echo $errorText; ?></p>
</div>
<div class="ti-connect-platform">
<div class="ti-connect-platform-inner">
<label><?php echo $labelText; ?>:</label>
<input class="ti-form-control" placeholder="<?php echo $placeholder; ?>" type="text" />
<a href="#" class="ti-btn"><?php echo $pluginManager::___('Check'); ?></a>
</div>
<span class="ti-info-text"><?php echo $infoText; ?></span>
</div>
<div class="ti-source-box ti-d-none">
<img />
<div class="ti-source-info"></div>
<a href="#" class="ti-btn btn-connect"><?php echo $pluginManager::___('Connect'); ?></a>
</div>
<?php endif; ?>
</form>
</div>
<?php endif; ?>
<h1 class="ti-header-title ti-mt-2"><?php echo $pluginManager::___('Check some %s widget layouts and styles', [ 'Google Reviews' ]); ?></h1>
<?php include(plugin_dir_path(__FILE__) . '../include/demo-widgets.php'); ?>
<?php elseif ($stepCurrent === 2): ?>
<h1 class="ti-header-title"><?php echo $pluginManager::___('Select Layout'); ?></h1>
<?php if (!count($reviews) && !$isReviewDownloadInProgress): ?>
<div class="ti-notice ti-notice-warning" style="margin: 0 0 15px 0">
<p>
<?php echo $pluginManager::___('There are no reviews on your %s platform.', [ 'Google' ]); ?>
</p>
</div>
<?php endif; ?>
<div class="ti-box ti-box-filter">
<label><?php echo $pluginManager::___('Layout'); ?>:</label>
<span class="ti-checkbox">
<input type="radio" name="layout-select" value="" data-ids="" checked>
<label><?php echo $pluginManager::___('All'); ?></label>
</span>
<?php foreach ($pluginManager::$widget_templates['categories'] as $category => $ids): ?>
<span class="ti-checkbox">
<input type="radio" name="layout-select" value="<?php echo esc_attr($category); ?>" data-ids="<?php echo esc_attr($ids); ?>">
<label><?php echo esc_html($pluginManager::___(ucfirst($category))); ?></label>
</span>
<?php endforeach; ?>
</div>
<div class="ti-preview-boxes-container">
<?php foreach ($pluginManager::$widget_templates['templates'] as $id => $template): ?>
<?php
$className = 'ti-full-width';
if (in_array($template['type'], [ 'badge', 'button', 'floating', 'popup', 'sidebar' ])) {
$className = 'ti-half-width';
}
$set = 'light-background';
if (in_array($template['type'], [ 'badge', 'button' ])) {
$set = 'drop-shadow';
}
if (!isset($template['is-active']) || $template['is-active']):
?>
<div class="<?php echo esc_attr($className); ?>">
<div class="ti-box ti-preview-boxes" data-layout-id="<?php echo esc_attr($id); ?>" data-set-id="<?php echo $set; ?>">
<div class="ti-box-inner">
<div class="ti-box-header ti-box-header-normal">
<?php echo $pluginManager::___('Layout'); ?>:
<strong><?php echo esc_html($pluginManager::___($template['name'])); ?></strong>
<a href="<?php echo wp_nonce_url('?page='. esc_attr($_GET['page']) .'&tab=free-widget-configurator&command=save-style&style_id='. esc_attr(urlencode($id)), 'ti-save-style'); ?>" class="ti-btn ti-btn-sm ti-btn-loading-on-click ti-pull-right"><?php echo $pluginManager::___('Select'); ?></a>
<div class="clear"></div>
</div>
<div class="preview">
<?php echo str_replace('ti-widget ti-disabled', 'ti-widget', $pluginManagerInstance->get_noreg_list_reviews(null, true, $id, $set, true, true)); ?>
</div>
</div>
</div>
</div>
<?php endif; ?>
<?php endforeach; ?>
</div>
<?php elseif ($stepCurrent === 3): ?>
<h1 class="ti-header-title"><?php echo $pluginManager::___('Select Style'); ?></h1>
<?php if (!count($reviews) && !$isReviewDownloadInProgress): ?>
<div class="ti-notice ti-notice-warning" style="margin: 0 0 15px 0">
<p>
<?php echo $pluginManager::___('There are no reviews on your %s platform.', [ 'Google' ]); ?>
</p>
</div>
<?php endif; ?>
<?php
$className = 'ti-full-width';
if (in_array($pluginManager::$widget_templates['templates'][ $styleId ]['type'], [ 'badge', 'button', 'floating', 'popup', 'sidebar' ])) {
$className = 'ti-half-width';
}
?>
<div class="ti-preview-boxes-container">
<?php foreach ($pluginManager::$widget_styles as $id => $style): ?>
<?php if (!isset($style['is-active']) || $style['is-active']): ?>
<div class="<?php echo esc_attr($className); ?>">
<div class="ti-box ti-preview-boxes" data-layout-id="<?php echo esc_attr($styleId); ?>" data-set-id="<?php echo esc_attr($id); ?>">
<div class="ti-box-inner">
<div class="ti-box-header ti-box-header-normal">
<?php echo $pluginManager::___('Style'); ?>:
<strong><?php echo $pluginManager::___($style['name']); ?></strong>
<a href="<?php echo wp_nonce_url('?page='. esc_attr($_GET['page']) .'&tab=free-widget-configurator&command=save-set&set_id='. esc_attr(urlencode($id)), 'ti-save-set'); ?>" class="ti-btn ti-btn-sm ti-btn-loading-on-click ti-pull-right"><?php echo $pluginManager::___('Select'); ?></a>
<div class="clear"></div>
</div>
<div class="preview">
<?php echo $pluginManagerInstance->get_noreg_list_reviews(null, true, $styleId, $id, true, true); ?>
</div>
</div>
</div>
</div>
<?php endif; ?>
<?php endforeach; ?>
</div>
<?php elseif ($stepCurrent === 4): ?>
<?php
$widgetType = $pluginManager::$widget_templates[ 'templates' ][ $styleId ]['type'];
$widgetHasReviews = !in_array($widgetType, [ 'button', 'badge' ]) || in_array($styleId, [ 23, 30, 32 ]);
?>
<h1 class="ti-header-title"><?php echo $pluginManager::___('Set up widget'); ?></h1>
<?php if (!count($reviews) && !$isReviewDownloadInProgress): ?>
<div class="ti-notice ti-notice-warning" style="margin: 0 0 15px 0">
<p>
<?php echo $pluginManager::___('There are no reviews on your %s platform.', [ 'Google' ]); ?>
</p>
</div>
<?php endif; ?>
<div class="ti-preview-boxes-container">
<div class="ti-full-width">
<div class="ti-box ti-preview-boxes" data-layout-id="<?php echo esc_attr($styleId); ?>" data-set-id="<?php echo esc_attr($scssSet); ?>">
<div class="ti-box-inner">
<div class="ti-box-header">
<?php echo $pluginManager::___('Widget Preview'); ?>
<?php if (!in_array($styleId, [ 17, 21, 52, 53 ])): ?>
<span class="ti-box-header-normal ti-pull-right">
<?php echo $pluginManager::___('Style'); ?>:
<strong><?php echo esc_html($pluginManager::___($pluginManager::$widget_styles[ $scssSet ]['name'])); ?></strong>
</span>
<?php endif; ?>
<span class="ti-box-header-normal ti-pull-right">
<?php echo $pluginManager::___('Layout'); ?>:
<strong><?php echo esc_html($pluginManager::___($pluginManager::$widget_templates['templates'][ $styleId ]['name'])); ?></strong>
</span>
</div>
<div class="preview ti-widget-editor-preview">
<?php echo $pluginManagerInstance->get_noreg_list_reviews(null, true, null, null, false, true); ?>
<div style="display: none; text-align: center">
<?php echo $pluginManager::___('You do not have reviews with the current filters. <br />Change your filters if you would like to display reviews on your page!'); ?>
</div>
</div>
</div>
</div>
</div>
</div>
<div class="ti-preview-boxes-container">
<div class="ti-full-width">
<div class="ti-box">
<div class="ti-box-inner">
<div class="ti-box-header"><?php echo $pluginManager::___('Widget Settings'); ?></div>
<div class="ti-left-block" id="ti-widget-selects">
<?php if ($widgetHasReviews): ?>
<div class="ti-form-group">
<label><?php echo $pluginManager::___('Filter your ratings'); ?></label>
<div class="ti-select" id="ti-filter-star" data-platform="google" data-nonce="<?php echo wp_create_nonce('ti-save-filter'); ?>">
<font></font>
<ul>
<li data-value="1,2,3,4,5"<?php if (count($filter['stars']) > 2): ?> class="ti-selected"<?php endif; ?>><?php echo $pluginManager::___('Show all'); ?></li>
<li data-value="4,5"<?php if (count($filter['stars']) === 2): ?> class="ti-selected"<?php endif; ?>>&starf;&starf;&starf;&starf; - &starf;&starf;&starf;&starf;&starf;</li>
<li data-value="5"<?php if (count($filter['stars']) === 1): ?> class="ti-selected"<?php endif; ?>><?php echo $pluginManager::___('only'); ?> &starf;&starf;&starf;&starf;&starf;</li>
</ul>
</div>
</div>
<?php endif; ?>
<div class="ti-form-group">
<label><?php echo $pluginManager::___('Select language'); ?></label>
<form method="post" action="">
<input type="hidden" name="command" value="save-language" />
<?php wp_nonce_field('ti-save-language'); ?>
<select class="ti-form-control" name="lang">
<?php foreach ($pluginManager::$widget_languages as $id => $name): ?>
<option value="<?php echo esc_attr($id); ?>" <?php echo $lang == $id ? 'selected' : ''; ?>><?php echo esc_html($name); ?></option>
<?php endforeach; ?>
</select>
</form>
</div>
<?php if ($widgetHasReviews): ?>
<div class="ti-form-group">
<label><?php echo $pluginManager::___('Select date format'); ?></label>
<form method="post" action="">
<input type="hidden" name="command" value="save-dateformat" />
<?php wp_nonce_field('ti-save-dateformat'); ?>
<select class="ti-form-control" name="dateformat">
<?php foreach ($pluginManager::$widget_dateformats as $format): ?>
<option value="<?php echo esc_attr($format); ?>" <?php echo $dateformat == $format ? 'selected' : ''; ?>><?php
switch ($format) {
case 'modern':
$lang = substr(get_locale(), 0, 2);
if (!in_array($lang, array_keys($pluginManager::$widget_date_format_locales))) {
$lang = 'en';
}
$tmp = explode('|', $pluginManager::$widget_date_format_locales[ $lang ]);
echo str_replace([ '%d', '%s' ], [ 2, $tmp[3] ], $tmp[0]);
break;
case 'hide':
echo $pluginManager::___('Hide');
break;
default:
echo date($format);
break;
}
?></option>
<?php endforeach; ?>
</select>
</form>
</div>
<?php if (!in_array($styleId, [ 17, 21, 52, 53 ])): ?>
<div class="ti-form-group">
<label><?php echo $pluginManager::___('Align'); ?></label>
<form method="post" action="">
<input type="hidden" name="command" value="save-align" />
<?php wp_nonce_field('ti-save-align'); ?>
<select class="ti-form-control" name="align">
<?php foreach ([ 'left', 'center', 'right', 'justify' ] as $alignType): ?>
<option value="<?php echo esc_attr($alignType); ?>" <?php echo $alignType == $align ? 'selected' : ''; ?>><?php echo $pluginManager::___($alignType); ?></option>
<?php endforeach; ?>
</select>
</form>
</div>
<div class="ti-form-group">
<label><?php echo $pluginManager::___('Review text'); ?></label>
<form method="post" action="">
<input type="hidden" name="command" value="save-review-text-mode" />
<?php wp_nonce_field('ti-save-review-text-mode'); ?>
<select class="ti-form-control" name="review_text_mode">
<?php foreach ([
'scroll' => 'Scroll',
'readmore' => 'Read more',
'truncated' => 'Truncated'
] as $type => $translated): ?>
<option value="<?php echo esc_attr($type); ?>" <?php echo $type == $reviewTextMode ? 'selected' : ''; ?>><?php echo $pluginManager::___($translated); ?></option>
<?php endforeach; ?>
</select>
</form>
</div>
<?php endif; ?>
<?php endif; ?>
</div>
<div class="ti-right-block">
<form method="post" id="ti-widget-options">
<input type="hidden" name="command" value="save-options" />
<?php wp_nonce_field('ti-save-options'); ?>
<?php if ($widgetHasReviews): ?>
<span class="ti-checkbox ti-checkbox-row">
<input type="checkbox" id="ti-filter-only-ratings" class="no-form-update" name="only-ratings" value="1"<?php if ($filter['only-ratings']): ?> checked<?php endif; ?> />
<label><?php echo $pluginManager::___('Hide reviews without comments'); ?></label>
</span>
<?php endif; ?>
<?php if (in_array($styleId, [ 4, 6, 7, 15, 16, 19, 31, 33, 36, 37, 38, 39, 44 ])): ?>
<span class="ti-checkbox ti-checkbox-row">
<input type="checkbox" name="no-rating-text" value="1"<?php if ($noRatingText): ?> checked<?php endif; ?> />
<label><?php echo $pluginManager::___('Hide rating text'); ?></label>
</span>
<?php endif; ?>
<?php if ($widgetHasReviews): ?>
<span class="ti-checkbox ti-checkbox-row">
<input type="checkbox" name="footer-filter-text" value="1"<?php if ($footerFilterText): ?> checked<?php endif; ?> />
<label><?php echo $pluginManager::___('Show minimum review filter condition'); ?></label>
</span>
<?php endif; ?>
<?php if (in_array($styleId, [ 8, 10, 13 ])): ?>
<span class="ti-checkbox ti-checkbox-row">
<input type="checkbox" name="show-header-button" value="1"<?php if ($showHeaderButton): ?> checked<?php endif; ?> />
<label><?php echo $pluginManager::___('Show write review button'); ?></label>
</span>
<?php endif; ?>
<?php if (in_array($styleId, [ 8, 16, 18, 31, 33 ])): ?>
<span class="ti-checkbox ti-checkbox-row">
<input type="checkbox" name="reviews-load-more" value="1"<?php if ($reviewsLoadMore): ?> checked<?php endif; ?> />
<label><?php echo $pluginManager::___('Show "Load more" button'); ?></label>
</span>
<?php endif; ?>
<?php if ($widgetHasReviews && in_array(ucfirst($pluginManagerInstance->shortname), $pluginManager::$verified_platforms)): ?>
<span class="ti-checkbox ti-checkbox-row">
<input type="checkbox" name="verified-icon" value="1"<?php if ($verifiedIcon): ?> checked<?php endif; ?> />
<label><?php echo $pluginManager::___('Show verified review icon'); ?></label>
</span>
<?php endif; ?>
<?php if (in_array($widgetType, [ 'slider', 'sidebar' ]) && !in_array($styleId, [ 8, 9, 10, 18, 19, 37, 54 ])): ?>
<span class="ti-checkbox ti-checkbox-row">
<input type="checkbox" name="show-arrows" value="1"<?php if ($showArrows): ?> checked<?php endif; ?> />
<label><?php echo $pluginManager::___('Show navigation arrows'); ?></label>
</span>
<?php endif; ?>
<?php if ($widgetHasReviews && $styleId != 52): ?>
<span class="ti-checkbox ti-checkbox-row">
<input type="checkbox" name="show-reviewers-photo" value="1"<?php if ($showReviewersPhoto): ?> checked<?php endif; ?> />
<label><?php echo $pluginManager::___("Show reviewers' photo"); ?></label>
</span>
<span class="ti-checkbox ti-checkbox-row ti-disabled">
<input type="checkbox" value="1" disabled />
<label class="ti-tooltip">
<?php echo $pluginManager::___("Show reviewers' photos locally, from a single image (less requests)"); ?>
<span class="ti-tooltip-message"><?php echo $pluginManager::___('Paid package feature'); ?></span>
</label>
</span>
<?php endif; ?>
<?php if (!in_array($widgetType, [ 'floating' ])): ?>
<span class="ti-checkbox ti-checkbox-row">
<input type="checkbox" name="enable-animation" value="1"<?php if ($enableAnimation): ?> checked<?php endif; ?> />
<label><?php echo $pluginManager::___('Enable mouseover animation'); ?></label>
</span>
<?php endif; ?>
<span class="ti-checkbox ti-checkbox-row">
<input type="checkbox" name="disable-font" value="1"<?php if ($disableFont): ?> checked<?php endif; ?> />
<label><?php echo $pluginManager::___("Use site's font"); ?></label>
</span>
<?php if ($widgetHasReviews): ?>
<span class="ti-checkbox ti-checkbox-row">
<input type="checkbox" name="show-logos" value="1"<?php if ($showLogos): ?> checked<?php endif;?> />
<label><?php echo $pluginManager::___('Show platform logos'); ?></label>
</span>
<?php if (!$pluginManagerInstance->is_ten_scale_rating_platform()): ?>
<span class="ti-checkbox ti-checkbox-row">
<input type="checkbox" name="show-stars" value="1"<?php if ($showStars): ?> checked<?php endif;?> />
<label><?php echo $pluginManager::___('Show platform stars'); ?></label>
</span>
<?php endif; ?>
<?php endif; ?>
</form>
</div>
<div class="clear"></div>
<div class="ti-box-footer">
<a href="<?php echo wp_nonce_url('?page='. esc_attr($_GET['page']) .'&tab=free-widget-configurator&setup_widget', 'ti-setup-widget'); ?>" class="ti-btn ti-btn-loading-on-click ti-pull-right"><?php echo $pluginManager::___('Save and get code'); ?></a>
<div class="clear"></div>
</div>
</div>
</div>
</div>
</div>
<?php else: ?>
<h1 class="ti-header-title"><?php echo $pluginManager::___('Insert code'); ?></h1>
<?php if (!count($reviews) && !$isReviewDownloadInProgress): ?>
<div class="ti-notice ti-notice-warning" style="margin: 0 0 15px 0">
<p>
<?php echo $pluginManager::___('There are no reviews on your %s platform.', [ 'Google' ]); ?>
</p>
</div>
<?php endif; ?>
<div class="ti-box">
<div class="ti-box-header"><?php echo $pluginManager::___('Insert this shortcode into your website'); ?></div>
<div class="ti-form-group" style="margin-bottom: 2px">
<label>Shortcode</label>
<code class="ti-shortcode">[<?php echo $pluginManagerInstance->get_shortcode_name(); ?> no-registration=google]</code>
<a href=".ti-shortcode" class="ti-btn ti-tooltip ti-toggle-tooltip btn-copy2clipboard">
<?php echo $pluginManager::___('Copy to clipboard'); ?>
<span class="ti-tooltip-message">
<span style="color: #00ff00; margin-right: 2px">✓</span>
<?php echo $pluginManager::___('Copied'); ?>
</span>
</a>
</div>
<div class="ti-info-text"><?php echo $pluginManager::___('Copy and paste this shortcode into post, page or widget.'); ?></div>
</div>
<?php if (!get_option($pluginManagerInstance->get_option_name('rate-us-feedback'), 0)): ?>
<?php include(plugin_dir_path(__FILE__) . '../include/rate-us-feedback-box.php'); ?>
<?php endif; ?>
<?php
$tiCampaign1 = 'wp-google-1';
$tiCampaign2 = 'wp-google-2';
include(plugin_dir_path(__FILE__) . '../include/get-more-customers-box.php');
?>
<?php endif; ?>
</div>