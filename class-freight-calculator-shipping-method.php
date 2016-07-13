<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Defining the shipping method class via a constructor
class WC_Freight_Calculator_Shipping_Method extends WC_Shipping_Method {
    
  public function __construct(){

// Separating the 2 methods     
    $this->id = 'freight_calculator_shipping_method';
    $this->id2 = 'freight_calculator_shipping_with_unloading_method';
    $this->method_title = __( 'Freight Calculator', 'woocommerce-freight-calculator' );

// Load the settings
    $this->init_form_fields(); // To be implemented in another file
    $this->init_settings();

// Define user set variables
    $this->enabled          = $this->get_option( 'enabled' );
    $this->title            = $this->get_option( 'title' );
    $this->title2           = $this->get_option( 'title2' );
    $this->priceperkm       = $this->get_option( 'priceperkm' );
    $this->minimumprice     = $this->get_option( 'minimumprice' );
    $this->unloadingprice   = $this->get_option( 'unloadingprice' );
    $this->originaddress    = $this->get_option( 'originaddress' );
    $this->maxdistance      = $this->get_option( 'maxdistance' );
    
  
// Saving changes  
    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
  
  }
    
// Defining the seetings fields in the admin pane
  public function init_form_fields(){
      
      $this->form_fields = array(
          
        'enabled' => array(
            'title'     => __( 'Enable/Disable', 'woocommerce-freight-calculator' ),
            'type'       => 'checkbox',
            'label'     => __( 'If checked, this option enables the Freight Calculator.', 'woocommerce-freight-calculator' ),
            'default'     => 'yes'
            ),
        'title' => array(
            'title'     => __( 'Freight Name', 'woocommerce-freight-calculator' ),
            'type'       => 'text',
            'description'   => __( 'Set here the name of your freight service.', 'woocommerce-freight-calculator' ),
            'default'    => __( 'Freight', 'woocommerce-freight-calculator' ),
            ),
        'title2' => array(
            'title'     => __( 'Freight and Cargo Unloading Name', 'woocommerce-freight-calculator' ),
            'type'       => 'text',
            'description'   => __( 'Set here the name of your freight service with the optional unloading', 'woocommerce-freight-calculator' ),
            'default'    => __( 'Freight and Unloading', 'woocommerce-freight-calculator' ),      
            ),
        'priceperkm' => array(
            'title' => __( 'Price per kilometer', 'woocommerce-freight-calculator'),
            'description' => __( 'Set your price rate. (Ex.: 1.40)', 'woocommerce-freight-calculator' ),
            'type' => 'text',
            'default' => __( '1.40', 'woocommerce-freight-calculator' ),
            ),
        'minimumprice' => array(
            'title' => __( 'Minimum Freight Price', 'woocommerce-freight-calculator'),
            'description' => __( 'Set your minimum price rate for the freight service. (Ex.: 5.00)', 'woocommerce-freight-calculator' ),
            'type' => 'text',
            'default' => __( '5.00', 'woocommerce-freight-calculator' ),
            ),
        'unloadingprice' => array(
            'title' => __( 'Cargo Unloading Service Price', 'woocommerce-freight-calculator' ),
            'description' => __( 'Set here the price percentage to be applied over the total value for the optional unloading service. (Ex.: 20)', 'woocommerce-freight-calculator' ),
            'type' => 'text',
            'default' => __( '20', 'woocommerce-freight-calculator' ),
            ),
        'originaddress' => array(
            'title' => __( 'Address of Origin', 'woocommerce-freight-calculator' ),
            'description'=> __( 'Set here the address of your warehouse. (Ex.: Street, 000, Neighborhood, P.O. Box, City, State, Country.)', 'woocommerce-freight-calculator' ),
            'type' => 'text',
            'default' => __( 'Street, 000, Neighborhood, P.O. Box, City, State, Country', 'woocommerce-freight-calculator' ),
            ),
        'maxdistance' => array(
            'title' => __( 'Maximum Distance of Coverage', 'woocommerce-freight-calculator' ),
            'description'=> __( 'Set here, in KM, the maximum distance of coverage for the freight service. (Ex.: 50)', 'woocommerce-freight-calculator' ),
            'type' => 'text',
            'default' => __( '50' ),
            )
        );
  }

// Checking if the freight should be available
public function is_available( $package ){
        
// Getting User Address from the checkout page into a string     
    $this->destination      = WC_Checkout::get_value( 'shipping_address_1') . " " . WC_Checkout::get_value( 'shipping_address_2') . " " . WC_Checkout::get_value( 'shipping_postcode' ) . " " . WC_Checkout::get_value( 'shipping_city' ) . " " . WC_Checkout::get_value( 'shipping_country' );
      
// Making the json request to Google's API and calculating the distance
    $this->apikey = "AIzaSyBCdPR7kOYjFUH97WvgGOVG22-X18JLr2U";
    
// Generating the request link and cleaning up the addresses  
    $this->mapsrequest = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=" . urlencode($this->originaddress) . "&destinations=" . urlencode($this->destination) . "&key=" . $this->apikey;

// Initiate curl
    $ch = curl_init();
        // Disable SSL verification
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // Will return the response, if false it print the response
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Set the url
          curl_setopt($ch, CURLOPT_URL, $this->mapsrequest);
        // Execute
          $result=curl_exec($ch);
        // Closing
          curl_close($ch);
        // Decoding the response    
          $this->mapsresult = json_decode( utf8_encode( $result ), true);
        // Getting the distance value from the array and converting it to km
          $this->distance = $this->mapsresult['rows'][0]['elements'][0]['distance']['value'] * 0.001;// Delimiting the max range for the delivery 
      
      if ( $this->distance > $this->maxdistance ) {
          return false;
      } else {
          return true;
      }
  }

// Calculating the Shipping for both methods
  public function calculate_shipping($package){
      
      $calc = $this->distance * $this->priceperkm;
          $cost = round( $calc, 2 );
        
      if($cost < $this->minimumprice) {
          $cost = $this->minimumprice;
      }
          
//  Calculating the % of the unloading variable
      global $woocommerce;
      $cartsubtotal = $woocommerce->cart->cart_contents_total;
      $unloadingtotal = ( $this->unloadingprice / 100 ) * $cartsubtotal;
          
      $cost2 = $cost + $unloadingtotal;
              
     
      
// Sending the final rate to the user 
    $this->add_rate( 
        array(
            'id'    => $this->id,
            'label' => $this->title,
            'cost'  => $cost
             )
        );
        
    $this->add_rate(
        array(
            'id'    => $this->id2,
            'label' => $this->title2,
            'cost'  => $cost2
             )    
        );
  }
}