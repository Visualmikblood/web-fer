<?php
defined('ABSPATH') or die('No script kiddies please!');
?>
<h1 class="ti-header-title"><?php echo $pluginManager::___('Get Reviews'); ?></h1>
<div class="ti-box">
<div class="ti-box-header"><?php echo $pluginManager::___('Want more %s reviews?', [ 'Google' ]); ?></div>
<?php if ($pluginManagerInstance->shortname === 'google'): ?>
<p><?php echo $pluginManager::___('Get 100+ REAL Google reviews, with only 3 minutes of work, without developer knowledge...'); ?></p>
<a href="https://wordpress.trustindex.io/collect-google-reviews/?source=wpcs-google" target="_blank" class="ti-btn"><?php echo $pluginManager::___('DOWNLOAD OUR FREE GUIDE'); ?></a>
<?php else: ?>
<p><?php echo $pluginManager::___('Get 100+ REAL Customer reviews, with only 3 minutes of work, without developer knowledge...'); ?></p>
<a href="https://wordpress.trustindex.io/collect-reviews/?source=wpcs-google" target="_blank" class="ti-btn"><?php echo $pluginManager::___('DOWNLOAD OUR FREE GUIDE'); ?></a>
<?php endif; ?>
</div>
<?php if (class_exists('Woocommerce')): ?>
<div class="ti-box">
<div class="ti-box-header"><?php echo $pluginManager::___('Collect reviews automatically for your WooCommerce shop'); ?></div>
<?php if (!class_exists('TrustindexCollectorPlugin')): ?>
<p><?php echo $pluginManager::___("Download our new <a href='%s' target='_blank'>%s</a> plugin and get features for free!", [ 'https://wordpress.org/plugins/customer-reviews-collector-for-woocommerce/', $pluginManager::___('Customer Reviews Collector for WooCommerce') ]); ?></p>
<?php endif; ?>
<ul class="ti-check-list">
<li><?php echo $pluginManager::___('Send unlimited review invitations for free'); ?></li>
<li><?php echo $pluginManager::___('E-mail templates are fully customizable'); ?></li>
<li><?php echo $pluginManager::___('Collect reviews on 100+ review platforms (Google, Facebook, Yelp, etc.)'); ?></li>
</ul>
<?php if (class_exists('TrustindexCollectorPlugin')): ?>
<a href="?page=customer-reviews-collector-for-woocommerce%2Fadmin.php&tab=settings" class="ti-btn">
<?php echo $pluginManager::___('Collect reviews automatically'); ?>
</a>
<?php else: ?>
<a href="https://wordpress.org/plugins/customer-reviews-collector-for-woocommerce/" target="_blank" class="ti-btn">
<?php echo $pluginManager::___('Download plugin'); ?>
</a>
<?php endif; ?>
</div>
<?php endif; ?>