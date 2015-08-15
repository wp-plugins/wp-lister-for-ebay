<?php

class WPLE_MemCache {

	// in memory caches
	private $product_attributes = array();
	private $product_variations = array();
	private $product_objects    = array();
    private $short_variations   = array();
    private $taxonomies         = array();
    private $taxonomy_terms     = array();


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

    // get product variations (short version)
    public function getShortProductVariations( $post_id ) {

        // update cache if required
        if ( ! array_key_exists( $post_id, $this->short_variations ) ) {
            $this->short_variations[ $post_id ] = ProductWrapper::getVariations( $post_id, true );
        }

        return $this->short_variations[ $post_id ];
    }


    // cached version of get_term_by
    public function getTermBy( $field, $value, $taxonomy ) {

        // update cache if required
        $cache_key = $field.$value.$taxonomy;
        if ( ! array_key_exists( $cache_key, $this->taxonomy_terms ) ) {
            $this->taxonomy_terms[ $cache_key ] = get_term_by( $field, $value, $taxonomy );
        }

        return $this->taxonomy_terms[ $cache_key ];
    }



} // class WPLE_MemCache
