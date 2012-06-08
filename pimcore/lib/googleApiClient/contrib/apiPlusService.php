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
   * The "activities" collection of methods.
   * Typical usage is:
   *  <code>
   *   $plusService = new apiPlusService(...);
   *   $activities = $plusService->activities;
   *  </code>
   */
  class ActivitiesServiceResource extends apiServiceResource {


    /**
     * Search public activities. (activities.search)
     *
     * @param string $query Full-text search query string.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string orderBy Specifies how to order search results.
     * @opt_param string pageToken The continuation token, used to page through large result sets. To get the next page of results, set this parameter to the value of "nextPageToken" from the previous response. This token may be of any length.
     * @opt_param string maxResults The maximum number of activities to include in the response, used for paging. For any response, the actual number returned may be less than the specified maxResults.
     * @opt_param string language Specify the preferred language to search with. See search language codes for available values.
     * @return ActivityFeed
     */
    public function search($query, $optParams = array()) {
      $params = array('query' => $query);
      $params = array_merge($params, $optParams);
      $data = $this->__call('search', array($params));
      if ($this->useObjects()) {
        return new ActivityFeed($data);
      } else {
        return $data;
      }
    }
    /**
     * List all of the activities in the specified collection for a particular user. (activities.list)
     *
     * @param string $userId The ID of the user to get activities for. The special value "me" can be used to indicate the authenticated user.
     * @param string $collection The collection of activities to list.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string pageToken The continuation token, used to page through large result sets. To get the next page of results, set this parameter to the value of "nextPageToken" from the previous response.
     * @opt_param string maxResults The maximum number of activities to include in the response, used for paging. For any response, the actual number returned may be less than the specified maxResults.
     * @return ActivityFeed
     */
    public function listActivities($userId, $collection, $optParams = array()) {
      $params = array('userId' => $userId, 'collection' => $collection);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new ActivityFeed($data);
      } else {
        return $data;
      }
    }
    /**
     * Get an activity. (activities.get)
     *
     * @param string $activityId The ID of the activity to get.
     * @return Activity
     */
    public function get($activityId, $optParams = array()) {
      $params = array('activityId' => $activityId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Activity($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "comments" collection of methods.
   * Typical usage is:
   *  <code>
   *   $plusService = new apiPlusService(...);
   *   $comments = $plusService->comments;
   *  </code>
   */
  class CommentsServiceResource extends apiServiceResource {


    /**
     * List all of the comments for an activity. (comments.list)
     *
     * @param string $activityId The ID of the activity to get comments for.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string pageToken The continuation token, used to page through large result sets. To get the next page of results, set this parameter to the value of "nextPageToken" from the previous response.
     * @opt_param string maxResults The maximum number of comments to include in the response, used for paging. For any response, the actual number returned may be less than the specified maxResults.
     * @opt_param string sortOrder The order in which to sort the list of comments.
     * @return CommentFeed
     */
    public function listComments($activityId, $optParams = array()) {
      $params = array('activityId' => $activityId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new CommentFeed($data);
      } else {
        return $data;
      }
    }
    /**
     * Get a comment. (comments.get)
     *
     * @param string $commentId The ID of the comment to get.
     * @return Comment
     */
    public function get($commentId, $optParams = array()) {
      $params = array('commentId' => $commentId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Comment($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "people" collection of methods.
   * Typical usage is:
   *  <code>
   *   $plusService = new apiPlusService(...);
   *   $people = $plusService->people;
   *  </code>
   */
  class PeopleServiceResource extends apiServiceResource {


    /**
     * List all of the people in the specified collection for a particular activity.
     * (people.listByActivity)
     *
     * @param string $activityId The ID of the activity to get the list of people for.
     * @param string $collection The collection of people to list.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string pageToken The continuation token, used to page through large result sets. To get the next page of results, set this parameter to the value of "nextPageToken" from the previous response.
     * @opt_param string maxResults The maximum number of people to include in the response, used for paging. For any response, the actual number returned may be less than the specified maxResults.
     * @return PeopleFeed
     */
    public function listByActivity($activityId, $collection, $optParams = array()) {
      $params = array('activityId' => $activityId, 'collection' => $collection);
      $params = array_merge($params, $optParams);
      $data = $this->__call('listByActivity', array($params));
      if ($this->useObjects()) {
        return new PeopleFeed($data);
      } else {
        return $data;
      }
    }
    /**
     * Search all public profiles. (people.search)
     *
     * @param string $query Full-text search query string.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string pageToken The continuation token, used to page through large result sets. To get the next page of results, set this parameter to the value of "nextPageToken" from the previous response. This token may be of any length.
     * @opt_param string maxResults The maximum number of people to include in the response, used for paging. For any response, the actual number returned may be less than the specified maxResults.
     * @opt_param string language Specify the preferred language to search with. See search language codes for available values.
     * @return PeopleFeed
     */
    public function search($query, $optParams = array()) {
      $params = array('query' => $query);
      $params = array_merge($params, $optParams);
      $data = $this->__call('search', array($params));
      if ($this->useObjects()) {
        return new PeopleFeed($data);
      } else {
        return $data;
      }
    }
    /**
     * Get a person's profile. (people.get)
     *
     * @param string $userId The ID of the person to get the profile for. The special value "me" can be used to indicate the authenticated user.
     * @return Person
     */
    public function get($userId, $optParams = array()) {
      $params = array('userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Person($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Plus (v1).
 *
 * <p>
 * The Google+ API enables developers to build on top of the Google+ platform.
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="http://developers.google.com/+/api/" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class apiPlusService extends apiService {
  public $activities;
  public $comments;
  public $people;
  /**
   * Constructs the internal representation of the Plus service.
   *
   * @param apiClient apiClient
   */
  public function __construct(apiClient $apiClient) {
    $this->restBasePath = '/plus/v1/';
    $this->version = 'v1';
    $this->serviceName = 'plus';

    $apiClient->addService($this->serviceName, $this->version);
    $this->activities = new ActivitiesServiceResource($this, $this->serviceName, 'activities', json_decode('{"methods": {"search": {"scopes": ["https://www.googleapis.com/auth/plus.me"], "parameters": {"orderBy": {"default": "recent", "enum": ["best", "recent"], "location": "query", "type": "string"}, "pageToken": {"type": "string", "location": "query"}, "language": {"default": "", "type": "string", "location": "query"}, "maxResults": {"format": "uint32", "default": "10", "maximum": "20", "minimum": "1", "location": "query", "type": "integer"}, "query": {"required": true, "type": "string", "location": "query"}}, "id": "plus.activities.search", "httpMethod": "GET", "path": "activities", "response": {"$ref": "ActivityFeed"}}, "list": {"scopes": ["https://www.googleapis.com/auth/plus.me"], "parameters": {"pageToken": {"type": "string", "location": "query"}, "alt": {"default": "json", "enum": ["json"], "location": "query", "type": "string"}, "userId": {"required": true, "type": "string", "location": "path"}, "collection": {"required": true, "enum": ["public"], "location": "path", "type": "string"}, "maxResults": {"format": "uint32", "default": "20", "maximum": "100", "minimum": "1", "location": "query", "type": "integer"}}, "id": "plus.activities.list", "httpMethod": "GET", "path": "people/{userId}/activities/{collection}", "response": {"$ref": "ActivityFeed"}}, "get": {"scopes": ["https://www.googleapis.com/auth/plus.me"], "parameters": {"activityId": {"required": true, "type": "string", "location": "path"}, "alt": {"default": "json", "enum": ["json"], "location": "query", "type": "string"}}, "id": "plus.activities.get", "httpMethod": "GET", "path": "activities/{activityId}", "response": {"$ref": "Activity"}}}}', true));
    $this->comments = new CommentsServiceResource($this, $this->serviceName, 'comments', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/plus.me"], "parameters": {"pageToken": {"type": "string", "location": "query"}, "activityId": {"required": true, "type": "string", "location": "path"}, "alt": {"default": "json", "enum": ["json"], "location": "query", "type": "string"}, "maxResults": {"format": "uint32", "default": "20", "maximum": "100", "minimum": "0", "location": "query", "type": "integer"}, "sortOrder": {"default": "ascending", "enum": ["ascending", "descending"], "location": "query", "type": "string"}}, "id": "plus.comments.list", "httpMethod": "GET", "path": "activities/{activityId}/comments", "response": {"$ref": "CommentFeed"}}, "get": {"scopes": ["https://www.googleapis.com/auth/plus.me"], "parameters": {"commentId": {"required": true, "type": "string", "location": "path"}}, "id": "plus.comments.get", "httpMethod": "GET", "path": "comments/{commentId}", "response": {"$ref": "Comment"}}}}', true));
    $this->people = new PeopleServiceResource($this, $this->serviceName, 'people', json_decode('{"methods": {"listByActivity": {"scopes": ["https://www.googleapis.com/auth/plus.me"], "parameters": {"pageToken": {"type": "string", "location": "query"}, "activityId": {"required": true, "type": "string", "location": "path"}, "collection": {"required": true, "enum": ["plusoners", "resharers"], "location": "path", "type": "string"}, "maxResults": {"format": "uint32", "default": "20", "maximum": "100", "minimum": "1", "location": "query", "type": "integer"}}, "id": "plus.people.listByActivity", "httpMethod": "GET", "path": "activities/{activityId}/people/{collection}", "response": {"$ref": "PeopleFeed"}}, "search": {"scopes": ["https://www.googleapis.com/auth/plus.me"], "parameters": {"pageToken": {"type": "string", "location": "query"}, "language": {"default": "", "type": "string", "location": "query"}, "maxResults": {"format": "uint32", "default": "10", "maximum": "20", "minimum": "1", "location": "query", "type": "integer"}, "query": {"required": true, "type": "string", "location": "query"}}, "id": "plus.people.search", "httpMethod": "GET", "path": "people", "response": {"$ref": "PeopleFeed"}}, "get": {"scopes": ["https://www.googleapis.com/auth/plus.me", "https://www.googleapis.com/auth/userinfo.email"], "parameters": {"userId": {"required": true, "type": "string", "location": "path"}}, "id": "plus.people.get", "httpMethod": "GET", "path": "people/{userId}", "response": {"$ref": "Person"}}}}', true));

  }
}

class Acl extends apiModel {
  protected $__itemsType = 'PlusAclentryResource';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $description;
  public function setItems(/* array(PlusAclentryResource) */ $items) {
    $this->assertIsArray($items, 'PlusAclentryResource', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setDescription($description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
}

class Activity extends apiModel {
  public $placeName;
  public $kind;
  public $updated;
  protected $__providerType = 'ActivityProvider';
  protected $__providerDataType = '';
  public $provider;
  public $title;
  public $url;
  public $geocode;
  protected $__objectType = 'ActivityObject';
  protected $__objectDataType = '';
  public $object;
  public $placeId;
  protected $__actorType = 'ActivityActor';
  protected $__actorDataType = '';
  public $actor;
  public $id;
  protected $__accessType = 'Acl';
  protected $__accessDataType = '';
  public $access;
  public $verb;
  public $etag;
  public $radius;
  public $address;
  public $crosspostSource;
  public $placeholder;
  public $annotation;
  public $published;
  public function setPlaceName($placeName) {
    $this->placeName = $placeName;
  }
  public function getPlaceName() {
    return $this->placeName;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setUpdated($updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setProvider(ActivityProvider $provider) {
    $this->provider = $provider;
  }
  public function getProvider() {
    return $this->provider;
  }
  public function setTitle($title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setGeocode($geocode) {
    $this->geocode = $geocode;
  }
  public function getGeocode() {
    return $this->geocode;
  }
  public function setObject(ActivityObject $object) {
    $this->object = $object;
  }
  public function getObject() {
    return $this->object;
  }
  public function setPlaceId($placeId) {
    $this->placeId = $placeId;
  }
  public function getPlaceId() {
    return $this->placeId;
  }
  public function setActor(ActivityActor $actor) {
    $this->actor = $actor;
  }
  public function getActor() {
    return $this->actor;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setAccess(Acl $access) {
    $this->access = $access;
  }
  public function getAccess() {
    return $this->access;
  }
  public function setVerb($verb) {
    $this->verb = $verb;
  }
  public function getVerb() {
    return $this->verb;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setRadius($radius) {
    $this->radius = $radius;
  }
  public function getRadius() {
    return $this->radius;
  }
  public function setAddress($address) {
    $this->address = $address;
  }
  public function getAddress() {
    return $this->address;
  }
  public function setCrosspostSource($crosspostSource) {
    $this->crosspostSource = $crosspostSource;
  }
  public function getCrosspostSource() {
    return $this->crosspostSource;
  }
  public function setPlaceholder($placeholder) {
    $this->placeholder = $placeholder;
  }
  public function getPlaceholder() {
    return $this->placeholder;
  }
  public function setAnnotation($annotation) {
    $this->annotation = $annotation;
  }
  public function getAnnotation() {
    return $this->annotation;
  }
  public function setPublished($published) {
    $this->published = $published;
  }
  public function getPublished() {
    return $this->published;
  }
}

class ActivityActor extends apiModel {
  public $url;
  protected $__imageType = 'ActivityActorImage';
  protected $__imageDataType = '';
  public $image;
  public $displayName;
  public $id;
  protected $__nameType = 'ActivityActorName';
  protected $__nameDataType = '';
  public $name;
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setImage(ActivityActorImage $image) {
    $this->image = $image;
  }
  public function getImage() {
    return $this->image;
  }
  public function setDisplayName($displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setName(ActivityActorName $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
}

class ActivityActorImage extends apiModel {
  public $url;
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class ActivityActorName extends apiModel {
  public $givenName;
  public $familyName;
  public function setGivenName($givenName) {
    $this->givenName = $givenName;
  }
  public function getGivenName() {
    return $this->givenName;
  }
  public function setFamilyName($familyName) {
    $this->familyName = $familyName;
  }
  public function getFamilyName() {
    return $this->familyName;
  }
}

class ActivityFeed extends apiModel {
  public $nextPageToken;
  public $kind;
  public $title;
  protected $__itemsType = 'Activity';
  protected $__itemsDataType = 'array';
  public $items;
  public $updated;
  public $nextLink;
  public $etag;
  public $id;
  public $selfLink;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setTitle($title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
  public function setItems(/* array(Activity) */ $items) {
    $this->assertIsArray($items, 'Activity', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
  }
  public function setUpdated($updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setNextLink($nextLink) {
    $this->nextLink = $nextLink;
  }
  public function getNextLink() {
    return $this->nextLink;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setSelfLink($selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
}

class ActivityObject extends apiModel {
  protected $__resharersType = 'ActivityObjectResharers';
  protected $__resharersDataType = '';
  public $resharers;
  protected $__attachmentsType = 'ActivityObjectAttachments';
  protected $__attachmentsDataType = 'array';
  public $attachments;
  public $originalContent;
  protected $__plusonersType = 'ActivityObjectPlusoners';
  protected $__plusonersDataType = '';
  public $plusoners;
  protected $__actorType = 'ActivityObjectActor';
  protected $__actorDataType = '';
  public $actor;
  public $content;
  public $url;
  protected $__repliesType = 'ActivityObjectReplies';
  protected $__repliesDataType = '';
  public $replies;
  public $id;
  public $objectType;
  public function setResharers(ActivityObjectResharers $resharers) {
    $this->resharers = $resharers;
  }
  public function getResharers() {
    return $this->resharers;
  }
  public function setAttachments(/* array(ActivityObjectAttachments) */ $attachments) {
    $this->assertIsArray($attachments, 'ActivityObjectAttachments', __METHOD__);
    $this->attachments = $attachments;
  }
  public function getAttachments() {
    return $this->attachments;
  }
  public function setOriginalContent($originalContent) {
    $this->originalContent = $originalContent;
  }
  public function getOriginalContent() {
    return $this->originalContent;
  }
  public function setPlusoners(ActivityObjectPlusoners $plusoners) {
    $this->plusoners = $plusoners;
  }
  public function getPlusoners() {
    return $this->plusoners;
  }
  public function setActor(ActivityObjectActor $actor) {
    $this->actor = $actor;
  }
  public function getActor() {
    return $this->actor;
  }
  public function setContent($content) {
    $this->content = $content;
  }
  public function getContent() {
    return $this->content;
  }
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setReplies(ActivityObjectReplies $replies) {
    $this->replies = $replies;
  }
  public function getReplies() {
    return $this->replies;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setObjectType($objectType) {
    $this->objectType = $objectType;
  }
  public function getObjectType() {
    return $this->objectType;
  }
}

class ActivityObjectActor extends apiModel {
  public $url;
  protected $__imageType = 'ActivityObjectActorImage';
  protected $__imageDataType = '';
  public $image;
  public $displayName;
  public $id;
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setImage(ActivityObjectActorImage $image) {
    $this->image = $image;
  }
  public function getImage() {
    return $this->image;
  }
  public function setDisplayName($displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
}

class ActivityObjectActorImage extends apiModel {
  public $url;
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class ActivityObjectAttachments extends apiModel {
  public $displayName;
  protected $__fullImageType = 'ActivityObjectAttachmentsFullImage';
  protected $__fullImageDataType = '';
  public $fullImage;
  public $url;
  protected $__imageType = 'ActivityObjectAttachmentsImage';
  protected $__imageDataType = '';
  public $image;
  public $content;
  protected $__embedType = 'ActivityObjectAttachmentsEmbed';
  protected $__embedDataType = '';
  public $embed;
  public $id;
  public $objectType;
  public function setDisplayName($displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setFullImage(ActivityObjectAttachmentsFullImage $fullImage) {
    $this->fullImage = $fullImage;
  }
  public function getFullImage() {
    return $this->fullImage;
  }
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setImage(ActivityObjectAttachmentsImage $image) {
    $this->image = $image;
  }
  public function getImage() {
    return $this->image;
  }
  public function setContent($content) {
    $this->content = $content;
  }
  public function getContent() {
    return $this->content;
  }
  public function setEmbed(ActivityObjectAttachmentsEmbed $embed) {
    $this->embed = $embed;
  }
  public function getEmbed() {
    return $this->embed;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setObjectType($objectType) {
    $this->objectType = $objectType;
  }
  public function getObjectType() {
    return $this->objectType;
  }
}

class ActivityObjectAttachmentsEmbed extends apiModel {
  public $url;
  public $type;
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}

class ActivityObjectAttachmentsFullImage extends apiModel {
  public $url;
  public $width;
  public $type;
  public $height;
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setWidth($width) {
    $this->width = $width;
  }
  public function getWidth() {
    return $this->width;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setHeight($height) {
    $this->height = $height;
  }
  public function getHeight() {
    return $this->height;
  }
}

class ActivityObjectAttachmentsImage extends apiModel {
  public $url;
  public $width;
  public $type;
  public $height;
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setWidth($width) {
    $this->width = $width;
  }
  public function getWidth() {
    return $this->width;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setHeight($height) {
    $this->height = $height;
  }
  public function getHeight() {
    return $this->height;
  }
}

class ActivityObjectPlusoners extends apiModel {
  public $totalItems;
  public $selfLink;
  public function setTotalItems($totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
  public function setSelfLink($selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
}

class ActivityObjectReplies extends apiModel {
  public $totalItems;
  public $selfLink;
  public function setTotalItems($totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
  public function setSelfLink($selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
}

class ActivityObjectResharers extends apiModel {
  public $totalItems;
  public $selfLink;
  public function setTotalItems($totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
  public function setSelfLink($selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
}

class ActivityProvider extends apiModel {
  public $title;
  public function setTitle($title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
}

class Comment extends apiModel {
  protected $__inReplyToType = 'CommentInReplyTo';
  protected $__inReplyToDataType = 'array';
  public $inReplyTo;
  public $kind;
  protected $__objectType = 'CommentObject';
  protected $__objectDataType = '';
  public $object;
  public $updated;
  protected $__actorType = 'CommentActor';
  protected $__actorDataType = '';
  public $actor;
  public $verb;
  public $etag;
  public $published;
  public $id;
  public $selfLink;
  public function setInReplyTo(/* array(CommentInReplyTo) */ $inReplyTo) {
    $this->assertIsArray($inReplyTo, 'CommentInReplyTo', __METHOD__);
    $this->inReplyTo = $inReplyTo;
  }
  public function getInReplyTo() {
    return $this->inReplyTo;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setObject(CommentObject $object) {
    $this->object = $object;
  }
  public function getObject() {
    return $this->object;
  }
  public function setUpdated($updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setActor(CommentActor $actor) {
    $this->actor = $actor;
  }
  public function getActor() {
    return $this->actor;
  }
  public function setVerb($verb) {
    $this->verb = $verb;
  }
  public function getVerb() {
    return $this->verb;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setPublished($published) {
    $this->published = $published;
  }
  public function getPublished() {
    return $this->published;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setSelfLink($selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
}

class CommentActor extends apiModel {
  public $url;
  protected $__imageType = 'CommentActorImage';
  protected $__imageDataType = '';
  public $image;
  public $displayName;
  public $id;
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setImage(CommentActorImage $image) {
    $this->image = $image;
  }
  public function getImage() {
    return $this->image;
  }
  public function setDisplayName($displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
}

class CommentActorImage extends apiModel {
  public $url;
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class CommentFeed extends apiModel {
  public $nextPageToken;
  public $kind;
  public $title;
  protected $__itemsType = 'Comment';
  protected $__itemsDataType = 'array';
  public $items;
  public $updated;
  public $nextLink;
  public $etag;
  public $id;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setTitle($title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
  public function setItems(/* array(Comment) */ $items) {
    $this->assertIsArray($items, 'Comment', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
  }
  public function setUpdated($updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setNextLink($nextLink) {
    $this->nextLink = $nextLink;
  }
  public function getNextLink() {
    return $this->nextLink;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
}

class CommentInReplyTo extends apiModel {
  public $url;
  public $id;
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
}

class CommentObject extends apiModel {
  public $content;
  public $objectType;
  public function setContent($content) {
    $this->content = $content;
  }
  public function getContent() {
    return $this->content;
  }
  public function setObjectType($objectType) {
    $this->objectType = $objectType;
  }
  public function getObjectType() {
    return $this->objectType;
  }
}

class PeopleFeed extends apiModel {
  public $nextPageToken;
  public $kind;
  public $title;
  protected $__itemsType = 'Person';
  protected $__itemsDataType = 'array';
  public $items;
  public $etag;
  public $selfLink;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setTitle($title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
  public function setItems(/* array(Person) */ $items) {
    $this->assertIsArray($items, 'Person', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setSelfLink($selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
}

class Person extends apiModel {
  public $relationshipStatus;
  protected $__organizationsType = 'PersonOrganizations';
  protected $__organizationsDataType = 'array';
  public $organizations;
  public $kind;
  public $displayName;
  protected $__nameType = 'PersonName';
  protected $__nameDataType = '';
  public $name;
  public $url;
  public $gender;
  public $aboutMe;
  public $tagline;
  protected $__urlsType = 'PersonUrls';
  protected $__urlsDataType = 'array';
  public $urls;
  protected $__placesLivedType = 'PersonPlacesLived';
  protected $__placesLivedDataType = 'array';
  public $placesLived;
  protected $__emailsType = 'PersonEmails';
  protected $__emailsDataType = 'array';
  public $emails;
  public $nickname;
  public $birthday;
  public $etag;
  protected $__imageType = 'PersonImage';
  protected $__imageDataType = '';
  public $image;
  public $hasApp;
  public $id;
  public $languagesSpoken;
  public $currentLocation;
  public $objectType;
  public function setRelationshipStatus($relationshipStatus) {
    $this->relationshipStatus = $relationshipStatus;
  }
  public function getRelationshipStatus() {
    return $this->relationshipStatus;
  }
  public function setOrganizations(/* array(PersonOrganizations) */ $organizations) {
    $this->assertIsArray($organizations, 'PersonOrganizations', __METHOD__);
    $this->organizations = $organizations;
  }
  public function getOrganizations() {
    return $this->organizations;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setDisplayName($displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setName(PersonName $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setGender($gender) {
    $this->gender = $gender;
  }
  public function getGender() {
    return $this->gender;
  }
  public function setAboutMe($aboutMe) {
    $this->aboutMe = $aboutMe;
  }
  public function getAboutMe() {
    return $this->aboutMe;
  }
  public function setTagline($tagline) {
    $this->tagline = $tagline;
  }
  public function getTagline() {
    return $this->tagline;
  }
  public function setUrls(/* array(PersonUrls) */ $urls) {
    $this->assertIsArray($urls, 'PersonUrls', __METHOD__);
    $this->urls = $urls;
  }
  public function getUrls() {
    return $this->urls;
  }
  public function setPlacesLived(/* array(PersonPlacesLived) */ $placesLived) {
    $this->assertIsArray($placesLived, 'PersonPlacesLived', __METHOD__);
    $this->placesLived = $placesLived;
  }
  public function getPlacesLived() {
    return $this->placesLived;
  }
  public function setEmails(/* array(PersonEmails) */ $emails) {
    $this->assertIsArray($emails, 'PersonEmails', __METHOD__);
    $this->emails = $emails;
  }
  public function getEmails() {
    return $this->emails;
  }
  public function setNickname($nickname) {
    $this->nickname = $nickname;
  }
  public function getNickname() {
    return $this->nickname;
  }
  public function setBirthday($birthday) {
    $this->birthday = $birthday;
  }
  public function getBirthday() {
    return $this->birthday;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setImage(PersonImage $image) {
    $this->image = $image;
  }
  public function getImage() {
    return $this->image;
  }
  public function setHasApp($hasApp) {
    $this->hasApp = $hasApp;
  }
  public function getHasApp() {
    return $this->hasApp;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setLanguagesSpoken(/* array(string) */ $languagesSpoken) {
    $this->assertIsArray($languagesSpoken, 'string', __METHOD__);
    $this->languagesSpoken = $languagesSpoken;
  }
  public function getLanguagesSpoken() {
    return $this->languagesSpoken;
  }
  public function setCurrentLocation($currentLocation) {
    $this->currentLocation = $currentLocation;
  }
  public function getCurrentLocation() {
    return $this->currentLocation;
  }
  public function setObjectType($objectType) {
    $this->objectType = $objectType;
  }
  public function getObjectType() {
    return $this->objectType;
  }
}

class PersonEmails extends apiModel {
  public $type;
  public $primary;
  public $value;
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setPrimary($primary) {
    $this->primary = $primary;
  }
  public function getPrimary() {
    return $this->primary;
  }
  public function setValue($value) {
    $this->value = $value;
  }
  public function getValue() {
    return $this->value;
  }
}

class PersonImage extends apiModel {
  public $url;
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class PersonName extends apiModel {
  public $honorificPrefix;
  public $middleName;
  public $familyName;
  public $formatted;
  public $givenName;
  public $honorificSuffix;
  public function setHonorificPrefix($honorificPrefix) {
    $this->honorificPrefix = $honorificPrefix;
  }
  public function getHonorificPrefix() {
    return $this->honorificPrefix;
  }
  public function setMiddleName($middleName) {
    $this->middleName = $middleName;
  }
  public function getMiddleName() {
    return $this->middleName;
  }
  public function setFamilyName($familyName) {
    $this->familyName = $familyName;
  }
  public function getFamilyName() {
    return $this->familyName;
  }
  public function setFormatted($formatted) {
    $this->formatted = $formatted;
  }
  public function getFormatted() {
    return $this->formatted;
  }
  public function setGivenName($givenName) {
    $this->givenName = $givenName;
  }
  public function getGivenName() {
    return $this->givenName;
  }
  public function setHonorificSuffix($honorificSuffix) {
    $this->honorificSuffix = $honorificSuffix;
  }
  public function getHonorificSuffix() {
    return $this->honorificSuffix;
  }
}

class PersonOrganizations extends apiModel {
  public $startDate;
  public $endDate;
  public $description;
  public $title;
  public $primary;
  public $location;
  public $department;
  public $type;
  public $name;
  public function setStartDate($startDate) {
    $this->startDate = $startDate;
  }
  public function getStartDate() {
    return $this->startDate;
  }
  public function setEndDate($endDate) {
    $this->endDate = $endDate;
  }
  public function getEndDate() {
    return $this->endDate;
  }
  public function setDescription($description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
  public function setTitle($title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
  public function setPrimary($primary) {
    $this->primary = $primary;
  }
  public function getPrimary() {
    return $this->primary;
  }
  public function setLocation($location) {
    $this->location = $location;
  }
  public function getLocation() {
    return $this->location;
  }
  public function setDepartment($department) {
    $this->department = $department;
  }
  public function getDepartment() {
    return $this->department;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
}

class PersonPlacesLived extends apiModel {
  public $primary;
  public $value;
  public function setPrimary($primary) {
    $this->primary = $primary;
  }
  public function getPrimary() {
    return $this->primary;
  }
  public function setValue($value) {
    $this->value = $value;
  }
  public function getValue() {
    return $this->value;
  }
}

class PersonUrls extends apiModel {
  public $type;
  public $primary;
  public $value;
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setPrimary($primary) {
    $this->primary = $primary;
  }
  public function getPrimary() {
    return $this->primary;
  }
  public function setValue($value) {
    $this->value = $value;
  }
  public function getValue() {
    return $this->value;
  }
}

class PlusAclentryResource extends apiModel {
  public $type;
  public $id;
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
}
