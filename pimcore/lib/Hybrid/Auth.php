<?php

/**
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

/**
 * Hybrid_Auth class
 *
 * Hybrid_Auth class provide a simple way to authenticate users via OpenID and OAuth.
 *
 * Generally, Hybrid_Auth is the only class you should instanciate and use throughout your application.
 */
class Hybrid_Auth {

	public static $version = "2.5.1";

	/**
	 * Configuration array
	 * @var array
	 */
	public static $config = array();

	/**
	 * Auth cache
	 * @var Hybrid_Storage
	 */
	public static $store = null;

	/**
	 * Error pool
	 * @var Hybrid_Error
	 */
	public static $error = null;

	/**
	 * Logger
	 * @var Hybrid_Logger
	 */
	public static $logger = null;

	/**
	 * Try to start a new session of none then initialize Hybrid_Auth
	 *
	 * Hybrid_Auth constructor will require either a valid config array or
	 * a path for a configuration file as parameter. To know more please
	 * refer to the Configuration section:
	 * http://hybridauth.sourceforge.net/userguide/Configuration.html
	 *
	 * @param array $config Configuration array or path to a configratuion file
	 */
	function __construct($config) {
		Hybrid_Auth::initialize($config);
	}

	/**
	 * Try to initialize Hybrid_Auth with given $config hash or file
	 *
	 * @param array $config Configuration array or path to a configratuion file
	 * @return void
	 * @throws Exception
	 */
	public static function initialize($config) {
		if (!is_array($config) && !file_exists($config)) {
			throw new Exception("Hybriauth config does not exist on the given path.", 1);
		}

		if (!is_array($config)) {
			$config = include $config;
		}

		// build some need'd paths
		$config["path_base"] = realpath(dirname(__FILE__)) . "/";
		$config["path_libraries"] = $config["path_base"] . "thirdparty/";
		$config["path_resources"] = $config["path_base"] . "resources/";
		$config["path_providers"] = $config["path_base"] . "Providers/";

		// reset debug mode
		if (!isset($config["debug_mode"])) {
			$config["debug_mode"] = false;
			$config["debug_file"] = null;
		}

		# load hybridauth required files, a autoload is on the way...
		require_once $config["path_base"] . "Error.php";
		require_once $config["path_base"] . "Exception.php";
		require_once $config["path_base"] . "Logger.php";

		require_once $config["path_base"] . "Provider_Adapter.php";

		require_once $config["path_base"] . "Provider_Model.php";
		require_once $config["path_base"] . "Provider_Model_OpenID.php";
		require_once $config["path_base"] . "Provider_Model_OAuth1.php";
		require_once $config["path_base"] . "Provider_Model_OAuth2.php";

		require_once $config["path_base"] . "User.php";
		require_once $config["path_base"] . "User_Profile.php";
		require_once $config["path_base"] . "User_Contact.php";
		require_once $config["path_base"] . "User_Activity.php";

		if (!class_exists("Hybrid_Storage", false)) {
			require_once $config["path_base"] . "Storage.php";
		}

		// hash given config
		Hybrid_Auth::$config = $config;

		// instance of log mng
		Hybrid_Auth::$logger = new Hybrid_Logger();

		// instance of errors mng
		Hybrid_Auth::$error = new Hybrid_Error();

		// start session storage mng
		Hybrid_Auth::$store = new Hybrid_Storage();

		Hybrid_Logger::info("Enter Hybrid_Auth::initialize()");
		Hybrid_Logger::info("Hybrid_Auth::initialize(). PHP version: " . PHP_VERSION);
		Hybrid_Logger::info("Hybrid_Auth::initialize(). Hybrid_Auth version: " . Hybrid_Auth::$version);
		Hybrid_Logger::info("Hybrid_Auth::initialize(). Hybrid_Auth called from: " . Hybrid_Auth::getCurrentUrl());

		// PHP Curl extension [http://www.php.net/manual/en/intro.curl.php]
		if (!function_exists('curl_init')) {
			Hybrid_Logger::error('Hybridauth Library needs the CURL PHP extension.');
			throw new Exception('Hybridauth Library needs the CURL PHP extension.');
		}

		// PHP JSON extension [http://php.net/manual/en/book.json.php]
		if (!function_exists('json_decode')) {
			Hybrid_Logger::error('Hybridauth Library needs the JSON PHP extension.');
			throw new Exception('Hybridauth Library needs the JSON PHP extension.');
		}

		// session.name
		if (session_name() != "PHPSESSID") {
			Hybrid_Logger::info('PHP session.name diff from default PHPSESSID. http://php.net/manual/en/session.configuration.php#ini.session.name.');
		}

		// safe_mode is on
		if (ini_get('safe_mode')) {
			Hybrid_Logger::info('PHP safe_mode is on. http://php.net/safe-mode.');
		}

		// open basedir is on
		if (ini_get('open_basedir')) {
			Hybrid_Logger::info('PHP open_basedir is on. http://php.net/open-basedir.');
		}

		Hybrid_Logger::debug("Hybrid_Auth initialize. dump used config: ", serialize($config));
		Hybrid_Logger::debug("Hybrid_Auth initialize. dump current session: ", Hybrid_Auth::storage()->getSessionData());
		Hybrid_Logger::info("Hybrid_Auth initialize: check if any error is stored on the endpoint...");

		if (Hybrid_Error::hasError()) {
			$m = Hybrid_Error::getErrorMessage();
			$c = Hybrid_Error::getErrorCode();
			$p = Hybrid_Error::getErrorPrevious();

			Hybrid_Logger::error("Hybrid_Auth initialize: A stored Error found, Throw an new Exception and delete it from the store: Error#$c, '$m'");

			Hybrid_Error::clearError();

			// try to provide the previous if any
			// Exception::getPrevious (PHP 5 >= 5.3.0) http://php.net/manual/en/exception.getprevious.php
			if (version_compare(PHP_VERSION, '5.3.0', '>=') && ($p instanceof Exception)) {
				throw new Exception($m, $c, $p);
			} else {
				throw new Exception($m, $c);
			}
		}

		Hybrid_Logger::info("Hybrid_Auth initialize: no error found. initialization succeed.");
	}

