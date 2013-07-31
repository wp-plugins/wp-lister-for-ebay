
<style type="text/css">

	input.price_input {
		width: 100%;
	}
	
	.service_table th {
		text-align: left;
	}
	
	/* shipping service type */
	select.select_shipping_type {
		width: auto;
		position: absolute;
		right: 4px;
		top: 4px;
		line-height: 20px;
		height: 21px;
		font-size: 12px;
	}

</style>


					<div class="postbox" id="ShippingOptionsBox">
						<h3><span><?php echo __('Shipping Options','wplister'); ?></span>
						</h3>
						<div class="inside">

							<?php include('edit_shipping_loc.php') ?>

						</div>
					</div>


					<div class="postbox" id="IntShippingOptionsBox">
						<h3><span><?php echo __('International shipping','wplister'); ?></span></h3>
						<div class="inside">

							<?php include('edit_shipping_int.php') ?>

						</div>
					</div>



<script type="text/javascript">

	<?php include('edit_shipping.js') ?>

</script>
