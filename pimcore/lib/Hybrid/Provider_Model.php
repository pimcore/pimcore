<?php

/**
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

/**
 * Hybrid_Provider_Model provide a common interface for supported IDps on HybridAuth.
 *
 * Basically, each provider adapter has to define at least 4 methods:
 *   Hybrid_Providers_{provider_name}::initialize()
 *   Hybrid_Providers_{provider_name}::loginBegin()
 *   Hybrid_Providers_{provider_name}::loginFinish()
 *   Hybrid_Providers_{provider_name}::getUserProfile()
 *
 * HybridAuth also come with three others models
 *   Class Hybrid_Provider_Model_OpenID for providers that uses the OpenID 1 and 2 protocol.
 *   Class Hybrid_Provider_Model_OAuth1 for providers that uses the OAuth 1 protocol.
 *   Class Hybrid_Provider_Model_OAuth2 for providers that uses the OAuth 2 protocol.
 */
abstract class Hybrid_Provider_Model {

	/**
	 * IDp ID (or unique name)
	 * @var mixed
	 */
	public $providerId = null;

	/**
	 * Specific provider adapter config
	 * @var array
	 */
	public $config = null;

	/**
	 * Provider extra parameters
	 * @var array
	 */
	public $params = null;

	/**
	 * Endpoint URL for that provider
	 * @var string
	 */
	public $endpoint = null;

	/**
	 * Hybrid_User obj, represents the current loggedin user
	 * @var Hybrid_User
	 */
	public $user = null;

	/**
	 * The provider api client (optional)
	 * @var stdClass
	 */
	public $api = null;

	/**
	 * Common providers adapter constructor
	 *
	 * @param mixed $providerId Provider ID
	 * @param array $config     Provider adapter config
	 * @param array $params     Provider extra params
	 */
	function __construct($providerId, $config, $params = null) {
		# init the IDp adapter parameters, get them from the cache if possible
		if (!$params) {
			$this->params = Hybrid_Auth::storage()->get("hauth_session.$providerId.id_provider_params");
		} else {
			$this->params = $params;
		}

		// idp id
		$this->providerId = $providerId;

		// set HybridAuth endpoint for this provider
		$this->endpoint = Hybrid_Auth::storage()->get("hauth_session.$providerId.hauth_endpoint");

		// idp config
		$this->config = $config;

		// new user instance
		$this->user = new Hybrid_User();
		$this->user->providerId = $providerId;

		// initialize the current provider adapter
		$this->initialize();

		Hybrid_Logger::debug("Hybrid_Provider_Model::__construct( $providerId ) initialized. dump current adapter instance: ", serialize($this));
	}

	/**
	 * IDp wrappers initializer
	 *
	 * The main job of wrappers initializer is to performs (depend on the IDp api client it self):
	 *     - include some libs needed by this provider,
	 *     - check IDp key and secret,
	 *     - set some needed parameters (stored in $this->params) by this IDp api client
	 *     - create and setup an instance of the IDp api client on $this->api
	 *
	 * @return void
	 * @throws Exception
	 */
	abstract protected function initialize();

	/**
	 * Begin login
	 *
	 * @return void
	 * @throws Exception
	 */
	abstract protected function loginBegin();

	/**
	 * Finish login
	 * @return void
	 * @throws Exception
	 */
	abstract protected function loginFinish();

	/**
	 * Generic logout, just erase current provider adapter stored data to let Hybrid_Auth all forget about it
	 * @return bool
	 */
	function logout() {
		Hybrid_Logger::info("Enter [{$this->providerId}]::logout()");
		$this->clearTokens();
		return true;
	}

	/**
	 * Grab the user profile from the IDp api client
	 * @return Hybrid_User_Profile
	 * @throw Exception
	 */
	function getUserProfile() {
		Hybrid_Logger::error("HybridAuth do not provide users contacts list for {$this->providerId} yet.");
		throw new Exception("Provider does not support this feature.", 8);
	}

	/**
	 * Load the current logged in user contacts list from the IDp api client
	 * @return Hybrid_User_Contact[]
	 * @throws Exception
	 */
	function getUserContacts() {
		Hybrid_Logger::error("HybridAuth do not provide users contacts list for {$this->providerId} yet.");
		throw new Exception("Provider does not support this feature.", 8);
	}

	/**
	 * Return the user activity stream
	 * @return Hybrid_User_Activity[]
	 * @throws Exception
	 */
	function getUserActivity($stream) {
		Hybrid_Logger::error("HybridAuth do not provide user's activity stream for {$this->providerId} yet.");
		throw new Exception("Provider does not support this feature.", 8);
	}

	/**
	 * Set user status
	 * @return mixed Provider response
	 * @throws Exception
	 */
	function setUserStatus($status) {
		Hybrid_Logger::error("HybridAuth do not provide user's activity stream for {$this->providerId} yet.");
		throw new Exception("Provider does not support this feature.", 8);
	}

	/**
	 * Return the user status
	 * @return mixed Provider response
	 * @throws Exception
	 */
	function getUserStatus($statusid) {
		Hybrid_Logger::error("HybridAuth do not provide user's status for {$this->providerId} yet.");
		throw new Exception("Provider does not support this feature.", 8);
	}

	/**
	 * Return true if the user is connected to the current provider
	 * @return bool
	 */
	public function isUserConnected() {
		return (bool) Hybrid_Auth::storage()->get("hauth_session.{$this->providerId}.is_logged_in");
	}

	/**
	 * Set user to connected
	 * @return void
	 */
	public function setUserConnected() {
		Hybrid_Logger::info("Enter [{$this->providerId}]::setUserConnected()");
		Hybrid_Auth::storage()->set("hauth_session.{$this->providerId}.is_logged_in", 1);
	}

	/**
	 * Set user to unconnected
	 * @return void
	 */
	public function setUserUnconnected() {
		Hybrid_Logger::info("Enter [{$this->providerId}]::setUserUnconnected()");
		Hybrid_Auth::storage()->set("hauth_session.{$this->providerId}.is_logged_in", 0);
	}

	/**
	 * Get or set a token
	 * @return string
	 */
	public function token($token, $value = null) {
		if ($value === null) {
			return Hybrid_Auth::storage()->get("hauth_session.{$this->providerId}.token.$token");
		} else {
			Hybrid_Auth::storage()->set("hauth_session.{$this->providerId}.token.$token", $value);
		}
	}

	/**
	 * Delete a stored token
	 * @return void
	 */
	public function deleteToken($token) {
		Hybrid_Auth::storage()->delete("hauth_session.{$this->providerId}.token.$token");
	}

	/**
	 * Clear all existent tokens for this provider
	 * @return void
	 */
	public function clearTokens() {
		Hybrid_Auth::storage()->deleteMatch("hauth_session.{$this->providerId}.");
	}

}
