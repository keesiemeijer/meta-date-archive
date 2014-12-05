<?php
/*
Plugin Name: Meta Date Archive
Version: 1.0
Plugin URI:
Description: Date archives from custom field ranges
Author: keesiemijer
Author URI:
License: GPL v2

Meta Date Archive
Copyright 2013  Kees Meijer  (email : keesie.meijer@gmail.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version. You may NOT assume that you can use any other version of the GPL.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/* loads plugin files, adds the shortcode and sets the text domain */
if ( !function_exists( 'meta_date_archive_init' ) ) {

	function meta_date_archive_init() {

		// include files only needed on the front end
		if ( !is_admin() ) {

			// functions to retrieve related posts from the database
			require_once plugin_dir_path( __FILE__ ) . 'functions.php';

			// functions for display of the related post thumbnail gallery
			require_once plugin_dir_path( __FILE__ ) . 'query.php';

		}
	}

	/* initialize plugin */
	meta_date_archive_init();

} // !function_exists