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

							<?php echo $wpl_content_help_setup ?>

						</div>
					</div>
					<div class="submit" style="padding-top:0">
						<input type="submit" value="<?php echo __('Begin the setup','wplister'); ?>" name="submit" class="button-primary">
					</div>
					<?php endif; ?>

					<div class="postbox" id="UserQuickstartBox">
						<h3 class="hndle"><span><?php echo __('Listing items','wplister'); ?></span></h3>
						<div class="inside">

							<?php echo $wpl_content_help_listing ?>

						</div>
					</div>

					<div class="postbox" id="UserQuickstartBox">
						<h3 class="hndle"><span><?php echo __('Ressources','wplister'); ?></span></h3>
						<div class="inside">

							<p><strong><?php echo __('Helpful links','wplister'); ?></strong></p>
							<p class="desc" style="display: block;">
								<a href="http://www.wplab.com/plugins/wp-lister/faq/" target="_blank"><?php echo __('FAQ','wplister'); ?></a> <br>
								<a href="http://www.wplab.com/plugins/wp-lister/documentation/" target="_blank"><?php echo __('Documentation','wplister'); ?></a> <br>
								<a href="http://www.wplab.com/plugins/wp-lister/installing-wp-lister/" target="_blank"><?php echo __('Installing WP-Lister','wplister'); ?></a> <br>
								<a href="http://www.wplab.com/plugins/wp-lister/screencasts/" target="_blank"><?php echo __('Screencasts','wplister'); ?></a> <br>
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