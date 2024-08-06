<?php
defined('ABSPATH') || exit;
class TrustindexGutenbergPlugin
{

public static $instance = null;

function init()
{
add_action('enqueue_block_editor_assets', [ $this, 'gutenberg_enqueue_block_editor_assets' ]);
add_action('init', [ $this, 'register_block' ]);
}

function gutenberg_enqueue_block_editor_assets()
{
wp_enqueue_script(
'gutenberg-ti',
plugins_url('block-editor.js', __FILE__),
[
'wp-api-fetch',
'wp-components',
'wp-compose',
'wp-blocks',
'wp-element',
'wp-i18n'
],
filemtime(plugin_dir_path(__FILE__) . 'block-editor.js')
);
}
function register_block()
{
register_block_type('trustindex/block-selector', [
'render_callback' => [ $this, 'render_block' ],
'attributes' => [
'widget_id' => [ 'type' => 'string' ],
'trustindex_widgets' => [ 'type' => 'object' ],
'free_widgets' => [ 'type' => 'object' ],
'custom_id' => [ 'type' => 'boolean' ],
'setup_url' => [ 'type' => 'string' ]
]
]);
}

function render_block($attributes)
{
if ($this->is_gutenberg_page()) {
return "";
}
if (isset($attributes['free_widgets'][ $attributes['widget_id'] ])) {
$shortCode = 'no-registration=' . $attributes['widget_id'];
}
else {
$shortCode = 'data-widget-id="'. $attributes['widget_id'] .'"';
}
return '[trustindex '. $shortCode .']';
}
function is_gutenberg_page()
{
if (function_exists('is_gutenberg_page') && is_gutenberg_page()) {
return true;
}
if (function_exists('get_current_screen')) {
$currentScreen = get_current_screen();
if ($currentScreen && method_exists($currentScreen, 'is_block_editor') && $currentScreen->is_block_editor()) {
return true;
}
}
return false;
}

public static function instance()
{
if (is_null(self::$instance)) {
self::$instance = new self();
self::$instance->init();
}
return self::$instance;
}

public function __clone()
{
_doing_it_wrong(__FUNCTION__, 'Cheating huh?', '1.0.0');
}

public function __wakeup()
{
_doing_it_wrong(__FUNCTION__, 'Cheating huh?', '1.0.0');
}
}
?>