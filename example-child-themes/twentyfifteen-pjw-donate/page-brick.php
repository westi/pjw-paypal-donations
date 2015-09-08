<?php
/**
 * Template Name: Brick Wall
 */

get_header(); ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			<div id='brick-wall-frame'>
			<img src="http://i1.wp.com/blog.ftwr.co.uk/wp-content/uploads/2015/07/IMG_2941.jpg?resize=466%2C466" id="hidden-behind-the-wall" />
				<?php // 20 * 2 * 14 = 560 => $11,200 Max
					// Consistently random
					mt_srand( 12 );

					$_donated_amt = $pjw_pdm->get_total_donations( '2015-appeal' );
					$_donated = array();

					// Build an array of donated bricks randomly across the whole wall based on the donations so far
					while( $_donated_amt > 0 ) {
						$_pos = mt_rand( 1, 560 );
						if ( ! isset( $_donated[$_pos] ) ) {
							$_donated[$_pos] = true;
							$_donated_amt -= 20;
						}
					}

					$_pos = 0;
					foreach( range( 1, 20 ) as $row ) {
						foreach( array( 1 => 'brick', 2 => 'brick alt' ) as $type => $class ) {
							foreach ( range( 1, 14 ) as $col ) {
								$_brick_class = $class;
								$_pos += 1;
								if ( isset( $_donated[ $_pos ] ) ) {
									$_brick_class .= ' donated';
								}
								?><span class='<?php echo esc_attr( $_brick_class ); ?>'></span><?php
							}
						}
					}
				?>
			</div>
		</main><!-- .site-main -->
	</div><!-- .content-area -->

<?php get_footer(); ?>
