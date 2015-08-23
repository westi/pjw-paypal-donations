<?php
/*
Plugin Name: PJW Paypal Donation Manager
Version: 0.1
 */

require_once( __DIR__ . '/includes/ipn-handler.php' );

class pjw_paypal_donation_manager {
	private $debug = false;
	
	public function __construct() {
		$ipn = new pjw_ipn_handler( true, true );
		add_action( 'pjw_ipn_verified_for-web_accept', array( $this, 'ipn_received' ) );
		add_action( 'pjw_ppdm_donation_received', array( $this, 'donation_received' ) );
		add_action( 'init', array( $this, 'register_donation_post_type' ) );
	}

	private function debug_log( $thing ) {
		if ( $this->debug ) {
			error_log( __CLASS__ . ':' . print_r( $thing, true ) );
		}
	}

	public function ipn_received( $_pp_txn_info ) {
		if ( $_pp_txn_info['payment_status'] === 'Completed' ) {
			do_action( 'pjw_ppdm_donation_received', $_pp_txn_info );
		}
		$this->debug_log( $_pp_txn_info );
	}

	public function donation_received( $_pp_txn_info ) {
		$_donor_info = array(
			'amount' => $_pp_txn_info['mc_gross'],
			'email' => $_pp_txn_info['payer_email'],
			'first_name' => $_pp_txn_info['first_name'],
			'last_name' => $_pp_txn_info['last_name'],
			'txn_id' => $_pp_txn_info['txn_id'],
		);
		$this->debug_log( $_donor_info );
		$_post = wp_insert_post( array (
				'post_type' => 'donation'
			)
		);

		if ( $_post ) {
			foreach( $_donor_info as $_key => $_value ) {
				add_post_meta( $_post, $_key, $_value, true );
			}
		}
	}

	public function register_donation_post_type( ) {
		register_post_type(
			'donation',
			array(
				'label' => 'Donations',
				'public' => true,
				'publicly_queryable' => false,
				'exclude_from_search' => true,
				'supports' => array( 'title', 'custom-fields' )
			)
		);
	}
}


// Boot strap the plugin.
function pjw_paypal_donate_bootstrap() {
	$pjw_pdm = new pjw_paypal_donation_manager();
}
pjw_paypal_donate_bootstrap();