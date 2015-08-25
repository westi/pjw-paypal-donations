<?php

/**
 * Simple Paypal IPN Handler class.
 *
 * Verifies the IPN is valid and then fires an action with the data for someone else to handle.
 * @see https://developer.paypal.com/webapps/developer/docs/classic/products/instant-payment-notification/
 */
class pjw_ipn_handler {
	private $version = 0.1;
	private $sandbox;
	private $debug;

	public function __construct( $sandbox = true, $debug = false ) {
		add_filter( 'query_vars', array( $this, 'filter_query_vars' ) );
		add_action( 'parse_request', array( $this, 'action_parse_request' ) );
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
		$this->sandbox = $sandbox;
		$this->debug = $debug;
	}

	private function debug_log( $thing ) {
		if ( $this->debug ) {
			error_log( __CLASS__ . ':' . print_r( $thing, true ) );
		}
	}

	public function filter_query_vars( $_query_vars ) {
		$_query_vars[] = '_pjw_ipn_handler';
		return $_query_vars;
	}

	public function action_init() {
		add_rewrite_rule( '^paypal/ipn-handler$', 'index.php?_pjw_ipn_handler=1', 'top' );
	}

	public function action_admin_init() {
		if ( get_option( __CLASS__ . '-version' ) != $this->version ) {
			flush_rewrite_rules();
			update_option( __CLASS__ . '-version', $this->version );
		}
	}

	public function action_parse_request() {
		if ( isset( $GLOBALS['wp']->query_vars[ '_pjw_ipn_handler' ] ) ) {

			header('HTTP/1.1 200 OK');
			$this->debug_log( 'IPN Received' );
			$_to_verfiy = array( 'cmd' => '_notify-validate' );
			$_to_verify = $_to_verfiy + $_POST;
			$response = wp_remote_post(
				( $this->sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr' ),
				array(
					'body' => $_to_verify,
					'useragent' => 'PJW IPN Handler/1.0'
				)
			);
			$_response = wp_remote_retrieve_body( $response );
			switch( $_response ) {
					case 'VERIFIED':
						$this->debug_log( 'IPN VERIFIED for ' . $_POST['txn_type'] );
						do_action( 'pjw_ipn_verified_for-' . $_POST['txn_type'], $_POST );
						break;
					case 'INVALID':
						$this->debug_log( 'IPN INVALID for ' . $_POST['txn_type'] );
						do_action( 'pjw_ipn_invalid_for-' . $_POST['txn_type'], $_POST );
						break;
			}
			exit;
		}
	}
}
