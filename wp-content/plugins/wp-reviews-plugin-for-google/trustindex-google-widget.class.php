<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
class TrustindexWidget_google extends WP_Widget {
private $widget_fields = [
'ti-widget-ID' => [
'default' => '',
'required' => true,
'placeholder' => 'eg.: 478dcc2136263f2b3a3726ff',
'name' => 'Trustindex Widget ID',
'help' => null,
'help-icon' => '<span class="dashicons dashicons-editor-help btn-insert-tooltip"></span>'
],
];
private $errors = array();
public function __construct()
{
parent::__construct(
'trustindex_google_widget',
'Review Widgets for Google',
[
'classname' => 'trustindex-widget',
'description' => 'Embed Google reviews fast and easily into your WordPress site. Increase SEO, trust and sales using Google reviews.'
]
);
}
function widget($args, $instance)
{
global $wpdb;
global $trustindex_pm_google;
if ($trustindex_pm_google->is_enabled()) {
extract($args);
echo $before_widget;
$wasError = false;
foreach ($this->widget_fields as $fname => $fparams) {
if ($fparams['required'] && (!isset($instance[ $fname ]) || $instance[ $fname ] == "")) {
$wasError = true;
break;
}
}
if (!$wasError && $instance['ti-widget-ID']) {
echo $trustindex_pm_google->get_trustindex_widget($instance['ti-widget-ID']);
}
else if ($trustindex_pm_google->is_noreg_linked()) {
echo $trustindex_pm_google->get_noreg_list_reviews();
}
else {
echo $trustindex_pm_google->error_box_for_admins(TrustindexPlugin_google::___("Please fill out <strong>all the required fields</strong> in the <a href='%s'>widget settings</a> page", [ admin_url('admin.php?page='.$trustindex_pm_google->get_plugin_slug().'/settings.php') ]));
}
echo $after_widget;
}
else {
}
}
function form($instance)
{
global $wp_version;
global $trustindex_pm_google;
$tiWidgets = $trustindex_pm_google->get_trustindex_widgets();
$selectedWidgetId = isset($instance['ti-widget-ID']) ? esc_attr($instance['ti-widget-ID']) : $this->widget_fields['ti-widget-ID']['default'];
?>
<div class="trustindex-widget-admin">
<?php if ($trustindex_pm_google->is_trustindex_connected()): ?>
<?php if ($tiWidgets): ?>
<h2><?php echo TrustindexPlugin_google::___('Your saved widgets'); ?></h2>
<?php foreach ($tiWidgets as $wc): ?>
<p><strong><?php echo esc_html($wc['name']); ?>:</strong></p>
<p>
<?php foreach ($wc['widgets'] as $w): ?>
<a href="#" class="btn-copy-widget-id <?php if ($selectedWidgetId === $w['id']): ?>text-danger<?php endif; ?>" data-ti-id="<?php echo esc_attr($w['id']); ?>">
<span class="dashicons <?php if ($selectedWidgetId === $w['id']): ?>dashicons-yes<?php else: ?>dashicons-admin-post<?php endif; ?>"></span>
<?php echo esc_html($w['name']); ?>
</a><br />
<?php endforeach; ?>
</p>
<?php endforeach; ?>
<?php else: ?>
<?php echo TrustindexPlugin_google::get_alertbox('warning',
TrustindexPlugin_google::___('You have no widget saved!') . ' '
. "<a target='_blank' href='" . "https://admin.trustindex.io/" . "widget'>". TrustindexPlugin_google::___("Let's go, create amazing widgets for free!")."</a>"
); ?>
<?php endif; ?>
<?php foreach ($this->widget_fields as $fname => $fparams): ?>
<div class="form-group">
<div class="col-sm-12">
<label class="<?php if (isset($this->errors[ $fname ])):?>text-danger<?php endif; ?>">
<?php echo TrustindexPlugin_google::___($fparams['name']); ?> <?php if ($fparams['required']): ?><strong class="text-danger">*</strong><?php endif; ?>
<?php if ($fparams['help-icon']): ?>
<?php echo TrustindexPlugin_google::___($fparams['help-icon']); ?>
<?php endif; ?>
</label>
<input
type="text"
placeholder="<?php echo TrustindexPlugin_google::___($fparams['placeholder']); ?>"
id="<?php echo $this->get_field_id($fname); ?>"
name="<?php echo $this->get_field_name($fname); ?>"
value="<?php echo isset($instance[ $fname ]) ? esc_attr($instance[ $fname ]) : $fparams['default']; ?>"
class="form-control"
<?php if ($fparams['required']): ?>required="required"<?php endif; ?>
/>
<?php if ($fparams['help']): ?>
<small class="text-muted"><?php echo TrustindexPlugin_google::___($fparams['help']); ?></small>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
<div class="help-block block-help-template">
<span class="dashicons dashicons-dismiss"></span>
<p>
Check our portal, <a href="<?php echo 'https://admin.trustindex.io/'; ?>widget" target="_blank">list your widgets</a> and find IDs in the first colums.
</p>
<img src="<?php echo $trustindex_pm_google->get_plugin_file_url('static/img/help-where-is-id.jpg'); ?>" alt="ID column here: <?php echo 'https://admin.trustindex.io/'; ?>widget" />
</div>
<?php else: ?>
<?php echo TrustindexPlugin_google::get_alertbox('warning',
TrustindexPlugin_google::___('You have not set up your Trustindex account yet!') . ' ' .
TrustindexPlugin_google::___('You can only list 10 reviews without it.') . '<br>'
. TrustindexPlugin_google::___("Go to <a href='%s'>plugin setup page</a> to complete the one-step setup guide and enjoy the full functionalization!", [ admin_url('admin.php?page='.$trustindex_pm_google->get_plugin_slug().'/settings.php&tab=setup_trustindex') ])
); ?>
<?php endif; ?>
</div>
<?php
}
}
?>