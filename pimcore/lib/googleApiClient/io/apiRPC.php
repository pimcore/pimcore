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
 * This class implements the experimental JSON-RPC transport for executing apiServiceRequest'
 *
 * @author Chris Chabot <chabotc@google.com>
 */
class apiRPC {
  static public function execute($requests) {
    $jsonRpcRequest = array();
    foreach ($requests as $request) {
      $parameters = array();
      foreach ($request->getParameters() as $parameterName => $parameterVal) {
        $parameters[$parameterName] = $parameterVal['value'];
      }
      $jsonRpcRequest[] = array(
        'id' => $request->getBatchKey(),
        'method' => $request->getRpcName(),
        'params' => $parameters,
      	'apiVersion' => 'v1'
      );
    }
    $httpRequest = new apiHttpRequest($request->getRpcPath());
    $httpRequest->setRequestHeaders(array('Content-Type' => 'application/json'));
    $httpRequest->setRequestMethod('POST');
    $httpRequest->setPostBody(json_encode($jsonRpcRequest));
    $httpRequest = apiClient::$io->authenticatedRequest($httpRequest);
    if (($decodedResponse = json_decode($httpRequest->getResponseBody(), true)) != false) {
      $ret = array();
      foreach ($decodedResponse as $response) {
        $ret[$response['id']] = self::checkNextLink($response['result']);
      }
      return $ret;
    } else {
      throw new apiServiceException("Invalid json returned by the json-rpc end-point");
    }
  }

  static private function checkNextLink($response) {
    if (isset($response['links']) && isset($response['links']['next'][0]['href'])) {
      parse_str($response['links']['next'][0]['href'], $params);
      if (isset($params['c'])) {
        $response['continuationToken'] = $params['c'];
      }
    }
    return $response;
  }
}