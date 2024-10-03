<?php
/*
Plugin Name: Multiple images tagger
Description: A plugin to select multiple images and assign metadatas(judokas, seasons,...).
Version: 1.0
Author: Gonsallo Ayrton
*/

// Enqueue scripts and styles
function ims_enqueue_scripts() {
    wp_enqueue_script('ims-js', plugin_dir_url(__FILE__) . 'js/image-selector.js', array('jquery'), '1.0', true);
    wp_enqueue_style('ims-css', plugin_dir_url(__FILE__) . 'css/image-selector.css');
}
add_action('admin_enqueue_scripts', 'ims_enqueue_scripts');

// Create a menu item
function ims_create_menu() {
    add_menu_page('Multiple images tagger', 'Multiple images tagger', 'manage_options', 'image-multi-selector', 'ims_render_selector_page','',50);
}
add_action('admin_menu', 'ims_create_menu');

// Render the selection page
function ims_render_selector_page() {
    include plugin_dir_path(__FILE__) . 'views/image-selector-view.php';
}