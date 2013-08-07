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
   * The "achievementDefinitions" collection of methods.
   * Typical usage is:
   *  <code>
   *   $gamesService = new Google_GamesService(...);
   *   $achievementDefinitions = $gamesService->achievementDefinitions;
   *  </code>
   */
  class Google_AchievementDefinitionsServiceResource extends Google_ServiceResource {

    /**
     * Lists all the achievement definitions for your application. (achievementDefinitions.list)
     *
     * @param array $optParams Optional parameters.
     *
     * @opt_param string language The preferred language to use for strings returned by this method.
     * @opt_param int maxResults The maximum number of achievement resources to return in the response, used for paging. For any response, the actual number of achievement resources returned may be less than the specified maxResults.
     * @opt_param string pageToken The token returned by the previous request.
     * @return Google_AchievementDefinitionsListResponse
     */
    public function listAchievementDefinitions($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_AchievementDefinitionsListResponse($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "achievements" collection of methods.
   * Typical usage is:
   *  <code>
   *   $gamesService = new Google_GamesService(...);
   *   $achievements = $gamesService->achievements;
   *  </code>
   */
  class Google_AchievementsServiceResource extends Google_ServiceResource {

    /**
     * Increments the steps of the achievement with the given ID for the currently authenticated player.
     * (achievements.increment)
     *
     * @param string $achievementId The ID of the achievement used by this method.
     * @param int $stepsToIncrement The number of steps to increment.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string requestId A randomly generated numeric ID for each request specified by the caller. This number is used at the server to ensure that the increment is performed correctly across retries.
     * @return Google_AchievementIncrementResponse
     */
    public function increment($achievementId, $stepsToIncrement, $optParams = array()) {
      $params = array('achievementId' => $achievementId, 'stepsToIncrement' => $stepsToIncrement);
      $params = array_merge($params, $optParams);
      $data = $this->__call('increment', array($params));
      if ($this->useObjects()) {
        return new Google_AchievementIncrementResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Lists the progress for all your application's achievements for the currently authenticated
     * player. (achievements.list)
     *
     * @param string $playerId A player ID. A value of me may be used in place of the authenticated player's ID.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string language The preferred language to use for strings returned by this method.
     * @opt_param int maxResults The maximum number of achievement resources to return in the response, used for paging. For any response, the actual number of achievement resources returned may be less than the specified maxResults.
     * @opt_param string pageToken The token returned by the previous request.
     * @opt_param string state Tells the server to return only achievements with the specified state. If this parameter isn't specified, all achievements are returned.
     * @return Google_PlayerAchievementListResponse
     */
    public function listAchievements($playerId, $optParams = array()) {
      $params = array('playerId' => $playerId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_PlayerAchievementListResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Sets the state of the achievement with the given ID to REVEALED for the currently authenticated
     * player. (achievements.reveal)
     *
     * @param string $achievementId The ID of the achievement used by this method.
     * @param array $optParams Optional parameters.
     * @return Google_AchievementRevealResponse
     */
    public function reveal($achievementId, $optParams = array()) {
      $params = array('achievementId' => $achievementId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('reveal', array($params));
      if ($this->useObjects()) {
        return new Google_AchievementRevealResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Unlocks this achievement for the currently authenticated player. (achievements.unlock)
     *
     * @param string $achievementId The ID of the achievement used by this method.
     * @param array $optParams Optional parameters.
     * @return Google_AchievementUnlockResponse
     */
    public function unlock($achievementId, $optParams = array()) {
      $params = array('achievementId' => $achievementId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('unlock', array($params));
      if ($this->useObjects()) {
        return new Google_AchievementUnlockResponse($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "applications" collection of methods.
   * Typical usage is:
   *  <code>
   *   $gamesService = new Google_GamesService(...);
   *   $applications = $gamesService->applications;
   *  </code>
   */
  class Google_ApplicationsServiceResource extends Google_ServiceResource {

    /**
     * Retrieves the metadata of the application with the given ID. If the requested application is not
     * available for the specified platformType, the returned response will not include any instance
     * data. (applications.get)
     *
     * @param string $applicationId The application being requested.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string language The preferred language to use for strings returned by this method.
     * @opt_param string platformType Restrict application details returned to the specific platform.
     * @return Google_Application
     */
    public function get($applicationId, $optParams = array()) {
      $params = array('applicationId' => $applicationId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Application($data);
      } else {
        return $data;
      }
    }
    /**
     * Indicate that the the currently authenticated user is playing your application.
     * (applications.played)
     *
     * @param array $optParams Optional parameters.
     */
    public function played($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('played', array($params));
      return $data;
    }
  }

  /**
   * The "leaderboards" collection of methods.
   * Typical usage is:
   *  <code>
   *   $gamesService = new Google_GamesService(...);
   *   $leaderboards = $gamesService->leaderboards;
   *  </code>
   */
  class Google_LeaderboardsServiceResource extends Google_ServiceResource {

    /**
     * Retrieves the metadata of the leaderboard with the given ID. (leaderboards.get)
     *
     * @param string $leaderboardId The ID of the leaderboard.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string language The preferred language to use for strings returned by this method.
     * @return Google_Leaderboard
     */
    public function get($leaderboardId, $optParams = array()) {
      $params = array('leaderboardId' => $leaderboardId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Leaderboard($data);
      } else {
        return $data;
      }
    }
    /**
     * Lists all the leaderboard metadata for your application. (leaderboards.list)
     *
     * @param array $optParams Optional parameters.
     *
     * @opt_param string language The preferred language to use for strings returned by this method.
     * @opt_param int maxResults The maximum number of leaderboards to return in the response. For any response, the actual number of leaderboards returned may be less than the specified maxResults.
     * @opt_param string pageToken The token returned by the previous request.
     * @return Google_LeaderboardListResponse
     */
    public function listLeaderboards($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_LeaderboardListResponse($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "players" collection of methods.
   * Typical usage is:
   *  <code>
   *   $gamesService = new Google_GamesService(...);
   *   $players = $gamesService->players;
   *  </code>
   */
  class Google_PlayersServiceResource extends Google_ServiceResource {

    /**
     * Retrieves the Player resource with the given ID. To retrieve the player for the currently
     * authenticated user, set playerId to me. (players.get)
     *
     * @param string $playerId A player ID. A value of me may be used in place of the authenticated player's ID.
     * @param array $optParams Optional parameters.
     * @return Google_Player
     */
    public function get($playerId, $optParams = array()) {
      $params = array('playerId' => $playerId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Player($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "revisions" collection of methods.
   * Typical usage is:
   *  <code>
   *   $gamesService = new Google_GamesService(...);
   *   $revisions = $gamesService->revisions;
   *  </code>
   */
  class Google_RevisionsServiceResource extends Google_ServiceResource {

    /**
     * Checks whether the games client is out of date. (revisions.check)
     *
     * @param string $clientRevision The revision of the client SDK used by your application.
     * @param array $optParams Optional parameters.
     * @return Google_RevisionCheckResponse
     */
    public function check($clientRevision, $optParams = array()) {
      $params = array('clientRevision' => $clientRevision);
      $params = array_merge($params, $optParams);
      $data = $this->__call('check', array($params));
      if ($this->useObjects()) {
        return new Google_RevisionCheckResponse($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "rooms" collection of methods.
   * Typical usage is:
   *  <code>
   *   $gamesService = new Google_GamesService(...);
   *   $rooms = $gamesService->rooms;
   *  </code>
   */
  class Google_RoomsServiceResource extends Google_ServiceResource {

    /**
     * Create a room. For internal use by the Games SDK only. Calling this method directly is
     * unsupported. (rooms.create)
     *
     * @param Google_RoomCreateRequest $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string language The preferred language to use for strings returned by this method.
     * @return Google_Room
     */
    public function create(Google_RoomCreateRequest $postBody, $optParams = array()) {
      $params = array('postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('create', array($params));
      if ($this->useObjects()) {
        return new Google_Room($data);
      } else {
        return $data;
      }
    }
    /**
     * Decline an invitation to join a room. For internal use by the Games SDK only. Calling this method
     * directly is unsupported. (rooms.decline)
     *
     * @param string $roomId The ID of the room.
     * @param array $optParams Optional parameters.
     * @return Google_Room
     */
    public function decline($roomId, $optParams = array()) {
      $params = array('roomId' => $roomId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('decline', array($params));
      if ($this->useObjects()) {
        return new Google_Room($data);
      } else {
        return $data;
      }
    }
    /**
     * Dismiss an invitation to join a room. For internal use by the Games SDK only. Calling this method
     * directly is unsupported. (rooms.dismiss)
     *
     * @param string $roomId The ID of the room.
     * @param array $optParams Optional parameters.
     */
    public function dismiss($roomId, $optParams = array()) {
      $params = array('roomId' => $roomId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('dismiss', array($params));
      return $data;
    }
    /**
     * Get the data for a room. (rooms.get)
     *
     * @param string $roomId The ID of the room.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string language Specify the preferred language to use to format room info.
     * @return Google_Room
     */
    public function get($roomId, $optParams = array()) {
      $params = array('roomId' => $roomId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Room($data);
      } else {
        return $data;
      }
    }
    /**
     * Join a room. For internal use by the Games SDK only. Calling this method directly is unsupported.
     * (rooms.join)
     *
     * @param string $roomId The ID of the room.
     * @param Google_RoomJoinRequest $postBody
     * @param array $optParams Optional parameters.
     * @return Google_Room
     */
    public function join($roomId, Google_RoomJoinRequest $postBody, $optParams = array()) {
      $params = array('roomId' => $roomId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('join', array($params));
      if ($this->useObjects()) {
        return new Google_Room($data);
      } else {
        return $data;
      }
    }
    /**
     * Leave a room. For internal use by the Games SDK only. Calling this method directly is
     * unsupported. (rooms.leave)
     *
     * @param string $roomId The ID of the room.
     * @param Google_RoomLeaveRequest $postBody
     * @param array $optParams Optional parameters.
     * @return Google_Room
     */
    public function leave($roomId, Google_RoomLeaveRequest $postBody, $optParams = array()) {
      $params = array('roomId' => $roomId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('leave', array($params));
      if ($this->useObjects()) {
        return new Google_Room($data);
      } else {
        return $data;
      }
    }
    /**
     * Returns invitations to join rooms. (rooms.list)
     *
     * @param array $optParams Optional parameters.
     *
     * @opt_param string language The preferred language to use for strings returned by this method.
     * @opt_param int maxResults The maximum number of rooms to return in the response, used for paging. For any response, the actual number of rooms to return may be less than the specified maxResults.
     * @opt_param string pageToken The token returned by the previous request.
     * @return Google_RoomList
     */
    public function listRooms($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_RoomList($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates sent by a client reporting the status of peers in a room. For internal use by the Games
     * SDK only. Calling this method directly is unsupported. (rooms.reportStatus)
     *
     * @param string $roomId The ID of the room.
     * @param Google_RoomP2PStatuses $postBody
     * @param array $optParams Optional parameters.
     * @return Google_RoomStatus
     */
    public function reportStatus($roomId, Google_RoomP2PStatuses $postBody, $optParams = array()) {
      $params = array('roomId' => $roomId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('reportStatus', array($params));
      if ($this->useObjects()) {
        return new Google_RoomStatus($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "scores" collection of methods.
   * Typical usage is:
   *  <code>
   *   $gamesService = new Google_GamesService(...);
   *   $scores = $gamesService->scores;
   *  </code>
   */
  class Google_ScoresServiceResource extends Google_ServiceResource {

    /**
     * Get high scores and optionally, ranks in leaderboards for the currently authenticated player. For
     * a specific time span, leaderboardId can be set to ALL to retrieve data for all leaderboards in a
     * given time span. (scores.get)
     *
     * @param string $playerId A player ID. A value of me may be used in place of the authenticated player's ID.
     * @param string $leaderboardId The ID of the leaderboard.
     * @param string $timeSpan The time span for the scores and ranks you're requesting.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string includeRankType The types of ranks to return. If the parameter is omitted, no ranks will be returned.
     * @opt_param string language The preferred language to use for strings returned by this method.
     * @opt_param int maxResults The maximum number of leaderboard scores to return in the response. For any response, the actual number of leaderboard scores returned may be less than the specified maxResults.
     * @opt_param string pageToken The token returned by the previous request.
     * @return Google_PlayerLeaderboardScoreListResponse
     */
    public function get($playerId, $leaderboardId, $timeSpan, $optParams = array()) {
      $params = array('playerId' => $playerId, 'leaderboardId' => $leaderboardId, 'timeSpan' => $timeSpan);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_PlayerLeaderboardScoreListResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Lists the scores in a leaderboard, starting from the top. (scores.list)
     *
     * @param string $leaderboardId The ID of the leaderboard.
     * @param string $collection The collection of scores you're requesting.
     * @param string $timeSpan The time span for the scores and ranks you're requesting.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string language The preferred language to use for strings returned by this method.
     * @opt_param int maxResults The maximum number of leaderboard scores to return in the response. For any response, the actual number of leaderboard scores returned may be less than the specified maxResults.
     * @opt_param string pageToken The token returned by the previous request.
     * @return Google_LeaderboardScores
     */
    public function listScores($leaderboardId, $collection, $timeSpan, $optParams = array()) {
      $params = array('leaderboardId' => $leaderboardId, 'collection' => $collection, 'timeSpan' => $timeSpan);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_LeaderboardScores($data);
      } else {
        return $data;
      }
    }
    /**
     * Lists the scores in a leaderboard around (and including) a player's score. (scores.listWindow)
     *
     * @param string $leaderboardId The ID of the leaderboard.
     * @param string $collection The collection of scores you're requesting.
     * @param string $timeSpan The time span for the scores and ranks you're requesting.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string language The preferred language to use for strings returned by this method.
     * @opt_param int maxResults The maximum number of leaderboard scores to return in the response. For any response, the actual number of leaderboard scores returned may be less than the specified maxResults.
     * @opt_param string pageToken The token returned by the previous request.
     * @opt_param int resultsAbove The preferred number of scores to return above the player's score. More scores may be returned if the player is at the bottom of the leaderboard; fewer may be returned if the player is at the top. Must be less than or equal to maxResults.
     * @opt_param bool returnTopIfAbsent True if the top scores should be returned when the player is not in the leaderboard. Defaults to true.
     * @return Google_LeaderboardScores
     */
    public function listWindow($leaderboardId, $collection, $timeSpan, $optParams = array()) {
      $params = array('leaderboardId' => $leaderboardId, 'collection' => $collection, 'timeSpan' => $timeSpan);
      $params = array_merge($params, $optParams);
      $data = $this->__call('listWindow', array($params));
      if ($this->useObjects()) {
        return new Google_LeaderboardScores($data);
      } else {
        return $data;
      }
    }
    /**
     * Submits a score to the specified leaderboard. (scores.submit)
     *
     * @param string $leaderboardId The ID of the leaderboard.
     * @param string $score The score you're submitting. The submitted score is ignored if it is worse than a previously submitted score, where worse depends on the leaderboard sort order. The meaning of the score value depends on the leaderboard format type. For fixed-point, the score represents the raw value. For time, the score represents elapsed time in milliseconds. For currency, the score represents a value in micro units.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string language The preferred language to use for strings returned by this method.
     * @return Google_PlayerScoreResponse
     */
    public function submit($leaderboardId, $score, $optParams = array()) {
      $params = array('leaderboardId' => $leaderboardId, 'score' => $score);
      $params = array_merge($params, $optParams);
      $data = $this->__call('submit', array($params));
      if ($this->useObjects()) {
        return new Google_PlayerScoreResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Submits multiple scores to leaderboards. (scores.submitMultiple)
     *
     * @param Google_PlayerScoreSubmissionList $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string language The preferred language to use for strings returned by this method.
     * @return Google_PlayerScoreListResponse
     */
    public function submitMultiple(Google_PlayerScoreSubmissionList $postBody, $optParams = array()) {
      $params = array('postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('submitMultiple', array($params));
      if ($this->useObjects()) {
        return new Google_PlayerScoreListResponse($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Google_Games (v1).
 *
 * <p>
 * The API for Google Play Game Services.
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://developers.google.com/games/services/" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_GamesService extends Google_Service {
  public $achievementDefinitions;
  public $achievements;
  public $applications;
  public $leaderboards;
  public $players;
  public $revisions;
  public $rooms;
  public $scores;
  /**
   * Constructs the internal representation of the Games service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client) {
    $this->servicePath = 'games/v1/';
    $this->version = 'v1';
    $this->serviceName = 'games';

    $client->addService($this->serviceName, $this->version);
    $this->achievementDefinitions = new Google_AchievementDefinitionsServiceResource($this, $this->serviceName, 'achievementDefinitions', json_decode('{"methods": {"list": {"id": "games.achievementDefinitions.list", "path": "achievements", "httpMethod": "GET", "parameters": {"language": {"type": "string", "location": "query"}, "maxResults": {"type": "integer", "format": "int32", "minimum": "1", "maximum": "200", "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "response": {"$ref": "AchievementDefinitionsListResponse"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}}}', true));
    $this->achievements = new Google_AchievementsServiceResource($this, $this->serviceName, 'achievements', json_decode('{"methods": {"increment": {"id": "games.achievements.increment", "path": "achievements/{achievementId}/increment", "httpMethod": "POST", "parameters": {"achievementId": {"type": "string", "required": true, "location": "path"}, "requestId": {"type": "string", "format": "int64", "location": "query"}, "stepsToIncrement": {"type": "integer", "required": true, "format": "int32", "minimum": "1", "location": "query"}}, "response": {"$ref": "AchievementIncrementResponse"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "list": {"id": "games.achievements.list", "path": "players/{playerId}/achievements", "httpMethod": "GET", "parameters": {"language": {"type": "string", "location": "query"}, "maxResults": {"type": "integer", "format": "int32", "minimum": "1", "maximum": "200", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "playerId": {"type": "string", "required": true, "location": "path"}, "state": {"type": "string", "enum": ["ALL", "HIDDEN", "REVEALED", "UNLOCKED"], "location": "query"}}, "response": {"$ref": "PlayerAchievementListResponse"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "reveal": {"id": "games.achievements.reveal", "path": "achievements/{achievementId}/reveal", "httpMethod": "POST", "parameters": {"achievementId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "AchievementRevealResponse"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "unlock": {"id": "games.achievements.unlock", "path": "achievements/{achievementId}/unlock", "httpMethod": "POST", "parameters": {"achievementId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "AchievementUnlockResponse"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}}}', true));
    $this->applications = new Google_ApplicationsServiceResource($this, $this->serviceName, 'applications', json_decode('{"methods": {"get": {"id": "games.applications.get", "path": "applications/{applicationId}", "httpMethod": "GET", "parameters": {"applicationId": {"type": "string", "required": true, "location": "path"}, "language": {"type": "string", "location": "query"}, "platformType": {"type": "string", "enum": ["ANDROID", "IOS", "WEB_APP"], "location": "query"}}, "response": {"$ref": "Application"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "played": {"id": "games.applications.played", "path": "applications/played", "httpMethod": "POST", "scopes": ["https://www.googleapis.com/auth/plus.login"]}}}', true));
    $this->leaderboards = new Google_LeaderboardsServiceResource($this, $this->serviceName, 'leaderboards', json_decode('{"methods": {"get": {"id": "games.leaderboards.get", "path": "leaderboards/{leaderboardId}", "httpMethod": "GET", "parameters": {"language": {"type": "string", "location": "query"}, "leaderboardId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "Leaderboard"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "list": {"id": "games.leaderboards.list", "path": "leaderboards", "httpMethod": "GET", "parameters": {"language": {"type": "string", "location": "query"}, "maxResults": {"type": "integer", "format": "int32", "minimum": "1", "maximum": "100", "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "response": {"$ref": "LeaderboardListResponse"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}}}', true));
    $this->players = new Google_PlayersServiceResource($this, $this->serviceName, 'players', json_decode('{"methods": {"get": {"id": "games.players.get", "path": "players/{playerId}", "httpMethod": "GET", "parameters": {"playerId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "Player"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}}}', true));
    $this->revisions = new Google_RevisionsServiceResource($this, $this->serviceName, 'revisions', json_decode('{"methods": {"check": {"id": "games.revisions.check", "path": "revisions/check", "httpMethod": "GET", "parameters": {"clientRevision": {"type": "string", "required": true, "location": "query"}}, "response": {"$ref": "RevisionCheckResponse"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}}}', true));
    $this->rooms = new Google_RoomsServiceResource($this, $this->serviceName, 'rooms', json_decode('{"methods": {"create": {"id": "games.rooms.create", "path": "rooms/create", "httpMethod": "POST", "parameters": {"language": {"type": "string", "location": "query"}}, "request": {"$ref": "RoomCreateRequest"}, "response": {"$ref": "Room"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "decline": {"id": "games.rooms.decline", "path": "rooms/{roomId}/decline", "httpMethod": "POST", "parameters": {"roomId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "Room"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "dismiss": {"id": "games.rooms.dismiss", "path": "rooms/{roomId}/dismiss", "httpMethod": "POST", "parameters": {"roomId": {"type": "string", "required": true, "location": "path"}}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "get": {"id": "games.rooms.get", "path": "rooms/{roomId}", "httpMethod": "GET", "parameters": {"language": {"type": "string", "location": "query"}, "roomId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "Room"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "join": {"id": "games.rooms.join", "path": "rooms/{roomId}/join", "httpMethod": "POST", "parameters": {"roomId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "RoomJoinRequest"}, "response": {"$ref": "Room"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "leave": {"id": "games.rooms.leave", "path": "rooms/{roomId}/leave", "httpMethod": "POST", "parameters": {"roomId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "RoomLeaveRequest"}, "response": {"$ref": "Room"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "list": {"id": "games.rooms.list", "path": "rooms", "httpMethod": "GET", "parameters": {"language": {"type": "string", "location": "query"}, "maxResults": {"type": "integer", "format": "int32", "minimum": "1", "maximum": "500", "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "response": {"$ref": "RoomList"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "reportStatus": {"id": "games.rooms.reportStatus", "path": "rooms/{roomId}/reportstatus", "httpMethod": "POST", "parameters": {"roomId": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "RoomP2PStatuses"}, "response": {"$ref": "RoomStatus"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}}}', true));
    $this->scores = new Google_ScoresServiceResource($this, $this->serviceName, 'scores', json_decode('{"methods": {"get": {"id": "games.scores.get", "path": "players/{playerId}/leaderboards/{leaderboardId}/scores/{timeSpan}", "httpMethod": "GET", "parameters": {"includeRankType": {"type": "string", "enum": ["ALL", "PUBLIC", "SOCIAL"], "location": "query"}, "language": {"type": "string", "location": "query"}, "leaderboardId": {"type": "string", "required": true, "location": "path"}, "maxResults": {"type": "integer", "format": "int32", "minimum": "1", "maximum": "25", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "playerId": {"type": "string", "required": true, "location": "path"}, "timeSpan": {"type": "string", "required": true, "enum": ["ALL", "ALL_TIME", "DAILY", "WEEKLY"], "location": "path"}}, "response": {"$ref": "PlayerLeaderboardScoreListResponse"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "list": {"id": "games.scores.list", "path": "leaderboards/{leaderboardId}/scores/{collection}", "httpMethod": "GET", "parameters": {"collection": {"type": "string", "required": true, "enum": ["PUBLIC", "SOCIAL"], "location": "path"}, "language": {"type": "string", "location": "query"}, "leaderboardId": {"type": "string", "required": true, "location": "path"}, "maxResults": {"type": "integer", "format": "int32", "minimum": "1", "maximum": "25", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "timeSpan": {"type": "string", "required": true, "enum": ["ALL_TIME", "DAILY", "WEEKLY"], "location": "query"}}, "response": {"$ref": "LeaderboardScores"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "listWindow": {"id": "games.scores.listWindow", "path": "leaderboards/{leaderboardId}/window/{collection}", "httpMethod": "GET", "parameters": {"collection": {"type": "string", "required": true, "enum": ["PUBLIC", "SOCIAL"], "location": "path"}, "language": {"type": "string", "location": "query"}, "leaderboardId": {"type": "string", "required": true, "location": "path"}, "maxResults": {"type": "integer", "format": "int32", "minimum": "1", "maximum": "25", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "resultsAbove": {"type": "integer", "format": "int32", "location": "query"}, "returnTopIfAbsent": {"type": "boolean", "location": "query"}, "timeSpan": {"type": "string", "required": true, "enum": ["ALL_TIME", "DAILY", "WEEKLY"], "location": "query"}}, "response": {"$ref": "LeaderboardScores"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "submit": {"id": "games.scores.submit", "path": "leaderboards/{leaderboardId}/scores", "httpMethod": "POST", "parameters": {"language": {"type": "string", "location": "query"}, "leaderboardId": {"type": "string", "required": true, "location": "path"}, "score": {"type": "string", "required": true, "format": "int64", "location": "query"}}, "response": {"$ref": "PlayerScoreResponse"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "submitMultiple": {"id": "games.scores.submitMultiple", "path": "leaderboards/scores", "httpMethod": "POST", "parameters": {"language": {"type": "string", "location": "query"}}, "request": {"$ref": "PlayerScoreSubmissionList"}, "response": {"$ref": "PlayerScoreListResponse"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}}}', true));

  }
}



class Google_AchievementDefinition extends Google_Model {
  public $achievementType;
  public $description;
  public $formattedTotalSteps;
  public $id;
  public $initialState;
  public $isRevealedIconUrlDefault;
  public $isUnlockedIconUrlDefault;
  public $kind;
  public $name;
  public $revealedIconUrl;
  public $totalSteps;
  public $unlockedIconUrl;
  public function setAchievementType( $achievementType) {
    $this->achievementType = $achievementType;
  }
  public function getAchievementType() {
    return $this->achievementType;
  }
  public function setDescription( $description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
  public function setFormattedTotalSteps( $formattedTotalSteps) {
    $this->formattedTotalSteps = $formattedTotalSteps;
  }
  public function getFormattedTotalSteps() {
    return $this->formattedTotalSteps;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setInitialState( $initialState) {
    $this->initialState = $initialState;
  }
  public function getInitialState() {
    return $this->initialState;
  }
  public function setIsRevealedIconUrlDefault( $isRevealedIconUrlDefault) {
    $this->isRevealedIconUrlDefault = $isRevealedIconUrlDefault;
  }
  public function getIsRevealedIconUrlDefault() {
    return $this->isRevealedIconUrlDefault;
  }
  public function setIsUnlockedIconUrlDefault( $isUnlockedIconUrlDefault) {
    $this->isUnlockedIconUrlDefault = $isUnlockedIconUrlDefault;
  }
  public function getIsUnlockedIconUrlDefault() {
    return $this->isUnlockedIconUrlDefault;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setName( $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setRevealedIconUrl( $revealedIconUrl) {
    $this->revealedIconUrl = $revealedIconUrl;
  }
  public function getRevealedIconUrl() {
    return $this->revealedIconUrl;
  }
  public function setTotalSteps( $totalSteps) {
    $this->totalSteps = $totalSteps;
  }
  public function getTotalSteps() {
    return $this->totalSteps;
  }
  public function setUnlockedIconUrl( $unlockedIconUrl) {
    $this->unlockedIconUrl = $unlockedIconUrl;
  }
  public function getUnlockedIconUrl() {
    return $this->unlockedIconUrl;
  }
}

class Google_AchievementDefinitionsListResponse extends Google_Model {
  protected $__itemsType = 'Google_AchievementDefinition';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public function setItems(/* array(Google_AchievementDefinition) */ $items) {
    $this->assertIsArray($items, 'Google_AchievementDefinition', __METHOD__);
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

class Google_AchievementIncrementResponse extends Google_Model {
  public $currentSteps;
  public $kind;
  public $newlyUnlocked;
  public function setCurrentSteps( $currentSteps) {
    $this->currentSteps = $currentSteps;
  }
  public function getCurrentSteps() {
    return $this->currentSteps;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setNewlyUnlocked( $newlyUnlocked) {
    $this->newlyUnlocked = $newlyUnlocked;
  }
  public function getNewlyUnlocked() {
    return $this->newlyUnlocked;
  }
}

class Google_AchievementRevealResponse extends Google_Model {
  public $currentState;
  public $kind;
  public function setCurrentState( $currentState) {
    $this->currentState = $currentState;
  }
  public function getCurrentState() {
    return $this->currentState;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
}

class Google_AchievementUnlockResponse extends Google_Model {
  public $kind;
  public $newlyUnlocked;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setNewlyUnlocked( $newlyUnlocked) {
    $this->newlyUnlocked = $newlyUnlocked;
  }
  public function getNewlyUnlocked() {
    return $this->newlyUnlocked;
  }
}

class Google_AggregateStats extends Google_Model {
  public $count;
  public $kind;
  public $max;
  public $min;
  public $sum;
  public function setCount( $count) {
    $this->count = $count;
  }
  public function getCount() {
    return $this->count;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setMax( $max) {
    $this->max = $max;
  }
  public function getMax() {
    return $this->max;
  }
  public function setMin( $min) {
    $this->min = $min;
  }
  public function getMin() {
    return $this->min;
  }
  public function setSum( $sum) {
    $this->sum = $sum;
  }
  public function getSum() {
    return $this->sum;
  }
}

class Google_AnonymousPlayer extends Google_Model {
  public $avatarImageUrl;
  public $displayName;
  public $kind;
  public function setAvatarImageUrl( $avatarImageUrl) {
    $this->avatarImageUrl = $avatarImageUrl;
  }
  public function getAvatarImageUrl() {
    return $this->avatarImageUrl;
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
}

class Google_Application extends Google_Model {
  public $achievement_count;
  protected $__assetsType = 'Google_ImageAsset';
  protected $__assetsDataType = 'array';
  public $assets;
  public $author;
  protected $__categoryType = 'Google_ApplicationCategory';
  protected $__categoryDataType = '';
  public $category;
  public $description;
  public $id;
  protected $__instancesType = 'Google_Instance';
  protected $__instancesDataType = 'array';
  public $instances;
  public $kind;
  public $lastUpdatedTimestamp;
  public $leaderboard_count;
  public $name;
  public function setAchievement_count( $achievement_count) {
    $this->achievement_count = $achievement_count;
  }
  public function getAchievement_count() {
    return $this->achievement_count;
  }
  public function setAssets(/* array(Google_ImageAsset) */ $assets) {
    $this->assertIsArray($assets, 'Google_ImageAsset', __METHOD__);
    $this->assets = $assets;
  }
  public function getAssets() {
    return $this->assets;
  }
  public function setAuthor( $author) {
    $this->author = $author;
  }
  public function getAuthor() {
    return $this->author;
  }
  public function setCategory(Google_ApplicationCategory $category) {
    $this->category = $category;
  }
  public function getCategory() {
    return $this->category;
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
  public function setInstances(/* array(Google_Instance) */ $instances) {
    $this->assertIsArray($instances, 'Google_Instance', __METHOD__);
    $this->instances = $instances;
  }
  public function getInstances() {
    return $this->instances;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setLastUpdatedTimestamp( $lastUpdatedTimestamp) {
    $this->lastUpdatedTimestamp = $lastUpdatedTimestamp;
  }
  public function getLastUpdatedTimestamp() {
    return $this->lastUpdatedTimestamp;
  }
  public function setLeaderboard_count( $leaderboard_count) {
    $this->leaderboard_count = $leaderboard_count;
  }
  public function getLeaderboard_count() {
    return $this->leaderboard_count;
  }
  public function setName( $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
}

class Google_ApplicationCategory extends Google_Model {
  public $kind;
  public $primary;
  public $secondary;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setPrimary( $primary) {
    $this->primary = $primary;
  }
  public function getPrimary() {
    return $this->primary;
  }
  public function setSecondary( $secondary) {
    $this->secondary = $secondary;
  }
  public function getSecondary() {
    return $this->secondary;
  }
}

class Google_ImageAsset extends Google_Model {
  public $height;
  public $kind;
  public $name;
  public $url;
  public $width;
  public function setHeight( $height) {
    $this->height = $height;
  }
  public function getHeight() {
    return $this->height;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setName( $name) {
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
  public function setWidth( $width) {
    $this->width = $width;
  }
  public function getWidth() {
    return $this->width;
  }
}

class Google_Instance extends Google_Model {
  public $acquisitionUri;
  protected $__androidInstanceType = 'Google_InstanceAndroidDetails';
  protected $__androidInstanceDataType = '';
  public $androidInstance;
  protected $__iosInstanceType = 'Google_InstanceIosDetails';
  protected $__iosInstanceDataType = '';
  public $iosInstance;
  public $kind;
  public $name;
  public $platformType;
  public $realtimePlay;
  public $turnBasedPlay;
  protected $__webInstanceType = 'Google_InstanceWebDetails';
  protected $__webInstanceDataType = '';
  public $webInstance;
  public function setAcquisitionUri( $acquisitionUri) {
    $this->acquisitionUri = $acquisitionUri;
  }
  public function getAcquisitionUri() {
    return $this->acquisitionUri;
  }
  public function setAndroidInstance(Google_InstanceAndroidDetails $androidInstance) {
    $this->androidInstance = $androidInstance;
  }
  public function getAndroidInstance() {
    return $this->androidInstance;
  }
  public function setIosInstance(Google_InstanceIosDetails $iosInstance) {
    $this->iosInstance = $iosInstance;
  }
  public function getIosInstance() {
    return $this->iosInstance;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setName( $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setPlatformType( $platformType) {
    $this->platformType = $platformType;
  }
  public function getPlatformType() {
    return $this->platformType;
  }
  public function setRealtimePlay( $realtimePlay) {
    $this->realtimePlay = $realtimePlay;
  }
  public function getRealtimePlay() {
    return $this->realtimePlay;
  }
  public function setTurnBasedPlay( $turnBasedPlay) {
    $this->turnBasedPlay = $turnBasedPlay;
  }
  public function getTurnBasedPlay() {
    return $this->turnBasedPlay;
  }
  public function setWebInstance(Google_InstanceWebDetails $webInstance) {
    $this->webInstance = $webInstance;
  }
  public function getWebInstance() {
    return $this->webInstance;
  }
}

class Google_InstanceAndroidDetails extends Google_Model {
  public $enablePiracyCheck;
  public $kind;
  public $packageName;
  public $preferred;
  public function setEnablePiracyCheck( $enablePiracyCheck) {
    $this->enablePiracyCheck = $enablePiracyCheck;
  }
  public function getEnablePiracyCheck() {
    return $this->enablePiracyCheck;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setPackageName( $packageName) {
    $this->packageName = $packageName;
  }
  public function getPackageName() {
    return $this->packageName;
  }
  public function setPreferred( $preferred) {
    $this->preferred = $preferred;
  }
  public function getPreferred() {
    return $this->preferred;
  }
}

class Google_InstanceIosDetails extends Google_Model {
  public $bundleIdentifier;
  public $itunesAppId;
  public $kind;
  public $preferredForIpad;
  public $preferredForIphone;
  public $supportIpad;
  public $supportIphone;
  public function setBundleIdentifier( $bundleIdentifier) {
    $this->bundleIdentifier = $bundleIdentifier;
  }
  public function getBundleIdentifier() {
    return $this->bundleIdentifier;
  }
  public function setItunesAppId( $itunesAppId) {
    $this->itunesAppId = $itunesAppId;
  }
  public function getItunesAppId() {
    return $this->itunesAppId;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setPreferredForIpad( $preferredForIpad) {
    $this->preferredForIpad = $preferredForIpad;
  }
  public function getPreferredForIpad() {
    return $this->preferredForIpad;
  }
  public function setPreferredForIphone( $preferredForIphone) {
    $this->preferredForIphone = $preferredForIphone;
  }
  public function getPreferredForIphone() {
    return $this->preferredForIphone;
  }
  public function setSupportIpad( $supportIpad) {
    $this->supportIpad = $supportIpad;
  }
  public function getSupportIpad() {
    return $this->supportIpad;
  }
  public function setSupportIphone( $supportIphone) {
    $this->supportIphone = $supportIphone;
  }
  public function getSupportIphone() {
    return $this->supportIphone;
  }
}

class Google_InstanceWebDetails extends Google_Model {
  public $kind;
  public $launchUrl;
  public $preferred;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setLaunchUrl( $launchUrl) {
    $this->launchUrl = $launchUrl;
  }
  public function getLaunchUrl() {
    return $this->launchUrl;
  }
  public function setPreferred( $preferred) {
    $this->preferred = $preferred;
  }
  public function getPreferred() {
    return $this->preferred;
  }
}

class Google_Leaderboard extends Google_Model {
  public $iconUrl;
  public $id;
  public $isIconUrlDefault;
  public $kind;
  public $name;
  public $order;
  public function setIconUrl( $iconUrl) {
    $this->iconUrl = $iconUrl;
  }
  public function getIconUrl() {
    return $this->iconUrl;
  }
  public function setId( $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setIsIconUrlDefault( $isIconUrlDefault) {
    $this->isIconUrlDefault = $isIconUrlDefault;
  }
  public function getIsIconUrlDefault() {
    return $this->isIconUrlDefault;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setName( $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setOrder( $order) {
    $this->order = $order;
  }
  public function getOrder() {
    return $this->order;
  }
}

class Google_LeaderboardEntry extends Google_Model {
  public $formattedScore;
  public $formattedScoreRank;
  public $kind;
  protected $__playerType = 'Google_Player';
  protected $__playerDataType = '';
  public $player;
  public $scoreRank;
  public $scoreValue;
  public $timeSpan;
  public $writeTimestampMillis;
  public function setFormattedScore( $formattedScore) {
    $this->formattedScore = $formattedScore;
  }
  public function getFormattedScore() {
    return $this->formattedScore;
  }
  public function setFormattedScoreRank( $formattedScoreRank) {
    $this->formattedScoreRank = $formattedScoreRank;
  }
  public function getFormattedScoreRank() {
    return $this->formattedScoreRank;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setPlayer(Google_Player $player) {
    $this->player = $player;
  }
  public function getPlayer() {
    return $this->player;
  }
  public function setScoreRank( $scoreRank) {
    $this->scoreRank = $scoreRank;
  }
  public function getScoreRank() {
    return $this->scoreRank;
  }
  public function setScoreValue( $scoreValue) {
    $this->scoreValue = $scoreValue;
  }
  public function getScoreValue() {
    return $this->scoreValue;
  }
  public function setTimeSpan( $timeSpan) {
    $this->timeSpan = $timeSpan;
  }
  public function getTimeSpan() {
    return $this->timeSpan;
  }
  public function setWriteTimestampMillis( $writeTimestampMillis) {
    $this->writeTimestampMillis = $writeTimestampMillis;
  }
  public function getWriteTimestampMillis() {
    return $this->writeTimestampMillis;
  }
}

class Google_LeaderboardListResponse extends Google_Model {
  protected $__itemsType = 'Google_Leaderboard';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public function setItems(/* array(Google_Leaderboard) */ $items) {
    $this->assertIsArray($items, 'Google_Leaderboard', __METHOD__);
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

class Google_LeaderboardScoreRank extends Google_Model {
  public $formattedNumScores;
  public $formattedRank;
  public $kind;
  public $numScores;
  public $rank;
  public function setFormattedNumScores( $formattedNumScores) {
    $this->formattedNumScores = $formattedNumScores;
  }
  public function getFormattedNumScores() {
    return $this->formattedNumScores;
  }
  public function setFormattedRank( $formattedRank) {
    $this->formattedRank = $formattedRank;
  }
  public function getFormattedRank() {
    return $this->formattedRank;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setNumScores( $numScores) {
    $this->numScores = $numScores;
  }
  public function getNumScores() {
    return $this->numScores;
  }
  public function setRank( $rank) {
    $this->rank = $rank;
  }
  public function getRank() {
    return $this->rank;
  }
}

class Google_LeaderboardScores extends Google_Model {
  protected $__itemsType = 'Google_LeaderboardEntry';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public $numScores;
  protected $__playerScoreType = 'Google_LeaderboardEntry';
  protected $__playerScoreDataType = '';
  public $playerScore;
  public $prevPageToken;
  public function setItems(/* array(Google_LeaderboardEntry) */ $items) {
    $this->assertIsArray($items, 'Google_LeaderboardEntry', __METHOD__);
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
  public function setNumScores( $numScores) {
    $this->numScores = $numScores;
  }
  public function getNumScores() {
    return $this->numScores;
  }
  public function setPlayerScore(Google_LeaderboardEntry $playerScore) {
    $this->playerScore = $playerScore;
  }
  public function getPlayerScore() {
    return $this->playerScore;
  }
  public function setPrevPageToken( $prevPageToken) {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken() {
    return $this->prevPageToken;
  }
}

class Google_NetworkDiagnostics extends Google_Model {
  public $androidNetworkSubtype;
  public $androidNetworkType;
  public $kind;
  public $registrationLatencyMillis;
  public function setAndroidNetworkSubtype( $androidNetworkSubtype) {
    $this->androidNetworkSubtype = $androidNetworkSubtype;
  }
  public function getAndroidNetworkSubtype() {
    return $this->androidNetworkSubtype;
  }
  public function setAndroidNetworkType( $androidNetworkType) {
    $this->androidNetworkType = $androidNetworkType;
  }
  public function getAndroidNetworkType() {
    return $this->androidNetworkType;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setRegistrationLatencyMillis( $registrationLatencyMillis) {
    $this->registrationLatencyMillis = $registrationLatencyMillis;
  }
  public function getRegistrationLatencyMillis() {
    return $this->registrationLatencyMillis;
  }
}

class Google_PeerChannelDiagnostics extends Google_Model {
  protected $__bytesReceivedType = 'Google_AggregateStats';
  protected $__bytesReceivedDataType = '';
  public $bytesReceived;
  protected $__bytesSentType = 'Google_AggregateStats';
  protected $__bytesSentDataType = '';
  public $bytesSent;
  public $kind;
  public $numMessagesLost;
  public $numMessagesReceived;
  public $numMessagesSent;
  public $numSendFailures;
  protected $__roundtripLatencyMillisType = 'Google_AggregateStats';
  protected $__roundtripLatencyMillisDataType = '';
  public $roundtripLatencyMillis;
  public function setBytesReceived(Google_AggregateStats $bytesReceived) {
    $this->bytesReceived = $bytesReceived;
  }
  public function getBytesReceived() {
    return $this->bytesReceived;
  }
  public function setBytesSent(Google_AggregateStats $bytesSent) {
    $this->bytesSent = $bytesSent;
  }
  public function getBytesSent() {
    return $this->bytesSent;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setNumMessagesLost( $numMessagesLost) {
    $this->numMessagesLost = $numMessagesLost;
  }
  public function getNumMessagesLost() {
    return $this->numMessagesLost;
  }
  public function setNumMessagesReceived( $numMessagesReceived) {
    $this->numMessagesReceived = $numMessagesReceived;
  }
  public function getNumMessagesReceived() {
    return $this->numMessagesReceived;
  }
  public function setNumMessagesSent( $numMessagesSent) {
    $this->numMessagesSent = $numMessagesSent;
  }
  public function getNumMessagesSent() {
    return $this->numMessagesSent;
  }
  public function setNumSendFailures( $numSendFailures) {
    $this->numSendFailures = $numSendFailures;
  }
  public function getNumSendFailures() {
    return $this->numSendFailures;
  }
  public function setRoundtripLatencyMillis(Google_AggregateStats $roundtripLatencyMillis) {
    $this->roundtripLatencyMillis = $roundtripLatencyMillis;
  }
  public function getRoundtripLatencyMillis() {
    return $this->roundtripLatencyMillis;
  }
}

class Google_PeerSessionDiagnostics extends Google_Model {
  public $connectedTimestampMillis;
  public $kind;
  public $participantId;
  protected $__reliableChannelType = 'Google_PeerChannelDiagnostics';
  protected $__reliableChannelDataType = '';
  public $reliableChannel;
  protected $__unreliableChannelType = 'Google_PeerChannelDiagnostics';
  protected $__unreliableChannelDataType = '';
  public $unreliableChannel;
  public function setConnectedTimestampMillis( $connectedTimestampMillis) {
    $this->connectedTimestampMillis = $connectedTimestampMillis;
  }
  public function getConnectedTimestampMillis() {
    return $this->connectedTimestampMillis;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setParticipantId( $participantId) {
    $this->participantId = $participantId;
  }
  public function getParticipantId() {
    return $this->participantId;
  }
  public function setReliableChannel(Google_PeerChannelDiagnostics $reliableChannel) {
    $this->reliableChannel = $reliableChannel;
  }
  public function getReliableChannel() {
    return $this->reliableChannel;
  }
  public function setUnreliableChannel(Google_PeerChannelDiagnostics $unreliableChannel) {
    $this->unreliableChannel = $unreliableChannel;
  }
  public function getUnreliableChannel() {
    return $this->unreliableChannel;
  }
}

class Google_Player extends Google_Model {
  public $avatarImageUrl;
  public $displayName;
  public $kind;
  public $playerId;
  public function setAvatarImageUrl( $avatarImageUrl) {
    $this->avatarImageUrl = $avatarImageUrl;
  }
  public function getAvatarImageUrl() {
    return $this->avatarImageUrl;
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
  public function setPlayerId( $playerId) {
    $this->playerId = $playerId;
  }
  public function getPlayerId() {
    return $this->playerId;
  }
}

class Google_PlayerAchievement extends Google_Model {
  public $achievementState;
  public $currentSteps;
  public $formattedCurrentStepsString;
  public $id;
  public $kind;
  public $lastUpdatedTimestamp;
  public function setAchievementState( $achievementState) {
    $this->achievementState = $achievementState;
  }
  public function getAchievementState() {
    return $this->achievementState;
  }
  public function setCurrentSteps( $currentSteps) {
    $this->currentSteps = $currentSteps;
  }
  public function getCurrentSteps() {
    return $this->currentSteps;
  }
  public function setFormattedCurrentStepsString( $formattedCurrentStepsString) {
    $this->formattedCurrentStepsString = $formattedCurrentStepsString;
  }
  public function getFormattedCurrentStepsString() {
    return $this->formattedCurrentStepsString;
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
  public function setLastUpdatedTimestamp( $lastUpdatedTimestamp) {
    $this->lastUpdatedTimestamp = $lastUpdatedTimestamp;
  }
  public function getLastUpdatedTimestamp() {
    return $this->lastUpdatedTimestamp;
  }
}

class Google_PlayerAchievementListResponse extends Google_Model {
  protected $__itemsType = 'Google_PlayerAchievement';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public function setItems(/* array(Google_PlayerAchievement) */ $items) {
    $this->assertIsArray($items, 'Google_PlayerAchievement', __METHOD__);
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

class Google_PlayerLeaderboardScore extends Google_Model {
  public $kind;
  public $leaderboard_id;
  protected $__publicRankType = 'Google_LeaderboardScoreRank';
  protected $__publicRankDataType = '';
  public $publicRank;
  public $scoreString;
  public $scoreValue;
  protected $__socialRankType = 'Google_LeaderboardScoreRank';
  protected $__socialRankDataType = '';
  public $socialRank;
  public $timeSpan;
  public $writeTimestamp;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setLeaderboard_id( $leaderboard_id) {
    $this->leaderboard_id = $leaderboard_id;
  }
  public function getLeaderboard_id() {
    return $this->leaderboard_id;
  }
  public function setPublicRank(Google_LeaderboardScoreRank $publicRank) {
    $this->publicRank = $publicRank;
  }
  public function getPublicRank() {
    return $this->publicRank;
  }
  public function setScoreString( $scoreString) {
    $this->scoreString = $scoreString;
  }
  public function getScoreString() {
    return $this->scoreString;
  }
  public function setScoreValue( $scoreValue) {
    $this->scoreValue = $scoreValue;
  }
  public function getScoreValue() {
    return $this->scoreValue;
  }
  public function setSocialRank(Google_LeaderboardScoreRank $socialRank) {
    $this->socialRank = $socialRank;
  }
  public function getSocialRank() {
    return $this->socialRank;
  }
  public function setTimeSpan( $timeSpan) {
    $this->timeSpan = $timeSpan;
  }
  public function getTimeSpan() {
    return $this->timeSpan;
  }
  public function setWriteTimestamp( $writeTimestamp) {
    $this->writeTimestamp = $writeTimestamp;
  }
  public function getWriteTimestamp() {
    return $this->writeTimestamp;
  }
}

class Google_PlayerLeaderboardScoreListResponse extends Google_Model {
  protected $__itemsType = 'Google_PlayerLeaderboardScore';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public function setItems(/* array(Google_PlayerLeaderboardScore) */ $items) {
    $this->assertIsArray($items, 'Google_PlayerLeaderboardScore', __METHOD__);
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

class Google_PlayerScore extends Google_Model {
  public $formattedScore;
  public $kind;
  public $score;
  public $timeSpan;
  public function setFormattedScore( $formattedScore) {
    $this->formattedScore = $formattedScore;
  }
  public function getFormattedScore() {
    return $this->formattedScore;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setScore( $score) {
    $this->score = $score;
  }
  public function getScore() {
    return $this->score;
  }
  public function setTimeSpan( $timeSpan) {
    $this->timeSpan = $timeSpan;
  }
  public function getTimeSpan() {
    return $this->timeSpan;
  }
}

class Google_PlayerScoreListResponse extends Google_Model {
  public $kind;
  protected $__submittedScoresType = 'Google_PlayerScoreResponse';
  protected $__submittedScoresDataType = 'array';
  public $submittedScores;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setSubmittedScores(/* array(Google_PlayerScoreResponse) */ $submittedScores) {
    $this->assertIsArray($submittedScores, 'Google_PlayerScoreResponse', __METHOD__);
    $this->submittedScores = $submittedScores;
  }
  public function getSubmittedScores() {
    return $this->submittedScores;
  }
}

class Google_PlayerScoreResponse extends Google_Model {
  public $beatenScoreTimeSpans;
  public $formattedScore;
  public $kind;
  public $leaderboardId;
  protected $__unbeatenScoresType = 'Google_PlayerScore';
  protected $__unbeatenScoresDataType = 'array';
  public $unbeatenScores;
  public function setBeatenScoreTimeSpans(/* array(Google_string) */ $beatenScoreTimeSpans) {
    $this->assertIsArray($beatenScoreTimeSpans, 'Google_string', __METHOD__);
    $this->beatenScoreTimeSpans = $beatenScoreTimeSpans;
  }
  public function getBeatenScoreTimeSpans() {
    return $this->beatenScoreTimeSpans;
  }
  public function setFormattedScore( $formattedScore) {
    $this->formattedScore = $formattedScore;
  }
  public function getFormattedScore() {
    return $this->formattedScore;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setLeaderboardId( $leaderboardId) {
    $this->leaderboardId = $leaderboardId;
  }
  public function getLeaderboardId() {
    return $this->leaderboardId;
  }
  public function setUnbeatenScores(/* array(Google_PlayerScore) */ $unbeatenScores) {
    $this->assertIsArray($unbeatenScores, 'Google_PlayerScore', __METHOD__);
    $this->unbeatenScores = $unbeatenScores;
  }
  public function getUnbeatenScores() {
    return $this->unbeatenScores;
  }
}

class Google_PlayerScoreSubmissionList extends Google_Model {
  public $kind;
  protected $__scoresType = 'Google_ScoreSubmission';
  protected $__scoresDataType = 'array';
  public $scores;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setScores(/* array(Google_ScoreSubmission) */ $scores) {
    $this->assertIsArray($scores, 'Google_ScoreSubmission', __METHOD__);
    $this->scores = $scores;
  }
  public function getScores() {
    return $this->scores;
  }
}

class Google_RevisionCheckResponse extends Google_Model {
  public $kind;
  public $revisionStatus;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setRevisionStatus( $revisionStatus) {
    $this->revisionStatus = $revisionStatus;
  }
  public function getRevisionStatus() {
    return $this->revisionStatus;
  }
}

class Google_Room extends Google_Model {
  public $applicationId;
  protected $__autoMatchingCriteriaType = 'Google_RoomAutoMatchingCriteria';
  protected $__autoMatchingCriteriaDataType = '';
  public $autoMatchingCriteria;
  protected $__autoMatchingStatusType = 'Google_RoomAutoMatchStatus';
  protected $__autoMatchingStatusDataType = '';
  public $autoMatchingStatus;
  protected $__creationDetailsType = 'Google_RoomModification';
  protected $__creationDetailsDataType = '';
  public $creationDetails;
  public $description;
  public $kind;
  protected $__lastUpdateDetailsType = 'Google_RoomModification';
  protected $__lastUpdateDetailsDataType = '';
  public $lastUpdateDetails;
  protected $__participantsType = 'Google_RoomParticipant';
  protected $__participantsDataType = 'array';
  public $participants;
  public $roomId;
  public $roomStatusVersion;
  public $status;
  public $variant;
  public function setApplicationId( $applicationId) {
    $this->applicationId = $applicationId;
  }
  public function getApplicationId() {
    return $this->applicationId;
  }
  public function setAutoMatchingCriteria(Google_RoomAutoMatchingCriteria $autoMatchingCriteria) {
    $this->autoMatchingCriteria = $autoMatchingCriteria;
  }
  public function getAutoMatchingCriteria() {
    return $this->autoMatchingCriteria;
  }
  public function setAutoMatchingStatus(Google_RoomAutoMatchStatus $autoMatchingStatus) {
    $this->autoMatchingStatus = $autoMatchingStatus;
  }
  public function getAutoMatchingStatus() {
    return $this->autoMatchingStatus;
  }
  public function setCreationDetails(Google_RoomModification $creationDetails) {
    $this->creationDetails = $creationDetails;
  }
  public function getCreationDetails() {
    return $this->creationDetails;
  }
  public function setDescription( $description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setLastUpdateDetails(Google_RoomModification $lastUpdateDetails) {
    $this->lastUpdateDetails = $lastUpdateDetails;
  }
  public function getLastUpdateDetails() {
    return $this->lastUpdateDetails;
  }
  public function setParticipants(/* array(Google_RoomParticipant) */ $participants) {
    $this->assertIsArray($participants, 'Google_RoomParticipant', __METHOD__);
    $this->participants = $participants;
  }
  public function getParticipants() {
    return $this->participants;
  }
  public function setRoomId( $roomId) {
    $this->roomId = $roomId;
  }
  public function getRoomId() {
    return $this->roomId;
  }
  public function setRoomStatusVersion( $roomStatusVersion) {
    $this->roomStatusVersion = $roomStatusVersion;
  }
  public function getRoomStatusVersion() {
    return $this->roomStatusVersion;
  }
  public function setStatus( $status) {
    $this->status = $status;
  }
  public function getStatus() {
    return $this->status;
  }
  public function setVariant( $variant) {
    $this->variant = $variant;
  }
  public function getVariant() {
    return $this->variant;
  }
}

class Google_RoomAutoMatchStatus extends Google_Model {
  public $kind;
  public $waitEstimateSeconds;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setWaitEstimateSeconds( $waitEstimateSeconds) {
    $this->waitEstimateSeconds = $waitEstimateSeconds;
  }
  public function getWaitEstimateSeconds() {
    return $this->waitEstimateSeconds;
  }
}

class Google_RoomAutoMatchingCriteria extends Google_Model {
  public $exclusiveBitmask;
  public $kind;
  public $maxAutoMatchingPlayers;
  public $minAutoMatchingPlayers;
  public function setExclusiveBitmask( $exclusiveBitmask) {
    $this->exclusiveBitmask = $exclusiveBitmask;
  }
  public function getExclusiveBitmask() {
    return $this->exclusiveBitmask;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setMaxAutoMatchingPlayers( $maxAutoMatchingPlayers) {
    $this->maxAutoMatchingPlayers = $maxAutoMatchingPlayers;
  }
  public function getMaxAutoMatchingPlayers() {
    return $this->maxAutoMatchingPlayers;
  }
  public function setMinAutoMatchingPlayers( $minAutoMatchingPlayers) {
    $this->minAutoMatchingPlayers = $minAutoMatchingPlayers;
  }
  public function getMinAutoMatchingPlayers() {
    return $this->minAutoMatchingPlayers;
  }
}

class Google_RoomClientAddress extends Google_Model {
  public $kind;
  public $xmppAddress;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setXmppAddress( $xmppAddress) {
    $this->xmppAddress = $xmppAddress;
  }
  public function getXmppAddress() {
    return $this->xmppAddress;
  }
}

class Google_RoomCreateRequest extends Google_Model {
  protected $__autoMatchingCriteriaType = 'Google_RoomAutoMatchingCriteria';
  protected $__autoMatchingCriteriaDataType = '';
  public $autoMatchingCriteria;
  public $capabilities;
  protected $__clientAddressType = 'Google_RoomClientAddress';
  protected $__clientAddressDataType = '';
  public $clientAddress;
  public $invitedPlayerIds;
  public $kind;
  protected $__networkDiagnosticsType = 'Google_NetworkDiagnostics';
  protected $__networkDiagnosticsDataType = '';
  public $networkDiagnostics;
  public $variant;
  public function setAutoMatchingCriteria(Google_RoomAutoMatchingCriteria $autoMatchingCriteria) {
    $this->autoMatchingCriteria = $autoMatchingCriteria;
  }
  public function getAutoMatchingCriteria() {
    return $this->autoMatchingCriteria;
  }
  public function setCapabilities(/* array(Google_string) */ $capabilities) {
    $this->assertIsArray($capabilities, 'Google_string', __METHOD__);
    $this->capabilities = $capabilities;
  }
  public function getCapabilities() {
    return $this->capabilities;
  }
  public function setClientAddress(Google_RoomClientAddress $clientAddress) {
    $this->clientAddress = $clientAddress;
  }
  public function getClientAddress() {
    return $this->clientAddress;
  }
  public function setInvitedPlayerIds(/* array(Google_string) */ $invitedPlayerIds) {
    $this->assertIsArray($invitedPlayerIds, 'Google_string', __METHOD__);
    $this->invitedPlayerIds = $invitedPlayerIds;
  }
  public function getInvitedPlayerIds() {
    return $this->invitedPlayerIds;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setNetworkDiagnostics(Google_NetworkDiagnostics $networkDiagnostics) {
    $this->networkDiagnostics = $networkDiagnostics;
  }
  public function getNetworkDiagnostics() {
    return $this->networkDiagnostics;
  }
  public function setVariant( $variant) {
    $this->variant = $variant;
  }
  public function getVariant() {
    return $this->variant;
  }
}

class Google_RoomJoinRequest extends Google_Model {
  public $capabilities;
  protected $__clientAddressType = 'Google_RoomClientAddress';
  protected $__clientAddressDataType = '';
  public $clientAddress;
  public $kind;
  protected $__networkDiagnosticsType = 'Google_NetworkDiagnostics';
  protected $__networkDiagnosticsDataType = '';
  public $networkDiagnostics;
  public function setCapabilities(/* array(Google_string) */ $capabilities) {
    $this->assertIsArray($capabilities, 'Google_string', __METHOD__);
    $this->capabilities = $capabilities;
  }
  public function getCapabilities() {
    return $this->capabilities;
  }
  public function setClientAddress(Google_RoomClientAddress $clientAddress) {
    $this->clientAddress = $clientAddress;
  }
  public function getClientAddress() {
    return $this->clientAddress;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setNetworkDiagnostics(Google_NetworkDiagnostics $networkDiagnostics) {
    $this->networkDiagnostics = $networkDiagnostics;
  }
  public function getNetworkDiagnostics() {
    return $this->networkDiagnostics;
  }
}

class Google_RoomLeaveDiagnostics extends Google_Model {
  public $androidNetworkSubtype;
  public $androidNetworkType;
  public $kind;
  protected $__peerSessionType = 'Google_PeerSessionDiagnostics';
  protected $__peerSessionDataType = 'array';
  public $peerSession;
  public $socketsUsed;
  public function setAndroidNetworkSubtype( $androidNetworkSubtype) {
    $this->androidNetworkSubtype = $androidNetworkSubtype;
  }
  public function getAndroidNetworkSubtype() {
    return $this->androidNetworkSubtype;
  }
  public function setAndroidNetworkType( $androidNetworkType) {
    $this->androidNetworkType = $androidNetworkType;
  }
  public function getAndroidNetworkType() {
    return $this->androidNetworkType;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setPeerSession(/* array(Google_PeerSessionDiagnostics) */ $peerSession) {
    $this->assertIsArray($peerSession, 'Google_PeerSessionDiagnostics', __METHOD__);
    $this->peerSession = $peerSession;
  }
  public function getPeerSession() {
    return $this->peerSession;
  }
  public function setSocketsUsed( $socketsUsed) {
    $this->socketsUsed = $socketsUsed;
  }
  public function getSocketsUsed() {
    return $this->socketsUsed;
  }
}

class Google_RoomLeaveRequest extends Google_Model {
  public $kind;
  protected $__leaveDiagnosticsType = 'Google_RoomLeaveDiagnostics';
  protected $__leaveDiagnosticsDataType = '';
  public $leaveDiagnostics;
  public $reason;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setLeaveDiagnostics(Google_RoomLeaveDiagnostics $leaveDiagnostics) {
    $this->leaveDiagnostics = $leaveDiagnostics;
  }
  public function getLeaveDiagnostics() {
    return $this->leaveDiagnostics;
  }
  public function setReason( $reason) {
    $this->reason = $reason;
  }
  public function getReason() {
    return $this->reason;
  }
}

class Google_RoomList extends Google_Model {
  protected $__itemsType = 'Google_Room';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public function setItems(/* array(Google_Room) */ $items) {
    $this->assertIsArray($items, 'Google_Room', __METHOD__);
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

class Google_RoomModification extends Google_Model {
  public $kind;
  public $modifiedTimestampMillis;
  public $participantId;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setModifiedTimestampMillis( $modifiedTimestampMillis) {
    $this->modifiedTimestampMillis = $modifiedTimestampMillis;
  }
  public function getModifiedTimestampMillis() {
    return $this->modifiedTimestampMillis;
  }
  public function setParticipantId( $participantId) {
    $this->participantId = $participantId;
  }
  public function getParticipantId() {
    return $this->participantId;
  }
}

class Google_RoomP2PStatus extends Google_Model {
  public $connectionSetupLatencyMillis;
  public $error;
  public $error_reason;
  public $kind;
  public $participantId;
  public $status;
  public $unreliableRoundtripLatencyMillis;
  public function setConnectionSetupLatencyMillis( $connectionSetupLatencyMillis) {
    $this->connectionSetupLatencyMillis = $connectionSetupLatencyMillis;
  }
  public function getConnectionSetupLatencyMillis() {
    return $this->connectionSetupLatencyMillis;
  }
  public function setError( $error) {
    $this->error = $error;
  }
  public function getError() {
    return $this->error;
  }
  public function setError_reason( $error_reason) {
    $this->error_reason = $error_reason;
  }
  public function getError_reason() {
    return $this->error_reason;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setParticipantId( $participantId) {
    $this->participantId = $participantId;
  }
  public function getParticipantId() {
    return $this->participantId;
  }
  public function setStatus( $status) {
    $this->status = $status;
  }
  public function getStatus() {
    return $this->status;
  }
  public function setUnreliableRoundtripLatencyMillis( $unreliableRoundtripLatencyMillis) {
    $this->unreliableRoundtripLatencyMillis = $unreliableRoundtripLatencyMillis;
  }
  public function getUnreliableRoundtripLatencyMillis() {
    return $this->unreliableRoundtripLatencyMillis;
  }
}

class Google_RoomP2PStatuses extends Google_Model {
  public $kind;
  protected $__updatesType = 'Google_RoomP2PStatus';
  protected $__updatesDataType = 'array';
  public $updates;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setUpdates(/* array(Google_RoomP2PStatus) */ $updates) {
    $this->assertIsArray($updates, 'Google_RoomP2PStatus', __METHOD__);
    $this->updates = $updates;
  }
  public function getUpdates() {
    return $this->updates;
  }
}

class Google_RoomParticipant extends Google_Model {
  protected $__autoMatchedPlayerType = 'Google_AnonymousPlayer';
  protected $__autoMatchedPlayerDataType = '';
  public $autoMatchedPlayer;
  public $capabilities;
  protected $__clientAddressType = 'Google_RoomClientAddress';
  protected $__clientAddressDataType = '';
  public $clientAddress;
  public $connected;
  public $id;
  public $kind;
  public $leaveReason;
  protected $__playerType = 'Google_Player';
  protected $__playerDataType = '';
  public $player;
  public $status;
  public function setAutoMatchedPlayer(Google_AnonymousPlayer $autoMatchedPlayer) {
    $this->autoMatchedPlayer = $autoMatchedPlayer;
  }
  public function getAutoMatchedPlayer() {
    return $this->autoMatchedPlayer;
  }
  public function setCapabilities(/* array(Google_string) */ $capabilities) {
    $this->assertIsArray($capabilities, 'Google_string', __METHOD__);
    $this->capabilities = $capabilities;
  }
  public function getCapabilities() {
    return $this->capabilities;
  }
  public function setClientAddress(Google_RoomClientAddress $clientAddress) {
    $this->clientAddress = $clientAddress;
  }
  public function getClientAddress() {
    return $this->clientAddress;
  }
  public function setConnected( $connected) {
    $this->connected = $connected;
  }
  public function getConnected() {
    return $this->connected;
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
  public function setLeaveReason( $leaveReason) {
    $this->leaveReason = $leaveReason;
  }
  public function getLeaveReason() {
    return $this->leaveReason;
  }
  public function setPlayer(Google_Player $player) {
    $this->player = $player;
  }
  public function getPlayer() {
    return $this->player;
  }
  public function setStatus( $status) {
    $this->status = $status;
  }
  public function getStatus() {
    return $this->status;
  }
}

class Google_RoomStatus extends Google_Model {
  protected $__autoMatchingStatusType = 'Google_RoomAutoMatchStatus';
  protected $__autoMatchingStatusDataType = '';
  public $autoMatchingStatus;
  public $kind;
  protected $__participantsType = 'Google_RoomParticipant';
  protected $__participantsDataType = 'array';
  public $participants;
  public $roomId;
  public $status;
  public $statusVersion;
  public function setAutoMatchingStatus(Google_RoomAutoMatchStatus $autoMatchingStatus) {
    $this->autoMatchingStatus = $autoMatchingStatus;
  }
  public function getAutoMatchingStatus() {
    return $this->autoMatchingStatus;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setParticipants(/* array(Google_RoomParticipant) */ $participants) {
    $this->assertIsArray($participants, 'Google_RoomParticipant', __METHOD__);
    $this->participants = $participants;
  }
  public function getParticipants() {
    return $this->participants;
  }
  public function setRoomId( $roomId) {
    $this->roomId = $roomId;
  }
  public function getRoomId() {
    return $this->roomId;
  }
  public function setStatus( $status) {
    $this->status = $status;
  }
  public function getStatus() {
    return $this->status;
  }
  public function setStatusVersion( $statusVersion) {
    $this->statusVersion = $statusVersion;
  }
  public function getStatusVersion() {
    return $this->statusVersion;
  }
}

class Google_ScoreSubmission extends Google_Model {
  public $kind;
  public $leaderboardId;
  public $score;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setLeaderboardId( $leaderboardId) {
    $this->leaderboardId = $leaderboardId;
  }
  public function getLeaderboardId() {
    return $this->leaderboardId;
  }
  public function setScore( $score) {
    $this->score = $score;
  }
  public function getScore() {
    return $this->score;
  }
}
