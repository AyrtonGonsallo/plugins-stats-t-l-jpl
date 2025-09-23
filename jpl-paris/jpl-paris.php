<?php
/**
 * Plugin Name: BetonJpl - Module de pronostics
 * Description: Gestion des joueurs, points et ligues pour la JPL.
 * Version: 0.1
 * Author: Gonsallo Ayrton
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define('JPL_PARIS_PATH', plugin_dir_path(__FILE__));
define('JPL_PARIS_URL', plugin_dir_url(__FILE__));

// Inclure nos classes
require_once JPL_PARIS_PATH . 'includes/class-jpl-acf.php';
require_once JPL_PARIS_PATH . 'includes/class-jpl-shortcodes.php';
require_once JPL_PARIS_PATH . 'includes/class-jpl-roles.php';
require_once JPL_PARIS_PATH . 'includes/class-jpl-auth.php';
require_once JPL_PARIS_PATH . 'includes/class-jpl-ligues.php';
require_once JPL_PARIS_PATH . 'includes/class-jpl-paris.php';




// Hooks
add_action('init', ['JPL_Roles', 'register_roles']);
add_action('acf/init', ['JPL_ACF', 'register_user_fields']);
add_action('init', ['JPL_Shortcodes', 'register_shortcodes']);
add_action('init', ['JPL_Auth', 'init']);
add_action('init', ['JPL_Ligues', 'init']);
add_action('init', ['JPL_Paris', 'init']);
add_action('init', ['JPL_Paris', 'cron_update_paris']); 
add_action('init', ['JPL_Paris', 'cron_update_score_total_series']); 
add_action('init', ['JPL_Paris', 'cron_update_and_save_stats_semaine']);