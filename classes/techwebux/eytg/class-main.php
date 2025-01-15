<?php
/**
 * Main class for Easy YouTube Gallery
 *
 * @package Easy_YouTube_Gallery
 */

namespace Techwebux\Eytg;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Main {

	private $defaults = array(
		'id'          => '',          // Single or array of YouTube video ID's
		'cols'        => 1,           // Number of columns (1-8)
		'ar'          => '16_9',      // empty for 16:9 or or `4_3` or `square`
		'thumbnail'   => 'hqdefault', // available: 0, 1, 2, 3, default, mqdefault, hqdefault, sddefault, and maxresdefault
		'controls'    => 1,           // Optionally hide player controls
		'class'       => '',          // Custom block class
		'privacy'     => 0,           // Enhanced Privacy
		'playsinline' => 0,           // When disabled video on iOS play in fullscreen, when eabled plays inline
		'wall'        => 0,           // Enable wall mode (instead lightbox)
		'title'       => 'top',       // Position of custom video title (top|bottom)
	);

	/**
	 * Construct class
	 */
	public function __construct() {

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
	} // END function __construct

	/**
	 * Enqueue admin scripts and styles
	 */
	public function admin_enqueue_scripts() {
		global $pagenow;

		if ( in_array( $pagenow, array( 'post-new.php', 'post.php' ), true ) ) {
			wp_register_style(
				'easy-youtube-gallery-admin',
				plugins_url( 'assets/css/admin.css', EYTG_FILE ),
				array(),
				EYTG_VER
			);
			wp_enqueue_style( 'easy-youtube-gallery-admin' );
		}
	} // END function admin_enqueue_scripts

	/**
	 * Enqueue frontend scripts and styles
	 */
	public function enqueue_scripts() {

		// Do we have enqueued Magnific Popup?
		if ( ! wp_script_is( 'magnific-popup-au', 'enqueued' ) ) {
			wp_enqueue_style(
				'magnific-popup-au',
				plugins_url( 'assets/lib/magnific-popup/magnific-popup.min.css', EYTG_FILE ),
				array(),
				EYTG_VER
			);
			wp_enqueue_script(
				'magnific-popup-au',
				plugins_url( 'assets/lib/magnific-popup/jquery.magnific-popup.min.js', EYTG_FILE ),
				array( 'jquery' ),
				EYTG_VER,
				true
			);
		}

		// Prepare and enqueue plugin assets
		wp_register_script(
			'easy-youtube-gallery',
			plugins_url( 'assets/js/eytg.min.js', EYTG_FILE ),
			array( 'magnific-popup-au' ),
			EYTG_VER,
			true
		);
		wp_register_style(
			'easy-youtube-gallery',
			plugins_url( 'assets/css/eytg.css', EYTG_FILE ),
			array(),
			EYTG_VER
		);
		wp_enqueue_style( 'easy-youtube-gallery' );
	}
	// END function enqueue_scripts

	/**
	 * Build Easy YouTube Gallery HTML structure based on provided shortcode options
	 * @param  array $atts    Custom selection of parameters
	 * @return text           Prepared HTML structure
	 */
	public function shortcode( $atts = array(), $content = null, $tag = '' ) {
		// Normalize attribute keys, lowercase
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );

		// Merge shortcode attributes and defaults
		$eytg_atts = shortcode_atts(
			$this->defaults,
			$atts,
			$tag
		);

		// Make array of video ID's and prepare other var's
		$eytg_ids  = $this->video_ids_to_array( $eytg_atts['id'] );
		$total_ids = count( $eytg_ids );
		// Complain if we don't have provided ID's
		if ( ! $total_ids && is_user_logged_in() && current_user_can( 'publish_posts' ) ) {
			return sprintf(
				'<p class="eytg-error">%s</p>',
				esc_html__( "You have not provided any valid YouTube video ID's within the Easy YouTube Gallery shortcode!", 'easy-youtube-gallery' )
			);
		}

		// Define number of columns
		$columns = max( 1, min( 8, (int) $eytg_atts['cols'] ) );

