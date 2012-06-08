<?php
/*
 * Copyright 2008 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once "external/OAuth.php";

/**
 * Authentication class that deals with 3-Legged OAuth 1.0a authentication
 *
 * This class uses the OAuth 1.0a spec which has a slightly different work flow in
 * how callback urls, request & access tokens are dealt with to prevent a possible
 * man in the middle attack.
 *
 * @author Chris Chabot <chabotc@google.com>
 *
 */
class apiOAuth extends apiAuth {
  public $cacheKey;
  protected $consumerToken;
  protected $accessToken;
  protected $privateKeyFile;
  protected $developerKey;
  public $service;

  /**
   * Instantiates the class, but does not initiate the login flow, leaving it
   * to the discretion of the caller.
   */
  public function __construct() {
    global $apiConfig;
    if (!empty($apiConfig['developer_key'])) {
      $this->setDeveloperKey($apiConfig['developer_key']);
    }
    $this->consumerToken = new apiClientOAuthConsumer($apiConfig['oauth_consumer_key'], $apiConfig['oauth_consumer_secret'], NULL);
    $this->signatureMethod = new apiClientOAuthSignatureMethod_HMAC_SHA1();
    $this->cacheKey = 'OAuth:' . $apiConfig['oauth_consumer_key']; // Scope data to the local user as well, or else multiple local users will share the same OAuth credentials.
  }

  /**
   * The 3 legged oauth class needs a way to store the access key and token
   * it uses the apiCache class to do so.
   *
   * Constructing this class will initiate the 3 legged oauth work flow, including redirecting
   * to the OAuth provider's site if required(!)
   *
   * @param string $consumerKey
   * @param string $consumerSecret
   * @return apiOAuth3Legged the logged-in provider instance
   */
  public function authenticate($service) {
    global $apiConfig;
    $this->service = $service;
    $this->service['authorization_token_url'] .= '?scope=' . apiClientOAuthUtil::urlencodeRFC3986($service['scope']) . '&domain=' . apiClientOAuthUtil::urlencodeRFC3986($apiConfig['site_name']) . '&oauth_token=';
    if (isset($_GET['oauth_verifier']) && isset($_GET['oauth_token'])  && isset($_GET['uid'])) {
      $uid = $_GET['uid'];
      $secret = apiClient::$cache->get($this->cacheKey.":nonce:" . $uid);
      apiClient::$cache->delete($this->cacheKey.":nonce:" . $uid);
      $token = $this->upgradeRequestToken($_GET['oauth_token'], $secret, $_GET['oauth_verifier']);
      return json_encode($token);
    } else {
      // Initialize the OAuth dance, first request a request token, then kick the client to the authorize URL
      // First we store the current URL in our cache, so that when the oauth dance is completed we can return there
      $callbackUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
      $uid = uniqid();
      $token = $this->obtainRequestToken($callbackUrl, $uid);
      apiClient::$cache->set($this->cacheKey.":nonce:" . $uid,  $token->secret);
      $this->redirectToAuthorization($token);
    }
  }

  /**
   * Sets the internal oauth access token (which is returned by the authenticate function), a user should only
   * go through the authenticate() flow once (which involces a bunch of browser redirections and authentication screens, not fun)
   * and every time the user comes back the access token from the authentication() flow should be re-used (it essentially never expires)
   * @param object $accessToken
   */
  public function setAccessToken($accessToken) {
    $accessToken = json_decode($accessToken, true);
    if ($accessToken == null) {
      throw new apiAuthException("Could not json decode the access token");
    }
    if (! isset($accessToken['key']) || ! isset($accessToken['secret'])) {
      throw new apiAuthException("Invalid OAuth token, missing key and/or secret");
    }
    $this->accessToken = new apiClientOAuthConsumer($accessToken['key'], $accessToken['secret']);
  }

  /**
   * Returns the current access token
   */
  public function getAccessToken() {
    return $this->accessToken;
  }


  /**
   * Set the developer key to use, these are obtained through the API Console
   */
  public function setDeveloperKey($developerKey) {
    $this->developerKey = $developerKey;
  }

  /**
   * Upgrades an existing request token to an access token.
   *
   * @param apiCache $cache cache class to use (file,apc,memcache,mysql)
   * @param oauthVerifier
   */
  public function upgradeRequestToken($requestToken, $requestTokenSecret, $oauthVerifier) {
    $ret = $this->requestAccessToken($requestToken, $requestTokenSecret, $oauthVerifier);
    $matches = array();
    @parse_str($ret, $matches);
    if (!isset($matches['oauth_token']) || !isset($matches['oauth_token_secret'])) {
      throw new apiAuthException("Error authorizing access key (result was: {$ret})");
    }
    // The token was upgraded to an access token, we can now continue to use it.
    $this->accessToken = new apiClientOAuthConsumer(apiClientOAuthUtil::urldecodeRFC3986($matches['oauth_token']), apiClientOAuthUtil::urldecodeRFC3986($matches['oauth_token_secret']));
    return $this->accessToken;
  }

