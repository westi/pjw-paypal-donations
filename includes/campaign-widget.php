<?php

class pjw_paypal_donation_campaign_widget extends WP_Widget {

	function __construct() {
		// Instantiate the parent object
		parent::__construct( false, 'Campaign Donation Status' );
	}

	function widget( $args, $instance ) {
		$title = $instance['title'];

		$campaign = empty( $instance['campaign'] ) ? '' : $instance['campaign'];

		if ( ! empty( $campaign ) ) {
			echo $args['before_widget'];
			if ( $title ) {
				echo $args['before_title'] . $title . $args['after_title'];
			}
		?>
		<ul>
			<?php echo $campaign; ?>
		</ul>
		<?php
			echo $args['after_widget'];
		}
	}

	function update( $new_instance, $old_instance ) {
		global $pjw_pdm;

		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		if ( in_array( $new_instance['campaign'], $pjw_pdm->get_available_campaigns() ) ) {
			$instance['campaign'] = $new_instance['campaign'];
		} else {
			$instance['campaign'] = '';
		}

		return $instance;
	}

	function form( $instance ) {
		global $pjw_pdm;
		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 'campaign' => '', 'title' => '') );

		$title = esc_attr( $instance['title'] );
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">
				<?php _e('Title:'); ?>
			</label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('campaign'); ?>"><?php _e( 'Campaign:' ); ?></label>
			<select name="<?php echo $this->get_field_name('campaign'); ?>" id="<?php echo $this->get_field_id('campaign'); ?>" class="widefat">
				<?php foreach( $pjw_pdm->get_available_campaigns() as $_pjw_ppdm_campaign ) {
					?>
					<option <?php selected( $instance['campaign'], $_pjw_ppdm_campaign); ?> value="<?php echo esc_attr( $_pjw_ppdm_campaign ); ?>" ><?php echo esc_html( $_pjw_ppdm_campaign ); ?></option>
					<?php
				}
				?>
			</select>
		</p>
		<?php
	}
}

function myplugin_register_widgets() {
	register_widget( 'pjw_paypal_donation_campaign_widget' );
}

add_action( 'widgets_init', 'myplugin_register_widgets' );