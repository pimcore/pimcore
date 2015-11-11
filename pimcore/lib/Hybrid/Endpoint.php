<?php

/**
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

/**
 * Hybrid_Endpoint class
 *
 * Provides a simple way to handle the OpenID and OAuth endpoint
 */
class Hybrid_Endpoint {

	protected $request = null;
	protected $initDone = false;

	/**
	 * Process the current request
	 *
	 * @param array $request The current request parameters. Leave as null to default to use $_REQUEST.
	 */
	public function __construct($request = null) {
		if (is_null($request)) {
			// Fix a strange behavior when some provider call back ha endpoint
			// with /index.php?hauth.done={provider}?{args}...
			// >here we need to parse $_SERVER[QUERY_STRING]
			$request = $_REQUEST;
			if (strrpos($_SERVER["QUERY_STRING"], '?')) {
				$_SERVER["QUERY_STRING"] = str_replace("?", "&", $_SERVER["QUERY_STRING"]);
				parse_str($_SERVER["QUERY_STRING"], $request);
			}
		}

		// Setup request variable
		$this->request = $request;

		// If openid_policy requested, we return our policy document
		if (isset($this->request["get"]) && $this->request["get"] == "openid_policy") {
			$this->processOpenidPolicy();
		}

		// If openid_xrds requested, we return our XRDS document
		if (isset($this->request["get"]) && $this->request["get"] == "openid_xrds") {
			$this->processOpenidXRDS();
		}

		// If we get a hauth.start
		if (isset($this->request["hauth_start"]) && $this->request["hauth_start"]) {
			$this->processAuthStart();
		}
		// Else if hauth.done
		elseif (isset($this->request["hauth_done"]) && $this->request["hauth_done"]) {
			$this->processAuthDone();
		}
		// Else we advertise our XRDS document, something supposed to be done from the Realm URL page
		else {
			$this->processOpenidRealm();
		}
	}

	/**
	 * Process the current request
	 *
	 * @param array $request The current request parameters. Leave as null to default to use $_REQUEST.
	 * @return Hybrid_Endpoint
	 */
	public static function process($request = null) {
		// Trick for PHP 5.2, because it doesn't support late static binding
		$class = function_exists('get_called_class') ? get_called_class() : __CLASS__;
		new $class($request);
	}

	/**
	 * Process OpenID policy request
	 * @return void
	 */
	protected function processOpenidPolicy() {
		$output = file_get_contents(dirname(__FILE__) . "/resources/openid_policy.html");
		print $output;
		die();
	}

	/**
	 * Process OpenID XRDS request
	 * @return void
	 */
	protected function processOpenidXRDS() {
		header("Content-Type: application/xrds+xml");

		$output = str_replace("{RETURN_TO_URL}", str_replace(
						array("<", ">", "\"", "'", "&"), array("&lt;", "&gt;", "&quot;", "&apos;", "&amp;"), Hybrid_Auth::getCurrentUrl(false)
				), file_get_contents(dirname(__FILE__) . "/resources/openid_xrds.xml"));
		print $output;
		die();
	}

	/**
	 * Process OpenID realm request
	 * @return void
	 */
	protected function processOpenidRealm() {
		$output = str_replace("{X_XRDS_LOCATION}", htmlentities(Hybrid_Auth::getCurrentUrl(false), ENT_QUOTES, 'UTF-8')
				. "?get=openid_xrds&v="
				. Hybrid_Auth::$version, file_get_contents(dirname(__FILE__) . "/resources/openid_realm.html"));
		print $output;
		die();
	}

	/**
	 * Define: endpoint step 3
	 * @return void
	 * @throws Hybrid_Exception
	 */
	protected function processAuthStart() {
		$this->authInit();

		$provider_id = trim(strip_tags($this->request["hauth_start"]));

		// check if page accessed directly
		if (!Hybrid_Auth::storage()->get("hauth_session.$provider_id.hauth_endpoint")) {
			Hybrid_Logger::error("Endpoint: hauth_endpoint parameter is not defined on hauth_start, halt login process!");

			throw new Hybrid_Exception("You cannot access this page directly.");
		}

		// define:hybrid.endpoint.php step 2.
		$hauth = Hybrid_Auth::setup($provider_id);

		// if REQUESTed hauth_idprovider is wrong, session not created, etc.
		if (!$hauth) {
			Hybrid_Logger::error("Endpoint: Invalid parameter on hauth_start!");
			throw new Hybrid_Exception("Invalid parameter! Please return to the login page and try again.");
		}

		try {
			Hybrid_Logger::info("Endpoint: call adapter [{$provider_id}] loginBegin()");

			$hauth->adapter->loginBegin();
		} catch (Exception $e) {
			Hybrid_Logger::error("Exception:" . $e->getMessage(), $e);
			Hybrid_Error::setError($e->getMessage(), $e->getCode(), $e->getTraceAsString(), $e->getPrevious());

			$hauth->returnToCallbackUrl();
		}

		die();
	}

	/**
	 * Define: endpoint step 3.1 and 3.2
	 * @return void
	 * @throws Hybrid_Exception
	 */
	protected function processAuthDone() {
		$this->authInit();

		$provider_id = trim(strip_tags($this->request["hauth_done"]));

		$hauth = Hybrid_Auth::setup($provider_id);

		if (!$hauth) {
			Hybrid_Logger::error("Endpoint: Invalid parameter on hauth_done!");

			$hauth->adapter->setUserUnconnected();

			throw new Hybrid_Exception("Invalid parameter! Please return to the login page and try again.");
		}

		try {
			Hybrid_Logger::info("Endpoint: call adapter [{$provider_id}] loginFinish() ");
			$hauth->adapter->loginFinish();
		} catch (Exception $e) {
			Hybrid_Logger::error("Exception:" . $e->getMessage(), $e);
			Hybrid_Error::setError($e->getMessage(), $e->getCode(), $e->getTraceAsString(), $e->getPrevious());

			$hauth->adapter->setUserUnconnected();
		}

		Hybrid_Logger::info("Endpoint: job done. return to callback url.");

		$hauth->returnToCallbackUrl();
		die();
	}

	/**
	 * Initializes authentication
	 * @throws Hybrid_Exception
	 */
	protected function authInit() {
		if (!$this->initDone) {
			$this->initDone = true;

			// Init Hybrid_Auth
			try {
				if (!class_exists("Hybrid_Storage", false)) {
					require_once realpath(dirname(__FILE__)) . "/Storage.php";
				}

				$storage = new Hybrid_Storage();

				// Check if Hybrid_Auth session already exist
				if (!$storage->config("CONFIG")) {
					throw new Hybrid_Exception("You cannot access this page directly.");
				}

				Hybrid_Auth::initialize($storage->config("CONFIG"));
			} catch (Exception $e) {
				Hybrid_Logger::error("Endpoint: Error while trying to init Hybrid_Auth: " . $e->getMessage());
				throw new Hybrid_Exception("Oophs. Error!");
			}
		}
	}

}
