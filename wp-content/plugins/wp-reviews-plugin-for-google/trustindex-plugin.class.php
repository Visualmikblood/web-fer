<?php
class TrustindexPlugin_google
{
private $plugin_file_path;
private $plugin_name;
private $platform_name;
public $shortname;
private $version;
public function __construct($shortname, $pluginFilePath, $version, $pluginName, $platformName)
{
$this->shortname = $shortname;
$this->plugin_file_path = $pluginFilePath;
$this->version = $version;
$this->plugin_name = $pluginName;
$this->platform_name = $platformName;
}
public function getPluginTabs()
{
$tabs = [];
if ($this->is_trustindex_connected()) {
$tabs[] = [
'place' => 'left',
'slug' => 'trustindex-admin',
'name' => 'Trustindex admin'
];
}
$tabs[] = [
'place' => 'left',
'slug' => 'free-widget-configurator',
'name' => self::___('Free Widget Configurator')
];
if ($this->is_noreg_linked()) {
$tabs[] = [
'place' => 'left',
'slug' => 'my-reviews',
'name' => self::___('My reviews')
];
}
$tabs[] = [
'place' => 'left',
'slug' => 'get-reviews',
'name' => self::___('Get Reviews')
];
$tabs[] = [
'place' => 'left',
'slug' => 'rate-us',
'name' => self::___('Rate Us')
];
if (!$this->is_trustindex_connected()) {
$tabs[] = [
'place' => 'left',
'slug' => 'get-more-features',
'name' => self::___('Get more Features')
];
$tabs[] = [
'place' => 'left',
'slug' => 'trustindex-admin',
'name' => self::___('Log In')
];
}
$tabs[] = [
'place' => 'right',
'slug' => 'feature-request',
'name' => self::___('Feature request')
];
$tabs[] = [
'place' => 'right',
'slug' => 'advanced',
'name' => self::___('Advanced')
];
return $tabs;
}
public function getShortName()
{
return $this->shortname;
}
public function get_webhook_action()
{
return 'trustindex_reviews_hook_' . $this->shortname;
}
public function get_webhook_url()
{
return admin_url('admin-ajax.php') . '?action='. $this->get_webhook_action();
}
public function is_review_download_in_progress()
{
return get_option($this->get_option_name('review-download-inprogress'), 0);
}
public function is_review_manual_download()
{
return get_option($this->get_option_name('review-manual-download'), 0);
}
public function delete_async_request()
{
$requestId = get_option($this->get_option_name('review-download-request-id'));
if (!$requestId) {
return false;
}
wp_remote_post('https://admin.trustindex.io/source/wordpressPageRequest', [
'body' => [
'is_delete' => 1,
'id' => $requestId
],
'timeout' => '30',
'redirection' => '5',
'blocking' => true
]);
return true;
}
public function save_details($tmp)
{
$name = isset($tmp['name']) ? sanitize_text_field(stripslashes($tmp['name'])) : "";
$name = json_encode($name);
$details = [
'id' => isset($tmp['page_id']) ? $tmp['page_id'] : $tmp['id'],
'name' => $name,
'address' => isset($tmp['address']) ? sanitize_text_field(stripslashes($tmp['address'])) : "",
'avatar_url' => isset($tmp['avatar_url']) ? sanitize_text_field(stripslashes($tmp['avatar_url'])) : "",
'rating_number' => isset($tmp['reviews']['count']) ? (int)$tmp['reviews']['count'] : 0,
'rating_score' => isset($tmp['reviews']['score']) ? (float)$tmp['reviews']['score'] : 0,
];
if (isset($tmp['access_token'])) {
$details['access_token'] = sanitize_text_field(stripslashes($tmp['access_token']));
}
update_option($this->get_option_name('page-details'), $details, false);
}
public function save_reviews($tmp)
{
global $wpdb;
$tableName = $this->get_tablename('reviews');
$wpdb->query('TRUNCATE `'. $tableName .'`');
if ($wpdb->last_error) {
throw new Exception('DB truncate failed: '. $wpdb->last_error);
}
foreach ($tmp as $i => $review) {
foreach ($review as $key => $value) {
if (is_array($value)) {
if ($key === 'reviewer') {
$review[ $key ] = array_map(function($v) {
return $v ? sanitize_text_field(stripslashes($v)) : $v;
}, $value);
}
else {
unset($review[ $key ]);
}
}
else if ($key === 'text') {
$review[ $key ] = $value ? wp_kses_post(stripslashes($value)) : $value;
}
else {
$review[ $key ] = $value ? sanitize_text_field(stripslashes($value)) : $value;
}
}
$wpdb->insert($tableName, [
'user' => $review['reviewer']['name'],
'user_photo' => $review['reviewer']['avatar_url'],
'text' => $review['text'],
'rating' => $review['rating'] ? $review['rating'] : 5,
'date' => substr($review['created_at'], 0, 10),
'reviewId' => isset($review['id']) ? $review['id'] : null,
'reply' => isset($review['reply']) ? $review['reply'] : ""
]);
if ($wpdb->last_error) {
throw new Exception('DB instert failed: '. $wpdb->last_error);
}
}
update_option($this->get_option_name('review-download-modal'), 0, false);
}


public function get_plugin_dir()
{
return plugin_dir_path($this->plugin_file_path);
}
public function get_plugin_file_url($file, $addVersioning = true)
{
$url = plugins_url($file, $this->plugin_file_path);
if ($addVersioning) {
$appendMark = strpos($url, '?') === FALSE ? '?' : '&';
$url .= $appendMark . 'ver=' . $this->version;
}
return $url;
}
public function get_plugin_slug()
{
return basename($this->get_plugin_dir());
}

/* I18N
 * make sure you do not use any translatable string function calls before the call to our ‘load_plugin_textdomain’
 */
public function loadI18N()
{
load_plugin_textdomain('trustindex', false, $this->get_plugin_slug() . DIRECTORY_SEPARATOR . 'languages');
}
public static function ___($text, $params = null)
{
if (!is_array($params)) {
$params = func_get_args();
$params = array_slice($params, 1);
}
return vsprintf(__($text, 'trustindex'), $params);
}


public function output_buffer()
{
ob_start();
}


public function uninstall()
{
$this->delete_async_request();
include $this->get_plugin_dir() . 'include' . DIRECTORY_SEPARATOR . 'uninstall.php';
if (is_file($this->getCssFile())) {
unlink($this->getCssFile());
}
}


public function activate()
{
include $this->get_plugin_dir() . 'include' . DIRECTORY_SEPARATOR . 'activate.php';
$this->setNotificationParam('not-using-no-connection', 'timestamp', time() + 86400);
if (!$this->getNotificationParam('rate-us', 'hidden', false) && $this->getNotificationParam('rate-us', 'active', true)) {
$this->setNotificationParam('rate-us', 'active', true);
$this->setNotificationParam('rate-us', 'timestamp', time() + 86400);
}
update_option($this->get_option_name('activation-redirect'), 1, false);
}
public function deactivate()
{
update_option($this->get_option_name('active'), '0');
}
public function is_enabled()
{
return get_option($this->get_option_name('active'), 0);
}

public function add_setting_menu()
{
global $menu, $submenu;
$permission = 'edit_pages';
$settingsPageUrl = $this->get_plugin_slug() . "/settings.php";
$settingsPageTitle = $this->platform_name . ' ';
if (function_exists('mb_strtolower')) {
$settingsPageTitle .= mb_strtolower(self::___('Reviews'));
}
else {
$settingsPageTitle .= strtolower(self::___('Reviews'));
}
$topMenu = false;
foreach ($menu as $key => $item) {
if ($item[0] === 'Trustindex.io') {
$topMenu = $item;
break;
}
}
if ($topMenu === false) {
add_menu_page(
$settingsPageTitle,
'Trustindex.io',
$permission,
$settingsPageUrl,
'',
$this->get_plugin_file_url('static/img/trustindex-sign-logo.png')
);
}
else {
if (!isset($submenu[ $topMenu[2] ])) {
add_submenu_page(
$topMenu[2],
'Trustindex.io',
$topMenu[3],
$permission,
$topMenu[2]
);
}
add_submenu_page(
$topMenu[2],
'Trustindex.io',
$settingsPageTitle,
$permission,
$settingsPageUrl
);
}
}
public function add_plugin_action_links($links, $file)
{
if (basename($file) === basename($this->plugin_file_path)) {
$newItem2 = '<a target="_blank" href="https://www.trustindex.io" target="_blank">by <span style="background-color: #4067af; color: white; font-weight: bold; padding: 1px 8px;">Trustindex.io</span></a>';
$newItem1 = '<a href="' . admin_url('admin.php?page=' . $this->get_plugin_slug() . '/settings.php') . '">' . self::___('Settings') . '</a>';
array_unshift($links, $newItem2, $newItem1);
}
return $links;
}
public function add_plugin_meta_links($meta, $file)
{
if (basename($file) === basename($this->plugin_file_path)) {
$meta[] = "<a href='http://wordpress.org/support/view/plugin-reviews/".$this->get_plugin_slug()."' target='_blank' rel='noopener noreferrer' title='" . self::___( 'Rate our plugin') . ': '.$this->plugin_name . "'>" . self::___('Rate our plugin') . '</a>';
}
return $meta;
}


public function init_widget()
{
if (!class_exists('TrustindexWidget_'.$this->shortname)) {
require $this->get_plugin_dir() . 'trustindex-'. $this->shortname .'-widget.class.php';
}
}
public function register_widget()
{
return register_widget('TrustindexWidget_'.$this->shortname);
}


public function get_option_name($opt_name)
{
if (!in_array($opt_name, $this->get_option_names())) {
echo 'Option not registered in plugin (Trustindex class)';
}
if (in_array($opt_name, [ 'subscription-id', 'proxy-check' ])) {
return 'trustindex-'. $opt_name;
}
else {
return 'trustindex-'. $this->shortname .'-'. $opt_name;
}
}
public function get_option_names()
{
return [
'active',
'version',
'page-details',
'subscription-id',
'proxy-check',
'style-id',
'review-content',
'filter',
'scss-set',
'css-content',
'lang',
'no-rating-text',
'dateformat',
'rate-us-feedback',
'verified-icon',
'enable-animation',
'show-arrows',
'content-saved-to',
'show-reviewers-photo',
'download-timestamp',
'widget-setted-up',
'disable-font',
'show-logos',
'show-stars',
'load-css-inline',
'align',
'review-text-mode',
'amp-hidden-notification',
'review-download-token',
'review-download-inprogress',
'review-download-request-id',
'review-download-modal',
'review-download-is-connecting',
'review-manual-download',
'reply-generated',
'footer-filter-text',
'show-header-button',
'reviews-load-more',
'activation-redirect',
'notifications',
];
}
public function setNotificationParam($type, $param, $value)
{
$notifications = get_option($this->get_option_name('notifications'), []);
if (!isset($notifications[ $type ])) {
$notifications[ $type ] = [];
}
$notifications[ $type ][ $param ] = $value;
update_option($this->get_option_name('notifications'), $notifications, false);
}
public function getNotificationParam($type, $param, $default = null)
{
$notifications = get_option($this->get_option_name('notifications'), []);
if (!isset($notifications[ $type ]) || !isset($notifications[ $type ][ $param ])) {
return $default;
}
return $notifications[ $type ][ $param ];
}
public function isNotificationActive($type, $notifications = null)
{
if (!$notifications) {
$notifications = get_option($this->get_option_name('notifications'), []);
}
if (
!isset($notifications[ $type ]) ||
!isset($notifications[ $type ]['active']) || !$notifications[ $type ]['active'] ||
(isset($notifications[ $type ]['hidden']) && $notifications[ $type ]['hidden']) ||
(isset($notifications[ $type ]['timestamp']) && $notifications[ $type ]['timestamp'] > time())
) {
return false;
}
return true;
}
public function getNotificationText($type)
{
$platformName = $this->get_platform_name($this->getShortName());
switch ($type) {
case 'not-using-no-connection':
return self::___('Display %s reviews on your website.', [ $platformName ]);
case 'not-using-no-widget':
return self::___('Build trust and display your %s reviews on your website.', [ $platformName ]);
case 'review-download-available':
return self::___('You can update your %s reviews.', [ $platformName ]);
case 'review-download-finished':
return self::___('Your new %s reviews have been downloaded.', [ $platformName ]);
case 'rate-us':
return self::___("Hello, I am happy to see that you've been using our <strong>%s</strong> plugin for a while now!", [ $this->plugin_name ]) . '<br />' .
self::___('Could you please help us and give it a 5-star rating on WordPress?') . '<br /><br />' .
self::___('-- Thanks, Gabor M.');
}
}
public function getNotificationButtonText($type)
{
$platformName = $this->get_platform_name($this->getShortName());
switch ($type) {
case 'not-using-no-connection':
return self::___('Create a free %s widget! »', [ $platformName ]);
case 'not-using-no-widget':
return self::___('Embed the %s reviews widget! »', [ $platformName ]);
case 'review-download-available':
return self::___('Download your latest reviews! »');
case 'review-download-finished':
return self::___('Reply with %s! »', [ 'ChatGPT' ]);
}
}
public function get_platforms()
{
return array (
 0 => 'facebook',
 1 => 'google',
 2 => 'tripadvisor',
 3 => 'yelp',
 4 => 'booking',
 5 => 'amazon',
 6 => 'arukereso',
 7 => 'airbnb',
 8 => 'hotels',
 9 => 'opentable',
 10 => 'foursquare',
 11 => 'capterra',
 12 => 'szallashu',
 13 => 'thumbtack',
 14 => 'expedia',
 15 => 'zillow',
 16 => 'wordpressPlugin',
 17 => 'aliexpress',
 18 => 'alibaba',
 19 => 'sourceForge',
 20 => 'ebay',
);
}
private $plugin_slugs = array (
 'facebook' => 'free-facebook-reviews-and-recommendations-widgets',
 'google' => 'wp-reviews-plugin-for-google',
 'tripadvisor' => 'review-widgets-for-tripadvisor',
 'yelp' => 'reviews-widgets-for-yelp',
 'booking' => 'review-widgets-for-booking-com',
 'amazon' => 'review-widgets-for-amazon',
 'arukereso' => 'review-widgets-for-arukereso',
 'airbnb' => 'review-widgets-for-airbnb',
 'hotels' => 'review-widgets-for-hotels-com',
 'opentable' => 'review-widgets-for-opentable',
 'foursquare' => 'review-widgets-for-foursquare',
 'capterra' => 'review-widgets-for-capterra',
 'szallashu' => 'review-widgets-for-szallas-hu',
 'thumbtack' => 'widgets-for-thumbtack-reviews',
 'expedia' => 'widgets-for-expedia-reviews',
 'zillow' => 'widgets-for-zillow-reviews',
 'wordpressPlugin' => 'reviews-widgets',
 'aliexpress' => 'widgets-for-aliexpress-reviews',
 'alibaba' => 'widgets-for-alibaba-reviews',
 'sourceForge' => 'widgets-for-sourceforge-reviews',
 'ebay' => 'widgets-for-ebay-reviews',
);
public function get_plugin_slugs()
{
return array_values($this->plugin_slugs);
}


public static function get_noticebox($type, $message)
{
return '<div class="ti-notice ti-notice-'. $type .' is-dismissible"><p>'. self::___($message) .'</p><button type="button" class="notice-dismiss"></button></div>';
}
public static function get_alertbox($type, $content, $newline_content = true)
{
$types = [
'warning' => [
'css' => 'color: #856404; background-color: #fff3cd; border-color: #ffeeba;',
'icon' => 'dashicons-warning'
],
'info' => [
'css' => 'color: #0c5460; background-color: #d1ecf1; border-color: #bee5eb;',
'icon' => 'dashicons-info'
],
'error' => [
'css' => 'color: #721c24; background-color: #f8d7da; border-color: #f5c6cb;',
'icon' => 'dashicons-info'
]
];
return '<div style="margin:20px 0px; padding:10px; '. $types[ $type ]['css'] .' border-radius: 5px">'
. '<span class="dashicons '. $types[ $type ]['icon'] .'"></span> <strong>'. strtoupper(self::___($type)) .'</strong>'
. ($newline_content ? '<br />' : "")
. $content
. '</div>';
}


public function get_trustindex_widget($ti_id)
{
wp_enqueue_script('trustindex-js', 'https://cdn.trustindex.io/loader.js', [], false, true);
$ti_id = preg_replace('/[^a-zA-Z0-9]/', '', $ti_id);
return '<div data-src="https://cdn.trustindex.io/loader.js?'. $ti_id .'"></div>';
}
public function get_shortcode_name()
{
return 'trustindex';
}
public function init_shortcode()
{
$tag = $this->get_shortcode_name();
$currentVersion = (float)$this->version;
if (shortcode_exists($tag)) {
$initedVersion = (float)get_option('trustindex-core-shortcode-inited', 0);
if (!$initedVersion || $initedVersion <= $currentVersion) {
remove_shortcode($tag);
}
else {
return false;
}
}
update_option('trustindex-core-shortcode-inited', $currentVersion, false);
add_shortcode($tag, [ $this, 'shortcode_func' ]);
}
public function shortcode_func($atts)
{
$atts = shortcode_atts([ 'data-widget-id' => null, 'no-registration' => null ], $atts);
if (isset($atts['data-widget-id']) && $atts['data-widget-id']) {
return $this->get_trustindex_widget($atts['data-widget-id']);
}
else if (isset($atts['no-registration']) && $atts['no-registration']) {
$forcePlatform = $atts['no-registration'];
if (substr($forcePlatform, 0, 5) !== 'trust' && substr($forcePlatform, -4) !== 'ilot' && !in_array($forcePlatform, $this->get_platforms())) {
$avPlatforms = $this->get_platforms();
$forcePlatform = $avPlatforms[0];
}
$filePath = __FILE__;
if (isset($this->plugin_slugs[ $forcePlatform ])) {
$filePath = preg_replace('/[^\/\\\\]+([\\\\\/]trustindex-plugin\.class\.php)/', $this->plugin_slugs[ $forcePlatform ] . '$1', $filePath);
}
$chosedPlatform = new self($forcePlatform, $filePath, "do-not-care-11.3", "do-not-care-Widgets for Google Reviews", "do-not-care-Google");
$chosedPlatform->setNotificationParam('not-using-no-widget', 'active', false);
if (!$chosedPlatform->is_noreg_linked()) {
return $this->error_box_for_admins(self::___('You have to connect your business (%s)!', [ $forcePlatform ]));
}
else {
return $chosedPlatform->get_noreg_list_reviews($forcePlatform);
}
}
else {
return $this->error_box_for_admins(self::___('Your shortcode is deficient: Trustindex Widget ID is empty! Example: ') . '<br /><code>['.$this->get_shortcode_name().' data-widget-id="478dcc2136263f2b3a3726ff"]</code>');
}
}
public function error_box_for_admins($text)
{
if (!current_user_can('manage_options')) {
return "";
}
return self::get_alertbox('error', ' @ <strong>'. self::___('Trustindex plugin') .'</strong> <i style="opacity: 0.65">('. self::___('This message is not be visible to visitors in public mode.') .')</i><br /><br />'. $text, false);
}

/* WITHOUT REG MODE HELPERS
 *
 * @force_platform - default ($this->shortname) platform name can be overriden, because of the general shortcode.
 * (For example: if Yelp plugin loaded first --> yelp plugin will load the widget, other Trustindex plugins will not load shortcode.
 * But Yelp plugins's shortcode should be able to operate other platforms' (ie.: Google) shortcodes, too. )
 */
public function is_noreg_linked()
{
$pageDetails = $this->getPageDetails();
return $pageDetails && !empty($pageDetails);
}
public function getPageDetails()
{
$pageDetails = get_option($this->get_option_name('page-details'));
if (isset($pageDetails['name']) && $this->isJson($pageDetails['name'])) {
$pageDetails['name'] = json_decode($pageDetails['name']);
}
return $pageDetails;
}
public function noreg_save_css($setChange = false)
{
$styleId = (int)get_option($this->get_option_name('style-id'), 4);
$setId = get_option($this->get_option_name('scss-set'));
$args = [
'timeout' => '20',
'redirection' => '5',
'blocking' => true
];
add_filter('https_ssl_verify', '__return_false');
add_filter('block_local_requests', '__return_false');
$params = [
'platform' => $this->shortname,
'layout_id' => $styleId,
'overrides' => [
'nav' => get_option($this->get_option_name('show-arrows'), 1) ? 'true' : 'false',
'hover-anim' => get_option($this->get_option_name('enable-animation'), 1) ? true : false,
'enable-font' => get_option($this->get_option_name('disable-font'), 0) ? false : true,
'review-text-mode' => get_option($this->get_option_name('review-text-mode'), 'readmore')
]
];
if (in_array($styleId, [ 36, 37, 38, 39 ])) {
$params['overrides']['content-align'] = get_option($this->get_option_name('align'), 'center');
}
else {
$params['overrides']['text-align'] = get_option($this->get_option_name('align'), 'left');
}
if ($setChange) {
$params['set_id'] = $setId;
}
$url = 'https://admin.trustindex.io/' . 'api/getLayoutScss?' . http_build_query($params);
$server_output = $this->post_request($url, [
'timeout' => '20',
'redirection' => '5',
'blocking' => true
]);
if ($server_output[0] !== '[' && $server_output[0] !== '{') {
$server_output = substr($server_output, strpos($server_output, '('));
$server_output = trim($server_output,'();');
}
$server_output = json_decode($server_output, true);
if (!$setChange) {
update_option($this->get_option_name('scss-set'), $server_output['default'], false);
}
if ($server_output['css']) {
if (in_array($styleId, [ 17, 21, 52, 53 ])) {
$server_output['css'] .= '.ti-preview-box { position: initial !important }';
}
update_option($this->get_option_name('css-content'), $server_output['css'], false);
$this->handleCssFile();
}
return $server_output;
}
public function plugin_loaded()
{
global $wpdb;
$version = $this->version;

if ($this->is_noreg_linked()) {
$tableName = $this->get_tablename('reviews');

if ($version >= 6.3 && count($wpdb->get_results('SHOW COLUMNS FROM `'. $tableName .'` LIKE "highlight"')) === 0) {
$wpdb->query('ALTER TABLE `'. $tableName .'` ADD highlight VARCHAR(11) NULL AFTER rating');
}

if ($version >= 10.1) {
if (count($wpdb->get_results('SHOW COLUMNS FROM `'. $tableName .'` LIKE "reply"')) === 0) {
$wpdb->query('ALTER TABLE `'. $tableName .'` ADD reply TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL AFTER date');
if (count($wpdb->get_results('SHOW COLUMNS FROM `'. $tableName .'` LIKE "replied"')) === 1) {
$wpdb->query('ALTER TABLE `'. $tableName .'` DROP replied');
}
if (in_array($this->shortname, [ 'facebook', 'google' ])) {
delete_option($this->get_option_name('download-timestamp'));
delete_option($this->get_option_name('review-download-inprogress'));
delete_option($this->get_option_name('review-manual-download'));
delete_option($this->get_option_name('review-download-request-id'));
delete_option($this->get_option_name('review-download-modal'));
}
}
if (count($wpdb->get_results('SHOW COLUMNS FROM `'. $tableName .'` LIKE "reviewId"')) === 0) {
$wpdb->query('ALTER TABLE `'. $tableName .'` ADD reviewId TEXT NULL AFTER date');
}
}
}
if ($this->is_noreg_linked() && get_option($this->get_option_name('review-content'))) {
$contentVersion = get_option($this->get_option_name('content-saved-to'));
if (!$contentVersion || $contentVersion != $version) {
update_option($this->get_option_name('content-saved-to'), $version, false);
delete_option($this->get_option_name('review-content'));
$this->noreg_save_css(true);
}
}
$this->handleCssFile();
if (get_option($this->get_option_name('activation-redirect'))) {
delete_option($this->get_option_name('activation-redirect'));
wp_redirect(admin_url('admin.php?page=' . $this->get_plugin_slug() . '/settings.php'));
exit;
}
$this->loadI18N();
if (
$this->is_noreg_linked() &&
!$this->is_review_download_in_progress() &&
get_option($this->get_option_name('download-timestamp'), time()) < time() &&
!$this->getNotificationParam('review-download-available', 'hidden') &&
$this->getNotificationParam('review-download-available', 'do-check', true)
) {
$this->setNotificationParam('review-download-available', 'active', true);
$this->setNotificationParam('review-download-available', 'do-check', false);
}
if (
!$this->is_noreg_linked() &&
!$this->getNotificationParam('not-using-no-connection', 'active', false) &&
$this->getNotificationParam('not-using-no-connection', 'do-check', true)
) {
$this->setNotificationParam('not-using-no-connection', 'active', true);
$this->setNotificationParam('not-using-no-connection', 'do-check', false);
}
if ( !class_exists('TrustindexGutenbergPlugin') && function_exists( 'register_block_type' ) && !WP_Block_Type_Registry::get_instance()->is_registered( 'trustindex/block-selector' )) {
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'block-editor' . DIRECTORY_SEPARATOR . 'block-editor.php';
TrustindexGutenbergPlugin::instance();
}
$oldRateUs = get_option('trustindex-'. $this->shortname .'-rate-us');
if ($oldRateUs) {
if ($oldRateUs === 'hide') {
$this->setNotificationParam('rate-us', 'hidden', true);
}
else {
$this->setNotificationParam('rate-us', 'active', true);
$this->setNotificationParam('rate-us', 'timestamp', $oldRateUs);
}
}
$oldNotificationEmail = get_option('trustindex-'. $this->shortname .'-review-download-notification-email');
if ($oldNotificationEmail) {
$this->setNotificationParam('review-download-finished', 'email', $oldNotificationEmail);
}
$usedOptions = [];
foreach ($this->get_option_names() as $optName) {
$usedOptions []= $this->get_option_name($optName);
}
$wpdb->query('DELETE FROM `'. $wpdb->options .'` WHERE option_name LIKE "trustindex-'. $this->shortname .'-%" AND option_name NOT IN ("'. implode('", "', $usedOptions) .'")');
}
public function getCssFile($returnOnlyFile = false)
{
$file = 'trustindex-'. $this->shortname .'-widget.css';
if ($returnOnlyFile) {
return $file;
}
$uploadDir = wp_upload_dir();
return trailingslashit($uploadDir['basedir']) . $file;
}
public function handleCssFile()
{
$css = get_option($this->get_option_name('css-content'));
if (!$css) {
return;
}
if (get_option($this->get_option_name('load-css-inline'), 0)) {
return;
}
$fileExists = is_file($this->getCssFile());
$success = false;
$errorType = null;
$errorMessage = "";
if ($fileExists && !is_readable($this->getCssFile())) {
$errorType = 'permission';
}
else {
if ($fileExists && $css === file_get_contents($this->getCssFile())) {
return;
}
require_once(ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'file.php');
global $wp_filesystem;
set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, $err_context = []) {
throw new ErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
}, E_WARNING);
add_filter('filesystem_method', array($this, 'filter_filesystem_method'));
WP_Filesystem();
try {
$success = $wp_filesystem->put_contents($this->getCssFile(), $css, 0777);
}
catch (Exception $e) {
if (strpos($e->getMessage(), 'Permission denied') !== FALSE) {
$errorType = 'permission';
}
else {
$errorType = 'filesystem';
$errorMessage = $e->__toString();
}
}
restore_error_handler();
remove_filter('filesystem_method', array($this, 'filter_filesystem_method'));
}
if (!$success) {
add_action('admin_notices', function() use ($fileExists, $errorType, $errorMessage) {
$html = '
<div class="notice notice-error" style="margin: 5px 0 15px">
<p>' .
'<strong>'. self::___('ERROR with the following plugin:') .'</strong> '. self::___($this->plugin_name) .'<br /><br />' .
self::___('CSS file could not saved.') .' <strong>('. $this->getCssFile() .')</strong> '. self::___('Your widgets do not display properly!') . '<br />';
if ($errorType === 'filesystem') {
$html .= '<br />
<strong>There is an error with your filesystem. We got the following error message:</strong>
<pre style="display: block; margin: 10px 0; padding: 20px; background: #eee">'. $errorMessage .'</pre>
<strong>Maybe you configured your filesystem incorrectly.<br />
<a href="https://wordpress.org/support/article/editing-wp-config-php/#wordpress-upgrade-constants" target="_blank">Here you can read about how to configure filesystem in your WordPress.</a></strong>';
}
else {
if ($fileExists) {
$html .= self::___('CSS file exists and it is not writeable. Delete the file');
}
else {
$html .= self::___('Grant write permissions to upload folder');
}
$html .= '<br />' .
self::___('or') . '<br />' .
self::___("enable 'CSS internal loading' in the %s page!", [ '<a href="'. admin_url('admin.php?page=' . $this->get_plugin_slug() . '/settings.php&tab=advanced') .'>'. self::___('Advanced') .'</a>' ]);
}
echo $html . '</p></div>';
});
}
return $success;
}
public static $widget_templates = array (
 'categories' => 
 array (
 'slider' => '4,5,13,14,15,19,34,36,37,39,44,45,46,47,95',
 'sidebar' => '6,7,8,9,10,18,54,81',
 'list' => '33,80',
 'grid' => '16,31,38,48,79',
 'badge' => '11,12,20,22,23,55,56,57,58',
 'button' => '24,25,26,27,28,29,30,32,35,59,60,61,62',
 'floating' => '17,21,52,53',
 'popup' => '23,30,32',
 ),
 'templates' => 
 array (
 4 => 
 array (
 'name' => 'Slider I.',
 'type' => 'slider',
 'is-active' => true,
 ),
 14 => 
 array (
 'name' => 'Slider I. - with header',
 'type' => 'slider',
 'is-active' => false,
 ),
 45 => 
 array (
 'name' => 'Slider I. - Big picture',
 'type' => 'slider',
 'is-active' => false,
 ),
 46 => 
 array (
 'name' => 'Slider II. - Big picture',
 'type' => 'slider',
 'is-active' => false,
 ),
 47 => 
 array (
 'name' => 'Slider III. - Big picture',
 'type' => 'slider',
 'is-active' => false,
 ),
 48 => 
 array (
 'name' => 'Grid I. - Big picture',
 'type' => 'grid',
 'is-active' => false,
 ),
 95 => 
 array (
 'name' => 'Slider I. - with AI summary',
 'type' => 'slider',
 'is-active' => false,
 ),
 5 => 
 array (
 'name' => 'Slider II.',
 'type' => 'slider',
 'is-active' => true,
 ),
 36 => 
 array (
 'name' => 'Slider III.',
 'type' => 'slider',
 'is-active' => true,
 ),
 34 => 
 array (
 'name' => 'Slider IV.',
 'type' => 'slider',
 'is-active' => true,
 ),
 39 => 
 array (
 'name' => 'Slider II. - centered',
 'type' => 'slider',
 'is-active' => false,
 ),
 13 => 
 array (
 'name' => 'Slider V.',
 'type' => 'slider',
 'is-active' => true,
 ),
 15 => 
 array (
 'name' => 'Slider VI.',
 'type' => 'slider',
 'is-active' => true,
 ),
 19 => 
 array (
 'name' => 'Slider IV.',
 'type' => 'slider',
 'is-active' => false,
 ),
 37 => 
 array (
 'name' => 'Slider VII.',
 'type' => 'slider',
 'is-active' => true,
 ),
 44 => 
 array (
 'name' => 'Slider VI.',
 'type' => 'slider',
 'is-active' => false,
 ),
 20 => 
 array (
 'name' => 'HTML badge III.',
 'type' => 'badge',
 'is-active' => false,
 ),
 33 => 
 array (
 'name' => 'List I.',
 'type' => 'list',
 'is-active' => true,
 ),
 80 => 
 array (
 'name' => 'List I. - with header',
 'type' => 'list',
 'is-active' => false,
 ),
 16 => 
 array (
 'name' => 'Grid',
 'type' => 'grid',
 'is-active' => true,
 ),
 31 => 
 array (
 'name' => 'Mansonry grid',
 'type' => 'grid',
 'is-active' => true,
 ),
 38 => 
 array (
 'name' => 'Grid II.',
 'type' => 'grid',
 'is-active' => false,
 ),
 79 => 
 array (
 'name' => 'Mansonry grid - with header',
 'type' => 'grid',
 'is-active' => false,
 ),
 54 => 
 array (
 'name' => 'Sidebar slider I.',
 'type' => 'sidebar',
 'is-active' => true,
 ),
 6 => 
 array (
 'name' => 'Sidebar slider II.',
 'type' => 'sidebar',
 'is-active' => true,
 ),
 7 => 
 array (
 'name' => 'Sidebar slider II.',
 'type' => 'sidebar',
 'is-active' => false,
 ),
 18 => 
 array (
 'name' => 'Full sidebar I.',
 'type' => 'sidebar',
 'is-active' => true,
 ),
 81 => 
 array (
 'name' => 'Full sidebar I. - with header',
 'type' => 'sidebar',
 'is-active' => false,
 ),
 8 => 
 array (
 'name' => 'Full sidebar II.',
 'type' => 'sidebar',
 'is-active' => true,
 ),
 9 => 
 array (
 'name' => 'Full sidebar II.',
 'type' => 'sidebar',
 'is-active' => false,
 ),
 10 => 
 array (
 'name' => 'Full sidebar III.',
 'type' => 'sidebar',
 'is-active' => false,
 ),
 24 => 
 array (
 'name' => 'Button I.',
 'type' => 'button',
 'is-active' => false,
 ),
 27 => 
 array (
 'name' => 'Button I.',
 'type' => 'button',
 'is-active' => true,
 ),
 26 => 
 array (
 'name' => 'Button II.',
 'type' => 'button',
 'is-active' => true,
 ),
 29 => 
 array (
 'name' => 'Button III.',
 'type' => 'button',
 'is-active' => true,
 ),
 30 => 
 array (
 'name' => 'Button IV. - with dropdown',
 'type' => 'button',
 'is-active' => true,
 ),
 28 => 
 array (
 'name' => 'Button V.',
 'type' => 'button',
 'is-active' => false,
 ),
 60 => 
 array (
 'name' => 'Button V.',
 'type' => 'button',
 'is-active' => true,
 ),
 25 => 
 array (
 'name' => 'Button VI.',
 'type' => 'button',
 'is-active' => true,
 ),
 32 => 
 array (
 'name' => 'Button VII. - with dropdown',
 'type' => 'button',
 'is-active' => true,
 ),
 35 => 
 array (
 'name' => 'Button VII.',
 'type' => 'button',
 'is-active' => false,
 ),
 59 => 
 array (
 'name' => 'Button VIII.',
 'type' => 'button',
 'is-active' => false,
 ),
 61 => 
 array (
 'name' => 'Button X.',
 'type' => 'button',
 'is-active' => false,
 ),
 62 => 
 array (
 'name' => 'Button XI.',
 'type' => 'button',
 'is-active' => false,
 ),
 55 => 
 array (
 'name' => 'HTML badge I.',
 'type' => 'badge',
 'is-active' => true,
 ),
 11 => 
 array (
 'name' => 'HTML badge II.',
 'type' => 'badge',
 'is-active' => true,
 ),
 12 => 
 array (
 'name' => 'HTML badge III.',
 'type' => 'badge',
 'is-active' => true,
 ),
 56 => 
 array (
 'name' => 'HTML badge IV.',
 'type' => 'badge',
 'is-active' => true,
 ),
 22 => 
 array (
 'name' => 'Company badge I.',
 'type' => 'badge',
 'is-active' => true,
 ),
 23 => 
 array (
 'name' => 'Company badge I. - with popup',
 'type' => 'badge',
 'is-active' => true,
 ),
 57 => 
 array (
 'name' => 'HTML badge V.',
 'type' => 'badge',
 'is-active' => false,
 ),
 58 => 
 array (
 'name' => 'HTML badge VI.',
 'type' => 'badge',
 'is-active' => false,
 ),
 17 => 
 array (
 'name' => 'Floating I.',
 'type' => 'floating',
 'is-active' => true,
 ),
 53 => 
 array (
 'name' => 'Floating II.',
 'type' => 'floating',
 'is-active' => true,
 ),
 21 => 
 array (
 'name' => 'Floating III.',
 'type' => 'floating',
 'is-active' => true,
 ),
 52 => 
 array (
 'name' => 'Floating IV.',
 'type' => 'floating',
 'is-active' => true,
 ),
 ),
);
public static $widget_styles = array (
 'light-background' => 
 array (
 'is-active' => true,
 'id' => 'light-background',
 'name' => 'Light background',
 'position' => 0,
 'select-position' => 0,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"light-background"',
 'bg-color' => '#ffffff',
 'text-color' => '#000000',
 'outside-text-color' => '#000000',
 'profile-color' => '#000000',
 'profile-font-size' => '14px',
 'review-font-size' => '15px',
 'rating-text' => '15px',
 'company-font-size' => '15px',
 'review-lines' => '4',
 'box-background-color' => '#f4f4f4',
 'box-border-color' => '#f4f4f4',
 'box-border-radius' => '4px',
 'box-padding' => '20px',
 'scroll-color' => '#555555',
 'arrow-color' => '#cccccc',
 'float-widget-align' => 'left',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '0px',
 'box-border-bottom-width' => '0px',
 'box-border-left-width' => '0px',
 'box-border-right-width' => '0px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#f4f4f4',
 'header-border-color' => '#f4f4f4',
 'header-border-top-width' => '1px',
 'header-border-bottom-width' => '1px',
 'header-border-left-width' => '1px',
 'header-border-right-width' => '1px',
 'header-border-radius' => '4px',
 'header-padding' => '20px',
 'header-color' => '#000000',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '15px',
 ),
 ),
 'light-background-large' => 
 array (
 'is-active' => false,
 'id' => 'light-background-large',
 'name' => 'Light background - large',
 'position' => 0,
 'select-position' => 0,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"light-background-large"',
 'bg-color' => '#ffffff',
 'text-color' => '#000000',
 'outside-text-color' => '#000000',
 'profile-color' => '#000000',
 'profile-font-size' => '15px',
 'review-font-size' => '16px',
 'rating-text' => '15px',
 'company-font-size' => '15px',
 'review-lines' => '5',
 'box-background-color' => '#f4f4f4',
 'box-border-color' => '#f4f4f4',
 'box-border-radius' => '10px',
 'box-padding' => '25px',
 'scroll-color' => '#c3c3c3',
 'arrow-color' => '#cccccc',
 'float-widget-align' => 'left',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '0px',
 'box-border-bottom-width' => '0px',
 'box-border-left-width' => '0px',
 'box-border-right-width' => '0px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#f8f9f9',
 'header-border-color' => '#f8f9f9',
 'header-border-top-width' => '1px',
 'header-border-bottom-width' => '1px',
 'header-border-left-width' => '1px',
 'header-border-right-width' => '1px',
 'header-border-radius' => '12px',
 'header-padding' => '30px',
 'header-color' => '#000000',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '15px',
 ),
 ),
 'ligth-border' => 
 array (
 'is-active' => true,
 'id' => 'ligth-border',
 'name' => 'Light border',
 'position' => 0,
 'select-position' => 1,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"light-border"',
 'bg-color' => '#ffffff',
 'text-color' => '#000000',
 'outside-text-color' => '#000000',
 'profile-color' => '#000000',
 'profile-font-size' => '14px',
 'review-font-size' => '15px',
 'rating-text' => '15px',
 'company-font-size' => '15px',
 'review-lines' => '4',
 'box-background-color' => '#ffffff',
 'box-border-color' => '#dbdde1',
 'box-border-radius' => '4px',
 'box-padding' => '20px',
 'scroll-color' => '#555555',
 'arrow-color' => '#cccccc',
 'float-widget-align' => 'left',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '1px',
 'box-border-bottom-width' => '1px',
 'box-border-left-width' => '1px',
 'box-border-right-width' => '1px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#ffffff',
 'header-border-color' => '#dbdde1',
 'header-border-top-width' => '1px',
 'header-border-bottom-width' => '1px',
 'header-border-left-width' => '1px',
 'header-border-right-width' => '1px',
 'header-border-radius' => '4px',
 'header-padding' => '20px',
 'header-color' => '#000000',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '15px',
 ),
 ),
 'ligth-border-3d-large' => 
 array (
 'is-active' => false,
 'id' => 'ligth-border-3d-large',
 'name' => 'Light border - 3D - large',
 'position' => 0,
 'select-position' => 1,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"light-border-3d-large"',
 'bg-color' => '#ffffff',
 'text-color' => '#000000',
 'outside-text-color' => '#000000',
 'profile-color' => '#000000',
 'profile-font-size' => '15px',
 'review-font-size' => '16px',
 'rating-text' => '14px',
 'company-font-size' => '16px',
 'review-lines' => '5',
 'box-background-color' => '#ffffff',
 'box-border-color' => '#efefef',
 'box-border-radius' => '10px',
 'box-padding' => '25px',
 'scroll-color' => '#b4b4b4',
 'arrow-color' => '#cccccc',
 'float-widget-align' => 'left',
 'nav' => 'desktop',
 'dots' => 'true',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '1px',
 'box-border-bottom-width' => '4px',
 'box-border-left-width' => '1px',
 'box-border-right-width' => '4px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#ffffff',
 'header-border-color' => '#efefef',
 'header-border-top-width' => '1px',
 'header-border-bottom-width' => '4px',
 'header-border-left-width' => '1px',
 'header-border-right-width' => '4px',
 'header-border-radius' => '10px',
 'header-padding' => '30px',
 'header-color' => '#000000',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '14px',
 ),
 ),
 'ligth-border-large' => 
 array (
 'is-active' => false,
 'id' => 'ligth-border-large',
 'name' => 'Light border - large',
 'position' => 0,
 'select-position' => 1,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"light-border-large"',
 'bg-color' => '#ffffff',
 'text-color' => '#000000',
 'outside-text-color' => '#000000',
 'profile-color' => '#000000',
 'profile-font-size' => '15px',
 'review-font-size' => '16px',
 'rating-text' => '15px',
 'company-font-size' => '16px',
 'review-lines' => '5',
 'box-background-color' => '#ffffff',
 'box-border-color' => '#e2e2e2',
 'box-border-radius' => '10px',
 'box-padding' => '25px',
 'scroll-color' => '#cccccc',
 'arrow-color' => '#cccccc',
 'float-widget-align' => 'left',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '1px',
 'box-border-bottom-width' => '1px',
 'box-border-left-width' => '1px',
 'box-border-right-width' => '1px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#ffffff',
 'header-border-color' => '#e2e2e2',
 'header-border-top-width' => '1px',
 'header-border-bottom-width' => '1px',
 'header-border-left-width' => '1px',
 'header-border-right-width' => '1px',
 'header-border-radius' => '4px',
 'header-padding' => '30px',
 'header-color' => '#000000',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '15px',
 ),
 ),
 'ligth-border-large-red' => 
 array (
 'is-active' => false,
 'id' => 'ligth-border-large-red',
 'name' => 'Light border - large - red',
 'position' => 0,
 'select-position' => 1,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"light-border-large-red"',
 'bg-color' => '#ffffff',
 'text-color' => '#000000',
 'outside-text-color' => '#000000',
 'profile-color' => '#000000',
 'profile-font-size' => '15px',
 'review-font-size' => '16px',
 'rating-text' => '14px',
 'company-font-size' => '16px',
 'review-lines' => '5',
 'box-background-color' => '#ffffff',
 'box-border-color' => '#d93623',
 'box-border-radius' => '0px',
 'box-padding' => '25px',
 'scroll-color' => '#8d8d8d',
 'arrow-color' => '#8d8d8d',
 'float-widget-align' => 'left',
 'nav' => 'desktop',
 'dots' => 'true',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '3px',
 'box-border-bottom-width' => '3px',
 'box-border-left-width' => '3px',
 'box-border-right-width' => '3px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#ffffff',
 'header-border-color' => '#d93623',
 'header-border-top-width' => '3px',
 'header-border-bottom-width' => '3px',
 'header-border-left-width' => '3px',
 'header-border-right-width' => '3px',
 'header-border-radius' => '0px',
 'header-padding' => '30px',
 'header-color' => '#000000',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '14px',
 ),
 ),
 'drop-shadow' => 
 array (
 'is-active' => true,
 'id' => 'drop-shadow',
 'name' => 'Drop shadow',
 'position' => 0,
 'select-position' => 2,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"drop-shadow"',
 'bg-color' => '#ffffff',
 'text-color' => '#000000',
 'outside-text-color' => '#000000',
 'profile-color' => '#000000',
 'profile-font-size' => '14px',
 'review-font-size' => '15px',
 'rating-text' => '15px',
 'company-font-size' => '15px',
 'review-lines' => '4',
 'box-background-color' => '#ffffff',
 'box-border-color' => '#ffffff',
 'box-border-radius' => '4px',
 'box-padding' => '20px',
 'scroll-color' => '#555555',
 'arrow-color' => '#cccccc',
 'float-widget-align' => 'right',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'false',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'true',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.13',
 'box-border-top-width' => '0px',
 'box-border-bottom-width' => '0px',
 'box-border-left-width' => '0px',
 'box-border-right-width' => '0px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#ffffff',
 'header-border-color' => '#ffffff',
 'header-border-top-width' => '0px',
 'header-border-bottom-width' => '0px',
 'header-border-left-width' => '0px',
 'header-border-right-width' => '0px',
 'header-border-radius' => '5px',
 'header-padding' => '20px',
 'header-color' => '#000000',
 'header-shadow' => 'true',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.1',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '15px',
 ),
 ),
 'drop-shadow-large' => 
 array (
 'is-active' => false,
 'id' => 'drop-shadow-large',
 'name' => 'Drop shadow - large',
 'position' => 0,
 'select-position' => 2,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"drop-shadow-large"',
 'bg-color' => '#ffffff',
 'text-color' => '#000000',
 'outside-text-color' => '#000000',
 'profile-color' => '#000000',
 'profile-font-size' => '15px',
 'review-font-size' => '16px',
 'rating-text' => '15px',
 'company-font-size' => '15px',
 'review-lines' => '5',
 'box-background-color' => '#ffffff',
 'box-border-color' => '#ffffff',
 'box-border-radius' => '10px',
 'box-padding' => '25px',
 'scroll-color' => '#939393',
 'arrow-color' => '#cccccc',
 'float-widget-align' => 'right',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'true',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.13',
 'box-border-top-width' => '0px',
 'box-border-bottom-width' => '0px',
 'box-border-left-width' => '0px',
 'box-border-right-width' => '0px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#ffffff',
 'header-border-color' => '#ffffff',
 'header-border-top-width' => '0px',
 'header-border-bottom-width' => '0px',
 'header-border-left-width' => '0px',
 'header-border-right-width' => '0px',
 'header-border-radius' => '12px',
 'header-padding' => '30px',
 'header-color' => '#000000',
 'header-shadow' => 'true',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.1',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '15px',
 ),
 ),
 'light-minimal' => 
 array (
 'is-active' => true,
 'id' => 'light-minimal',
 'name' => 'Minimal',
 'position' => 0,
 'select-position' => 3,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"light-minimal"',
 'bg-color' => '#ffffff',
 'text-color' => '#000000',
 'outside-text-color' => '#000000',
 'profile-color' => '#000000',
 'profile-font-size' => '14px',
 'review-font-size' => '15px',
 'rating-text' => '15px',
 'company-font-size' => '15px',
 'review-lines' => '4',
 'box-background-color' => '#f4f4f4',
 'box-border-color' => '#f4f4f4',
 'box-border-radius' => '4px',
 'box-padding' => '20px',
 'scroll-color' => '#555555',
 'arrow-color' => '#cccccc',
 'float-widget-align' => 'right',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '0px',
 'box-border-bottom-width' => '0px',
 'box-border-left-width' => '0px',
 'box-border-right-width' => '0px',
 'box-background-opacity' => '0',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#ffffff',
 'header-border-color' => '#e5e5e5',
 'header-border-top-width' => '0px',
 'header-border-bottom-width' => '1px',
 'header-border-left-width' => '0px',
 'header-border-right-width' => '0px',
 'header-border-radius' => '0px',
 'header-padding' => '20px',
 'header-color' => '#000000',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '15px',
 ),
 ),
 'light-minimal-large' => 
 array (
 'is-active' => false,
 'id' => 'light-minimal-large',
 'name' => 'Minimal - large',
 'position' => 0,
 'select-position' => 3,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"light-minimal-large"',
 'bg-color' => '#ffffff',
 'text-color' => '#000000',
 'outside-text-color' => '#000000',
 'profile-color' => '#000000',
 'profile-font-size' => '15px',
 'review-font-size' => '16px',
 'rating-text' => '15px',
 'company-font-size' => '15px',
 'review-lines' => '5',
 'box-background-color' => '#ffffff',
 'box-border-color' => '#ffffff',
 'box-border-radius' => '10px',
 'box-padding' => '15px',
 'scroll-color' => '#555555',
 'arrow-color' => '#cccccc',
 'float-widget-align' => 'right',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '0px',
 'box-border-bottom-width' => '0px',
 'box-border-left-width' => '0px',
 'box-border-right-width' => '0px',
 'box-background-opacity' => '0',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#ffffff',
 'header-border-color' => '#ffffff',
 'header-border-top-width' => '0px',
 'header-border-bottom-width' => '0px',
 'header-border-left-width' => '0px',
 'header-border-right-width' => '0px',
 'header-border-radius' => '0px',
 'header-padding' => '30px',
 'header-color' => '#000000',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '15px',
 ),
 ),
 'soft' => 
 array (
 'is-active' => true,
 'id' => 'soft',
 'name' => 'Soft',
 'position' => 1,
 'select-position' => 4,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"soft"',
 'bg-color' => '#f6f6f9',
 'text-color' => '#000000',
 'outside-text-color' => '#000000',
 'profile-color' => '#000000',
 'profile-font-size' => '14px',
 'review-font-size' => '15px',
 'rating-text' => '15px',
 'company-font-size' => '15px',
 'review-lines' => '4',
 'box-background-color' => '#ffffff',
 'box-border-color' => '#ffffff',
 'box-border-radius' => '4px',
 'box-padding' => '20px',
 'scroll-color' => '#555555',
 'arrow-color' => '#b7b7b7',
 'float-widget-align' => 'left',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '2px',
 'box-border-bottom-width' => '2px',
 'box-border-left-width' => '2px',
 'box-border-right-width' => '2px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#ffffff',
 'header-border-color' => '#ffffff',
 'header-border-top-width' => '1px',
 'header-border-bottom-width' => '1px',
 'header-border-left-width' => '1px',
 'header-border-right-width' => '1px',
 'header-border-radius' => '4px',
 'header-padding' => '20px',
 'header-color' => '#000000',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '15px',
 ),
 ),
 'light-clean' => 
 array (
 'is-active' => false,
 'id' => 'light-clean',
 'name' => 'Light clean',
 'position' => 0,
 'select-position' => 5,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"light-clean"',
 'bg-color' => '#ffffff',
 'text-color' => '#000000',
 'outside-text-color' => '#000000',
 'profile-color' => '#000000',
 'profile-font-size' => '14px',
 'review-font-size' => '13px',
 'rating-text' => '15px',
 'company-font-size' => '15px',
 'review-lines' => '4',
 'box-background-color' => '#ffffff',
 'box-border-color' => '#dddddd',
 'box-border-radius' => '0px',
 'box-padding' => '15px',
 'scroll-color' => '#555555',
 'arrow-color' => '#cccccc',
 'float-widget-align' => 'right',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '1px',
 'box-border-bottom-width' => '1px',
 'box-border-left-width' => '1px',
 'box-border-right-width' => '1px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#ffffff',
 'header-border-color' => '#dddddd',
 'header-border-top-width' => '1px',
 'header-border-bottom-width' => '1px',
 'header-border-left-width' => '1px',
 'header-border-right-width' => '1px',
 'header-border-radius' => '0px',
 'header-padding' => '20px',
 'header-color' => '#000000',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '15px',
 ),
 ),
 'light-square' => 
 array (
 'is-active' => false,
 'id' => 'light-square',
 'name' => 'Light square',
 'position' => 0,
 'select-position' => 6,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"light-square"',
 'bg-color' => '#ffffff',
 'text-color' => '#000000',
 'outside-text-color' => '#000000',
 'profile-color' => '#000000',
 'profile-font-size' => '14px',
 'review-font-size' => '14px',
 'rating-text' => '14px',
 'company-font-size' => '15px',
 'review-lines' => '4',
 'box-background-color' => '#f3f3f3',
 'box-border-color' => '#dddddd',
 'box-border-radius' => '0px',
 'box-padding' => '15px',
 'scroll-color' => '#555555',
 'arrow-color' => '#cccccc',
 'float-widget-align' => 'right',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '0px',
 'box-border-bottom-width' => '3px',
 'box-border-left-width' => '0px',
 'box-border-right-width' => '0px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#f3f3f3',
 'header-border-color' => '#dddddd',
 'header-border-top-width' => '0px',
 'header-border-bottom-width' => '3px',
 'header-border-left-width' => '0px',
 'header-border-right-width' => '0px',
 'header-border-radius' => '0px',
 'header-padding' => '20px',
 'header-color' => '#000000',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '14px',
 ),
 ),
 'light-background-border' => 
 array (
 'is-active' => false,
 'id' => 'light-background-border',
 'name' => 'Light background border',
 'position' => 0,
 'select-position' => 7,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"light-background-border"',
 'bg-color' => '#ffffff',
 'text-color' => '#000000',
 'outside-text-color' => '#000000',
 'profile-color' => '#000000',
 'profile-font-size' => '15px',
 'review-font-size' => '14px',
 'rating-text' => '14px',
 'company-font-size' => '16px',
 'review-lines' => '4',
 'box-background-color' => '#efefef',
 'box-border-color' => '#cccccc',
 'box-border-radius' => '4px',
 'box-padding' => '15px',
 'scroll-color' => '#555555',
 'arrow-color' => '#cccccc',
 'float-widget-align' => 'left',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '2px',
 'box-border-bottom-width' => '2px',
 'box-border-left-width' => '2px',
 'box-border-right-width' => '2px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#efefef',
 'header-border-color' => '#cccccc',
 'header-border-top-width' => '2px',
 'header-border-bottom-width' => '2px',
 'header-border-left-width' => '2px',
 'header-border-right-width' => '2px',
 'header-border-radius' => '4px',
 'header-padding' => '20px',
 'header-color' => '#000000',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '14px',
 ),
 ),
 'blue' => 
 array (
 'is-active' => false,
 'id' => 'blue',
 'name' => 'Blue',
 'position' => 0,
 'select-position' => 8,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"blue"',
 'bg-color' => '#ffffff',
 'text-color' => '#000000',
 'outside-text-color' => '#000000',
 'profile-color' => '#365899',
 'profile-font-size' => '14px',
 'review-font-size' => '14px',
 'rating-text' => '14px',
 'company-font-size' => '15px',
 'review-lines' => '4',
 'box-background-color' => '#ffffff',
 'box-border-color' => '#dddfe2',
 'box-border-radius' => '4px',
 'box-padding' => '15px',
 'scroll-color' => '#555555',
 'arrow-color' => '#cccccc',
 'float-widget-align' => 'left',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '1px',
 'box-border-bottom-width' => '1px',
 'box-border-left-width' => '1px',
 'box-border-right-width' => '1px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#ffffff',
 'header-border-color' => '#dddfe2',
 'header-border-top-width' => '1px',
 'header-border-bottom-width' => '1px',
 'header-border-left-width' => '1px',
 'header-border-right-width' => '1px',
 'header-border-radius' => '4px',
 'header-padding' => '20px',
 'header-color' => '#000000',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '14px',
 ),
 ),
 'light-background-large-purple' => 
 array (
 'is-active' => false,
 'id' => 'light-background-large-purple',
 'name' => 'Light background - large - purple',
 'position' => 0,
 'select-position' => 9,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"light-background-large-purple"',
 'bg-color' => '#ffffff',
 'text-color' => '#593072',
 'outside-text-color' => '#593072',
 'profile-color' => '#593072',
 'profile-font-size' => '15px',
 'review-font-size' => '16px',
 'rating-text' => '14px',
 'company-font-size' => '16px',
 'review-lines' => '5',
 'box-background-color' => '#f6f1f9',
 'box-border-color' => '#fbf9fc',
 'box-border-radius' => '15px',
 'box-padding' => '25px',
 'scroll-color' => '#593072',
 'arrow-color' => '#593072',
 'float-widget-align' => 'left',
 'nav' => 'desktop',
 'dots' => 'true',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '5px',
 'box-border-bottom-width' => '5px',
 'box-border-left-width' => '5px',
 'box-border-right-width' => '5px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#f6f1f9',
 'header-border-color' => '#fbf9fc',
 'header-border-top-width' => '5px',
 'header-border-bottom-width' => '5px',
 'header-border-left-width' => '5px',
 'header-border-right-width' => '5px',
 'header-border-radius' => '15px',
 'header-padding' => '30px',
 'header-color' => '#000000',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '14px',
 ),
 ),
 'light-background-image' => 
 array (
 'is-active' => false,
 'id' => 'light-background-image',
 'name' => 'Light background image',
 'position' => 0,
 'select-position' => 9,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"light-background-image"',
 'bg-color' => '#ffffff',
 'text-color' => '#000000',
 'outside-text-color' => '#000000',
 'profile-color' => '#000000',
 'profile-font-size' => '14px',
 'review-font-size' => '14px',
 'rating-text' => '15px',
 'company-font-size' => '18px',
 'review-lines' => '4',
 'box-background-color' => '#ffffff',
 'box-border-color' => '#ffffff',
 'box-border-radius' => '8px',
 'box-padding' => '15px',
 'scroll-color' => '#555555',
 'arrow-color' => '#999999',
 'float-widget-align' => 'right',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'false',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'true',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.05',
 'box-border-top-width' => '0px',
 'box-border-bottom-width' => '0px',
 'box-border-left-width' => '0px',
 'box-border-right-width' => '0px',
 'box-background-opacity' => '0.3',
 'box-backdrop-blur' => '5px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#ffffff',
 'header-border-color' => '#ffffff',
 'header-border-top-width' => '0px',
 'header-border-bottom-width' => '0px',
 'header-border-left-width' => '0px',
 'header-border-right-width' => '0px',
 'header-border-radius' => '4px',
 'header-padding' => '20px',
 'header-color' => '#000000',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '0.3',
 'header-backdrop-blur' => '5px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '15px',
 ),
 ),
 'light-contrast' => 
 array (
 'is-active' => true,
 'id' => 'light-contrast',
 'name' => 'Light contrast',
 'position' => 0,
 'select-position' => 10,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"light-contrast"',
 'bg-color' => '#ffffff',
 'text-color' => '#ffffff',
 'outside-text-color' => '#000000',
 'profile-color' => '#ffffff',
 'profile-font-size' => '14px',
 'review-font-size' => '15px',
 'rating-text' => '15px',
 'company-font-size' => '15px',
 'review-lines' => '4',
 'box-background-color' => '#333333',
 'box-border-color' => '#333333',
 'box-border-radius' => '4px',
 'box-padding' => '20px',
 'scroll-color' => '#555555',
 'arrow-color' => '#555555',
 'float-widget-align' => 'left',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '2px',
 'box-border-bottom-width' => '2px',
 'box-border-left-width' => '2px',
 'box-border-right-width' => '2px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#333333',
 'header-border-color' => '#333333',
 'header-border-top-width' => '1px',
 'header-border-bottom-width' => '1px',
 'header-border-left-width' => '1px',
 'header-border-right-width' => '1px',
 'header-border-radius' => '4px',
 'header-padding' => '20px',
 'header-color' => '#ffffff',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '15px',
 ),
 ),
 'light-contrast-large' => 
 array (
 'is-active' => false,
 'id' => 'light-contrast-large',
 'name' => 'Light contrast - large',
 'position' => 0,
 'select-position' => 10,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"light-contrast-large"',
 'bg-color' => '#ffffff',
 'text-color' => '#ffffff',
 'outside-text-color' => '#252c44',
 'profile-color' => '#ffffff',
 'profile-font-size' => '15px',
 'review-font-size' => '16px',
 'rating-text' => '14px',
 'company-font-size' => '16px',
 'review-lines' => '5',
 'box-background-color' => '#252c44',
 'box-border-color' => '#252c44',
 'box-border-radius' => '0px',
 'box-padding' => '25px',
 'scroll-color' => '#ffffff',
 'arrow-color' => '#252c44',
 'float-widget-align' => 'left',
 'nav' => 'desktop',
 'dots' => 'true',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'true',
 'box-shadow-color' => '#252c44',
 'box-shadow-opacity' => '0.45',
 'box-border-top-width' => '0px',
 'box-border-bottom-width' => '0px',
 'box-border-left-width' => '0px',
 'box-border-right-width' => '0px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#252c44',
 'header-border-color' => '#252c44',
 'header-border-top-width' => '1px',
 'header-border-bottom-width' => '1px',
 'header-border-left-width' => '1px',
 'header-border-right-width' => '1px',
 'header-border-radius' => '0px',
 'header-padding' => '30px',
 'header-color' => '#ffffff',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '14px',
 ),
 ),
 'light-contrast-large-blue' => 
 array (
 'is-active' => false,
 'id' => 'light-contrast-large-blue',
 'name' => 'Light contrast - large - blue',
 'position' => 0,
 'select-position' => 10,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"light-contrast-large-blue"',
 'bg-color' => '#ffffff',
 'text-color' => '#ffffff',
 'outside-text-color' => '#252c44',
 'profile-color' => '#ffffff',
 'profile-font-size' => '15px',
 'review-font-size' => '16px',
 'rating-text' => '14px',
 'company-font-size' => '16px',
 'review-lines' => '5',
 'box-background-color' => '#242f62',
 'box-border-color' => '#2aa8d7',
 'box-border-radius' => '0px',
 'box-padding' => '25px',
 'scroll-color' => '#ffffff',
 'arrow-color' => '#242f62',
 'float-widget-align' => 'left',
 'nav' => 'desktop',
 'dots' => 'true',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#252c44',
 'box-shadow-opacity' => '0.45',
 'box-border-top-width' => '0px',
 'box-border-bottom-width' => '0px',
 'box-border-left-width' => '10px',
 'box-border-right-width' => '0px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#242f62',
 'header-border-color' => '#2aa8d7',
 'header-border-top-width' => '0px',
 'header-border-bottom-width' => '0px',
 'header-border-left-width' => '10px',
 'header-border-right-width' => '0px',
 'header-border-radius' => '0px',
 'header-padding' => '30px',
 'header-color' => '#ffffff',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '14px',
 ),
 ),
 'dark-background' => 
 array (
 'is-active' => true,
 'id' => 'dark-background',
 'name' => 'Dark background',
 'position' => 1,
 'select-position' => 11,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"dark-background"',
 'bg-color' => '#000000',
 'text-color' => '#ffffff',
 'outside-text-color' => '#ffffff',
 'profile-color' => '#ffffff',
 'profile-font-size' => '14px',
 'review-font-size' => '15px',
 'rating-text' => '15px',
 'company-font-size' => '15px',
 'review-lines' => '4',
 'box-background-color' => '#222222',
 'box-border-color' => '#222222',
 'box-border-radius' => '4px',
 'box-padding' => '20px',
 'scroll-color' => '#555555',
 'arrow-color' => '#666666',
 'float-widget-align' => 'left',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '0px',
 'box-border-bottom-width' => '0px',
 'box-border-left-width' => '0px',
 'box-border-right-width' => '0px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#222222',
 'header-border-color' => '#222222',
 'header-border-top-width' => '1px',
 'header-border-bottom-width' => '1px',
 'header-border-left-width' => '1px',
 'header-border-right-width' => '1px',
 'header-border-radius' => '4px',
 'header-padding' => '20px',
 'header-color' => '#ffffff',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '15px',
 ),
 ),
 'dark-minimal' => 
 array (
 'is-active' => true,
 'id' => 'dark-minimal',
 'name' => 'Minimal dark',
 'position' => 0,
 'select-position' => 11,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"dark-minimal"',
 'bg-color' => '#000000',
 'text-color' => '#ffffff',
 'outside-text-color' => '#ffffff',
 'profile-color' => '#ffffff',
 'profile-font-size' => '14px',
 'review-font-size' => '15px',
 'rating-text' => '15px',
 'company-font-size' => '15px',
 'review-lines' => '4',
 'box-background-color' => '#000000',
 'box-border-color' => '#000000',
 'box-border-radius' => '0px',
 'box-padding' => '20px',
 'scroll-color' => '#555555',
 'arrow-color' => '#cccccc',
 'float-widget-align' => 'right',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '0px',
 'box-border-bottom-width' => '0px',
 'box-border-left-width' => '0px',
 'box-border-right-width' => '0px',
 'box-background-opacity' => '0',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => 'rgba(#000000, 0)',
 'header-border-color' => '#191919',
 'header-border-top-width' => '0px',
 'header-border-bottom-width' => '1px',
 'header-border-left-width' => '0px',
 'header-border-right-width' => '0px',
 'header-border-radius' => '0px',
 'header-padding' => '20px',
 'header-color' => '#fff',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '15px',
 ),
 ),
 'dark-border' => 
 array (
 'is-active' => false,
 'id' => 'dark-border',
 'name' => 'Dark border',
 'position' => 1,
 'select-position' => 12,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"dark-border"',
 'bg-color' => '#222222',
 'text-color' => '#ffffff',
 'outside-text-color' => '#ffffff',
 'profile-color' => '#ffffff',
 'profile-font-size' => '15px',
 'review-font-size' => '14px',
 'rating-text' => '14px',
 'company-font-size' => '16px',
 'review-lines' => '4',
 'box-background-color' => '#222222',
 'box-border-color' => '#444444',
 'box-border-radius' => '4px',
 'box-padding' => '15px',
 'scroll-color' => '#555555',
 'arrow-color' => '#444444',
 'float-widget-align' => 'left',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '2px',
 'box-border-bottom-width' => '2px',
 'box-border-left-width' => '2px',
 'box-border-right-width' => '2px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#222222',
 'header-border-color' => '#444444',
 'header-border-top-width' => '2px',
 'header-border-bottom-width' => '2px',
 'header-border-left-width' => '2px',
 'header-border-right-width' => '2px',
 'header-border-radius' => '4px',
 'header-padding' => '20px',
 'header-color' => '#ffffff',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '14px',
 ),
 ),
 'dark-contrast' => 
 array (
 'is-active' => false,
 'id' => 'dark-contrast',
 'name' => 'Dark contrast',
 'position' => 1,
 'select-position' => 14,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"dark-contrast"',
 'bg-color' => '#222222',
 'text-color' => '#000000',
 'outside-text-color' => '#ffffff',
 'profile-color' => '#000000',
 'profile-font-size' => '15px',
 'review-font-size' => '14px',
 'rating-text' => '14px',
 'company-font-size' => '16px',
 'review-lines' => '4',
 'box-background-color' => '#ffffff',
 'box-border-color' => '#ffffff',
 'box-border-radius' => '4px',
 'box-padding' => '15px',
 'scroll-color' => '#555555',
 'arrow-color' => '#ffffff',
 'float-widget-align' => 'left',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'true',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'false',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.15',
 'box-border-top-width' => '2px',
 'box-border-bottom-width' => '2px',
 'box-border-left-width' => '2px',
 'box-border-right-width' => '2px',
 'box-background-opacity' => '1',
 'box-backdrop-blur' => '0px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#ffffff',
 'header-border-color' => '#ffffff',
 'header-border-top-width' => '2px',
 'header-border-bottom-width' => '2px',
 'header-border-left-width' => '2px',
 'header-border-right-width' => '2px',
 'header-border-radius' => '4px',
 'header-padding' => '20px',
 'header-color' => '#000000',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '1',
 'header-backdrop-blur' => '0px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '14px',
 ),
 ),
 'dark-background-image' => 
 array (
 'is-active' => false,
 'id' => 'dark-background-image',
 'name' => 'Dark background image',
 'position' => 0,
 'select-position' => 15,
 'reviewer-photo' => true,
 'verified-icon' => true,
 'hide-logos' => false,
 'hide-stars' => false,
 '_vars' => 
 array (
 'style_id' => '"dark-background-image"',
 'bg-color' => '#ffffff',
 'text-color' => '#ffffff',
 'outside-text-color' => '#ffffff',
 'profile-color' => '#ffffff',
 'profile-font-size' => '15px',
 'review-font-size' => '15px',
 'rating-text' => '15px',
 'company-font-size' => '16px',
 'review-lines' => '4',
 'box-background-color' => '#000000',
 'box-border-color' => '#000000',
 'box-border-radius' => '5px',
 'box-padding' => '20px',
 'scroll-color' => '#555555',
 'arrow-color' => '#cccccc',
 'float-widget-align' => 'right',
 'nav' => 'desktop',
 'dots' => 'mobile',
 'hover-anim' => 'false',
 'review-italic' => 'false',
 'enable-font' => 'true',
 'align-mini' => 'center',
 'popup-background' => '#ffffff',
 'popup-company-color' => '#333333',
 'popup-company-size' => '16px',
 'popup-profile-color' => '#333333',
 'popup-profile-size' => '15px',
 'popup-review-color' => '#333333',
 'popup-review-size' => '14px',
 'popup-separator-color' => '#dedede',
 'popup-separator-width' => '1px',
 'box-shadow' => 'true',
 'box-shadow-color' => '#000000',
 'box-shadow-opacity' => '0.2',
 'box-border-top-width' => '0px',
 'box-border-bottom-width' => '0px',
 'box-border-left-width' => '0px',
 'box-border-right-width' => '0px',
 'box-background-opacity' => '0.3',
 'box-backdrop-blur' => '5px',
 'highlight-color' => '#fbe049',
 'highlight-size' => '19px',
 'review-title' => 'normal',
 'content-align' => 'center',
 'text-align' => 'left',
 'star-color' => '#f6bb06',
 'review-text-mode' => 'readmore',
 'aggregate-rating-text-size' => '24px',
 'nav-line' => 'mobile',
 'header-background-color' => '#000000',
 'header-border-color' => '#000000',
 'header-border-top-width' => '0px',
 'header-border-bottom-width' => '0px',
 'header-border-left-width' => '0px',
 'header-border-right-width' => '0px',
 'header-border-radius' => '5px',
 'header-padding' => '25px',
 'header-color' => '#ffffff',
 'header-shadow' => 'false',
 'header-shadow-color' => '#000000',
 'header-shadow-opacity' => '0.15',
 'header-background-opacity' => '0.3',
 'header-backdrop-blur' => '5px',
 'header-btn-background-color' => '#4285f4',
 'header-btn-border-color' => '#4285f4',
 'header-btn-border-top-width' => '1px',
 'header-btn-border-bottom-width' => '1px',
 'header-btn-border-left-width' => '1px',
 'header-btn-border-right-width' => '1px',
 'header-btn-border-radius' => '4px',
 'header-btn-padding' => '7px',
 'header-btn-color' => '#ffffff',
 'header-btn-size' => '13px',
 'review-gap' => '16px',
 'button-widget-font-size' => '14px',
 'ai-summary-background' => 'true',
 'original-rating-text' => '15px',
 ),
 ),
);
public static $widget_languages = [
'ar' => "العربية",
'zh' => "汉语",
'cs' => "Čeština",
'da' => "Dansk",
'nl' => "Nederlands",
'en' => "English",
'et' => "Eestlane",
'fi' => "Suomi",
'fr' => "Français",
'de' => "Deutsch",
'el' => "Ελληνικά",
'hi' => "हिन्दी",
'hu' => "Magyar",
'it' => "Italiano",
'no' => "Norsk",
'pl' => "Polski",
'pt' => "Português",
'ro' => "Română",
'ru' => "Русский",
'sk' => "Slovenčina",
'es' => "Español",
'sv' => "Svenska",
'tr' => "Türkçe",
'gd' => 'Gàidhlig na h-Alba',
'hr' => 'Hrvatski',
'id' => 'Bahasa Indonesia',
'is' => 'Íslensku',
'he' => 'עִברִית',
'ja' => '日本',
'ko' => '한국어',
'lt' => 'Lietuvių',
'ms' => 'Bahasa Melayu',
'sl' => 'Slovenščina',
'sr' => 'Српски',
'th' => 'ไทย',
'uk' => 'Українська',
'vi' => 'Tiếng Việt',
'mk' => 'Македонски',
'bg' => 'български',
'sq' => 'Shqip',
'af' => 'Afrikaans',
'az' => 'Azərbaycan dili',
'bn' => 'বাংলা',
'bs' => 'Bosanski',
'cy' => 'Cymraeg',
'fa' => 'فارسی',
'gl' => 'Galego',
'hy' => 'հայերեն',
'ka' => 'ქართული',
'kk' => 'қазақ'
];
public static $widget_dateformats = [ 'modern', 'j F Y', 'j. F, Y', 'F j, Y', 'Y.m.d.', 'Y-m-d', 'd/m/Y', 'hide' ];
private static $widget_rating_texts = array (
 'en' => 
 array (
 0 => 'poor',
 1 => 'below average',
 2 => 'average',
 3 => 'good',
 4 => 'excellent',
 ),
 'af' => 
 array (
 0 => 'arm',
 1 => 'onder gemiddeld',
 2 => 'gemiddeld',
 3 => 'goed',
 4 => 'uitstekend',
 ),
 'ar' => 
 array (
 0 => 'ضعيف',
 1 => 'مقبول',
 2 => 'متوسط',
 3 => 'جيد جدا',
 4 => 'ممتاز',
 ),
 'az' => 
 array (
 0 => 'kasıb',
 1 => 'ortalamadan aşağı',
 2 => 'orta',
 3 => 'yaxşı',
 4 => 'əla',
 ),
 'bg' => 
 array (
 0 => 'беден',
 1 => 'под средното',
 2 => 'средно аритметично',
 3 => 'добре',
 4 => 'отлично',
 ),
 'bn' => 
 array (
 0 => 'দরিদ্র',
 1 => 'গড়ের নিচে',
 2 => 'গড়',
 3 => 'ভাল',
 4 => 'চমৎকার',
 ),
 'bs' => 
 array (
 0 => 'jadan',
 1 => 'ispod prosjeka',
 2 => 'prosjek',
 3 => 'dobro',
 4 => 'odličan',
 ),
 'cs' => 
 array (
 0 => 'Slabý',
 1 => 'Podprůměrný',
 2 => 'Průměrný',
 3 => 'Dobrý',
 4 => 'Vynikající',
 ),
 'cy' => 
 array (
 0 => 'gwael',
 1 => 'islaw\'r cyfartaledd',
 2 => 'cyffredin',
 3 => 'da',
 4 => 'rhagorol',
 ),
 'da' => 
 array (
 0 => 'Svag',
 1 => 'Under gennemsnitlig',
 2 => 'Gennemsnitlig',
 3 => 'God',
 4 => 'Fremragende',
 ),
 'de' => 
 array (
 0 => 'Schwach',
 1 => 'Unterdurchschnittlich',
 2 => 'Durchschnittlich',
 3 => 'Gut',
 4 => 'Ausgezeichnet',
 ),
 'el' => 
 array (
 0 => 'Χαμηλή',
 1 => 'Κάτω από τον μέσο όρο',
 2 => 'Μέτρια',
 3 => 'Καλή',
 4 => 'Άριστη',
 ),
 'es' => 
 array (
 0 => 'Flojo',
 1 => 'Por debajo de lo regular',
 2 => 'Regular',
 3 => 'Bueno',
 4 => 'Excelente',
 ),
 'et' => 
 array (
 0 => 'halb',
 1 => 'alla keskmise',
 2 => 'keskmine',
 3 => 'hea',
 4 => 'suurepärane',
 ),
 'fa' => 
 array (
 0 => 'فقیر',
 1 => 'زیر میانگین',
 2 => 'میانگین',
 3 => 'خوب',
 4 => 'عالی',
 ),
 'fi' => 
 array (
 0 => 'Heikko',
 1 => 'Keskitasoa alhaisempi',
 2 => 'Keskitasoinen',
 3 => 'Hyvä',
 4 => 'Erinomainen',
 ),
 'fr' => 
 array (
 0 => 'faible',
 1 => 'moyenne basse',
 2 => 'moyenne',
 3 => 'bien',
 4 => 'excellent',
 ),
 'gd' => 
 array (
 0 => 'bochd',
 1 => 'nas ìsle na a ’chuibheasachd',
 2 => 'cuibheasach',
 3 => 'math',
 4 => 'sgoinneil',
 ),
 'gl' => 
 array (
 0 => 'pobre',
 1 => 'por debaixo da media',
 2 => 'media',
 3 => 'bo',
 4 => 'excelente',
 ),
 'he' => 
 array (
 0 => 'עני',
 1 => 'מתחת לממוצע',
 2 => 'מְמוּצָע',
 3 => 'טוֹב',
 4 => 'מְעוּלֶה',
 ),
 'hi' => 
 array (
 0 => 'कमज़ोर',
 1 => 'औसत से कम',
 2 => 'औसत',
 3 => 'अच्छा',
 4 => 'अति उत्कृष्ट',
 ),
 'hr' => 
 array (
 0 => 'slabo',
 1 => 'ispod prosjeka',
 2 => 'prosjed',
 3 => 'dobro',
 4 => 'odličan',
 ),
 'hu' => 
 array (
 0 => 'Gyenge',
 1 => 'Átlag alatti',
 2 => 'Átlagos',
 3 => 'Jó',
 4 => 'Kiváló',
 ),
 'hy' => 
 array (
 0 => 'աղքատ',
 1 => 'միջինից ցածր',
 2 => 'միջին',
 3 => 'լավ',
 4 => 'գերազանց',
 ),
 'id' => 
 array (
 0 => 'miskin',
 1 => 'dibawah rata-rata',
 2 => 'rata-rata',
 3 => 'bagus',
 4 => 'bagus sekali',
 ),
 'is' => 
 array (
 0 => 'fátækur',
 1 => 'fyrir neðan meðallag',
 2 => 'að meðaltali',
 3 => 'góður',
 4 => 'Æðislegt',
 ),
 'it' => 
 array (
 0 => 'Scarso',
 1 => 'Sotto la media',
 2 => 'Medio',
 3 => 'Buono',
 4 => 'Eccellente',
 ),
 'ja' => 
 array (
 0 => '悪い',
 1 => '平均以下の',
 2 => '平均',
 3 => '良い',
 4 => '優れた',
 ),
 'ka' => 
 array (
 0 => 'ღარიბი',
 1 => 'საშუალოზე დაბლა',
 2 => 'საშუალო',
 3 => 'კარგი',
 4 => 'შესანიშნავი',
 ),
 'kk' => 
 array (
 0 => 'кедей',
 1 => 'орташадан төмен',
 2 => 'орташа',
 3 => 'жақсы',
 4 => 'өте жақсы',
 ),
 'ko' => 
 array (
 0 => '가난한',
 1 => '평균 이하',
 2 => '평균',
 3 => '좋은',
 4 => '훌륭한',
 ),
 'lt' => 
 array (
 0 => 'vargšas',
 1 => 'žemiau vidurkio',
 2 => 'vidurkis',
 3 => 'gerai',
 4 => 'puikus',
 ),
 'mk' => 
 array (
 0 => 'Сиромашен',
 1 => 'под просек',
 2 => 'просек',
 3 => 'Добро',
 4 => 'одлично',
 ),
 'ms' => 
 array (
 0 => 'miskin',
 1 => 'bawah purata',
 2 => 'purata',
 3 => 'baik',
 4 => 'cemerlang',
 ),
 'nl' => 
 array (
 0 => 'Slecht',
 1 => 'Onder het gemiddelde',
 2 => 'Gemiddeld',
 3 => 'Goed',
 4 => 'Uitstekend',
 ),
 'no' => 
 array (
 0 => 'Dårlig',
 1 => 'Utilstrekkelig',
 2 => 'Gjennomsnittlig',
 3 => 'Bra',
 4 => 'Utmerket',
 ),
 'pl' => 
 array (
 0 => 'Słaba',
 1 => 'Poniżej średniej',
 2 => 'Średnia',
 3 => 'Dobra',
 4 => 'Doskonała',
 ),
 'pt' => 
 array (
 0 => 'Fraco',
 1 => 'Inferior ao médio',
 2 => 'Medíocre',
 3 => 'Bom',
 4 => 'Excelente',
 ),
 'ro' => 
 array (
 0 => 'sărac',
 1 => 'sub medie',
 2 => 'in medie',
 3 => 'bun',
 4 => 'excelent',
 ),
 'ru' => 
 array (
 0 => 'Слабо',
 1 => 'Ниже среднего',
 2 => 'Средний',
 3 => 'Хорошо',
 4 => 'Отлично',
 ),
 'sk' => 
 array (
 0 => 'Slabé',
 1 => 'Podpriemerné',
 2 => 'Priemerné',
 3 => 'Dobré',
 4 => 'Vynikajúce',
 ),
 'sl' => 
 array (
 0 => 'slabo',
 1 => 'pod povprečjem',
 2 => 'povprečno',
 3 => 'dobro',
 4 => 'odlično',
 ),
 'sq' => 
 array (
 0 => 'i varfer',
 1 => 'nën mesataren',
 2 => 'mesatare',
 3 => 'mire',
 4 => 'e shkëlqyeshme',
 ),
 'sr' => 
 array (
 0 => 'Слабо',
 1 => 'Испод просека',
 2 => 'Просек',
 3 => 'Добро',
 4 => 'Oдлично',
 ),
 'sv' => 
 array (
 0 => 'Dålig',
 1 => 'Under genomsnittet',
 2 => 'Genomsnittlig',
 3 => 'Bra',
 4 => 'Utmärkt',
 ),
 'th' => 
 array (
 0 => 'ยากจน',
 1 => 'ต่ำกว่าค่าเฉลี่ย',
 2 => 'เฉลี่ย',
 3 => 'ดี',
 4 => 'ยอดเยี่ยม',
 ),
 'tr' => 
 array (
 0 => 'Zayıf',
 1 => 'Ortanın altıi',
 2 => 'Orta',
 3 => 'İyi',
 4 => 'Mükemmel',
 ),
 'uk' => 
 array (
 0 => 'погано',
 1 => 'нижче середнього',
 2 => 'середній',
 3 => 'добре',
 4 => 'відмінно',
 ),
 'vi' => 
 array (
 0 => 'nghèo nàn',
 1 => 'dưới mức trung bình',
 2 => 'Trung bình',
 3 => 'tốt',
 4 => 'thông minh',
 ),
 'zh' => 
 array (
 0 => '差',
 1 => '不如一般',
 2 => '一般',
 3 => '好',
 4 => '非常好',
 ),
);
private static $widget_recommendation_texts = array (
 'en' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON not recommends',
 'positive' => 'RECOMMEND_ICON recommends',
 ),
 'af' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON beveel nie aan',
 'positive' => 'RECOMMEND_ICON beveel aan',
 ),
 'ar' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON لا توصي',
 'positive' => 'RECOMMEND_ICON توصي',
 ),
 'az' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON tövsiyə etmir',
 'positive' => 'RECOMMEND_ICON tövsiyə edir',
 ),
 'bg' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON не препоръчва',
 'positive' => 'RECOMMEND_ICON препоръчва',
 ),
 'bn' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON সুপারিশ করে না',
 'positive' => 'RECOMMEND_ICON সুপারিশ করে',
 ),
 'bs' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON ne preporučuje',
 'positive' => 'RECOMMEND_ICON preporučuje',
 ),
 'cs' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON nedoporučuje',
 'positive' => 'RECOMMEND_ICON doporučuje',
 ),
 'cy' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON ddim yn argymell',
 'positive' => 'RECOMMEND_ICON yn argymell',
 ),
 'da' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON anbefaler ikke',
 'positive' => 'RECOMMEND_ICON anbefaler',
 ),
 'de' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON wird nicht empfohlen',
 'positive' => 'RECOMMEND_ICON empfiehlt',
 ),
 'el' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON δεν συνιστά',
 'positive' => 'RECOMMEND_ICON συνιστά',
 ),
 'es' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON no recomienda',
 'positive' => 'RECOMMEND_ICON recomienda',
 ),
 'et' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON ei soovita',
 'positive' => 'RECOMMEND_ICON soovitab',
 ),
 'fa' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON توصیه نمی کند',
 'positive' => 'RECOMMEND_ICON توصیه می‌کند',
 ),
 'fi' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON ei suosittele',
 'positive' => 'RECOMMEND_ICON suosittelee',
 ),
 'fr' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON ne recommande pas',
 'positive' => 'RECOMMEND_ICON recommande',
 ),
 'gd' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON no moladh',
 'positive' => 'RECOMMEND_ICON a ’moladh',
 ),
 'gl' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON non recomendado',
 'positive' => 'RECOMMEND_ICON recomenda',
 ),
 'he' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON לא ממליץ',
 'positive' => 'RECOMMEND_ICON ממליץ',
 ),
 'hi' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON अनुशंसा नहीं करता है',
 'positive' => 'RECOMMEND_ICON अनुशंसा करता है',
 ),
 'hr' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON ne preporučuje',
 'positive' => 'RECOMMEND_ICON preporučuje',
 ),
 'hu' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON nem ajánlja',
 'positive' => 'RECOMMEND_ICON ajánlja',
 ),
 'hy' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON-ը խորհուրդ չի տալիս',
 'positive' => 'RECOMMEND_ICON խորհուրդ է տալիս',
 ),
 'id' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON tidak merekomendasikan',
 'positive' => 'RECOMMEND_ICON merekomendasikan',
 ),
 'is' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON mælir ekki með',
 'positive' => 'RECOMMEND_ICON mælir með',
 ),
 'it' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON non lo consiglia',
 'positive' => 'RECOMMEND_ICON consiglia',
 ),
 'ja' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON おすすめできない',
 'positive' => 'RECOMMEND_ICON おすすめ',
 ),
 'ka' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON არ გირჩევთ',
 'positive' => 'RECOMMEND_ICON გირჩევთ',
 ),
 'kk' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON ұсынбайды',
 'positive' => 'RECOMMEND_ICON ұсынады',
 ),
 'ko' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON 권장하지 않음',
 'positive' => 'RECOMMEND_ICON 추천',
 ),
 'lt' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON nerekomenduoja',
 'positive' => 'RECOMMEND_ICON rekomenduoja',
 ),
 'mk' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON не препорачува',
 'positive' => 'RECOMMEND_ICON препорачува',
 ),
 'ms' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON tidak mengesyorkan',
 'positive' => 'RECOMMEND_ICON mengesyorkan',
 ),
 'nl' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON niet aanbevolen',
 'positive' => 'RECOMMEND_ICON aanbevolen',
 ),
 'no' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON anbefaler ikke',
 'positive' => 'RECOMMEND_ICON anbefaler',
 ),
 'pl' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON nie poleca',
 'positive' => 'RECOMMEND_ICON poleca',
 ),
 'pt' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON não recomenda',
 'positive' => 'RECOMMEND_ICON recomenda',
 ),
 'ro' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON nu se recomandă',
 'positive' => 'RECOMMEND_ICON recomandă',
 ),
 'ru' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON не рекомендует',
 'positive' => 'RECOMMEND_ICON рекомендует',
 ),
 'sk' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON neodporúča',
 'positive' => 'RECOMMEND_ICON odporúča',
 ),
 'sl' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON ne priporoča',
 'positive' => 'RECOMMEND_ICON priporoča',
 ),
 'sq' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON nuk rekomandon',
 'positive' => 'RECOMMEND_ICON rekomandon',
 ),
 'sr' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON не препоручује',
 'positive' => 'RECOMMEND_ICON препоручује',
 ),
 'sv' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON rekommenderar inte',
 'positive' => 'RECOMMEND_ICON rekommenderar',
 ),
 'th' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON ไม่แนะนำ',
 'positive' => 'RECOMMEND_ICON แนะนำ',
 ),
 'tr' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON önerilmez',
 'positive' => 'RECOMMEND_ICON önerir',
 ),
 'uk' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON не рекомендує',
 'positive' => 'RECOMMEND_ICON рекомендує',
 ),
 'vi' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON không được đề xuất',
 'positive' => 'RECOMMEND_ICON đề xuất',
 ),
 'zh' => 
 array (
 'negative' => 'NOT_RECOMMEND_ICON 不推荐',
 'positive' => 'RECOMMEND_ICON 推荐',
 ),
);
private static $widget_verified_texts = array (
 'en' => 'Verified',
 'af' => 'Geverifieer',
 'ar' => 'تم التحقق',
 'az' => 'Doğrulanmışdır',
 'bg' => 'Проверени',
 'bn' => 'যাচাই',
 'bs' => 'Provjereno',
 'cs' => 'Ověřená',
 'cy' => 'Wedi\'i ddilysu',
 'da' => 'Bekræftet',
 'de' => 'Verifiziert',
 'el' => 'επαληθεύτηκε',
 'es' => 'Verificada',
 'et' => 'Kinnitatud',
 'fa' => 'تأیید شده',
 'fi' => 'Vahvistettu',
 'fr' => 'vérifié',
 'gd' => 'Dearbhaichte',
 'gl' => 'Verificado',
 'he' => 'מְאוּמָת',
 'hi' => 'सत्यापित',
 'hr' => 'Potvrđen',
 'hu' => 'Hitelesített',
 'hy' => 'Ստուգված',
 'id' => 'Diverifikasi',
 'is' => 'Staðfesting',
 'it' => 'Verificata',
 'ja' => '確認済み',
 'ka' => 'დამოწმებული',
 'kk' => 'тексерілген',
 'ko' => '검증 된',
 'lt' => 'Patvirtinta',
 'mk' => 'Потврдена',
 'ms' => 'Disahkan',
 'nl' => 'Geverifieerd',
 'no' => 'Bekreftet',
 'pl' => 'Zweryfikowana',
 'pt' => 'Verificada',
 'ro' => 'Verificat',
 'ru' => 'Проверенный',
 'sk' => 'Overená',
 'sl' => 'Preverjeno',
 'sq' => 'Verifikuar',
 'sr' => 'Проверено',
 'sv' => 'Verifierad',
 'th' => 'ตรวจสอบแล้ว',
 'tr' => 'Doğrulanmış',
 'uk' => 'Перевірено',
 'vi' => 'Đã xác minh',
 'zh' => '已验证',
);
private static $widget_footer_filter_texts = array (
 'en' => 
 array (
 'star' => 'Showing only RATING_STAR_FILTER star reviews',
 'latest' => 'Showing our latest reviews',
 ),
 'af' => 
 array (
 'star' => 'Wys tans net RATING_STAR_FILTER sterresensies',
 'latest' => 'Wys ons jongste resensies',
 ),
 'ar' => 
 array (
 'star' => 'يتم عرض تقييمات RATING_STAR_FILTER نجمة فقط',
 'latest' => 'عرض أحدث تقييماتنا',
 ),
 'az' => 
 array (
 'star' => 'Yalnız RATING_STAR_FILTER ulduzlu rəylər göstərilir',
 'latest' => 'Ən son rəylərimiz göstərilir',
 ),
 'bg' => 
 array (
 'star' => 'Показани са само отзиви с RATING_STAR_FILTER звезди',
 'latest' => 'Показване на най-новите ни отзиви',
 ),
 'bn' => 
 array (
 'star' => 'শুধুমাত্র RATING_STAR_FILTER স্টার রিভিউ দেখানো হচ্ছে',
 'latest' => 'আমাদের সর্বশেষ পর্যালোচনা দেখাচ্ছে',
 ),
 'bs' => 
 array (
 'star' => 'Prikazuju se samo recenzije sa RATING_STAR_FILTER zvjezdicama',
 'latest' => 'Prikazujemo naše najnovije recenzije',
 ),
 'cs' => 
 array (
 'star' => 'Zobrazují se pouze recenze s RATING_STAR_FILTER hvězdičkami',
 'latest' => 'Zobrazujeme naše nejnovější recenze',
 ),
 'cy' => 
 array (
 'star' => 'Yn dangos adolygiadau seren RATING_STAR_FILTER yn unig',
 'latest' => 'Yn dangos ein adolygiadau diweddaraf',
 ),
 'da' => 
 array (
 'star' => 'Viser kun RATING_STAR_FILTER stjerneanmeldelser',
 'latest' => 'Viser vores seneste anmeldelser',
 ),
 'de' => 
 array (
 'star' => 'Es werden nur RATING_STAR_FILTER Sternebewertungen angezeigt',
 'latest' => 'Wir zeigen unsere neuesten Bewertungen',
 ),
 'el' => 
 array (
 'star' => 'Εμφάνιση μόνο RATING_STAR_FILTER κριτικές με αστέρια',
 'latest' => 'Εμφάνιση των τελευταίων κριτικές μας',
 ),
 'es' => 
 array (
 'star' => 'Mostrando solo RATING_STAR_FILTER reseñas de estrellas',
 'latest' => 'Mostrando nuestras últimas reseñas',
 ),
 'et' => 
 array (
 'star' => 'Kuvatakse ainult RATING_STAR_FILTER tärniga arvustused',
 'latest' => 'Kuvatakse meie viimased arvustused',
 ),
 'fa' => 
 array (
 'star' => 'نمایش فقط نظرات RATING_STAR_FILTER ستاره',
 'latest' => 'نمایش آخرین نظرات ما',
 ),
 'fi' => 
 array (
 'star' => 'Näytetään vain RATING_STAR_FILTER tähden arvostelut',
 'latest' => 'Näytetään viimeisimmät arvostelut',
 ),
 'fr' => 
 array (
 'star' => 'Affichage de RATING_STAR_FILTER avis étoiles uniquement',
 'latest' => 'Affichage de nos derniers avis',
 ),
 'gd' => 
 array (
 'star' => 'A’ sealltainn RATING_STAR_FILTER lèirmheasan rionnagan a-mhàin',
 'latest' => 'A’ sealltainn na lèirmheasan as ùire againn',
 ),
 'gl' => 
 array (
 'star' => 'Mostrando só RATING_STAR_FILTER comentarios de estrelas',
 'latest' => 'Mostrando os nosos últimos comentarios',
 ),
 'he' => 
 array (
 'star' => 'מציג רק RATING_STAR_FILTER ביקורות כוכבים',
 'latest' => 'מציג את הביקורות האחרונות שלנו',
 ),
 'hi' => 
 array (
 'star' => 'मैन्युअल रूप से चुनी गई समीक्षाएँ दिखाई जा रही हैं',
 'latest' => 'हमारी सबसे पुरानी समीक्षाएँ दिखा रहा हूँ',
 ),
 'hr' => 
 array (
 'star' => 'Prikazuju se samo recenzije s RATING_STAR_FILTER zvjezdica',
 'latest' => 'Prikazuju se naše najnovije recenzije',
 ),
 'hu' => 
 array (
 'star' => 'Csak RATING_STAR_FILTER csillagos vélemények láthatók',
 'latest' => 'Legkorábbi véleményeink láthatók',
 ),
 'hy' => 
 array (
 'star' => 'Ցուցադրվում են միայն RATING_STAR_FILTER աստղային կարծիքներ',
 'latest' => 'Ցուցադրվում են մեր վերջին ակնարկները',
 ),
 'id' => 
 array (
 'star' => 'Hanya menampilkan ulasan berbintang RATING_STAR_FILTER',
 'latest' => 'Menampilkan ulasan terbaru kami',
 ),
 'is' => 
 array (
 'star' => 'Sýnir aðeins RATING_STAR_FILTER stjörnu umsagnir',
 'latest' => 'Sýnir nýjustu umsagnirnar okkar',
 ),
 'it' => 
 array (
 'star' => 'Vengono visualizzate solo RATING_STAR_FILTER recensioni a stelle',
 'latest' => 'Mostrando le nostre ultime recensioni',
 ),
 'ja' => 
 array (
 'star' => 'RATING_STAR_FILTER の星付きレビューのみを表示しています',
 'latest' => '最新のレビューを表示しています',
 ),
 'ka' => 
 array (
 'star' => 'ნაჩვენებია მხოლოდ RATING_STAR_FILTER ვარსკვლავიანი მიმოხილვები',
 'latest' => 'ნაჩვენებია ჩვენი უახლესი მიმოხილვები',
 ),
 'kk' => 
 array (
 'star' => 'Тек RATING_STAR_FILTER жұлдызды шолулар көрсетілген',
 'latest' => 'Соңғы шолуларымызды көрсету',
 ),
 'ko' => 
 array (
 'star' => '별점 리뷰 RATING_STAR_FILTER 개만 표시',
 'latest' => '최신 리뷰 표시',
 ),
 'lt' => 
 array (
 'star' => 'Rodomi tik RATING_STAR_FILTER žvaigždučių atsiliepimais',
 'latest' => 'Rodomi mūsų naujausios atsiliepimais',
 ),
 'mk' => 
 array (
 'star' => 'Се прикажуваат само RATING_STAR_FILTER рецензии со ѕвезди',
 'latest' => 'Се прикажуваат нашите најнови критики',
 ),
 'ms' => 
 array (
 'star' => 'Menunjukkan ulasan bintang RATING_STAR_FILTER sahaja',
 'latest' => 'Menunjukkan ulasan terkini kami',
 ),
 'nl' => 
 array (
 'star' => 'Er worden alleen RATING_STAR_FILTER sterrecensies weergegeven',
 'latest' => 'Toont onze laatste recensies',
 ),
 'no' => 
 array (
 'star' => 'Viser bare RATING_STAR_FILTER stjerneanmeldelser',
 'latest' => 'Viser de siste anmeldelsene våre',
 ),
 'pl' => 
 array (
 'star' => 'Wyświetlanie tylko RATING_STAR_FILTER opinii w postaci gwiazdek',
 'latest' => 'Wyświetlanie naszych najnowszych opinii',
 ),
 'pt' => 
 array (
 'star' => 'Mostrando apenas avaliações com estrelas de RATING_STAR_FILTER',
 'latest' => 'Mostrando nossas avaliações mais recentes',
 ),
 'ro' => 
 array (
 'star' => 'Se afișează numai RATING_STAR_FILTER recenzii cu stele',
 'latest' => 'Se afișează cele mai recente recenzii ale noastre',
 ),
 'ru' => 
 array (
 'star' => 'Показаны только отзывы со звездами RATING_STAR_FILTER',
 'latest' => 'Показаны наши последние отзывы',
 ),
 'sk' => 
 array (
 'star' => 'Zobrazujú sa iba recenzie s RATING_STAR_FILTER hviezdičkami',
 'latest' => 'Zobrazujú sa naše najnovšie recenzie',
 ),
 'sl' => 
 array (
 'star' => 'Prikazane so le ocen s RATING_STAR_FILTER zvezdicami',
 'latest' => 'Prikazane so naše najnovejše ocen',
 ),
 'sq' => 
 array (
 'star' => 'Duke shfaqur vetëm komente RATING_STAR_FILTER yje',
 'latest' => 'Duke shfaqur komentet tona më të fundit',
 ),
 'sr' => 
 array (
 'star' => 'Приказују се само рецензије са RATING_STAR_FILTER звездицама',
 'latest' => 'Приказујемо наше најновије рецензије',
 ),
 'sv' => 
 array (
 'star' => 'Visar endast RATING_STAR_FILTER stjärnrecensioner',
 'latest' => 'Visar våra senaste recensioner',
 ),
 'th' => 
 array (
 'star' => 'แสดงเฉพาะบทวิจารณ์ระดับ RATING_STAR_FILTER ดาว',
 'latest' => 'แสดงความคิดเห็นล่าสุดของเรา',
 ),
 'tr' => 
 array (
 'star' => 'Yalnızca RATING_STAR_FILTER yıldızlı değerlendirmeler gösteriliyor',
 'latest' => 'En son değerlendirmeler gösteriliyor',
 ),
 'uk' => 
 array (
 'star' => 'Показано лише RATING_STAR_FILTER старих рецензії',
 'latest' => 'Показано наші останні рецензії',
 ),
 'vi' => 
 array (
 'star' => 'Chỉ hiển thị RATING_STAR_FILTER bài đánh giá sao',
 'latest' => 'Đang hiển thị các đánh giá mới nhất của chúng tôi',
 ),
 'zh' => 
 array (
 'star' => '仅显示 RATING_STAR_FILTER 星评价',
 'latest' => '显示我们的最新评论',
 ),
);
public static $verified_platforms = array (
 0 => 'Abia',
 1 => 'Agoda',
 2 => 'Airbnb',
 3 => 'Alibaba',
 4 => 'Aliexpress',
 5 => 'Amazon',
 6 => 'AppleAppstore',
 7 => 'Booking',
 8 => 'CarGurus',
 9 => 'Classpass',
 10 => 'Ebay',
 11 => 'Ekomi',
 12 => 'Etsy',
 13 => 'Expedia',
 14 => 'Fresha',
 15 => 'Getyourguide',
 16 => 'Hotels',
 17 => 'HotelSpecials',
 18 => 'Immobilienscout24',
 19 => 'Indeed',
 20 => 'Justdial',
 21 => 'Lawyerscom',
 22 => 'Martindale',
 23 => 'Meilleursagents',
 24 => 'Mobilede',
 25 => 'OnlinePenztarca',
 26 => 'Opentable',
 27 => 'Peerspot',
 28 => 'ProductReview',
 29 => 'Realself',
 30 => 'Reco',
 31 => 'Resellerratings',
 32 => 'ReserveOut',
 33 => 'Reviewsio',
 34 => 'Sitejabber',
 35 => 'SoftwareAdvice',
 36 => 'SourceForge',
 37 => 'Szallashu',
 38 => 'Talabat',
 39 => 'Tandlakare',
 40 => 'TheFork',
 41 => 'Thumbtack',
 42 => 'Tripadvisor',
 43 => 'TrustedShops',
 44 => 'TrustRadius',
 45 => 'Vardense',
 46 => 'Vrbo',
 47 => 'WeddingWire',
 48 => 'Whatclinic',
 49 => 'Whichtrustedtraders',
 50 => 'Yelp',
 51 => 'Zillow',
 52 => 'ZocDoc',
 53 => 'Zomato',
 54 => 'G2Crowd',
 55 => 'FertilityIQ',
);
private static $widget_month_names = array (
 'en' => 
 array (
 0 => 'January',
 1 => 'February',
 2 => 'March',
 3 => 'April',
 4 => 'May',
 5 => 'June',
 6 => 'July',
 7 => 'August',
 8 => 'September',
 9 => 'October',
 10 => 'November',
 11 => 'December',
 ),
 'af' => 
 array (
 0 => 'Januarie',
 1 => 'Februarie',
 2 => 'Maart',
 3 => 'April',
 4 => 'Mei',
 5 => 'Junie',
 6 => 'Julie',
 7 => 'Augustus',
 8 => 'September',
 9 => 'Oktober',
 10 => 'November',
 11 => 'Desember',
 ),
 'ar' => 
 array (
 0 => 'يناير',
 1 => 'فبراير',
 2 => 'مارس',
 3 => 'أبريل',
 4 => 'مايو',
 5 => 'يونيو',
 6 => 'يوليه',
 7 => 'أغسطس',
 8 => 'سبتمبر',
 9 => 'أكتوبر',
 10 => 'نوفمبر',
 11 => 'ديسمبر',
 ),
 'az' => 
 array (
 0 => 'Yanvar',
 1 => 'Fevral',
 2 => 'Mart',
 3 => 'Aprel',
 4 => 'May',
 5 => 'İyun',
 6 => 'İyul',
 7 => 'Avqust',
 8 => 'Sentyabr',
 9 => 'Oktyabr',
 10 => 'Noyabr',
 11 => 'Dekabr',
 ),
 'bg' => 
 array (
 0 => 'Януари',
 1 => 'февруари',
 2 => 'Март',
 3 => 'Aприл',
 4 => 'май',
 5 => 'юни',
 6 => 'юли',
 7 => 'Август',
 8 => 'Септември',
 9 => 'Октомври',
 10 => 'Ноември',
 11 => 'Декември',
 ),
 'bn' => 
 array (
 0 => 'জানুয়ারি',
 1 => 'ফেব্রুয়ারি',
 2 => 'মার্চ',
 3 => 'এপ্রিল',
 4 => 'মে',
 5 => 'জুন',
 6 => 'জুলাই',
 7 => 'আগস্ট',
 8 => 'সেপ্টেম্বর',
 9 => 'অক্টোবর',
 10 => 'নভেম্বর',
 11 => 'ডিসেম্বর',
 ),
 'bs' => 
 array (
 0 => 'Januar',
 1 => 'Februar',
 2 => 'Mart',
 3 => 'April',
 4 => 'Maj',
 5 => 'Jun',
 6 => 'Jul',
 7 => 'Avgust',
 8 => 'Septembar',
 9 => 'Oktobar',
 10 => 'Novembar',
 11 => 'Decembar',
 ),
 'cs' => 
 array (
 0 => 'Leden',
 1 => 'Únor',
 2 => 'Březen',
 3 => 'Duben',
 4 => 'Květen',
 5 => 'Červen',
 6 => 'Červenec',
 7 => 'Srpen',
 8 => 'Září',
 9 => 'Říjen',
 10 => 'Listopad',
 11 => 'Prosinec',
 ),
 'cy' => 
 array (
 0 => 'Ionawr',
 1 => 'Chwefror',
 2 => 'Mawrth',
 3 => 'Ebrill',
 4 => 'Mai',
 5 => 'Mehefin',
 6 => 'Gorffennaf',
 7 => 'Awst',
 8 => 'Medi',
 9 => 'Hydref',
 10 => 'Tachwedd',
 11 => 'Rhagfyr',
 ),
 'da' => 
 array (
 0 => 'Januar',
 1 => 'Februar',
 2 => 'Marts',
 3 => 'April',
 4 => 'Maj',
 5 => 'Juni',
 6 => 'Juli',
 7 => 'August',
 8 => 'September',
 9 => 'Oktober',
 10 => 'November',
 11 => 'December',
 ),
 'de' => 
 array (
 0 => 'Januar',
 1 => 'Februar',
 2 => 'März',
 3 => 'April',
 4 => 'Mai',
 5 => 'Juni',
 6 => 'Juli',
 7 => 'August',
 8 => 'September',
 9 => 'Oktober',
 10 => 'November',
 11 => 'Dezember',
 ),
 'el' => 
 array (
 0 => 'Iανουάριος',
 1 => 'Φεβρουάριος',
 2 => 'Μάρτιος',
 3 => 'Aρίλιος',
 4 => 'Μάιος',
 5 => 'Iούνιος',
 6 => 'Iούλιος',
 7 => 'Αύγουστος',
 8 => 'Σεπτέμβριος',
 9 => 'Oκτώβριος',
 10 => 'Νοέμβριος',
 11 => 'Δεκέμβριος',
 ),
 'es' => 
 array (
 0 => 'Enero',
 1 => 'Febrero',
 2 => 'Marzo',
 3 => 'Abril',
 4 => 'Mayo',
 5 => 'Junio',
 6 => 'Julio',
 7 => 'Agosto',
 8 => 'Septiembre',
 9 => 'Octubre',
 10 => 'Noviembre',
 11 => 'Diciembre',
 ),
 'et' => 
 array (
 0 => 'jaanuar',
 1 => 'veebruar',
 2 => 'märts',
 3 => 'aprill',
 4 => 'mai',
 5 => 'juuni',
 6 => 'juuli',
 7 => 'august',
 8 => 'september',
 9 => 'oktoober',
 10 => 'november',
 11 => 'detsember',
 ),
 'fa' => 
 array (
 0 => 'ژانویه',
 1 => 'فوریه',
 2 => 'مارس',
 3 => 'آوریل',
 4 => 'ممکن است',
 5 => 'ژوئن',
 6 => 'جولای',
 7 => 'اوت',
 8 => 'سپتامبر',
 9 => 'اکتبر',
 10 => 'نوامبر',
 11 => 'دسامبر',
 ),
 'fi' => 
 array (
 0 => 'Tammikuu',
 1 => 'Helmikuu',
 2 => 'Maaliskuu',
 3 => 'Huhtikuu',
 4 => 'Toukokuu',
 5 => 'Kesäkuu',
 6 => 'Heinäkuu',
 7 => 'Elokuu',
 8 => 'Syyskuu',
 9 => 'Lokakuu',
 10 => 'Marraskuu',
 11 => 'Joulukuu',
 ),
 'fr' => 
 array (
 0 => 'Janvier',
 1 => 'Février',
 2 => 'Mars',
 3 => 'Avril',
 4 => 'Mai',
 5 => 'Juin',
 6 => 'Juillet',
 7 => 'Août',
 8 => 'Septembre',
 9 => 'Octobre',
 10 => 'Novembre',
 11 => 'Décembre',
 ),
 'gd' => 
 array (
 0 => 'am Faoilleach',
 1 => 'an Gearran',
 2 => 'am Màrt',
 3 => 'an Giblean',
 4 => 'an Cèitean',
 5 => 'an t-Ògmhios',
 6 => 'an t-luchar',
 7 => 'an Lùnastal',
 8 => 'an t-Sultain',
 9 => 'an Dàmhair',
 10 => 'an t-Samhain',
 11 => 'an Dùbhlachd',
 ),
 'gl' => 
 array (
 0 => 'Xaneiro',
 1 => 'Febreiro',
 2 => 'Marzo',
 3 => 'Abril',
 4 => 'Maio',
 5 => 'Xuño',
 6 => 'Xullo',
 7 => 'Agosto',
 8 => 'Setembro',
 9 => 'Outubro',
 10 => 'Novembro',
 11 => 'Decembro',
 ),
 'he' => 
 array (
 0 => 'ינואר',
 1 => 'פברואר',
 2 => 'מרץ',
 3 => 'אפריל',
 4 => 'מאי',
 5 => 'יוני',
 6 => 'יולי',
 7 => 'אוגוסט',
 8 => 'ספטמבר',
 9 => 'אוקטובר',
 10 => 'נובמבר',
 11 => 'דצמבר',
 ),
 'hi' => 
 array (
 0 => 'जनवरी',
 1 => 'फ़रवरी',
 2 => 'मार्च',
 3 => 'अप्रैल',
 4 => 'मई',
 5 => 'जून',
 6 => 'जुलाई',
 7 => 'अगस्त',
 8 => 'सितंबर',
 9 => 'अक्टूबर',
 10 => 'नवंबर',
 11 => 'दिसंबर',
 ),
 'hr' => 
 array (
 0 => 'Siječanj',
 1 => 'Veljača',
 2 => 'Ožujak',
 3 => 'Travanj',
 4 => 'Svibanj',
 5 => 'Lipanj',
 6 => 'Srpanj',
 7 => 'Kolovoz',
 8 => 'Rujan',
 9 => 'Listopad',
 10 => 'Studeni',
 11 => 'Prosinac',
 ),
 'hu' => 
 array (
 0 => 'Január',
 1 => 'Február',
 2 => 'Március',
 3 => 'Április',
 4 => 'Május',
 5 => 'Június',
 6 => 'Július',
 7 => 'Augusztus',
 8 => 'Szeptember',
 9 => 'Október',
 10 => 'November',
 11 => 'December',
 ),
 'hy' => 
 array (
 0 => 'Հունվար',
 1 => 'փետրվար',
 2 => 'մարտ',
 3 => 'ապրիլ',
 4 => 'մայիս',
 5 => 'հունիս',
 6 => 'հուլիս',
 7 => 'օգոստոս',
 8 => 'սեպտեմբեր',
 9 => 'հոկտեմբեր',
 10 => 'նոյեմբեր',
 11 => 'դեկտեմբեր',
 ),
 'id' => 
 array (
 0 => 'Januari',
 1 => 'Februari',
 2 => 'Maret',
 3 => 'April',
 4 => 'Mei',
 5 => 'Juni',
 6 => 'Juli',
 7 => 'Agustus',
 8 => 'September',
 9 => 'Oktober',
 10 => 'November',
 11 => 'Desember',
 ),
 'is' => 
 array (
 0 => 'Janúar',
 1 => 'Febrúar',
 2 => 'Mars',
 3 => 'April',
 4 => 'Maí',
 5 => 'Júní',
 6 => 'Júlí',
 7 => 'Ágúst',
 8 => 'September',
 9 => 'Október',
 10 => 'Nóvember',
 11 => 'Desember',
 ),
 'it' => 
 array (
 0 => 'Gennaio',
 1 => 'Febbraio',
 2 => 'Marzo',
 3 => 'Aprile',
 4 => 'Maggio',
 5 => 'Giugno',
 6 => 'Luglio',
 7 => 'Agosto',
 8 => 'Settembre',
 9 => 'Ottobre',
 10 => 'Novembre',
 11 => 'Dicembre',
 ),
 'ja' => 
 array (
 0 => '1月',
 1 => '2月',
 2 => '3月',
 3 => '4月',
 4 => '5月',
 5 => '6月',
 6 => '7月',
 7 => '8月',
 8 => '9月',
 9 => '10月',
 10 => '11月',
 11 => '12月',
 ),
 'ka' => 
 array (
 0 => 'იანვარი',
 1 => 'თებერვალი',
 2 => 'მარტი',
 3 => 'აპრილი',
 4 => 'მაისი',
 5 => 'ივნისი',
 6 => 'ივლისი',
 7 => 'აგვისტო',
 8 => 'სექტემბერი',
 9 => 'ოქტომბერი',
 10 => 'ნოემბერი',
 11 => 'დეკემბერი',
 ),
 'kk' => 
 array (
 0 => 'қаңтар',
 1 => 'ақпан',
 2 => 'наурыз',
 3 => 'сәуір',
 4 => 'мамыр',
 5 => 'маусым',
 6 => 'шілде',
 7 => 'тамыз',
 8 => 'қыркүйек',
 9 => 'қазан',
 10 => 'қараша',
 11 => 'желтоқсан',
 ),
 'ko' => 
 array (
 0 => '일월',
 1 => '이월',
 2 => '삼월',
 3 => '사월',
 4 => '오월',
 5 => '유월',
 6 => '칠월',
 7 => '팔월',
 8 => '구월',
 9 => '시월',
 10 => '십일월',
 11 => '십이월',
 ),
 'lt' => 
 array (
 0 => 'Sausis',
 1 => 'Vasaris',
 2 => 'Kovas',
 3 => 'Balandis',
 4 => 'Gegužė',
 5 => 'Birželis',
 6 => 'Liepa',
 7 => 'Rugpjūtis',
 8 => 'Rugsėjis',
 9 => 'Spalis',
 10 => 'Lapkritis',
 11 => 'Gruodis',
 ),
 'mk' => 
 array (
 0 => 'Jануари',
 1 => 'февруари',
 2 => 'март',
 3 => 'април',
 4 => 'мај',
 5 => 'јуни',
 6 => 'јули',
 7 => 'август',
 8 => 'септември',
 9 => 'октомври',
 10 => 'ноември',
 11 => 'декември',
 ),
 'ms' => 
 array (
 0 => 'Januari',
 1 => 'Februari',
 2 => 'Mac',
 3 => 'April',
 4 => 'Mei',
 5 => 'Jun',
 6 => 'Julai',
 7 => 'Ogos',
 8 => 'September',
 9 => 'Oktober',
 10 => 'November',
 11 => 'Disember',
 ),
 'nl' => 
 array (
 0 => 'Januari',
 1 => 'Februari',
 2 => 'Maart',
 3 => 'April',
 4 => 'Mei',
 5 => 'Juni',
 6 => 'Juli',
 7 => 'Augustus',
 8 => 'September',
 9 => 'Oktober',
 10 => 'November',
 11 => 'December',
 ),
 'no' => 
 array (
 0 => 'Januar',
 1 => 'Februar',
 2 => 'Mars',
 3 => 'April',
 4 => 'Mai',
 5 => 'Juni',
 6 => 'Juli',
 7 => 'August',
 8 => 'September',
 9 => 'Oktober',
 10 => 'November',
 11 => 'Desember',
 ),
 'pl' => 
 array (
 0 => 'Styczeń',
 1 => 'Luty',
 2 => 'Marzec',
 3 => 'Kwiecień',
 4 => 'Maj',
 5 => 'Czerwiec',
 6 => 'Lipiec',
 7 => 'Sierpień',
 8 => 'Wrzesień',
 9 => 'Październik',
 10 => 'Listopad',
 11 => 'Grudzień',
 ),
 'pt' => 
 array (
 0 => 'Janeiro',
 1 => 'Fevereiro',
 2 => 'Março',
 3 => 'Abril',
 4 => 'Maio',
 5 => 'Junho',
 6 => 'Julho',
 7 => 'Agosto',
 8 => 'Setembro',
 9 => 'Outubro',
 10 => 'Novembro',
 11 => 'Dezembro',
 ),
 'ro' => 
 array (
 0 => 'Ianuarie',
 1 => 'Februarie',
 2 => 'Martie',
 3 => 'Aprilie',
 4 => 'Mai',
 5 => 'Iunie',
 6 => 'Iulie',
 7 => 'August',
 8 => 'Septembrie',
 9 => 'Octombrie',
 10 => 'Noiembrie',
 11 => 'Decembrie',
 ),
 'ru' => 
 array (
 0 => 'январь',
 1 => 'февраль',
 2 => 'март',
 3 => 'апрель',
 4 => 'май',
 5 => 'июнь',
 6 => 'июль',
 7 => 'август',
 8 => 'сентябрь',
 9 => 'октябрь',
 10 => 'ноябрь',
 11 => 'декабрь',
 ),
 'sk' => 
 array (
 0 => 'Január',
 1 => 'Február',
 2 => 'Marec',
 3 => 'Apríl',
 4 => 'Máj',
 5 => 'Jún',
 6 => 'Júl',
 7 => 'August',
 8 => 'September',
 9 => 'Október',
 10 => 'November',
 11 => 'December',
 ),
 'sl' => 
 array (
 0 => 'Januar',
 1 => 'Februar',
 2 => 'Marec',
 3 => 'April',
 4 => 'Maj',
 5 => 'Junij',
 6 => 'Julij',
 7 => 'Avgust',
 8 => 'September',
 9 => 'Oktober',
 10 => 'November',
 11 => 'December',
 ),
 'sq' => 
 array (
 0 => 'Janar',
 1 => 'Shkurt',
 2 => 'Mars',
 3 => 'Prill',
 4 => 'Maj',
 5 => 'Qershor',
 6 => 'Korrik',
 7 => 'Gusht',
 8 => 'Shtator',
 9 => 'Tetor',
 10 => 'Nëntor',
 11 => 'Dhjetor',
 ),
 'sr' => 
 array (
 0 => 'Јануар',
 1 => 'Фебруар',
 2 => 'Март',
 3 => 'Април',
 4 => 'Mај',
 5 => 'Јуни',
 6 => 'Јул',
 7 => 'Август',
 8 => 'Cептембар',
 9 => 'Октобар',
 10 => 'Новембар',
 11 => 'Децембар',
 ),
 'sv' => 
 array (
 0 => 'Januari',
 1 => 'Februari',
 2 => 'Mars',
 3 => 'April',
 4 => 'Maj',
 5 => 'Juni',
 6 => 'Juli',
 7 => 'Augusti',
 8 => 'September',
 9 => 'Oktober',
 10 => 'November',
 11 => 'December',
 ),
 'th' => 
 array (
 0 => 'มกราคม',
 1 => 'กุมภาพันธ์',
 2 => 'มีนาคม',
 3 => 'เมษายน',
 4 => 'พฤษภาคม',
 5 => 'มิถุนายน',
 6 => 'กรกฎาคม',
 7 => 'สิงหาคม',
 8 => 'กันยายน',
 9 => 'ตุลาคม',
 10 => 'พฤศจิกายน',
 11 => 'ธันวาคม',
 ),
 'tr' => 
 array (
 0 => 'Ocak',
 1 => 'Şubat',
 2 => 'Mart',
 3 => 'Nisan',
 4 => 'Mayis',
 5 => 'Haziran',
 6 => 'Temmuz',
 7 => 'Ağustos',
 8 => 'Eylül',
 9 => 'Ekim',
 10 => 'Kasım',
 11 => 'Aralık',
 ),
 'uk' => 
 array (
 0 => 'Січня',
 1 => 'Лютий',
 2 => 'Березень',
 3 => 'квітень',
 4 => 'травень',
 5 => 'червень',
 6 => 'липень',
 7 => 'серпень',
 8 => 'вересень',
 9 => 'жовтень',
 10 => 'листопад',
 11 => 'грудень',
 ),
 'vi' => 
 array (
 0 => 'tháng một',
 1 => 'tháng hai',
 2 => 'tháng ba',
 3 => 'tháng tư',
 4 => 'tháng năm',
 5 => 'tháng sáu',
 6 => 'tháng bảy',
 7 => 'tháng tám',
 8 => 'tháng chín',
 9 => 'tháng mười',
 10 => 'tháng mười một',
 11 => 'tháng mười hai',
 ),
 'zh' => 
 array (
 0 => '一月',
 1 => '二月',
 2 => '三月',
 3 => '四月',
 4 => '五月',
 5 => '六月',
 6 => '七月',
 7 => '八月',
 8 => '九月',
 9 => '十月',
 10 => '十一月',
 11 => '十二月',
 ),
);
private static $dot_separated_languages = array (
 0 => 'ar',
 1 => 'en',
 2 => 'es',
 3 => 'ms',
 4 => 'ga',
 5 => 'hi',
 6 => 'iw',
 7 => 'jp',
 8 => 'ko',
 9 => 'mi',
 10 => 'mt',
 11 => 'ne',
 12 => 'si',
 13 => 'th',
 14 => 'tl',
 15 => 'ur',
 16 => 'zh',
);
public static $widget_date_format_locales = array (
 'en' => '%d %s ago|today|day|days|week|weeks|month|months|year|years',
 'af' => '%d %s gelede|vandag|dag|dae|week|weke|maand|maande|jaar|jaar',
 'ar' => '%d %s مضى|اليوم|يوم|أيام|أسبوع|أسابيع|شهر|أشهر|سنة|سنوات',
 'az' => '%d %s əvvəl|bu gün|gün|gün|həftə|həftə|ay|ay|il|il',
 'bg' => 'преди %d %s|днес|ден|дни|седмица|седмици|месец|месеца|година|години',
 'bn' => '%d %s আগে|আজ|দিন|দিন|সপ্তাহ|সপ্তাহ|মাস|মাস|বছর|বছর',
 'bs' => 'prije %d %s|danas|dan|dana|sedmicu|sedmica|mjesec|mjeseci|godinu|godina',
 'cs' => 'před %d %s|dnes|dnem|dny|týdnem|týdny|měsícem|měsíci|rokem|roky',
 'cy' => '%d %s yn ôl|heddiw|diwrnod|diwrnod|wythnos|wythnosau|mis|mis|flwyddyn|flynyddoedd',
 'da' => '%d %s siden|i dag|dag|dage|uge|uger|måned|måneder|år|år',
 'de' => 'vor %d %s|heute|tag|tagen|woche|wochen|monat|monaten|jahr|jahren',
 'el' => 'πριν από %d ημέρα|σήμερα|ημέρα|ημέρες|εβδομάδα|εβδομάδες|μήνα|μήνες|χρόνο|χρόνια',
 'es' => 'hace %d %s|hoy|día|días|semana|semanas|mes|meses|año|años',
 'et' => '%d %s tagasi|täna|päev|päeva|nädal|nädalat|kuu|kuud|aasta|aastat',
 'fa' => '%d %s قبل|امروز|روز|روز|هفته|هفته|ماه|ماه|سال|سال',
 'fi' => '%d %s sitten|tänään|päivä|päivää|viikko|viikkoa|kuukausi|kuukautta|vuosi|vuotta',
 'fr' => 'il y a %d %s|aujourd\'hui|jour|jours|semaine|semaines|mois|mois|année|ans',
 'gd' => '%d %s air ais|an diugh|latha|làithean|seachdain|seachdainean|mìos|mìosan|bliadhna|bliadhna',
 'gl' => 'hai %d %s|hoxe|día|días|semana|semanas|mes|meses|ano|anos',
 'he' => '%d לפני %s|היום|יום|ימים|שבוע|שבועות|חודש|חודשים|שנה|שנים',
 'hi' => '%d %s पहले|आज|दिन|दिन|सप्ताह|सप्ताह|महीने|महीने|वर्ष|वर्ष',
 'hr' => 'prije %d %s|danas|dan|dana|tjedan|tjedana|mjesec|mjeseci|godinu|godina',
 'hu' => '%d %s|ma|napja|napja|hete|hete|hónapja|hónapja|éve|éve',
 'hy' => '%d %s առաջ|այսօր|օր|օր|շաբաթ|շաբաթ|ամիս|ամիս|տարի|տարի',
 'id' => '%d %s lalu|hari ini|hari|hari yang|minggu|minggu yang|bulan|bulan yang|tahun|tahun yang',
 'is' => 'fyrir %d %s|í dag|degi|dögum|viku|vikum|mánuði|mánuðum|ári|árum',
 'it' => '%d %s fa|oggi|giorno|giorni|settimana|settimane|mese|mesi|anno|anni',
 'ja' => '%d %s 前|今日|日|日|週間|週間|か月|か月|年|年',
 'ka' => '%d %s წინ|დღეს|დღის|დღის|კვირის|კვირის|თვის|თვის|წლის|წლის',
 'kk' => '%d %s бұрын|бүгін|күн|күн|апта|апта|ай|ай|жыл|жыл',
 'ko' => '%d %s 전|오늘|일|일|주|주|월|월|년|년',
 'lt' => 'prieš %d %s|šiandien|dieną|dienų|savaitę|savaites|mėnesį|mėnesių|metų|metų',
 'mk' => 'пред %d %s|денес|ден|дена|недела|недели|месец|месеци|година|години',
 'ms' => '%d %s lalu|hari ini|hari|hari|minggu|minggu|bulan|bulan|tahun|tahun',
 'nl' => '%d %s geleden|vandaag|dag|dagen|week|weken|maand|maanden|jaar|jaar',
 'no' => '%d %s siden|i dag|dag|dager|uke|uker|måned|måneder|år|år',
 'pl' => '%d %s temu|dziś|dzień|dni|tydzień|tygodni|miesiąc|miesięcy|rok|lat',
 'pt' => '%d %s atrás|hoje|dia|dias|semana|semanas|mês|meses|ano|anos',
 'ro' => 'acum %d %s|astăzi|zi|zile|săptămână|săptămâni|lună|luni|an|ani',
 'ru' => '%d %s назад|сегодня|день|дней|неделю|недель|месяц|месяцев|год|лет',
 'sk' => 'pred %d %s|dnes|dňom|dňami|týždňom|týždňami|mesiacom|mesiacmi|rokom|rokmi',
 'sl' => 'pred %d %s|danes|dnevom|dnevi|tednom|tedni|mesecem|meseci|letom|leti',
 'sq' => '%d %s më parë|sot|ditë|ditë|javë|javë|muaj|muaj|vit|vit',
 'sr' => 'пре %d %s|данас|дан|дана|недељу|недеље|месец|месеци|године|година',
 'sv' => '%d %s sedan|i dag|dag|dagar|vecka|veckor|månad|månader|år|år',
 'th' => '%d %s ที่แล้ว|วันนี้|วัน|วัน|สัปดาห์|สัปดาห์|เดือน|เดือน|ปี|ปี',
 'tr' => '%d %s önce|bugün|gün|gün|hafta|hafta|ay|ay|yıl|yıl',
 'uk' => '%d %s тому|сьогодні|день|днів|тиждень|тижнів|місяць|місяців|рік|років',
 'vi' => '%d %s trước|hôm nay|ngày|ngày|tuần|tuần|tháng|tháng|năm|năm',
 'zh' => '%d %s 前|今天|天|天|周|周|个月|个月|年|年',
);
private static $page_urls = array (
 'facebook' => 'https://www.facebook.com/pg/%page_id%',
 'google' => 'https://www.google.com/maps/search/?api=1&query=Google&query_place_id=%page_id%',
 'tripadvisor' => 'https://www.tripadvisor.com/%page_id%',
 'yelp' => 'https://www.yelp.com/biz/%25page_id%25',
 'booking' => 'https://www.booking.com/hotel/%page_id%',
 'amazon' => 'https://www.amazon.%domain%/sp?seller=%page_id%',
 'arukereso' => 'https://www.arukereso.hu/stores/%page_id%/#velemenyek',
 'airbnb' => 'https://www.airbnb.com/rooms/%page_id%',
 'hotels' => 'https://hotels.com/%page_id%',
 'opentable' => 'https://www.opentable.com/%page_id%',
 'foursquare' => 'https://foursquare.com/v/%25page_id%25',
 'capterra' => 'https://www.capterra.%page_id%/reviews',
 'szallashu' => 'https://szallas.hu/%page_id%?#rating',
 'thumbtack' => 'https://www.thumbtack.com/%page_id%',
 'expedia' => 'https://www.expedia.com/%page_id%',
 'zillow' => 'https://www.zillow.com/profile/%page_id%/#reviews',
 'wordpressPlugin' => 'https://www.wordpress.org/plugins/%page_id%',
 'aliexpress' => 'https://www.aliexpress.com/store/%page_id%',
 'alibaba' => 'https://%page_id%.en.alibaba.com',
 'sourceForge' => 'https://sourceforge.net/software/product/%page_id%/',
 'ebay' => 'https://www.ebay.com/fdbk/feedback_profile/%page_id%',
);
public function getPageUrl()
{
if (!isset(self::$page_urls[ $this->shortname ])) {
return "";
}
$pageDetails = $this->getPageDetails();
if (!$pageDetails) {
return "";
}
$pageId = $pageDetails['id'];
$domain = "";
if ($this->shortname === 'amazon' || $this->shortname === 'arukereso') {
$tmp = explode('|', $pageId);
$domain = $tmp[0];
if (isset($tmp[1])) {
$pageId = $tmp[1];
}
else {
$domain = 'com';
}
}
$url = str_replace([ '%domain%', '%page_id%', '%25page_id%25' ], [ $domain, $pageId, $pageId ], self::$page_urls[ $this->shortname ]);
if ($this->shortname === 'airbnb') {
$url = str_replace('rooms/experiences/', 'experiences/', $url);
}
if ($this->shortname === 'amazon' && strpos($pageId, '/') !== false) {
$url = str_replace('sp?seller=', '', $url);
}
if ($this->shortname === 'google' && $this->getGoogleType($pageId) === 'shop') {
$url = 'https://customerreviews.google.com/v/merchant?q=' . $pageId;
}
return $url;
}
private function getGoogleType($pageId)
{
return preg_match('/&c=\w+&v=\d+/', $pageId) ? 'shop' : 'map';
}
public function getReviewPageUrl()
{
$pageDetails = $this->getPageDetails();
if (!$pageDetails) {
return "";
}
$pageId = $pageDetails['id'];
if ($this->getGoogleType($pageId) === 'shop') {
return 'https://customerreviews.google.com/v/merchant?q=' . $pageId;
}
else {
return 'http://search.google.com/local/reviews?placeid=' . $pageId;
}
}
public function getReviewWriteUrl()
{
$pageDetails = $this->getPageDetails();
if (!$pageDetails) {
return "";
}
$pageId = $pageDetails['id'];
if ($this->getGoogleType($pageId) === 'shop') {
return 'https://customerreviews.google.com/v/merchant?q=' . $pageId;
}
else {
return 'http://search.google.com/local/writereview?placeid=' . $pageId;
}
}
public function getReviewHtml($review)
{
$html = $review->text;
if ($review->text) {
$html = preg_replace('/\r\n|\r|\n/', "\n", html_entity_decode($review->text, ENT_HTML5 | ENT_QUOTES));
}
if (isset($review->highlight) && $review->highlight) {
$tmp = explode(',', $review->highlight);
$start = (int)$tmp[0];
$length = (int)$tmp[1];
$html = mb_substr($html, 0, $start) . '<mark class="ti-highlight">' . mb_substr($html, $start, $length) . '</mark>' . mb_substr($html, $start + $length, mb_strlen($html));
/* format <mark></mark> tags in other tags
 * like:
 * <strong><mark>...</strong>...</mark>....
 * to:
 * <strong><mark>...</mark></strong><mark>...</mark>....
 */
preg_match('/<mark class="ti-highlight">(.*)<\/mark>/Us', $html, $matches);
if (isset($matches[1])) {
$replaced_content = preg_replace('/(<\/?[^>]+>)/U', '</mark>$1<mark class="ti-highlight">', $matches[1]);
$html = str_replace($matches[0], '<mark class="ti-highlight">' . $replaced_content . '</mark>', $html);
}
}
return $html;
}
public function get_default_no_rating_text($styleId, $setId)
{
$value = in_array($styleId, [ 15, 19, 36, 38, 39, 44 ]) ? 1 : 0;
if ($setId && self::$widget_styles[ $setId ]['_vars']['dots'] === 'true') {
$value = 1;
}
return $value;
}
private $previewContent = null;
private $templateCache = null;
public function get_noreg_list_reviews($forcePlatform = null, $listAll = false, $defaultStyleId = 4, $defaultSetId = 'light-background', $onlyPreview = false, $defaultReviews = false, $forceDefaultReviews = false)
{
global $wpdb;
$pageDetails = $this->getPageDetails();
$styleId = (int)get_option($this->get_option_name('style-id'), 4);
$setId = get_option($this->get_option_name('scss-set'), 'light-background');
$content = get_option($this->get_option_name('review-content'));
$lang = get_option($this->get_option_name('lang'), 'en');
$dateformat = get_option($this->get_option_name('dateformat'), 'Y-m-d');
$noRatingText = get_option($this->get_option_name('no-rating-text'), $this->get_default_no_rating_text($styleId, $setId));
$verifiedIcon = get_option($this->get_option_name('verified-icon'), 0);
$showReviewersPhoto = get_option($this->get_option_name('show-reviewers-photo'), self::$widget_styles[ $setId ]['reviewer-photo'] ? 1 : 0);
$showLogos = get_option($this->get_option_name('show-logos'), self::$widget_styles[ $setId ]['hide-logos'] ? 0 : 1);
$showStars = get_option($this->get_option_name('show-stars'), self::$widget_styles[ $setId ]['hide-stars'] ? 0 : 1);
$footerFilterText = get_option($this->get_option_name('footer-filter-text'), 0);
$showHeaderButton = get_option($this->get_option_name('show-header-button'), 1);
$reviewsLoadMore = get_option($this->get_option_name('reviews-load-more'), 1);
$needToParse = true;
if ($onlyPreview) {
$content = false;
$styleId = $defaultStyleId;
$setId = $defaultSetId;
$showLogos = self::$widget_styles[ $setId ]['hide-logos'] ? 0 : 1;
$showStars = self::$widget_styles[ $setId ]['hide-stars'] ? 0 : 1;
$showReviewersPhoto = self::$widget_styles[ $setId ]['reviewer-photo'] ? 1 : 0;
if ($this->previewContent && $this->previewContent['id'] === $styleId) {
$content = $this->previewContent['content'];
}
$noRatingText = $this->get_default_no_rating_text($styleId, $setId);
$footerFilterText = 0;
}
$sqlRatingField = 'rating';
if ($this->is_ten_scale_rating_platform()) {
$sqlRatingField = 'ROUND(rating / 2, 0)';
}
$sql = 'SELECT *, rating as original_rating, '. $sqlRatingField .' as rating FROM `'. $this->get_tablename('reviews') .'` ';
$filter = get_option($this->get_option_name('filter'), $this->get_widget_default_filter());
if (!$listAll && $filter) {
if (count($filter['stars']) === 0) {
$sql .= 'WHERE 0 ';
}
else {
$sql .= 'WHERE ('. $sqlRatingField .' IN ('. implode(',', $filter['stars']) .')';
if (in_array(5, $filter['stars'])) {
$sql .= ' or rating IS NULL';
}
$sql .= ') ';
if (isset($filter['only-ratings']) && $filter['only-ratings']) {
$sql .= 'and text != "" ';
}
}
}
$sql .= 'ORDER BY date DESC';
$reviews = $wpdb->get_results($sql);
if ($defaultReviews && ($forceDefaultReviews || !count($reviews))) {
$lang = substr(get_locale(), 0, 2);
if (!isset(self::$widget_languages[ $lang ])) {
$lang = 'en';
}
if (!$pageDetails) {
$pageDetails = [];
}
if (!isset($pageDetails['avatar_url'])) {
$pageDetails['avatar_url'] = 'https://cdn.trustindex.io/companies/default_avatar.jpg';
}
$ratingNum = 10;
$pageDetails['rating_number'] = $ratingNum;
$scoreTmp = round((($ratingNum - 1) * 5 + 4) / ($ratingNum * 5) * 10, 1);
if ($this->is_ten_scale_rating_platform()) {
$pageDetails['rating_score'] = number_format($scoreTmp, 1);
}
else {
$pageDetails['rating_score'] = number_format($scoreTmp / 2, 1);
}
if (!isset($pageDetails['id'])) {
$pageDetails['id'] = '';
}
if (!isset($pageDetails['name'])) {
$pageDetails['name'] = get_bloginfo('name');
}
$reviews = $this->getRandomReviews($ratingNum);
}
if (!count($reviews)) {
$text = self::___('There are no reviews on your %s platform.', [ ucfirst($this->shortname) ]);
if ($this->is_review_download_in_progress()) {
$text = self::___('Your reviews are being downloaded.');
if (!in_array($this->shortname, [ 'facebook', 'google' ])) {
$text .= ' ' . self::___('This process should only take a few minutes.');
}
}
return $this->error_box_for_admins($text);
}
if (self::is_amp_active() && self::is_amp_enabled()) {
return $this->error_box_for_admins(self::___('Free plugin features are unavailable with AMP plugin.'));
}
$scriptName = 'trustindex-js';
if (!wp_script_is($scriptName, 'enqueued')) {
wp_enqueue_script($scriptName, 'https://cdn.trustindex.io/loader.js', [], false, true);
}
$scripts = wp_scripts();
if (isset($scripts->registered[ $scriptName ]) && !isset($scripts->registered[ $scriptName ]->extra['after'])) {
wp_add_inline_script($scriptName, '(function ti_init() {
if(typeof Trustindex == "undefined"){setTimeout(ti_init, 1985);return false;}
if(typeof Trustindex.pager_inited != "undefined"){return false;}
Trustindex.init_pager(document.querySelectorAll(".ti-widget"));
})();');
}
if ($content === false || empty($content) || (strpos($content, '<!-- R-LIST -->') === false && $needToParse)) {
if (!$this->templateCache) {
add_action('http_api_curl', function($handle) {
curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
}, 10);
$response = wp_remote_get("https://cdn.trustindex.io/widget-assets/template/$lang.json", [ 'timeout' => 30 ]);
if (is_wp_error($response)) {
echo $this->get_alertbox('error', '<br />' .$this->___('Could not download the template for the widget.<br />Please reload the page.<br />If the problem persists, please write an email to support@trustindex.io.') .'<br /><br />'. print_r($response, true));
die;
}
$this->templateCache = json_decode($response['body'], true);
}
$content = $this->templateCache[ $styleId ];
if (!$onlyPreview) {
update_option($this->get_option_name('review-content'), $content, false);
}
}
if ($needToParse) {
$content = $this->parse_noreg_list_reviews([
'content' => $content,
'reviews' => $reviews,
'page-details' => $pageDetails,
'style-id' => $styleId,
'set-id' => $setId,
'no-rating-text' => $noRatingText,
'dateformat' => $dateformat,
'language' => $lang,
'verified-icon' => $verifiedIcon,
'show-reviewers-photo' => $showReviewersPhoto,
'footer-filter-text' => $footerFilterText,
'show-header-button' => $showHeaderButton,
'reviews-load-more' => $reviewsLoadMore,
]);
$this->previewContent = [
'id' => $styleId,
'content' => $content
];
}
$content = preg_replace('/data-set[_-]id=[\'"][^\'"]*[\'"]/m', 'data-set-id="'. $setId .'"', $content);
$classAppends = [];
$widgetType = self::$widget_templates[ 'templates' ][ $styleId ]['type'];
if (!in_array($widgetType, [ 'button', 'badge' ]) && !$showLogos) {
$classAppends []= 'ti-no-logo';
}
if (!in_array($widgetType, [ 'button', 'badge' ]) && !$showStars) {
$classAppends []= 'ti-no-stars';
}
if (!$showReviewersPhoto) {
$classAppends []= 'ti-no-profile-img';
}
$freeCssClass = 'ti-' . substr($this->shortname, 0, 4);
if ($onlyPreview) {
wp_enqueue_style("trustindex-widget-css-". $this->shortname ."-". $styleId . "-". $setId, "https://cdn.trustindex.io/assets/widget-presetted-css/". $styleId ."-". $setId .".css");
}
else {
$widgetCss = get_option($this->get_option_name('css-content'));
if (!$widgetCss) {
wp_enqueue_style("trustindex-widget-css-" . $this->shortname, "https://cdn.trustindex.io/widget-assets/css/". $styleId ."-blue.css");
}
else {
$classAppends []= $freeCssClass;
}
}
if ($classAppends) {
$content = str_replace('" data-layout-id=', ' '. implode(' ', $classAppends) .'" data-layout-id=', $content);
}
if ($dateformat === 'modern') {
$content = preg_replace('/class="(ti-widget[^\'"]*)" data-layout-id=/', 'class="$1" data-time-locale="'. self::$widget_date_format_locales[ $lang ] .'" data-layout-id=', $content);
}
if (!$onlyPreview) {
if (!wp_style_is('ti-widget-css-' . $this->shortname, 'registered')) {
if (!get_option($this->get_option_name('load-css-inline'), 0)) {
if (class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
}
else {
return $this->error_box_for_admins(self::___('CSS file could not saved.'));
}
}
$content .= '<style type="text/css">'. $widgetCss .'</style>';
}
else {
wp_enqueue_style('ti-widget-css-' . $this->shortname);
}
}
return $content;
}
public function parse_noreg_list_reviews($array = [])
{
$widgetType = self::$widget_templates[ 'templates' ][ $array['style-id'] ]['type'];
preg_match('/<!-- R-LIST -->(.*)<!-- R-LIST -->/', $array['content'], $matches);
if (isset($matches[1])) {
$reviewContent = "";
if ($array['reviews'] && count($array['reviews'])) {
foreach ($array['reviews'] as $r) {
$customAttributes = 'data-empty="'. (empty($r->text) ? 1 : 0) .'"';
$date = "&nbsp;";
if ($r->date && $r->date !== '0000-00-00') {
if (in_array($array['dateformat'], [ 'hide', 'modern' ])) {
$date = '';
if ($array['dateformat'] === 'modern') {
$customAttributes .= ' data-time="'. strtotime($r->date) .'"';
}
}
else {
$date = str_replace(self::$widget_month_names['en'], self::$widget_month_names[ $array['language'] ], date($array['dateformat'], strtotime($r->date)));
}
}
$ratingContent = $this->get_rating_stars($r->rating);
if ($this->shortname === 'facebook' && in_array($r->rating, [ 1, 5 ])) {
if ($r->rating == 1) {
$ratingContent = self::$widget_recommendation_texts[ $array['language'] ]['negative'];
}
else {
$ratingContent = self::$widget_recommendation_texts[ $array['language'] ]['positive'];
}
$recommendationText = trim(str_replace([ 'NOT_RECOMMEND_ICON', 'RECOMMEND_ICON' ], '', $ratingContent));
$ratingContent = '<span class="ti-recommendation">'. str_replace(
[
'NOT_RECOMMEND_ICON',
'RECOMMEND_ICON',
' ' . $recommendationText,
$recommendationText . ' '
], [
'<span class="ti-recommendation-icon negative"></span>',
'<span class="ti-recommendation-icon positive"></span>',
'<span class="ti-recommendation-title">'. $recommendationText .'</span>',
'<span class="ti-recommendation-title">'. $recommendationText .'</span>'
],
$ratingContent
) .'</span>';
$ratingContent .= '<span class="ti-dummy-stars">';
for ($si = 1; $si <= 5; $si++) {
$ratingContent .= '<span class="ti-star '. ($si == 1 || $r->rating == 5 ? 'f' : 'e') .'"></span>';
}
$ratingContent .= '</span>';
}
else if($this->shortname === 'ebay' && in_array($r->rating, [ 1, 3, 5 ])) {
if ($r->rating == 1) {
$polarity = 'negative';
}
else if($r->rating == 3) {
$polarity = 'neutral';
}
else {
$polarity = 'positive';
}
$ratingContent = '<span class="ti-polarity"><span class="ti-polarity-icon ' . $polarity . '"></span></span>';
}
else if($this->is_ten_scale_rating_platform()) {
$ratingContent = '<div class="ti-rating-box">'. $this->formatTenRating($r->original_rating, $array['language']) .'</div>';
}
$platformName = ucfirst($this->getShortName());
if ($array['verified-icon'] && in_array($platformName, self::$verified_platforms)) {
if ($array['style-id'] == 21) {
$ratingContent .= '</div><div class="ti-logo-text"><span class="ti-verified-review"><span class="ti-verified-tooltip">'. self::$widget_verified_texts[ $array['language'] ] .'</span></span><span class="ti-logo-title">Trustindex</span></div><div>';
}
else {
$ratingContent .= '<span class="ti-verified-review"><span class="ti-verified-tooltip">'. self::$widget_verified_texts[ $array['language'] ] .'</span></span>';
}
}
if($platformName === 'Szallashu') {
$tmp = explode('/', $array['page-details']['id']);
$platformName .= '" data-domain="' . $tmp[0];
}
if (!$array['show-reviewers-photo']) {
$matches[1] = str_replace('<div class="ti-profile-img"> <img src="%reviewer_photo%" alt="%reviewer_name%" /> </div>', '', $matches[1]);
}
$reviewContent .= str_replace(
[
'%platform%',
'%reviewer_photo%',
'%reviewer_name%',
'%created_at%',
'%text%',
'<span class="ti-star f"></span><span class="ti-star f"></span><span class="ti-star f"></span><span class="ti-star f"></span><span class="ti-star f"></span>',
'%rating_score%',
'class="ti-review-item'
],
[
$platformName,
$r->user_photo,
$r->user,
$date,
$this->getReviewHtml($r),
$ratingContent,
round($r->original_rating),
$customAttributes . ' class="ti-review-item'
],
$matches[1]
);
$reviewContent = str_replace('<div></div>', '', $reviewContent);
}
}
$array['content'] = str_replace($matches[0], $reviewContent, $array['content']);
}
$ratingCount = $array['page-details']['rating_number'];
$ratingScore = $array['page-details']['rating_score'];
if (empty($ratingCount)) {
$ratingCount = count($array['reviews']);
}
if (empty($ratingScore)) {
$ratingSum = 0.0;
foreach ($array['reviews'] as $review) {
$ratingSum += (float)$review->rating;
}
$c = count($array['reviews']);
$ratingScore = $c ? $ratingSum / $c : 0;
}
$ratingText = $this->get_rating_text($ratingScore, $array['language']);
$ratingTextUcfirst = ucfirst(strtolower($ratingText));
if (function_exists('mb_strtolower')) {
$ratingTextUcfirst = mb_substr($ratingText, 0, 1, 'UTF-8') . mb_strtolower(mb_substr($ratingText, 1, null, 'UTF-8'));
}
$array['content'] = str_replace(
[
'%platform%',
'%site_name%',
"RATING_NUMBER",
"RATING_SCORE",
"RATING_SCALE",
"RATING_TEXT",
"Rating_Text",
"PLATFORM_URL_LOGO",
"PLATFORM_NAME",
'<span class="ti-star e"></span><span class="ti-star e"></span><span class="ti-star e"></span><span class="ti-star e"></span><span class="ti-star e"></span>',
'PLATFORM_SMALL_LOGO',
],
[
ucfirst($this->getShortName()),
$array['page-details']['name'],
$ratingCount,
number_format((float)$ratingScore, 1),
$this->is_ten_scale_rating_platform() ? 10 : 5,
$ratingText,
$ratingTextUcfirst,
isset($array['page-details']['avatar_url']) ? $array['page-details']['avatar_url'] : null,
$this->get_platform_name($this->getShortName(), $array['page-details']['id']),
$this->is_ten_scale_rating_platform() ? "<div class='ti-rating-box'>". $this->formatTenRating($ratingScore, $array['language']) ."</div>" : $this->get_rating_stars($ratingScore),
'<div class="ti-small-logo"><img src="'. $this->get_plugin_file_url('static/img/platform/logo.svg') . '" alt="'. ucfirst($this->getShortName()) .'"></div>',
],
$array['content']
);
if ($this->isDarkLogo($array['style-id'], $array['set-id'])) {
$array['content'] = str_replace('img/platform/logo', 'img/platform/logo-dark', $array['content']);
$array['content'] = str_replace('platform/'. ucfirst($this->getShortName()) .'/logo', 'platform/'. ucfirst($this->getShortName()) .'/logo-dark', $array['content']);
}
if ($this->is_ten_scale_rating_platform() && $array['style-id'] == 11) {
$array['content'] = str_replace('<span class="ti-rating">'. $ratingScore .'</span> ', '', $array['content']);
}
if ($this->shortname === 'szallashu' || $this->shortname === 'arukereso') {
$split = '/';
$replaceHu = false;
if ($this->shortname === 'arukereso') {
$split = '|';
$replaceHu = true;
}
$tmp = explode($split, $array['page-details']['id']);
$array['content'] = str_replace([ 'img/platform/logo.svg', 'img/platform/logo-dark.svg' ], [ 'img/platform/logo-'. $tmp[0] .'.svg', 'img/platform/logo-'. $tmp[0] .'-dark.svg' ], $array['content']);
$array['content'] = str_replace([ 'platform/'. ucfirst($this->getShortName()) .'/logo.svg', 'platform/'. ucfirst($this->getShortName()) .'/logo-dark.svg' ], [ 'platform/'. ucfirst($this->shortname) .'/logo-'. $tmp[0] .'.svg', 'platform/'. ucfirst($this->shortname) .'/logo-'. $tmp[0] .'-dark.svg' ], $array['content']);
if ($replaceHu) {
$array['content'] = str_replace('img/platform/logo-hu', 'img/platform/logo', $array['content']);
$array['content'] = str_replace('platform/'. ucfirst($this->getShortName()) .'/logo-hu', 'platform/'. ucfirst($this->getShortName()) .'/logo', $array['content']);
}
}
if (in_array($array['style-id'], [ 8, 10, 11, 12, 13, 20, 22, 24, 25, 26, 27, 28, 29, 35, 55, 56, 57, 58, 59, 60, 61, 62 ])) {
if (!$array['show-header-button']) {
$array['content'] = preg_replace('/<!-- HEADER-BUTTON-START.+HEADER-BUTTON-END -->/s', '', $array['content']);
}
$array['content'] = str_replace([ '<!-- HEADER-BUTTON-START', 'HEADER-BUTTON-END -->' ], '', $array['content']);
if ($this->shortname === 'google') {
$array['content'] = str_replace('%footer_link%', in_array($array['style-id'], [ 26, 59 ]) ? $this->getReviewWriteUrl() : $this->getReviewPageUrl(), $array['content']);
}
else {
$array['content'] = str_replace('%footer_link%', $this->getPageUrl(), $array['content']);
}
}
else {
$array['content'] = preg_replace('/<a href=[\'"]%footer_link%[\'"][^>]*>(.+)<\/a>/mU', '$1', $array['content']);
}
if (!$array['reviews-load-more']) {
$array['content'] = preg_replace('/<div class="ti-load-more-reviews-container"[^>]*>.+<\/div>\s*<\/div>/U', '', $array['content']);
}
if ($array['no-rating-text'] && in_array($array['style-id'], [ 4, 6, 7, 15, 16, 19, 31, 33, 36, 37, 38, 39, 44 ])) {
if (in_array($array['style-id'], [ 6, 7 ])) {
$array['content'] = preg_replace('/<div class="ti-footer">.*<\/div>/mU', '<div class="ti-footer"></div>', $array['content']);
}
else if(in_array($array['style-id'], [ 31, 33 ])) {
$array['content'] = preg_replace('/<div class="ti-header source-.*<\/div>\s?<div class="ti-reviews-container">/mU', '<div class="ti-reviews-container">', $array['content']);
}
else {
$array['content'] = preg_replace('/<div class="ti-rating-text">.*<\/div>/mU', '', $array['content']);
$array['content'] = preg_replace('/<div class="ti-footer">\s*<\/div>/m', '', $array['content']);
}
}
if ($array['footer-filter-text'] && (!in_array($widgetType, [ 'button', 'badge', 'floating' ]) || in_array($array['style-id'], [ 23, 30, 32, 53 ]))) {
$filterText = $this->get_footer_filter_text($array['language']);
if (!$array['no-rating-text'] && !in_array($array['style-id'], [ 5, 8, 9, 10, 13, 18, 23, 30, 31, 32, 33, 34, 53, 54 ])) {
$array['content'] = str_replace('</span><!-- FOOTER FILTER TEXT -->', ',</span><span class="nowrap"><!-- FOOTER FILTER TEXT --></span>', $array['content']);
$array['content'] = str_replace('<div class="ti-footer-filter-text"><!-- FOOTER FILTER TEXT --></div>', '', $array['content']);
$array['content'] = str_replace('<!-- FOOTER FILTER TEXT -->', function_exists('mb_strtolower') ? mb_strtolower($filterText) : strtolower($filterText), $array['content']);
}
else {
$array['content'] = str_replace('<!-- FOOTER FILTER TEXT -->', $filterText, $array['content']);
}
}
else {
$array['content'] = str_replace([ '<div class="ti-footer-filter-text"><!-- FOOTER FILTER TEXT --></div>', '<!-- FOOTER FILTER TEXT -->' ], '', $array['content']);
}
if (!in_array($array['style-id'], [ 53, 54 ])) {
preg_match('/src="([^"]+logo[^\.]*\.svg)"/m', $array['content'], $matches);
if (isset($matches[1]) && !empty($matches[1])) {
$array['content'] = str_replace($matches[0], $matches[0] . ' width="150" height="25"', $array['content']);
$array['content'] = preg_replace('/width="([\d%]+)" height="([\d%]+)"( alt="[^"]+")? width="([\d%]+)" height="([\d%]+)"/', 'width="$1" height="$2"$3', $array['content']);
}
}
return $array['content'];
}
public function get_footer_filter_text($lang = 'en')
{
$filter = get_option($this->get_option_name('filter'), $this->get_widget_default_filter());
$langExists = self::$widget_footer_filter_texts && isset(self::$widget_footer_filter_texts[ $lang ]);
$text = $langExists ? self::$widget_footer_filter_texts[ $lang ]['latest'] : 'Showing our latest reviews';
if (isset($filter['stars']) && count($filter['stars']) < 4) {
sort($filter['stars']);
$start = $filter['stars'][0];
$end = array_pop($filter['stars']);
if ($start == $end) {
$replace = $start;
}
else {
$replace = $start .'-'. $end;
}
$text = str_replace('RATING_STAR_FILTER', $replace, $langExists ? self::$widget_footer_filter_texts[ $lang ]['star'] : 'Showing only RATING_STAR_FILTER star reviews');
}
return $text;
}
public function isDarkLogo($layoutId, $colorSchema)
{
if (in_array($layoutId, [ 5, 9, 31, 34, 33 ])) {
return substr($colorSchema, 0, 5) === 'dark-';
}
switch ($colorSchema) {
case 'light-contrast':
case 'light-contrast-large':
case 'light-contrast-large-blue':
case 'dark-background':
case 'dark-border':
return true;
}
return false;
}
public function get_platform_name($type, $id = "")
{
$text = ucfirst($type);
if ($text === 'Szallashu') {
$domains = [
'cz' => 'Hotely.cz',
'hu' => 'Szallas.hu',
'ro' => 'Hotelguru.ro',
'com' => 'Revngo.com',
'pl' => 'Noclegi.pl'
];
$tmp = explode('/', $id);
if (isset($domains[ $tmp[0] ])) {
$text = $domains[ $tmp[0] ];
}
}
else if ($text === 'Arukereso') {
$domains = [
'hu' => 'Árukereső.hu',
'bg' => 'Pazaruvaj.com',
'ro' => 'Compari.ro'
];
$tmp = explode('|', $id);
if (isset($domains[ $tmp[0] ])) {
$text = $domains[ $tmp[0] ];
}
}
else if($text === 'WordpressPlugin') {
$text = 'Wordpress Plugin';
}
return $text;
}
public function get_rating_text($rating, $lang = "en")
{
$texts = self::$widget_rating_texts[ $lang ];
$rating = round($rating);
if ($rating < 1) {
$rating = 1;
}
else if($rating > 5) {
$rating = 5;
}
if (function_exists('mb_strtoupper')) {
return mb_strtoupper($texts[ $rating - 1 ]);
}
else {
return strtoupper($texts[ $rating - 1 ]);
}
}
public function get_widget_default_filter()
{
global $wpdb;
$onlyRatingsDefault = false;
if ($this->is_noreg_linked()) {
$onlyRatingsDefault = (float)$wpdb->get_var('SELECT COUNT(`id`) FROM `'. $this->get_tablename('reviews') .'` WHERE `text` != ""') >= 3;
}
return [
'stars' => [1, 2, 3, 4, 5],
'only-ratings' => $onlyRatingsDefault
];
}
public function get_rating_stars($ratingScore)
{
$text = "";
if (!is_numeric($ratingScore)) {
return $text;
}
for ($si = 1; $si <= $ratingScore; $si++) {
$text .= '<span class="ti-star f"></span>';
}
$fractional = $ratingScore - floor($ratingScore);
if(0.25 <= $fractional) {
if ($fractional < 0.75) {
$text .= '<span class="ti-star h"></span>';
}
else {
$text .= '<span class="ti-star f"></span>';
}
$si++;
}
for (; $si <= 5; $si++) {
$text .= '<span class="ti-star e"></span>';
}
return $text;
}
private function getRandomReviews($count = 9)
{
$exampleReviews = null;
$jsonFile = plugin_dir_path($this->plugin_file_path) . 'static' . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . 'example-reviews.json';
if (file_exists($jsonFile)) {
$exampleReviews = json_decode(file_get_contents($jsonFile), true);
if (is_array($exampleReviews)) {
foreach ($exampleReviews as $i => $tmp) {
$exampleReviews[ $i ]['image'] = $this->get_plugin_file_url('static/img/'. $tmp['image']);
}
}
}
$reviews = [];
foreach ($exampleReviews as $i => $exampleReview) {
if ($i >= $count) {
break;
}
$r = new stdClass();
$r->id = $i;
$r->user = $exampleReview['name'];
$r->user_photo = $exampleReview['image'];
$r->text = $exampleReview['text'];
$r->original_rating = $i == max(0, $count-2) ? 4 : 5;
$r->rating = $r->original_rating;
$r->highlight = null;
$r->date = date('Y-m-d', strtotime('-'. ($i * 2) .' days'));
if ($this->is_ten_scale_rating_platform()) {
$r->original_rating = number_format($i == max(0, $count-2) ? 8 : 10, 1);
$r->rating = round($r->original_rating / 2);
}
$reviews[] = $r;
$i++;
}
return $reviews;
}
public function get_plugin_current_version()
{
add_action('http_api_curl', function($handle) {
curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
}, 10);
$response = wp_remote_get('https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]='. $this->get_plugin_slug());
$json = json_decode($response['body'], true);
if (!$json || !isset($json['version'])) {
return false;
}
return $json['version'];
}


