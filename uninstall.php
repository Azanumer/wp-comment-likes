<?php
/**
 * Uninstall cleanup for Comment Likes.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE meta_key IN ('_wcl_likes', '_wcl_voters')" );
