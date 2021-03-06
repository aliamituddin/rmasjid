<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 5/19/2019
 * Time: 8:39 PM
 *
 * @package Masjid/Settings
 */

namespace Masjid\Settings;

use Kirki\Core\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Customizer' ) && class_exists( 'Kirki' ) ) {

	/**
	 * Class Customizer
	 *
	 * @package Masjid\Settings
	 */
	class Customizer {

		/**
		 * Private instance variable
		 *
		 * @var null
		 */
		private static $instance = null;
		/**
		 * Private config_id variable
		 *
		 * @var string
		 */
		private $config = '';

		/**
		 * Singleton
		 *
		 * @return \Masjid\Settings\Customizer|null
		 */
		public static function init() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Customizer constructor.
		 */
		private function __construct() {
			$this->config = 'ma_customizer';
			$this->add_config();
			$this->add_panels();
			$this->add_sections();
			$this->add_fields();
		}

		/**
		 * Callback to add config
		 */
		private function add_config() {
			\Kirki::add_config(
				$this->config,
				[
					'capability'  => 'edit_theme_options',
					'option_type' => 'theme_mod',
				]
			);
		}

		/**
		 * Callback to add panels
		 */
		private function add_panels() {
			\Kirki::add_panel(
				'designer',
				[
					'priority' => 100,
					'title'    => __( 'Designer', 'masjid' ),
				]
			);
		}

		/**
		 * Callback to add sections
		 */
		private function add_sections() {
			\Kirki::add_section(
				'header',
				[
					'title'    => __( 'Header', 'masjid' ),
					'panel'    => 'designer',
					'priority' => 160,
				]
			);

			\Kirki::add_section(
				'footer',
				[
					'title'    => __( 'Footer', 'masjid' ),
					'panel'    => 'designer',
					'priority' => 160,
				]
			);
		}

		/**
		 * Callback to add fields
		 */
		private function add_fields() {
			// Site Identity.
			\Kirki::add_field(
				$this->config,
				[
					'type'        => 'image',
					'settings'    => 'def_bg',
					'label'       => __( 'Background Image', 'masjid' ),
					'description' => __( 'This will be set as a default background image, leave it empty to use the stock image (image of prophet mosque)', 'masjid' ),
					'section'     => 'title_tagline',
					'default'     => '',
					'choices'     => [
						'save_as' => 'id',
					],
				]
			);
			\Kirki::add_field(
				$this->config,
				[
					'type'     => 'color',
					'settings' => 'def_color',
					'label'    => __( 'Color Scheme', 'masjid' ),
					'section'  => 'title_tagline',
					'default'  => '#d3a55f',
				]
			);

			// Header.
			\Kirki::add_field(
				$this->config,
				[
					'type'        => 'text',
					'settings'    => 'location',
					'label'       => __( 'Location', 'masjid' ),
					'section'     => 'header',
					'default'     => 'sleman',
					'description' => __( 'Put your city name or zip code to fetch prayer times', 'masjid' ),
					'priority'    => 10,
				]
			);

			// Footer.
			\Kirki::add_field(
				$this->config,
				[
					'type'     => 'radio-image',
					'settings' => 'footer_style',
					'label'    => __( 'Design', 'masjid' ),
					'section'  => 'footer',
					'default'  => 'style1',
					'priority' => 10,
					'choices'  => [
						'style1' => TEMP_URI . '/assets/admin/img/footer1-min.jpg',
						'style2' => TEMP_URI . '/assets/admin/img/footer2-min.jpg',
					],
				]
			);
			\Kirki::add_field(
				$this->config,
				[
					'type'     => 'radio',
					'settings' => 'color_scheme',
					'label'    => __( 'Color Scheme', 'masjid' ),
					'section'  => 'footer',
					'default'  => 'bg-dark',
					'priority' => 20,
					'choices'  => [
						'bg-dark'    => [
							__( 'Dark', 'masjid' ),
						],
						'bg-light'   => [
							__( 'Light', 'masjid' ),
						],
						'bg-primary' => [
							__( 'Default', 'masjid' ),
							__( 'Use default color scheme', 'masjid' ),
						],
					],
				]
			);
			\Kirki::add_field(
				$this->config,
				[
					'type'     => 'link',
					'settings' => 'facebook_url',
					'label'    => __( 'Facebook URL', 'masjid' ),
					'section'  => 'footer',
					'priority' => 30,
				]
			);
			\Kirki::add_field(
				$this->config,
				[
					'type'     => 'link',
					'settings' => 'telegram_url',
					'label'    => __( 'Telegram URL', 'masjid' ),
					'section'  => 'footer',
					'priority' => 40,
				]
			);
			\Kirki::add_field(
				$this->config,
				[
					'type'     => 'link',
					'settings' => 'instagram_url',
					'label'    => __( 'Instagram URL', 'masjid' ),
					'section'  => 'footer',
					'priority' => 40,
				]
			);
			\Kirki::add_field(
				$this->config,
				[
					'type'     => 'link',
					'settings' => 'youtube_url',
					'label'    => __( 'Youtube URL', 'masjid' ),
					'section'  => 'footer',
					'priority' => 50,
				]
			);
		}
	}
}

Customizer::init();
