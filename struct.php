<?php

if ( !defined( 'ABSPATH' ) )
	exit;

/*

struct : array|null
	'meeting_list' : meeting[int]
	'meeting_ai' : int
	'meeting_key' : int|null
	'readonly' : bool

meeting : array
	'meeting_key' : int
	'player_list' : player[int]
	'player_ai' : int
	'datetime' : string
	'sport' : string|null
	'teams' : int

player: array
	'player_key' : int
	'user' : int|null
	'name' : string
	'rank' : int|null
	'team' : int|null
	'turn' : int|null
	'availability' : bool
	'timestamp' : int
	'stats' : int[string]|undefined
	'mvp' : int|null|undefined

*/

final class Balitsa_Struct {

	private const LIMIT = 2;

	private $post;
	private $struct;

	public function __construct( WP_Post $post ) {
		$this->post = $post;
		$this->load();
	}

	private function load(): void {
		$this->struct = get_post_meta( $this->post->ID, 'balitsa_struct', TRUE );
		if ( $this->struct === '' )
			$this->struct = NULL;
	}

	private function save(): void {
		if ( !is_null( $this->struct ) )
			update_post_meta( $this->post->ID, 'balitsa_struct', $this->struct );
		else
			delete_post_meta( $this->post->ID, 'balitsa_struct' );
	}

	// functions

	public function can_edit( WP_User|int|null $user = NULL ): bool {
		if ( is_a( $user, 'WP_User' ) )
			$user = $user->ID;
		elseif ( is_null( $user ) )
			$user = get_current_user_id();
		return user_can( $user, 'edit_post', $this->post->ID );
	}

	public function can_view( WP_User|int|null $user = NULL ): bool {
		if ( is_a( $user, 'WP_User' ) )
			$user = $user->ID;
		elseif ( is_null( $user ) )
			$user = get_current_user_id();
		if ( $this->can_edit( $user ) )
			return TRUE;
		$access = new Balitsa_Access( $this->post );
		return $access->is_empty() || $access->get( $user );
	}

