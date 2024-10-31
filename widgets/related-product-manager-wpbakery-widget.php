<?php
/*
 * Display Related Product Visual Composer Elements
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
function related_product_manager_shortcode($atts, $content = null, $shortcode_handle = ' ') {

    $default_atts = array(
        'rpmw_columns' => 3,
        'rpmw_number_of_product' => 6,
        'rpmw_heading'=> ' ',
        'rpmw_show_heading' => 'true',
        'rpmw_heading_html_tag' => 'h2',
        'rpmw_heading_color'=>'#000',
        'rpmw_heading_aligment'=> 'left',
        'rpmw_heading_spacing' => '20px',
        'rpmw_layout_image_border_radius'=>'20px',
        'rpmw_layout_image_overlay'=> ' ',
        'rpmw_show_sale_badge' => 'true',
        'rpmw_sale_badge' => 'Sale!',
        'rpmw_sale_color' => '#fff',
        'rpmw_sale_background_color'=>'#0274be',
        'rpmw_sale_border_radius' => '50',
        'rpmw_show_category' => 'true',
        'rpmw_show_tag' =>'true',
        'rpmw_category_color' => '#000',
        'rpmw_tag_color' => '#000',
        'rpmw_category_hover_color' =>'#0274be',
        'rpmw_tag_hover_color' => '#0274be',
        'rpmw_category_spacing' =>' ',
        'rpmw_tag_spacing' => '10px',
        'rpmw_category_aligment' =>'left',
        'rpmw_category_font_size' =>'12px',
        'rpmw_tag_aligment' =>'left',
        'rpmw_tag_font_size' =>'12px',
        'rpmw_product_name_color' =>'#000',
        'rpmw_product_name_hover_color' =>'#0274be',
        'rpmw_product_name_aligment'=> 'left',
        'rpmw_product_name_spacing' => '10px',
        'rpmw_product_name_font_size' =>'15px',
        'rpmw_product_price_color' => '#000',
        'rpmw_product_price_hover_color' =>'#0274be',
        'rpmw_product_price_aligment'=> 'left',
        'rpmw_product_price_spacing' => ' ',
        'rpmw_product_price_font_size' =>'15px',
        'rpmw_rating_star' =>'10px',
        'rpmw_rating_star_color' => ' ',
        'rpmw_rating_star_unmarked_color'=> ' ',
        'rpmw_rating_star_spacing_left'=>' ',
        'rpmw_rating_star_spacing_bottom'=>'10px',
        'rpmw_text_button'=>'Add to cart',
        'rpmw_button_text_color' => '#fff',
        'rpmw_button_background_color' => '#0274be',
        'rpmw_button_background_hover_color' => '#000',
        'rpmw_button_text_hover_color' =>'#fff',
        'rpmw_button_border_radius' => ' ',
        'rpmw_button_aligment' => 'left',
        'rpmw_button_url' => ' ',
        'rpmw_product_order_by' => 'post_id',
        'rpmw_product_sort_by' => 'asc',
        'rpmw_grid_gap' => ' ',
        'rpmw_rows_gap' => ' ',
        'rpmw_grid_gap' => '30',
        'rpmw_rows_gap' => '35',
        'rpmw_design_options' =>' ',
        'rpmw_rating_aligment' => ' ',
        'multiple'=>' ',
        'category' =>' ',
        'exclude_product_categories'=>false,
        'rpmw_sale_size' => ' ',
    );

    $atts = shortcode_atts($default_atts, $atts);
    extract($atts);
    $content = wpb_js_remove_wpautop($content, true);
    $css_class = apply_filters( VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, vc_shortcode_custom_css_class( $rpmw_design_options, ' ' ));
    ob_start();
 
    /**
     * Render Related Product widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @access protected
     */
    global $product;
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $rpmw_number_of_product,
        'post_status' => 'publish',
        'columns' => $rpmw_columns,
        'orderby' => $rpmw_product_order_by,
        'order' => $rpmw_product_sort_by,
        'hide_empty' =>false,
        'taxonomy'     => 'product_cat',     
    );?>
    <!-- Related Product for ordrby -->
    <?php switch ($rpmw_product_order_by){
    case 'price':
        $args = array(
            'post_type'      => 'product',
            'orderby'        => 'meta_value_num',
            'order'          => $rpmw_product_sort_by,
            'meta_key'       => '_price'
            );
        break;
    case 'price-desc':
        $args = array(
            'post_type'      => 'product',
            'orderby'        => 'meta_value_num',
            'order'          => $rpmw_product_sort_by,
            'meta_key'       => '_price'
            );
        break;
    case 'rating':
            $args = array(
            'post_type'      => 'product',
            'orderby'        => 'meta_value_num',
            'order'          => $rpmw_product_sort_by,
            'meta_key'       => '_wc_average_rating'
            );
        break;
    }
    // Related Product post per page
    if ( ! empty( $rpmw_number_of_product ) ) {
            $args['posts_per_page'] = $rpmw_number_of_product;
    }
    // Related Product column 
    if ( ! empty( $rpmw_columns ) ) {
            $args['columns'] = $rpmw_columns;
    }
    // Related Product Exclude category
    if (isset($exclude_product_categories) && !empty($exclude_product_categories)) {
        $product_cats = 'NOT IN';
    } else {
        $product_cats = 'IN';
    }
    // Related Product Display Category
    $tax_slugs = array();
    if(!empty($category)){
        foreach (explode(",", $category) as $tax_id) {
            $tax_cat = get_term_by('id', $tax_id, 'product_cat', 'ARRAY_A');
            $tax_slugs[] = $tax_cat['slug']; 
        }
    } 
    if (isset($tax_slugs) && !empty($tax_slugs)) {
        // Display All Product with Category 
        $args['tax_query'][] = array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $tax_slugs,
                'operator' => $product_cats,
            ),
        );
    }

    $the_query = new \WP_Query($args);   
    if ( $the_query->have_posts() ) {
        if ($rpmw_show_heading == 'true') {
            $tag = $rpmw_heading_html_tag;?>
            <<?php echo esc_attr($tag); ?> class="related-product-heading" style="color: <?php echo esc_attr($rpmw_heading_color); ?>; text-align: <?php echo esc_attr($rpmw_heading_aligment); ?>; margin-bottom: <?php echo esc_attr($rpmw_heading_spacing); ?>;">
            <?php echo esc_html($rpmw_heading ?: __('Related Products', 'related-products-manager-woocommerce')); ?>
            </<?php echo esc_attr($tag); ?>>
            <?php } ?>
            <div class="vc_grid-container-wrapper vc_clearfix <?php echo esc_attr($css_class); ?>" style='<?php esc_attr($rpmw_design_options); ?>' >
                <div class="related-products vc_grid-container vc_product-grid-gap product_border_radius related-products_contanair-<?php echo esc_html($rpmw_columns);?>" >
                    <?php while ($the_query->have_posts()) : $the_query->the_post(); global $product; global $post; ?> 
                        <div class="related-products_contanair product-container"> 
                            <div class="related-products_img product_thumbnail"> <?php   
                                if ($rpmw_show_sale_badge == 'true') {?>
                                <div class="related-product-sale-price">
                                <?php
                                    if( $product->is_on_sale() ) {?>
                                    <span class="onsale"><?php
                                            echo esc_attr($rpmw_sale_badge);      
                                    }?></span>
                                </div>
                            <?php } ?>                                      
                                <?php if ( has_post_thumbnail() ) {?> 
                                    <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_post_thumbnail(); ?></a>
                                <?php } ?>
                        </div>                             
                        <div class="related-products_contant" ><?php
                            if ($rpmw_show_category == 'true') {?>
                            <div class="related-product-category">
                                <?php echo wp_kses_post(wc_get_product_category_list( $post->ID ));?>
                            </div> <?php
                                } 
                            if ($rpmw_show_tag == 'true') {?>
                                <div class="related-product-tag" style="color: <?php echo esc_attr($rpmw_tag_color); ?>;">
                                    <?php echo wp_kses_post(wc_get_product_tag_list( $post->ID ));?>
                                </div>
                                <?php
                            } ?>
                            <h4 class="related_product_title"><?php the_title(); ?></h4>    
                            <div class="related_product_star_rating">
                            <?php if ($average = $product->get_average_rating()) : ?>
                                <?php echo '<div class="star-rating" title="' . esc_attr(sprintf(
                                        /* translators: Placeholder is for the rating */
                                        __( 'Rated %s out of 5', 'woocommerce' ), $average)) . '">
                                        <span style="width:' . esc_attr((($average / 5) * 100)) . '%">' . esc_html($average) . esc_html__( 'out of 5', 'woocommerce' ) . '</span></div>'; ?>
                                <?php endif; ?>
                            </div>       
                            <div class="related-price">
                                <?php echo wp_kses_post($product->get_price_html()); ?>
                            </div>
                            <div class="view-btn">
                                <a class="button" href="<?php the_permalink(); ?>"><?php
                                    echo esc_attr($rpmw_text_button); ?></a>
                            </div>
                        </div>   
                    </div> 
                    <?php
                endwhile;
                wp_reset_postdata();?>
            <?php
        }
        echo '</div>';
    echo '</div>';  
    ?>  

    <!-- Wb-Bakery css -->
    <style>
    .related-products.vc_grid-container.vc_product-grid-gap {
        grid-column-gap: <?php echo esc_attr($rpmw_grid_gap) . 'px !important'; ?>;
        grid-row-gap: <?php echo esc_attr($rpmw_rows_gap) . 'px'; ?>;
        display: grid;
        grid-column-gap: 20px;
    }
    .related-products_img.product_thumbnail a img {
        border-radius: <?php echo esc_attr($rpmw_layout_image_border_radius) . 'px'; ?>;
        opacity: 0.9 ;
    }
    .related-products_img.product_thumbnail {
        background-color: <?php echo esc_attr($rpmw_layout_image_overlay); ?>;
        border-radius: <?php echo esc_attr($rpmw_layout_image_border_radius) . 'px'; ?>;
    }
    .related-product-sale-price span.onsale {
        color: <?php echo esc_attr($rpmw_sale_color); ?>;
        background-color : <?php echo esc_attr($rpmw_sale_background_color); ?>;
        border-radius: <?php echo esc_attr($rpmw_sale_border_radius) . 'px'; ?>;
    }
    .related-product-category {
        margin-bottom:<?php echo esc_attr($rpmw_category_spacing); ?> ;
        text-align:<?php echo esc_attr($rpmw_category_aligment); ?> ;
        font-size:<?php echo esc_attr($rpmw_category_font_size); ?> ;
    }
    .related-product-tag {
        margin-bottom:<?php echo esc_attr($rpmw_tag_spacing); ?> ;
        text-align:<?php echo esc_attr($rpmw_tag_aligment); ?> ;
        font-size:<?php echo esc_attr($rpmw_tag_font_size); ?> ;
    }
    .related-product-category a {
        color : <?php echo esc_attr($rpmw_category_color); ?>;
    }
    .related-product-tag a {
        color : <?php echo esc_attr($rpmw_tag_color); ?>;
    }
    .related-product-category a:hover {
        color : <?php echo esc_attr($rpmw_category_hover_color); ?>;
    }
    .related-product-tag a:hover{
        color : <?php echo esc_attr($rpmw_tag_hover_color); ?>;
    }
    h4.related_product_title {
        color:<?php echo esc_attr($rpmw_product_name_color); ?>;
        margin-bottom:<?php echo esc_attr($rpmw_product_name_spacing); ?> ;
        text-align:<?php echo esc_attr($rpmw_product_name_aligment); ?> ;
        font-size:<?php echo esc_attr($rpmw_product_name_font_size); ?> ;
    }
    h4.related_product_title:hover {
        color : <?php echo esc_attr($rpmw_product_name_hover_color); ?>;
    }
    .related-price {
        color:<?php echo esc_attr($rpmw_product_price_color); ?>;
        margin-bottom:<?php echo esc_attr($rpmw_product_price_spacing); ?> ;
        text-align:<?php echo esc_attr($rpmw_product_price_aligment); ?> ;
        font-size:<?php echo esc_attr($rpmw_product_price_font_size); ?> ;
    }
    .related-price:hover {
        color : <?php echo esc_attr($rpmw_product_price_hover_color); ?>;
    }
    .related_product_star_rating {
        justify-content:<?php echo esc_attr($rpmw_rating_aligment); ?>;
    }
    .related_product_star_rating .star-rating {
        font-size: <?php echo esc_attr($rpmw_rating_star); ?>;
        margin-right:<?php echo esc_attr($rpmw_rating_star_spacing_left); ?>;
        margin-left:<?php echo esc_attr($rpmw_rating_star_spacing_left); ?>;
        margin-bottom:<?php echo esc_attr($rpmw_rating_star_spacing_bottom); ?>;
    }
    .related_product_star_rating .star-rating ::before {
        color:<?php echo esc_attr($rpmw_rating_star_color); ?>;
    }
    .related_product_star_rating .star-rating::before {
        color:<?php echo esc_attr($rpmw_rating_star_unmarked_color); ?>;
    }
    .view-btn a {
        <?php echo esc_attr($rpmw_button_url);?>
    }
    .view-btn a.button {
        color:<?php echo esc_attr($rpmw_button_text_color); ?>;
        background-color : <?php echo esc_attr($rpmw_button_background_color); ?>;
        border-radius: <?php echo esc_attr($rpmw_button_border_radius) . 'px'; ?>;
    }  
    .view-btn a.button:hover {
        color:<?php echo esc_attr($rpmw_button_text_hover_color); ?>;
        background-color : <?php echo esc_attr($rpmw_button_background_hover_color) ?>; 
    }
    .view-btn {
        text-align:<?php echo esc_attr($rpmw_button_aligment); ?> ;
    
    }
    .related-product-sale-price span.onsale{
        font-size:<?php echo esc_attr($rpmw_sale_size); ?>;
    }
    </style>
