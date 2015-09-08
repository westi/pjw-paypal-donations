<?php

class pjw_paypal_donation_campaign_widget extends WP_Widget {

	function __construct() {
		// Instantiate the parent object
		parent::__construct( false, 'Campaign Donation Status' );
	}

	function widget( $args, $instance ) {
		global $pjw_pdm, $wp_query;

		$title = $instance['title'];

		$campaign = get_post_meta( $wp_query->get_queried_object_id(), 'pjw_ppdm-campaign' , true );

		if ( ! empty( $campaign ) ) {
			echo $args['before_widget'];
			if ( $title ) {
				echo $args['before_title'] . $title . $args['after_title'];
			}
		?>
		<ul>
			The '<?php echo $campaign; ?>' has raised $<?php echo $pjw_pdm->get_total_donations( $campaign ); ?> so far.
		</ul>
		<?php
			echo $args['after_widget'];
		}
	}

	function update( $new_instance, $old_instance ) {
		global $pjw_pdm;

		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);

		return $instance;
	}

	function form( $instance ) {
		global $pjw_pdm;
		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 'title' => '') );

		$title = esc_attr( $instance['title'] );
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">
				<?php _e('Title:'); ?>
			</label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<?php
	}
}

function myplugin_register_widgets() {
	register_widget( 'pjw_paypal_donation_campaign_widget' );
}

add_action( 'widgets_init', 'myplugin_register_widgets' );