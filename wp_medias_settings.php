<?php 

// Remove unused image sizes
if (!function_exists('remove_unwanted_image_sizes')) {
    function remove_unwanted_image_sizes($sizes) {
        unset($sizes['medium_large']);
        unset($sizes['1536x1536']);
        unset($sizes['2048x2048']);
        unset($sizes['full']);
        
        return $sizes;
    }
}
add_filter('intermediate_image_sizes_advanced', 'remove_unwanted_image_sizes');


// Disactivate 1536x1536 & 2048x2048 completely - Image sizes are still visible
// Since WP 5.3 these are automatically regenerated to handle big images such as retina
function remove_large_image_sizes() {
	remove_image_size( '1536x1536' );  // 2 x Medium Large (1536 x 1536)
	remove_image_size( '2048x2048' );  // 2 x Large (2048 x 2048)
}
add_action( 'init', 'remove_large_image_sizes' );


// Display svg's code instead of an 'img' element
if (!function_exists('get_svg')) {
    function get_svg( $media_file, $is_url = true ) {
        if ($is_url) {
            if ($media_file['mime_type'] === 'image/svg+xml') {
                $file_path = get_attached_file( $media_file['ID'] );
                $html = file_get_contents( $file_path );
                return $html;
            }
        }else{
            $html = file_get_contents( $media_file );
            return $html;
        }

        return is_user_logged_in() ? '<p class="admin-msg">Invalid file type. Please upload an SVG.</p>' : '';
    }
}



// Create Webp Format for each uploaded images
if (!function_exists('go_convert_all_sizes_to_webp')) {
    function go_convert_all_sizes_to_webp($metadata, $attachment_id) {

        $quality = 85;

        // WP Image editor API
        if (!function_exists('wp_get_image_editor')) {
            return $metadata;
        }

        // Get upload dir, add a / with trailingslashit id not allready there
        $upload_dir = wp_upload_dir();
        $basedir    = trailingslashit($upload_dir['basedir']);

        // Convert All images sizes in webp
        if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size_key => $size_data) {

                $size_file = path_join($basedir, $size_data['file']);
                if (!file_exists($size_file)) {
                    continue;
                }

                $info = pathinfo($size_file);
                $ext  = strtolower($info['extension'] ?? '');

                // Ignore webp (if it exists) or svg generation
                if (in_array($ext, ['webp', 'svg'])) {
                    continue;
                }

                // Convert in webp
                $editor = wp_get_image_editor($size_file);
                if (is_wp_error($editor)) {
                    continue;
                }

                $editor->set_quality($quality);
                $new_filename = $info['dirname'] . '/' . $info['filename'] . '.webp';

                $result = $editor->save($new_filename, 'image/webp');
                if (is_wp_error($result)) {
                    continue;
                }

                // Delete previous JPG/PNG
                @unlink($size_file);

                // Update meta (filename + mime-type)
                $relative_file = str_replace($basedir, '', $new_filename);
                $metadata['sizes'][$size_key]['file']      = $relative_file;
                $metadata['sizes'][$size_key]['mime-type'] = 'image/webp';
            }
        }

        // 4) Convert original image
        // => $metadata['file'] has “Full” (or “-scaled” if WP > 5.3)
        if (!empty($metadata['file'])) {
            $image_path = get_attached_file($attachment_id);
            $info = pathinfo($image_path);
            $ext  = strtolower($info['extension'] ?? '');
            $max_width = 2560;

            if (! in_array($ext, ['webp', 'svg'])) {
                $editor = wp_get_image_editor($image_path);
                if (! is_wp_error($editor)) {
                    $editor->set_quality($quality);
                    $editor->resize($max_width, 0); 
            
                    $new_filename = $info['dirname'] . '/' . $info['filename'] . '.webp';
                    $result = $editor->save($new_filename, 'image/webp');
                    if (! is_wp_error($result)) {
                        $relative_file = str_replace($basedir, '', $new_filename);
                        
                        $metadata['file'] = $relative_file;
                        if (isset($metadata['sizes']['scaled'])) {
                            $metadata['sizes']['scaled']['file']      = $relative_file;
                            $metadata['sizes']['scaled']['mime-type'] = 'image/webp';
                        }

                        update_attached_file($attachment_id, $new_filename);
    

                        
                        if (file_exists($image_path)) {
                            $scaled_version = str_replace("-scaled", "", $image_path);
                            @unlink($image_path);
                            @unlink($scaled_version);
                        }
                    }
                }
            }

        }

        return $metadata;
    }
    add_filter('wp_generate_attachment_metadata', 'go_convert_all_sizes_to_webp', 10, 2);
}
