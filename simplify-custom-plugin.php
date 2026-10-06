<?php
/**
 * Plugin Name: Simplify Custom Plugin
 * Plugin URI:  https://sblik.com
 * Description: Sends Gravity Forms submissions to an external webhook as JSON, and loads custom site styles and scripts.
 * Version:     1.1
 * Author:      Pastor Zimondi
 * Author URI:  https://sblik.com
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct file access
}

/*
 * Configuration
 *
 * Define these in wp-config.php to override the defaults, e.g.
 *   define('SIMPLIFY_WEBHOOK_URL', 'https://hooks.example.com/abc123');
 *   define('SIMPLIFY_WEBHOOK_FORM_ID', 2);
 */
if (!defined('SIMPLIFY_WEBHOOK_URL')) {
    define('SIMPLIFY_WEBHOOK_URL', get_option('simplify_webhook_url', ''));
}
if (!defined('SIMPLIFY_WEBHOOK_FORM_ID')) {
    define('SIMPLIFY_WEBHOOK_FORM_ID', (int) get_option('simplify_webhook_form_id', 2));
}

// Enqueue custom styles and scripts
function simplify_custom_enqueue_assets() {
    wp_enqueue_style(
        'simplify-custom-style',
        plugin_dir_url(__FILE__) . 'assets/style.css',
        array(),
        '1.1',
        'all'
    );

    wp_enqueue_script(
        'simplify-custom-script',
        plugin_dir_url(__FILE__) . 'assets/script.js',
        array('jquery'),
        '1.1',
        true
    );
}
add_action('wp_enqueue_scripts', 'simplify_custom_enqueue_assets');

/**
 * Forward a Gravity Forms submission to the configured webhook as JSON.
 *
 * Flow: form submitted -> entry fields mapped to named keys -> JSON encoded -> POSTed to SIMPLIFY_WEBHOOK_URL
 */
function simplify_custom_gravity_webhook($entry, $form) {
    if ((int) $form['id'] !== SIMPLIFY_WEBHOOK_FORM_ID) {
        return;
    }

    if (empty(SIMPLIFY_WEBHOOK_URL)) {
        error_log('Simplify webhook: no webhook URL configured (SIMPLIFY_WEBHOOK_URL or option simplify_webhook_url).');
        return;
    }

    // Map Gravity Forms field IDs to readable keys
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

    $response = wp_remote_post(SIMPLIFY_WEBHOOK_URL, array(
        'method'  => 'POST',
        'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
        'body'    => wp_json_encode($data),
        'timeout' => 15,
    ));

    if (is_wp_error($response)) {
        error_log('Simplify webhook error: ' . $response->get_error_message());
        return;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        error_log('Simplify webhook: unexpected HTTP ' . $code . ' from ' . SIMPLIFY_WEBHOOK_URL);
    }
}
add_action('gform_after_submission', 'simplify_custom_gravity_webhook', 10, 2);
