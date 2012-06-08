<?php
/*
 * Copyright 2010 Google Inc.
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
 * Wrapper for the (experimental!) JSON-RPC protocol, for production use regular REST calls instead
 *
 * @author Chris Chabot <chabotc@google.com>
 */
class apiBatch {

  /**
   * Execute one or multiple Google API requests, takes one or multiple requests as param
   * Example usage:
   *   $ret = apiBatch::execute(
   *     $apiClient->activities->list(array('@public', '@me'), 'listActivitiesKey'),
   *     $apiClient->people->get(array('userId' => '@me'), 'getPeopleKey')
   *   );
   *   print_r($ret['getPeopleKey']);
   */
  static public function execute( /* polymorphic */) {
    $requests = func_get_args();
    return apiRPC::execute($requests);
  }

}