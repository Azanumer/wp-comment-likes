<?php
/**
 * Plugin Name:       Comment Likes
 * Description:       Adds an AJAX like button to comments, with one like per visitor (cookie + IP-hash guard).
 * Version:           1.0.0
 * Author:            Azan Umer
 * License:           GPL-2.0+
 * Text Domain:       wcl
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WCL_META_COUNT', '_wcl_likes' );
define( 'WCL_META_VOTERS', '_wcl_voters' );
define( 'WCL_COOKIE', 'wcl_liked' );

/**
 * Get the like count for a comment.
 *
 * @param int $comment_id Comment ID.
 * @return int
 */
function wcl_get_count( $comment_id ) {
    return (int) get_comment_meta( $comment_id, WCL_META_COUNT, true );
}

/**
 * Hash used to recognise a repeat voter without storing raw IPs.
 *
 * @return string
 */
function wcl_voter_hash() {
    $ip = '';
    if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
    }
    // Salted with the site URL so hashes are useless if exported elsewhere.
    return hash( 'sha256', $ip . '|' . home_url() );
}

/**
 * Comment IDs this visitor has already liked, from the cookie.
 *
 * @return array
 */
function wcl_liked_ids() {
    if ( empty( $_COOKIE[ WCL_COOKIE ] ) ) {
        return array();
    }
    $raw = sanitize_text_field( wp_unslash( $_COOKIE[ WCL_COOKIE ] ) );
    $ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );
    return array_values( array_unique( $ids ) );
}

/**
 * Append the like button to approved comment text on the front end.
 *
 * @param string $text Comment text.
 * @return string
 */
function wcl_append_button( $text ) {
    if ( is_admin() || ! is_singular() ) {
        return $text;
    }

    $comment = get_comment();
    if ( ! $comment || (int) $comment->comment_approved !== 1 ) {
        return $text;
    }

    $comment_id = (int) $comment->comment_ID;
    $liked      = in_array( $comment_id, wcl_liked_ids(), true );

    $button = sprintf(
        '<button type="button" class="wcl-like-button%s" data-comment-id="%d" aria-pressed="%s">%s <span class="wcl-count">%d</span></button>',
        $liked ? ' wcl-liked' : '',
        $comment_id,
        $liked ? 'true' : 'false',
        $liked ? '&#10004; Liked' : '&#9825; Like',
        wcl_get_count( $comment_id )
    );

    return $text . '<p class="wcl-like-wrap">' . $button . '</p>';
}
add_filter( 'comment_text', 'wcl_append_button' );

/**
 * Enqueue the front-end script.
 */
function wcl_enqueue_script() {
    if ( ! is_singular() || ! comments_open() ) {
        return;
    }

    wp_enqueue_script(
        'wcl-like',
        plugins_url( 'comment-likes.js', __FILE__ ),
        array(),
        '1.0.0',
        true
    );
    wp_localize_script( 'wcl-like', 'wclData', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'wcl_like' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'wcl_enqueue_script' );

/**
 * AJAX handler: register one like.
 */
function wcl_ajax_like() {
    check_ajax_referer( 'wcl_like', 'nonce' );

    $comment_id = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0;
    $comment    = get_comment( $comment_id );

    if ( ! $comment || (int) $comment->comment_approved !== 1 ) {
        wp_send_json_error( array( 'message' => 'Comment not found.' ), 404 );
    }

    if ( in_array( $comment_id, wcl_liked_ids(), true ) ) {
        wp_send_json_error( array( 'message' => 'Already liked.' ), 409 );
    }

    $voters = get_comment_meta( $comment_id, WCL_META_VOTERS, true );
    if ( ! is_array( $voters ) ) {
        $voters = array();
    }

    $hash = wcl_voter_hash();
    if ( in_array( $hash, $voters, true ) ) {
        wp_send_json_error( array( 'message' => 'Already liked.' ), 409 );
    }

    // Cap the voter list so commentmeta never grows unboundedly.
    $voters[] = $hash;
    $voters   = array_slice( array_unique( $voters ), -5000 );
    update_comment_meta( $comment_id, WCL_META_VOTERS, $voters );

    $count = wcl_get_count( $comment_id ) + 1;
    update_comment_meta( $comment_id, WCL_META_COUNT, $count );

    // Remember in a 1-year cookie so the button state survives page loads.
    $liked   = wcl_liked_ids();
    $liked[] = $comment_id;
    setcookie( WCL_COOKIE, implode( ',', array_unique( $liked ) ), time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );

    wp_send_json_success( array( 'count' => $count ) );
}
add_action( 'wp_ajax_wcl_like', 'wcl_ajax_like' );
add_action( 'wp_ajax_nopriv_wcl_like', 'wcl_ajax_like' );
