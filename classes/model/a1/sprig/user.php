<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Abstract A1 Authentication User Model.
 * To be extended and completed to user's needs.
 *
 * This class is mostly a port of Wouter's A1 ORM
 * class to Sprig.
 *
 * @package     Sentry
 * @author      Kyle Treubig
 * @copyright   (c) 2010 Kyle Treubig
 * @license     MIT
 */
abstract class Model_A1_Sprig_User extends Sprig {
	/** A1 config file name */
	protected $_config = 'a1';

	/** User model (from config) */
	protected $_user_model;

	/** User columns (from config) */
	protected $_columns;

	/**
	 * Initialize the sprig model
	 */
	public function _init() {
		$this->_columns    = Kohana::config($this->_config)->columns;
		$this->_user_model = Kohana::config($this->_config)->user_model;

		$this->_fields += array(
			'id'       => new Sprig_Field_Auto,
			'username' => new Sprig_Field_Char(array(
				'empty'      => FALSE,
				'unique'     => TRUE,
				'min_length' => 4,
				'max_length' => 32,
				'filters'    => array('trim' => NULL),
				'rules'      => array(
					'not_empty' => NULL,
					'regex'     => array('/^[\pL_.-]+$/ui'),
				),
				'callbacks'  => array(
					array($this, 'username_available'),
				),
			)),
			'password' => new Sprig_Field_Password(array(
				'hash_with'  => NULL,
				'min_length' => 5,
				'max_length' => 50,
				'filters'    => array('trim' => NULL,),
				'callbacks'  => array(
					array($this, 'hash_password'),
				),
			)),
			'password_confirm' => new Sprig_Field_Password(array(
				'empty' => TRUE,
				'hash_with'  => NULL,
				'in_db' => FALSE,
				'rules' => array(
					'matches' => array('password'),
				),
			)),
			'role'       => new Sprig_Field_Char(array(
				'choices'  => array('user', 'admin'),
			)),
			'token'      => new Sprig_Field_Char(array(
				'editable' => FALSE,
			)),
			'logins'     => new Sprig_Field_Integer(array(
				'default'  => 0,
				'editable' => FALSE,
			)),
			'last_login' => new Sprig_Field_Integer(array(
				'default'  => 0,
				'editable' => FALSE,
			)),
		);
	}

	/**
	 * Hash callback using the A1 library
	 *
	 * @param   string  password to hash
	 * @return  string
	 */
	public function hash_password(Validate $array, $field) {
		$pass = $array[$field];
		$array[$field] = A1::instance($this->_config)->hash_password($pass);
	}

	/**
	 * Tests if a username exists in the database
	 *
	 * @param   Validation
	 * @param   string      field to check
	 * @return  array
	 */
	public function username_available(Validate $array, $field) {
		if ($this->loaded() AND ! $this->changed($field))
			return TRUE;	// The value is unchanged

		if (Sprig::factory($this->_user_model, array($field=>$array[$field]))
			->load(null, FALSE)->count())
		{
			$array->error($field, 'username_available');
		}
	}

}	// End of Model_A1_Sprig_User

