
<?php for ($i=0; $i < count($images); $i++) : ?>

	<?php $image_url = $images[$i];	?>
	
	<a href="#" onclick="if (typeof wplOnThumbnailClick == 'function') wplOnThumbnailClick('<?php echo $image_url ?>');return false;" onmouseover="if (typeof wplOnThumbnailHover == 'function') wplOnThumbnailHover('<?php echo $image_url ?>');return false;" >
		<img class="wpl_thumb thumb_<?php echo $i+1 ?>" src="<?php echo $image_url ?>" alt="<?php echo basename( $image_url ) ?>" />
	</a>

<?php endfor; ?>
