<?php #include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">
	p.desc {
		padding-left: 14px;
	}
</style>

<div class="wrap">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/hammer-32x32.png) no-repeat;" id="wpl-icon"><br /></div>
	<h2><?php echo __('Tutorial','wplister') ?></h2>
	
	<div style="width:640px;" class="postbox-container">
		<div class="metabox-holder">
			<div class="meta-box-sortables ui-sortable">
				<form method="post" action="<?php echo $wpl_form_action; ?>">
				
					<?php if ( get_option('wplister_setup_next_step') != '0' ): ?>
					<div class="postbox" id="ConnectionSettingsBox">
						<h3 class="hndle"><span><?php echo __('Installation and Setup','wplister'); ?></span></h3>
						<div class="inside">
							<p><strong>1. Link WP-Lister to your eBay account.</strong></p>
							<p class="desc" style="display: block;">
								In order to list products in your name, WP-Lister needs access to your eBay account. Clicking the button "Connect with eBay" will take you to the eBay Sign In page, where you will be asked to grant access for WP-Lister after login.
							</p>
							<p class="desc" style="display: block;">
								After granting access you will be asked to close the window / tab again and go back to WP-Listers settings page where you click on "Fetch Token" to complete the process.
							</p>

							<p><strong>2. Configure eBay Site and Paypal</strong></p>
							<p class="desc" style="display: block;">
								Select the eBay Site you want to use and enter the Paypal address to receive payments.
							</p>

							<p><strong>3. Update eBay categories, shipping options, payment methods, etc.</strong></p>
							<p class="desc" style="display: block;">
								WP-Lister needs to download certain information from the selected site, like shipping options, payment methods and more. This includes a list of all eBay categories which require a little patience since there are more than 20.000.
							</p>
						</div>
					</div>
					<div class="submit" style="padding-top:0">
						<input type="submit" value="<?php echo __('Begin the setup','wplister'); ?>" name="submit" class="button-primary">
					</div>
					<?php endif; ?>

					<div class="postbox" id="UserQuickstartBox">
						<h3 class="hndle"><span><?php echo __('Listing items','wplister'); ?></span></h3>
						<div class="inside">

							<p><strong>1. Create your first auction profile</strong></p>
							<p class="desc" style="display: block;">
								Create a profile to define all options like listing duration, shipping costs and accepted payment methods for your listings. This profile will act as a template when you start to list items in the next step.
							</p>

							<p><strong>2. Select products and prepare your listings</strong></p>
							<p class="desc" style="display: block;">
								Switch to <a href="edit.php?post_type=<?php echo ProductWrapper::getPostType() ?>">Products</a> and select as many products as you wish. Then select "Prepare listings" from batch actions and you will be asked to select a profile for your listings.
							</p>

							<p><strong>3. List your items on eBay</strong></p>
							<p class="desc" style="display: block;">
								Now you can either verify your prepared listings (one by one, by selection or all at once) which will make sure there are no missing requirements and will fetch the listing fee from eBay.
							</p>
							<p class="desc" style="display: block;">
								Or you can list your items right away - again one by one, by selection or all at once.
							</p>
							<p class="desc" style="display: block;">
								You might want to preview you listing within WordPress and if you're not satisfied you might want to apply another profile. If you make changes to a profile and want to these changes to apply to already prepared listings, you need to re-apply the profile as well.
							</p>
							<br class="clear" />

							<p><strong>Further ressources</strong></p>
							<p class="desc" style="display: block;">
								<a href="http://www.wplab.com/plugins/wp-lister/faq/" target="_blank">FAQ</a> <br>
								<a href="http://www.wplab.com/plugins/wp-lister/documentation/" target="_blank">Documentation</a> <br>
								<a href="http://www.wplab.com/plugins/wp-lister/installing-wp-lister/" target="_blank">Installing WP-Lister</a> <br>
								<a href="http://www.wplab.com/plugins/wp-lister/screencasts/" target="_blank">Screencasts</a> <br>
							</p>
							<br class="clear" />
						</div>
					</div>

				</form>
			</div>
		</div>
	</div>

	<script type="text/javascript">
	</script>

</div>