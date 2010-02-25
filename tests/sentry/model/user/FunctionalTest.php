<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Sentry module user model functional tests
 *
 * @author  Kyle Treubig
 * @group   sentry
 * @group   sentry.model.user
 */
class Sentry_Model_User_FunctionalTest extends PHPUnit_Framework_TestCase {

	/**
	 * Setup the test case
	 * - Create MySQL users table
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
	}

	/**
	 * Clean up the test case
	 * - Drop the MySQL table
	 */
	protected function tearDown() {
		DB::query(Database::DELETE, 'DROP TABLE users')->execute();
	}

	/**
	 * Test validation of empty fields
	 */
	public function testValidateEmptyFields() {
		$user = Sprig::factory('user');
		$values = array(
			'username' => '',
			'password' => '',
			'password_confirm' => '',
			'email' => '',
		);

		try
		{
			$user->check($values);
			$errors = array();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors();
		}

		$this->assertArrayHasKey('username', $errors);
		$this->assertContains('not_empty', $errors['username']);
		$this->assertArrayHasKey('password', $errors);
		$this->assertContains('not_empty', $errors['password']);
		$this->assertArrayNotHasKey('password_confirm', $errors);
		$this->assertArrayHasKey('email', $errors);
		$this->assertContains('not_empty', $errors['email']);
	}

	/**
	 * Test validation with empty username
	 */
	public function testValidateEmptyUsername() {
		$user = Sprig::factory('user');
		$values = array(
			'username'  => '',
			'password'  => 'some_pass',
			'password_confirm' => 'some_pass',
		);

		try
		{
			$user->check($values);
			$errors = array();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors();
		}

		$this->assertArrayHasKey('username', $errors);
		$this->assertContains('not_empty', $errors['username']);
		$this->assertArrayNotHasKey('password', $errors);
		$this->assertArrayNotHasKey('password_confirm', $errors);
	}

	/**
	 * Test validation with short username
	 */
	public function testValidateShortUsername() {
		$user = Sprig::factory('user');
		$user->username = 'me';

		try
		{
			$user->check();
			$errors = array();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors();
		}

		$this->assertArrayHasKey('username', $errors);
		$this->assertContains('min_length', $errors['username']);
	}

	/**
	 * Test validation with long username
	 */
	public function testValidateLongUsername() {
		$user = Sprig::factory('user');
		$user->username = 'myoutrageouslyinsanelyridiculouslyandobscenelylongusername';

		try
		{
			$user->check();
			$errors = array();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors();
		}

		$this->assertArrayHasKey('username', $errors);
		$this->assertContains('max_length', $errors['username']);
	}

	/**
	 * Test validation with duplicate username
	 */
	public function testValidateDuplicateUsername() {
		try
		{
			DB::insert('users', array('username'))
				->values(array('JohnDoe'))->execute();
		}
		catch (Database_Exception $e)
		{
			echo $e->getMessage();
		}

		$user = Sprig::factory('user');
		$user->username = 'JohnDoe';

		try
		{
			$user->check();
			$errors = array();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors();
		}

		$this->assertArrayHasKey('username', $errors);
		$this->assertContains('username_available', $errors['username']);
	}

	/**
	 * Test validation with invalid username
	 */
	public function testValidateInvalidUsername() {
		$user = Sprig::factory('user');
		$user->username = 'some&/inv alid(username';

		try
		{
			$user->check();
			$errors = array();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors();
		}

		$this->assertArrayHasKey('username', $errors);
		$this->assertContains('regex', $errors['username']);
	}

	/**
	 * Test validation with empty password
	 */
	public function testValidateEmptyPassword() {
		$user = Sprig::factory('user');
		$values = array(
			'username'  => 'test_user',
			'password'  => '',
			'password_confirm' => '',
		);

		try
		{
			$user->check($values);
			$errors = array();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors();
		}

		$this->assertArrayNotHasKey('username', $errors);
		$this->assertArrayHasKey('password', $errors);
		$this->assertContains('not_empty', $errors['password']);
		$this->assertArrayNotHasKey('password_confirm', $errors);
	}

	/**
	 * Test validation with empty confirmation password
	 */
	public function testValidateEmptyPasswordConfirm() {
		$user = Sprig::factory('user');
		$values = array(
			'username'  => 'test_user',
			'password'  => 'test_pass',
			'password_confirm' => '',
		);

		try
		{
			$user->check($values);
			$errors = array();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors();
		}

		$this->assertArrayNotHasKey('username', $errors);
		$this->assertArrayNotHasKey('password', $errors);
		$this->assertArrayHasKey('password_confirm', $errors);
		$this->assertContains('matches', $errors['password_confirm']);
	}