public function post_request($url, $args)
{
$response = wp_remote_post($url, $args);
if (is_wp_error($response)) {
echo $this->get_alertbox('error', '<br />Error with wp_remote_post, error message: <br /><b>'. $response->get_error_message() .'</b>');
die;
}
return wp_remote_retrieve_body($response);
}


public function is_trustindex_connected()
{
return get_option($this->get_option_name("subscription-id"));
}
public function get_trustindex_widget_number()
{
$widgets = $this->get_trustindex_widgets();
$number = 0;
foreach ($widgets as $wc) {
$number += count($wc['widgets']);
}
return $number;
}
public function get_trustindex_widgets()
{
$widgets = array();
$trustindexSubscriptionId = $this->is_trustindex_connected();
if ($trustindexSubscriptionId) {
$response = wp_remote_get("https://admin.trustindex.io/" . "api/getWidgets?subscription_id=" . $trustindexSubscriptionId);
if ($response && !is_wp_error($response)) {
$widgets = json_decode($response['body'], true);
}
}
return $widgets;
}
public function connect_trustindex_api($postData, $mode = 'new')
{
$url = "https://admin.trustindex.io/" . "api/connectApi";
$postData['wp_info'] = $this->get_wp_details();
$serverOutput = $this->post_request($url, [
'body' => $postData,
'timeout' => '5',
'redirection' => '5',
'blocking' => true
]);
if ($serverOutput[0] !== '[' && $serverOutput[0] !== '{') {
$serverOutput = substr($serverOutput, strpos($serverOutput, '('));
$serverOutput = trim($serverOutput,'();');
}
$serverOutput = json_decode($serverOutput, true);
if ($serverOutput['success']) {
update_option( $this->get_option_name("subscription-id"), $serverOutput["subscription_id"]);
$GLOBALS['wp_object_cache']->delete( $this->get_option_name('subscription-id'), 'options' );
}
return $serverOutput;
}


