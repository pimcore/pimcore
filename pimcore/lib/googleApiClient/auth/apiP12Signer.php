<?php
/*
 * Copyright 2011 Google Inc.
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

/**
 * Signs data.
 *
 * Only used for testing.
 *
 * @author Brian Eaton <beaton@google.com>
 */
class apiP12Signer extends apiSigner {
  // OpenSSL private key resource
  private $privateKey;

  // Creates a new signer from a .p12 file.
  function __construct($p12, $password) {
    if (!function_exists('openssl_x509_read')) {
      throw new Exception(
          'The Google PHP API library needs the openssl PHP extension');
    }
    // This throws on error
    $certs = array();
    if (!openssl_pkcs12_read($p12, $certs, $password)) {
      throw new apiAuthException("Unable to parse the p12 file.  " .
          "Is this a .p12 file?  Is the password correct?  OpenSSL error: " .
          openssl_error_string());
    }
    // TODO(beaton): is this part of the contract for the openssl_pkcs12_read
    // method?  What happens if there are multiple private keys?  Do we care?
    if (!array_key_exists("pkey", $certs) || !$certs["pkey"]) {
      throw new apiAuthException("No private key found in p12 file.");
    }
    $this->privateKey = openssl_pkey_get_private($certs["pkey"]);
    if (!$this->privateKey) {
      throw new apiAuthException("Unable to load private key in ");
    }
  }

  function __destruct() {
    if ($this->privateKey) {
      openssl_pkey_free($this->privateKey);
    }
  }

  function sign($data) {
    if (!openssl_sign($data, $signature, $this->privateKey, "sha256")) {
      throw new apiAuthException("Unable to sign data");
    }
    return $signature;
  }
}
