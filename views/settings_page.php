<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">
	
	#poststuff #side-sortables .postbox input.text_input,
	#poststuff #side-sortables .postbox select.select {
	    width: 50%;
	}
	#poststuff #side-sortables .postbox label.text_label {
	    width: 45%;
	}
	#poststuff #side-sortables .postbox p.desc {
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
						<h3><span><?php echo __('Update','wplister'); ?></span></h3>
						<div class="inside">

							<div id="submitpost" class="submitbox">

								<div id="misc-publishing-actions">
									<div class="misc-pub-section">
									<?php if ( ! empty( WPLE()->accounts ) ): ?>
										<p><?php echo __('These are the most commonly used configuration options.','wplister') ?></p>
									<?php else: ?>
										<p><?php echo __('No eBay account has been set up yet.','wplister') ?></p>
									<?php endif; ?>
									</div>
								</div>

								<div id="major-publishing-actions">

									<div id="publishing-action">
										<input type="submit" value="<?php echo __('Save Settings','wplister'); ?>" id="save_settings" class="button-primary" name="save">
									</div>
									<div class="clear"></div>
								</div>

							</div>

						</div>
					</div>

					<?php if ( $wpl_is_staging_site ) : ?>
					<div class="postbox" id="StagingSiteBox">
						<h3 class="hndle"><span><?php echo __('Staging Site','wplister') ?></span></h3>
						<div class="inside">
							<p>
								<span style="color:darkred; font-weight:bold">
									Note: Automatic background updates and order creation have been disabled on this staging site.
								</span>
							</p>
						</div>
					</div>
					<?php endif; ?>

					<?php if ( get_option( 'wplister_cron_auctions' ) ) : ?>
					<div class="postbox" id="UpdateScheduleBox">
						<h3 class="hndle"><span><?php echo __('Update Schedule','wplister') ?></span></h3>
						<div class="inside">

							<p>
							<?php if ( wp_next_scheduled( 'wplister_update_auctions' ) ) : ?>
								<?php echo __('Next scheduled update','wplister'); ?> 
								<?php echo human_time_diff( wp_next_scheduled( 'wplister_update_auctions' ), current_time('timestamp',1) ) ?>
								<?php echo wp_next_scheduled( 'wplister_update_auctions' ) < current_time('timestamp',1) ? 'ago' : '' ?>
							<?php elseif ( $wpl_option_cron_auctions == 'external' ) : ?>
								<?php echo __('Background updates are handled by an external cron job.','wplister'); ?> 
								<a href="#TB_inline?height=420&width=900&inlineId=cron_setup_instructions" class="thickbox">
									<?php echo __('Details','wplister'); ?>
								</a>

								<div id="cron_setup_instructions" style="display: none;">
									<h2>
										<?php echo __('How to set up an external cron job','wplister'); ?>
									</h2>
									<p>
										<?php echo __('Luckily, you don\'t have to be a server admin to set up an external cron job.','wplister'); ?>
										<?php echo __('You can ask your server admin to set up a cron job on your own server - or use a 3rd party service like CronBlast, which provides a user friendly interface and additional features for a small monthly fee.','wpla'); ?>
									</p>

									<h3>
										<?php echo __('Option A: Web cron service','wplister'); ?>
									</h3>
									<p>
										<?php $ec_link = '<a href="https://www.cronblast.com/" target="_blank">www.cronblast.com</a>' ?>
										<?php echo sprintf( __('The easiest way to set up a cron job is to sign up with %s and use the following URL to create a new task.','wplister'), $ec_link ); ?><br>
									</p>
									<code>
										<?php echo bloginfo('siteurl') ?>/wp-admin/admin-ajax.php?action=wplister_run_scheduled_tasks
									</code>

									<h3>
										<?php echo __('Option B: Server cron job','wplister'); ?>
									</h3>
									<p>
										<?php echo __('If you prefer to set up a cron job on your own server you can create a cron job that will execute the following command:','wplister'); ?>
									</p>

									<code style="font-size:0.8em;">
										wget -q -O - <?php echo bloginfo('siteurl') ?>/wp-admin/admin-ajax.php?action=wplister_run_scheduled_tasks >/dev/null 2>&1
									</code>

									<p>
										<?php echo __('Note: Your cron job should run at least every 15 minutes but not more often than every 5 minutes.','wplister'); ?>
									</p>
								</div>

							<?php else: ?>
								<span style="color:darkred; font-weight:bold">
									Warning: Update schedule is disabled.
								</span></p><p>
								Please click the "Save Settings" button above in order to reset the update schedule.
							<?php endif; ?>
							</p>

							<?php if ( get_option('wplister_cron_last_run') ) : ?>
							<p>
								<?php echo __('Last run','wplister'); ?>: 
								<?php echo human_time_diff( get_option('wplister_cron_last_run'), current_time('timestamp',1) ) ?> ago
							</p>
							<?php endif; ?>

						</div>
					</div>
					<?php endif; ?>

				</div>
			</div> <!-- #postbox-container-1 -->


			<!-- #postbox-container-2 -->
			<div id="postbox-container-2" class="postbox-container">
				<div class="meta-box-sortables ui-sortable">
					
				<form method="post" id="settingsForm" action="<?php echo $wpl_form_action; ?>">
					<input type="hidden" name="action" value="save_wplister_settings" >

					<div class="postbox" id="UpdateOptionBox">
						<h3 class="hndle"><span><?php echo __('Background Tasks','wplister') ?></span></h3>
						<div class="inside">
							<!-- <p><?php echo __('Enable to update listings and transactions using WP-Cron.','wplister'); ?></p> -->

							<label for="wpl-option-cron_auctions" class="text_label">
								<?php echo __('Update interval','wplister') ?>
                                <?php wplister_tooltip('Select how often WP-Lister should check for new sales on eBay.') ?>
							</label>
							<select id="wpl-option-cron_auctions" name="wpl_e2e_option_cron_auctions" class=" required-entry select">
								<option value="" <?php if ( $wpl_option_cron_auctions == '' ): ?>selected="selected"<?php endif; ?>><?php echo __('manually','wplister') ?></option>
								<option value="hourly" <?php if ( $wpl_option_cron_auctions == 'hourly' ): ?>selected="selected"<?php endif; ?>><?php echo __('hourly','wplister') ?></option>
								<option value="daily" <?php if ( $wpl_option_cron_auctions == 'daily' ): ?>selected="selected"<?php endif; ?>><?php echo __('daily','wplister') ?></option>
							</select>


						</div>
					</div>


					<div class="postbox" id="OtherSettingsBox">
						<h3 class="hndle"><span><?php echo __('Misc Options','wplister') ?></span></h3>
						<div class="inside">

							<label for="wpl-local_auction_display" class="text_label">
								<?php echo __('Link auctions to eBay','wplister'); ?>
                                <?php wplister_tooltip('In order to prevent selling an item in WooCommerce which is currently on auction, WP-Lister can replace the "Add to cart" button with a "View on eBay" button.') ?>
							</label>
							<select id="wpl-local_auction_display" name="wpl_e2e_local_auction_display" class=" required-entry select">
								<option value="off" 	<?php if ( $wpl_local_auction_display == 'off'    ): ?>selected="selected"<?php endif; ?>><?php echo __('Off','wplister'); ?></option>
								<option value="always"  <?php if ( $wpl_local_auction_display == 'always' ): ?>selected="selected"<?php endif; ?>><?php echo __('Always show link to eBay for products on auction','wplister'); ?></option>
								<option value="forced"  <?php if ( $wpl_local_auction_display == 'forced' ): ?>selected="selected"<?php endif; ?>><?php echo __('Always show link to eBay for auctions and fixed price items','wplister'); ?> (<?php _e('not recommended','wplister'); ?>)</option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Enable this to modify the product details page for items currently on auction.','wplister'); ?>
							</p>

							<label for="wpl-send_weight_and_size" class="text_label">
								<?php echo __('Send weight and dimensions','wplister'); ?>
                                <?php wplister_tooltip('By default, product weight and dimensions are only sent to eBay when calculated shipping is used.<br>Enable this option to send weight and dimensions for all listings.') ?>
							</label>
							<select id="wpl-send_weight_and_size" name="wpl_e2e_send_weight_and_size" class=" required-entry select">
								<option value="default" <?php if ( $wpl_send_weight_and_size == 'default'): ?>selected="selected"<?php endif; ?>><?php echo __('Only for calculated shipping services','wplister'); ?> (<?php _e('default','wplister'); ?>)</option>
								<option value="always"  <?php if ( $wpl_send_weight_and_size == 'always' ): ?>selected="selected"<?php endif; ?>><?php echo __('Always send weight and dimensions if set','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Enable this if eBay requires package weight or dimensions for flat shipping.','wplister'); ?>
							</p>

						</div>
					</div>


				</form>

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
		
				// save changes button
				jQuery('#save_settings').click( function() {					

					// // handle input fields outside of form
					// var paypal_address = jQuery('#wpl-text_paypal_email-field').first().attr('value');
					// jQuery('#wpl_text_paypal_email').attr('value', paypal_address );

					jQuery('#settingsForm').first().submit();
					
				});

			}
		);
	
	</script>


</div>
