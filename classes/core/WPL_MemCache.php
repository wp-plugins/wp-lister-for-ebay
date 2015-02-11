<?php

class WPLE_MemCache {

	// in memory caches
	private $product_attributes = array();
	private $product_variations = array();
	private $product_objects    = array();


	// get WooCommerce product object
	public function getProductObject( $post_id ) {

        // update cache if required
        if ( ! array_key_exists( $post_id, $this->product_objects ) ) {
            $this->product_objects[ $post_id ] = get_product( $post_id );
        }

        return $this->product_objects[ $post_id ];
	}


	// get product attributes
	public function getProductAttributes( $post_id ) {

        // update cache if required
        if ( ! array_key_exists( $post_id, $this->product_attributes ) ) {
            $this->product_attributes[ $post_id ] = ProductWrapper::getAttributes( $post_id );
        }

        return $this->product_attributes[ $post_id ];
	}


    // get product variations
    public function getProductVariations( $post_id ) {

        // update cache if required
        if ( ! array_key_exists( $post_id, $this->product_variations ) ) {
            $this->product_variations[ $post_id ] = ProductWrapper::getVariations( $post_id );
        }

        return $this->product_variations[ $post_id ];
    }



} // class WPLE_MemCache
