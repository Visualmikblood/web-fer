<?php
defined('ABSPATH') or die('No script kiddies please!');
if (isset($_POST['command']) && $_POST['command'] === 'send-feature-request') {
check_admin_referer('send-feature-request_' . $pluginManagerInstance->get_plugin_slug(), '_wpnonce_send_feature_request');
$name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : "";
$email = isset($_POST['email']) ? sanitize_email($_POST['email']) : "";
$description = isset($_POST['description']) ? wp_kses_post(stripslashes($_POST['description'])) : "";
$workaround = isset($_POST['workaround']) ? wp_kses_post(stripslashes($_POST['workaround'])) : "";
$attachments = isset($_POST['attachments']) ? wp_kses_post(stripslashes($_POST['attachments'])) : "";
if ($email && $description) {
$subject = 'Feature request from '. $pluginNameForEmails .' plugin';
$message = 'We received a feature request to the '. $pluginNameForEmails .' plugin from <strong>'. $name .' ('. $email .', url: '. get_option('siteurl') .')</strong>:<br /><br /><strong>'. $description .'</strong><br /><br />Current workaround: <br /><br /><strong>'. $workaround .'</strong>';
if ($attachments) {
$message .= '<br />Attached urls: <br />- '. str_replace("\n", '<br />- ', $attachments);
}
ob_start();
include $pluginManagerInstance->get_plugin_dir() . 'include' . DIRECTORY_SEPARATOR . 'troubleshooting.php';
$troubleshootingData = ob_get_clean();
$message .= '<br /><br />Troubleshooting:<br />'. nl2br(str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $troubleshootingData));
wp_mail('support@trustindex.io', $subject, $message, [ 'From: '. $email, 'Content-Type: text/html; charset=UTF-8' ]);
}
exit;
}
?>
<h1 class="ti-header-title"><?php echo $pluginManager::___('Feature request'); ?></h1>
<div class="ti-box ti-feature-request">
<div class="ti-box-inner">
<div class="ti-box-header"><?php echo $pluginManager::___('Missing a feature?'); ?></div>
<p>
<?php echo $pluginManager::___('Anything you are missing in our product?'); ?><br />
<?php echo $pluginManager::___('Drop a message here to let us know!'); ?>
</p>
<form method="post" enctype="multipart/form-data">
<?php wp_nonce_field('send-feature-request_' . $pluginManagerInstance->get_plugin_slug(), '_wpnonce_send_feature_request' ); ?>
<input type="hidden" name="command" value="send-feature-request" />
<div class="ti-form-group">
<label><?php echo $pluginManager::___('Please describe the feature you need'); ?>*</label>
<textarea class="ti-form-control" name="description" rows="3" placeholder="<?php echo $pluginManager::___('The more detail you can share, the better.'); ?>"></textarea>
</div>
<div class="ti-form-group">
<label><?php echo $pluginManager::___('Attach images'); ?> (<?php echo $pluginManager::___('use %s for image share', [ '<a href="https://snipboard.io/" target="_blank">snipboard.io</a>' ]); ?>)</label>
<textarea class="ti-form-control" name="attachments" rows="3" placeholder="<?php echo $pluginManager::___('URL of images (each on a separate line)'); ?>"></textarea>
</div>
<div class="ti-form-group">
<label><?php echo $pluginManager::___('Please describe your current workaround'); ?></label>
<textarea class="ti-form-control" name="workaround" rows="3" placeholder="<?php echo $pluginManager::___('If you have one - otherwise leave it blank.'); ?>"></textarea>
</div>
<div class="ti-form-group">
<label><?php echo $pluginManager::___('Your name'); ?></label>
<input type="text" class="ti-form-control" name="name" placeholder="<?php echo $pluginManager::___('The more detail you can share, the better.'); ?>" />
</div>
<div class="ti-form-group">
<label><?php echo $pluginManager::___('Your email address'); ?>*</label>
<input type="text" class="ti-form-control" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" />
</div>
<p><?php echo $pluginManager::___('Thanks for taking the time - we will get back to you as soon as possible to ask a few clarifying question or to give you an update.'); ?></p>
<div class="ti-box-footer">
<a href="#" class="btn-send-feature-request ti-btn ti-tooltip ti-toggle-tooltip">
<?php echo $pluginManager::___('Send feature request') ;?>
<span class="ti-tooltip-message">
<span style="color: #00ff00; margin-right: 2px">✓</span>
<?php echo $pluginManager::___('Feature request sent'); ?>
</span>
</a>
</div>
</form>
</div>
</div>