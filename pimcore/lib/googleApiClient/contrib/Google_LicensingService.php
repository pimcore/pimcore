<?php
/*
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
   * The "licenseAssignments" collection of methods.
   * Typical usage is:
   *  <code>
   *   $licensingService = new Google_LicensingService(...);
   *   $licenseAssignments = $licensingService->licenseAssignments;
   *  </code>
   */
  class Google_LicenseAssignmentsServiceResource extends Google_ServiceResource {

    /**
     * Revoke License. (licenseAssignments.delete)
     *
     * @param string $productId Name for product
     * @param string $skuId Name for sku
     * @param string $userId email id or unique Id of the user
     * @param array $optParams Optional parameters.
     */
    public function delete($productId, $skuId, $userId, $optParams = array()) {
      $params = array('productId' => $productId, 'skuId' => $skuId, 'userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
    /**
     * Get license assignment of a particular product and sku for a user (licenseAssignments.get)
     *
     * @param string $productId Name for product
     * @param string $skuId Name for sku
     * @param string $userId email id or unique Id of the user
     * @param array $optParams Optional parameters.
     * @return Google_LicenseAssignment
     */
    public function get($productId, $skuId, $userId, $optParams = array()) {
      $params = array('productId' => $productId, 'skuId' => $skuId, 'userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_LicenseAssignment($data);
      } else {
        return $data;
      }
    }
    /**
     * Assign License. (licenseAssignments.insert)
     *
     * @param string $productId Name for product
     * @param string $skuId Name for sku
     * @param Google_LicenseAssignmentInsert $postBody
     * @param array $optParams Optional parameters.
     * @return Google_LicenseAssignment
     */
    public function insert($productId, $skuId, Google_LicenseAssignmentInsert $postBody, $optParams = array()) {
      $params = array('productId' => $productId, 'skuId' => $skuId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_LicenseAssignment($data);
      } else {
        return $data;
      }
    }
    /**
     * List license assignments for given product of the customer. (licenseAssignments.listForProduct)
     *
     * @param string $productId Name for product
     * @param string $customerId CustomerId represents the customer for whom licenseassignments are queried
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults Maximum number of campaigns to return at one time. Must be positive. Optional. Default value is 100.
     * @opt_param string pageToken Token to fetch the next page.Optional. By default server will return first page
     * @return Google_LicenseAssignmentList
     */
    public function listForProduct($productId, $customerId, $optParams = array()) {
      $params = array('productId' => $productId, 'customerId' => $customerId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('listForProduct', array($params));
      if ($this->useObjects()) {
        return new Google_LicenseAssignmentList($data);
      } else {
        return $data;
      }
    }
    /**
     * List license assignments for given product and sku of the customer.
     * (licenseAssignments.listForProductAndSku)
     *
     * @param string $productId Name for product
     * @param string $skuId Name for sku
     * @param string $customerId CustomerId represents the customer for whom licenseassignments are queried
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults Maximum number of campaigns to return at one time. Must be positive. Optional. Default value is 100.
     * @opt_param string pageToken Token to fetch the next page.Optional. By default server will return first page
     * @return Google_LicenseAssignmentList
     */
    public function listForProductAndSku($productId, $skuId, $customerId, $optParams = array()) {
      $params = array('productId' => $productId, 'skuId' => $skuId, 'customerId' => $customerId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('listForProductAndSku', array($params));
      if ($this->useObjects()) {
        return new Google_LicenseAssignmentList($data);
      } else {
        return $data;
      }
    }
    /**
     * Assign License. This method supports patch semantics. (licenseAssignments.patch)
     *
     * @param string $productId Name for product
     * @param string $skuId Name for sku for which license would be revoked
     * @param string $userId email id or unique Id of the user
     * @param Google_LicenseAssignment $postBody
     * @param array $optParams Optional parameters.
     * @return Google_LicenseAssignment
     */
    public function patch($productId, $skuId, $userId, Google_LicenseAssignment $postBody, $optParams = array()) {
      $params = array('productId' => $productId, 'skuId' => $skuId, 'userId' => $userId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Google_LicenseAssignment($data);
      } else {
        return $data;
      }
    }
    /**
     * Assign License. (licenseAssignments.update)
     *
     * @param string $productId Name for product
     * @param string $skuId Name for sku for which license would be revoked
     * @param string $userId email id or unique Id of the user
     * @param Google_LicenseAssignment $postBody
     * @param array $optParams Optional parameters.
     * @return Google_LicenseAssignment
     */
    public function update($productId, $skuId, $userId, Google_LicenseAssignment $postBody, $optParams = array()) {
      $params = array('productId' => $productId, 'skuId' => $skuId, 'userId' => $userId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Google_LicenseAssignment($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Google_Licensing (v1).
 *
 * <p>
 * Licensing API to view and manage license for your domain.
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://developers.google.com/google-apps/licensing/" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_LicensingService extends Google_Service {
  public $licenseAssignments;
  /**
   * Constructs the internal representation of the Licensing service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client) {
    $this->servicePath = 'apps/licensing/v1/product/';
    $this->version = 'v1';
    $this->serviceName = 'licensing';

    $client->addService($this->serviceName, $this->version);
    $this->licenseAssignments = new Google_LicenseAssignmentsServiceResource($this, $this->serviceName, 'licenseAssignments', json_decode('{"methods": {"delete": {"id": "licensing.licenseAssignments.delete", "path": "{productId}/sku/{skuId}/user/{userId}", "httpMethod": "DELETE", "parameters": {"productId": {"type": "string", "required": true, "location": "path"}, "skuId": {"type": "string", "required": true, "location": "path"}, "userId": {"type": "string", "required": true, "location": "path"}}}, "get": {"id": "licensing.licenseAssignments.get", "path": "{productId}/sku/{skuId}/user/{userId}", "httpMethod": "GET", "parameters": {"productId": {"type": "string", "required": true, "location": "path"}, "skuId": {"type": "string", "required": true, "location": "path"}, "userId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "LicenseAssignment"}}, "insert": {"id": "licensing.licenseAssignments.insert", "path": "{productId}/sku/{skuId}/user", "httpMethod": "POST", "parameters": {"productId": {"type": "string", "required": true, "location": "path"}, "skuId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "LicenseAssignmentInsert"}, "response": {"$ref": "LicenseAssignment"}}, "listForProduct": {"id": "licensing.licenseAssignments.listForProduct", "path": "{productId}/users", "httpMethod": "GET", "parameters": {"customerId": {"type": "string", "required": true, "location": "query"}, "maxResults": {"type": "integer", "default": "100", "format": "uint32", "minimum": "1", "maximum": "1000", "location": "query"}, "pageToken": {"type": "string", "default": "", "location": "query"}, "productId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "LicenseAssignmentList"}}, "listForProductAndSku": {"id": "licensing.licenseAssignments.listForProductAndSku", "path": "{productId}/sku/{skuId}/users", "httpMethod": "GET", "parameters": {"customerId": {"type": "string", "required": true, "location": "query"}, "maxResults": {"type": "integer", "default": "100", "format": "uint32", "minimum": "1", "maximum": "1000", "location": "query"}, "pageToken": {"type": "string", "default": "", "location": "query"}, "productId": {"type": "string", "required": true, "location": "path"}, "skuId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "LicenseAssignmentList"}}, "patch": {"id": "licensing.licenseAssignments.patch", "path": "{productId}/sku/{skuId}/user/{userId}", "httpMethod": "PATCH", "parameters": {"productId": {"type": "string", "required": true, "location": "path"}, "skuId": {"type": "string", "required": true, "location": "path"}, "userId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "LicenseAssignment"}, "response": {"$ref": "LicenseAssignment"}}, "update": {"id": "licensing.licenseAssignments.update", "path": "{productId}/sku/{skuId}/user/{userId}", "httpMethod": "PUT", "parameters": {"productId": {"type": "string", "required": true, "location": "path"}, "skuId": {"type": "string", "required": true, "location": "path"}, "userId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "LicenseAssignment"}, "response": {"$ref": "LicenseAssignment"}}}}', true));

  }
}



class Google_LicenseAssignment extends Google_Model {
  public $etags;
  public $kind;
  public $productId;
  public $selfLink;
  public $skuId;
  public $userId;
  public function setEtags( $etags) {
    $this->etags = $etags;
  }
  public function getEtags() {
    return $this->etags;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setProductId( $productId) {
    $this->productId = $productId;
  }
  public function getProductId() {
    return $this->productId;
  }
  public function setSelfLink( $selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
  public function setSkuId( $skuId) {
    $this->skuId = $skuId;
  }
  public function getSkuId() {
    return $this->skuId;
  }
  public function setUserId( $userId) {
    $this->userId = $userId;
  }
  public function getUserId() {
    return $this->userId;
  }
}

class Google_LicenseAssignmentInsert extends Google_Model {
  public $userId;
  public function setUserId( $userId) {
    $this->userId = $userId;
  }
  public function getUserId() {
    return $this->userId;
  }
}

class Google_LicenseAssignmentList extends Google_Model {
  public $etag;
  protected $__itemsType = 'Google_LicenseAssignment';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setItems(/* array(Google_LicenseAssignment) */ $items) {
    $this->assertIsArray($items, 'Google_LicenseAssignment', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setNextPageToken( $nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
}
