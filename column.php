<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_filter( 'manage_users_columns', function( array $columns ): array {
	$columns['balitsa_rank'] = esc_html( 'Rank' );
	return $columns;
} );

add_action( 'manage_users_custom_column', function( string $output, string $column_name, int $user_id ): string {
	if ( $column_name !== 'balitsa_rank' )
		return $output;
	return $output . balitsa_user_rank( $user_id );
}, 10, 3 );

function balitsa_user_rank( int $user ): string {
	$sports = balitsa_get_sports();
	$ranks = balitsa_get_user_ranks( $user );
	$html = '<div class="balitsa-container">' . "\n";
	foreach ( $sports as $sport_key => $sport ) {
		$html .= '<p>' . "\n";
		$html .= sprintf( '<span class="%s" title="%s" style="margin-right: 10px;"></span>', esc_attr( $sport['icon'] ), esc_attr( $sport['name'] ) ) . "\n";
		$rank = array_key_exists( $sport_key, $ranks ) ? $ranks[$sport_key] : NULL;
		for ( $r = 1; $r <= 5; $r++ ) {
			$html .= sprintf( '<a%s><span class="%s"></span></a>', balitsa_attrs( [
				'href' => add_query_arg( [
					'action' => 'balitsa_user_rank_update',
					'user' => $user,
					'sport' => $sport_key,
					'rank' => $r,
					'nonce' => balitsa_nonce_create( 'balitsa_user_rank_update', $user, $sport_key ),
				], admin_url( 'admin-ajax.php' ) ),
				'class' => 'balitsa-link',
			] ), esc_attr( !is_null( $rank ) && $rank >= $r ? 'fas fa-fw fa-star' : 'far fa-fw fa-star' ) ) . "\n";
		}
		$html .= sprintf( '<a%s>%s</a>', balitsa_attrs( [
			'href' => add_query_arg( [
				'action' => 'balitsa_user_rank_update',
				'user' => $user,
				'sport' => $sport_key,
				'rank' => NULL,
				'nonce' => balitsa_nonce_create( 'balitsa_user_rank_update', $user, $sport_key ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'balitsa-link',
			'title' => esc_attr( 'Clear' ),
			'style' => 'margin-left: 10px;',
		] ), '<span class="fas fa-fw fa-ban"></span>' ) . "\n";
		$html .= '</p>' . "\n";
	}
	$html .= '<p>' . "\n";
	$html .= sprintf( '<a%s>%s</a>', balitsa_attrs( [
		'href' => add_query_arg( [
			'action' => 'balitsa_user_rank_refresh',
			'user' => $user,
			'nonce' => balitsa_nonce_create( 'balitsa_user_rank_refresh', $user ),
		], admin_url( 'admin-ajax.php' ) ),
		'class' => 'balitsa-link',
	] ), esc_html( 'Refresh' ) );
	$html .= '<span class="balitsa-spinner spinner" data-balitsa-spinner-toggle="is-active"></span>';
	$html .= '</p>' . "\n";
	$html .= '</div>' . "\n"; 
	return $html;
}

add_action( 'admin_enqueue_scripts', function( string $hook_suffix ): void {
	if ( $hook_suffix !== 'users.php' )
		return;
	wp_enqueue_script( 'balitsa_script', BALITSA_URL . 'script.js', [ 'jquery', ], balitsa_version() );
} );

add_action( 'wp_ajax_' . 'balitsa_user_rank_refresh', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	$user = balitsa_get_user();
	balitsa_nonce_verify( 'balitsa_user_rank_refresh', $user->ID );
	balitsa_success( balitsa_user_rank( $user->ID ) );
} );

add_action( 'wp_ajax_' . 'balitsa_user_rank_update', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	$user = balitsa_get_user();
	$sports = balitsa_get_sports();
	$sport_key = balitsa_get_str( 'sport' );
	if ( !array_key_exists( $sport_key, $sports ) )
		exit( 'sport' );
	balitsa_nonce_verify( 'balitsa_user_rank_update', $user->ID, $sport_key );
	$rank = balitsa_get_int( 'rank', TRUE );
	$ranks = balitsa_get_user_ranks( $user );
	if ( is_null( $rank ) ) {
		unset( $ranks[$sport_key] );
	} elseif ( $rank < 1 || $rank > 5 ) {
		exit( 'rank' );
	} else {
		$ranks[$sport_key] = $rank;
	}
	balitsa_set_user_ranks( $user, $ranks );
	balitsa_success( balitsa_user_rank( $user->ID ) );
} );
