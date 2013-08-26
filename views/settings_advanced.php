<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">
	
	#AuthSettingsBox ol li {
		margin-bottom: 25px;
	}
	#AuthSettingsBox ol li > small {
		margin-left: 4px;
	}

	#side-sortables .postbox input.text_input,
	#side-sortables .postbox select.select {
	    width: 50%;
	}
	#side-sortables .postbox label.text_label {
	    width: 45%;
	}
	#side-sortables .postbox p.desc {
	    margin-left: 5px;
	}

</style>

<div class="wrap wplister-page">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/hammer-32x32.png) no-repeat;" id="wpl-icon"><br /></div>
          
	<?php include_once( dirname(__FILE__).'/settings_tabs.php' ); ?>		
	<?php echo $wpl_message ?>

	<form method="post" id="settingsForm" action="<?php echo $wpl_form_action; ?>">

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">

			<div id="postbox-container-1" class="postbox-container">
				<div id="side-sortables" class="meta-box">


					<!-- first sidebox -->
					<div class="postbox" id="submitdiv">
						<!--<div title="Click to toggle" class="handlediv"><br></div>-->
						<h3><span><?php echo __('Update','wplister'); ?></span></h3>
						<div class="inside">

							<div id="submitpost" class="submitbox">

								<div id="misc-publishing-actions">
									<div class="misc-pub-section">
										<p><?php echo __('This page contains some advanced options for special use cases.','wplister') ?></p>
									</div>
								</div>

								<div id="major-publishing-actions">
									<div id="publishing-action">
										<input type="hidden" name="action" value="save_wplister_advanced_settings" >
										<input type="submit" value="<?php echo __('Save Settings','wplister'); ?>" id="save_settings" class="button-primary" name="save">
									</div>
									<div class="clear"></div>
								</div>

							</div>

						</div>
					</div>

					<?php if ( ( ! is_multisite() ) || ( is_main_site() ) ) : ?>
					<div class="postbox" id="UninstallSettingsBox">
						<h3 class="hndle"><span><?php echo __('Uninstall on deactivation','wplister') ?></span></h3>
						<div class="inside">

							<label for="wpl-option-uninstall" class="text_label"><?php echo __('Uninstall','wplister'); ?></label>
							<select id="wpl-option-uninstall" name="wpl_e2e_option_uninstall" class="required-entry select">
								<option value="0" <?php if ( $wpl_option_uninstall != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
								<option value="1" <?php if ( $wpl_option_uninstall == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Enable to completely remove listings, transactions and settings when deactivating the plugin.','wplister'); ?><br><br>
								<?php echo __('To remove your listing templates as well, please delete the folder <code>wp-content/uploads/wp-lister/templates/</code>.','wplister'); ?>
							</p>

						</div>
					</div>
					<?php endif; ?>

					<?php #include('profile/edit_sidebar.php') ?>
				</div>
			</div> <!-- #postbox-container-1 -->


			<!-- #postbox-container-2 -->
			<div id="postbox-container-2" class="postbox-container">
				<div class="meta-box-sortables ui-sortable">
					
					<div class="postbox" id="TemplateSettingsBox">
						<h3 class="hndle"><span><?php echo __('Listing Templates','wplister') ?></span></h3>
						<div class="inside">

							<label for="wpl-process_shortcodes" class="text_label">
								<?php echo __('Shortcode processing','wplister'); ?>
                                <?php wplister_tooltip('By default, WP-Lister runs your product description through the usual WordPress content filters which enabled you to use shortcodes in your product descriptions.<br>If a plugin causes trouble by adding unwanted HTML to your description on eBay, you should try setting this to "off".') ?>
							</label>
							<select id="wpl-process_shortcodes" name="wpl_e2e_process_shortcodes" class="required-entry select">
								<option value="off"     <?php if ( $wpl_process_shortcodes == 'off' ): ?>selected="selected"<?php endif; ?>><?php echo __('off','wplister'); ?></option>
								<option value="content" <?php if ( $wpl_process_shortcodes == 'content' ): ?>selected="selected"<?php endif; ?>><?php echo __('only in product description','wplister'); ?></option>
								<option value="full"    <?php if ( $wpl_process_shortcodes == 'full' ): ?>selected="selected"<?php endif; ?>><?php echo __('in description and listing template','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Enable this if you want to use WordPress shortcodes in your product description or your listing template.','wplister'); ?><br>
							</p>

							<label for="wpl-remove_links" class="text_label">
								<?php echo __('Link handling','wplister'); ?>
                                <?php wplister_tooltip('Should WP-Lister replace links within the product description with plain text?') ?>
							</label>
							<select id="wpl-remove_links" name="wpl_e2e_remove_links" class="required-entry select">
								<option value="default"   <?php if ( $wpl_remove_links == 'default'   ): ?>selected="selected"<?php endif; ?>><?php echo __('remove all links from description','wplister'); ?></option>
								<option value="allow_all" <?php if ( $wpl_remove_links == 'allow_all' ): ?>selected="selected"<?php endif; ?>><?php echo __('allow all links','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('WP-Lister does remove links from product descriptions by default to avoid violating the eBay Links policy.','wplister'); ?>
								<?php echo __('Specifically you are not allowed to advertise products that you list on eBay by linking to their product pages on your site.','wplister'); ?>
								Read more about eBay's Link policy <a href="http://pages.ebay.com/help/policies/listing-links.html" target="_blank">here</a>
							</p>

							<label for="wpl-default_image_size" class="text_label">
								<?php echo __('Default image size','wplister'); ?>
                                <?php wplister_tooltip('Select the image size WP-Lister should use on eBay. It is recommended to set this to "full".') ?>
							</label>
							<select id="wpl-default_image_size" name="wpl_e2e_default_image_size" class="required-entry select">
								<option value="full"    <?php if ( $wpl_default_image_size == 'full'   ): ?>selected="selected"<?php endif; ?>><?php echo __('full','wplister'); ?></option>
								<option value="large"   <?php if ( $wpl_default_image_size == 'large'  ): ?>selected="selected"<?php endif; ?>><?php echo __('large','wplister'); ?></option>
							</select>

							<label for="wpl-wc2_gallery_fallback" class="text_label">
								<?php echo __('Product Gallery','wplister'); ?>
                                <?php wplister_tooltip('In order to get additional product images, WP-Lister first checks if there is a dedicated <i>Product Gallery</i> (WC 2.0+).<br>
                                						If there\'s not, it can use all images which were uploaded (attached) to the product - as it was the usual behaviour in WooCommerce 1.x.') ?>
							</label>
							<select id="wpl-wc2_gallery_fallback" name="wpl_e2e_wc2_gallery_fallback" class="required-entry select">
								<option value="attached" <?php if ( $wpl_wc2_gallery_fallback == 'attached' ): ?>selected="selected"<?php endif; ?>><?php echo __('use attached images if no Gallery found','wplister'); ?></option>
								<option value="none"     <?php if ( $wpl_wc2_gallery_fallback == 'none'     ): ?>selected="selected"<?php endif; ?>><?php echo __('no fallback','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('If you find unwanted images in your listings try disabling this option.','wplister'); ?>
							</p>

						</div>
					</div>

					<div class="postbox" id="OtherSettingsBox">
						<h3 class="hndle"><span><?php echo __('Misc options','wplister') ?></span></h3>
						<div class="inside">

							<label for="wpl-hide_dupe_msg" class="text_label">
								<?php echo __('Hide duplicates warning','wplister'); ?>
                                <?php wplister_tooltip('Technically, WP-Lister allows you to list the same product multiple times on eBay - in order to increase your visibility. However, this is not recommended as WP-Lister Pro would not be able to decrease the stock on eBay accordingly when the product is sold in WooCommerce.') ?>
							</label>
							<select id="wpl-hide_dupe_msg" name="wpl_e2e_hide_dupe_msg" class="required-entry select">
								<option value=""  <?php if ( $wpl_hide_dupe_msg == ''  ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?> (recommended)</option>
								<option value="1" <?php if ( $wpl_hide_dupe_msg == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes, I know what I am doing.','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('If you do not plan to use the inventory sync feature, you can safely list one product multiple times.','wplister'); ?>
							</p>

							<label for="wpl-option-foreign_transactions" class="text_label">
								<?php echo __('Handle foreign transactions','wplister') ?>
                                <?php wplister_tooltip('WP-Lister is designed to process a sale on eBay only if it "knows" the sold item (ie. the listing was created by WP-Lister itself). Disable this on your own risk.') ?>
							</label>
							<select id="wpl-option-foreign_transactions" name="wpl_e2e_option_foreign_transactions" class="required-entry select">
								<option value="0" <?php if ( $wpl_option_foreign_transactions != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Skip','wplister'); ?> (recommended)</option>
								<option value="1" <?php if ( $wpl_option_foreign_transactions == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Import','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Transactions for items which were not listed with WP-Lister are skipped by default.','wplister'); ?><br>
							</p>

							<label for="wpl-option-allow_backorders" class="text_label">
								<?php echo __('Ignore backorders','wplister') ?>
                                <?php wplister_tooltip('Since eBay relies on each item having a definitive quantity, allowing backorders for WooCommerce products can cause issues when the last item is sold. WP-Lister can force WooCommerce to mark an product as out of stock when the quantity reaches zero, even with backorders allowed.') ?>
							</label>
							<select id="wpl-option-allow_backorders" name="wpl_e2e_option_allow_backorders" class="required-entry select">
								<option value="0" <?php if ( $wpl_option_allow_backorders != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?> (recommended)</option>
								<option value="1" <?php if ( $wpl_option_allow_backorders == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Should WP-Lister mark a product as out of stock even when it has backorders enabled?','wplister'); ?><br>
							</p>

						</div>
					</div>



				<?php if ( ( is_multisite() ) && ( is_main_site() ) ) : ?>
				<p>
					<b>Warning:</b> Deactivating WP-Lister on a multisite network will remove all settings and data from all sites.
				</p>
				<?php endif; ?>


				</div> <!-- .meta-box-sortables -->
			</div> <!-- #postbox-container-1 -->



		</div> <!-- #post-body -->
		<br class="clear">
	</div> <!-- #poststuff -->

	</form>


</div>
