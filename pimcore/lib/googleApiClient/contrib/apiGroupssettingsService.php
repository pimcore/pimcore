<?php
/*
 * Copyright (c) 2010 Google Inc.
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
   * The "groups" collection of methods.
   * Typical usage is:
   *  <code>
   *   $groupssettingsService = new apiGroupssettingsService(...);
   *   $groups = $groupssettingsService->groups;
   *  </code>
   */
  class GroupsServiceResource extends apiServiceResource {


    /**
     * Updates an existing resource. This method supports patch semantics. (groups.patch)
     *
     * @param string $groupUniqueId The resource ID
     * @param Groups $postBody
     * @return Groups
     */
    public function patch($groupUniqueId, Groups $postBody, $optParams = array()) {
      $params = array('groupUniqueId' => $groupUniqueId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Groups($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates an existing resource. (groups.update)
     *
     * @param string $groupUniqueId The resource ID
     * @param Groups $postBody
     * @return Groups
     */
    public function update($groupUniqueId, Groups $postBody, $optParams = array()) {
      $params = array('groupUniqueId' => $groupUniqueId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Groups($data);
      } else {
        return $data;
      }
    }
    /**
     * Gets one resource by id. (groups.get)
     *
     * @param string $groupUniqueId The resource ID
     * @return Groups
     */
    public function get($groupUniqueId, $optParams = array()) {
      $params = array('groupUniqueId' => $groupUniqueId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Groups($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Groupssettings (v1).
 *
 * <p>
 * Groups Settings Api
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class apiGroupssettingsService extends apiService {
  public $groups;
  /**
   * Constructs the internal representation of the Groupssettings service.
   *
   * @param apiClient apiClient
   */
  public function __construct(apiClient $apiClient) {
    $this->rpcPath = '/rpc';
    $this->restBasePath = '/groups/v1/groups/';
    $this->version = 'v1';
    $this->serviceName = 'groupssettings';

    $apiClient->addService($this->serviceName, $this->version);
    $this->groups = new GroupsServiceResource($this, $this->serviceName, 'groups', json_decode('{"methods": {"get": {"scopes": ["https://www.googleapis.com/auth/apps.groups.settings"], "parameters": {"groupUniqueId": {"required": true, "type": "string", "location": "path"}}, "id": "groupsSettings.groups.get", "httpMethod": "GET", "path": "{groupUniqueId}", "response": {"$ref": "Groups"}}, "update": {"scopes": ["https://www.googleapis.com/auth/apps.groups.settings"], "parameters": {"groupUniqueId": {"required": true, "type": "string", "location": "path"}}, "request": {"$ref": "Groups"}, "id": "groupsSettings.groups.update", "httpMethod": "PUT", "path": "{groupUniqueId}", "response": {"$ref": "Groups"}}, "patch": {"scopes": ["https://www.googleapis.com/auth/apps.groups.settings"], "parameters": {"groupUniqueId": {"required": true, "type": "string", "location": "path"}}, "request": {"$ref": "Groups"}, "id": "groupsSettings.groups.patch", "httpMethod": "PATCH", "path": "{groupUniqueId}", "response": {"$ref": "Groups"}}}}', true));

  }
}

class Groups extends apiModel {
  public $allowExternalMembers;
  public $whoCanJoin;
  public $primaryLanguage;
  public $whoCanViewMembership;
  public $defaultMessageDenyNotificationText;
  public $archiveOnly;
  public $isArchived;
  public $membersCanPostAsTheGroup;
  public $allowWebPosting;
  public $email;
  public $messageModerationLevel;
  public $description;
  public $replyTo;
  public $customReplyTo;
  public $sendMessageDenyNotification;
  public $messageDisplayFont;
  public $whoCanPostMessage;
  public $name;
  public $kind;
  public $whoCanInvite;
  public $whoCanViewGroup;
  public $showInGroupDirectory;
  public $maxMessageBytes;
  public $allowGoogleCommunication;
  public function setAllowExternalMembers($allowExternalMembers) {
    $this->allowExternalMembers = $allowExternalMembers;
  }
  public function getAllowExternalMembers() {
    return $this->allowExternalMembers;
  }
  public function setWhoCanJoin($whoCanJoin) {
    $this->whoCanJoin = $whoCanJoin;
  }
  public function getWhoCanJoin() {
    return $this->whoCanJoin;
  }
  public function setPrimaryLanguage($primaryLanguage) {
    $this->primaryLanguage = $primaryLanguage;
  }
  public function getPrimaryLanguage() {
    return $this->primaryLanguage;
  }
  public function setWhoCanViewMembership($whoCanViewMembership) {
    $this->whoCanViewMembership = $whoCanViewMembership;
  }
  public function getWhoCanViewMembership() {
    return $this->whoCanViewMembership;
  }
  public function setDefaultMessageDenyNotificationText($defaultMessageDenyNotificationText) {
    $this->defaultMessageDenyNotificationText = $defaultMessageDenyNotificationText;
  }
  public function getDefaultMessageDenyNotificationText() {
    return $this->defaultMessageDenyNotificationText;
  }
  public function setArchiveOnly($archiveOnly) {
    $this->archiveOnly = $archiveOnly;
  }
  public function getArchiveOnly() {
    return $this->archiveOnly;
  }
  public function setIsArchived($isArchived) {
    $this->isArchived = $isArchived;
  }
  public function getIsArchived() {
    return $this->isArchived;
  }
  public function setMembersCanPostAsTheGroup($membersCanPostAsTheGroup) {
    $this->membersCanPostAsTheGroup = $membersCanPostAsTheGroup;
  }
  public function getMembersCanPostAsTheGroup() {
    return $this->membersCanPostAsTheGroup;
  }
  public function setAllowWebPosting($allowWebPosting) {
    $this->allowWebPosting = $allowWebPosting;
  }
  public function getAllowWebPosting() {
    return $this->allowWebPosting;
  }
  public function setEmail($email) {
    $this->email = $email;
  }
  public function getEmail() {
    return $this->email;
  }
  public function setMessageModerationLevel($messageModerationLevel) {
    $this->messageModerationLevel = $messageModerationLevel;
  }
  public function getMessageModerationLevel() {
    return $this->messageModerationLevel;
  }
  public function setDescription($description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
  public function setReplyTo($replyTo) {
    $this->replyTo = $replyTo;
  }
  public function getReplyTo() {
    return $this->replyTo;
  }
  public function setCustomReplyTo($customReplyTo) {
    $this->customReplyTo = $customReplyTo;
  }
  public function getCustomReplyTo() {
    return $this->customReplyTo;
  }
  public function setSendMessageDenyNotification($sendMessageDenyNotification) {
    $this->sendMessageDenyNotification = $sendMessageDenyNotification;
  }
  public function getSendMessageDenyNotification() {
    return $this->sendMessageDenyNotification;
  }
  public function setMessageDisplayFont($messageDisplayFont) {
    $this->messageDisplayFont = $messageDisplayFont;
  }
  public function getMessageDisplayFont() {
    return $this->messageDisplayFont;
  }
  public function setWhoCanPostMessage($whoCanPostMessage) {
    $this->whoCanPostMessage = $whoCanPostMessage;
  }
  public function getWhoCanPostMessage() {
    return $this->whoCanPostMessage;
  }
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setWhoCanInvite($whoCanInvite) {
    $this->whoCanInvite = $whoCanInvite;
  }
  public function getWhoCanInvite() {
    return $this->whoCanInvite;
  }
  public function setWhoCanViewGroup($whoCanViewGroup) {
    $this->whoCanViewGroup = $whoCanViewGroup;
  }
  public function getWhoCanViewGroup() {
    return $this->whoCanViewGroup;
  }
  public function setShowInGroupDirectory($showInGroupDirectory) {
    $this->showInGroupDirectory = $showInGroupDirectory;
  }
  public function getShowInGroupDirectory() {
    return $this->showInGroupDirectory;
  }
  public function setMaxMessageBytes($maxMessageBytes) {
    $this->maxMessageBytes = $maxMessageBytes;
  }
  public function getMaxMessageBytes() {
    return $this->maxMessageBytes;
  }
  public function setAllowGoogleCommunication($allowGoogleCommunication) {
    $this->allowGoogleCommunication = $allowGoogleCommunication;
  }
  public function getAllowGoogleCommunication() {
    return $this->allowGoogleCommunication;
  }
}
