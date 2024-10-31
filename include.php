<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

foreach ( array_diff( scandir( RDWCOM_PATH . '/classes' ), array( '..', '.' ) ) as $rdwcom_filename ) {
	if ( substr( $rdwcom_filename, 0, 6 ) == 'class.' || substr( $rdwcom_filename, 0, 10 ) == 'interface.' ) {
	  require( RDWCOM_PATH . '/classes/' . $rdwcom_filename );
	}
}
