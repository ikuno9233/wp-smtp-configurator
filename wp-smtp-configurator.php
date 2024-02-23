<?php
/*
Plugin Name: WP SMTP Configurator
Description: WordPress SMTP configuration plugin
Version: 1.0.0
Plugin URI: https://github.com/ikuno9233/wp-smtp-configurator/
Author: Fumihiko Takayama
Author URI: https://github.com/ikuno9233/
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-wp-smtp-configurator.php';

$wp_smtp_configurator = \WP_SMTP_Configurator\WP_SMTP_Configurator::instance();

register_activation_hook( __FILE__, array( $wp_smtp_configurator::class, 'activation' ) );
register_deactivation_hook( __FILE__, array( $wp_smtp_configurator::class, 'deactivation' ) );
register_uninstall_hook( __FILE__, array( $wp_smtp_configurator::class, 'uninstall' ) );
