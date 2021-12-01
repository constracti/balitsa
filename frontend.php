<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_action( 'pre_get_posts', function( WP_Query $query ): void {
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
			'value' => sprintf( '%%#%d#%%', $user_id ),
		],
	] );
} );

function balitsa_get_player_key_by_user( array $meeting, WP_User|int|null $user = NULL ): int|null {
	if ( is_a( $user, 'WP_User' ) )
		$user = $user->ID;
	elseif ( is_null( $user ) )
		$user = get_current_user_id();
	foreach ( $meeting['player_list'] as $player_key => $player ) {
		if ( $player['user'] === $user )
			return $player_key;
	}
	return NULL;
}

function balitsa_get_user_rank_by_meeting( array $meeting, WP_User $user ): int|null {
	$sports = balitsa_get_sports();
	$sport = NULL;
	if ( !array_key_exists( 'sport', $meeting ) )
		return NULL;
	if ( !array_key_exists( $meeting['sport'], $sports ) )
		return NULL;
	$sport = $sports[$meeting['sport']];
	if ( is_null( $sport ) )
		return NULL;
	$ranks = balitsa_get_user_ranks( $user );
	if ( !array_key_exists( $sport['key'], $ranks ) )
		return NULL;
	return $ranks[$sport['key']];
}

function balitsa_has_view_access( WP_Post $post, WP_User|int|null $user = NULL ): bool {
	$access = balitsa_get_access( $post );
	if ( is_a( $user, 'WP_User' ) )
		$user = $user->ID;
	elseif ( is_null( $user ) )
		$user = get_current_user_id();
	if ( $user === 0 )
		return FALSE;
	return empty( $access ) || in_array( $user, $access, TRUE );
}

function balitsa_has_edit_access( WP_Post $post, WP_User|int|null $user = NULL ): bool {
	if ( is_a( $user, 'WP_User' ) )
		$user = $user->ID;
	elseif ( is_null( $user ) )
		$user = get_current_user_id();
	return user_can( $user, 'edit_post', $post->ID );
}

add_filter( 'the_content', function( string $content ): string {
	$post = get_post();
	if ( is_null( $post ) )
		return $content;
	return $content . balitsa_frontend( $post );
} );

function balitsa_player_form( WP_Post $post ): string {
	if ( !balitsa_has_edit_access( $post ) )
		return '';
	$html = '<div class="balitsa-form balitsa-form-player flex-col" style="display: none;">' . "\n";
	// user
	$html .= '<label class="flex-row flex-justify-between flex-align-center">' . "\n";
	$html .= sprintf( '<span class="leaf">%s</span>', esc_html__( 'User', 'balitsa' ) ) . "\n";
	$html .= '<select class="balitsa-field leaf" data-balitsa-name="user">' . "\n";
	$html .= '<option value=""></option>' . "\n";
	$users = get_users( [
		'orderby' => 'display_name',
		'order' => 'ASC',
	] );
	foreach ( $users as $user )
		$html .= sprintf( '<option value="%d">%s</option>', $user->ID, esc_html( $user->display_name ) ) . "\n";
	$html .= '</select>' . "\n";
	$html .= '</label>' . "\n";
	// name
	$html .= '<label class="flex-row flex-justify-between flex-align-center">' . "\n";
	$html .= sprintf( '<span class="leaf">%s</span>', esc_html__( 'Name', 'balitsa' ) ) . "\n";
	$html .= '<input type="text" class="balitsa-field leaf" data-balitsa-name="name" />' . "\n";
	$html .= '</label>' . "\n";
	// rank
	$html .= '<label class="flex-row flex-justify-between flex-align-center">' . "\n";
	$html .= sprintf( '<span class="leaf">%s</span>', esc_html__( 'Rank', 'balitsa' ) ) . "\n";
	$html .= '<select class="balitsa-field leaf" data-balitsa-name="rank">' . "\n";
	$html .= '<option value=""></option>' . "\n";
	for ( $r = 1; $r <= 5; $r++ )
		$html .= sprintf( '<option value="%d">%d</option>', $r, $r ) . "\n";
	$html .= '</select>' . "\n";
	$html .= '</label>' . "\n";
	// team
	$html .= '<label class="flex-row flex-justify-between flex-align-center">' . "\n";
	$html .= sprintf( '<span class="leaf">%s</span>', esc_html__( 'Team', 'balitsa' ) ) . "\n";
	$html .= '<input type="number" class="balitsa-field leaf" data-balitsa-name="team" min="0" />' . "\n";
	$html .= '</label>' . "\n";
	// turn
	$html .= '<label class="flex-row flex-justify-between flex-align-center">' . "\n";
	$html .= sprintf( '<span class="leaf">%s</span>', esc_html__( 'Turn', 'balitsa' ) ) . "\n";
	$html .= '<input type="number" class="balitsa-field leaf" data-balitsa-name="turn" />' . "\n";
	$html .= '</label>' . "\n";
	// availability
	$html .= '<label class="flex-row flex-justify-between flex-align-center">' . "\n";
	$html .= sprintf( '<span class="leaf">%s</span>', esc_html__( 'Availability', 'balitsa' ) ) . "\n";
	$html .= '<select class="balitsa-field leaf" data-balitsa-name="availability">' . "\n";
	$html .= sprintf( '<option value="%s">%s</option>', esc_attr( 'on' ), esc_html__( 'Yes', 'balitsa' ) ) . "\n";
	$html .= sprintf( '<option value="%s">%s</option>', esc_attr( 'maybe' ), esc_html__( 'Yes, if need be', 'balitsa' ) ) . "\n";
	$html .= '</select>' . "\n";
	$html .= '</label>' . "\n";
	// submit
	$html .= '<div class="flex-row flex-justify-between flex-align-center">' . "\n";
	$html .= sprintf( '<a href="" class="balitsa-link balitsa-submit leaf"><span class="fas fa-fw fa-save"></span> %s</a>', esc_html__( 'Submit', 'balitsa' ) ) . "\n";
	$html .= sprintf( '<a href="" class="balitsa-cancel leaf"><span class="fas fa-fw fa-ban"></span> %s</a>', esc_html__( 'Cancel', 'balitsa' ) ) . "\n";
	$html .= '</div>' . "\n";
	$html .= '<hr class="leaf" />' . "\n";
	$html .= '</div>' . "\n";
	return $html;
}

