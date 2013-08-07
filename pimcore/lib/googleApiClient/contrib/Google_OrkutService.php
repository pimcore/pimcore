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
   * The "acl" collection of methods.
   * Typical usage is:
   *  <code>
   *   $orkutService = new Google_OrkutService(...);
   *   $acl = $orkutService->acl;
   *  </code>
   */
  class Google_AclServiceResource extends Google_ServiceResource {

    /**
     * Excludes an element from the ACL of the activity. (acl.delete)
     *
     * @param string $activityId ID of the activity.
     * @param string $userId ID of the user to be removed from the activity.
     * @param array $optParams Optional parameters.
     */
    public function delete($activityId, $userId, $optParams = array()) {
      $params = array('activityId' => $activityId, 'userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
  }

  /**
   * The "activities" collection of methods.
   * Typical usage is:
   *  <code>
   *   $orkutService = new Google_OrkutService(...);
   *   $activities = $orkutService->activities;
   *  </code>
   */
  class Google_ActivitiesServiceResource extends Google_ServiceResource {

    /**
     * Deletes an existing activity, if the access controls allow it. (activities.delete)
     *
     * @param string $activityId ID of the activity to remove.
     * @param array $optParams Optional parameters.
     */
    public function delete($activityId, $optParams = array()) {
      $params = array('activityId' => $activityId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
    /**
     * Retrieves a list of activities. (activities.list)
     *
     * @param string $userId The ID of the user whose activities will be listed. Can be me to refer to the viewer (i.e. the authenticated user).
     * @param string $collection The collection of activities to list.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string hl Specifies the interface language (host language) of your user interface.
     * @opt_param string maxResults The maximum number of activities to include in the response.
     * @opt_param string pageToken A continuation token that allows pagination.
     * @return Google_ActivityList
     */
    public function listActivities($userId, $collection, $optParams = array()) {
      $params = array('userId' => $userId, 'collection' => $collection);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_ActivityList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "activityVisibility" collection of methods.
   * Typical usage is:
   *  <code>
   *   $orkutService = new Google_OrkutService(...);
   *   $activityVisibility = $orkutService->activityVisibility;
   *  </code>
   */
  class Google_ActivityVisibilityServiceResource extends Google_ServiceResource {

    /**
     * Gets the visibility of an existing activity. (activityVisibility.get)
     *
     * @param string $activityId ID of the activity to get the visibility.
     * @param array $optParams Optional parameters.
     * @return Google_Visibility
     */
    public function get($activityId, $optParams = array()) {
      $params = array('activityId' => $activityId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Visibility($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates the visibility of an existing activity. This method supports patch semantics.
     * (activityVisibility.patch)
     *
     * @param string $activityId ID of the activity.
     * @param Google_Visibility $postBody
     * @param array $optParams Optional parameters.
     * @return Google_Visibility
     */
    public function patch($activityId, Google_Visibility $postBody, $optParams = array()) {
      $params = array('activityId' => $activityId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Google_Visibility($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates the visibility of an existing activity. (activityVisibility.update)
     *
     * @param string $activityId ID of the activity.
     * @param Google_Visibility $postBody
     * @param array $optParams Optional parameters.
     * @return Google_Visibility
     */
    public function update($activityId, Google_Visibility $postBody, $optParams = array()) {
      $params = array('activityId' => $activityId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Google_Visibility($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "badges" collection of methods.
   * Typical usage is:
   *  <code>
   *   $orkutService = new Google_OrkutService(...);
   *   $badges = $orkutService->badges;
   *  </code>
   */
  class Google_BadgesServiceResource extends Google_ServiceResource {

    /**
     * Retrieves a badge from a user. (badges.get)
     *
     * @param string $userId The ID of the user whose badges will be listed. Can be me to refer to caller.
     * @param string $badgeId The ID of the badge that will be retrieved.
     * @param array $optParams Optional parameters.
     * @return Google_Badge
     */
    public function get($userId, $badgeId, $optParams = array()) {
      $params = array('userId' => $userId, 'badgeId' => $badgeId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Badge($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves the list of visible badges of a user. (badges.list)
     *
     * @param string $userId The id of the user whose badges will be listed. Can be me to refer to caller.
     * @param array $optParams Optional parameters.
     * @return Google_BadgeList
     */
    public function listBadges($userId, $optParams = array()) {
      $params = array('userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_BadgeList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "comments" collection of methods.
   * Typical usage is:
   *  <code>
   *   $orkutService = new Google_OrkutService(...);
   *   $comments = $orkutService->comments;
   *  </code>
   */
  class Google_CommentsServiceResource extends Google_ServiceResource {

    /**
     * Deletes an existing comment. (comments.delete)
     *
     * @param string $commentId ID of the comment to remove.
     * @param array $optParams Optional parameters.
     */
    public function delete($commentId, $optParams = array()) {
      $params = array('commentId' => $commentId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
    /**
     * Retrieves an existing comment. (comments.get)
     *
     * @param string $commentId ID of the comment to get.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string hl Specifies the interface language (host language) of your user interface.
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
     * Inserts a new comment to an activity. (comments.insert)
     *
     * @param string $activityId The ID of the activity to contain the new comment.
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
     * Retrieves a list of comments, possibly filtered. (comments.list)
     *
     * @param string $activityId The ID of the activity containing the comments.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string hl Specifies the interface language (host language) of your user interface.
     * @opt_param string maxResults The maximum number of activities to include in the response.
     * @opt_param string orderBy Sort search results.
     * @opt_param string pageToken A continuation token that allows pagination.
     * @return Google_CommentList
     */
    public function listComments($activityId, $optParams = array()) {
      $params = array('activityId' => $activityId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_CommentList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "communities" collection of methods.
   * Typical usage is:
   *  <code>
   *   $orkutService = new Google_OrkutService(...);
   *   $communities = $orkutService->communities;
   *  </code>
   */
  class Google_CommunitiesServiceResource extends Google_ServiceResource {

    /**
     * Retrieves the basic information (aka. profile) of a community. (communities.get)
     *
     * @param int $communityId The ID of the community to get.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string hl Specifies the interface language (host language) of your user interface.
     * @return Google_Community
     */
    public function get($communityId, $optParams = array()) {
      $params = array('communityId' => $communityId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Community($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves the list of communities the current user is a member of. (communities.list)
     *
     * @param string $userId The ID of the user whose communities will be listed. Can be me to refer to caller.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string hl Specifies the interface language (host language) of your user interface.
     * @opt_param string maxResults The maximum number of communities to include in the response.
     * @opt_param string orderBy How to order the communities by.
     * @return Google_CommunityList
     */
    public function listCommunities($userId, $optParams = array()) {
      $params = array('userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_CommunityList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "communityFollow" collection of methods.
   * Typical usage is:
   *  <code>
   *   $orkutService = new Google_OrkutService(...);
   *   $communityFollow = $orkutService->communityFollow;
   *  </code>
   */
  class Google_CommunityFollowServiceResource extends Google_ServiceResource {

    /**
     * Removes a user from the followers of a community. (communityFollow.delete)
     *
     * @param int $communityId ID of the community.
     * @param string $userId ID of the user.
     * @param array $optParams Optional parameters.
     */
    public function delete($communityId, $userId, $optParams = array()) {
      $params = array('communityId' => $communityId, 'userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
    /**
     * Adds a user as a follower of a community. (communityFollow.insert)
     *
     * @param int $communityId ID of the community.
     * @param string $userId ID of the user.
     * @param array $optParams Optional parameters.
     * @return Google_CommunityMembers
     */
    public function insert($communityId, $userId, $optParams = array()) {
      $params = array('communityId' => $communityId, 'userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_CommunityMembers($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "communityMembers" collection of methods.
   * Typical usage is:
   *  <code>
   *   $orkutService = new Google_OrkutService(...);
   *   $communityMembers = $orkutService->communityMembers;
   *  </code>
   */
  class Google_CommunityMembersServiceResource extends Google_ServiceResource {

    /**
     * Makes the user leave a community. (communityMembers.delete)
     *
     * @param int $communityId ID of the community.
     * @param string $userId ID of the user.
     * @param array $optParams Optional parameters.
     */
    public function delete($communityId, $userId, $optParams = array()) {
      $params = array('communityId' => $communityId, 'userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
    /**
     * Retrieves the relationship between a user and a community. (communityMembers.get)
     *
     * @param int $communityId ID of the community.
     * @param string $userId ID of the user.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string hl Specifies the interface language (host language) of your user interface.
     * @return Google_CommunityMembers
     */
    public function get($communityId, $userId, $optParams = array()) {
      $params = array('communityId' => $communityId, 'userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_CommunityMembers($data);
      } else {
        return $data;
      }
    }
    /**
     * Makes the user join a community. (communityMembers.insert)
     *
     * @param int $communityId ID of the community.
     * @param string $userId ID of the user.
     * @param array $optParams Optional parameters.
     * @return Google_CommunityMembers
     */
    public function insert($communityId, $userId, $optParams = array()) {
      $params = array('communityId' => $communityId, 'userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_CommunityMembers($data);
      } else {
        return $data;
      }
    }
    /**
     * Lists members of a community. Use the pagination tokens to retrieve the full list; do not rely on
     * the member count available in the community profile information to know when to stop iterating,
     * as that count may be approximate. (communityMembers.list)
     *
     * @param int $communityId The ID of the community whose members will be listed.
     * @param array $optParams Optional parameters.
     *
     * @opt_param bool friendsOnly Whether to list only community members who are friends of the user.
     * @opt_param string hl Specifies the interface language (host language) of your user interface.
     * @opt_param string maxResults The maximum number of members to include in the response.
     * @opt_param string pageToken A continuation token that allows pagination.
     * @return Google_CommunityMembersList
     */
    public function listCommunityMembers($communityId, $optParams = array()) {
      $params = array('communityId' => $communityId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_CommunityMembersList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "communityMessages" collection of methods.
   * Typical usage is:
   *  <code>
   *   $orkutService = new Google_OrkutService(...);
   *   $communityMessages = $orkutService->communityMessages;
   *  </code>
   */
  class Google_CommunityMessagesServiceResource extends Google_ServiceResource {

    /**
     * Moves a message of the community to the trash folder. (communityMessages.delete)
     *
     * @param int $communityId The ID of the community whose message will be moved to the trash folder.
     * @param string $topicId The ID of the topic whose message will be moved to the trash folder.
     * @param string $messageId The ID of the message to be moved to the trash folder.
     * @param array $optParams Optional parameters.
     */
    public function delete($communityId, $topicId, $messageId, $optParams = array()) {
      $params = array('communityId' => $communityId, 'topicId' => $topicId, 'messageId' => $messageId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
    /**
     * Adds a message to a given community topic. (communityMessages.insert)
     *
     * @param int $communityId The ID of the community the message should be added to.
     * @param string $topicId The ID of the topic the message should be added to.
     * @param Google_CommunityMessage $postBody
     * @param array $optParams Optional parameters.
     * @return Google_CommunityMessage
     */
    public function insert($communityId, $topicId, Google_CommunityMessage $postBody, $optParams = array()) {
      $params = array('communityId' => $communityId, 'topicId' => $topicId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_CommunityMessage($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves the messages of a topic of a community. (communityMessages.list)
     *
     * @param int $communityId The ID of the community which messages will be listed.
     * @param string $topicId The ID of the topic which messages will be listed.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string hl Specifies the interface language (host language) of your user interface.
     * @opt_param string maxResults The maximum number of messages to include in the response.
     * @opt_param string pageToken A continuation token that allows pagination.
     * @return Google_CommunityMessageList
     */
    public function listCommunityMessages($communityId, $topicId, $optParams = array()) {
      $params = array('communityId' => $communityId, 'topicId' => $topicId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_CommunityMessageList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "communityPollComments" collection of methods.
   * Typical usage is:
   *  <code>
   *   $orkutService = new Google_OrkutService(...);
   *   $communityPollComments = $orkutService->communityPollComments;
   *  </code>
   */
  class Google_CommunityPollCommentsServiceResource extends Google_ServiceResource {

    /**
     * Adds a comment on a community poll. (communityPollComments.insert)
     *
     * @param int $communityId The ID of the community whose poll is being commented.
     * @param string $pollId The ID of the poll being commented.
     * @param Google_CommunityPollComment $postBody
     * @param array $optParams Optional parameters.
     * @return Google_CommunityPollComment
     */
    public function insert($communityId, $pollId, Google_CommunityPollComment $postBody, $optParams = array()) {
      $params = array('communityId' => $communityId, 'pollId' => $pollId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_CommunityPollComment($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves the comments of a community poll. (communityPollComments.list)
     *
     * @param int $communityId The ID of the community whose poll is having its comments listed.
     * @param string $pollId The ID of the community whose polls will be listed.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string hl Specifies the interface language (host language) of your user interface.
     * @opt_param string maxResults The maximum number of comments to include in the response.
     * @opt_param string pageToken A continuation token that allows pagination.
     * @return Google_CommunityPollCommentList
     */
    public function listCommunityPollComments($communityId, $pollId, $optParams = array()) {
      $params = array('communityId' => $communityId, 'pollId' => $pollId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_CommunityPollCommentList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "communityPollVotes" collection of methods.
   * Typical usage is:
   *  <code>
   *   $orkutService = new Google_OrkutService(...);
   *   $communityPollVotes = $orkutService->communityPollVotes;
   *  </code>
   */
  class Google_CommunityPollVotesServiceResource extends Google_ServiceResource {

    /**
     * Votes on a community poll. (communityPollVotes.insert)
     *
     * @param int $communityId The ID of the community whose poll is being voted.
     * @param string $pollId The ID of the poll being voted.
     * @param Google_CommunityPollVote $postBody
     * @param array $optParams Optional parameters.
     * @return Google_CommunityPollVote
     */
    public function insert($communityId, $pollId, Google_CommunityPollVote $postBody, $optParams = array()) {
      $params = array('communityId' => $communityId, 'pollId' => $pollId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_CommunityPollVote($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "communityPolls" collection of methods.
   * Typical usage is:
   *  <code>
   *   $orkutService = new Google_OrkutService(...);
   *   $communityPolls = $orkutService->communityPolls;
   *  </code>
   */
  class Google_CommunityPollsServiceResource extends Google_ServiceResource {

    /**
     * Retrieves one specific poll of a community. (communityPolls.get)
     *
     * @param int $communityId The ID of the community for whose poll will be retrieved.
     * @param string $pollId The ID of the poll to get.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string hl Specifies the interface language (host language) of your user interface.
     * @return Google_CommunityPoll
     */
    public function get($communityId, $pollId, $optParams = array()) {
      $params = array('communityId' => $communityId, 'pollId' => $pollId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_CommunityPoll($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves the polls of a community. (communityPolls.list)
     *
     * @param int $communityId The ID of the community which polls will be listed.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string hl Specifies the interface language (host language) of your user interface.
     * @opt_param string maxResults The maximum number of polls to include in the response.
     * @opt_param string pageToken A continuation token that allows pagination.
     * @return Google_CommunityPollList
     */
    public function listCommunityPolls($communityId, $optParams = array()) {
      $params = array('communityId' => $communityId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_CommunityPollList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "communityRelated" collection of methods.
   * Typical usage is:
   *  <code>
   *   $orkutService = new Google_OrkutService(...);
   *   $communityRelated = $orkutService->communityRelated;
   *  </code>
   */
  class Google_CommunityRelatedServiceResource extends Google_ServiceResource {

    /**
     * Retrieves the communities related to another one. (communityRelated.list)
     *
     * @param int $communityId The ID of the community whose related communities will be listed.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string hl Specifies the interface language (host language) of your user interface.
     * @return Google_CommunityList
     */
    public function listCommunityRelated($communityId, $optParams = array()) {
      $params = array('communityId' => $communityId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_CommunityList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "communityTopics" collection of methods.
   * Typical usage is:
   *  <code>
   *   $orkutService = new Google_OrkutService(...);
   *   $communityTopics = $orkutService->communityTopics;
   *  </code>
   */
  class Google_CommunityTopicsServiceResource extends Google_ServiceResource {

    /**
     * Moves a topic of the community to the trash folder. (communityTopics.delete)
     *
     * @param int $communityId The ID of the community whose topic will be moved to the trash folder.
     * @param string $topicId The ID of the topic to be moved to the trash folder.
     * @param array $optParams Optional parameters.
     */
    public function delete($communityId, $topicId, $optParams = array()) {
      $params = array('communityId' => $communityId, 'topicId' => $topicId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
    /**
     * Retrieves a topic of a community. (communityTopics.get)
     *
     * @param int $communityId The ID of the community whose topic will be retrieved.
     * @param string $topicId The ID of the topic to get.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string hl Specifies the interface language (host language) of your user interface.
     * @return Google_CommunityTopic
     */
    public function get($communityId, $topicId, $optParams = array()) {
      $params = array('communityId' => $communityId, 'topicId' => $topicId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_CommunityTopic($data);
      } else {
        return $data;
      }
    }
    /**
     * Adds a topic to a given community. (communityTopics.insert)
     *
     * @param int $communityId The ID of the community the topic should be added to.
     * @param Google_CommunityTopic $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param bool isShout Whether this topic is a shout.
     * @return Google_CommunityTopic
     */
    public function insert($communityId, Google_CommunityTopic $postBody, $optParams = array()) {
      $params = array('communityId' => $communityId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_CommunityTopic($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves the topics of a community. (communityTopics.list)
     *
     * @param int $communityId The ID of the community which topics will be listed.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string hl Specifies the interface language (host language) of your user interface.
     * @opt_param string maxResults The maximum number of topics to include in the response.
     * @opt_param string pageToken A continuation token that allows pagination.
     * @return Google_CommunityTopicList
     */
    public function listCommunityTopics($communityId, $optParams = array()) {
      $params = array('communityId' => $communityId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_CommunityTopicList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "counters" collection of methods.
   * Typical usage is:
   *  <code>
   *   $orkutService = new Google_OrkutService(...);
   *   $counters = $orkutService->counters;
   *  </code>
   */
  class Google_CountersServiceResource extends Google_ServiceResource {

    /**
     * Retrieves the counters of a user. (counters.list)
     *
     * @param string $userId The ID of the user whose counters will be listed. Can be me to refer to caller.
     * @param array $optParams Optional parameters.
     * @return Google_Counters
     */
    public function listCounters($userId, $optParams = array()) {
      $params = array('userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_Counters($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "scraps" collection of methods.
   * Typical usage is:
   *  <code>
   *   $orkutService = new Google_OrkutService(...);
   *   $scraps = $orkutService->scraps;
   *  </code>
   */
  class Google_ScrapsServiceResource extends Google_ServiceResource {

    /**
     * Creates a new scrap. (scraps.insert)
     *
     * @param Google_Activity $postBody
     * @param array $optParams Optional parameters.
     * @return Google_Activity
     */
    public function insert(Google_Activity $postBody, $optParams = array()) {
      $params = array('postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_Activity($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Google_Orkut (v2).
 *
 * <p>
 * Lets you manage activities, comments and badges in Orkut. More stuff coming in time.
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="http://code.google.com/apis/orkut/v2/reference.html" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_OrkutService extends Google_Service {
  public $acl;
  public $activities;
  public $activityVisibility;
  public $badges;
  public $comments;
  public $communities;
  public $communityFollow;
  public $communityMembers;
  public $communityMessages;
  public $communityPollComments;
  public $communityPollVotes;
  public $communityPolls;
  public $communityRelated;
  public $communityTopics;
  public $counters;
  public $scraps;
  /**
   * Constructs the internal representation of the Orkut service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client) {
    $this->servicePath = 'orkut/v2/';
    $this->version = 'v2';
    $this->serviceName = 'orkut';

    $client->addService($this->serviceName, $this->version);
    $this->acl = new Google_AclServiceResource($this, $this->serviceName, 'acl', json_decode('{"methods": {"delete": {"id": "orkut.acl.delete", "path": "activities/{activityId}/acl/{userId}", "httpMethod": "DELETE", "parameters": {"activityId": {"type": "string", "required": true, "location": "path"}, "userId": {"type": "string", "required": true, "location": "path"}}, "scopes": ["https://www.googleapis.com/auth/orkut"]}}}', true));
    $this->activities = new Google_ActivitiesServiceResource($this, $this->serviceName, 'activities', json_decode('{"methods": {"delete": {"id": "orkut.activities.delete", "path": "activities/{activityId}", "httpMethod": "DELETE", "parameters": {"activityId": {"type": "string", "required": true, "location": "path"}}, "scopes": ["https://www.googleapis.com/auth/orkut"]}, "list": {"id": "orkut.activities.list", "path": "people/{userId}/activities/{collection}", "httpMethod": "GET", "parameters": {"collection": {"type": "string", "required": true, "enum": ["all", "scraps", "stream"], "location": "path"}, "hl": {"type": "string", "location": "query"}, "maxResults": {"type": "integer", "format": "uint32", "minimum": "1", "maximum": "100", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "userId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "ActivityList"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}}}', true));
    $this->activityVisibility = new Google_ActivityVisibilityServiceResource($this, $this->serviceName, 'activityVisibility', json_decode('{"methods": {"get": {"id": "orkut.activityVisibility.get", "path": "activities/{activityId}/visibility", "httpMethod": "GET", "parameters": {"activityId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "Visibility"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}, "patch": {"id": "orkut.activityVisibility.patch", "path": "activities/{activityId}/visibility", "httpMethod": "PATCH", "parameters": {"activityId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "Visibility"}, "response": {"$ref": "Visibility"}, "scopes": ["https://www.googleapis.com/auth/orkut"]}, "update": {"id": "orkut.activityVisibility.update", "path": "activities/{activityId}/visibility", "httpMethod": "PUT", "parameters": {"activityId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "Visibility"}, "response": {"$ref": "Visibility"}, "scopes": ["https://www.googleapis.com/auth/orkut"]}}}', true));
    $this->badges = new Google_BadgesServiceResource($this, $this->serviceName, 'badges', json_decode('{"methods": {"get": {"id": "orkut.badges.get", "path": "people/{userId}/badges/{badgeId}", "httpMethod": "GET", "parameters": {"badgeId": {"type": "string", "required": true, "format": "int64", "location": "path"}, "userId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "Badge"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}, "list": {"id": "orkut.badges.list", "path": "people/{userId}/badges", "httpMethod": "GET", "parameters": {"userId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "BadgeList"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}}}', true));
    $this->comments = new Google_CommentsServiceResource($this, $this->serviceName, 'comments', json_decode('{"methods": {"delete": {"id": "orkut.comments.delete", "path": "comments/{commentId}", "httpMethod": "DELETE", "parameters": {"commentId": {"type": "string", "required": true, "location": "path"}}, "scopes": ["https://www.googleapis.com/auth/orkut"]}, "get": {"id": "orkut.comments.get", "path": "comments/{commentId}", "httpMethod": "GET", "parameters": {"commentId": {"type": "string", "required": true, "location": "path"}, "hl": {"type": "string", "location": "query"}}, "response": {"$ref": "Comment"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}, "insert": {"id": "orkut.comments.insert", "path": "activities/{activityId}/comments", "httpMethod": "POST", "parameters": {"activityId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "Comment"}, "response": {"$ref": "Comment"}, "scopes": ["https://www.googleapis.com/auth/orkut"]}, "list": {"id": "orkut.comments.list", "path": "activities/{activityId}/comments", "httpMethod": "GET", "parameters": {"activityId": {"type": "string", "required": true, "location": "path"}, "hl": {"type": "string", "location": "query"}, "maxResults": {"type": "integer", "format": "uint32", "minimum": "1", "location": "query"}, "orderBy": {"type": "string", "default": "DESCENDING_SORT", "enum": ["ascending", "descending"], "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "response": {"$ref": "CommentList"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}}}', true));
    $this->communities = new Google_CommunitiesServiceResource($this, $this->serviceName, 'communities', json_decode('{"methods": {"get": {"id": "orkut.communities.get", "path": "communities/{communityId}", "httpMethod": "GET", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "hl": {"type": "string", "location": "query"}}, "response": {"$ref": "Community"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}, "list": {"id": "orkut.communities.list", "path": "people/{userId}/communities", "httpMethod": "GET", "parameters": {"hl": {"type": "string", "location": "query"}, "maxResults": {"type": "integer", "format": "uint32", "minimum": "1", "location": "query"}, "orderBy": {"type": "string", "enum": ["id", "ranked"], "location": "query"}, "userId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "CommunityList"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}}}', true));
    $this->communityFollow = new Google_CommunityFollowServiceResource($this, $this->serviceName, 'communityFollow', json_decode('{"methods": {"delete": {"id": "orkut.communityFollow.delete", "path": "communities/{communityId}/followers/{userId}", "httpMethod": "DELETE", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "userId": {"type": "string", "required": true, "location": "path"}}, "scopes": ["https://www.googleapis.com/auth/orkut"]}, "insert": {"id": "orkut.communityFollow.insert", "path": "communities/{communityId}/followers/{userId}", "httpMethod": "POST", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "userId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "CommunityMembers"}, "scopes": ["https://www.googleapis.com/auth/orkut"]}}}', true));
    $this->communityMembers = new Google_CommunityMembersServiceResource($this, $this->serviceName, 'communityMembers', json_decode('{"methods": {"delete": {"id": "orkut.communityMembers.delete", "path": "communities/{communityId}/members/{userId}", "httpMethod": "DELETE", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "userId": {"type": "string", "required": true, "location": "path"}}, "scopes": ["https://www.googleapis.com/auth/orkut"]}, "get": {"id": "orkut.communityMembers.get", "path": "communities/{communityId}/members/{userId}", "httpMethod": "GET", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "hl": {"type": "string", "location": "query"}, "userId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "CommunityMembers"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}, "insert": {"id": "orkut.communityMembers.insert", "path": "communities/{communityId}/members/{userId}", "httpMethod": "POST", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "userId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "CommunityMembers"}, "scopes": ["https://www.googleapis.com/auth/orkut"]}, "list": {"id": "orkut.communityMembers.list", "path": "communities/{communityId}/members", "httpMethod": "GET", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "friendsOnly": {"type": "boolean", "location": "query"}, "hl": {"type": "string", "location": "query"}, "maxResults": {"type": "integer", "format": "uint32", "minimum": "1", "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "response": {"$ref": "CommunityMembersList"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}}}', true));
    $this->communityMessages = new Google_CommunityMessagesServiceResource($this, $this->serviceName, 'communityMessages', json_decode('{"methods": {"delete": {"id": "orkut.communityMessages.delete", "path": "communities/{communityId}/topics/{topicId}/messages/{messageId}", "httpMethod": "DELETE", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "messageId": {"type": "string", "required": true, "format": "int64", "location": "path"}, "topicId": {"type": "string", "required": true, "format": "int64", "location": "path"}}, "scopes": ["https://www.googleapis.com/auth/orkut"]}, "insert": {"id": "orkut.communityMessages.insert", "path": "communities/{communityId}/topics/{topicId}/messages", "httpMethod": "POST", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "topicId": {"type": "string", "required": true, "format": "int64", "location": "path"}}, "request": {"$ref": "CommunityMessage"}, "response": {"$ref": "CommunityMessage"}, "scopes": ["https://www.googleapis.com/auth/orkut"]}, "list": {"id": "orkut.communityMessages.list", "path": "communities/{communityId}/topics/{topicId}/messages", "httpMethod": "GET", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "hl": {"type": "string", "location": "query"}, "maxResults": {"type": "integer", "format": "uint32", "minimum": "1", "maximum": "100", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "topicId": {"type": "string", "required": true, "format": "int64", "location": "path"}}, "response": {"$ref": "CommunityMessageList"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}}}', true));
    $this->communityPollComments = new Google_CommunityPollCommentsServiceResource($this, $this->serviceName, 'communityPollComments', json_decode('{"methods": {"insert": {"id": "orkut.communityPollComments.insert", "path": "communities/{communityId}/polls/{pollId}/comments", "httpMethod": "POST", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "pollId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "CommunityPollComment"}, "response": {"$ref": "CommunityPollComment"}, "scopes": ["https://www.googleapis.com/auth/orkut"]}, "list": {"id": "orkut.communityPollComments.list", "path": "communities/{communityId}/polls/{pollId}/comments", "httpMethod": "GET", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "hl": {"type": "string", "location": "query"}, "maxResults": {"type": "integer", "format": "uint32", "minimum": "1", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "pollId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "CommunityPollCommentList"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}}}', true));
    $this->communityPollVotes = new Google_CommunityPollVotesServiceResource($this, $this->serviceName, 'communityPollVotes', json_decode('{"methods": {"insert": {"id": "orkut.communityPollVotes.insert", "path": "communities/{communityId}/polls/{pollId}/votes", "httpMethod": "POST", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "pollId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "CommunityPollVote"}, "response": {"$ref": "CommunityPollVote"}, "scopes": ["https://www.googleapis.com/auth/orkut"]}}}', true));
    $this->communityPolls = new Google_CommunityPollsServiceResource($this, $this->serviceName, 'communityPolls', json_decode('{"methods": {"get": {"id": "orkut.communityPolls.get", "path": "communities/{communityId}/polls/{pollId}", "httpMethod": "GET", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "hl": {"type": "string", "location": "query"}, "pollId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "CommunityPoll"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}, "list": {"id": "orkut.communityPolls.list", "path": "communities/{communityId}/polls", "httpMethod": "GET", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "hl": {"type": "string", "location": "query"}, "maxResults": {"type": "integer", "format": "uint32", "minimum": "1", "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "response": {"$ref": "CommunityPollList"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}}}', true));
    $this->communityRelated = new Google_CommunityRelatedServiceResource($this, $this->serviceName, 'communityRelated', json_decode('{"methods": {"list": {"id": "orkut.communityRelated.list", "path": "communities/{communityId}/related", "httpMethod": "GET", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "hl": {"type": "string", "location": "query"}}, "response": {"$ref": "CommunityList"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}}}', true));
    $this->communityTopics = new Google_CommunityTopicsServiceResource($this, $this->serviceName, 'communityTopics', json_decode('{"methods": {"delete": {"id": "orkut.communityTopics.delete", "path": "communities/{communityId}/topics/{topicId}", "httpMethod": "DELETE", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "topicId": {"type": "string", "required": true, "format": "int64", "location": "path"}}, "scopes": ["https://www.googleapis.com/auth/orkut"]}, "get": {"id": "orkut.communityTopics.get", "path": "communities/{communityId}/topics/{topicId}", "httpMethod": "GET", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "hl": {"type": "string", "location": "query"}, "topicId": {"type": "string", "required": true, "format": "int64", "location": "path"}}, "response": {"$ref": "CommunityTopic"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}, "insert": {"id": "orkut.communityTopics.insert", "path": "communities/{communityId}/topics", "httpMethod": "POST", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "isShout": {"type": "boolean", "location": "query"}}, "request": {"$ref": "CommunityTopic"}, "response": {"$ref": "CommunityTopic"}, "scopes": ["https://www.googleapis.com/auth/orkut"]}, "list": {"id": "orkut.communityTopics.list", "path": "communities/{communityId}/topics", "httpMethod": "GET", "parameters": {"communityId": {"type": "integer", "required": true, "format": "int32", "location": "path"}, "hl": {"type": "string", "location": "query"}, "maxResults": {"type": "integer", "format": "uint32", "minimum": "1", "maximum": "100", "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "response": {"$ref": "CommunityTopicList"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}}}', true));
    $this->counters = new Google_CountersServiceResource($this, $this->serviceName, 'counters', json_decode('{"methods": {"list": {"id": "orkut.counters.list", "path": "people/{userId}/counters", "httpMethod": "GET", "parameters": {"userId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "Counters"}, "scopes": ["https://www.googleapis.com/auth/orkut", "https://www.googleapis.com/auth/orkut.readonly"]}}}', true));
    $this->scraps = new Google_ScrapsServiceResource($this, $this->serviceName, 'scraps', json_decode('{"methods": {"insert": {"id": "orkut.scraps.insert", "path": "activities/scraps", "httpMethod": "POST", "request": {"$ref": "Activity"}, "response": {"$ref": "Activity"}, "scopes": ["https://www.googleapis.com/auth/orkut"]}}}', true));

  }
}



class Google_Acl extends Google_Model {
  public $description;
  protected $__itemsType = 'Google_AclItems';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $totalParticipants;
  public function setDescription( $description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
  public function setItems(/* array(Google_AclItems) */ $items) {
    $this->assertIsArray($items, 'Google_AclItems', __METHOD__);
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
  public function setTotalParticipants( $totalParticipants) {
    $this->totalParticipants = $totalParticipants;
  }
  public function getTotalParticipants() {
    return $this->totalParticipants;
  }
}

class Google_AclItems extends Google_Model {
  public $id;
  public $type;
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

class Google_Activity extends Google_Model {
  protected $__accessType = 'Google_Acl';
  protected $__accessDataType = '';
  public $access;
  protected $__actorType = 'Google_OrkutAuthorResource';
  protected $__actorDataType = '';
  public $actor;
  public $id;
  public $kind;
  protected $__linksType = 'Google_OrkutLinkResource';
  protected $__linksDataType = 'array';
  public $links;
  protected $__objectType = 'Google_ActivityObject';
  protected $__objectDataType = '';
  public $object;
  public $published;
  public $title;
  public $updated;
  public $verb;
  public function setAccess(Google_Acl $access) {
    $this->access = $access;
  }
  public function getAccess() {
    return $this->access;
  }
  public function setActor(Google_OrkutAuthorResource $actor) {
    $this->actor = $actor;
  }
  public function getActor() {
    return $this->actor;
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
  public function setLinks(/* array(Google_OrkutLinkResource) */ $links) {
    $this->assertIsArray($links, 'Google_OrkutLinkResource', __METHOD__);
    $this->links = $links;
  }
  public function getLinks() {
    return $this->links;
  }
  public function setObject(Google_ActivityObject $object) {
    $this->object = $object;
  }
  public function getObject() {
    return $this->object;
  }
  public function setPublished( $published) {
    $this->published = $published;
  }
  public function getPublished() {
    return $this->published;
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
  public function setVerb( $verb) {
    $this->verb = $verb;
  }
  public function getVerb() {
    return $this->verb;
  }
}

class Google_ActivityList extends Google_Model {
  protected $__itemsType = 'Google_Activity';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
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
  public function setNextPageToken( $nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
}

class Google_ActivityObject extends Google_Model {
  public $content;
  protected $__itemsType = 'Google_OrkutActivityobjectsResource';
  protected $__itemsDataType = 'array';
  public $items;
  public $objectType;
  protected $__repliesType = 'Google_ActivityObjectReplies';
  protected $__repliesDataType = '';
  public $replies;
  public function setContent( $content) {
    $this->content = $content;
  }
  public function getContent() {
    return $this->content;
  }
  public function setItems(/* array(Google_OrkutActivityobjectsResource) */ $items) {
    $this->assertIsArray($items, 'Google_OrkutActivityobjectsResource', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
  }
  public function setObjectType( $objectType) {
    $this->objectType = $objectType;
  }
  public function getObjectType() {
    return $this->objectType;
  }
  public function setReplies(Google_ActivityObjectReplies $replies) {
    $this->replies = $replies;
  }
  public function getReplies() {
    return $this->replies;
  }
}

class Google_ActivityObjectReplies extends Google_Model {
  protected $__itemsType = 'Google_Comment';
  protected $__itemsDataType = 'array';
  public $items;
  public $totalItems;
  public $url;
  public function setItems(/* array(Google_Comment) */ $items) {
    $this->assertIsArray($items, 'Google_Comment', __METHOD__);
    $this->items = $items;
  }
  public function getItems() {
    return $this->items;
  }
  public function setTotalItems( $totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_Badge extends Google_Model {
  public $badgeLargeLogo;
  public $badgeSmallLogo;
  public $caption;
  public $description;
  public $id;
  public $kind;
  public $sponsorLogo;
  public $sponsorName;
  public $sponsorUrl;
  public function setBadgeLargeLogo( $badgeLargeLogo) {
    $this->badgeLargeLogo = $badgeLargeLogo;
  }
  public function getBadgeLargeLogo() {
    return $this->badgeLargeLogo;
  }
  public function setBadgeSmallLogo( $badgeSmallLogo) {
    $this->badgeSmallLogo = $badgeSmallLogo;
  }
  public function getBadgeSmallLogo() {
    return $this->badgeSmallLogo;
  }
  public function setCaption( $caption) {
    $this->caption = $caption;
  }
  public function getCaption() {
    return $this->caption;
  }
  public function setDescription( $description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
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
  public function setSponsorLogo( $sponsorLogo) {
    $this->sponsorLogo = $sponsorLogo;
  }
  public function getSponsorLogo() {
    return $this->sponsorLogo;
  }
  public function setSponsorName( $sponsorName) {
    $this->sponsorName = $sponsorName;
  }
  public function getSponsorName() {
    return $this->sponsorName;
  }
  public function setSponsorUrl( $sponsorUrl) {
    $this->sponsorUrl = $sponsorUrl;
  }
  public function getSponsorUrl() {
    return $this->sponsorUrl;
  }
}

class Google_BadgeList extends Google_Model {
  protected $__itemsType = 'Google_Badge';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(Google_Badge) */ $items) {
    $this->assertIsArray($items, 'Google_Badge', __METHOD__);
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

class Google_Comment extends Google_Model {
  protected $__actorType = 'Google_OrkutAuthorResource';
  protected $__actorDataType = '';
  public $actor;
  public $content;
  public $id;
  protected $__inReplyToType = 'Google_CommentInReplyTo';
  protected $__inReplyToDataType = '';
  public $inReplyTo;
  public $kind;
  protected $__linksType = 'Google_OrkutLinkResource';
  protected $__linksDataType = 'array';
  public $links;
  public $published;
  public function setActor(Google_OrkutAuthorResource $actor) {
    $this->actor = $actor;
  }
  public function getActor() {
    return $this->actor;
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
  public function setInReplyTo(Google_CommentInReplyTo $inReplyTo) {
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
  public function setLinks(/* array(Google_OrkutLinkResource) */ $links) {
    $this->assertIsArray($links, 'Google_OrkutLinkResource', __METHOD__);
    $this->links = $links;
  }
  public function getLinks() {
    return $this->links;
  }
  public function setPublished( $published) {
    $this->published = $published;
  }
  public function getPublished() {
    return $this->published;
  }
}

class Google_CommentInReplyTo extends Google_Model {
  public $href;
  public $ref;
  public $rel;
  public $type;
  public function setHref( $href) {
    $this->href = $href;
  }
  public function getHref() {
    return $this->href;
  }
  public function setRef( $ref) {
    $this->ref = $ref;
  }
  public function getRef() {
    return $this->ref;
  }
  public function setRel( $rel) {
    $this->rel = $rel;
  }
  public function getRel() {
    return $this->rel;
  }
  public function setType( $type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}

class Google_CommentList extends Google_Model {
  protected $__itemsType = 'Google_Comment';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public $previousPageToken;
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
  public function setNextPageToken( $nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setPreviousPageToken( $previousPageToken) {
    $this->previousPageToken = $previousPageToken;
  }
  public function getPreviousPageToken() {
    return $this->previousPageToken;
  }
}

class Google_Community extends Google_Model {
  public $category;
  protected $__co_ownersType = 'Google_OrkutAuthorResource';
  protected $__co_ownersDataType = 'array';
  public $co_owners;
  public $creation_date;
  public $description;
  public $id;
  public $kind;
  public $language;
  protected $__linksType = 'Google_OrkutLinkResource';
  protected $__linksDataType = 'array';
  public $links;
  public $location;
  public $member_count;
  protected $__moderatorsType = 'Google_OrkutAuthorResource';
  protected $__moderatorsDataType = 'array';
  public $moderators;
  public $name;
  protected $__ownerType = 'Google_OrkutAuthorResource';
  protected $__ownerDataType = '';
  public $owner;
  public $photo_url;
  public function setCategory( $category) {
    $this->category = $category;
  }
  public function getCategory() {
    return $this->category;
  }
  public function setCo_owners(/* array(Google_OrkutAuthorResource) */ $co_owners) {
    $this->assertIsArray($co_owners, 'Google_OrkutAuthorResource', __METHOD__);
    $this->co_owners = $co_owners;
  }
  public function getCo_owners() {
    return $this->co_owners;
  }
  public function setCreation_date( $creation_date) {
    $this->creation_date = $creation_date;
  }
  public function getCreation_date() {
    return $this->creation_date;
  }
  public function setDescription( $description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
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
  public function setLanguage( $language) {
    $this->language = $language;
  }
  public function getLanguage() {
    return $this->language;
  }
  public function setLinks(/* array(Google_OrkutLinkResource) */ $links) {
    $this->assertIsArray($links, 'Google_OrkutLinkResource', __METHOD__);
    $this->links = $links;
  }
  public function getLinks() {
    return $this->links;
  }
  public function setLocation( $location) {
    $this->location = $location;
  }
  public function getLocation() {
    return $this->location;
  }
  public function setMember_count( $member_count) {
    $this->member_count = $member_count;
  }
  public function getMember_count() {
    return $this->member_count;
  }
  public function setModerators(/* array(Google_OrkutAuthorResource) */ $moderators) {
    $this->assertIsArray($moderators, 'Google_OrkutAuthorResource', __METHOD__);
    $this->moderators = $moderators;
  }
  public function getModerators() {
    return $this->moderators;
  }
  public function setName( $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setOwner(Google_OrkutAuthorResource $owner) {
    $this->owner = $owner;
  }
  public function getOwner() {
    return $this->owner;
  }
  public function setPhoto_url( $photo_url) {
    $this->photo_url = $photo_url;
  }
  public function getPhoto_url() {
    return $this->photo_url;
  }
}

class Google_CommunityList extends Google_Model {
  protected $__itemsType = 'Google_Community';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(Google_Community) */ $items) {
    $this->assertIsArray($items, 'Google_Community', __METHOD__);
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

class Google_CommunityMembers extends Google_Model {
  protected $__communityMembershipStatusType = 'Google_CommunityMembershipStatus';
  protected $__communityMembershipStatusDataType = '';
  public $communityMembershipStatus;
  public $kind;
  protected $__personType = 'Google_OrkutActivitypersonResource';
  protected $__personDataType = '';
  public $person;
  public function setCommunityMembershipStatus(Google_CommunityMembershipStatus $communityMembershipStatus) {
    $this->communityMembershipStatus = $communityMembershipStatus;
  }
  public function getCommunityMembershipStatus() {
    return $this->communityMembershipStatus;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setPerson(Google_OrkutActivitypersonResource $person) {
    $this->person = $person;
  }
  public function getPerson() {
    return $this->person;
  }
}

class Google_CommunityMembersList extends Google_Model {
  public $firstPageToken;
  protected $__itemsType = 'Google_CommunityMembers';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $lastPageToken;
  public $nextPageToken;
  public $prevPageToken;
  public function setFirstPageToken( $firstPageToken) {
    $this->firstPageToken = $firstPageToken;
  }
  public function getFirstPageToken() {
    return $this->firstPageToken;
  }
  public function setItems(/* array(Google_CommunityMembers) */ $items) {
    $this->assertIsArray($items, 'Google_CommunityMembers', __METHOD__);
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
  public function setLastPageToken( $lastPageToken) {
    $this->lastPageToken = $lastPageToken;
  }
  public function getLastPageToken() {
    return $this->lastPageToken;
  }
  public function setNextPageToken( $nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setPrevPageToken( $prevPageToken) {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken() {
    return $this->prevPageToken;
  }
}

class Google_CommunityMembershipStatus extends Google_Model {
  public $canCreatePoll;
  public $canCreateTopic;
  public $canShout;
  public $isCoOwner;
  public $isFollowing;
  public $isModerator;
  public $isOwner;
  public $isRestoreAvailable;
  public $isTakebackAvailable;
  public $kind;
  public $status;
  public function setCanCreatePoll( $canCreatePoll) {
    $this->canCreatePoll = $canCreatePoll;
  }
  public function getCanCreatePoll() {
    return $this->canCreatePoll;
  }
  public function setCanCreateTopic( $canCreateTopic) {
    $this->canCreateTopic = $canCreateTopic;
  }
  public function getCanCreateTopic() {
    return $this->canCreateTopic;
  }
  public function setCanShout( $canShout) {
    $this->canShout = $canShout;
  }
  public function getCanShout() {
    return $this->canShout;
  }
  public function setIsCoOwner( $isCoOwner) {
    $this->isCoOwner = $isCoOwner;
  }
  public function getIsCoOwner() {
    return $this->isCoOwner;
  }
  public function setIsFollowing( $isFollowing) {
    $this->isFollowing = $isFollowing;
  }
  public function getIsFollowing() {
    return $this->isFollowing;
  }
  public function setIsModerator( $isModerator) {
    $this->isModerator = $isModerator;
  }
  public function getIsModerator() {
    return $this->isModerator;
  }
  public function setIsOwner( $isOwner) {
    $this->isOwner = $isOwner;
  }
  public function getIsOwner() {
    return $this->isOwner;
  }
  public function setIsRestoreAvailable( $isRestoreAvailable) {
    $this->isRestoreAvailable = $isRestoreAvailable;
  }
  public function getIsRestoreAvailable() {
    return $this->isRestoreAvailable;
  }
  public function setIsTakebackAvailable( $isTakebackAvailable) {
    $this->isTakebackAvailable = $isTakebackAvailable;
  }
  public function getIsTakebackAvailable() {
    return $this->isTakebackAvailable;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setStatus( $status) {
    $this->status = $status;
  }
  public function getStatus() {
    return $this->status;
  }
}

class Google_CommunityMessage extends Google_Model {
  public $addedDate;
  protected $__authorType = 'Google_OrkutAuthorResource';
  protected $__authorDataType = '';
  public $author;
  public $body;
  public $id;
  public $isSpam;
  public $kind;
  protected $__linksType = 'Google_OrkutLinkResource';
  protected $__linksDataType = 'array';
  public $links;
  public $subject;
  public function setAddedDate( $addedDate) {
    $this->addedDate = $addedDate;
  }
  public function getAddedDate() {
    return $this->addedDate;
  }
  public function setAuthor(Google_OrkutAuthorResource $author) {
    $this->author = $author;
  }
  public function getAuthor() {
    return $this->author;
  }
  public function setBody( $body) {
    $this->body = $body;
  }
  public function getBody() {
    return $this->body;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setIsSpam( $isSpam) {
    $this->isSpam = $isSpam;
  }
  public function getIsSpam() {
    return $this->isSpam;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setLinks(/* array(Google_OrkutLinkResource) */ $links) {
    $this->assertIsArray($links, 'Google_OrkutLinkResource', __METHOD__);
    $this->links = $links;
  }
  public function getLinks() {
    return $this->links;
  }
  public function setSubject( $subject) {
    $this->subject = $subject;
  }
  public function getSubject() {
    return $this->subject;
  }
}

class Google_CommunityMessageList extends Google_Model {
  public $firstPageToken;
  protected $__itemsType = 'Google_CommunityMessage';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $lastPageToken;
  public $nextPageToken;
  public $prevPageToken;
  public function setFirstPageToken( $firstPageToken) {
    $this->firstPageToken = $firstPageToken;
  }
  public function getFirstPageToken() {
    return $this->firstPageToken;
  }
  public function setItems(/* array(Google_CommunityMessage) */ $items) {
    $this->assertIsArray($items, 'Google_CommunityMessage', __METHOD__);
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
  public function setLastPageToken( $lastPageToken) {
    $this->lastPageToken = $lastPageToken;
  }
  public function getLastPageToken() {
    return $this->lastPageToken;
  }
  public function setNextPageToken( $nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setPrevPageToken( $prevPageToken) {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken() {
    return $this->prevPageToken;
  }
}

class Google_CommunityPoll extends Google_Model {
  protected $__authorType = 'Google_OrkutAuthorResource';
  protected $__authorDataType = '';
  public $author;
  public $communityId;
  public $creationTime;
  public $description;
  public $endingTime;
  public $hasVoted;
  public $id;
  protected $__imageType = 'Google_CommunityPollImage';
  protected $__imageDataType = '';
  public $image;
  public $isClosed;
  public $isMultipleAnswers;
  public $isOpenForVoting;
  public $isRestricted;
  public $isSpam;
  public $isUsersVotePublic;
  public $isVotingAllowedForNonMembers;
  public $kind;
  public $lastUpdate;
  protected $__linksType = 'Google_OrkutLinkResource';
  protected $__linksDataType = 'array';
  public $links;
  protected $__optionsType = 'Google_OrkutCommunitypolloptionResource';
  protected $__optionsDataType = 'array';
  public $options;
  public $question;
  public $totalNumberOfVotes;
  public $votedOptions;
  public function setAuthor(Google_OrkutAuthorResource $author) {
    $this->author = $author;
  }
  public function getAuthor() {
    return $this->author;
  }
  public function setCommunityId( $communityId) {
    $this->communityId = $communityId;
  }
  public function getCommunityId() {
    return $this->communityId;
  }
  public function setCreationTime( $creationTime) {
    $this->creationTime = $creationTime;
  }
  public function getCreationTime() {
    return $this->creationTime;
  }
  public function setDescription( $description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
  public function setEndingTime( $endingTime) {
    $this->endingTime = $endingTime;
  }
  public function getEndingTime() {
    return $this->endingTime;
  }
  public function setHasVoted( $hasVoted) {
    $this->hasVoted = $hasVoted;
  }
  public function getHasVoted() {
    return $this->hasVoted;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setImage(Google_CommunityPollImage $image) {
    $this->image = $image;
  }
  public function getImage() {
    return $this->image;
  }
  public function setIsClosed( $isClosed) {
    $this->isClosed = $isClosed;
  }
  public function getIsClosed() {
    return $this->isClosed;
  }
  public function setIsMultipleAnswers( $isMultipleAnswers) {
    $this->isMultipleAnswers = $isMultipleAnswers;
  }
  public function getIsMultipleAnswers() {
    return $this->isMultipleAnswers;
  }
  public function setIsOpenForVoting( $isOpenForVoting) {
    $this->isOpenForVoting = $isOpenForVoting;
  }
  public function getIsOpenForVoting() {
    return $this->isOpenForVoting;
  }
  public function setIsRestricted( $isRestricted) {
    $this->isRestricted = $isRestricted;
  }
  public function getIsRestricted() {
    return $this->isRestricted;
  }
  public function setIsSpam( $isSpam) {
    $this->isSpam = $isSpam;
  }
  public function getIsSpam() {
    return $this->isSpam;
  }
  public function setIsUsersVotePublic( $isUsersVotePublic) {
    $this->isUsersVotePublic = $isUsersVotePublic;
  }
  public function getIsUsersVotePublic() {
    return $this->isUsersVotePublic;
  }
  public function setIsVotingAllowedForNonMembers( $isVotingAllowedForNonMembers) {
    $this->isVotingAllowedForNonMembers = $isVotingAllowedForNonMembers;
  }
  public function getIsVotingAllowedForNonMembers() {
    return $this->isVotingAllowedForNonMembers;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setLastUpdate( $lastUpdate) {
    $this->lastUpdate = $lastUpdate;
  }
  public function getLastUpdate() {
    return $this->lastUpdate;
  }
  public function setLinks(/* array(Google_OrkutLinkResource) */ $links) {
    $this->assertIsArray($links, 'Google_OrkutLinkResource', __METHOD__);
    $this->links = $links;
  }
  public function getLinks() {
    return $this->links;
  }
  public function setOptions(/* array(Google_OrkutCommunitypolloptionResource) */ $options) {
    $this->assertIsArray($options, 'Google_OrkutCommunitypolloptionResource', __METHOD__);
    $this->options = $options;
  }
  public function getOptions() {
    return $this->options;
  }
  public function setQuestion( $question) {
    $this->question = $question;
  }
  public function getQuestion() {
    return $this->question;
  }
  public function setTotalNumberOfVotes( $totalNumberOfVotes) {
    $this->totalNumberOfVotes = $totalNumberOfVotes;
  }
  public function getTotalNumberOfVotes() {
    return $this->totalNumberOfVotes;
  }
  public function setVotedOptions(/* array(Google_int) */ $votedOptions) {
    $this->assertIsArray($votedOptions, 'Google_int', __METHOD__);
    $this->votedOptions = $votedOptions;
  }
  public function getVotedOptions() {
    return $this->votedOptions;
  }
}

class Google_CommunityPollComment extends Google_Model {
  public $addedDate;
  protected $__authorType = 'Google_OrkutAuthorResource';
  protected $__authorDataType = '';
  public $author;
  public $body;
  public $id;
  public $kind;
  public function setAddedDate( $addedDate) {
    $this->addedDate = $addedDate;
  }
  public function getAddedDate() {
    return $this->addedDate;
  }
  public function setAuthor(Google_OrkutAuthorResource $author) {
    $this->author = $author;
  }
  public function getAuthor() {
    return $this->author;
  }
  public function setBody( $body) {
    $this->body = $body;
  }
  public function getBody() {
    return $this->body;
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
}

class Google_CommunityPollCommentList extends Google_Model {
  public $firstPageToken;
  protected $__itemsType = 'Google_CommunityPollComment';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $lastPageToken;
  public $nextPageToken;
  public $prevPageToken;
  public function setFirstPageToken( $firstPageToken) {
    $this->firstPageToken = $firstPageToken;
  }
  public function getFirstPageToken() {
    return $this->firstPageToken;
  }
  public function setItems(/* array(Google_CommunityPollComment) */ $items) {
    $this->assertIsArray($items, 'Google_CommunityPollComment', __METHOD__);
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
  public function setLastPageToken( $lastPageToken) {
    $this->lastPageToken = $lastPageToken;
  }
  public function getLastPageToken() {
    return $this->lastPageToken;
  }
  public function setNextPageToken( $nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setPrevPageToken( $prevPageToken) {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken() {
    return $this->prevPageToken;
  }
}

class Google_CommunityPollImage extends Google_Model {
  public $url;
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_CommunityPollList extends Google_Model {
  public $firstPageToken;
  protected $__itemsType = 'Google_CommunityPoll';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $lastPageToken;
  public $nextPageToken;
  public $prevPageToken;
  public function setFirstPageToken( $firstPageToken) {
    $this->firstPageToken = $firstPageToken;
  }
  public function getFirstPageToken() {
    return $this->firstPageToken;
  }
  public function setItems(/* array(Google_CommunityPoll) */ $items) {
    $this->assertIsArray($items, 'Google_CommunityPoll', __METHOD__);
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
  public function setLastPageToken( $lastPageToken) {
    $this->lastPageToken = $lastPageToken;
  }
  public function getLastPageToken() {
    return $this->lastPageToken;
  }
  public function setNextPageToken( $nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setPrevPageToken( $prevPageToken) {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken() {
    return $this->prevPageToken;
  }
}

class Google_CommunityPollVote extends Google_Model {
  public $isVotevisible;
  public $kind;
  public $optionIds;
  public function setIsVotevisible( $isVotevisible) {
    $this->isVotevisible = $isVotevisible;
  }
  public function getIsVotevisible() {
    return $this->isVotevisible;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setOptionIds(/* array(Google_int) */ $optionIds) {
    $this->assertIsArray($optionIds, 'Google_int', __METHOD__);
    $this->optionIds = $optionIds;
  }
  public function getOptionIds() {
    return $this->optionIds;
  }
}

class Google_CommunityTopic extends Google_Model {
  protected $__authorType = 'Google_OrkutAuthorResource';
  protected $__authorDataType = '';
  public $author;
  public $body;
  public $id;
  public $isClosed;
  public $kind;
  public $lastUpdate;
  public $latestMessageSnippet;
  protected $__linksType = 'Google_OrkutLinkResource';
  protected $__linksDataType = 'array';
  public $links;
  protected $__messagesType = 'Google_CommunityMessage';
  protected $__messagesDataType = 'array';
  public $messages;
  public $numberOfReplies;
  public $title;
  public function setAuthor(Google_OrkutAuthorResource $author) {
    $this->author = $author;
  }
  public function getAuthor() {
    return $this->author;
  }
  public function setBody( $body) {
    $this->body = $body;
  }
  public function getBody() {
    return $this->body;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setIsClosed( $isClosed) {
    $this->isClosed = $isClosed;
  }
  public function getIsClosed() {
    return $this->isClosed;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setLastUpdate( $lastUpdate) {
    $this->lastUpdate = $lastUpdate;
  }
  public function getLastUpdate() {
    return $this->lastUpdate;
  }
  public function setLatestMessageSnippet( $latestMessageSnippet) {
    $this->latestMessageSnippet = $latestMessageSnippet;
  }
  public function getLatestMessageSnippet() {
    return $this->latestMessageSnippet;
  }
  public function setLinks(/* array(Google_OrkutLinkResource) */ $links) {
    $this->assertIsArray($links, 'Google_OrkutLinkResource', __METHOD__);
    $this->links = $links;
  }
  public function getLinks() {
    return $this->links;
  }
  public function setMessages(/* array(Google_CommunityMessage) */ $messages) {
    $this->assertIsArray($messages, 'Google_CommunityMessage', __METHOD__);
    $this->messages = $messages;
  }
  public function getMessages() {
    return $this->messages;
  }
  public function setNumberOfReplies( $numberOfReplies) {
    $this->numberOfReplies = $numberOfReplies;
  }
  public function getNumberOfReplies() {
    return $this->numberOfReplies;
  }
  public function setTitle( $title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
}

class Google_CommunityTopicList extends Google_Model {
  public $firstPageToken;
  protected $__itemsType = 'Google_CommunityTopic';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $lastPageToken;
  public $nextPageToken;
  public $prevPageToken;
  public function setFirstPageToken( $firstPageToken) {
    $this->firstPageToken = $firstPageToken;
  }
  public function getFirstPageToken() {
    return $this->firstPageToken;
  }
  public function setItems(/* array(Google_CommunityTopic) */ $items) {
    $this->assertIsArray($items, 'Google_CommunityTopic', __METHOD__);
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
  public function setLastPageToken( $lastPageToken) {
    $this->lastPageToken = $lastPageToken;
  }
  public function getLastPageToken() {
    return $this->lastPageToken;
  }
  public function setNextPageToken( $nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setPrevPageToken( $prevPageToken) {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken() {
    return $this->prevPageToken;
  }
}

class Google_Counters extends Google_Model {
  protected $__itemsType = 'Google_OrkutCounterResource';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(Google_OrkutCounterResource) */ $items) {
    $this->assertIsArray($items, 'Google_OrkutCounterResource', __METHOD__);
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

class Google_OrkutActivityobjectsResource extends Google_Model {
  protected $__communityType = 'Google_Community';
  protected $__communityDataType = '';
  public $community;
  public $content;
  public $displayName;
  public $id;
  protected $__linksType = 'Google_OrkutLinkResource';
  protected $__linksDataType = 'array';
  public $links;
  public $objectType;
  protected $__personType = 'Google_OrkutActivitypersonResource';
  protected $__personDataType = '';
  public $person;
  public function setCommunity(Google_Community $community) {
    $this->community = $community;
  }
  public function getCommunity() {
    return $this->community;
  }
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
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setLinks(/* array(Google_OrkutLinkResource) */ $links) {
    $this->assertIsArray($links, 'Google_OrkutLinkResource', __METHOD__);
    $this->links = $links;
  }
  public function getLinks() {
    return $this->links;
  }
  public function setObjectType( $objectType) {
    $this->objectType = $objectType;
  }
  public function getObjectType() {
    return $this->objectType;
  }
  public function setPerson(Google_OrkutActivitypersonResource $person) {
    $this->person = $person;
  }
  public function getPerson() {
    return $this->person;
  }
}

class Google_OrkutActivitypersonResource extends Google_Model {
  public $birthday;
  public $gender;
  public $id;
  protected $__imageType = 'Google_OrkutActivitypersonResourceImage';
  protected $__imageDataType = '';
  public $image;
  protected $__nameType = 'Google_OrkutActivitypersonResourceName';
  protected $__nameDataType = '';
  public $name;
  public $url;
  public function setBirthday( $birthday) {
    $this->birthday = $birthday;
  }
  public function getBirthday() {
    return $this->birthday;
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
  public function setImage(Google_OrkutActivitypersonResourceImage $image) {
    $this->image = $image;
  }
  public function getImage() {
    return $this->image;
  }
  public function setName(Google_OrkutActivitypersonResourceName $name) {
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

class Google_OrkutActivitypersonResourceImage extends Google_Model {
  public $url;
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_OrkutActivitypersonResourceName extends Google_Model {
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

class Google_OrkutAuthorResource extends Google_Model {
  public $displayName;
  public $id;
  protected $__imageType = 'Google_OrkutAuthorResourceImage';
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
  public function setImage(Google_OrkutAuthorResourceImage $image) {
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

class Google_OrkutAuthorResourceImage extends Google_Model {
  public $url;
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_OrkutCommunitypolloptionResource extends Google_Model {
  public $description;
  protected $__imageType = 'Google_OrkutCommunitypolloptionResourceImage';
  protected $__imageDataType = '';
  public $image;
  public $numberOfVotes;
  public $optionId;
  public function setDescription( $description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
  public function setImage(Google_OrkutCommunitypolloptionResourceImage $image) {
    $this->image = $image;
  }
  public function getImage() {
    return $this->image;
  }
  public function setNumberOfVotes( $numberOfVotes) {
    $this->numberOfVotes = $numberOfVotes;
  }
  public function getNumberOfVotes() {
    return $this->numberOfVotes;
  }
  public function setOptionId( $optionId) {
    $this->optionId = $optionId;
  }
  public function getOptionId() {
    return $this->optionId;
  }
}

class Google_OrkutCommunitypolloptionResourceImage extends Google_Model {
  public $url;
  public function setUrl( $url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class Google_OrkutCounterResource extends Google_Model {
  protected $__linkType = 'Google_OrkutLinkResource';
  protected $__linkDataType = '';
  public $link;
  public $name;
  public $total;
  public function setLink(Google_OrkutLinkResource $link) {
    $this->link = $link;
  }
  public function getLink() {
    return $this->link;
  }
  public function setName( $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setTotal( $total) {
    $this->total = $total;
  }
  public function getTotal() {
    return $this->total;
  }
}

class Google_OrkutLinkResource extends Google_Model {
  public $href;
  public $rel;
  public $title;
  public $type;
  public function setHref( $href) {
    $this->href = $href;
  }
  public function getHref() {
    return $this->href;
  }
  public function setRel( $rel) {
    $this->rel = $rel;
  }
  public function getRel() {
    return $this->rel;
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

class Google_Visibility extends Google_Model {
  public $kind;
  protected $__linksType = 'Google_OrkutLinkResource';
  protected $__linksDataType = 'array';
  public $links;
  public $visibility;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setLinks(/* array(Google_OrkutLinkResource) */ $links) {
    $this->assertIsArray($links, 'Google_OrkutLinkResource', __METHOD__);
    $this->links = $links;
  }
  public function getLinks() {
    return $this->links;
  }
  public function setVisibility( $visibility) {
    $this->visibility = $visibility;
  }
  public function getVisibility() {
    return $this->visibility;
  }
}