public function register_tinymce_features()
{
if (!has_filter('mce_external_plugins', 'add_tinymce_buttons')) {
add_filter('mce_external_plugins', [ $this, 'add_tinymce_buttons' ]);
add_filter('mce_buttons', [ $this, 'register_tinymce_buttons' ]);
}
}
public function add_tinymce_buttons($pluginArray)
{
$pluginName = 'trustindex';
if (!isset($pluginArray[ $pluginName ])) {
$pluginArray[ $pluginName ] = $this->get_plugin_file_url('static/js/admin-editor.js');
}
wp_localize_script('jquery', 'ajax_object', [ 'ajax_url' => admin_url( 'admin-ajax.php' ) ]);
return $pluginArray;
}
public function register_tinymce_buttons($buttons)
{
$buttonName = 'trustindex';
if (!in_array($buttonName, $buttons)) {
$buttons []= $buttonName;
}
return $buttons;
}


public function list_trustindex_widgets_ajax()
{
$ti_widgets = $this->get_trustindex_widgets();
if ($this->is_trustindex_connected()): ?>
<?php if ($ti_widgets): ?>
<h2><?php echo self::___('Your saved widgets'); ?></h2>
<?php foreach ($ti_widgets as $wc): ?>
<p><strong><?php echo esc_html($wc['name']); ?>:</strong></p>
<p>
<?php foreach ($wc['widgets'] as $w): ?>
<a href="#" class="btn-copy-widget-id" data-ti-id="<?php echo esc_attr($w['id']); ?>">
<span class="dashicons dashicons-admin-post"></span>
<?php echo esc_html($w['name']); ?>
</a><br />
<?php endforeach; ?>
</p>
<?php endforeach; ?>
<?php else: ?>
<?php echo self::get_alertbox("warning",
self::___("You have no widget saved!") . " "
. "<a target='_blank' href='" . "https://admin.trustindex.io/" . "widget'>". self::___("Let's go, create amazing widgets for free!")."</a>"
); ?>
<?php endif; ?>
<?php else: ?>
<?php echo self::get_alertbox("warning",
self::___("You have not set up your Trustindex account yet!") . " "
. self::___("Go to <a href='%s'>plugin setup page</a> to complete the one-step setup guide and enjoy the full functionalization!", [ admin_url('admin.php?page='.$this->get_plugin_slug().'/settings.php&tab=setup_trustindex_join') ])
); ?>
<?php endif;
wp_die();
}
public function trustindex_add_scripts($hook)
{
if ($hook === 'widgets.php') {
wp_enqueue_script('trustindex_script', $this->get_plugin_file_url('static/js/admin-widget.js'));
wp_enqueue_style('trustindex_style', $this->get_plugin_file_url('static/css/admin-widget.css'));
}
else if ($hook === 'post.php') {
wp_enqueue_style('trustindex_editor_style', $this->get_plugin_file_url('static/css/admin-editor.css'));
}
else {
$tmp = explode(DIRECTORY_SEPARATOR, $this->plugin_file_path);
$pluginSlug = preg_replace('/\.php$/', '', array_pop($tmp));
$tmp = explode('/', $hook);
$currentSlug = array_shift($tmp);
if ($pluginSlug === $currentSlug) {
if (file_exists($this->get_plugin_dir() . 'static' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'admin-page-settings.css')) {
wp_enqueue_style('trustindex_settings_style_'. $this->shortname, $this->get_plugin_file_url('static/css/admin-page-settings.css'));
}
if (file_exists($this->get_plugin_dir() . 'static' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'admin-page-settings-common.js')) {
wp_enqueue_script('trustindex_settings_script_common_'. $this->shortname, $this->get_plugin_file_url('static/js/admin-page-settings-common.js'));
}
if(file_exists($this->get_plugin_dir() . 'static' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'admin-page-settings-connect.js')) {
wp_enqueue_script('trustindex_settings_script_connect_'. $this->shortname, $this->get_plugin_file_url('static/js/admin-page-settings-connect.js'));
}
if (in_array($this->shortname, [ 'google', 'facebook' ]) && file_exists($this->get_plugin_dir() . 'static' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'admin-page-settings.js')) {
wp_enqueue_script('trustindex_settings_script_'. $this->shortname, $this->get_plugin_file_url('static/js/admin-page-settings.js') );
}
}
}
wp_register_script('trustindex_admin_notification', $this->get_plugin_file_url('static/js/admin-notification.js') );
wp_enqueue_script('trustindex_admin_notification');
wp_enqueue_style('trustindex_admin_notification', $this->get_plugin_file_url('static/css/admin-notification.css'));
}


