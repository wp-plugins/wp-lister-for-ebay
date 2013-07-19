<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">

	.inside p {
		width: 70%;
	}

	a.right,
	input.button-secondary {
		float: right;
	}

</style>

<div class="wrap">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/hammer-32x32.png) no-repeat;" id="wpl-icon"><br /></div>
	<h2><?php echo __('Tools','wplister') ?></h2>
	<?php echo $wpl_message ?>


	<div style="width:640px;" class="postbox-container">
		<div class="metabox-holder">
			<div class="meta-box-sortables ui-sortable">
				
				<div class="postbox" id="UpdateToolsBox">
					<h3 class="hndle"><span><?php echo __('Tools','wplister'); ?></span></h3>
					<div class="inside">

						<!-- Update eBay data --> 
						<a id="btn_update_ebay_data" class="button-secondary right"><?php echo __('Update eBay data','wplister'); ?></a>
						<p><?php echo __('This will load available categories, shipping services, payment options and your custom store categories from eBay','wplister'); ?></p>
						<br style="clear:both;"/>

						<!-- Force update check --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="force_update_check" />
								<input type="submit" value="<?php echo __('Force update check','wplister'); ?>" name="submit" class="button-secondary">
								<p><?php echo __('Since WordPress only checks twice a day for plugin updates, it might be neccessary to force an immediate update check if you want to install an update which was released within the last hours.','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<!-- Get user id --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="GetUser" />
								<input type="submit" value="<?php echo __('Update user details','wplister'); ?>" name="submit" class="button-secondary">
								<p><?php echo __('Update account details from eBay','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<!-- Get token expiration date --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="GetTokenStatus" />
								<input type="submit" value="<?php echo __('Get token expiration date','wplister'); ?>" name="submit" class="button-secondary">
								<p><?php echo __('Get token expiration date','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="update_ebay_transactions_30" />
								<input type="submit" value="<?php echo __('Update eBay transactions','wplister'); ?>" name="submit" class="button-secondary">
								<p><?php echo __('This will load all transactions within 30 days from eBay.','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="check_ebay_time_offset" />
								<input type="submit" value="<?php echo __('Fetch current eBay time','wplister'); ?>" name="submit" class="button-secondary">
								<p><?php echo __('Check eBay time to server time offset','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

					</div>
				</div> <!-- postbox -->

				<?php #if ( get_option('wplister_log_level') > 5 ): ?>
				<div class="postbox" id="DeveloperToolBox" style="display:none;">
					<h3 class="hndle"><span><?php echo __('Debug','wplister'); ?></span></h3>
					<div class="inside">

						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="update_ebay_time_offset" />
								<input type="submit" value="<?php echo __('Test eBay connection','wplister'); ?>" name="submit" class="button-secondary">
								<p><?php echo __('Test connection to eBay API','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="test_curl" />
								<input type="submit" value="<?php echo __('Test Curl / PHP connection','wplister'); ?>" name="submit" class="button-secondary">
								<p><?php echo __('Check availability of CURL php extension and show phpinfo()','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>


						<form method="post" action="admin-ajax.php" target="_blank">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="wplister_tail_log" />
								<input type="submit" value="<?php echo __('View debug log','wplister'); ?>" name="submit" class="button-secondary">
								<p><?php echo __('Open logfile viewer in new tab','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<!--
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="view_logfile" />
								<input type="submit" value="<?php echo __('View debug log','wplister'); ?>" name="submit" class="button-secondary">
								<p><?php echo __('View Logfile','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>
						-->

						<!-- platform notifictions don't work yet -->
						<!--
						<p>GetNotificationPreferences</p>
						<form method="post" action="<?php echo $wpl_form_action; ?>">
							<div class="submit" style="padding-top: 0; float: left;">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="GetNotificationPreferences" />
								<input type="submit" value="GetNotificationPreferences" name="submit" class="button-secondary">
							</div>
						</form>
						<br style="clear:both;"/>

						<p>SetNotificationPreferences</p>
						<form method="post" action="<?php echo $wpl_form_action; ?>">
							<div class="submit" style="padding-top: 0; float: left;">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="SetNotificationPreferences" />
								<input type="submit" value="SetNotificationPreferences" name="submit" class="button-secondary">
							</div>
						</form>
						<br style="clear:both;"/>
						-->

					</div>
				</div> <!-- postbox -->
				<?php #endif; ?>

			</div>
		</div>
	</div>

	<br style="clear:both;"/>

	<?php if ( get_option('wplister_log_level') > 5 ): ?>
		<pre><?php print_r($wpl_debug); ?></pre>
	<?php endif; ?>

	<?php if ( @$_REQUEST['action'] == 'test_curl' ): ?>
		
		<?php if( extension_loaded('curl') ) : ?>
			cURL extension is loaded
			<pre>
				<?php $curl_version = curl_version(); print_r($curl_version) ?>
			</pre>

		<?php else: ?>
			cURL extension is not installed!
		<?php endif; ?>
		<br style="clear:both;"/>

		<?php
			// test for command line app
			echo "cURL command line version:<br><pre>";
			echo `curl --version`;
			echo "</pre>";
		?>
		<br style="clear:both;"/>

		<?php phpinfo() ?>
	<?php endif; ?>


</div>