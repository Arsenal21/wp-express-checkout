<?php
// The display-all-products-from-category template. Used in the shortcode [wpec_show_products_from_category]

/* @var $args array Template arguments */

wp_enqueue_style( 'dashicons' );
wp_enqueue_style( 'wpec-all-products-css' );

$wpec_shortcode = \WP_Express_Checkout\Shortcodes::get_instance();

$params   = isset( $args['sc_params'] ) ? $args['sc_params'] : array();
$products = isset( $args['products'] ) ? $args['products'] : array();

$search_box         = isset( $params['search_box'] ) ? boolval( $params['search_box'] ) : false;
$search             = isset( $args['search'] ) ? $args['search'] : '';
$clear_search_url   = remove_query_arg( array( 'wpec_search', 'wpec_page' ) );
$search_result_text = $products->found_posts === 0 ? __( 'Nothing found for', 'wp-express-checkout' ) . ' "%s".' : __( 'Search results for', 'wp-express-checkout' ) . ' "%s".';
$search_result_text = sprintf( $search_result_text, htmlentities( $search ) );
$search_term        = htmlentities( $search );

$products_per_row = 3;
$i = $products_per_row;
$pages = $products->max_num_pages;
$page = isset( $args['page'] ) ? absint($args['page']) : 1;
$sort_by = isset( $args['sort_by'] ) ? $args['sort_by'] : '';

?>
<div class="wpec_shop_products">
    <div class="wpec-post-grid wpec-grid">
        <div id="wpec-sort-wrapper">
            <form method="GET" id="wpec-sort-by-form">
                <select id="wpec-sort-by" name="wpec-sortby">
                    <option <?php echo ( $sort_by == "id-desc" ? "selected='selected'" : "" )?> value="id-desc">Sort by latest</option>
                    <option <?php echo ( $sort_by == "id-asc" ? "selected='selected'" : "" )?> value="id-asc">Sort by chronological order</option>
                    <option <?php echo ( $sort_by == "title-asc" ? "selected='selected'" : "" )?> value="title-asc">Sort by title</option>
                    <option <?php echo ( $sort_by == "price-asc" ? "selected='selected'" : "" )?> value="price-asc">Sort by price (low to high)</option>
                    <option <?php echo ( $sort_by == "price-desc" ? "selected='selected'" : "" )?> value="price-desc">Sort by price(high to low)</option>
                </select>
            </form>
        </div>
        <?php if ( !empty( $search_box ) ) { ?>
        <div id="wpec-search-wrapper">
            <form id="wpec-search-form" method="GET">
                <div class="wpec-listing-search-field">
                    <input type="text" class="wpec-search-input" name="wpec_search" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php _e( 'Search', 'wp-express-checkout' ); ?>...">
                    <button type="submit" class="wpec-search-button" title="<?php _e( 'Search', 'wp-express-checkout' ) ?>">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                </div>
            </form>
        </div>
        <?php if ( ! empty( $search ) ) { ?>
            <div class="wpec-search-res-text">
                <?php echo wp_kses_post( $search_result_text ) ?> <a href="<?php echo esc_url( $clear_search_url ) ?>"><?php _e( 'Clear search', 'wp-express-checkout' ) ?></a>
            </div>
        <?php } ?>
		<?php } ?>

        <div id="wpec-members-list">
            <div class="wpec-grid-row">
				<?php

				while ( $products->have_posts() ) {
					$products->the_post();

					$i--;
					if ( $i < 0 ) {
						// start new row
						echo '</div><div class="wpec-grid-row">';
						$i = $products_per_row - 1;
					}

					$product_id = get_the_ID();

					try {
						$product = \WP_Express_Checkout\Products::retrieve( $product_id );
					} catch ( Exception $exc ) {
						return $wpec_shortcode->show_err_msg( $exc->getMessage() );
					}

					$thumb_url = $product->get_thumbnail_url();
					$thumb_url = !empty($thumb_url) ? $thumb_url : WPEC_PLUGIN_URL . '/assets/img/product-thumb-placeholder.png';

					$price = $product->get_price();

					$price_args = wp_parse_args(
						array(
							'name'                  => get_the_title( $product_id ),
							'price'                 => (float) $product->get_price(),
							'shipping'              => $product->get_shipping(),
							'shipping_per_quantity' => $product->get_shipping_per_quantity(),
							'tax'                   => $product->get_tax(),
							'quantity'              => $product->get_quantity(),
							'product_id'            => $product_id,
						),
						array(
							'price'    => 0,
							'shipping' => 0,
							'tax'      => 0,
							'quantity' => 1,
						)
					);

					$price_tag = $wpec_shortcode->generate_price_tag( $price_args );
					?>

                    <div class="wpec-grid-item wpec-product-id-<?php echo esc_attr( $product_id ) ?>">
                        <div class="wpec-tpl-ap-product-thumb"><img src="<?php echo esc_attr( $thumb_url ) ?>"></div>
                        <div class="wpec-tpl-ap-product-price"><?php echo wp_kses_post( $price_tag ) ?></div>
                        <div class="wpec-tpl-ap-product-name"><?php echo esc_attr( get_the_title() ); ?></div>
                        <div class="wpec-view-product-btn">
                            <a href="<?php echo esc_url( get_permalink() ) ?>" class="wpec-view-product-lnk">
                                <button><?php _e( 'View Item', 'wp-express-checkout' ); ?></button>
                            </a>
                        </div>
                    </div>

					<?php
				}
				?>

            </div>
        </div>
        <!-- Pagination -->
		<?php
		if ( $pages > 1 ) {
			$i = 1;
			echo '<div class="wpec-pagination"><ul>';
			while ( $i <= $pages ) {
				if ( $i != $page ) {
					echo '<li><a href="' . esc_url( add_query_arg( 'wpec_page', $i ) ) . '">' . $i . '</a></li>';
				} else {
					echo '<li><span>' . $i . '</span></li>';
				}

				$i++;
			}
			echo '</ul></div>';
		}
		?>
    </div>
</div>