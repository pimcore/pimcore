<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
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

class Hybrid_Providers_Foursquare extends Hybrid_Provider_Model_OAuth2
{ 
	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider apis end-points
		$this->api->api_base_url  = "https://api.foursquare.com/v2/";
		$this->api->authorize_url = "https://foursquare.com/oauth2/authenticate";
		$this->api->token_url     = "https://foursquare.com/oauth2/access_token"; 

		$this->api->sign_token_name = "oauth_token";
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$data = $this->api->api( "users/self", "GET", array( "v" => "20120610" ) ); 

		if ( ! isset( $data->response->user->id ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$data = $data->response->user;

		// get profile photo size from config
		$photoSize = ((isset($this->config["params"]["photo_size"]))?($this->config["params"]["photo_size"]):("100x100"));

		$this->user->profile->identifier    = $data->id;
		$this->user->profile->firstName     = $data->firstName;
		$this->user->profile->lastName      = $data->lastName;
		$this->user->profile->displayName   = trim( $this->user->profile->firstName . " " . $this->user->profile->lastName );
		$this->user->profile->photoURL      = $data->photo->prefix.$photoSize.$data->photo->suffix;
		$this->user->profile->profileURL    = "https://www.foursquare.com/user/" . $data->id;
		$this->user->profile->gender        = $data->gender;
		$this->user->profile->city          = $data->homeCity;
		$this->user->profile->email         = $data->contact->email;
		$this->user->profile->emailVerified = $data->contact->email;

		return $this->user->profile;
	}
}
