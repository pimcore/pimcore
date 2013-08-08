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
   * The "currentLocation" collection of methods.
   * Typical usage is:
   *  <code>
   *   $latitudeService = new Google_LatitudeService(...);
   *   $currentLocation = $latitudeService->currentLocation;
   *  </code>
   */
  class Google_CurrentLocationServiceResource extends Google_ServiceResource {

    /**
     * Deletes the authenticated user's current location. (currentLocation.delete)
     *
     * @param array $optParams Optional parameters.
     */
    public function delete($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
    /**
     * Returns the authenticated user's current location. (currentLocation.get)
     *
     * @param array $optParams Optional parameters.
     *
     * @opt_param string granularity Granularity of the requested location.
     * @return Google_Location
     */
    public function get($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Location($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates or creates the user's current location. (currentLocation.insert)
     *
     * @param Google_Location $postBody
     * @param array $optParams Optional parameters.
     * @return Google_Location
     */
    public function insert(Google_Location $postBody, $optParams = array()) {
      $params = array('postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_Location($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "location" collection of methods.
   * Typical usage is:
   *  <code>
   *   $latitudeService = new Google_LatitudeService(...);
   *   $location = $latitudeService->location;
   *  </code>
   */
  class Google_LocationServiceResource extends Google_ServiceResource {

    /**
     * Deletes a location from the user's location history. (location.delete)
     *
     * @param string $locationId Timestamp of the location to delete (ms since epoch).
     * @param array $optParams Optional parameters.
     */
    public function delete($locationId, $optParams = array()) {
      $params = array('locationId' => $locationId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
    /**
     * Reads a location from the user's location history. (location.get)
     *
     * @param string $locationId Timestamp of the location to read (ms since epoch).
     * @param array $optParams Optional parameters.
     *
     * @opt_param string granularity Granularity of the location to return.
     * @return Google_Location
     */
    public function get($locationId, $optParams = array()) {
      $params = array('locationId' => $locationId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Location($data);
      } else {
        return $data;
      }
    }
    /**
     * Inserts or updates a location in the user's location history. (location.insert)
     *
     * @param Google_Location $postBody
     * @param array $optParams Optional parameters.
     * @return Google_Location
     */
    public function insert(Google_Location $postBody, $optParams = array()) {
      $params = array('postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_Location($data);
      } else {
        return $data;
      }
    }
    /**
     * Lists the user's location history. (location.list)
     *
     * @param array $optParams Optional parameters.
     *
     * @opt_param string granularity Granularity of the requested locations.
     * @opt_param string max-results Maximum number of locations to return.
     * @opt_param string max-time Maximum timestamp of locations to return (ms since epoch).
     * @opt_param string min-time Minimum timestamp of locations to return (ms since epoch).
     * @return Google_LocationFeed
     */
    public function listLocation($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_LocationFeed($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Google_Latitude (v1).
 *
 * <p>
 * Lets you read and update your current location and work with your location history
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://developers.google.com/latitude/v1/using" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_LatitudeService extends Google_Service {
  public $currentLocation;
  public $location;
  /**
   * Constructs the internal representation of the Latitude service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client) {
    $this->servicePath = 'latitude/v1/';
    $this->version = 'v1';
    $this->serviceName = 'latitude';

    $client->addService($this->serviceName, $this->version);
    $this->currentLocation = new Google_CurrentLocationServiceResource($this, $this->serviceName, 'currentLocation', json_decode('{"methods": {"delete": {"id": "latitude.currentLocation.delete", "path": "currentLocation", "httpMethod": "DELETE", "scopes": ["https://www.googleapis.com/auth/latitude.all.best", "https://www.googleapis.com/auth/latitude.all.city", "https://www.googleapis.com/auth/latitude.current.best", "https://www.googleapis.com/auth/latitude.current.city"]}, "get": {"id": "latitude.currentLocation.get", "path": "currentLocation", "httpMethod": "GET", "parameters": {"granularity": {"type": "string", "default": "city", "enum": ["best", "city"], "location": "query"}}, "response": {"$ref": "LatitudeCurrentlocationResourceJson"}, "scopes": ["https://www.googleapis.com/auth/latitude.all.best", "https://www.googleapis.com/auth/latitude.all.city", "https://www.googleapis.com/auth/latitude.current.best", "https://www.googleapis.com/auth/latitude.current.city"]}, "insert": {"id": "latitude.currentLocation.insert", "path": "currentLocation", "httpMethod": "POST", "request": {"$ref": "LatitudeCurrentlocationResourceJson"}, "response": {"$ref": "LatitudeCurrentlocationResourceJson"}, "scopes": ["https://www.googleapis.com/auth/latitude.all.best", "https://www.googleapis.com/auth/latitude.all.city", "https://www.googleapis.com/auth/latitude.current.best", "https://www.googleapis.com/auth/latitude.current.city"]}}}', true));
    $this->location = new Google_LocationServiceResource($this, $this->serviceName, 'location', json_decode('{"methods": {"delete": {"id": "latitude.location.delete", "path": "location/{locationId}", "httpMethod": "DELETE", "parameters": {"locationId": {"type": "string", "required": true, "location": "path"}}, "scopes": ["https://www.googleapis.com/auth/latitude.all.best", "https://www.googleapis.com/auth/latitude.all.city"]}, "get": {"id": "latitude.location.get", "path": "location/{locationId}", "httpMethod": "GET", "parameters": {"granularity": {"type": "string", "default": "city", "enum": ["best", "city"], "location": "query"}, "locationId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "Location"}, "scopes": ["https://www.googleapis.com/auth/latitude.all.best", "https://www.googleapis.com/auth/latitude.all.city"]}, "insert": {"id": "latitude.location.insert", "path": "location", "httpMethod": "POST", "request": {"$ref": "Location"}, "response": {"$ref": "Location"}, "scopes": ["https://www.googleapis.com/auth/latitude.all.best", "https://www.googleapis.com/auth/latitude.all.city"]}, "list": {"id": "latitude.location.list", "path": "location", "httpMethod": "GET", "parameters": {"granularity": {"type": "string", "default": "city", "enum": ["best", "city"], "location": "query"}, "max-results": {"type": "string", "location": "query"}, "max-time": {"type": "string", "location": "query"}, "min-time": {"type": "string", "location": "query"}}, "response": {"$ref": "LocationFeed"}, "scopes": ["https://www.googleapis.com/auth/latitude.all.best", "https://www.googleapis.com/auth/latitude.all.city"]}}}', true));

  }
}



class Google_Location extends Google_Model {
  public $accuracy;
  public $activityId;
  public $altitude;
  public $altitudeAccuracy;
  public $heading;
  public $kind;
  public $latitude;
  public $longitude;
  public $speed;
  public $timestampMs;
  public function setAccuracy( $accuracy) {
    $this->accuracy = $accuracy;
  }
  public function getAccuracy() {
    return $this->accuracy;
  }
  public function setActivityId( $activityId) {
    $this->activityId = $activityId;
  }
  public function getActivityId() {
    return $this->activityId;
  }
  public function setAltitude( $altitude) {
    $this->altitude = $altitude;
  }
  public function getAltitude() {
    return $this->altitude;
  }
  public function setAltitudeAccuracy( $altitudeAccuracy) {
    $this->altitudeAccuracy = $altitudeAccuracy;
  }
  public function getAltitudeAccuracy() {
    return $this->altitudeAccuracy;
  }
  public function setHeading( $heading) {
    $this->heading = $heading;
  }
  public function getHeading() {
    return $this->heading;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setLatitude( $latitude) {
    $this->latitude = $latitude;
  }
  public function getLatitude() {
    return $this->latitude;
  }
  public function setLongitude( $longitude) {
    $this->longitude = $longitude;
  }
  public function getLongitude() {
    return $this->longitude;
  }
  public function setSpeed( $speed) {
    $this->speed = $speed;
  }
  public function getSpeed() {
    return $this->speed;
  }
  public function setTimestampMs( $timestampMs) {
    $this->timestampMs = $timestampMs;
  }
  public function getTimestampMs() {
    return $this->timestampMs;
  }
}

class Google_LocationFeed extends Google_Model {
  protected $__itemsType = 'Google_Location';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(Google_Location) */ $items) {
    $this->assertIsArray($items, 'Google_Location', __METHOD__);
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
}