public function get_plugin_details($pluginSlug = null)
{
if (!$pluginSlug) {
$pluginSlug = $this->get_plugin_slug();
}
$pluginReturn = false;
$wpRepoPlugins = '';
$wpResponse = '';
$wpVersion = get_bloginfo('version');
if ($pluginSlug && $wp_version > 3.8) {
$args = [
'author' => 'Trustindex.io',
'fields' => [
'downloaded' => true,
'active_installs' => true,
'ratings' => true
]
];
$wpResponse = wp_remote_post(
'http://api.wordpress.org/plugins/info/1.0/',
[
'body' => [
'action' => 'query_plugins',
'request' => serialize((object) $args)
]
]
);
if (!is_wp_error($wpResponse)) {
$wpRepoResponse = unserialize(wp_remote_retrieve_body($wpResponse));
$wpRepoPlugins = $wpRepoResponse->plugins;
}
if ($wpRepoPlugins) {
foreach ($wpRepoPlugins as $pluginDetails) {
if ($pluginSlug === $pluginDetails->slug) {
$pluginReturn = $pluginDetails;
}
}
}
}
return $pluginReturn;
}
public function get_wp_details()
{
$data = [
'domain' => $_SERVER['SERVER_NAME'],
'current_theme' => [ 'slug' => get_template() ],
'themes' => [],
'plugins' => []
];
if (function_exists('wp_get_theme')) {
$theme = wp_get_theme();
}
else {
$theme = get_theme(get_current_theme());
}
$data['current_theme']['name'] = $theme['Name'];
$data['current_theme']['author'] = strip_tags($theme['Author']);
$data['current_theme']['version'] = $theme['Version'];
if (function_exists('wp_get_themes')) {
$themes = wp_get_themes();
}
else {
$themes = get_themes();
}
if ($themes) {
foreach ($themes as $slug => $theme) {
$data['themes'][] = [
'slug' => $theme['Template'],
'name' => $theme['Name'],
'author' => strip_tags($theme['Author']),
'version' => $theme['Version']
];
}
}
if (!function_exists('get_plugins')) {
require_once ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php';
}
$plugins = get_plugins();
if ($plugins) {
foreach ($plugins as $slug => $plugin) {
$data['plugins'][] = [
'slug' => explode('/', $slug)[0],
'name' => $plugin['Name'],
'author' => strip_tags($plugin['Author']),
'version' => $plugin['Version']
];
}
}
return json_encode($data);
}


