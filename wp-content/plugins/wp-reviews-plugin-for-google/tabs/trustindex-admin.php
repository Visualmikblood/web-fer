<?php
defined('ABSPATH') or die('No script kiddies please!');
$tiSuccess = "";
if (isset($_COOKIE['ti-success'])) {
$tiSuccess = sanitize_text_field($_COOKIE['ti-success']);
setcookie('ti-success', '', time() - 60, "/");
}
$tiError = null;
$tiCommand = isset($_POST['command']) ? sanitize_text_field($_POST['command']) : null;
if (!in_array($tiCommand, [ 'connect', 'disconnect' ])) {
$tiCommand = null;
}
if ($tiCommand === 'connect') {
check_admin_referer('connect-reg_' . $pluginManagerInstance->get_plugin_slug());
$sanitizedEmail = sanitize_email($_POST['email']);
$sanitizedPassword = stripslashes(sanitize_text_field(htmlentities($_POST['password'], ENT_QUOTES)));
if ($sanitizedEmail && $sanitizedPassword) {
$serverOutput = $pluginManagerInstance->connect_trustindex_api([
'signin' => [
'username' => $sanitizedEmail,
'password' => html_entity_decode($sanitizedPassword),
],
'callback' => bin2hex(openssl_random_pseudo_bytes(10))
], 'connect');
if ($serverOutput['success']) {
setcookie('ti-success', 'connected', time() + 60, '/');
header('Location: admin.php?page=' . sanitize_text_field($_GET['page']) . '&tab=' . sanitize_text_field($_GET['tab']));
exit;
}
else {
$tiError = $pluginManager::___('Wrong e-mail or password!');
}
}
else {
$tiError = $pluginManager::___('You must provide a password and a valid e-mail!');
}
}
else if ($tiCommand === 'disconnect') {
check_admin_referer('disconnect-reg_' . $pluginManagerInstance->get_plugin_slug());
delete_option($pluginManagerInstance->get_option_name('subscription-id'));
setcookie('ti-success', 'disconnected', time() + 60, '/');
header('Location: admin.php?page=' . sanitize_text_field($_GET['page']) . '&tab=' . sanitize_text_field($_GET['tab']));
exit;
}
$trustindexSubscriptionId = $pluginManagerInstance->is_trustindex_connected();
$widgetNumber = $pluginManagerInstance->get_trustindex_widget_number();
?>
<?php if (!$trustindexSubscriptionId): ?>
<h1 class="ti-header-title"><?php echo $pluginManager::___('Log In'); ?></h1>
<?php else: ?>
<h1 class="ti-header-title">Trustindex admin</h1>
<?php endif; ?>
<div class="ti-box">
<div class="ti-box-header"><?php echo $pluginManager::___('Connect your Trustindex account'); ?></div>
<?php if ($tiSuccess === 'connected'): ?>
<?php echo $pluginManager::get_noticebox('success', $pluginManager::___('Trustindex account successfully connected!')); ?>
<?php elseif ($tiSuccess === 'disconnected'): ?>
<?php echo $pluginManager::get_noticebox('success', $pluginManager::___('Trustindex account successfully disconnected!')); ?>
<?php endif; ?>
<?php if ($tiError): ?>
<?php echo $pluginManager::get_noticebox('error', $tiError); ?>
<?php endif; ?>
<?php if ($trustindexSubscriptionId): ?>
<?php
$tiWidgets = $pluginManagerInstance->get_trustindex_widgets();
$tiPackage = is_array($tiWidgets) && $tiWidgets && isset($tiWidgets[0]['package']) ? $tiWidgets[0]['package'] : null;
?>
<p>
<?php echo $pluginManager::___('Your %s is connected.', [ $pluginManager::___('Trustindex account') ]); ?><br />
- <?php echo $pluginManager::___('Your subscription ID:'); ?> <strong><?php echo esc_html($trustindexSubscriptionId); ?></strong><br />
<?php if ($tiPackage): ?>
- <?php echo $pluginManager::___('Your package:'); ?> <strong><?php echo esc_html($pluginManager::___($tiPackage)); ?></strong>
<?php endif; ?>
</p>
<?php if ($tiPackage === 'free'): ?>
<?php echo $pluginManager::get_noticebox('error', $pluginManager::___("Once the trial period has expired, the widgets will not appear. You can subscribe or switch back to the \"%s\" tab", [ $pluginManager::___('Free Widget Configurator') ])); ?>
<?php elseif ($tiPackage === 'trial'): ?>
<?php echo $pluginManager::get_noticebox('warning', $pluginManager::___("Once the trial period has expired, the widgets will not appear. You can subscribe or switch back to the \"%s\" tab", [ $pluginManager::___('Free Widget Configurator') ])); ?>
<?php endif; ?>
<form method="post" class="ti-mt-0" action="">
<input type="hidden" name="command" value="disconnect" />
<?php wp_nonce_field('disconnect-reg_' . $pluginManagerInstance->get_plugin_slug()); ?>
<button class="ti-btn ti-btn-loading-on-click ti-pull-right" type="submit"><?php echo $pluginManager::___('Disconnect'); ?></button>
<div class="clear"></div>
</form>
<?php else: ?>
<p><?php echo $pluginManager::___('You can connect your %s with your Trustindex account, and can display your widgets easier.', [ 'Widgets for Google Reviews' ]); ?></p>
<form id="form-connect" method="post" action="">
<input type="hidden" name="command" value="connect" />
<?php wp_nonce_field('connect-reg_' . $pluginManagerInstance->get_plugin_slug()); ?>
<div class="ti-form-group">
<label>E-mail</label>
<input type="email" placeholder="E-mail" name="email" class="ti-form-control" required="required" id="ti-reg-email2" value="<?php echo esc_attr($current_user->user_email); ?>" />
</div>
<div class="ti-form-group ti-mb-1">
<label><?php echo $pluginManager::___('Password'); ?></label>
<input type="password" placeholder="<?php echo $pluginManager::___('Password'); ?>" name="password" class="ti-form-control" required="required" id="ti-reg-password2" />
<span class="dashicons dashicons-visibility ti-toggle-password"></span>
</div>
<p class="ti-text-center">
<button type="submit" class="ti-btn ti-mb-1"><?php echo $pluginManager::___('CONNECT ACCOUNT');?></button>
<br />
<a class="ti-btn ti-btn-default" href="<?php echo 'https://admin.trustindex.io/'; ?>forgot-password" target="_blank"><?php echo $pluginManager::___('Have you forgotten your password?'); ?></a>
<a class="ti-btn ti-btn-default" href="https://www.trustindex.io/ti-redirect.php?a=sys&c=wp-google-4" target="_blank"><?php echo $pluginManager::___('Create a new Trustindex account');?></a>
</p>
</form>
<?php endif; ?>
</div>
<?php if ($trustindexSubscriptionId): ?>
<div class="ti-box">
<div class="ti-box-header"><?php echo $pluginManager::___('Manage your Trustindex account'); ?></div>
<a class="ti-btn" href="<?php echo 'https://admin.trustindex.io/'; ?>widget" target="_blank"><?php echo $pluginManager::___("Go to Trustindex's admin!"); ?></a>
</div>
<div class="ti-box">
<div class="ti-box-header"><?php echo $pluginManager::___('Insert your widget into your wordpress site using shortcode'); ?></div>
<?php if ($trustindexSubscriptionId): ?>
<?php if ($widgetNumber): ?>
<p><?php echo $pluginManager::___('You have got %d widgets saved in Trustindex admin.', [ $widgetNumber ]); ?></p>
<?php foreach ($tiWidgets as $wcIndex => $wc): ?>
<p class="ti-bold"><?php echo esc_html($wc['name']); ?>:</p>
<?php if ($wc['widgets']): ?>
<ul>
<?php foreach ($wc['widgets'] as $wiNum => $w): ?>
<li>
<?php echo esc_html($wiNum + 1); ?>.
<a href=".ti-w-<?php echo esc_attr($wcIndex .'-'. $wiNum); ?>" class="btn-toggle" data-ti-id="<?php echo esc_attr($w['id']); ?>"><?php echo esc_html($w['name']); ?></a>
<div style="display: none" class="ti-w-<?php echo esc_attr($wcIndex .'-'. $wiNum); ?>">
<code class="code-ti-w-<?php echo esc_attr($wcIndex .'-'. $wiNum); ?>">[<?php echo $pluginManagerInstance->get_shortcode_name(); ?> data-widget-id="<?php echo esc_attr($w['id']); ?>"]</code>
<a href=".code-ti-w-<?php echo esc_attr($wcIndex .'-'. $wiNum); ?>" class="btn-text btn-copy2clipboard"><?php echo $pluginManager::___('Copy to clipboard'); ?></a>
<br />
<br />
</div>
</li>
<?php endforeach; ?>
</ul>
<?php else: ?>
-
<?php endif; ?>
<?php endforeach; ?>
<?php else: ?>
<?php echo $pluginManager::get_noticebox('error', $pluginManager::___('You have no widgets saved!')); ?>
<?php endif; ?>
<a class="ti-btn" href="<?php echo 'https://admin.trustindex.io/'; ?>widget" target="_blank"><?php echo $pluginManager::___('Create more!'); ?></a>
<?php endif; ?>
</div>
<?php endif; ?>