<?php
/*
 * Plugin Name: Vis.js Timelines plugin for WordPress
 * Version: 1.0
 * Plugin URI: http://premium.wpmudev.org
 * Description: Powerful timeline tool relying on the vis.js library and utilizing query-based shortcodes.
 * Author: David (incsub)
 * Author URI: http://premium.wpmudev.org/
 * Requires at least: 3.9
 * Tested up to: 4.0
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'VisjsTimelines' ) ) {

	class VisjsTimelines {

		var $version = '0.1 beta';
		var $name = 'Visjs Timelines';
		var $dir_name = 'visjs-timelines';
		var $location = '';
		var $plugin_dir = '';
		var $plugin_url = '';

		function __construct() {
			//Register Globals
			$GLOBALS['plugin_dir'] = $this->plugin_dir;
			// call method to define templates
			// create class based template system	
			add_shortcode( 'vis-timeline', array( __CLASS__, 'handle_shortcode' ) );
			// actions for getting post content
			add_action( 'wp_ajax_timeline_post_content', array( $this, 'timeline_post_content_callback' ) ); 
			add_action( 'wp_ajax_nopriv_timeline_post_content', array( $this, 'timeline_post_content_callback' ) );
		}

		public function enqueue_scripts(){
			if ( defined( 'TIMELINES_PLUS_CDN' ) && constant( 'TIMELINES_PLUS_CDN' ) ) {
				wp_enqueue_style( 'vis-js-css', '//cdnjs.cloudflare.com/ajax/libs/vis/3.5.0/vis.min.css' );
				wp_enqueue_script( 'vis-js', '//cdnjs.cloudflare.com/ajax/libs/vis/3.5.0/vis.min.js' );
			} else {
				wp_enqueue_style( 'vis-js-css', plugins_url( 'css/vis.css', __FILE__ ) );
				wp_enqueue_script( 'vis-js', plugins_url( 'js/vis.min.js', __FILE__ ) );
			}

			wp_enqueue_script( 'jquery' );

			wp_enqueue_script( 'visjs-timelines-js', plugins_url( 'js/visjs-timelines.js', __FILE__ ), array( 'jquery' ), '1.0' );
		}

		static function handle_shortcode( $atts ) {

			// get shortcode attributes
			$a = shortcode_atts( array(
				'id' => 'timeline1',
				'template' => 'default',
				'args' => 'post_type=post',
				'args2' => '',
				'args3' => '',
				'customstartfield' => '',
				'customendfield' => '',
				'customtypefield' => ''
			), $atts );

			return self::render_timeline( array( $a['args'], $a['args2'], $a['args3'] ), $a['id'], $a['template'] );
		}

		public function render_timeline( $query_array, $div_id, $template ) {
			self::enqueue_scripts();
			$items = self::get_timeline_content( $query_array );
			self::render_timeline_content( $items );
			return self::timeline_html( $div_id, $template );
		}

		static function get_timeline_content( $query_array ){

			// ensure we're working with an array, let users provide string for query args
			if ( !is_array( $query_array) ){
				$query_array = array( $query_array );
			}

			$items = array();

			$defaults = array(
				'post_type' => 'post'
			);

			foreach ( $query_array as &$query ) {
				if ( '' != $query) {
					$args = wp_parse_args( $query, $defaults );

					$q = new WP_Query( $args );
					$total = $q->post_count;

					while( $q->have_posts() )
					{
						$q->the_post();
						global $post;

						$item = array(
							"id" => $post->ID,
							"content" => get_the_title(),
							"start" => the_date( "Y-m-d", '"', '"', false )
						);

						// if there's a custom field of timeline_type, use it
						$type = get_post_meta( $post->ID, 'timeline_type', true );
						if ( strcasecmp( $type, 'point' ) == 0 ) {
							$item['type'] = 'point';
						}

						// add $item if not already in array
						if( !in_array( $item, $items ) ){
							array_push( $items, $item );
						}
					}
				}
				// reset query
				wp_reset_postdata();
			}
			return $items;
		}

		static function timeline_html( $div_id, $template ){
			$return = '<div id="' . $div_id . '">';
			$return .= '<div id="visualization"></div>';
			$return .= '<div class="slider">';
			$return .= '<div class="arrow left"></div>';
			$return .= '<div class="timeline-content"></div>';
			$return .= '<div class="arrow right"></div>';
			$return .= '</div>'; // slider
			$return .= '</div>'; // timeline-id
			return $return;
		}

		static function render_timeline_content( $items ){
			// enqueue items to be used by timelines-plus.js
			wp_localize_script( 'visjs-timelines-js', 'visjs_timelines_items', json_encode( $items ) );

			// send ajax url to script
			$timelines_plus_ajax = array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'security' => wp_create_nonce( 'ajax-nonce' )
			);
			wp_localize_script( 'visjs-timelines-js', 'visjs_timelines_ajax', $timelines_plus_ajax );
		}

		public function timeline_post_content_callback(){
			//check_ajax_referer( 'ajax-nonce', 'security' );
			$post_id = intval( $_POST[ 'id' ] );
			$post = get_post( $post_id, OBJECT );
			$response = '<div>';
			$response .= '<div class="entry-date">' . get_the_date( "F j, Y", $post_id ) . '</div>';
			$response .= '<h2 class="entry-title"><a href="' . get_permalink( $post_id ) . '" rel="bookmark">';
			$response .= get_the_title( $post_id ) . '</a></h2>';
			$response .= '<div class="entry-content">';
			$response .= apply_filters( 'the_content', $post->post_content );
			$response .= '</div></div>';
			echo $response;
			die(1);
		}
	}
}

global $visjs_timeslines;
$visjs_timeslines = new VisjsTimelines();
?>