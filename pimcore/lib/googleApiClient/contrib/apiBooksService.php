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
   * The "layers" collection of methods.
   * Typical usage is:
   *  <code>
   *   $booksService = new apiBooksService(...);
   *   $layers = $booksService->layers;
   *  </code>
   */
  class LayersServiceResource extends apiServiceResource {


    /**
     * List the layer summaries for a volume. (layers.list)
     *
     * @param string $volumeId The volume to retrieve layers for.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string pageToken The value of the nextToken from the previous page.
     * @opt_param string contentVersion The content version for the requested volume.
     * @opt_param string maxResults Maximum number of results to return
     * @opt_param string source String to identify the originator of this request.
     * @return Layersummaries
     */
    public function listLayers($volumeId, $optParams = array()) {
      $params = array('volumeId' => $volumeId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Layersummaries($data);
      } else {
        return $data;
      }
    }
    /**
     * Gets the layer summary for a volume. (layers.get)
     *
     * @param string $summaryId The ID for the layer to get the summary for.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string source String to identify the originator of this request.
     * @return Layersummary
     */
    public function get($summaryId, $optParams = array()) {
      $params = array('summaryId' => $summaryId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Layersummary($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "annotationData" collection of methods.
   * Typical usage is:
   *  <code>
   *   $booksService = new apiBooksService(...);
   *   $annotationData = $booksService->annotationData;
   *  </code>
   */
  class LayersAnnotationDataServiceResource extends apiServiceResource {


    /**
     * Gets the annotation data for a volume and layer. (annotationData.list)
     *
     * @param string $volumeId The volume to retrieve annotation data for.
     * @param string $layerId The ID for the layer to get the annotation data.
     * @param string $contentVersion The content version for the requested volume.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string source String to identify the originator of this request.
     * @opt_param string locale The locale information for the data. ISO-639-1 language and ISO-3166-1 country code. Ex: 'en_US'.
     * @opt_param int h The requested pixel height for any images. If height is provided width must also be provided.
     * @opt_param string updatedMax RFC 3339 timestamp to restrict to items updated prior to this timestamp (exclusive).
     * @opt_param string maxResults Maximum number of results to return
     * @opt_param string annotationDataId The list of Annotation Data Ids to retrieve. Pagination is ignored if this is set.
     * @opt_param string pageToken The value of the nextToken from the previous page.
     * @opt_param int w The requested pixel width for any images. If width is provided height must also be provided.
     * @opt_param string updatedMin RFC 3339 timestamp to restrict to items updated since this timestamp (inclusive).
     * @return Annotationsdata
     */
    public function listLayersAnnotationData($volumeId, $layerId, $contentVersion, $optParams = array()) {
      $params = array('volumeId' => $volumeId, 'layerId' => $layerId, 'contentVersion' => $contentVersion);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Annotationsdata($data);
      } else {
        return $data;
      }
    }
    /**
     * Gets the annotation data. (annotationData.get)
     *
     * @param string $volumeId The volume to retrieve annotations for.
     * @param string $layerId The ID for the layer to get the annotations.
     * @param string $annotationDataId The ID of the annotation data to retrieve.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string locale The locale information for the data. ISO-639-1 language and ISO-3166-1 country code. Ex: 'en_US'.
     * @opt_param int h The requested pixel height for any images. If height is provided width must also be provided.
     * @opt_param string source String to identify the originator of this request.
     * @opt_param int w The requested pixel width for any images. If width is provided height must also be provided.
     * @return Annotationdata
     */
    public function get($volumeId, $layerId, $annotationDataId, $optParams = array()) {
      $params = array('volumeId' => $volumeId, 'layerId' => $layerId, 'annotationDataId' => $annotationDataId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Annotationdata($data);
      } else {
        return $data;
      }
    }
  }
  /**
   * The "volumeAnnotations" collection of methods.
   * Typical usage is:
   *  <code>
   *   $booksService = new apiBooksService(...);
   *   $volumeAnnotations = $booksService->volumeAnnotations;
   *  </code>
   */
  class LayersVolumeAnnotationsServiceResource extends apiServiceResource {


    /**
     * Gets the volume annotations for a volume and layer. (volumeAnnotations.list)
     *
     * @param string $volumeId The volume to retrieve annotations for.
     * @param string $layerId The ID for the layer to get the annotations.
     * @param string $contentVersion The content version for the requested volume.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param bool showDeleted Set to true to return deleted annotations. updatedMin must be in the request to use this. Defaults to false.
     * @opt_param string endPosition The end position to end retrieving data from.
     * @opt_param string endOffset The end offset to end retrieving data from.
     * @opt_param string locale The locale information for the data. ISO-639-1 language and ISO-3166-1 country code. Ex: 'en_US'.
     * @opt_param string updatedMin RFC 3339 timestamp to restrict to items updated since this timestamp (inclusive).
     * @opt_param string updatedMax RFC 3339 timestamp to restrict to items updated prior to this timestamp (exclusive).
     * @opt_param string maxResults Maximum number of results to return
     * @opt_param string pageToken The value of the nextToken from the previous page.
     * @opt_param string source String to identify the originator of this request.
     * @opt_param string startOffset The start offset to start retrieving data from.
     * @opt_param string startPosition The start position to start retrieving data from.
     * @return Volumeannotations
     */
    public function listLayersVolumeAnnotations($volumeId, $layerId, $contentVersion, $optParams = array()) {
      $params = array('volumeId' => $volumeId, 'layerId' => $layerId, 'contentVersion' => $contentVersion);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Volumeannotations($data);
      } else {
        return $data;
      }
    }
    /**
     * Gets the volume annotation. (volumeAnnotations.get)
     *
     * @param string $volumeId The volume to retrieve annotations for.
     * @param string $layerId The ID for the layer to get the annotations.
     * @param string $annotationId The ID of the volume annotation to retrieve.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string locale The locale information for the data. ISO-639-1 language and ISO-3166-1 country code. Ex: 'en_US'.
     * @opt_param string source String to identify the originator of this request.
     * @return Volumeannotation
     */
    public function get($volumeId, $layerId, $annotationId, $optParams = array()) {
      $params = array('volumeId' => $volumeId, 'layerId' => $layerId, 'annotationId' => $annotationId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Volumeannotation($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "bookshelves" collection of methods.
   * Typical usage is:
   *  <code>
   *   $booksService = new apiBooksService(...);
   *   $bookshelves = $booksService->bookshelves;
   *  </code>
   */
  class BookshelvesServiceResource extends apiServiceResource {


    /**
     * Retrieves a list of public bookshelves for the specified user. (bookshelves.list)
     *
     * @param string $userId ID of user for whom to retrieve bookshelves.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string source String to identify the originator of this request.
     * @return Bookshelves
     */
    public function listBookshelves($userId, $optParams = array()) {
      $params = array('userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Bookshelves($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves metadata for a specific bookshelf for the specified user. (bookshelves.get)
     *
     * @param string $userId ID of user for whom to retrieve bookshelves.
     * @param string $shelf ID of bookshelf to retrieve.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string source String to identify the originator of this request.
     * @return Bookshelf
     */
    public function get($userId, $shelf, $optParams = array()) {
      $params = array('userId' => $userId, 'shelf' => $shelf);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Bookshelf($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "volumes" collection of methods.
   * Typical usage is:
   *  <code>
   *   $booksService = new apiBooksService(...);
   *   $volumes = $booksService->volumes;
   *  </code>
   */
  class BookshelvesVolumesServiceResource extends apiServiceResource {


    /**
     * Retrieves volumes in a specific bookshelf for the specified user. (volumes.list)
     *
     * @param string $userId ID of user for whom to retrieve bookshelf volumes.
     * @param string $shelf ID of bookshelf to retrieve volumes.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param bool showPreorders Set to true to show pre-ordered books. Defaults to false.
     * @opt_param string maxResults Maximum number of results to return
     * @opt_param string source String to identify the originator of this request.
     * @opt_param string startIndex Index of the first element to return (starts at 0)
     * @return Volumes
     */
    public function listBookshelvesVolumes($userId, $shelf, $optParams = array()) {
      $params = array('userId' => $userId, 'shelf' => $shelf);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Volumes($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "myconfig" collection of methods.
   * Typical usage is:
   *  <code>
   *   $booksService = new apiBooksService(...);
   *   $myconfig = $booksService->myconfig;
   *  </code>
   */
  class MyconfigServiceResource extends apiServiceResource {


    /**
     * Release downloaded content access restriction. (myconfig.releaseDownloadAccess)
     *
     * @param string $volumeIds The volume(s) to release restrictions for.
     * @param string $cpksver The device/version ID from which to release the restriction.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string locale ISO-639-1, ISO-3166-1 codes for message localization, i.e. en_US.
     * @opt_param string source String to identify the originator of this request.
     * @return DownloadAccesses
     */
    public function releaseDownloadAccess($volumeIds, $cpksver, $optParams = array()) {
      $params = array('volumeIds' => $volumeIds, 'cpksver' => $cpksver);
      $params = array_merge($params, $optParams);
      $data = $this->__call('releaseDownloadAccess', array($params));
      if ($this->useObjects()) {
        return new DownloadAccesses($data);
      } else {
        return $data;
      }
    }
    /**
     * Request concurrent and download access restrictions. (myconfig.requestAccess)
     *
     * @param string $source String to identify the originator of this request.
     * @param string $volumeId The volume to request concurrent/download restrictions for.
     * @param string $nonce The client nonce value.
     * @param string $cpksver The device/version ID from which to request the restrictions.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string locale ISO-639-1, ISO-3166-1 codes for message localization, i.e. en_US.
     * @return RequestAccess
     */
    public function requestAccess($source, $volumeId, $nonce, $cpksver, $optParams = array()) {
      $params = array('source' => $source, 'volumeId' => $volumeId, 'nonce' => $nonce, 'cpksver' => $cpksver);
      $params = array_merge($params, $optParams);
      $data = $this->__call('requestAccess', array($params));
      if ($this->useObjects()) {
        return new RequestAccess($data);
      } else {
        return $data;
      }
    }
    /**
     * Request downloaded content access for specified volumes on the My eBooks shelf.
     * (myconfig.syncVolumeLicenses)
     *
     * @param string $source String to identify the originator of this request.
     * @param string $nonce The client nonce value.
     * @param string $cpksver The device/version ID from which to release the restriction.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string locale ISO-639-1, ISO-3166-1 codes for message localization, i.e. en_US.
     * @opt_param bool showPreorders Set to true to show pre-ordered books. Defaults to false.
     * @opt_param string volumeIds The volume(s) to request download restrictions for.
     * @return Volumes
     */
    public function syncVolumeLicenses($source, $nonce, $cpksver, $optParams = array()) {
      $params = array('source' => $source, 'nonce' => $nonce, 'cpksver' => $cpksver);
      $params = array_merge($params, $optParams);
      $data = $this->__call('syncVolumeLicenses', array($params));
      if ($this->useObjects()) {
        return new Volumes($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "volumes" collection of methods.
   * Typical usage is:
   *  <code>
   *   $booksService = new apiBooksService(...);
   *   $volumes = $booksService->volumes;
   *  </code>
   */
  class VolumesServiceResource extends apiServiceResource {


    /**
     * Performs a book search. (volumes.list)
     *
     * @param string $q Full-text search query string.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string orderBy Sort search results.
     * @opt_param string projection Restrict information returned to a set of selected fields.
     * @opt_param string libraryRestrict Restrict search to this user's library.
     * @opt_param string langRestrict Restrict results to books with this language code.
     * @opt_param bool showPreorders Set to true to show books available for preorder. Defaults to false.
     * @opt_param string printType Restrict to books or magazines.
     * @opt_param string maxResults Maximum number of results to return.
     * @opt_param string filter Filter search results.
     * @opt_param string source String to identify the originator of this request.
     * @opt_param string startIndex Index of the first result to return (starts at 0)
     * @opt_param string download Restrict to volumes by download availability.
     * @opt_param string partner Restrict and brand results for partner ID.
     * @return Volumes
     */
    public function listVolumes($q, $optParams = array()) {
      $params = array('q' => $q);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Volumes($data);
      } else {
        return $data;
      }
    }
    /**
     * Gets volume information for a single volume. (volumes.get)
     *
     * @param string $volumeId ID of volume to retrieve.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string partner Brand results for partner ID.
     * @opt_param string projection Restrict information returned to a set of selected fields.
     * @opt_param string source String to identify the originator of this request.
     * @return Volume
     */
    public function get($volumeId, $optParams = array()) {
      $params = array('volumeId' => $volumeId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Volume($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "mylibrary" collection of methods.
   * Typical usage is:
   *  <code>
   *   $booksService = new apiBooksService(...);
   *   $mylibrary = $booksService->mylibrary;
   *  </code>
   */
  class MylibraryServiceResource extends apiServiceResource {


  }

  /**
   * The "bookshelves" collection of methods.
   * Typical usage is:
   *  <code>
   *   $booksService = new apiBooksService(...);
   *   $bookshelves = $booksService->bookshelves;
   *  </code>
   */
  class MylibraryBookshelvesServiceResource extends apiServiceResource {


    /**
     * Removes a volume from a bookshelf. (bookshelves.removeVolume)
     *
     * @param string $shelf ID of bookshelf from which to remove a volume.
     * @param string $volumeId ID of volume to remove.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string source String to identify the originator of this request.
     */
    public function removeVolume($shelf, $volumeId, $optParams = array()) {
      $params = array('shelf' => $shelf, 'volumeId' => $volumeId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('removeVolume', array($params));
      return $data;
    }
    /**
     * Retrieves metadata for a specific bookshelf belonging to the authenticated user.
     * (bookshelves.get)
     *
     * @param string $shelf ID of bookshelf to retrieve.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string source String to identify the originator of this request.
     * @return Bookshelf
     */
    public function get($shelf, $optParams = array()) {
      $params = array('shelf' => $shelf);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Bookshelf($data);
      } else {
        return $data;
      }
    }
    /**
     * Clears all volumes from a bookshelf. (bookshelves.clearVolumes)
     *
     * @param string $shelf ID of bookshelf from which to remove a volume.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string source String to identify the originator of this request.
     */
    public function clearVolumes($shelf, $optParams = array()) {
      $params = array('shelf' => $shelf);
      $params = array_merge($params, $optParams);
      $data = $this->__call('clearVolumes', array($params));
      return $data;
    }
    /**
     * Retrieves a list of bookshelves belonging to the authenticated user. (bookshelves.list)
     *
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string source String to identify the originator of this request.
     * @return Bookshelves
     */
    public function listMylibraryBookshelves($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Bookshelves($data);
      } else {
        return $data;
      }
    }
    /**
     * Adds a volume to a bookshelf. (bookshelves.addVolume)
     *
     * @param string $shelf ID of bookshelf to which to add a volume.
     * @param string $volumeId ID of volume to add.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string source String to identify the originator of this request.
     */
    public function addVolume($shelf, $volumeId, $optParams = array()) {
      $params = array('shelf' => $shelf, 'volumeId' => $volumeId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('addVolume', array($params));
      return $data;
    }
    /**
     * Moves a volume within a bookshelf. (bookshelves.moveVolume)
     *
     * @param string $shelf ID of bookshelf with the volume.
     * @param string $volumeId ID of volume to move.
     * @param int $volumePosition Position on shelf to move the item (0 puts the item before the current first item, 1 puts it between the first and the second and so on.)
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string source String to identify the originator of this request.
     */
    public function moveVolume($shelf, $volumeId, $volumePosition, $optParams = array()) {
      $params = array('shelf' => $shelf, 'volumeId' => $volumeId, 'volumePosition' => $volumePosition);
      $params = array_merge($params, $optParams);
      $data = $this->__call('moveVolume', array($params));
      return $data;
    }
  }

  /**
   * The "volumes" collection of methods.
   * Typical usage is:
   *  <code>
   *   $booksService = new apiBooksService(...);
   *   $volumes = $booksService->volumes;
   *  </code>
   */
  class MylibraryBookshelvesVolumesServiceResource extends apiServiceResource {


    /**
     * Gets volume information for volumes on a bookshelf. (volumes.list)
     *
     * @param string $shelf The bookshelf ID or name retrieve volumes for.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string projection Restrict information returned to a set of selected fields.
     * @opt_param bool showPreorders Set to true to show pre-ordered books. Defaults to false.
     * @opt_param string maxResults Maximum number of results to return
     * @opt_param string q Full-text search query string in this bookshelf.
     * @opt_param string source String to identify the originator of this request.
     * @opt_param string startIndex Index of the first element to return (starts at 0)
     * @return Volumes
     */
    public function listMylibraryBookshelvesVolumes($shelf, $optParams = array()) {
      $params = array('shelf' => $shelf);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Volumes($data);
      } else {
        return $data;
      }
    }
  }
  /**
   * The "readingpositions" collection of methods.
   * Typical usage is:
   *  <code>
   *   $booksService = new apiBooksService(...);
   *   $readingpositions = $booksService->readingpositions;
   *  </code>
   */
  class MylibraryReadingpositionsServiceResource extends apiServiceResource {


    /**
     * Sets my reading position information for a volume. (readingpositions.setPosition)
     *
     * @param string $volumeId ID of volume for which to update the reading position.
     * @param string $timestamp RFC 3339 UTC format timestamp associated with this reading position.
     * @param string $position Position string for the new volume reading position.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string source String to identify the originator of this request.
     * @opt_param string contentVersion Volume content version for which this reading position applies.
     * @opt_param string action Action that caused this reading position to be set.
     */
    public function setPosition($volumeId, $timestamp, $position, $optParams = array()) {
      $params = array('volumeId' => $volumeId, 'timestamp' => $timestamp, 'position' => $position);
      $params = array_merge($params, $optParams);
      $data = $this->__call('setPosition', array($params));
      return $data;
    }
    /**
     * Retrieves my reading position information for a volume. (readingpositions.get)
     *
     * @param string $volumeId ID of volume for which to retrieve a reading position.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string source String to identify the originator of this request.
     * @opt_param string contentVersion Volume content version for which this reading position is requested.
     * @return ReadingPosition
     */
    public function get($volumeId, $optParams = array()) {
      $params = array('volumeId' => $volumeId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new ReadingPosition($data);
      } else {
        return $data;
      }
    }
  }
  /**
   * The "annotations" collection of methods.
   * Typical usage is:
   *  <code>
   *   $booksService = new apiBooksService(...);
   *   $annotations = $booksService->annotations;
   *  </code>
   */
  class MylibraryAnnotationsServiceResource extends apiServiceResource {


    /**
     * Inserts a new annotation. (annotations.insert)
     *
     * @param Annotation $postBody
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string source String to identify the originator of this request.
     * @return Annotation
     */
    public function insert(Annotation $postBody, $optParams = array()) {
      $params = array('postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Annotation($data);
      } else {
        return $data;
      }
    }
    /**
     * Gets an annotation by its ID. (annotations.get)
     *
     * @param string $annotationId The ID for the annotation to retrieve.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string source String to identify the originator of this request.
     * @return Annotation
     */
    public function get($annotationId, $optParams = array()) {
      $params = array('annotationId' => $annotationId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Annotation($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves a list of annotations, possibly filtered. (annotations.list)
     *
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param bool showDeleted Set to true to return deleted annotations. updatedMin must be in the request to use this. Defaults to false.
     * @opt_param string updatedMin RFC 3339 timestamp to restrict to items updated since this timestamp (inclusive).
     * @opt_param string updatedMax RFC 3339 timestamp to restrict to items updated prior to this timestamp (exclusive).
     * @opt_param string volumeId The volume to restrict annotations to.
     * @opt_param string maxResults Maximum number of results to return
     * @opt_param string pageToken The value of the nextToken from the previous page.
     * @opt_param string pageIds The page ID(s) for the volume that is being queried.
     * @opt_param string contentVersion The content version for the requested volume.
     * @opt_param string source String to identify the originator of this request.
     * @opt_param string layerId The layer ID to limit annotation by.
     * @return Annotations
     */
    public function listMylibraryAnnotations($optParams = array()) {
      $params = array();
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Annotations($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates an existing annotation. (annotations.update)
     *
     * @param string $annotationId The ID for the annotation to update.
     * @param Annotation $postBody
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string source String to identify the originator of this request.
     * @return Annotation
     */
    public function update($annotationId, Annotation $postBody, $optParams = array()) {
      $params = array('annotationId' => $annotationId, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Annotation($data);
      } else {
        return $data;
      }
    }
    /**
     * Deletes an annotation. (annotations.delete)
     *
     * @param string $annotationId The ID for the annotation to delete.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string source String to identify the originator of this request.
     */
    public function delete($annotationId, $optParams = array()) {
      $params = array('annotationId' => $annotationId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
  }

/**
 * Service definition for Books (v1).
 *
 * <p>
 * Lets you search for books and manage your Google Books library.
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://code.google.com/apis/books/docs/v1/getting_started.html" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class apiBooksService extends apiService {
  public $layers;
  public $layers_annotationData;
  public $layers_volumeAnnotations;
  public $bookshelves;
  public $bookshelves_volumes;
  public $myconfig;
  public $volumes;
  public $mylibrary_bookshelves;
  public $mylibrary_bookshelves_volumes;
  public $mylibrary_readingpositions;
  public $mylibrary_annotations;
  /**
   * Constructs the internal representation of the Books service.
   *
   * @param apiClient apiClient
   */
  public function __construct(apiClient $apiClient) {
    $this->restBasePath = '/books/v1/';
    $this->version = 'v1';
    $this->serviceName = 'books';

    $apiClient->addService($this->serviceName, $this->version);
    $this->layers = new LayersServiceResource($this, $this->serviceName, 'layers', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"pageToken": {"type": "string", "location": "query"}, "contentVersion": {"type": "string", "location": "query"}, "volumeId": {"required": true, "type": "string", "location": "path"}, "maxResults": {"format": "uint32", "maximum": "40", "minimum": "0", "location": "query", "type": "integer"}, "source": {"type": "string", "location": "query"}}, "id": "books.layers.list", "httpMethod": "GET", "path": "volumes/{volumeId}/layersummary", "response": {"$ref": "Layersummaries"}}, "get": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"source": {"type": "string", "location": "query"}, "summaryId": {"required": true, "type": "string", "location": "path"}}, "id": "books.layers.get", "httpMethod": "GET", "path": "volumes/layersummary/{summaryId}", "response": {"$ref": "Layersummary"}}}}', true));
    $this->layers_annotationData = new LayersAnnotationDataServiceResource($this, $this->serviceName, 'annotationData', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"pageToken": {"type": "string", "location": "query"}, "updatedMax": {"type": "string", "location": "query"}, "locale": {"type": "string", "location": "query"}, "h": {"format": "int32", "type": "integer", "location": "query"}, "volumeId": {"required": true, "type": "string", "location": "path"}, "maxResults": {"format": "uint32", "maximum": "40", "minimum": "0", "location": "query", "type": "integer"}, "annotationDataId": {"repeated": true, "type": "string", "location": "query"}, "source": {"type": "string", "location": "query"}, "contentVersion": {"required": true, "type": "string", "location": "query"}, "w": {"format": "int32", "type": "integer", "location": "query"}, "layerId": {"required": true, "type": "string", "location": "path"}, "updatedMin": {"type": "string", "location": "query"}}, "id": "books.layers.annotationData.list", "httpMethod": "GET", "path": "volumes/{volumeId}/layers/{layerId}/data", "response": {"$ref": "Annotationsdata"}}, "get": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"locale": {"type": "string", "location": "query"}, "h": {"format": "int32", "type": "integer", "location": "query"}, "volumeId": {"required": true, "type": "string", "location": "path"}, "annotationDataId": {"required": true, "type": "string", "location": "path"}, "source": {"type": "string", "location": "query"}, "w": {"format": "int32", "type": "integer", "location": "query"}, "layerId": {"required": true, "type": "string", "location": "path"}}, "id": "books.layers.annotationData.get", "httpMethod": "GET", "path": "volumes/{volumeId}/layers/{layerId}/data/{annotationDataId}", "response": {"$ref": "Annotationdata"}}}}', true));
    $this->layers_volumeAnnotations = new LayersVolumeAnnotationsServiceResource($this, $this->serviceName, 'volumeAnnotations', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"pageToken": {"type": "string", "location": "query"}, "endPosition": {"type": "string", "location": "query"}, "updatedMax": {"type": "string", "location": "query"}, "locale": {"type": "string", "location": "query"}, "updatedMin": {"type": "string", "location": "query"}, "endOffset": {"type": "string", "location": "query"}, "volumeId": {"required": true, "type": "string", "location": "path"}, "maxResults": {"format": "uint32", "maximum": "40", "minimum": "0", "location": "query", "type": "integer"}, "showDeleted": {"type": "boolean", "location": "query"}, "contentVersion": {"required": true, "type": "string", "location": "query"}, "source": {"type": "string", "location": "query"}, "startOffset": {"type": "string", "location": "query"}, "layerId": {"required": true, "type": "string", "location": "path"}, "startPosition": {"type": "string", "location": "query"}}, "id": "books.layers.volumeAnnotations.list", "httpMethod": "GET", "path": "volumes/{volumeId}/layers/{layerId}", "response": {"$ref": "Volumeannotations"}}, "get": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"locale": {"type": "string", "location": "query"}, "source": {"type": "string", "location": "query"}, "annotationId": {"required": true, "type": "string", "location": "path"}, "volumeId": {"required": true, "type": "string", "location": "path"}, "layerId": {"required": true, "type": "string", "location": "path"}}, "id": "books.layers.volumeAnnotations.get", "httpMethod": "GET", "path": "volumes/{volumeId}/layers/{layerId}/annotations/{annotationId}", "response": {"$ref": "Volumeannotation"}}}}', true));
    $this->bookshelves = new BookshelvesServiceResource($this, $this->serviceName, 'bookshelves', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"source": {"type": "string", "location": "query"}, "userId": {"required": true, "type": "string", "location": "path"}}, "id": "books.bookshelves.list", "httpMethod": "GET", "path": "users/{userId}/bookshelves", "response": {"$ref": "Bookshelves"}}, "get": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"shelf": {"required": true, "type": "string", "location": "path"}, "userId": {"required": true, "type": "string", "location": "path"}, "source": {"type": "string", "location": "query"}}, "id": "books.bookshelves.get", "httpMethod": "GET", "path": "users/{userId}/bookshelves/{shelf}", "response": {"$ref": "Bookshelf"}}}}', true));
    $this->bookshelves_volumes = new BookshelvesVolumesServiceResource($this, $this->serviceName, 'volumes', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"shelf": {"required": true, "type": "string", "location": "path"}, "showPreorders": {"type": "boolean", "location": "query"}, "maxResults": {"format": "uint32", "minimum": "0", "type": "integer", "location": "query"}, "source": {"type": "string", "location": "query"}, "startIndex": {"format": "uint32", "minimum": "0", "type": "integer", "location": "query"}, "userId": {"required": true, "type": "string", "location": "path"}}, "id": "books.bookshelves.volumes.list", "httpMethod": "GET", "path": "users/{userId}/bookshelves/{shelf}/volumes", "response": {"$ref": "Volumes"}}}}', true));
    $this->myconfig = new MyconfigServiceResource($this, $this->serviceName, 'myconfig', json_decode('{"methods": {"releaseDownloadAccess": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"locale": {"type": "string", "location": "query"}, "source": {"type": "string", "location": "query"}, "cpksver": {"required": true, "type": "string", "location": "query"}, "volumeIds": {"repeated": true, "required": true, "type": "string", "location": "query"}}, "id": "books.myconfig.releaseDownloadAccess", "httpMethod": "POST", "path": "myconfig/releaseDownloadAccess", "response": {"$ref": "DownloadAccesses"}}, "requestAccess": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"locale": {"type": "string", "location": "query"}, "nonce": {"required": true, "type": "string", "location": "query"}, "source": {"required": true, "type": "string", "location": "query"}, "cpksver": {"required": true, "type": "string", "location": "query"}, "volumeId": {"required": true, "type": "string", "location": "query"}}, "id": "books.myconfig.requestAccess", "httpMethod": "POST", "path": "myconfig/requestAccess", "response": {"$ref": "RequestAccess"}}, "syncVolumeLicenses": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"nonce": {"required": true, "type": "string", "location": "query"}, "locale": {"type": "string", "location": "query"}, "showPreorders": {"type": "boolean", "location": "query"}, "cpksver": {"required": true, "type": "string", "location": "query"}, "source": {"required": true, "type": "string", "location": "query"}, "volumeIds": {"repeated": true, "type": "string", "location": "query"}}, "id": "books.myconfig.syncVolumeLicenses", "httpMethod": "POST", "path": "myconfig/syncVolumeLicenses", "response": {"$ref": "Volumes"}}}}', true));
    $this->volumes = new VolumesServiceResource($this, $this->serviceName, 'volumes', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"orderBy": {"enum": ["newest", "relevance"], "type": "string", "location": "query"}, "filter": {"enum": ["ebooks", "free-ebooks", "full", "paid-ebooks", "partial"], "type": "string", "location": "query"}, "projection": {"enum": ["full", "lite"], "type": "string", "location": "query"}, "libraryRestrict": {"enum": ["my-library", "no-restrict"], "type": "string", "location": "query"}, "langRestrict": {"type": "string", "location": "query"}, "printType": {"enum": ["all", "books", "magazines"], "type": "string", "location": "query"}, "showPreorders": {"type": "boolean", "location": "query"}, "maxResults": {"format": "uint32", "maximum": "40", "minimum": "0", "location": "query", "type": "integer"}, "q": {"required": true, "type": "string", "location": "query"}, "source": {"type": "string", "location": "query"}, "startIndex": {"format": "uint32", "minimum": "0", "type": "integer", "location": "query"}, "download": {"enum": ["epub"], "type": "string", "location": "query"}, "partner": {"type": "string", "location": "query"}}, "id": "books.volumes.list", "httpMethod": "GET", "path": "volumes", "response": {"$ref": "Volumes"}}, "get": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"source": {"type": "string", "location": "query"}, "partner": {"type": "string", "location": "query"}, "projection": {"enum": ["full", "lite"], "type": "string", "location": "query"}, "volumeId": {"required": true, "type": "string", "location": "path"}}, "id": "books.volumes.get", "httpMethod": "GET", "path": "volumes/{volumeId}", "response": {"$ref": "Volume"}}}}', true));
    $this->mylibrary_bookshelves = new MylibraryBookshelvesServiceResource($this, $this->serviceName, 'bookshelves', json_decode('{"methods": {"removeVolume": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"shelf": {"required": true, "type": "string", "location": "path"}, "volumeId": {"required": true, "type": "string", "location": "query"}, "source": {"type": "string", "location": "query"}}, "httpMethod": "POST", "path": "mylibrary/bookshelves/{shelf}/removeVolume", "id": "books.mylibrary.bookshelves.removeVolume"}, "get": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"shelf": {"required": true, "type": "string", "location": "path"}, "source": {"type": "string", "location": "query"}}, "id": "books.mylibrary.bookshelves.get", "httpMethod": "GET", "path": "mylibrary/bookshelves/{shelf}", "response": {"$ref": "Bookshelf"}}, "clearVolumes": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"shelf": {"required": true, "type": "string", "location": "path"}, "source": {"type": "string", "location": "query"}}, "httpMethod": "POST", "path": "mylibrary/bookshelves/{shelf}/clearVolumes", "id": "books.mylibrary.bookshelves.clearVolumes"}, "list": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"source": {"type": "string", "location": "query"}}, "response": {"$ref": "Bookshelves"}, "httpMethod": "GET", "path": "mylibrary/bookshelves", "id": "books.mylibrary.bookshelves.list"}, "addVolume": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"shelf": {"required": true, "type": "string", "location": "path"}, "volumeId": {"required": true, "type": "string", "location": "query"}, "source": {"type": "string", "location": "query"}}, "httpMethod": "POST", "path": "mylibrary/bookshelves/{shelf}/addVolume", "id": "books.mylibrary.bookshelves.addVolume"}, "moveVolume": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"source": {"type": "string", "location": "query"}, "shelf": {"required": true, "type": "string", "location": "path"}, "volumeId": {"required": true, "type": "string", "location": "query"}, "volumePosition": {"format": "int32", "required": true, "type": "integer", "location": "query"}}, "httpMethod": "POST", "path": "mylibrary/bookshelves/{shelf}/moveVolume", "id": "books.mylibrary.bookshelves.moveVolume"}}}', true));
    $this->mylibrary_bookshelves_volumes = new MylibraryBookshelvesVolumesServiceResource($this, $this->serviceName, 'volumes', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"projection": {"enum": ["full", "lite"], "type": "string", "location": "query"}, "shelf": {"required": true, "type": "string", "location": "path"}, "showPreorders": {"type": "boolean", "location": "query"}, "maxResults": {"format": "uint32", "minimum": "0", "type": "integer", "location": "query"}, "q": {"type": "string", "location": "query"}, "source": {"type": "string", "location": "query"}, "startIndex": {"format": "uint32", "minimum": "0", "type": "integer", "location": "query"}}, "id": "books.mylibrary.bookshelves.volumes.list", "httpMethod": "GET", "path": "mylibrary/bookshelves/{shelf}/volumes", "response": {"$ref": "Volumes"}}}}', true));
    $this->mylibrary_readingpositions = new MylibraryReadingpositionsServiceResource($this, $this->serviceName, 'readingpositions', json_decode('{"methods": {"setPosition": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"timestamp": {"required": true, "type": "string", "location": "query"}, "volumeId": {"required": true, "type": "string", "location": "path"}, "source": {"type": "string", "location": "query"}, "contentVersion": {"type": "string", "location": "query"}, "action": {"enum": ["bookmark", "chapter", "next-page", "prev-page", "scroll", "search"], "type": "string", "location": "query"}, "position": {"required": true, "type": "string", "location": "query"}}, "httpMethod": "POST", "path": "mylibrary/readingpositions/{volumeId}/setPosition", "id": "books.mylibrary.readingpositions.setPosition"}, "get": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"source": {"type": "string", "location": "query"}, "contentVersion": {"type": "string", "location": "query"}, "volumeId": {"required": true, "type": "string", "location": "path"}}, "id": "books.mylibrary.readingpositions.get", "httpMethod": "GET", "path": "mylibrary/readingpositions/{volumeId}", "response": {"$ref": "ReadingPosition"}}}}', true));
    $this->mylibrary_annotations = new MylibraryAnnotationsServiceResource($this, $this->serviceName, 'annotations', json_decode('{"methods": {"insert": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"source": {"type": "string", "location": "query"}}, "request": {"$ref": "Annotation"}, "id": "books.mylibrary.annotations.insert", "httpMethod": "POST", "path": "mylibrary/annotations", "response": {"$ref": "Annotation"}}, "delete": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"source": {"type": "string", "location": "query"}, "annotationId": {"required": true, "type": "string", "location": "path"}}, "httpMethod": "DELETE", "path": "mylibrary/annotations/{annotationId}", "id": "books.mylibrary.annotations.delete"}, "list": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"pageToken": {"type": "string", "location": "query"}, "updatedMax": {"type": "string", "location": "query"}, "updatedMin": {"type": "string", "location": "query"}, "volumeId": {"type": "string", "location": "query"}, "maxResults": {"format": "uint32", "maximum": "40", "minimum": "0", "location": "query", "type": "integer"}, "showDeleted": {"type": "boolean", "location": "query"}, "pageIds": {"repeated": true, "type": "string", "location": "query"}, "contentVersion": {"type": "string", "location": "query"}, "source": {"type": "string", "location": "query"}, "layerId": {"type": "string", "location": "query"}}, "response": {"$ref": "Annotations"}, "httpMethod": "GET", "path": "mylibrary/annotations", "id": "books.mylibrary.annotations.list"}, "update": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"source": {"type": "string", "location": "query"}, "annotationId": {"required": true, "type": "string", "location": "path"}}, "request": {"$ref": "Annotation"}, "id": "books.mylibrary.annotations.update", "httpMethod": "PUT", "path": "mylibrary/annotations/{annotationId}", "response": {"$ref": "Annotation"}}, "get": {"scopes": ["https://www.googleapis.com/auth/books"], "parameters": {"source": {"type": "string", "location": "query"}, "annotationId": {"required": true, "type": "string", "location": "path"}}, "id": "books.mylibrary.annotations.get", "httpMethod": "GET", "path": "mylibrary/annotations/{annotationId}", "response": {"$ref": "Annotation"}}}}', true));

  }
}

