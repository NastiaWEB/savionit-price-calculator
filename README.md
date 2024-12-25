# savionit-price-calculator


This was an interesting task cause it requires a deep understanding of all woo processes. First, I needed to create default dimension values ​​for each product using ACF. I used conditional logic to hide all inputs if the product did not use the calculator. Then on the product page I render a calculator using default values. Then using url.searchParams.set() I paste all selected parameters to the url so when WooCommerce adding product to cart I check if we have any of this parameters and if so in hook woocommerce_add_cart_item_data I get all data by  $_GET then save all parameters in cart, calculate and update product price based on parameters. 

That’s not all. Now we need to show all parameters in the cart so I use hook woocommerce_get_item_data and paste all parameters we have into the $cart_item_data array then it is displayed in the cart. 
