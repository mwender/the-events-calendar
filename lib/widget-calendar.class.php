<?php
/**
 * Events Calendar widget class
 */

// Don't load directly
if ( !defined('ABSPATH') ) { die('-1'); }

if( !class_exists( 'TribeEventsCalendarWidget') ) {

	class TribeEventsCalendarWidget extends WP_Widget {
		
		function TribeEventsCalendarWidget() {
			$widget_ops = array('classname' => 'tribe-events-calendar-widget', 'description' => __( 'A calendar of your events') );
			$this->WP_Widget('calendar', __('Events Calendar'), $widget_ops);

			add_action('wp_enqueue_scripts', array($this, 'maybe_load_scripts') );
			add_action('the_widget', array($this, 'force_load_scripts') );
		
		}

		public function force_load_scripts( $widget ) {
			if ( $widget === __CLASS__ ) {
				$this->maybe_load_scripts( true );
			}
		}

		function maybe_load_scripts( $force = false ) {

			if ( $force || is_active_widget( false, false, $this->id_base ) ) {
				
				// Enqueue Core
				Tribe_Template_Factory::asset_package('events-css');

				$widget_data = array( "ajaxurl" => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ) );
				
				$ecp = TribeEventsPro::instance();
				
				// Enqueue the pro-stylesheet.
				if ( tribe_get_option('stylesheetOption') == 'skeleton') {
					$stylesheet_url = $ecp->pluginUrl . 'resources/tribe-events-pro-skeleton.css';
				} else {
					$stylesheet_url = $ecp->pluginUrl . 'resources/tribe-events-pro-full.css';
				}
				$stylesheet_url = TribeEventsTemplates::locate_stylesheet('tribe-events/pro/tribe-events-pro.css', $stylesheet_url);
				$stylesheet_url = apply_filters( 'tribe_events_pro_stylesheet_url', $stylesheet_url );

				if ( $stylesheet_url )
					wp_enqueue_style( 'tribe_events_pro_stylesheet', $stylesheet_url, array( TribeEvents::POSTTYPE . '-calendar-style' ) );

				wp_enqueue_script( 'tribe-events-mini-calendar', TribeEventsPro::instance()->pluginUrl . 'resources/tribe-events-mini-ajax.js' );
				wp_enqueue_style( 'tribe-events-mini-calendar', TribeEventsPro::instance()->pluginUrl . 'resources/tribe-events-mini-ajax.css' );
				wp_localize_script( 'tribe-events-mini-calendar', 'TribeMiniCalendar', $widget_data );
			
			}
		}

		function widget( $args, $instance ) {

			extract($args);
			$title = apply_filters('widget_title', empty($instance['title']) ? '&nbsp;' : $instance['title']);
			echo $before_widget;
			if ( $title ) { echo $before_title . $title . $after_title; }
			echo '<div id="tribe-events-calendar-wrap">';
			tribe_calendar_mini_grid();
			echo '</div>';
			echo $after_widget;
		}
	
		function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['title'] = strip_tags($new_instance['title']);

			return $instance;
		}

		function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
			$tribe_ecp = TribeEvents::instance();		
			require_once( TribeEventsPro::instance()->pluginPath . 'admin-views/widget-admin-calendar.php' );
		}
	
	}

	/* Add function to the widgets_ hook. */
	// hook is commented out until development is finished, allows WP's default calendar widget to work
	add_action( 'widgets_init', 'events_calendar_load_widgets',100);
	//add_action( 'widgets_init', 'get_calendar_custom' );

	//function get_calendar_custom(){echo "hi";}

	/* Function that registers widget. */
	function events_calendar_load_widgets() {
		register_widget( 'TribeEventsCalendarWidget' );
	}

	// AJAX functionality for the mini calendar
	add_action( 'wp_ajax_calendar-mini', 'tribe_calendar_mini_ajax_call' );
	add_action( 'wp_ajax_nopriv_calendar-mini', 'tribe_calendar_mini_ajax_call' );




	function tribe_calendar_mini_ajax_call() {
		if ( isset( $_POST["eventDate"] ) && $_POST["eventDate"] ) {

			global $wp_query;
			$wp_query->set( 'eventDate', $_POST["eventDate"] );
			tribe_calendar_mini_grid();

		}
		die();
	}

}