	/**
	 * Test validation with wrong confirmation password
	 */
	public function testValidateWrongPasswordConfirm() {
		$user = Sprig::factory('user');
		$values = array(
			'username'  => 'test_user',
			'password'  => 'test_pass',
			'password_confirm' => 'user_pass',
		);

		try
		{
			$user->check($values);
			$errors = array();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors();
		}

		$this->assertArrayNotHasKey('username', $errors);
		$this->assertArrayNotHasKey('password', $errors);
		$this->assertArrayHasKey('password_confirm', $errors);
		$this->assertContains('matches', $errors['password_confirm']);
	}

	/**
	 * Test validation with empty email
	 */
	public function testValidateEmptyEmail() {
		$user = Sprig::factory('user');
		$values = array(
			'username' => 'test_user',
			'password' => 'test_pass',
			'password_confirm' => 'test_pass',
			'email' => '',
		);

		try
		{
			$user->check($values);
			$errors = array();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors();
		}

		$this->assertArrayNotHasKey('username', $errors);
		$this->assertArrayNotHasKey('password', $errors);
		$this->assertArrayNotHasKey('password_confirm', $errors);
		$this->assertArrayHasKey('email', $errors);
		$this->assertContains('not_empty', $errors['email']);
	}

	/**
	 * Test validation with invalid email
	 */
	public function testValidateInvalidEmail() {
		$user = Sprig::factory('user');
		$user->email = 'some/bad@email';

		try
		{
			$user->check();
			$errors = array();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors();
		}

		$this->assertArrayHasKey('email', $errors);
		$this->assertContains('email', $errors['email']);
	}

	/**
	 * Test validation with good values
	 */
	public function testValidateGoodValues() {
		$user = Sprig::factory('user');
		$user->username = 'test_user';
		$user->password = 'test_pass';
		$user->password_confirm = 'test_pass';
		$user->email = 'good@email.com';

		try
		{
			$user->check();
			$errors = array();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors();
		}

		$this->assertArrayNotHasKey('username', $errors);
		$this->assertArrayNotHasKey('password', $errors);
		$this->assertArrayNotHasKey('password_confirm', $errors);
		$this->assertArrayNotHasKey('email', $errors);
	}

	/**
	 * Test creation of user
	 */
	public function testCreateUser() {
		$user = Sprig::factory('user')
			->values(array(
				'username'  => 'test_user',
				'password'  => 'test_pass',
				'password_confirm'  => 'test_pass',
			));
		$errors = array();

		try
		{
			$user->create();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors();
		}

		$this->assertArrayNotHasKey('username', $errors);
		$this->assertArrayNotHasKey('password', $errors);
		$this->assertArrayNotHasKey('password_confirm', $errors);

		$model = Sprig::factory('user',array('username'=>'test_user'))
			->load();

		$this->assertEquals('test_user', $model->username);
	}

	/**
	 * Test change in username
	 */
	public function testChangeUsername() {
		DB::insert('users', array('username'))
			->values(array('test_user'))->execute();

		$user = Sprig::factory('user', array('username'=>'test_user'))
			->load();
		$this->assertEquals('test_user', $user->username);

		$user->username = 'test_name';
		$user->update();

		$SUT = DB::select()->from('users')->where('id', '=', $user->id)
			->execute()->current();
		$this->assertEquals('test_name', $SUT['username']);
	}

	/**
	 * Test change in password
	 */
	public function testChangePassword() {
		$pass = A1::instance('a1')->hash_password('test_pass');
		DB::insert('users', array('username','password'))
			->values(array('test_user', $pass))->execute();

		$user = Sprig::factory('user', array('username'=>'test_user'))
			->load();
		$this->assertEquals($pass, $user->password);

		$user->password = 'new_pass';
		$user->update();
		$user->load();	// Reload hashed password

		$salt = A1::instance('a1')->find_salt($user->password);
		$changed = A1::instance('a1')->hash_password('new_pass',$salt);

		$SUT = DB::select()->from('users')->where('id', '=', $user->id)
			->execute()->current();
		$this->assertEquals($changed, $SUT['password']);
	}

	/**
	 * Test change in email
	 */
	public function testChangeEmail() {
		DB::insert('users', array('email'))
			->values(array('some@email.com'))->execute();

		$user = Sprig::factory('user', array('email'=>'some@email.com'))
			->load();
		$this->assertEquals('some@email.com', $user->email);

		$user->email = 'new@email.com';
		$user->update();

		$SUT = DB::select()->from('users')->where('id', '=', $user->id)
			->execute()->current();
		$this->assertEquals('new@email.com', $SUT['email']);
	}

}

