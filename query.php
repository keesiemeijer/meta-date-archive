<?php
class Meta_Date_Archive_Query {

	public $start;
	public $end;
	public $dates;
	public $post_type;
	public $filters;
	public $orderby_key;


	function __construct() {
		add_action( 'wp_loaded', array( $this, 'setup' ) );
	}


	/**
	 * Sets up properties for a meta date query.
	 *
	 * @since 1.0
	 * @return void
	 */
	public function setup() {

		if ( is_admin() ) {
			return;
		}

		$this->start     = apply_filters( 'meta_date_archive_start',     'event_start_date' );
		$this->end       = apply_filters( 'meta_date_archive_end',       'event_end_date' );
		$this->post_type = apply_filters( 'meta_date_archive_post_type', 'post' );

		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 70 );
		add_filter( 'query_vars',    array( $this, 'query_vars' ) );
		add_action( 'save_post',     array( $this, 'save_post' ) );
	}


	/**
	 * Meta date query.
	 *
	 * @since 1.0
	 * @param object  $query The WP_Query instance.
	 * @return void
	 */
	public function pre_get_posts( $query ) {

		$this->dates       = array();
		$this->orderby_key = '';
		$this->filters     = "meta_date_archive";

		$start_date      = $query->get( 'meta_archive_start_date' );
		$end_date        = $query->get( 'meta_archive_end_date' );
		$dates           = array();
		$meta_date_query = false;

		if ( $query->is_main_query() ) {
			// main queries

			if ( is_date() ) {
				// main query for date archive
				$dates           = meta_date_archive_dates();
				$meta_date_query = true;
			}
		} else {
			// external queries

			if ( !empty( $start_date ) || !empty( $end_date )  ) {
				// one or both of the dates is provided
				$dates = meta_date_archive_start_end_date( $start_date, $end_date );
				$meta_date_query = true;
			} else {
				// not the query we're looking for
				return;
			}
		}

		// not a meta date query
		if ( !$meta_date_query ) {
			return;
		}

		if ( isset( $query->query_vars['suppress_filters'] ) ) {
			$this->filters = $query->query_vars['suppress_filters'];
		}

		// we need the filters for a meta date query
		unset( $query->query_vars['suppress_filters'] );

		if ( empty( $dates ) ) {

			// return no posts found
			//
			// not a date archives main query
			// or an external query with dates where the dates didn't validate
			// or an external query where one of the dates is missing

			$this->empty_query();
			$this->cleanup_query();
			return;
		}

		$this->dates = $dates;
		$query->set( 'meta_date_archive_query', 1 );

		$reset_query_vars = array(
			'second' , 'minute', 'hour',
			'day', 'monthnum', 'year',
			'w', 'm', 'meta_query'
		);

		// reset query vars.
		foreach ( $reset_query_vars as $var ) {
			$query->set( $var, '' );
		}

		$query->set( 'post_type', $this->post_type );

		$meta_query =  array(
			'relation' => 'AND',

			// needs to be two meta key arrays
			array(
				'key'       => $this->start,
				'compare'   => '>=',
				'value'     => $this->dates['start_date'],
				'type'      => 'DATE'
			),
			array(
				'key'       => $this->end,
				'compare'   => '<=',
				'value'     => $this->dates['end_date'],
				'type'      => 'DATE'
			),
		);

		$query->set( 'meta_query', $meta_query );

		// orderby 'meta_key'
		$this->orderby_key = $query->get( 'meta_key' );

		// add the filter
		add_filter( 'get_meta_sql', array( $this, 'get_meta_sql' ), 10, 2 );

		// remove the filters down the road
		$this->cleanup_query();
	}


	/**
	 * Returns meta date archive meta sql.
	 *
	 * @since 1.0
	 * @param array   $pieces  Array containing the query's JOIN and WHERE clauses.
	 * @param array   $queries Array of meta queries.
	 * @return array Array containing the query's JOIN and WHERE clauses.
	 */
	public function get_meta_sql( $pieces, $queries ) {
		global $wpdb;

		$start_date  = $this->dates['start_date'];
		$end_date    = $this->dates['end_date'];
		$where       = "";
		$start_alias = "$wpdb->postmeta";
		$end_alias   = "mt1";
		$join        = " INNER JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id)
		                INNER JOIN $wpdb->postmeta AS mt1 ON ( $wpdb->posts.ID = mt1.post_id )";


		if ( !empty( $this->orderby_key ) ) {
			$where .= $wpdb->prepare( " AND ($wpdb->postmeta.meta_key = %s)", $this->orderby_key );
			$join .= " INNER JOIN $wpdb->postmeta AS mt2 ON ($wpdb->posts.ID = mt2.post_id)";
			$start_alias = "mt1";
			$end_alias   = "mt2";
		}

		$start_key = $wpdb->prepare( "{$start_alias}.meta_key = %s", $this->start );
		$end_key   = $wpdb->prepare( "{$end_alias}.meta_key = %s", $this->end );

		// after start date AND before end date
		$query = " AND ( (
        ( $start_key AND ( CAST($start_alias.meta_value AS DATE) >= %s) )
            AND ( $end_key AND ( CAST($end_alias.meta_value AS DATE) <= %s) )
        )";
		$where .= $wpdb->prepare( $query, $start_date, $end_date );

		// OR before start date AND after end end date
		$query = " OR (
        ( $start_key AND ( CAST($start_alias.meta_value AS DATE) <= %s) )
            AND ( $end_key AND ( CAST($end_alias.meta_value AS DATE) >= %s) ))";
		$where .= $wpdb->prepare( $query, $start_date, $end_date );

		// OR before start date AND (before end date AND end date after start date)
		$query = " OR (
        ( $start_key AND ( CAST($start_alias.meta_value AS DATE) <= %s) )
        AND ( $end_key
            AND ( CAST($end_alias.meta_value AS DATE) <= %s )
            AND ( CAST($end_alias.meta_value AS DATE) >= %s )
        ))";
		$where .= $wpdb->prepare( $query, $start_date, $end_date, $start_date );

		// OR after end date AND (after start date AND start date before end date) )
		$query = "OR (
        ( $end_key AND ( CAST($end_alias.meta_value AS DATE) >= %s ) )
            AND ( $start_key AND ( CAST($start_alias.meta_value AS DATE) >= %s )
            AND ( CAST($start_alias.meta_value AS DATE) <= %s )
        )))";
		$where .= $wpdb->prepare( $query, $end_date, $start_date, $end_date );

		$pieces['join']  = $join;
		$pieces['where'] = $where;

		return $pieces;
	}


	/**
	 * Adds the end and start date variables to the public query variables.
	 *
	 * @since 1.0
	 * @param array   $query_vars The array of whitelisted query variables.
	 * @return array The array of whitelisted query variables.
	 */
	public function query_vars( $query_vars ) {
		$query_vars[] = 'meta_date_archive_query';
		$query_vars[] = 'meta_archive_start_date';
		$query_vars[] = 'meta_archive_end_date';
		return $query_vars;
	}


	/**
	 * Adds a filter to 'the_posts' to remove filters used in pre_get_posts.
	 *
	 * @since 1.0
	 * @param object  $query The WP_Query instance.
	 * @return [type]        [description]
	 */
	private function cleanup_query() {
		add_filter( 'the_posts', array( $this, 'the_posts' ), 99, 2 );
	}


	/**
	 * Removes filters used in pre_get_posts.
	 * Restores the suppress_filters parameter if it was used.
	 *
	 * @since 1.0
	 * @param array   $posts Array with post objects.
	 * @return array Array with post objects.
	 */
	public function the_posts( $posts, $_this ) {

		if ( 'meta_date_archive' !== $this->filters ) {
			$_this->query_vars['suppress_filters'] = $this->filters;
		}

		remove_filter( 'get_meta_sql', array( $this, 'get_meta_sql' ), 10, 2 );
		remove_filter( 'posts_where',  array( $this, 'posts_where' ) );
		remove_filter( 'the_posts',    array( $this, 'the_posts' ) );
		return $posts;
	}


	/**
	 * Adds a filter to 'posts_where' to have the query return no posts.
	 *
	 * @since 1.0
	 * @return void
	 */
	private function empty_query() {
		add_filter( 'posts_where', array( $this, 'posts_where' ) );
	}


	/**
	 * Adds 1=0 to the where clause to have the query return no posts.
	 *
	 * @since 1.0
	 * @return void
	 */
	public function posts_where( $where ) {
		return $where . 'AND 1 = 0';
	}


	/**
	 * Adds the start date meta if only end date meta is provided when editing or publishing a post.
	 *
	 * @since 1.0
	 * @return void
	 */
	public function save_post( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( !current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		$post_type = ( isset( $_POST['post_type'] ) ) ?  $_POST['post_type'] : '';

		if ( $this->post_type !== $post_type ) {
			return $post_id;
		}

		$end_date = get_post_meta( $post_id, $this->end, true );
		if ( !empty( $end_date ) ) {
			if ( !get_post_meta( $post_id, $this->start, true ) ) {
				update_post_meta( $post_id, $this->start, $end_date );
			}
		}
	}

}

$meta_date_archive = new Meta_Date_Archive_Query();