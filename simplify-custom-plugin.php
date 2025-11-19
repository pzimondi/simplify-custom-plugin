<?php
/**
 * Plugin Name: Simplify Custom Plugin
 * Plugin URI:  https://sblik.com
 * Description: Internship plugin to demonstrate adding custom scripts, styles, and functionality to a WordPress site.
 * Version:     1.0
 * Author:      Pastor Zimondi
 * Author URI:  https://sblik.com
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct file access
}

// Enqueue custom styles and scripts
function simplify_custom_enqueue_assets() {
    // CSS
    wp_enqueue_style(
        'simplify-custom-style',
        plugin_dir_url(__FILE__) . 'assets/style.css',
        array(),
        '1.0',
        'all'
    );

    // JavaScript
    wp_enqueue_script(
        'simplify-custom-script',
        plugin_dir_url(__FILE__) . 'assets/script.js',
        array('jquery'),
        '1.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'simplify_custom_enqueue_assets');




add_action('gform_after_submission', 'my_custom_plugin_gravity_webhook', 10, 2);

function my_custom_plugin_gravity_webhook($entry, $form) {
    $target_form_id = 2;
    if ($form['id'] != $target_form_id) {
        return;
    }

    // Map all fields with their actual IDs as in WordPress
    $data = array(
        'name'                      => rgar($entry, '1'),   // Your Name (First + Last)
        'name_first'                => rgar($entry, '1.3'), // First name only
        'name_last'                 => rgar($entry, '1.6'), // Last name only
        'email'                     => rgar($entry, '2'),   // Your Email Address
        'message'                   => rgar($entry, '3'),   // Paragraph text/message
        'address_full'              => rgar($entry, '4'),   // Full address
        'address_street'            => rgar($entry, '4.1'), // Street Address
        'address_line2'             => rgar($entry, '4.2'), // Address Line 2
        'address_city'              => rgar($entry, '4.3'), // City
        'address_state'             => rgar($entry, '4.4'), // State/Province
        'address_zip'               => rgar($entry, '4.5'), // ZIP/Postal Code
        'address_country'           => rgar($entry, '4.6'), // Country
        'phone'                     => rgar($entry, '5'),   // Phone number
        'preferred_contact_method'  => rgar($entry, '11'),  // Dropdown: Email or Phone
        'best_time_to_call'         => rgar($entry, '12'),  // Dropdown: Best time to call
    );

    // Convert to JSON
    $body = wp_json_encode($data);

    // Send to Webhook.site
    $response = wp_remote_post('https://webhook.site/51f59ca2-afef-4882-9c28-5a89ea646adf', array(
        'method'    => 'POST',
        'headers'   => array('Content-Type' => 'application/json; charset=utf-8'),
        'body'      => $body,
        'timeout'   => 15,
    ));

    // Logging for debugging
    if (is_wp_error($response)) {
        error_log('Gravity Form webhook error: ' . $response->get_error_message());
    } else {
        error_log('Gravity Form webhook response: ' . print_r($response, true));
    }
}