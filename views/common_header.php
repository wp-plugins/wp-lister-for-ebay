
<!-- sandbox notice -->
<?php if( get_option('wplister_sandbox_enabled') == '1' ) : ?>

	<script type="text/javascript">
		jQuery( document ).ready( function () {
		
		    jQuery('#screen-meta-links').append(
		        '<div id="wpl-sandbox-reminder-wrap" class="hide-if-no-js screen-meta-toggle">' +
		            '<a href="#" id="wpl-sandbox-reminder-link" class="show-settings"><?php echo __('Sandbox enabled','wplister'); ?></a>' +
		        '</div>'
		    );
	
		});

	</script>

<?php endif; ?>



<!-- jobs window -->
<div id="jobs_window_container" style="display:none">
	<div id="jobs_window">
		
		<h2 id="jobs_title">Jobs</h2>
		
		<div id="progressbar"><span class="caption">loading...</span></div>			
		<div id="jobs_message">warming up...</div>
		
		<div id="jobs_log">
			<span></span>
		</div>
		
		<div id="job_bottom_notice" style="">
			<?php echo __("Please don't close this window until all tasks are completed.",'wplister') ?>
		</div>
		<div class="submit" style="float:right; padding: 10px 0 0 0;">
			<a class="btn_close button-secondary"><?php echo __('Close window','wplister') ?></a>
		</div>

	</div>
</div>


<script type="text/javascript">
	
	var wplister_url= '<?php echo WPLISTER_URL; ?>/';
	var wplister_ajax_error_handling = "<?php echo get_option( 'wplister_ajax_error_handling', 'halt' ); ?>";

	// on page load
	jQuery( document ).ready(
		function () {
	
			// init JobRunner
			WpLister.JobRunner.init();

			// btn_update_ebay_data
			jQuery('#btn_update_ebay_data').click( function(event) {
				WpLister.JobRunner.runJob( 'updateEbayData', 'Loading data from eBay...' );
			});

			// btn_verify_all_prepared_items
			jQuery('.btn_verify_all_prepared_items').click( function(event) {
				WpLister.JobRunner.runJob( 'verifyAllPreparedItems', 'Verifying items...' );
			});

			// btn_publish_all_verified_items
			jQuery('.btn_publish_all_verified_items').click( function(event) {
				WpLister.JobRunner.runJob( 'publishAllVerifiedItems', 'Listing items...' );
			});

			// btn_revise_all_changed_items
			jQuery('.btn_revise_all_changed_items').click( function(event) {
				WpLister.JobRunner.runJob( 'reviseAllChangedItems', 'Revising items...' );
			});
			jQuery('.btn_revise_all_changed_items_reminder').click( function(event) {
				WpLister.JobRunner.runJob( 'reviseAllChangedItems', 'Revising items...' );
			});

			// btn_update_all_published_items
			jQuery('.btn_update_all_published_items').click( function(event) {
				WpLister.JobRunner.runJob( 'updateAllPublishedItems', 'Updating items...' );
			});

		}
	);

</script>