function balitsa_player_insert_link( WP_Post $post, string $meeting_key ): string {
	if ( !balitsa_has_edit_access( $post ) )
		return '';
	return sprintf( '<a%s><span class="%s"></span> %s</a>', balitsa_attrs( [
		'href' => add_query_arg( [
			'action' => 'balitsa_player_insert',
			'post' => $post->ID,
			'meeting' => $meeting_key,
			'nonce' => balitsa_nonce_create( 'balitsa_player_insert', $post->ID, $meeting_key ),
		], admin_url( 'admin-ajax.php' ) ),
		'class' => 'balitsa-insert leaf',
		'data-balitsa-form' => '.balitsa-form-player',
		'data-balitsa-field-availability' => esc_attr( 'on' ),
	] ), esc_attr( 'fas fa-fw fa-user-plus' ), esc_html__( 'Insert', 'balitsa' ) ) . "\n";
}

function balitsa_player_tag( WP_Post $post, array $struct, array $meeting, array $player ): string {
	$html = '<div class="flex-row flex-wrap flex-justify-between root4 leaf" style="border: thin solid;">' . "\n";
	$html .= '<div class="flex-row flex-grow flex-justify-start">' . "\n";
	$html .= '<div class="leaf">' . "\n";
	if ( is_null( $struct['meeting_key'] ) ) {
		if ( $player['availability'] )
			$html .= '<span class="fas fa-fw fa-check-double"></span>' . "\n";
		else
			$html .= '<span class="fas fa-fw fa-check"></span>' . "\n";
	}
	$user = get_user_by( 'ID', $player['user'] );
	if ( $user === FALSE )
		$user = NULL;
	$name = !is_null( $user ) ? $user->display_name : $player['name'];
	$html .= sprintf( '<span>%s</span>', esc_html( $name ) ) . "\n";
	$html .= '</div>' . "\n";
	$html .= '</div>' . "\n";
	$html .= '<div class="flex-row flex-grow flex-justify-end">' . "\n";
	if ( !is_null( $struct['meeting_key'] ) ) {
		$sports = balitsa_get_sports();
		$sport = array_key_exists( 'sport', $meeting ) && array_key_exists( $meeting['sport'], $sports ) ? $sports[$meeting['sport']] : NULL;
		if ( !is_null( $sport ) && array_key_exists( 'stats', $sport ) && is_array( $sport['stats'] ) ) {
			foreach ( $sport['stats'] as $stat_key => $stat ) {
				$value = array_key_exists( 'stats', $player ) && is_array( $player['stats'] ) && array_key_exists( $stat_key, $player['stats'] ) && !is_null( $player['stats'][$stat_key] ) ? $player['stats'][$stat_key] : 0;
				if ( $value < 3 )
					$html .= str_repeat( sprintf( '<div class="leaf"><span class="%s"></span></div>', esc_attr( $stat['icon'] ) ) . "\n", $value );
				else
					$html .= sprintf( '<div class="leaf"><span class="%s"></span>&times;%d</div>', esc_attr( $stat['icon'] ), $value ) . "\n";
			}
		}
		$value = 0;
		foreach ( $meeting['player_list'] as $p ) {
			if ( array_key_exists( 'mvp', $p ) && $p['mvp'] === $player['player_key'] )
				$value++;
		}
		if ( $value < 3 )
			$html .= str_repeat( sprintf( '<div class="leaf"><span class="%s"></span></div>', esc_attr( 'fas fa-fw fa-trophy' ) ) . "\n", $value );
		else
			$html .= sprintf( '<div class="leaf"><span class="%s"></span>&times;%d</div>', esc_attr( 'fas fa-fw fa-trophy' ), $value ) . "\n";
	}
	if ( balitsa_has_edit_access( $post ) && !$struct['readonly'] ) {
		// update
		$html .= sprintf( '<a%s><span class="%s"></span></a>', balitsa_attrs( [
			'href' => add_query_arg( [
				'action' => 'balitsa_player_update',
				'post' => $post->ID,
				'meeting' => $meeting['meeting_key'],
				'player' => $player['player_key'],
				'nonce' => balitsa_nonce_create( 'balitsa_player_update', $post->ID, $meeting['meeting_key'], $player['player_key'] ),
			], admin_url( 'admin-ajax.php' ) ),
			'title' => esc_attr__( 'Update', 'balitsa' ),
			'class' => 'balitsa-insert leaf',
			'data-balitsa-form' => '.balitsa-form-player',
			'data-balitsa-field-user' => $user?->ID,
			'data-balitsa-field-name' => esc_attr( $player['name'] ),
			'data-balitsa-field-rank' => esc_attr( $player['rank'] ),
			'data-balitsa-field-team' => esc_attr( $player['team'] ),
			'data-balitsa-field-turn' => esc_attr( $player['turn'] ),
			'data-balitsa-field-availability' => $player['availability'] ? esc_attr( 'on' ) : esc_attr( 'maybe' ),
		] ), esc_attr( 'fas fa-fw fa-user-edit' ) ) . "\n";
		// delete
		$html .= sprintf( '<a%s><span class="%s"></span></a>', balitsa_attrs( [
			'href' => add_query_arg( [
				'action' => 'balitsa_player_delete',
				'post' => $post->ID,
				'meeting' => $meeting['meeting_key'],
				'player' => $player['player_key'],
				'nonce' => balitsa_nonce_create( 'balitsa_player_delete', $post->ID, $meeting['meeting_key'], $player['player_key'] ),
			], admin_url( 'admin-ajax.php' ) ),
			'title' => esc_attr__( 'Delete', 'balitsa' ),
			'class' => 'balitsa-link leaf',
			'data-balitsa-confirm' => esc_attr( sprintf( __( 'Delete %s?', 'balitsa' ), $name ) ),
		] ), esc_attr( 'fas fa-fw fa-user-minus' ) ) . "\n";
	}
	$html .= '</div>' . "\n";
	$html .= '</div>' . "\n";
	return $html;
}

