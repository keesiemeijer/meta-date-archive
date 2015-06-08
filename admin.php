<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'save_post', 'meta_date_archive_save_post' );

/**
 * Adds the start date meta if only end date meta is provided when editing or publishing a post.
 *
 * @since 1.0
 * @return void
 */
function meta_date_archive_save_post( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}

	if ( !current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}

	$start_key = apply_filters( 'meta_date_archive_start', 'meta_start_date' );
	$end_key   = apply_filters( 'meta_date_archive_end',   'meta_end_date' );

	$post_type = ( isset( $_POST['post_type'] ) ) ?  $_POST['post_type'] : 'post';
	$end_date  = get_post_meta( $post_id, $end_key, true );
	$start_date  = get_post_meta( $post_id, $start_key, true );

	if( empty( $end_date ) && empty( $start_date ) ){
		// both are empty
		return;
	}

	if( !empty( $end_date ) && !empty( $start_date ) ) {
		// both are not empty
		return;
	}

	// one of them is empty
	if ( !empty( $end_date ) &&  $post_type ) {
		if ( !get_post_meta( $post_id, $start_key, true ) ) {
			update_post_meta( $post_id, $start_key, $end_date );
		}
	}

	if ( !empty( $start_date ) &&  $post_type ) {
		if ( !get_post_meta( $post_id, $end_key, true ) ) {
			update_post_meta( $post_id, $end_key, $start_date );
		}
	}
}