<?php

/**
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

/**
 * Hybrid_User_Activity
 *
 * used to provider the connected user activity stream on a standardized structure across supported social apis.
 *
 * http://hybridauth.sourceforge.net/userguide/Profile_Data_User_Activity.html
 */
class Hybrid_User_Activity {

	/**
	 * Activity id on the provider side, usually given as integer
	 * @var mixed
	 */
	public $id = null;

	/**
	 * Activity date of creation
	 * @var int
	 */
	public $date = null;

	/**
	 * Activity content as a string
	 * @var string
	 */
	public $text = null;

	/**
	 * User who created the activity
	 * @var stdClass
	 */
	public $user = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->user = new stdClass();

		// typically, we should have a few information about the user who created the event from social apis
		$this->user->identifier = null;
		$this->user->displayName = null;
		$this->user->profileURL = null;
		$this->user->photoURL = null;
	}

}
