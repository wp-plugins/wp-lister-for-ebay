<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<script src="<?php echo $wpl_plugin_url; ?>/js/ace/ace.js" type="text/javascript" charset="utf-8"></script>
<script src="<?php echo $wpl_plugin_url; ?>/js/ace/mode-scss.js" type="text/javascript" charset="utf-8"></script>
<script src="<?php echo $wpl_plugin_url; ?>/js/ace/mode-php.js" type="text/javascript" charset="utf-8"></script>
<script src="<?php echo $wpl_plugin_url; ?>/js/ace/mode-html.js" type="text/javascript" charset="utf-8"></script>
<script src="<?php echo $wpl_plugin_url; ?>/js/ace/theme-chrome.js" type="text/javascript" charset="utf-8"></script>

<style type="text/css">

	/* sidebar */
	#side-sortables .postbox input.text_input,
	#side-sortables .postbox select.select {
	    width: 30%;
	}
	#side-sortables .postbox label.text_label {
	    width: 66%;
	}

	#side-sortables .postbox .inside p.desc {
		/*margin-left: 2%;*/
	}

	#side-sortables .postbox .inside h4 {
		/*margin-left: 1%;*/
		margin-top: 1em;
		margin-bottom: 0.5em;
	}


	/* edit styles */
	#html_editor,
	#styles_editor,
	#header_editor,
	#footer_editor,
	#functs_editor {
		height: 240px;
		width: 100%;
		position: relative;
		border: 1px solid #ccc;
	}
	#html_editor,
	#styles_editor {
		height: 420px;
	}
	
	/* hide warnings in css editor */
	#styles_editor .ace_gutter-cell.ace_warning {
		background-image: none;
	}	


	.postbox h3 {
	    cursor: default;
	}
		
	/* backwards compatibility to WP 3.3 */
	#poststuff #post-body.columns-2 {
	    margin-right: 300px;
	}
	#poststuff #post-body {
	    padding: 0;
	}
	#post-body.columns-2 #postbox-container-1 {
	    float: right;
	    margin-right: -300px;
	    width: 280px;
	}
	#poststuff .postbox-container {
	    width: 100%;
	}
	#major-publishing-actions {
	    border-top: 1px solid #F5F5F5;
	    clear: both;
	    margin-top: -2px;
	    padding: 10px 10px 8px;
	}
	#post-body .misc-pub-section {
	    max-width: 100%;
	    border-right: none;
	}
</style>



