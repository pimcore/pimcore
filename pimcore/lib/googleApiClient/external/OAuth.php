<?php

/* Generic exception class
 */
class apiClientOAuthException extends Exception {
  // pass
}

class apiClientOAuthConsumer {
  public $key;
  public $secret;

  public function __construct($key, $secret, $callback_url=NULL) {
    $this->key = $key;
    $this->secret = $secret;
    $this->callback_url = $callback_url;
  }
}

class apiClientOAuthToken {
  // access tokens and request tokens
  public $key;
  public $secret;

  /**
   * key = the token
   * secret = the token secret
   */
  function __construct($key, $secret) {
    $this->key = $key;
    $this->secret = $secret;
  }

  /**
   * generates the basic string serialization of a token that a server
   * would respond to request_token and access_token calls with
   */
  function to_string() {
    return "oauth_token=" . apiClientOAuthUtil::urlencodeRFC3986($this->key) .
        "&oauth_token_secret=" . apiClientOAuthUtil::urlencodeRFC3986($this->secret);
  }

  function __toString() {
    return $this->to_string();
  }
}

class apiClientOAuthSignatureMethod {
  public function check_signature(&$request, $consumer, $token, $signature) {
    $built = $this->build_signature($request, $consumer, $token);
    return $built == $signature;
  }
}

class apiClientOAuthSignatureMethod_HMAC_SHA1 extends apiClientOAuthSignatureMethod {
  function get_name() {
    return "HMAC-SHA1";
  }

  public function build_signature($request, $consumer, $token, $privKey=NULL) {
    $base_string = $request->get_signature_base_string();
    $request->base_string = $base_string;

    $key_parts = array(
      $consumer->secret,
      ($token) ? $token->secret : ""
    );

    $key_parts = array_map(array('apiClientOAuthUtil','urlencodeRFC3986'), $key_parts);
    $key = implode('&', $key_parts);

    return base64_encode( hash_hmac('sha1', $base_string, $key, true));
  }
}

class apiClientOAuthSignatureMethod_RSA_SHA1 extends apiClientOAuthSignatureMethod {
  public function get_name() {
    return "RSA-SHA1";
  }

  protected function fetch_public_cert(&$request) {
    // not implemented yet, ideas are:
    // (1) do a lookup in a table of trusted certs keyed off of consumer
    // (2) fetch via http using a url provided by the requester
    // (3) some sort of specific discovery code based on request
    //
    // either way should return a string representation of the certificate
    throw Exception("fetch_public_cert not implemented");
  }

  protected function fetch_private_cert($privKey) {//&$request) {
    // not implemented yet, ideas are:
    // (1) do a lookup in a table of trusted certs keyed off of consumer
    //
    // either way should return a string representation of the certificate
    throw Exception("fetch_private_cert not implemented");
  }

  public function build_signature(&$request, $consumer, $token, $privKey) {
    $base_string = $request->get_signature_base_string();

    // Fetch the private key cert based on the request
    //$cert = $this->fetch_private_cert($consumer->privKey);

    //Pull the private key ID from the certificate
    //$privatekeyid = openssl_get_privatekey($cert);

    // hacked in
    if ($privKey == '') {
      $fp = fopen($GLOBALS['PRIV_KEY_FILE'], "r");
      $privKey = fread($fp, 8192);
      fclose($fp);
    }
    $privatekeyid = openssl_get_privatekey($privKey);

    //Check the computer signature against the one passed in the query
    $ok = openssl_sign($base_string, $signature, $privatekeyid);

    //Release the key resource
    openssl_free_key($privatekeyid);

    return base64_encode($signature);
  }

  public function check_signature(&$request, $consumer, $token, $signature) {
    $decoded_sig = base64_decode($signature);

    $base_string = $request->get_signature_base_string();

    // Fetch the public key cert based on the request
    $cert = $this->fetch_public_cert($request);

    //Pull the public key ID from the certificate
    $publickeyid = openssl_get_publickey($cert);

    //Check the computer signature against the one passed in the query
    $ok = openssl_verify($base_string, $decoded_sig, $publickeyid);

    //Release the key resource
    openssl_free_key($publickeyid);

    return $ok == 1;
  }
}

class apiClientOAuthRequest {
  private $parameters;
  private $http_method;
  private $http_url;
  // for debug purposes
  public $base_string;
  public static $version = '1.0';

  function __construct($http_method, $http_url, $parameters=NULL) {
    @$parameters or $parameters = array();
    $this->parameters = $parameters;
    $this->http_method = $http_method;
    $this->http_url = $http_url;
  }


