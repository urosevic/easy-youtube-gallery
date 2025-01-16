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
		'id'          => '',          // Single or array of YouTube Video ID's
		'ar'          => '16_9',      // empty for 16:9 or or `4_3` or `square`
		'thumbnail'   => 'hqdefault', // available: 0, 1, 2, 3, default, mqdefault, hqdefault, sddefault, and maxresdefault
		'controls'    => 1,           // Optionally hide player controls
		'privacy'     => 0,           // Enhanced Privacy
		'playsinline' => 0,           // When disabled video on iOS play in fullscreen, when eabled plays inline
		'cols'        => 1,           // Number of columns (1-8)
		'wall'        => 0,           // Enable wall mode (instead lightbox)
		'class'       => '',          // Custom block class
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
	 * @param  array $atts Custom selection of parameters
	 * @return text        Prepared HTML structure
	 */
	public function shortcode( $atts = array(), $content = null, $tag = '' ) {
		// Normalize attribute keys, lowercase
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );

		/**
		 * Combined user attributes from shortcode and default attribute values.
		 *
		 * @var array $eytg_atts This parameter contains array of attributes defined by user in shortcode merged to default attribute values.
		 */
		$eytg_atts = shortcode_atts(
			$this->defaults,
			$atts,
			$tag
		);

		/**
		 * Array of sanitized YouTube Video ID's.
		 *
		 * @var array $eytg_ids This parameter contains array of sanitized YouTube Video IDs.
		 */
		$eytg_ids = $this->video_ids_to_array( $eytg_atts['id'] );

		/**
		 * Number of Video ID's.
		 *
		 * @var integer $total_ids This parameter indicates total number of YouTube Video ID's.
		 */
		$total_ids = count( $eytg_ids );

		// Print on frontend info about missing Video ID's to logged in user with managing posts permissions.
		if ( ! $total_ids && is_user_logged_in() && current_user_can( 'publish_posts' ) ) {
			return sprintf(
				'<p class="eytg-error">%s</p>',
				esc_html__( "You have not provided any valid YouTube video ID's within the Easy YouTube Gallery shortcode!", 'easy-youtube-gallery' )
			);
		}

		/**
		 * Number of columns in the gallery.
		 *
		 * @var integer $columns This parameter indicates number of columns in the video gallery. Supported range is 1-8.
		 */
		$columns = max( 1, min( 8, (int) $eytg_atts['cols'] ) );

		/**
		 * Video Aspect Ratio.
		 *
		 * @var string $aspect_ratio This parameter indicates aspect ratio of the video thumbnail. Supported values are:
		 * - square: Represent aspect ratio 1:1
		 * - 4_3: Represent aspect ratio 4:3
		 * - 16_9: (default) Represent aspect ratio 16:9
		 */
		$aspect_ratio = in_array( sanitize_key( $eytg_atts['ar'] ), array( '4_3', 'square', '16_9' ), true )
		? sanitize_key( $eytg_atts['ar'] )
		: $this->defaults['ar'];

		/**
		 * Thumbnail size.
		 *
		 * @var string $thumbnail_size This parameter indicates YouTube thumbnail size. Supported values are:
		 * - 0: 480x260px
		 * - 1, 2, 3: 2nd, 3rd and 4th thumbnail in resolution 120x90
		 * - default: 120x90px
		 * - mqdefault: 320x180px
		 * - hqdefault: 480x360px
		 * - sddefault: 640x480px
		 * - maxresdefault: Unscaled thumbnail resolution
		 */
		$thumbnail_size = in_array( sanitize_key( $eytg_atts['thumbnail'] ), array( '0', '1', '2', '3', 'default', 'mqdefault', 'hqdefault', 'sddefault', 'maxresdefault' ), true )
		? $this->defaults['thumbnail']
		: sanitize_key( $eytg_atts['thumbnail'] );

		/**
		 * HTML class for thumbnails gallery container.
		 *
		 * @var string $html_class This parameter indicates valid custom HTML class composed of uppercase and lowercase letters, digits, underscore and dash.
		 */
		$html_class = sanitize_html_class( $eytg_atts['class'], $this->defaults['class'] );

		/**
		 * Wall mode.
		 *
		 * @var bool $wall_mode This parameter indicates what player is triggered by thumbnails click. Supported values are:
		 * - true: Render a big video player at the top and load videos to play in it.
		 * - false: (default) Open and play video in lightbox.
		 */
		$wall_mode = (bool) $eytg_atts['wall'] ? true : false;

		/**
		 * Custom title position.
		 *
		 * @var string $title_position This parameter indicates where is positioned custom thumbnail title. Supported values are:
		 * - top: Display thumbnail title at the top of the thumbnail.
		 * - bottom: Display thumbnail title at the bottom of the thumbnail.
		 */
		$title_position = in_array( sanitize_key( $eytg_atts['title'] ), array( 'top', 'bottom' ), true )
		? $this->defaults['title']
		: sanitize_key( $eytg_atts['title'] );

		/**
		 * YouTube Embed feature `Enhanced Privacy`.
		 *
		 * @var string $enhance_privacy This parameter indicates state of the Privacy Enhanced Mode of the YouTube embedded player. Valid values are:
		 * - 0: (default) Embeded video player is loaded from https://www.youtube.com
		 * - 1: Embeded video player is loaded from https://www.youtube-nocookie.com and prevent embedded YouTube content from influencing the viewer's browsing experience on YouTube
		 */
		$enhance_privacy = (bool) $eytg_atts['privacy'] ? '1' : '0';

		/**
		 * YouTube Embed feature `controls`.
		 *
		 * @var string $controls This parameter indicates whether the video player controls are displayed:
		 * - 0: Player controls do not display in the player.
		 * - 1: (default) Player controls display in the player.
		 */
		$controls = (bool) $eytg_atts['controls'] ? '1' : '0';

		/**
		 * YouTube Embed feature `Plays Inline` on iOS.
		 *
		 * @var string $playsinline This parameter controls whether videos play inline or fullscreen on iOS. Valid values are:
		 * - 0: (default) Results in fullscreen playback. This is currently the default value, though the default is subject to change.
		 * - 1: Results in inline playback for mobile browsers and for WebViews created with the allowsInlineMediaPlayback property set to YES.
		 */
		$playsinline = (bool) $eytg_atts['playsinline'] ? '1' : '0';

		// Prepare custom thumbnail titles, if available.
		if ( ! empty( $content ) ) {
			// Remove all HTML tags and special characters, then trim to remove 1st newline.
			$content = trim( strip_tags( $content ) );
			// Convert special characters to HTML entities to prevent XSS.
			$content = htmlspecialchars( $content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			// Convert pipes to newlines.
			$content = str_replace( '|', PHP_EOL, $content );

			/**
			 * Custom thumbnail titles array.
			 *
			 * @var array $titles This parameter contains array of sanitized strings representing custom title for video thumbnails.
			 */
			$titles = explode( "\n", $content );
		}

		/**
		 * HTML structure for output.
		 *
		 * @var string $output This parameter contains complete HTML structure of the rendered shortcode.
		 */
		$output  = '';
		$output .= '<section class="eytg_main_container">';

		if ( true === $wall_mode ) {
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
				$this->build_video_query( 1, $controls, $enhance_privacy, $playsinline, 0 ) // 3
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

		/**
		 * Gallery items iterator.
		 *
		 * @var integer $item_num This parameter indicates current video item position.
		 */
		$item_num = 0;

		/**
		 * Iterate through a list of YouTube Video ID's and process each one.
		 *
		 * @var string[] $eytg_ids An array of YouTube Video IDs.
		 * @var string   $video_id The current YouTube Video ID from the $eytg_ids array.
		 */
		foreach ( $eytg_ids as $video_id ) {

			// Increase number of items
			++$item_num;

			/**
			 * Active thumbnail indicator.
			 *
			 * @var string $item_active This parameter indicates if current thumbnail represent currently played video.
			 */
			$item_active = '';

			/**
			 * Position of the item in gallery.
			 *
			 * @var string $item_position This parameter indicates if current item is first, middle or last in the gallery.
			 */
			$item_position = '';

			switch ( $item_num ) {
				case 1:
					$item_position = 'first';
					if ( true === $wall_mode ) {
						$item_active = 'active';
					}
					break;
				case $total_ids:
					$item_position = 'last';
					break;
				default:
					$item_position = 'mid';
			}

			/**
			 * Current item custom thumbnail title.
			 *
			 * @var string $item_title This parameter indicates current thumbnail custom title.
			 */
			$item_title = '';
			if ( ! empty( $titles ) ) {
				$tnum = $item_num - 1;
				if ( is_array( $titles ) && ! empty( $titles[ $tnum ] ) ) {
					$item_title = sprintf(
						'<span class="eytg-title %1$s">%2$s</span>',
						$title_position,                     // 1
						esc_html( trim( $titles[ $tnum ] ) ) // 2
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
						style="background-image: url(https://img.youtube.com/vi/%1$s/%8$s.jpg)"></span>
					%9$s
				</a>',
				$video_id,        // 1
				$controls,        // 2
				$playsinline,     // 3
				$enhance_privacy, // 4
				$item_num,        // 5
				$item_position,   // 6
				$item_active,     // 7
				$thumbnail_size,  // 8
				$item_title,      // 9
				$this->build_video_query( 1, $controls, $enhance_privacy, $playsinline, 0 ) // 10
			);

		} // END foreach

		// Close gallery container
		$output .= sprintf(
			'</section><!-- easy_youtube_gallery col-%1$s ar-%2$s %3$s --></section><!-- .eytg_main_container .ar-%2$s -->',
			$columns,      // 1
			$aspect_ratio, // 2
			$html_class    // 3
		);

		// Now enqueue plugin JS as we need it
		if ( ! wp_script_is( 'easy-youtube-gallery', 'enqueued' ) ) {
			wp_enqueue_script( 'easy-youtube-gallery' );
		}

		// Return prepared HTML structure
		return $output;
	} // END public function shortcode

	/**
	 * Sanitize and validate YouTube video IDs separated by commas.
	 *
	 * @param string $video_ids Comma-separated YouTube Video IDs.
	 * @return array Array of sanitized YouTube Video IDs
	 */
	public function video_ids_to_array( $video_ids ) {
		// Split the input string into an array of IDs
		$ids = explode( ',', $video_ids );

		// Valid YouTube Video ID ha 11 characters:
		// lowercase and uppercase letters, digits, underscore and dash
		$allowed_characters_pattern = '/^[a-zA-Z0-9_-]{11}$/';

		// Sanitize and validate each ID
		$sanitized_ids = array();
		foreach ( $ids as $id ) {
			$id = trim( $id ); // Remove extra spaces
			if ( preg_match( $allowed_characters_pattern, $id ) ) {
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
	 * @param string $playsinline
	 * @param string $rel
	 * @return string URL query parameters
	 */
	public function build_video_query(
		$autoplay = '0',
		$controls = '0',
		$enhanceprivacy = '0',
		$playsinline = '0',
		$rel = '0'
	) {
		$query = http_build_query(
			array(
				'autoplay'       => $autoplay,
				'controls'       => $controls,
				'enhanceprivacy' => $enhanceprivacy,
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
} // END class Main
