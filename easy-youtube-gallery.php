<?php
/*
Plugin Name: Easy YouTube Gallery
Plugin URI: https://urosevic.net/wordpress/plugins/easy-youtube-gallery/
Description: Quick and easy embed thumbnails gallery for custom set of YouTube videos provided in shortcode, and autoplay video on click in Magnific PopUp lightbox.
Author: Aleksandar Urošević
Version: 1.0.4
Author URI: https://urosevic.net/
Text Domain: easy-youtube-gallery
*/
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPAU_EASY_YOUTUBE_GALLERY' ) ) {
	class WPAU_EASY_YOUTUBE_GALLERY {

		const DB_VER = 1;
		const VER = '1.0.4';

		/**
		 * Construct class
		 */
		function __construct() {

			// Register shortcodes `youtube_channel` and `ytc`
			add_shortcode( 'easy_youtube_gallery', array( $this, 'shortcode' ) );
			add_shortcode( 'eytg', array( $this, 'shortcode' ) );
			add_shortcode( 'eyg', array( $this, 'shortcode' ) );

			// Enqueue scripts and styles
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// Enqueue scripts and styles for Edit page
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

			// TinyMCE AddOn
			add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ), 998 );
			add_filter( 'mce_buttons', array( $this, 'mce_buttons' ), 999 );

		} // END function __construct()

		/**
		 * Enqueue admin scripts and styles
		 */
		function admin_enqueue_scripts() {
			global $pagenow;

			if ( in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) ) {
				wp_register_style(
					'easy-youtube-gallery-admin',
					plugins_url( 'assets/css/admin.css', __FILE__ ),
					array(),
					self::VER
				);
				wp_enqueue_style( 'easy-youtube-gallery-admin' );
			}
		} // END function admin_enqueue_scripts()

		/**
		 * Enqueue frontend scripts and styles
		 */
		function enqueue_scripts() {

			// Do we have enqueued Magnific Popup?
			if ( ! wp_script_is( 'magnific-popup-au', 'enqueued' ) ) {
				wp_enqueue_style(
					'magnific-popup-au',
					plugins_url( 'assets/lib/magnific-popup/magnific-popup.min.css', __FILE__ ),
					array(),
					self::VER
				);
				wp_enqueue_script(
					'magnific-popup-au',
					plugins_url( 'assets/lib/magnific-popup/jquery.magnific-popup.min.js', __FILE__ ),
					array( 'jquery' ),
					self::VER,
					true
				);
			}

			// Prepare and enqueue plugin assets
			wp_register_script(
				'easy-youtube-gallery',
				plugins_url( 'assets/js/eytg.min.js', __FILE__ ),
				array( 'magnific-popup-au' ),
				self::VER,
				true
			);
			wp_register_style(
				'easy-youtube-gallery',
				plugins_url( 'assets/css/eytg.css', __FILE__ ),
				array(),
				self::VER
			);
			wp_enqueue_style( 'easy-youtube-gallery' );

		} // END function enqueue_scripts()

		/**
		 * Build Easy YouTube Gallery HTML structure based on provided shortcode options
		 * @param  array $atts    Custom selection of parameters
		 * @return text           Prepared HTML structure
		 */
		public function shortcode( $atts, $content = null ) {

			$atts = shortcode_atts(
				array(
					'id'          => '',          // Single or array of YouTube video ID's
					'cols'        => 1,           // Number of columns (1-8)
					'ar'          => '16_9',      // empty for 16:9 or or `4_3` or `square`
					'thumbnail'   => 'hqdefault', // 0, 1, 2, 3, default, mqdefault, hqdefault, sddefault, maxresdefault
					'controls'    => 1,           // Optionally hide player controls
					'class'       => '',          // Custom block class
					'privacy'     => 0,           // Enhanced Privacy
					'playsinline' => 0,           // When disabled video on iOS play in fullscreen, when eabled plays inline
					'wall'        => 0,           // Enable wall mode (instead lightbox)
					'title'       => 'top',       // Position of custo video title (top|bottom)
					),
				$atts
			);

			// Prepare titles if exists
			if ( ! empty( $content ) ) {
				$titles = str_replace( '|', PHP_EOL, trim( $content ) ); // Convert pipes to newlines
				$titles = str_replace( '<br />', '', trim( $titles ) ); // Strip BR's
				$titles = explode( "\n", trim( $titles ) ); // Explode string to array
			}
			$title_pos = sanitize_key( $atts['title'] );

			// Start output
			$output = '';

			// Complain if we don't have provided ID's
			if ( empty( $atts['id'] ) ) {
				return sprintf(
					'<p class="eytg-error">%s</p>',
					__( 'Please provide ID’s for some YouTube videos', 'easy-youtube-gallery' )
				);
			}

			// Cleanup privacy and playsinline parameters
			$enhanceprivacy = empty( $atts['privacy'] ) ? 0 : 1;
			$playsinline    = empty( $atts['playsinline'] ) ? 0 : 1;

			// Now enqueue plugin JS as we need it
			if ( ! wp_script_is( 'easy-youtube-gallery', 'enqueued' ) ) {
				wp_enqueue_script( 'easy-youtube-gallery' );
			}

			// Make array of video ID's and prepare other var's
			$ids         = explode( ',', $atts['id'] );
			$total_items = count( $ids );
			$item_num    = 0;

			$output .= '<div class="eytg_main_container">';

			if ( ! empty( $atts['wall'] ) && 'false' != $atts['wall'] ) {
				$domain = empty( $atts['privacy'] ) ? '' : '-nocookie';
				$output .= sprintf(
					'<div class="eytg-wall"><iframe width="560" height="315" src="https://www.youtube%1$s.com/embed/%2$s?rel=0&modestbranding=0&autoplay=1&controls=%3$s&playsinline=%4$s" frameborder="0" allowfullscreen></iframe></div>',
					$domain,
					$ids[0],
					$atts['controls'],
					$playsinline
				);
				$atts['class'] .= ' eytg-wall-items';
			} else {
				$atts['class'] .= ' eytg-lightbox-items';
			}

			// Open gallery container
			$output .= sprintf(
				'<div class="easy_youtube_gallery col-%1$s ar-%2$s %3$s">',
				(int) $atts['cols'],
				$atts['ar'],
				$atts['class']
			);

			// Process each video
			foreach ( $ids as $video_id ) {

				// Trim spaces from Video ID
				$video_id = trim( $video_id );

				// Increase number of items
				++$item_num;

				$active_item = '';
				switch ( $item_num ) {
					case 1:
						$item_position = 'first';
						if ( ! empty( $atts['wall'] ) ) {
							$active_item = 'active';
						}
						break;
					case $total_items:
						$item_position = 'last';
						break;
					default:
						$item_position = 'mid';
				}

				// Construct HTML structure for single item
				$output .= sprintf(
					'<a href="//www.youtube.com/watch?v=%1$s&amp;rel=0&amp;modestbranding=1&amp;controls=%2$s&amp;playsinline=%3$s&amp;enhanceprivacy=%4$s" class="eytg-item eytg-item-%5$s eytg-item-%6$s %7$s" data-eytg_video_id="%8$s" data-eytg_controls="%9$s" data-eytg_playsinline="%10$s" data-eytg_privacy="%11$s"><span class="eytg-thumbnail" style="background-image:url(//img.youtube.com/vi/%1$s/%12$s.jpg)"></span>',
					$video_id,               // 1
					$atts['controls'],       // 2
					$playsinline,            // 3
					$enhanceprivacy,         // 4
					$item_num,               // 5
					$item_position,          // 6
					$active_item,            // 7
					$video_id,               // 8
					$atts['controls'],       // 9
					$atts['playsinline'],    // 10
					$atts['privacy'],        // 11
					$atts['thumbnail']       // 12
				);

				// Do we have custom title?
				if ( ! empty( $content ) ) {
					$tnum = $item_num - 1;
					if ( is_array( $titles ) && ! empty( $titles[ $tnum ] ) ) {
						$output .= sprintf(
							'<span class="eytg-title %1$s">%2$s</span>',
							$title_pos,              // 1
							trim( $titles[ $tnum ] ) // 2
						);
					}
				}

				$output .= '</a>';

			} // END foreach ( $ids as $video_id )

			// Close gallery container
			$output .= sprintf(
				'</div><!-- easy_youtube_gallery col-%1$s ar-%2$s %3$s --></div><!-- .eytg_main_container .ar-%2$s -->',
				$atts['cols'],     // 1
				$atts['ar'],       // 2
				$atts['class']     // 3
			);

			// Print out prepared HTML structure
			return $output;

		} // END public function shortcode($atts)

		/**
		 * Register TinyMCE button for EYTG
		 * @param  array $plugins Unmodified set of plugins
		 * @return array          Set of TinyMCE plugins with EYTG addition
		 */
		function mce_external_plugins( $plugins ) {

			$plugins['eytg'] = plugin_dir_url( __FILE__ ) . 'inc/tinymce/plugin.min.js';

			return $plugins;

		} // END function mce_external_plugins($plugins)

		/**
		 * Append TinyMCE button for EYTG at the end of row 1
		 * @param  array $buttons Unmodified set of buttons
		 * @return array          Set of TinyMCE buttons with EYTG addition
		 */
		function mce_buttons( $buttons ) {

			$buttons[] = 'eytg_shortcode';
			return $buttons;

		} // END function mce_buttons($buttons)

	} // END class WPAU_EASY_YOUTUBE_GALLERY

	// Initialise class
	new WPAU_EASY_YOUTUBE_GALLERY;

} // END class_exists WPAU_EASY_YOUTUBE_GALLERY
