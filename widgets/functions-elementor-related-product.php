<?php
/**
 * Return product category array.
 */
if (!function_exists('rpmw_post_categories')) {

    function rpmw_post_categories() {
        // Get all product categories
        $product_categories = get_terms( 'product_cat' );
        
        $product_cat = array();
        if( !empty($product_categories) && !is_wp_error($product_categories) ){
            // Filter product categories based on conditions
            foreach ($product_categories as $category) {
                if ( !empty($category->name) ) {
                    $product_cat[$category->term_id] = $category->name;
                }
            }
        }
        return $product_cat;
    }
}


/**
 * Return product tags array.
 */
if (!function_exists('rpmw_post_tag')) {

    function rpmw_post_tag() {
        
        $product_categories = get_terms( 'product_tag' );
        
        $product_cat = array();
        if( !empty($product_categories) && !is_wp_error($product_categories) ){
            foreach ($product_categories as $category) {
                $product_cat[$category->term_id] = $category->name;
            }          
        }
        return $product_cat;

    }
}