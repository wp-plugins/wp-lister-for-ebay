<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Listing Gallery</title>

<style type="text/css">

	body { margin: 0; }

	.wpl_listing_thumbs {
		text-align: center;
		/*width: 1500px;*/
		width: <?php echo sizeof($items) * 150 ?>px;
	}

	.wpl_listing_thumbs a {
		float: left;
		display: block;
		width: 120px;
		height: 135px;
		margin-left: 0;
		margin-right: 10px;
		/*margin-bottom: 20px;*/
		border: 1px solid #ccc;
		padding: 9px;
		color: #000099;
		text-decoration: none;
		border-radius: 5px;
	}

	.wpl_listing_thumbs a:hover {
		background-color: #FFC;
		border-color: #aaa;
		color: #000;
	}

	.wpl_listing_thumbs .thumb img {
		border: none;
		max-height: 90px;
		max-width: 120px;
		width: auto;
	}

	.wpl_listing_thumbs .title {
		font-size: 12px;
		font-family: sans-serif;
		line-height: 15px;
		height: 45px;
		overflow: hidden;
		margin: 3px 0;
	}

</style>

</head>
<body>

<div class="wpl_listing_thumbs">

	<?php foreach ($items as $item) : ?>		
	<a href="<?php echo $item['ViewItemURL'] ?>" title="<?php echo $item['auction_title'] ?>" target="_top">

		<div class="thumb">
			<!-- <img src="<?php echo ProductWrapper::getImageURL( $item['post_id'] ) ?>" alt="<?php echo $item['auction_title'] ?>" />  -->
			<img src="<?php echo $item['GalleryURL'] ?>" alt="<?php echo $item['auction_title'] ?>" /> 
		</div>

		<div class="title"><?php echo $item['auction_title'] ?></div>

	</a>
	<?php endforeach; ?>

</div>

</body>
</html>
