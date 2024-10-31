<?php

class OptimizedInstagramImageHandler {

	/**
	 * Get contents of the remote location
	 * @param  string $url Remote location
	 */
	public static function get_remote_data($url) {
		$get = wp_remote_get( $url );

		if ( $get && is_array( $get ) ) {
			$data = wp_remote_retrieve_body( $get );

			if ( $data ) {
				$response_code = wp_remote_retrieve_response_code( $get );

				if ( !empty( $response_code ) && ($response_code == '200' ) ) {
					return $data;
				}
			}
		}

		return null;
	}

	/**
	 * Get full local path for saved images
	 * @param  [type] $username [description]
	 * @return [type]           [description]
	 */
	public static function get_upload_dir( $username ) {
		$upload_dir = wp_upload_dir();
		$user_dirname = $upload_dir['basedir'].'/optimized-instagram/'.$username;

		if ( ! file_exists( $user_dirname ) ) {
			wp_mkdir_p( $user_dirname );
		}

		return $user_dirname;
	}

	/**
	 * Get local url for saved images
	 * @param  [type] $username [description]
	 * @return [type]           [description]
	 */
	public static function get_upload_url( $username ) {
		$upload_dir = wp_upload_dir();
		$user_dirname = $upload_dir['baseurl'].'/optimized-instagram/'.$username;
		return $user_dirname;
	}

	/**
	 * Get info about account images
	 * @param  [type]  $username    [description]
	 * @param  [type]  $limit       [description]
	 * @param  boolean $load_videos [description]
	 * @return [type]               [description]
	 */
	public static function get_user_images( $username, $limit, $load_videos = true ) {
		$max_id = 0;
		$all_items = array();
		$i = 0; //number of images
		while(1) {
			$url = 'https://www.instagram.com/'.$username.'/media/'.( 0 != $max_id ? '?max_id='.$max_id : '');
			$json = self::get_remote_data($url);
			if (!$json) {
				break;
			}
			$init_data = json_decode($json);
			if ($init_data) {
				if ( 'ok' == $init_data->status ) {
					foreach ( $init_data->items as $item ) {
						if (!$load_videos && isset($item->videos)) {
							continue;
						}
						$all_items[$item->code] = $item->images;
						$last_item_id = $item->id;
						$i++;
						if ($i >= $limit){
							break;
						}
					}
					if ($i >= $limit) {
						break;
					}
					if ( 20 <= count($init_data->items) ) {
								//we have more that 20 items
						$max_id = $last_item_id;
					} else {
						break;
					}
				} else {
					break;
				}

			} else {
				break;
			}
		}

		return $all_items;
	}

/**
 * Save images to local directory
 * @param  [type] $username instagram account username
 * @param  int 		$number 	number of images to save 
 * @param  [type] $size     pixel size of width and height
 * @return [type]           array of file paths and sizes
 */
public static function save_images( $username, $limit, $size = '80', $load_videos = true ) {

	$upload_dir = self::get_upload_dir( $username );
	$images_info = self::get_user_images( $username, $limit, $load_videos );
	$saved_images = array();

	foreach ( $images_info as $code => $image_info) {

		$image_url = $image_info->standard_resolution->url;

		$remote_content = self::get_remote_data($image_url);

		if (!empty($remote_content)) {

			//save standart resolution
			$standart_image = $code.'.jpg';
			$standart_image_path = $upload_dir.'/'.$standart_image;

			$bytes_written = FALSE;

			if (!file_exists($standart_image_path)) {
				$bytes_written = file_put_contents( $standart_image_path, $remote_content);
			} else {
				$bytes_written = filesize($standart_image_path);
			}

			if (!empty($bytes_written)) {
				//save resized image
				$resized_image = $code.'_'.$size.'.jpg';

				if (!file_exists($upload_dir.'/'.$resized_image)){
					self::img_resize( $standart_image_path, $size, $upload_dir, $resized_image );
				}

				$saved_images[$code] = array( 
					'standart' => $standart_image, 
					$size => $resized_image,
				);
			}
		}
	}

	return $saved_images;
}

/**
 * Update widget images
 * @return [type] [description]
 */
public static function update_images( $schedule ) {
	$instances = get_option('widget_opt_inst');

	if (!empty($instances) && is_array($instances)) {
		foreach ( $instances as $k => $instance ) {
			if ('_multiwidget' == $k  || !$instance['username'] ||  !$instance['images_count'] || !$instance['thumb_size'] ) {
				continue;
			}

			if ( $schedule != $instance['schedule'] ) {
				//current schedule not as widget's
				continue;
			}

			$load_videos = true;

			if (!empty($instance['filter_videos'])) {
				$load_videos = 'on' != $instance['filter_videos'];
			}		

			$saved_images = self::save_images( $instance['username'],  $instance['images_count'],  $instance['thumb_size'], $load_videos );
			$instances[$k]['saved_images'] = base64_encode(serialize( $saved_images ));
		}
	}

	update_option('widget_opt_inst', $instances );
}

/**
 * Resized thumbnails from standart size picture
 * @param  [type]  $tmpname     [description]
 * @param  [type]  $size        [description]
 * @param  [type]  $save_dir    [description]
 * @param  [type]  $save_name   [description]
 * @param  integer $maxisheight [description]
 * @return [type]               [description]
 */
public static function img_resize( $tmpname, $size, $save_dir, $save_name, $maxisheight = 0 ) {
	$save_dir    .= ( substr($save_dir,-1) != "/") ? "/" : "";
	$gis         = getimagesize($tmpname);
	$type        = $gis[2];

	switch($type) {
		case "1": $imorig = imagecreatefromgif($tmpname); break;
		case "2": $imorig = imagecreatefromjpeg($tmpname);break;
		case "3": $imorig = imagecreatefrompng($tmpname); break;
		default:  $imorig = imagecreatefromjpeg($tmpname);
	}

	$x = imagesx($imorig);
	$y = imagesy($imorig);

	$woh = (!$maxisheight)? $gis[0] : $gis[1] ;    

	if ($woh <= $size) {
		$aw = $x;
		$ah = $y;
	} else {
		if (!$maxisheight) {
			$aw = $size;
			$ah = $size * $y / $x;
		} else {
			$aw = $size * $x / $y;
			$ah = $size;
		}
	}

	$im = imagecreatetruecolor($aw,$ah);

	if (imagecopyresampled($im,$imorig , 0,0,0,0,$aw,$ah,$x,$y))
		if (imagejpeg($im, $save_dir.$save_name)) {
			return true;
		} else {
			return false;
		}
	}
}

?>