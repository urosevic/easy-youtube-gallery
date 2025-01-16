<?php
/**
 * Plugin Name: Easy YouTube Gallery
 * Plugin URI: https://urosevic.net/wordpress/plugins/easy-youtube-gallery/
 * Description: Quick and easy embed thumbnails gallery for custom set of YouTube videos provided in shortcode, and autoplay video on click in Magnific PopUp lightbox.
 * Author: Aleksandar Urošević
 * Version: 1.0.5
 * Author URI: https://urosevic.net/
 * Text Domain: easy-youtube-gallery
 */
namespace Techwebux\Eytg;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EYTG_VER', '1.0.5' );
define( 'EYTG_DB_VER', 1 );
define( 'EYTG_FILE', __FILE__ );

// Load files.
require_once __DIR__ . '/classes/autoload.php';
new Main();
