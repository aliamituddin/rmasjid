<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 4/18/2019
 * Time: 10:43 PM
 *
 * @package Masjid/Settinga
 */

namespace Masjid\Settings;

use Masjid\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MaDesigner' ) ) {

	/**
	 * Class MaDesigner
	 */
	class Designer {

		/**
		 * Private instance variable
		 *
		 * @var null
		 */
		private static $instance = null;
		/**
		 * Private template variable
		 *
		 * @var null
		 */
		private $temp = null;

		/**
		 * Singleton
		 *
		 * @return Designer|null
		 */
		public static function init() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * MaDesigner constructor.
		 */
		private function __construct() {
			$this->instance_temp();
			$this->design_header();
			$this->design_footer();
			$this->custom_css();
		}

		/**
		 * Instance template
		 */
		private function instance_temp() {
			global $temp;
			$this->temp = $temp;
		}

		/**
		 * Adjust content for header
		 */
		private function design_header() {
			add_action( 'header_content', [ $this, 'header_content_callback' ], 10 );
			add_action( 'header_content', [ $this, 'top_navbar_callback' ], 20 );
			add_action( 'header_content', [ $this, 'maybe_small_header_callback' ], 30 );
			add_action( 'header_content', [ $this, 'maybe_content_wrapper_callback' ], 40 );
			add_filter( 'small_header_content', [ $this, 'small_header_content_callback' ], 1, 3 );
		}

		/**
		 * Adjust content for footer
		 */
		private function design_footer() {
			add_action( 'footer_content', [ $this, 'maybe_content_wrapper_close_callback' ], 10 );
			add_action( 'footer_content', [ $this, 'footer_content_callback' ], 20 );
		}

		/**
		 * Add custom css
		 */
		private function custom_css() {
			add_action( 'wp_head', [ $this, 'custom_css_callback' ] );
		}

		/**
		 * Callback for rendering header content
		 */
		public function header_content_callback() {
			echo $this->temp->render( 'header' ); // phpcs:ignore WordPress.Security.EscapeOutput
		}

		/**
		 * Callback for rendering top navbar
		 */
		public function top_navbar_callback() {
			echo $this->temp->render( 'top-nav-2', [ 'brand' => get_bloginfo( 'name' ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput
		}

		/**
		 * Maybe callback for rendering small header
		 */
		public function maybe_small_header_callback() {
			if ( ! is_front_page() ) {
				echo $this->temp->render( 'header-small' ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}

		/**
		 * Callback for rendering custom header small
		 *
		 * @param int    $image_id   image attachment id.
		 * @param string $title      header title content.
		 * @param string $subcontent header subtitle content.
		 */
		public function small_header_content_callback( $image_id, $title, $subcontent = '' ) {
			$result = $this->temp->render(
				'header-small',
				[
					'image_url'    => ( $image_id ) ? wp_get_attachment_image_url( $image_id, 'large' ) : '',
					'header_title' => $title,
					'subcontent'   => $subcontent,
				]
			);
			echo $result;  // phpcs:ignore WordPress.Security.EscapeOutput
		}

		/**
		 * Maybe callback for rendering content wrapper
		 */
		public function maybe_content_wrapper_callback() {
			if ( ! is_front_page() ) {
				echo '<div class="container py-5 normal">';
				echo '<div class="container">';
			}
		}

		/**
		 * Maybe callback for rendering content wrapper close
		 */
		public function maybe_content_wrapper_close_callback() {
			if ( ! is_front_page() ) {
				echo '</div>';
				echo '</div>';
			}
		}

		/**
		 * Callback for rendering footer content
		 */
		public function footer_content_callback() {
			$footer_style  = get_theme_mod( 'footer_style', 'style1' );
			$color_scheme  = get_theme_mod( 'color_scheme', 'bg-dark' );
			$locations     = get_nav_menu_locations();
			$footer1       = wp_get_nav_menu_object( $locations['footer1_nav'] );
			$footer1_title = $footer1->name;
			$footer1_items = wp_get_nav_menu_items( $footer1->name );
			$footer2       = wp_get_nav_menu_object( $locations['footer2_nav'] );
			$footer2_title = $footer2->name;
			$footer2_items = wp_get_nav_menu_items( $footer2->name );
			$footer3       = wp_get_nav_menu_object( $locations['footer3_nav'] );
			$footer3_title = $footer3->name;
			$footer3_items = wp_get_nav_menu_items( $footer3->name );
			$result        = $this->temp->render(
				'footer-' . $footer_style,
				[
					'title'           => get_bloginfo( 'name' ),
					'description'     => get_bloginfo( 'description' ),
					'footnote'        => '&copy; ' . get_bloginfo( 'name' ) . ' ' . date( 'Y' ),
					'color_scheme'    => $color_scheme,
					'footer1_title'   => $footer1_title,
					'footer1_items'   => $footer1_items,
					'footer2_title'   => $footer2_title,
					'footer2_items'   => $footer2_items,
					'footer3_title'   => $footer3_title,
					'footer3_items'   => $footer3_items,
					'social_networks' => Helpers\Helper::get_social_network_url(),
				]
			);
			echo $result;  // phpcs:ignore WordPress.Security.EscapeOutput
		}

		/**
		 * Callback for rendering custom css
		 */
		public function custom_css_callback() {
			if ( ! is_admin() ) {
				echo $this->temp->render( 'custom-css' ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}
	}
}

global $designer;
$designer = Designer::init();
