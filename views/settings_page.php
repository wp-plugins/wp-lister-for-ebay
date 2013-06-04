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

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">

			<div id="postbox-container-1" class="postbox-container">
				<div id="side-sortables" class="meta-box">


					<!-- first sidebox -->
					<div class="postbox" id="submitdiv">
						<!--<div title="Click to toggle" class="handlediv"><br></div>-->
						<h3><span><?php echo __('Your Account','wplister'); ?></span></h3>
						<div class="inside">

							<div id="submitpost" class="submitbox">

								<div id="misc-publishing-actions">
									<div class="misc-pub-section">
									<?php if ( $wpl_ebay_token_userid ): ?>
										<p>
											<!-- <b><?php echo __('Account Details','wplister') ?></b> -->
											<table style="width:95%">
												<tr><td><?php echo __('User ID','wplister') . ':</td><td>' . $wpl_ebay_token_userid ?></td></tr>
												<tr><td><?php echo __('Status','wplister') . ':</td><td>' . $wpl_ebay_user->Status ?></td></tr>
												<tr><td><?php echo __('Score','wplister') . ':</td><td>' . $wpl_ebay_user->FeedbackScore ?></td></tr>
												<tr><td><?php echo __('Site','wplister') . ':</td><td>' . $wpl_ebay_user->Site ?></td></tr>
												<?php if ( $wpl_ebay_user->StoreOwner ) : ?>
												<tr><td><?php echo __('Store','wplister') . ':</td><td>' ?><a href="<?php echo $wpl_ebay_user->StoreURL ?>" target="_blank"><?php echo __('visit store','wplister') ?></a></td></tr>
												<?php endif; ?>
											</table>												
										</p>
									<?php elseif ( $wpl_text_ebay_token ): ?>
										<p><?php echo __('WP-Lister has been linked to your eBay account.','wplister') ?></p>
										<p><?php echo __('Please visit the Tools page and click on "Update user details".','wplister') ?></p>
									<?php else: ?>
										<p><?php echo __('WP-Lister is not linked to your eBay account yet.','wplister') ?></p>
									<?php endif; ?>
									</div>
								</div>

								<div id="major-publishing-actions">
									<?php if ( $wpl_ebay_token_userid ): ?>
										<div id="publishing-action" style="float:left">
										<form method="post" id="removeTokenForm" action="<?php echo $wpl_form_action; ?>">
											<?php wp_nonce_field( 'remove_token' ); ?>
											<input type="hidden" name="action" value="remove_token" >
											<input type="submit" value="<?php echo __('Change Account','wplister'); ?>" id="remove_token" class="button-secondary" name="remove_token">
										</form>
										</div>
									<?php elseif ( $wpl_text_ebay_token ): ?>
										<div id="publishing-action" style="float:left">
										<form method="post" id="removeTokenForm" action="<?php echo $wpl_form_action; ?>">
											<?php wp_nonce_field( 'remove_token' ); ?>
											<input type="hidden" name="action" value="remove_token" >
											<input type="submit" value="<?php echo __('Reset Account','wplister'); ?>" id="remove_token" class="button-secondary" name="remove_token">
										</form>
										</div>
									<?php endif; ?>
									<div id="publishing-action">
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

							<label for="wpl-option-uninstall" class="text_label"><?php echo __('Uninstall','wplister'); ?>:</label>
							<select id="wpl-option-uninstall" name="wpl_e2e_option_uninstall" title="Uninstall" class=" required-entry select">
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
					
				<?php if ( $wpl_text_ebay_token == '' ) : ?>
				
					<div class="postbox" id="AuthSettingsBox">
						<h3 class="hndle"><span><?php echo __('eBay authorization','wplister') ?></span></h3>
						<div class="inside">
							<p><strong><?php echo __('Follow these steps to link WP-Lister with your eBay account','wplister') ?></strong></p>

							<p>
								<ol>
									<li>
										<form id="frmSetEbaySite" method="post" action="<?php echo $wpl_form_action; ?>">
											<input type="hidden" name="action" value="save_ebay_site" >
											<select id="wpl-text-ebay_site_id" name="wpl_e2e_text_ebay_site_id" title="Site" class="required-entry select" style="width:auto;float: right">
												<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
												<?php foreach ($wpl_ebay_sites as $site_id => $site_name) : ?>
													<option value="<?php echo $site_id ?>" <?php if ( $wpl_text_ebay_site_id == $site_id ): ?>selected="selected"<?php endif; ?>><?php echo $site_name ?></option>					
												<?php endforeach; ?>
											</select>
											<?php echo __('Select the eBay site you want to list your items on:','wplister') ?>:
											<br>
											<small>
											If you want to change the site later, you will need to go through setup again. <br>
											</small>
										</form>
								</li>
									<li>
										<a style="float:right;" href="<?php echo $wpl_auth_url; ?>" class="button-primary" target="_blank">Connect with eBay</a>
										<?php echo __('Click "Connect with eBay" to sign in to eBay and grant access for WP-Lister','wplister') ?>:
										<br>
										<small>This will open the eBay Sign In page in a new window.</small><br>
										<small>Please sign in, grant access for WP-Lister and close the new window to come back here.</small>
									</li>
									<li>
										<form id="frmFetchToken" method="post" action="<?php echo $wpl_form_action; ?>">
											<input type="hidden" name="action" value="FetchToken" >
											<input  style="float:right;" type="submit" value="<?php echo __('Fetch eBay Token','wplister') ?>" name="submit" class="button-secondary">
											<?php echo __('After linking WP-Lister with your eBay account, click here to fetch your token','wplister') ?>:
											<br>
											<small>
											After retrieving your token, we will proceed with the first time set up. 
											</small>
										</form>
									</li>
								</ol>
							</p>

							<p style=""><small>
								You can view and revoke this authorization by visiting: <br>&raquo; My eBay &raquo; Account &raquo; Site Preferences  &raquo; General Preferences  &raquo; Third-party authorizations
							</small>
							</p>

						</div>
					</div>

				<?php else: // $wpl_text_ebay_token != ''  ?>

				<form method="post" id="settingsForm" action="<?php echo $wpl_form_action; ?>">
					<input type="hidden" name="action" value="save_wplister_settings" >

					<div class="postbox" id="ConnectionSettingsBox">
						<h3 class="hndle"><span><?php echo __('eBay settings','wplister') ?></span></h3>
						<div class="inside">

							<label for="wpl-text-ebay_site_id" class="text_label"><?php echo __('eBay site','wplister'); ?>:</label>
							<select id="wpl-text-ebay_site_id" name="wpl_e2e_text_ebay_site_id" title="Site" class=" required-entry select">
								<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
								<?php foreach ($wpl_ebay_sites as $site_id => $site_name) : ?>
									<option value="<?php echo $site_id ?>" <?php if ( $wpl_text_ebay_site_id == $site_id ): ?>selected="selected"<?php endif; ?>><?php echo $site_name ?></option>					
								<?php endforeach; ?>
							</select>

							<div id="wrap_enable_ebay_motors" style="<?php echo $wpl_text_ebay_site_id != 0 ? 'display:none' : '' ?>">
								<label for="wpl-option-enable_ebay_motors" class="text_label"><?php echo __('Include eBay Motors','wplister') ?></label>
								<select id="wpl-option-enable_ebay_motors" name="wpl_e2e_option_enable_ebay_motors" title="Handle stock" class=" required-entry select">
									<option value="1" <?php if ( $wpl_option_enable_ebay_motors == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
									<option value="0" <?php if ( $wpl_option_enable_ebay_motors != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
								</select>
							</div>

							<label for="wpl-text-paypal_email" class="text_label"><?php echo __('PayPal Email adress','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_text_paypal_email" id="wpl-text-paypal_email" value="<?php echo $wpl_text_paypal_email; ?>" class="text_input" />
							<p class="desc" style="display: none;">
								<?php echo __('To use PayPal you need to enter your PayPal adress.','wplister'); ?>
							</p>

						</div>
					</div>


					<div class="postbox" id="UpdateOptionBox"
						<?php if ( $wpl_text_ebay_token == '' ) echo 'style="display:none;"' ?>
						>
						<h3 class="hndle"><span><?php echo __('Automatic updates','wplister') ?></span></h3>
						<div class="inside">
							<p><?php echo __('Enable to update listings and transactions using WP-Cron.','wplister'); ?></p>

							<label for="wpl-option-cron_auctions" class="text_label"><?php echo __('Update transactions','wplister') ?></label>
							<select id="wpl-option-cron_auctions" name="wpl_e2e_option_cron_auctions" title="Updates" class=" required-entry select">
								<option value="" <?php if ( $wpl_option_cron_auctions == '' ): ?>selected="selected"<?php endif; ?>><?php echo __('manually','wplister') ?></option>
								<option value="hourly" <?php if ( $wpl_option_cron_auctions == 'hourly' ): ?>selected="selected"<?php endif; ?>><?php echo __('hourly','wplister') ?></option>
								<option value="daily" <?php if ( $wpl_option_cron_auctions == 'daily' ): ?>selected="selected"<?php endif; ?>><?php echo __('daily','wplister') ?></option>
							</select>


						</div>
					</div>

					<div class="postbox" id="TemplateSettingsBox">
						<h3 class="hndle"><span><?php echo __('Listing Templates','wplister') ?></span></h3>
						<div class="inside">

							<label for="wpl-process_shortcodes" class="text_label"><?php echo __('Shortcode processing','wplister'); ?>:</label>
							<select id="wpl-process_shortcodes" name="wpl_e2e_process_shortcodes" title="Uninstall" class=" required-entry select">
								<option value="off"     <?php if ( $wpl_process_shortcodes == 'off' ): ?>selected="selected"<?php endif; ?>><?php echo __('off','wplister'); ?></option>
								<option value="content" <?php if ( $wpl_process_shortcodes == 'content' ): ?>selected="selected"<?php endif; ?>><?php echo __('only in product description','wplister'); ?></option>
								<option value="full"    <?php if ( $wpl_process_shortcodes == 'full' ): ?>selected="selected"<?php endif; ?>><?php echo __('in description and listing template','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Enable this if you want to use WordPress shortcodes in your product description or your listing template.','wplister'); ?><br>
							</p>

							<label for="wpl-remove_links" class="text_label"><?php echo __('Link handling','wplister'); ?>:</label>
							<select id="wpl-remove_links" name="wpl_e2e_remove_links" title="Uninstall" class=" required-entry select">
								<option value="default"   <?php if ( $wpl_remove_links == 'default'   ): ?>selected="selected"<?php endif; ?>><?php echo __('remove all links from description','wplister'); ?></option>
								<option value="allow_all" <?php if ( $wpl_remove_links == 'allow_all' ): ?>selected="selected"<?php endif; ?>><?php echo __('allow all links','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('WP-Lister does remove links from product descrptions by default to avoid violating the eBay Links policy.','wplister'); ?>
								<?php echo __('Specifically you are not allowed to advertise products that you list on eBay by linking to their product pages on your site.','wplister'); ?>
								Read more about eBay's Link policy <a href="http://pages.ebay.com/help/policies/listing-links.html" target="_blank">here</a>
							</p>

						</div>
					</div>

					<div class="postbox" id="OtherSettingsBox">
						<h3 class="hndle"><span><?php echo __('Misc options','wplister') ?></span></h3>
						<div class="inside">

							<label for="wpl-hide_dupe_msg" class="text_label"><?php echo __('Hide duplicates warning','wplister'); ?>:</label>
							<select id="wpl-hide_dupe_msg" name="wpl_e2e_hide_dupe_msg" title="Uninstall" class=" required-entry select">
								<option value=""  <?php if ( $wpl_hide_dupe_msg == ''  ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
								<option value="1" <?php if ( $wpl_hide_dupe_msg == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes, I know what I am doing.','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('If you do not plan to use the inventory sync feature, you can safely list one product multiple times.','wplister'); ?>
							</p>

						</div>
					</div>


				</form>
				<?php endif; // $wpl_text_ebay_token == ''  ?>

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






	<script type="text/javascript">
		jQuery( document ).ready(
			function () {
		
				// ebay site selector during install: submit form on selection
				jQuery('#AuthSettingsBox #wpl-text-ebay_site_id').change( function(event, a, b) {					

					var site_id = event.target.value;
					if ( site_id ) {
						jQuery('#frmSetEbaySite').submit();
					}
					
				});

				// show eBay Motors option only for US site
				jQuery('#ConnectionSettingsBox #wpl-text-ebay_site_id').change( function(event, a, b) {					

					var site_id = event.target.value;
					if ( site_id == '0') {
						jQuery('#wrap_enable_ebay_motors').slideDown(300);
					} else {
						jQuery('#wrap_enable_ebay_motors').slideUp(300);						
					}
					
				});

				// change account button
				jQuery('#remove_token').click( function() {					

					return confirm('Do you really want to do this?');
					
				});

				// save changes button
				jQuery('#save_settings').click( function() {					

					jQuery('#settingsForm').first().submit();
					
				});

			}
		);
	
	</script>


</div>
