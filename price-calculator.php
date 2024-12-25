<?php

add_shortcode( "get_product_calculator_price", "get_product_calculator_price" );
function get_product_calculator_price( $atts ) {
    $product =  $atts['product'];
    $width = get_field('min_width',$product);
	$height = get_field('min_height',$product);
	$length = get_field('min_length',$product);
	$additional_costs_ratio = get_field('additional_costs_ratio',$product);
	$profit_ratio = get_field('profit_ratio',$product);
	$total_price = 0;
	$round_calc = (($length*$height*2)+($width*$height*2))/10000;
	$top_calc = ($width*$length)/10000;
	$total_calc = $round_calc+$top_calc;
	$cost_calc = $total_calc*$additional_costs_ratio*35;
	$total_price = round($cost_calc*$profit_ratio);
	if ($total_price>0) {
	  return $total_price;  
	}
}

// add_action( 'woocommerce_before_add_to_cart_button', 'product_custom_calculator', PHP_INT_MAX );
add_shortcode( "product_custom_calculator", "product_custom_calculator" );
function product_custom_calculator(){
	if( get_field('activate_calculator') ){
    $dimensions = ['length', 'width', 'additional_height', 'height'];
    function get_options_select($default_val, $min_val, $max_val, $step_val, $parameter, $type){
      $options_html;
      $elements = ($max_val - $min_val)/$step_val;
        for ($i = 0; $i <= $elements; $i++) {
            $value = $min_val+$step_val*$i;
            $selected = $value == $default_val ? "selected" : "";
            if ($type == "option") {
              $options_html .= '<option value="'. $value .'" '. $selected .'>'. $value .'	cm</option>';
            }else if($type == "li"){
              $options_html .= ' <li class="closed" data-value="'. $value .'" data-parameter="'. $parameter .'" ><a class="calc_value">'. $value .'	cm</a></li>';
            }
        }
        if ($options_html) {
          return $options_html;
        }
      }
      function get_list_dimentions($default_val, $min_val, $max_val, $step_val, $label, $parameter){
        echo '<div class="accordion-item accordion-item-select calc-item" data-name="default_'.$parameter.'">
                 <div class="list-colors">
                     <div class="card-drop">
                         <a class="toggle">
                             <div class="label-active"><span class="label-name">'. $label .'</span><span class="label-text">'.$default_val.'	cm</span></div>
                         </a>
                         <ul>'.  get_options_select($default_val, $min_val, $max_val, $step_val, $parameter, "li")  .'</ul>
                     </div>
                 </div>
             </div>
             <select id="default_'.$parameter.'" name="default_'.$parameter.'" class="hidden_select">'. get_options_select($default_val, $min_val, $max_val, $step_val, $parameter, "option")  .'</select>';
      }
		 ?>
	<div class="price-calculator-wrapper">
    <?php foreach ($dimensions as $i => $parameter):
      $default_val = get_field('default_'.$parameter);
      $step_val = get_field('step_'.$parameter) ? get_field('step_'.$parameter) : 10;
  		$min_val = get_field('min_'.$parameter) ? get_field('min_'.$parameter) : 10;
  		$max_val = get_field('max_'.$parameter) ? get_field('max_'.$parameter) : 400;
      $label = $i;
      if ($parameter == 'length') { $label = 'Length: '; }
      if ($parameter == 'width') { $label = 'Width: '; }
      if ($parameter == 'additional_height') { $label = 'Front height: '; }
      if ($parameter == 'height') { $label = 'Height: '; 	if( get_field('add_additional_height') ){ $label = 'Back height: ';}}
      if( !get_field('add_additional_height') && $parameter == 'additional_height' ){ continue; }else{
				get_list_dimentions($default_val, $min_val, $max_val, $step_val, $label, $parameter);
			}
    endforeach; ?>
	  <input type="hidden" id="additional_costs_ratio" name="additional_costs_ratio" value="<?= the_field('additional_costs_ratio'); ?>"/>
	  <input type="hidden" id="profit_ratio" name="profit_ratio" value="<?= the_field('profit_ratio'); ?>"/>
		<input type="hidden" id="updated_price" name="updated_price" value=""/>
	</div>
	<style>
  .hidden_select{
    display: none;
  }
  .price-calculator-wrapper .accordion-item{
    border: 1px solid #dedede;
		border-bottom: 0;
  }
  .variation-accordion .accordion-item {
    background: #ffffff;
    border: 1px solid #dedede;
    margin: 0;
	}
	.variation-accordion .accordion-item:last-of-type{
		border-bottom: 0;
	}
	.price-calculator-wrapper{
		display: flex;
		flex-direction: column;
		border-bottom: 1px solid #dedede;
	}
	.price-calculator-wrapper .accordion-item[data-name="default_additional_height"]{
		order:1;
	}
	</style>
	<script>
	jQuery(document).ready(function ($) {
	// Set parameters from url if product added
	var cart_url = new URL($(location).attr('href'));
	let cart_width = cart_url.searchParams.get('width');
	let cart_height = cart_url.searchParams.get('height');
	let cart_length = cart_url.searchParams.get('length');
	let cart_additional_height = 	cart_url.searchParams.get('additional_height');
  let dimentions = {
    width: cart_width,
    height: cart_height,
    length: cart_length
  };
  if (cart_additional_height) {
    dimentions.additional_height = cart_additional_height;
  }
  // console.log(dimentions);
	if (cart_width) {
    Object.entries(dimentions).forEach(([parameter, value]) => {
      // console.log(parameter, value);
			// Unselect the previously selected option
		  $(`select#default_${parameter} option`).prop("selected", false);
		  $(`select#default_${parameter} option[value='${value}']`).prop("selected", true);
      $(`.calc-item.accordion-item.accordion-item-select[data-name='default_${parameter}']`).find(".label-text").text(`${value}	cm`);
			$('.product-flex .product-top-b a.product-btn_cart').prop('href', cart_url.search);
			$(`select#default_${parameter}`).trigger("change");
    });
	}

    function calc_price(){
  	    let default_width = $("#default_width").val();
  	    let default_height = $("#default_height").val();
  	    let default_length = $("#default_length").val();
  	    let additional_costs_ratio = $("#additional_costs_ratio").val();
  	    let profit_ratio = $("#profit_ratio").val();
  	    let currency = $("#current_currency_wp").text();
  	    let total_price = 0;
  			let additional_height;
  			if ($("#default_additional_height").length){
  				additional_height = $("#default_additional_height").val();
  			}
  	    const round_calc = ((default_length*default_height*2)+(default_width*default_height*2))/10000; console.log("round_calc: "+round_calc);
  	    const top_calc = (default_width*default_length)/10000; console.log("top_calc: "+top_calc);
  	    const total_calc = round_calc+top_calc; console.log("total_calc: "+total_calc);
  	    const cost_calc = total_calc*additional_costs_ratio*35; console.log("cost_calc: "+cost_calc);
  	    total_price = Math.round(cost_calc*profit_ratio);
  			if (total_price > 0) {
  				let qty = $('.i-qu').val();
  				total_price = total_price * qty;
  		    // $(".price-calculator-wrapper #total_price_calc").html(total_price);
  				$("#updated_price").val(total_price);
  				$(".price-wcc .custom-prc").html(total_price);
  				$(".price-wcc .pr-val ins").html(currency + total_price);
  				$(".price-wcc .pr-val del").hide();
					let oldUrl = $('.add_cart_wcc.product-btn_cart').prop('href');
					var url = new URL(oldUrl);
					url.searchParams.set('width', default_width);
					url.searchParams.set('height', default_height);
					url.searchParams.set('length', default_length);
					url.searchParams.set('acr', additional_costs_ratio);
					url.searchParams.set('pr', profit_ratio);
					if (additional_height) {
						url.searchParams.set('additional_height', additional_height);
					}
					// console.log('url', url.search);
					$('.add_cart_wcc.product-btn_cart').prop('href', url.search);
				}
    }
    calc_price(); //update calculated total on page load
		$(".product-flex .bt-q").on("click", function(){	//re-calc updated price based on changed quantity
			calc_price();
		});
		$(".calc-item.accordion-item.accordion-item-select ul li").on("click", function(){
			let value = $(this).attr("data-value");
		  let parameter = $(this).attr("data-parameter");
		  // Update the label
		  $(`.calc-item.accordion-item.accordion-item-select[data-name='default_${parameter}']`).find(".label-text").text(`${value} cm`);
		  // Unselect the previously selected option
		  $(`select#default_${parameter} option`).prop("selected", false);
		  // Select the correct option
		  $(`select#default_${parameter} option[value='${value}']`).prop("selected", true);
		  // Trigger a change event to ensure that any listeners are fired
		  $(`select#default_${parameter}`).trigger("change");
      calc_price();
    });
		$(".variation-accordion .accordion-item ul li").on("click", function(){	calc_price(); });
		// Function to observe href changes
		function observeHrefChange(element) {
			const observer = new MutationObserver(function(mutationsList) {
				for (let mutation of mutationsList) {
					if (mutation.type === 'attributes' && mutation.attributeName === 'href') {
						const newHref = $(element).prop('href');
						var cart_url = new URL(newHref);
						let cart_width = cart_url.searchParams.get('width');
						if (!cart_width) {
							calc_price();
						}
					}
				}
			});
			// Configure the observer to watch for attribute changes (specifically 'href')
			observer.observe(element, { attributes: true });
		}
		const anchorElement = document.querySelector('.product-flex .product-top-b a.product-btn_cart');
		if (anchorElement) {
			observeHrefChange(anchorElement);
		}
	});
	</script>
	<?php }
}

