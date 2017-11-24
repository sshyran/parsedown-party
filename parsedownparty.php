<?php

/*
Plugin Name: Parsedown Party
Plugin URI: https://github.com/connerbw/parsedown-party
Description: Markdown editing for WordPress
Version: 0.0.1
Author: KIZU514
Author URI: https://kizu514.com
License: GPLv2
*/


require_once( __DIR__ . '/inc/class-parsedownparty.php' ); // TODO: Use autoloader

add_action( 'init', [ '\Kizu514\ParsedownParty', 'init' ] );
