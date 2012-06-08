<?php
/**
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
 * Implements the actual methods/resources of the discovered Google API using magic function
 * calling overloading (__call()), which on call will see if the method name (plus.activities.list)
 * is available in this service, and if so construct an apiHttpRequest representing it.
 *
 * @author Chris Chabot <chabotc@google.com>
 * @author Chirag Shah <chirags@google.com>
 *
 */
class apiServiceResource {
  // Valid query parameters that work, but don't appear in discovery.
  private $stackParameters = array(
      'alt' => array('type' => 'string', 'location' => 'query'),
      'boundary' => array('type' => 'string', 'location' => 'query'),
      'fields' => array('type' => 'string', 'location' => 'query'),
      'trace' => array('type' => 'string', 'location' => 'query'),
      'userIp' => array('type' => 'string', 'location' => 'query'),
      'userip' => array('type' => 'string', 'location' => 'query'),
      'file' => array('type' => 'complex', 'location' => 'body'),
      'data' => array('type' => 'string', 'location' => 'body'),
      'mimeType' => array('type' => 'string', 'location' => 'header'),
      'uploadType' => array('type' => 'string', 'location' => 'query'),
      'mediaUpload' => array('type' => 'complex', 'location' => 'query'),
  );

  /** @var apiService $service */
  private $service;

  /** @var string $serviceName */
  private $serviceName;

  /** @var string $resourceName */
  private $resourceName;

  /** @var array $methods */
  private $methods;

  public function __construct($service, $serviceName, $resourceName, $resource) {
    $this->service = $service;
    $this->serviceName = $serviceName;
    $this->resourceName = $resourceName;
    $this->methods = isset($resource['methods']) ? $resource['methods'] : array($resourceName => $resource);
  }

  /**
   * @param $name
   * @param $arguments
   * @return apiHttpRequest|array
   * @throws apiException
   */
  public function __call($name, $arguments) {
    if (! isset($this->methods[$name])) {
      throw new apiException("Unknown function: {$this->serviceName}->{$this->resourceName}->{$name}()");
    }
    $method = $this->methods[$name];
    $parameters = $arguments[0];

    // postBody is a special case since it's not defined in the discovery document as parameter, but we abuse the param entry for storing it
    $postBody = null;
    if (isset($parameters['postBody'])) {
      if (is_object($parameters['postBody'])) {
        $this->stripNull($parameters['postBody']);
      }

      // Some APIs require the postBody to be set under the data key.
      if (is_array($parameters['postBody']) && 'latitude' == $this->serviceName) {
        if (!isset($parameters['postBody']['data'])) {
          $rawBody = $parameters['postBody'];
          unset($parameters['postBody']);
          $parameters['postBody']['data'] = $rawBody;
        }
      }

      $postBody = is_array($parameters['postBody']) || is_object($parameters['postBody'])
          ? json_encode($parameters['postBody'])
          : $parameters['postBody'];
      unset($parameters['postBody']);

      if (isset($parameters['optParams'])) {
        $optParams = $parameters['optParams'];
        unset($parameters['optParams']);
        $parameters = array_merge($parameters, $optParams);
      }
    }

    if (!isset($method['parameters'])) {
      $method['parameters'] = array();
    }
    
    $method['parameters'] = array_merge($method['parameters'], $this->stackParameters);
    foreach ($parameters as $key => $val) {
      if ($key != 'postBody' && ! isset($method['parameters'][$key])) {
        throw new apiException("($name) unknown parameter: '$key'");
      }
    }
    if (isset($method['parameters'])) {
      foreach ($method['parameters'] as $paramName => $paramSpec) {
        if (isset($paramSpec['required']) && $paramSpec['required'] && ! isset($parameters[$paramName])) {
          throw new apiException("($name) missing required param: '$paramName'");
        }
        if (isset($parameters[$paramName])) {
          $value = $parameters[$paramName];
          $parameters[$paramName] = $paramSpec;
          $parameters[$paramName]['value'] = $value;
          unset($parameters[$paramName]['required']);
        } else {
          unset($parameters[$paramName]);
        }
      }
    }

    // Discovery v1.0 puts the canonical method id under the 'id' field.
    if (! isset($method['id'])) {
      $method['id'] = $method['rpcMethod'];
    }

    // Discovery v1.0 puts the canonical path under the 'path' field.
    if (! isset($method['path'])) {
      $method['path'] = $method['restPath'];
    }

    $restBasePath = $this->service->restBasePath;

    // Process Media Request
    $contentType = false;
    if (isset($method['mediaUpload'])) {
      $media = apiMediaFileUpload::process($postBody, $parameters);
      if ($media) {
        $contentType = isset($media['content-type']) ? $media['content-type']: null;
        $postBody = isset($media['postBody']) ? $media['postBody'] : null;
        $restBasePath = $method['mediaUpload']['protocols']['simple']['path'];
        $method['path'] = '';
      }
    }

    $url = apiREST::createRequestUri($restBasePath, $method['path'], $parameters);

    $httpRequest = new apiHttpRequest($url, $method['httpMethod'], null, $postBody);
    if ($postBody) {
      $contentTypeHeader = array();
      if (isset($contentType) && $contentType) {
        $contentTypeHeader['content-type'] = $contentType;
      } else {
        $contentTypeHeader['content-type'] = 'application/json; charset=UTF-8';
        $contentTypeHeader['content-length'] = apiUtils::getStrLen($postBody);
      }
      $httpRequest->setRequestHeaders($contentTypeHeader);
    }

    $httpRequest = apiClient::$auth->sign($httpRequest);
    if (apiClient::$useBatch) {
      return $httpRequest;
    }

    // Terminate immediatly if this is a resumable request.
    if (isset($parameters['uploadType']['value'])
        && 'resumable' == $parameters['uploadType']['value']) {
      return $httpRequest;
    }

    return apiREST::execute($httpRequest);
  }

  protected function useObjects() {
    global $apiConfig;
    return (isset($apiConfig['use_objects']) && $apiConfig['use_objects']);
  }

  protected function stripNull(&$o) {
    $o = (array) $o;
    foreach ($o as $k => $v) {
      if ($v === null || strstr($k, "\0*\0__")) {
        unset($o[$k]);
      }
      elseif (is_object($v) || is_array($v)) {
        $this->stripNull($o[$k]);
      }
    }
  }
}
