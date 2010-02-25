<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Sentry Installation controller
 */
class Controller_Install extends Controller_Template_Auth_Install {

	/**
	 * Set tables
	 */
	public function before() {
		Sink::instance()->table('users');
	}

}