function balitsa_declaration_links( WP_Post $post, array $struct, array $meeting, array|null $player ): string {
	if ( $struct['readonly'] )
		return '';
	$availability_list = [
		'on'    => [
			'icon' => 'fas fa-fw fa-check-double',
			'text' => __( 'Yes', 'balitsa' ),
			'is_link' => is_null( $player ) || !$player['availability'],
			'count' => 0,
		],
		'maybe' => [
			'icon' => 'fas fa-fw fa-check',
			'text' => __( 'Yes, if need be', 'balitsa' ),
			'is_link' => is_null( $player ) || $player['availability'],
			'count' => 0,
		],
		'off'   => [
			'icon' => 'fas fa-fw fa-times',
			'text' => __( 'No', 'balitsa' ),
			'is_link' => !is_null( $player ),
			'count' => NULL,
		],
	];
	foreach ( array_column( $meeting['player_list'], 'availability' ) as $availability ) {
		if ( $availability )
			$availability_list['on']['count']++;
		else
			$availability_list['maybe']['count']++;
	}
	$html = '<div class="flex-row flex-align-center">' . "\n";
	foreach ( $availability_list as $availability => $a ) {
		$icon = sprintf( '<span class="%s"></span> %s', esc_attr( $a['icon'] ), esc_html( $a['text'] ) );
		if ( !is_null( $a['count'] ) )
			$icon .= sprintf( ' (%d)', $a['count'] );
		if ( $a['is_link'] ) {
			$html .= sprintf( '<a%s>%s</a>', balitsa_attrs( [
				'href' => add_query_arg( [
					'action' => 'balitsa_declare',
					'post' => $post->ID,
					'meeting' => $meeting['meeting_key'],
					'availability' => $availability,
					'nonce' => balitsa_nonce_create( 'balitsa_declare', $post->ID, $meeting['meeting_key'] ),
				], admin_url( 'admin-ajax.php' ) ),
				'class' => 'balitsa-link leaf',
			] ), $icon ) . "\n";
		} else {
			$html .= sprintf( '<span class="leaf">%s</span>', $icon ) . "\n";
		}
	}
	$html .= '</div>' . "\n";
	return $html;
}

function balitsa_header_tag( array $meeting ): string {
	// https://wordpress.org/support/article/formatting-date-and-time/
	$sports = balitsa_get_sports();
	$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $meeting['datetime'], wp_timezone() );
	$sport = NULL;
	if ( array_key_exists( 'sport', $meeting ) && array_key_exists( $meeting['sport'], $sports ) )
		$sport = $sports[$meeting['sport']];
	$html = '<div class="flex-row flex-wrap flex-justify-between">' . "\n";
	$html .= '<div class="flex-row flex-grow flex-justify-start">' . "\n";
	// sport
	if ( !is_null( $sport ) )
		$html .= sprintf( '<div class="leaf"><span class="%s"></span> %s</div>', esc_attr( $sport['icon'] ), esc_html( $sport['name'] ) ) . "\n";
	else
		$html .= '<div class="leaf">&mdash;</div>' . "\n";
	// count
	$html .= sprintf( '<div class="leaf"><span class="fas fa-fw fa-users"></span> %d</div>', count( $meeting['player_list'] ) ) . "\n";
	$html .= '</div>' . "\n";
	$html .= '<div class="flex-row flex-grow flex-justify-end">' . "\n";
	// date
	$html .= sprintf( '<div class="leaf"><span class="fas fa-fw fa-calendar"></span> %s</div>', wp_date( 'D, j M Y', $dt->getTimestamp() ) ) . "\n";
	// time
	$html .= sprintf( '<div class="leaf"><span class="fas fa-fw fa-clock"></span> %s</div>', wp_date( 'g:ia', $dt->getTimestamp() ) ) . "\n";
	$html .= '</div>' . "\n";
	$html .= '</div>' . "\n";
	return $html;
}

