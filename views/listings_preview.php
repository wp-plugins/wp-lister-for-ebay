<style type="text/css">


	#wpl_preview_header {
		width: 800px;
		margin: auto;
		margin-top: 1.5em;
	}

	#wpl_preview_header,
	#wpl_preview_header th,
	#wpl_preview_header td {
		font-family: Arial,Helvetica,sans-serif;
		font-size: small;
	}

	#wpl_preview_header h1.listing-title {
		font-size: 18px;
		font-weight: bold;
		color: #333;
		line-height: normal;
		margin: 0;
		padding: 0;
		font-family: Trebuchet,"Trebuchet MS";
	}

	#wpl_preview_header .main_image_wrapper {
		width: 300px;
		height: 300px;
		border: 1px solid #ccc;
		margin-bottom: 0.5em;
		text-align: center;
	}

	#wpl_preview_header .main_image_wrapper .helper {
	    display: inline-block;
	    height: 100%;
	    vertical-align: middle;
	}
	#wpl_preview_header .main_image_wrapper img {
		max-width: 300px;
		max-height: 300px;
	    vertical-align: middle;
	}

	#wpl_preview_header td.images .zoomlink {
		font-family: Verdana;
		font-size: 10px;
		color: #555;
		position: relative;		
	}

	#wpl_preview_header table {
		border-collapse: separate;
		/*border-spacing: 3px;*/
		border-color: gray;
		width: 100%;
	}

	.vi-is1-lbl, .vi-is1-lblp {
		color: #666;
		vertical-align: text-top;
		font-weight: normal;
		text-align: right;
		width: 21%;
	}
	.vi-is1-clr {
		padding: 0 5px 0 15px;
	}	
	.vi-is1-solid, .vi-is1-bdr {
		background-color: #e2e2e2;
	}
	.vi-is1-prcp {
		font-size: medium;
		font-weight: bold;
		font-family: Trebuchet MS;
		padding: 0;
		color: #333;
		white-space: nowrap;
	}

	div#message.error {
		font-family: sans-serif;
		font-size: small;
		padding: 0 1em;
		background-color: #ffeeee;
		border: 1px solid red;
	}

	div#message.updated {
		font-family: sans-serif;
		font-size: small;
		padding: 0 1em;
		background-color: #ffffee;
		border: 1px solid #cca;
	}

	#TB_window table.variations_table {
	}

</style>