  /**
   * Sends the actual request to exchange an existing request token for an access token.
   *
   * @param string $requestToken the existing request token
   * @param string $requestTokenSecret the request token secret
   * @return array('http_code' => HTTP response code (200, 404, 401, etc), 'data' => the html document)
   */
  protected function requestAccessToken($requestToken, $requestTokenSecret, $oauthVerifier) {
    $accessToken = new apiClientOAuthConsumer($requestToken, $requestTokenSecret);
    $accessRequest = apiClientOAuthRequest::from_consumer_and_token($this->consumerToken, $accessToken, "GET", $this->service['access_token_url'], array('oauth_verifier' => $oauthVerifier));
    $accessRequest->sign_request($this->signatureMethod, $this->consumerToken, $accessToken);
    $request = apiClient::$io->makeRequest(new apiHttpRequest($accessRequest));
    if ($request->getResponseHttpCode() != 200) {
      throw new apiAuthException("Could not fetch access token, http code: " . $request->getResponseHttpCode() . ', response body: '. $request->getResponseBody());
    }
    return $request->getResponseBody();
  }

  /**
   * Obtains a request token from the specified provider.
   */
  public function obtainRequestToken($callbackUrl, $uid) {
    $callbackParams = (strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?') . 'uid=' . urlencode($uid);
    $ret = $this->requestRequestToken($callbackUrl . $callbackParams);
    $matches = array();
    preg_match('/oauth_token=(.*)&oauth_token_secret=(.*)&oauth_callback_confirmed=(.*)/', $ret, $matches);
    if (!is_array($matches) || count($matches) != 4) {
      throw new apiAuthException("Error retrieving request key ({$ret})");
    }
    return new apiClientOAuthToken(apiClientOAuthUtil::urldecodeRFC3986($matches[1]), apiClientOAuthUtil::urldecodeRFC3986($matches[2]));
  }

  /**
   * Sends the actual request to obtain a request token.
   *
   * @return array('http_code' => HTTP response code (200, 404, 401, etc), 'data' => the html document)
   */
  protected function requestRequestToken($callbackUrl) {
    $requestTokenRequest = apiClientOAuthRequest::from_consumer_and_token($this->consumerToken, NULL, "GET", $this->service['request_token_url'], array());
    $requestTokenRequest->set_parameter('scope', $this->service['scope']);
    $requestTokenRequest->set_parameter('oauth_callback', $callbackUrl);
    $requestTokenRequest->sign_request($this->signatureMethod, $this->consumerToken, NULL);
    $request = apiClient::$io->makeRequest(new apiHttpRequest($requestTokenRequest));
    if ($request->getResponseHttpCode() != 200) {
      throw new apiAuthException("Couldn't fetch request token, http code: " . $request->getResponseHttpCode() . ', response body: '. $request->getResponseBody());
    }
    return $request->getResponseBody();
  }

  /**
   * Redirect the uset to the (provider's) authorize page, if approved it should kick the user back to the call back URL
   * which hopefully means we'll end up in the constructor of this class again, but with oauth_continue=1 set
   *
   * @param OAuthToken $token the request token
   * @param string $callbackUrl the URL to return to post-authorization (passed to login site)
   */
  public function redirectToAuthorization($token) {
    $authorizeRedirect = $this->service['authorization_token_url']. $token->key;
    header("Location: $authorizeRedirect");
  }

  /**
   * Sign the request using OAuth. This uses the consumer token and key
   *
   * @param string $method the method (get/put/delete/post)
   * @param string $url the url to sign (http://site/social/rest/people/1/@me)
   * @param array $params the params that should be appended to the url (count=20 fields=foo, etc)
   * @param string $postBody for POST/PUT requests, the postBody is included in the signature
   * @return string the signed url
   */
  public function sign(apiHttpRequest $request) {
    // add the developer key to the request before signing it
    if ($this->developerKey) {
      $request->setUrl($request->getUrl() . ((strpos($request->getUrl(), '?') === false) ? '?' : '&') . 'key='.urlencode($this->developerKey));
    }
    // and sign the request
    $oauthRequest = apiClientOAuthRequest::from_request($request->getMethod(), $request->getBaseUrl(), $request->getQueryParams());
    $params = $this->mergeParameters($request->getQueryParams());
    foreach ($params as $key => $val) {
      if (is_array($val)) {
        $val = implode(',', $val);
      }
      $oauthRequest->set_parameter($key, $val);
    }
    $oauthRequest->sign_request($this->signatureMethod, $this->consumerToken, $this->accessToken);
    $authHeaders = $oauthRequest->to_header();
    $headers = $request->getHeaders();
    $headers[] = $authHeaders;
    $request->setHeaders($headers);
    // and add the access token key to it (since it doesn't include the secret, it's still secure to store this in cache)
    $request->accessKey = $this->accessToken->key;
    return $request;
  }

  /**
   * Merges the supplied parameters with reasonable defaults for 2 legged oauth. User-supplied parameters
   * will have precedent over the defaults.
   *
   * @param array $params the user-supplied params that will be appended to the url
   * @return array the combined parameters
   */
  protected function mergeParameters($params) {
    $defaults = array(
      'oauth_nonce' => md5(microtime() . mt_rand()),
      'oauth_version' => apiClientOAuthRequest::$version, 'oauth_timestamp' => time(),
      'oauth_consumer_key' => $this->consumerToken->key
    );
    if ($this->accessToken != null) {
      $params['oauth_token'] = $this->accessToken->key;
    }
    return array_merge($defaults, $params);
  }

  public function createAuthUrl($scope) {return null;}
  public function refreshToken($refreshToken) {/* noop*/}
  public function revokeToken() {/* noop*/}
}