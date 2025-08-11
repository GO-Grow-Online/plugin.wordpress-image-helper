<?php

function render_image($args = []) {

        // If image is empty get placeholder in "general" option page
        $img = $args['img'] ?: get_field('img_placeholder', 'options');

        // Classes parameters - Can be overwritten by WebMaster in $args
        // force_portrait   | create a blured bg image
        // display_legend   | allow to show or hide <caption> 
        // seamless         | add a class deleting default styles
        $force_portrait = !empty($img) ? get_field('force_portrait', $img['id']) : false;
        $display_legend = !empty($img) ? get_field('display_legend', $img['id']) : false;
        $seamless = !empty($img) ? get_field('seamless', $img['id']) : false;

        $defaults = [
            'img' => null,
            'format' => null,
            'fs' => false,
            'defer' => true,

            'seamless' => $seamless,
            'force_portrait' => $force_portrait,
            'display_legend' => $display_legend,
            
            // If display legend is true, we need <figcaption> anyway
            'figcaption' => $display_legend,
        ];
        
        // Combine both argument arrays - $args is primary
        $args = wp_parse_args($args, $defaults);

        // Validate 'img' argument
        if ($args['img'] && (!is_array($args['img']) || !isset($args['img']['url']))) {
            trigger_error('Invalid image format provided. Expected an array with a "url" key.', E_USER_WARNING);
            $args['img'] = null;
        }

        $loading = $args['defer'] ? "lazy" : "eager";
        $mime_type = $img['mime_type'] ?? '';
        $is_svg = $mime_type == 'image/svg+xml';

        ?>

        <div class="img-wrap<?php 
            echo $args['force_portrait'] ? ' img-wrap--portrait' : ''; 
            echo $args['seamless'] ? ' img-wrap--seamless' : ''; 
            echo $args['display_legend'] ? ' img-wrap--displayLegend' : ''; 
            ?>">

            <?php if ($img) : ?>
                <?php 
                // Render blured bg image in "force_portrait" mode
                if ($args['force_portrait'] && !$is_svg) { 
                    echo '<!--googleoff: index--><img class="imgWrap__bg__img" loading="'. esc_attr($loading) .'" type="'. esc_attr($mime_type) .'" src="'. esc_url($img['sizes']['thumbnail']) .'" alt="'. esc_attr($img['alt']) .'"><!--googleon: index-->'; }
                ?>
                
                <?php if ($args['figcaption']) : ?>
                    <figure class="img-wrap__figure" itemscope itemtype="http://schema.org/ImageObject">
                <?php endif; ?>

                <?php 
                // Render picture content if no image format is set, and if file is svg only with tab or mob files assigned
                $no_img_format = !$args['format'];

                if ($no_img_format && !$is_svg) {
                    $mob = get_field('mob_img', $img['id']);
                    $tab = get_field('tab_img', $img['id']);
                    $thumbnail = $mob ? $mob['sizes']['thumbnail'] : $img['sizes']['thumbnail'];
                    $medium = $tab ? $tab['sizes']['medium'] : $img['sizes']['medium'];

                    // Width & Height attr
                    $w = $img['width'] ? $img['width'] : 650;
                    $h = $img['height'] ? $img['height'] : 650;
                    ?>
                    <picture>
                        <source media="(max-width: 500px)" type="<?php echo esc_attr($mime_type); ?>" srcset="<?php echo esc_url($thumbnail); ?>">
                        <source media="(max-width: 1023px)" type="<?php echo esc_attr($mime_type); ?>" srcset="<?php echo esc_url($medium); ?>">

                        <?php if ($args['fs']) : ?>
                            <source media="(min-width: 1024px)" type="<?php echo esc_attr($mime_type); ?>" srcset="<?php echo esc_url($img['sizes']['large']); ?>">
                        <?php elseif ($img['sizes']['medium'] !== $medium) : ?>
                            <source media="(min-width: 1024px)" type="<?php echo esc_attr($mime_type); ?>" srcset="<?php echo esc_url($img['sizes']['medium']); ?>">
                        <?php endif; ?>

                        <img width="<?php echo $w; ?>" width="<?php echo $h; ?>" loading="<?php echo esc_attr($loading); ?>" alt="<?php echo esc_attr($img['alt']); ?>" src="<?php echo esc_url($medium); ?>">

                    </picture>
                    <?php
                } else {
                    
                    if (!$is_svg) { // Has image format but is not svg
                        
                        // Fallback if the image format is not generated on the website
                        $format = isset($img['sizes'][$args['format']]) ? $img['sizes'][$args['format']] : $img['sizes']['thumbnail'];
                        if (!isset($img['sizes'][$args['format']]) && is_user_logged_in()) {
                            echo "<span class='admin-msg'>Format not found. Thumbnail loaded.</span>";
                        }

                        printf('<img loading="%s" type="%s" src="%s" alt="%s" width="%d" height="%d">', esc_attr($loading), esc_attr($mime_type), esc_url($format), esc_attr($img['alt']), esc_attr($img['width']), esc_attr($img['height']));

                    } else { // Is SVG
                        printf('<img loading="%s" type="%s" src="%s" alt="%s" width="%d" height="%d">', esc_attr($loading), esc_attr($mime_type), esc_url($img['url']), esc_attr($img['alt']), esc_attr($img['width']), esc_attr($img['height']));
                    }
                }
                ?>

                <?php 
                // Render figcaption 
                if ($args['figcaption']) :
                        if (!empty($img['caption'])) { echo '<figcaption class="img-wrap__figcaption">' . esc_html($img['caption']) . '</figcaption>'; }
                        if (!empty($img['url'])) { echo '<meta itemprop="url" content="' . esc_html($img['url']) . '"/>'; }
                        if (!empty($img['description'])) { echo '<meta itemprop="description" content="' . esc_html($img['description']) . '"/>'; }
                        if (!empty($img['name'])) { echo '<meta itemprop="name" content="' . esc_html($img['name']) . '"/>'; } ?>
                    </figure>
                <?php endif; ?>

            <?php else : ?>
                <img width="650" height="650" loading="<?php echo esc_attr($loading) ?>" src="<?php echo esc_url(plugins_url('/assets/image_placeholder.svg', __FILE__)); ?>" alt="Logo de <?php bloginfo('name'); ?> - Aucune image trouvée">
            <?php endif; ?>
        </div>

    <?php
}

// Display svg's code instead of an 'img' element
function get_svg( $media_file, $is_url = false ) {
    if ($is_url) {
        $html = file_get_contents( $media_file );
        return $html;
    }else{
        if ($media_file['mime_type'] === 'image/svg+xml') {
            $file_path = get_attached_file( $media_file['ID'] );
            $html = file_get_contents( $file_path );
            return $html;
        }
    }

    return is_user_logged_in() ? '<p class="admin-msg">Invalid file type. Please upload an SVG.</p>' : '';
}

?>