<?php
/*
Plugin Name: PJW Paypal Donation Manager
Version: 0.1
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Author: Peter Westwood
*/

require_once( __DIR__ . '/includes/ipn-handler.php' );

/**
 * Overaching plugin class to handle processing donations and quering for them.
 *
 * @todo We need a good wp-admin ui for the custom post-type
 * @todo We need a way to query the donations to get an idea of how much had been donated
 * @todo We need a way to build custom paypal donation buttons so we can have control over how much is donated - fixed minimum amount but no upper limit
 * @todo We need to support donation campaigns in the most simple way
 */
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

	/**
	 * Handle a verfied IPN notification
	 */
	public function ipn_received( $_pp_txn_info ) {
		if ( $_pp_txn_info['payment_status'] === 'Completed' ) {
			do_action( 'pjw_ppdm_donation_received', $_pp_txn_info );
		} else {
			// @todo What should we do about uncompleted transactions, should we record them with a different status?
		}
		$this->debug_log( $_pp_txn_info );
	}

	/**
	 * Process and incoming donation and record meta-data about it.
	 *
	 * @todo - We can receive multiple events for the same donation and we need to handle that.
	 */
	public function donation_received( $_pp_txn_info ) {
		$_donor_info = array(
			'pjw_ppdm-amount' => $_pp_txn_info['mc_gross'],
			'pjw_ppdm-email' => $_pp_txn_info['payer_email'],
			'pjw_ppdm-first_name' => $_pp_txn_info['first_name'],
			'pjw_ppdm-last_name' => $_pp_txn_info['last_name'],
			'pjw_ppdm-txn_id' => $_pp_txn_info['txn_id'],
			'pjw_ppdm-campaign' => $_pp_txn_info['item_number'],
		);
		$this->debug_log( $_donor_info );
		$_post = wp_insert_post( array (
				'post_type' => 'pjw-donation'
			)
		);

		if ( $_post ) {
			foreach( $_donor_info as $_key => $_value ) {
				add_post_meta( $_post, $_key, $_value, true );
			}
		}
	}

	/**
	 * Register a new post type that we can use to record infomation about donations
	 *
	 */
	public function register_donation_post_type( ) {
		register_post_type(
			'pjw-donation',
			array(
				'label' => 'Donations',
				'public' => true,
				'publicly_queryable' => false,
				'exclude_from_search' => true,
				'supports' => array( 'title', 'custom-fields' ),
				'capabilities' => array( 'create_posts' => false ),
				'map_meta_cap' => true,
			)
		);
	}
}


// Boot strap the plugin.
function pjw_paypal_donate_bootstrap() {
	$pjw_pdm = new pjw_paypal_donation_manager();
}
pjw_paypal_donate_bootstrap();