<?php
    return ob_get_clean();
}

add_shortcode('related_product_manager_card_layout', 'related_product_manager_shortcode');

// Related Product dropdown for category and tag shortcode 

vc_add_shortcode_param( 'dropdown_multi', 'dropdown_multi_settings_field' );
function dropdown_multi_settings_field( $param, $value ) {
    $param_line = ' ';
    $param_line .= '<select multiple name="'. esc_attr( $param['param_name'] ).'" class="wpb_vc_param_value wpb-input wpb-select '. esc_attr( $param['param_name'] ).' '. esc_attr($param['type']).'">';
    foreach ( $param['value'] as $text_val => $val ) {
        if ( is_numeric($text_val) && (is_string($val) || is_numeric($val)) ) {
            $text_val = $val;
        }
        // $text_val = __($text_val, 'related-products-manager-woocommerce');
        $selected = ' ';

        if(!is_array($value)) {
            $param_value_arr = explode(',',$value);
        } else {
            $param_value_arr = $value;
        }

        if ($value!==' ' && in_array($val, $param_value_arr)) {
            $selected = ' selected="selected"';
        }
        $param_line .= '<option class="'.$val.'" value="'.$val.'"'.$selected.'>'.$text_val.'</option>';
    }
    $param_line .= '</select>';

    return  $param_line;
}

