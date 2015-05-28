<?php include_once( dirname(__FILE__).'/../common_header.php' ); ?>

<style type="text/css">

	.postbox h3 {
	    cursor: default;
	}

</style>

<div class="wrap wplister-page">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/hammer-32x32.png) no-repeat;" id="wpl-icon"><br /></div>
	<?php if ( $wpl_account->id ): ?>
	<h2><?php echo __('Edit Account','wplister') ?></h2>
	<?php else: ?>
	<h2><?php echo __('New Account','wplister') ?></h2>
	<?php endif; ?>
	
	<?php echo $wpl_message ?>

	<form method="post" action="<?php echo $wpl_form_action; ?>">

	<!--
	<div id="titlediv" style="margin-top:10px; margin-bottom:5px; width:60%">
		<div id="titlewrap">1
			<label class="hide-if-no-js" style="visibility: hidden; " id="title-prompt-text" for="title">Enter title here</label>
			<input type="text" name="wplister_title" size="30" tabindex="1" value="<?php echo $wpl_account->title; ?>" id="title" autocomplete="off">
		</div>
	</div>
	-->

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">

			<div id="postbox-container-1" class="postbox-container">
				<div id="side-sortables" class="meta-box">
					<?php include('account_edit_sidebar.php') ?>
				</div>
			</div> <!-- #postbox-container-1 -->


			<!-- #postbox-container-2 -->
			<div id="postbox-container-2" class="postbox-container">
				<div class="meta-box-sortables ui-sortable">
					

					<div class="postbox" id="GeneralSettingsBox">
						<h3><span><?php echo __('Account settings','wplister'); ?></span></h3>
						<div class="inside">

							<div id="titlediv" style="margin-bottom:5px;">
								<div id="titlewrap">
									<label for="title" class="text_label"><?php echo __('Account Title','wplister'); ?></label>
									<input type="text" name="wplister_title" size="30" value="<?php echo $wpl_account->title; ?>" id="title" autocomplete="off" style="width:65%;">
								</div>
							</div>

							<label for="wpl-site_id" class="text_label">
								<?php echo __('eBay Site','wplister'); ?>
                                <?php wplister_tooltip('Select which eBay site you want to use this account with. To work with multiple sites you need to add one account for each site.') ?>
							</label>
							<select id="wpl-site_id" name="wplister_site_id" class=" required-entry select">
								<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
								<?php foreach ($wpl_ebay_sites as $site_id => $site_title) : ?>
									<option value="<?php echo $site_id ?>" 
										<?php if ( $wpl_account->site_id == $site_id ) : ?>
											selected="selected"
										<?php endif; ?>
										><?php echo $site_title ?></option>
								<?php endforeach; ?>
							</select>
	
							<label for="wpl-account_is_active" class="text_label">
								<?php echo __('Account Status','wplister'); ?>
                                <?php wplister_tooltip('If you deactivate an account, WP-Lister will stop updating products and orders for this account.') ?>
							</label>
							<select id="wpl-account_is_active" name="wplister_account_is_active" class=" required-entry select">
								<option value="1" <?php if ( $wpl_account->active == 1 ) echo 'selected' ?> ><?php echo __('Enabled','wplister'); ?></option>
								<option value="0" <?php if ( $wpl_account->active == 0 ) echo 'selected' ?> ><?php echo __('Disabled','wplister'); ?></option>
							</select>
	
							<label for="wpl-paypal_email" class="text_label">
								<?php echo __('PayPal Account','wplister'); ?>
                                <?php wplister_tooltip('To use PayPal you need to enter your PayPal address.') ?>
							</label>
							<input type="text" name="wplister_paypal_email" id="wpl-paypal_email" value="<?php echo $wpl_account->paypal_email ?>" class="text_input" />
	
			                <?php if ( WPL_Setup::isV2() ) : ?>
							<label for="wpl-oosc_mode" class="text_label">
								<?php echo __('Out Of Stock Control','wplister'); ?>
                                <?php wplister_tooltip('This option has to be enabled in your eBay account preferences on eBay directly. Please refresh your account details in WP-Lister when you have changed the setting on eBay.') ?>
							</label>
							<select id="wpl-oosc_mode" name="wplister_oosc_mode" class=" required-entry select" disabled >
								<option value="1" <?php if ( $wpl_account->oosc_mode == 1 ) echo 'selected' ?> ><?php echo __('Active','wplister'); ?></option>
								<option value="0" <?php if ( $wpl_account->oosc_mode == 0 ) echo 'selected' ?> ><?php echo __('Inactive','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('More information about this option at','wplister'); ?>
								<a href="http://pages.ebay.com/help/sell/multiple.html#outofstock" target="_blank">http://pages.ebay.com/help/sell/multiple.html</a>
							</p>
							<?php endif; ?>

							<div class="dev_box" style="display:none">

								<label for="wpl-sandbox_mode" class="text_label">
									<?php echo __('Sandbox','wplister'); ?>
	                                <?php // wplister_tooltip('') ?>
								</label>
								<select id="wpl-sandbox_mode" name="wplister_sandbox_mode" class=" required-entry select">
									<option value="0" <?php if ( $wpl_account->sandbox_mode == 0 ) echo 'selected' ?> ><?php echo __('Production (default)','wplister'); ?></option>
									<option value="1" <?php if ( $wpl_account->sandbox_mode == 1 ) echo 'selected' ?> ><?php echo __('Sandbox enabled','wplister'); ?></option>
								</select>
								<br class="clear" />

								<label for="wpl-token" class="text_label">
									<?php echo __('eBay Token','wplister'); ?>
	                                <?php // wplister_tooltip('') ?>
								</label>
								<input type="text" name="wplister_token" id="wpl-token" value="<?php echo str_replace('"','&quot;', $wpl_account->token ); ?>" class="text_input" />
								<br class="clear" />

							</div>
							
						</div>
					</div>


					<div class="postbox dev_box" id="DebugInfoBox" style="display:none">
						<h3><span><?php echo __('Debug Information','wplister'); ?></span></h3>
						<div class="inside">

							<?php
								echo "<pre>";print_r($wpl_account->user_details);echo"</pre>";
							?>
	
						</div>
					</div>


						
				</div> <!-- .meta-box-sortables -->
			</div> <!-- #postbox-container-1 -->



		</div> <!-- #post-body -->
		<br class="clear">
	</div> <!-- #poststuff -->

	</form>


	<?php if ( get_option('wplister_log_level') > 6 ): ?>
	<pre><?php print_r($wpl_account); ?></pre>
	<?php endif; ?>


	<script type="text/javascript">

		jQuery( document ).ready( function () {

		});	
	
	</script>

</div>



	
