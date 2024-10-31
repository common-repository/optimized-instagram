<?php

class OptimizedInstagramWidget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'opt_inst', // Base ID
			__( 'Optimized Instagram', 'opt_inst' ), // Name
			array( 'description' => __( 'Optimized Instagram Widget', 'opt_inst' ), ) // Args
			);
		$this->opt_inst_settings = OptimizedInstagramWidgetSettings::get_widget_settings();
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];

		$images = null;

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}

		$options = $instance;

		if (!empty( $instance['saved_images'] ) ) {
			$images = array_slice( unserialize( base64_decode( $instance['saved_images'] ) ), 0, $instance['images_count'] );
		}

		if ( !empty( $images ) ) {
			?>
			<ul class="opt-inst-wrap">
				<?php
				if ( 'lightbox' == $instance['image_open_mode']) {

					$rel_attribute = 'lightbox';

					if ( !empty( $instance['rel_attribute'] ) ) {
						$rel_attribute = $instance['rel_attribute'];
					}

					foreach ($images as $code => $image) {
					?>
						<li><a href="<?php echo OptimizedInstagramImageHandler::get_upload_url($instance['username']).'/'. $image['standart'] ?>" rel="<?php echo $rel_attribute; ?>"><img alt="" src="<?php echo OptimizedInstagramImageHandler::get_upload_url($instance['username']) . '/' . $image[$instance['thumb_size']] ?>" /></a></li>
					<?php
					}
				} elseif ( 'linktoimage' == $instance['image_open_mode']) {
					foreach ($images as $code => $image) {
					?>
						<li><a href="<?php echo OptimizedInstagramImageHandler::get_upload_url($instance['username']).'/'. $image['standart'] ?>"><img alt="" src="<?php echo OptimizedInstagramImageHandler::get_upload_url($instance['username']) . '/' . $image[$instance['thumb_size']] ?>" /></a></li>
					<?php
					}
				} elseif ( 'redirect' == $instance['image_open_mode']) {
					foreach ($images as $code => $image) {
					?>
						<li><a href="https://instagram.com/p/<?php echo $code ?>" target="_blank"><img alt="" src="<?php echo OptimizedInstagramImageHandler::get_upload_url($instance['username']) . '/' . $image[$instance['thumb_size']] ?>" /></a></li>
					<?php
					}
				} elseif ( 'nolink' == $instance['image_open_mode']) {
					foreach ($images as $code => $image) {
					?>
						<li><img alt="" src="<?php echo OptimizedInstagramImageHandler::get_upload_url($instance['username']).'/'. $image[$instance['thumb_size']] ?>" /></li>
					<?php
					}
				}
				?>
				</ul>
				<?php
			}

			echo $args['after_widget'];
		}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {

		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$opts = $instance;

		$settings = $this->opt_inst_settings;

		?>
		<input type="hidden" id="<?php echo $this->get_field_id( 'saved_images' ) ?>" name="<?php echo $this->get_field_name( 'saved_images' ) ?>" value="<?php if (!empty($instance['saved_images'])) echo esc_attr( $instance['saved_images'] ) ?>"/>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				var image_open_mode = jQuery('select#<?php echo $this->get_field_id('image_open_mode'); ?>');
				var lightbox_note = jQuery('#<?php echo $this->get_field_id('image_open_mode'); ?>_note');
				var rel_attribute = jQuery('#<?php echo $this->get_field_id('rel_attribute'); ?>');

				image_open_mode.change( function() {
					if ('lightbox' == jQuery(this).val()) {
						lightbox_note.html( '<?php echo self::display_lightbox_note() ?>' );

						if ( rel_attribute.length ) {
							rel_attribute.closest('p').removeClass('hidden');
						}
					} else {
						lightbox_note.html('');

						if ( rel_attribute.length ) {
							rel_attribute.closest('p').addClass('hidden');
						}
					}
				});
			});
		</script>
		<?php

		foreach ($settings as $k => $v ) {

			$option_value = isset($opts[$k]) ? $opts[$k] : '';

			switch ($v['type']) {
				case 'select': {
					?>
					<p>
					<?php

					$output = '<label>'.esc_html($v['title']).' <select name="' .$this->get_field_name($k) . '" id="'. $this->get_field_id($k) . '">';

					foreach ( $v['options'] as $opt_key => $opt_title ) {
						$output .= ' <option value="' . esc_attr( $opt_key ) . '" ' . selected( $option_value, $opt_key, false ). '>' .  $opt_title . "</option>";
					}

					$output .= '</select></label>';

					if ('image_open_mode' == $k) {
						$output .= '<span id="'.$this->get_field_id($k) .'_note">' . ( ( (!isset($opts[$k])) || ($opts[$k] == 'lightbox') ) ? self::display_lightbox_note() : '' ) . '</span>';
					}

					echo $output;

					?>
					</p>
					<?php
				}
				break;

				case 'text': {

					$elem_class = '';

					if ( !empty( $v['showif'] ) ) {
						if (
							( !empty( $opts[ $v['showif']['field'] ] ) ) &&
							( $opts[ $v['showif']['field'] ] !== $v['showif']['value'] )
						) {
							$elem_class = 'hidden';
						}
					}

					?>
					<p<?php if ( !empty( $elem_class ) ) echo ' class="' . $elem_class . '"'; ?>><label><?php echo esc_html($v['title'])?> <input type="text" id="<?php echo $this->get_field_id($k) ?>" name="<?php echo $this->get_field_name($k) ?>" <?php if ( !empty( $v['placeholder'] ) ) echo 'placeholder="' . esc_attr( $v['placeholder'] ) . '" '; ?>value="<?php echo esc_attr( $option_value ) ?>"/></label></p>
					<?php
				}
				break;

				case 'checkbox': {
					?>
					<p><label><?php echo esc_html($v['title'])?> <input type="checkbox" id="<?php echo $this->get_field_id($k) ?>"  name="<?php echo $this->get_field_name($k) ?>" <?php checked( $option_value, 'on', true )?> /></label></p>
					<?php
				}
				break;
			}
		}
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) { 
		$instance = array();
		$instance = $new_instance;
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		//upload photos
		if ($instance['username'] && $instance['images_count'] && $instance['thumb_size'] ) {
			$saved_images = OptimizedInstagramImageHandler::save_images( $instance['username'],  $instance['images_count'],  $instance['thumb_size'], 'on' != $instance['filter_videos'] );
			$instance['saved_images'] = base64_encode(serialize( $saved_images ));
		}

		return $instance;
	}

	public function display_lightbox_note() {
		return __( 'Recommended lightbox plugins:', 'optimized-instagram' ) . '<br/>' . 
		__('<strong><a target="_blank" href="https://wordpress.org/plugins/responsive-lightbox/">Responsive Lightbox</a></strong> by dFactory, <strong><a target="_blank" href="https://wordpress.org/plugins/wp-lightbox-2/">WP Lightbox 2</a></strong>, <strong><a target="_blank" href="https://wordpress.org/plugins/fancybox-for-wordpress/">FancyBox for WordPress</a></strong>, <strong><a target="_blank" href="https://wordpress.org/plugins/wp-jquery-lightbox/">WP jQuery Lightbox</a></strong>', 'optimized-instagram' );
	}
}

?>