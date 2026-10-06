<?php
/**
 * Uninstall handler for AC Print + Email (Lightweight)
 *
 * Deletes plugin options from wp_options (and from all sites in multisite).
 *
 * @package ac-print-email
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$acpe_option_keys = [
	'acpe_enable_posts',
	'acpe_enable_pages',

	'acpe_enable_category',
	'acpe_enable_tag',

	'acpe_position',
	'acpe_providers',
	'acpe_icon_style',
	'acpe_text_color',
	'acpe_menu_bg',
	'acpe_display_mode',
];

if ( is_multisite() ) {
	$acpe_site_ids = get_sites( [ 'fields' => 'ids' ] );

	foreach ( $acpe_site_ids as $acpe_site_id ) {
		switch_to_blog( (int) $acpe_site_id );

		foreach ( $acpe_option_keys as $acpe_key ) {
			delete_option( $acpe_key );
		}

		restore_current_blog();
	}

	// In case you ever stored network-level options (you currently don't),
	// this keeps things future-proof.
	foreach ( $acpe_option_keys as $acpe_key ) {
		delete_site_option( $acpe_key );
	}
} else {
	foreach ( $acpe_option_keys as $acpe_key ) {
		delete_option( $acpe_key );
	}
}