public function is_ten_scale_rating_platform()
{
return in_array($this->shortname, [ 'booking', 'hotels', 'foursquare', 'szallashu', 'expedia' ]);
}
public function formatTenRating($rating, $language = null)
{
if (!$language) {
$language = get_option($this->get_option_name('lang'), 'en');
}
if ($rating == 10) {
$rating = '10';
}
if (!in_array($language, self::$dot_separated_languages)) {
$rating = str_replace('.', ',', $rating);
}
return $rating;
}
public static function is_amp_active()
{
if (!function_exists('get_plugins')) {
require_once ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php';
}
$amp_plugin_keys = [
'accelerated-mobile-pages/accelerated-moblie-pages.php',
'amp/amp.php'
];
foreach (get_plugins() as $key => $plugin) {
if (in_array($key, $amp_plugin_keys) && is_plugin_active($key)) {
return true;
}
}
return false;
}
public static function is_amp_enabled()
{
if (function_exists('amp_is_request')) {
return amp_is_request();
}
else if (function_exists('ampforwp_is_amp_endpoint')) {
return ampforwp_is_amp_endpoint();
}
else {
return false;
}
}
public function filter_filesystem_method($method)
{
if ($method !== 'direct' && !defined('FS_METHOD')) {
return 'direct';
}
return $method;
}
public function isJson($str) {
$json = json_decode($str);
return $json && $str !== $json;
}
public function register_block_editor()
{
if (!class_exists('WP_Block_Type_Registry')) {
return;
}
if (!WP_Block_Type_Registry::get_instance()->is_registered('trustindex/block-selector')) {
wp_register_script('trustindex-block-editor', $this->get_plugin_file_url('static/block-editor/block-editor.js'), [ 'wp-blocks', 'wp-editor' ], true);
register_block_type('trustindex/block-selector', [ 'editor_script' => 'trustindex-block-editor' ]);
}
}
function is_widget_setted_up()
{
$result = [];
$activePlugins = get_option('active_plugins');
$platforms = $this->get_platforms();
foreach ($this->get_plugin_slugs() as $index => $slug) {
if (in_array($slug .'/'. $slug .'.php', $activePlugins)) {
$activePluginSlug = $slug;
$result[ $platforms[ $index ] ] = get_option('trustindex-'. $platforms[ $index ] .'-widget-setted-up', 0);
}
}
return [
'result' => $result,
'setup_url' => admin_url('admin.php?page='. $activePluginSlug .'/settings.php&tab=setup_trustindex_join')
];
}
function init_restapi()
{
register_rest_route('trustindex/v1', '/get-widgets', [
'methods' => 'GET',
'callback' => [ $this, 'get_trustindex_widgets' ],
'permission_callback' => '__return_true'
]);
register_rest_route('trustindex/v1', '/setup-complete', [
'methods' => 'GET',
'callback' => [ $this, 'is_widget_setted_up' ],
'permission_callback' => '__return_true'
]);
}


public function get_tablename($name = "")
{
global $wpdb;
return $wpdb->prefix . 'trustindex_' . $this->shortname . '_' . $name;
}
public function is_table_exists($name = "")
{
global $wpdb;
$tableName = $this->get_tablename($name);
return ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") == $tableName);
}
public function get_noreg_tablename($forcePlatform = null)
{
global $wpdb;
$forcePlatform = $forcePlatform ? $forcePlatform : $this->shortname;
return $wpdb->prefix .'trustindex_'. $forcePlatform .'_reviews';
}
public function is_noreg_table_exists($forcePlatform = null)
{
global $wpdb;
$dbtable = $this->get_noreg_tablename($forcePlatform);
return ($wpdb->get_var("SHOW TABLES LIKE '$dbtable'") == $dbtable);
}
}
?>