	private function get_user_key( int|null $meeting_key = NULL, WP_User|int|null $user = NULL ): int|null {
		assert( !is_null( $this->struct ) );
		if ( is_null( $meeting_key ) ) {
			assert( !is_null( $this->struct['meeting_key'] ) );
			$meeting_key = $this->struct['meeting_key'];
		}
		assert( array_key_exists( $meeting_key, $this->struct['meeting_list'] ) );
		$meeting = $this->struct['meeting_list'][$meeting_key];
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

	private static function get_player_name( array $player ): string|null {
		if ( is_null( $player['user'] ) )
			return $player['name'];
		$user = get_user_by( 'ID', $player['user'] );
		if ( $user === FALSE )
			return $player['name'];
		return $user->display_name;
	}

	private static function get_player_color_css( array $player ): string {
		if ( is_null( $player['user'] ) )
			return '';
		$user = get_user_by( 'ID', $player['user'] );
		if ( $user === FALSE )
			return '';
		$color = new Balitsa_Color( $user );
		return $color->css();
	}

	private static function get_player_rank( array $player, string|null $sport_key ): int|null {
		if ( is_null( $sport_key ) )
			return $player['rank'];
		if ( is_null( $player['user'] ) )
			return $player['rank'];
		$user = get_user_by( 'ID', $player['user'] );
		if ( $user === FALSE )
			return $player['rank'];
		$ranks = new Balitsa_Ranks( $user );
		return $ranks->get( $sport_key );
	}

	// metabox

	public function metabox_echo(): void {
		echo $this->metabox();
	}

	public function metabox(): string {
		$html = '<div class="balitsa-home balitsa-root balitsa-flex-col" style="margin: 0px -4px 0px -14px;">' . "\n";
		$html .= $this->metabox_refresh_section();
		if ( is_null( $this->struct ) ) {
			$html .= $this->metabox_construct_section();
		} else {
			$html .= $this->metabox_meeting_section();
			$html .= $this->metabox_lock_section();
			$html .= $this->metabox_destruct_section();
		}
		$html .= '</div>' . "\n";
		return $html;
	}

	private function metabox_refresh_section(): string {
		$html = '<div class="balitsa-flex-row balitsa-flex-justify-between balitsa-flex-align-center">' . "\n";
		$html .= sprintf( '<a%s>%s</a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'metabox_refresh' ),
			'class' => 'balitsa-link button balitsa-leaf',
		] ), esc_html__( 'Refresh', 'balitsa' ) ) . "\n";
		$html .= '<span class="balitsa-spinner spinner balitsa-leaf" data-balitsa-spinner-toggle="is-active"></span>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	private function metabox_construct_section(): string {
		$html = '<div class="balitsa-flex-row">' . "\n";
		$html .= sprintf( '<a%s>%s</a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'metabox_construct' ),
			'class' => 'balitsa-link button balitsa-leaf',
		] ), esc_html__( 'Construct', 'balitsa' ) ) . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	private function metabox_destruct_section(): string {
		$html = sprintf( '<h3 class="balitsa-leaf">%s</h3>', esc_html__( 'Danger Zone', 'balitsa' ) ) . "\n";
		$html .= '<div class="balitsa-flex-row">' . "\n";
		$html .= sprintf( '<a%s>%s</a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'metabox_destruct' ),
			'class' => 'balitsa-link button balitsa-leaf',
			'data-balitsa-confirm' => esc_attr__( 'Destruct?', 'balitsa' ),
		] ), esc_html__( 'Destruct', 'balitsa' ) ) . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	private function metabox_meeting_section(): string {
		$html = $this->metabox_meeting_table();
		$html .= $this->metabox_meeting_insert();
		$html .= self::metabox_meeting_form();
		return $html;
	}

	private function metabox_meeting_insert(): string {
		$html = '<div class="balitsa-flex-row balitsa-flex-justify-between balitsa-flex-align-center">' . "\n";
		$html .= sprintf( '<a%s>%s</a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'metabox_meeting_insert' ),
			'class' => 'balitsa-insert button balitsa-leaf',
			'data-balitsa-form' => '.balitsa-form-meeting',
		] ), esc_html__( 'Insert', 'balitsa' ) ) . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	private function metabox_meeting_table(): string {
		$html = '<div class="balitsa-leaf">' . "\n";
		$html .= '<table class="fixed widefat striped">' . "\n";
		$html .= '<thead>' . "\n";
		$html .= '<tr>' . "\n";
		$html .= sprintf( '<th class="column-primary">%s</th>', esc_html__( 'Meetings', 'balitsa' ) ) . "\n";
		$html .= '</tr>' . "\n";
		$html .= '</thead>' . "\n";
		$html .= '<tbody>' . "\n";
		$meeting_list = $this->struct['meeting_list'];
		uasort( $meeting_list, Balitsa::sorter( 'datetime', 'meeting_key' ) );
		foreach ( $meeting_list as $meeting ) {
			$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $meeting['datetime'], wp_timezone() );
			$sport = !is_null( $meeting['sport'] ) ? Balitsa_Sports::select( $meeting['sport'] ) : NULL;
			$actions = [];
			$actions[] = sprintf( '<span><a%s>%s</a></span>', Balitsa::atts( [
				'href' => $this->ajax_href( 'metabox_meeting_insert' ),
				'class' => 'balitsa-insert',
				'data-balitsa-form' => '.balitsa-form-meeting',
				'data-balitsa-field-datetime' => $dt->format( 'Y-m-d\TH:i' ),
				'data-balitsa-field-sport' => !is_null( $sport ) ? $sport['key'] : NULL,
				'data-balitsa-field-teams' => $meeting['teams'],
			] ), esc_html__( 'Clone', 'balitsa' ) ) . "\n";
			$actions[] = sprintf( '<span><a%s>%s</a></span>', Balitsa::atts( [
				'href' => $this->ajax_href( 'metabox_meeting_update', [
					'meeting' => $meeting['meeting_key'],
				] ),
				'class' => 'balitsa-insert',
				'data-balitsa-form' => '.balitsa-form-meeting',
				'data-balitsa-field-datetime' => $dt->format( 'Y-m-d\TH:i' ),
				'data-balitsa-field-sport' => !is_null( $sport ) ? $sport['key'] : NULL,
				'data-balitsa-field-teams' => $meeting['teams'],
			] ), esc_html__( 'Update', 'balitsa' ) ) . "\n";
			if ( $meeting['meeting_key'] !== $this->struct['meeting_key'] ) {
				$actions[] = sprintf( '<span class="delete"><a%s>%s</a></span>', Balitsa::atts( [
					'href' => $this->ajax_href( 'metabox_meeting_delete', [
						'meeting' => $meeting['meeting_key'],
					] ),
					'class' => 'balitsa-link',
					'data-balitsa-confirm' => esc_attr__( 'Delete?', 'balitsa' ),
				] ), esc_html__( 'Delete', 'balitsa' ) ) . "\n";
			}
			$html .= '<tr>' . "\n";
			$html .= '<td class="column-primary has-row-actions">' . "\n";
			$html .= '<div class="balitsa-flex-row balitsa-flex-justify-between balitsa-flex-align-center">' . "\n";
			$html .= '<div>' . "\n";
			$html .= sprintf( '<strong>%s</strong>', esc_html( $dt->format( 'Y-m-d H:i' ) ) ) . "\n";
			if ( $this->struct['meeting_key'] === $meeting['meeting_key'] )
				$html .= '<span class="fas fa-fw fa-check"></span>' . "\n";
			$html .= '</div>' . "\n";
			$html .= '<div>' . "\n";
			if ( !is_null( $sport ) ) {
				$html .= sprintf( '<span class="%s"></span>', esc_attr( $sport['icon'] ) ) . "\n";
				$html .= '<span>|</span>' . "\n";
			}
			$html .= '<span class="fas fa-fw fa-users"></span>' . "\n";
			$html .= sprintf( '<span>%s</span>', esc_html( $meeting['teams'] ) ) . "\n";
			$html .= '</div>' . "\n";
			$html .= '</div>' . "\n";
			$html .= '<div class="row-actions">' . implode( ' | ', $actions ) . '</div>' . "\n";
			$html .= '</td>' . "\n";
			$html .= '</tr>' . "\n";
		}
		$html .= '</tbody>' . "\n";
		$html .= '</table>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	private static function metabox_meeting_form(): string {
		$html = '<div class="balitsa-form balitsa-form-meeting balitsa-flex-col" style="display: none;">' . "\n";
		$html .= '<label class="balitsa-flex-row balitsa-flex-justify-between balitsa-flex-align-center">' . "\n";
		$html .= sprintf( '<span class="balitsa-flex-noshrink balitsa-leaf">%s</span>', esc_html__( 'Datetime', 'balitsa' ) ) . "\n";
		$html .= '<input type="datetime-local" class="balitsa-field balitsa-leaf" data-balitsa-name="datetime" />' . "\n";
		$html .= '</label>' . "\n";
		$html .= '<label class="balitsa-flex-row balitsa-flex-justify-between balitsa-flex-align-center">' . "\n";
		$html .= sprintf( '<span class="balitsa-leaf">%s</span>', esc_html__( 'Sport', 'balitsa' ) ) . "\n";
		$html .= '<select class="balitsa-field balitsa-leaf" data-balitsa-name="sport">' . "\n";
		$html .= '<option value=""></option>' . "\n";
		foreach ( Balitsa_Sports::select() as $sport )
			$html .= sprintf( '<option value="%s">%s</option>', esc_attr( $sport['key'] ), esc_html( $sport['name'] ) ) . "\n";
		$html .= '</select>' . "\n";
		$html .= '</label>' . "\n";
		$html .= '<label class="balitsa-flex-row balitsa-flex-justify-between balitsa-flex-align-center">' . "\n";
		$html .= sprintf( '<span class="balitsa-leaf">%s</span>', esc_html__( 'Teams', 'balitsa' ) ) . "\n";
		$html .= '<input type="number" class="balitsa-field balitsa-leaf" data-balitsa-name="teams" min="1" />' . "\n";
		$html .= '</label>' . "\n";
		$html .= '<div class="balitsa-flex-row balitsa-flex-justify-between balitsa-flex-align-center">' . "\n";
		$html .= sprintf( '<a href="" class="balitsa-link balitsa-submit button button-primary balitsa-leaf">%s</a>', esc_html__( 'Submit', 'balitsa' ) ) . "\n";
		$html .= sprintf( '<a href="" class="balitsa-cancel button balitsa-leaf">%s</a>', esc_html__( 'Cancel', 'balitsa' ) ) . "\n";
		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	private function metabox_lock_section(): string {
		$html = sprintf( '<h3 class="balitsa-leaf">%s</h3>', esc_html__( 'Status', 'balitsa' ) ) . "\n";
		$html .= '<div class="balitsa-flex-row balitsa-flex-justify-between balitsa-flex-align-center">' . "\n";
		$html .= '<span class="balitsa-leaf">' . "\n";
		if ( $this->struct['readonly'] ) {
			$html .= '<span class="fas fa-fw fa-lock"></span>' . "\n";
			$html .= sprintf( '<span>%s</span>', esc_html__( 'Locked', 'balitsa' ) ) . "\n";
		} else {
			$html .= '<span class="fas fa-fw fa-unlock"></span>' . "\n";
			$html .= sprintf( '<span>%s</span>', esc_html__( 'Unlocked', 'balitsa' ) ) . "\n";
		}
		$html .= '</span>' . "\n";
		$html .= '<span class="balitsa-leaf">' . "\n";
		$html .= sprintf( '<a%s>%s</a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'metabox_lock' ),
			'class' => 'balitsa-link button',
		] ), $this->struct['readonly'] ? esc_html__( 'Unlock', 'balitsa' ) : esc_html__( 'Lock', 'balitsa' ) ) . "\n";
		$html .= '</span>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	// frontend

	public function frontend(): string {
		if ( is_null( $this->struct ) )
			return '';
		$html = '<div class="balitsa-home balitsa-meeting-list">' . "\n";
		if ( is_null( $this->struct['meeting_key'] ) ) {
			$meeting_list = $this->struct['meeting_list'];
			uasort( $meeting_list, Balitsa::sorter( 'datetime', 'meeting_key' ) );
			foreach ( $meeting_list as $meeting_key => $meeting ) {
				$html .= $this->frontend_header_tag( $meeting_key );
				$html .= '<div class="balitsa-declaration">' . "\n";
				if ( is_user_logged_in() )
					$html .= $this->frontend_declaration_choices( $meeting_key );
				$html .= $this->frontend_declaration_players( $meeting_key );
				$html .= '</div><!-- .balitsa-declaration -->' . "\n";
				if ( $this->can_edit() && !$this->struct['readonly'] ) {
					$html .= '<div class="balitsa-meeting-actions">' . "\n";
					$html .= $this->frontend_meeting_select_link( $meeting_key );
					$html .= $this->frontend_player_insert_link( $meeting_key );
					$html .= '</div><!-- .balitsa-meeting-actions -->' . "\n";
				}
				$html .= '<hr class="balitsa-sep" />' . "\n";
			}
		} else {
			$html .= $this->frontend_header_tag();
			$html .= $this->frontend_teams_section();
			$html .= $this->frontend_statistics_section();
			if ( $this->struct['readonly'] )
				$html .= $this->frontend_mvp_section();
			else
				$html .= $this->frontend_mvpvote_section();
			if ( $this->can_edit() && !$this->struct['readonly'] ) {
				$html .= '<div class="balitsa-meeting-actions">' . "\n";
				$html .= $this->frontend_meeting_unselect_link();
				$html .= $this->frontend_meeting_shuffle_link();
				$html .= $this->frontend_meeting_split_link();
				$html .= $this->frontend_player_insert_link();
				$html .= '</div><!-- .balitsa-meeting-actions -->' . "\n";
			}
			$html .= '<hr class="balitsa-sep" />' . "\n";
		}
		if ( $this->can_edit() && !$this->struct['readonly'] )
			$html .= self::frontend_player_form();
		$html .= '<div class="balitsa-footer">' . "\n";
		$html .= $this->frontend_refresh_link();
		$html .= '<span class="balitsa-spinner" data-balitsa-spinner-toggle="fas fa-fw fa-spinner fa-pulse"></span>' . "\n";
		$html .= '</div><!-- .balitsa-footer -->' . "\n";
		$html .= '</div><!-- .balitsa-meeting-list -->' . "\n";
		return $html;
	}

	private function frontend_header_tag( int|null $meeting_key = NULL ): string {
		assert( !is_null( $this->struct ) );
		if ( !is_null( $meeting_key ) ) {
			assert( array_key_exists( $meeting_key, $this->struct['meeting_list'] ) );
		} else {
			assert( !is_null( $this->struct['meeting_key'] ) );
			$meeting_key = $this->struct['meeting_key'];
		}
		$meeting = $this->struct['meeting_list'][$meeting_key];
		$sport = Balitsa_Sports::select( $meeting['sport'] );
		// https://wordpress.org/support/article/formatting-date-and-time/
		$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $meeting['datetime'], wp_timezone() );
		$html = '<div class="balitsa-header">' . "\n";
		$html .= '<div class="balitsa-header-left">' . "\n";
		// sport
		if ( !is_null( $sport ) ) {
			$html .= '<div class="balitsa-sport">' . "\n";
			$html .= sprintf( '<span class="%s"></span>', esc_attr( $sport['icon'] ) ) . "\n";
			$html .= sprintf( '<span>%s</span>', esc_html( $sport['name'] ) ) . "\n";
			$html .= '</div><!-- .balitsa-sport -->' . "\n";
		} else {
			$html .= '<div class="balitsa-sport">&mdash;</div>' . "\n";
		}
		// count
		$html .= sprintf( '<div class="balitsa-count"><span class="fas fa-fw fa-users"></span> %d</div>', count( $meeting['player_list'] ) ) . "\n";
		$html .= '</div><!-- .balitsa-header-left -->' . "\n";
		$html .= '<div class="balitsa-header-right">' . "\n";
		// date
		$html .= sprintf( '<div class="balitsa-date"><span class="fas fa-fw fa-calendar"></span> %s</div>', wp_date( 'D, j M Y', $dt->getTimestamp() ) ) . "\n";
		// time
		$html .= sprintf( '<div class="balitsa-time"><span class="fas fa-fw fa-clock"></span> %s</div>', wp_date( 'g:ia', $dt->getTimestamp() ) ) . "\n";
		$html .= '</div>' . "\n";
		$html .= '</div><!-- .balitsa-header -->' . "\n";
		return $html;
	}

	private static function frontend_player_form(): string {
		$html = '<div class="balitsa-form balitsa-form-player" style="display: none;">' . "\n";
		$html .= '<div class="balitsa-form-player-fields">' . "\n";
		// user
		$html .= '<label>' . "\n";
		$html .= sprintf( '<span>%s</span>', esc_html__( 'User', 'balitsa' ) ) . "\n";
		$html .= '<select class="balitsa-field" data-balitsa-name="user">' . "\n";
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
		$html .= '<label>' . "\n";
		$html .= sprintf( '<span>%s</span>', esc_html__( 'Name', 'balitsa' ) ) . "\n";
		$html .= '<input type="text" class="balitsa-field" data-balitsa-name="name" />' . "\n";
		$html .= '</label>' . "\n";
		// rank
		$html .= '<label>' . "\n";
		$html .= sprintf( '<span>%s</span>', esc_html__( 'Rank', 'balitsa' ) ) . "\n";
		$html .= '<select class="balitsa-field" data-balitsa-name="rank">' . "\n";
		$html .= '<option value=""></option>' . "\n";
		for ( $r = Balitsa_Ranks::MIN; $r <= Balitsa_Ranks::MAX; $r++ )
			$html .= sprintf( '<option value="%d">%d</option>', $r, $r ) . "\n";
		$html .= '</select>' . "\n";
		$html .= '</label>' . "\n";
		// team
		$html .= '<label>' . "\n";
		$html .= sprintf( '<span>%s</span>', esc_html__( 'Team', 'balitsa' ) ) . "\n";
		$html .= '<input type="number" class="balitsa-field" data-balitsa-name="team" min="0" />' . "\n";
		$html .= '</label>' . "\n";
		// turn
		$html .= '<label>' . "\n";
		$html .= sprintf( '<span>%s</span>', esc_html__( 'Turn', 'balitsa' ) ) . "\n";
		$html .= '<input type="number" class="balitsa-field" data-balitsa-name="turn" />' . "\n";
		$html .= '</label>' . "\n";
		// availability
		$html .= '<label>' . "\n";
		$html .= sprintf( '<span>%s</span>', esc_html__( 'Availability', 'balitsa' ) ) . "\n";
		$html .= '<select class="balitsa-field" data-balitsa-name="availability">' . "\n";
		$html .= sprintf( '<option value="%s">%s</option>', esc_attr( 'on' ), esc_html__( 'Yes', 'balitsa' ) ) . "\n";
		$html .= sprintf( '<option value="%s">%s</option>', esc_attr( 'off' ), esc_html__( 'Yes, if need be', 'balitsa' ) ) . "\n";
		$html .= '</select>' . "\n";
		$html .= '</label>' . "\n";
		$html .= '</div><!-- .balitsa-form-player-fields -->' . "\n";
		// submit
		$html .= '<div class="balitsa-form-player-footer">' . "\n";
		$html .= sprintf( '<a href="" class="balitsa-link balitsa-submit balitsa-button"><span class="fas fa-fw fa-save"></span> %s</a>', esc_html__( 'Submit', 'balitsa' ) ) . "\n";
		$html .= sprintf( '<a href="" class="balitsa-cancel balitsa-button"><span class="fas fa-fw fa-ban"></span> %s</a>', esc_html__( 'Cancel', 'balitsa' ) ) . "\n";
		$html .= '</div><!-- .balitsa-form-player-footer -->' . "\n";
		$html .= '</div><!-- .balitsa-form-player -->' . "\n";
		return $html;
	}

	private function frontend_declaration_choices( int $meeting_key ): string {
		assert( !is_null( $this->struct ) );
		assert( is_null( $this->struct['meeting_key'] ) );
		assert( array_key_exists( $meeting_key, $this->struct['meeting_list'] ) );
		$meeting = $this->struct['meeting_list'][$meeting_key];
		$player_key = $this->get_user_key( $meeting_key );
		$player = !is_null( $player_key ) ? $meeting['player_list'][$player_key] : NULL;
		$availability_list = [
			'yes'    => [
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
			'no' => [
				'icon' => 'fas fa-fw fa-times',
				'text' => __( 'No', 'balitsa' ),
				'is_link' => !is_null( $player ),
				'count' => NULL,
			],
		];
		foreach ( $meeting['player_list'] as $player ) {
			if ( $player['availability'] )
				$availability_list['yes']['count']++;
			else
				$availability_list['maybe']['count']++;
		}
		$html = '<div class="balitsa-declaration-choices">' . "\n";
		foreach ( $availability_list as $availability => $a ) {
			$icon = sprintf( '<span class="%s"></span>', esc_attr( $a['icon'] ) );
			$title = $a['text'];
			if ( !is_null( $a['count'] ) )
				$title .= sprintf( ' (%d)', $a['count'] );
			if ( $a['is_link'] ) {
				$html .= sprintf( '<a%s>%s</a>', Balitsa::atts( [
					'href' => $this->ajax_href( 'frontend_declare', [
						'meeting' => $meeting['meeting_key'],
						'availability' => $availability,
					] ),
					'class' => 'balitsa-link balitsa-button',
					'title' => esc_attr( $title ),
				] ), $icon ) . "\n";
			} else {
				$html .= sprintf( '<span class="balitsa-button">%s <span>%s</span></span>', $icon, esc_html( $title ) ) . "\n";
			}
		}
		$html .= '</div><!-- .balitsa-declaration-choices -->' . "\n";
		return $html;
	}

	private function frontend_declaration_players( int $meeting_key ): string {
		assert( !is_null( $this->struct ) );
		assert( is_null( $this->struct['meeting_key'] ) );
		assert( array_key_exists( $meeting_key, $this->struct['meeting_list'] ) );
		$meeting = $this->struct['meeting_list'][$meeting_key];
		$player_list = $meeting['player_list'];
		uasort( $player_list, Balitsa::sorter( 'timestamp', 'player_key' ) );
		$html = '<div class="balitsa-declaration-players">' . "\n";
		foreach ( $player_list as $player_key => $player )
			$html .= $this->frontend_player_tag( $player_key, $meeting_key );
		$html .= '</div><!-- .balitsa-declaration-players -->' . "\n";
		return $html;
	}

	private function frontend_teams_section(): string {
		assert( !is_null( $this->struct ) );
		$meeting_key = $this->struct['meeting_key'];
		assert( !is_null( $meeting_key ) );
		$meeting = $this->struct['meeting_list'][$meeting_key];
		$teams = [];
		foreach ( $meeting['player_list'] as $player ) {
			$team = $player['team'];
			if ( !array_key_exists( $team, $teams ) )
				$teams[$team] = [];
			$teams[$team][] = $player;
		}
		ksort( $teams );
		$html = '<div class="balitsa-team-list">';
		foreach ( $teams as $team ) {
			usort( $team, Balitsa::sorter( 'turn', 'player_key' ) );
			$html .= '<div class="balitsa-team">' . "\n";
			foreach ( $team as $player )
				$html .= $this->frontend_player_tag( $player['player_key'] );
			$html .= '</div><!-- .balitsa-team -->' . "\n";
		}
		$html .= '</div><!-- .balitsa-team-list -->' . "\n";
		return $html;
	}

	private function frontend_player_tag( int $player_key, int|null $meeting_key = NULL ): string {
		assert( !is_null( $this->struct ) );
		if ( !is_null( $meeting_key ) ) {
			assert( is_null( $this->struct['meeting_key'] ) );
			assert( array_key_exists( $meeting_key, $this->struct['meeting_list'] ) );
		} else {
			assert( !is_null( $this->struct['meeting_key'] ) );
			$meeting_key = $this->struct['meeting_key'];
		}
		$meeting = $this->struct['meeting_list'][$meeting_key];
		assert( array_key_exists( $player_key, $meeting['player_list'] ) );
		$player = $meeting['player_list'][$player_key];
		$html = sprintf( '<div class="balitsa-player" style="%s">', esc_attr( self::get_player_color_css( $player ) ) ) . "\n";
		$html .= '<div class="balitsa-player-left">' . "\n";
		if ( is_null( $this->struct['meeting_key'] ) ) {
			$html .= '<div class="balitsa-player-availability">' . "\n";
			if ( $player['availability'] )
				$html .= '<span class="fas fa-fw fa-check-double"></span>' . "\n";
			else
				$html .= '<span class="fas fa-fw fa-check"></span>' . "\n";
			$html .= '</div><!-- .balitsa-player-availability -->' . "\n";
		}
		$html .= sprintf( '<span class="balitsa-player-name">%s</span>', esc_html( self::get_player_name( $player ) ) ) . "\n";
		$html .= '</div><!-- .balitsa-player-left -->' . "\n";
		$html .= '<div class="balitsa-player-right">' . "\n";
		if ( !is_null( $this->struct['meeting_key'] ) ) {
			$sport = Balitsa_Sports::select( $meeting['sport'] );
			if ( !is_null( $sport ) ) {
				foreach ( $sport['stats'] as $stat_key => $stat ) {
					$value = array_key_exists( 'stats', $player ) && is_array( $player['stats'] ) && array_key_exists( $stat_key, $player['stats'] ) && is_int( $player['stats'][$stat_key] ) ? $player['stats'][$stat_key] : NULL;
					if ( !is_int( $value ) )
						;
					elseif ( $value <= self::LIMIT )
						$html .= str_repeat( sprintf( '<div class="balitsa-player-stat"><span class="%s"></span></div>', esc_attr( $stat['icon'] ) ) . "\n", $value );
					else
						$html .= sprintf( '<div class="balitsa-player-stat"><span class="%s"></span>&times;%d</div>', esc_attr( $stat['icon'] ), $value ) . "\n";
				}
			}
			if ( $this->struct['readonly'] ) {
				$value = 0;
				foreach ( $meeting['player_list'] as $p ) {
					if ( array_key_exists( 'mvp', $p ) && $p['mvp'] === $player_key )
						$value++;
				}
				if ( $value <= self::LIMIT )
					$html .= str_repeat( sprintf( '<div class="balitsa-player-mvp"><span class="%s"></span></div>', esc_attr( 'fas fa-fw fa-trophy' ) ) . "\n", $value );
				else
					$html .= sprintf( '<div class="balitsa-player-mvp"><span class="%s"></span>&times;%d</div>', esc_attr( 'fas fa-fw fa-trophy' ), $value ) . "\n";
			}
		}
		if ( $this->can_edit() && !$this->struct['readonly'] ) {
			$html .= $this->frontend_player_update_link( $player_key, $meeting_key );
			$html .= $this->frontend_player_delete_link( $player_key, $meeting_key );
		}
		$html .= '</div><!-- .balitsa-player-right -->' . "\n";
		$html .= '</div><!-- .balitsa-player -->' . "\n";
		return $html;
	}

	private function frontend_statistics_section(): string {
		assert( !is_null( $this->struct ) );
		assert( !is_null( $this->struct['meeting_key'] ) );
		$meeting_key = $this->struct['meeting_key'];
		$meeting = $this->struct['meeting_list'][$meeting_key];
		if ( $this->struct['readonly'] )
			return '';
		$sport = Balitsa_Sports::select( $meeting['sport'] );
		if ( is_null( $sport ) || empty( $sport['stats'] ) )
			return '';
		$player = $this->get_user_key();
		if ( is_null( $player ) )
			return '';
		$player = $meeting['player_list'][$player];
		if ( !array_key_exists( 'stats', $player ) )
			$player['stats'] = [];
		$html = '<div class="balitsa-stat-panel">' . "\n";
		$html .= '<div class="balitsa-stat-header">' . "\n";
		$html .= '<span class="fas fa-fw fa-chart-bar"></span>' . "\n";
		$html .= sprintf( '<span>%s</span>', esc_html__( 'Statistics', 'balitsa' ) ) . "\n";
		$html .= '</div><!-- .balitsa-stat-header -->' . "\n";
		$html .= '<div class="balitsa-stat-list">' . "\n";
		foreach ( $sport['stats'] as $stat_key => $stat ) {
			$html .= '<div class="balitsa-stat">' . "\n";
			$value = array_key_exists( $stat_key, $player['stats'] ) ? $player['stats'][$stat_key] : NULL;
			if ( is_null( $value ) )
				$value = 0;
			$html .= sprintf( '<div class="balitsa-stat-left"><span class="%s"></span> %s: %s</div>', esc_attr( $stat['icon'] ), esc_html( $stat['name'] ), esc_html( $value ) ) . "\n";
			$html .= '<div class="balitsa-stat-right">' . "\n";
			if ( $value > 0 ) {
				$html .= sprintf( '<a%s><span class="%s"></span></a>', Balitsa::atts( [
					'href' => $this->ajax_href( 'frontend_stat', [
						'stat' => $stat_key,
						'value' => $value - 1,
					] ),
					'class' => 'balitsa-link balitsa-button balitsa-stat-dec',
					'title' => esc_attr__( 'Decrease', 'balitsa' ),
				] ), esc_attr( 'fas fa-fw fa-minus' ) ) . "\n";
			}
			$html .= sprintf( '<a%s><span class="%s"></span></a>', Balitsa::atts( [
				'href' => $this->ajax_href( 'frontend_stat', [
					'stat' => $stat_key,
					'value' => $value + 1,
				] ),
				'class' => 'balitsa-link balitsa-button balitsa-stat-inc',
				'title' => esc_attr__( 'Increase', 'balitsa' ),
			] ), esc_attr( 'fas fa-fw fa-plus' ) ) . "\n";
			$html .= '</div><!-- .balitsa-stat-right -->' . "\n";
			$html .= '</div><!-- .balitsa-stat -->' . "\n";
		}
		$html .= '</div><!-- .balitsa-stat-list -->' . "\n";
		$html .= '</div><!-- .balitsa-stat-panel -->' . "\n";
		return $html;
	}

	private function frontend_mvp_section(): string {
		assert( !is_null( $this->struct ) );
		assert( !is_null( $this->struct['meeting_key'] ) );
		$meeting_key = $this->struct['meeting_key'];
		$meeting = $this->struct['meeting_list'][$meeting_key];
		assert( $this->struct['readonly'] );
		$votes = [];
		$votes_max = 0;
		$player_list = $meeting['player_list'];
		uasort( $player_list, Balitsa::sorter( 'turn', 'player_key' ) );
		foreach ( $player_list as $player ) {
			if ( !array_key_exists( 'mvp', $player ) || !array_key_exists( $player['mvp'], $meeting['player_list'] ) )
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
		$html = '<div class="balitsa-mvp-panel">' . "\n";
		$html .= '<div class="balitsa-mvp-left">' . "\n";
		$html .= '<span class="fas fa-fw fa-trophy"></span>' . "\n";
		$html .= sprintf( '<span>%s</span>', esc_html__( 'MVP:', 'balitsa' ) ) . "\n";
		$html .= '</div><!-- .balitsa-mvp-left -->' . "\n";
		$html .= '<div class="balitsa-mvp-right">' . "\n";
		foreach ( $votes as $player => $vote ) {
			if ( $vote < $votes_max )
				continue;
			$player = $player_list[$player];
			$html .= sprintf( '<span class="balitsa-mvp" style="%s">%s</span>', esc_attr( self::get_player_color_css( $player ) ), esc_html( self::get_player_name( $player ) ) ) . "\n";
		}
		$html .= '</div><!-- .balitsa-mvp-right -->' . "\n";
		$html .= '</div><!-- .balitsa-mvp-panel -->' . "\n";
		return $html;
	}

	private function frontend_mvpvote_section(): string {
		assert( !is_null( $this->struct ) );
		assert( !is_null( $this->struct['meeting_key'] ) );
		$meeting_key = $this->struct['meeting_key'];
		$meeting = $this->struct['meeting_list'][$meeting_key];
		assert( !$this->struct['readonly'] );
		$player_key = $this->get_user_key();
		if ( is_null( $player_key ) )
			return '';
		$player = $meeting['player_list'][$player_key];
		$mvp = NULL;
		if ( array_key_exists( 'mvp', $player ) ) {
			$mvp = $player['mvp'];
			$mvp = array_key_exists( $mvp, $meeting['player_list'] ) ? $meeting['player_list'][$mvp] : NULL;
		}
		$html = '<div class="balitsa-mvpvote-panel">' . "\n";
		$html .= '<div class="balitsa-mvpvote-header">' . "\n";
		$html .= '<div class="balitsa-mvpvote-header-left">' . "\n";
		$html .= '<span class="fas fa-fw fa-person-booth"></span>' . "\n";
		$html .= sprintf( '<span>%s</span>', esc_html__( 'MVP Vote:', 'balitsa' ) ) . "\n";
		$html .= '</div><!-- .balitsa-mvpvote-header-left -->' . "\n";
		$html .= '<div class="balitsa-mvpvote-header-right">' . "\n";
		$html .= sprintf( '<span>%s</span>', !is_null( $mvp ) ? self::get_player_name( $mvp ) : '&mdash;' ) . "\n";
		$html .= '</div><!-- .balitsa-mvpvote-header-right -->' . "\n";
		$html .= '</div><!-- .balitsa-mvpvote-header -->' . "\n";
		$html .= '<div class="balitsa-mvpvote-list">' . "\n";
		$player_list = $meeting['player_list'];
		uasort( $player_list, Balitsa::sorter( 'turn', 'player_key' ) );
		foreach ( $player_list as $p ) {
			if ( is_null( $mvp ) || $p['player_key'] !== $mvp['player_key'] ) {
				$html .= sprintf( '<a%s>%s</a>', Balitsa::atts( [
					'href' => $this->ajax_href( 'frontend_mvp', [
						'player' => $p['player_key'],
					] ),
					'class' => 'balitsa-link balitsa-button balitsa-mvpvote-item',
				] ), esc_html( self::get_player_name( $p ) ) ) . "\n";
			} else {
				$html .= sprintf( '<a%s>%s</a>', Balitsa::atts( [
					'href' => $this->ajax_href( 'frontend_mvp' ),
					'class' => 'balitsa-link balitsa-button balitsa-mvpvote-item',
				] ), esc_html( self::get_player_name( $p ) ) ) . "\n";
			}
		}
		$html .= '</div><!-- .balitsa-mvpvote-list -->' . "\n";
		$html .= '</div><!-- .balitsa-mvpvote-panel -->' . "\n";
		return $html;
	}

	private function frontend_refresh_link(): string {
		return sprintf( '<a%s><span class="%s"></span> %s</a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'frontend_refresh' ),
			'class' => 'balitsa-link balitsa-button',
		] ), esc_attr( 'fas fa-fw fa-sync-alt' ), esc_html__( 'Refresh', 'balitsa' ) ) . "\n";
	}

	private function frontend_meeting_select_link( int $meeting_key ): string {
		return sprintf( '<a%s><span class="%s"></span></a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'frontend_meeting_select', [
				'meeting' => $meeting_key,
			] ),
			'class' => 'balitsa-link balitsa-button',
			'title' => esc_attr__( 'Select', 'balitsa' ),
		] ), esc_attr( 'fas fa-fw fa-step-forward' ) ) . "\n";
	}

	private function frontend_meeting_unselect_link(): string {
		return sprintf( '<a%s><span class="%s"></span></a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'frontend_meeting_unselect' ),
			'class' => 'balitsa-link balitsa-button',
			'title' => esc_attr__( 'Unselect', 'balitsa' ),
		] ), esc_attr( 'fas fa-fw fa-step-backward' ) ) . "\n";
	}

	private function frontend_meeting_shuffle_link(): string {
		return sprintf( '<a%s><span class="%s"></span></a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'frontend_meeting_shuffle' ),
			'class' => 'balitsa-link balitsa-button',
			'title' => esc_attr__( 'Shuffle', 'balitsa' ),
		] ),  esc_attr( 'fas fa-fw fa-random' ) ) . "\n";
	}

	private function frontend_meeting_split_link(): string {
		return sprintf( '<a%s><span class="%s"></span></a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'frontend_meeting_split' ),
			'class' => 'balitsa-link balitsa-button',
			'title' => esc_attr__( 'Split', 'balitsa' ),
		] ), esc_attr( 'fas fa-fw fa-columns' ) ) . "\n";
	}

	private function frontend_player_insert_link( int|null $meeting_key = NULL ): string {
		assert( !is_null( $this->struct ) );
		if ( !is_null( $meeting_key ) ) {
			assert( array_key_exists( $meeting_key, $this->struct['meeting_list'] ) );
		} else {
			assert( !is_null( $this->struct['meeting_key'] ) );
			$meeting_key = $this->struct['meeting_key'];
		}
		return sprintf( '<a%s><span class="%s"></span></a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'frontend_player_insert', [
				'meeting' => $meeting_key,
			] ),
			'class' => 'balitsa-insert balitsa-button',
			'title' => esc_attr__( 'Insert', 'balitsa' ),
			'data-balitsa-form' => '.balitsa-form-player',
			'data-balitsa-field-availability' => esc_attr( 'on' ),
		] ), esc_attr( 'fas fa-fw fa-user-plus' ) ) . "\n";
	}

	private function frontend_player_update_link( int $player_key, int $meeting_key ): string {
		assert( !is_null( $this->struct ) );
		assert( array_key_exists( $meeting_key, $this->struct['meeting_list'] ) );
		$meeting = $this->struct['meeting_list'][$meeting_key];
		assert( array_key_exists( $player_key, $meeting['player_list'] ) );
		$player = $meeting['player_list'][$player_key];
		return sprintf( '<a%s><span class="%s"></span></a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'frontend_player_update', [
				'meeting' => $meeting_key,
				'player' => $player_key,
			] ),
			'title' => esc_attr__( 'Update', 'balitsa' ),
			'class' => 'balitsa-insert balitsa-player-update',
			'data-balitsa-form' => '.balitsa-form-player',
			'data-balitsa-field-user' => esc_attr( $player['user'] ),
			'data-balitsa-field-name' => esc_attr( $player['name'] ),
			'data-balitsa-field-rank' => esc_attr( $player['rank'] ),
			'data-balitsa-field-team' => esc_attr( $player['team'] ),
			'data-balitsa-field-turn' => esc_attr( $player['turn'] ),
			'data-balitsa-field-availability' => esc_attr( $player['availability'] ? 'on' : 'off' ),
		] ), esc_attr( 'fas fa-fw fa-user-edit' ) ) . "\n";
	}

	private function frontend_player_delete_link( int $player_key, int $meeting_key ): string {
		assert( !is_null( $this->struct ) );
		assert( array_key_exists( $meeting_key, $this->struct['meeting_list'] ) );
		$meeting = $this->struct['meeting_list'][$meeting_key];
		assert( array_key_exists( $player_key, $meeting['player_list'] ) );
		$player = $meeting['player_list'][$player_key];
		return sprintf( '<a%s><span class="%s"></span></a>', Balitsa::atts( [
			'href' => $this->ajax_href( 'frontend_player_delete', [
				'meeting' => $meeting_key,
				'player' => $player_key,
			] ),
			'title' => esc_attr__( 'Delete', 'balitsa' ),
			'class' => 'balitsa-link balitsa-player-delete',
			'data-balitsa-confirm' => esc_attr( sprintf( __( 'Delete %s?', 'balitsa' ), self::get_player_name( $player ) ) ),
		] ), esc_attr( 'fas fa-fw fa-user-minus' ) ) . "\n";
	}

	// ajax

	private function ajax_href( string $task, array $args = [] ): string {
		return add_query_arg( array_merge( [
			'action' => 'balitsa_struct',
			'task' => $task,
			'post' => $this->post->ID,
			'nonce' => $this->nonce_create( $task ),
		], $args ), admin_url( 'admin-ajax.php' ) );
	}

	private function nonce_create( string $task, string ...$args ): string {
		$action = 'balitsa_struct' . '_' . $task;
		array_unshift( $args, $this->post->ID );
		return Balitsa::nonce_create( $action, ...$args );
	}

	private function nonce_verify( string $task, string ...$args ): void {
		$action = 'balitsa_struct' . '_' . $task;
		array_unshift( $args, $this->post->ID );
		Balitsa::nonce_verify( $action, ...$args );
	}

	public function ajax( string $task ): void {
		$this->nonce_verify( $task );
		switch ( $task ) {
			case 'metabox_refresh':
				if ( !$this->can_edit() )
					exit( 'role' );
				Balitsa::success( $this->metabox() );
			case 'metabox_construct':
				if ( !$this->can_edit() )
					exit( 'role' );
				if ( !is_null( $this->struct ) )
					exit( 'post' );
				$this->struct = [
					'meeting_list' => [],
					'meeting_ai' => 0,
					'meeting_key' => NULL,
					'readonly' => FALSE,
				];
				$this->save();
				Balitsa::success( $this->metabox() );
			case 'metabox_destruct':
				if ( !$this->can_edit() )
					exit( 'role' );
				if ( is_null( $this->struct ) )
					exit( 'post' );
				$this->struct = NULL;
				$this->save();
				Balitsa::success( $this->metabox() );
			case 'metabox_meeting_insert':
				if ( !$this->can_edit() )
					exit( 'role' );
				if ( is_null( $this->struct ) )
					exit( 'post' );
				$meeting_key = $this->struct['meeting_ai']++;
				$meeting = [
					'meeting_key' => $meeting_key,
					'player_list' => [],
					'player_ai' => 0,
				];
				$meeting['datetime'] = Balitsa_Request::post( 'datetime' );
				$meeting['sport'] = Balitsa_Request::post( 'str', 'sport', TRUE );
				if ( !is_null( $meeting['sport'] ) && !Balitsa_Sports::exists( $meeting['sport'] ) )
					exit( 'sport' );
				$meeting['teams'] = Balitsa_Request::post( 'int', 'teams' );
				if ( $meeting['teams'] <= 0 )
					exit( 'teams' );
				$this->struct['meeting_list'][$meeting_key] = $meeting;
				$this->save();
				Balitsa::success( $this->metabox() );
			case 'metabox_meeting_update':
				if ( !$this->can_edit() )
					exit( 'role' );
				if ( is_null( $this->struct ) )
					exit( 'post' );
				$meeting_key = Balitsa_Request::get( 'int', 'meeting' );
				if ( !array_key_exists( $meeting_key, $this->struct['meeting_list'] ) )
					exit( 'meeting' );
				$meeting = &$this->struct['meeting_list'][$meeting_key];
				$meeting['datetime'] = Balitsa_Request::post( 'datetime' );
				$meeting['sport'] = Balitsa_Request::post( 'str', 'sport', TRUE );
				if ( !is_null( $meeting['sport'] ) && !Balitsa_Sports::exists( $meeting['sport'] ) )
					exit( 'sport' );
				$meeting['teams'] = Balitsa_Request::post( 'int', 'teams' );
				if ( $meeting['teams'] <= 0 )
					exit( 'teams' );
				$this->save();
				Balitsa::success( $this->metabox() );
			case 'metabox_meeting_delete':
				if ( !$this->can_edit() )
					exit( 'role' );
				if ( is_null( $this->struct ) )
					exit( 'post' );
				$meeting_key = Balitsa_Request::get( 'int', 'meeting' );
				if ( !array_key_exists( $meeting_key, $this->struct['meeting_list'] ) )
					exit( 'meeting' );
				if ( $meeting_key === $this->struct['meeting_key'] )
					exit( 'meeting' );
				unset( $this->struct['meeting_list'][$meeting_key] );
				$this->save();
				Balitsa::success( $this->metabox() );
			case 'metabox_lock':
				if ( !$this->can_edit() )
					exit( 'role' );
				if ( is_null( $this->struct ) )
					exit( 'post' );
				$this->struct['readonly'] = !$this->struct['readonly'];
				$this->save();
				Balitsa::success( $this->metabox() );
			case 'frontend_refresh':
				if ( !$this->can_view() )
					exit( 'role' );
				if ( is_null( $this->struct ) )
					exit( 'post' );
				Balitsa::success( $this->frontend() );
			case 'frontend_player_insert':
				if ( !$this->can_edit() )
					exit( 'role' );
				if ( is_null( $this->struct ) )
					exit( 'post' );
				if ( $this->struct['readonly'] )
					exit( 'post' );
				$meeting_key = Balitsa_Request::get( 'int', 'meeting' );
				if ( !array_key_exists( $meeting_key, $this->struct['meeting_list'] ) )
					exit( 'meeting' );
				$meeting = &$this->struct['meeting_list'][$meeting_key];
				$player_key = $meeting['player_ai']++;
				$player = [
					'player_key' => $player_key,
				];
				$player['user'] = Balitsa_Request::post( 'user', NULL, TRUE )?->ID;
				$player['name'] = Balitsa_Request::post( 'text', 'name', TRUE );
				if ( is_null( $player['name'] ) )
					$player['name'] = self::get_player_name( $player );
				if ( is_null( $player['name'] ) )
					exit( 'name' );
				$player['rank'] = Balitsa_Request::post( 'int', 'rank', TRUE );
				if ( is_null( $player['rank'] ) )
					$player['rank'] = self::get_player_rank( $player, $meeting['sport'] );
				$player['team'] = Balitsa_Request::post( 'int', 'team', TRUE );
				if ( !is_null( $player['team'] ) && $player['team'] < 0 )
					exit( 'team' );
				$player['turn'] = Balitsa_Request::post( 'int', 'turn', TRUE );
				$player['availability'] = Balitsa_Request::post( 'onoff', 'availability' );
				$player['timestamp'] = time();
				$meeting['player_list'][$player_key] = $player;
				$this->save();
				Balitsa::success( $this->frontend() );
			case 'frontend_player_update':
				if ( !$this->can_edit() )
					exit( 'role' );
				if ( is_null( $this->struct ) )
					exit( 'post' );
				if ( $this->struct['readonly'] )
					exit( 'post' );
				$meeting_key = Balitsa_Request::get( 'int', 'meeting' );
				if ( !array_key_exists( $meeting_key, $this->struct['meeting_list'] ) )
					exit( 'meeting' );
				$meeting = &$this->struct['meeting_list'][$meeting_key];
				$player_key = Balitsa_Request::get( 'int', 'player' );
				if ( !array_key_exists( $player_key, $meeting['player_list'] ) )
					exit( 'player' );
				$player = &$meeting['player_list'][$player_key];
				$player['user'] = Balitsa_Request::post( 'user', NULL, TRUE )?->ID;
				$player['name'] = Balitsa_Request::post( 'text', 'name', TRUE );
				if ( is_null( $player['name'] ) )
					$player['name'] = self::get_player_name( $player );
				if ( is_null( $player['name'] ) )
					exit( 'name' );
				$player['rank'] = Balitsa_Request::post( 'int', 'rank', TRUE );
				if ( is_null( $player['rank'] ) )
					$player['rank'] = self::get_player_rank( $player, $meeting['sport'] );
				$player['team'] = Balitsa_Request::post( 'int', 'team', TRUE );
				if ( !is_null( $player['team'] ) && $player['team'] < 0 )
					exit( 'team' );
				$player['turn'] = Balitsa_Request::post( 'int', 'turn', TRUE );
				$player['availability'] = Balitsa_Request::post( 'onoff', 'availability' );
				$this->save();
				Balitsa::success( $this->frontend() );
			case 'frontend_player_delete':
				if ( !$this->can_edit() )
					exit( 'role' );
				if ( is_null( $this->struct ) )
					exit( 'post' );
				if ( $this->struct['readonly'] )
					exit( 'post' );
				$meeting_key = Balitsa_Request::get( 'int', 'meeting' );
				if ( !array_key_exists( $meeting_key, $this->struct['meeting_list'] ) )
					exit( 'meeting' );
				$meeting = &$this->struct['meeting_list'][$meeting_key];
				$player_key = Balitsa_Request::get( 'int', 'player' );
				if ( !array_key_exists( $player_key, $meeting['player_list'] ) )
					exit( 'player' );
				unset( $meeting['player_list'][$player_key] );
				$this->save();
				Balitsa::success( $this->frontend() );
			case 'frontend_meeting_select':
				if ( !$this->can_edit() )
					exit( 'role' );
				if ( is_null( $this->struct ) )
					exit( 'post' );
				if ( $this->struct['readonly'] )
					exit( 'post' );
				if ( !is_null( $this->struct['meeting_key'] ) )
					exit( 'post' );
				$meeting = Balitsa_Request::get( 'int', 'meeting' );
				if ( !array_key_exists( $meeting, $this->struct['meeting_list'] ) )
					exit( 'meeting' );
				$this->struct['meeting_key'] = $meeting;
				$this->save();
				Balitsa::success( $this->frontend() );
			case 'frontend_meeting_unselect':
				if ( !$this->can_edit() )
					exit( 'role' );
				if ( is_null( $this->struct ) )
					exit( 'post' );
				if ( $this->struct['readonly'] )
					exit( 'post' );
				if ( is_null( $this->struct['meeting_key'] ) )
					exit( 'post' );
				$this->struct['meeting_key'] = NULL;
				$this->save();
				Balitsa::success( $this->frontend() );
			case 'frontend_meeting_shuffle':
				if ( !$this->can_edit() )
					exit( 'role' );
				if ( is_null( $this->struct ) )
					exit( 'post' );
				if ( $this->struct['readonly'] )
					exit( 'post' );
				if ( is_null( $this->struct['meeting_key'] ) )
					exit( 'post' );
				$meeting = &$this->struct['meeting_list'][$this->struct['meeting_key']];
				$turns = range( 0, count( $meeting['player_list'] ) - 1 );
				shuffle( $turns );
				foreach ( $meeting['player_list'] as &$player )
					$player['turn'] = array_pop( $turns );
				unset( $player );
				$this->save();
				Balitsa::success( $this->frontend() );
			case 'frontend_meeting_split':
				if ( !$this->can_edit() )
					exit( 'role' );
				if ( is_null( $this->struct ) )
					exit( 'post' );
				if ( $this->struct['readonly'] )
					exit( 'post' );
				if ( is_null( $this->struct['meeting_key'] ) )
					exit( 'post' );
				$meeting = &$this->struct['meeting_list'][$this->struct['meeting_key']];
				$cards = [];
				foreach ( $meeting['player_list'] as $player ) {
					$user = $player['user'];
					$user = get_user_by( 'ID', $user );
					if ( $user !== FALSE ) {
						$ranks = new Balitsa_Ranks( $user );
						$rank = $ranks->get( $meeting['sport'] );
					} else {
						$rank = $player['rank'];
					}
					if ( is_null( $rank ) )
						$rank = rand( Balitsa_Ranks::MIN, Balitsa_Ranks::MAX );
					$cards[] = [
						'key' => $player['player_key'],
						'rank' => $rank,
						'seed' => mt_rand() / mt_getrandmax(),
					];
				}
				usort( $cards, Balitsa::sorter( '~rank', 'seed' ) );
				$teams = [];
				foreach ( $cards as $card ) {
					if ( empty( $teams ) ) {
						$teams = range( 0, $meeting['teams'] - 1 );
						shuffle( $teams );
						$teams = array_merge( $teams, array_reverse( $teams ) );
					}
					$meeting['player_list'][$card['key']]['team'] = array_pop( $teams );
				}
				$this->save();
				Balitsa::success( $this->frontend() );
			case 'frontend_declare':
				if ( !is_user_logged_in() )
					exit( 'role' );
				if ( !$this->can_view() )
					exit( 'role' );
				if ( is_null( $this->struct ) )
					exit( 'post' );
				if ( $this->struct['readonly'] )
					exit( 'post' );
				if ( !is_null( $this->struct['meeting_key'] ) )
					exit( 'post' );
				$meeting_key = Balitsa_Request::get( 'int', 'meeting' );
				if ( !array_key_exists( $meeting_key, $this->struct['meeting_list'] ) )
					exit( 'meeting' );
				$meeting = &$this->struct['meeting_list'][$meeting_key];
				$availability = Balitsa_Request::get( 'str', 'availability' );
				if ( !in_array( $availability, [ 'yes', 'maybe', 'no' ], TRUE ) )
					exit( 'availability' );
				$player_key = $this->get_user_key( $meeting_key );
				if ( $availability !== 'no' ) {
					if ( is_null( $player_key ) ) {
						$player_key = $meeting['player_ai']++;
						$meeting['player_list'][$player_key] = [
							'player_key' => $player_key,
							'user' => get_current_user_id(),
							'name' => NULL,
							'rank' => NULL,
							'team' => NULL,
							'turn' => NULL,
							'availability' => NULL,
							'timestamp' => time(),
						];
					}
					$player = &$meeting['player_list'][$player_key];
					$player['name'] = self::get_player_name( $player );
					$player['rank'] = self::get_player_rank( $player, $meeting['sport'] );
					$player['availability'] = $availability === 'yes';
				} else {
					if ( !is_null( $player_key ) )
						unset( $meeting['player_list'][$player_key] );
				}
				$this->save();
				Balitsa::success( $this->frontend() );
			case 'frontend_stat':
				if ( !is_user_logged_in() )
					exit( 'role' );
				if ( !$this->can_view() )
					exit( 'role' );
				if ( is_null( $this->struct ) )
					exit( 'post' );
				if ( $this->struct['readonly'] )
					exit( 'post' );
				if ( is_null( $this->struct['meeting_key'] ) )
					exit( 'post' );
				$meeting_key = $this->struct['meeting_key'];
				$meeting = &$this->struct['meeting_list'][$meeting_key];
				$player_key = $this->get_user_key( $meeting_key );
				if ( is_null( $player_key ) )
					exit( 'role' );
				$player = &$meeting['player_list'][$player_key];
				$sport = Balitsa_Sports::select( $meeting['sport'] );
				if ( is_null( $sport ) )
					exit( 'post' );
				$stat_key = Balitsa_Request::get( 'word', 'stat' );
				if ( !array_key_exists( $stat_key, $sport['stats'] ) )
					exit( 'stat' );
				if ( !array_key_exists( 'stats', $player ) )
					$player['stats'] = [];
				$player['stats'][$stat_key] = Balitsa_Request::get( 'int', 'value' );
				if ( $player['stats'][$stat_key] < 0 )
					exit( 'value' );
				$this->save();
				Balitsa::success( $this->frontend() );
			case 'frontend_mvp':
				if ( !is_user_logged_in() )
					exit( 'role' );
				if ( !$this->can_view() )
					exit( 'role' );
				if ( is_null( $this->struct ) )
					exit( 'post' );
				if ( $this->struct['readonly'] )
					exit( 'post' );
				if ( is_null( $this->struct['meeting_key'] ) )
					exit( 'post' );
				$meeting_key = $this->struct['meeting_key'];
				$meeting = &$this->struct['meeting_list'][$meeting_key];
				$player_key = $this->get_user_key( $meeting_key );
				if ( is_null( $player_key ) )
					exit( 'role' );
				$player = &$meeting['player_list'][$player_key];
				$mvp = Balitsa_Request::get( 'int', 'player', TRUE );
				if ( !is_null( $mvp ) && !array_key_exists( $mvp, $meeting['player_list'] ) )
					exit( 'player' );
				$player['mvp'] = $mvp;
				$this->save();
				Balitsa::success( $this->frontend() );
			default:
				exit( 'task' );
		}
	}

	// callbacks

	public static function callback(): void {
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' )
			exit( 'method' );
		$post = Balitsa_Request::get( 'post' );
		$task = Balitsa_Request::get( 'str', 'task' );
		$struct = new self( $post );
		$struct->ajax( $task );
	}
}