add_filter( 'woocommerce_add_cart_item_data', 'save_custom_cart_item_data', 10, 4 );
function save_custom_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {
    if( isset($_GET['width']) && ! empty($_GET['width'])  ) {
				$product_id = (float) wc_clean($_GET['add-to-cart']);
				$width = (float) wc_clean($_GET['width']);
				$height = (float) wc_clean($_GET['height']);
				$length = (float) wc_clean($_GET['length']);
				$additional_height = (float) wc_clean($_GET['additional_height']);
				$additional_costs_ratio = (float) wc_clean($_GET['acr']);
				$profit_ratio = (float) wc_clean($_GET['pr']);
				$total_price = 0;
				$round_calc = (($length*$height*2)+($width*$height*2))/10000;
				$top_calc = ($width*$length)/10000;
				$total_calc = $round_calc+$top_calc;
				$cost_calc = $total_calc*$additional_costs_ratio*35;
				$total_price = round($cost_calc*$profit_ratio);
				if ($total_price>0) {
					$cart_item_data['updated_price'] = $total_price;
					$cart_item_data['width'] = $width;
					$cart_item_data['height'] = $height;
					$cart_item_data['length'] = $length;
					// Make each item as a unique separated cart item
					$cart_item_data['unique_key'] = md5( microtime().rand() );
				  if( isset($_GET['additional_height']) && ! empty($_GET['additional_height'])  ) {
						$cart_item_data['additional_height'] = $additional_height;
					}
				}
    }
    return $cart_item_data;
}

