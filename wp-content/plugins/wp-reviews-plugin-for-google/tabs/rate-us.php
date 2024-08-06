<?php
defined('ABSPATH') or die('No script kiddies please!');
wp_enqueue_script('trustindex-js', 'https://cdn.trustindex.io/loader.js', [], false, true);
?>
<h1 class="ti-header-title"><?php echo $pluginManager::___('Rate Us'); ?></h1>
<div class="ti-box">
<div class="ti-box-header"><?php echo $pluginManager::___('Please help us by reviewing our Plugin'); ?></div>
<p>
<?php echo $pluginManager::___("We've spent a lot of time developing this software. If you use the free version, you can still support us by leaving a review!"); ?><br />
<?php echo $pluginManager::___('Thank you in advance!'); ?>
</p>
<a class="ti-btn" href="https://wordpress.org/support/plugin/<?php echo $pluginManagerInstance->get_plugin_slug(); ?>/reviews/?rate=5#new-post" target="_blank"><?php echo $pluginManager::___('Click here to rate us!'); ?></a>
<div class="ti-box-footer">
<div src='https://cdn.trustindex.io/loader.js?<?php echo '3ef6962888fb40403c525129f9'; ?>'></div>
</div>
</div>