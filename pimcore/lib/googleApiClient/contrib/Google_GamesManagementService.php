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
   * The "achievements" collection of methods.
   * Typical usage is:
   *  <code>
   *   $gamesManagementService = new Google_GamesManagementService(...);
   *   $achievements = $gamesManagementService->achievements;
   *  </code>
   */
  class Google_AchievementsServiceResource extends Google_ServiceResource {

    /**
     * Resets the achievement with the given ID. This method is only accessible to whitelisted tester
     * accounts for your application. (achievements.reset)
     *
     * @param string $achievementId The ID of the achievement used by this method.
     * @param array $optParams Optional parameters.
     * @return Google_AchievementResetResponse
     */
    public function reset($achievementId, $optParams = array()) {
      $params = array('achievementId' => $achievementId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('reset', array($params));
      if ($this->useObjects()) {
        return new Google_AchievementResetResponse($data);
      } else {
        return $data;
      }
    }
    /**
     * Resets all achievements for the currently authenticated player for your application. This method
     * is only accessible to whitelisted tester accounts for your application. (achievements.resetAll)
     *
     * @param array $optParams Optional parameters.
     * @return Google_AchievementResetAllResponse
     */
    public function resetAll($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('resetAll', array($params));
      if ($this->useObjects()) {
        return new Google_AchievementResetAllResponse($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "applications" collection of methods.
   * Typical usage is:
   *  <code>
   *   $gamesManagementService = new Google_GamesManagementService(...);
   *   $applications = $gamesManagementService->applications;
   *  </code>
   */
  class Google_ApplicationsServiceResource extends Google_ServiceResource {

    /**
     * Get the list of players hidden from the given application. This method is only available to user
     * accounts for your developer console. (applications.listHidden)
     *
     * @param string $applicationId The application being requested.
     * @param array $optParams Optional parameters.
     *
     * @opt_param int maxResults The maximum number of player resources to return in the response, used for paging. For any response, the actual number of player resources returned may be less than the specified maxResults.
     * @opt_param string pageToken The token returned by the previous request.
     * @return Google_HiddenPlayerList
     */
    public function listHidden($applicationId, $optParams = array()) {
      $params = array('applicationId' => $applicationId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('listHidden', array($params));
      if ($this->useObjects()) {
        return new Google_HiddenPlayerList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "players" collection of methods.
   * Typical usage is:
   *  <code>
   *   $gamesManagementService = new Google_GamesManagementService(...);
   *   $players = $gamesManagementService->players;
   *  </code>
   */
  class Google_PlayersServiceResource extends Google_ServiceResource {

    /**
     * Hide the given player's leaderboard scores from the given application. This method is only
     * available to user accounts for your developer console. (players.hide)
     *
     * @param string $applicationId The application being requested.
     * @param string $playerId A player ID. A value of me may be used in place of the authenticated player's ID.
     * @param array $optParams Optional parameters.
     */
    public function hide($applicationId, $playerId, $optParams = array()) {
      $params = array('applicationId' => $applicationId, 'playerId' => $playerId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('hide', array($params));
      return $data;
    }
    /**
     * Unhide the given player's leaderboard scores from the given application. This method is only
     * available to user accounts for your developer console. (players.unhide)
     *
     * @param string $applicationId The application being requested.
     * @param string $playerId A player ID. A value of me may be used in place of the authenticated player's ID.
     * @param array $optParams Optional parameters.
     */
    public function unhide($applicationId, $playerId, $optParams = array()) {
      $params = array('applicationId' => $applicationId, 'playerId' => $playerId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('unhide', array($params));
      return $data;
    }
  }

  /**
   * The "rooms" collection of methods.
   * Typical usage is:
   *  <code>
   *   $gamesManagementService = new Google_GamesManagementService(...);
   *   $rooms = $gamesManagementService->rooms;
   *  </code>
   */
  class Google_RoomsServiceResource extends Google_ServiceResource {

    /**
     * Reset all rooms for the currently authenticated player for your application. This method is only
     * accessible to whitelisted tester accounts for your application. (rooms.reset)
     *
     * @param array $optParams Optional parameters.
     */
    public function reset($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('reset', array($params));
      return $data;
    }
  }

  /**
   * The "scores" collection of methods.
   * Typical usage is:
   *  <code>
   *   $gamesManagementService = new Google_GamesManagementService(...);
   *   $scores = $gamesManagementService->scores;
   *  </code>
   */
  class Google_ScoresServiceResource extends Google_ServiceResource {

    /**
     * Reset scores for the specified leaderboard, resetting the leaderboard to empty. This method is
     * only accessible to whitelisted tester accounts for your application. (scores.reset)
     *
     * @param string $leaderboardId The ID of the leaderboard.
     * @param array $optParams Optional parameters.
     * @return Google_PlayerScoreResetResponse
     */
    public function reset($leaderboardId, $optParams = array()) {
      $params = array('leaderboardId' => $leaderboardId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('reset', array($params));
      if ($this->useObjects()) {
        return new Google_PlayerScoreResetResponse($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Google_GamesManagement (v1management).
 *
 * <p>
 * The Management API for Google Play Game Services.
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://developers.google.com/games/services" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_GamesManagementService extends Google_Service {
  public $achievements;
  public $applications;
  public $players;
  public $rooms;
  public $scores;
  /**
   * Constructs the internal representation of the GamesManagement service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client) {
    $this->servicePath = 'games/v1management/';
    $this->version = 'v1management';
    $this->serviceName = 'gamesManagement';

    $client->addService($this->serviceName, $this->version);
    $this->achievements = new Google_AchievementsServiceResource($this, $this->serviceName, 'achievements', json_decode('{"methods": {"reset": {"id": "gamesManagement.achievements.reset", "path": "achievements/{achievementId}/reset", "httpMethod": "POST", "parameters": {"achievementId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "AchievementResetResponse"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "resetAll": {"id": "gamesManagement.achievements.resetAll", "path": "achievements/reset", "httpMethod": "POST", "response": {"$ref": "AchievementResetAllResponse"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}}}', true));
    $this->applications = new Google_ApplicationsServiceResource($this, $this->serviceName, 'applications', json_decode('{"methods": {"listHidden": {"id": "gamesManagement.applications.listHidden", "path": "applications/{applicationId}/players/hidden", "httpMethod": "GET", "parameters": {"applicationId": {"type": "string", "required": true, "location": "path"}, "maxResults": {"type": "integer", "format": "int32", "minimum": "1", "maximum": "15", "location": "query"}, "pageToken": {"type": "string", "location": "query"}}, "response": {"$ref": "HiddenPlayerList"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}}}', true));
    $this->players = new Google_PlayersServiceResource($this, $this->serviceName, 'players', json_decode('{"methods": {"hide": {"id": "gamesManagement.players.hide", "path": "applications/{applicationId}/players/hidden/{playerId}", "httpMethod": "POST", "parameters": {"applicationId": {"type": "string", "required": true, "location": "path"}, "playerId": {"type": "string", "required": true, "location": "path"}}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}, "unhide": {"id": "gamesManagement.players.unhide", "path": "applications/{applicationId}/players/hidden/{playerId}", "httpMethod": "DELETE", "parameters": {"applicationId": {"type": "string", "required": true, "location": "path"}, "playerId": {"type": "string", "required": true, "location": "path"}}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}}}', true));
    $this->rooms = new Google_RoomsServiceResource($this, $this->serviceName, 'rooms', json_decode('{"methods": {"reset": {"id": "gamesManagement.rooms.reset", "path": "rooms/reset", "httpMethod": "POST", "scopes": ["https://www.googleapis.com/auth/plus.login"]}}}', true));
    $this->scores = new Google_ScoresServiceResource($this, $this->serviceName, 'scores', json_decode('{"methods": {"reset": {"id": "gamesManagement.scores.reset", "path": "leaderboards/{leaderboardId}/scores/reset", "httpMethod": "POST", "parameters": {"leaderboardId": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "PlayerScoreResetResponse"}, "scopes": ["https://www.googleapis.com/auth/plus.login"]}}}', true));

  }
}



class Google_AchievementResetAllResponse extends Google_Model {
  public $kind;
  protected $__resultsType = 'Google_AchievementResetResponse';
  protected $__resultsDataType = 'array';
  public $results;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setResults(/* array(Google_AchievementResetResponse) */ $results) {
    $this->assertIsArray($results, 'Google_AchievementResetResponse', __METHOD__);
    $this->results = $results;
  }
  public function getResults() {
    return $this->results;
  }
}

class Google_AchievementResetResponse extends Google_Model {
  public $currentState;
  public $definitionId;
  public $kind;
  public $updateOccurred;
  public function setCurrentState( $currentState) {
    $this->currentState = $currentState;
  }
  public function getCurrentState() {
    return $this->currentState;
  }
  public function setDefinitionId( $definitionId) {
    $this->definitionId = $definitionId;
  }
  public function getDefinitionId() {
    return $this->definitionId;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setUpdateOccurred( $updateOccurred) {
    $this->updateOccurred = $updateOccurred;
  }
  public function getUpdateOccurred() {
    return $this->updateOccurred;
  }
}

class Google_HiddenPlayer extends Google_Model {
  public $hiddenTimeMillis;
  public $kind;
  protected $__playerType = 'Google_Player';
  protected $__playerDataType = '';
  public $player;
  public function setHiddenTimeMillis( $hiddenTimeMillis) {
    $this->hiddenTimeMillis = $hiddenTimeMillis;
  }
  public function getHiddenTimeMillis() {
    return $this->hiddenTimeMillis;
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
}

class Google_HiddenPlayerList extends Google_Model {
  protected $__itemsType = 'Google_HiddenPlayer';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public function setItems(/* array(Google_HiddenPlayer) */ $items) {
    $this->assertIsArray($items, 'Google_HiddenPlayer', __METHOD__);
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

class Google_PlayerScoreResetResponse extends Google_Model {
  public $kind;
  public $resetScoreTimeSpans;
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setResetScoreTimeSpans(/* array(Google_string) */ $resetScoreTimeSpans) {
    $this->assertIsArray($resetScoreTimeSpans, 'Google_string', __METHOD__);
    $this->resetScoreTimeSpans = $resetScoreTimeSpans;
  }
  public function getResetScoreTimeSpans() {
    return $this->resetScoreTimeSpans;
  }
}