		// Define Aspect Ratio
		$aspect_ratio = in_array( sanitize_key( $eytg_atts['ar'] ), array( '4_3', 'square', '16_9' ), true )
		? sanitize_key( $eytg_atts['ar'] )
		: $this->defaults['ar'];

		// Define thumbnail size
		$thumbnail_size = in_array( sanitize_key( $eytg_atts['thumbnail'] ), array( '0', '1', '2', '3', 'default', 'mqdefault', 'hqdefault', 'sddefault', 'maxresdefault' ), true )
		? $this->defaults['thumbnail']
		: sanitize_key( $eytg_atts['thumbnail'] );

		// Define controls
		$controls = (bool) $eytg_atts['controls'] ? '1' : '0';

		// Define class
		$html_class = sanitize_html_class( $eytg_atts['class'], $this->defaults['class'] );

		// Define Enhanced Privacy
		$enhance_privacy = (bool) $eytg_atts['privacy'] ? '1' : '0';

		// Deifine Plays Inline on iOS (is this deprecated?)
		$playsinline = (bool) $eytg_atts['playsinline'] ? '1' : '0';

		// Deifine Wall mode
		$wall_mode = (bool) $eytg_atts['wall'] ? '1' : '0';

		// Define title position
		$title_position = in_array( sanitize_key( $eytg_atts['title'] ), array( 'top', 'bottom' ), true )
		? $this->defaults['title']
		: sanitize_key( $eytg_atts['title'] );

		// Prepare titles if available
		if ( ! empty( $content ) ) {
			// Convert pipes to newlines and strip BR's
			$titles = preg_replace( '/<br\s*\/?>/', '', trim( str_replace( '|', PHP_EOL, $content ) ) );
			// Explode string to array
			$titles = explode( "\n", trim( $titles ) );
		}

		// Now enqueue plugin JS as we need it
		if ( ! wp_script_is( 'easy-youtube-gallery', 'enqueued' ) ) {
			wp_enqueue_script( 'easy-youtube-gallery' );
		}

		// Start output
		$output  = '';
		$output .= '<section class="eytg_main_container">';

		if ( '1' === $wall_mode ) {
			$html_class .= ' eytg-wall-items';
			$output     .= sprintf(
				'<section class="eytg-wall">
					<iframe width="560" height="315" 
						src="https://www.youtube%1$s.com/embed/%2$s?%3$s" 
						frameborder="0" allowfullscreen 
						title="YouTube Video Player"></iframe>
				</section>',
				! $enhance_privacy ? '' : '-nocookie', // 1
				$eytg_ids[0],                          // 2
				$this->build_video_query( 1, $controls, $enhance_privacy, 0, $playsinline, 0 ) // 3
			);
		} else {
			$html_class .= ' eytg-lightbox-items';
		}

		// Open gallery container
		$output .= sprintf(
			'<section class="easy_youtube_gallery col-%1$s ar-%2$s %3$s">',
			$columns,      // 1
			$aspect_ratio, // 2
			$html_class    // 3
		);

		// Build gallery items
		$item_num = 0;

		// Process each sanitized and cleaned video id
		foreach ( $eytg_ids as $video_id ) {

			// Increase number of items
			++$item_num;

			$active_item = '';
			switch ( $item_num ) {
				case 1:
					$item_position = 'first';
					if ( '1' === $wall_mode ) {
						$active_item = 'active';
					}
					break;
				case $total_ids:
					$item_position = 'last';
					break;
				default:
					$item_position = 'mid';
			}

			// Do we have custom title for this item?
			$item_title = '';
			if ( ! empty( $titles ) ) {
				$tnum = $item_num - 1;
				if ( is_array( $titles ) && ! empty( $titles[ $tnum ] ) ) {
					$item_title = sprintf(
						'<span class="eytg-title %1$s">%2$s</span>',
						$title_position,         // 1
						trim( $titles[ $tnum ] ) // 2
					);
				}
			}

			// Construct HTML structure for single item
			$output .= sprintf(
				'<a href="https://www.youtube.com/watch?v=%1$s&%10$s"
					class="eytg-item eytg-item-%5$s eytg-item-%6$s %7$s"
					data-eytg_video_id="%1$s"
					data-eytg_controls="%2$s"
					data-eytg_playsinline="%3$s"
					data-eytg_privacy="%4$s">
					<span class="eytg-thumbnail"
						style="background-image:url(https://img.youtube.com/vi/%1$s/%8$s.jpg)"></span>
					%9$s
				</a>',
				$video_id,        // 1
				$controls,        // 2
				$playsinline,     // 3
				$enhance_privacy, // 4
				$item_num,        // 5
				$item_position,   // 6
				$active_item,     // 7
				$thumbnail_size,  // 8
				$item_title,      // 9
				$this->build_video_query( 1, $controls, $enhance_privacy, 1, $playsinline, 0 ) // 10
			);

		} // END foreach

