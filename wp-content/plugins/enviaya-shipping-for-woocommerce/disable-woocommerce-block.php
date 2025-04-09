<?php
use Automattic\WooCommerce\Blocks\BlockTypesController;
use Automattic\WooCommerce\Blocks\Package;

add_action( 'woocommerce_blocks_loaded', function() {
   // remove_action( 'init', [ Package::container()->get( BlockTypesController::class ), 'register_blocks' ] );
} );

add_action( 'wp_head', function() {
    wp_deregister_style( 'wc-blocks-style' );
}, 0 );