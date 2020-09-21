<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly
/**
 * Configures the site link system for the network reporting
 */

// Adds the type of network connection to the site link system
add_filter( 'site_link_type', 'dt_webform_site_link_type', 10, 1 );
function dt_webform_site_link_type( $type ) {
    $type['network_dashboard_both'] = __('Network Dashboard Transfer Both Ways');
    $type['network_dashboard_receiving'] = __( 'Network Dashboard Receiving Only' );
    $type['network_dashboard_sending'] = __('Network Dashboard Sending Only');
    return $type;
}

// Add the specific capabilities needed for the site to site linking.
add_filter( 'site_link_type_capabilities', 'dt_webform_site_link_capabilities', 10, 1 );
function dt_webform_site_link_capabilities( $args ) {
    if ( 'network_dashboard_receiving' === $args['connection_type'] || 'network_dashboard_both' === $args['connection_type'] || 'network_dashboard_sending' === $args['connection_type'] ) {
        $args['capabilities'][] = 'network_dashboard_both';
        $args['capabilities'][] = 'network_dashboard_receiving';
        $args['capabilities'][] = 'network_dashboard_sending';
    }
    return $args;
}