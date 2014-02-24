<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">

	.inside p {
		width: 70%;
	}

	a.right,
	input.button {
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



	<?php if ( @$_REQUEST['action'] == 'check_max_execution_time' ): ?>

		<div id="message" class="updated" style="display:block !important;">
			<p>
				<?php

					// shutdown handler to log last error
					function wpl_timeout_shutdown_handler() { 
						global $wpl_timeout_shutdown_handler_enabled;
						if ( ! $wpl_timeout_shutdown_handler_enabled ) return;

						// write to log
						$filename = WP_CONTENT_DIR . '/uploads/wplister_shutdown.log';
						touch( $filename );

				        $error = error_get_last();
				        if ($error['type'] === E_ERROR) {
					        $logmsg = "PHP was shut down./n";
					        $logmsg = "Last error: ".print_r($error,1);
							file_put_contents( $filename, $logmsg );

							echo "<br>PHP was shut down. Log file has been written to: $filename"; 
						}

						echo "<br>Last error: ".print_r($error,1)."<br>"; 
					}

					// register shutdown handler
					global $wpl_timeout_shutdown_handler_enabled;
					$wpl_timeout_shutdown_handler_enabled = true;
					register_shutdown_function('wpl_timeout_shutdown_handler');
					
					// enable to debug
					// set_time_limit(1); // quit after 1 sec.	

					// get current setting
					$max_execution_time = ini_get('max_execution_time'); 
					if ( ! $max_execution_time ) $max_execution_time = 42;

					echo "The current value of <code>max_execution_time</code> on your server is <b>$max_execution_time seconds</b>.<br>";
					echo "So please wait just as long - if your server regarding this setting, you should see the same number of dots:<br>";

					for ($sec=0; $sec < $max_execution_time; $sec++) { 
						sleep(1);
						// echo $sec."<br>";
						echo ".";
						ob_flush();
					}

					$wpl_timeout_shutdown_handler_enabled = false;
					echo "<br>";
					echo "OK, this script ran $sec seconds.<br>";
					if ( $sec == $max_execution_time )
						echo "Everthing seems to be all right.<br>";
				?>
			</p>
		</div>

	<?php endif; ?>

	<?php if ( @$_REQUEST['action'] == 'wpl_clear_shutdown_log' ): ?>
		<?php unlink( WP_CONTENT_DIR . '/uploads/wplister_shutdown.log' ) ?>
	<?php endif; ?>

	<?php if ( file_exists( WP_CONTENT_DIR . '/uploads/wplister_shutdown.log' ) ): ?>
		<div id="message" class="updated" style="display:block !important;">
			<p>
				Shutdown log record:
				<pre><?php echo file_get_contents( WP_CONTENT_DIR . '/uploads/wplister_shutdown.log' ) ?></pre>
				<!-- <a href="<?php echo $wpl_form_action ?>&action=wpl_clear_shutdown_log">clear log</a> -->
				<form method="post" action="<?php echo $wpl_form_action; ?>">
					<?php wp_nonce_field( 'e2e_tools_page' ); ?>
					<input type="hidden" name="action" value="wpl_clear_shutdown_log" />
					<input type="submit" value="<?php echo __('Clear log','wplister'); ?>" name="submit" class="button">
				</form>
				<br style="clear:both;"/>
			</p>
		</div>		
	<?php endif; ?>



	<div style="width:640px;" class="postbox-container">
		<div class="metabox-holder">
			<div class="meta-box-sortables ui-sortable">
				
				<div class="postbox" id="UpdateToolsBox">
					<h3 class="hndle"><span><?php echo __('Updates','wplister'); ?></span></h3>
					<div class="inside">

						<!-- Update eBay data --> 
						<a id="btn_update_ebay_data" class="button right"><?php echo __('Update eBay data','wplister'); ?></a>
						<p><?php echo __('This will load available categories, shipping services, payment options and your custom store categories from eBay','wplister'); ?></p>
						<br style="clear:both;"/>

						<!-- Update user details --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="GetUser" />
								<input type="submit" value="<?php echo __('Update user details','wplister'); ?>" name="submit" class="button">
								<p><?php echo __('Update account details from eBay','wplister'); ?> 
									<?php echo __('and update your seller profiles for shipping, payment and return policy.','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<!-- Force update check --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="force_update_check" />
								<input type="submit" value="<?php echo __('Force update check','wplister'); ?>" name="submit" class="button">
								<p><?php echo __('Since WordPress only checks twice a day for plugin updates, it might be neccessary to force an immediate update check if you want to install an update which was released within the last hours.','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<?php /* if ( 'order' == get_option( 'wplister_ebay_update_mode', 'order' ) ) : ?>

						<!-- Update eBay orders --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="update_ebay_orders_30" />
								<input type="submit" value="<?php echo __('Update eBay orders','wplister'); ?>" name="submit" class="button">
								<p><?php echo __('This will load all orders within 30 days from eBay.','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<?php else: ?>

						<!-- Update eBay transactions --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="update_ebay_transactions_30" />
								<input type="submit" value="<?php echo __('Update eBay transactions','wplister'); ?>" name="submit" class="button">
								<p><?php echo __('This will load all transactions within 30 days from eBay.','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<?php endif; */ ?>

					</div>
				</div> <!-- postbox -->

				<div class="postbox" id="InventoryToolBox" style="display:block;">
					<h3 class="hndle"><span><?php echo __('Inventory Check','wplister'); ?></span></h3>
					<div class="inside">

						<!-- check for out of sync products --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="check_wc_out_of_sync" />
								<input type="submit" value="<?php echo __('Check product inventory','wplister'); ?>" name="submit" class="button">
								<p><?php echo __('Check all published listings and find products with different stock or price in WooCommerce.','wplister'); ?>
									<br>
									<small>Note: If you are using price modifiers in your profile, this check could find false positives which are actually in sync.</small>
								</p>
						</form>
						<br style="clear:both;"/>

						<!-- check for out of stock products --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="check_wc_out_of_stock" />
								<input type="submit" value="<?php echo __('Check product stock','wplister'); ?>" name="submit" class="button">
								<p><?php echo __('Check all published listings and find products which are out of stock in WooCommerce.','wplister'); ?>
									<br>
									<small>Note: This doesn't work for variable products at this time.<br>Please use the "Check product inventory" button above instead.</small>
								</p>
						</form>
						<br style="clear:both;"/>

					</div>
				</div> <!-- postbox -->

				<div class="postbox" id="OtherToolBox" style="display:block;">
					<h3 class="hndle"><span><?php echo __('Other','wplister'); ?></span></h3>
					<div class="inside">

						<!-- check for out of sync products --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="check_ebay_image_requirements" />
								<input type="submit" value="<?php echo __('Check product images','wplister'); ?>" name="submit" class="button">
								<p><?php echo __('Check all listings for product images smaller than 500px.','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<!-- check for missing transactions --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="check_missing_ebay_transactions" />
								<input type="submit" value="<?php echo __('Check transactions','wplister'); ?>" name="submit" class="button">
								<p><?php echo __('Fix missing transactions and check for duplicates.','wplister'); ?></p>
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
								<input type="submit" value="<?php echo __('Test eBay connection','wplister'); ?>" name="submit" class="button">
								<p><?php echo __('Test connection to eBay API','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<!-- check PHP max_execution_time --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="check_max_execution_time" />
								<input type="submit" value="<?php echo __('Test PHP time limit','wplister'); ?>" name="submit" class="button">
								<p>
									<?php echo __('Test if your server regards the PHP max_execution_time setting','wplister'); ?><br>
									<small>
										This action is supposed to run for <?php echo ini_get('max_execution_time'); ?> seconds. If you get a timeout error <i>before</i> this time has passed, you need to contact your server admin.
									</small>
								</p>
						</form>
						<br style="clear:both;"/>

						<!-- Check eBay time offset --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="check_ebay_time_offset" />
								<input type="submit" value="<?php echo __('Check eBay time offset','wplister'); ?>" name="submit" class="button">
								<p><?php echo __('Check eBay time to server time offset','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<!-- Show cURL debug info --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="curl_debug" />
								<input type="submit" value="<?php echo __('Show cURL debug info','wplister'); ?>" name="submit" class="button">
								<p><?php echo __('Check availability of CURL php extension and show phpinfo()','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<!-- Get token expiration date --> 
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="GetTokenStatus" />
								<input type="submit" value="<?php echo __('Get token expiration date','wplister'); ?>" name="submit" class="button">
								<p><?php echo __('Get token expiration date','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>


						<!-- View debug log - if enabled --> 
						<?php if ( get_option('wplister_log_level') > 1 ): ?>

						<form method="post" action="admin-ajax.php" target="_blank">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="wplister_tail_log" />
								<input type="submit" value="<?php echo __('View debug log','wplister'); ?>" name="submit" class="button">
								<p><?php echo __('Open logfile viewer in new tab','wplister'); ?></p>
						</form>
						<br style="clear:both;"/>

						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="wplister_clear_log" />
								<input type="submit" value="<?php echo __('Clear debug log','wplister'); ?>" name="submit" class="button">
								<p><?php echo __('Current log file size','wplister'); ?>: <?php echo round($wpl_log_size/1024/1024,1) ?> mb</p>
						</form>
						<br style="clear:both;"/>

						<?php endif; ?>


						<!--
						<form method="post" action="<?php echo $wpl_form_action; ?>">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="view_logfile" />
								<input type="submit" value="<?php echo __('View debug log','wplister'); ?>" name="submit" class="button">
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
								<input type="submit" value="GetNotificationPreferences" name="submit" class="button">
							</div>
						</form>
						<br style="clear:both;"/>

						<p>SetNotificationPreferences</p>
						<form method="post" action="<?php echo $wpl_form_action; ?>">
							<div class="submit" style="padding-top: 0; float: left;">
								<?php wp_nonce_field( 'e2e_tools_page' ); ?>
								<input type="hidden" name="action" value="SetNotificationPreferences" />
								<input type="submit" value="SetNotificationPreferences" name="submit" class="button">
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


		<!-- mysql show variables -->
		<?php 
			global $wpdb;
			$mysql_variables = $wpdb->get_results('SHOW VARIABLES');
		?>
		<table>
			<?php foreach ($mysql_variables as $var) : ?>
				<tr>
					<td><?php echo $var->Variable_name ?></td>
					<td><?php echo $var->Value ?></td>
				</tr>
			<?php endforeach; ?>
		</table>

		<?php 
			if ( ini_get('disable_functions') )
				echo "PHP disable_functions: ".ini_get('disable_functions')."<br>\n";
			if ( ini_get('disable_classes') )
				echo "PHP disable_classes: ".ini_get('disable_classes')."<br>\n";
		?>


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