add_action( 'woocommerce_cart_item_price', 'filter_cart_displayed_price', 10, 2 );
function filter_cart_displayed_price( $price, $cart_item ) {
    if ( isset($cart_item['updated_price']) ) {
        $args = array( 'price' => floatval( $cart_item['updated_price'] ) );

        if ( 'incl' === get_option('woocommerce_tax_display_cart') ) {
            $product_price = wc_get_price_including_tax( $cart_item['data'], $args );
        } else {
            $product_price = wc_get_price_excluding_tax( $cart_item['data'], $args );
        }
        return wc_price( $product_price );
    }
    return $price;
}

add_action( 'woocommerce_before_calculate_totals', 'set_new_cart_item_updated_price' );
function set_new_cart_item_updated_price( $cart ) {
    if ( ( is_admin() && ! defined( 'DOING_AJAX' ) ) )
        return;

    if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
        return;

    // Loop through cart items and set the updated price
    foreach ( $cart->get_cart() as $cart_item ) {
        // Set the new price
        if( isset($cart_item['updated_price']) ){
            $cart_item['data']->set_price($cart_item['updated_price']);
        }
    }
}

// Display custom cart item data in cart and checkout
add_filter( 'woocommerce_get_item_data', 'display_custom_cart_item_data_2', 20, 2 );
function display_custom_cart_item_data_2( $cart_item_data, $cart_item ) {
    if( isset( $cart_item['updated_price'] ) ) {
			// Get the product object
			$product = $cart_item['data'];
			// Check if it's a variable product
			if ( isset( $cart_item['variation_id'] ) ) {
					// Get variation attributes
					$variation_id = $cart_item['variation_id'];
					$variation = wc_get_product( $variation_id );
					// Get variation attributes (name => value pairs)
					$variation_attributes = $cart_item['variation'];
					foreach ( $variation_attributes as $attribute_name => $attribute_value ) {
							$cleaned_name = wc_attribute_label( str_replace( 'attribute_', '', $attribute_name ) );
							// Get the attribute value in a human-readable form
							$term = get_term_by( 'slug', $attribute_value, str_replace( 'attribute_', '', $attribute_name ) );
							$attribute_value_human = $term ? $term->name : $attribute_value;
							$cleaned_name = str_replace( 'pa_', '', $cleaned_name );
							$cleaned_name = str_replace( '-', ' ', $cleaned_name );

							echo '<p>' . urldecode( $cleaned_name ) . ': ' . urldecode( $attribute_value_human ) . '</p>';
							if ( is_cart() || is_checkout() ) {
								 // Loop through the item data (variation attributes)
								 foreach ( $cart_item_data as $key => $data ) {
										 // Remove all variation attributes (color, size, etc.) from being displayed
										 unset( $cart_item_data[$key] );
								 }
						 }
					}
			}
			$cart_item_data[] = array(
				'name' => 'Length',
				'value' => $cart_item['length'] . ' cm');
      $cart_item_data[] = array(
          'name' => 'Width',
          'value' => $cart_item['width'] . ' cm');
			if (isset($cart_item['additional_height'])) {
				$cart_item_data[] = array(
					'name' => 'Back height',
					'value' => $cart_item['height'] . ' cm');
					$cart_item_data[] = array(
						'name' => 'Front height',
						'value' => $cart_item['additional_height'] . ' cm');
			}else{
				$cart_item_data[] = array(
					'name' => 'Height',
					'value' => $cart_item['height']  . ' cm');
			}
    }
    return $cart_item_data;
}

