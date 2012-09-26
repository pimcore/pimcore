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
   * The "blogs" collection of methods.
   * Typical usage is:
   *  <code>
   *   $bloggerService = new apiBloggerService(...);
   *   $blogs = $bloggerService->blogs;
   *  </code>
   */
  class BlogsServiceResource extends apiServiceResource {


    /**
     * Gets one blog by id. (blogs.get)
     *
     * @param string $blogId The ID of the blog to get.
     * @return Blog
     */
    public function get($blogId, $optParams = array()) {
      $params = array('blogId' => $blogId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Blog($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "posts" collection of methods.
   * Typical usage is:
   *  <code>
   *   $bloggerService = new apiBloggerService(...);
   *   $posts = $bloggerService->posts;
   *  </code>
   */
  class PostsServiceResource extends apiServiceResource {


    /**
     * Retrieves a list of posts, possibly filtered. (posts.list)
     *
     * @param string $blogId ID of the blog to fetch posts from.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string pageToken Continuation token if the request is paged.
     * @opt_param bool fetchBodies Whether the body content of posts is included.
     * @opt_param string maxResults Maximum number of posts to fetch.
     * @opt_param string startDate Earliest post date to fetch.
     * @return PostList
     */
    public function listPosts($blogId, $optParams = array()) {
      $params = array('blogId' => $blogId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new PostList($data);
      } else {
        return $data;
      }
    }
    /**
     * Get a post by id. (posts.get)
     *
     * @param string $blogId ID of the blog to fetch the post from.
     * @param string $postId The ID of the post
     * @return Post
     */
    public function get($blogId, $postId, $optParams = array()) {
      $params = array('blogId' => $blogId, 'postId' => $postId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Post($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "pages" collection of methods.
   * Typical usage is:
   *  <code>
   *   $bloggerService = new apiBloggerService(...);
   *   $pages = $bloggerService->pages;
   *  </code>
   */
  class PagesServiceResource extends apiServiceResource {


    /**
     * Retrieves pages for a blog, possibly filtered. (pages.list)
     *
     * @param string $blogId ID of the blog to fetch pages from.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param bool fetchBodies Whether to retrieve the Page bodies.
     * @return PageList
     */
    public function listPages($blogId, $optParams = array()) {
      $params = array('blogId' => $blogId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new PageList($data);
      } else {
        return $data;
      }
    }
    /**
     * Gets one blog page by id. (pages.get)
     *
     * @param string $blogId ID of the blog containing the page.
     * @param string $pageId The ID of the page to get.
     * @return Page
     */
    public function get($blogId, $pageId, $optParams = array()) {
      $params = array('blogId' => $blogId, 'pageId' => $pageId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new Page($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "comments" collection of methods.
   * Typical usage is:
   *  <code>
   *   $bloggerService = new apiBloggerService(...);
   *   $comments = $bloggerService->comments;
   *  </code>
   */
  class CommentsServiceResource extends apiServiceResource {


    /**
     * Retrieves the comments for a blog, possibly filtered. (comments.list)
     *
     * @param string $blogId ID of the blog to fetch comments from.
     * @param string $postId ID of the post to fetch posts from.
     * @param array $optParams Optional parameters. Valid optional parameters are listed below.
     *
     * @opt_param string startDate Earliest date of comment to fetch.
     * @opt_param string maxResults Maximum number of comments to include in the result.
     * @opt_param string pageToken Continuation token if request is paged.
     * @opt_param bool fetchBodies Whether the body content of the comments is included.
     * @return CommentList
     */
    public function listComments($blogId, $postId, $optParams = array()) {
      $params = array('blogId' => $blogId, 'postId' => $postId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new CommentList($data);
      } else {
        return $data;
      }
    }
    /**
     * Gets one comment by id. (comments.get)
     *
     * @param string $blogId ID of the blog to containing the comment.
     * @param string $postId ID of the post to fetch posts from.
     * @param string $commentId The ID of the comment to get.
     * @return Comment
     */
    public function get($blogId, $postId, $commentId, $optParams = array()) {
      $params = array('blogId' => $blogId, 'postId' => $postId, 'commentId' => $commentId);
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
   * The "users" collection of methods.
   * Typical usage is:
   *  <code>
   *   $bloggerService = new apiBloggerService(...);
   *   $users = $bloggerService->users;
   *  </code>
   */
  class UsersServiceResource extends apiServiceResource {


    /**
     * Gets one user by id. (users.get)
     *
     * @param string $userId The ID of the user to get.
     * @return User
     */
    public function get($userId, $optParams = array()) {
      $params = array('userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('get', array($params));
      if ($this->useObjects()) {
        return new User($data);
      } else {
        return $data;
      }
    }
  }

  /**
   * The "blogs" collection of methods.
   * Typical usage is:
   *  <code>
   *   $bloggerService = new apiBloggerService(...);
   *   $blogs = $bloggerService->blogs;
   *  </code>
   */
  class UsersBlogsServiceResource extends apiServiceResource {


    /**
     * Retrieves a list of blogs, possibly filtered. (blogs.list)
     *
     * @param string $userId ID of the user whose blogs are to be fetched. Either the word 'self' (sans quote marks) or the user's profile identifier.
     * @return BlogList
     */
    public function listUsersBlogs($userId, $optParams = array()) {
      $params = array('userId' => $userId);
      $params = array_merge($params, $optParams);
      $data = $this->__call('list', array($params));
      if ($this->useObjects()) {
        return new BlogList($data);
      } else {
        return $data;
      }
    }
  }

/**
 * Service definition for Blogger (v2).
 *
 * <p>
 * API for access to the data within Blogger.
 * </p>
 *
 * <p>
 * For more information about this service, see the
 * <a href="https://code.google.com/apis/blogger/docs/2.0/json/getting_started.html" target="_blank">API Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class apiBloggerService extends apiService {
  public $blogs;
  public $posts;
  public $pages;
  public $comments;
  public $users;
  public $users_blogs;
  /**
   * Constructs the internal representation of the Blogger service.
   *
   * @param apiClient apiClient
   */
  public function __construct(apiClient $apiClient) {
    $this->restBasePath = '/blogger/v2/';
    $this->version = 'v2';
    $this->serviceName = 'blogger';

    $apiClient->addService($this->serviceName, $this->version);
    $this->blogs = new BlogsServiceResource($this, $this->serviceName, 'blogs', json_decode('{"methods": {"get": {"scopes": ["https://www.googleapis.com/auth/blogger"], "parameters": {"blogId": {"required": true, "type": "string", "location": "path"}}, "id": "blogger.blogs.get", "httpMethod": "GET", "path": "blogs/{blogId}", "response": {"$ref": "Blog"}}}}', true));
    $this->posts = new PostsServiceResource($this, $this->serviceName, 'posts', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/blogger"], "parameters": {"pageToken": {"type": "string", "location": "query"}, "fetchBodies": {"type": "boolean", "location": "query"}, "blogId": {"required": true, "type": "string", "location": "path"}, "maxResults": {"format": "uint32", "type": "integer", "location": "query"}, "startDate": {"type": "string", "location": "query"}}, "id": "blogger.posts.list", "httpMethod": "GET", "path": "blogs/{blogId}/posts", "response": {"$ref": "PostList"}}, "get": {"scopes": ["https://www.googleapis.com/auth/blogger"], "parameters": {"postId": {"required": true, "type": "string", "location": "path"}, "blogId": {"required": true, "type": "string", "location": "path"}}, "id": "blogger.posts.get", "httpMethod": "GET", "path": "blogs/{blogId}/posts/{postId}", "response": {"$ref": "Post"}}}}', true));
    $this->pages = new PagesServiceResource($this, $this->serviceName, 'pages', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/blogger"], "parameters": {"fetchBodies": {"type": "boolean", "location": "query"}, "blogId": {"required": true, "type": "string", "location": "path"}}, "id": "blogger.pages.list", "httpMethod": "GET", "path": "blogs/{blogId}/pages", "response": {"$ref": "PageList"}}, "get": {"scopes": ["https://www.googleapis.com/auth/blogger"], "parameters": {"pageId": {"required": true, "type": "string", "location": "path"}, "blogId": {"required": true, "type": "string", "location": "path"}}, "id": "blogger.pages.get", "httpMethod": "GET", "path": "blogs/{blogId}/pages/{pageId}", "response": {"$ref": "Page"}}}}', true));
    $this->comments = new CommentsServiceResource($this, $this->serviceName, 'comments', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/blogger"], "parameters": {"startDate": {"type": "string", "location": "query"}, "postId": {"required": true, "type": "string", "location": "path"}, "maxResults": {"format": "uint32", "type": "integer", "location": "query"}, "pageToken": {"type": "string", "location": "query"}, "fetchBodies": {"type": "boolean", "location": "query"}, "blogId": {"required": true, "type": "string", "location": "path"}}, "id": "blogger.comments.list", "httpMethod": "GET", "path": "blogs/{blogId}/posts/{postId}/comments", "response": {"$ref": "CommentList"}}, "get": {"scopes": ["https://www.googleapis.com/auth/blogger"], "parameters": {"commentId": {"required": true, "type": "string", "location": "path"}, "postId": {"required": true, "type": "string", "location": "path"}, "blogId": {"required": true, "type": "string", "location": "path"}}, "id": "blogger.comments.get", "httpMethod": "GET", "path": "blogs/{blogId}/posts/{postId}/comments/{commentId}", "response": {"$ref": "Comment"}}}}', true));
    $this->users = new UsersServiceResource($this, $this->serviceName, 'users', json_decode('{"methods": {"get": {"scopes": ["https://www.googleapis.com/auth/blogger"], "parameters": {"userId": {"required": true, "type": "string", "location": "path"}}, "id": "blogger.users.get", "httpMethod": "GET", "path": "users/{userId}", "response": {"$ref": "User"}}}}', true));
    $this->users_blogs = new UsersBlogsServiceResource($this, $this->serviceName, 'blogs', json_decode('{"methods": {"list": {"scopes": ["https://www.googleapis.com/auth/blogger"], "parameters": {"userId": {"required": true, "type": "string", "location": "path"}}, "id": "blogger.users.blogs.list", "httpMethod": "GET", "path": "users/{userId}/blogs", "response": {"$ref": "BlogList"}}}}', true));

  }
}

class Blog extends apiModel {
  public $kind;
  public $description;
  protected $__localeType = 'BlogLocale';
  protected $__localeDataType = '';
  public $locale;
  protected $__postsType = 'BlogPosts';
  protected $__postsDataType = '';
  public $posts;
  public $updated;
  public $id;
  public $url;
  public $published;
  protected $__pagesType = 'BlogPages';
  protected $__pagesDataType = '';
  public $pages;
  public $selfLink;
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
  public function setLocale(BlogLocale $locale) {
    $this->locale = $locale;
  }
  public function getLocale() {
    return $this->locale;
  }
  public function setPosts(BlogPosts $posts) {
    $this->posts = $posts;
  }
  public function getPosts() {
    return $this->posts;
  }
  public function setUpdated($updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setPublished($published) {
    $this->published = $published;
  }
  public function getPublished() {
    return $this->published;
  }
  public function setPages(BlogPages $pages) {
    $this->pages = $pages;
  }
  public function getPages() {
    return $this->pages;
  }
  public function setSelfLink($selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }
}

class BlogList extends apiModel {
  protected $__itemsType = 'Blog';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(Blog) */ $items) {
    $this->assertIsArray($items, 'Blog', __METHOD__);
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

class BlogLocale extends apiModel {
  public $country;
  public $variant;
  public $language;
  public function setCountry($country) {
    $this->country = $country;
  }
  public function getCountry() {
    return $this->country;
  }
  public function setVariant($variant) {
    $this->variant = $variant;
  }
  public function getVariant() {
    return $this->variant;
  }
  public function setLanguage($language) {
    $this->language = $language;
  }
  public function getLanguage() {
    return $this->language;
  }
}

class BlogPages extends apiModel {
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

class BlogPosts extends apiModel {
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

class Comment extends apiModel {
  public $content;
  public $kind;
  protected $__inReplyToType = 'CommentInReplyTo';
  protected $__inReplyToDataType = '';
  public $inReplyTo;
  protected $__authorType = 'CommentAuthor';
  protected $__authorDataType = '';
  public $author;
  public $updated;
  protected $__blogType = 'CommentBlog';
  protected $__blogDataType = '';
  public $blog;
  public $published;
  protected $__postType = 'CommentPost';
  protected $__postDataType = '';
  public $post;
  public $id;
  public $selfLink;
  public function setContent($content) {
    $this->content = $content;
  }
  public function getContent() {
    return $this->content;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setInReplyTo(CommentInReplyTo $inReplyTo) {
    $this->inReplyTo = $inReplyTo;
  }
  public function getInReplyTo() {
    return $this->inReplyTo;
  }
  public function setAuthor(CommentAuthor $author) {
    $this->author = $author;
  }
  public function getAuthor() {
    return $this->author;
  }
  public function setUpdated($updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setBlog(CommentBlog $blog) {
    $this->blog = $blog;
  }
  public function getBlog() {
    return $this->blog;
  }
  public function setPublished($published) {
    $this->published = $published;
  }
  public function getPublished() {
    return $this->published;
  }
  public function setPost(CommentPost $post) {
    $this->post = $post;
  }
  public function getPost() {
    return $this->post;
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

class CommentAuthor extends apiModel {
  public $url;
  protected $__imageType = 'CommentAuthorImage';
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
  public function setImage(CommentAuthorImage $image) {
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

class CommentAuthorImage extends apiModel {
  public $url;
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class CommentBlog extends apiModel {
  public $id;
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
}

class CommentInReplyTo extends apiModel {
  public $id;
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
}

class CommentList extends apiModel {
  public $nextPageToken;
  protected $__itemsType = 'Comment';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $prevPageToken;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setItems(/* array(Comment) */ $items) {
    $this->assertIsArray($items, 'Comment', __METHOD__);
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
  public function setPrevPageToken($prevPageToken) {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken() {
    return $this->prevPageToken;
  }
}

class CommentPost extends apiModel {
  public $id;
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
}

class Page extends apiModel {
  public $content;
  public $kind;
  protected $__authorType = 'PageAuthor';
  protected $__authorDataType = '';
  public $author;
  public $url;
  public $title;
  public $updated;
  protected $__blogType = 'PageBlog';
  protected $__blogDataType = '';
  public $blog;
  public $published;
  public $id;
  public $selfLink;
  public function setContent($content) {
    $this->content = $content;
  }
  public function getContent() {
    return $this->content;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setAuthor(PageAuthor $author) {
    $this->author = $author;
  }
  public function getAuthor() {
    return $this->author;
  }
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
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
  public function setBlog(PageBlog $blog) {
    $this->blog = $blog;
  }
  public function getBlog() {
    return $this->blog;
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

class PageAuthor extends apiModel {
  public $url;
  protected $__imageType = 'PageAuthorImage';
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
  public function setImage(PageAuthorImage $image) {
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

class PageAuthorImage extends apiModel {
  public $url;
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class PageBlog extends apiModel {
  public $id;
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
}

class PageList extends apiModel {
  protected $__itemsType = 'Page';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public function setItems(/* array(Page) */ $items) {
    $this->assertIsArray($items, 'Page', __METHOD__);
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

class Post extends apiModel {
  public $content;
  public $kind;
  protected $__authorType = 'PostAuthor';
  protected $__authorDataType = '';
  public $author;
  protected $__repliesType = 'PostReplies';
  protected $__repliesDataType = '';
  public $replies;
  public $labels;
  public $updated;
  protected $__blogType = 'PostBlog';
  protected $__blogDataType = '';
  public $blog;
  public $url;
  public $published;
  public $title;
  public $id;
  public $selfLink;
  public function setContent($content) {
    $this->content = $content;
  }
  public function getContent() {
    return $this->content;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
  public function setAuthor(PostAuthor $author) {
    $this->author = $author;
  }
  public function getAuthor() {
    return $this->author;
  }
  public function setReplies(PostReplies $replies) {
    $this->replies = $replies;
  }
  public function getReplies() {
    return $this->replies;
  }
  public function setLabels(/* array(string) */ $labels) {
    $this->assertIsArray($labels, 'string', __METHOD__);
    $this->labels = $labels;
  }
  public function getLabels() {
    return $this->labels;
  }
  public function setUpdated($updated) {
    $this->updated = $updated;
  }
  public function getUpdated() {
    return $this->updated;
  }
  public function setBlog(PostBlog $blog) {
    $this->blog = $blog;
  }
  public function getBlog() {
    return $this->blog;
  }
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
  public function setPublished($published) {
    $this->published = $published;
  }
  public function getPublished() {
    return $this->published;
  }
  public function setTitle($title) {
    $this->title = $title;
  }
  public function getTitle() {
    return $this->title;
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

class PostAuthor extends apiModel {
  public $url;
  protected $__imageType = 'PostAuthorImage';
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
  public function setImage(PostAuthorImage $image) {
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

class PostAuthorImage extends apiModel {
  public $url;
  public function setUrl($url) {
    $this->url = $url;
  }
  public function getUrl() {
    return $this->url;
  }
}

class PostBlog extends apiModel {
  public $id;
  public function setId($id) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }
}

class PostList extends apiModel {
  public $nextPageToken;
  protected $__itemsType = 'Post';
  protected $__itemsDataType = 'array';
  public $items;
  public $kind;
  public $prevPageToken;
  public function setNextPageToken($nextPageToken) {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken() {
    return $this->nextPageToken;
  }
  public function setItems(/* array(Post) */ $items) {
    $this->assertIsArray($items, 'Post', __METHOD__);
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
  public function setPrevPageToken($prevPageToken) {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken() {
    return $this->prevPageToken;
  }
}

class PostReplies extends apiModel {
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

class User extends apiModel {
  public $about;
  public $displayName;
  public $created;
  protected $__localeType = 'UserLocale';
  protected $__localeDataType = '';
  public $locale;
  protected $__blogsType = 'UserBlogs';
  protected $__blogsDataType = '';
  public $blogs;
  public $kind;
  public $url;
  public $id;
  public $selfLink;
  public function setAbout($about) {
    $this->about = $about;
  }
  public function getAbout() {
    return $this->about;
  }
  public function setDisplayName($displayName) {
    $this->displayName = $displayName;
  }
  public function getDisplayName() {
    return $this->displayName;
  }
  public function setCreated($created) {
    $this->created = $created;
  }
  public function getCreated() {
    return $this->created;
  }
  public function setLocale(UserLocale $locale) {
    $this->locale = $locale;
  }
  public function getLocale() {
    return $this->locale;
  }
  public function setBlogs(UserBlogs $blogs) {
    $this->blogs = $blogs;
  }
  public function getBlogs() {
    return $this->blogs;
  }
  public function setKind($kind) {
    $this->kind = $kind;
  }
  public function getKind() {
    return $this->kind;
  }
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
  public function setSelfLink($selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
}

class UserBlogs extends apiModel {
  public $selfLink;
  public function setSelfLink($selfLink) {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink() {
    return $this->selfLink;
  }
}

class UserLocale extends apiModel {
  public $country;
  public $variant;
  public $language;
  public function setCountry($country) {
    $this->country = $country;
  }
  public function getCountry() {
    return $this->country;
  }
  public function setVariant($variant) {
    $this->variant = $variant;
  }
  public function getVariant() {
    return $this->variant;
  }
  public function setLanguage($language) {
    $this->language = $language;
  }
  public function getLanguage() {
    return $this->language;
  }
}
