<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">

/*	td.column-price {
		text-align: right;
	}
	th.column-auction_title {
		width: 25%;
	}
*/

	#message .checkbox_label {
		vertical-align:  text-bottom;
	}

	/* hide some columns which are useless on prepare page */
	.tablenav .actions,
	.wp-list-table .row-actions,
	.wp-list-table .check-column,
	.wp-list-table .column-cb,
	.wp-list-table .column-quantity,
	.wp-list-table .column-quantity_sold,
	.wp-list-table .column-fees,
	.wp-list-table .column-date_published,
	.wp-list-table .column-end_date,
	.wp-list-table .column-profile,
	.wp-list-table .column-template,
	.wp-list-table .column-status {
		display: none;
	}


	/* style profile selection */

	.option_section input {
		vertical-align: bottom;
	}
	
	.option_section label {
		/*font-weight: bold;*/
		font-size: 1.3em;
		margin-left: 5px;
	}
	
	.option_section p.desc {
		/*font-size: smaller;*/
		/*font-style: italic;*/
		margin: 0;
		margin-top: 5px;
		margin-left: 18px;
	}

	.option_section {
		border: 1px solid #eee;
		border-radius: 8px 8px 8px 8px;
		margin-bottom: 8px;
		padding: 15px;

		background-color: rgba(255, 255, 255, 0.5);


		width: 50%;
		min-width: 420px;
	}


	.option_section:hover {
	    /*box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);*/
		background-color: #ffffff;
		cursor: pointer;
	}
	.option_section.active {
		background-color: #fff;
	    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
	}

	

</style>

<div class="wrap">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/hammer-32x32.png) no-repeat;" id="wpl-icon"><br /></div>
	<h2><?php echo __('Choose a listing profile','wplister') ?></h2>
	<?php #echo $wpl_message ?>
	
	<div id="message" class="updated fade below-h2"  style="padding-left: 15px; padding-top: 5px; display:block !important;">
    <form id="profiles-filter" method="get" action="<?php echo $wpl_form_action; ?>" >
        <input type="hidden" name="action" value="apply_listing_profile" />
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
		
		<p>
			<?php echo __('Please select a profile for the products shown below:','wplister') ?>

			<?php foreach ($wpl_profiles as $profile) : ?>
			<?php $p_id = $profile['profile_id'] ?>
			<div class="option_section <?php if ( $wpl_last_selected_profile == $p_id ): ?>active<?php endif; ?>" id="section-wpl-selected-profile">
				<input type="radio" id="wpl-selected-profile_<?php echo $p_id ?>" name="wpl_e2e_profile_to_apply" 
					value="<?php echo $p_id ?>" class="profile_radio_btn"
					<?php if ( $wpl_last_selected_profile == $p_id ): ?>checked="checked"<?php endif; ?> 
				/>
				<label for="wpl-selected-profile_<?php echo $p_id ?>"><?php echo $profile['profile_name'] ?></label>
				<br class="clear" />
				<?php if ( trim( $profile['profile_description'] ) != '' ) : ?>
				<p class="desc" style="display: block;"><?php echo $profile['profile_description'] ?></p>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>

			<br style="clear:both;">
			
			<input type="submit" value="<?php echo __('Apply Profile','wplister') ?>" name="submit" class="button-primary" style="margin-right:10px;">

			<input type="checkbox" name="wpl_e2e_verify_after_profile" id="wpl_e2e_verify_after_profile" value="1">
			<label for="wpl_e2e_verify_after_profile" class="checkbox_label"><?php echo __('Verify all selected items with eBay now.','wplister') ?></label>


		</p>

    </form>
	</div>

	<!-- show profiles table -->
    <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
    <form id="profiles-filter" method="get" action="<?php echo $wpl_form_action; ?>" >
        <!-- For plugins, we also need to ensure that the form posts back to our current page -->
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <!-- Now we can render the completed list table -->
        <?php $wpl_listingsTable->display() ?>
    </form>

	<br style="clear:both;"/>

	<!--
	<p>
		debug info below:
	</p>
	-->

	<?php if ( get_option('wplister_log_level') > 6 ): ?>
	<pre><?php print_r($wpl_profiles); ?></pre>
	<?php endif; ?>


	<script type="text/javascript">
		jQuery( document ).ready(
			function () {
		
				// if no profile is selected, select the first one
				if ( jQuery( 'input.profile_radio_btn:checked' ).length == 0 ) {
					jQuery( 'input.profile_radio_btn' ).first().attr('checked','checked');
				}

				jQuery( 'input:checked' ).parents( 'div.option_section' ).addClass( 'active' );
				
				jQuery( '.option_section' ).bind( 'click', onSectionClick );
				jQuery( '.option_section input[type=checkbox],.option_section label' ).bind( 'click',
					function ( inoEvent ) {
						var $this = jQuery( this );
						var oParent = $this.parents( 'div.option_section' );

						var oInput = jQuery( 'input[type=checkbox]', oParent );
						if ( oInput.is( ':checked' ) ) {
							oParent.addClass( 'active' );
						}
						else {
							oParent.removeClass( 'active' );
						}
					}
				);
	

			}
		);
	
		function onSectionClick( inoEvent ) {
			var oDiv = jQuery( inoEvent.currentTarget );
			if ( inoEvent.target.tagName && inoEvent.target.tagName.match( /input|label/i ) ) {
				return true;
			}
	
			var oEl = oDiv.find( 'input[type=checkbox]' );
			if ( oEl.length > 0 ) {
				if ( oEl.is( ':checked' ) ) {
					oEl.removeAttr( 'checked' );
					oDiv.removeClass( 'active' );
				}
				else {
					oEl.attr( 'checked', 'checked' );
					oDiv.addClass( 'active' );
				}
			}
	
			var oEl = oDiv.find( 'input[type=radio]' );
			if ( oEl.length > 0 && !oEl.is( ':checked' ) ) {
				oEl.attr( 'checked', 'checked' );
				oDiv.addClass( 'active' ).siblings().removeClass( 'active' );
			}
		}
	
	</script>

</div>