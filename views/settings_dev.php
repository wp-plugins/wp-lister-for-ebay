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



					<div class="postbox dev_box" id="VersionInfoBox" style="<?php echo defined('WPLISTER_RESELLER_VERSION') ? 'display:none' : ''; ?>">
						<h3 class="hndle"><span><?php echo __('Version Info','wplister') ?></span></h3>
						<div class="inside">

							<table style="width:100%">
								<tr><td>WP-Lister</td><td>	<?php echo WPLISTER_VERSION ?> </td></tr>
								<tr><td>Database</td><td> <?php echo get_option('wplister_db_version') ?> </td></tr>
								<tr><td>WordPress</td><td> <?php global $wp_version; echo $wp_version ?> </td></tr>
								<tr><td>WooCommerce</td><td> <?php echo defined('WC_VERSION') ? WC_VERSION : WOOCOMMERCE_VERSION ?> </td></tr>
								<?php if ( defined('WPLISTER_RESELLER_VERSION') ) : ?>
									<tr><td>Reseller Add-On</td><td> <?php echo WPLISTER_RESELLER_VERSION ?> </td></tr>
								<?php endif; ?>
							</table>

						</div>
					</div>

				</div>
			</div> <!-- #postbox-container-1 -->


			<!-- #postbox-container-2 -->
			<div id="postbox-container-2" class="postbox-container">
				<div class="meta-box-sortables ui-sortable">


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

							<label for="wpl-option-log_days_limit" class="text_label">
								<?php echo __('Keep log records for','wplister'); ?>
                                <?php wplister_tooltip('Select how long log records should be kept. Older records are removed automatically. The default is 30 days.') ?>
							</label>
							<select id="wpl-option-log_days_limit" name="wpl_e2e_log_days_limit" class=" required-entry select">
								<option value="7"  <?php if ( $wpl_log_days_limit == '7' ):  ?>selected="selected"<?php endif; ?>>7 days</option>
								<option value="14"  <?php if ( $wpl_log_days_limit == '14' ):  ?>selected="selected"<?php endif; ?>>14 days</option>
								<option value="30"  <?php if ( $wpl_log_days_limit == '30' ):  ?>selected="selected"<?php endif; ?>>30 days</option>
								<option value="60"  <?php if ( $wpl_log_days_limit == '60' ):  ?>selected="selected"<?php endif; ?>>60 days</option>
								<option value="90"  <?php if ( $wpl_log_days_limit == '90' ):  ?>selected="selected"<?php endif; ?>>90 days</option>
							</select>

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
                                <?php wplister_tooltip('Select which XML formatter should be used to display log records. If the default settings does not work, switch to the built in formatter.') ?>
							</label>
							<select id="wpl-option-xml_formatter" name="wpl_e2e_xml_formatter" class=" required-entry select">
								<option value="default" <?php if ( $wpl_xml_formatter == 'default' ): ?>selected="selected"<?php endif; ?>>auto detect</option>
								<option value="custom"  <?php if ( $wpl_xml_formatter == 'custom' ):  ?>selected="selected"<?php endif; ?>>force built in XML formatter</option>
							</select>

						</div>
					</div>

					<div class="postbox" id="StagingSiteSettingsBox">
						<h3 class="hndle"><span><?php echo __('Staging site','wplister') ?></span></h3>
						<div class="inside">

							<p>
								<?php echo __('If you frequently clone your WordPress installation to a staging site, you can make WP-Lister automatically disable background updates and order creation when running on the staging site.','wplister'); ?>
								<?php echo __('Enter a unique part of your staging domain below to activate this feature.','wplister'); ?><br>
							</p>
							<label for="wpl-staging_site_pattern" class="text_label">
								<?php echo __('Staging site pattern','wplister') ?>
								<?php $tip_msg  = __('You do not need to enter the full domain name of your staging site.','wplister'); ?>
								<?php $tip_msg .= __('If your staging domain is mydomain.staging.wpengine.com enter staging.wpengine.com as a general pattern.','wplister'); ?>
                                <?php wplister_tooltip($tip_msg) ?>
							</label>
							<input type="text" name="wpl_e2e_staging_site_pattern" id="wpl-staging_site_pattern" value="<?php echo $wpl_staging_site_pattern; ?>" class="text_input" />
							<p class="desc" style="display: block;">
								Example: staging.wpengine.com
							</p>

						</div>
					</div>

					<div class="postbox" id="ErrorHandlingBox">
						<h3 class="hndle"><span><?php echo __('Debug options','wplister') ?></span></h3>
						<div class="inside">

							<p>
								<?php echo __('Warning: These options are for debugging purposes only. Please do not change them unless our support told you to do so.','wplister'); ?>
							</p>

							<label for="wpl-option-php_error_handling" class="text_label">
								<?php echo __('PHP error handling','wplister'); ?>
                                <?php wplister_tooltip('Please leave this set to Production unless told otherwise by support.') ?>
							</label>
							<select id="wpl-option-php_error_handling" name="wpl_e2e_php_error_handling" class=" required-entry select">
								<option value="0" <?php if ( $wpl_php_error_handling == '0' ): ?>selected="selected"<?php endif; ?>><?php echo __('Production Mode','wplister'); ?> (default)</option>
								<option value="9" <?php if ( $wpl_php_error_handling == '9' ): ?>selected="selected"<?php endif; ?>><?php echo __('Production Mode','wplister'); ?> (forced)</option>
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

							<label for="wpl-option-eps_xfer_mode" class="text_label">
								<?php echo __('EPS transfer mode','wplister'); ?>
								<?php $tip_msg = __('If you have trouble uploading your images to EPS (eBay Picture Service), set the transfer mode to active.','wplister'); ?>
                                <?php wplister_tooltip($tip_msg) ?>
							</label>
							<select id="wpl-option-eps_xfer_mode" name="wpl_e2e_eps_xfer_mode" class=" required-entry select">
								<option value="passive" <?php if ( $wpl_eps_xfer_mode == 'passive' ): ?>selected="selected"<?php endif; ?>><?php echo __('Passive','wplister'); ?> (default)</option>
								<option value="active"  <?php if ( $wpl_eps_xfer_mode == 'active'  ): ?>selected="selected"<?php endif; ?>><?php echo __('Active','wplister'); ?></option>
							</select>

							<label for="wpl-option-disable_variations" class="text_label">
								<?php echo __('Disable variations','wplister'); ?>
                                <?php wplister_tooltip('This is intended to work around an issue with the eBay API and will force using AddItem instead of AddFixedPriceItem, RelistItem instead of RelistFixedPriceItem, etc.<br>Do not enable this unless you do not want to list variations!') ?>
							</label>
							<select id="wpl-option-disable_variations" name="wpl_e2e_disable_variations" class=" required-entry select">
								<option value="0" <?php if ( $wpl_disable_variations == '0' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?> (default)</option>
								<option value="1" <?php if ( $wpl_disable_variations == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
							</select>

							<label for="wpl-option-disable_compat_list" class="text_label">
								<?php echo __('Disable parts compatibility','wplister'); ?>
                                <?php wplister_tooltip('This is intended to work around an issue with the eBay API and will omit Parts Compatibility Lists from being submitted to eBay when an item is listed or revised.') ?>
							</label>
							<select id="wpl-option-disable_compat_list" name="wpl_e2e_disable_compat_list" class=" required-entry select">
								<option value="0" <?php if ( $wpl_disable_compat_list == '0' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?> (default)</option>
								<option value="1" <?php if ( $wpl_disable_compat_list == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
							</select>


							<label for="wpl-option-enable_item_edit_link" class="text_label">
								<?php echo __('Allow direct editing','wplister'); ?>
                                <?php wplister_tooltip('Shows an additional "edit" link on the listing page, which allows you to edit the listing database fields directly. It is not recommended to use this option at all - all your changes will be overwritten when the linked product is updated again!') ?>
							</label>
							<select id="wpl-option-enable_item_edit_link" name="wpl_e2e_enable_item_edit_link" class=" required-entry select">
								<option value="0" <?php if ( $wpl_enable_item_edit_link == '0' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?> (default)</option>
								<option value="1" <?php if ( $wpl_enable_item_edit_link == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
							</select>

							<label for="wpl-option-force_table_items_limit" class="text_label">
								<?php echo __('Limit displayed items','wplister'); ?>
                                <?php wplister_tooltip('If you can not open the listings or orders page or receive a timeout error when doing so, you can use this option to temporarily limit the maxmimum number of displayed listings or orders.') ?>
							</label>
							<select id="wpl-option-force_table_items_limit" name="wpl_e2e_force_table_items_limit" class=" required-entry select">
								<option value=""><?php echo __('No limit','wplister'); ?> (default) </option>
								<option value="1" <?php if ( $wpl_force_table_items_limit == '1' ): ?>selected="selected"<?php endif; ?>>1 item</option>
								<option value="2" <?php if ( $wpl_force_table_items_limit == '2' ): ?>selected="selected"<?php endif; ?>>2 items</option>
								<option value="3" <?php if ( $wpl_force_table_items_limit == '3' ): ?>selected="selected"<?php endif; ?>>3 items</option>
								<option value="5" <?php if ( $wpl_force_table_items_limit == '5' ): ?>selected="selected"<?php endif; ?>>5 items</option>
								<option value="10" <?php if ( $wpl_force_table_items_limit == '10' ): ?>selected="selected"<?php endif; ?>>10 items</option>
							</select>

							<label for="wpl-option-apply_profile_batch_size" class="text_label">
								<?php echo __('Apply profiles in batches of','wplister'); ?>
                                <?php wplister_tooltip('If your server times out or runs out of memory when applying a profile to a huge number of items you may have to lower this setting.') ?>
							</label>
							<select id="wpl-option-apply_profile_batch_size" name="wpl_e2e_apply_profile_batch_size" class=" required-entry select">
								<option value="20"   <?php if ( $wpl_apply_profile_batch_size == '20'   ): ?>selected="selected"<?php endif; ?>>20 items</option>
								<option value="50"   <?php if ( $wpl_apply_profile_batch_size == '50'   ): ?>selected="selected"<?php endif; ?>>50 items</option>
								<option value="100"  <?php if ( $wpl_apply_profile_batch_size == '100'  ): ?>selected="selected"<?php endif; ?>>100 item</option>
								<option value="200"  <?php if ( $wpl_apply_profile_batch_size == '200'  ): ?>selected="selected"<?php endif; ?>>200 items</option>
								<option value="500"  <?php if ( $wpl_apply_profile_batch_size == '500'  ): ?>selected="selected"<?php endif; ?>>500 items</option>
								<option value="1000" <?php if ( $wpl_apply_profile_batch_size == '1000' ): ?>selected="selected"<?php endif; ?>>1000 items (default)</option>
							</select>

							<label for="wpl-ignore_orders_before_ts" class="text_label">
								<?php echo __('Ignore orders before','wplister') ?>
								<?php wplister_tooltip('This is where WP-Lister remembers when it was connected to your eBay account. Orders placed before that date will be ignored.') ?>
							</label>
							<input type="text" name="wpl_e2e_ignore_orders_before_ts" id="wpl-ignore_orders_before_ts" value="<?php echo $wpl_ignore_orders_before_ts ? date('Y-m-d H:i:s T',$wpl_ignore_orders_before_ts) : ''; ?>" class="text_input" />
							<p class="desc" style="display: block;">
								Example: <?php echo date('Y-m-d H:i:s T') ?>
							</p>

						</div>
					</div>

					<div class="postbox dev_box" id="DeveloperToolBox" style="display:none;">
						<h3 class="hndle"><span><?php echo __('Developer options','wplister') ?></span></h3>
						<div class="inside">

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

							<label for="wpl-option-log_include_authinfo" class="text_label">
								<?php echo __('Include auth debug info in log','wplister'); ?>
                                <?php wplister_tooltip('Collect additional debug information when the database log option is enabled.') ?>
							</label>
							<select id="wpl-option-log_include_authinfo" name="wpl_e2e_log_include_authinfo" class=" required-entry select">
								<option value="0" <?php if ( $wpl_log_include_authinfo == '0' ): ?>selected="selected"<?php endif; ?>><?php echo __('Disabled','wplister'); ?> (default)</option>
								<option value="1" <?php if ( $wpl_log_include_authinfo == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Enabled','wplister'); ?></option>
							</select>

							<label for="wpl-wple_instance" class="text_label"><?php echo __('Instance ID','wplister'); ?></label>
							<input type="text" name="wpl_e2e_wple_instance" id="wpl-wple_instance" value="<?php echo get_option('wple_instance') ?>" class="text_input" />
							<p class="desc" style="display: block;">
								Don't change this unless you migrated your installation to a different domain.
							</p>

						</div>
					</div>


				</div> <!-- .meta-box-sortables -->
			</div> <!-- #postbox-container-1 -->



		</div> <!-- #post-body -->
		<br class="clear">
	</div> <!-- #poststuff -->

	</form>


</div>
