<?php

class pjw_ipn_handler {
	private $sandbox;

	public function __construct( $sandbox = true ) {
		add_filter( 'query_vars', array( $this, 'filter_query_vars' ) );
		add_action( 'parse_request', array( $this, 'action_parse_request' ) );
		add_action( 'init', array( $this, 'action_init' ) );
		$this->sandbox = $sandbox;
	}

	public function filter_query_vars( $_query_vars ) {
		$_query_vars[] = '_pjw_ipn_handler';
		return $_query_vars;
	}

	public function action_init() {
		add_rewrite_rule( '^paypal/ipn-handler$', 'index.php?_pjw_ipn_handler=1', 'top' );
		// TODO ... CAN WE AVOID THIS
		flush_rewrite_rules();
	}

	public function action_parse_request() {
		if ( isset( $GLOBALS['wp']->query_vars[ '_pjw_ipn_handler' ] ) ) {

			header('HTTP/1.1 200 OK');
			error_log('IPN RX');
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
						do_action( 'pjw_ipn_verified_for-' . $_POST['txn_type'], $_POST );
						break;
					case 'INVALID':
						do_action( 'pjw_ipn_invalid_for-' . $_POST['txn_type'], $_POST );
						break;
			}
			exit;
		}
	}
}
