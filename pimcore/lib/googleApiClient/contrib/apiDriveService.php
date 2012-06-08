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
   * The "files" collection of methods.
   * Typical usage is:
   *  <code>
   *   $driveService = new apiDriveService(...);
   *   $files = $driveService->files;
   *  </code>
   */
  class FilesServiceResource extends apiServiceResource {


    /**
     * Inserts a file, and any settable metadata or blob content sent with the request. (files.insert)
     *
     * @param DriveFile $postBody
     * @return DriveFile
     */
    public function insert(DriveFile $postBody, $optParams = array()) {
      $params = array('postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new DriveFile($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates file metadata and/or content. This method supports patch semantics. (files.patch)
     *
     * @param string $id The id for the file in question.
     * @param DriveFile $postBody
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param bool updateViewedDate Whether to update the view date after successfully updating the file.
     * @opt_param bool updateModifiedDate Controls updating the modified date of the file. If true, the modified date will be updated to the current time, regardless of whether other changes are being made. If false, the modified date will only be updated to the current time if other changes are also being made (changing the title, for example).
     * @opt_param bool newRevision Whether a blob upload should create a new revision. If not set or false, the blob data in the current head revision will be replaced.
     * @return DriveFile
     */
    public function patch($id, DriveFile $postBody, $optParams = array()) {
      $params = array('id' => $id, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new DriveFile($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates file metadata and/or content (files.update)
     *
     * @param string $id The id for the file in question.
     * @param DriveFile $postBody
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param bool updateViewedDate Whether to update the view date after successfully updating the file.
     * @opt_param bool updateModifiedDate Controls updating the modified date of the file. If true, the modified date will be updated to the current time, regardless of whether other changes are being made. If false, the modified date will only be updated to the current time if other changes are also being made (changing the title, for example).
     * @opt_param bool newRevision Whether a blob upload should create a new revision. If not set or false, the blob data in the current head revision will be replaced.
     * @return DriveFile
     */
    public function update($id, DriveFile $postBody, $optParams = array()) {
      $params = array('id' => $id, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new DriveFile($data);
      } else {
        return $data;
      }
    }
    /**
     * Gets a file's metadata by id. (files.get)
     *
     * @param string $id The id for the file in question.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param bool updateViewedDate Whether to update the view date after successfully retrieving the file.
     * @opt_param string projection Restrict information returned for simplicity and optimization.
     * @return DriveFile
     */
    public function get($id, $optParams = array()) {
      $params = array('id' => $id);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new DriveFile($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Drive (v1).
 *
 * <p>
 * The API to interact with Drive
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://developers.google.com/drive/" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class apiDriveService extends apiService {
  public $files;
  /**
   * Constructs the internal representation of the Drive service.
   *
   * @param apiClient apiClient
   */
  public function __construct(apiClient $apiClient) {
    $this->restBasePath = '/drive/v1/';
    $this->version = 'v1';
    $this->serviceName = 'drive';

    $apiClient->addService($this->serviceName, $this->version);
    $this->files = new FilesServiceResource($this, $this->serviceName, 'files', json_decode('{"methods": {"insert": {"scopes": ["https://www.googleapis.com/auth/drive.file"], "mediaUpload": {"maxSize": "10GB", "accept": ["*/*"], "protocols": {"simple": {"path": "/upload/drive/v1/files", "multipart": true}, "resumable": {"path": "/resumable/upload/drive/v1/files", "multipart": true}}}, "request": {"$ref": "File"}, "response": {"$ref": "File"}, "httpMethod": "POST", "path": "files", "id": "drive.files.insert"}, "get": {"scopes": ["https://www.googleapis.com/auth/drive.file"], "parameters": {"updateViewedDate": {"default": "true", "type": "boolean", "location": "query"}, "id": {"required": true, "type": "string", "location": "path"}, "projection": {"enum": ["BASIC", "FULL"], "type": "string", "location": "query"}}, "id": "drive.files.get", "httpMethod": "GET", "path": "files/{id}", "response": {"$ref": "File"}}, "update": {"scopes": ["https://www.googleapis.com/auth/drive.file"], "parameters": {"updateViewedDate": {"default": "true", "type": "boolean", "location": "query"}, "updateModifiedDate": {"default": "false", "type": "boolean", "location": "query"}, "id": {"required": true, "type": "string", "location": "path"}, "newRevision": {"default": "true", "type": "boolean", "location": "query"}}, "mediaUpload": {"maxSize": "10GB", "accept": ["*/*"], "protocols": {"simple": {"path": "/upload/drive/v1/files/{id}", "multipart": true}, "resumable": {"path": "/resumable/upload/drive/v1/files/{id}", "multipart": true}}}, "request": {"$ref": "File"}, "id": "drive.files.update", "httpMethod": "PUT", "path": "files/{id}", "response": {"$ref": "File"}}, "patch": {"scopes": ["https://www.googleapis.com/auth/drive.file"], "parameters": {"updateViewedDate": {"default": "true", "type": "boolean", "location": "query"}, "updateModifiedDate": {"default": "false", "type": "boolean", "location": "query"}, "id": {"required": true, "type": "string", "location": "path"}, "newRevision": {"default": "true", "type": "boolean", "location": "query"}}, "request": {"$ref": "File"}, "id": "drive.files.patch", "httpMethod": "PATCH", "path": "files/{id}", "response": {"$ref": "File"}}}}', true));

  }
}

class DriveFile extends apiModel {
  public $mimeType;
  public $selfLink;
  public $kind;
  public $description;
  public $title;
  public $modifiedByMeDate;
  protected $__labelsType = 'DriveFileLabels';
  protected $__labelsDataType = '';
  public $labels;
  protected $__indexableTextType = 'DriveFileIndexableText';
  protected $__indexableTextDataType = '';
  public $indexableText;
  protected $__parentsCollectionType = 'DriveFileParentsCollection';
  protected $__parentsCollectionDataType = 'array';
  public $parentsCollection;
  public $downloadUrl;
  protected $__userPermissionType = 'Permission';
  protected $__userPermissionDataType = '';
  public $userPermission;
  public $etag;
  public $fileSize;
  public $createdDate;
  public $fileExtension;
  public $lastViewedDate;
  public $id;
  public $md5Checksum;
  public $modifiedDate;
  public function setMimeType($mimeType) {
    $this->mimeType = $mimeType;
  }
  public function getMimeType() {
    return $this->mimeType;
  }
  public function setSelfLink($selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
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
  public function setTitle($title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
  public function setModifiedByMeDate($modifiedByMeDate) {
    $this->modifiedByMeDate = $modifiedByMeDate;
  }
  public function getModifiedByMeDate() {
    return $this->modifiedByMeDate;
  }
  public function setLabels(DriveFileLabels $labels) {
    $this->labels = $labels;
  }
  public function getLabels() {
    return $this->labels;
  }
  public function setIndexableText(DriveFileIndexableText $indexableText) {
    $this->indexableText = $indexableText;
  }
  public function getIndexableText() {
    return $this->indexableText;
  }
  public function setParentsCollection(/* array(DriveFileParentsCollection) */ $parentsCollection) {
    $this->assertIsArray($parentsCollection, 'DriveFileParentsCollection', __METHOD__);
    $this->parentsCollection = $parentsCollection;
  }
  public function getParentsCollection() {
    return $this->parentsCollection;
  }
  public function setDownloadUrl($downloadUrl) {
    $this->downloadUrl = $downloadUrl;
  }
  public function getDownloadUrl() {
    return $this->downloadUrl;
  }
  public function setUserPermission(Permission $userPermission) {
    $this->userPermission = $userPermission;
  }
  public function getUserPermission() {
    return $this->userPermission;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setFileSize($fileSize) {
    $this->fileSize = $fileSize;
  }
  public function getFileSize() {
    return $this->fileSize;
  }
  public function setCreatedDate($createdDate) {
    $this->createdDate = $createdDate;
  }
  public function getCreatedDate() {
    return $this->createdDate;
  }
  public function setFileExtension($fileExtension) {
    $this->fileExtension = $fileExtension;
  }
  public function getFileExtension() {
    return $this->fileExtension;
  }
  public function setLastViewedDate($lastViewedDate) {
    $this->lastViewedDate = $lastViewedDate;
  }
  public function getLastViewedDate() {
    return $this->lastViewedDate;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setMd5Checksum($md5Checksum) {
    $this->md5Checksum = $md5Checksum;
  }
  public function getMd5Checksum() {
    return $this->md5Checksum;
  }
  public function setModifiedDate($modifiedDate) {
    $this->modifiedDate = $modifiedDate;
  }
  public function getModifiedDate() {
    return $this->modifiedDate;
  }
}

class DriveFileIndexableText extends apiModel {
  public $text;
  public function setText($text) {
    $this->text = $text;
  }
  public function getText() {
    return $this->text;
  }
}

class DriveFileLabels extends apiModel {
  public $hidden;
  public $starred;
  public $trashed;
  public function setHidden($hidden) {
    $this->hidden = $hidden;
  }
  public function getHidden() {
    return $this->hidden;
  }
  public function setStarred($starred) {
    $this->starred = $starred;
  }
  public function getStarred() {
    return $this->starred;
  }
  public function setTrashed($trashed) {
    $this->trashed = $trashed;
  }
  public function getTrashed() {
    return $this->trashed;
  }
}

class DriveFileParentsCollection extends apiModel {
  public $parentLink;
  public $id;
  public function setParentLink($parentLink) {
    $this->parentLink = $parentLink;
  }
  public function getParentLink() {
    return $this->parentLink;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
}

class Permission extends apiModel {
  public $type;
  public $kind;
  public $etag;
  public $role;
  public $additionalRoles;
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setRole($role) {
    $this->role = $role;
  }
  public function getRole() {
    return $this->role;
  }
  public function setAdditionalRoles(/* array(string) */ $additionalRoles) {
    $this->assertIsArray($additionalRoles, 'string', __METHOD__);
    $this->additionalRoles = $additionalRoles;
  }
  public function getAdditionalRoles() {
    return $this->additionalRoles;
  }
}
