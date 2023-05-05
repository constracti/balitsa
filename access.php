<?php

if ( !defined( 'ABSPATH' ) )
	exit;

/*

access : int[]

*/

final class Balitsa_Access {

	private $post;
	private $access;

	public function __construct( WP_Post $post ) {
		$this->post = $post;
		$this->load();
	}

	private function load(): void {
		$s = get_post_meta( $this->post->ID, 'balitsa_access', TRUE );
		if ( preg_match_all( '#(\d+)#', $s, $m ) === FALSE )
			$this->access = [];
		else
			$this->access = array_map( 'intval', $m[0] );
	}

	private function save(): void {
		$s = '';
		foreach ( $this->access as $i )
			$s .= sprintf( '#%d#', $i );
		if ( $s !== '' )
			update_post_meta( $this->post->ID, 'balitsa_access', $s );
		else
			delete_post_meta( $this->post->ID, 'balitsa_access' );
	}

	// functions

	public function is_empty(): bool {
		return empty( $this->access );
	}

	public function get( WP_User|int $user ): bool {
		if ( is_a( $user, 'WP_User' ) )
			$user = $user->ID;
		$key = array_search( $user, $this->access, TRUE );
		return $key !== FALSE;
	}

	private function set( WP_User|int $user, bool $accesses ): void {
		if ( is_a( $user, 'WP_User' ) )
			$user = $user->ID;
		$key = array_search( $user, $this->access, TRUE );
		if ( $accesses ) {
			if ( $key !== FALSE )
				return;
			$this->access[] = $user;
		} else {
			if ( $key === FALSE )
				return;
			unset( $this->access[$key] );
		}
		$this->save();
	}

	// metabox

	public function metabox_echo(): void {
		echo $this->metabox();
	}

	public function metabox(): string {
		$users = get_users( [
			'orderby' => 'display_name',
			'order' => 'ASC',
		] );
		$html = '<div class="balitsa-home balitsa-root balitsa-flex-col" style="margin: 0px -4px 0px -14px;">' . "\n";
		$html .= '<div class="balitsa-flex-row balitsa-flex-justify-between balitsa-flex-align-center">' . "\n";
		$html .= $this->metabox_refresh_link();
		$html .= '<span class="balitsa-spinner spinner balitsa-leaf" data-balitsa-spinner-toggle="is-active"></span>' . "\n";
		$html .= '</div>' . "\n";
		$html .= $this->metabox_table( $users );
		$html .= '<div class="balitsa-flex-row balitsa-flex-justify-between balitsa-flex-align-center">' . "\n";
		$html .= $this->metabox_accept_link();
		$html .= '</div>' . "\n";
		$html .= $this->metabox_form( $users );
		return $html;
	}

