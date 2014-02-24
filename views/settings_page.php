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
												<?php if ( $expdate = get_option( 'wplister_ebay_token_expirationtime' ) ) : ?>
												<!--
												<tr><td><?php echo __('eBay token valid for','wplister') . ':</td><td>' ?><?php echo human_time_diff( strtotime($expdate) ) ?></td></tr>
												-->
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
											<input type="submit" value="<?php echo __('Change Account','wplister'); ?>" id="remove_token" class="button" name="remove_token">
										</form>
										</div>
									<?php elseif ( $wpl_text_ebay_token ): ?>
										<div id="publishing-action" style="float:left">
										<form method="post" id="removeTokenForm" action="<?php echo $wpl_form_action; ?>">
											<?php wp_nonce_field( 'remove_token' ); ?>
											<input type="hidden" name="action" value="remove_token" >
											<input type="submit" value="<?php echo __('Reset Account','wplister'); ?>" id="remove_token" class="button" name="remove_token">
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

					<?php if ( get_option( 'wplister_cron_auctions' ) ) : ?>
					<div class="postbox" id="UpdateScheduleBox">
						<h3 class="hndle"><span><?php echo __('Update Schedule','wplister') ?></span></h3>
						<div class="inside">

							<p>
							<?php if ( wp_next_scheduled( 'wplister_update_auctions' ) ) : ?>
								<?php echo __('Next scheduled update','wplister'); ?> 
								<?php echo human_time_diff( wp_next_scheduled( 'wplister_update_auctions' ), current_time('timestamp',1) ) ?>
								<?php echo wp_next_scheduled( 'wplister_update_auctions' ) < current_time('timestamp',1) ? 'ago' : '' ?>
							<?php else: ?>
								<span style="color:darkred; font-weight:bold">
									Warning: Update schedule is disabled.
								</span></p><p>
								Please click the "Save Settings" button above in order to reset the update schedule.
							<?php endif; ?>
							</p>

						</div>
					</div>
					<?php endif; ?>

					<div class="postbox" id="PayPalSettingsBox">
						<h3 class="hndle"><span><?php echo __('PayPal','wplister') ?></span></h3>
						<div class="inside">

							<label for="wpl-text_paypal_email-field" class="text_label"><?php echo __('PayPal account','wplister'); ?></label>
							<input type="text" name="wpl_e2e_text_paypal_email" id="wpl-text_paypal_email-field" value="<?php echo $wpl_text_paypal_email; ?>" class="text_input" />
							<p class="desc" style="display: block;">
								<?php echo __('To use PayPal you need to enter your PayPal address.','wplister'); ?>
							</p>

						</div>
					</div>

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
											<select id="wpl-text-ebay_site_id" name="wpl_e2e_text_ebay_site_id" class="required-entry select" style="width:auto;float: right">
												<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
												<?php foreach ($wpl_ebay_sites as $site_id => $site_name) : ?>
													<option value="<?php echo $site_id ?>" <?php if ( $wpl_text_ebay_site_id == $site_id ): ?>selected="selected"<?php endif; ?>><?php echo $site_name ?></option>					
												<?php endforeach; ?>
											</select>
											<?php echo __('Select the eBay site you want to list your items on:','wplister') ?>
											<br>
											<small>
											If you want to change the site later, you will need to go through setup again. <br>
											</small>
										</form>
								</li>
									<li>
										<a style="float:right;" href="<?php echo $wpl_auth_url; ?>" class="button-primary" target="_blank">Connect with eBay</a>
										<?php echo __('Click "Connect with eBay" to sign in to eBay and grant access for WP-Lister','wplister') ?>
										<br>
										<small>This will open the eBay Sign In page in a new window.</small><br>
										<small>Please sign in, grant access for WP-Lister and close the new window to come back here.</small>
									</li>
									<li>
										<form id="frmFetchToken" method="post" action="<?php echo $wpl_form_action; ?>">
											<input type="hidden" name="action" value="FetchToken" >
											<input  style="float:right;" type="submit" value="<?php echo __('Fetch eBay Token','wplister') ?>" name="submit" class="button">
											<?php echo __('After linking WP-Lister with your eBay account, click here to fetch your token','wplister') ?>
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
					<input type="hidden" name="wpl_e2e_text_paypal_email" id="wpl_text_paypal_email" value="<?php echo $wpl_text_paypal_email; ?>" >

					<div class="postbox" id="ConnectionSettingsBox">
						<h3 class="hndle"><span><?php echo __('eBay settings','wplister') ?></span></h3>
						<div class="inside">

							<label for="wpl-text-ebay_site_id" class="text_label"><?php echo __('eBay site','wplister'); ?></label>
							<select id="wpl-text-ebay_site_id" name="wpl_e2e_text_ebay_site_id" class=" required-entry select">
								<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
								<?php foreach ($wpl_ebay_sites as $site_id => $site_name) : ?>
									<option value="<?php echo $site_id ?>" <?php if ( $wpl_text_ebay_site_id == $site_id ): ?>selected="selected"<?php endif; ?>><?php echo $site_name ?></option>					
								<?php endforeach; ?>
							</select>

							<div id="wrap_enable_ebay_motors" style="<?php echo $wpl_text_ebay_site_id != 0 ? 'display:none' : '' ?>">
								<label for="wpl-option-enable_ebay_motors" class="text_label"><?php echo __('Include eBay Motors','wplister') ?></label>
								<select id="wpl-option-enable_ebay_motors" name="wpl_e2e_option_enable_ebay_motors" class=" required-entry select">
									<option value="1" <?php if ( $wpl_option_enable_ebay_motors == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
									<option value="0" <?php if ( $wpl_option_enable_ebay_motors != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
								</select>
							</div>

						</div>
					</div>


					<div class="postbox" id="UpdateOptionBox"
						<?php if ( $wpl_text_ebay_token == '' ) echo 'style="display:none;"' ?>
						>
						<h3 class="hndle"><span><?php echo __('Automatic updates','wplister') ?></span></h3>
						<div class="inside">
							<p><?php echo __('Enable to update listings and transactions using WP-Cron.','wplister'); ?></p>

							<label for="wpl-option-cron_auctions" class="text_label">
								<?php echo __('Update interval','wplister') ?>
                                <?php wplister_tooltip('Select how often WP-Lister should check for new sales on eBay.') ?>
							</label>
							<select id="wpl-option-cron_auctions" name="wpl_e2e_option_cron_auctions" class=" required-entry select">
								<option value="" <?php if ( $wpl_option_cron_auctions == '' ): ?>selected="selected"<?php endif; ?>><?php echo __('manually','wplister') ?></option>
								<option value="hourly" <?php if ( $wpl_option_cron_auctions == 'hourly' ): ?>selected="selected"<?php endif; ?>><?php echo __('hourly','wplister') ?></option>
								<option value="daily" <?php if ( $wpl_option_cron_auctions == 'daily' ): ?>selected="selected"<?php endif; ?>><?php echo __('daily','wplister') ?></option>
							</select>


							<label for="wpl-option-ebay_update_mode" class="text_label">
								<?php echo __('Update mode','wplister') ?>
                                <?php wplister_tooltip('Set this to "Order" if you want to create a single WooCommerce order from a combined order on eBay. This is a transitory option which will be removed in future versions.') ?>
							</label>
							<select id="wpl-option-ebay_update_mode" name="wpl_e2e_option_ebay_update_mode" class=" required-entry select">
								<option value="transaction" <?php if ( $wpl_option_ebay_update_mode == 'transaction' ): ?>selected="selected"<?php endif; ?>><?php echo __('Transaction','wplister'); ?> (legacy)</option>
								<option value="order"       <?php if ( $wpl_option_ebay_update_mode == 'order'       ): ?>selected="selected"<?php endif; ?>><?php echo __('Order','wplister'); ?> (default)</option>
							</select>
							<p class="desc" style="display: block;">
								<?php if ( $wpl_option_ebay_update_mode == 'transaction' ): ?>
									Set this to "Order" to enable the new order processing and disable the old transaction processing mode.<br>
								<?php else: ?>
									Note: Once you enabled the new order processing mode you should not switch back to transaction mode.<br>
								<?php endif; ?>

								<?php global $woocommerce; ?>
								<?php if ( ( isset($woocommerce->version) ) && ( version_compare( $woocommerce->version, '2.0' ) < 0 ) ) : ?>
									<span style="color:darkred;">Warning: You need to update to WooCommerce 2.0 to use the new "Order" update mode.<br></span>
								<?php endif; ?>
								
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

					// handle input fields outside of form
					var paypal_address = jQuery('#wpl-text_paypal_email-field').first().attr('value');
					jQuery('#wpl_text_paypal_email').attr('value', paypal_address );

					jQuery('#settingsForm').first().submit();
					
				});

			}
		);
	
	</script>


</div>
