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
   * The "bucketAccessControls" collection of methods.
   * Typical usage is:
   *  <code>
   *   $storageService = new Google_StorageService(...);
   *   $bucketAccessControls = $storageService->bucketAccessControls;
   *  </code>
   */
  class Google_BucketAccessControlsServiceResource extends Google_ServiceResource {

    /**
     * Permanently deletes the ACL entry for the specified entity on the specified bucket.
     * (bucketAccessControls.delete)
     *
     * @param string $bucket Name of a bucket.
     * @param string $entity The entity holding the permission. Can be user-userId, group-groupId, allUsers, or allAuthenticatedUsers.
     * @param array $optParams Optional parameters.
     */
    public function delete($bucket, $entity, $optParams = array()) {
      $params = array('bucket' => $bucket, 'entity' => $entity);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
    /**
     * Returns the ACL entry for the specified entity on the specified bucket.
     * (bucketAccessControls.get)
     *
     * @param string $bucket Name of a bucket.
     * @param string $entity The entity holding the permission. Can be user-userId, group-groupId, allUsers, or allAuthenticatedUsers.
     * @param array $optParams Optional parameters.
     * @return Google_BucketAccessControl
     */
    public function get($bucket, $entity, $optParams = array()) {
      $params = array('bucket' => $bucket, 'entity' => $entity);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_BucketAccessControl($data);
      } else {
        return $data;
      }
    }
    /**
     * Creates a new ACL entry on the specified bucket. (bucketAccessControls.insert)
     *
     * @param string $bucket Name of a bucket.
     * @param Google_BucketAccessControl $postBody
     * @param array $optParams Optional parameters.
     * @return Google_BucketAccessControl
     */
    public function insert($bucket, Google_BucketAccessControl $postBody, $optParams = array()) {
      $params = array('bucket' => $bucket, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_BucketAccessControl($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves ACL entries on the specified bucket. (bucketAccessControls.list)
     *
     * @param string $bucket Name of a bucket.
     * @param array $optParams Optional parameters.
     * @return Google_BucketAccessControls
     */
    public function listBucketAccessControls($bucket, $optParams = array()) {
      $params = array('bucket' => $bucket);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_BucketAccessControls($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates an ACL entry on the specified bucket. This method supports patch semantics.
     * (bucketAccessControls.patch)
     *
     * @param string $bucket Name of a bucket.
     * @param string $entity The entity holding the permission. Can be user-userId, group-groupId, allUsers, or allAuthenticatedUsers.
     * @param Google_BucketAccessControl $postBody
     * @param array $optParams Optional parameters.
     * @return Google_BucketAccessControl
     */
    public function patch($bucket, $entity, Google_BucketAccessControl $postBody, $optParams = array()) {
      $params = array('bucket' => $bucket, 'entity' => $entity, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Google_BucketAccessControl($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates an ACL entry on the specified bucket. (bucketAccessControls.update)
     *
     * @param string $bucket Name of a bucket.
     * @param string $entity The entity holding the permission. Can be user-userId, group-groupId, allUsers, or allAuthenticatedUsers.
     * @param Google_BucketAccessControl $postBody
     * @param array $optParams Optional parameters.
     * @return Google_BucketAccessControl
     */
    public function update($bucket, $entity, Google_BucketAccessControl $postBody, $optParams = array()) {
      $params = array('bucket' => $bucket, 'entity' => $entity, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Google_BucketAccessControl($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "buckets" collection of methods.
   * Typical usage is:
   *  <code>
   *   $storageService = new Google_StorageService(...);
   *   $buckets = $storageService->buckets;
   *  </code>
   */
  class Google_BucketsServiceResource extends Google_ServiceResource {

    /**
     * Permanently deletes an empty bucket. (buckets.delete)
     *
     * @param string $bucket Name of a bucket.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string ifMetagenerationMatch Makes the return of the bucket metadata conditional on whether the bucket's current metageneration matches the given value.
     * @opt_param string ifMetagenerationNotMatch Makes the return of the bucket metadata conditional on whether the bucket's current metageneration does not match the given value.
     */
    public function delete($bucket, $optParams = array()) {
      $params = array('bucket' => $bucket);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
    /**
     * Returns metadata for the specified bucket. (buckets.get)
     *
     * @param string $bucket Name of a bucket.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string ifMetagenerationMatch Makes the return of the bucket metadata conditional on whether the bucket's current metageneration matches the given value.
     * @opt_param string ifMetagenerationNotMatch Makes the return of the bucket metadata conditional on whether the bucket's current metageneration does not match the given value.
     * @opt_param string projection Set of properties to return. Defaults to noAcl.
     * @return Google_Bucket
     */
    public function get($bucket, $optParams = array()) {
      $params = array('bucket' => $bucket);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_Bucket($data);
      } else {
        return $data;
      }
    }
    /**
     * Creates a new bucket. (buckets.insert)
     *
     * @param string $project A valid API project identifier.
     * @param Google_Bucket $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string projection Set of properties to return. Defaults to noAcl, unless the bucket resource specifies acl or defaultObjectAcl properties, when it defaults to full.
     * @return Google_Bucket
     */
    public function insert($project, Google_Bucket $postBody, $optParams = array()) {
      $params = array('project' => $project, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_Bucket($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves a list of buckets for a given project. (buckets.list)
     *
     * @param string $project A valid API project identifier.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string maxResults Maximum number of buckets to return.
     * @opt_param string pageToken A previously-returned page token representing part of the larger set of results to view.
     * @opt_param string projection Set of properties to return. Defaults to noAcl.
     * @return Google_Buckets
     */
    public function listBuckets($project, $optParams = array()) {
      $params = array('project' => $project);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_Buckets($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates a bucket. This method supports patch semantics. (buckets.patch)
     *
     * @param string $bucket Name of a bucket.
     * @param Google_Bucket $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string ifMetagenerationMatch Makes the return of the bucket metadata conditional on whether the bucket's current metageneration matches the given value.
     * @opt_param string ifMetagenerationNotMatch Makes the return of the bucket metadata conditional on whether the bucket's current metageneration does not match the given value.
     * @opt_param string projection Set of properties to return. Defaults to full.
     * @return Google_Bucket
     */
    public function patch($bucket, Google_Bucket $postBody, $optParams = array()) {
      $params = array('bucket' => $bucket, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Google_Bucket($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates a bucket. (buckets.update)
     *
     * @param string $bucket Name of a bucket.
     * @param Google_Bucket $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string ifMetagenerationMatch Makes the return of the bucket metadata conditional on whether the bucket's current metageneration matches the given value.
     * @opt_param string ifMetagenerationNotMatch Makes the return of the bucket metadata conditional on whether the bucket's current metageneration does not match the given value.
     * @opt_param string projection Set of properties to return. Defaults to full.
     * @return Google_Bucket
     */
    public function update($bucket, Google_Bucket $postBody, $optParams = array()) {
      $params = array('bucket' => $bucket, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Google_Bucket($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "channels" collection of methods.
   * Typical usage is:
   *  <code>
   *   $storageService = new Google_StorageService(...);
   *   $channels = $storageService->channels;
   *  </code>
   */
  class Google_ChannelsServiceResource extends Google_ServiceResource {

    /**
     * (channels.stop)
     *
     * @param Google_Channel $postBody
     * @param array $optParams Optional parameters.
     */
    public function stop(Google_Channel $postBody, $optParams = array()) {
      $params = array('postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('stop', array($params));
      return $data;
    }
  }

  /**
   * The "defaultObjectAccessControls" collection of methods.
   * Typical usage is:
   *  <code>
   *   $storageService = new Google_StorageService(...);
   *   $defaultObjectAccessControls = $storageService->defaultObjectAccessControls;
   *  </code>
   */
  class Google_DefaultObjectAccessControlsServiceResource extends Google_ServiceResource {

    /**
     * Permanently deletes the default object ACL entry for the specified entity on the specified
     * bucket. (defaultObjectAccessControls.delete)
     *
     * @param string $bucket Name of a bucket.
     * @param string $entity The entity holding the permission. Can be user-userId, group-groupId, allUsers, or allAuthenticatedUsers.
     * @param array $optParams Optional parameters.
     */
    public function delete($bucket, $entity, $optParams = array()) {
      $params = array('bucket' => $bucket, 'entity' => $entity);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
    /**
     * Returns the default object ACL entry for the specified entity on the specified bucket.
     * (defaultObjectAccessControls.get)
     *
     * @param string $bucket Name of a bucket.
     * @param string $entity The entity holding the permission. Can be user-userId, group-groupId, allUsers, or allAuthenticatedUsers.
     * @param array $optParams Optional parameters.
     * @return Google_ObjectAccessControl
     */
    public function get($bucket, $entity, $optParams = array()) {
      $params = array('bucket' => $bucket, 'entity' => $entity);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_ObjectAccessControl($data);
      } else {
        return $data;
      }
    }
    /**
     * Creates a new default object ACL entry on the specified bucket.
     * (defaultObjectAccessControls.insert)
     *
     * @param string $bucket Name of a bucket.
     * @param Google_ObjectAccessControl $postBody
     * @param array $optParams Optional parameters.
     * @return Google_ObjectAccessControl
     */
    public function insert($bucket, Google_ObjectAccessControl $postBody, $optParams = array()) {
      $params = array('bucket' => $bucket, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_ObjectAccessControl($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves default object ACL entries on the specified bucket. (defaultObjectAccessControls.list)
     *
     * @param string $bucket Name of a bucket.
     * @param array $optParams Optional parameters.
     * @return Google_ObjectAccessControls
     */
    public function listDefaultObjectAccessControls($bucket, $optParams = array()) {
      $params = array('bucket' => $bucket);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_ObjectAccessControls($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates a default object ACL entry on the specified bucket. This method supports patch semantics.
     * (defaultObjectAccessControls.patch)
     *
     * @param string $bucket Name of a bucket.
     * @param string $entity The entity holding the permission. Can be user-userId, group-groupId, allUsers, or allAuthenticatedUsers.
     * @param Google_ObjectAccessControl $postBody
     * @param array $optParams Optional parameters.
     * @return Google_ObjectAccessControl
     */
    public function patch($bucket, $entity, Google_ObjectAccessControl $postBody, $optParams = array()) {
      $params = array('bucket' => $bucket, 'entity' => $entity, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Google_ObjectAccessControl($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates a default object ACL entry on the specified bucket. (defaultObjectAccessControls.update)
     *
     * @param string $bucket Name of a bucket.
     * @param string $entity The entity holding the permission. Can be user-userId, group-groupId, allUsers, or allAuthenticatedUsers.
     * @param Google_ObjectAccessControl $postBody
     * @param array $optParams Optional parameters.
     * @return Google_ObjectAccessControl
     */
    public function update($bucket, $entity, Google_ObjectAccessControl $postBody, $optParams = array()) {
      $params = array('bucket' => $bucket, 'entity' => $entity, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Google_ObjectAccessControl($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "objectAccessControls" collection of methods.
   * Typical usage is:
   *  <code>
   *   $storageService = new Google_StorageService(...);
   *   $objectAccessControls = $storageService->objectAccessControls;
   *  </code>
   */
  class Google_ObjectAccessControlsServiceResource extends Google_ServiceResource {

    /**
     * Permanently deletes the ACL entry for the specified entity on the specified object.
     * (objectAccessControls.delete)
     *
     * @param string $bucket Name of a bucket.
     * @param string $object Name of the object.
     * @param string $entity The entity holding the permission. Can be user-userId, group-groupId, allUsers, or allAuthenticatedUsers.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string generation If present, selects a specific revision of this object (as opposed to the latest version, the default).
     */
    public function delete($bucket, $object, $entity, $optParams = array()) {
      $params = array('bucket' => $bucket, 'object' => $object, 'entity' => $entity);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
    /**
     * Returns the ACL entry for the specified entity on the specified object.
     * (objectAccessControls.get)
     *
     * @param string $bucket Name of a bucket.
     * @param string $object Name of the object.
     * @param string $entity The entity holding the permission. Can be user-userId, group-groupId, allUsers, or allAuthenticatedUsers.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string generation If present, selects a specific revision of this object (as opposed to the latest version, the default).
     * @return Google_ObjectAccessControl
     */
    public function get($bucket, $object, $entity, $optParams = array()) {
      $params = array('bucket' => $bucket, 'object' => $object, 'entity' => $entity);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_ObjectAccessControl($data);
      } else {
        return $data;
      }
    }
    /**
     * Creates a new ACL entry on the specified object. (objectAccessControls.insert)
     *
     * @param string $bucket Name of a bucket.
     * @param string $object Name of the object.
     * @param Google_ObjectAccessControl $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string generation If present, selects a specific revision of this object (as opposed to the latest version, the default).
     * @return Google_ObjectAccessControl
     */
    public function insert($bucket, $object, Google_ObjectAccessControl $postBody, $optParams = array()) {
      $params = array('bucket' => $bucket, 'object' => $object, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_ObjectAccessControl($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves ACL entries on the specified object. (objectAccessControls.list)
     *
     * @param string $bucket Name of a bucket.
     * @param string $object Name of the object.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string generation If present, selects a specific revision of this object (as opposed to the latest version, the default).
     * @return Google_ObjectAccessControls
     */
    public function listObjectAccessControls($bucket, $object, $optParams = array()) {
      $params = array('bucket' => $bucket, 'object' => $object);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_ObjectAccessControls($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates an ACL entry on the specified object. This method supports patch semantics.
     * (objectAccessControls.patch)
     *
     * @param string $bucket Name of a bucket.
     * @param string $object Name of the object.
     * @param string $entity The entity holding the permission. Can be user-userId, group-groupId, allUsers, or allAuthenticatedUsers.
     * @param Google_ObjectAccessControl $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string generation If present, selects a specific revision of this object (as opposed to the latest version, the default).
     * @return Google_ObjectAccessControl
     */
    public function patch($bucket, $object, $entity, Google_ObjectAccessControl $postBody, $optParams = array()) {
      $params = array('bucket' => $bucket, 'object' => $object, 'entity' => $entity, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Google_ObjectAccessControl($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates an ACL entry on the specified object. (objectAccessControls.update)
     *
     * @param string $bucket Name of a bucket.
     * @param string $object Name of the object.
     * @param string $entity The entity holding the permission. Can be user-userId, group-groupId, allUsers, or allAuthenticatedUsers.
     * @param Google_ObjectAccessControl $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string generation If present, selects a specific revision of this object (as opposed to the latest version, the default).
     * @return Google_ObjectAccessControl
     */
    public function update($bucket, $object, $entity, Google_ObjectAccessControl $postBody, $optParams = array()) {
      $params = array('bucket' => $bucket, 'object' => $object, 'entity' => $entity, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Google_ObjectAccessControl($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "objects" collection of methods.
   * Typical usage is:
   *  <code>
   *   $storageService = new Google_StorageService(...);
   *   $objects = $storageService->objects;
   *  </code>
   */
  class Google_ObjectsServiceResource extends Google_ServiceResource {

    /**
     * Concatenates a list of existing objects into a new object in the same bucket. (objects.compose)
     *
     * @param string $destinationBucket Name of the bucket in which to store the new object.
     * @param string $destinationObject Name of the new object.
     * @param Google_ComposeRequest $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string ifGenerationMatch Makes the operation conditional on whether the object's current generation matches the given value.
     * @opt_param string ifMetagenerationMatch Makes the operation conditional on whether the object's current metageneration matches the given value.
     * @return Google_StorageObject
     */
    public function compose($destinationBucket, $destinationObject, Google_ComposeRequest $postBody, $optParams = array()) {
      $params = array('destinationBucket' => $destinationBucket, 'destinationObject' => $destinationObject, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('compose', array($params));
      if ($this->useObjects()) {
        return new Google_StorageObject($data);
      } else {
        return $data;
      }
    }
    /**
     * Copies an object to a destination in the same location. Optionally overrides metadata.
     * (objects.copy)
     *
     * @param string $sourceBucket Name of the bucket in which to find the source object.
     * @param string $sourceObject Name of the source object.
     * @param string $destinationBucket Name of the bucket in which to store the new object. Overrides the provided object metadata's bucket value, if any.
     * @param string $destinationObject Name of the new object. Required when the object metadata is not otherwise provided. Overrides the object metadata's name value, if any.
     * @param Google_StorageObject $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string ifGenerationMatch Makes the operation conditional on whether the destination object's current generation matches the given value.
     * @opt_param string ifGenerationNotMatch Makes the operation conditional on whether the destination object's current generation does not match the given value.
     * @opt_param string ifMetagenerationMatch Makes the operation conditional on whether the destination object's current metageneration matches the given value.
     * @opt_param string ifMetagenerationNotMatch Makes the operation conditional on whether the destination object's current metageneration does not match the given value.
     * @opt_param string ifSourceGenerationMatch Makes the operation conditional on whether the source object's generation matches the given value.
     * @opt_param string ifSourceGenerationNotMatch Makes the operation conditional on whether the source object's generation does not match the given value.
     * @opt_param string ifSourceMetagenerationMatch Makes the operation conditional on whether the source object's current metageneration matches the given value.
     * @opt_param string ifSourceMetagenerationNotMatch Makes the operation conditional on whether the source object's current metageneration does not match the given value.
     * @opt_param string projection Set of properties to return. Defaults to noAcl, unless the object resource specifies the acl property, when it defaults to full.
     * @opt_param string sourceGeneration If present, selects a specific revision of the source object (as opposed to the latest version, the default).
     * @return Google_StorageObject
     */
    public function copy($sourceBucket, $sourceObject, $destinationBucket, $destinationObject, Google_StorageObject $postBody, $optParams = array()) {
      $params = array('sourceBucket' => $sourceBucket, 'sourceObject' => $sourceObject, 'destinationBucket' => $destinationBucket, 'destinationObject' => $destinationObject, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('copy', array($params));
      if ($this->useObjects()) {
        return new Google_StorageObject($data);
      } else {
        return $data;
      }
    }
    /**
     * Deletes data blobs and associated metadata. Deletions are permanent if versioning is not enabled
     * for the bucket, or if the generation parameter is used. (objects.delete)
     *
     * @param string $bucket Name of the bucket in which the object resides.
     * @param string $object Name of the object.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string generation If present, permanently deletes a specific revision of this object (as opposed to the latest version, the default).
     * @opt_param string ifGenerationMatch Makes the operation conditional on whether the object's current generation matches the given value.
     * @opt_param string ifGenerationNotMatch Makes the operation conditional on whether the object's current generation does not match the given value.
     * @opt_param string ifMetagenerationMatch Makes the operation conditional on whether the object's current metageneration matches the given value.
     * @opt_param string ifMetagenerationNotMatch Makes the operation conditional on whether the object's current metageneration does not match the given value.
     */
    public function delete($bucket, $object, $optParams = array()) {
      $params = array('bucket' => $bucket, 'object' => $object);
      $params = array_merge($params, $optParams);
      $data = $this->__call('delete', array($params));
      return $data;
    }
    /**
     * Retrieves objects or their associated metadata. (objects.get)
     *
     * @param string $bucket Name of the bucket in which the object resides.
     * @param string $object Name of the object.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string generation If present, selects a specific revision of this object (as opposed to the latest version, the default).
     * @opt_param string ifGenerationMatch Makes the operation conditional on whether the object's generation matches the given value.
     * @opt_param string ifGenerationNotMatch Makes the operation conditional on whether the object's generation does not match the given value.
     * @opt_param string ifMetagenerationMatch Makes the operation conditional on whether the object's current metageneration matches the given value.
     * @opt_param string ifMetagenerationNotMatch Makes the operation conditional on whether the object's current metageneration does not match the given value.
     * @opt_param string projection Set of properties to return. Defaults to noAcl.
     * @return Google_StorageObject
     */
    public function get($bucket, $object, $optParams = array()) {
      $params = array('bucket' => $bucket, 'object' => $object);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Google_StorageObject($data);
      } else {
        return $data;
      }
    }
    /**
     * Stores new data blobs and associated metadata. (objects.insert)
     *
     * @param string $bucket Name of the bucket in which to store the new object. Overrides the provided object metadata's bucket value, if any.
     * @param Google_StorageObject $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string ifGenerationMatch Makes the operation conditional on whether the object's current generation matches the given value.
     * @opt_param string ifGenerationNotMatch Makes the operation conditional on whether the object's current generation does not match the given value.
     * @opt_param string ifMetagenerationMatch Makes the operation conditional on whether the object's current metageneration matches the given value.
     * @opt_param string ifMetagenerationNotMatch Makes the operation conditional on whether the object's current metageneration does not match the given value.
     * @opt_param string name Name of the object. Required when the object metadata is not otherwise provided. Overrides the object metadata's name value, if any.
     * @opt_param string projection Set of properties to return. Defaults to noAcl, unless the object resource specifies the acl property, when it defaults to full.
     * @return Google_StorageObject
     */
    public function insert($bucket, Google_StorageObject $postBody, $optParams = array()) {
      $params = array('bucket' => $bucket, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('insert', array($params));
      if ($this->useObjects()) {
        return new Google_StorageObject($data);
      } else {
        return $data;
      }
    }
    /**
     * Retrieves a list of objects matching the criteria. (objects.list)
     *
     * @param string $bucket Name of the bucket in which to look for objects.
     * @param array $optParams Optional parameters.
     *
     * @opt_param string delimiter Returns results in a directory-like mode. items will contain only objects whose names, aside from the prefix, do not contain delimiter. Objects whose names, aside from the prefix, contain delimiter will have their name, truncated after the delimiter, returned in prefixes. Duplicate prefixes are omitted.
     * @opt_param string maxResults Maximum number of items plus prefixes to return. As duplicate prefixes are omitted, fewer total results may be returned than requested.
     * @opt_param string pageToken A previously-returned page token representing part of the larger set of results to view.
     * @opt_param string prefix Filter results to objects whose names begin with this prefix.
     * @opt_param string projection Set of properties to return. Defaults to noAcl.
     * @opt_param bool versions If true, lists all versions of a file as distinct results.
     * @return Google_Objects
     */
    public function listObjects($bucket, $optParams = array()) {
      $params = array('bucket' => $bucket);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new Google_Objects($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates a data blob's associated metadata. This method supports patch semantics. (objects.patch)
     *
     * @param string $bucket Name of the bucket in which the object resides.
     * @param string $object Name of the object.
     * @param Google_StorageObject $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string generation If present, selects a specific revision of this object (as opposed to the latest version, the default).
     * @opt_param string ifGenerationMatch Makes the operation conditional on whether the object's current generation matches the given value.
     * @opt_param string ifGenerationNotMatch Makes the operation conditional on whether the object's current generation does not match the given value.
     * @opt_param string ifMetagenerationMatch Makes the operation conditional on whether the object's current metageneration matches the given value.
     * @opt_param string ifMetagenerationNotMatch Makes the operation conditional on whether the object's current metageneration does not match the given value.
     * @opt_param string projection Set of properties to return. Defaults to full.
     * @return Google_StorageObject
     */
    public function patch($bucket, $object, Google_StorageObject $postBody, $optParams = array()) {
      $params = array('bucket' => $bucket, 'object' => $object, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('patch', array($params));
      if ($this->useObjects()) {
        return new Google_StorageObject($data);
      } else {
        return $data;
      }
    }
    /**
     * Updates a data blob's associated metadata. (objects.update)
     *
     * @param string $bucket Name of the bucket in which the object resides.
     * @param string $object Name of the object.
     * @param Google_StorageObject $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string generation If present, selects a specific revision of this object (as opposed to the latest version, the default).
     * @opt_param string ifGenerationMatch Makes the operation conditional on whether the object's current generation matches the given value.
     * @opt_param string ifGenerationNotMatch Makes the operation conditional on whether the object's current generation does not match the given value.
     * @opt_param string ifMetagenerationMatch Makes the operation conditional on whether the object's current metageneration matches the given value.
     * @opt_param string ifMetagenerationNotMatch Makes the operation conditional on whether the object's current metageneration does not match the given value.
     * @opt_param string projection Set of properties to return. Defaults to full.
     * @return Google_StorageObject
     */
    public function update($bucket, $object, Google_StorageObject $postBody, $optParams = array()) {
      $params = array('bucket' => $bucket, 'object' => $object, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('update', array($params));
      if ($this->useObjects()) {
        return new Google_StorageObject($data);
      } else {
        return $data;
      }
    }
    /**
     * Watch for changes on all objects in a bucket. (objects.watchAll)
     *
     * @param string $bucket Name of the bucket in which to look for objects.
     * @param Google_Channel $postBody
     * @param array $optParams Optional parameters.
     *
     * @opt_param string delimiter Returns results in a directory-like mode. items will contain only objects whose names, aside from the prefix, do not contain delimiter. Objects whose names, aside from the prefix, contain delimiter will have their name, truncated after the delimiter, returned in prefixes. Duplicate prefixes are omitted.
     * @opt_param string maxResults Maximum number of items plus prefixes to return. As duplicate prefixes are omitted, fewer total results may be returned than requested.
     * @opt_param string pageToken A previously-returned page token representing part of the larger set of results to view.
     * @opt_param string prefix Filter results to objects whose names begin with this prefix.
     * @opt_param string projection Set of properties to return. Defaults to noAcl.
     * @opt_param bool versions If true, lists all versions of a file as distinct results.
     * @return Google_Channel
     */
    public function watchAll($bucket, Google_Channel $postBody, $optParams = array()) {
      $params = array('bucket' => $bucket, 'postBody' => $postBody);
      $params = array_merge($params, $optParams);
      $data = $this->__call('watchAll', array($params));
      if ($this->useObjects()) {
        return new Google_Channel($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Google_Storage (v1beta2).
 *
 * <p>
 * Lets you store and retrieve potentially-large, immutable data objects.
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://developers.google.com/storage/docs/json_api/" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_StorageService extends Google_Service {
  public $bucketAccessControls;
  public $buckets;
  public $channels;
  public $defaultObjectAccessControls;
  public $objectAccessControls;
  public $objects;
  /**
   * Constructs the internal representation of the Storage service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client) {
    $this->servicePath = 'storage/v1beta2/';
    $this->version = 'v1beta2';
    $this->serviceName = 'storage';

    $client->addService($this->serviceName, $this->version);
    $this->bucketAccessControls = new Google_BucketAccessControlsServiceResource($this, $this->serviceName, 'bucketAccessControls', json_decode('{"methods": {"delete": {"id": "storage.bucketAccessControls.delete", "path": "b/{bucket}/acl/{entity}", "httpMethod": "DELETE", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "entity": {"type": "string", "required": true, "location": "path"}}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}, "get": {"id": "storage.bucketAccessControls.get", "path": "b/{bucket}/acl/{entity}", "httpMethod": "GET", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "entity": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "BucketAccessControl"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}, "insert": {"id": "storage.bucketAccessControls.insert", "path": "b/{bucket}/acl", "httpMethod": "POST", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "BucketAccessControl"}, "response": {"$ref": "BucketAccessControl"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}, "list": {"id": "storage.bucketAccessControls.list", "path": "b/{bucket}/acl", "httpMethod": "GET", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "BucketAccessControls"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}, "patch": {"id": "storage.bucketAccessControls.patch", "path": "b/{bucket}/acl/{entity}", "httpMethod": "PATCH", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "entity": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "BucketAccessControl"}, "response": {"$ref": "BucketAccessControl"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}, "update": {"id": "storage.bucketAccessControls.update", "path": "b/{bucket}/acl/{entity}", "httpMethod": "PUT", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "entity": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "BucketAccessControl"}, "response": {"$ref": "BucketAccessControl"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}}}', true));
    $this->buckets = new Google_BucketsServiceResource($this, $this->serviceName, 'buckets', json_decode('{"methods": {"delete": {"id": "storage.buckets.delete", "path": "b/{bucket}", "httpMethod": "DELETE", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "ifMetagenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifMetagenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control", "https://www.googleapis.com/auth/devstorage.read_write"]}, "get": {"id": "storage.buckets.get", "path": "b/{bucket}", "httpMethod": "GET", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "ifMetagenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifMetagenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}, "projection": {"type": "string", "enum": ["full", "noAcl"], "location": "query"}}, "response": {"$ref": "Bucket"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control", "https://www.googleapis.com/auth/devstorage.read_only", "https://www.googleapis.com/auth/devstorage.read_write"]}, "insert": {"id": "storage.buckets.insert", "path": "b", "httpMethod": "POST", "parameters": {"project": {"type": "string", "required": true, "location": "query"}, "projection": {"type": "string", "enum": ["full", "noAcl"], "location": "query"}}, "request": {"$ref": "Bucket"}, "response": {"$ref": "Bucket"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control", "https://www.googleapis.com/auth/devstorage.read_write"]}, "list": {"id": "storage.buckets.list", "path": "b", "httpMethod": "GET", "parameters": {"maxResults": {"type": "integer", "format": "uint32", "minimum": "0", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "project": {"type": "string", "required": true, "location": "query"}, "projection": {"type": "string", "enum": ["full", "noAcl"], "location": "query"}}, "response": {"$ref": "Buckets"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control", "https://www.googleapis.com/auth/devstorage.read_only", "https://www.googleapis.com/auth/devstorage.read_write"]}, "patch": {"id": "storage.buckets.patch", "path": "b/{bucket}", "httpMethod": "PATCH", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "ifMetagenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifMetagenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}, "projection": {"type": "string", "enum": ["full", "noAcl"], "location": "query"}}, "request": {"$ref": "Bucket"}, "response": {"$ref": "Bucket"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control", "https://www.googleapis.com/auth/devstorage.read_write"]}, "update": {"id": "storage.buckets.update", "path": "b/{bucket}", "httpMethod": "PUT", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "ifMetagenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifMetagenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}, "projection": {"type": "string", "enum": ["full", "noAcl"], "location": "query"}}, "request": {"$ref": "Bucket"}, "response": {"$ref": "Bucket"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control", "https://www.googleapis.com/auth/devstorage.read_write"]}}}', true));
    $this->channels = new Google_ChannelsServiceResource($this, $this->serviceName, 'channels', json_decode('{"methods": {"stop": {"id": "storage.channels.stop", "path": "channels/stop", "httpMethod": "POST", "request": {"$ref": "Channel"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control", "https://www.googleapis.com/auth/devstorage.read_only", "https://www.googleapis.com/auth/devstorage.read_write"]}}}', true));
    $this->defaultObjectAccessControls = new Google_DefaultObjectAccessControlsServiceResource($this, $this->serviceName, 'defaultObjectAccessControls', json_decode('{"methods": {"delete": {"id": "storage.defaultObjectAccessControls.delete", "path": "b/{bucket}/defaultObjectAcl/{entity}", "httpMethod": "DELETE", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "entity": {"type": "string", "required": true, "location": "path"}}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}, "get": {"id": "storage.defaultObjectAccessControls.get", "path": "b/{bucket}/defaultObjectAcl/{entity}", "httpMethod": "GET", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "entity": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "ObjectAccessControl"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}, "insert": {"id": "storage.defaultObjectAccessControls.insert", "path": "b/{bucket}/defaultObjectAcl", "httpMethod": "POST", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "ObjectAccessControl"}, "response": {"$ref": "ObjectAccessControl"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}, "list": {"id": "storage.defaultObjectAccessControls.list", "path": "b/{bucket}/defaultObjectAcl", "httpMethod": "GET", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "ObjectAccessControls"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}, "patch": {"id": "storage.defaultObjectAccessControls.patch", "path": "b/{bucket}/defaultObjectAcl/{entity}", "httpMethod": "PATCH", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "entity": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "ObjectAccessControl"}, "response": {"$ref": "ObjectAccessControl"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}, "update": {"id": "storage.defaultObjectAccessControls.update", "path": "b/{bucket}/defaultObjectAcl/{entity}", "httpMethod": "PUT", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "entity": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "ObjectAccessControl"}, "response": {"$ref": "ObjectAccessControl"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}}}', true));
    $this->objectAccessControls = new Google_ObjectAccessControlsServiceResource($this, $this->serviceName, 'objectAccessControls', json_decode('{"methods": {"delete": {"id": "storage.objectAccessControls.delete", "path": "b/{bucket}/o/{object}/acl/{entity}", "httpMethod": "DELETE", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "entity": {"type": "string", "required": true, "location": "path"}, "generation": {"type": "string", "format": "uint64", "location": "query"}, "object": {"type": "string", "required": true, "location": "path"}}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}, "get": {"id": "storage.objectAccessControls.get", "path": "b/{bucket}/o/{object}/acl/{entity}", "httpMethod": "GET", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "entity": {"type": "string", "required": true, "location": "path"}, "generation": {"type": "string", "format": "uint64", "location": "query"}, "object": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "ObjectAccessControl"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}, "insert": {"id": "storage.objectAccessControls.insert", "path": "b/{bucket}/o/{object}/acl", "httpMethod": "POST", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "generation": {"type": "string", "format": "uint64", "location": "query"}, "object": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "ObjectAccessControl"}, "response": {"$ref": "ObjectAccessControl"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}, "list": {"id": "storage.objectAccessControls.list", "path": "b/{bucket}/o/{object}/acl", "httpMethod": "GET", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "generation": {"type": "string", "format": "uint64", "location": "query"}, "object": {"type": "string", "required": true, "location": "path"}}, "response": {"$ref": "ObjectAccessControls"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}, "patch": {"id": "storage.objectAccessControls.patch", "path": "b/{bucket}/o/{object}/acl/{entity}", "httpMethod": "PATCH", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "entity": {"type": "string", "required": true, "location": "path"}, "generation": {"type": "string", "format": "uint64", "location": "query"}, "object": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "ObjectAccessControl"}, "response": {"$ref": "ObjectAccessControl"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}, "update": {"id": "storage.objectAccessControls.update", "path": "b/{bucket}/o/{object}/acl/{entity}", "httpMethod": "PUT", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "entity": {"type": "string", "required": true, "location": "path"}, "generation": {"type": "string", "format": "uint64", "location": "query"}, "object": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "ObjectAccessControl"}, "response": {"$ref": "ObjectAccessControl"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control"]}}}', true));
    $this->objects = new Google_ObjectsServiceResource($this, $this->serviceName, 'objects', json_decode('{"methods": {"compose": {"id": "storage.objects.compose", "path": "b/{destinationBucket}/o/{destinationObject}/compose", "httpMethod": "POST", "parameters": {"destinationBucket": {"type": "string", "required": true, "location": "path"}, "destinationObject": {"type": "string", "required": true, "location": "path"}, "ifGenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifMetagenerationMatch": {"type": "string", "format": "uint64", "location": "query"}}, "request": {"$ref": "ComposeRequest"}, "response": {"$ref": "Object"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control", "https://www.googleapis.com/auth/devstorage.read_write"], "supportsMediaDownload": true}, "copy": {"id": "storage.objects.copy", "path": "b/{sourceBucket}/o/{sourceObject}/copyTo/b/{destinationBucket}/o/{destinationObject}", "httpMethod": "POST", "parameters": {"destinationBucket": {"type": "string", "required": true, "location": "path"}, "destinationObject": {"type": "string", "required": true, "location": "path"}, "ifGenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifGenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifMetagenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifMetagenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifSourceGenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifSourceGenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifSourceMetagenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifSourceMetagenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}, "projection": {"type": "string", "enum": ["full", "noAcl"], "location": "query"}, "sourceBucket": {"type": "string", "required": true, "location": "path"}, "sourceGeneration": {"type": "string", "format": "uint64", "location": "query"}, "sourceObject": {"type": "string", "required": true, "location": "path"}}, "request": {"$ref": "Object"}, "response": {"$ref": "Object"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control", "https://www.googleapis.com/auth/devstorage.read_write"], "supportsMediaDownload": true}, "delete": {"id": "storage.objects.delete", "path": "b/{bucket}/o/{object}", "httpMethod": "DELETE", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "generation": {"type": "string", "format": "uint64", "location": "query"}, "ifGenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifGenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifMetagenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifMetagenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}, "object": {"type": "string", "required": true, "location": "path"}}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control", "https://www.googleapis.com/auth/devstorage.read_write"]}, "get": {"id": "storage.objects.get", "path": "b/{bucket}/o/{object}", "httpMethod": "GET", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "generation": {"type": "string", "format": "uint64", "location": "query"}, "ifGenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifGenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifMetagenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifMetagenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}, "object": {"type": "string", "required": true, "location": "path"}, "projection": {"type": "string", "enum": ["full", "noAcl"], "location": "query"}}, "response": {"$ref": "Object"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control", "https://www.googleapis.com/auth/devstorage.read_only", "https://www.googleapis.com/auth/devstorage.read_write"], "supportsMediaDownload": true}, "insert": {"id": "storage.objects.insert", "path": "b/{bucket}/o", "httpMethod": "POST", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "ifGenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifGenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifMetagenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifMetagenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}, "name": {"type": "string", "location": "query"}, "projection": {"type": "string", "enum": ["full", "noAcl"], "location": "query"}}, "request": {"$ref": "Object"}, "response": {"$ref": "Object"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control", "https://www.googleapis.com/auth/devstorage.read_write"], "supportsMediaDownload": true, "supportsMediaUpload": true, "mediaUpload": {"accept": ["*/*"], "protocols": {"simple": {"multipart": true, "path": "/upload/storage/v1beta2/b/{bucket}/o"}, "resumable": {"multipart": true, "path": "/resumable/upload/storage/v1beta2/b/{bucket}/o"}}}}, "list": {"id": "storage.objects.list", "path": "b/{bucket}/o", "httpMethod": "GET", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "delimiter": {"type": "string", "location": "query"}, "maxResults": {"type": "integer", "format": "uint32", "minimum": "0", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "prefix": {"type": "string", "location": "query"}, "projection": {"type": "string", "enum": ["full", "noAcl"], "location": "query"}, "versions": {"type": "boolean", "location": "query"}}, "response": {"$ref": "Objects"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control", "https://www.googleapis.com/auth/devstorage.read_only", "https://www.googleapis.com/auth/devstorage.read_write"], "supportsSubscription": true}, "patch": {"id": "storage.objects.patch", "path": "b/{bucket}/o/{object}", "httpMethod": "PATCH", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "generation": {"type": "string", "format": "uint64", "location": "query"}, "ifGenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifGenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifMetagenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifMetagenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}, "object": {"type": "string", "required": true, "location": "path"}, "projection": {"type": "string", "enum": ["full", "noAcl"], "location": "query"}}, "request": {"$ref": "Object"}, "response": {"$ref": "Object"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control", "https://www.googleapis.com/auth/devstorage.read_write"]}, "update": {"id": "storage.objects.update", "path": "b/{bucket}/o/{object}", "httpMethod": "PUT", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "generation": {"type": "string", "format": "uint64", "location": "query"}, "ifGenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifGenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifMetagenerationMatch": {"type": "string", "format": "uint64", "location": "query"}, "ifMetagenerationNotMatch": {"type": "string", "format": "uint64", "location": "query"}, "object": {"type": "string", "required": true, "location": "path"}, "projection": {"type": "string", "enum": ["full", "noAcl"], "location": "query"}}, "request": {"$ref": "Object"}, "response": {"$ref": "Object"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control", "https://www.googleapis.com/auth/devstorage.read_write"], "supportsMediaDownload": true}, "watchAll": {"id": "storage.objects.watchAll", "path": "b/{bucket}/o/watch", "httpMethod": "POST", "parameters": {"bucket": {"type": "string", "required": true, "location": "path"}, "delimiter": {"type": "string", "location": "query"}, "maxResults": {"type": "integer", "format": "uint32", "minimum": "0", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "prefix": {"type": "string", "location": "query"}, "projection": {"type": "string", "enum": ["full", "noAcl"], "location": "query"}, "versions": {"type": "boolean", "location": "query"}}, "request": {"$ref": "Channel"}, "response": {"$ref": "Channel"}, "scopes": ["https://www.googleapis.com/auth/devstorage.full_control", "https://www.googleapis.com/auth/devstorage.read_only", "https://www.googleapis.com/auth/devstorage.read_write"], "supportsSubscription": true}}}', true));

  }
}



class Google_Bucket extends Google_Model {
  protected $__aclType = 'Google_BucketAccessControl';
  protected $__aclDataType = 'array';
  public $acl;
  protected $__corsType = 'Google_BucketCors';
  protected $__corsDataType = 'array';
  public $cors;
  protected $__defaultObjectAclType = 'Google_ObjectAccessControl';
  protected $__defaultObjectAclDataType = 'array';
  public $defaultObjectAcl;
  public $etag;
  public $id;
  public $kind;
  protected $__lifecycleType = 'Google_BucketLifecycle';
  protected $__lifecycleDataType = '';
  public $lifecycle;
  public $location;
  protected $__loggingType = 'Google_BucketLogging';
  protected $__loggingDataType = '';
  public $logging;
  public $metageneration;
  public $name;
  protected $__ownerType = 'Google_BucketOwner';
  protected $__ownerDataType = '';
  public $owner;
  public $selfLink;
  public $storageClass;
  public $timeCreated;
  protected $__versioningType = 'Google_BucketVersioning';
  protected $__versioningDataType = '';
  public $versioning;
  protected $__websiteType = 'Google_BucketWebsite';
  protected $__websiteDataType = '';
  public $website;
  public function setAcl(/* array(Google_BucketAccessControl) */ $acl) {
    $this->assertIsArray($acl, 'Google_BucketAccessControl', __METHOD__);
    $this->acl = $acl;
  }
  public function getAcl() {
    return $this->acl;
  }
  public function setCors(/* array(Google_BucketCors) */ $cors) {
    $this->assertIsArray($cors, 'Google_BucketCors', __METHOD__);
    $this->cors = $cors;
  }
  public function getCors() {
    return $this->cors;
  }
  public function setDefaultObjectAcl(/* array(Google_ObjectAccessControl) */ $defaultObjectAcl) {
    $this->assertIsArray($defaultObjectAcl, 'Google_ObjectAccessControl', __METHOD__);
    $this->defaultObjectAcl = $defaultObjectAcl;
  }
  public function getDefaultObjectAcl() {
    return $this->defaultObjectAcl;
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
  public function setLifecycle(Google_BucketLifecycle $lifecycle) {
    $this->lifecycle = $lifecycle;
  }
  public function getLifecycle() {
    return $this->lifecycle;
  }
  public function setLocation( $location) {
    $this->location = $location;
  }
  public function getLocation() {
    return $this->location;
  }
  public function setLogging(Google_BucketLogging $logging) {
    $this->logging = $logging;
  }
  public function getLogging() {
    return $this->logging;
  }
  public function setMetageneration( $metageneration) {
    $this->metageneration = $metageneration;
  }
  public function getMetageneration() {
    return $this->metageneration;
  }
  public function setName( $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setOwner(Google_BucketOwner $owner) {
    $this->owner = $owner;
  }
  public function getOwner() {
    return $this->owner;
  }
  public function setSelfLink( $selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
  public function setStorageClass( $storageClass) {
    $this->storageClass = $storageClass;
  }
  public function getStorageClass() {
    return $this->storageClass;
  }
  public function setTimeCreated( $timeCreated) {
    $this->timeCreated = $timeCreated;
  }
  public function getTimeCreated() {
    return $this->timeCreated;
  }
  public function setVersioning(Google_BucketVersioning $versioning) {
    $this->versioning = $versioning;
  }
  public function getVersioning() {
    return $this->versioning;
  }
  public function setWebsite(Google_BucketWebsite $website) {
    $this->website = $website;
  }
  public function getWebsite() {
    return $this->website;
  }
}

class Google_BucketAccessControl extends Google_Model {
  public $bucket;
  public $domain;
  public $email;
  public $entity;
  public $entityId;
  public $etag;
  public $id;
  public $kind;
  public $role;
  public $selfLink;
  public function setBucket( $bucket) {
    $this->bucket = $bucket;
  }
  public function getBucket() {
    return $this->bucket;
  }
  public function setDomain( $domain) {
    $this->domain = $domain;
  }
  public function getDomain() {
    return $this->domain;
  }
  public function setEmail( $email) {
    $this->email = $email;
  }
  public function getEmail() {
    return $this->email;
  }
  public function setEntity( $entity) {
    $this->entity = $entity;
  }
  public function getEntity() {
    return $this->entity;
  }
  public function setEntityId( $entityId) {
    $this->entityId = $entityId;
  }
  public function getEntityId() {
    return $this->entityId;
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
  public function setRole( $role) {
    $this->role = $role;
  }
  public function getRole() {
    return $this->role;
  }
  public function setSelfLink( $selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
}

class Google_BucketAccessControls extends Google_Model {
  protected $__itemsType = 'Google_BucketAccessControl';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(Google_BucketAccessControl) */ $items) {
    $this->assertIsArray($items, 'Google_BucketAccessControl', __METHOD__);
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

class Google_BucketCors extends Google_Model {
  public $maxAgeSeconds;
  public $method;
  public $origin;
  public $responseHeader;
  public function setMaxAgeSeconds( $maxAgeSeconds) {
    $this->maxAgeSeconds = $maxAgeSeconds;
  }
  public function getMaxAgeSeconds() {
    return $this->maxAgeSeconds;
  }
  public function setMethod(/* array(Google_string) */ $method) {
    $this->assertIsArray($method, 'Google_string', __METHOD__);
    $this->method = $method;
  }
  public function getMethod() {
    return $this->method;
  }
  public function setOrigin(/* array(Google_string) */ $origin) {
    $this->assertIsArray($origin, 'Google_string', __METHOD__);
    $this->origin = $origin;
  }
  public function getOrigin() {
    return $this->origin;
  }
  public function setResponseHeader(/* array(Google_string) */ $responseHeader) {
    $this->assertIsArray($responseHeader, 'Google_string', __METHOD__);
    $this->responseHeader = $responseHeader;
  }
  public function getResponseHeader() {
    return $this->responseHeader;
  }
}

class Google_BucketLifecycle extends Google_Model {
  protected $__ruleType = 'Google_BucketLifecycleRule';
  protected $__ruleDataType = 'array';
  public $rule;
  public function setRule(/* array(Google_BucketLifecycleRule) */ $rule) {
    $this->assertIsArray($rule, 'Google_BucketLifecycleRule', __METHOD__);
    $this->rule = $rule;
  }
  public function getRule() {
    return $this->rule;
  }
}

class Google_BucketLifecycleRule extends Google_Model {
  protected $__actionType = 'Google_BucketLifecycleRuleAction';
  protected $__actionDataType = '';
  public $action;
  protected $__conditionType = 'Google_BucketLifecycleRuleCondition';
  protected $__conditionDataType = '';
  public $condition;
  public function setAction(Google_BucketLifecycleRuleAction $action) {
    $this->action = $action;
  }
  public function getAction() {
    return $this->action;
  }
  public function setCondition(Google_BucketLifecycleRuleCondition $condition) {
    $this->condition = $condition;
  }
  public function getCondition() {
    return $this->condition;
  }
}

class Google_BucketLifecycleRuleAction extends Google_Model {
  public $type;
  public function setType( $type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}

class Google_BucketLifecycleRuleCondition extends Google_Model {
  public $age;
  public $createdBefore;
  public $isLive;
  public $numNewerVersions;
  public function setAge( $age) {
    $this->age = $age;
  }
  public function getAge() {
    return $this->age;
  }
  public function setCreatedBefore( $createdBefore) {
    $this->createdBefore = $createdBefore;
  }
  public function getCreatedBefore() {
    return $this->createdBefore;
  }
  public function setIsLive( $isLive) {
    $this->isLive = $isLive;
  }
  public function getIsLive() {
    return $this->isLive;
  }
  public function setNumNewerVersions( $numNewerVersions) {
    $this->numNewerVersions = $numNewerVersions;
  }
  public function getNumNewerVersions() {
    return $this->numNewerVersions;
  }
}

class Google_BucketLogging extends Google_Model {
  public $logBucket;
  public $logObjectPrefix;
  public function setLogBucket( $logBucket) {
    $this->logBucket = $logBucket;
  }
  public function getLogBucket() {
    return $this->logBucket;
  }
  public function setLogObjectPrefix( $logObjectPrefix) {
    $this->logObjectPrefix = $logObjectPrefix;
  }
  public function getLogObjectPrefix() {
    return $this->logObjectPrefix;
  }
}

class Google_BucketOwner extends Google_Model {
  public $entity;
  public $entityId;
  public function setEntity( $entity) {
    $this->entity = $entity;
  }
  public function getEntity() {
    return $this->entity;
  }
  public function setEntityId( $entityId) {
    $this->entityId = $entityId;
  }
  public function getEntityId() {
    return $this->entityId;
  }
}

class Google_BucketVersioning extends Google_Model {
  public $enabled;
  public function setEnabled( $enabled) {
    $this->enabled = $enabled;
  }
  public function getEnabled() {
    return $this->enabled;
  }
}

class Google_BucketWebsite extends Google_Model {
  public $mainPageSuffix;
  public $notFoundPage;
  public function setMainPageSuffix( $mainPageSuffix) {
    $this->mainPageSuffix = $mainPageSuffix;
  }
  public function getMainPageSuffix() {
    return $this->mainPageSuffix;
  }
  public function setNotFoundPage( $notFoundPage) {
    $this->notFoundPage = $notFoundPage;
  }
  public function getNotFoundPage() {
    return $this->notFoundPage;
  }
}

class Google_Buckets extends Google_Model {
  protected $__itemsType = 'Google_Bucket';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public function setItems(/* array(Google_Bucket) */ $items) {
    $this->assertIsArray($items, 'Google_Bucket', __METHOD__);
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

class Google_Channel extends Google_Model {
  public $address;
  public $expiration;
  public $id;
  public $kind;
  public $params;
  public $resourceId;
  public $resourceUri;
  public $token;
  public $type;
  public function setAddress( $address) {
    $this->address = $address;
  }
  public function getAddress() {
    return $this->address;
  }
  public function setExpiration( $expiration) {
    $this->expiration = $expiration;
  }
  public function getExpiration() {
    return $this->expiration;
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
  public function setParams( $params) {
    $this->params = $params;
  }
  public function getParams() {
    return $this->params;
  }
  public function setResourceId( $resourceId) {
    $this->resourceId = $resourceId;
  }
  public function getResourceId() {
    return $this->resourceId;
  }
  public function setResourceUri( $resourceUri) {
    $this->resourceUri = $resourceUri;
  }
  public function getResourceUri() {
    return $this->resourceUri;
  }
  public function setToken( $token) {
    $this->token = $token;
  }
  public function getToken() {
    return $this->token;
  }
  public function setType( $type) {
    $this->type = $type;
  }
  public function getType() {
    return $this->type;
  }
}

class Google_ComposeRequest extends Google_Model {
  protected $__destinationType = 'Google_StorageObject';
  protected $__destinationDataType = '';
  public $destination;
  public $kind;
  protected $__sourceObjectsType = 'Google_ComposeRequestSourceObjects';
  protected $__sourceObjectsDataType = 'array';
  public $sourceObjects;
  public function setDestination(Google_StorageObject $destination) {
    $this->destination = $destination;
  }
  public function getDestination() {
    return $this->destination;
  }
  public function setKind( $kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setSourceObjects(/* array(Google_ComposeRequestSourceObjects) */ $sourceObjects) {
    $this->assertIsArray($sourceObjects, 'Google_ComposeRequestSourceObjects', __METHOD__);
    $this->sourceObjects = $sourceObjects;
  }
  public function getSourceObjects() {
    return $this->sourceObjects;
  }
}

class Google_ComposeRequestSourceObjects extends Google_Model {
  public $generation;
  public $name;
  protected $__objectPreconditionsType = 'Google_ComposeRequestSourceObjectsObjectPreconditions';
  protected $__objectPreconditionsDataType = '';
  public $objectPreconditions;
  public function setGeneration( $generation) {
    $this->generation = $generation;
  }
  public function getGeneration() {
    return $this->generation;
  }
  public function setName( $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setObjectPreconditions(Google_ComposeRequestSourceObjectsObjectPreconditions $objectPreconditions) {
    $this->objectPreconditions = $objectPreconditions;
  }
  public function getObjectPreconditions() {
    return $this->objectPreconditions;
  }
}

class Google_ComposeRequestSourceObjectsObjectPreconditions extends Google_Model {
  public $ifGenerationMatch;
  public function setIfGenerationMatch( $ifGenerationMatch) {
    $this->ifGenerationMatch = $ifGenerationMatch;
  }
  public function getIfGenerationMatch() {
    return $this->ifGenerationMatch;
  }
}

class Google_ObjectAccessControl extends Google_Model {
  public $bucket;
  public $domain;
  public $email;
  public $entity;
  public $entityId;
  public $etag;
  public $generation;
  public $id;
  public $kind;
  public $object;
  public $role;
  public $selfLink;
  public function setBucket( $bucket) {
    $this->bucket = $bucket;
  }
  public function getBucket() {
    return $this->bucket;
  }
  public function setDomain( $domain) {
    $this->domain = $domain;
  }
  public function getDomain() {
    return $this->domain;
  }
  public function setEmail( $email) {
    $this->email = $email;
  }
  public function getEmail() {
    return $this->email;
  }
  public function setEntity( $entity) {
    $this->entity = $entity;
  }
  public function getEntity() {
    return $this->entity;
  }
  public function setEntityId( $entityId) {
    $this->entityId = $entityId;
  }
  public function getEntityId() {
    return $this->entityId;
  }
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setGeneration( $generation) {
    $this->generation = $generation;
  }
  public function getGeneration() {
    return $this->generation;
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
  public function setObject( $object) {
    $this->object = $object;
  }
  public function getObject() {
    return $this->object;
  }
  public function setRole( $role) {
    $this->role = $role;
  }
  public function getRole() {
    return $this->role;
  }
  public function setSelfLink( $selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
}

class Google_ObjectAccessControls extends Google_Model {
  public $items;
  public $kind;
  public function setItems(/* array(Google_object) */ $items) {
    $this->assertIsArray($items, 'Google_object', __METHOD__);
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

class Google_Objects extends Google_Model {
  protected $__itemsType = 'Google_StorageObject';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $nextPageToken;
  public $prefixes;
  public function setItems(/* array(Google_StorageObject) */ $items) {
    $this->assertIsArray($items, 'Google_StorageObject', __METHOD__);
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
  public function setPrefixes(/* array(Google_string) */ $prefixes) {
    $this->assertIsArray($prefixes, 'Google_string', __METHOD__);
    $this->prefixes = $prefixes;
  }
  public function getPrefixes() {
    return $this->prefixes;
  }
}

class Google_StorageObject extends Google_Model {
  protected $__aclType = 'Google_ObjectAccessControl';
  protected $__aclDataType = 'array';
  public $acl;
  public $bucket;
  public $cacheControl;
  public $componentCount;
  public $contentDisposition;
  public $contentEncoding;
  public $contentLanguage;
  public $contentType;
  public $crc32c;
  public $etag;
  public $generation;
  public $id;
  public $kind;
  public $md5Hash;
  public $mediaLink;
  public $metadata;
  public $metageneration;
  public $name;
  protected $__ownerType = 'Google_StorageObjectOwner';
  protected $__ownerDataType = '';
  public $owner;
  public $selfLink;
  public $size;
  public $timeDeleted;
  public $updated;
  public function setAcl(/* array(Google_ObjectAccessControl) */ $acl) {
    $this->assertIsArray($acl, 'Google_ObjectAccessControl', __METHOD__);
    $this->acl = $acl;
  }
  public function getAcl() {
    return $this->acl;
  }
  public function setBucket( $bucket) {
    $this->bucket = $bucket;
  }
  public function getBucket() {
    return $this->bucket;
  }
  public function setCacheControl( $cacheControl) {
    $this->cacheControl = $cacheControl;
  }
  public function getCacheControl() {
    return $this->cacheControl;
  }
  public function setComponentCount( $componentCount) {
    $this->componentCount = $componentCount;
  }
  public function getComponentCount() {
    return $this->componentCount;
  }
  public function setContentDisposition( $contentDisposition) {
    $this->contentDisposition = $contentDisposition;
  }
  public function getContentDisposition() {
    return $this->contentDisposition;
  }
  public function setContentEncoding( $contentEncoding) {
    $this->contentEncoding = $contentEncoding;
  }
  public function getContentEncoding() {
    return $this->contentEncoding;
  }
  public function setContentLanguage( $contentLanguage) {
    $this->contentLanguage = $contentLanguage;
  }
  public function getContentLanguage() {
    return $this->contentLanguage;
  }
  public function setContentType( $contentType) {
    $this->contentType = $contentType;
  }
  public function getContentType() {
    return $this->contentType;
  }
  public function setCrc32c( $crc32c) {
    $this->crc32c = $crc32c;
  }
  public function getCrc32c() {
    return $this->crc32c;
  }
  public function setEtag( $etag) {
    $this->etag = $etag;
  }
  public function getEtag() {
    return $this->etag;
  }
  public function setGeneration( $generation) {
    $this->generation = $generation;
  }
  public function getGeneration() {
    return $this->generation;
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
  public function setMd5Hash( $md5Hash) {
    $this->md5Hash = $md5Hash;
  }
  public function getMd5Hash() {
    return $this->md5Hash;
  }
  public function setMediaLink( $mediaLink) {
    $this->mediaLink = $mediaLink;
  }
  public function getMediaLink() {
    return $this->mediaLink;
  }
  public function setMetadata( $metadata) {
    $this->metadata = $metadata;
  }
  public function getMetadata() {
    return $this->metadata;
  }
  public function setMetageneration( $metageneration) {
    $this->metageneration = $metageneration;
  }
  public function getMetageneration() {
    return $this->metageneration;
  }
  public function setName( $name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
  public function setOwner(Google_StorageObjectOwner $owner) {
    $this->owner = $owner;
  }
  public function getOwner() {
    return $this->owner;
  }
  public function setSelfLink( $selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
  public function setSize( $size) {
    $this->size = $size;
  }
  public function getSize() {
    return $this->size;
  }
  public function setTimeDeleted( $timeDeleted) {
    $this->timeDeleted = $timeDeleted;
  }
  public function getTimeDeleted() {
    return $this->timeDeleted;
  }
  public function setUpdated( $updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
}

class Google_StorageObjectOwner extends Google_Model {
  public $entity;
  public $entityId;
  public function setEntity( $entity) {
    $this->entity = $entity;
  }
  public function getEntity() {
    return $this->entity;
  }
  public function setEntityId( $entityId) {
    $this->entityId = $entityId;
  }
  public function getEntityId() {
    return $this->entityId;
  }
}
