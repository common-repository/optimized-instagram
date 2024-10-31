<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'OptimizedInstagramWidgetSettings' ) ) {
	/**
  * Static class for setting widget options
  */
	class OptimizedInstagramWidgetSettings {

		static function get_widget_settings() {
			
			return array(

				'username'		=> array(
					'type'			=> 'text',
					'title'			=> __( 'Account name', 'optimized-instagram' )
					),

				'images_count'	=> array(
					'type'			=> 'text',
					'title'			=> __( 'Number of images to show', 'optimized-instagram' )
					),

				'thumb_size'	=> array(
					'type'			=> 'text',
					'title'			=> __( 'Size of thumbnails(px)', 'optimized-instagram' )
					),

				'filter_videos' => array(
					'type'			=> 'checkbox',
					'title'			=> __( 'Don\'t show videos', 'optimized-instagram' )
					),

				'schedule'		=>  array(
					'type'			=> 'select',
					'title'			=> 'Image update period',
					'options'		=> array(
						'hourly'		=> __( 'Hourly', 'optimized-instagram' ),
						'daily'			=> __( 'Daily', 'optimized-instagram' ),
						'weekly'		=> __( 'Weekly', 'optimized-instagram' )
						)
					),

				'image_open_mode'	=> array(
					'type'				=> 'select', 
					'title'				=> __( 'Thumbnail type', 'optimized-instagram' ),
					'options'			=> array(
						'lightbox'			=> __( 'Link to image + Lightbox support', 'optimized-instagram' ),
						'linktoimage'		=> __( 'Standard link to image', 'optimized-instagram' ),
						'redirect'			=> __( 'Redirect to Instagram', 'optimized-instagram' ),
						'nolink'			=> __( 'Show image only', 'optimized-instagram' ),
						)
					),

				'rel_attribute'	=> array(
					'type'				=> 'text',
					'placeholder'		=> 'lightbox',
					'showif'			=> array(
							'field'			=> 'image_open_mode',
							'value'			=> 'lightbox'
						),
					'title'				=> __( '"rel" attribute value for <A> tag', 'optimized-instagram' )
					),

				);
		}
	}
}

?>