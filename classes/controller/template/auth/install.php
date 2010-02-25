<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Auth install controller (Sentry module)
 */
abstract class Controller_Template_Auth_Install extends Controller_Template_Sink {

	/**
	 * Overload index() action to perform root user install
	 */
	public function action_index() {
		parent::action_index();
		$this->action_root();
	}

	/**
	 * Create root user
	 */
	public function action_root() {
		echo '<h1>Root Account:</h1>';

		$pass = text::random('alnum', 8);
		$user = Sprig::factory('user')->values(array(
			'username' => 'root',
			'email'    => 'root@domain.com',
			'password' => $pass,
			'password_confirm' => $pass,
			'role'     => 'admin',
		));

		try
		{
			$user->create();
			echo 'Root user created, password is '.$pass.'.';
		}
		catch (Exception $e)
		{
			echo 'Error creating root user.';
			throw $e;
		}
	}

}