<div id="wpl_preview_header">

	<table width="100%" cellspacing="0" cellpadding="0">
		<tbody>
			<tr>
				<td class="images" style="width:310px;" valign="top">

					<div class="main_image_wrapper">					
						<span class="helper"></span><img id="main_image_tag" src="<?php echo @$wpl_item->PictureDetails->GalleryURL ?>" title=""/> <!-- this has to be a single line -->
					</div>

					<center class="zoomlink">
						Click to view larger image
					</center>
				</td>
				<td valign="top">


					<h1 class="listing-title">
						<?php echo $wpl_item->Title ?>
					</h1>
					<hr>

					<table width="100%" cellspacing="0" cellpadding="0">
						<tbody>

							<tr>
								<th class="vi-is1-lbl">
									Item condition:
								</th>
								<td colspan="3" class="vi-is1-clr">
									<span class="vi-is1-condText">New</span>
								</td>
							</tr>
							<tr>
								<td colspan="4" height="10"></td>
							</tr>
							<tr>
								<th class="vi-is1-lbl">
									Listing duration:
								</th>
								<td colspan="3" class="vi-is1-clr">
									<?php 
										if ( $wpl_item->ListingDuration == 'GTC')
											echo 'GTC';
										else
											echo str_replace('Days_','', $wpl_item->ListingDuration ) . ' days' 
									?>
								</td>
							</tr>
							<tr>
								<td colspan="4" height="10"></td>
							</tr>
							<!--
							<tr>
								<th class="vi-is1-lbl">
									<label for="63">Color:</label>
								</th>
								<td colspan="3" class="vi-is1-clr">
									<div>
										<div>
											<select class="vi-is1-jsSelect" id="63" name="Color">
												<option value="-1">
													- Select -
												</option>
												<option value="1" style="color: black;">
													blue
												</option>
												<option value="2" style="color: black;">
													green
												</option>
												<option value="3" style="color: black;">
													red
												</option>
												<option value="4" style="color: black;">
													yellow
												</option>
											</select>
										</div>
									</div>
								</td>
							</tr>
							<tr>
								<td colspan="4" height="10"></td>
							</tr>
							<tr>
								<th class="vi-is1-lbl">
									<label for="4032">Size:</label>
								</th>
								<td colspan="3" class="vi-is1-clr">
									<div>
										<div>
											<select class="vi-is1-jsSelect" id="4032" name="Size">
												<option value="-1">
													- Select -
												</option>
												<option value="64" style="color: black;">
													L
												</option>
												<option value="128" style="color: black;">
													XL
												</option>
											</select>
										</div>
									</div>
								</td>
							</tr>
							<tr>
								<td colspan="4" height="10"></td>
							</tr>
							-->
							<?php
								#echo "<pre>";print_r($);echo"</pre>";die();
							?>

							<?php if ( is_object( $wpl_item->Variations ) ) : ?>

								<tr>
									<td colspan="4" height="10" class="vi-is1-solid"></td>
								</tr>
								<tr>
									<th class="vi-is1-lblp vi-is1-solid">
										Variations:
									</th>
									<td class="vi-is1-solid vi-is1-tbll vi-is1-clr">
										<?php 
											#echo "<pre>";print_r($wpl_item->Variations);echo"</pre>";die();
										?>
											
											<table style="width:auto;">
											<?php foreach ($wpl_item->Variations->Variation as $var) : ?>
												<tr>
													<td><?php echo $var->Quantity ?> x </td>
													<td>
														<?php foreach ($var->VariationSpecifics->NameValueList as $spec) {
															echo $spec->Value.' ';
														}
														?>
													</td>
													<td> &nbsp; <i><?php echo $var->SKU ?></i> </td>
													<td> &nbsp; <b><?php echo woocommerce_price( $var->StartPrice ) ?></b></td>
												</tr>
											<?php endforeach ?>
											</table>

										
									</td>
									<td colspan="2" class="vi-is1-solid vi-is1-tblb">
									</td>
								</tr>
								<tr>
									<td colspan="4" height="10" class="vi-is1-solid"></td>
								</tr>

							<?php elseif ( $wpl_item->ListingType == 'Chinese' ) : ?>

							<tr>
								<th class="vi-is1-lbl">
									<label for="v4-32qtyId">Quantity:</label>
								</th>
								<td colspan="3" class="vi-is1-clr">
									<?php echo $wpl_item->Quantity ?> available
								</td>
							</tr>
							<tr>
								<td colspan="4" height="10"></td>
							</tr>
							<tr>
								<td colspan="4" height="10" class="vi-is1-solid"></td>
							</tr>
							<tr>
								<th class="vi-is1-lblp vi-is1-solid">
									Start price:
								</th>
								<td class="vi-is1-solid vi-is1-tbll vi-is1-clr">
									<span class="vi-is1-prcp" id="v4-30" itemprop="price">
										<?php echo woocommerce_price( $wpl_item->StartPrice->value ) ?>
									</span>
								</td>
								<td colspan="2" class="vi-is1-solid vi-is1-tblb">
								</td>
							</tr>
							<tr>
								<td colspan="4" height="10" class="vi-is1-solid"></td>
							</tr>

							<?php if ( $wpl_item->BuyItNowPrice->value ) : ?>
							
								<tr>
									<th class="vi-is1-lblp vi-is1-solid">
										Buy Now price:
									</th>
									<td class="vi-is1-solid vi-is1-tbll vi-is1-clr">
										<span class="vi-is1-prcp" id="v4-30" itemprop="price">
											<?php echo woocommerce_price( $wpl_item->BuyItNowPrice->value ) ?>
										</span>
									</td>
									<td colspan="2" class="vi-is1-solid vi-is1-tblb">
									</td>
								</tr>
								<tr>
									<td colspan="4" height="10" class="vi-is1-solid"></td>
								</tr>

							<?php endif; ?>

							<?php else : ?>

							<tr>
								<th class="vi-is1-lbl">
									<label for="v4-32qtyId">Quantity:</label>
								</th>
								<td colspan="3" class="vi-is1-clr">
									<?php echo $wpl_item->Quantity ?> available
								</td>
							</tr>
							<tr>
								<td colspan="4" height="10"></td>
							</tr>
							<tr>
								<td colspan="4" height="10" class="vi-is1-solid"></td>
							</tr>
							<tr>
								<th class="vi-is1-lblp vi-is1-solid">
									Price:
								</th>
								<td class="vi-is1-solid vi-is1-tbll vi-is1-clr">
									<span class="vi-is1-prcp" id="v4-30" itemprop="price">
										<?php echo woocommerce_price( $wpl_item->StartPrice->value ) ?>
									</span>
								</td>
								<td colspan="2" class="vi-is1-solid vi-is1-tblb">
								</td>
							</tr>
							<tr>
								<td colspan="4" height="10" class="vi-is1-solid"></td>
							</tr>

							<?php endif; ?>

							<!--
							<tr>
								<td colspan="4" height="10"></td>
							</tr>
							<tr id="v4-4sf">
								<th class="vi-is1-lbl">
									Shipping:
								</th>
								<td colspan="3" class="vi-is1-clr">
								</td>
							</tr>
							<tr>
								<th class="vi-is1-lbl"></th>
								<td colspan="3" class="vi-is1-clr">
									<div class="sh-DlvryDtl">
										Item location: <span class="g-b">New York, New York, United States</span>
									</div>
								</td>
							</tr>
							<tr>
								<th class="vi-is1-lbl"></th>
								<td colspan="3" class="vi-is1-clr">
									<div class="sh-DlvryDtl">
									Ships to: 
									<?php
										if ( is_array( $wpl_item->ShipToLocations ))
											echo join(', ', $wpl_item->ShipToLocations );
									?>
									</div>
								</td>
							</tr>
							-->
							<tr>
								<td colspan="4" height="10"></td>
							</tr>
							<tr>
								<th class="vi-is1-lbl">
									Listing type:
								</th>
								<td colspan="3" class="vi-is1-clr">
									<?php
										switch ( $wpl_item->ListingType ) {
											case 'Chinese':
												echo "Auction";
												break;
											
											default:
												echo "Fixed Price";
												break;
										}
										// echo $wpl_item->ListingType;
									?>
								</td>
							</tr>
							<tr>
								<td colspan="4" height="10"></td>
							</tr>
							<tr>
								<th class="vi-is1-lbl">
									Payments:
								</th>
								<td colspan="3" class="vi-is1-clr">
									<?php
										if ( is_array( $wpl_item->PaymentMethods ))
											echo join(', ', $wpl_item->PaymentMethods );
									?>
								</td>
							</tr>
							<tr>
								<td colspan="4" height="10"></td>
							</tr>
							<tr>
								<th class="vi-is1-lbl">
									Returns:
								</th>
								<td colspan="3" class="vi-is1-clr">
									<?php 
										echo $wpl_item->ReturnPolicy->ReturnsAcceptedOption;
										if ( isset( $wpl_item->ReturnPolicy->ReturnsWithinOption ) )
											echo $wpl_item->ReturnPolicy->ReturnsWithinOption;
									?>
								</td>
							</tr>
							<tr>
								<td colspan="4" height="10"></td>
							</tr>

							<tr>
								<th class="vi-is1-lbl">
									eBay Category:
								</th>
								<td colspan="3" class="vi-is1-clr">
									<?php 

										if ( $wpl_item->PrimaryCategory->CategoryID )
											echo EbayCategoriesModel::getFullEbayCategoryName( $wpl_item->PrimaryCategory->CategoryID );

										if ( $wpl_item->SecondaryCategory->CategoryID )
											echo '<br>'.EbayCategoriesModel::getFullEbayCategoryName( $wpl_item->SecondaryCategory->CategoryID );

									?>
								</td>
							</tr>
							<tr>
								<td colspan="4" height="10"></td>
							</tr>

							<?php if ( $wpl_item->Storefront->StoreCategoryID ) : ?>
							<tr>
								<th class="vi-is1-lbl">
									Store Category:
								</th>
								<td colspan="3" class="vi-is1-clr">
									<?php 

										if ( $wpl_item->Storefront->StoreCategoryID )
											echo EbayCategoriesModel::getFullStoreCategoryName( $wpl_item->Storefront->StoreCategoryID );

										if ( $wpl_item->Storefront->StoreCategory2ID )
											echo '<br>'.EbayCategoriesModel::getFullStoreCategoryName( $wpl_item->Storefront->StoreCategory2ID );

									?>
								</td>
							</tr>
							<tr>
								<td colspan="4" height="10"></td>
							</tr>
							<?php endif; ?>

						</tbody>
					</table>


				</td>
			</tr>
		</tbody>
	</table>


</div>

<?php /* if ( ! $wpl_check_result->success ) : ?>

	<hr>
	<?php echo $wpl_check_result->errors[0]->HtmlMessage ?>

<?php endif */ ?>

<br>
<hr>

<?php echo $wpl_preview_html ?>


<!-- show image size as tooltip -->
<script>

function loadImageSize(imgSrc, callback){
	var image = new Image();
	image.src = imgSrc;
	if (image.complete) {
		callback(image);
		image.onload=function(){};
	} else {
		image.onload = function() {
			callback(image);
			// clear onLoad, IE behaves erratically with animated gifs otherwise
			image.onload=function(){};
		}
		image.onerror = function() {
	    	alert("Could not load image.");
		}
	}
}

function updateImageSize(image) {
	var img = document.getElementById('main_image_tag');
	img.title = "Image size: " + image.width + "x" + image.height;
}
var imgSrc = "<?php echo @$wpl_item->PictureDetails->GalleryURL ?>";
if ( imgSrc != '' ) loadImageSize( imgSrc, updateImageSize);

</script>

