<?php
// The all-products template for shop/products page. Used in the shortcode [wpec_show_all_products]

wp_enqueue_style('dashicons');
wp_enqueue_style('wpec-all-products-css');

ob_start();
//Page
?>
<div class="wpec-post-grid wpec-grid">
	_%search_box%_
	<div id="wpec-members-list">
		_%products_list%_
	</div>
	_%pagination%_
</div>
<?php
$tpl['page'] = ob_get_clean();
ob_start();
$strSearch      = __('Search', 'wp-express-checkout');
$strClearSearch = __('Clear search', 'wp-express-checkout');
$strViewItem    = __('View Item', 'wp-express-checkout');
//Search box
?>
<div id="wpec-sort-wrapper">	
<form method="GET" id="wpec-sort-by-form">
<select id="wpec-sort-by" name="wpec-sortby">
	<option <?php echo ("id-desc"==$sort_by?"selected='selected'":"")?> value="id-desc">Sort by latest</option>
	<option <?php echo ("id-asc"==$sort_by?"selected='selected'":"")?> value="id-asc">Sort by chronological order</option>
	<option <?php echo ("title-asc"==$sort_by?"selected='selected'":"")?> value="title-asc">Sort by title</option>
	<option <?php echo ("price-asc"==$sort_by?"selected='selected'":"")?> value="price-asc">Sort by price (low to high)</option>
	<option <?php echo ("price-desc"==$sort_by?"selected='selected'":"")?> value="price-desc">Sort by price(high to low)</option>
</select>
</form>
</div>
<div id="wpec-search-wrapper">	
<form id="wpec-search-form" method="GET">
	<div class="wpec-listing-search-field">
		<input type="text" class="wpec-search-input" name="wpec_search" value="_%search_term%_" placeholder="<?php echo esc_attr($strSearch); ?> ...">
		<button type="submit" class="wpec-search-button" value="<?php echo esc_attr($strSearch); ?>" title="<?php echo esc_attr($strSearch); ?>"><span class="dashicons dashicons-search"></button>
	</div>
</form>
</div>
<div class="wpec-search-res-text">
	_%search_result_text%__%clear_search_button%_
</div>
<?php
$tpl['search_box']          = ob_get_clean();
$tpl['clear_search_button'] = ' <a href="_%clear_search_url%_">' . $strClearSearch . '</a>';
ob_start();
//Member item
?>

<div class="wpec-grid-item wpec-product-id-%[product_id]%">
	<div class="wpec-tpl-ap-product-thumb"><img src="%[product_thumb]%"></div>
	<div class="wpec-tpl-ap-product-price">%[product_price]%</div>
	<div class="wpec-tpl-ap-product-name">%[product_name]%</div>
	%[view_product_btn]%
</div>



<?php
$tpl['products_item']      = ob_get_clean();
$tpl['products_list']      = '';
$tpl['products_per_row']   = 3;
$tpl['products_row_start'] = '<div class="wpec-grid-row">';
$tpl['products_row_end']   = '</div>';
ob_start();
//Pagination
?>
<div class="wpec-pagination">
	<ul>
		_%pagination_items%_
	</ul>
</div>
<?php
$tpl['pagination'] = ob_get_clean();
//Pagination item
$tpl['pagination_item'] = '<li><a href="%[url]%">%[page_num]%</a></li>';
//Pagination item - current page
$tpl['pagination_item_current'] = '<li><span>%[page_num]%</span></li>';

//Profile button
$tpl['view_product_btn'] = '<div class="wpec-view-product-btn"><a href="%[product_url]%" class="wpec-view-product-lnk"><button>' . $strViewItem . '</button></a></div>';
