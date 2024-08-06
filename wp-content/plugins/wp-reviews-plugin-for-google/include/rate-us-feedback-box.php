<div class="ti-box ti-rate-us-box">
<div class="ti-box-header"><?php echo $pluginManager::___("How's experience with Trustindex?"); ?></div>
<p><?php echo $pluginManager::___('Rate us by clicking on the stars'); ?></p>
<div class="ti-quick-rating" data-nonce="<?php echo wp_create_nonce('ti-rate-us'); ?>">
<?php for ($i = 5; $i >= 1; $i--): ?><div class="ti-star-check" data-value="<?php echo $i; ?>"></div><?php endfor; ?>
</div>
</div>
<div class="ti-modal ti-rateus-modal" id="ti-rateus-modal-feedback">
<div class="ti-modal-dialog">
<div class="ti-modal-content">
<span class="ti-close-icon btn-modal-close"></span>
<div class="ti-modal-body">
<div class="ti-rating-textbox">
<div class="ti-quick-rating">
<?php for ($i = 5; $i >= 1; $i--): ?><div class="ti-star-check" data-value="<?php echo $i; ?>"></div><?php endfor; ?>
<div class="clear"></div>
</div>
</div>
<div class="ti-rateus-title"><?php echo $pluginManager::___('Thanks for your feedback!<br />Let us know how we can improve.') ;?></div>
<input type="text" class="ti-form-control" placeholder="<?php echo $pluginManager::___('Contact e-mail') ;?>" value="<?php echo $current_user->user_email; ?>" />
<textarea class="ti-form-control" placeholder="<?php echo $pluginManager::___('Describe your experience') ;?>"></textarea>
</div>
<div class="ti-modal-footer">
<a href="#" class="ti-btn ti-btn-default btn-modal-close"><?php echo $pluginManager::___('Cancel') ;?></a>
<a href="#" data-nonce="<?php echo wp_create_nonce('ti-rate-us'); ?>" class="ti-btn btn-rateus-support"><?php echo $pluginManager::___('Contact our support') ;?></a>
</div>
</div>
</div>
</div>