	/**
	 * Hybrid storage system accessor
	 *
	 * Users sessions are stored using HybridAuth storage system ( HybridAuth 2.0 handle PHP Session only) and can be accessed directly by
	 * Hybrid_Auth::storage()->get($key) to retrieves the data for the given key, or calling
	 * Hybrid_Auth::storage()->set($key, $value) to store the key => $value set.
	 *
	 * @return Hybrid_Storage
	 */
	public static function storage() {
		return Hybrid_Auth::$store;
	}

	/**
	 * Get hybridauth session data
	 * @return string|null
	 */
	function getSessionData() {
		return Hybrid_Auth::storage()->getSessionData();
	}

	/**
	 * Restore hybridauth session data
	 *
	 * @param string $sessiondata Serialized session data
	 * @retun void
	 */
	function restoreSessionData($sessiondata = null) {
		Hybrid_Auth::storage()->restoreSessionData($sessiondata);
	}

	/**
	 * Try to authenticate the user with a given provider.
	 *
	 * If the user is already connected we just return and instance of provider adapter,
	 * ELSE, try to authenticate and authorize the user with the provider.
	 *
	 * $params is generally an array with required info in order for this provider and HybridAuth to work,
	 *  like :
	 *          hauth_return_to: URL to call back after authentication is done
	 *        openid_identifier: The OpenID identity provider identifier
	 *           google_service: can be "Users" for Google user accounts service or "Apps" for Google hosted Apps
	 *
	 * @param string $providerId ID of the provider
	 * @param array  $params      Params
	 * @return
	 */
	public static function authenticate($providerId, $params = null) {
		Hybrid_Logger::info("Enter Hybrid_Auth::authenticate( $providerId )");

		if (!Hybrid_Auth::storage()->get("hauth_session.$providerId.is_logged_in")) {
			// if user not connected to $providerId then try setup a new adapter and start the login process for this provider
			Hybrid_Logger::info("Hybrid_Auth::authenticate( $providerId ), User not connected to the provider. Try to authenticate..");
			$provider_adapter = Hybrid_Auth::setup($providerId, $params);
			$provider_adapter->login();
		} else {
			// else, then return the adapter instance for the given provider
			Hybrid_Logger::info("Hybrid_Auth::authenticate( $providerId ), User is already connected to this provider. Return the adapter instance.");
			return Hybrid_Auth::getAdapter($providerId);
		}
	}

	/**
	 * Return the adapter instance for an authenticated provider
	 *
	 * @param string $providerId ID of the provider
	 * @return Hybrid_Provider_Adapter
	 */
	public static function getAdapter($providerId = null) {
		Hybrid_Logger::info("Enter Hybrid_Auth::getAdapter( $providerId )");
		return Hybrid_Auth::setup($providerId);
	}

