<?php
$popups = [];
$popup_cpt_name = 'main-popup';

function generate_popup_trigger_shortcode_in_admin_panel($field)
{
    $popup_id = get_the_id();
    $field['readonly'] = 1;
    $field['wrapper']['class'] = 'popup-trigger-shortcode';

    // Generate the shortcode for the popup trigger
    $field['value'] = '[popup_trigger id="' . esc_attr($popup_id) . '" label="Learn More"]';

    // If the post is not published, add an instruction to publish it
    if (get_post_status($popup_id) !== 'publish') {
        $field['instructions'] .= '<span style="color: red; margin: .5em 0 .75em;">To use the shortcode, please publish this popup first</span>';
    }

    return $field;
}

// Add the filter to modify the ACF field named 'popup_trigger_shortcode'.
add_filter('acf/prepare_field/name=popup_trigger_shortcode', 'generate_popup_trigger_shortcode_in_admin_panel');

function is_core_popup_exists($popup_id)
{
    global $popup_cpt_name;
    $popup = get_post($popup_id);

    return $popup && $popup->post_type === $popup_cpt_name;
}

add_shortcode('popup_trigger', 'popup_trigger_shortcode');
function popup_trigger_shortcode($atts)
{
    global $popups;

    $label_text = "Learn More";
    $atts = shortcode_atts(
        [
            'id' => null,
            'site_id' => get_current_blog_id(),
            'label' => $label_text,
            'type' => 'button',
            'class' => null,
        ],
        $atts,
        'popup_trigger'
    );
    extract($atts);

    $content = $label ?: $label_text;

    $id = is_numeric($id) ? intval($id) : null;
    $site_id = is_numeric($site_id) ? intval($site_id) : null;

    if (!$id || !$site_id || !get_blog_details($site_id)) return;

    switch_to_blog($site_id);
    if (!is_core_popup_exists($id)) {
        restore_current_blog();
        return;
    }

    if (!in_array(['id' => $id, 'site_id' => $site_id], $popups)) {
        $popups[] = [
            'id' => $id,
            'site_id' => $site_id
        ];
    }

    restore_current_blog();

    $front_end_id = "$id-$site_id";

    $class = $type === 'button' && $class === null ? "button" : $class;
    $class_attr = $class ? " class='" . esc_attr($class) . "'" : '';
    return '<a href="#"' . $class_attr . ' data-popup-trigger="' . esc_attr($front_end_id) . '">' . esc_html($content) . '</a>';
};

//Register JS and CSS 
function register_core_popup_style_and_script()
{
    wp_register_style('popup', get_core_enqueue_path('popup.css'), [], null);
    wp_register_script('popup', get_core_enqueue_path('popup.js'), [], null, ['in_footer' => true, 'strategy' => 'defer']);
}
add_action('wp_enqueue_scripts', 'register_core_popup_style_and_script');

//If current object has popups display them in footer
function display_core_popups()
{
    global $popups, $popup_cpt_name;
    if (empty($popups)) return; // Ensure there are popups to display

    wp_enqueue_style('popup');
    wp_enqueue_script('popup');

    foreach ($popups as $popup) {

        if (!is_array($popup) || !isset($popup['id']) || !isset($popup['site_id'])) continue;

        $id = $popup['id'];
        $site_id = $popup['site_id'];
        $front_end_id = "$id-$site_id";
        switch_to_blog($site_id);

        if (is_core_popup_exists($id) && get_post_status($id) === 'publish') {
            $title = get_field('popup_title', $id);
            $content = apply_filters('the_content', get_the_content(false, false, $id));
            $selector = "[data-popup-trigger='$front_end_id']";

            $args = [
                'id' => "$popup_cpt_name-$front_end_id",
                'selector' => $selector,
                'content' => $content,
                'title' => $title
            ];
            get_template_part('components/popup', null, $args);
        }
        restore_current_blog();
    }
}
add_action('wp_footer', 'display_core_popups');
