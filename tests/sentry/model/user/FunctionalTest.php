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
		);

		try
		{
			$user->check($values);
			$errors = array();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors('sentry');
		}

		$this->assertTrue(isset($errors['username']));
		$this->assertTrue(isset($errors['password']));
	}

	/**
	 * Test validation with empty username
	 */
	public function testValidateEmptyUsername() {
		$user = Sprig::factory('user');
		$values = array(
			'username'  => '',
			'password'  => 'some_pass',
		);

		try
		{
			$user->check($values);
			$errors = array();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors('sentry');
		}

		$this->assertTrue(isset($errors['username']));
		$this->assertFalse(isset($errors['password']));
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
			$errors = $e->array->errors('sentry');
		}

		$this->assertTrue(isset($errors['username']));
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
			$errors = $e->array->errors('sentry');
		}

		$this->assertTrue(isset($errors['username']));
	}

	/**
	 * Test validation with invalid username
	 */
	public function testValidateInvalidUsername() {
		$user = Sprig::factory('user');
		$user->username = 'some&/invalid(username';

		try
		{
			$user->check();
			$errors = array();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors('sentry');
		}

		$this->assertTrue(isset($errors['username']));
	}

	/**
	 * Test validation with empty password
	 */
	public function testValidateEmptyPassword() {
		$user = Sprig::factory('user');
		$values = array(
			'username'  => 'test_user',
			'password'  => '',
		);

		try
		{
			$user->check($values);
			$errors = array();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors('sentry');
		}

		$this->assertFalse(isset($errors['username']));
		$this->assertTrue(isset($errors['password']));
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
			$errors = $e->array->errors('sentry');
		}

		$this->assertFalse(isset($errors['username']));
		$this->assertFalse(isset($errors['password']));
		$this->assertTrue(isset($errors['password_confirm']));
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
			$errors = $e->array->errors('sentry');
		}

		$this->assertFalse(isset($errors['username']));
		$this->assertFalse(isset($errors['password']));
		$this->assertTrue(isset($errors['password_confirm']));
	}

	/**
	 * Test validation with good values
	 */
	public function testValidateGoodValues() {
		$user = Sprig::factory('user');
		$user->username = 'test_user';
		$user->password = 'test_pass';
		$user->password_confirm = 'test_pass';
		$errors = array();

		try
		{
			$user->check();
		}
		catch (Validate_Exception $e)
		{
			$errors = $e->array->errors('sentry');
		}

		$this->assertFalse(isset($errors['username']));
		$this->assertFalse(isset($errors['password']));
		$this->assertFalse(isset($errors['password_confirm']));
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
			$errors = $e->array->errors('sentry');
		}

		$this->assertFalse(isset($errors['username']));
		$this->assertFalse(isset($errors['password']));
		$this->assertFalse(isset($errors['password_confirm']));

		$model = Sprig::factory('user',array('username'=>'test_user'))
			->load();

		$this->assertEquals('test_user', $model->username);
	}

	/**
	 * Test change in username
	 */
	public function testChangeUsername() {
	}

	/**
	 * Test change in password
	 */
	public function testChangePassword() {
	}

}

