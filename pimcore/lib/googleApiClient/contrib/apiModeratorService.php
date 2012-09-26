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
   * The "votes" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $votes = $moderatorService->votes;
   *  </code>
   */
  class VotesServiceResource extends apiServiceResource {


    /**
     * Inserts a new vote by the authenticated user for the specified submission within the specified
     * series. (votes.insert)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param string $submissionId The decimal ID of the Submission within the Series.
     * @param Vote $postBody
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string unauthToken User identifier for unauthenticated usage mode
     * @return Vote
     */
    public function insert($seriesId, $submissionId, Vote $postBody, $optParams = array()) {
      $params = array('seriesId' => $seriesId, 'submissionId' => $submissionId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Vote($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates the votes by the authenticated user for the specified submission within the specified
     * series. This method supports patch semantics. (votes.patch)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param string $submissionId The decimal ID of the Submission within the Series.
     * @param Vote $postBody
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string userId
     * @opt_param string unauthToken User identifier for unauthenticated usage mode
     * @return Vote
     */
    public function patch($seriesId, $submissionId, Vote $postBody, $optParams = array()) {
      $params = array('seriesId' => $seriesId, 'submissionId' => $submissionId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Vote($data);
      } else {
        return $data;
      }
    }
    /**
     * Lists the votes by the authenticated user for the given series. (votes.list)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string max-results Maximum number of results to return.
     * @opt_param string start-index Index of the first result to be retrieved.
     * @return VoteList
     */
    public function listVotes($seriesId, $optParams = array()) {
      $params = array('seriesId' => $seriesId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new VoteList($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates the votes by the authenticated user for the specified submission within the specified
     * series. (votes.update)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param string $submissionId The decimal ID of the Submission within the Series.
     * @param Vote $postBody
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string userId
     * @opt_param string unauthToken User identifier for unauthenticated usage mode
     * @return Vote
     */
    public function update($seriesId, $submissionId, Vote $postBody, $optParams = array()) {
      $params = array('seriesId' => $seriesId, 'submissionId' => $submissionId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Vote($data);
      } else {
        return $data;
      }
    }
    /**
     * Returns the votes by the authenticated user for the specified submission within the specified
     * series. (votes.get)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param string $submissionId The decimal ID of the Submission within the Series.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string userId
     * @opt_param string unauthToken User identifier for unauthenticated usage mode
     * @return Vote
     */
    public function get($seriesId, $submissionId, $optParams = array()) {
      $params = array('seriesId' => $seriesId, 'submissionId' => $submissionId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Vote($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "responses" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $responses = $moderatorService->responses;
   *  </code>
   */
  class ResponsesServiceResource extends apiServiceResource {


    /**
     * Inserts a response for the specified submission in the specified topic within the specified
     * series. (responses.insert)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param string $topicId The decimal ID of the Topic within the Series.
     * @param string $parentSubmissionId The decimal ID of the parent Submission within the Series.
     * @param Submission $postBody
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string unauthToken User identifier for unauthenticated usage mode
     * @opt_param bool anonymous Set to true to mark the new submission as anonymous.
     * @return Submission
     */
    public function insert($seriesId, $topicId, $parentSubmissionId, Submission $postBody, $optParams = array()) {
      $params = array('seriesId' => $seriesId, 'topicId' => $topicId, 'parentSubmissionId' => $parentSubmissionId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Submission($data);
      } else {
        return $data;
      }
    }
    /**
     * Lists or searches the responses for the specified submission within the specified series and
     * returns the search results. (responses.list)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param string $submissionId The decimal ID of the Submission within the Series.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string max-results Maximum number of results to return.
     * @opt_param string sort Sort order.
     * @opt_param string author Restricts the results to submissions by a specific author.
     * @opt_param string start-index Index of the first result to be retrieved.
     * @opt_param string q Search query.
     * @opt_param bool hasAttachedVideo Specifies whether to restrict to submissions that have videos attached.
     * @return SubmissionList
     */
    public function listResponses($seriesId, $submissionId, $optParams = array()) {
      $params = array('seriesId' => $seriesId, 'submissionId' => $submissionId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new SubmissionList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "tags" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $tags = $moderatorService->tags;
   *  </code>
   */
  class TagsServiceResource extends apiServiceResource {


    /**
     * Inserts a new tag for the specified submission within the specified series. (tags.insert)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param string $submissionId The decimal ID of the Submission within the Series.
     * @param Tag $postBody
     * @return Tag
     */
    public function insert($seriesId, $submissionId, Tag $postBody, $optParams = array()) {
      $params = array('seriesId' => $seriesId, 'submissionId' => $submissionId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Tag($data);
      } else {
        return $data;
      }
    }
    /**
     * Lists all tags for the specified submission within the specified series. (tags.list)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param string $submissionId The decimal ID of the Submission within the Series.
     * @return TagList
     */
    public function listTags($seriesId, $submissionId, $optParams = array()) {
      $params = array('seriesId' => $seriesId, 'submissionId' => $submissionId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new TagList($data);
      } else {
        return $data;
      }
    }
    /**
     * Deletes the specified tag from the specified submission within the specified series.
     * (tags.delete)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param string $submissionId The decimal ID of the Submission within the Series.
     * @param string $tagId
     */
    public function delete($seriesId, $submissionId, $tagId, $optParams = array()) {
      $params = array('seriesId' => $seriesId, 'submissionId' => $submissionId, 'tagId' => $tagId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
  }

  /**
   * The "series" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $series = $moderatorService->series;
   *  </code>
   */
  class SeriesServiceResource extends apiServiceResource {


    /**
     * Inserts a new series. (series.insert)
     *
     * @param Series $postBody
     * @return Series
     */
    public function insert(Series $postBody, $optParams = array()) {
      $params = array('postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Series($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates the specified series. This method supports patch semantics. (series.patch)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param Series $postBody
     * @return Series
     */
    public function patch($seriesId, Series $postBody, $optParams = array()) {
      $params = array('seriesId' => $seriesId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Series($data);
      } else {
        return $data;
      }
    }
    /**
     * Searches the series and returns the search results. (series.list)
     *
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string max-results Maximum number of results to return.
     * @opt_param string q Search query.
     * @opt_param string start-index Index of the first result to be retrieved.
     * @return SeriesList
     */
    public function listSeries($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new SeriesList($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates the specified series. (series.update)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param Series $postBody
     * @return Series
     */
    public function update($seriesId, Series $postBody, $optParams = array()) {
      $params = array('seriesId' => $seriesId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Series($data);
      } else {
        return $data;
      }
    }
    /**
     * Returns the specified series. (series.get)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @return Series
     */
    public function get($seriesId, $optParams = array()) {
      $params = array('seriesId' => $seriesId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Series($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "submissions" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $submissions = $moderatorService->submissions;
   *  </code>
   */
  class SeriesSubmissionsServiceResource extends apiServiceResource {


    /**
     * Searches the submissions for the specified series and returns the search results.
     * (submissions.list)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string lang The language code for the language the client prefers resuls in.
     * @opt_param string max-results Maximum number of results to return.
     * @opt_param bool includeVotes Specifies whether to include the current user's vote
     * @opt_param string start-index Index of the first result to be retrieved.
     * @opt_param string author Restricts the results to submissions by a specific author.
     * @opt_param string sort Sort order.
     * @opt_param string q Search query.
     * @opt_param bool hasAttachedVideo Specifies whether to restrict to submissions that have videos attached.
     * @return SubmissionList
     */
    public function listSeriesSubmissions($seriesId, $optParams = array()) {
      $params = array('seriesId' => $seriesId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new SubmissionList($data);
      } else {
        return $data;
      }
    }
  }
  /**
   * The "responses" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $responses = $moderatorService->responses;
   *  </code>
   */
  class SeriesResponsesServiceResource extends apiServiceResource {


    /**
     * Searches the responses for the specified series and returns the search results. (responses.list)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string max-results Maximum number of results to return.
     * @opt_param string sort Sort order.
     * @opt_param string author Restricts the results to submissions by a specific author.
     * @opt_param string start-index Index of the first result to be retrieved.
     * @opt_param string q Search query.
     * @opt_param bool hasAttachedVideo Specifies whether to restrict to submissions that have videos attached.
     * @return SeriesList
     */
    public function listSeriesResponses($seriesId, $optParams = array()) {
      $params = array('seriesId' => $seriesId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new SeriesList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "topics" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $topics = $moderatorService->topics;
   *  </code>
   */
  class TopicsServiceResource extends apiServiceResource {


    /**
     * Inserts a new topic into the specified series. (topics.insert)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param Topic $postBody
     * @return Topic
     */
    public function insert($seriesId, Topic $postBody, $optParams = array()) {
      $params = array('seriesId' => $seriesId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Topic($data);
      } else {
        return $data;
      }
    }
    /**
     * Searches the topics within the specified series and returns the search results. (topics.list)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string max-results Maximum number of results to return.
     * @opt_param string q Search query.
     * @opt_param string start-index Index of the first result to be retrieved.
     * @opt_param string mode
     * @return TopicList
     */
    public function listTopics($seriesId, $optParams = array()) {
      $params = array('seriesId' => $seriesId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new TopicList($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates the specified topic within the specified series. (topics.update)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param string $topicId The decimal ID of the Topic within the Series.
     * @param Topic $postBody
     * @return Topic
     */
    public function update($seriesId, $topicId, Topic $postBody, $optParams = array()) {
      $params = array('seriesId' => $seriesId, 'topicId' => $topicId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Topic($data);
      } else {
        return $data;
      }
    }
    /**
     * Returns the specified topic from the specified series. (topics.get)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param string $topicId The decimal ID of the Topic within the Series.
     * @return Topic
     */
    public function get($seriesId, $topicId, $optParams = array()) {
      $params = array('seriesId' => $seriesId, 'topicId' => $topicId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Topic($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "submissions" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $submissions = $moderatorService->submissions;
   *  </code>
   */
  class TopicsSubmissionsServiceResource extends apiServiceResource {


    /**
     * Searches the submissions for the specified topic within the specified series and returns the
     * search results. (submissions.list)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param string $topicId The decimal ID of the Topic within the Series.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string max-results Maximum number of results to return.
     * @opt_param bool includeVotes Specifies whether to include the current user's vote
     * @opt_param string start-index Index of the first result to be retrieved.
     * @opt_param string author Restricts the results to submissions by a specific author.
     * @opt_param string sort Sort order.
     * @opt_param string q Search query.
     * @opt_param bool hasAttachedVideo Specifies whether to restrict to submissions that have videos attached.
     * @return SubmissionList
     */
    public function listTopicsSubmissions($seriesId, $topicId, $optParams = array()) {
      $params = array('seriesId' => $seriesId, 'topicId' => $topicId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new SubmissionList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "global" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $global = $moderatorService->global;
   *  </code>
   */
  class ModeratorGlobalServiceResource extends apiServiceResource {


  }

  /**
   * The "series" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $series = $moderatorService->series;
   *  </code>
   */
  class ModeratorGlobalSeriesServiceResource extends apiServiceResource {


    /**
     * Searches the public series and returns the search results. (series.list)
     *
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string max-results Maximum number of results to return.
     * @opt_param string q Search query.
     * @opt_param string start-index Index of the first result to be retrieved.
     * @return SeriesList
     */
    public function listModeratorGlobalSeries($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new SeriesList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "profiles" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $profiles = $moderatorService->profiles;
   *  </code>
   */
  class ProfilesServiceResource extends apiServiceResource {


    /**
     * Updates the profile information for the authenticated user. This method supports patch semantics.
     * (profiles.patch)
     *
     * @param Profile $postBody
     * @return Profile
     */
    public function patch(Profile $postBody, $optParams = array()) {
      $params = array('postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Profile($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates the profile information for the authenticated user. (profiles.update)
     *
     * @param Profile $postBody
     * @return Profile
     */
    public function update(Profile $postBody, $optParams = array()) {
      $params = array('postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Profile($data);
      } else {
        return $data;
      }
    }
    /**
     * Returns the profile information for the authenticated user. (profiles.get)
     *
     * @return Profile
     */
    public function get($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Profile($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "featured" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $featured = $moderatorService->featured;
   *  </code>
   */
  class FeaturedServiceResource extends apiServiceResource {


  }

  /**
   * The "series" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $series = $moderatorService->series;
   *  </code>
   */
  class FeaturedSeriesServiceResource extends apiServiceResource {


    /**
     * Lists the featured series. (series.list)
     *
     * @return SeriesList
     */
    public function listFeaturedSeries($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new SeriesList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "myrecent" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $myrecent = $moderatorService->myrecent;
   *  </code>
   */
  class MyrecentServiceResource extends apiServiceResource {


  }

  /**
   * The "series" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $series = $moderatorService->series;
   *  </code>
   */
  class MyrecentSeriesServiceResource extends apiServiceResource {


    /**
     * Lists the series the authenticated user has visited. (series.list)
     *
     * @return SeriesList
     */
    public function listMyrecentSeries($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new SeriesList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "my" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $my = $moderatorService->my;
   *  </code>
   */
  class MyServiceResource extends apiServiceResource {


  }

  /**
   * The "series" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $series = $moderatorService->series;
   *  </code>
   */
  class MySeriesServiceResource extends apiServiceResource {


    /**
     * Lists all series created by the authenticated user. (series.list)
     *
     * @return SeriesList
     */
    public function listMySeries($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new SeriesList($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "submissions" collection of methods.
   * Typical usage is:
   *  <code>
   *   $moderatorService = new apiModeratorService(...);
   *   $submissions = $moderatorService->submissions;
   *  </code>
   */
  class SubmissionsServiceResource extends apiServiceResource {


    /**
     * Inserts a new submission in the specified topic within the specified series. (submissions.insert)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param string $topicId The decimal ID of the Topic within the Series.
     * @param Submission $postBody
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string unauthToken User identifier for unauthenticated usage mode
     * @opt_param bool anonymous Set to true to mark the new submission as anonymous.
     * @return Submission
     */
    public function insert($seriesId, $topicId, Submission $postBody, $optParams = array()) {
      $params = array('seriesId' => $seriesId, 'topicId' => $topicId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Submission($data);
      } else {
        return $data;
      }
    }
    /**
     * Returns the specified submission within the specified series. (submissions.get)
     *
     * @param string $seriesId The decimal ID of the Series.
     * @param string $submissionId The decimal ID of the Submission within the Series.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string lang The language code for the language the client prefers resuls in.
     * @opt_param bool includeVotes Specifies whether to include the current user's vote
     * @return Submission
     */
    public function get($seriesId, $submissionId, $optParams = array()) {
      $params = array('seriesId' => $seriesId, 'submissionId' => $submissionId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Submission($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Moderator (v1).
 *
 * <p>
 * Moderator API
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="http://code.google.com/apis/moderator/v1/using_rest.html" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class apiModeratorService extends apiService {
  public $votes;
  public $responses;
  public $tags;
  public $series;
  public $series_submissions;
  public $series_responses;
  public $topics;
  public $topics_submissions;
  public $global_series;
  public $profiles;
  public $featured_series;
  public $myrecent_series;
  public $my_series;
  public $submissions;
  /**
   * Constructs the internal representation of the Moderator service.
   *
   * @param apiClient apiClient
   */
  public function __construct(apiClient $apiClient) {
    $this->restBasePath = '/moderator/v1/';
    $this->version = 'v1';
    $this->serviceName = 'moderator';

    $apiClient->addService($this->serviceName, $this->version);
    $this->votes = new VotesServiceResource($this, $this->serviceName, 'votes', json_decode('{"methods": {"insert": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "unauthToken": {"type": "string", "location": "query"}, "submissionId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}}, "request": {"$ref": "Vote"}, "id": "moderator.votes.insert", "httpMethod": "POST", "path": "series/{seriesId}/submissions/{submissionId}/votes/@me", "response": {"$ref": "Vote"}}, "get": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "userId": {"type": "string", "location": "query"}, "unauthToken": {"type": "string", "location": "query"}, "submissionId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}}, "id": "moderator.votes.get", "httpMethod": "GET", "path": "series/{seriesId}/submissions/{submissionId}/votes/@me", "response": {"$ref": "Vote"}}, "list": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"max-results": {"format": "uint32", "type": "integer", "location": "query"}, "seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "start-index": {"format": "uint32", "type": "integer", "location": "query"}}, "id": "moderator.votes.list", "httpMethod": "GET", "path": "series/{seriesId}/votes/@me", "response": {"$ref": "VoteList"}}, "update": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "userId": {"type": "string", "location": "query"}, "unauthToken": {"type": "string", "location": "query"}, "submissionId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}}, "request": {"$ref": "Vote"}, "id": "moderator.votes.update", "httpMethod": "PUT", "path": "series/{seriesId}/submissions/{submissionId}/votes/@me", "response": {"$ref": "Vote"}}, "patch": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "userId": {"type": "string", "location": "query"}, "unauthToken": {"type": "string", "location": "query"}, "submissionId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}}, "request": {"$ref": "Vote"}, "id": "moderator.votes.patch", "httpMethod": "PATCH", "path": "series/{seriesId}/submissions/{submissionId}/votes/@me", "response": {"$ref": "Vote"}}}}', true));
    $this->responses = new ResponsesServiceResource($this, $this->serviceName, 'responses', json_decode('{"methods": {"insert": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "parentSubmissionId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "unauthToken": {"type": "string", "location": "query"}, "anonymous": {"type": "boolean", "location": "query"}, "topicId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}}, "request": {"$ref": "Submission"}, "id": "moderator.responses.insert", "httpMethod": "POST", "path": "series/{seriesId}/topics/{topicId}/submissions/{parentSubmissionId}/responses", "response": {"$ref": "Submission"}}, "list": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"max-results": {"format": "uint32", "type": "integer", "location": "query"}, "sort": {"type": "string", "location": "query"}, "seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "author": {"type": "string", "location": "query"}, "start-index": {"format": "uint32", "type": "integer", "location": "query"}, "submissionId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "q": {"type": "string", "location": "query"}, "hasAttachedVideo": {"type": "boolean", "location": "query"}}, "id": "moderator.responses.list", "httpMethod": "GET", "path": "series/{seriesId}/submissions/{submissionId}/responses", "response": {"$ref": "SubmissionList"}}}}', true));
    $this->tags = new TagsServiceResource($this, $this->serviceName, 'tags', json_decode('{"methods": {"insert": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "submissionId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}}, "request": {"$ref": "Tag"}, "id": "moderator.tags.insert", "httpMethod": "POST", "path": "series/{seriesId}/submissions/{submissionId}/tags", "response": {"$ref": "Tag"}}, "list": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "submissionId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}}, "id": "moderator.tags.list", "httpMethod": "GET", "path": "series/{seriesId}/submissions/{submissionId}/tags", "response": {"$ref": "TagList"}}, "delete": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "tagId": {"required": true, "type": "string", "location": "path"}, "submissionId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}}, "httpMethod": "DELETE", "path": "series/{seriesId}/submissions/{submissionId}/tags/{tagId}", "id": "moderator.tags.delete"}}}', true));
    $this->series = new SeriesServiceResource($this, $this->serviceName, 'series', json_decode('{"methods": {"insert": {"scopes": ["https://www.googleapis.com/auth/moderator"], "request": {"$ref": "Series"}, "response": {"$ref": "Series"}, "httpMethod": "POST", "path": "series", "id": "moderator.series.insert"}, "get": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}}, "id": "moderator.series.get", "httpMethod": "GET", "path": "series/{seriesId}", "response": {"$ref": "Series"}}, "list": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"max-results": {"format": "uint32", "type": "integer", "location": "query"}, "q": {"type": "string", "location": "query"}, "start-index": {"format": "uint32", "type": "integer", "location": "query"}}, "response": {"$ref": "SeriesList"}, "httpMethod": "GET", "path": "series", "id": "moderator.series.list"}, "update": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}}, "request": {"$ref": "Series"}, "id": "moderator.series.update", "httpMethod": "PUT", "path": "series/{seriesId}", "response": {"$ref": "Series"}}, "patch": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}}, "request": {"$ref": "Series"}, "id": "moderator.series.patch", "httpMethod": "PATCH", "path": "series/{seriesId}", "response": {"$ref": "Series"}}}}', true));
    $this->series_submissions = new SeriesSubmissionsServiceResource($this, $this->serviceName, 'submissions', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"lang": {"type": "string", "location": "query"}, "max-results": {"format": "uint32", "type": "integer", "location": "query"}, "seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "author": {"type": "string", "location": "query"}, "start-index": {"format": "uint32", "type": "integer", "location": "query"}, "includeVotes": {"type": "boolean", "location": "query"}, "sort": {"type": "string", "location": "query"}, "q": {"type": "string", "location": "query"}, "hasAttachedVideo": {"type": "boolean", "location": "query"}}, "id": "moderator.series.submissions.list", "httpMethod": "GET", "path": "series/{seriesId}/submissions", "response": {"$ref": "SubmissionList"}}}}', true));
    $this->series_responses = new SeriesResponsesServiceResource($this, $this->serviceName, 'responses', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"max-results": {"format": "uint32", "type": "integer", "location": "query"}, "sort": {"type": "string", "location": "query"}, "seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "author": {"type": "string", "location": "query"}, "start-index": {"format": "uint32", "type": "integer", "location": "query"}, "q": {"type": "string", "location": "query"}, "hasAttachedVideo": {"type": "boolean", "location": "query"}}, "id": "moderator.series.responses.list", "httpMethod": "GET", "path": "series/{seriesId}/responses", "response": {"$ref": "SeriesList"}}}}', true));
    $this->topics = new TopicsServiceResource($this, $this->serviceName, 'topics', json_decode('{"methods": {"insert": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}}, "request": {"$ref": "Topic"}, "id": "moderator.topics.insert", "httpMethod": "POST", "path": "series/{seriesId}/topics", "response": {"$ref": "Topic"}}, "list": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"max-results": {"format": "uint32", "type": "integer", "location": "query"}, "q": {"type": "string", "location": "query"}, "start-index": {"format": "uint32", "type": "integer", "location": "query"}, "mode": {"type": "string", "location": "query"}, "seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}}, "id": "moderator.topics.list", "httpMethod": "GET", "path": "series/{seriesId}/topics", "response": {"$ref": "TopicList"}}, "update": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "topicId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}}, "request": {"$ref": "Topic"}, "id": "moderator.topics.update", "httpMethod": "PUT", "path": "series/{seriesId}/topics/{topicId}", "response": {"$ref": "Topic"}}, "get": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "topicId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}}, "id": "moderator.topics.get", "httpMethod": "GET", "path": "series/{seriesId}/topics/{topicId}", "response": {"$ref": "Topic"}}}}', true));
    $this->topics_submissions = new TopicsSubmissionsServiceResource($this, $this->serviceName, 'submissions', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"max-results": {"format": "uint32", "type": "integer", "location": "query"}, "seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "includeVotes": {"type": "boolean", "location": "query"}, "topicId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "start-index": {"format": "uint32", "type": "integer", "location": "query"}, "author": {"type": "string", "location": "query"}, "sort": {"type": "string", "location": "query"}, "q": {"type": "string", "location": "query"}, "hasAttachedVideo": {"type": "boolean", "location": "query"}}, "id": "moderator.topics.submissions.list", "httpMethod": "GET", "path": "series/{seriesId}/topics/{topicId}/submissions", "response": {"$ref": "SubmissionList"}}}}', true));
    $this->global_series = new ModeratorGlobalSeriesServiceResource($this, $this->serviceName, 'series', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"max-results": {"format": "uint32", "type": "integer", "location": "query"}, "q": {"type": "string", "location": "query"}, "start-index": {"format": "uint32", "type": "integer", "location": "query"}}, "response": {"$ref": "SeriesList"}, "httpMethod": "GET", "path": "search", "id": "moderator.global.series.list"}}}', true));
    $this->profiles = new ProfilesServiceResource($this, $this->serviceName, 'profiles', json_decode('{"methods": {"get": {"scopes": ["https://www.googleapis.com/auth/moderator"], "id": "moderator.profiles.get", "httpMethod": "GET", "path": "profiles/@me", "response": {"$ref": "Profile"}}, "update": {"scopes": ["https://www.googleapis.com/auth/moderator"], "request": {"$ref": "Profile"}, "response": {"$ref": "Profile"}, "httpMethod": "PUT", "path": "profiles/@me", "id": "moderator.profiles.update"}, "patch": {"scopes": ["https://www.googleapis.com/auth/moderator"], "request": {"$ref": "Profile"}, "response": {"$ref": "Profile"}, "httpMethod": "PATCH", "path": "profiles/@me", "id": "moderator.profiles.patch"}}}', true));
    $this->featured_series = new FeaturedSeriesServiceResource($this, $this->serviceName, 'series', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/moderator"], "id": "moderator.featured.series.list", "httpMethod": "GET", "path": "series/featured", "response": {"$ref": "SeriesList"}}}}', true));
    $this->myrecent_series = new MyrecentSeriesServiceResource($this, $this->serviceName, 'series', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/moderator"], "id": "moderator.myrecent.series.list", "httpMethod": "GET", "path": "series/@me/recent", "response": {"$ref": "SeriesList"}}}}', true));
    $this->my_series = new MySeriesServiceResource($this, $this->serviceName, 'series', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/moderator"], "id": "moderator.my.series.list", "httpMethod": "GET", "path": "series/@me/mine", "response": {"$ref": "SeriesList"}}}}', true));
    $this->submissions = new SubmissionsServiceResource($this, $this->serviceName, 'submissions', json_decode('{"methods": {"insert": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "topicId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "unauthToken": {"type": "string", "location": "query"}, "anonymous": {"type": "boolean", "location": "query"}}, "request": {"$ref": "Submission"}, "id": "moderator.submissions.insert", "httpMethod": "POST", "path": "series/{seriesId}/topics/{topicId}/submissions", "response": {"$ref": "Submission"}}, "get": {"scopes": ["https://www.googleapis.com/auth/moderator"], "parameters": {"lang": {"type": "string", "location": "query"}, "seriesId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}, "includeVotes": {"type": "boolean", "location": "query"}, "submissionId": {"format": "uint32", "required": true, "type": "integer", "location": "path"}}, "id": "moderator.submissions.get", "httpMethod": "GET", "path": "series/{seriesId}/submissions/{submissionId}", "response": {"$ref": "Submission"}}}}', true));

  }
}

class ModeratorTopicsResourcePartial extends apiModel {
  protected $__idType = 'ModeratorTopicsResourcePartialId';
  protected $__idDataType = '';
  public $id;
  public function setId(ModeratorTopicsResourcePartialId $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
}

class ModeratorTopicsResourcePartialId extends apiModel {
  public $seriesId;
  public $topicId;
  public function setSeriesId($seriesId) {
    $this->seriesId = $seriesId;
  }
  public function getSeriesId() {
    return $this->seriesId;
  }
  public function setTopicId($topicId) {
    $this->topicId = $topicId;
  }
  public function getTopicId() {
    return $this->topicId;
  }
}

class ModeratorVotesResourcePartial extends apiModel {
  public $vote;
  public $flag;
  public function setVote($vote) {
    $this->vote = $vote;
  }
  public function getVote() {
    return $this->vote;
  }
  public function setFlag($flag) {
    $this->flag = $flag;
  }
  public function getFlag() {
    return $this->flag;
  }
}

class Profile extends apiModel {
  public $kind;
  protected $__attributionType = 'ProfileAttribution';
  protected $__attributionDataType = '';
  public $attribution;
  protected $__idType = 'ProfileId';
  protected $__idDataType = '';
  public $id;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setAttribution(ProfileAttribution $attribution) {
    $this->attribution = $attribution;
  }
  public function getAttribution() {
    return $this->attribution;
  }
  public function setId(ProfileId $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
}

class ProfileAttribution extends apiModel {
  protected $__geoType = 'ProfileAttributionGeo';
  protected $__geoDataType = '';
  public $geo;
  public $displayName;
  public $location;
  public $avatarUrl;
  public function setGeo(ProfileAttributionGeo $geo) {
    $this->geo = $geo;
  }
  public function getGeo() {
    return $this->geo;
  }
  public function setDisplayName($displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setLocation($location) {
    $this->location = $location;
  }
  public function getLocation() {
    return $this->location;
  }
  public function setAvatarUrl($avatarUrl) {
    $this->avatarUrl = $avatarUrl;
  }
  public function getAvatarUrl() {
    return $this->avatarUrl;
  }
}

class ProfileAttributionGeo extends apiModel {
  public $latitude;
  public $location;
  public $longitude;
  public function setLatitude($latitude) {
    $this->latitude = $latitude;
  }
  public function getLatitude() {
    return $this->latitude;
  }
  public function setLocation($location) {
    $this->location = $location;
  }
  public function getLocation() {
    return $this->location;
  }
  public function setLongitude($longitude) {
    $this->longitude = $longitude;
  }
  public function getLongitude() {
    return $this->longitude;
  }
}

class ProfileId extends apiModel {
  public $user;
  public function setUser($user) {
    $this->user = $user;
  }
  public function getUser() {
    return $this->user;
  }
}

class Series extends apiModel {
  public $kind;
  public $description;
  protected $__rulesType = 'SeriesRules';
  protected $__rulesDataType = '';
  public $rules;
  public $unauthVotingAllowed;
  public $videoSubmissionAllowed;
  public $name;
  public $numTopics;
  public $anonymousSubmissionAllowed;
  public $unauthSubmissionAllowed;
  protected $__idType = 'SeriesId';
  protected $__idDataType = '';
  public $id;
  protected $__countersType = 'SeriesCounters';
  protected $__countersDataType = '';
  public $counters;
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
  public function setRules(SeriesRules $rules) {
    $this->rules = $rules;
  }
  public function getRules() {
    return $this->rules;
  }
  public function setUnauthVotingAllowed($unauthVotingAllowed) {
    $this->unauthVotingAllowed = $unauthVotingAllowed;
  }
  public function getUnauthVotingAllowed() {
    return $this->unauthVotingAllowed;
  }
  public function setVideoSubmissionAllowed($videoSubmissionAllowed) {
    $this->videoSubmissionAllowed = $videoSubmissionAllowed;
  }
  public function getVideoSubmissionAllowed() {
    return $this->videoSubmissionAllowed;
  }
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setNumTopics($numTopics) {
    $this->numTopics = $numTopics;
  }
  public function getNumTopics() {
    return $this->numTopics;
  }
  public function setAnonymousSubmissionAllowed($anonymousSubmissionAllowed) {
    $this->anonymousSubmissionAllowed = $anonymousSubmissionAllowed;
  }
  public function getAnonymousSubmissionAllowed() {
    return $this->anonymousSubmissionAllowed;
  }
  public function setUnauthSubmissionAllowed($unauthSubmissionAllowed) {
    $this->unauthSubmissionAllowed = $unauthSubmissionAllowed;
  }
  public function getUnauthSubmissionAllowed() {
    return $this->unauthSubmissionAllowed;
  }
  public function setId(SeriesId $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setCounters(SeriesCounters $counters) {
    $this->counters = $counters;
  }
  public function getCounters() {
    return $this->counters;
  }
}

class SeriesCounters extends apiModel {
  public $users;
  public $noneVotes;
  public $videoSubmissions;
  public $minusVotes;
  public $anonymousSubmissions;
  public $submissions;
  public $plusVotes;
  public function setUsers($users) {
    $this->users = $users;
  }
  public function getUsers() {
    return $this->users;
  }
  public function setNoneVotes($noneVotes) {
    $this->noneVotes = $noneVotes;
  }
  public function getNoneVotes() {
    return $this->noneVotes;
  }
  public function setVideoSubmissions($videoSubmissions) {
    $this->videoSubmissions = $videoSubmissions;
  }
  public function getVideoSubmissions() {
    return $this->videoSubmissions;
  }
  public function setMinusVotes($minusVotes) {
    $this->minusVotes = $minusVotes;
  }
  public function getMinusVotes() {
    return $this->minusVotes;
  }
  public function setAnonymousSubmissions($anonymousSubmissions) {
    $this->anonymousSubmissions = $anonymousSubmissions;
  }
  public function getAnonymousSubmissions() {
    return $this->anonymousSubmissions;
  }
  public function setSubmissions($submissions) {
    $this->submissions = $submissions;
  }
  public function getSubmissions() {
    return $this->submissions;
  }
  public function setPlusVotes($plusVotes) {
    $this->plusVotes = $plusVotes;
  }
  public function getPlusVotes() {
    return $this->plusVotes;
  }
}

class SeriesId extends apiModel {
  public $seriesId;
  public function setSeriesId($seriesId) {
    $this->seriesId = $seriesId;
  }
  public function getSeriesId() {
    return $this->seriesId;
  }
}

class SeriesList extends apiModel {
  protected $__itemsType = 'Series';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(Series) */ $items) {
    $this->assertIsArray($items, 'Series', __METHOD__);
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
}

class SeriesRules extends apiModel {
  protected $__votesType = 'SeriesRulesVotes';
  protected $__votesDataType = '';
  public $votes;
  protected $__submissionsType = 'SeriesRulesSubmissions';
  protected $__submissionsDataType = '';
  public $submissions;
  public function setVotes(SeriesRulesVotes $votes) {
    $this->votes = $votes;
  }
  public function getVotes() {
    return $this->votes;
  }
  public function setSubmissions(SeriesRulesSubmissions $submissions) {
    $this->submissions = $submissions;
  }
  public function getSubmissions() {
    return $this->submissions;
  }
}

class SeriesRulesSubmissions extends apiModel {
  public $close;
  public $open;
  public function setClose($close) {
    $this->close = $close;
  }
  public function getClose() {
    return $this->close;
  }
  public function setOpen($open) {
    $this->open = $open;
  }
  public function getOpen() {
    return $this->open;
  }
}

class SeriesRulesVotes extends apiModel {
  public $close;
  public $open;
  public function setClose($close) {
    $this->close = $close;
  }
  public function getClose() {
    return $this->close;
  }
  public function setOpen($open) {
    $this->open = $open;
  }
  public function getOpen() {
    return $this->open;
  }
}

class Submission extends apiModel {
  public $kind;
  protected $__attributionType = 'SubmissionAttribution';
  protected $__attributionDataType = '';
  public $attribution;
  public $created;
  public $text;
  protected $__topicsType = 'ModeratorTopicsResourcePartial';
  protected $__topicsDataType = 'array';
  public $topics;
  public $author;
  protected $__translationsType = 'SubmissionTranslations';
  protected $__translationsDataType = 'array';
  public $translations;
  protected $__parentSubmissionIdType = 'SubmissionParentSubmissionId';
  protected $__parentSubmissionIdDataType = '';
  public $parentSubmissionId;
  protected $__voteType = 'ModeratorVotesResourcePartial';
  protected $__voteDataType = '';
  public $vote;
  public $attachmentUrl;
  protected $__geoType = 'SubmissionGeo';
  protected $__geoDataType = '';
  public $geo;
  protected $__idType = 'SubmissionId';
  protected $__idDataType = '';
  public $id;
  protected $__countersType = 'SubmissionCounters';
  protected $__countersDataType = '';
  public $counters;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setAttribution(SubmissionAttribution $attribution) {
    $this->attribution = $attribution;
  }
  public function getAttribution() {
    return $this->attribution;
  }
  public function setCreated($created) {
    $this->created = $created;
  }
  public function getCreated() {
    return $this->created;
  }
  public function setText($text) {
    $this->text = $text;
  }
  public function getText() {
    return $this->text;
  }
  public function setTopics(/* array(ModeratorTopicsResourcePartial) */ $topics) {
    $this->assertIsArray($topics, 'ModeratorTopicsResourcePartial', __METHOD__);
    $this->topics = $topics;
  }
  public function getTopics() {
    return $this->topics;
  }
  public function setAuthor($author) {
    $this->author = $author;
  }
  public function getAuthor() {
    return $this->author;
  }
  public function setTranslations(/* array(SubmissionTranslations) */ $translations) {
    $this->assertIsArray($translations, 'SubmissionTranslations', __METHOD__);
    $this->translations = $translations;
  }
  public function getTranslations() {
    return $this->translations;
  }
  public function setParentSubmissionId(SubmissionParentSubmissionId $parentSubmissionId) {
    $this->parentSubmissionId = $parentSubmissionId;
  }
  public function getParentSubmissionId() {
    return $this->parentSubmissionId;
  }
  public function setVote(ModeratorVotesResourcePartial $vote) {
    $this->vote = $vote;
  }
  public function getVote() {
    return $this->vote;
  }
  public function setAttachmentUrl($attachmentUrl) {
    $this->attachmentUrl = $attachmentUrl;
  }
  public function getAttachmentUrl() {
    return $this->attachmentUrl;
  }
  public function setGeo(SubmissionGeo $geo) {
    $this->geo = $geo;
  }
  public function getGeo() {
    return $this->geo;
  }
  public function setId(SubmissionId $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setCounters(SubmissionCounters $counters) {
    $this->counters = $counters;
  }
  public function getCounters() {
    return $this->counters;
  }
}

class SubmissionAttribution extends apiModel {
  public $displayName;
  public $location;
  public $avatarUrl;
  public function setDisplayName($displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setLocation($location) {
    $this->location = $location;
  }
  public function getLocation() {
    return $this->location;
  }
  public function setAvatarUrl($avatarUrl) {
    $this->avatarUrl = $avatarUrl;
  }
  public function getAvatarUrl() {
    return $this->avatarUrl;
  }
}

class SubmissionCounters extends apiModel {
  public $noneVotes;
  public $minusVotes;
  public $plusVotes;
  public function setNoneVotes($noneVotes) {
    $this->noneVotes = $noneVotes;
  }
  public function getNoneVotes() {
    return $this->noneVotes;
  }
  public function setMinusVotes($minusVotes) {
    $this->minusVotes = $minusVotes;
  }
  public function getMinusVotes() {
    return $this->minusVotes;
  }
  public function setPlusVotes($plusVotes) {
    $this->plusVotes = $plusVotes;
  }
  public function getPlusVotes() {
    return $this->plusVotes;
  }
}

class SubmissionGeo extends apiModel {
  public $latitude;
  public $location;
  public $longitude;
  public function setLatitude($latitude) {
    $this->latitude = $latitude;
  }
  public function getLatitude() {
    return $this->latitude;
  }
  public function setLocation($location) {
    $this->location = $location;
  }
  public function getLocation() {
    return $this->location;
  }
  public function setLongitude($longitude) {
    $this->longitude = $longitude;
  }
  public function getLongitude() {
    return $this->longitude;
  }
}

class SubmissionId extends apiModel {
  public $seriesId;
  public $submissionId;
  public function setSeriesId($seriesId) {
    $this->seriesId = $seriesId;
  }
  public function getSeriesId() {
    return $this->seriesId;
  }
  public function setSubmissionId($submissionId) {
    $this->submissionId = $submissionId;
  }
  public function getSubmissionId() {
    return $this->submissionId;
  }
}

class SubmissionList extends apiModel {
  protected $__itemsType = 'Submission';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(Submission) */ $items) {
    $this->assertIsArray($items, 'Submission', __METHOD__);
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
}

class SubmissionParentSubmissionId extends apiModel {
  public $seriesId;
  public $submissionId;
  public function setSeriesId($seriesId) {
    $this->seriesId = $seriesId;
  }
  public function getSeriesId() {
    return $this->seriesId;
  }
  public function setSubmissionId($submissionId) {
    $this->submissionId = $submissionId;
  }
  public function getSubmissionId() {
    return $this->submissionId;
  }
}

class SubmissionTranslations extends apiModel {
  public $lang;
  public $text;
  public function setLang($lang) {
    $this->lang = $lang;
  }
  public function getLang() {
    return $this->lang;
  }
  public function setText($text) {
    $this->text = $text;
  }
  public function getText() {
    return $this->text;
  }
}

class Tag extends apiModel {
  public $text;
  public $kind;
  protected $__idType = 'TagId';
  protected $__idDataType = '';
  public $id;
  public function setText($text) {
    $this->text = $text;
  }
  public function getText() {
    return $this->text;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setId(TagId $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
}

class TagId extends apiModel {
  public $seriesId;
  public $tagId;
  public $submissionId;
  public function setSeriesId($seriesId) {
    $this->seriesId = $seriesId;
  }
  public function getSeriesId() {
    return $this->seriesId;
  }
  public function setTagId($tagId) {
    $this->tagId = $tagId;
  }
  public function getTagId() {
    return $this->tagId;
  }
  public function setSubmissionId($submissionId) {
    $this->submissionId = $submissionId;
  }
  public function getSubmissionId() {
    return $this->submissionId;
  }
}

class TagList extends apiModel {
  protected $__itemsType = 'Tag';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(Tag) */ $items) {
    $this->assertIsArray($items, 'Tag', __METHOD__);
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
}

class Topic extends apiModel {
  public $kind;
  public $description;
  protected $__rulesType = 'TopicRules';
  protected $__rulesDataType = '';
  public $rules;
  protected $__featuredSubmissionType = 'Submission';
  protected $__featuredSubmissionDataType = '';
  public $featuredSubmission;
  public $presenter;
  protected $__countersType = 'TopicCounters';
  protected $__countersDataType = '';
  public $counters;
  protected $__idType = 'TopicId';
  protected $__idDataType = '';
  public $id;
  public $name;
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
  public function setRules(TopicRules $rules) {
    $this->rules = $rules;
  }
  public function getRules() {
    return $this->rules;
  }
  public function setFeaturedSubmission(Submission $featuredSubmission) {
    $this->featuredSubmission = $featuredSubmission;
  }
  public function getFeaturedSubmission() {
    return $this->featuredSubmission;
  }
  public function setPresenter($presenter) {
    $this->presenter = $presenter;
  }
  public function getPresenter() {
    return $this->presenter;
  }
  public function setCounters(TopicCounters $counters) {
    $this->counters = $counters;
  }
  public function getCounters() {
    return $this->counters;
  }
  public function setId(TopicId $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
}

class TopicCounters extends apiModel {
  public $users;
  public $noneVotes;
  public $videoSubmissions;
  public $minusVotes;
  public $submissions;
  public $plusVotes;
  public function setUsers($users) {
    $this->users = $users;
  }
  public function getUsers() {
    return $this->users;
  }
  public function setNoneVotes($noneVotes) {
    $this->noneVotes = $noneVotes;
  }
  public function getNoneVotes() {
    return $this->noneVotes;
  }
  public function setVideoSubmissions($videoSubmissions) {
    $this->videoSubmissions = $videoSubmissions;
  }
  public function getVideoSubmissions() {
    return $this->videoSubmissions;
  }
  public function setMinusVotes($minusVotes) {
    $this->minusVotes = $minusVotes;
  }
  public function getMinusVotes() {
    return $this->minusVotes;
  }
  public function setSubmissions($submissions) {
    $this->submissions = $submissions;
  }
  public function getSubmissions() {
    return $this->submissions;
  }
  public function setPlusVotes($plusVotes) {
    $this->plusVotes = $plusVotes;
  }
  public function getPlusVotes() {
    return $this->plusVotes;
  }
}

class TopicId extends apiModel {
  public $seriesId;
  public $topicId;
  public function setSeriesId($seriesId) {
    $this->seriesId = $seriesId;
  }
  public function getSeriesId() {
    return $this->seriesId;
  }
  public function setTopicId($topicId) {
    $this->topicId = $topicId;
  }
  public function getTopicId() {
    return $this->topicId;
  }
}

class TopicList extends apiModel {
  protected $__itemsType = 'Topic';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(Topic) */ $items) {
    $this->assertIsArray($items, 'Topic', __METHOD__);
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
}

class TopicRules extends apiModel {
  protected $__votesType = 'TopicRulesVotes';
  protected $__votesDataType = '';
  public $votes;
  protected $__submissionsType = 'TopicRulesSubmissions';
  protected $__submissionsDataType = '';
  public $submissions;
  public function setVotes(TopicRulesVotes $votes) {
    $this->votes = $votes;
  }
  public function getVotes() {
    return $this->votes;
  }
  public function setSubmissions(TopicRulesSubmissions $submissions) {
    $this->submissions = $submissions;
  }
  public function getSubmissions() {
    return $this->submissions;
  }
}

class TopicRulesSubmissions extends apiModel {
  public $close;
  public $open;
  public function setClose($close) {
    $this->close = $close;
  }
  public function getClose() {
    return $this->close;
  }
  public function setOpen($open) {
    $this->open = $open;
  }
  public function getOpen() {
    return $this->open;
  }
}

class TopicRulesVotes extends apiModel {
  public $close;
  public $open;
  public function setClose($close) {
    $this->close = $close;
  }
  public function getClose() {
    return $this->close;
  }
  public function setOpen($open) {
    $this->open = $open;
  }
  public function getOpen() {
    return $this->open;
  }
}

class Vote extends apiModel {
  public $vote;
  public $flag;
  protected $__idType = 'VoteId';
  protected $__idDataType = '';
  public $id;
  public $kind;
  public function setVote($vote) {
    $this->vote = $vote;
  }
  public function getVote() {
    return $this->vote;
  }
  public function setFlag($flag) {
    $this->flag = $flag;
  }
  public function getFlag() {
    return $this->flag;
  }
  public function setId(VoteId $id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
}

class VoteId extends apiModel {
  public $seriesId;
  public $submissionId;
  public function setSeriesId($seriesId) {
    $this->seriesId = $seriesId;
  }
  public function getSeriesId() {
    return $this->seriesId;
  }
  public function setSubmissionId($submissionId) {
    $this->submissionId = $submissionId;
  }
  public function getSubmissionId() {
    return $this->submissionId;
  }
}

class VoteList extends apiModel {
  protected $__itemsType = 'Vote';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(Vote) */ $items) {
    $this->assertIsArray($items, 'Vote', __METHOD__);
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
}