  /**
   * attempt to build up a request from what was passed to the server
   */
  public static function from_request($http_method=NULL, $http_url=NULL, $parameters=NULL) {
    $scheme = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") ? 'http' : 'https';
    @$http_url or $http_url = $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    @$http_method or $http_method = $_SERVER['REQUEST_METHOD'];

    $request_headers = apiClientOAuthRequest::get_headers();

    // let the library user override things however they'd like, if they know
    // which parameters to use then go for it, for example XMLRPC might want to
    // do this
    if ($parameters) {
      $req = new apiClientOAuthRequest($http_method, $http_url, $parameters);
    }
    // next check for the auth header, we need to do some extra stuff
    // if that is the case, namely suck in the parameters from GET or POST
    // so that we can include them in the signature
    else if (@substr($request_headers['Authorization'], 0, 5) == "OAuth") {
      $header_parameters = apiClientOAuthRequest::split_header($request_headers['Authorization']);
      if ($http_method == "GET") {
        $req_parameters = $_GET;
      }
      else if ($http_method = "POST") {
        $req_parameters = $_POST;
      }
      $parameters = array_merge($header_parameters, $req_parameters);
      $req = new apiClientOAuthRequest($http_method, $http_url, $parameters);
    }
    else if ($http_method == "GET") {
      $req = new apiClientOAuthRequest($http_method, $http_url, $_GET);
    }
    else if ($http_method == "POST") {
      $req = new apiClientOAuthRequest($http_method, $http_url, $_POST);
    }
    return $req;
  }

  /**
   * pretty much a helper function to set up the request
   */
  public static function from_consumer_and_token($consumer, $token, $http_method, $http_url, $parameters=NULL) {
    @$parameters or $parameters = array();
    $defaults = array("oauth_version" => apiClientOAuthRequest::$version,
                      "oauth_nonce" => apiClientOAuthRequest::generate_nonce(),
                      "oauth_timestamp" => apiClientOAuthRequest::generate_timestamp(),
                      "oauth_consumer_key" => $consumer->key);
    $parameters = array_merge($defaults, $parameters);

    if ($token) {
      $parameters['oauth_token'] = $token->key;
    }

    // oauth v1.0a
    /*if (isset($_REQUEST['oauth_verifier'])) {
      $parameters['oauth_verifier'] = $_REQUEST['oauth_verifier'];
    }*/


    return new apiClientOAuthRequest($http_method, $http_url, $parameters);
  }

  public function set_parameter($name, $value) {
    $this->parameters[$name] = $value;
  }

  public function get_parameter($name) {
    return $this->parameters[$name];
  }

  public function get_parameters() {
    return $this->parameters;
  }

  /**
   * Returns the normalized parameters of the request
   *
   * This will be all (except oauth_signature) parameters,
   * sorted first by key, and if duplicate keys, then by
   * value.
   *
   * The returned string will be all the key=value pairs
   * concated by &.
   *
   * @return string
   */
  public function get_signable_parameters() {
    // Grab all parameters
    $params = $this->parameters;

    // Remove oauth_signature if present
    if (isset($params['oauth_signature'])) {
      unset($params['oauth_signature']);
    }

    // Urlencode both keys and values
    $keys = array_map(array('apiClientOAuthUtil', 'urlencodeRFC3986'), array_keys($params));
    $values = array_map(array('apiClientOAuthUtil', 'urlencodeRFC3986'), array_values($params));
    $params = array_combine($keys, $values);

    // Sort by keys (natsort)
    uksort($params, 'strnatcmp');

if(isset($params['title']) && isset($params['title-exact'])) {
    $temp = $params['title-exact'];
    $title = $params['title'];

    unset($params['title']);
    unset($params['title-exact']);

    $params['title-exact'] = $temp;
    $params['title'] = $title;
}

    // Generate key=value pairs
    $pairs = array();
    foreach ($params as $key=>$value ) {
      if (is_array($value)) {
        // If the value is an array, it's because there are multiple
        // with the same key, sort them, then add all the pairs
        natsort($value);
        foreach ($value as $v2) {
          $pairs[] = $key . '=' . $v2;
        }
      } else {
        $pairs[] = $key . '=' . $value;
      }
    }

    // Return the pairs, concated with &
    return implode('&', $pairs);
  }

  /**
   * Returns the base string of this request
   *
   * The base string defined as the method, the url
   * and the parameters (normalized), each urlencoded
   * and the concated with &.
   */
  public function get_signature_base_string() {
    $parts = array(
      $this->get_normalized_http_method(),
      $this->get_normalized_http_url(),
      $this->get_signable_parameters()
    );

    $parts = array_map(array('apiClientOAuthUtil', 'urlencodeRFC3986'), $parts);

    return implode('&', $parts);
  }

  /**
   * just uppercases the http method
   */
  public function get_normalized_http_method() {
    return strtoupper($this->http_method);
  }

