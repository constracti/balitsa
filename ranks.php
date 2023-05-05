<?php

if ( !defined( 'ABSPATH' ) )
	exit;

/*

ranks : int[string]

*/

final class Balitsa_Ranks {

	public const MIN = 1;
	public const MAX = 5;

	private $user;
	private $ranks;

	public function __construct( WP_User $user ) {
		$this->user = $user;
		$this->load();
	}

	private function load(): void {
		$this->ranks = get_user_meta( $this->user->ID, 'balitsa_ranks', TRUE );
		if ( $this->ranks === '' )
			$this->ranks = [];
	}

	private function save(): void {
		if ( !empty( $this->ranks ) )
			update_user_meta( $this->user->ID, 'balitsa_ranks', $this->ranks );
		else
			delete_user_meta( $this->user->ID, 'balitsa_ranks' );
	}

	// functions

	public function get( string $sport ): int|null {
		if ( !isset( $this->ranks[$sport] ) )
			return NULL;
		return $this->ranks[$sport];
	}

	private function set( string $sport, int|null $rank ): void {
		if ( !is_null( $rank ) )
			$this->ranks[$sport] = $rank;
		else
			unset( $this->ranks[$sport] );
		$this->save();
	}

	// column

	public function column(): string {
		$sports = Balitsa_Sports::select();
		$html = '<div class="balitsa-home">' . "\n";
		foreach ( $sports as $sport_key => $sport ) {
			$html .= '<p>' . "\n";
			$html .= sprintf( '<span class="%s" title="%s"></span>', esc_attr( $sport['icon'] ), esc_attr( $sport['name'] ) ) . "\n";
			$rank = $this->get( $sport_key );
			for ( $r = self::MIN; $r <= self::MAX; $r++ )
				$html .= $this->column_update_link( $sport_key, $r, !is_null( $rank ) && $rank >= $r );
			$html .= '</p>' . "\n";
		}
		$html .= '<p>' . "\n";
		$html .= $this->column_refresh_link();
		$html .= '<span class="balitsa-spinner spinner" data-balitsa-spinner-toggle="is-active"></span>';
		$html .= '</p>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	private function column_refresh_link(): string {
		return sprintf( '<a%s>%s</a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'column_refresh' ),
			'class' => 'balitsa-link',
		] ), esc_html__( 'Refresh', 'balitsa' ) ) . "\n";
	}

	private function column_update_link( string $sport, int $rank, bool $active ): string {
		return sprintf( '<a%s><span class="%s"></span></a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'column_update', [
				'sport' => $sport,
				'rank' => $rank,
			] ),
			'class' => 'balitsa-link',
		] ), esc_attr( $active ? 'fas fa-fw fa-star' : 'far fa-fw fa-star' ) ) . "\n";
	}

	// ajax

	private function ajax_href( string $task, array $args = [] ): string {
		return add_query_arg( array_merge( [
			'action' => 'balitsa_ranks',
			'task' => $task,
			'user' => $this->user->ID,
			'nonce' => $this->nonce_create( $task ),
		], $args ), admin_url( 'admin-ajax.php' ) );
	}

	private function nonce_create( string $task ): string {
		return Balitsa::nonce_create( 'balitsa_ranks', $task, $this->user->ID );
	}

	private function nonce_verify( string $task ): void {
		Balitsa::nonce_verify( 'balitsa_ranks', $task, $this->user->ID );
	}

	public function ajax( string $task ): void {
		$this->nonce_verify( $task );
		switch ( $task ) {
			case 'column_refresh':
				Balitsa::success( $this->column() );
			case 'column_update':
				$sport = Balitsa::request_slug( 'get', 'sport' );
				if ( !Balitsa_Sports::exists( $sport ) )
					exit( 'sport' );
				$rank = $this->get( $sport );
				$r = Balitsa::request_int( 'get', 'rank' );
				if ( $r < self::MIN || $r > self::MAX )
					exit( 'rank' );
				if ( $r === $rank )
					$r = NULL;
				$this->set( $sport, $r );
				Balitsa::success( $this->column() );
			default:
				exit( 'task' );
		}
	}
}

add_filter( 'manage_users_columns', function( array $columns ): array {
	$columns['balitsa_rank'] = esc_html__( 'Rank', 'balitsa' );
	return $columns;
} );

add_action( 'manage_users_custom_column', function( string $output, string $column_name, int $user_id ): string {
	if ( $column_name !== 'balitsa_rank' )
		return $output;
	$user = get_user_by( 'ID', $user_id );
	assert( $user !== FALSE );
	$ranks = new Balitsa_Ranks( $user );
	return $output . $ranks->column();
}, 10, 3 );

add_action( 'admin_enqueue_scripts', function( string $hook_suffix ): void {
	if ( $hook_suffix !== 'users.php' )
		return;
	wp_enqueue_script( 'balitsa-script', Balitsa::url( 'script.js' ), [ 'jquery' ], Balitsa::version() );
} );

add_action( 'wp_ajax_' . 'balitsa_ranks', function(): void {
	if ( $_SERVER['REQUEST_METHOD'] !== 'POST' )
		exit( 'method' );
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	$task = Balitsa::request_str( 'get', 'task' );
	$user = Balitsa::request_user( 'get', 'user' );
	$ranks = new Balitsa_Ranks( $user );
	$ranks->ajax( $task );
} );