<div class="wrap wplister-page">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/hammer-32x32.png) no-repeat;" id="wpl-icon"><br /></div>
	<?php if ( $wpl_item['template_id'] ): ?>
	<h2><?php echo __('Edit Template','wplister') ?></h2>
	<?php else: ?>
	<h2><?php echo __('New Template','wplister') ?></h2>
	<?php endif; ?>
	
	<?php echo $wpl_message ?>

	<form method="post" action="<?php echo $wpl_form_action; ?>">
	<?php if ( $wpl_add_new_template ) : ?>
		<input type="hidden" name="wpl_add_new_template" value="<?php echo $wpl_add_new_template ?>" />
	<?php endif; ?>

	<!--
	<div id="titlediv" style="margin-top:10px; margin-bottom:5px; width:60%">
		<div id="titlewrap">
			<label class="hide-if-no-js" style="visibility: hidden; " id="title-prompt-text" for="title">Enter title here</label>
			<input type="text" name="wpl_e2e_template_name" size="30" tabindex="1" value="<?php echo $wpl_item['template_name']; ?>" id="title" autocomplete="off">
		</div>
	</div>
	-->


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

									<?php if ( ! $wpl_add_new_template ): ?>

										<div class="misc-pub-section">
										<!-- optional save and apply to all prepared listings already using this template -->
										<?php if ( count($wpl_prepared_listings) > -1 ): ?>
											<p><?php printf( __('There are %s prepared, %s verified and %s published items using this template.','wplister'), count($wpl_prepared_listings), count($wpl_verified_listings), count($wpl_published_listings) ) ?></p>
										<?php else: ?>
											<p>There are no prepared items using this template.</p>
										<?php endif; ?>

											<p>To update already published items you need to revise them after saving the template.</p>
										</div>

										<!--
										<div class="misc-pub-section">
											<p>
												<?php echo __('You can find the theme files in this folder:','wplister'); ?>
												<em><?php echo $wpl_template_location; ?></em>
											</p>
										</div>
										-->

									<?php else: ?>

										<div class="misc-pub-section">
											<p>To update already published items you need to revise them after saving the template.</p>
										</div>

									<?php endif; ?>



								</div>



								<div id="major-publishing-actions">
									<div id="publishing-action">
										<input type="hidden" name="action" value="save_template" />
										<input type="hidden" name="wpl_e2e_template_id" value="<?php echo $wpl_item['template_id']; ?>" />
										<input type="hidden" name="return_to" value="<?php echo @$_GET['return_to']; ?>" />
										<input type="submit" value="<?php echo __('Save template','wplister'); ?>" id="publish" class="button-primary" name="save">
									</div>
									<div class="clear"></div>
								</div>

							</div>

						</div>
					</div>


					<?php if ( sizeof($wpl_tpl_fields) > 0 ) : ?>
					<div class="postbox" id="TemplateFieldsBox">
						<h3><span><?php echo __('Template Options','wplister'); ?></span></h3>
						<div class="inside">
							<!--<p>This listing template provides the following custom settings:</p>-->

							<?php foreach ($wpl_tpl_fields as $field_id => $field) : ?>
								
								<?php if ( 'title' == $field->type ) : ?>

									<h4><?php echo $field->label ?></h4>

								<?php elseif ( 'color' == $field->type ) : ?>

								<!-- color input -->
								<div>
									<label for="<?php echo $field->id ?>" class="text_label"><?php echo $field->label ?></label>
									<input 	type="<?php echo 'text' ?>" 
										   	  id="<?php echo $field->id ?>"
										   	name="wpl_e2e_tpl_field_<?php echo $field->id ?>"
										   value="<?php echo strtoupper( $field->value ) ?>"
										   class="text_input colorpick"
										   
									/>
									<div id="colorPickerDiv_<?php echo $field->id ?>" class="colorpickdiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;right:1.2em;display:none;"></div>
								</div>

								<?php elseif ( 'select' == $field->type ) : ?>

									<label for="<?php echo $field->id ?>" class="text_label"><?php echo $field->label ?></label>
									<select	  id="<?php echo $field->id ?>"
										   	name="wpl_e2e_tpl_field_<?php echo $field->id ?>"										   
										   class="select" >
										<?php foreach ($field->options as $option_name => $option_value) : ?>
											<option value="<?php echo $option_value ?>" <?php if ($field->value == $option_value) echo "selected" ?>><?php echo $option_name ?></option>
										<?php endforeach; ?>
									</select>

								<?php else : ?>
									<!-- default text inpit -->
									<label for="<?php echo $field->id ?>" class="text_label"><?php echo $field->label ?></label>
									<input 	type="<?php echo 'text' ?>" 
										   	  id="<?php echo $field->id ?>"
										   	name="wpl_e2e_tpl_field_<?php echo $field->id ?>"
										   value="<?php echo $field->value ?>"
										   class="text_input"
									/>

								<?php endif; ?>
								<!-- <pre><?php print_r($field) ?></pre> -->

							<?php endforeach; ?>

						</div>
					</div>
					<?php endif; ?>


					<div class="postbox" id="HelpBox">
						<h3><span><?php echo __('Help','wplister'); ?></span></h3>
						<div class="inside">
							<p>
								You can use the following shortcodes in your listing template. 
								WordPress shortcodes won't work - and these will only work here.
							</p>
							<p>
								<b>Available Shortcodes</b><br>
							</p>
							<p>
								<code>[[product_title]]</code><br>
								listing title<br>
							</p>
							<p>
								<code>[[product_content]]</code><br>
								product main content<br>
							</p>
							<p>
								<code>[[product_excerpt]]</code> or<br>
								<code>[[product_excerpt_nl2br]]</code> or<br>
								<code>[[product_additional_content]]</code><br>
								additional product content<br>
							</p>
							<p>
								<code>[[product_price]]</code><br>
								product price - formatted according to language settings<br>
							</p>
							<p>
								<code>[[product_main_image]]</code><br>
								main product image as HTML tag<br>
							</p>
							<p>
								<code>[[product_main_image_url]]</code><br>
								main product image as raw URL<br>
							</p>
							<p>
								<code>[[additional_product_images]]</code><br>
								lists additional images - can be styled via css<br>
							</p>
							<p>
								<code>[[img_1]]</code> ... <code>[[img_9]]</code><br>
								<code>[[img_url_1]]</code> ... <code>[[img_url_9]]</code><br>
								lists single images resp. their urls
							</p>
							<p>
								<code>[[product_weight]]</code><br>
								<code>[[product_height]]</code><br>
								<code>[[product_width]]</code><br>
								<code>[[product_length]]</code><br>
								product dimensions
							</p>
							<p>
								<code>[[attribute_Size]]</code><br>
								<code>[[attribute_Brand]]</code><br>
								example for custom product attributes<br>
							</p>
							<p>
								<code>[[product_variations]]</code><br>
								adds a html table listing all product variations<br>
							</p>
							<p>
								<code>[[product_category]]</code><br>
								main product category name<br>
							</p>
							<p>
								<code>[[meta_<em>custom-meta-field-name</em>]]</code><br>
								adds custom meta values<br>
							</p>
							<p>
								<code>[[widget_new_listings]]</code><br>
								<code>[[widget_ending_listings]]</code><br>
								<code>[[widget_related_listings]]</code><br>
								<code>[[widget_featured_listings]]</code><br>
								embed the new crosssell widget (beta)
							</p>
							<p>
								Note: The <i>related listings widget</i> will only show up-sells or cross-sells - which you need to set up for each product in WooCommerce.
							</p>
							<p>
								<b>Shortcodes for advanced developers</b><br>
							</p>
							<p>
								<!-- For advanced developers:<br> -->
								<!-- <code>[[ebay_item_id]]</code><br> -->
								<code>[[ebay_store_url]]</code><br>
								<code>[[ebay_store_category_id]]</code><br>
								<code>[[ebay_store_category_name]]</code><br>
							</p>
							<p>
								For more information visit the 
								<a href="http://www.wplab.com/plugins/wp-lister/faq/" target="_blank">FAQ</a>.
							</p>
							<p>
								If you need help setting up your template, please contact support at wplab.com.
							</p>
						</div>
					</div>


				</div>
			</div> <!-- #postbox-container-2 -->

			<div id="postbox-container-2" class="postbox-container">
				<div class="meta-box-sortables ui-sortable">
					

					<div class="postbox" id="TemplateSettingsBox">
						<h3 class="hndle"><span><?php echo __('Template settings','wplister'); ?></span></h3>
						<div class="inside">

							<div id="titlediv" style="margin-bottom:5px;">
								<div id="titlewrap">
									<label for="wpl-text-template_description" class="text_label"><?php echo __('Name','wplister'); ?>:</label>
									<input type="text" name="wpl_e2e_template_name" size="30" value="<?php echo $wpl_item['template_name']; ?>" id="title" autocomplete="off" style="width:65%;">
								</div>
							</div>

							<label for="wpl-text-template_description" class="text_label"><?php echo __('Description','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_template_description" id="wpl-text-template_description" value="<?php echo $wpl_item['template_description']; ?>" class="text_input" />
							<br class="clear" />

							<label for="wpl-text-template_version" class="text_label"><?php echo __('Version','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_template_version" id="wpl-text-template_version" value="<?php echo $wpl_item['template_version']; ?>" class="text_input" />
							<br class="clear" />

							<!--
							<?php if ( ! $wpl_add_new_template ): ?>
								<label for="wpl-text-template_location" class="text_label"><?php echo __('Location','wplister'); ?>:</label>
								<input type="text" name="wpl_e2e_template_location" id="wpl-text-template_location" disabled value="<?php echo $wpl_template_location; ?>" class="text_input" style="background-color:#f5f5f5;" />
								<br class="clear" />
							<?php endif; ?>

							<p class="desc" style="display: block;">
								<?php echo __('Template description is only for internal use.','wplister'); ?>
							</p>
							-->				

						</div>
					</div>

					<?php if ( $wpl_disable_wysiwyg_editor != 1 ) : ?>

					    <?php 
					    	// template stylesheet url
					    	$stylesheet 	 = WP_CONTENT_DIR . $wpl_item['template_path'] . '/style.css';
					    	$stylesheet_url  = WP_CONTENT_URL . $wpl_item['template_path'] . '/style.css' . '?ver='.@filemtime( $stylesheet );

					    	// get parsed stylesheet (v2)
					    	$stylesheet_url  = 'admin-ajax.php?action=wpl_get_tpl_css&tpl=' . $wpl_item['template_id'] . '&ver='.@filemtime( $stylesheet );
					    	
					    	// $stylesheet_url = str_replace(' ', urlencode(' '), $stylesheet_url);
					    	if ( $wpl_add_new_template ) $stylesheet_url = $wpl_plugin_url . '/templates/default/default.css';
					    	// echo "loading stylesheet $stylesheet_url <br>";

					        // unstyle editor content
					        add_filter( 'mce_css', create_function('', 'return "'.$stylesheet_url.'";') ); 

					        // default settings
					        $settings = array( 
					            'wpautop' => false, 
					            'media_buttons'=>true,
					            'teeny'=>false, 
					            'textarea_name' => 'wpl_e2e_tpl_html' 
					        );
					    ?>
					    <div id="wp-editor-wrapper">
					        <!-- <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" /> -->
					        <!-- <input type="hidden" name="action" value="saveListingTemplate" /> -->
					        <input type="hidden" name="wpl_e2e_filename" value="<?php echo $wpl_item['template_path'] ?>" />
					        <?php wp_editor( $wpl_html, 'tpl_html', $settings ); ?> 
					    </div>
					    <p><i>Note: You can disable the WYSIWYG editor at WP-Lister &raquo; Settings &raquo; Advanced.</i></p>
					    <p>&nbsp;</p>
					
					<?php else: ?>

					    <h2>Template Content</h2>

					    <div id="html-editor-wrapper">
					    	<textarea name="wpl_e2e_tpl_html"><?php echo $wpl_html ?></textarea>
					    	<div id="html_editor"></div>
					    </div>
					    <p><i>You have disabled the WYSIWYG editor.</i></p>
					    <p>&nbsp;</p>
				    
					<?php endif; ?>
				    
				    <h2>Stylesheet</h2>

				    <div id="css-editor-wrapper">
				    	<textarea name="wpl_e2e_tpl_css"><?php echo $wpl_css ?></textarea>
				    	<div id="styles_editor"></div>
				    </div>
				    <p>&nbsp;</p>
				    
				    <a name="header">&nbsp;</a>
				    <h2>header.php</h2>

				    <div id="header-editor-wrapper">
				    	<textarea name="wpl_e2e_tpl_header"><?php echo $wpl_header ?></textarea>
				    	<div id="header_editor"><?php #echo htmlspecialchars( $wpl_header ) ?></div>
				    </div>
				    <p>&nbsp;</p>
				    
				    <h2>footer.php</h2>

				    <div id="footer-editor-wrapper">
				    	<textarea name="wpl_e2e_tpl_footer"><?php echo $wpl_footer ?></textarea>
				    	<div id="footer_editor"></div>
				    </div>
				    <p>&nbsp;</p>
				    
				    <h2>functions.php</h2>

				    <div id="functions-editor-wrapper">
				    	<textarea name="wpl_e2e_tpl_functions"><?php echo $wpl_functions ?></textarea>
				    	<div id="functs_editor"></div>
				    </div>



					<!-- optional save and apply to all prepared listings already using this template -->
					<?php if ( (isset($wpl_prepared_auctions)) && ( count($wpl_prepared_auctions) > 0 ) ): ?>
						<input type="checkbox" name="wpl_e2e_apply_changes_to_all_prepared" value="yes" id="apply_changes_to_all_prepared" />
						<label for="apply_changes_to_all_prepared"><?php printf( __('apply changes to all %s prepared listings using this template','wplister'), count($wpl_prepared_auctions) ) ?></label>
					<?php endif; ?>

					<div class="submit" style="padding-top: 0; float: right; display:none;">
						<input type="hidden" name="action" value="save_template" />
						<input type="hidden" name="wpl_e2e_template_id" value="<?php echo $wpl_item['template_id']; ?>" />
						<input type="submit" value="<?php echo __('Save template','wplister'); ?>" name="submit" class="button-primary">
					</div>

						
				</div> <!-- .meta-box-sortables -->
			</div> <!-- #postbox-container-2 -->


		</div> <!-- #post-body -->
		<br class="clear">
	</div> <!-- #poststuff -->

	</form>

	<br style="clear:both;"/>

	<?php if ( get_option('wplister_log_level') > 6 ): ?>
	<pre><?php print_r($wpl_item); ?></pre>
	<?php endif; ?>

	<?php if ( $wpl_disable_wysiwyg_editor == 1 ) : ?>
	<script type="text/javascript">
		jQuery( document ).ready( function () {

			    var html_editor = ace.edit("html_editor");
			    var html_textarea = jQuery('textarea[name="wpl_e2e_tpl_html"]').hide();

			    html_editor.setTheme("ace/theme/chrome");
			    html_editor.setShowPrintMargin( false );

			    var HtmlMode = require("ace/mode/html").Mode;
			    html_editor.getSession().setMode(new HtmlMode());
	
			    // connect editors with textareas
			    html_editor.getSession().setValue(html_textarea.val());

			    html_editor.getSession().on('change', function(){
					html_textarea.val(html_editor.getSession().getValue());
				});

		});	
	</script>
	<?php endif; ?>


	<script type="text/javascript">
		jQuery( document ).ready(
			function () {

			    var styles_editor = ace.edit("styles_editor");
			    var header_editor = ace.edit("header_editor");
			    var footer_editor = ace.edit("footer_editor");
			    var functs_editor = ace.edit("functs_editor");
			    var styles_textarea = jQuery('textarea[name="wpl_e2e_tpl_css"]').hide();
			    var header_textarea = jQuery('textarea[name="wpl_e2e_tpl_header"]').hide();
			    var footer_textarea = jQuery('textarea[name="wpl_e2e_tpl_footer"]').hide();
			    var functs_textarea = jQuery('textarea[name="wpl_e2e_tpl_functions"]').hide();

			    styles_editor.setTheme("ace/theme/chrome");
			    header_editor.setTheme("ace/theme/chrome");
			    footer_editor.setTheme("ace/theme/chrome");
			    functs_editor.setTheme("ace/theme/chrome");
			    styles_editor.setShowPrintMargin( false );
			    header_editor.setShowPrintMargin( false );
			    footer_editor.setShowPrintMargin( false );
			    functs_editor.setShowPrintMargin( false );

			    // var JavaScriptMode = require("ace/mode/javascript").Mode;
			    var PhpMode = require("ace/mode/php").Mode;
			    var ScssMode = require("ace/mode/scss").Mode;
			    styles_editor.getSession().setMode(new ScssMode());
			    header_editor.getSession().setMode(new PhpMode());
			    footer_editor.getSession().setMode(new PhpMode());
			    functs_editor.getSession().setMode(new PhpMode());
	
			    // connect editors with textareas
			    // http://stackoverflow.com/questions/6440439/how-do-i-make-a-textarea-an-ace-editor
			    styles_editor.getSession().setValue(styles_textarea.val());
			    header_editor.getSession().setValue(header_textarea.val());
			    footer_editor.getSession().setValue(footer_textarea.val());
			    functs_editor.getSession().setValue(functs_textarea.val());

			    styles_editor.getSession().on('change', function(){
					styles_textarea.val(styles_editor.getSession().getValue());
				});
			    header_editor.getSession().on('change', function(){
					header_textarea.val(header_editor.getSession().getValue());
				});
			    footer_editor.getSession().on('change', function(){
					footer_textarea.val(footer_editor.getSession().getValue());
				});
			    functs_editor.getSession().on('change', function(){
					functs_textarea.val(functs_editor.getSession().getValue());
				});
				// or just call
				// textarea.val(editor.getSession().getValue());
				// only when you submit the form 


				// farbtastic color picker
				jQuery('.colorpick').each(function(){
					jQuery( '.colorpickdiv', jQuery(this).parent() ).farbtastic(this);
					jQuery(this).click(function() {
						if ( jQuery(this).val() == "" ) jQuery(this).val('#');
						jQuery('.colorpickdiv', jQuery(this).parent() ).show();
					});
				});
				jQuery(document).mousedown(function(){
					jQuery('.colorpickdiv').hide();
				});

				// new WP color picker
			    // if ( typeof jQuery.wp === 'object' && typeof jQuery.wp.wpColorPicker === 'function' ) {
		    	//     jQuery( '.colorpick' ).wpColorPicker();
		    	// }


				// check required values on submit
				jQuery('.wplister-page form').on('submit', function() {
					
					// folder name is required
					if ( jQuery('#title')[0].value == '' ) {
						alert('Please enter a template name.');
						return false;
					}

					return true;
				})


			}
		);	
	</script>

</div>
