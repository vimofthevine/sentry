<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Sentry Module User model
 *
 * @package     Sentry
 * @author      Kyle Treubig
 * @copyright   (c) 2010 Kyle Treubig
 * @license     MIT
 */
class Model_Sentry_User extends Model_A1_Sprig_User implements Acl_Role_Interface, Acl_Resource_Interface {

	/**
	 * Initialize the Sprig model
	 */
	public function _init() {
		parent::_init();

		$this->_fields += array(
			'email' => new Sprig_Field_Email(array(
				'unique'     => TRUE,
				'max_length' => 64,
			)),
		);
	}

	/**
	 * Acl_Role_Interface implementation of get_role_id
	 *
	 * @return  string
	 */
	public function get_role_id() {
		return $this->role;
	}

	/**
	 * Acl_Resource_Interface implementation of get_resource_id
	 *
	 * @return  string
	 */
	public function get_resource_id() {
		return 'user';
	}

}	// End of Model_Sentry_User

