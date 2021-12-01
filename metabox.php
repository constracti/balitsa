<?php

if ( !defined( 'ABSPATH' ) )
	exit;

function balitsa_metabox_echo( WP_Post $post ): void {
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		$struct = NULL;
	$access = balitsa_get_access( $post );
	$sports = balitsa_get_sports();
?>
<div class="balitsa-container root4 flex-col" style="margin: 0px -4px 0px -14px;">
	<div class="flex-row flex-justify-between">
<?php
	echo sprintf( '<a%s>%s</a>', balitsa_attrs( [
		'href' => add_query_arg( [
			'action' => 'balitsa_metabox_refresh',
			'post' => $post->ID,
			'nonce' => balitsa_nonce_create( 'balitsa_metabox_refresh', $post->ID ),
		], admin_url( 'admin-ajax.php' ) ),
		'class' => 'balitsa-link button leaf',
	] ), esc_html__( 'Refresh', 'balitsa' ) ) . "\n";
?>
		<span class="balitsa-spinner spinner leaf" data-balitsa-spinner-toggle="is-active"></span>
	</div>
	<hr class="leaf" />
<?php
	if ( is_null( $struct ) ) {
		echo sprintf( '<div class="flex-row"><a%s>%s</a></div>', balitsa_attrs( [
			'href' => add_query_arg( [
				'action' => 'balitsa_metabox_construct',
				'post' => $post->ID,
				'nonce' => balitsa_nonce_create( 'balitsa_metabox_construct', $post->ID ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'balitsa-link button leaf',
		] ), esc_html__( 'Construct', 'balitsa' ) ) . "\n";
	} else {
?>
	<h3 class="leaf"><?= esc_html__( 'Access Permissions', 'balitsa' ) ?></h3>
	<div class="flex-row flex-justify-between flex-align-center">
		<span class="leaf">
			<span class="<?= esc_attr( $struct['readonly'] ? 'fas fa-fw fa-lock' : 'fas fa-fw fa-unlock' ) ?>"></span>
			<span><?= $struct['readonly'] ? esc_html__( 'Locked', 'balitsa' ) : esc_html__( 'Unlocked', 'balitsa' ) ?></span>
		</span>
		<span class="leaf">
<?php
		$attrs = balitsa_attrs( [
			'href' => add_query_arg( [
				'action' => $struct['readonly'] ? 'balitsa_metabox_unlock' : 'balitsa_metabox_lock',
				'post' => $post->ID,
				'nonce' => balitsa_nonce_create( $struct['readonly'] ? 'balitsa_metabox_unlock' : 'balitsa_metabox_lock', $post->ID ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'balitsa-link button',
		] );
?>
			<a <?= $attrs ?>><?= $struct['readonly'] ? esc_html__( 'Unlock', 'balitsa' ) : esc_html__( 'Lock', 'balitsa' ) ?></a>
		</span>
	</div>
	<hr class="leaf" />
<?php
		// user list
		$attrs = balitsa_attrs( [
			'href' => add_query_arg( [
				'action' => 'balitsa_metabox_user_accept',
				'post' => $post->ID,
				'nonce' => balitsa_nonce_create( 'balitsa_metabox_user_accept', $post->ID ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'balitsa-insert button leaf',
			'data-balitsa-form' => '.balitsa-form-access',
		] );
?>
	<div class="flex-row flex-justify-between flex-align-center">
		<h3 class="leaf"><?= esc_html__( 'Invite Users', 'balitsa' ) ?></h3>
		<a <?= $attrs ?>><?= esc_html__( 'Accept', 'balitsa' ) ?></a>
	</div>
	<div class="leaf">
		<table class="fixed widefat striped">
			<thead>
				<tr>
					<th class="column-primary"><?= esc_html__( 'Display Name', 'balitsa' ) ?></th>
					<th><?= esc_html__( 'Action', 'balitsa' ) ?></th>
				</tr>
			</thead>
			<tbody>
<?php
		$users = get_users( [
			'orderby' => 'display_name',
			'order' => 'ASC',
		] );
		foreach ( $users as $user ) {
			if ( !in_array( $user->ID, $access, TRUE ) )
				continue;
			$action = sprintf( '<a%s>%s</a>', balitsa_attrs( [
				'href' => add_query_arg( [
					'action' => 'balitsa_metabox_user_reject',
					'post' => $post->ID,
					'user' => $user->ID,
					'nonce' => balitsa_nonce_create( 'balitsa_metabox_user_reject', $post->ID, $user->ID ),	
				], admin_url( 'admin-ajax.php' ) ),
				'class' => 'balitsa-link',
			] ), esc_html__( 'Reject', 'balitsa' ) );
?>
				<tr>
					<td class="column-primary"><?= esc_html( $user->display_name ) ?></td>
					<td><?= $action ?></td>
				</tr>
<?php
		}
?>
			</tbody>
		</table>
	</div>
	<div class="balitsa-form balitsa-form-access flex-col" style="display: none;">
		<label class="flex-row flex-justify-between flex-align-center">
			<span class="leaf"><?= esc_html__( 'User', 'balitsa' ) ?></span>
			<select class="balitsa-field leaf" data-balitsa-name="user">
				<option value=""></option>
<?php
		foreach ( $users as $user ) {
			if ( in_array( $user->ID, $access, TRUE ) )
				continue;
?>
				<option value="<?= esc_attr( $user->ID ) ?>"><?= esc_html( $user->display_name ) ?></option>
<?php
		}
?>
			</select>
		</label>
		<div class="flex-row flex-justify-between flex-align-center">
			<a href="" class="balitsa-link balitsa-submit button button-primary leaf"><?= esc_html__( 'Submit', 'balitsa' ) ?></a>
			<a href="" class="balitsa-cancel button leaf"><?= esc_html__( 'Cancel', 'balitsa' ) ?></a>
		</div>
	</div>
	<hr class="leaf" />
<?php
		// meeting list
		$attrs = balitsa_attrs( [
			'href' => add_query_arg( [
				'action' => 'balitsa_metabox_meeting_insert',
				'post' => $post->ID,
				'nonce' => balitsa_nonce_create( 'balitsa_metabox_meeting_insert', $post->ID ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'balitsa-insert button leaf',
			'data-balitsa-form' => '.balitsa-form-meeting',
		] );
?>
	<div class="flex-row flex-justify-between flex-align-center">
		<h3 class="leaf"><?= esc_html__( 'Meeting List', 'balitsa' ) ?></h3>
		<a <?= $attrs ?>><?= esc_html__( 'Insert', 'balitsa' ) ?></a>
	</div>
	<div class="leaf">
		<table class="fixed widefat striped">
			<thead>
				<tr>
					<th class="column-primary"><?= esc_html__( 'Datetime', 'balitsa' ) ?></th>
					<th style="width: 50px;"><?= esc_html__( 'Sport', 'balitsa' ) ?></th>
					<th style="width: 50px;"><?= esc_html__( 'Teams', 'balitsa' ) ?></th>
				</tr>
			</thead>
			<tbody>
<?php
		$meeting_list = $struct['meeting_list'];
		uasort( $meeting_list, balitsa_sorter( 'datetime', 'sport', 'meeting_key' ) );
		foreach ( $meeting_list as $meeting_key => $meeting ) {
			$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $meeting['datetime'], wp_timezone() );
			$sport = NULL;
			if ( array_key_exists( 'sport', $meeting ) && array_key_exists( $meeting['sport'], $sports ) )
				$sport = $sports[$meeting['sport']];
			$actions = [];
			$actions['clone'] = sprintf( '<a%s>%s</a>', balitsa_attrs( [
				'href' => add_query_arg( [
					'action' => 'balitsa_metabox_meeting_insert',
					'post' => $post->ID,
					'nonce' => balitsa_nonce_create( 'balitsa_metabox_meeting_insert', $post->ID ),
				], admin_url( 'admin-ajax.php' ) ),
				'class' => 'balitsa-insert',
				'data-balitsa-form' => '.balitsa-form-meeting',
				'data-balitsa-field-datetime' => $dt->format( 'Y-m-d\TH:i' ),
				'data-balitsa-field-sport' => !is_null( $sport ) ? $sport['key'] : NULL,
				'data-balitsa-field-teams' => $meeting['teams'],
			] ), esc_html__( 'Clone', 'balitsa' ) );
			$actions['update'] = sprintf( '<a%s>%s</a>', balitsa_attrs( [
				'href' => add_query_arg( [
					'action' => 'balitsa_metabox_meeting_update',
					'post' => $post->ID,
					'meeting' => $meeting_key,
					'nonce' => balitsa_nonce_create( 'balitsa_metabox_meeting_update', $post->ID, $meeting_key ),
				], admin_url( 'admin-ajax.php' ) ),
				'class' => 'balitsa-insert',
				'data-balitsa-form' => '.balitsa-form-meeting',
				'data-balitsa-field-datetime' => $dt->format( 'Y-m-d\TH:i' ),
				'data-balitsa-field-sport' => !is_null( $sport ) ? $sport['key'] : NULL,
				'data-balitsa-field-teams' => $meeting['teams'],
			] ), esc_html__( 'Update', 'balitsa' ) );
			$actions['delete'] = sprintf( '<span class="delete"><a%s>%s</a></span>', balitsa_attrs( [
				'href' => add_query_arg( [
					'action' => 'balitsa_metabox_meeting_delete',
					'post' => $post->ID,
					'meeting' => $meeting_key,
					'nonce' => balitsa_nonce_create( 'balitsa_metabox_meeting_delete', $post->ID, $meeting_key ),
				], admin_url( 'admin-ajax.php' ) ),
				'class' => 'balitsa-link',
				'data-balitsa-confirm' => esc_attr__( 'Delete?', 'balitsa' ),
			] ), esc_html__( 'Delete', 'balitsa' ) );
?>
				<tr>
					<td class="column-primary has-row-actions">
						<div>
							<strong><?= esc_html( $dt->format( 'Y-m-d H:i' ) ) ?></strong>
							<span class="<?= $struct['meeting_key'] === $meeting_key ? 'dashicons dashicons-yes' : '' ?>"></span>
						</div>
						<div class="row-actions"><?= implode( ' | ', $actions ) ?></div>
					</td>
					<td style="width: 40px;"><?= !is_null( $sport ) ? sprintf( '<span class="%s"></span>', esc_attr( $sport['icon'] ) ) : '&mdash;' ?></td>
					<td style="width: 50px;"><?= esc_html( $meeting['teams'] ) ?></td>
				</tr>
<?php
		}
?>
			</tbody>
		</table>
	</div>
	<div class="balitsa-form balitsa-form-meeting flex-col" style="display: none;">
		<label class="flex-row flex-justify-between flex-align-center">
			<span class="flex-noshrink leaf"><?= esc_html__( 'Datetime', 'balitsa' ) ?></span>
			<input type="datetime-local" class="balitsa-field leaf" data-balitsa-name="datetime" />
		</label>
		<label class="flex-row flex-justify-between flex-align-center">
			<span class="leaf"><?= esc_html__( 'Sport', 'balitsa' ) ?></span>
			<select class="balitsa-field leaf" data-balitsa-name="sport">
				<option value=""></option>
<?php
		foreach ( $sports as $sport_key => $sport ) {
?>
				<option value="<?= esc_attr( $sport['key'] ) ?>"><?= esc_html( $sport['name'] ) ?></option>
<?php
		}
?>
			</select>
		</label>
		<label class="flex-row flex-justify-between flex-align-center">
			<span class="leaf"><?= esc_html__( 'Teams', 'balitsa' ) ?></span>
			<input type="number" class="balitsa-field leaf" data-balitsa-name="teams" min="1" style="max-width: 100px;" />
		</label>
		<div class="flex-row flex-justify-between flex-align-center">
			<a href="" class="balitsa-link balitsa-submit button button-primary leaf"><?= esc_html__( 'Submit', 'balitsa' ) ?></a>
			<a href="" class="balitsa-cancel button leaf"><?= esc_html__( 'Cancel', 'balitsa' ) ?></a>
		</div>
	</div>
	<hr class="leaf" />
<?php
	// danger zone
?>
	<h3 class="leaf"><?= esc_html__( 'Danger Zone', 'balitsa' ) ?></h3>
	<div class="flex-row">
<?php
		echo sprintf( '<a%s>%s</a>', balitsa_attrs( [
			'href' => add_query_arg( [
				'action' => 'balitsa_metabox_destruct',
				'post' => $post->ID,
				'nonce' => balitsa_nonce_create( 'balitsa_metabox_destruct', $post->ID ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'balitsa-link button leaf',
			'data-balitsa-confirm' => esc_attr__( 'Destruct?', 'balitsa' ),
		] ), esc_html__( 'Destruct', 'balitsa' ) ) . "\n";
	}
?>
	</div>
</div>
<?php
}

function balitsa_metabox( WP_Post $post ): string {
	ob_start();
	balitsa_metabox_echo( $post );
	return ob_get_clean();
}

add_action( 'add_meta_boxes', function(): void {
	add_meta_box( 'balitsa', 'Balitsa', 'balitsa_metabox_echo', NULL, 'side' );
} );

add_action( 'admin_enqueue_scripts', function( string $hook_suffix ): void {
	if ( !in_array( $hook_suffix, [ 'post.php', 'post-new.php', ], TRUE ) )
		return;
	wp_enqueue_style( 'flex', BALITSA_URL . 'flex.css', [], balitsa_version() );
	wp_enqueue_script( 'balitsa_script', BALITSA_URL . 'script.js', [ 'jquery', ], balitsa_version() );
} );

add_action( 'wp_ajax_' . 'balitsa_metabox_refresh', function(): void {
	$post = balitsa_get_post();
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	balitsa_nonce_verify( 'balitsa_metabox_refresh', $post->ID );
	balitsa_success( balitsa_metabox( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_metabox_construct', function(): void {
	$post = balitsa_get_post();
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	balitsa_nonce_verify( 'balitsa_metabox_construct', $post->ID );
	update_post_meta( $post->ID, 'balitsa_struct', [
		'meeting_list' => [],
		'meeting_ai' => 0,
		'meeting_key' => NULL,
		'readonly' => FALSE,
	] );
	balitsa_set_access( $post, [] );
	balitsa_success( balitsa_metabox( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_metabox_destruct', function(): void {
	$post = balitsa_get_post();
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	balitsa_nonce_verify( 'balitsa_metabox_destruct', $post->ID );
	delete_post_meta( $post->ID, 'balitsa_struct' );
	balitsa_set_access( $post, [] );
	balitsa_success( balitsa_metabox( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_metabox_user_accept', function(): void {
	$post = balitsa_get_post();
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	balitsa_nonce_verify( 'balitsa_metabox_user_accept', $post->ID );
	$access = balitsa_get_access( $post );
	$user = balitsa_post_user();
	$key = array_search( $user->ID, $access, TRUE );
	if ( $key !== FALSE )
		exit( 'user' );
	$access[] = $user->ID;
	balitsa_set_access( $post, $access );
	balitsa_success( balitsa_metabox( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_metabox_user_reject', function(): void {
	$post = balitsa_get_post();
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	$access = balitsa_get_access( $post );
	$user = balitsa_get_user();
	$key = array_search( $user->ID, $access, TRUE );
	if ( $key === FALSE )
		exit( 'user' );
	balitsa_nonce_verify( 'balitsa_metabox_user_reject', $post->ID, $user->ID );
	unset( $access[$key] );
	balitsa_set_access( $post, $access );
	balitsa_success( balitsa_metabox( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_metabox_meeting_insert', function(): void {
	$post = balitsa_get_post();
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		exit( 'post' );
	balitsa_nonce_verify( 'balitsa_metabox_meeting_insert', $post->ID );
	$sports = balitsa_get_sports();
	$meeting_key = $struct['meeting_ai']++;
	$meeting = [
		'meeting_key' => $meeting_key,
		'player_list' => [],
		'player_ai' => 0,
	];
	$meeting['datetime'] = balitsa_post_datetime( 'datetime' );
	$meeting['sport'] = balitsa_post_str( 'sport', TRUE );
	if ( !is_null( $meeting['sport'] ) && !array_key_exists( $meeting['sport'], $sports ) )
		exit( 'sport' );
	$meeting['teams'] = balitsa_post_int( 'teams' );
	if ( $meeting['teams'] <= 0 )
		exit( 'teams' );
	$struct['meeting_list'][$meeting_key] = $meeting;
	update_post_meta( $post->ID, 'balitsa_struct', $struct );
	balitsa_success( balitsa_metabox( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_metabox_meeting_update', function(): void {
	$post = balitsa_get_post();
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		exit( 'post' );
	$meeting_key = balitsa_get_int( 'meeting' );
	if ( !array_key_exists( $meeting_key, $struct['meeting_list'] ) )
		exit( 'meeting' );
	balitsa_nonce_verify( 'balitsa_metabox_meeting_update', $post->ID, $meeting_key );
	$sports = balitsa_get_sports();
	$meeting = &$struct['meeting_list'][$meeting_key];
	$meeting['datetime'] = balitsa_post_datetime( 'datetime' );
	$meeting['sport'] = balitsa_post_str( 'sport', TRUE );
	if ( !is_null( $meeting['sport'] ) && !array_key_exists( $meeting['sport'], $sports ) )
		exit( 'sport' );
	$meeting['teams'] = balitsa_post_int( 'teams' );
	if ( $meeting['teams'] <= 0 )
		exit( 'teams' );
	update_post_meta( $post->ID, 'balitsa_struct', $struct );
	balitsa_success( balitsa_metabox( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_metabox_meeting_delete', function(): void {
	$post = balitsa_get_post();
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		exit( 'post' );
	$meeting_key = balitsa_get_int( 'meeting' );
	if ( !array_key_exists( $meeting_key, $struct['meeting_list'] ) )
		exit( 'meeting' );
	balitsa_nonce_verify( 'balitsa_metabox_meeting_delete', $post->ID, $meeting_key );
	unset( $struct['meeting_list'][$meeting_key] );
	if ( $stuct['meeting_key'] === $meeting_key )
		$struct['meeting_key'] = NULL;
	update_post_meta( $post->ID, 'balitsa_struct', $struct );
	balitsa_success( balitsa_metabox( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_metabox_lock', function(): void {
	$post = balitsa_get_post();
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		exit( 'post' );
	if ( $struct['readonly'] )
		exit( 'post' );
	balitsa_nonce_verify( 'balitsa_metabox_lock', $post->ID );
	$struct['readonly'] = TRUE;
	update_post_meta( $post->ID, 'balitsa_struct', $struct );
	balitsa_success( balitsa_metabox( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_metabox_unlock', function(): void {
	$post = balitsa_get_post();
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		exit( 'post' );
	if ( !$struct['readonly'] )
		exit( 'post' );
	balitsa_nonce_verify( 'balitsa_metabox_unlock', $post->ID );
	$struct['readonly'] = FALSE;
	update_post_meta( $post->ID, 'balitsa_struct', $struct );
	balitsa_success( balitsa_metabox( $post ) );
} );
