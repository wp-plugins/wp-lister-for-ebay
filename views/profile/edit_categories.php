<style type="text/css">

	#ebay_categories_tree_wrapper,
	#store_categories_tree_wrapper {
		/*max-height: 320px;*/
		/*margin-left: 35%;*/
		overflow: auto;
		width: 65%;
		display: none;
	}

	#EbayCategorySelectionBox span.texyt_input,
	#StoreCategorySelectionBox span.texyt_input {
		line-height: 25px;
	}

	#EbayCategorySelectionBox .category_row_actions,
	#StoreCategorySelectionBox .category_row_actions {
		position: absolute;
		top: 0;
		right: 0;
	}


	a.link_select_category {
		float: right;
		padding-top: 3px;
		text-decoration: none;
	}
	a.link_remove_category {
		padding-left: 3px;
		text-decoration: none;
	}
	
</style>

					<?php
						// fetch full category names
						$item_details['ebay_category_1_name']  = EbayCategoriesModel::getFullEbayCategoryName( $item_details['ebay_category_1_id'] );
						$item_details['ebay_category_2_name']  = EbayCategoriesModel::getFullEbayCategoryName( $item_details['ebay_category_2_id'] );
						$item_details['store_category_1_name'] = EbayCategoriesModel::getFullStoreCategoryName( $item_details['store_category_1_id'] );
						$item_details['store_category_2_name'] = EbayCategoriesModel::getFullStoreCategoryName( $item_details['store_category_2_id'] );
					?>

					<div class="postbox" id="EbayCategorySelectionBox">
						<h3><span><?php echo __('eBay categories','wplister'); ?></span></h3>
						<div class="inside">

							<div style="position:relative; margin: 0 5px;">
								<label for="wpl-text-ebay_category_1_name" class="text_label"><?php echo __('Category','wplister'); ?> 1: <?php echo WPLISTER_LIGHT ? '*' : '' ?></label>
								<input type="hidden" name="wpl_e2e_ebay_category_1_id" id="ebay_category_id_1" value="<?php echo $item_details['ebay_category_1_id']; ?>" class="" />
								<span  id="ebay_category_name_1" class="text_input" style="width:45%;float:left;"><?php echo $item_details['ebay_category_1_name']; ?></span>
								<div class="category_row_actions">
									<input type="button" value="<?php echo __('select','wplister'); ?>" class="button-secondary btn_select_ebay_category" onclick="">
									<input type="button" value="<?php echo __('remove','wplister'); ?>" class="button-secondary btn_remove_ebay_category" onclick="">
								</div>
							</div>
							
							<div style="position:relative; margin: 0 5px; clear:both">
								<label for="wpl-text-ebay_category_2_name" class="text_label"><?php echo __('Category','wplister'); ?> 2:</label>
								<input type="hidden" name="wpl_e2e_ebay_category_2_id" id="ebay_category_id_2" value="<?php echo $item_details['ebay_category_2_id']; ?>" class="" />
								<span  id="ebay_category_name_2" class="text_input" style="width:45%;float:left;"><?php echo $item_details['ebay_category_2_name']; ?></span>
								<div class="category_row_actions">
									<input type="button" value="<?php echo __('select','wplister'); ?>" class="button-secondary btn_select_ebay_category" onclick="">
									<input type="button" value="<?php echo __('remove','wplister'); ?>" class="button-secondary btn_remove_ebay_category" onclick="">
								</div>
							</div>
							<div class="clear"></div>

							<?php if ( @$wpl_default_ebay_category_id && ! $item_details['ebay_category_1_id'] ) : ?>
							<div style="position:relative; margin: 5px 10px; clear:both">
								<?php echo __('Conditions and item specifics are based on the category','wplister'); ?>: <?php echo EbayCategoriesModel::getCategoryName( $wpl_default_ebay_category_id ) ?>
							</div>
							<?php endif; ?>

						</div>
					</div>

					<div class="postbox" id="StoreCategorySelectionBox">
						<h3><span><?php echo __('Store categories','wplister'); ?></span></h3>
						<div class="inside">

							<div style="position:relative; margin: 0 5px;">
								<label for="wpl-text-store_category_1_name" class="text_label"><?php echo __('Store category','wplister'); ?> 1:</label>
								<input type="hidden" name="wpl_e2e_store_category_1_id" id="store_category_id_1" value="<?php echo $item_details['store_category_1_id']; ?>" class="" />
								<span  id="store_category_name_1" class="text_input" style="width:45%;float:left;"><?php echo $item_details['store_category_1_name']; ?></span>
								<div class="category_row_actions">
									<input type="button" value="<?php echo __('select','wplister'); ?>" class="button-secondary btn_select_store_category" onclick="">
									<input type="button" value="<?php echo __('remove','wplister'); ?>" class="button-secondary btn_remove_store_category" onclick="">
								</div>
							</div>
							
							<div style="position:relative; margin: 0 5px; clear:both">
								<label for="wpl-text-store_category_2_name" class="text_label"><?php echo __('Store category','wplister'); ?> 2:</label>
								<input type="hidden" name="wpl_e2e_store_category_2_id" id="store_category_id_2" value="<?php echo $item_details['store_category_2_id']; ?>" class="" />
								<span  id="store_category_name_2" class="text_input" style="width:45%;float:left;"><?php echo $item_details['store_category_2_name']; ?></span>
								<div class="category_row_actions">
									<input type="button" value="<?php echo __('select','wplister'); ?>" class="button-secondary btn_select_store_category" onclick="">
									<input type="button" value="<?php echo __('remove','wplister'); ?>" class="button-secondary btn_remove_store_category" onclick="">
								</div>
							</div>
							<div class="clear"></div>

						</div>
					</div>


			<!-- hidden ajax categories tree -->
			<div id="ebay_categories_tree_wrapper">
				<div id="ebay_categories_tree_container"></div>
			</div>
			<!-- hidden ajax categories tree -->
			<div id="store_categories_tree_wrapper">
				<div id="store_categories_tree_container"></div>
			</div>


	<script type="text/javascript">

		/* recusive function to gather the full category path names */
        function wpl_getCategoryPathName( pathArray, depth ) {
			var pathname = '';
			if (typeof depth == 'undefined' ) depth = 0;

        	// get name
	        if ( depth == 0 ) {
	        	var cat_name = jQuery('[rel=' + pathArray.join('\\\/') + ']').html();
	        } else {
		        var cat_name = jQuery('[rel=' + pathArray.join('\\\/') +'\\\/'+ ']').html();
	        }

	        // console.log('path...: ', pathArray.join('\\\/') );
	        // console.log('catname: ', cat_name);
	        // console.log('pathArray: ', pathArray);

	        // strip last (current) item
	        popped = pathArray.pop();
	        // console.log('popped: ',popped);

	        // call self with parent path
	        if ( pathArray.length > 2 ) {
		        pathname = wpl_getCategoryPathName( pathArray, depth + 1 ) + ' &raquo; ' + cat_name;
	        } else if ( pathArray.length > 1 ) {
		        pathname = cat_name;
	        }

	        return pathname;

        }

		jQuery( document ).ready(
			function () {


				// select ebay category button
				jQuery('input.btn_select_ebay_category').click( function(event) {
					// var cat_id = jQuery(this).parent()[0].id.split('sel_ebay_cat_id_')[1];
					e2e_selecting_cat = ('ebay_category_name_1' == jQuery(this).parent().parent().first().find('.text_input')[0].id) ? 1 : 2;

					var tbHeight = tb_getPageSize()[1] - 120;
					var tbURL = "#TB_inline?height="+tbHeight+"&width=500&inlineId=ebay_categories_tree_wrapper"; 
        			tb_show("Select a category", tbURL);  
					
				});
				// remove ebay category button
				jQuery('input.btn_remove_ebay_category').click( function(event) {
					var cat_id = ('ebay_category_name_1' == jQuery(this).parent().parent().first().find('.text_input')[0].id) ? 1 : 2;
					
					jQuery('#ebay_category_id_'+cat_id).attr('value','');
					jQuery('#ebay_category_name_'+cat_id).html('');
				});
		
				// select store category button
				jQuery('input.btn_select_store_category').click( function(event) {
					// var cat_id = jQuery(this).parent()[0].id.split('sel_store_cat_id_')[1];
					e2e_selecting_cat = ('store_category_name_1' == jQuery(this).parent().parent().first().find('.text_input')[0].id) ? 1 : 2;

					var tbHeight = tb_getPageSize()[1] - 120;
					var tbURL = "#TB_inline?height="+tbHeight+"&width=500&inlineId=store_categories_tree_wrapper"; 
        			tb_show("Select a category", tbURL);  
					
				});
				// remove store category button
				jQuery('input.btn_remove_store_category').click( function(event) {
					var cat_id = ('store_category_name_1' == jQuery(this).parent().parent().first().find('.text_input')[0].id) ? 1 : 2;
					
					jQuery('#store_category_id_'+cat_id).attr('value','');
					jQuery('#store_category_name_'+cat_id).html('');
				});
		
		
				// jqueryFileTree 1 - ebay categories
			    jQuery('#ebay_categories_tree_container').fileTree({
			        root: '/0/',
			        script: ajaxurl+'?action=e2e_get_ebay_categories_tree',
			        expandSpeed: 400,
			        collapseSpeed: 400,
			        loadMessage: 'loading eBay categories...',
			        multiFolder: false
			    }, function(catpath) {

					// get cat id from full path
			        var cat_id = catpath.split('/').pop(); // get last item - like php basename()

			        // get name of selected category
			        var cat_name = '';

			        var pathname = wpl_getCategoryPathName( catpath.split('/') );
					// console.log('pathname: ',pathname);
			        
			        // update fields
			        jQuery('#ebay_category_id_'+e2e_selecting_cat).attr( 'value', cat_id );
			        jQuery('#ebay_category_name_'+e2e_selecting_cat).html( pathname );
			        
			        // close thickbox
			        tb_remove();

			        if ( e2e_selecting_cat == 1 ) {
			        	updateItemSpecifics();
			        	updateItemConditions();
			        }

			    });
	
				// jqueryFileTree 2 - store categories
			    jQuery('#store_categories_tree_container').fileTree({
			        root: '/0/',
			        script: ajaxurl+'?action=e2e_get_store_categories_tree',
			        expandSpeed: 400,
			        collapseSpeed: 400,
			        loadMessage: 'loading store categories...',
			        multiFolder: false
			    }, function(catpath) {

					// get cat id from full path
			        var cat_id = catpath.split('/').pop(); // get last item - like php basename()

			        // get name of selected category
			        var cat_name = '';

			        var pathname = wpl_getCategoryPathName( catpath.split('/') );
					// console.log('pathname: ',pathname);
			        
			        // update fields
			        jQuery('#store_category_id_'+e2e_selecting_cat).attr( 'value', cat_id );
			        jQuery('#store_category_name_'+e2e_selecting_cat).html( pathname );
			        
			        // close thickbox
			        tb_remove();

			    });
	


			}
		);
	
	
	</script>
