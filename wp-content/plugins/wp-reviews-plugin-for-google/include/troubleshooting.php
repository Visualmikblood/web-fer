<?php
$dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $pluginManagerInstance->get_plugin_slug() .'.php';
$pluginData = get_plugin_data($dir);
$reviews = null;
if (method_exists($pluginManagerInstance, 'is_noreg_linked') && $pluginManagerInstance->is_table_exists('reviews')) {
$reviews = [];
if ($pluginManagerInstance->is_noreg_linked()) {
$reviews = $wpdb->get_results('SELECT * FROM `'. $pluginManagerInstance->get_tablename('reviews') .'` ORDER BY date DESC');
}
}
?>
<?php
$memoryLimit = 'N/A';
if(ini_get('memory_limit')) {
$memoryLimit = sanitize_text_field(ini_get('memory_limit'));
}
$uploadMax = 'N/A';
if (ini_get('upload_max_filesize')) {
$uploadMax = sanitize_text_field(ini_get('upload_max_filesize'));
}
$postMax = 'N/A';
if (ini_get('post_max_size')) {
$postMax = sanitize_text_field(ini_get('post_max_size'));
}
$maxExecute = 'N/A';
if (ini_get('max_execution_time')) {
$maxExecute = sanitize_text_field(ini_get('max_execution_time'));
}
$addCss = false;
if (in_array('css-content', $pluginManagerInstance->get_option_names())) {
$addCss = true;
}
?>
URL: <?php echo esc_url(get_option('siteurl')) ."\n"; ?>
MySQL Version: <?php echo esc_html($wpdb->db_version()) ."\n"; ?>
WP Table Prefix: <?php echo esc_html($wpdb->prefix) ."\n"; ?>
WP Version: <?php echo esc_html($wp_version) ."\n"; ?>
Server Name: <?php echo esc_html($_SERVER['SERVER_NAME']) ."\n"; ?>
Cookie Domain: <?php $cookieDomain = parse_url(strtolower(get_bloginfo('wpurl'))); echo esc_html($cookieDomain['host']) ."\n"; ?>
CURL Library Present: <?php echo (function_exists('curl_init') ? 'Yes' : 'No') ."\n"; ?>
<?php if (method_exists($pluginManagerInstance, 'getCssFile')): ?>CSS path: <?php echo esc_html($pluginManagerInstance->getCssFile()) ."\n"; ?><?php endif; ?>
PHP Info: <?php echo "\n\t"; ?>
Version: <?php echo esc_html(phpversion()) ."\n\t"; ?>
Memory Usage: <?php echo round(memory_get_usage() / 1024 / 1024, 2) . "MB\n\t"; ?>
Memory Limit: <?php echo esc_html($memoryLimit) . "\n\t"; ?>
Max Upload Size: <?php echo esc_html($uploadMax) . "\n\t"; ?>
Max Post Size: <?php echo esc_html($postMax) . "\n\t"; ?>
Allow URL fopen: <?php echo (ini_get('allow_url_fopen') ? 'On' : 'Off') . "\n\t"; ?>
Allow URL Include: <?php echo (ini_get('allow_url_include') ? 'On' : 'Off') . "\n\t"; ?>
Display Errors: <?php echo (ini_get('display_errors') ? 'On' : 'Off') . "\n\t"; ?>
Max Script Execution Time: <?php echo esc_html($maxExecute) . " seconds\n\t"; ?>
WP_HTTP_BLOCK_EXTERNAL: <?php echo (defined('WP_HTTP_BLOCK_EXTERNAL') ? var_export(WP_HTTP_BLOCK_EXTERNAL, true) : 'not defined') . "\n\t"; ?>
WP_ACCESSIBLE_HOSTS: <?php echo (defined('WP_ACCESSIBLE_HOSTS') ? WP_ACCESSIBLE_HOSTS : 'not defined') . "\n"; ?>
Plugin: <?php echo esc_html($pluginData['Name']) ."\n"; ?>
Plugin Version: <?php echo esc_html($pluginData['Version']) ."\n"; ?>
Options: <?php foreach($pluginManagerInstance->get_option_names() as $optName) {
if ($optName == 'css-content') {
continue;
}
$option = get_option($pluginManagerInstance->get_option_name( $optName ));
echo "\n\t". esc_html($optName) .': ';
if ($optName === 'page-details' || is_array($option)) {
if (isset($option['reviews'])) {
unset($option['reviews']);
}
echo esc_html(str_replace("\n", "\n\t\t", print_r($option, true)));
}
else if($optName === 'download-timestamp' && $option) {
echo date('Y-m-d H:i:s', esc_html($option));
}
else {
echo esc_html($option);
}
}
?>
<?php
if (!is_null($reviews)) {
echo "\n\nReviews: ". trim(esc_html(str_replace("\n", "\n\t", print_r($reviews, true))));
}
if ($addCss) {
echo "\n\nCSS: ". esc_html(get_option($pluginManagerInstance->get_option_name('css-content')));
}
echo "\n\n";
?>
Active Theme: <?php
if (!function_exists('wp_get_theme')) {
$theme = get_theme(get_current_theme());
echo esc_html($theme['Name'] . ' ' . $theme['Version']);
}
else {
$theme = wp_get_theme();
echo esc_html($theme->Name . ' ' . $theme->Version);
}
echo "\n"; ?>
Plugins: <?php foreach (get_plugins() as $key => $plugin) {
echo "\n\t". esc_html($plugin['Name'].' ('.$plugin['Version'] . (is_plugin_active($key) ? ' - active' : '') . ')');
} ?>