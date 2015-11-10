<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">
	
	div.tablenav.top { display: none; }

	th.column-site,
	th.column-user_name {
		width: 20%;
	}
	th.column-valid_until {
		width: 20%;
	}

	#AuthSettingsBox ol li {
		margin-bottom: 25px;
	}
	#AuthSettingsBox ol li > small {
		margin-left: 4px;
	}

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

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">

			<div id="postbox-container-1" class="postbox-container">
				<div id="side-sortables" class="meta-box">


					<!-- first sidebox -->
					<div class="postbox" id="submitdiv">
						<!--<div title="Click to toggle" class="handlediv"><br></div>-->
						<h3><span><?php echo __('Account Status','wplister'); ?></span></h3>
						<div class="inside">

							<div id="submitpost" class="submitbox">

								<div id="misc-publishing-actions">
									<div class="misc-pub-section">
										<?php if ( sizeof( $wpl_ebay_accounts ) == 0 ) : ?>
											<p><?php echo __('WP-Lister is not linked to your eBay account yet.','wplister') ?></p>
										<?php elseif ( ! $wpl_default_account ) : ?>
											<p><?php echo __('You need to select a default account.','wplister') ?></p>
										<?php else : ?>
											<p>
												<?php echo sprintf( __('Your default account is <b>%s</b>.','wplister'), WPLE_eBayAccount::getAccountTitle( $wpl_default_account ) ) ?>
											</p>
											<p>
												<?php echo __('The default account will always be used by WP-Lister unless specified otherwise.','wplister') ?>
											</p>
										<?php endif; ?>


									</div>
								</div>

								<div id="major-publishing-actions">
									<div id="publishing-action">
										<!-- <input type="submit" value="<?php echo __('Save Settings','wplister'); ?>" id="save_settings" class="button-primary" name="save"> -->
									</div>
									<div class="clear"></div>
								</div>

							</div>

						</div>
					</div>

					<?php if ( sizeof( $wpl_ebay_accounts ) > 0 ) : ?>
					<div class="postbox" id="EbaySitesBox">
						<h3><span><?php echo __('Active eBay Sites','wplister'); ?></span></h3>
						<div class="inside">
							<p>
								<?php foreach ($wpl_active_ebay_sites as $site) : ?>
									<?php
								        // $button = '<a href="#" data-site_id="'.$site->id.'" data-account_id="" class="btn_update_ebay_data_for_site button button-small" style="float:right;">'.__('Refresh','wplister').'</a>';
								        // echo $button;
						        		$last_update = $site->last_refresh ? ( human_time_diff( strtotime( $site->last_refresh ) ) . ' ago' ) : '<span style="color:darkred; font-weight:bold;">never</span>'; 
								    ?>

									&bull; eBay <?php echo $site->title ?> <br>
									&nbsp;&nbsp; <small>Last Refresh: <?php echo $last_update ?> </small> <br>
								<?php endforeach; ?>
								<?php
									// echo "<pre>";print_r($wpl_ebay_sites);echo"</pre>";#die();
								?>
							</p>
						</div>
					</div>
					<?php endif; ?>

					<div class="postbox dev_box" id="DevToolsBox" style="display:none">
						<h3><span><?php echo __('Developer','wplister'); ?></span></h3>
						<div class="inside">
							<p>
								<a href="<?php echo $wpl_form_action ?>&action=wple_add_dev_account" class="button-secondary" >Add Developer Account</a>
							</p>
							<p>
								This is only intended for developers.
							</p>
						</div>
					</div>

					<?php if ( WPL_Setup::isV2() || ( sizeof( $wpl_ebay_accounts ) == 0 ) ) : ?>
					<div class="postbox" id="HelpInfoBox">
						<h3 class="hndle"><span><?php echo __('Help','wplister') ?></span></h3>
						<div class="inside">
							
							<p>
								<h4><?php _e('Adding your eBay account','wplister') ?></h4>
								<ol>
									<li><?php _e('Select an eBay site','wplister') ?></li>
									<li><?php _e('Follow the instructions below','wplister') ?></li>
								</ol>
							</p>

						</div>
					</div>
					<?php endif; ?>

				</div>
			</div> <!-- #postbox-container-1 -->


			<!-- #postbox-container-2 -->
			<div id="postbox-container-2" class="postbox-container">
				<div class="meta-box-sortables ui-sortable">
					
				<?php if ( sizeof( $wpl_ebay_accounts ) == 0 ) : ?>
				
					<div class="postbox" id="AuthSettingsBox">
						<h3 class="hndle"><span><?php echo __('Welcome','wplister') ?></span></h3>
						<div class="inside">
							<p>
								<strong><?php echo __('Before you can begin listing your products on eBay, you need to set up your eBay account.','wplister') ?></strong>
							</p>
							<p>
								<?php echo __('Please select the eBay site you want to use and follow the instructions that will appear below.','wplister') ?>
							</p>
						</div>
					</div>

				<?php else: ?>

					<!-- show accounts table -->
				    <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
				    <form id="accounts-filter" method="post" action="<?php echo $wpl_form_action; ?>" >
				        <!-- For plugins, we also need to ensure that the form posts back to our current page -->
				        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				        <!-- Now we can render the completed list table -->
				        <?php $wpl_accountsTable->display() ?>
				    </form>

					<div class="postbox" id="AccountsBox" style="display:none">
						<h3 class="hndle"><span><?php echo __('Accounts','wplister') ?></span></h3>
						<div class="inside">

						</div>
					</div>

				<?php endif; // $wpl_ebay_accounts ?>

				<?php if ( WPL_Setup::isV2() || ( sizeof( $wpl_ebay_accounts ) == 0 ) ) : ?>
					<?php 
						$max_accounts = apply_filters( 'wple_max_number_of_accounts', 12 );
						if ( sizeof( $wpl_ebay_accounts ) < $max_accounts ) {
							require_once('account/settings_add_account.php');
						}
					?>
				<?php endif; ?>

				</div> <!-- .meta-box-sortables -->
			</div> <!-- #postbox-container-1 -->


		</div> <!-- #post-body -->
		<br class="clear">
	</div> <!-- #poststuff -->



	<?php if ( isset( $_REQUEST['debug'] ) ) { echo "<pre>";print_r($wpl_ebay_accounts);echo"</pre>"; } ?>
	<?php #echo "<pre>";print_r($wpl_ebay_markets);echo"</pre>"; ?>


	<script type="text/javascript">
		jQuery( document ).ready(
			function () {

				// account details button
				jQuery('.wplister_btn_edit_account').click( function( ) {					
					jQuery( this ).nextAll('.ebay_account_details').slideToggle(300);
					return false;
				});

				// ask again before deleting items
				jQuery('a.delete').on('click', function() {
					return confirm("<?php echo __('Are you sure you want to remove this account from WP-Lister?','wplister') ?>");
				})
				// ask again before deleting items
				jQuery('.row-actions .delete_account a').on('click', function() {
					return confirm("<?php echo __('Are you sure you want to remove this account from WP-Lister?','wplister') ?>");
				})

			}
		);
	
	</script>


</div>