	private function metabox_table( array $users ): string {
		$html = '<div class="balitsa-leaf">' . "\n";
		$html .= '<table class="fixed widefat striped">' . "\n";
		$html .= '<thead>' . "\n";
		$html .= '<tr>' . "\n";
		$html .= sprintf( '<th class="column-primary">%s</th>', esc_html__( 'Display Name', 'balitsa' ) ) . "\n";
		$html .= sprintf( '<th>%s</th>', esc_html__( 'Action', 'balitsa' ) ) . "\n";
		$html .= '</tr>' . "\n";
		$html .= '</thead>' . "\n";
		$html .= '<tbody>' . "\n";
		foreach ( $users as $user ) {
			if ( !$this->get( $user ) )
				continue;
			$html .= '<tr>' . "\n";
			$html .= sprintf( '<td class="column-primary">%s</td>', esc_html( $user->display_name ) ) . "\n";
			$html .= '<td>' . "\n";
			$html .= $this->metabox_reject_link( $user );
			$html .= '</td>' . "\n";
			$html .= '</tr>' . "\n";
		}
		$html .= '</tbody>' . "\n";
		$html .= '</table>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	private function metabox_form( array $users ): string {
		$html = '<div class="balitsa-form balitsa-form-access balitsa-flex-col" style="display: none;">' . "\n";
		$html .= '<label class="balitsa-flex-col balitsa-leaf">' . "\n";
		$html .= sprintf( '<span>%s</span>', esc_html__( 'User', 'balitsa' ) ) . "\n";
		$html .= '<select class="balitsa-field" data-balitsa-name="user">' . "\n";
		$html .= '<option value=""></option>' . "\n";
		foreach ( $users as $user ) {
			if ( $this->get( $user ) )
				continue;
			$html .= sprintf( '<option value="%d">%s</option>', esc_attr( $user->ID ), esc_html( $user->display_name ) ) . "\n";
		}
		$html .= '</select>' . "\n";
		$html .= '</label>' . "\n";
		$html .= '<div class="balitsa-flex-row balitsa-flex-justify-between balitsa-flex-align-center">' . "\n";
		$html .= sprintf( '<a href="" class="balitsa-link balitsa-submit button button-primary balitsa-leaf">%s</a>', esc_html__( 'Submit', 'balitsa' ) ) . "\n";
		$html .= sprintf( '<a href="" class="balitsa-cancel button balitsa-leaf">%s</a>', esc_html__( 'Cancel', 'balitsa' ) ) . "\n";
		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	private function metabox_refresh_link(): string {
		return sprintf( '<a%s>%s</a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'metabox_refresh' ),
			'class' => 'balitsa-link button balitsa-leaf',
		] ), esc_html__( 'Refresh', 'balitsa' ) ) . "\n";
	}

	private function metabox_accept_link(): string {
		return sprintf( '<a%s>%s</a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'metabox_accept' ),
			'class' => 'balitsa-insert button balitsa-leaf',
			'data-balitsa-form' => '.balitsa-form-access',
		] ), esc_html__( 'Accept', 'balitsa' ) ) . "\n";
	}

	private function metabox_reject_link( WP_User $user ): string {
		return sprintf( '<a%s>%s</a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'metabox_reject', [
				'user' => $user->ID,
			] ),
			'class' => 'balitsa-link',
		] ), esc_html__( 'Reject', 'balitsa' ) ) . "\n";
	}

	// ajax

	private function ajax_href( string $task, array $args = [] ): string {
		return add_query_arg( array_merge( [
			'action' => 'balitsa_access',
			'task' => $task,
			'post' => $this->post->ID,
			'nonce' => $this->nonce_create( $task ),
		], $args ), admin_url( 'admin-ajax.php' ) );
	}

	private function nonce_create( string $task ): string {
		return Balitsa::nonce_create( 'balitsa_access', $task, $this->post->ID );
	}

	private function nonce_verify( string $task ): void {
		Balitsa::nonce_verify( 'balitsa_access', $task, $this->post->ID );
	}

	public function ajax( string $task ): void {
		$this->nonce_verify( $task );
		switch ( $task ) {
			case 'metabox_refresh':
				Balitsa::success( $this->metabox() );
			case 'metabox_accept':
				$user = Balitsa::request_user( 'post', 'user' );
				$this->set( $user, TRUE );
				Balitsa::success( $this->metabox() );
			case 'metabox_reject':
				$user = Balitsa::request_user( 'get', 'user' );
				$this->set( $user, FALSE );
				Balitsa::success( $this->metabox() );
			default:
				exit( 'task' );
		}
	}

	// callabacks

	public static function pre_get_posts( WP_Query $query ): void {
		if ( is_admin() )
			return;
		if ( current_user_can( 'edit_pages' ) )
			return;
		if ( $query->is_page() )
			return;
		$user_id = get_current_user_id();
		$query->set( 'meta_query', [
			'relation' => 'OR',
			[
				'key' => 'balitsa_access',
				'compare' => 'NOT EXISTS',
			],
			[
				'key' => 'balitsa_access',
				'compare' => 'LIKE',
				'value' => sprintf( '#%d#', $user_id ),
			],
		] );
	}

	public static function get_adjacent_post_join( string $join ): string {
		if ( is_admin() )
			return $join;
		if ( current_user_can( 'edit_pages' ) )
			return $join;
		global $wpdb;
		$join .= " LEFT JOIN $wpdb->postmeta AS pm ON pm.post_id = p.ID AND pm.meta_key = 'balitsa_access'";
		return $join;
	}

	public static function get_adjacent_post_where( string $where ): string {
		if ( is_admin() )
			return $where;
		if ( current_user_can( 'edit_pages' ) )
			return $where;
		$user_id = get_current_user_id();
		$value = sprintf( '#%d#', $user_id );
		$where .= " AND (pm.meta_value IS NULL OR pm.meta_value LIKE '$value')";
		return $where;
	}
}

add_action( 'add_meta_boxes', function( string $post_type, WP_Post $post ): void {
	if ( $post_type !== 'post' )
		return;
	if ( !current_user_can( 'edit_post', $post->ID ) )
		return;
	$access = new Balitsa_Access( $post );
	add_meta_box( 'balitsa_access', __( 'Invite Users', 'balitsa' ), [ $access, 'metabox_echo' ], NULL, 'side' );
}, 10, 2 );

add_action( 'admin_enqueue_scripts', function( string $hook_suffix ): void {
	if ( !in_array( $hook_suffix, [ 'post.php', 'post-new.php', ], TRUE ) )
		return;
	wp_enqueue_style( 'balitsa-flex', Balitsa::url( 'flex.css' ), [], Balitsa::version() );
	wp_enqueue_style( 'balitsa-tree', Balitsa::url( 'tree.css' ), [], Balitsa::version() );
	wp_enqueue_script( 'balitsa-script', Balitsa::url( 'script.js' ), [ 'jquery' ], Balitsa::version() );
} );

add_action( 'wp_ajax_' . 'balitsa_access', function(): void {
	if ( $_SERVER['REQUEST_METHOD'] !== 'POST' )
		exit( 'method' );
	$post = Balitsa::request_post( 'get', 'post' );
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	$task = Balitsa::request_str( 'get', 'task' );
	$access = new Balitsa_Access( $post );
	$access->ajax( $task );
} );

add_action( 'pre_get_posts', [ 'Balitsa_Access', 'pre_get_posts' ] );

add_filter( 'get_previous_post_join', [ 'Balitsa_Access', 'get_adjacent_post_join' ] );
add_filter( 'get_next_post_join', [ 'Balitsa_Access', 'get_adjacent_post_join' ] );

add_filter( 'get_previous_post_where', [ 'Balitsa_Access', 'get_adjacent_post_where' ] );
add_filter( 'get_next_post_where', [ 'Balitsa_Access', 'get_adjacent_post_where' ] );
