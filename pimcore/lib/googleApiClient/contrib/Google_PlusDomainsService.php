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
   * The "activities" collection of methods.
   * Typical usage is:
   *  <code>
   *   $plusService = new Google_PlusService(...);
   *   $activities = $plusService->activities;
   *  </code>
   */
  class Google_ActivitiesServiceResource extends Google_ServiceResource {

    /**
     * Get an activity. (activities.get)
     *
     * @param string $activityId The ID of the activity to get.
     * @param array $optParams Optional parameters.
     * @return Google_Activity
     */
    public function get($activityId, $optParams = array()) {
      $params = array('activityId' => $activityId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Activity($data);
      } else {
        return $data;
      }
    }
    /**
     * Create a new activity for the authenticated user. (activities.insert)
     *
     * @param string $userId The ID of the user to create the activity on behalf of. Its value should be "me", to indicate the authenticated user.
     * @param Google_Activity $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param bool preview If "true", extract the potential media attachments for a url. The response will include all possible attachments for a url, including video, photos, and articles based on the content of the page.
     * @return Google_Activity
     */
    public function insert($userId, Google_Activity $postBody, $optParams = array()) {
      $params = array('userId' => $userId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_Activity($data);
      } else {
        return $data;
      }
    }
    /**
     * List all of the activities in the specified collection for a particular user.
     * (activities.list)
     *
     * @param string $userId The ID of the user to get activities for. The special value "me" can be used to indicate the authenticated user.
     * @param string $collection The collection of activities to list.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults The maximum number of activities to include in the response, which is used for paging. For any response, the actual number returned might be less than the specified maxResults.
     * @opt_param string pageToken The continuation token, which is used to page through large result sets. To get the next page of results, set this parameter to the value of "nextPageToken" from the previous response.
     * @return Google_ActivityFeed
     */
    public function listActivities($userId, $collection, $optParams = array()) {
      $params = array('userId' => $userId, 'collection' => $collection);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_ActivityFeed($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "audiences" collection of methods.
   * Typical usage is:
   *  <code>
   *   $plusService = new Google_PlusService(...);
   *   $audiences = $plusService->audiences;
   *  </code>
   */
  class Google_AudiencesServiceResource extends Google_ServiceResource {

    /**
     * List all of the audiences to which a user can share. (audiences.list)
     *
     * @param string $userId The ID of the user to get audiences for. The special value "me" can be used to indicate the authenticated user.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults The maximum number of circles to include in the response, which is used for paging. For any response, the actual number returned might be less than the specified maxResults.
     * @opt_param string pageToken The continuation token, which is used to page through large result sets. To get the next page of results, set this parameter to the value of "nextPageToken" from the previous response.
     * @return Google_AudiencesFeed
     */
    public function listAudiences($userId, $optParams = array()) {
      $params = array('userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_AudiencesFeed($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "circles" collection of methods.
   * Typical usage is:
   *  <code>
   *   $plusService = new Google_PlusService(...);
   *   $circles = $plusService->circles;
   *  </code>
   */
  class Google_CirclesServiceResource extends Google_ServiceResource {

    /**
     * Add a person to a circle. Google+ limits certain circle operations, including
     * the number of circle adds. Learn More. (circles.addPeople)
     *
     * @param string $circleId The ID of the circle to add the person to.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string email Email of the people to add to the circle. Optional, can be repeated.
     * @opt_param string userId IDs of the people to add to the circle. Optional, can be repeated.
     * @return Google_Circle
     */
    public function addPeople($circleId, $optParams = array()) {
      $params = array('circleId' => $circleId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('addPeople', array($params));
      if ($this->useObjects()) {
        return new Google_Circle($data);
      } else {
        return $data;
      }
    }
    /**
     * Get a circle. (circles.get)
     *
     * @param string $circleId The ID of the circle to get.
     * @param array $optParams Optional parameters.
     * @return Google_Circle
     */
    public function get($circleId, $optParams = array()) {
      $params = array('circleId' => $circleId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Circle($data);
      } else {
        return $data;
      }
    }
    /**
     * Create a new circle for the authenticated user. (circles.insert)
     *
     * @param string $userId The ID of the user to create the circle on behalf of. The value "me" can be used to indicate the authenticated user.
     * @param Google_Circle $postBody
     * @param array $optParams Optional parameters.
     * @return Google_Circle
     */
    public function insert($userId, Google_Circle $postBody, $optParams = array()) {
      $params = array('userId' => $userId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_Circle($data);
      } else {
        return $data;
      }
    }
    /**
     * List all of the circles for a user. (circles.list)
     *
     * @param string $userId The ID of the user to get circles for. The special value "me" can be used to indicate the authenticated user.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults The maximum number of circles to include in the response, which is used for paging. For any response, the actual number returned might be less than the specified maxResults.
     * @opt_param string pageToken The continuation token, which is used to page through large result sets. To get the next page of results, set this parameter to the value of "nextPageToken" from the previous response.
     * @return Google_CircleFeed
     */
    public function listCircles($userId, $optParams = array()) {
      $params = array('userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_CircleFeed($data);
      } else {
        return $data;
      }
    }
    /**
     * Update a circle. This method supports patch semantics. (circles.patch)
     *
     * @param string $circleId The ID of the circle to update.
     * @param Google_Circle $postBody
     * @param array $optParams Optional parameters.
     * @return Google_Circle
     */
    public function patch($circleId, Google_Circle $postBody, $optParams = array()) {
      $params = array('circleId' => $circleId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Google_Circle($data);
      } else {
        return $data;
      }
    }
    /**
     * Delete a circle. (circles.remove)
     *
     * @param string $circleId The ID of the circle to delete.
     * @param array $optParams Optional parameters.
     */
    public function remove($circleId, $optParams = array()) {
      $params = array('circleId' => $circleId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('remove', array($params));
      return $data;
    }
    /**
     * Remove a person from a circle. (circles.removePeople)
     *
     * @param string $circleId The ID of the circle to remove the person from.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string email Email of the people to add to the circle. Optional, can be repeated.
     * @opt_param string userId IDs of the people to remove from the circle. Optional, can be repeated.
     */
    public function removePeople($circleId, $optParams = array()) {
      $params = array('circleId' => $circleId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('removePeople', array($params));
      return $data;
    }
    /**
     * Update a circle. (circles.update)
     *
     * @param string $circleId The ID of the circle to update.
     * @param Google_Circle $postBody
     * @param array $optParams Optional parameters.
     * @return Google_Circle
     */
    public function update($circleId, Google_Circle $postBody, $optParams = array()) {
      $params = array('circleId' => $circleId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Google_Circle($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "comments" collection of methods.
   * Typical usage is:
   *  <code>
   *   $plusService = new Google_PlusService(...);
   *   $comments = $plusService->comments;
   *  </code>
   */
  class Google_CommentsServiceResource extends Google_ServiceResource {

    /**
     * Get a comment. (comments.get)
     *
     * @param string $commentId The ID of the comment to get.
     * @param array $optParams Optional parameters.
     * @return Google_Comment
     */
    public function get($commentId, $optParams = array()) {
      $params = array('commentId' => $commentId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Comment($data);
      } else {
        return $data;
      }
    }
    /**
     * Create a new comment in reply to an activity. (comments.insert)
     *
     * @param string $activityId The ID of the activity to reply to.
     * @param Google_Comment $postBody
     * @param array $optParams Optional parameters.
     * @return Google_Comment
     */
    public function insert($activityId, Google_Comment $postBody, $optParams = array()) {
      $params = array('activityId' => $activityId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_Comment($data);
      } else {
        return $data;
      }
    }
    /**
     * List all of the comments for an activity. (comments.list)
     *
     * @param string $activityId The ID of the activity to get comments for.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults The maximum number of comments to include in the response, which is used for paging. For any response, the actual number returned might be less than the specified maxResults.
     * @opt_param string pageToken The continuation token, which is used to page through large result sets. To get the next page of results, set this parameter to the value of "nextPageToken" from the previous response.
     * @opt_param string sortOrder The order in which to sort the list of comments.
     * @return Google_CommentFeed
     */
    public function listComments($activityId, $optParams = array()) {
      $params = array('activityId' => $activityId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_CommentFeed($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "media" collection of methods.
   * Typical usage is:
   *  <code>
   *   $plusService = new Google_PlusService(...);
   *   $media = $plusService->media;
   *  </code>
   */
  class Google_MediaServiceResource extends Google_ServiceResource {

    /**
     * Add a new media item to an album. The current upload size limitations are
     * 36MB for a photo and 1GB for a video. Uploads will not count against quota if
     * photos are less than 2048 pixels on their longest side or videos are less
     * than 15 minutes in length. (media.insert)
     *
     * @param string $userId The ID of the user to create the activity on behalf of.
     * @param string $collection
     * @param Google_Media $postBody
     * @param array $optParams Optional parameters.
     * @return Google_Media
     */
    public function insert($userId, $collection, Google_Media $postBody, $optParams = array()) {
      $params = array('userId' => $userId, 'collection' => $collection, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_Media($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "people" collection of methods.
   * Typical usage is:
   *  <code>
   *   $plusService = new Google_PlusService(...);
   *   $people = $plusService->people;
   *  </code>
   */
  class Google_PeopleServiceResource extends Google_ServiceResource {

    /**
     * Get a person's profile. (people.get)
     *
     * @param string $userId The ID of the person to get the profile for. The special value "me" can be used to indicate the authenticated user.
     * @param array $optParams Optional parameters.
     * @return Google_Person
     */
    public function get($userId, $optParams = array()) {
      $params = array('userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Person($data);
      } else {
        return $data;
      }
    }
    /**
     * List all of the people in the specified collection. (people.list)
     *
     * @param string $userId Get the collection of people for the person identified. Use "me" to indicate the authenticated user.
     * @param string $collection The collection of people to list.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults The maximum number of people to include in the response, which is used for paging. For any response, the actual number returned might be less than the specified maxResults.
     * @opt_param string orderBy The order to return people in.
     * @opt_param string pageToken The continuation token, which is used to page through large result sets. To get the next page of results, set this parameter to the value of "nextPageToken" from the previous response.
     * @return Google_PeopleFeed
     */
    public function listPeople($userId, $collection, $optParams = array()) {
      $params = array('userId' => $userId, 'collection' => $collection);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_PeopleFeed($data);
      } else {
        return $data;
      }
    }
    /**
     * List all of the people in the specified collection for a particular activity.
     * (people.listByActivity)
     *
     * @param string $activityId The ID of the activity to get the list of people for.
     * @param string $collection The collection of people to list.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults The maximum number of people to include in the response, which is used for paging. For any response, the actual number returned might be less than the specified maxResults.
     * @opt_param string pageToken The continuation token, which is used to page through large result sets. To get the next page of results, set this parameter to the value of "nextPageToken" from the previous response.
     * @return Google_PeopleFeed
     */
    public function listByActivity($activityId, $collection, $optParams = array()) {
      $params = array('activityId' => $activityId, 'collection' => $collection);
      $params = array_merge($params, $optParams);
      $data = $this->__call('listByActivity', array($params));
      if ($this->useObjects()) {
        return new Google_PeopleFeed($data);
      } else {
        return $data;
      }
    }
    /**
     * List all of the people who are members of a circle. (people.listByCircle)
     *
     * @param string $circleId The ID of the circle to get the members of.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults The maximum number of people to include in the response, which is used for paging. For any response, the actual number returned might be less than the specified maxResults.
     * @opt_param string pageToken The continuation token, which is used to page through large result sets. To get the next page of results, set this parameter to the value of "nextPageToken" from the previous response.
     * @return Google_PeopleFeed
     */
    public function listByCircle($circleId, $optParams = array()) {
      $params = array('circleId' => $circleId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('listByCircle', array($params));
      if ($this->useObjects()) {
        return new Google_PeopleFeed($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Google_Plus (v1domains).
 *
 * <p>
 * The Google+ API enables developers to build on top of the Google+ platform.
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://developers.google.com/+/api/" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_PlusService extends Google_Service {
  public $activities;
  public $audiences;
  public $circles;
  public $comments;
  public $media;
  public $people;
  /**
   * Constructs the internal representation of the Plus service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client) {
    $this->servicePath = 'plus/v1domains/';
    $this->version = 'v1domains';
    $this->serviceName = 'plus';

    $client->addService($this->serviceName, $this->version);
    $this->activities = new Google_ActivitiesServiceResource($this, $this->serviceName, 'activities', json_decode('{"methods": {"get": {"id": "plus.activities.get", "path": "activities/{activityId}", "httpMethod": "GET", "parameters": {"activityId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "Activity"}, "scopes": ["https://www.googleapis.com/auth/plus.login", "https://www.googleapis.com/auth/plus.me"]}, "insert": {"id": "plus.activities.insert", "path": "people/{userId}/activities", "httpMethod": "POST", "parameters": {"preview": {"type": "boolean", "location": "query"}, "userId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "Activity"}, "response": {"$ref": "Activity"}, "scopes": ["https://www.googleapis.com/auth/plus.login", "https://www.googleapis.com/auth/plus.me"]}, "list": {"id": "plus.activities.list", "path": "people/{userId}/activities/{collection}", "httpMethod": "GET", "parameters": {"collection": {"type": "string", "required": true, "enum": ["user"], "location": "path"}, "maxResults": {"type": "integer", "default": "20", "format": "uint32", "minimum": "1", "maximum": "100", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "userId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "ActivityFeed"}, "scopes": ["https://www.googleapis.com/auth/plus.login", "https://www.googleapis.com/auth/plus.me"]}}}', true));
    $this->audiences = new Google_AudiencesServiceResource($this, $this->serviceName, 'audiences', json_decode('{"methods": {"list": {"id": "plus.audiences.list", "path": "people/{userId}/audiences", "httpMethod": "GET", "parameters": {"maxResults": {"type": "integer", "default": "20", "format": "uint32", "minimum": "1", "maximum": "100", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "userId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "AudiencesFeed"}, "scopes": ["https://www.googleapis.com/auth/plus.login", "https://www.googleapis.com/auth/plus.me"]}}}', true));
    $this->circles = new Google_CirclesServiceResource($this, $this->serviceName, 'circles', json_decode('{"methods": {"addPeople": {"id": "plus.circles.addPeople", "path": "circles/{circleId}/people", "httpMethod": "PUT", "parameters": {"circleId": {"type": "string", "required": true, "location": "path"}, "email": {"type": "string", "repeated": true, "location": "query"}, "userId": {"type": "string", "repeated": true, "location": "query"}}, "response": {"$ref": "Circle"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "get": {"id": "plus.circles.get", "path": "circles/{circleId}", "httpMethod": "GET", "parameters": {"circleId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "Circle"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "insert": {"id": "plus.circles.insert", "path": "people/{userId}/circles", "httpMethod": "POST", "parameters": {"userId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "Circle"}, "response": {"$ref": "Circle"}, "scopes": ["https://www.googleapis.com/auth/plus.login", "https://www.googleapis.com/auth/plus.me"]}, "list": {"id": "plus.circles.list", "path": "people/{userId}/circles", "httpMethod": "GET", "parameters": {"maxResults": {"type": "integer", "default": "20", "format": "uint32", "minimum": "1", "maximum": "100", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "userId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "CircleFeed"}, "scopes": ["https://www.googleapis.com/auth/plus.login", "https://www.googleapis.com/auth/plus.me"]}, "patch": {"id": "plus.circles.patch", "path": "circles/{circleId}", "httpMethod": "PATCH", "parameters": {"circleId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "Circle"}, "response": {"$ref": "Circle"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "remove": {"id": "plus.circles.remove", "path": "circles/{circleId}", "httpMethod": "DELETE", "parameters": {"circleId": {"type": "string", "required": true, "location": "path"}}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "removePeople": {"id": "plus.circles.removePeople", "path": "circles/{circleId}/people", "httpMethod": "DELETE", "parameters": {"circleId": {"type": "string", "required": true, "location": "path"}, "email": {"type": "string", "repeated": true, "location": "query"}, "userId": {"type": "string", "repeated": true, "location": "query"}}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "update": {"id": "plus.circles.update", "path": "circles/{circleId}", "httpMethod": "PUT", "parameters": {"circleId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "Circle"}, "response": {"$ref": "Circle"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}}}', true));
    $this->comments = new Google_CommentsServiceResource($this, $this->serviceName, 'comments', json_decode('{"methods": {"get": {"id": "plus.comments.get", "path": "comments/{commentId}", "httpMethod": "GET", "parameters": {"commentId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "Comment"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "insert": {"id": "plus.comments.insert", "path": "activities/{activityId}/comments", "httpMethod": "POST", "parameters": {"activityId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "Comment"}, "response": {"$ref": "Comment"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "list": {"id": "plus.comments.list", "path": "activities/{activityId}/comments", "httpMethod": "GET", "parameters": {"activityId": {"type": "string", "required": true, "location": "path"}, "maxResults": {"type": "integer", "default": "20", "format": "uint32", "minimum": "0", "maximum": "500", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "sortOrder": {"type": "string", "default": "ascending", "enum": ["ascending", "descending"], "location": "query"}}, "response": {"$ref": "CommentFeed"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}}}', true));
    $this->media = new Google_MediaServiceResource($this, $this->serviceName, 'media', json_decode('{"methods": {"insert": {"id": "plus.media.insert", "path": "people/{userId}/media/{collection}", "httpMethod": "POST", "parameters": {"collection": {"type": "string", "required": true, "enum": ["cloud"], "location": "path"}, "userId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "Media"}, "response": {"$ref": "Media"}, "scopes": ["https://www.googleapis.com/auth/plus.login"], "supportsMediaUpload": true, "mediaUpload": {"accept": ["image/*", "video/*"], "protocols": {"simple": {"multipart": true, "path": "/upload/plus/v1domains/people/{userId}/media/{collection}"}, "resumable": {"multipart": true, "path": "/resumable/upload/plus/v1domains/people/{userId}/media/{collection}"}}}}}}', true));
    $this->people = new Google_PeopleServiceResource($this, $this->serviceName, 'people', json_decode('{"methods": {"get": {"id": "plus.people.get", "path": "people/{userId}", "httpMethod": "GET", "parameters": {"userId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "Person"}, "scopes": ["https://www.googleapis.com/auth/plus.login", "https://www.googleapis.com/auth/plus.me", "https://www.googleapis.com/auth/plus.profiles.read"]}, "list": {"id": "plus.people.list", "path": "people/{userId}/people/{collection}", "httpMethod": "GET", "parameters": {"collection": {"type": "string", "required": true, "enum": ["circled"], "location": "path"}, "maxResults": {"type": "integer", "default": "100", "format": "uint32", "minimum": "1", "maximum": "100", "location": "query"}, "orderBy": {"type": "string", "enum": ["alphabetical", "best"], "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "userId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "PeopleFeed"}, "scopes": ["https://www.googleapis.com/auth/plus.login", "https://www.googleapis.com/auth/plus.me"]}, "listByActivity": {"id": "plus.people.listByActivity", "path": "activities/{activityId}/people/{collection}", "httpMethod": "GET", "parameters": {"activityId": {"type": "string", "required": true, "location": "path"}, "collection": {"type": "string", "required": true, "enum": ["plusoners", "resharers", "sharedto"], "location": "path"}, "maxResults": {"type": "integer", "default": "20", "format": "uint32", "minimum": "1", "maximum": "100", "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "response": {"$ref": "PeopleFeed"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "listByCircle": {"id": "plus.people.listByCircle", "path": "circles/{circleId}/people", "httpMethod": "GET", "parameters": {"circleId": {"type": "string", "required": true, "location": "path"}, "maxResults": {"type": "integer", "default": "20", "format": "uint32", "minimum": "1", "maximum": "100", "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "response": {"$ref": "PeopleFeed"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}}}', true));

  }
}



class Google_Acl extends Google_Model {
  public $description;
  public $domainRestricted;
  protected $__itemsType = 'Google_PlusAclentryResource';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setDescription( $description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
  public function setDomainRestricted( $domainRestricted) {
    $this->domainRestricted = $domainRestricted;
  }
  public function getDomainRestricted() {
    return $this->domainRestricted;
  }
  public function setItems(/* array(Google_PlusAclentryResource) */ $items) {
    $this->assertIsArray($items, 'Google_PlusAclentryResource', __METHOD__);
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

class Google_Activity extends Google_Model {
  protected $__accessType = 'Google_Acl';
  protected $__accessDataType = '';
  public $access;
  protected $__actorType = 'Google_ActivityActor';
  protected $__actorDataType = '';
  public $actor;
  public $address;
  public $annotation;
  public $crosspostSource;
  public $etag;
  public $geocode;
  public $id;
  public $kind;
  protected $__locationType = 'Google_Place';
  protected $__locationDataType = '';
  public $location;
  protected $__objectType = 'Google_ActivityObject';
  protected $__objectDataType = '';
  public $object;
  public $placeId;
  public $placeName;
  protected $__providerType = 'Google_ActivityProvider';
  protected $__providerDataType = '';
  public $provider;
  public $published;
  public $radius;
  public $title;
  public $updated;
  public $url;
  public $verb;
  public function setAccess(Google_Acl $access) {
    $this->access = $access;
  }
  public function getAccess() {
    return $this->access;
  }
  public function setActor(Google_ActivityActor $actor) {
    $this->actor = $actor;
  }
  public function getActor() {
    return $this->actor;
  }
  public function setAddress( $address) {
    $this->address = $address;
  }
  public function getAddress() {
    return $this->address;
  }
  public function setAnnotation( $annotation) {
    $this->annotation = $annotation;
  }
  public function getAnnotation() {
    return $this->annotation;
  }
  public function setCrosspostSource( $crosspostSource) {
    $this->crosspostSource = $crosspostSource;
  }
  public function getCrosspostSource() {
    return $this->crosspostSource;
  }
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setGeocode( $geocode) {
    $this->geocode = $geocode;
  }
  public function getGeocode() {
    return $this->geocode;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setLocation(Google_Place $location) {
    $this->location = $location;
  }
  public function getLocation() {
    return $this->location;
  }
  public function setObject(Google_ActivityObject $object) {
    $this->object = $object;
  }
  public function getObject() {
    return $this->object;
  }
  public function setPlaceId( $placeId) {
    $this->placeId = $placeId;
  }
  public function getPlaceId() {
    return $this->placeId;
  }
  public function setPlaceName( $placeName) {
    $this->placeName = $placeName;
  }
  public function getPlaceName() {
    return $this->placeName;
  }
  public function setProvider(Google_ActivityProvider $provider) {
    $this->provider = $provider;
  }
  public function getProvider() {
    return $this->provider;
  }
  public function setPublished( $published) {
    $this->published = $published;
  }
  public function getPublished() {
    return $this->published;
  }
  public function setRadius( $radius) {
    $this->radius = $radius;
  }
  public function getRadius() {
    return $this->radius;
  }
  public function setTitle( $title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
  public function setUpdated( $updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setVerb( $verb) {
    $this->verb = $verb;
  }
  public function getVerb() {
    return $this->verb;
  }
}

class Google_ActivityActor extends Google_Model {
  public $displayName;
  public $id;
  protected $__imageType = 'Google_ActivityActorImage';
  protected $__imageDataType = '';
  public $image;
  protected $__nameType = 'Google_ActivityActorName';
  protected $__nameDataType = '';
  public $name;
  public $url;
  public function setDisplayName( $displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setImage(Google_ActivityActorImage $image) {
    $this->image = $image;
  }
  public function getImage() {
    return $this->image;
  }
  public function setName(Google_ActivityActorName $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_ActivityActorImage extends Google_Model {
  public $url;
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_ActivityActorName extends Google_Model {
  public $familyName;
  public $givenName;
  public function setFamilyName( $familyName) {
    $this->familyName = $familyName;
  }
  public function getFamilyName() {
    return $this->familyName;
  }
  public function setGivenName( $givenName) {
    $this->givenName = $givenName;
  }
  public function getGivenName() {
    return $this->givenName;
  }
}

class Google_ActivityFeed extends Google_Model {
  public $etag;
  public $id;
  protected $__itemsType = 'Google_Activity';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextLink;
  public $nextPageToken;
  public $selfLink;
  public $title;
  public $updated;
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setItems(/* array(Google_Activity) */ $items) {
    $this->assertIsArray($items, 'Google_Activity', __METHOD__);
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
  public function setNextLink( $nextLink) {
    $this->nextLink = $nextLink;
  }
  public function getNextLink() {
    return $this->nextLink;
  }
  public function setNextPageToken( $nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setSelfLink( $selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
  public function setTitle( $title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
  public function setUpdated( $updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
}

class Google_ActivityObject extends Google_Model {
  protected $__actorType = 'Google_ActivityObjectActor';
  protected $__actorDataType = '';
  public $actor;
  protected $__attachmentsType = 'Google_ActivityObjectAttachments';
  protected $__attachmentsDataType = 'array';
  public $attachments;
  public $content;
  public $id;
  public $objectType;
  public $originalContent;
  protected $__plusonersType = 'Google_ActivityObjectPlusoners';
  protected $__plusonersDataType = '';
  public $plusoners;
  protected $__repliesType = 'Google_ActivityObjectReplies';
  protected $__repliesDataType = '';
  public $replies;
  protected $__resharersType = 'Google_ActivityObjectResharers';
  protected $__resharersDataType = '';
  public $resharers;
  protected $__statusForViewerType = 'Google_ActivityObjectStatusForViewer';
  protected $__statusForViewerDataType = '';
  public $statusForViewer;
  public $url;
  public function setActor(Google_ActivityObjectActor $actor) {
    $this->actor = $actor;
  }
  public function getActor() {
    return $this->actor;
  }
  public function setAttachments(/* array(Google_ActivityObjectAttachments) */ $attachments) {
    $this->assertIsArray($attachments, 'Google_ActivityObjectAttachments', __METHOD__);
    $this->attachments = $attachments;
  }
  public function getAttachments() {
    return $this->attachments;
  }
  public function setContent( $content) {
    $this->content = $content;
  }
  public function getContent() {
    return $this->content;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setObjectType( $objectType) {
    $this->objectType = $objectType;
  }
  public function getObjectType() {
    return $this->objectType;
  }
  public function setOriginalContent( $originalContent) {
    $this->originalContent = $originalContent;
  }
  public function getOriginalContent() {
    return $this->originalContent;
  }
  public function setPlusoners(Google_ActivityObjectPlusoners $plusoners) {
    $this->plusoners = $plusoners;
  }
  public function getPlusoners() {
    return $this->plusoners;
  }
  public function setReplies(Google_ActivityObjectReplies $replies) {
    $this->replies = $replies;
  }
  public function getReplies() {
    return $this->replies;
  }
  public function setResharers(Google_ActivityObjectResharers $resharers) {
    $this->resharers = $resharers;
  }
  public function getResharers() {
    return $this->resharers;
  }
  public function setStatusForViewer(Google_ActivityObjectStatusForViewer $statusForViewer) {
    $this->statusForViewer = $statusForViewer;
  }
  public function getStatusForViewer() {
    return $this->statusForViewer;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_ActivityObjectActor extends Google_Model {
  public $displayName;
  public $id;
  protected $__imageType = 'Google_ActivityObjectActorImage';
  protected $__imageDataType = '';
  public $image;
  public $url;
  public function setDisplayName( $displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setImage(Google_ActivityObjectActorImage $image) {
    $this->image = $image;
  }
  public function getImage() {
    return $this->image;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_ActivityObjectActorImage extends Google_Model {
  public $url;
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_ActivityObjectAttachments extends Google_Model {
  public $content;
  public $displayName;
  protected $__embedType = 'Google_ActivityObjectAttachmentsEmbed';
  protected $__embedDataType = '';
  public $embed;
  protected $__fullImageType = 'Google_ActivityObjectAttachmentsFullImage';
  protected $__fullImageDataType = '';
  public $fullImage;
  public $id;
  protected $__imageType = 'Google_ActivityObjectAttachmentsImage';
  protected $__imageDataType = '';
  public $image;
  public $objectType;
  protected $__previewThumbnailsType = 'Google_ActivityObjectAttachmentsPreviewThumbnails';
  protected $__previewThumbnailsDataType = 'array';
  public $previewThumbnails;
  protected $__thumbnailsType = 'Google_ActivityObjectAttachmentsThumbnails';
  protected $__thumbnailsDataType = 'array';
  public $thumbnails;
  public $url;
  public function setContent( $content) {
    $this->content = $content;
  }
  public function getContent() {
    return $this->content;
  }
  public function setDisplayName( $displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setEmbed(Google_ActivityObjectAttachmentsEmbed $embed) {
    $this->embed = $embed;
  }
  public function getEmbed() {
    return $this->embed;
  }
  public function setFullImage(Google_ActivityObjectAttachmentsFullImage $fullImage) {
    $this->fullImage = $fullImage;
  }
  public function getFullImage() {
    return $this->fullImage;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setImage(Google_ActivityObjectAttachmentsImage $image) {
    $this->image = $image;
  }
  public function getImage() {
    return $this->image;
  }
  public function setObjectType( $objectType) {
    $this->objectType = $objectType;
  }
  public function getObjectType() {
    return $this->objectType;
  }
  public function setPreviewThumbnails(/* array(Google_ActivityObjectAttachmentsPreviewThumbnails) */ $previewThumbnails) {
    $this->assertIsArray($previewThumbnails, 'Google_ActivityObjectAttachmentsPreviewThumbnails', __METHOD__);
    $this->previewThumbnails = $previewThumbnails;
  }
  public function getPreviewThumbnails() {
    return $this->previewThumbnails;
  }
  public function setThumbnails(/* array(Google_ActivityObjectAttachmentsThumbnails) */ $thumbnails) {
    $this->assertIsArray($thumbnails, 'Google_ActivityObjectAttachmentsThumbnails', __METHOD__);
    $this->thumbnails = $thumbnails;
  }
  public function getThumbnails() {
    return $this->thumbnails;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_ActivityObjectAttachmentsEmbed extends Google_Model {
  public $type;
  public $url;
  public function setType( $type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_ActivityObjectAttachmentsFullImage extends Google_Model {
  public $height;
  public $type;
  public $url;
  public $width;
  public function setHeight( $height) {
    $this->height = $height;
  }
  public function getHeight() {
    return $this->height;
  }
  public function setType( $type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setWidth( $width) {
    $this->width = $width;
  }
  public function getWidth() {
    return $this->width;
  }
}

class Google_ActivityObjectAttachmentsImage extends Google_Model {
  public $height;
  public $type;
  public $url;
  public $width;
  public function setHeight( $height) {
    $this->height = $height;
  }
  public function getHeight() {
    return $this->height;
  }
  public function setType( $type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setWidth( $width) {
    $this->width = $width;
  }
  public function getWidth() {
    return $this->width;
  }
}

class Google_ActivityObjectAttachmentsPreviewThumbnails extends Google_Model {
  public $url;
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_ActivityObjectAttachmentsThumbnails extends Google_Model {
  public $description;
  protected $__imageType = 'Google_ActivityObjectAttachmentsThumbnailsImage';
  protected $__imageDataType = '';
  public $image;
  public $url;
  public function setDescription( $description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
  public function setImage(Google_ActivityObjectAttachmentsThumbnailsImage $image) {
    $this->image = $image;
  }
  public function getImage() {
    return $this->image;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_ActivityObjectAttachmentsThumbnailsImage extends Google_Model {
  public $height;
  public $type;
  public $url;
  public $width;
  public function setHeight( $height) {
    $this->height = $height;
  }
  public function getHeight() {
    return $this->height;
  }
  public function setType( $type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setWidth( $width) {
    $this->width = $width;
  }
  public function getWidth() {
    return $this->width;
  }
}

class Google_ActivityObjectPlusoners extends Google_Model {
  public $selfLink;
  public $totalItems;
  public function setSelfLink( $selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
  public function setTotalItems( $totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
}

class Google_ActivityObjectReplies extends Google_Model {
  public $selfLink;
  public $totalItems;
  public function setSelfLink( $selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
  public function setTotalItems( $totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
}

class Google_ActivityObjectResharers extends Google_Model {
  public $selfLink;
  public $totalItems;
  public function setSelfLink( $selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
  public function setTotalItems( $totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
}

class Google_ActivityObjectStatusForViewer extends Google_Model {
  public $canComment;
  public $canPlusone;
  public $isPlusOned;
  public $resharingDisabled;
  public function setCanComment( $canComment) {
    $this->canComment = $canComment;
  }
  public function getCanComment() {
    return $this->canComment;
  }
  public function setCanPlusone( $canPlusone) {
    $this->canPlusone = $canPlusone;
  }
  public function getCanPlusone() {
    return $this->canPlusone;
  }
  public function setIsPlusOned( $isPlusOned) {
    $this->isPlusOned = $isPlusOned;
  }
  public function getIsPlusOned() {
    return $this->isPlusOned;
  }
  public function setResharingDisabled( $resharingDisabled) {
    $this->resharingDisabled = $resharingDisabled;
  }
  public function getResharingDisabled() {
    return $this->resharingDisabled;
  }
}

class Google_ActivityProvider extends Google_Model {
  public $title;
  public function setTitle( $title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
}

class Google_Audience extends Google_Model {
  public $etag;
  protected $__itemType = 'Google_PlusAclentryResource';
  protected $__itemDataType = '';
  public $item;
  public $kind;
  public $visibility;
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setItem(Google_PlusAclentryResource $item) {
    $this->item = $item;
  }
  public function getItem() {
    return $this->item;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setVisibility( $visibility) {
    $this->visibility = $visibility;
  }
  public function getVisibility() {
    return $this->visibility;
  }
}

class Google_AudiencesFeed extends Google_Model {
  public $etag;
  protected $__itemsType = 'Google_Audience';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public $totalItems;
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setItems(/* array(Google_Audience) */ $items) {
    $this->assertIsArray($items, 'Google_Audience', __METHOD__);
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
  public function setTotalItems( $totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
}

class Google_Circle extends Google_Model {
  public $description;
  public $displayName;
  public $etag;
  public $id;
  public $kind;
  protected $__peopleType = 'Google_CirclePeople';
  protected $__peopleDataType = '';
  public $people;
  public $selfLink;
  public function setDescription( $description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
  public function setDisplayName( $displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setPeople(Google_CirclePeople $people) {
    $this->people = $people;
  }
  public function getPeople() {
    return $this->people;
  }
  public function setSelfLink( $selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
}

class Google_CircleFeed extends Google_Model {
  public $etag;
  protected $__itemsType = 'Google_Circle';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextLink;
  public $nextPageToken;
  public $selfLink;
  public $title;
  public $totalItems;
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setItems(/* array(Google_Circle) */ $items) {
    $this->assertIsArray($items, 'Google_Circle', __METHOD__);
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
  public function setNextLink( $nextLink) {
    $this->nextLink = $nextLink;
  }
  public function getNextLink() {
    return $this->nextLink;
  }
  public function setNextPageToken( $nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setSelfLink( $selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
  public function setTitle( $title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
  public function setTotalItems( $totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
}

class Google_CirclePeople extends Google_Model {
  public $totalItems;
  public function setTotalItems( $totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
}

class Google_Comment extends Google_Model {
  protected $__actorType = 'Google_CommentActor';
  protected $__actorDataType = '';
  public $actor;
  public $etag;
  public $id;
  protected $__inReplyToType = 'Google_CommentInReplyTo';
  protected $__inReplyToDataType = 'array';
  public $inReplyTo;
  public $kind;
  protected $__objectType = 'Google_CommentObject';
  protected $__objectDataType = '';
  public $object;
  protected $__plusonersType = 'Google_CommentPlusoners';
  protected $__plusonersDataType = '';
  public $plusoners;
  public $published;
  public $selfLink;
  public $updated;
  public $verb;
  public function setActor(Google_CommentActor $actor) {
    $this->actor = $actor;
  }
  public function getActor() {
    return $this->actor;
  }
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setInReplyTo(/* array(Google_CommentInReplyTo) */ $inReplyTo) {
    $this->assertIsArray($inReplyTo, 'Google_CommentInReplyTo', __METHOD__);
    $this->inReplyTo = $inReplyTo;
  }
  public function getInReplyTo() {
    return $this->inReplyTo;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setObject(Google_CommentObject $object) {
    $this->object = $object;
  }
  public function getObject() {
    return $this->object;
  }
  public function setPlusoners(Google_CommentPlusoners $plusoners) {
    $this->plusoners = $plusoners;
  }
  public function getPlusoners() {
    return $this->plusoners;
  }
  public function setPublished( $published) {
    $this->published = $published;
  }
  public function getPublished() {
    return $this->published;
  }
  public function setSelfLink( $selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
  public function setUpdated( $updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setVerb( $verb) {
    $this->verb = $verb;
  }
  public function getVerb() {
    return $this->verb;
  }
}

class Google_CommentActor extends Google_Model {
  public $displayName;
  public $id;
  protected $__imageType = 'Google_CommentActorImage';
  protected $__imageDataType = '';
  public $image;
  public $url;
  public function setDisplayName( $displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setImage(Google_CommentActorImage $image) {
    $this->image = $image;
  }
  public function getImage() {
    return $this->image;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_CommentActorImage extends Google_Model {
  public $url;
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_CommentFeed extends Google_Model {
  public $etag;
  public $id;
  protected $__itemsType = 'Google_Comment';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextLink;
  public $nextPageToken;
  public $title;
  public $updated;
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setItems(/* array(Google_Comment) */ $items) {
    $this->assertIsArray($items, 'Google_Comment', __METHOD__);
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
  public function setNextLink( $nextLink) {
    $this->nextLink = $nextLink;
  }
  public function getNextLink() {
    return $this->nextLink;
  }
  public function setNextPageToken( $nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setTitle( $title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
  public function setUpdated( $updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
}

class Google_CommentInReplyTo extends Google_Model {
  public $id;
  public $url;
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_CommentObject extends Google_Model {
  public $content;
  public $objectType;
  public $originalContent;
  public function setContent( $content) {
    $this->content = $content;
  }
  public function getContent() {
    return $this->content;
  }
  public function setObjectType( $objectType) {
    $this->objectType = $objectType;
  }
  public function getObjectType() {
    return $this->objectType;
  }
  public function setOriginalContent( $originalContent) {
    $this->originalContent = $originalContent;
  }
  public function getOriginalContent() {
    return $this->originalContent;
  }
}

class Google_CommentPlusoners extends Google_Model {
  public $totalItems;
  public function setTotalItems( $totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
}

class Google_Media extends Google_Model {
  protected $__authorType = 'Google_MediaAuthor';
  protected $__authorDataType = '';
  public $author;
  public $displayName;
  public $etag;
  protected $__exifType = 'Google_MediaExif';
  protected $__exifDataType = '';
  public $exif;
  public $height;
  public $id;
  public $kind;
  public $mediaUrl;
  public $published;
  public $sizeBytes;
  protected $__streamsType = 'Google_Videostream';
  protected $__streamsDataType = 'array';
  public $streams;
  public $summary;
  public $updated;
  public $url;
  public $videoDuration;
  public $videoStatus;
  public $width;
  public function setAuthor(Google_MediaAuthor $author) {
    $this->author = $author;
  }
  public function getAuthor() {
    return $this->author;
  }
  public function setDisplayName( $displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setExif(Google_MediaExif $exif) {
    $this->exif = $exif;
  }
  public function getExif() {
    return $this->exif;
  }
  public function setHeight( $height) {
    $this->height = $height;
  }
  public function getHeight() {
    return $this->height;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setMediaUrl( $mediaUrl) {
    $this->mediaUrl = $mediaUrl;
  }
  public function getMediaUrl() {
    return $this->mediaUrl;
  }
  public function setPublished( $published) {
    $this->published = $published;
  }
  public function getPublished() {
    return $this->published;
  }
  public function setSizeBytes( $sizeBytes) {
    $this->sizeBytes = $sizeBytes;
  }
  public function getSizeBytes() {
    return $this->sizeBytes;
  }
  public function setStreams(/* array(Google_Videostream) */ $streams) {
    $this->assertIsArray($streams, 'Google_Videostream', __METHOD__);
    $this->streams = $streams;
  }
  public function getStreams() {
    return $this->streams;
  }
  public function setSummary( $summary) {
    $this->summary = $summary;
  }
  public function getSummary() {
    return $this->summary;
  }
  public function setUpdated( $updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setVideoDuration( $videoDuration) {
    $this->videoDuration = $videoDuration;
  }
  public function getVideoDuration() {
    return $this->videoDuration;
  }
  public function setVideoStatus( $videoStatus) {
    $this->videoStatus = $videoStatus;
  }
  public function getVideoStatus() {
    return $this->videoStatus;
  }
  public function setWidth( $width) {
    $this->width = $width;
  }
  public function getWidth() {
    return $this->width;
  }
}

class Google_MediaAuthor extends Google_Model {
  public $displayName;
  public $id;
  protected $__imageType = 'Google_MediaAuthorImage';
  protected $__imageDataType = '';
  public $image;
  public $url;
  public function setDisplayName( $displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setImage(Google_MediaAuthorImage $image) {
    $this->image = $image;
  }
  public function getImage() {
    return $this->image;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_MediaAuthorImage extends Google_Model {
  public $url;
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_MediaExif extends Google_Model {
  public $time;
  public function setTime( $time) {
    $this->time = $time;
  }
  public function getTime() {
    return $this->time;
  }
}

class Google_PeopleFeed extends Google_Model {
  public $etag;
  protected $__itemsType = 'Google_Person';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public $selfLink;
  public $title;
  public $totalItems;
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setItems(/* array(Google_Person) */ $items) {
    $this->assertIsArray($items, 'Google_Person', __METHOD__);
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
  public function setSelfLink( $selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
  public function setTitle( $title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
  public function setTotalItems( $totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
}

class Google_Person extends Google_Model {
  public $aboutMe;
  public $birthday;
  public $braggingRights;
  public $circledByCount;
  protected $__coverType = 'Google_PersonCover';
  protected $__coverDataType = '';
  public $cover;
  public $currentLocation;
  public $displayName;
  public $etag;
  public $gender;
  public $id;
  protected $__imageType = 'Google_PersonImage';
  protected $__imageDataType = '';
  public $image;
  public $isPlusUser;
  public $kind;
  protected $__nameType = 'Google_PersonName';
  protected $__nameDataType = '';
  public $name;
  public $nickname;
  public $objectType;
  protected $__organizationsType = 'Google_PersonOrganizations';
  protected $__organizationsDataType = 'array';
  public $organizations;
  protected $__placesLivedType = 'Google_PersonPlacesLived';
  protected $__placesLivedDataType = 'array';
  public $placesLived;
  public $plusOneCount;
  public $relationshipStatus;
  public $tagline;
  public $url;
  protected $__urlsType = 'Google_PersonUrls';
  protected $__urlsDataType = 'array';
  public $urls;
  public $verified;
  public function setAboutMe( $aboutMe) {
    $this->aboutMe = $aboutMe;
  }
  public function getAboutMe() {
    return $this->aboutMe;
  }
  public function setBirthday( $birthday) {
    $this->birthday = $birthday;
  }
  public function getBirthday() {
    return $this->birthday;
  }
  public function setBraggingRights( $braggingRights) {
    $this->braggingRights = $braggingRights;
  }
  public function getBraggingRights() {
    return $this->braggingRights;
  }
  public function setCircledByCount( $circledByCount) {
    $this->circledByCount = $circledByCount;
  }
  public function getCircledByCount() {
    return $this->circledByCount;
  }
  public function setCover(Google_PersonCover $cover) {
    $this->cover = $cover;
  }
  public function getCover() {
    return $this->cover;
  }
  public function setCurrentLocation( $currentLocation) {
    $this->currentLocation = $currentLocation;
  }
  public function getCurrentLocation() {
    return $this->currentLocation;
  }
  public function setDisplayName( $displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setGender( $gender) {
    $this->gender = $gender;
  }
  public function getGender() {
    return $this->gender;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setImage(Google_PersonImage $image) {
    $this->image = $image;
  }
  public function getImage() {
    return $this->image;
  }
  public function setIsPlusUser( $isPlusUser) {
    $this->isPlusUser = $isPlusUser;
  }
  public function getIsPlusUser() {
    return $this->isPlusUser;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setName(Google_PersonName $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setNickname( $nickname) {
    $this->nickname = $nickname;
  }
  public function getNickname() {
    return $this->nickname;
  }
  public function setObjectType( $objectType) {
    $this->objectType = $objectType;
  }
  public function getObjectType() {
    return $this->objectType;
  }
  public function setOrganizations(/* array(Google_PersonOrganizations) */ $organizations) {
    $this->assertIsArray($organizations, 'Google_PersonOrganizations', __METHOD__);
    $this->organizations = $organizations;
  }
  public function getOrganizations() {
    return $this->organizations;
  }
  public function setPlacesLived(/* array(Google_PersonPlacesLived) */ $placesLived) {
    $this->assertIsArray($placesLived, 'Google_PersonPlacesLived', __METHOD__);
    $this->placesLived = $placesLived;
  }
  public function getPlacesLived() {
    return $this->placesLived;
  }
  public function setPlusOneCount( $plusOneCount) {
    $this->plusOneCount = $plusOneCount;
  }
  public function getPlusOneCount() {
    return $this->plusOneCount;
  }
  public function setRelationshipStatus( $relationshipStatus) {
    $this->relationshipStatus = $relationshipStatus;
  }
  public function getRelationshipStatus() {
    return $this->relationshipStatus;
  }
  public function setTagline( $tagline) {
    $this->tagline = $tagline;
  }
  public function getTagline() {
    return $this->tagline;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setUrls(/* array(Google_PersonUrls) */ $urls) {
    $this->assertIsArray($urls, 'Google_PersonUrls', __METHOD__);
    $this->urls = $urls;
  }
  public function getUrls() {
    return $this->urls;
  }
  public function setVerified( $verified) {
    $this->verified = $verified;
  }
  public function getVerified() {
    return $this->verified;
  }
}

class Google_PersonCover extends Google_Model {
  protected $__coverInfoType = 'Google_PersonCoverCoverInfo';
  protected $__coverInfoDataType = '';
  public $coverInfo;
  protected $__coverPhotoType = 'Google_PersonCoverCoverPhoto';
  protected $__coverPhotoDataType = '';
  public $coverPhoto;
  public $layout;
  public function setCoverInfo(Google_PersonCoverCoverInfo $coverInfo) {
    $this->coverInfo = $coverInfo;
  }
  public function getCoverInfo() {
    return $this->coverInfo;
  }
  public function setCoverPhoto(Google_PersonCoverCoverPhoto $coverPhoto) {
    $this->coverPhoto = $coverPhoto;
  }
  public function getCoverPhoto() {
    return $this->coverPhoto;
  }
  public function setLayout( $layout) {
    $this->layout = $layout;
  }
  public function getLayout() {
    return $this->layout;
  }
}

class Google_PersonCoverCoverInfo extends Google_Model {
  public $leftImageOffset;
  public $topImageOffset;
  public function setLeftImageOffset( $leftImageOffset) {
    $this->leftImageOffset = $leftImageOffset;
  }
  public function getLeftImageOffset() {
    return $this->leftImageOffset;
  }
  public function setTopImageOffset( $topImageOffset) {
    $this->topImageOffset = $topImageOffset;
  }
  public function getTopImageOffset() {
    return $this->topImageOffset;
  }
}

class Google_PersonCoverCoverPhoto extends Google_Model {
  public $height;
  public $url;
  public $width;
  public function setHeight( $height) {
    $this->height = $height;
  }
  public function getHeight() {
    return $this->height;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setWidth( $width) {
    $this->width = $width;
  }
  public function getWidth() {
    return $this->width;
  }
}

class Google_PersonImage extends Google_Model {
  public $url;
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_PersonName extends Google_Model {
  public $familyName;
  public $formatted;
  public $givenName;
  public $honorificPrefix;
  public $honorificSuffix;
  public $middleName;
  public function setFamilyName( $familyName) {
    $this->familyName = $familyName;
  }
  public function getFamilyName() {
    return $this->familyName;
  }
  public function setFormatted( $formatted) {
    $this->formatted = $formatted;
  }
  public function getFormatted() {
    return $this->formatted;
  }
  public function setGivenName( $givenName) {
    $this->givenName = $givenName;
  }
  public function getGivenName() {
    return $this->givenName;
  }
  public function setHonorificPrefix( $honorificPrefix) {
    $this->honorificPrefix = $honorificPrefix;
  }
  public function getHonorificPrefix() {
    return $this->honorificPrefix;
  }
  public function setHonorificSuffix( $honorificSuffix) {
    $this->honorificSuffix = $honorificSuffix;
  }
  public function getHonorificSuffix() {
    return $this->honorificSuffix;
  }
  public function setMiddleName( $middleName) {
    $this->middleName = $middleName;
  }
  public function getMiddleName() {
    return $this->middleName;
  }
}

class Google_PersonOrganizations extends Google_Model {
  public $department;
  public $description;
  public $endDate;
  public $location;
  public $name;
  public $primary;
  public $startDate;
  public $title;
  public $type;
  public function setDepartment( $department) {
    $this->department = $department;
  }
  public function getDepartment() {
    return $this->department;
  }
  public function setDescription( $description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
  public function setEndDate( $endDate) {
    $this->endDate = $endDate;
  }
  public function getEndDate() {
    return $this->endDate;
  }
  public function setLocation( $location) {
    $this->location = $location;
  }
  public function getLocation() {
    return $this->location;
  }
  public function setName( $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setPrimary( $primary) {
    $this->primary = $primary;
  }
  public function getPrimary() {
    return $this->primary;
  }
  public function setStartDate( $startDate) {
    $this->startDate = $startDate;
  }
  public function getStartDate() {
    return $this->startDate;
  }
  public function setTitle( $title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
  public function setType( $type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}

class Google_PersonPlacesLived extends Google_Model {
  public $primary;
  public $value;
  public function setPrimary( $primary) {
    $this->primary = $primary;
  }
  public function getPrimary() {
    return $this->primary;
  }
  public function setValue( $value) {
    $this->value = $value;
  }
  public function getValue() {
    return $this->value;
  }
}

class Google_PersonUrls extends Google_Model {
  public $label;
  public $type;
  public $value;
  public function setLabel( $label) {
    $this->label = $label;
  }
  public function getLabel() {
    return $this->label;
  }
  public function setType( $type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setValue( $value) {
    $this->value = $value;
  }
  public function getValue() {
    return $this->value;
  }
}

class Google_Place extends Google_Model {
  protected $__addressType = 'Google_PlaceAddress';
  protected $__addressDataType = '';
  public $address;
  public $displayName;
  public $kind;
  protected $__positionType = 'Google_PlacePosition';
  protected $__positionDataType = '';
  public $position;
  public function setAddress(Google_PlaceAddress $address) {
    $this->address = $address;
  }
  public function getAddress() {
    return $this->address;
  }
  public function setDisplayName( $displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setPosition(Google_PlacePosition $position) {
    $this->position = $position;
  }
  public function getPosition() {
    return $this->position;
  }
}

class Google_PlaceAddress extends Google_Model {
  public $formatted;
  public function setFormatted( $formatted) {
    $this->formatted = $formatted;
  }
  public function getFormatted() {
    return $this->formatted;
  }
}

class Google_PlacePosition extends Google_Model {
  public $latitude;
  public $longitude;
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
}

class Google_PlusAclentryResource extends Google_Model {
  public $displayName;
  public $id;
  public $type;
  public function setDisplayName( $displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setType( $type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}

class Google_Videostream extends Google_Model {
  public $height;
  public $type;
  public $url;
  public $width;
  public function setHeight( $height) {
    $this->height = $height;
  }
  public function getHeight() {
    return $this->height;
  }
  public function setType( $type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setWidth( $width) {
    $this->width = $width;
  }
  public function getWidth() {
    return $this->width;
  }
}
