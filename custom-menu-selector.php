<?php
/*
Plugin Name: Custom Primary Menu Selector
Description: Allow users to set a custom primary menu for each page.
Version: 1.0
Author: omibeaver-Dong
License: GPL-2.0+
*/

// Add meta boxes for menu selection on the post edit screen
add_action('add_meta_boxes', 'custom_menu_positions_meta_boxes');

function custom_menu_positions_meta_boxes()
{
    // Define menu positions
    $menu_positions = array(
        'header_menu' => 'Header Menu'
    );

    foreach ($menu_positions as $position_key => $position_label) {
        add_meta_box(
            'custom-menu-selector-' . $position_key,
            'Custom Menu Selector - ' . $position_label,
            'custom_menu_selector_callback',
            'page',
            'side',
            'high',
            array('position' => $position_key)
        );
    }
}

// Callback function to display meta box content
function custom_menu_selector_callback($post, $metabox)
{
    $position = $metabox['args']['position'];
    $selected_menu = get_post_meta($post->ID, '_custom_menu_' . $position, true);
    $menus = get_terms('nav_menu', array('hide_empty' => false));

    echo '<label for="menu-selector-' . $position . '">Select Menu for ' . esc_html($position) . ':</label>';
    echo '<select id="menu-selector-' . $position . '" name="menu-locations[' . $position . ']">';
    echo '<option value="">Default (' . esc_html($position) . ') Menu</option>';

    foreach ($menus as $menu) {
        echo '<option value="' . esc_attr($menu->term_id) . '" ' . selected($selected_menu, $menu->term_id, false) . '>' . esc_html($menu->name) . '</option>';
    }

    echo '</select>';
    wp_nonce_field('custom_menu_positions_nonce', 'custom_menu_positions_nonce');
}

// Save custom menu selections
add_action('save_post', 'save_custom_menu_positions');

function save_custom_menu_positions($post_id)
{
    // Check if nonce is set
    if (!isset($_POST['custom_menu_positions_nonce']) || !wp_verify_nonce($_POST['custom_menu_positions_nonce'], 'custom_menu_positions_nonce')) {
        return;
    }

    // Define menu positions
    $menu_positions = array('header_menu');

    foreach ($menu_positions as $position) {
        if (array_key_exists($position, $_POST['menu-locations'])) {
            update_post_meta($post_id, '_custom_menu_' . $position, sanitize_text_field($_POST['menu-locations'][$position]));
        }
    }
}


// Hook to update primary menu before template is loaded
add_action('template_redirect', 'update_primary_menu');

function update_primary_menu()
{
    // Get the ID of the current post
    $post_id = get_queried_object_id();

    // Get the selection for the Header Menu
    $header_menu = get_post_meta($post_id, '_custom_menu_header_menu', true);

    // Output the custom menu selection
    // echo 'Header Menu ID: ' . esc_html($header_menu) . '<br>';

    // Get the menu location for the primary navigation
    $primary_menu_location = 'primary'; // Identifier for the primary navigation location

    // Get the menu locations and their corresponding menu IDs
    $menu_locations = get_nav_menu_locations();

    if (isset($menu_locations[$primary_menu_location])) {

        // Get the ID of the primary navigation menu
        $primary_menu_id = $menu_locations[$primary_menu_location];
        $selected_menu_id = $header_menu ? $header_menu : $primary_menu_id;

        // Update theme settings, setting the new menu ID for the primary menu location
        set_theme_mod('nav_menu_locations', array('primary' => $selected_menu_id));

        // Output the ID of the primary navigation menu
        // echo 'Primary Menu ID: ' . esc_html($primary_menu_id);
    }
}
