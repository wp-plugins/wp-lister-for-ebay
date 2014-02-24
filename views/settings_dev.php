<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">
	
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
										<p><?php echo __('This page contains some special options intended for developers and debugging.','wplister') ?></p>
									</div>
								</div>

								<div id="major-publishing-actions">
									<div id="publishing-action">
										<input type="hidden" name="action" value="save_wplister_devsettings" >
										<input type="submit" value="<?php echo __('Save Settings','wplister'); ?>" id="save_settings" class="button-primary" name="save">
									</div>
									<div class="clear"></div>
								</div>

							</div>

						</div>
					</div>



					<?php if ( $expdate = get_option( 'wplister_ebay_token_expirationtime' ) ) : ?>
					<div class="postbox" id="TokenExpiryBox">
						<h3 class="hndle"><span><?php echo __('eBay Token','wplister') ?></span></h3>
						<div class="inside">

							<p>
								<?php echo sprintf( __('Your token will expire in %s.','wplister'), human_time_diff( strtotime($expdate) ) ) ?>
								<!-- expdate <?php echo $expdate ?> -->
							</p>
							<p>
								<?php echo __('When it expires, you will need to re-authenticate WP-Lister with your eBay account.','wplister') ?>
							</p>

						</div>
					</div>
					<?php endif; ?>

				</div>
			</div> <!-- #postbox-container-1 -->


			<!-- #postbox-container-2 -->
			<div id="postbox-container-2" class="postbox-container">
				<div class="meta-box-sortables ui-sortable">


					<div class="postbox" id="SandboxSettingsBox">
						<h3 class="hndle"><span><?php echo __('eBay Sandbox','wplister') ?></span></h3>
						<div class="inside">

							<p>
								<?php echo __('The eBay sandbox allows you to list items to a testing area free of charge.','wplister'); ?>
								<?php echo __('This is feature intended for developers only and not recommended for end users.','wplister'); ?><br>
							</p>
							<label for="wpl-option-sandbox_enabled" class="text_label">
								<?php echo __('Sandbox enabled','wplister') ?>
								<?php $tip_msg  = __('To use the sandbox, you need to create a dedicated sandbox account and connect WP-Lister with it.','wplister'); ?>
								<?php $tip_msg .= __('After enabling sandbox mode click "Change Account" and authenticate WP-Lister using your sandbox account.','wplister'); ?>
                                <?php wplister_tooltip($tip_msg) ?>
							</label>
							<select id="wpl-option-sandbox_enabled" name="wpl_e2e_option_sandbox_enabled" title="Sandbox" class=" required-entry select">
								<option value="1" <?php if ( $wpl_option_sandbox_enabled == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="0" <?php if ( $wpl_option_sandbox_enabled != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
							</select>

						</div>
					</div>

					<div class="postbox" id="DbLoggingBox">
						<h3 class="hndle"><span><?php echo __('Logging','wplister') ?></span></h3>
						<div class="inside">

							<label for="wpl-option-log_to_db" class="text_label">
								<?php echo __('Log to database','wplister'); ?>
                                <?php wplister_tooltip('If you have any issues or want support to look into a specific error message from eBay then please enable logging, repeat the steps and send the resulting log record to support.') ?>
							</label>
							<select id="wpl-option-log_to_db" name="wpl_e2e_option_log_to_db" title="Logging" class=" required-entry select">
								<option value="1" <?php if ( $wpl_option_log_to_db == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="0" <?php if ( $wpl_option_log_to_db != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Enable to log all communication with eBay to the database.','wplister'); ?>
							</p>

							<label for="wpl-option-log_record_limit" class="text_label">
								<?php echo __('Log entry size limit','wplister'); ?>
                                <?php wplister_tooltip('Limit the maximum size of a single log record. The default value is 4k.') ?>
							</label>
							<select id="wpl-option-log_record_limit" name="wpl_e2e_log_record_limit" class=" required-entry select">
								<option value="4096"  <?php if ( $wpl_log_record_limit == '4096' ):  ?>selected="selected"<?php endif; ?>>4 kb</option>
								<option value="8192"  <?php if ( $wpl_log_record_limit == '8192' ):  ?>selected="selected"<?php endif; ?>>8 kb</option>
								<option value="64000" <?php if ( $wpl_log_record_limit == '64000' ): ?>selected="selected"<?php endif; ?>>64 kb</option>
							</select>

							<label for="wpl-option-xml_formatter" class="text_label">
								<?php echo __('XML Beautifier','wplister'); ?>
                                <?php wplister_tooltip('Select which XML formatter should be used to display log records.') ?>
							</label>
							<select id="wpl-option-xml_formatter" name="wpl_e2e_xml_formatter" class=" required-entry select">
								<option value="default" <?php if ( $wpl_xml_formatter == 'default' ): ?>selected="selected"<?php endif; ?>>default</option>
								<option value="custom"  <?php if ( $wpl_xml_formatter == 'custom' ):  ?>selected="selected"<?php endif; ?>>use built in XML formatter</option>
							</select>

						</div>
					</div>

					<div class="postbox" id="ErrorHandlingBox">
						<h3 class="hndle"><span><?php echo __('Debug options','wplister') ?></span></h3>
						<div class="inside">

							<p>
								Warning: These options are for debugging purposes only. Please do not change them unless WP Lab support told you to do so.
							</p>

							<label for="wpl-option-php_error_handling" class="text_label">
								<?php echo __('PHP error handling','wplister'); ?>
                                <?php wplister_tooltip('Please leave this set to Production unless told otherwise by support.') ?>
							</label>
							<select id="wpl-option-php_error_handling" name="wpl_e2e_php_error_handling" class=" required-entry select">
								<option value="0" <?php if ( $wpl_php_error_handling == '0' ): ?>selected="selected"<?php endif; ?>><?php echo __('Production Mode','wplister'); ?> (default)</option>
								<option value="1" <?php if ( $wpl_php_error_handling == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Show all errors inline','wplister'); ?></option>
								<option value="2" <?php if ( $wpl_php_error_handling == '2' ): ?>selected="selected"<?php endif; ?>><?php echo __('Show fatal errors on shutdown','wplister'); ?></option>
								<option value="3" <?php if ( $wpl_php_error_handling == '3' ): ?>selected="selected"<?php endif; ?>><?php echo __('Show errors inline and on shutdown','wplister'); ?></option>
								<option value="6" <?php if ( $wpl_php_error_handling == '6' ): ?>selected="selected"<?php endif; ?>><?php echo __('Show fatal and non-fatal errors on shutdown','wplister'); ?></option>
							</select>

							<label for="wpl-option-ajax_error_handling" class="text_label">
								<?php echo __('AJAX error handling','wplister'); ?>
								<?php $tip_msg = __('404 errors for admin-ajax.php should actually never happen and are generally a sign of incorrect server configuration.','wplister') .' '. __('This setting is just a workaround. You should consider moving to a proper hosting provider instead.','wplister'); ?>
                                <?php wplister_tooltip($tip_msg) ?>
							</label>
							<select id="wpl-option-ajax_error_handling" name="wpl_e2e_ajax_error_handling" class=" required-entry select">
								<option value="halt" <?php if ( $wpl_ajax_error_handling == 'halt' ): ?>selected="selected"<?php endif; ?>><?php echo __('Halt on error','wplister'); ?> (default)</option>
								<option value="skip" <?php if ( $wpl_ajax_error_handling == 'skip' ): ?>selected="selected"<?php endif; ?>><?php echo __('Continue with next item','wplister'); ?></option>
								<option value="retry" <?php if ( $wpl_ajax_error_handling == 'retry' ): ?>selected="selected"<?php endif; ?>><?php echo __('Try again','wplister'); ?></option>
							</select>

							<label for="wpl-option-disable_variations" class="text_label">
								<?php echo __('Disable variations','wplister'); ?>
                                <?php wplister_tooltip('This is intended to work around an issue with the eBay API and will force using AddItem instead of AddFixedPriceItem, RelistItem instead of RelistFixedPriceItem, etc.<br>Do not enable this unless you do not want to list variations!') ?>
							</label>
							<select id="wpl-option-disable_variations" name="wpl_e2e_disable_variations" class=" required-entry select">
								<option value="0" <?php if ( $wpl_disable_variations == '0' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?> (default)</option>
								<option value="1" <?php if ( $wpl_disable_variations == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
							</select>

						</div>
					</div>

					<div class="postbox" id="DeveloperToolBox" style="display:none;">
						<h3 class="hndle"><span><?php echo __('Developer options','wplister') ?></span></h3>
						<div class="inside">

							<label for="wpl-option-enable_messages_page" class="text_label">
								<?php echo __('eBay Messages','wplister'); ?>
                                <?php wplister_tooltip('Enable handling of eBay messages within WP-Lister. This feature is still in beta.') ?>
							</label>
							<select id="wpl-option-enable_messages_page" name="wpl_e2e_enable_messages_page" class=" required-entry select">
								<option value="0" <?php if ( $wpl_enable_messages_page == '0' ): ?>selected="selected"<?php endif; ?>><?php echo __('Disabled','wplister'); ?> (default)</option>
								<option value="1" <?php if ( $wpl_enable_messages_page == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Enabled','wplister'); ?></option>
							</select>

							<label for="wpl-text-log_level" class="text_label"><?php echo __('Log to logfile','wplister'); ?></label>
							<select id="wpl-text-log_level" name="wpl_e2e_text_log_level" title="Logging" class=" required-entry select">
								<option value=""> -- <?php echo __('no logfile','wplister'); ?> -- </option>
								<option value="2" <?php if ( $wpl_text_log_level == '2' ): ?>selected="selected"<?php endif; ?>>Error</option>
								<option value="3" <?php if ( $wpl_text_log_level == '3' ): ?>selected="selected"<?php endif; ?>>Critical</option>
								<option value="4" <?php if ( $wpl_text_log_level == '4' ): ?>selected="selected"<?php endif; ?>>Warning</option>
								<option value="5" <?php if ( $wpl_text_log_level == '5' ): ?>selected="selected"<?php endif; ?>>Notice</option>
								<option value="6" <?php if ( $wpl_text_log_level == '6' ): ?>selected="selected"<?php endif; ?>>Info</option>
								<option value="7" <?php if ( $wpl_text_log_level == '7' ): ?>selected="selected"<?php endif; ?>>Debug</option>
								<option value="9" <?php if ( $wpl_text_log_level == '9' ): ?>selected="selected"<?php endif; ?>>All</option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('write debug information to logfile.','wplister'); ?>
								<?php if ( $wpl_text_log_level > 1 ): ?>
									&raquo; <a href="/wp-content/uploads/wp-lister/wplister.log" target="_blank">view log</a>
								<?php endif; ?>
							</p>

							<label for="wpl-text-ebay_token" class="text_label"><?php echo __('eBay token','wplister'); ?></label>
							<input type="text" name="wpl_e2e_text_ebay_token" id="wpl-text-ebay_token" value="<?php echo $wpl_text_ebay_token; ?>" class="text_input" />
							<p class="desc" style="display: block;">
								<?php #echo __('To use this application you need to generate an eBay token.','wplister'); ?>
								Please use the setup wizard to link WP-Lister to your eBay account. Entering the token manually should only be neccessary for developers when using sandbox mode.
							</p>

						</div>
					</div>

					<!--
					<div class="submit" style="padding-top: 0; float: right;">
						<input type="submit" value="<?php echo __('Save Settings','wplister') ?>" name="submit" class="button-primary">
					</div>
					-->


				</div> <!-- .meta-box-sortables -->
			</div> <!-- #postbox-container-1 -->



		</div> <!-- #post-body -->
		<br class="clear">
	</div> <!-- #poststuff -->

	</form>


</div>
