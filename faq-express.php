<?php
/*
Plugin Name: FAQ Express
Description: Manage FAQs and display them on pages or posts with optional JSON-LD schema.
Version: 1.0
Author: Matt Burdett
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Include the main plugin class
require_once plugin_dir_path(__FILE__) . 'includes/class-faq-express.php';

// Initialize plugin
function faq_express_init() {
    $faq_express = new FAQ_express();
}
add_action('plugins_loaded', 'faq_express_init');
