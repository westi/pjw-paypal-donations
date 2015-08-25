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
 * @todo We need a way to build custom paypal donation buttons so we can have control over how much is donated - fixed minimum amount but no upper limit
 * @todo We probably need to merge the two pre_get_posts filters so that we can sort and filter at the same time sucessfully - needs to have multiple meta queries.
 */
class pjw_paypal_donation_manager {
	private $debug = true;
	
	public function __construct() {
		$ipn = new pjw_ipn_handler( true, true );
		add_action( 'pjw_ipn_verified_for-web_accept', array( $this, 'ipn_received' ) );
		add_action( 'pjw_ppdm_donation_received', array( $this, 'donation_received' ) );
		add_action( 'init', array( $this, 'register_donation_post_type' ) );
		add_filter( 'manage_pjw-donation_posts_columns', array( $this, 'register_custom_post_type_columns' ) );
		add_filter( 'manage_edit-pjw-donation_sortable_columns', array( $this, 'register_custom_post_type_sortable_columns' ) );
		add_action( 'manage_pjw-donation_posts_custom_column', array( $this, 'display_custom_post_type_columns' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'custom_post_type_sorting' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_campaign_filter' ) );
		add_action( 'pre_get_posts', array( $this, 'custom_post_type_filtering' ) );
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

		$_existing = get_posts(
			array(
				'numberposts' => 1,
				'post_type' => 'pjw-donation',
				'meta_key' => 'pjw_ppdm-txn_id',
				'meta_value' => $_donor_info['pjw_ppdm-txn_id']
			)
		);

		if ( ! empty( $_existing ) ) {
			$this->debug_log( $_existing );
			$this->debug_log( "Found existing donation {$_existing[0]->ID} for {$_donor_info['pjw_ppdm-txn_id']} updating metadata." );

			wp_update_post( array(
					'ID' => $_existing[0]->ID,
					'post_title' => "Donation from {$_donor_info['pjw_ppdm-first_name']} {$_donor_info['pjw_ppdm-last_name']} for {$_donor_info['pjw_ppdm-campaign']}"
				)
			);

			foreach( $_donor_info as $_key => $_value ) {
				update_post_meta( $_existing[0]->ID, $_key, $_value );
			}

		} else {
	
			$_post = wp_insert_post( array (
					'post_type' => 'pjw-donation',
					'post_status' => 'publish',
					'post_title' => "Donation from {$_donor_info['pjw_ppdm-first_name']} {$_donor_info['pjw_ppdm-last_name']} for {$_donor_info['pjw_ppdm-campaign']}"
				)
			);
	
			if ( $_post ) {
				foreach( $_donor_info as $_key => $_value ) {
					add_post_meta( $_post, $_key, $_value, true );
				}
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

	/**
	 * Modify the list of columns that are displayed for posts in wp-admin.
	 * 
	 * @param array $_columns
	 * @return array
	 */
	public function register_custom_post_type_columns( $_columns ) {
		unset( $_columns['date'] ); // Remove the default date position so we can rename and add at the end.
		$_columns['pjw_ppdm-campaign'] = 'Campaign';
		$_columns['pjw_ppdm-txn_id'] = 'Transaction ID';
		$_columns['pjw_ppdm-email'] = 'Donor Email';
		$_columns['pjw_ppdm-amount'] = 'Amount';
		$_columns['date'] = 'Donation Date';
		return $_columns;
	}

	public function register_custom_post_type_sortable_columns( $_columns ) {
		$_columns['pjw_ppdm-campaign'] = 'pjw_ppdm-campaign';
		$_columns['pjw_ppdm-txn_id'] = 'pjw_ppdm-txn_id';
		$_columns['pjw_ppdm-email'] = 'pjw_ppdm-email';
		return $_columns;
	}

	/**
	 * Output the content for the custom columns in wp-admin
	 */
	public function display_custom_post_type_columns( $_column_name, $_post_id ) {
		switch( $_column_name ) {
			case 'pjw_ppdm-campaign':
			case 'pjw_ppdm-txn_id':
			case 'pjw_ppdm-email':
			case 'pjw_ppdm-amount':
				echo get_post_meta( $_post_id, $_column_name, true );
				break;
		}
	}

	/**
	 * Convert our custom order by arguments into meta query ordering for the wp-admin edit view..
	 */
	public function custom_post_type_sorting( $_query ) {
		if ( ! is_admin() ) {
			return;
		}

		$_screen = get_current_screen();
		if ( ( $_screen->post_type == 'pjw-donation' ) && ( $_screen->base == 'edit' ) ) {
			$_orderby = $_query->get( 'orderby');
			switch( $_orderby ) {
				case 'pjw_ppdm-campaign':
				case 'pjw_ppdm-txn_id':
				case 'pjw_ppdm-email':
					$_query->set('meta_key', $_orderby );
					$_query->set('orderby', 'meta_value' );
			}
		}
	}

	/**
	 * Add a filter UI for Campaigns.
	 *
	 * @access public
	 * @return void
	 */
	public function add_campaign_filter() {
		global $wpdb;
		$_screen = get_current_screen();
		if ( ( $_screen->post_type == 'pjw-donation' ) && ( $_screen->base == 'edit' ) ) {
			$_selected_pjw_ppdm_campaign = isset( $_GET['pjw_ppdm-campaign'] ) ? $_GET['pjw_ppdm-campaign']  : '';
			$_pjw_ppdm_campaigns = $wpdb->get_col( "SELECT DISTINCT(meta_value) FROM {$wpdb->postmeta} WHERE meta_key='pjw_ppdm-campaign';" )
			?>
			<label for="filter-by-pjw_ppdm-campaign" class="screen-reader-text">Filter by Campaign</label>
			<select name="pjw_ppdm-campaign" id="filter-by-pjw_ppdm-campaign">
				<option <?php selected( $_selected_pjw_ppdm_campaign, ''); ?> value="" >All Campaigns</option>
				<?php
					foreach( $_pjw_ppdm_campaigns as $_pjw_ppdm_campaign ) {
						?>
							<option <?php selected( $_selected_pjw_ppdm_campaign, $_pjw_ppdm_campaign); ?> value="<?php echo esc_attr( $_pjw_ppdm_campaign ); ?>" ><?php echo esc_html( $_pjw_ppdm_campaign ); ?></option>
						<?php
					}
				?>
			</select>
			<?php
		}
	}

	public function custom_post_type_filtering( $_query ) {
		if ( ! is_admin() ) {
			return;
		}

		$_screen = get_current_screen();
		if ( ( $_screen->post_type == 'pjw-donation' ) && ( $_screen->base == 'edit' ) ) {
			if ( isset( $_GET['pjw_ppdm-campaign'] ) ) {
				$_query->set('meta_key','pjw_ppdm-campaign' );
				$_query->set('meta_value', $_GET['pjw_ppdm-campaign'] );
			}
		}
	}

	/**
	 * Fetch the total donations for a campaign
	 *
	 * @param string $_campaign The campaign slug used in the buttons
	 * @return float
	 */
	public function get_total_donations( $_campaign ) {
		$_donations = get_posts(
			array(
				'fields' => 'ids',
				'numberposts' => -1,
				'post_type' => 'pjw-donation',
				'meta_key' => 'pjw_ppdm-campaign',
				'meta_value' => $_campaign
			)
		);

		$_total = 0.00;
		foreach( $_donations as $_donation ) {
			$_total += get_post_meta( $_donation, 'pjw_ppdm-amount', true );
		}

		$this->debug_log( "Found \${$_total} donations for {$_campaign}." );

		return $_total;
	}
}


// Boot strap the plugin.
function pjw_paypal_donate_bootstrap() {
	$pjw_pdm = new pjw_paypal_donation_manager();
}
pjw_paypal_donate_bootstrap();