class Annotation extends apiModel {
  public $kind;
  public $updated;
  public $created;
  public $deleted;
  public $beforeSelectedText;
  protected $__currentVersionRangesType = 'AnnotationCurrentVersionRanges';
  protected $__currentVersionRangesDataType = '';
  public $currentVersionRanges;
  public $afterSelectedText;
  protected $__clientVersionRangesType = 'AnnotationClientVersionRanges';
  protected $__clientVersionRangesDataType = '';
  public $clientVersionRanges;
  public $volumeId;
  public $pageIds;
  public $layerId;
  public $selectedText;
  public $highlightStyle;
  public $data;
  public $id;
  public $selfLink;
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
  public function setCreated($created) {
    $this->created = $created;
  }
  public function getCreated() {
    return $this->created;
  }
  public function setDeleted($deleted) {
    $this->deleted = $deleted;
  }
  public function getDeleted() {
    return $this->deleted;
  }
  public function setBeforeSelectedText($beforeSelectedText) {
    $this->beforeSelectedText = $beforeSelectedText;
  }
  public function getBeforeSelectedText() {
    return $this->beforeSelectedText;
  }
  public function setCurrentVersionRanges(AnnotationCurrentVersionRanges $currentVersionRanges) {
    $this->currentVersionRanges = $currentVersionRanges;
  }
  public function getCurrentVersionRanges() {
    return $this->currentVersionRanges;
  }
  public function setAfterSelectedText($afterSelectedText) {
    $this->afterSelectedText = $afterSelectedText;
  }
  public function getAfterSelectedText() {
    return $this->afterSelectedText;
  }
  public function setClientVersionRanges(AnnotationClientVersionRanges $clientVersionRanges) {
    $this->clientVersionRanges = $clientVersionRanges;
  }
  public function getClientVersionRanges() {
    return $this->clientVersionRanges;
  }
  public function setVolumeId($volumeId) {
    $this->volumeId = $volumeId;
  }
  public function getVolumeId() {
    return $this->volumeId;
  }
  public function setPageIds(/* array(string) */ $pageIds) {
    $this->assertIsArray($pageIds, 'string', __METHOD__);
    $this->pageIds = $pageIds;
  }
  public function getPageIds() {
    return $this->pageIds;
  }
  public function setLayerId($layerId) {
    $this->layerId = $layerId;
  }
  public function getLayerId() {
    return $this->layerId;
  }
  public function setSelectedText($selectedText) {
    $this->selectedText = $selectedText;
  }
  public function getSelectedText() {
    return $this->selectedText;
  }
  public function setHighlightStyle($highlightStyle) {
    $this->highlightStyle = $highlightStyle;
  }
  public function getHighlightStyle() {
    return $this->highlightStyle;
  }
  public function setData($data) {
    $this->data = $data;
  }
  public function getData() {
    return $this->data;
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

class AnnotationClientVersionRanges extends apiModel {
  public $contentVersion;
  protected $__gbTextRangeType = 'BooksAnnotationsRange';
  protected $__gbTextRangeDataType = '';
  public $gbTextRange;
  protected $__cfiRangeType = 'BooksAnnotationsRange';
  protected $__cfiRangeDataType = '';
  public $cfiRange;
  protected $__gbImageRangeType = 'BooksAnnotationsRange';
  protected $__gbImageRangeDataType = '';
  public $gbImageRange;
  public function setContentVersion($contentVersion) {
    $this->contentVersion = $contentVersion;
  }
  public function getContentVersion() {
    return $this->contentVersion;
  }
  public function setGbTextRange(BooksAnnotationsRange $gbTextRange) {
    $this->gbTextRange = $gbTextRange;
  }
  public function getGbTextRange() {
    return $this->gbTextRange;
  }
  public function setCfiRange(BooksAnnotationsRange $cfiRange) {
    $this->cfiRange = $cfiRange;
  }
  public function getCfiRange() {
    return $this->cfiRange;
  }
  public function setGbImageRange(BooksAnnotationsRange $gbImageRange) {
    $this->gbImageRange = $gbImageRange;
  }
  public function getGbImageRange() {
    return $this->gbImageRange;
  }
}

class AnnotationCurrentVersionRanges extends apiModel {
  public $contentVersion;
  protected $__gbTextRangeType = 'BooksAnnotationsRange';
  protected $__gbTextRangeDataType = '';
  public $gbTextRange;
  protected $__cfiRangeType = 'BooksAnnotationsRange';
  protected $__cfiRangeDataType = '';
  public $cfiRange;
  protected $__gbImageRangeType = 'BooksAnnotationsRange';
  protected $__gbImageRangeDataType = '';
  public $gbImageRange;
  public function setContentVersion($contentVersion) {
    $this->contentVersion = $contentVersion;
  }
  public function getContentVersion() {
    return $this->contentVersion;
  }
  public function setGbTextRange(BooksAnnotationsRange $gbTextRange) {
    $this->gbTextRange = $gbTextRange;
  }
  public function getGbTextRange() {
    return $this->gbTextRange;
  }
  public function setCfiRange(BooksAnnotationsRange $cfiRange) {
    $this->cfiRange = $cfiRange;
  }
  public function getCfiRange() {
    return $this->cfiRange;
  }
  public function setGbImageRange(BooksAnnotationsRange $gbImageRange) {
    $this->gbImageRange = $gbImageRange;
  }
  public function getGbImageRange() {
    return $this->gbImageRange;
  }
}

class Annotationdata extends apiModel {
  public $annotationType;
  public $kind;
  public $updated;
  public $volumeId;
  public $encoded_data;
  public $layerId;
  protected $__dataType = 'BooksLayerGeoData';
  protected $__dataDataType = '';
  public $data;
  public $id;
  public $selfLink;
  public function setAnnotationType($annotationType) {
    $this->annotationType = $annotationType;
  }
  public function getAnnotationType() {
    return $this->annotationType;
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
  public function setVolumeId($volumeId) {
    $this->volumeId = $volumeId;
  }
  public function getVolumeId() {
    return $this->volumeId;
  }
  public function setEncoded_data($encoded_data) {
    $this->encoded_data = $encoded_data;
  }
  public function getEncoded_data() {
    return $this->encoded_data;
  }
  public function setLayerId($layerId) {
    $this->layerId = $layerId;
  }
  public function getLayerId() {
    return $this->layerId;
  }
  public function setData(BooksLayerGeoData $data) {
    $this->data = $data;
  }
  public function getData() {
    return $this->data;
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

class Annotations extends apiModel {
  public $nextPageToken;
  protected $__itemsType = 'Annotation';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $totalItems;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setItems(/* array(Annotation) */ $items) {
    $this->assertIsArray($items, 'Annotation', __METHOD__);
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
  public function setTotalItems($totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
}

class Annotationsdata extends apiModel {
  public $nextPageToken;
  protected $__itemsType = 'Annotationdata';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $totalItems;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setItems(/* array(Annotationdata) */ $items) {
    $this->assertIsArray($items, 'Annotationdata', __METHOD__);
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
  public function setTotalItems($totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
}

class BooksAnnotationsRange extends apiModel {
  public $startPosition;
  public $endPosition;
  public $startOffset;
  public $endOffset;
  public function setStartPosition($startPosition) {
    $this->startPosition = $startPosition;
  }
  public function getStartPosition() {
    return $this->startPosition;
  }
  public function setEndPosition($endPosition) {
    $this->endPosition = $endPosition;
  }
  public function getEndPosition() {
    return $this->endPosition;
  }
  public function setStartOffset($startOffset) {
    $this->startOffset = $startOffset;
  }
  public function getStartOffset() {
    return $this->startOffset;
  }
  public function setEndOffset($endOffset) {
    $this->endOffset = $endOffset;
  }
  public function getEndOffset() {
    return $this->endOffset;
  }
}

class BooksLayerGeoData extends apiModel {
  protected $__geoType = 'BooksLayerGeoDataGeo';
  protected $__geoDataType = '';
  public $geo;
  protected $__commonType = 'BooksLayerGeoDataCommon';
  protected $__commonDataType = '';
  public $common;
  public function setGeo(BooksLayerGeoDataGeo $geo) {
    $this->geo = $geo;
  }
  public function getGeo() {
    return $this->geo;
  }
  public function setCommon(BooksLayerGeoDataCommon $common) {
    $this->common = $common;
  }
  public function getCommon() {
    return $this->common;
  }
}

class BooksLayerGeoDataCommon extends apiModel {
  public $lang;
  public $previewImageUrl;
  public $snippet;
  public $snippetUrl;
  public function setLang($lang) {
    $this->lang = $lang;
  }
  public function getLang() {
    return $this->lang;
  }
  public function setPreviewImageUrl($previewImageUrl) {
    $this->previewImageUrl = $previewImageUrl;
  }
  public function getPreviewImageUrl() {
    return $this->previewImageUrl;
  }
  public function setSnippet($snippet) {
    $this->snippet = $snippet;
  }
  public function getSnippet() {
    return $this->snippet;
  }
  public function setSnippetUrl($snippetUrl) {
    $this->snippetUrl = $snippetUrl;
  }
  public function getSnippetUrl() {
    return $this->snippetUrl;
  }
}

class BooksLayerGeoDataGeo extends apiModel {
  public $countryCode;
  public $longitude;
  public $mapType;
  public $latitude;
  protected $__boundaryType = 'BooksLayerGeoDataGeoBoundary';
  protected $__boundaryDataType = 'array';
  public $boundary;
  public $resolution;
  protected $__viewportType = 'BooksLayerGeoDataGeoViewport';
  protected $__viewportDataType = '';
  public $viewport;
  public $cachePolicy;
  public function setCountryCode($countryCode) {
    $this->countryCode = $countryCode;
  }
  public function getCountryCode() {
    return $this->countryCode;
  }
  public function setLongitude($longitude) {
    $this->longitude = $longitude;
  }
  public function getLongitude() {
    return $this->longitude;
  }
  public function setMapType($mapType) {
    $this->mapType = $mapType;
  }
  public function getMapType() {
    return $this->mapType;
  }
  public function setLatitude($latitude) {
    $this->latitude = $latitude;
  }
  public function getLatitude() {
    return $this->latitude;
  }
  public function setBoundary(/* array(BooksLayerGeoDataGeoBoundary) */ $boundary) {
    $this->assertIsArray($boundary, 'BooksLayerGeoDataGeoBoundary', __METHOD__);
    $this->boundary = $boundary;
  }
  public function getBoundary() {
    return $this->boundary;
  }
  public function setResolution($resolution) {
    $this->resolution = $resolution;
  }
  public function getResolution() {
    return $this->resolution;
  }
  public function setViewport(BooksLayerGeoDataGeoViewport $viewport) {
    $this->viewport = $viewport;
  }
  public function getViewport() {
    return $this->viewport;
  }
  public function setCachePolicy($cachePolicy) {
    $this->cachePolicy = $cachePolicy;
  }
  public function getCachePolicy() {
    return $this->cachePolicy;
  }
}

class BooksLayerGeoDataGeoBoundary extends apiModel {
  public $latitude;
  public $longitude;
  public function setLatitude($latitude) {
    $this->latitude = $latitude;
  }
  public function getLatitude() {
    return $this->latitude;
  }
  public function setLongitude($longitude) {
    $this->longitude = $longitude;
  }
  public function getLongitude() {
    return $this->longitude;
  }
}

class BooksLayerGeoDataGeoViewport extends apiModel {
  protected $__loType = 'BooksLayerGeoDataGeoViewportLo';
  protected $__loDataType = '';
  public $lo;
  protected $__hiType = 'BooksLayerGeoDataGeoViewportHi';
  protected $__hiDataType = '';
  public $hi;
  public function setLo(BooksLayerGeoDataGeoViewportLo $lo) {
    $this->lo = $lo;
  }
  public function getLo() {
    return $this->lo;
  }
  public function setHi(BooksLayerGeoDataGeoViewportHi $hi) {
    $this->hi = $hi;
  }
  public function getHi() {
    return $this->hi;
  }
}

class BooksLayerGeoDataGeoViewportHi extends apiModel {
  public $latitude;
  public $longitude;
  public function setLatitude($latitude) {
    $this->latitude = $latitude;
  }
  public function getLatitude() {
    return $this->latitude;
  }
  public function setLongitude($longitude) {
    $this->longitude = $longitude;
  }
  public function getLongitude() {
    return $this->longitude;
  }
}

class BooksLayerGeoDataGeoViewportLo extends apiModel {
  public $latitude;
  public $longitude;
  public function setLatitude($latitude) {
    $this->latitude = $latitude;
  }
  public function getLatitude() {
    return $this->latitude;
  }
  public function setLongitude($longitude) {
    $this->longitude = $longitude;
  }
  public function getLongitude() {
    return $this->longitude;
  }
}

class Bookshelf extends apiModel {
  public $kind;
  public $description;
  public $created;
  public $volumeCount;
  public $title;
  public $updated;
  public $access;
  public $volumesLastUpdated;
  public $id;
  public $selfLink;
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
  public function setCreated($created) {
    $this->created = $created;
  }
  public function getCreated() {
    return $this->created;
  }
  public function setVolumeCount($volumeCount) {
    $this->volumeCount = $volumeCount;
  }
  public function getVolumeCount() {
    return $this->volumeCount;
  }
  public function setTitle($title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
  public function setUpdated($updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setAccess($access) {
    $this->access = $access;
  }
  public function getAccess() {
    return $this->access;
  }
  public function setVolumesLastUpdated($volumesLastUpdated) {
    $this->volumesLastUpdated = $volumesLastUpdated;
  }
  public function getVolumesLastUpdated() {
    return $this->volumesLastUpdated;
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

class Bookshelves extends apiModel {
  protected $__itemsType = 'Bookshelf';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(Bookshelf) */ $items) {
    $this->assertIsArray($items, 'Bookshelf', __METHOD__);
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

class ConcurrentAccessRestriction extends apiModel {
  public $nonce;
  public $kind;
  public $restricted;
  public $volumeId;
  public $maxConcurrentDevices;
  public $deviceAllowed;
  public $source;
  public $timeWindowSeconds;
  public $signature;
  public $reasonCode;
  public $message;
  public function setNonce($nonce) {
    $this->nonce = $nonce;
  }
  public function getNonce() {
    return $this->nonce;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setRestricted($restricted) {
    $this->restricted = $restricted;
  }
  public function getRestricted() {
    return $this->restricted;
  }
  public function setVolumeId($volumeId) {
    $this->volumeId = $volumeId;
  }
  public function getVolumeId() {
    return $this->volumeId;
  }
  public function setMaxConcurrentDevices($maxConcurrentDevices) {
    $this->maxConcurrentDevices = $maxConcurrentDevices;
  }
  public function getMaxConcurrentDevices() {
    return $this->maxConcurrentDevices;
  }
  public function setDeviceAllowed($deviceAllowed) {
    $this->deviceAllowed = $deviceAllowed;
  }
  public function getDeviceAllowed() {
    return $this->deviceAllowed;
  }
  public function setSource($source) {
    $this->source = $source;
  }
  public function getSource() {
    return $this->source;
  }
  public function setTimeWindowSeconds($timeWindowSeconds) {
    $this->timeWindowSeconds = $timeWindowSeconds;
  }
  public function getTimeWindowSeconds() {
    return $this->timeWindowSeconds;
  }
  public function setSignature($signature) {
    $this->signature = $signature;
  }
  public function getSignature() {
    return $this->signature;
  }
  public function setReasonCode($reasonCode) {
    $this->reasonCode = $reasonCode;
  }
  public function getReasonCode() {
    return $this->reasonCode;
  }
  public function setMessage($message) {
    $this->message = $message;
  }
  public function getMessage() {
    return $this->message;
  }
}

class DownloadAccessRestriction extends apiModel {
  public $nonce;
  public $kind;
  public $justAcquired;
  public $maxDownloadDevices;
  public $downloadsAcquired;
  public $signature;
  public $volumeId;
  public $deviceAllowed;
  public $source;
  public $restricted;
  public $reasonCode;
  public $message;
  public function setNonce($nonce) {
    $this->nonce = $nonce;
  }
  public function getNonce() {
    return $this->nonce;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setJustAcquired($justAcquired) {
    $this->justAcquired = $justAcquired;
  }
  public function getJustAcquired() {
    return $this->justAcquired;
  }
  public function setMaxDownloadDevices($maxDownloadDevices) {
    $this->maxDownloadDevices = $maxDownloadDevices;
  }
  public function getMaxDownloadDevices() {
    return $this->maxDownloadDevices;
  }
  public function setDownloadsAcquired($downloadsAcquired) {
    $this->downloadsAcquired = $downloadsAcquired;
  }
  public function getDownloadsAcquired() {
    return $this->downloadsAcquired;
  }
  public function setSignature($signature) {
    $this->signature = $signature;
  }
  public function getSignature() {
    return $this->signature;
  }
  public function setVolumeId($volumeId) {
    $this->volumeId = $volumeId;
  }
  public function getVolumeId() {
    return $this->volumeId;
  }
  public function setDeviceAllowed($deviceAllowed) {
    $this->deviceAllowed = $deviceAllowed;
  }
  public function getDeviceAllowed() {
    return $this->deviceAllowed;
  }
  public function setSource($source) {
    $this->source = $source;
  }
  public function getSource() {
    return $this->source;
  }
  public function setRestricted($restricted) {
    $this->restricted = $restricted;
  }
  public function getRestricted() {
    return $this->restricted;
  }
  public function setReasonCode($reasonCode) {
    $this->reasonCode = $reasonCode;
  }
  public function getReasonCode() {
    return $this->reasonCode;
  }
  public function setMessage($message) {
    $this->message = $message;
  }
  public function getMessage() {
    return $this->message;
  }
}

class DownloadAccesses extends apiModel {
  protected $__downloadAccessListType = 'DownloadAccessRestriction';
  protected $__downloadAccessListDataType = 'array';
  public $downloadAccessList;
  public $kind;
  public function setDownloadAccessList(/* array(DownloadAccessRestriction) */ $downloadAccessList) {
    $this->assertIsArray($downloadAccessList, 'DownloadAccessRestriction', __METHOD__);
    $this->downloadAccessList = $downloadAccessList;
  }
  public function getDownloadAccessList() {
    return $this->downloadAccessList;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
}

class Layersummaries extends apiModel {
  public $totalItems;
  protected $__itemsType = 'Layersummary';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setTotalItems($totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
  public function setItems(/* array(Layersummary) */ $items) {
    $this->assertIsArray($items, 'Layersummary', __METHOD__);
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

class Layersummary extends apiModel {
  public $kind;
  public $annotationCount;
  public $dataCount;
  public $annotationsLink;
  public $updated;
  public $volumeId;
  public $id;
  public $annotationTypes;
  public $contentVersion;
  public $layerId;
  public $annotationsDataLink;
  public $selfLink;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setAnnotationCount($annotationCount) {
    $this->annotationCount = $annotationCount;
  }
  public function getAnnotationCount() {
    return $this->annotationCount;
  }
  public function setDataCount($dataCount) {
    $this->dataCount = $dataCount;
  }
  public function getDataCount() {
    return $this->dataCount;
  }
  public function setAnnotationsLink($annotationsLink) {
    $this->annotationsLink = $annotationsLink;
  }
  public function getAnnotationsLink() {
    return $this->annotationsLink;
  }
  public function setUpdated($updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setVolumeId($volumeId) {
    $this->volumeId = $volumeId;
  }
  public function getVolumeId() {
    return $this->volumeId;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setAnnotationTypes(/* array(string) */ $annotationTypes) {
    $this->assertIsArray($annotationTypes, 'string', __METHOD__);
    $this->annotationTypes = $annotationTypes;
  }
  public function getAnnotationTypes() {
    return $this->annotationTypes;
  }
  public function setContentVersion($contentVersion) {
    $this->contentVersion = $contentVersion;
  }
  public function getContentVersion() {
    return $this->contentVersion;
  }
  public function setLayerId($layerId) {
    $this->layerId = $layerId;
  }
  public function getLayerId() {
    return $this->layerId;
  }
  public function setAnnotationsDataLink($annotationsDataLink) {
    $this->annotationsDataLink = $annotationsDataLink;
  }
  public function getAnnotationsDataLink() {
    return $this->annotationsDataLink;
  }
  public function setSelfLink($selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
}

class ReadingPosition extends apiModel {
  public $kind;
  public $gbImagePosition;
  public $epubCfiPosition;
  public $updated;
  public $volumeId;
  public $pdfPosition;
  public $gbTextPosition;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setGbImagePosition($gbImagePosition) {
    $this->gbImagePosition = $gbImagePosition;
  }
  public function getGbImagePosition() {
    return $this->gbImagePosition;
  }
  public function setEpubCfiPosition($epubCfiPosition) {
    $this->epubCfiPosition = $epubCfiPosition;
  }
  public function getEpubCfiPosition() {
    return $this->epubCfiPosition;
  }
  public function setUpdated($updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setVolumeId($volumeId) {
    $this->volumeId = $volumeId;
  }
  public function getVolumeId() {
    return $this->volumeId;
  }
  public function setPdfPosition($pdfPosition) {
    $this->pdfPosition = $pdfPosition;
  }
  public function getPdfPosition() {
    return $this->pdfPosition;
  }
  public function setGbTextPosition($gbTextPosition) {
    $this->gbTextPosition = $gbTextPosition;
  }
  public function getGbTextPosition() {
    return $this->gbTextPosition;
  }
}

class RequestAccess extends apiModel {
  protected $__downloadAccessType = 'DownloadAccessRestriction';
  protected $__downloadAccessDataType = '';
  public $downloadAccess;
  public $kind;
  protected $__concurrentAccessType = 'ConcurrentAccessRestriction';
  protected $__concurrentAccessDataType = '';
  public $concurrentAccess;
  public function setDownloadAccess(DownloadAccessRestriction $downloadAccess) {
    $this->downloadAccess = $downloadAccess;
  }
  public function getDownloadAccess() {
    return $this->downloadAccess;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setConcurrentAccess(ConcurrentAccessRestriction $concurrentAccess) {
    $this->concurrentAccess = $concurrentAccess;
  }
  public function getConcurrentAccess() {
    return $this->concurrentAccess;
  }
}

class Review extends apiModel {
  public $rating;
  public $kind;
  protected $__authorType = 'ReviewAuthor';
  protected $__authorDataType = '';
  public $author;
  public $title;
  public $volumeId;
  public $content;
  protected $__sourceType = 'ReviewSource';
  protected $__sourceDataType = '';
  public $source;
  public $date;
  public $type;
  public $fullTextUrl;
  public function setRating($rating) {
    $this->rating = $rating;
  }
  public function getRating() {
    return $this->rating;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setAuthor(ReviewAuthor $author) {
    $this->author = $author;
  }
  public function getAuthor() {
    return $this->author;
  }
  public function setTitle($title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
  public function setVolumeId($volumeId) {
    $this->volumeId = $volumeId;
  }
  public function getVolumeId() {
    return $this->volumeId;
  }
  public function setContent($content) {
    $this->content = $content;
  }
  public function getContent() {
    return $this->content;
  }
  public function setSource(ReviewSource $source) {
    $this->source = $source;
  }
  public function getSource() {
    return $this->source;
  }
  public function setDate($date) {
    $this->date = $date;
  }
  public function getDate() {
    return $this->date;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
  public function setFullTextUrl($fullTextUrl) {
    $this->fullTextUrl = $fullTextUrl;
  }
  public function getFullTextUrl() {
    return $this->fullTextUrl;
  }
}

class ReviewAuthor extends apiModel {
  public $displayName;
  public function setDisplayName($displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
}

class ReviewSource extends apiModel {
  public $extraDescription;
  public $url;
  public $description;
  public function setExtraDescription($extraDescription) {
    $this->extraDescription = $extraDescription;
  }
  public function getExtraDescription() {
    return $this->extraDescription;
  }
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setDescription($description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
}

class Volume extends apiModel {
  public $kind;
  protected $__accessInfoType = 'VolumeAccessInfo';
  protected $__accessInfoDataType = '';
  public $accessInfo;
  protected $__searchInfoType = 'VolumeSearchInfo';
  protected $__searchInfoDataType = '';
  public $searchInfo;
  protected $__saleInfoType = 'VolumeSaleInfo';
  protected $__saleInfoDataType = '';
  public $saleInfo;
  public $etag;
  protected $__userInfoType = 'VolumeUserInfo';
  protected $__userInfoDataType = '';
  public $userInfo;
  protected $__volumeInfoType = 'VolumeVolumeInfo';
  protected $__volumeInfoDataType = '';
  public $volumeInfo;
  public $id;
  public $selfLink;
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setAccessInfo(VolumeAccessInfo $accessInfo) {
    $this->accessInfo = $accessInfo;
  }
  public function getAccessInfo() {
    return $this->accessInfo;
  }
  public function setSearchInfo(VolumeSearchInfo $searchInfo) {
    $this->searchInfo = $searchInfo;
  }
  public function getSearchInfo() {
    return $this->searchInfo;
  }
  public function setSaleInfo(VolumeSaleInfo $saleInfo) {
    $this->saleInfo = $saleInfo;
  }
  public function getSaleInfo() {
    return $this->saleInfo;
  }
  public function setEtag($etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setUserInfo(VolumeUserInfo $userInfo) {
    $this->userInfo = $userInfo;
  }
  public function getUserInfo() {
    return $this->userInfo;
  }
  public function setVolumeInfo(VolumeVolumeInfo $volumeInfo) {
    $this->volumeInfo = $volumeInfo;
  }
  public function getVolumeInfo() {
    return $this->volumeInfo;
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

class VolumeAccessInfo extends apiModel {
  public $webReaderLink;
  public $publicDomain;
  public $embeddable;
  protected $__downloadAccessType = 'DownloadAccessRestriction';
  protected $__downloadAccessDataType = '';
  public $downloadAccess;
  public $country;
  public $viewOrderUrl;
  public $textToSpeechPermission;
  protected $__pdfType = 'VolumeAccessInfoPdf';
  protected $__pdfDataType = '';
  public $pdf;
  public $viewability;
  protected $__epubType = 'VolumeAccessInfoEpub';
  protected $__epubDataType = '';
  public $epub;
  public $accessViewStatus;
  public function setWebReaderLink($webReaderLink) {
    $this->webReaderLink = $webReaderLink;
  }
  public function getWebReaderLink() {
    return $this->webReaderLink;
  }
  public function setPublicDomain($publicDomain) {
    $this->publicDomain = $publicDomain;
  }
  public function getPublicDomain() {
    return $this->publicDomain;
  }
  public function setEmbeddable($embeddable) {
    $this->embeddable = $embeddable;
  }
  public function getEmbeddable() {
    return $this->embeddable;
  }
  public function setDownloadAccess(DownloadAccessRestriction $downloadAccess) {
    $this->downloadAccess = $downloadAccess;
  }
  public function getDownloadAccess() {
    return $this->downloadAccess;
  }
  public function setCountry($country) {
    $this->country = $country;
  }
  public function getCountry() {
    return $this->country;
  }
  public function setViewOrderUrl($viewOrderUrl) {
    $this->viewOrderUrl = $viewOrderUrl;
  }
  public function getViewOrderUrl() {
    return $this->viewOrderUrl;
  }
  public function setTextToSpeechPermission($textToSpeechPermission) {
    $this->textToSpeechPermission = $textToSpeechPermission;
  }
  public function getTextToSpeechPermission() {
    return $this->textToSpeechPermission;
  }
  public function setPdf(VolumeAccessInfoPdf $pdf) {
    $this->pdf = $pdf;
  }
  public function getPdf() {
    return $this->pdf;
  }
  public function setViewability($viewability) {
    $this->viewability = $viewability;
  }
  public function getViewability() {
    return $this->viewability;
  }
  public function setEpub(VolumeAccessInfoEpub $epub) {
    $this->epub = $epub;
  }
  public function getEpub() {
    return $this->epub;
  }
  public function setAccessViewStatus($accessViewStatus) {
    $this->accessViewStatus = $accessViewStatus;
  }
  public function getAccessViewStatus() {
    return $this->accessViewStatus;
  }
}

class VolumeAccessInfoEpub extends apiModel {
  public $isAvailable;
  public $downloadLink;
  public $acsTokenLink;
  public function setIsAvailable($isAvailable) {
    $this->isAvailable = $isAvailable;
  }
  public function getIsAvailable() {
    return $this->isAvailable;
  }
  public function setDownloadLink($downloadLink) {
    $this->downloadLink = $downloadLink;
  }
  public function getDownloadLink() {
    return $this->downloadLink;
  }
  public function setAcsTokenLink($acsTokenLink) {
    $this->acsTokenLink = $acsTokenLink;
  }
  public function getAcsTokenLink() {
    return $this->acsTokenLink;
  }
}

class VolumeAccessInfoPdf extends apiModel {
  public $isAvailable;
  public $downloadLink;
  public $acsTokenLink;
  public function setIsAvailable($isAvailable) {
    $this->isAvailable = $isAvailable;
  }
  public function getIsAvailable() {
    return $this->isAvailable;
  }
  public function setDownloadLink($downloadLink) {
    $this->downloadLink = $downloadLink;
  }
  public function getDownloadLink() {
    return $this->downloadLink;
  }
  public function setAcsTokenLink($acsTokenLink) {
    $this->acsTokenLink = $acsTokenLink;
  }
  public function getAcsTokenLink() {
    return $this->acsTokenLink;
  }
}

class VolumeSaleInfo extends apiModel {
  public $country;
  protected $__retailPriceType = 'VolumeSaleInfoRetailPrice';
  protected $__retailPriceDataType = '';
  public $retailPrice;
  public $isEbook;
  public $saleability;
  public $buyLink;
  public $onSaleDate;
  protected $__listPriceType = 'VolumeSaleInfoListPrice';
  protected $__listPriceDataType = '';
  public $listPrice;
  public function setCountry($country) {
    $this->country = $country;
  }
  public function getCountry() {
    return $this->country;
  }
  public function setRetailPrice(VolumeSaleInfoRetailPrice $retailPrice) {
    $this->retailPrice = $retailPrice;
  }
  public function getRetailPrice() {
    return $this->retailPrice;
  }
  public function setIsEbook($isEbook) {
    $this->isEbook = $isEbook;
  }
  public function getIsEbook() {
    return $this->isEbook;
  }
  public function setSaleability($saleability) {
    $this->saleability = $saleability;
  }
  public function getSaleability() {
    return $this->saleability;
  }
  public function setBuyLink($buyLink) {
    $this->buyLink = $buyLink;
  }
  public function getBuyLink() {
    return $this->buyLink;
  }
  public function setOnSaleDate($onSaleDate) {
    $this->onSaleDate = $onSaleDate;
  }
  public function getOnSaleDate() {
    return $this->onSaleDate;
  }
  public function setListPrice(VolumeSaleInfoListPrice $listPrice) {
    $this->listPrice = $listPrice;
  }
  public function getListPrice() {
    return $this->listPrice;
  }
}

class VolumeSaleInfoListPrice extends apiModel {
  public $amount;
  public $currencyCode;
  public function setAmount($amount) {
    $this->amount = $amount;
  }
  public function getAmount() {
    return $this->amount;
  }
  public function setCurrencyCode($currencyCode) {
    $this->currencyCode = $currencyCode;
  }
  public function getCurrencyCode() {
    return $this->currencyCode;
  }
}

class VolumeSaleInfoRetailPrice extends apiModel {
  public $amount;
  public $currencyCode;
  public function setAmount($amount) {
    $this->amount = $amount;
  }
  public function getAmount() {
    return $this->amount;
  }
  public function setCurrencyCode($currencyCode) {
    $this->currencyCode = $currencyCode;
  }
  public function getCurrencyCode() {
    return $this->currencyCode;
  }
}

class VolumeSearchInfo extends apiModel {
  public $textSnippet;
  public function setTextSnippet($textSnippet) {
    $this->textSnippet = $textSnippet;
  }
  public function getTextSnippet() {
    return $this->textSnippet;
  }
}

class VolumeUserInfo extends apiModel {
  public $isInMyBooks;
  public $updated;
  protected $__reviewType = 'Review';
  protected $__reviewDataType = '';
  public $review;
  public $isPurchased;
  protected $__readingPositionType = 'ReadingPosition';
  protected $__readingPositionDataType = '';
  public $readingPosition;
  public $isPreordered;
  public function setIsInMyBooks($isInMyBooks) {
    $this->isInMyBooks = $isInMyBooks;
  }
  public function getIsInMyBooks() {
    return $this->isInMyBooks;
  }
  public function setUpdated($updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setReview(Review $review) {
    $this->review = $review;
  }
  public function getReview() {
    return $this->review;
  }
  public function setIsPurchased($isPurchased) {
    $this->isPurchased = $isPurchased;
  }
  public function getIsPurchased() {
    return $this->isPurchased;
  }
  public function setReadingPosition(ReadingPosition $readingPosition) {
    $this->readingPosition = $readingPosition;
  }
  public function getReadingPosition() {
    return $this->readingPosition;
  }
  public function setIsPreordered($isPreordered) {
    $this->isPreordered = $isPreordered;
  }
  public function getIsPreordered() {
    return $this->isPreordered;
  }
}

class VolumeVolumeInfo extends apiModel {
  public $publisher;
  public $subtitle;
  public $description;
  public $language;
  public $pageCount;
  protected $__imageLinksType = 'VolumeVolumeInfoImageLinks';
  protected $__imageLinksDataType = '';
  public $imageLinks;
  public $publishedDate;
  public $previewLink;
  public $printType;
  public $ratingsCount;
  public $mainCategory;
  protected $__dimensionsType = 'VolumeVolumeInfoDimensions';
  protected $__dimensionsDataType = '';
  public $dimensions;
  public $contentVersion;
  protected $__industryIdentifiersType = 'VolumeVolumeInfoIndustryIdentifiers';
  protected $__industryIdentifiersDataType = 'array';
  public $industryIdentifiers;
  public $authors;
  public $title;
  public $canonicalVolumeLink;
  public $infoLink;
  public $categories;
  public $averageRating;
  public function setPublisher($publisher) {
    $this->publisher = $publisher;
  }
  public function getPublisher() {
    return $this->publisher;
  }
  public function setSubtitle($subtitle) {
    $this->subtitle = $subtitle;
  }
  public function getSubtitle() {
    return $this->subtitle;
  }
  public function setDescription($description) {
    $this->description = $description;
  }
  public function getDescription() {
    return $this->description;
  }
  public function setLanguage($language) {
    $this->language = $language;
  }
  public function getLanguage() {
    return $this->language;
  }
  public function setPageCount($pageCount) {
    $this->pageCount = $pageCount;
  }
  public function getPageCount() {
    return $this->pageCount;
  }
  public function setImageLinks(VolumeVolumeInfoImageLinks $imageLinks) {
    $this->imageLinks = $imageLinks;
  }
  public function getImageLinks() {
    return $this->imageLinks;
  }
  public function setPublishedDate($publishedDate) {
    $this->publishedDate = $publishedDate;
  }
  public function getPublishedDate() {
    return $this->publishedDate;
  }
  public function setPreviewLink($previewLink) {
    $this->previewLink = $previewLink;
  }
  public function getPreviewLink() {
    return $this->previewLink;
  }
  public function setPrintType($printType) {
    $this->printType = $printType;
  }
  public function getPrintType() {
    return $this->printType;
  }
  public function setRatingsCount($ratingsCount) {
    $this->ratingsCount = $ratingsCount;
  }
  public function getRatingsCount() {
    return $this->ratingsCount;
  }
  public function setMainCategory($mainCategory) {
    $this->mainCategory = $mainCategory;
  }
  public function getMainCategory() {
    return $this->mainCategory;
  }
  public function setDimensions(VolumeVolumeInfoDimensions $dimensions) {
    $this->dimensions = $dimensions;
  }
  public function getDimensions() {
    return $this->dimensions;
  }
  public function setContentVersion($contentVersion) {
    $this->contentVersion = $contentVersion;
  }
  public function getContentVersion() {
    return $this->contentVersion;
  }
  public function setIndustryIdentifiers(/* array(VolumeVolumeInfoIndustryIdentifiers) */ $industryIdentifiers) {
    $this->assertIsArray($industryIdentifiers, 'VolumeVolumeInfoIndustryIdentifiers', __METHOD__);
    $this->industryIdentifiers = $industryIdentifiers;
  }
  public function getIndustryIdentifiers() {
    return $this->industryIdentifiers;
  }
  public function setAuthors(/* array(string) */ $authors) {
    $this->assertIsArray($authors, 'string', __METHOD__);
    $this->authors = $authors;
  }
  public function getAuthors() {
    return $this->authors;
  }
  public function setTitle($title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
  }
  public function setCanonicalVolumeLink($canonicalVolumeLink) {
    $this->canonicalVolumeLink = $canonicalVolumeLink;
  }
  public function getCanonicalVolumeLink() {
    return $this->canonicalVolumeLink;
  }
  public function setInfoLink($infoLink) {
    $this->infoLink = $infoLink;
  }
  public function getInfoLink() {
    return $this->infoLink;
  }
  public function setCategories(/* array(string) */ $categories) {
    $this->assertIsArray($categories, 'string', __METHOD__);
    $this->categories = $categories;
  }
  public function getCategories() {
    return $this->categories;
  }
  public function setAverageRating($averageRating) {
    $this->averageRating = $averageRating;
  }
  public function getAverageRating() {
    return $this->averageRating;
  }
}

class VolumeVolumeInfoDimensions extends apiModel {
  public $width;
  public $thickness;
  public $height;
  public function setWidth($width) {
    $this->width = $width;
  }
  public function getWidth() {
    return $this->width;
  }
  public function setThickness($thickness) {
    $this->thickness = $thickness;
  }
  public function getThickness() {
    return $this->thickness;
  }
  public function setHeight($height) {
    $this->height = $height;
  }
  public function getHeight() {
    return $this->height;
  }
}

class VolumeVolumeInfoImageLinks extends apiModel {
  public $medium;
  public $smallThumbnail;
  public $large;
  public $extraLarge;
  public $small;
  public $thumbnail;
  public function setMedium($medium) {
    $this->medium = $medium;
  }
  public function getMedium() {
    return $this->medium;
  }
  public function setSmallThumbnail($smallThumbnail) {
    $this->smallThumbnail = $smallThumbnail;
  }
  public function getSmallThumbnail() {
    return $this->smallThumbnail;
  }
  public function setLarge($large) {
    $this->large = $large;
  }
  public function getLarge() {
    return $this->large;
  }
  public function setExtraLarge($extraLarge) {
    $this->extraLarge = $extraLarge;
  }
  public function getExtraLarge() {
    return $this->extraLarge;
  }
  public function setSmall($small) {
    $this->small = $small;
  }
  public function getSmall() {
    return $this->small;
  }
  public function setThumbnail($thumbnail) {
    $this->thumbnail = $thumbnail;
  }
  public function getThumbnail() {
    return $this->thumbnail;
  }
}

class VolumeVolumeInfoIndustryIdentifiers extends apiModel {
  public $identifier;
  public $type;
  public function setIdentifier($identifier) {
    $this->identifier = $identifier;
  }
  public function getIdentifier() {
    return $this->identifier;
  }
  public function setType($type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}

class Volumeannotation extends apiModel {
  public $annotationType;
  public $kind;
  public $updated;
  public $deleted;
  protected $__contentRangesType = 'VolumeannotationContentRanges';
  protected $__contentRangesDataType = '';
  public $contentRanges;
  public $selectedText;
  public $volumeId;
  public $annotationDataId;
  public $annotationDataLink;
  public $pageIds;
  public $layerId;
  public $data;
  public $id;
  public $selfLink;
  public function setAnnotationType($annotationType) {
    $this->annotationType = $annotationType;
  }
  public function getAnnotationType() {
    return $this->annotationType;
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
  public function setDeleted($deleted) {
    $this->deleted = $deleted;
  }
  public function getDeleted() {
    return $this->deleted;
  }
  public function setContentRanges(VolumeannotationContentRanges $contentRanges) {
    $this->contentRanges = $contentRanges;
  }
  public function getContentRanges() {
    return $this->contentRanges;
  }
  public function setSelectedText($selectedText) {
    $this->selectedText = $selectedText;
  }
  public function getSelectedText() {
    return $this->selectedText;
  }
  public function setVolumeId($volumeId) {
    $this->volumeId = $volumeId;
  }
  public function getVolumeId() {
    return $this->volumeId;
  }
  public function setAnnotationDataId($annotationDataId) {
    $this->annotationDataId = $annotationDataId;
  }
  public function getAnnotationDataId() {
    return $this->annotationDataId;
  }
  public function setAnnotationDataLink($annotationDataLink) {
    $this->annotationDataLink = $annotationDataLink;
  }
  public function getAnnotationDataLink() {
    return $this->annotationDataLink;
  }
  public function setPageIds(/* array(string) */ $pageIds) {
    $this->assertIsArray($pageIds, 'string', __METHOD__);
    $this->pageIds = $pageIds;
  }
  public function getPageIds() {
    return $this->pageIds;
  }
  public function setLayerId($layerId) {
    $this->layerId = $layerId;
  }
  public function getLayerId() {
    return $this->layerId;
  }
  public function setData($data) {
    $this->data = $data;
  }
  public function getData() {
    return $this->data;
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

class VolumeannotationContentRanges extends apiModel {
  public $contentVersion;
  protected $__gbTextRangeType = 'BooksAnnotationsRange';
  protected $__gbTextRangeDataType = '';
  public $gbTextRange;
  protected $__cfiRangeType = 'BooksAnnotationsRange';
  protected $__cfiRangeDataType = '';
  public $cfiRange;
  protected $__gbImageRangeType = 'BooksAnnotationsRange';
  protected $__gbImageRangeDataType = '';
  public $gbImageRange;
  public function setContentVersion($contentVersion) {
    $this->contentVersion = $contentVersion;
  }
  public function getContentVersion() {
    return $this->contentVersion;
  }
  public function setGbTextRange(BooksAnnotationsRange $gbTextRange) {
    $this->gbTextRange = $gbTextRange;
  }
  public function getGbTextRange() {
    return $this->gbTextRange;
  }
  public function setCfiRange(BooksAnnotationsRange $cfiRange) {
    $this->cfiRange = $cfiRange;
  }
  public function getCfiRange() {
    return $this->cfiRange;
  }
  public function setGbImageRange(BooksAnnotationsRange $gbImageRange) {
    $this->gbImageRange = $gbImageRange;
  }
  public function getGbImageRange() {
    return $this->gbImageRange;
  }
}

class Volumeannotations extends apiModel {
  public $nextPageToken;
  protected $__itemsType = 'Volumeannotation';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $totalItems;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setItems(/* array(Volumeannotation) */ $items) {
    $this->assertIsArray($items, 'Volumeannotation', __METHOD__);
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
  public function setTotalItems($totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
}

class Volumes extends apiModel {
  public $totalItems;
  protected $__itemsType = 'Volume';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setTotalItems($totalItems) {
    $this->totalItems = $totalItems;
  }
  public function getTotalItems() {
    return $this->totalItems;
  }
  public function setItems(/* array(Volume) */ $items) {
    $this->assertIsArray($items, 'Volume', __METHOD__);
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