	/**
	 * Setup an adapter for a given provider
	 *
	 * @param string $providerId ID of the provider
	 * @param array  $params     Adapter params
	 * @return Hybrid_Provider_Adapter
	 */
	public static function setup($providerId, $params = null) {
		Hybrid_Logger::debug("Enter Hybrid_Auth::setup( $providerId )", $params);

		if (!$params) {
			$params = Hybrid_Auth::storage()->get("hauth_session.$providerId.id_provider_params");

			Hybrid_Logger::debug("Hybrid_Auth::setup( $providerId ), no params given. Trying to get the stored for this provider.", $params);
		}

		if (!$params) {
			$params = array();
			Hybrid_Logger::info("Hybrid_Auth::setup( $providerId ), no stored params found for this provider. Initialize a new one for new session");
		}

		if (is_array($params) && !isset($params["hauth_return_to"])) {
			$params["hauth_return_to"] = Hybrid_Auth::getCurrentUrl();
			Hybrid_Logger::debug("Hybrid_Auth::setup( $providerId ). HybridAuth Callback URL set to: ", $params["hauth_return_to"]);
		}

		# instantiate a new IDProvider Adapter
		$provider = new Hybrid_Provider_Adapter();
		$provider->factory($providerId, $params);
		return $provider;
	}

	/**
	 * Check if the current user is connected to a given provider
	 *
	 * @param string $providerId ID of the provider
	 * @return bool
	 */
	public static function isConnectedWith($providerId) {
		return (bool) Hybrid_Auth::storage()->get("hauth_session.{$providerId}.is_logged_in");
	}

	/**
	 * Return array listing all authenticated providers
	 * @return array
	 */
	public static function getConnectedProviders() {
		$idps = array();

		foreach (Hybrid_Auth::$config["providers"] as $idpid => $params) {
			if (Hybrid_Auth::isConnectedWith($idpid)) {
				$idps[] = $idpid;
			}
		}

		return $idps;
	}

	/**
	 * Return array listing all enabled providers as well as a flag if you are connected
	 *
	 * <code>
	 * array(
	 *   'Facebook' => array(
	 *     'connected' => true
	 *   )
	 * )
	 * </code>
	 * @return array
	 */
	public static function getProviders() {
		$idps = array();

		foreach (Hybrid_Auth::$config["providers"] as $idpid => $params) {
			if ($params['enabled']) {
				$idps[$idpid] = array('connected' => false);

				if (Hybrid_Auth::isConnectedWith($idpid)) {
					$idps[$idpid]['connected'] = true;
				}
			}
		}

		return $idps;
	}

	/**
	 * A generic function to logout all connected provider at once
	 * @return void
	 */
	public static function logoutAllProviders() {
		$idps = Hybrid_Auth::getConnectedProviders();

		foreach ($idps as $idp) {
			$adapter = Hybrid_Auth::getAdapter($idp);
			$adapter->logout();
		}
	}

	/**
	 * Utility function, redirect to a given URL with php header or using javascript location.href
	 *
	 * @param string $url  URL to redirect to
	 * @param string $mode PHP|JS
	 */
	public static function redirect($url, $mode = "PHP") {
		Hybrid_Logger::info("Enter Hybrid_Auth::redirect( $url, $mode )");

		// Ensure session is saved before sending response, see https://github.com/symfony/symfony/pull/12341
		if ((PHP_VERSION_ID >= 50400 && PHP_SESSION_ACTIVE === session_status()) || (PHP_VERSION_ID < 50400 && isset($_SESSION) && session_id())) {
			session_write_close();
		}

		if ($mode == "PHP") {
			header("Location: $url");
		} elseif ($mode == "JS") {
			echo '<html>';
			echo '<head>';
			echo '<script type="text/javascript">';
			echo 'function redirect(){ window.top.location.href="' . $url . '"; }';
			echo '</script>';
			echo '</head>';
			echo '<body onload="redirect()">';
			echo 'Redirecting, please wait...';
			echo '</body>';
			echo '</html>';
		}

		die();
	}

	/**
	 * Utility function, return the current url
	 *
	 * @param bool $request_uri true to get $_SERVER['REQUEST_URI'], false for $_SERVER['PHP_SELF']
	 * @return string
	 */
	public static function getCurrentUrl($request_uri = true) {
		if (php_sapi_name() == 'cli') {
			return '';
		}

		$protocol = 'http://';

		if ((isset($_SERVER['HTTPS']) && ( $_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1 ))
				|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'))
		{
			$protocol = 'https://';
		}

		$url = $protocol . $_SERVER['HTTP_HOST'];

		if ($request_uri) {
			$url .= $_SERVER['REQUEST_URI'];
		} else {
			$url .= $_SERVER['PHP_SELF'];
		}

		// return current url
		return $url;
	}

}
