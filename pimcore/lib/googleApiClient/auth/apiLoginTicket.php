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
 * Class to hold information about an authenticated login.
 *
 * @author Brian Eaton <beaton@google.com>
 */
class apiLoginTicket {
  const USER_ATTR = "id";

  // Information from id token envelope.
  private $envelope;

  // Information from id token payload.
  private $payload;

  /**
   * Creates a user based on the supplied token.
   *
   * envelope: header from a verified authentication token.
   * payload: information from a verified authentication token.
   */
  public function __construct($envelope, $payload) {
    $this->envelope = $envelope;
    $this->payload = $payload;
  }

  /**
   * Returns the numeric identifier for the user.
   */
  public function getUserId() {
    if (array_key_exists(self::USER_ATTR, $this->payload)) {
      return $this->payload[self::USER_ATTR];
    }
    throw new apiAuthException("No user_id in token");
  }

  /**
   * Returns attributes from the login ticket.  This can contain
   * various information about the user session.
   */
  public function getAttributes() {
    return array("envelope" => $this->envelope, "payload" => $this->payload);
  }
}
