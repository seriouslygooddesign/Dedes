<?php
//Body Class
function core_custom_body_class($classes, $custom_classes = '')
{
    $classes[] = 'sticky-footer';

    if (!empty($custom_classes)) {
        if (is_array($custom_classes)) {
            $classes = array_merge($classes, $custom_classes);
        } else {
            $classes[] = $custom_classes;
        }
    }

    return $classes;
}
add_filter('body_class', 'core_custom_body_class');

//Remove WordPress Version Number
add_filter('the_generator', '__return_empty_string');

//Getting rid of archive "prefix"
function my_theme_archive_title($title)
{
    if (is_category()) {
        $title = single_cat_title('', false);
    } elseif (is_tag()) {
        $title = single_tag_title('', false);
    } elseif (is_author()) {
        $title = '<span class="vcard">' . get_the_author() . '</span>';
    } elseif (is_post_type_archive()) {
        $title = post_type_archive_title('', false);
    } elseif (is_tax()) {
        $title = single_term_title('', false);
    }
    return $title;
}
add_filter('get_the_archive_title', 'my_theme_archive_title');


//Read More
function new_excerpt_length($length)
{
    return 25;
}
add_filter('excerpt_length', 'new_excerpt_length', 999);

function new_excerpt_more($more)
{
    return '...';
}
add_filter('excerpt_more', 'new_excerpt_more');


// Add custom attributes to the script tag
function add_attributes_to_script_tag($url)
{
    if (strpos($url, 'core-module')) {
        $url = str_replace(' src', ' type="module" src', $url);
    }
    return $url;
}
add_filter('script_loader_tag', 'add_attributes_to_script_tag', 10);


//Disable wpo-plugins-tables-list.json file
add_filter('wpo_update_plugin_json', '__return_false');

//Rename "Default template" to "Content Blocks"
add_filter('default_page_template_title', function () {
    return __('Content Blocks', 'your_text_domain');
});


//Upload WOFF and WOFF2 files
add_filter('upload_mimes', 'add_custom_upload_mimes');
function add_custom_upload_mimes($existing_mimes)
{
    $existing_mimes['woff'] = 'application/x-font-woff';
    $existing_mimes['woff2'] = 'application/x-font-woff2';
    return $existing_mimes;
}

//Add Custom Attributes To Gallery Links
function add_custom_attributes_to_gallery_links($link_html, $id, $size)
{
    $size = $size == '1536x1536' ? $size : 'medium_large';
    $full_img_object = wp_get_attachment_image_src($id, '1536x1536');
    $full_img_src = $full_img_object[0];
    $full_img_width = $full_img_object[1];
    $full_img_height = $full_img_object[2];
    $preview_img = wp_get_attachment_image($id, $size, null, ['loading' => 'lazy']);

    wp_enqueue_style('photoswipe', get_template_directory_uri() . '/src/static-plugins/photoswipe/photoswipe.css', array(), '1.1.8');
    wp_enqueue_script('photoswipe-core-module', get_template_directory_uri() . '/src/static-plugins/photoswipe/photoswipe.js', array(), '1.1.16');

    $link_html = "<a data-cropped='true' data-pswp-width='$full_img_width' data-pswp-height='$full_img_height' href='$full_img_src'>$preview_img</a>";
    return $link_html;
}

add_filter('wp_get_attachment_link', 'add_custom_attributes_to_gallery_links', 10, 6);

// Custom gallery
add_filter('post_gallery', 'custom_gallery', 10, 2);
function custom_gallery($output, $attr)
{
    $images = get_posts([
        'post_type' => 'attachment',
        'include' => $attr['ids'],
        'orderby' => $attr['orderby'] ?? 'post__in'
    ]);
    if ($images) {

        $columns = $attr['columns'] ?? 3;
        $size = $attr['size'] ?? 'thumbnail';
        $link = $attr['link'] ?? 'attachment';
        $link_none = $link === 'none';


        $slidesPerView = $columns;
        $slidesPerViewTablet = round($slidesPerView / 2);
        $slidesPerViewMobile = round($slidesPerViewTablet / 2);

        $swiper_options = json_encode(array(
            'slidesPerView' => $slidesPerViewMobile,
            'spaceBetween' => 0,
            'loop' => false,
            'autoHeight' => $link_none,
            'breakpoints' => [
                '768' => [
                    'slidesPerView' => $slidesPerViewTablet,
                ],
                '1200' => [
                    'slidesPerView' => $slidesPerView,
                ]
            ],
        ));

        $output = "<div class='swiper swiper--center swiper--gallery' data-photoswipe data-swiper='$swiper_options'><div class='swiper-wrapper'>";

        foreach ($images as $image) {
            $image_id = $image->ID;
            $output .= "<div class='swiper-slide text-center'>";
            if ($link_none) {
                $output .= wp_get_attachment_image($image_id, $size, null, ['loading' => 'lazy']);
            } else {
                $output .= wp_get_attachment_link($image_id, $size);
            }
            $output .= "</div>";
        }

        $output .= "</div>"; //swiper-wrapper
        $output .= "<div class='text-center swiper-controls-wrap'>";
        ob_start();
        get_template_part('components/slider-controls');
        $slider_controls = ob_get_clean();
        $output .= $slider_controls;
        $output .= "</div>"; //text-center
        $output .= "</div>"; //swiper
    }
    return $output;
}


//Exclude post type from WordPress link builder
function exclude_post_type_from_link_builder($query)
{
    $cpts_to_remove = [
        'global_content_block',
        'main-popup',
        'testimonial',
        'faq',
        'menu',
        'room',
    ];
    foreach ($cpts_to_remove as $cpt) {
        $key = array_search($cpt, $query['post_type']);
        if ($key) {
            unset($query['post_type'][$key]);
        }
    }
    return $query;
}
add_filter('wp_link_query_args', 'exclude_post_type_from_link_builder');
