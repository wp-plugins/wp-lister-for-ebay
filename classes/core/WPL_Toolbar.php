<?php

class WPLister_Toolbar  {
	
	public function __construct() {

		// custom toolbar
		add_action( 'admin_bar_menu', array( &$this, 'customize_toolbar' ), 999 );

	}


	// custom toolbar bar
	function customize_toolbar( $wp_admin_bar ) {

		// top level 'eBay'
		$args = array(
			'id'    => 'wplister_top',
			'title' => __('eBay', 'wplister'),
			'href'  => admin_url( 'admin.php?page=wplister' ),
			'meta'  => array('class' => 'wplister-toolbar-top')
		);
		$wp_admin_bar->add_node($args);
		
		// Listings page	
		$args = array(
			'id'    => 'wplister_listings',
			'title' => __('Listings', 'wplister'),
			'href'  => admin_url( 'admin.php?page=wplister' ),
			'parent'  => 'wplister_top',
			'meta'  => array('class' => 'wplister-toolbar-page')
		);
		$wp_admin_bar->add_node($args);

		// Profiles page
		$args = array(
			'id'    => 'wplister_profiles',
			'title' => __('Profiles', 'wplister'),
			'href'  => admin_url( 'admin.php?page=wplister-profiles' ),
			'parent'  => 'wplister_top',
			'meta'  => array('class' => 'wplister-toolbar-page')
		);
		$wp_admin_bar->add_node($args);

		// Settings page
		$args = array(
			'id'    => 'wplister_settings',
			'title' => __('Settings', 'wplister'),
			'href'  => admin_url( 'admin.php?page=wplister-settings' ),
			'parent'  => 'wplister_top',
			'meta'  => array('class' => 'wplister-toolbar-page')
		);
		$wp_admin_bar->add_node($args);

		// Settings - General tab
		$args = array(
			'id'    => 'wplister_settings_general',
			'title' => __('General Settings', 'wplister'),
			'href'  => admin_url( 'admin.php?page=wplister-settings' ),
			'parent'  => 'wplister_settings',
			'meta'  => array('class' => 'wplister-toolbar-page')
		);
		$wp_admin_bar->add_node($args);

		// Settings - Categories tab
		$args = array(
			'id'    => 'wplister_settings_categories',
			'title' => __('Categories', 'wplister'),
			'href'  => admin_url( 'admin.php?page=wplister-settings&tab=categories' ),
			'parent'  => 'wplister_settings',
			'meta'  => array('class' => 'wplister-toolbar-page')
		);
		$wp_admin_bar->add_node($args);

		// Settings - License tab
		$args = array(
			'id'    => 'wplister_settings_license',
			'title' => __('License', 'wplister'),
			'href'  => admin_url( 'admin.php?page=wplister-settings&tab=license' ),
			'parent'  => 'wplister_settings',
			'meta'  => array('class' => 'wplister-toolbar-page')
		);
		$wp_admin_bar->add_node($args);

		// Settings - Developer tab
		$args = array(
			'id'    => 'wplister_settings_developer',
			'title' => __('Developer', 'wplister'),
			'href'  => admin_url( 'admin.php?page=wplister-settings&tab=developer' ),
			'parent'  => 'wplister_settings',
			'meta'  => array('class' => 'wplister-toolbar-page')
		);
		$wp_admin_bar->add_node($args);


		if ( current_user_can('administrator') && ( get_option( 'wplister_log_to_db' ) == '1' ) ) {
		
			// Logs page
			$args = array(
				'id'    => 'wplister_log',
				'title' => __('Logs', 'wplister'),
				'href'  => admin_url( 'admin.php?page=wplister-log' ),
				'parent'  => 'wplister_top',
				'meta'  => array('class' => 'wplister-toolbar-page')
			);
			$wp_admin_bar->add_node($args);

		}

		// admin only from here on
		if ( ! current_user_can('administrator') ) return;

		// product page
		global $post;
		global $wp_query;
		$post_id = false;

		if ( isset( $wp_query->post->post_type ) && ( $wp_query->post->post_type == 'product' ) ) {
			$post_id = $wp_query->post->ID;
		} elseif ( isset( $post->post_type ) && ( $post->post_type == 'product' ) ) {
			$post_id = $post->ID;
		}

		// do we have a single product page?
		if ( $post_id ) {

			// get all items
			$lm = new ListingsModel();
			$listings = $lm->getAllListingsFromPostID( $post_id );

			if ( sizeof($listings) > 0 ) {

				$ebay_id = $lm->getEbayIDFromPostID( $post_id );
				$url = $lm->getViewItemURLFromPostID( $post_id );

				// View on eBay link
				$args = array(
					'id'    => 'wplister_view_on_ebay',
					'title' => __('View item on eBay', 'wplister'), # ." ($ebay_id)",
					'href'  => $url,
					'parent'  => 'wplister_top',
					'meta'  => array('target' => '_blank', 'class' => 'wplister-toolbar-link')
				);
				if ( $url ) $wp_admin_bar->add_node($args);

				foreach ($listings as $listing) {

					$args = array(
						'id'    => 'wplister_view_on_ebay_'.$listing->id,
						'title' => '#'.$listing->ebay_id . ': ' . $listing->auction_title,
						'href'  => $listing->ViewItemURL,
						'parent'  => 'wplister_view_on_ebay',
						'meta'  => array('target' => '_blank', 'class' => 'wplister-toolbar-link')
					);
					if ( $listing->ViewItemURL ) $wp_admin_bar->add_node($args);

				}


			} else {

				// $args = $this->addPrepareActions( $args );

			}

			$this->addPrepareActions( $wp_admin_bar, $post_id );


		} // if is product page



	} // customize_toolbar()

	function addPrepareActions( $wp_admin_bar, $post_id ) {

		// Prepare listing link
		$url = '';
		$args = array(
			'id'    => 'wplister_prepare_listing',
			'title' => __('Prepare listing', 'wplister'),
			'href'  => $url,
			'parent'  => 'wplister_top'
		);
		$wp_admin_bar->add_node( $args );

		$pm = new ProfilesModel();
		$profiles = $pm->getAll();

		foreach ($profiles as $profile) {

			// echo "<pre>";print_r($profile);echo"</pre>";#die();
			$profile_id = $profile['profile_id'];
			$url = admin_url( 'admin.php?page=wplister&action=wpl_prepare_single_listing&product_id='.$post_id.'&profile_id='.$profile_id );
			$args = array(
				'id'    => 'wplister_view_on_ebay_'.$profile['profile_id'],
				'title' => $profile['profile_name'],
				'href'  => $url,
				'parent'  => 'wplister_prepare_listing'
			);
			$wp_admin_bar->add_node($args);

		}

		return $args;
	}
	

} // class WPLister_Toolbar

// instantiate object
$oWPLister_Toolbar = new WPLister_Toolbar();

