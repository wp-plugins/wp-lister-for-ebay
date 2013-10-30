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
          
	<?php include_once( dirname(__FILE__).'/settings_tabs.php' ); ?>
	<?php echo $wpl_message ?>

	<div style="width:60%;min-width:640px;" class="postbox-container">
		<div class="metabox-holder">
			<div class="meta-box-sortables ui-sortable">


				<form method="post" action="<?php echo $wpl_form_action; ?>">
					<input type="hidden" name="action" value="save_wplister_devsettings" >

					<div class="postbox" id="SandboxSettingsBox">
						<h3 class="hndle"><span><?php echo __('eBay Sandbox','wplister') ?></span></h3>
						<div class="inside">

							<p>
								<?php echo __('The eBay sandbox allows you to list items to a testing area free of charge.','wplister'); ?>
								<?php echo __('To use the sandbox, you need to create a dedicated sandbox account and connect WP-Lister with it.','wplister'); ?>
								<?php echo __('After enabling sandbox mode click "Change Account" and authenticate WP-Lister using your sandbox account.','wplister'); ?>
							</p>
							<label for="wpl-option-sandbox_enabled" class="text_label"><?php echo __('Sandbox enabled','wplister') ?></label>
							<select id="wpl-option-sandbox_enabled" name="wpl_e2e_option_sandbox_enabled" title="Sandbox" class=" required-entry select">
								<option value="1" <?php if ( $wpl_option_sandbox_enabled == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="0" <?php if ( $wpl_option_sandbox_enabled != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
							</select>

						</div>
					</div>




					<div class="postbox" id="DbLoggingBox">
						<h3 class="hndle"><span><?php echo __('Logging','wplister') ?></span></h3>
						<div class="inside">

							<label for="wpl-option-log_to_db" class="text_label"><?php echo __('Log to database','wplister'); ?>:</label>
							<select id="wpl-option-log_to_db" name="wpl_e2e_option_log_to_db" title="Logging" class=" required-entry select">
								<option value="1" <?php if ( $wpl_option_log_to_db == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="0" <?php if ( $wpl_option_log_to_db != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Enable to log all communication with eBay to the database.','wplister'); ?>
							</p>

							<label for="wpl-option-log_record_limit" class="text_label"><?php echo __('Log entry size limit','wplister'); ?>:</label>
							<select id="wpl-option-log_record_limit" name="wpl_e2e_log_record_limit" class=" required-entry select">
								<option value="4096"  <?php if ( $wpl_log_record_limit == '4096' ):  ?>selected="selected"<?php endif; ?>>4 kb</option>
								<option value="8192"  <?php if ( $wpl_log_record_limit == '8192' ):  ?>selected="selected"<?php endif; ?>>8 kb</option>
								<option value="64000" <?php if ( $wpl_log_record_limit == '64000' ): ?>selected="selected"<?php endif; ?>>64 kb</option>
							</select>

							<label for="wpl-option-xml_formatter" class="text_label"><?php echo __('XML Beautifier','wplister'); ?>:</label>
							<select id="wpl-option-xml_formatter" name="wpl_e2e_xml_formatter" class=" required-entry select">
								<option value="default" <?php if ( $wpl_xml_formatter == 'default' ): ?>selected="selected"<?php endif; ?>>default</option>
								<option value="custom"  <?php if ( $wpl_xml_formatter == 'custom' ):  ?>selected="selected"<?php endif; ?>>use built in XML formatter</option>
							</select>

						</div>
					</div>

					<div class="postbox" id="ErrorHandlingBox">
						<h3 class="hndle"><span><?php echo __('Debug options','wplister') ?></span></h3>
						<div class="inside">

							<label for="wpl-option-ajax_error_handling" class="text_label"><?php echo __('Handle 404 errors for admin-ajax.php','wplister'); ?>:</label>
							<select id="wpl-option-ajax_error_handling" name="wpl_e2e_ajax_error_handling" class=" required-entry select">
								<option value="halt" <?php if ( $wpl_ajax_error_handling == 'halt' ): ?>selected="selected"<?php endif; ?>><?php echo __('Halt on error','wplister'); ?></option>
								<option value="skip" <?php if ( $wpl_ajax_error_handling == 'skip' ): ?>selected="selected"<?php endif; ?>><?php echo __('Continue with next item','wplister'); ?></option>
								<option value="retry" <?php if ( $wpl_ajax_error_handling == 'retry' ): ?>selected="selected"<?php endif; ?>><?php echo __('Try again','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('404 errors for admin-ajax.php should actually never happen and are generally a sign of incorrect server configuration.','wplister'); ?>
								<?php echo __('This setting is just a workaround. You should consider moving to a proper hosting provider instead.','wplister'); ?>
							</p>

							<label for="wpl-option-disable_variations" class="text_label"><?php echo __('Disable variations','wplister'); ?>:</label>
							<select id="wpl-option-disable_variations" name="wpl_e2e_disable_variations" class=" required-entry select">
								<option value="0" <?php if ( $wpl_disable_variations == '0' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
								<option value="1" <?php if ( $wpl_disable_variations == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								This is intended to work around an issue with the eBay API and will force using AddItem instead of AddFixedPriceItem, RelistItem instead of RelistFixedPriceItem, etc.<br>
								Don't enable this unless you do not want to list variations.
							</p>

						</div>
					</div>

					<div class="postbox" id="DeveloperToolBox" style="display:none;">
						<h3 class="hndle"><span><?php echo __('Debug options','wplister') ?></span></h3>
						<div class="inside">

							<label for="wpl-text-log_level" class="text_label"><?php echo __('Log to logfile','wplister'); ?>:</label>
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

							<label for="wpl-text-ebay_token" class="text_label"><?php echo __('eBay token','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_text_ebay_token" id="wpl-text-ebay_token" value="<?php echo $wpl_text_ebay_token; ?>" class="text_input" />
							<p class="desc" style="display: block;">
								<?php #echo __('To use this application you need to generate an eBay token.','wplister'); ?>
								Please use the setup wizard to link WP-Lister to your eBay account. Entering the token manually should only be neccessary for developers when using sandbox mode.
							</p>

						</div>
					</div>

					<div class="submit" style="padding-top: 0; float: right;">
						<input type="submit" value="<?php echo __('Save Settings','wplister') ?>" name="submit" class="button-primary">
					</div>
				</form>


			</div>
		</div>
	</div>




</div>
