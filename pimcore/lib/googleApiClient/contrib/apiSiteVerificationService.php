<?php
/*
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */


  /**
   * The "webResource" collection of methods.
   * Typical usage is:
   *  <code>
   *   $siteVerificationService = new apiSiteVerificationService(...);
   *   $webResource = $siteVerificationService->webResource;
   *  </code>
   */
  class WebResourceServiceResource extends apiServiceResource {


    /**
     * Attempt verification of a website or domain. (webResource.insert)
     *
     * @param string $verificationMethod The method to use for verifying a site or domain.
     * @param SiteVerificationWebResourceResource $postBody
     * @return SiteVerificationWebResourceResource
     */
    public function insert($verificationMethod, SiteVerificationWebResourceResource $postBody, $optParams = array()) {
      $params = array('verificationMethod' => $verificationMethod, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new SiteVerificationWebResourceResource($data);
      } else {
        return $data;
      }
    }
    /**
     * Get the most current data for a website or domain. (webResource.get)
     *
     * @param string $id The id of a verified site or domain.
     * @return SiteVerificationWebResourceResource
     */
    public function get($id, $optParams = array()) {
      $params = array('id' => $id);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new SiteVerificationWebResourceResource($data);
      } else {
        return $data;
      }
    }
    /**
     * Get the list of your verified websites and domains. (webResource.list)
     *
     * @return SiteVerificationWebResourceListResponse
     */
    public function listWebResource($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new SiteVerificationWebResourceListResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Modify the list of owners for your website or domain. (webResource.update)
     *
     * @param string $id The id of a verified site or domain.
     * @param SiteVerificationWebResourceResource $postBody
     * @return SiteVerificationWebResourceResource
     */
    public function update($id, SiteVerificationWebResourceResource $postBody, $optParams = array()) {
      $params = array('id' => $id, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new SiteVerificationWebResourceResource($data);
      } else {
        return $data;
      }
    }
    /**
     * Modify the list of owners for your website or domain. This method supports patch semantics.
     * (webResource.patch)
     *
     * @param string $id The id of a verified site or domain.
     * @param SiteVerificationWebResourceResource $postBody
     * @return SiteVerificationWebResourceResource
     */
    public function patch($id, SiteVerificationWebResourceResource $postBody, $optParams = array()) {
      $params = array('id' => $id, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new SiteVerificationWebResourceResource($data);
      } else {
        return $data;
      }
    }
    /**
     * Get a verification token for placing on a website or domain. (webResource.getToken)
     *
     * @param SiteVerificationWebResourceGettokenRequest $postBody
     * @return SiteVerificationWebResourceGettokenResponse
     */
    public function getToken(SiteVerificationWebResourceGettokenRequest $postBody, $optParams = array()) {
      $params = array('postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('getToken', array($params));
      if ($this->useObjects()) {
        return new SiteVerificationWebResourceGettokenResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Relinquish ownership of a website or domain. (webResource.delete)
     *
     * @param string $id The id of a verified site or domain.
     */
    public function delete($id, $optParams = array()) {
      $params = array('id' => $id);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
  }

/**
 * Service definition for SiteVerification (v1).
 *
 * <p>
 * Lets you programatically verify ownership of websites or domains with Google.
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="http://code.google.com/apis/siteverification/" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class apiSiteVerificationService extends apiService {
  public $webResource;
  /**
   * Constructs the internal representation of the SiteVerification service.
   *
   * @param apiClient apiClient
   */
  public function __construct(apiClient $apiClient) {
    $this->restBasePath = '/siteVerification/v1/';
    $this->version = 'v1';
    $this->serviceName = 'siteVerification';

    $apiClient->addService($this->serviceName, $this->version);
    $this->webResource = new WebResourceServiceResource($this, $this->serviceName, 'webResource', json_decode('{"methods": {"insert": {"scopes": ["https://www.googleapis.com/auth/siteverification", "https://www.googleapis.com/auth/siteverification.verify_only"], "parameters": {"verificationMethod": {"required": true, "type": "string", "location": "query"}}, "request": {"$ref": "SiteVerificationWebResourceResource"}, "id": "siteVerification.webResource.insert", "httpMethod": "POST", "path": "webResource", "response": {"$ref": "SiteVerificationWebResourceResource"}}, "get": {"scopes": ["https://www.googleapis.com/auth/siteverification"], "parameters": {"id": {"required": true, "type": "string", "location": "path"}}, "id": "siteVerification.webResource.get", "httpMethod": "GET", "path": "webResource/{id}", "response": {"$ref": "SiteVerificationWebResourceResource"}}, "list": {"scopes": ["https://www.googleapis.com/auth/siteverification"], "id": "siteVerification.webResource.list", "httpMethod": "GET", "path": "webResource", "response": {"$ref": "SiteVerificationWebResourceListResponse"}}, "update": {"scopes": ["https://www.googleapis.com/auth/siteverification"], "parameters": {"id": {"required": true, "type": "string", "location": "path"}}, "request": {"$ref": "SiteVerificationWebResourceResource"}, "id": "siteVerification.webResource.update", "httpMethod": "PUT", "path": "webResource/{id}", "response": {"$ref": "SiteVerificationWebResourceResource"}}, "patch": {"scopes": ["https://www.googleapis.com/auth/siteverification"], "parameters": {"id": {"required": true, "type": "string", "location": "path"}}, "request": {"$ref": "SiteVerificationWebResourceResource"}, "id": "siteVerification.webResource.patch", "httpMethod": "PATCH", "path": "webResource/{id}", "response": {"$ref": "SiteVerificationWebResourceResource"}}, "getToken": {"scopes": ["https://www.googleapis.com/auth/siteverification", "https://www.googleapis.com/auth/siteverification.verify_only"], "request": {"$ref": "SiteVerificationWebResourceGettokenRequest"}, "response": {"$ref": "SiteVerificationWebResourceGettokenResponse"}, "httpMethod": "POST", "path": "token", "id": "siteVerification.webResource.getToken"}, "delete": {"scopes": ["https://www.googleapis.com/auth/siteverification"], "parameters": {"id": {"required": true, "type": "string", "location": "path"}}, "httpMethod": "DELETE", "path": "webResource/{id}", "id": "siteVerification.webResource.delete"}}}', true));

  }
}

class SiteVerificationWebResourceGettokenRequest extends apiModel {
  public $verificationMethod;
  protected $__siteType = 'SiteVerificationWebResourceGettokenRequestSite';
  protected $__siteDataType = '';
  public $site;
  public function setVerificationMethod($verificationMethod) {
    $this->verificationMethod = $verificationMethod;
  }
  public function getVerificationMethod() {
    return $this->verificationMethod;
  }
  public function setSite(SiteVerificationWebResourceGettokenRequestSite $site) {
    $this->site = $site;
  }
  public function getSite() {
    return $this->site;
  }
}

class SiteVerificationWebResourceGettokenRequestSite extends apiModel {
  public $identifier;
  public $type;
  public function setIdentifier($identifier) {
    $this->identifier = $identifier;
  }
  public function getIdentifier() {
    return $this->identifier;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}

class SiteVerificationWebResourceGettokenResponse extends apiModel {
  public $token;
  public $method;
  public function setToken($token) {
    $this->token = $token;
  }
  public function getToken() {
    return $this->token;
  }
  public function setMethod($method) {
    $this->method = $method;
  }
  public function getMethod() {
    return $this->method;
  }
}

class SiteVerificationWebResourceListResponse extends apiModel {
  protected $__itemsType = 'SiteVerificationWebResourceResource';
  protected $__itemsDataType = 'array';
  public $items;
  public function setItems(/* array(SiteVerificationWebResourceResource) */ $items) {
    $this->assertIsArray($items, 'SiteVerificationWebResourceResource', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
  }
}

class SiteVerificationWebResourceResource extends apiModel {
  public $owners;
  public $id;
  protected $__siteType = 'SiteVerificationWebResourceResourceSite';
  protected $__siteDataType = '';
  public $site;
  public function setOwners(/* array(string) */ $owners) {
    $this->assertIsArray($owners, 'string', __METHOD__);
    $this->owners = $owners;
  }
  public function getOwners() {
    return $this->owners;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setSite(SiteVerificationWebResourceResourceSite $site) {
    $this->site = $site;
  }
  public function getSite() {
    return $this->site;
  }
}

class SiteVerificationWebResourceResourceSite extends apiModel {
  public $identifier;
  public $type;
  public function setIdentifier($identifier) {
    $this->identifier = $identifier;
  }
  public function getIdentifier() {
    return $this->identifier;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}
