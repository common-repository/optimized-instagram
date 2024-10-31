<?php

/*
Plugin Name: Optimized Instagram
Plugin URI: https://wordpress.org/plugins/optimized-instagram/
Description: Downloads images to local server and outputs them through widget
Author: CompleteWebResources
Author URI: https://www.completewebresources.com/
Text Domain: optimized-instagram
Version: 1.0.2
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define("OPTIMIZED_INSTAGRAM_VERSION", "1.0.2");

if ( ! class_exists( 'OptimizedInstagram' ) ) {

	class OptimizedInstagram {
			/**
	 		* @var $instance
	 		*/
	 		private static $instance;

			public function __construct() {
				global $pagenow, $typenow;
				//checking for max execution time
				$this->setup_globals();
				$this->include_libs();
				$this->setup_hooks();
			}

			private function include_libs() {
				require_once ( $this->plugin_dir . 'inc/image_handler.php' );
				require_once ( $this->plugin_dir . 'inc/widget.php' );
				require_once ( $this->plugin_dir . 'inc/widget_settings.php' );
			}

			private function setup_globals() {
				$this->file         = __FILE__;
				$this->basename     = plugin_basename( $this->file );
				$this->plugin_dir   = plugin_dir_path( $this->file );
				$this->plugin_url   = plugin_dir_url ( $this->file );
			}

			private function setup_hooks() {
				register_activation_hook( __FILE__, array( $this, 'activate' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' )  );
				add_action( 'widgets_init', function() {
					register_widget( 'OptimizedInstagramWidget' );
				});
				register_activation_hook( __FILE__, array( $this, 'activate' ) );
				register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
				add_filter( 'cron_schedules',  array( $this, 'set_custom_cron_schedules' ));
				add_action( 'opt-inst-check-updates', array( 'OptimizedInstagramImageHandler', 'update_images'), 10, 1 );
			}

			public function set_custom_cron_schedules( $schedules ) {
				$schedules['weekly'] = array(
	      			'interval' => 604800, // 1 week in seconds
   					'display'  => __( 'Once Weekly' ),
	      		);

				return $schedules;
			}

			public function enqueue_script() {
				wp_enqueue_style( 'opt-inst-style', $this->plugin_url . 'css/style.css', array(), OPTIMIZED_INSTAGRAM_VERSION );
			}
		
			public function activate() {
				if( !wp_next_scheduled( 'opt-inst-check-updates' ) ) {
					wp_schedule_event( time(), 'hourly', 'opt-inst-check-updates', array('hourly') );
				}
				if( !wp_next_scheduled( 'opt-inst-check-updates' ) ) {
					wp_schedule_event( time(), 'daily', 'opt-inst-check-updates', array('daily') );
				}
				if( !wp_next_scheduled( 'opt-inst-check-updates' ) ) {
					wp_schedule_event( time(), 'weekly', 'opt-inst-check-updates', array('weekly') );
				}
			}

			public function deactivate() {
				$timestamp = wp_next_scheduled( 'opt-inst-check-updates', array('hourly') );
				wp_unschedule_event( $timestamp, 'opt-inst-check-updates', array('hourly') );
				$timestamp = wp_next_scheduled( 'opt-inst-check-updates', array('daily') );
				wp_unschedule_event( $timestamp, 'opt-inst-check-updates', array('daily') );
				$timestamp = wp_next_scheduled( 'opt-inst-check-updates', array('weekly') );
				wp_unschedule_event( $timestamp, 'opt-inst-check-updates', array('weekly') );
			}
		}

		new OptimizedInstagram();
	}

?>