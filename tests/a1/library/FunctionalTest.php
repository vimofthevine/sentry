<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * A1 library unit tests (Sentry module modifications)
 *
 * @author  Kyle Treubig
 * @group   sentry
 */
class A1_Library_FunctionalTest extends PHPUnit_Framework_TestCase {

	/**
	 * Setup the test case
	 * - Create MySQL users table
	 * - Clear any set cookies
	 */
	protected function setUp() {
		// Use unit test database
		Kohana::config('database')->default['connection']['database'] = "unit_test";
		// Import schema file
		$users_schema = Kohana::find_file('queries/schemas', 'users', 'sql');
		$users_sql = file_get_contents($users_schema);
		try
		{
			DB::query(Database::INSERT, $users_sql)->execute();
		}
		catch (Database_Exception $e)
		{
			echo $e->getMessage();
		}

		cookie::delete('a1_a1_autologin');
	}

	/**
	 * Clean up the test case
	 * - Destroy the session
	 * - Drop the MySQL table
	 */
	protected function tearDown() {
		$session = Session::instance(Kohana::config('a1.session_type'));
		$session->delete('a1_a1');
		DB::query(Database::DELETE, 'DROP TABLE users')->execute();
	}

	/**
	 * Test finding the salt
	 */
	public function testFindSalt() {
		$config = Kohana::config('a1');
		$salt_pattern = is_array($config['salt_pattern'])
			? $config['salt_pattern']
			: preg_split('/,\s*/', $config['salt_pattern']);
		$salt_length = count($salt_pattern);
		$salt = substr('s0m3r&ndoms&l7', 0, $salt_length);

		$hashed = A1::instance('a1')->hash_password('some_pass', $salt);
		$found = A1::instance('a1')->find_salt($hashed);
		$this->assertEquals($salt, $found);
	}

	/**
	 * Test hashing the password
	 */
	public function testHashPassword() {
		$pass = 'some_pass';
		$hashed = A1::instance('a1')->hash_password($pass);
		$salt = A1::instance('a1')->find_salt($hashed);
		$redo = A1::instance('a1')->hash_password($pass, $salt);
		$this->assertEquals($hashed, $redo);
	}

	/**
	 * Test hash function
	 */
	public function testHash() {
		Kohana::config('a1')->hash_method = 'sha1';
		$pass = 'some_pass';
		$a1 = A1::instance('a1')->hash($pass);
		$sha1 = sha1($pass);
		$this->assertEquals($sha1, $a1);
	}

	/**
	 * Test login with empty values
	 */
	public function testLoginEmptyValues() {
		$result = A1::instance('a1')->login('','');
		$this->assertFalse($result);
	}

	/**
	 * Test login with empty username
	 */
	public function testLoginEmptyUsername() {
		$result = A1::instance('a1')->login('', 'some_pass');
		$this->assertFalse($result);
	}

	/**
	 * Test login with wrong username
	 */
	public function testLoginWrongUsername() {
		$result = A1::instance('a1')->login('no_name', 'some_pass');
		$this->assertFalse($result);
	}

	/**
	 * Test login with empty password
	 */
	public function testLoginEmptyPassword() {
		$result = A1::instance('a1')->login('some_name', '');
		$this->assertFalse($result);
	}

	/**
	 * Test login with wrong password
	 */
	public function testLoginWrongPassword() {
		$pass = A1::instance('a1')->hash_password('test_pass');
		DB::insert('users', array('username','password'))
			->values(array('wrong_pass_user', $pass))->execute();
		$result = A1::instance('a1')->login('wrong_pass_user', 'other_pass');
		$this->assertFalse($result);
	}

	/**
	 * Test login success
	 */
	public function testLoginSuccess() {
		$pass = A1::instance('a1')->hash_password('test_pass');
		DB::insert('users', array('username','password'))
			->values(array('login_success_user', $pass))->execute();
		$result = A1::instance('a1')->login('login_success_user', 'test_pass');
		$this->assertType('object', $result);

		$session = Session::instance(Kohana::config('a1.session_type'));
		$user = $session->get('a1_a1');
		$this->assertType('object', $user);
		$this->assertEquals('login_success_user', $user->username);
		$this->assertEquals($user->username, $result->username);
	}

	/**
	 * Test login success with cookie (remember me)
	 */
	/*
	public function testLoginSuccessWithCookie() {
		$pass = A1::instance('a1')->hash_password('test_pass');
		DB::insert('users', array('username','password'))
			->values(array('login_cookie_user', $pass))->execute();

		$result = A1::instance('a1')->login('login_cookie_user', 'test_pass', TRUE);
		$this->assertType('object', $result);

		// TODO need to get cookies written
		$token = cookie::get('a1_a1_autologin', FALSE);
		$this->assertType('string', $token);
		$token = explode(".", $token);
		$user = Sprig::factory('user', array('username' => 'login_cookie_user'))->load();

		$this->assertEquals(2, count($token));
		$this->assertEquals($user->id, $token[1]);
		$this->assertEquals($user->token, $token[0]);
	}
	 */

