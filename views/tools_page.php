<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">

	.inside p {
		width: 70%;
	}

	a.right,
	input.button-secondary {
		float: right;
	}


	.test_results h3 {
		margin-left: .4em;
	}

	.test_results .details {
		margin-left: 3em;
		font-size: 1em;
		color: #999;
		/*margin-bottom: 1em;*/
	}

	.test_results img.inline_status {
		vertical-align: bottom;
		height: 16px;
		margin-left: .5em;
		margin-right: 1em;
	}

</style>

<div class="wrap">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/hammer-32x32.png) no-repeat;" id="wpl-icon"><br /></div>
	<h2><?php echo __('Tools','wplister') ?></h2>
	<?php echo $wpl_message ?>


	<?php if ( @$_REQUEST['action'] == 'check_ebay_connection' ): ?>
		<div id="message" class="updated fade below-h2 test_results" style="display:block !important">
			<h3>Test Results</h3>
			<p>
				<?php echo $wpl_resultsHtml ?>
			</p>
			<p style="padding-left:1em">
				<?php if ( $wpl_results->successEbay_1 && $wpl_results->successWordPress && $wpl_results->successWplabApi ) : ?>
					Everthing seems to be all right.
				<?php elseif ( ! $wpl_results->successEbay_1 && $wpl_results->successWordPress && $wpl_results->successWplabApi ) : ?>
					Your server allows connections to wordpress.org and other sites, but not to api.ebay.com.<br>
					Please contact your hosting provider in order to solve this problem.
				<?php else : ?>
					Your server seems to block outgoing connections. You need to contact your hosting provider.
				<?php endif; ?>
			</p>
			<!--<pre><?php print_r( $wpl_results ) ?></pre>-->
		</div>
	<?php endif; ?>

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

						<!-- Update user details --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="GetUser" />
								<input type="submit" value="<?php echo __('Update user details','wplister'); ?>" name="submit" class="button-secondary">
								<p><?php echo __('Update account details from eBay','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<!-- Update eBay transactions --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="update_ebay_transactions_30" />
								<input type="submit" value="<?php echo __('Update eBay transactions','wplister'); ?>" name="submit" class="button-secondary">
								<p><?php echo __('This will load all transactions within 30 days from eBay.','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

					</div>
				</div> <!-- postbox -->

				<?php #if ( get_option('wplister_log_level') > 5 ): ?>
				<div class="postbox" id="DeveloperToolBox" style="display:block;">
					<h3 class="hndle"><span><?php echo __('Debugging','wplister'); ?></span></h3>
					<div class="inside">

						<!-- Test eBay connection --> 
						<form method="get" action="<?php echo $wpl_form_action; ?>">
								<?php #wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="page" value="<?php echo @$_REQUEST['page'] ?>" />
								<input type="hidden" name="action" value="check_ebay_connection" />
								<input type="submit" value="<?php echo __('Test eBay connection','wplister'); ?>" name="submit" class="button-secondary">
								<p><?php echo __('Test connection to eBay API','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<!-- Check eBay time offset --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="check_ebay_time_offset" />
								<input type="submit" value="<?php echo __('Check eBay time offset','wplister'); ?>" name="submit" class="button-secondary">
								<p><?php echo __('Check eBay time to server time offset','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<!-- Show cURL debug info --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="curl_debug" />
								<input type="submit" value="<?php echo __('Show cURL debug info','wplister'); ?>" name="submit" class="button-secondary">
								<p><?php echo __('Check availability of CURL php extension and show phpinfo()','wplister'); ?></p>
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


						<!-- View debug log - if enabled --> 
						<?php if ( get_option('wplister_log_level') > 1 ): ?>

						<form method="post" action="admin-ajax.php" target="_blank">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="wplister_tail_log" />
								<input type="submit" value="<?php echo __('View debug log','wplister'); ?>" name="submit" class="button-secondary">
								<p><?php echo __('Open logfile viewer in new tab','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="wplister_clear_log" />
								<input type="submit" value="<?php echo __('Clear debug log','wplister'); ?>" name="submit" class="button-secondary">
								<p><?php echo __('Current log file size','wplister'); ?>: <?php echo round($wpl_log_size/1024/1024,1) ?> mb</p>
						</form>
						<br style="clear:both;"/>

						<?php endif; ?>


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

	<?php if ( @$_REQUEST['action'] == 'curl_debug' ): ?>
		
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

	<?php
		// just in case - I've seen sites where WPL_Setup::checkSetup() wasn't properly called...
        if ( ini_get('safe_mode') ) {
			echo "
				<b>Warning: PHP safe mode is enabled.</b><br>
				Your server seems to have PHP safe mode enabled, which can cause unexpected behaviour or stop WP-Lister from working properly.<br>
				PHP safe mode has been deprecated and will be completely removed in the next PHP version - so it is highly recommended to disable it or ask your hoster to do it for you.
			";
		}
	?>

</div>