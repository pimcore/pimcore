<?php

/* !
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

/**
 * Hybrid_Providers_Foursquare provider adapter based on OAuth2 protocol
 *
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Foursquare.html
 */

/**
 * Howto define profile photo size:
 * - add params key into hybridauth config
 * ...
 *    "Foursquare" => array (
 *       "enabled" => true,
 *       "keys"    => ...,
 *       "params" => array( "photo_size" => "16x16" )
 *   	),
 * ...
 * - list of valid photo_size values is described here https://developer.foursquare.com/docs/responses/photo.html
 * - default photo_size is 100x100
 */
class Hybrid_Providers_Foursquare extends Hybrid_Provider_Model_OAuth2 {

	private static $apiVersion = array("v" => "20120610");
	private static $defPhotoSize = "100x100";

	/**
	 * {@inheritdoc}
	 */
	function initialize() {
		parent::initialize();

		// Provider apis end-points
		$this->api->api_base_url = "https://api.foursquare.com/v2/";
		$this->api->authorize_url = "https://foursquare.com/oauth2/authenticate";
		$this->api->token_url = "https://foursquare.com/oauth2/access_token";

		$this->api->sign_token_name = "oauth_token";
	}

	/**
	 * {@inheritdoc}
	 */
	function getUserProfile() {
		$data = $this->api->api("users/self", "GET", Hybrid_Providers_Foursquare::$apiVersion);

		if (!isset($data->response->user->id)) {
			throw new Exception("User profile request failed! {$this->providerId} returned an invalid response.", 6);
		}

		$data = $data->response->user;

		$this->user->profile->identifier = $data->id;
		$this->user->profile->firstName = $data->firstName;
		$this->user->profile->lastName = $data->lastName;
		$this->user->profile->displayName = $this->buildDisplayName($this->user->profile->firstName, $this->user->profile->lastName);
		$this->user->profile->photoURL = $this->buildPhotoURL($data->photo->prefix, $data->photo->suffix);
		$this->user->profile->profileURL = "https://www.foursquare.com/user/" . $data->id;
		$this->user->profile->gender = $data->gender;
		$this->user->profile->city = $data->homeCity;
		$this->user->profile->email = $data->contact->email;
		$this->user->profile->emailVerified = $data->contact->email;

		return $this->user->profile;
	}

	/**
	 * {@inheritdoc}
	 */
	function getUserContacts() {
		// refresh tokens if needed
		$this->refreshToken();

		//
		$response = array();
		$contacts = array();
		try {
			$response = $this->api->api("users/self/friends", "GET", Hybrid_Providers_Foursquare::$apiVersion);
		} catch (LinkedInException $e) {
			throw new Exception("User contacts request failed! {$this->providerId} returned an error: $e");
		}

		if (isset($response) && $response->meta->code == 200) {
			foreach ($response->response->friends->items as $contact) {
				$uc = new Hybrid_User_Contact();
				//
				$uc->identifier = $contact->id;
				//$uc->profileURL		= ;
				//$uc->webSiteURL		= ;
				$uc->photoURL = $this->buildPhotoURL($contact->photo->prefix, $contact->photo->suffix);
				$uc->displayName = $this->buildDisplayName((isset($contact->firstName) ? ($contact->firstName) : ("")), (isset($contact->lastName) ? ($contact->lastName) : ("")));
				//$uc->description	= ;
				$uc->email = (isset($contact->contact->email) ? ($contact->contact->email) : (""));
				//
				$contacts[] = $uc;
			}
		}
		return $contacts;
	}

	/**
	 * {@inheritdoc}
	 */
	private function buildDisplayName($firstName, $lastName) {
		return trim($firstName . " " . $lastName);
	}

	private function buildPhotoURL($prefix, $suffix) {
		if (isset($prefix) && isset($suffix)) {
			return $prefix . ((isset($this->config["params"]["photo_size"])) ? ($this->config["params"]["photo_size"]) : (Hybrid_Providers_Foursquare::$defPhotoSize)) . $suffix;
		}
		return ("");
	}

}