//Select category for dropdown
$link_category = array();
$link_cats = get_terms( 'product_cat' );
if ( is_array( $link_cats ) && ! empty( $link_cats ) ) {
	foreach ( $link_cats as $link_cat ) {
		if ( is_object( $link_cat ) && isset( $link_cat->name, $link_cat->term_id ) ) {
			$link_category[ $link_cat->name ] = $link_cat->term_id;
		}
	}
}

/*
* How many product columns
*/
$rpmw_columns = array(
   __('1', 'related-products-manager-woocommerce') => '1',
   __('2', 'related-products-manager-woocommerce') => '2',
   __('3', 'related-products-manager-woocommerce') => '3',
   __('4', 'related-products-manager-woocommerce') => '4',
   __('6', 'related-products-manager-woocommerce') => '6'
);

/*
 * Title HTML Tag
 */
$post_title_html_tag = array(
    __('H1', 'related-products-manager-woocommerce') => 'h1',
    __('H2', 'related-products-manager-woocommerce') => 'h2',
    __('H3', 'related-products-manager-woocommerce') => 'h3',
    __('H4', 'related-products-manager-woocommerce') => 'h4',
    __('H5', 'related-products-manager-woocommerce') => 'h5',
    __('H6', 'related-products-manager-woocommerce') => 'h6',
    __('div', 'related-products-manager-woocommerce') => 'div',
    __('span', 'related-products-manager-woocommerce') => 'span',
    __('p', 'related-products-manager-woocommerce') => 'p'
);

