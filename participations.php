<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_filter( 'manage_users_columns', function( array $columns ): array {
	$columns['balitsa-participations'] = esc_html__( 'Participations', 'balitsa' );
	return $columns;
} );

add_filter( 'manage_users_custom_column', function( string $output, string $column_name, int $user_id ): string {
	if ( $column_name !== 'balitsa-participations' )
		return $output;
	$posts = get_posts( [
		'nopaging' => TRUE,
		'meta_query' => [
			'relation' => 'AND',
			[
				'key' => 'balitsa_access',
				'compare' => 'NOT EXISTS',
			],
			[
				'key' => 'balitsa_struct',
				'compare' => 'EXISTS',
			],
		],
		'fields' => 'ids',
	] );
	$participations = 0;
	foreach ( $posts as $post_id ) {
		$struct = get_post_meta( $post_id, 'balitsa_struct', TRUE );
		if ( $struct === '' )
			continue;
		$meeting_key = $struct['meeting_key'];
		if ( is_null( $meeting_key ) )
			continue;
		$meeting = $struct['meeting_list'][$meeting_key];
		$participates = FALSE;
		foreach ( $meeting['player_list'] as $player ) {
			if ( $player['user'] === $user_id )
				$participates = TRUE;
		}
		if ( $participates )
			$participations++;
	}
	return $participations;
}, 10, 3 );

add_shortcode( 'balitsa_participations', function( array|string $atts ): string {
	$atts = wp_parse_args( $atts, [
		'limit' => 0,
	] );
	$participations = [];
	$posts = get_posts( [
		'nopaging' => TRUE,
		'meta_query' => [
			'relation' => 'AND',
			[
				'key' => 'balitsa_access',
				'compare' => 'NOT EXISTS',
			],
			[
				'key' => 'balitsa_struct',
				'compare' => 'EXISTS',
			],
		],
		'fields' => 'ids',
	] );
	foreach ( $posts as $post_id ) {
		$struct = get_post_meta( $post_id, 'balitsa_struct', TRUE );
		if ( $struct === '' )
			continue;
		$meeting_key = $struct['meeting_key'];
		if ( is_null( $meeting_key ) )
			continue;
		$meeting = $struct['meeting_list'][$meeting_key];
		foreach ( $meeting['player_list'] as $player ) {
			$user_id = $player['user'];
			if ( !isset( $participations[$user_id] ) )
				$participations[$user_id] = [];
			$participations[$user_id][] = $post_id;
		}
	}
	foreach ( $participations as $user_id => $post_id_list )
		$participations[$user_id] = count( array_unique( $post_id_list ) );
	$users = get_users();
	usort( $users, function( WP_User $a, WP_User $b ) use ( $participations ): int {
		$a = $a->ID;
		$a = isset( $participations[$a] ) ? $participations[$a] : 0;
		$b = $b->ID;
		$b = isset( $participations[$b] ) ? $participations[$b] : 0;
		return $b <=> $a;
	} );
	$ret = '';
	$ret .= '<div class="balitsa-team">' . "\n";
	if ( $atts['limit'] > 0 )
		$users = array_slice( $users, 0, $atts['limit'] );
	foreach ( $users as $user ) {
		$p = isset( $participations[$user->ID] ) ? $participations[$user->ID] : 0;
		$color = new Balitsa_Color( $user );
		$ret .= sprintf( '<div class="balitsa-player" style="%s">', esc_attr( $color->css() ) ) . "\n";
		$ret .= sprintf( '<div class="balitsa-player-left"><span>%s</span></div>', esc_html( $user->display_name ) ) . "\n";
		$ret .= sprintf( '<div class="balitsa-player-right"><span>%s</span></div>', esc_html( $p ) ) . "\n";
		$ret .= '</div>' . "\n";
	}
	$ret .= '</div>' . "\n";
	return $ret;
} );
