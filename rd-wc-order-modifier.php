<?php 
/* Plugin Name: RD Order Modifier for WooCommerce
Plugin URI: 
Description: Allows editing order items pricing inclusive of tax.
Version: 1.1.1
Author: Robot Dwarf
Author URI: https://www.robotdwarf.com
WC requires at least: 4.7.2
WC tested up to: 9.2.3
Requires PHP: 7.2
Requires at least: 5.0
License: GPLv2 or later
Text Domain: rdwcom
Domain Path: /languages
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2010-2023 Robot Dwarf.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'RDWCOM_VERSION', '1.1.1' );
define('RDWCOM_URL', plugin_dir_url( __FILE__ ) );
define( 'RDWCOM_PATH', plugin_dir_path( __FILE__ ) );
define( 'RDWCOM_PLUGIN_FILE', __FILE__ );
define( 'RDWCOM_API_URL', 'https://www.robotdwarf.com/wp-json/robotdwarf/v1/' );

require( RDWCOM_PATH . '/include.php' );
if ( method_exists( 'RDWCOM_Manager', 'load' ) ) {
	RDWCOM_Manager::load( __FILE__ );

	register_activation_hook( RDWCOM_PLUGIN_FILE, array( 'RDWCOM_Manager', 'activate' ) );
}