// Save / Display custom field value as custom order item meta data
add_action( 'woocommerce_checkout_create_order_line_item', 'custom_field_update_order_item_meta_2', 20, 4 );
function custom_field_update_order_item_meta_2( $item, $cart_item_key, $values, $order ) {
    if( isset($values['updated_price']) ){
        $item->update_meta_data( 'Width', $values['width'] );
				$item->update_meta_data( 'Length', $values['length'] );
				$item->update_meta_data( 'Height', $values['height'] );
				if( isset($values['additional_height']) ){
					$item->update_meta_data( 'Additional height', $values['additional_height'] );
				}
    }
}

// Save custom fields values as custom order meta data
add_action( 'woocommerce_checkout_create_order', 'my_custom_checkout_field_update_order_meta_2', 20, 2 );
function my_custom_checkout_field_update_order_meta_2( $order, $data ) {
    $dimensions = array();

    // Loop through order items
    foreach( $order->get_items() as $item ){
        // Set each order item '1e box abonnement' in an array
        $dimensions[] = $item->get_meta( 'Width' );
				$dimensions[] = $item->get_meta( 'Length' );
				$dimensions[] = $item->get_meta( 'Height' );
				$dimensions[] = $item->get_meta( 'Additional height' );
    }
    // Save the data as a coma separated string in order meta data
    if( sizeof($dimensions) > 0 )
        $order->update_meta_data( 'Dimensions', implode( ',', $dimensions ) );
}