  /**
   * parses the url and rebuilds it to be
   * scheme://host/path
   */
  public function get_normalized_http_url() {
    $parts = parse_url($this->http_url);

    // FIXME: port should handle according to http://groups.google.com/group/oauth/browse_thread/thread/1b203a51d9590226
    $port = (isset($parts['port']) && $parts['port'] != '80') ? ':' . $parts['port'] : '';
    $path = (isset($parts['path'])) ? $parts['path'] : '';

    return $parts['scheme'] . '://' . $parts['host'] . $port . $path;
  }

  /**
   * builds a url usable for a GET request
   */
  public function to_url() {
    $out = $this->get_normalized_http_url() . "?";
    $out .= $this->to_postdata();
    return $out;
  }

  /**
   * builds the data one would send in a POST request
   */
  public function to_postdata() {
    $total = array();
    foreach ($this->parameters as $k => $v) {
      $total[] = apiClientOAuthUtil::urlencodeRFC3986($k) . "=" . apiClientOAuthUtil::urlencodeRFC3986($v);
    }
    $out = implode("&", $total);
    return $out;
  }

  /**
   * builds the Authorization: header
   */
  public function to_header() {
    $out ='Authorization: OAuth ';
    $total = array();
    foreach ($this->parameters as $k => $v) {
      if (substr($k, 0, 5) != "oauth") continue;
      $out .= apiClientOAuthUtil::urlencodeRFC3986($k) . '="' . apiClientOAuthUtil::urlencodeRFC3986($v) . '", ';
    }
    $out = substr_replace($out, '', strlen($out) - 2);
    return $out;
  }

  public function __toString() {
    return $this->to_url();
  }


  public function sign_request($signature_method, $consumer, $token, $privKey=NULL) {
    $this->set_parameter("oauth_signature_method", $signature_method->get_name());
    $signature = $this->build_signature($signature_method, $consumer, $token, $privKey);
    $this->set_parameter("oauth_signature", $signature);
  }

  public function build_signature($signature_method, $consumer, $token, $privKey=NULL) {
    $signature = $signature_method->build_signature($this, $consumer, $token, $privKey);
    return $signature;
  }

  /**
   * util function: current timestamp
   */
  private static function generate_timestamp() {
    return time();
  }

  /**
   * util function: current nonce
   */
  private static function generate_nonce() {
    $mt = microtime();
    $rand = mt_rand();

    return md5($mt . $rand); // md5s look nicer than numbers
  }

  /**
   * util function for turning the Authorization: header into
   * parameters, has to do some unescaping
   */
  private static function split_header($header) {
    // this should be a regex
    // error cases: commas in parameter values
    $parts = explode(",", $header);
    $out = array();
    foreach ($parts as $param) {
      $param = ltrim($param);
      // skip the "realm" param, nobody ever uses it anyway
      if (substr($param, 0, 5) != "oauth") continue;

      $param_parts = explode("=", $param);

      // rawurldecode() used because urldecode() will turn a "+" in the
      // value into a space
      $out[$param_parts[0]] = rawurldecode(substr($param_parts[1], 1, -1));
    }
    return $out;
  }

  /**
   * helper to try to sort out headers for people who aren't running apache
   */
  private static function get_headers() {
    if (function_exists('apache_request_headers')) {
      // we need this to get the actual Authorization: header
      // because apache tends to tell us it doesn't exist
      return apache_request_headers();
    }
    // otherwise we don't have apache and are just going to have to hope
    // that $_SERVER actually contains what we need
    $out = array();
    foreach ($_SERVER as $key => $value) {
      if (substr($key, 0, 5) == "HTTP_") {
        // this is chaos, basically it is just there to capitalize the first
        // letter of every word that is not an initial HTTP and strip HTTP
        // code from przemek
        $key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
        $out[$key] = $value;
      }
    }
    return $out;
  }
}

class apiClientOAuthDataStore {
  function lookup_consumer($consumer_key) {
    // implement me
  }

  function lookup_token($consumer, $token_type, $token) {
    // implement me
  }

  function lookup_nonce($consumer, $token, $nonce, $timestamp) {
    // implement me
  }

  function fetch_request_token($consumer) {
    // return a new token attached to this consumer
  }

  function fetch_access_token($token, $consumer) {
    // return a new access token attached to this consumer
    // for the user associated with this token if the request token
    // is authorized
    // should also invalidate the request token
  }

}

class apiClientOAuthUtil {
  public static function urlencodeRFC3986($string) {
    return str_replace('%7E', '~', rawurlencode($string));
  }

  public static function urldecodeRFC3986($string) {
    return rawurldecode($string);
  }
}
