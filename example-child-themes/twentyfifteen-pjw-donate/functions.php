<?php

function twentyfifteen_pjw_donate_widgets_init() {
	register_sidebar( array(
		'name'          => 'Brick Wall Widget Area',
		'id'            => 'sidebar-brick',
		'description'   => 'Add widgets here to appear in the brick wall sidebar.',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'twentyfifteen_pjw_donate_widgets_init' );