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
										<p><?php echo __('This page contains some advanced options for special use cases.','wplister') ?></p>
									</div>
								</div>

								<div id="major-publishing-actions">
									<div id="publishing-action">
										<input type="hidden" name="action" value="save_wplister_advanced_settings" >
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

							<label for="wpl-option-uninstall" class="text_label"><?php echo __('Uninstall','wplister'); ?></label>
							<select id="wpl-option-uninstall" name="wpl_e2e_option_uninstall" class="required-entry select">
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

			<!-- #postbox-container-3 -->
			<?php if ( ( ! is_multisite() || is_main_site() ) && apply_filters( 'wpl_enable_capabilities_options', true ) ) : ?>
			<div id="postbox-container-3" class="postbox-container">
				<div class="meta-box-sortables ui-sortable">
					
					<div class="postbox" id="PermissionsSettingsBox">
						<h3 class="hndle"><span><?php echo __('Roles and Capabilities','wplister') ?></span></h3>
						<div class="inside">

							<?php
								$wpl_caps = array(
									'manage_ebay_listings'  => __('Manage Listings','wplister'),
									'manage_ebay_options'   => __('Manage Settings','wplister'),
									'prepare_ebay_listings' => __('Prepare Listings','wplister'),
									'publish_ebay_listings' => __('Publish Listings','wplister'),
								);
							?>

							<table style="width:100%">
                            <?php foreach ($wpl_available_roles as $role => $role_name) : ?>
                            	<tr>
                            		<th style="text-align: left">
		                                <?php echo $role_name; ?>
		                            </th>

		                            <?php foreach ($wpl_caps as $cap => $cap_name ) : ?>
                            		<td>
		                                <input type="checkbox" 
		                                    	name="wpl_permissions[<?php echo $role ?>][<?php echo $cap ?>]" 
		                                       	id="wpl_permissions_<?php echo $role.'_'.$cap ?>" class="checkbox_cap" 
		                                       	<?php if ( isset( $wpl_wp_roles[ $role ]['capabilities'][ $cap ] ) ) : ?>
		                                       		checked
		                                   		<?php endif; ?>
		                                       	/>
		                                       	<label for="wpl_permissions_<?php echo $role.'_'.$cap ?>">
				                               		<?php echo $cap_name; ?>
				                               	</label>
			                            </td>
		                            <?php endforeach; ?>

		                        </tr>
                            <?php endforeach; ?>
                        	</table>


						</div>
					</div>

				</div>
			</div> <!-- #postbox-container-1 -->
			<?php endif; ?>


			<!-- #postbox-container-2 -->
			<div id="postbox-container-2" class="postbox-container">
				<div class="meta-box-sortables ui-sortable">
					
					<?php do_action( 'wple_before_advanced_settings' ) ?>

					<div class="postbox" id="TemplateSettingsBox">
						<h3 class="hndle"><span><?php echo __('Listing Templates','wplister') ?></span></h3>
						<div class="inside">

							<label for="wpl-process_shortcodes" class="text_label">
								<?php echo __('Shortcode processing','wplister'); ?>
                                <?php wplister_tooltip('By default, WP-Lister runs your product description through the usual WordPress content filters which enabled you to use shortcodes in your product descriptions.<br>If a plugin causes trouble by adding unwanted HTML to your description on eBay, you should try setting this to "off".') ?>
							</label>
							<select id="wpl-process_shortcodes" name="wpl_e2e_process_shortcodes" class="required-entry select">
								<option value="off"     <?php if ( $wpl_process_shortcodes == 'off' ): ?>selected="selected"<?php endif; ?>><?php echo __('off','wplister'); ?></option>
								<option value="content" <?php if ( $wpl_process_shortcodes == 'content' ): ?>selected="selected"<?php endif; ?>><?php echo __('only in product description','wplister'); ?></option>
								<option value="full"    <?php if ( $wpl_process_shortcodes == 'full' ): ?>selected="selected"<?php endif; ?>><?php echo __('in description and listing template','wplister'); ?></option>
								<option value="remove"  <?php if ( $wpl_process_shortcodes == 'remove' ): ?>selected="selected"<?php endif; ?>><?php echo __('remove all shortcodes from description','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Enable this if you want to use WordPress shortcodes in your product description or your listing template.','wplister'); ?><br>
							</p>

							<label for="wpl-remove_links" class="text_label">
								<?php echo __('Link handling','wplister'); ?>
                                <?php wplister_tooltip('Should WP-Lister replace links within the product description with plain text?') ?>
							</label>
							<select id="wpl-remove_links" name="wpl_e2e_remove_links" class="required-entry select">
								<option value="default"   <?php if ( $wpl_remove_links == 'default'   ): ?>selected="selected"<?php endif; ?>><?php echo __('remove all links from description','wplister'); ?></option>
								<option value="allow_all" <?php if ( $wpl_remove_links == 'allow_all' ): ?>selected="selected"<?php endif; ?>><?php echo __('allow all links','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Links are removed from product descriptions by default to avoid violating the eBay Links policy.','wplister'); ?>
								<?php echo __('Specifically you are not allowed to advertise products that you list on eBay by linking to their product pages on your site.','wplister'); ?>
								
								<?php echo __('Read more about eBay\'s Link policy','wplister'); ?>
								<a href="<?php echo __('http://pages.ebay.com/help/policies/listing-links.html','wplister'); ?>" target="_blank"><?php echo __('here','wplister'); ?></a>
							</p>

							<label for="wpl-default_image_size" class="text_label">
								<?php echo __('Default image size','wplister'); ?>
                                <?php wplister_tooltip('Select the image size WP-Lister should use on eBay. It is recommended to set this to "full".') ?>
							</label>
							<select id="wpl-default_image_size" name="wpl_e2e_default_image_size" class="required-entry select">
								<option value="full"    <?php if ( $wpl_default_image_size == 'full'   ): ?>selected="selected"<?php endif; ?>><?php echo __('full','wplister'); ?></option>
								<option value="large"   <?php if ( $wpl_default_image_size == 'large'  ): ?>selected="selected"<?php endif; ?>><?php echo __('large','wplister'); ?></option>
							</select>

							<label for="wpl-wc2_gallery_fallback" class="text_label">
								<?php echo __('Product Gallery','wplister'); ?>
                                <?php wplister_tooltip('In order to find additional product images, WP-Lister first checks if there is a dedicated <i>Product Gallery</i> (WC 2.0+).<br>
                                						If there\'s none, it can use all images which were uploaded (attached) to the product - as it was the default behaviour in WooCommerce 1.x.') ?>
							</label>
							<select id="wpl-wc2_gallery_fallback" name="wpl_e2e_wc2_gallery_fallback" class="required-entry select">
								<option value="attached" <?php if ( $wpl_wc2_gallery_fallback == 'attached' ): ?>selected="selected"<?php endif; ?>><?php echo __('use attached images if no Gallery found','wplister'); ?></option>
								<option value="none"     <?php if ( $wpl_wc2_gallery_fallback == 'none'     ): ?>selected="selected"<?php endif; ?>><?php echo __('use Product Gallery images','wplister'); ?> (<?php _e('default','wplister'); ?>)</option>
							</select>
							<?php if ( $wpl_wc2_gallery_fallback == 'attached' ): ?>
							<p class="desc" style="display: block;">
								<?php echo __('If you find unwanted images in your listings try disabling this option.','wplister'); ?>
							</p>
							<?php endif; ?>

							<label for="wpl-gallery_items_limit" class="text_label">
								<?php echo __('Gallery Widget limit','wplister'); ?>
                                <?php wplister_tooltip('Limit the number of items displayed by the gallery widgets in your listing template - like <i>recent additions</i> or <i>ending soon</i>. The default is 12 items.') ?>
							</label>
							<select id="wpl-gallery_items_limit" name="wpl_e2e_gallery_items_limit" class="required-entry select">
								<option value="3" <?php if ( $wpl_gallery_items_limit == '3' ): ?>selected="selected"<?php endif; ?>>3 <?php echo __('items','wplister'); ?></option>
								<option value="6" <?php if ( $wpl_gallery_items_limit == '6' ): ?>selected="selected"<?php endif; ?>>6 <?php echo __('items','wplister'); ?></option>
								<option value="9" <?php if ( $wpl_gallery_items_limit == '9' ): ?>selected="selected"<?php endif; ?>>9 <?php echo __('items','wplister'); ?></option>
								<option value="12" <?php if ( $wpl_gallery_items_limit == '12' ): ?>selected="selected"<?php endif; ?>>12 <?php echo __('items','wplister'); ?></option>
								<option value="15" <?php if ( $wpl_gallery_items_limit == '15' ): ?>selected="selected"<?php endif; ?>>15 <?php echo __('items','wplister'); ?></option>
								<option value="24" <?php if ( $wpl_gallery_items_limit == '24' ): ?>selected="selected"<?php endif; ?>>24 <?php echo __('items','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('The maximum number of items shown by listings template gallery widgets.','wplister'); ?>
							</p>

						</div>
					</div>

					<div class="postbox" id="UISettingsBox">
						<h3 class="hndle"><span><?php echo __('User Interface','wplister') ?></span></h3>
						<div class="inside">

							<?php if ( ! defined('WPLISTER_RESELLER_VERSION') ) : ?>
							<label for="wpl-text-admin_menu_label" class="text_label">
								<?php echo __('Menu label','wplister') ?>
                                <?php wplister_tooltip('You can change the main admin menu label in your dashboard from WP-Lister to anything you like.') ?>
							</label>
							<input type="text" name="wpl_e2e_text_admin_menu_label" id="wpl-text-admin_menu_label" value="<?php echo $wpl_text_admin_menu_label; ?>" class="text_input" />
							<p class="desc" style="display: block;">
								<?php echo __('Customize the main admin menu label of WP-Lister.','wplister'); ?><br>
							</p>
							<?php endif; ?>

							<label for="wpl-option-preview_in_new_tab" class="text_label">
								<?php echo __('Open preview in new tab','wplister') ?>
                                <?php wplister_tooltip('WP-Lister uses a Thickbox modal window to display the preview by default. However, this can cause issues in rare cases where you embed some JavaScript code (like NivoSlider) - or you might just want more screen estate to preview your listings.') ?>
							</label>
							<select id="wpl-option-preview_in_new_tab" name="wpl_e2e_option_preview_in_new_tab" class="required-entry select">
								<option value="0" <?php if ( $wpl_option_preview_in_new_tab != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?> (<?php _e('default','wplister'); ?>)</option>
								<option value="1" <?php if ( $wpl_option_preview_in_new_tab == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Select if you want the listing preview open in a new tab by default.','wplister'); ?><br>
							</p>

							<label for="wpl-option-enable_thumbs_column" class="text_label">
								<?php echo __('Listing thumbnails','wplister') ?>
                                <?php wplister_tooltip('Enable this to show product thumbnails on the listings page. Disabled by default to save screen estate.') ?>
							</label>
							<select id="wpl-option-enable_thumbs_column" name="wpl_e2e_enable_thumbs_column" class="required-entry select">
								<option value="0" <?php if ( $wpl_enable_thumbs_column != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?> (<?php _e('default','wplister'); ?>)</option>
								<option value="1" <?php if ( $wpl_enable_thumbs_column == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Show product images on listings page.','wplister'); ?><br>
							</p>

							<label for="wpl-enable_custom_product_prices" class="text_label">
								<?php echo __('Enable custom price field','wplister') ?>
                                <?php wplister_tooltip('If do not use custom prices in eBay and prefer less options when editing a product, you can disable the custom price fields here.') ?>
							</label>
							<select id="wpl-enable_custom_product_prices" name="wpl_e2e_enable_custom_product_prices" class=" required-entry select">
								<option value="0" <?php if ( $wpl_enable_custom_product_prices == '0' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
								<option value="1" <?php if ( $wpl_enable_custom_product_prices == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?> (<?php _e('default','wplister'); ?>)</option>
								<option value="2" <?php if ( $wpl_enable_custom_product_prices == '2' ): ?>selected="selected"<?php endif; ?>><?php echo __('Hide for variations','wplister'); ?></option>
							</select>

							<label for="wpl-enable_mpn_and_isbn_fields" class="text_label">
								<?php echo __('Enable MPN and ISBN fields','wplister') ?>
                                <?php wplister_tooltip('If your variable products have MPNs or ISBNs, set this option to Yes.') ?>
							</label>
							<select id="wpl-enable_mpn_and_isbn_fields" name="wpl_e2e_enable_mpn_and_isbn_fields" class=" required-entry select">
								<option value="0" <?php if ( $wpl_enable_mpn_and_isbn_fields == '0' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
								<option value="1" <?php if ( $wpl_enable_mpn_and_isbn_fields == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="2" <?php if ( $wpl_enable_mpn_and_isbn_fields == '2' ): ?>selected="selected"<?php endif; ?>><?php echo __('Hide for variations','wplister'); ?> (<?php _e('default','wplister'); ?>)</option>
							</select>

							<label for="wpl-enable_categories_page" class="text_label">
								<?php echo __('Categories in main menu','wplister') ?>
                                <?php wplister_tooltip('This will add a <em>Categories</em> submenu entry visible to users who can manage listings.') ?>
							</label>
							<select id="wpl-enable_categories_page" name="wpl_e2e_enable_categories_page" class="required-entry select">
								<option value="0" <?php if ( $wpl_enable_categories_page != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?> (<?php _e('default','wplister'); ?>)</option>
								<option value="1" <?php if ( $wpl_enable_categories_page == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Enable this to make category settings available to users without access to other eBay settings.','wplister'); ?><br>
							</p>

							<label for="wpl-enable_accounts_page" class="text_label">
								<?php echo __('Accounts in main menu','wplister') ?>
                                <?php wplister_tooltip('This will add a <em>Accounts</em> submenu entry visible to users who can manage listings.') ?>
							</label>
							<select id="wpl-enable_accounts_page" name="wpl_e2e_enable_accounts_page" class="required-entry select">
								<option value="0" <?php if ( $wpl_enable_accounts_page != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?> (<?php _e('default','wplister'); ?>)</option>
								<option value="1" <?php if ( $wpl_enable_accounts_page == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Enable this to make account settings available to users without access to other eBay settings.','wplister'); ?><br>
							</p>

							<label for="wpl-option-disable_wysiwyg_editor" class="text_label">
								<?php echo __('Disable WYSIWYG editor','wplister') ?>
                                <?php wplister_tooltip('Depending in your listing template content, you might want to disable the built in WP editor to edit your template content.') ?>
							</label>
							<select id="wpl-option-disable_wysiwyg_editor" name="wpl_e2e_option_disable_wysiwyg_editor" class="required-entry select">
								<option value="0" <?php if ( $wpl_option_disable_wysiwyg_editor != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?> (<?php _e('default','wplister'); ?>)</option>
								<option value="1" <?php if ( $wpl_option_disable_wysiwyg_editor == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Select the editor you want to use to edit listing templates.','wplister'); ?><br>
							</p>

							<label for="wpl-hide_dupe_msg" class="text_label">
								<?php echo __('Hide duplicates warning','wplister'); ?>
                                <?php wplister_tooltip('Technically, WP-Lister allows you to list the same product multiple times on eBay - in order to increase your visibility. However, this is not recommended as WP-Lister Pro would not be able to decrease the stock on eBay accordingly when the product is sold in WooCommerce.') ?>
							</label>
							<select id="wpl-hide_dupe_msg" name="wpl_e2e_hide_dupe_msg" class="required-entry select">
								<option value=""  <?php if ( $wpl_hide_dupe_msg == ''  ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?> (<?php _e('recommended','wplister'); ?>)</option>
								<option value="1" <?php if ( $wpl_hide_dupe_msg == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes, I know what I am doing.','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('If you do not plan to use the synchronize sales feature, you can safely list one product multiple times.','wplister'); ?>
							</p>

						</div>
					</div>


					<div class="postbox" id="OtherSettingsBox">
						<h3 class="hndle"><span><?php echo __('Misc Options','wplister') ?></span></h3>
						<div class="inside">

							<label for="wpl-autofill_missing_gtin" class="text_label">
								<?php echo __('Missing Product Identifiers','wplister'); ?>
                                <?php wplister_tooltip('eBay requires product identifiers (UPC/EAN) in selected categories starting 2015 - missing EANs/UPCs can cause the revise process to fail.<br><br>If your products do not have either UPCs or EANs, please use this option.') ?>
							</label>
							<select id="wpl-autofill_missing_gtin" name="wpl_e2e_autofill_missing_gtin" class="required-entry select">
								<option value=""  <?php if ( $wpl_autofill_missing_gtin == ''  ): ?>selected="selected"<?php endif; ?>><?php echo __('Do nothing','wplister'); ?> (<?php _e('default','wplister'); ?>)</option>
								<option value="upc" <?php if ( $wpl_autofill_missing_gtin == 'upc' ): ?>selected="selected"<?php endif; ?>><?php echo __('If UPC is empty use "Does not apply" instead','wplister'); ?></option>
								<option value="ean" <?php if ( $wpl_autofill_missing_gtin == 'ean' ): ?>selected="selected"<?php endif; ?>><?php echo __('If EAN is empty use "Does not apply" instead','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Enable this option if your products do not have UPCs or EANs.','wplister'); ?>
							</p>

							<label for="wpl-option-local_timezone" class="text_label">
								<?php echo __('Local timezone','wplister') ?>
                                <?php wplister_tooltip('This is currently used to convert the order creation date from UTC to local time.') ?>
							</label>
							<select id="wpl-option-local_timezone" name="wpl_e2e_option_local_timezone" class="required-entry select">
								<option value="">-- <?php echo __('no timezone selected','wplister'); ?> --</option>
								<?php foreach ($wpl_timezones as $tz_id => $tz_name) : ?>
									<option value="<?php echo $tz_id ?>" <?php if ( $wpl_option_local_timezone == $tz_id ): ?>selected="selected"<?php endif; ?>><?php echo $tz_name ?></option>					
								<?php endforeach; ?>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Select your local timezone.','wplister'); ?><br>
							</p>

							<label for="wpl-convert_dimensions" class="text_label">
								<?php echo __('Dimension Unit Conversion','wplister'); ?>
                                <?php wplister_tooltip('WP-Lister assumes that you use the same dimension unit in WooCommerce as on eBay. Enable this to convert length, width and height from one unit to another.') ?>
							</label>
							<select id="wpl-convert_dimensions" name="wpl_e2e_convert_dimensions" class="required-entry select">
								<option value=""  <?php if ( $wpl_convert_dimensions == ''  ): ?>selected="selected"<?php endif; ?>><?php echo __('No conversion','wplister'); ?> (<?php _e('default','wplister'); ?>)</option>
								<option value="in-cm" <?php if ( $wpl_convert_dimensions == 'in-cm' ): ?>selected="selected"<?php endif; ?>><?php echo __('Convert inches to centimeters','wplister'); ?> ( in &raquo; cm )</option>
								<option value="mm-cm" <?php if ( $wpl_convert_dimensions == 'mm-cm' ): ?>selected="selected"<?php endif; ?>><?php echo __('Convert milimeters to centimeters','wplister'); ?> ( mm &raquo; cm )</option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Convert length, width and height to the unit required by eBay.','wplister'); ?>
							</p>

							<label for="wpl-convert_attributes_mode" class="text_label">
								<?php echo __('Use attributes as item specifics','wplister'); ?>
                                <?php wplister_tooltip('The default is to convert all WooCommerce product attributes to item specifics on eBay.<br><br>If you disable this option, only the item specifics defined in your listing profile will be sent to eBay.') ?>
							</label>
							<select id="wpl-convert_attributes_mode" name="wpl_e2e_convert_attributes_mode" class="required-entry select">
								<option value="all"    <?php if ( $wpl_convert_attributes_mode == 'all'    ): ?>selected="selected"<?php endif; ?>><?php echo __('Convert all attributes to item specifics','wplister'); ?> (<?php _e('default','wplister'); ?>)</option>
								<option value="single" <?php if ( $wpl_convert_attributes_mode == 'single' ): ?>selected="selected"<?php endif; ?>><?php echo __('Convert all attributes, but disable multi value attributes','wplister'); ?></option>
								<option value="none"   <?php if ( $wpl_convert_attributes_mode == 'none'   ): ?>selected="selected"<?php endif; ?>><?php echo __('Disabled','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Disable this option if you do not want all product attributes to be sent to eBay.','wplister'); ?>
							</p>

							<label for="wpl-exclude_attributes" class="text_label">
								<?php echo __('Exclude attributes','wplister') ?>
                                <?php wplister_tooltip('If you want to hide certain product attributes from eBay enter their names separated by commas here.<br>Example: Brand,Size,MPN') ?>
							</label>
							<input type="text" name="wpl_e2e_exclude_attributes" id="wpl-exclude_attributes" value="<?php echo $wpl_exclude_attributes; ?>" class="text_input" />
							<p class="desc" style="display: block;">
								<?php echo __('Enter a comma separated list of product attributes to exclude from eBay.','wplister'); ?><br>
							</p>

							<label for="wpl-exclude_variation_values" class="text_label">
								<?php echo __('Exclude variations','wplister') ?>
                                <?php wplister_tooltip('If you want to hide certain variations from eBay enter their attribute values separated by commas here.<br>Example: Brown,Blue,Orange') ?>
							</label>
							<input type="text" name="wpl_e2e_exclude_variation_values" id="wpl-exclude_variation_values" value="<?php echo $wpl_exclude_variation_values; ?>" class="text_input" />
							<p class="desc" style="display: block;">
								<?php echo __('Enter a comma separated list of variation attribute values to exclude from eBay.','wplister'); ?><br>
							</p>

							<label for="wpl-enable_item_compat_tab" class="text_label">
								<?php echo __('Enable Item Compatibility tab','wplister'); ?>
                                <?php wplister_tooltip('Item compatibility lists are currently only created for imported products. Future versions of WP-Lister Pro will allow to define compatibility lists in WooCommerce.') ?>
							</label>
							<select id="wpl-enable_item_compat_tab" name="wpl_e2e_enable_item_compat_tab" class="required-entry select">
								<option value=""  <?php if ( $wpl_enable_item_compat_tab == ''  ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
								<option value="1" <?php if ( $wpl_enable_item_compat_tab == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?> (<?php _e('default','wplister'); ?>)</option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Show eBay Item Compatibility List as new tab on single product page.','wplister'); ?>
							</p>

							<label for="wpl-disable_sale_price" class="text_label">
								<?php echo __('Use sale price','wplister'); ?>
                                <?php wplister_tooltip('Set this to No if you want your sale prices to be ignored. You can still use a relative profile price to increase your prices by a percentage.') ?>
							</label>
							<select id="wpl-disable_sale_price" name="wpl_e2e_disable_sale_price" class="required-entry select">
								<option value="0" <?php if ( $wpl_disable_sale_price != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?> (<?php _e('default','wplister'); ?>)</option>
								<option value="1" <?php if ( $wpl_disable_sale_price == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Should sale prices be used automatically on eBay?','wplister'); ?><br>
							</p>


							<label for="wpl-option-allow_backorders" class="text_label">
								<?php echo __('Ignore backorders','wplister') ?>
                                <?php wplister_tooltip('Since eBay relies on each item having a definitive quantity, allowing backorders for WooCommerce products can cause issues when the last item is sold. WP-Lister can force WooCommerce to mark an product as out of stock when the quantity reaches zero, even with backorders allowed.') ?>
							</label>
							<select id="wpl-option-allow_backorders" name="wpl_e2e_option_allow_backorders" class="required-entry select">
								<option value="0" <?php if ( $wpl_option_allow_backorders != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?> (<?php _e('recommended','wplister'); ?>)</option>
								<option value="1" <?php if ( $wpl_option_allow_backorders == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Should a product be marked as out of stock even when it has backorders enabled?','wplister'); ?><br>
							</p>

							<label for="wpl-api_enable_auto_relist" class="text_label">
								<?php echo __('Enable API auto relist','wplister') ?>
                                <?php wplister_tooltip('When a locked product is marked out of stock via the API or CSV import, WP-Lister automatically ends the listing on eBay. Enable this option to allow WP-Lister to automatically relist the item when it is back in stock.') ?>
							</label>
							<select id="wpl-api_enable_auto_relist" name="wpl_e2e_api_enable_auto_relist" class="required-entry select">
								<option value="0" <?php if ( $wpl_api_enable_auto_relist != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?> (<?php _e('default','wplister'); ?>)</option>
								<option value="1" <?php if ( $wpl_api_enable_auto_relist == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Enable this if you update your inventory via the API or CSV import.','wplister'); ?>
								<?php echo __('This only effects locked items.','wplister'); ?><br>
							</p>

							<label for="wpl-auto_update_ended_items" class="text_label">
								<?php echo __('Auto update ended items','wplister') ?>
                                <?php wplister_tooltip('This can be helpful if you manually relisted items on eBay - which is not recommended.<br>Use it with care as it might cause performance issues and unexpected results.') ?>
							</label>
							<select id="wpl-auto_update_ended_items" name="wpl_e2e_auto_update_ended_items" class="required-entry select">
								<option value="0" <?php if ( $wpl_auto_update_ended_items != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?> (<?php _e('default','wplister'); ?>)</option>
								<option value="1" <?php if ( $wpl_auto_update_ended_items == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Automatically update item details from eBay when a listing has ended.','wplister'); ?> (beta)
							</p>

							<label for="wpl-archive_days_limit" class="text_label">
								<?php echo __('Keep archived items for','wplister'); ?>
                                <?php wplister_tooltip('Select how long archived listings should be kept. Older records are removed automatically. The default is 90 days.') ?>
							</label>
							<select id="wpl-archive_days_limit" name="wpl_e2e_archive_days_limit" class=" required-entry select">
								<option value="7"  <?php if ( $wpl_archive_days_limit == '7' ):  ?>selected="selected"<?php endif; ?>>7 days</option>
								<option value="14"  <?php if ( $wpl_archive_days_limit == '14' ):  ?>selected="selected"<?php endif; ?>>14 days</option>
								<option value="30"  <?php if ( $wpl_archive_days_limit == '30' ):  ?>selected="selected"<?php endif; ?>>30 days</option>
								<option value="60"  <?php if ( $wpl_archive_days_limit == '60' ):  ?>selected="selected"<?php endif; ?>>60 days</option>
								<option value="90"  <?php if ( $wpl_archive_days_limit == '90' ):  ?>selected="selected"<?php endif; ?>>90 days</option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Select how long archived listings should be kept.','wplister'); ?>
							</p>

						</div>
					</div>

					<?php do_action( 'wple_after_advanced_settings' ) ?>


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

	</form>


</div>