add_action( 'add_meta_boxes', function( string $post_type, WP_Post $post ): void {
	if ( $post_type !== 'post' )
		return;
	$struct = new Balitsa_Struct( $post );
	if ( !$struct->can_edit() )
		return;
	add_meta_box( 'balitsa', __( 'Balitsa', 'balitsa' ), [ $struct, 'metabox_echo' ], NULL, 'side' );
}, 10, 2 );

add_action( 'admin_enqueue_scripts', function( string $hook_suffix ): void {
	if ( !in_array( $hook_suffix, [ 'post.php', 'post-new.php', ], TRUE ) )
		return;
	wp_enqueue_style( 'balitsa-flex', Balitsa::url( 'flex.css' ), [], Balitsa::version() );
	wp_enqueue_style( 'balitsa-tree', Balitsa::url( 'tree.css' ), [], Balitsa::version() );
	wp_enqueue_script( 'balitsa-script', Balitsa::url( 'script.js' ), [ 'jquery' ], Balitsa::version() );
} );

add_filter( 'the_content', function( string $content ): string {
	$post = get_post();
	if ( is_null( $post ) )
		return $content;
	$struct = new Balitsa_Struct( $post );
	return $content . $struct->frontend();
} );

add_action( 'wp_enqueue_scripts', function(): void {
	wp_enqueue_style( 'balitsa-style', Balitsa::url( 'style.css' ), [], Balitsa::version() );
	wp_enqueue_script( 'balitsa-script', Balitsa::url( 'script.js' ), [ 'jquery' ], Balitsa::version() );
} );

if (
	defined( 'DOING_AJAX' ) && DOING_AJAX
	&& isset( $_GET['action'] ) && $_GET['action'] === 'balitsa_struct'
	&& isset( $_GET['task'] ) && str_starts_with( $_GET['task'], 'frontend_' )
) {
	add_filter( 'pre_determine_locale', 'get_locale' );
}

add_action( 'wp_ajax_' . 'balitsa_struct', [ 'Balitsa_Struct', 'callback' ] );
add_action( 'wp_ajax_nopriv_' . 'balitsa_struct', [ 'Balitsa_Struct', 'callback' ] );