	/**
	 * Test login with user model object
	 */
	public function testLoginWithUserObject() {
		$pass = A1::instance('a1')->hash_password('test_pass');
		DB::insert('users', array('username','password'))
			->values(array('login_object_user', $pass))->execute();

		$user = Sprig::factory('user', array('username'=>'login_object_user'))->load();
		$this->assertTrue($user->loaded());

		$result = A1::instance('a1')->login($user, 'test_pass');
		$this->assertType('object', $result);
	}

	/**
	 * Test logout as guest
	 */
	public function testLogoutAsGuest() {
		$result = A1::instance('a1')->logout();
		$this->assertTrue($result);
	}

	/**
	 * Test logout as user
	 */
	public function testLogoutAsUser() {
		$user = (object) array('username' => 'logout_user');
		$session = Session::instance(Kohana::config('a1.session_type'));
		$session->set('a1_a1', $user);
		$sess = $session->get('a1_a1');
		$this->assertType('object', $sess);

		$result = A1::instance('a1')->logout();
		$this->assertTrue($result);

		$sess = $session->get('a1_a1', FALSE);
		$this->assertFalse($sess);
	}

	/**
	 * Test logout with cookie
	 */
	public function testLogoutWithCookie() {
		$_COOKIE['a1_a1_autologin'] = cookie::salt('a1_a1_autologin', '1234.2').'~1234.2';
		$this->assertType('string', cookie::get('a1_a1_autologin'));

		$result = A1::instance('a1')->logout();
		$this->assertTrue($result);

		$cookie = cookie::get('a1_a1_autologin', FALSE);
		$this->assertFalse($cookie);
	}

	/**
	 * Test getting user when guest
	 */
	public function testGetUserWhenGuest() {
		$result = A1::instance('a1')->get_user();
		$this->assertFalse($result);
	}

	/**
	 * Test getting user when user
	 */
	public function testGetUserWhenUser() {
		DB::insert('users', array('username'))
			->values(array('get_user'))->execute();
		$user = Sprig::factory('user', array('username'=>'get_user'))->load();

		$session = Session::instance(Kohana::config('a1.session_type'));
		$session->set('a1_a1', $user);
		$sess = $session->get('a1_a1');
		$this->assertType('object', $sess);

		$result = A1::instance('a1')->get_user();
		$this->assertType('object', $result);
		$this->assertEquals('get_user', $result->username);
	}

	/**
	 * Test getting user from cookie
	 */
	public function testGetUserFromCookie() {
		DB::insert('users', array('id','username','token'))
			->values(array(2,'get_user_cookie_user', 1234))->execute();

		$_COOKIE['a1_a1_autologin'] = cookie::salt('a1_a1_autologin', '1234.2').'~1234.2';
		$this->assertType('string', cookie::get('a1_a1_autologin'));

		$result = A1::instance('a1')->get_user();
		$this->assertType('object', $result);
		$this->assertEquals('get_user_cookie_user', $result->username);

		$session = Session::instance(Kohana::config('a1.session_type'));
		$user = $session->get('a1_a1');
		$this->assertType('object', $user);
		$this->assertEquals('get_user_cookie_user', $user->username);
		$this->assertEquals($user->username, $result->username);
	}

	/**
	 * Test logged in when guest
	 */
	public function testLoggedInWhenGuest() {
		$result = A1::instance('a1')->logged_in();
		$this->assertFalse($result);
	}

	/**
	 * Test logged in when user
	 */
	public function testLoggedInWhenUser() {
		DB::insert('users', array('username'))
			->values(array('logged_in_user'))->execute();
		$user = Sprig::factory('user', array('username'=>'logged_in_user'))->load();

		$session = Session::instance(Kohana::config('a1.session_type'));
		$session->set('a1_a1', $user);
		$sess = $session->get('a1_a1');
		$this->assertType('object', $sess);

		$result = A1::instance('a1')->logged_in();
		$this->assertTrue($result);
	}

	/**
	 * Test logged in from cookie
	 */
	public function testLoggedInFromCookie() {
		DB::insert('users', array('id','username','token'))
			->values(array(2,'logged_in_cookie_user', 1234))->execute();

		$_COOKIE['a1_a1_autologin'] = cookie::salt('a1_a1_autologin', '1234.2').'~1234.2';
		$this->assertType('string', cookie::get('a1_a1_autologin'));

		$result = A1::instance('a1')->logged_in();
		$this->assertTrue($result);

		$session = Session::instance(Kohana::config('a1.session_type'));
		$user = $session->get('a1_a1');
		$this->assertType('object', $user);
		$this->assertEquals('logged_in_cookie_user', $user->username);
		$this->assertEquals(2, $user->id);
	}

}

