# Meta Date Archive

version:      1.2  
Tested up to: 4.2.2  

This plugin replaces the default date archives with archives for posts that have a start and end date custom field. Or use it to [query for posts between two date custom fields](#custom-queries).

[Enable the custom fields archive](#enable-the-custom-fields-archive) and add a start and end date custom field to your posts for them to show up in the date archives.

**Note**: If you only provide **one** of the date custom field when saving a post the plugin automatically saves the other date custom field with the same date. 

It finds posts with overlapping start and end dates. For example if you're on a date archive for the month of may (`example.com/2015/05`) it displays posts that have the custom field values:

 * start date in may and end date in may.
 * start date in may and end date after may.
 * start date before may and end date in may.
 * start date before may and end date after may.

The format for the start and end date custom field values is `YYYYMMDD` (Year Month Day). Example 20150521.

## Registering custom field keys
The start and end date custom field keys are by default `meta_start_date` and `meta_end_date`.

To register your own start and end date custom field keys use this in your (child) theme's functions.php file.

 ```php
add_filter( 'meta_date_archive_start', 'meta_date_archive_start_key' );
add_filter( 'meta_date_archive_end',   'meta_date_archive_end_key' );

function meta_date_archive_start_key( $key ) {
	// Return your custom field start date key
	return 'start_date';
}

function meta_date_archive_end_key( $key ) {
	// Return your custom field end date key
	return 'end_date';
}
```

## Enable the custom fields archive.
The custom fields archive is not enabled by default. Put this in your (child) theme's functions.php file to enable it.
```php
add_filter( 'meta_date_archives', '__return_true' );
```

Or enable it from the `pre_get_posts` action.
```php
function enable_custom_field_archive( $query ) {

	if ( !is_admin() && $query->is_main_query() ) {

		if ( is_date() ) {
			// date archives
			$query->set( 'meta_date_archives', 1 );

			// set your own query vars here
			$query->set( 'meta_key', 'end_date' ); // your end date meta key
			$query->set( 'orderby',  'meta_value' ); // meta_value or meta_value_num
			$query->set( 'order',    'ASC' ); // ASC or DESC			
		}
	}
}

add_action( 'pre_get_posts', 'enable_custom_field_archive' );
```

## Custom Queries
Use the query variables `meta_archive_start_date` and `meta_archive_end_date` for the start and end dates. If you provide only one of the two the plugin assumes both start and end dates are the same.

Example:
```php
<?php
$args = array (
	'meta_archive_start_date' => '20150601',
	'meta_archive_end_date'   => '20150630',

	// set your own query vars here
	'post_type'      => 'post',
	'posts_per_page' => 10,
	'meta_key'       => 'end_date', // your end date meta key
	'orderby'        => 'meta_value', // meta_value or meta_value_num
	'order'          => 'ASC', // ASC or DESC
);

$date_query = new WP_Query( $args );
?>
```

## Calendar Example
This example shows how you would show your dates in a calender using the [SimpleCalendar class](https://donatstudios.com/SimpleCalendar)

### Step one
Include the SimpleCalendar.php and SimpleCalendar.css in your theme.

### Step two
Put this in the theme template where you want to show the calendar. In this example the custom field keys are `start_date` and `end_date`

```php
<?php
$args = array (
	// Get posts for the month June.
	'meta_archive_start_date' => '20150601',
	'meta_archive_end_date'   => '20150630',

	// Show all posts in the calendar.
	'posts_per_page' => -1,

	// Set your own query vars here.
	'post_type'      => 'post',
	'meta_key'       => 'end_date', // if your start date key is end_date
	'orderby'        => 'meta_value', // meta_value or meta_value_num
	'order'          => 'ASC', // ASC or DESC
);

$date_posts = get_posts( $args );

if ( $date_posts ) {

	// Add year and month to the calendar.
	$calendar = new donatj\SimpleCalendar('2015-06');

	// Set the start of the week.
	$calendar->setStartOfWeek( 'Sunday' );

	foreach ( $date_posts as $post ) {

		// Get the dates.
		$start = get_post_meta( $post->ID, 'start_date', true );
		$end   = get_post_meta( $post->ID, 'end_date', true );

		// Add the dates to the calendar.
		$calendar->addDailyHtml( $post->post_title, $start, $end );
	}

	// Show the calendar.
	$calendar->show( true );
}
?>```