/*
 * Order By
 */
$product_order_by = array(
    __('Id', 'related-products-manager-woocommerce') => 'post_id',
    __('Date', 'related-products-manager-woocommerce') => 'post_date',
    __('Title', 'related-products-manager-woocommerce') => 'post_title',
    __('Random', 'related-products-manager-woocommerce') => 'rand',
    __('Price', 'related-products-manager-woocommerce') => 'price',
    __('Rating', 'related-products-manager-woocommerce') => 'rating', 
    __( 'Modified',  'related-products-manager-woocommerce' ) => 'modified', 
);

/*
 * Sort By
 */
$product_sort_by = array(
    __('ASC', 'related-products-manager-woocommerce') => 'asc',
    __('DESC', 'related-products-manager-woocommerce') => 'desc',
);

/*
 * Related product Visual Composer Elements
 */
$related_product_fields = array(
    array(
        'type' => 'checkbox',
        'heading' => esc_html__('Show Heading', 'related-products-manager-woocommerce'),
        'param_name' => 'rpmw_show_heading',
        'value' => __('true', 'related-products-manager-woocommerce'),
        'admin_label' => true,
        'std' => 'true',
        'description' => __('Check/Uncheck to show/hide the title.', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'textfield',
        'heading' => esc_html__('Heading', 'related-products-manager-woocommerce'),
        'param_name' => 'rpmw_heading',
        'value' => ' ' ,
        'admin_label' => true,
        'description' => esc_html__('Enter heading.', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'dropdown',
        'heading' => esc_html__('Title HTML Tag', 'related-products-manager-woocommerce'),
        'param_name' => 'rpmw_heading_html_tag',
        'value' => $post_title_html_tag,
        'std' => 'h2',
        'group' => 'Heading Style',
        'admin_label' => true,
        'description' => esc_html__('Select title HTML tag.', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'dropdown',
        'heading' => __( 'Alignment',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_heading_aligment',
        'value' => array(
          __( 'Left',  'related-products-manager-woocommerce'  ) => 'left',
          __( 'Center',  'related-products-manager-woocommerce'  ) => 'center',
          __( 'Right',  'related-products-manager-woocommerce'  ) => 'right',
        ),
        'std' => 'left',
        'group' => 'Heading Style',
        "description" => __( "Select Alignment.", 'related-products-manager-woocommerce' )
    ),
    array(
        'type' => 'textfield',
        'heading' => __( 'Spacing For Heading ',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_heading_spacing',
        'value' => ' ' ,
        'group' => 'Heading Style',
        'description' => __('Set the Heading bottom spacing eg. 25px or 1.5em ', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'textfield',
        'heading' => esc_html__('Display Number of Product', 'related-products-manager-woocommerce'),
        'param_name' => 'rpmw_number_of_product',
        'value' => __('6', 'related-products-manager-woocommerce'),
        'admin_label' => true,
        'description' => esc_html__('Enter number of product. e.g. 6.', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'dropdown',
        'heading' => esc_html__('Columns', 'related-products-manager-woocommerce'),
        'param_name' => 'rpmw_columns',
        'value' => $rpmw_columns,
        'std' => '3',
        'admin_label' => true,
        'description' => esc_html__('Select product columns.', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'dropdown',
        'heading' => esc_html__('Order By', 'related-products-manager-woocommerce'),
        'param_name' => 'rpmw_product_order_by',
        'value' => $product_order_by,
        'admin_label' => true,
        'description' => esc_html__('Select order by.', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'dropdown',
        'heading' => esc_html__('Sort By', 'related-products-manager-woocommerce'),
        'param_name' => 'rpmw_product_sort_by',
        'value' => $product_sort_by,
        'admin_label' => true,
        'description' => esc_html__('Select sort by.', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'checkbox',
        'heading' => esc_html__('Show Category', 'related-products-manager-woocommerce'),
        'param_name' => 'rpmw_show_category',
        'value' => __('true', 'related-products-manager-woocommerce'),
        'admin_label' => true,
        'std' => 'true',
        'description' => __('Check/Uncheck to show/hide the title.', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'checkbox',
        'heading' => esc_html__('Show Tag', 'related-products-manager-woocommerce'),
        'param_name' => 'rpmw_show_tag',
        'value' => __('true', 'related-products-manager-woocommerce'),
        'admin_label' => true,
        'std' => 'true',
        'description' => __('Check/Uncheck to show/hide the title.', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'checkbox',
        'heading' => esc_html__('Show Sale Badge', 'related-products-manager-woocommerce'),
        'param_name' => 'rpmw_show_sale_badge',
        'value' => __('true', 'related-products-manager-woocommerce'),
        'admin_label' => true,
        'std' => 'true',
        'description' => __('Check/Uncheck to show/hide the title.', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'textfield',
        'heading' => esc_html__('Sale Badge Title', 'related-products-manager-woocommerce'),
        'param_name' => 'rpmw_sale_badge',
        'value' => __('Sale!', 'related-products-manager-woocommerce'),
        'admin_label' => true,
        'description' => esc_html__('Enter Sale Badge Title.', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'dropdown_multi',
        'heading' => __( 'Select Category', 'related-products-manager-woocommerce' ),
        'param_name' => 'category',
        'value' => $link_category,
        'admin_label' => true,
        'description' => __('Please select the categories you would like to display for your product. You can select multiple categories too (ctrl + click on PC and command + click on Mac).', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'checkbox',
        'heading' => esc_html__('Exclude Above Categories', 'related-products-manager-woocommerce'),
        'param_name' => 'exclude_product_categories',
        'value' => __('false', 'related-products-manager-woocommerce'),
        'description' => __('Exclude Above Categories.', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'textfield',
        'heading' => __( 'Button Text ',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_text_button',
        'value' => ' ',
    ), 
    array(
        'type' => 'textfield',
        'heading' => esc_html__('Grid Gap', 'related-products-manager-woocommerce'),
        'param_name' => 'rpmw_grid_gap',
        'value' => __('30', 'related-products-manager-woocommerce'),
        'group' => 'Layout',
        'description' => esc_html__('Set layout grid gap e.g. 30', 'related-products-manager-woocommerce')

    ),
    array(
        'type' => 'textfield',
        'heading' => esc_html__('Rows Gap', 'related-products-manager-woocommerce'),
        'param_name' => 'rpmw_rows_gap',
        'value' => __('35', 'related-products-manager-woocommerce'),
        'group' => 'Layout',
        'description' => esc_html__('Set layout row gap e.g. 35', 'related-products-manager-woocommerce')
    ),
    array(
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Heading Text Color', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_heading_color",
        "value" => '#e74c3c', 
        'group' => 'Heading Style',
        "description" => ' '
    ),
    array(
        'type' => 'textfield',
        'heading' => __( 'Border Radius',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_layout_image_border_radius',
        'value' => ' ' ,
        'group' => 'Image',
        'description' => esc_html__('Set layout Image border radius', 'related-products-manager-woocommerce')
    ),
    array(  
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Image Overlay', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_layout_image_overlay",
        "value" => ' ',
        'group' => 'Image',
        "description" => __( "Set overlay for layout Image", 'related-products-manager-woocommerce' )
    ),
    array(
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Sale Badge Text Color', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_sale_color",
        "value" => '#e74c3c', 
        'group' => 'Sale Badge Style',
        "description" => ' '
    ),
    array(
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Sale Badge Background Color', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_sale_background_color",
        "value" => '#e74c3c', 
        'group' => 'Sale Badge Style',
        "description" => ' '
    ),
    array(
        'type' => 'textfield',
        'heading' => __( 'Sale Badge Size ',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_sale_size',
        'value' => ' ',
        'group' => 'Sale Badge Style',
        'description' => __('Set the sale size eg. 25px or 1.5em ', 'related-products-manager-woocommerce')
    ),
    
    array(
        'type' => 'textfield',
        'heading' => __( 'Border Radius',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_sale_border_radius',
        'value' => ' ',
        'group' => 'Sale Badge Style',
        'description' => esc_html__('Set Sale Badge border radius', 'related-products-manager-woocommerce')
    ),
    array(
        "type" => "label",
        "param_name" => "rpmw_category_label",
        "class" => ' ',
        "heading" => esc_html__('Product Category', 'related-products-manager-woocommerce'),
        "value" => ' ', 
        'group' => 'Content',
        "description" => ' '
        
    ),
    array(
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Category Text Color', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_category_color",
        "value" => '#e74c3c', 
        'group' => 'Content',
        'edit_field_class' => 'vc_col-sm-6',
        "description" => ' '
    ),
    array(
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Category Text Hover Color', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_category_hover_color",
        "value" => '#e74c3c', 
        'group' => 'Content',
        'edit_field_class' => 'vc_col-sm-6',
        "description" => ' '
    ),
    array(
        'type' => 'textfield',
        'heading' => __( 'Category Font Size ',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_category_font_size',
        'value' => ' ',
        'group' => 'Content',
        'description' => __('Set the font size eg. 25px or 1.5em ', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'dropdown',
        'heading' => __( 'Select Category Alignment',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_category_aligment',
        'value' => array(
          __( 'Left',  'related-products-manager-woocommerce'  ) => 'left',
          __( 'Center',  'related-products-manager-woocommerce'  ) => 'center',
          __( 'Right',  'related-products-manager-woocommerce'  ) => 'right',
        ),
        'std' => 'left',
        'group' => 'Content',
        "description" => __( "Select Alignment For Category.", 'related-products-manager-woocommerce' )
    ),
    array(
        'type' => 'textfield',
        'heading' => __( 'Spacing For Category ',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_category_spacing',
        'value' => ' ',
        'group' => 'Content',
        'description' => __('Set the category title bottom spacing eg. 25px or 1.5em ', 'related-products-manager-woocommerce')
    ),
    array(
        "type" => "label",
        "class" => ' ',
        "param_name" => "rpmw_tag_label",
        "heading" => esc_html__('Product Tag', 'related-products-manager-woocommerce'),
        "value" => ' ', 
        'group' => 'Content',
        "description" => ' '
    ), 
    array(
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Tag Text Color', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_tag_color",
        "value" => '#e74c3c', 
        'group' => 'Content',
        'edit_field_class' => 'vc_col-sm-6',
        "description" => ' '
    ),
    array(
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Tag Text Hover Color', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_tag_hover_color",
        "value" => '#e74c3c', 
        'group' => 'Content',
        'edit_field_class' => 'vc_col-sm-6',
        "description" => ' '
    ),
    array(
        'type' => 'textfield',
        'heading' => __( 'Tag Font Size ',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_tag_font_size',
        'value' => ' ',
        'group' => 'Content',
        'description' => __('Set the font size eg. 25px or 1.5em ', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'dropdown',
        'heading' => __( 'Select Tag Alignment',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_tag_aligment',
        'value' => array(
          __( 'Left',  'related-products-manager-woocommerce'  ) => 'left',
          __( 'Center',  'related-products-manager-woocommerce'  ) => 'center',
          __( 'Right',  'related-products-manager-woocommerce'  ) => 'right',
        ),
        'std' => 'left',
        'group' => 'Content',
        "description" => __( "Select Alignment For Tag.", 'related-products-manager-woocommerce' )
    ),
    array(
        'type' => 'textfield',
        'heading' => __( 'Spacing For Tag ',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_tag_spacing',
        'value' => ' ',
        'group' => 'Content',
        'description' => __('Set the tag title bottom spacing eg. 25px or 1.5em ', 'related-products-manager-woocommerce')
    ),
    array(
        "type" => "label",
        "class" => ' ',
        "param_name" => "rpmw_product_name_label",
        "heading" => esc_html__('Product Name', 'related-products-manager-woocommerce'),
        "value" => ' ', 
        'group' => 'Content',
        "description" => ' '
    ), 
    array(
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Product Name Color', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_product_name_color",
        "value" => '#e74c3c', 
        'group' => 'Content',
        'edit_field_class' => 'vc_col-sm-6',
        "description" => ' '
    ),
    array(
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Product Name Hover Color', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_product_name_hover_color",
        "value" => '#e74c3c', 
        'group' => 'Content',
        'edit_field_class' => 'vc_col-sm-6',
        "description" => ' '
    ),
    array(
        'type' => 'textfield',
        'heading' => __( 'Product Name Font Size ',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_product_name_font_size',
        'value' => ' ',
        'group' => 'Content',
        'description' => __('Set the font size eg. 25px or 1.5em ', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'dropdown',
        'heading' => __( 'Product Name Alignment',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_product_name_aligment',
        'value' => array(
          __( 'Left',  'related-products-manager-woocommerce'  ) => 'left',
          __( 'Center',  'related-products-manager-woocommerce'  ) => 'center',
          __( 'Right',  'related-products-manager-woocommerce'  ) => 'right',
        ),
        'std' => 'left',
        'group' => 'Content',
        "description" => __( "Select Alignment For Tag.", 'related-products-manager-woocommerce' )
    ),
    array(
        'type' => 'textfield',
        'heading' => __( 'Spacing For Product Name ',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_product_name_spacing',
        'value' => ' ',
        'group' => 'Content',
        'description' => __('Set the Product Name bottom spacing eg. 25px or 1.5em ', 'related-products-manager-woocommerce')
    ),
    array(
        "type" => "label",
        "class" => ' ',
        "param_name" => "rpmw_product_price_label",
        "heading" => esc_html__('Product Price', 'related-products-manager-woocommerce'),
        "value" => ' ', 
        'group' => 'Content',
        "description" => ' '
    ), 
    array(
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Product Price Color', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_product_price_color",
        "value" => '#e74c3c', 
        'group' => 'Content',
        'edit_field_class' => 'vc_col-sm-6',
        "description" => ' '
    ),
    array(
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Product Price Hover Color', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_product_price_hover_color",
        "value" => '#e74c3c', 
        'group' => 'Content',
        'edit_field_class' => 'vc_col-sm-6',
        "description" => ' '
    ),
    array(
        'type' => 'textfield',
        'heading' => __( 'Product Price Font Size ',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_product_price_font_size',
        'value' => ' ',
        'group' => 'Content',
        'description' => __('Set the font size eg. 25px or 1.5em ', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'dropdown',
        'heading' => __( 'Product Price Alignment',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_product_price_aligment',
        'value' => array(
          __( 'Left',  'related-products-manager-woocommerce'  ) => 'left',
          __( 'Center',  'related-products-manager-woocommerce'  ) => 'center',
          __( 'Right',  'related-products-manager-woocommerce'  ) => 'right',
        ),
        'std' => 'left',
        'group' => 'Content',
        "description" => __( "Select Alignment For Tag.", 'related-products-manager-woocommerce' )
    ),
    array(
        'type' => 'textfield',
        'heading' => __( 'Spacing For Product Price ',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_product_price_spacing',
        'value' => ' ',
        'group' => 'Content',
        'description' => __('Set the Product Price bottom spacing eg. 25px or 1.5em ', 'related-products-manager-woocommerce')
    ),
    array(
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Rating Star Color', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_rating_star_color",
        "value" => '#e74c3c', 
        'group' => 'Rating Star Style',
        "description" => ' '
    ),
    array(
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Rating Star Unmarked Color', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_rating_star_unmarked_color",
        "value" => '#e74c3c', 
        'group' => 'Rating Star Style',
        "description" => ' '
    ),
    array(
        'type' => 'textfield',
        'heading' => __( 'Rating Star Size ',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_rating_star',
        'value' => ' ',
        'group' => 'Rating Star Style',
        'description' => __('Set the size eg. 25px or 1.5em ', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'dropdown',
        'heading' => __( 'Select Alignment',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_rating_aligment',
        'value' => array(
          __( 'Left',  'related-products-manager-woocommerce'  ) => 'left',
          __( 'Center',  'related-products-manager-woocommerce'  ) => 'center',
          __( 'Right',  'related-products-manager-woocommerce'  ) => 'flex-end',
        ),
        'std' => 'center',
        'group' => 'Rating Star Style',
        "description" => __( "Select Alignment.", 'related-products-manager-woocommerce' )
    ),
    array(
        'type' => 'textfield',
        'heading' => __( 'Spacing Bottom ',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_rating_star_spacing_bottom',
        'value' => ' ',
        'group' => 'Rating Star Style',
        'description' => __('Set the Rating Star Bottom spacing eg. 25px or 1.5em ', 'related-products-manager-woocommerce')
    ),
    array(
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Text Color', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_button_text_color",
        "value" => '#e74c3c', 
        'group' => 'Button Style',
        'edit_field_class' => 'vc_col-sm-6',
        "description" => ' '
    ),
    array(
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Background Color', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_button_background_color",
        "value" => '#e74c3c', 
        'group' => 'Button Style',
        'edit_field_class' => 'vc_col-sm-6',
        "description" => ' '
    ),
    array(
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Text Hover Color', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_button_text_hover_color",
        "value" => '#e74c3c', 
        'group' => 'Button Style',
        'edit_field_class' => 'vc_col-sm-6',
        "description" => ' '
    ),
    array(
        "type" => "colorpicker",
        "class" => ' ',
        "heading" => esc_html__('Background Hover Color', 'related-products-manager-woocommerce'),
        "param_name" => "rpmw_button_background_hover_color",
        "value" => '#e74c3c', 
        'group' => 'Button Style',
        'edit_field_class' => 'vc_col-sm-6',
        "description" => ' '
    ),
    array(
        'type' => 'textfield',
        'heading' => __( 'Border Radius',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_button_border_radius',
        'value' => ' ',
        'group' => 'Button Style',
        'description' => esc_html__('Set border radius', 'related-products-manager-woocommerce')
    ),
    array(
        'type' => 'dropdown',
        'heading' => __( 'Alignment',  'related-products-manager-woocommerce' ),
        'param_name' => 'rpmw_button_aligment',
        'value' => array(
          __( 'Left',  'related-products-manager-woocommerce'  ) => 'left',
          __( 'Center',  'related-products-manager-woocommerce'  ) => 'center',
          __( 'Right',  'related-products-manager-woocommerce'  ) => 'right',
        ),
        'std' => 'left',
        'group' => 'Button Style',
    ),
    array(  
        "type" => "css_editor",
        "class" => ' ',
        "heading" => __( "Field Label", 'related-products-manager-woocommerce' ),
        "param_name" => "rpmw_design_options",
        "value" => ' ', 
        'group' => 'Design Options',
        "description" => __( "Enter description.", 'related-products-manager-woocommerce' )
    ),
    
);
 
/*
 * Params
 */
$params = array(
    'name' => esc_html__('Related Product Manager Layout', 'related-products-manager-woocommerce'),
    'description' => esc_html__('Display Related Product Manager Layout.', 'related-products-manager-woocommerce'),
    'base' => 'related_product_manager_card_layout',
    'class' => 'cewb_element_wrapper',
    'controls' => 'full',
    'icon' => ' ',
    'category' => esc_html__('Related Product Manager', 'related-products-manager-woocommerce'),
    'show_settings_on_create' => true,
    'params' => $related_product_fields
);
vc_map($params);
