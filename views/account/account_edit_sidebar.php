<style type="text/css">

	#poststuff #side-sortables .postbox input.text_input,
	#poststuff #side-sortables .postbox select.select {
	    width: 45%;
	}
	#poststuff #side-sortables .postbox label.text_label {
	    width: 50%;
	}

	#poststuff #side-sortables .postbox .inside p.desc {
		margin-left: 2%;
	}

</style>




					<!-- first sidebox -->
					<div class="postbox" id="submitdiv">
						<!--<div title="Click to toggle" class="handlediv"><br></div>-->
						<h3><span><?php echo __('Update','wplister'); ?></span></h3>
						<div class="inside">

							<div id="submitpost" class="submitbox">

								<div id="misc-publishing-actions">
									<div class="misc-pub-section">
										<?php if ( $wpl_account->id == get_option('wplister_default_account_id') ) : ?>
										<p>
											<?php echo __('This is your current default account.','wplister') ?>
										</p>
										<?php endif; ?>
										<p>
											<?php echo __('Please do not change any account details except account title and PayPal address.','wplister') ?>
											
										</p>
									</div>
								</div>

								<div id="major-publishing-actions">
									<div id="publishing-action">
										<input type="hidden" name="action" value="save_account" />
										<input type="hidden" name="wplister_account_id" value="<?php echo $wpl_account->id; ?>" />
										<input type="hidden" name="return_to" value="<?php echo @$_GET['return_to']; ?>" />
										<input type="submit" value="<?php echo __('Update','wplister'); ?>" id="publish" class="button-primary" name="save">
									</div>
									<div class="clear"></div>
								</div>

							</div>

						</div>
					</div>

					<div class="postbox" id="AccountInfoBox">
						<h3><span><?php echo __('Account Details','wplister'); ?></span></h3>
						<div class="inside">
							<p>
								<?php if ( is_object( $wpl_account->user_details ) ) : ?>
								<table style="width:95%">
									<tr><td><?php echo __('User ID','wplister') . ':</td><td>' . $wpl_account->user_details->UserID ?></td></tr>
									<tr><td><?php echo __('Status','wplister') . ':</td><td>' . $wpl_account->user_details->Status ?></td></tr>
									<tr><td><?php echo __('Score','wplister') . ':</td><td>' . $wpl_account->user_details->FeedbackScore ?></td></tr>
									<tr><td><?php echo __('Site','wplister') . ':</td><td>' . $wpl_account->user_details->Site ?></td></tr>
									<?php if ( $wpl_account->user_details->SellerBusinessType ) : ?>
									<tr><td><?php echo __('Type','wplister') . ':</td><td>' . $wpl_account->user_details->SellerBusinessType ?></td></tr>
									<?php endif; ?>
									<?php if ( $wpl_account->user_details->StoreOwner ) : ?>
									<tr><td><?php echo __('Store','wplister') . ':</td><td>' ?><a href="<?php echo $wpl_account->user_details->StoreURL ?>" target="_blank"><?php echo __('visit store','wplister') ?></a></td></tr>
									<?php endif; ?>
									<tr><td><?php echo __('Valid until','wplister') . ':</td><td>' ?><?php echo mysql2date( get_option('date_format'), $wpl_account->valid_until ) ?></td></tr>
								</table>												
								<?php else : ?>
									<?php echo __('No details available','wplister') ?>
								<?php endif; ?>
							</p>
						</div>
					</div>

					<div class="postbox" id="ResetTokenBox">
						<h3><span><?php echo __('Refresh eBay Token','wplister'); ?></span></h3>
						<div class="inside">

							<p>
								<?php echo sprintf( __('Your token will expire on %s.','wplister'), mysql2date( get_option('date_format'), $wpl_account->valid_until ) ) ?><br>
								<?php echo __('You should refresh your token before that date by following the steps below.','wplister') ?>
							</p>

							<p>
								<?php echo __('Click "Connect with eBay" to sign in to eBay and grant access for WP-Lister','wplister') ?>
							</p>
							<p>
								<a id="btn_connect" href="<?php echo $wpl_auth_url; ?>" class="button-primary" target="_blank">Connect with eBay</a>
							</p>
							<p>
								<small>This will open the eBay Sign In page in a new window.</small>
								<small>Please sign in, grant access for WP-Lister and close the new window to come back here and click the button below.</small>						
							</p>
							<p>
								<?php echo __('After linking WP-Lister with your eBay account, click here to fetch your token','wplister') ?>
							</p>
							<p>
								<a id="btn_fetch_token" href="<?php echo $wpl_form_action; ?>&amp;action=wplister_fetch_ebay_token&amp;account_id=<?php echo $wpl_account->id ?>" class="button-secondary"><?php echo __('Fetch eBay Token','wplister') ?></a>
								<!-- <input type="submit" value="<?php echo __('Fetch eBay Token','wplister') ?>" name="submit" class="button"> -->
							</p>

						</div>
					</div>

					<!--
					<div class="postbox" id="HelpBox">
						<h3><span><?php echo __('Help','wplister'); ?></span></h3>
						<div class="inside">
							<p>
								Please don't change any account details other than the account title.
							</p>
						</div>
					</div>
					-->

