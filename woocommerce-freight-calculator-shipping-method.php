<?php
/*
 * Plugin Name: WooCommerce Freight Calculator
 * Plugin URI: http://brucecantarim.github.io
 * Description: A simple freight calculator integrated with Goggle's Distance Matrix API.
 * Version: 1.0.0
 * Author: Bruce Cantarim
 * Author URI: http://brucecantarim.github.io
 * Text Domain: woocommerce-freight-calculator
 * Domain Path: /languages/
 */

// Loading the plugin translation
function load_woocommerce_freight_calculator_textdomain() {
  load_plugin_textdomain( 'woocommerce-freight-calculator', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}

add_action( 'plugins_loaded', 'load_woocommerce_freight_calculator_textdomain' );

// Check if WooCommerce is active
    $active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
    
    if ( in_array( 'woocommerce/woocommerce.php',  $active_plugins) ) {
  
// Adding the shipping method to the default methods of WooCommerce

        add_filter( 'woocommerce_shipping_methods', 'add_freight_calculator_shipping_method' );
    
        function add_freight_calculator_shipping_method( $methods ) {
            $methods[] = 'WC_Freight_Calculator_Shipping_Method';
            return $methods;
        }
    
// Including the shipping method class    
        
        add_action( 'woocommerce_shipping_init', 'freight_calculator_shipping_method_init' );
    
        function freight_calculator_shipping_method_init(){
            require_once 'class-freight-calculator-shipping-method.php';
        }
    
// This is the end of the plugin's main file.
}