<?php
require_once( 'api.php' );
require_once( 'db.php' );
require_once( 'settings.php' );

function update_quantity_data() {
	error_log( "Updating quantity data" );
	$sep_quantities_list = fetch_all_sepidar_products_quantity();
	foreach ( $sep_quantities_list as $sku => $quantity ) {
		$product_id = wc_get_product_id_by_sku( $sku );
		if ( $product_id == 0 ) {
			continue;
		}
		error_log( 'Found quantity product: ' . $sku . ' with numbers of: ' . $quantity );
		$product = wc_get_product( $product_id );
		$product->set_stock_quantity( $quantity );
		$product->save();
	}
}

function zero_all_products() {
	error_log( "zero all products quantity" );
	$args      = array( 'limit' => - 1 );
	$products  = array();
	$products1 = wc_get_products( $args );
	foreach ( $products1 as $product ) {
		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $variation_product_id ) {
				$products[] = wc_get_product( $variation_product_id );
			}
		} else {
			$products[] = $product;
		}
	}
	foreach ( $products as $product ) {
		$product->set_stock_quantity( 0 );
		$product->save();
	}
	error_log( "zero all products quantity ended" );
}

function update_price_data() {
	error_log( "Updating price data" );
	$sep_prices_list = fetch_all_sepidar_products_price(false);
	foreach ( $sep_prices_list as $sku => $price ) {
		$product_id = wc_get_product_id_by_sku( $sku );
		if ( $product_id == 0 ) {
			continue;
		}
		error_log( 'Found price product: ' . $sku );
		if ( $price > 1 ) {
			$product = wc_get_product( $product_id );
			$product->set_regular_price( $price );
			$product->save();
		}
	}
}

function update_price_data_sale() {
	error_log( "Updating sale price data" );
	$sep_prices_list = fetch_all_sepidar_products_price(true);
	foreach ( $sep_prices_list as $sku => $price ) {
		$product_id = wc_get_product_id_by_sku( $sku );
		if ( $product_id == 0 ) {
			continue;
		}
		error_log( 'Found sale price product: ' . $sku );
		if ( $price > 1 ) {
			$product = wc_get_product( $product_id );
	        $product->set_sale_price( $price );
			$product->save();
		}
	}
}

function sw_complete_todo_factors() {
	global $SW_SEND_FACTOR;
	error_log( 'Completing todo factors' );
	if ( ! $SW_SEND_FACTOR ) {
		error_log( 'Send factor is off' );

		return;
	}
	$todos = sw_db_get_todo_factors();
	foreach ( $todos as $todo ) {
		error_log( "Completing todo $todo->order_id($todo->stage) with $todo->factor_id number" );
		$order = wc_get_order( $todo->order_id );
		switch ( $todo->stage ) {
			case 0:
				if ( ! sw_api_register_invoice( $order, $todo->factor_id ) ) {
					break;
				}
				$todo->stage ++;
			case 1:
				if ( ! sw_api_register_delivery( $order, $todo->factor_id ) ) {
					break;
				}
				$todo->stage ++;
		}
		error_log( "Completed todo $todo->order_id($todo->stage)" );
		sw_db_update_todo_factor( $todo );
	}
}