function balitsa_statistics_section( WP_Post $post, array $struct, array $meeting ): string {
	if ( $struct['readonly'] )
		return '';
	$sports = balitsa_get_sports();
	if ( !array_key_exists( 'sport', $meeting ) )
		return '';
	$sport = $meeting['sport'];
	if ( !array_key_exists( $sport, $sports ) )
		return '';
	$sport = $sports[$sport];
	if ( !array_key_exists( 'stats', $sport ) )
		return '';
	$stats = $sport['stats'];
	if ( !is_array( $stats ) )
		return '';
	if ( empty( $stats ) )
		return '';
	$player = balitsa_get_player_key_by_user( $meeting );
	if ( is_null( $player ) )
		return '';
	$player = $meeting['player_list'][$player];
	if ( !array_key_exists( 'stats', $player ) || !is_array( $player['stats'] ) )
		$player['stats'] = [];
	$html = '<div class="flex-col">' . "\n";
	$html .= sprintf( '<div class="leaf">%s</div>', esc_html__( 'Statistics', 'balitsa' ) ) . "\n";
	foreach ( $stats as $stat_key => $stat ) {
		$html .= '<div class="flex-row flex-justify-between flex-align-center">' . "\n";
		$value = array_key_exists( $stat_key, $player['stats'] ) ? $player['stats'][$stat_key] : NULL;
		if ( is_null( $value ) )
			$value = 0;
		$html .= sprintf( '<div class="leaf"><span class="%s"></span> %s: %s</div>', esc_attr( $stat['icon'] ), esc_html( $stat['name'] ), esc_html( $value ) ) . "\n";
		$html .= '<div class="flex-row">' . "\n";
		if ( $value > 0 )
			$html .= sprintf( '<a%s><span class="%s"></span> %s</a>', balitsa_attrs( [
				'href' => add_query_arg( [
					'action' => 'balitsa_stat_decrease',
					'post' => $post->ID,
					'meeting' => $meeting['meeting_key'],
					'stat' => $stat_key,
					'nonce' => balitsa_nonce_create( 'balitsa_stat_decrease', $post->ID, $meeting['meeting_key'], $stat_key ),
				], admin_url( 'admin-ajax.php' ) ),
				'class' => 'balitsa-link leaf',
			] ), esc_attr( 'fas fa-fw fa-minus' ), esc_html__( 'Decrease', 'balitsa' ) ) . "\n";
		$html .= sprintf( '<a%s><span class="%s"></span> %s</a>', balitsa_attrs( [
			'href' => add_query_arg( [
				'action' => 'balitsa_stat_increase',
				'post' => $post->ID,
				'meeting' => $meeting['meeting_key'],
				'stat' => $stat_key,
				'nonce' => balitsa_nonce_create( 'balitsa_stat_increase', $post->ID, $meeting['meeting_key'], $stat_key ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'balitsa-link leaf',
		] ), esc_attr( 'fas fa-fw fa-plus' ), esc_html__( 'Increase', 'balitsa' ) ) . "\n";
		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";
	}
	$html .= '</div>' . "\n";
	$html .= '<hr class="leaf" />' . "\n";
	return $html;
}

function balitsa_mvp_section( WP_Post $post, array $struct, array $meeting ): string {
	if ( $struct['readonly'] ) {
		$votes = [];
		$votes_max = 0;
		$player_list = $meeting['player_list'];
		uasort( $player_list, balitsa_sorter( 'turn', 'player_key' ) );
		foreach ( $player_list as $player ) {
			if ( is_null( $player['mvp'] ) )
				continue;
			$mvp = $player['mvp'];
			if ( !array_key_exists( $mvp, $votes ) )
				$votes[$mvp] = 0;
			$votes[$mvp]++;
			if ( $votes[$mvp] > $votes_max )
				$votes_max = $votes[$mvp];
		}
		if ( empty( $votes ) )
			return '';
		$html = '<div class="flex-row flex-wrap flex-justify-between">' . "\n";
		$html .= sprintf( '<div class="leaf"><span class="%s"></span> %s</div>', esc_attr( 'fas fa-fw fa-trophy' ), esc_html__( 'MVP:', 'balitsa' ) ) . "\n";
		$html .= '<div class="flex-row flex-grow flex-justify-end">' . "\n";
		foreach ( $votes as $player => $vote ) {
			if ( $vote < $votes_max )
				continue;
			$player = $player_list[$player];
			$user = get_user_by( 'ID', $player['user'] );
			$name = $user !== FALSE ? $user->display_name : $player['name'];
			$html .= sprintf( '<span class="leaf">%s</span>', esc_html( $name ) ) . "\n";
		}
		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<hr class="leaf" />' . "\n";
		return $html;
	}
	$player = balitsa_get_player_key_by_user( $meeting );
	if ( is_null( $player ) )
		return '';
	$player = $meeting['player_list'][$player];
	$mvp = array_key_exists( 'mvp', $player ) ? $player['mvp'] : NULL;
	$html = '<div class="flex-col">' . "\n";
	$html .= '<div class="flex-row flex-justify-between flex-align-center">' . "\n";
	$html .= sprintf( '<div class="leaf"><span class="%s"></span> %s</div>', esc_attr( 'fas fa-fw fa-trophy' ), esc_html__( 'MVP', 'balitsa' ) ) . "\n";
	if ( !is_null( $mvp ) ) {
		$html .= sprintf( '<a%s><span class="%s"></span> %s</a>', balitsa_attrs( [
			'href' => add_query_arg( [
				'action' => 'balitsa_mvp',
				'post' => $post->ID,
				'meeting' => $meeting['meeting_key'],
				'nonce' => balitsa_nonce_create( 'balitsa_mvp', $post->ID, $meeting['meeting_key'] ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'balitsa-link leaf',
		] ), esc_attr( 'fas fa-fw fa-ban' ), esc_html__( 'Clear', 'balitsa' ) ) . "\n";
	}
	$html .= '</div>' . "\n";
	$html .= '<div class="flex-row flex-wrap flex-align-center">' . "\n";
	$player_list = $meeting['player_list'];
	uasort( $player_list, balitsa_sorter( 'turn', 'player_key' ) );
	foreach ( $player_list as $player ) {
		$user = get_user_by( 'ID', $player['user'] );
		if ( $user === FALSE )
			$user = NULL;
		$name = !is_null( $user ) ? $user->display_name : $player['name'];
		$html .= $mvp !== $player['player_key'] ? sprintf( '<a%s>%s</a>', balitsa_attrs( [
			'href' => add_query_arg( [
				'action' => 'balitsa_mvp',
				'post' => $post->ID,
				'meeting' => $meeting['meeting_key'],
				'player' => $player['player_key'],
				'nonce' => balitsa_nonce_create( 'balitsa_mvp', $post->ID, $meeting['meeting_key'] ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'balitsa-link leaf',
		] ), esc_html( $name ) ) . "\n" : sprintf( '<span class="leaf">%s</span>', esc_html( $name ) ) . "\n";
	}
	$html .= '</div>' . "\n";
	$html .= '</div>' . "\n";
	$html .= '<hr class="leaf" />' . "\n";
	return $html;
}

function balitsa_frontend( WP_Post $post ): string {
	if ( !balitsa_has_view_access( $post ) )
		return '';
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		$struct = NULL;
	if ( is_null( $struct ) )
		return '';
	$html = '<div class="balitsa-container flex-col root4">' . "\n";
	if ( is_null( $struct['meeting_key'] ) ) {
		$meeting_list = $struct['meeting_list'];
		uasort( $meeting_list, balitsa_sorter( 'datetime', 'sport', 'meeting_key' ) );
		foreach ( $meeting_list as $meeting_key => $meeting ) {
			// header
			$html .= balitsa_header_tag( $meeting );
			// declarations
			$player_key = balitsa_get_player_key_by_user( $meeting );
			$player = !is_null( $player_key ) ? $meeting['player_list'][$player_key] : NULL;
			$html .= balitsa_declaration_links( $post, $struct, $meeting, $player );
			// players
			$player_list = $meeting['player_list'];
			uasort( $player_list, balitsa_sorter( 'timestamp', 'player_key' ) );
			$html .= '<div class="flex-row flex-wrap">' . "\n";
			foreach ( $player_list as $player_key => $player ) {
				$html .= balitsa_player_tag( $post, $struct, $meeting, $player );
			}
			$html .= '</div>' . "\n";
			if ( !$struct['readonly'] ) {
				$html .= '<div class="flex-row flex-justify-between flex-align-center">' . "\n";
				// select
				$html .= sprintf( '<a%s><span class="%s"></span> %s</a>', balitsa_attrs( [
					'href' => add_query_arg( [
						'action' => 'balitsa_meeting_select',
						'post' => $post->ID,
						'meeting' => $meeting_key,
						'nonce' => balitsa_nonce_create( 'balitsa_meeting_select', $post->ID, $meeting_key ),
					], admin_url( 'admin-ajax.php' ) ),
					'class' => 'balitsa-link leaf',
				] ), esc_attr( 'fas fa-fw fa-step-forward' ), esc_html__( 'Select', 'balitsa' ) ) . "\n";
				// insert
				$html .= balitsa_player_insert_link( $post, $meeting_key );
				$html .= '</div>' . "\n";
			}
			$html .= '<hr class="leaf" />' . "\n";
		}
	} else {
		$meeting_key = $struct['meeting_key'];
		$meeting = $struct['meeting_list'][$meeting_key];
		// header
		$html .= balitsa_header_tag( $meeting );
		// teams
		$teams = [];
		foreach ( $meeting['player_list'] as $player ) {
			$team = $player['team'];
			if ( !array_key_exists( $team, $teams ) )
				$teams[$team] = [];
			$teams[$team][] = $player;
		}
		ksort( $teams );
		foreach ( $teams as $team ) {
			usort( $team, balitsa_sorter( 'turn', 'player_key' ) );
			$html .= '<div class="flex-col leaf root4">' . "\n";
			foreach ( $team as $player )
				$html .= balitsa_player_tag( $post, $struct, $meeting, $player );
			$html .= '</div>' . "\n";
		}
		$html .= '<hr class="leaf" />' . "\n";
		// statistics
		$html .= balitsa_statistics_section( $post, $struct, $meeting );
		// mvp
		$html .= balitsa_mvp_section( $post, $struct, $meeting );
		if ( balitsa_has_edit_access( $post ) && !$struct['readonly'] ) {
			$html .= '<div class="flex-row flex-justify-between flex-align-center">' . "\n";
			// unselect
			$html .= sprintf( '<a%s><span class="%s"></span> %s</a>', balitsa_attrs( [
				'href' => add_query_arg( [
					'action' => 'balitsa_meeting_unselect',
					'post' => $post->ID,
					'nonce' => balitsa_nonce_create( 'balitsa_meeting_unselect', $post->ID ),
				], admin_url( 'admin-ajax.php' ) ),
				'class' => 'balitsa-link leaf',
			] ), esc_attr( 'fas fa-fw fa-step-backward' ), esc_html__( 'Unselect', 'balitsa' ) ) . "\n";
			// shuffle
			$html .= sprintf( '<a%s><span class="%s"></span> %s</a>', balitsa_attrs( [
				'href' => add_query_arg( [
					'action' => 'balitsa_meeting_shuffle',
					'post' => $post->ID,
					'nonce' => balitsa_nonce_create( 'balitsa_meeting_shuffle', $post->ID ),
				], admin_url( 'admin-ajax.php' ) ),
				'class' => 'balitsa-link leaf',
			] ),  esc_attr( 'fas fa-fw fa-random' ), esc_html__( 'Shuffle', 'balitsa' ) ) . "\n";
			// split
			$html .= sprintf( '<a%s><span class="%s"></span> %s</a>', balitsa_attrs( [
				'href' => add_query_arg( [
					'action' => 'balitsa_meeting_split',
					'post' => $post->ID,
					'nonce' => balitsa_nonce_create( 'balitsa_meeting_split', $post->ID ),
				], admin_url( 'admin-ajax.php' ) ),
				'class' => 'balitsa-link leaf',
			] ), esc_attr( 'fas fa-fw fa-columns' ), esc_html__( 'Split', 'balitsa' ) ) . "\n";
			// insert
			$html .= balitsa_player_insert_link( $post, $meeting_key );
			$html .= '</div>' . "\n";
			$html .= '<hr class="leaf" />' . "\n";
		}
	}
	$html .= balitsa_player_form( $post );
	$html .= '<div class="flex-row flex-justify-between flex-align-center">' . "\n";
	$html .= sprintf( '<a%s><span class="%s"></span> %s</a>', balitsa_attrs( [
		'href' => add_query_arg( [
			'action' => 'balitsa_frontend_refresh',
			'post' => $post->ID,
			'nonce' => balitsa_nonce_create( 'balitsa_frontend_refresh', $post->ID ),
		], admin_url( 'admin-ajax.php' ) ),
		'class' => 'balitsa-link leaf',
	] ), esc_attr( 'fas fa-fw fa-sync-alt' ), esc_html__( 'Refresh', 'balitsa' ) ) . "\n";
	$html .= '<span class="balitsa-spinner leaf" data-balitsa-spinner-toggle="fas fa-fw fa-spinner fa-pulse"></span>' . "\n";
	$html .= '</div>' . "\n";
	$html .= '</div>' . "\n";
	return $html;
}

add_action( 'wp_enqueue_scripts', function(): void {
	wp_enqueue_style( 'flex', BALITSA_URL . 'flex.css', [], balitsa_version() );
	wp_enqueue_script( 'balitsa_script', BALITSA_URL . 'script.js', [ 'jquery', ], balitsa_version() );
} );


// frontend - viewer

add_action( 'wp_ajax_' . 'balitsa_frontend_refresh', function(): void {
	$post = balitsa_get_post();
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		exit( 'post' );
	if ( !balitsa_has_view_access( $post ) )
		exit( 'role' );
	balitsa_nonce_verify( 'balitsa_frontend_refresh', $post->ID );
	balitsa_success( balitsa_frontend( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_declare', function(): void {
	$user = wp_get_current_user();
	$post = balitsa_get_post();
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		exit( 'post' );
	if ( $struct['readonly'] )
		exit( 'post' );
	if ( !is_null( $struct['meeting_key'] ) )
		exit( 'post' );
	if ( !balitsa_has_view_access( $post, $user->ID ) )
		exit( 'role' );
	$meeting_key = balitsa_get_int( 'meeting', TRUE );
	if ( !array_key_exists( $meeting_key, $struct['meeting_list'] ) )
		exit( 'meeting' );
	$meeting = &$struct['meeting_list'][$meeting_key];
	balitsa_nonce_verify( 'balitsa_declare', $post->ID, $meeting_key );
	$availability = balitsa_get_str( 'availability' );
	if ( !in_array( $availability, [ 'on', 'maybe', 'off', ], TRUE ) )
		exit( 'availability' );
	if ( $availability !== 'off' ) {
		$player_key = balitsa_get_player_key_by_user( $meeting, $user->ID );
		if ( is_null( $player_key ) ) {
			$player_key = $meeting['player_ai']++;
			$meeting['player_list'][$player_key] = [
				'player_key' => $player_key,
				'user' => $user->ID,
				'team' => NULL,
				'turn' => NULL,
				'timestamp' => time(),
			];
		}
		$player = &$meeting['player_list'][$player_key];
		$player['name'] = $user->display_name;
		$player['rank'] = balitsa_get_user_rank_by_meeting( $meeting, $user );
		$player['availability'] = $availability === 'on';
	} else {
		$player_key = balitsa_get_player_key_by_user( $meeting, $user->ID );
		if ( !is_null( $player_key ) )
			unset( $meeting['player_list'][$player_key] );
	}
	update_post_meta( $post->ID, 'balitsa_struct', $struct );
	balitsa_success( balitsa_frontend( $post ) );
} );


// frontend - player

add_action( 'wp_ajax_' . 'balitsa_stat_increase', function(): void {
	$user = wp_get_current_user();
	$post = balitsa_get_post();
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		exit( 'post' );
	if ( $struct['readonly'] )
		exit( 'post' );
	if ( is_null( $struct['meeting_key'] ) )
		exit( 'post' );
	if ( !balitsa_has_view_access( $post, $user->ID ) )
		exit( 'role' );
	$meeting = balitsa_get_int( 'meeting', TRUE );
	if ( !array_key_exists( $meeting, $struct['meeting_list'] ) )
		exit( 'meeting' );
	if ( $meeting !== $struct['meeting_key'] )
		exit( 'meeting' );
	$meeting = &$struct['meeting_list'][$meeting];
	$player = balitsa_get_player_key_by_user( $meeting );
	if ( is_null( $player ) )
		exit( 'player' );
	$player = &$meeting['player_list'][$player];
	$sports = balitsa_get_sports();
	if ( !array_key_exists( 'sport', $meeting ) )
		exit( 'stat' );
	$sport = $meeting['sport'];
	if ( !array_key_exists( $sport, $sports ) )
		exit( 'stat' );
	$sport = $sports[$sport];
	$stat = balitsa_get_str( 'stat' );
	if ( !array_key_exists( $stat, $sport['stats'] ) )
		exit( 'stat' );
	balitsa_nonce_verify( 'balitsa_stat_increase', $post->ID, $meeting['meeting_key'], $stat );
	if ( !array_key_exists( 'stats', $player ) || !is_array( $player['stats'] ) )
		$player['stats'] = [];
	if ( !array_key_exists( $stat, $player['stats'] ) || is_null( $player['stats'][$stat] ) )
		$player['stats'][$stat] = 0;
	$player['stats'][$stat]++;
	update_post_meta( $post->ID, 'balitsa_struct', $struct );
	balitsa_success( balitsa_frontend( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_stat_decrease', function(): void {
	$user = wp_get_current_user();
	$post = balitsa_get_post();
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		exit( 'post' );
	if ( $struct['readonly'] )
		exit( 'post' );
	if ( is_null( $struct['meeting_key'] ) )
		exit( 'post' );
	if ( !balitsa_has_view_access( $post, $user->ID ) )
		exit( 'role' );
	$meeting = balitsa_get_int( 'meeting', TRUE );
	if ( !array_key_exists( $meeting, $struct['meeting_list'] ) )
		exit( 'meeting' );
	if ( $meeting !== $struct['meeting_key'] )
		exit( 'meeting' );
	$meeting = &$struct['meeting_list'][$meeting];
	$player = balitsa_get_player_key_by_user( $meeting );
	if ( is_null( $player ) )
		exit( 'player' );
	$player = &$meeting['player_list'][$player];
	$sports = balitsa_get_sports();
	if ( !array_key_exists( 'sport', $meeting ) )
		exit( 'stat' );
	$sport = $meeting['sport'];
	if ( !array_key_exists( $sport, $sports ) )
		exit( 'stat' );
	$sport = $sports[$sport];
	$stat = balitsa_get_str( 'stat' );
	if ( !array_key_exists( $stat, $sport['stats'] ) )
		exit( 'stat' );
	balitsa_nonce_verify( 'balitsa_stat_decrease', $post->ID, $meeting['meeting_key'], $stat );
	if ( !array_key_exists( 'stats', $player ) || !is_array( $player['stats'] ) )
		$player['stats'] = [];
	if ( !array_key_exists( $stat, $player['stats'] ) || is_null( $player['stats'][$stat] ) )
		exit( 'stat' );
	$player['stats'][$stat]--;
	update_post_meta( $post->ID, 'balitsa_struct', $struct );
	balitsa_success( balitsa_frontend( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_mvp', function(): void {
	$user = wp_get_current_user();
	$post = balitsa_get_post();
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		exit( 'post' );
	if ( $struct['readonly'] )
		exit( 'post' );
	if ( is_null( $struct['meeting_key'] ) )
		exit( 'post' );
	if ( !balitsa_has_view_access( $post, $user->ID ) )
		exit( 'role' );
	$meeting = balitsa_get_int( 'meeting', TRUE );
	if ( !array_key_exists( $meeting, $struct['meeting_list'] ) )
		exit( 'meeting' );
	if ( $meeting !== $struct['meeting_key'] )
		exit( 'meeting' );
	$meeting = &$struct['meeting_list'][$meeting];
	$player = balitsa_get_player_key_by_user( $meeting );
	if ( is_null( $player ) )
		exit( 'player' );
	$player = &$meeting['player_list'][$player];
	balitsa_nonce_verify( 'balitsa_mvp', $post->ID, $meeting['meeting_key'] );
	$mvp = balitsa_get_int( 'player', TRUE );
	if ( !is_null( $mvp ) && !array_key_exists( $mvp, $meeting['player_list'] ) )
		exit( 'player' );
	$player['mvp'] = $mvp;
	update_post_meta( $post->ID, 'balitsa_struct', $struct );
	balitsa_success( balitsa_frontend( $post ) );
} );


// frontend - author

add_action( 'wp_ajax_' . 'balitsa_player_insert', function(): void {
	$post = balitsa_get_post();
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		exit( 'post' );
	if ( $struct['readonly'] )
		exit( 'post' );
	if ( !balitsa_has_edit_access( $post ) )
		exit( 'role' );
	$meeting_key = balitsa_get_int( 'meeting' );
	if ( !array_key_exists( $meeting_key, $struct['meeting_list'] ) )
		exit( 'meeting' );
	$meeting = &$struct['meeting_list'][$meeting_key];
	balitsa_nonce_verify( 'balitsa_player_insert', $post->ID, $meeting_key );
	$user = balitsa_post_user( NULL, TRUE );
	$availability = balitsa_post_str( 'availability' );
	if ( !in_array( $availability, [ 'on', 'maybe', ], TRUE ) )
		exit( 'availability' );
	if ( !is_null( $user ) ) {
		$player_key = balitsa_get_player_key_by_user( $meeting, $user->ID );
		if ( !is_null( $player_key ) )
			exit( 'user' );
	}
	$player_key = $meeting['player_ai']++;
	$player = [];
	$player['player_key'] = $player_key;
	$player['user'] = $user?->ID;
	if ( !is_null( $user ) ) {
		$player['name'] = $user->display_name;
		$player['rank'] = balitsa_get_user_rank_by_meeting( $meeting, $user );
	} else {
		$player['name'] = balitsa_post_text( 'name' );
		$player['rank'] = balitsa_post_int( 'rank', TRUE );
		if ( !is_null( $player['rank'] ) && ( $player['rank'] < 1 || $player['rank'] > 5 ) )
			exit( 'rank' );
	}
	$player['team'] = balitsa_post_int( 'team', TRUE );
	if ( !is_null( $player['team'] ) && ( $player['team'] < 0 || $player['team'] >= $meeting['teams'] ) )
		exit( 'team' );
	$player['turn'] = balitsa_post_int( 'turn', TRUE );
	$player['availability'] = $availability === 'on';
	$player['timestamp'] = time();
	$meeting['player_list'][$player_key] = $player;
	update_post_meta( $post->ID, 'balitsa_struct', $struct );
	balitsa_success( balitsa_frontend( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_player_update', function(): void {
	$post = balitsa_get_post();
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		exit( 'post' );
	if ( $struct['readonly'] )
		exit( 'post' );
	if ( !balitsa_has_edit_access( $post ) )
		exit( 'role' );
	$meeting_key = balitsa_get_int( 'meeting' );
	if ( !array_key_exists( $meeting_key, $struct['meeting_list'] ) )
		exit( 'meeting' );
	$meeting = &$struct['meeting_list'][$meeting_key];
	$player_key = balitsa_get_int( 'player' );
	if ( !array_key_exists( $player_key, $meeting['player_list'] ) )
		exit( 'player' );
	balitsa_nonce_verify( 'balitsa_player_update', $post->ID, $meeting_key, $player_key );
	$user = balitsa_post_user( NULL, TRUE );
	$availability = balitsa_post_str( 'availability' );
	if ( !in_array( $availability, [ 'on', 'maybe', ], TRUE ) )
		exit( 'availability' );
	if ( !is_null( $user ) ) {
		if ( !in_array( balitsa_get_player_key_by_user( $meeting, $user->ID ), [ $player_key, NULL, ], TRUE ) )
			exit( 'user' );
	}
	$player = &$meeting['player_list'][$player_key];
	$player['user'] = $user?->ID;
	if ( !is_null( $user ) ) {
		$player['name'] = $user->display_name;
		$player['rank'] = balitsa_get_user_rank_by_meeting( $meeting, $user );
	} else {
		$player['name'] = balitsa_post_text( 'name' );
		$player['rank'] = balitsa_post_int( 'rank', TRUE );
		if ( !is_null( $player['rank'] ) && ( $player['rank'] < 0 || $player['rank'] > 5 ) )
			exit( 'rank' );
	}
	$player['team'] = balitsa_post_int( 'team', TRUE );
	if ( !is_null( $player['team'] ) && ( $player['team'] < 0 || $player['team'] >= $meeting['teams'] ) )
		exit( 'team' );
	$player['turn'] = balitsa_post_int( 'turn', TRUE );
	$player['availability'] = $availability === 'on';
	$meeting['player_list'][$player_key] = $player;
	update_post_meta( $post->ID, 'balitsa_struct', $struct );
	balitsa_success( balitsa_frontend( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_player_delete', function(): void {
	$post = balitsa_get_post();
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		exit( 'post' );
	if ( $struct['readonly'] )
		exit( 'post' );
	if ( !balitsa_has_edit_access( $post ) )
		exit( 'role' );
	$meeting = balitsa_get_int( 'meeting' );
	if ( !array_key_exists( $meeting, $struct['meeting_list'] ) )
		exit( 'meeting' );
	$meeting = &$struct['meeting_list'][$meeting];
	$player = balitsa_get_int( 'player' );
	if ( !array_key_exists( $player, $meeting['player_list'] ) )
		exit( 'player' );
	balitsa_nonce_verify( 'balitsa_player_delete', $post->ID, $meeting['meeting_key'], $player );
	unset( $meeting['player_list'][$player] );
	update_post_meta( $post->ID, 'balitsa_struct', $struct );
	balitsa_success( balitsa_frontend( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_meeting_select', function(): void {
	$post = balitsa_get_post();
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		exit( 'post' );
	if ( $struct['readonly'] )
		exit( 'post' );
	if ( !is_null( $struct['meeting_key'] ) )
		exit( 'post' );
	if ( !balitsa_has_edit_access( $post ) )
		exit( 'role' );
	$meeting = balitsa_get_int( 'meeting' );
	if ( !array_key_exists( $meeting, $struct['meeting_list'] ) )
		exit( 'meeting' );
	balitsa_nonce_verify( 'balitsa_meeting_select', $post->ID, $meeting );
	$struct['meeting_key'] = $meeting;
	update_post_meta( $post->ID, 'balitsa_struct', $struct );
	balitsa_success( balitsa_frontend( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_meeting_unselect', function(): void {
	$post = balitsa_get_post();
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		exit( 'post' );
	if ( $struct['readonly'] )
		exit( 'post' );
	if ( is_null( $struct['meeting_key'] ) )
		exit( 'post' );
	if ( !balitsa_has_edit_access( $post ) )
		exit( 'role' );
	balitsa_nonce_verify( 'balitsa_meeting_unselect', $post->ID );
	$struct['meeting_key'] = NULL;
	update_post_meta( $post->ID, 'balitsa_struct', $struct );
	balitsa_success( balitsa_frontend( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_meeting_split', function(): void {
	$post = balitsa_get_post();
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		exit( 'post' );
	if ( $struct['readonly'] )
		exit( 'post' );
	if ( is_null( $struct['meeting_key'] ) )
		exit( 'post' );
	$meeting = $struct['meeting_key'];
	if ( !array_key_exists( $meeting, $struct['meeting_list'] ) )
		exit( 'post' );
	$meeting = &$struct['meeting_list'][$meeting];
	if ( !balitsa_has_edit_access( $post ) )
		exit( 'role' );
	balitsa_nonce_verify( 'balitsa_meeting_split', $post->ID );
	$cards = [];
	foreach ( $meeting['player_list'] as $player ) {
		$user = $player['user'];
		$user = get_user_by( 'ID', $user );
		if ( $user === FALSE )
			$rank = $player['rank'];
		else
			$rank = balitsa_get_user_rank_by_meeting( $meeting, $user );
		if ( is_null( $rank ) )
			$rank = rand( 1, 5 );
		$cards[] = [
			'key' => $player['player_key'],
			'rank' => -$rank,
			'seed' => mt_rand() / mt_getrandmax(),
		];
	}
	usort( $cards, balitsa_sorter( 'rank', 'seed' ) );
	$teams = [];
	foreach ( $cards as $card ) {
		if ( empty( $teams ) ) {
			$teams = range( 0, $meeting['teams'] - 1 );
			shuffle( $teams );
			$teams = array_merge( $teams, array_reverse( $teams ) );
		}
		$meeting['player_list'][$card['key']]['team'] = array_pop( $teams );
	}
	update_post_meta( $post->ID, 'balitsa_struct', $struct );
	balitsa_success( balitsa_frontend( $post ) );
} );

add_action( 'wp_ajax_' . 'balitsa_meeting_shuffle', function(): void {
	$post = balitsa_get_post();
	$struct = get_post_meta( $post->ID, 'balitsa_struct', TRUE );
	if ( $struct === '' )
		exit( 'post' );
	if ( $struct['readonly'] )
		exit( 'post' );
	if ( is_null( $struct['meeting_key'] ) )
		exit( 'post' );
	$meeting = $struct['meeting_key'];
	if ( !array_key_exists( $meeting, $struct['meeting_list'] ) )
		exit( 'post' );
	$meeting = &$struct['meeting_list'][$meeting];
	if ( !balitsa_has_edit_access( $post ) )
		exit( 'role' );
	balitsa_nonce_verify( 'balitsa_meeting_shuffle', $post->ID );
	$turns = range( 0, count( $meeting['player_list'] ) - 1 );
	shuffle( $turns );
	foreach ( $meeting['player_list'] as &$player )
		$player['turn'] = array_pop( $turns );
	update_post_meta( $post->ID, 'balitsa_struct', $struct );
	balitsa_success( balitsa_frontend( $post ) );
} );
