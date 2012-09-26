<?php
/*
 * Copyright 2012 Google Inc.
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
 * Credentials object used for OAuth 2.0 Signed JWT assertion grants.
 *
 * @author Chirag Shah <chirags@google.com>
 */
class apiAssertionCredentials {
  const MAX_TOKEN_LIFETIME_SECS = 3600;

  public $serviceAccountName;
  public $scopes;
  public $privateKey;
  public $privateKeyPassword;
  public $assertionType;

  /**
   * @param $serviceAccountName
   * @param $scopes array List of scopes
   * @param $privateKey
   * @param string $privateKeyPassword
   * @param string $assertionType
   */
  public function __construct(
      $serviceAccountName,
      $scopes,
      $privateKey,
      $privateKeyPassword = 'notasecret',
      $assertionType = 'http://oauth.net/grant_type/jwt/1.0/bearer') {
    $this->serviceAccountName = $serviceAccountName;
    $this->scopes = is_string($scopes) ? $scopes : implode(' ', $scopes);
    $this->privateKey = $privateKey;
    $this->privateKeyPassword = $privateKeyPassword;
    $this->assertionType = $assertionType;
  }

  public function generateAssertion() {
    $now = time();

    $jwt = $this->makeSignedJwt(array(
      'aud' => apiOAuth2::OAUTH2_TOKEN_URI,
      'scope' => $this->scopes,
      'iat' => $now,
      'exp' => $now + self::MAX_TOKEN_LIFETIME_SECS,
      'iss' => $this->serviceAccountName,
    ));

    return $jwt;
  }

  /**
   * Creates a signed JWT.
   * @param array $payload
   * @return string The signed JWT.
   */
  private function makeSignedJwt($payload) {
    $header = array('typ' => 'JWT', 'alg' => 'RS256');

    $segments = array(
      apiUtils::urlSafeB64Encode(json_encode($header)),
      apiUtils::urlSafeB64Encode(json_encode($payload))
    );
    $signingInput = implode('.', $segments);

    $signer = new apiP12Signer($this->privateKey, $this->privateKeyPassword);
    $signature = $signer->sign($signingInput);
    $segments[] = apiUtils::urlSafeB64Encode($signature);

    return implode(".", $segments);
  }
}
