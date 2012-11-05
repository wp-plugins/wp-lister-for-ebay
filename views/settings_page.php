<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">
	
	#AuthSettingsBox ol li {
		margin-bottom: 25px;
	}
	#AuthSettingsBox ol li > small {
		margin-left: 4px;
	}

</style>

<div class="wrap wplister-page">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/hammer-32x32.png) no-repeat;" id="wpl-icon"><br /></div>
	<!--<h2><?php echo __('Settings','wplister') ?></h2>-->
          
	<?php include_once( dirname(__FILE__).'/settings_tabs.php' ); ?>		
	<?php echo $wpl_message ?>

	<div style="width:60%;min-width:640px;" class="postbox-container">
		<div class="metabox-holder">
			<div class="meta-box-sortables ui-sortable">

				<?php if ( $active_tab == 'settings' ): ?>
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

				<form method="post" action="<?php echo $wpl_form_action; ?>">
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

							<label for="wpl-text-paypal_email" class="text_label"><?php echo __('PayPal Email adress','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_text_paypal_email" id="wpl-text-paypal_email" value="<?php echo $wpl_text_paypal_email; ?>" class="text_input" />
							<p class="desc" style="display: block;">
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
								<option value="" <?php if ( $wpl_option_cron_auctions == '' ): ?>selected="selected"<?php endif; ?>><?php echo __('none','wplister') ?></option>
								<option value="hourly" <?php if ( $wpl_option_cron_auctions == 'hourly' ): ?>selected="selected"<?php endif; ?>><?php echo __('hourly','wplister') ?></option>
								<option value="daily" <?php if ( $wpl_option_cron_auctions == 'daily' ): ?>selected="selected"<?php endif; ?>><?php echo __('daily','wplister') ?></option>
							</select>
	

						</div>
					</div>

					<div class="postbox" id="UninstallSettingsBox">
						<h3 class="hndle"><span><?php echo __('Uninstall','wplister') ?></span></h3>
						<div class="inside">

							<label for="wpl-option-uninstall" class="text_label"><?php echo __('Uninstall on deactivation','wplister'); ?>:</label>
							<select id="wpl-option-uninstall" name="wpl_e2e_option_uninstall" title="Uninstall" class=" required-entry select">
								<option value="1" <?php if ( $wpl_option_uninstall == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="0" <?php if ( $wpl_option_uninstall != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Enable to completely remove listings, transactions and settings when deactivating the plugin.','wplister'); ?><br>
								<?php echo __('To remove your listing templates as well, please delete the folder <code>wp-content/uploads/wp-lister/templates/</code>.','wplister'); ?>
							</p>

						</div>
					</div>



					<div class="submit" style="padding-top: 0; float: right;">
						<input type="submit" value="<?php echo __('Save Settings','wplister') ?>" name="submit" class="button-primary">
					</div>
				</form>
				<?php endif; // $wpl_text_ebay_token == ''  ?>
				<?php endif; // $active_tab == 'settings' ) ?>



			</div>
		</div>
	</div>





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

			}
		);
	
	</script>


</div>