		// Close gallery container
		$output .= sprintf(
			'</section><!-- easy_youtube_gallery col-%1$s ar-%2$s %3$s --></section><!-- .eytg_main_container .ar-%2$s -->',
			$columns,      // 1
			$aspect_ratio, // 2
			$html_class    // 3
		);

		// Return prepared HTML structure
		return wp_kses(
			$output,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				// Allow iframe and EYTG specific attributes
				array(
					'a'      => array(
						'href'                  => array(),
						'class'                 => array(),
						'data-eytg_video_id'    => array(),
						'data-eytg_controls'    => array(),
						'data-eytg_playsinline' => array(),
						'data-eytg_privacy'     => array(),
					),
					'iframe' => array(
						'src'             => array(),
						'width'           => array(),
						'height'          => array(),
						'frameborder'     => array(),
						'allowfullscreen' => array(),
						'sandbox'         => array(),
						'title'           => array(),
					),
				)
			)
		);
	} // END public function shortcode

	/**
	 * Sanitize and validate YouTube video IDs separated by commas.
	 *
	 * @param string $video_ids Comma-separated YouTube video IDs.
	 * @return array Array of sanitized and validated video IDs
	 */
	public function video_ids_to_array( $video_ids ) {
		// Split the input string into an array of IDs
		$ids = explode( ',', $video_ids );

		// Define a regular expression for valid YouTube video IDs
		$pattern = '/^[a-zA-Z0-9_-]{11}$/';

		// Sanitize and validate each ID
		$sanitized_ids = array();
		foreach ( $ids as $id ) {
			$id = trim( $id ); // Remove extra spaces
			if ( preg_match( $pattern, $id ) ) {
				$sanitized_ids[] = $id;
			}
		}

		return $sanitized_ids;
	} // END public function video_ids_to_array

	/**
	 * Function to build link and embed video URL query
	 *
	 * @param string $autoplay
	 * @param string $controls
	 * @param string $enhanceprivacy
	 * @param string $modestbranding
	 * @param string $playsinline
	 * @param string $rel
	 * @return string URL query parameters
	 */
	public function build_video_query(
		$autoplay = '0',
		$controls = '0',
		$enhanceprivacy = '0',
		$modestbranding = '0',
		$playsinline = '0',
		$rel = '0'
	) {
		$query = http_build_query(
			array(
				'autoplay'       => $autoplay,
				'controls'       => $controls,
				'enhanceprivacy' => $enhanceprivacy,
				'modestbranding' => $modestbranding,
				'playsinline'    => $playsinline,
				'rel'            => $rel,
			)
		);
		return $query;
	} // END public function build_video_query

	/**
	 * Register TinyMCE button for Easy YouTube Gallery
	 * @param  array $plugins Unmodified set of plugins
	 * @return array          Set of TinyMCE plugins with EYTG addition
	 */
	public function mce_external_plugins( $plugins ) {
		$plugins['eytg'] = plugin_dir_url( EYTG_FILE ) . 'inc/tinymce/plugin.min.js';
		return $plugins;
	} // END function mce_external_plugins

	/**
	 * Append TinyMCE button for EYTG at the end of row 1
	 * @param  array $buttons Unmodified set of buttons
	 * @return array          Set of TinyMCE buttons with EYTG addition
	 */
	public function mce_buttons( $buttons ) {
		$buttons[] = 'eytg_shortcode';
		return $buttons;
	} // END function mce_buttons
} // END class WPAU_EASY_YOUTUBE_GALLERY
