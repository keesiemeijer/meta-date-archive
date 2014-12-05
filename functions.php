<?php
/**
 * Returns start and end dates for date archives.
 *
 * @since 1.0
 * @return array Array with start and end date.
 */
function meta_date_archive_dates() {
	$year = $month = $day = $m = false;
	$start_date = $end_date = '';

	// is date archives
	if ( is_date() ) {
		$m     = ( get_query_var( 'm' ) ) ? get_query_var( 'm' ) : false;
		$year  = ( get_query_var( 'year' ) ) ? get_query_var( 'year' ) : false;
		$month = ( get_query_var( 'monthnum' ) ) ? zeroise( get_query_var( 'monthnum' ), 2 ) : false;
		$day   = ( get_query_var( 'day' ) ) ? get_query_var( 'day' ) : false;
		if ( $m ) {
			$dates = meta_date_archive_getdate( $m );
			$year  = $dates['year'];
			$month = $dates['month'];
			$day   = $dates['day'];
		}

		return meta_date_archive_validated_dates( $year, $month, $day );
	}

	return array();
}


/**
 * Returns start and end dates.
 *
 * @since 1.0
 * @param int     $start Date.
 * @param int     $end   Date.
 * @return array Array with start and end date.
 */
function meta_date_archive_start_end_date( $start = '', $end = '' ) {

	$year = $month = $day = $m = false;
	$start_date = $end_date = '';

	$start = absint( $start ); // int
	$end   = absint( $end ); // int

	// if not both dates are provided
	if ( !( $start && $end ) ) {
		return array();
	}

	$dates  = meta_date_archive_getdate( $start );
	$dates  = meta_date_archive_validated_dates( $dates['year'], $dates['month'], $dates['day'] );
	$start_date = isset( $dates['start_date'] ) ? $dates['start_date'] : '';

	$dates = meta_date_archive_getdate( $end );
	$dates = meta_date_archive_validated_dates( $dates['year'], $dates['month'], $dates['day'] );
	$end_date = isset( $dates['end_date'] ) ? $dates['end_date'] : '';

	if ( !empty( $start_date ) && !empty( $end_date ) ) {
		return compact( 'start_date', 'end_date' );
	}

	return array();
}


/**
 * Returns year, month or day from date .
 *
 * @since 1.0
 * @param string  $date Date.
 * @return array Array with year, month and day.
 */
function meta_date_archive_getdate( $date = '' ) {
	$year = $month = $day = false;
	switch ( strlen( $date ) ) {
	case 4: // Yearly
		$year = substr( $date, 0, 4 );
		break;
	case 6: // Monthly
		$year = substr( $date, 0, 4 );
		$month = substr( $date, 4, 2 );
		break;
	case 8: // Daily
		$year = substr( $date, 0, 4 );
		$month = substr( $date, 4, 2 );
		$day = substr( $date, 6, 2 );
		break;
	}

	return compact( 'year', 'month', 'day' );
}


/**
 * Returns full date validated.
 *
 * @since 1.0
 * @param integer $year  Year
 * @param integer $month Month
 * @param integer $day   Day
 * @return string Validated date.
 */
function meta_date_archive_validated_dates( $year = 0, $month = 0, $day = 0 ) {
	$start_date = $end_date = '';

	$year = absint( $year );
	$month = absint( $month );
	$day = absint( $day );

	if ( $year ) {
		// check if date exists
		if ( checkdate( '01', '01', $year ) ) {
			$start_date = $year . '0101';
			$end_date   = $year . '1231';
		}
	}

	if ( $year && $month ) {
		$month = zeroise( $month, 2 );
		// check if date exists
		if ( checkdate( $month, '01', $year ) ) {
			$start_date =  $year . $month . '01';
			$end_date   =  date( 'Ymt', mktime( 23, 59, 59, $month, 1, $year ) ); // 't' gets the last day
		}
	}

	if ( $year && $month && $day ) {
		$month = zeroise( $month, 2 );
		$day   = zeroise( $day, 2 );
		// check if date exists
		if ( checkdate( $month, $day, $year ) ) {
			$start_date =  $year . $month . $day;
			$end_date   =  $start_date;
		}
	}

	if ( empty( $start_date ) || empty( $end_date ) ) {
		return array();
	}

	return compact( 'start_date', 'end_date' );
}