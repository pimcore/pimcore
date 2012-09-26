<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2012 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */
/**
 * Redis Cache Backend Class for Pimcore
 *
 * This Class uses {@link http://redis.io Redis} as Cache Backend. It requires the
 * {@link https://github.com/nicolasff/phpredis phpredis} module.
 *
 * All Keys are prefixed by option "prefix" which defaults to "pc". Therefore it's possible to
 * use a shared redis database.
 *
 * The Class uses these Redis Datastructures:
 * HASH $prefix:data:$id     - "d"      => (string) cached data
 *                           - "mtime"  => (int) Last Modified Timestamp
 *                           - "l"      => (int) lifetime
 *                           - "expire" =>(int) Expire Timestamp
 * SET $prefix:tags:$id      - List of Tags associated with cache-id $id
 * SET $prefix:tagref:$tag   - List of cache $id's associated to $tag
 * SORTED SET $prefix:expiry - Sorted List of cache id's, sorted by the $id's expire time.
 *
 * Additionally a Key $prefix:datastructure_version is written.
 *
 * @author     Bruno Baketaric (bruno.baketaric@wob.ag)
 * @package    Pimcore_Cache
 * @subpackage Pimcore_Cache_Backend
 * @copyright  Copyright (c) 2012 wob digital GmbH
 * @license    http://www.pimcore.org/license     New BSD License
 */
class Pimcore_Cache_Backend_Redis extends Zend_Cache_Backend implements Zend_Cache_Backend_ExtendedInterface
{
	/**
	 * default Host
	 */
	const DEFAULT_HOST = '127.0.0.1';
	
	/**
	 * default Port
	 */
	const DEFAULT_PORT =  6379;
	
	/**
	 * default Database
	 * 
	 * DB's in Redis are identified by numbers!
	 */
	const DEFAULT_DBINDEX = 0;
	
	/**
	 * default Prefix
	 *
	 * All Keys geneated by this class are prefixed with this string: "pc" = Pimcore Cache
	 */
	const DEFAULT_PREFIX = 'pc';
	
	/**
	 * Versioning of internal datastructures
	 */
	const DATASTRUCTURE_VERSION = 1;
	
	/**
	 * Redis instance
	 */
	protected $_conn;

	/**
	 * Available options
	 *
	 * =====> (string) host :
     * can be a host, or the path to a unix domain socket
     *
	 * =====> (int) port :
     * (optional) port
     *
	 * =====> (int) dbindex :
     * the id of the database to use
     *
	 * =====> (string) prefix :
     * the prefix for all keys used by this cache
	 *
	 * @var array available options
	 */
	protected $_options = array(
		 'host'       => self::DEFAULT_HOST
		,'port'       => self::DEFAULT_PORT
		,'dbindex'    => self::DEFAULT_DBINDEX
		,'prefix'     => self::DEFAULT_PREFIX
	);

    /**
	 * Constructor
	 *
	 * @throws Zend_Cache_Exception
     * @return void
     */
    public function __construct(array $options)
    {
		if (!extension_loaded('redis')) {
			Zend_Cache::throwException('The Redis extension must be loaded for using this backend !');
		}
		parent::__construct($options);
		
		// Merge the options passed in; overridding any default options
		$this->_options = array_merge($this->_options, $options);
		
		/** @var Redis */
		$this->_conn = new Redis();
		if ('socket' === @filetype($this->_options['host'])) {
			if ( !$this->_conn->connect($this->_options['host']) ) {
				Zend_Cache::throwException('Could not connect to Redis socket!');
			}
		} else {
			if ( !$this->_conn->connect($this->_options['host'], $this->_options['port']) ) {
				Zend_Cache::throwException('Could not connect to Redis host!');
			}
		}
		if ( !$this->_conn->select($this->_options['dbindex']) ) {
			Zend_Cache::throwException('Failed to select Redis Database!');
		}
		$this->_conn->setnx($this->_options['prefix'].':datastructure_version', self::DATASTRUCTURE_VERSION);
		if ( (int) $this->_conn->get($this->_options['prefix'].':datastructure_version') !== self::DATASTRUCTURE_VERSION) {
			Zend_Cache::throwException('Found different Datastructure Version in Redis Database!');
		}
    }
	/**
	 * Utility method to prefix keys
	 *
	 * @param string $suffix the id or tag to prefix
	 * @param string $type the prefix type, currently either "data","tags" or "tagref" 
	 * @return string the prefixed key
	 * @throws Zend_Cache_Exception
	 */
	protected function doPrefix($suffix, $type) {
		$ret = $this->_options['prefix'].':';
		switch ($type) {
			case 'data':
				$ret .= 'data';
				break;
			case 'tags':
				$ret .= 'tags';
				break;
			case 'tagref':
				$ret .= 'tagref';
				break;
			default:
				Zend_Cache::throwException('Illegal type passed.');
		}
		return $ret.':'.$suffix;
	}
    /**
     * Expires a record (mostly used for testing purposes)
     * @param string $id
     * @return void
     */
    public function ___expire($id)
	{
		$key = $this->doPrefix($id);
		// new "expire" to modified time - which is always in the past
		$expire = $this->_conn->hGet($key,'mtime');
		
		$this->_conn->hSet($key,'l', 0); // set TTL to 0
		$this->_conn->hSet($key,'expire', $expire); // set "expire" to "mtime"
		// update Sorted set "expiry"
		$this->_conn->zAdd(
			 $this->_options['prefix'].':expiry'
			,$expire
			,$id
		);
	}

    /**
     * Test if a cache is available for the given id and (if yes) return it (false else)
     *
     * @param  string  $id  Cache id
     * @param  boolean $doNotTestCacheValidity If set to true, the cache validity won't be tested
     * @return string|false cached datas
     */
    public function load($id, $doNotTestCacheValidity = false)
    {
		try {
			$tmp = $this->get($id);
			if ($tmp) {
				if ($doNotTestCacheValidity || !$doNotTestCacheValidity && ($tmp['expire']) >= time()) {
					return $tmp['d'];
				}
				return false;
			}
		} catch (Exception $e) {
			return false;
		}
		return false;
	}

    /**
     * Test if a cache is available or not (for the given id)
     *
     * @param  string $id Cache id
     * @return mixed|false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    public function test($id)
    {
		try {
			$tmp = $this->get($id);
			if ($tmp) {
				return $tmp['mtime'];
			}
		} catch (Exception $e) {
			return false;
		}
		
		return false;
    }

    /**
     * Save some string datas into a cache record
     *
     * Note : $data is always "string" (serialization is done by the
     * core not by the backend)
     *
     * @param  string $data             Datas to cache
     * @param  string $id               Cache id
     * @param  array  $tags             Array of strings, the cache record will be tagged by each string entry
     * @param  int    $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @return boolean True if no problem
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
		try {
			$lifetime = $this->getLifetime($specificLifetime);
			$result = $this->set($id, $data, $lifetime, $tags);
		} catch (Exception $e) {
			return false;
		}
		return $result;
	}

    /**
     * Remove a cache record
     *
     * @param  string $id Cache id
     * @return boolean True if no problem
     * @todo this should be made atomic
     */
    public function remove($id)
    {
		$key = $this->doPrefix($id, 'data');
		if ( $this->_conn->exists($key) ) {
			$this->_conn->del($key); // delete the key itself
			$this->_conn->zRem($this->_options['prefix'].':expiry', $key); // remove from sorted set "expiry"
			// delete from tags & tagref
			$key = $this->doPrefix($id,'tags');
			if ( $this->_conn->exists($key) ) {
				$tags = $this->_conn->sMembers($key);
				$this->_conn->del($key);
				// delete the id from tagref
				foreach ( $tags as $tag ) {
					$tagRefKey = $this->doPrefix($tag, 'tagref');
					$this->_conn->sRem(
						 $tagRefKey
						,$id
					);
					// if the Key is empty now, remove it.
					if ($this->_conn->sCard($tagRefKey) == 0) {
						$this->_conn->del($tagRefKey);
					}
				}
			}
		}

        return true;
    }

    /**
     * Clean some cache records (protected method used for recursive stuff)
     *
     * Available modes are :
     * Zend_Cache::CLEANING_MODE_ALL (default)    => remove all cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_OLD              => remove too old cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_MATCHING_TAG     => remove cache entries matching all given tags
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG => remove cache entries not {matching one of the given tags}
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG => remove cache entries matching any given tags
     *                                               ($tags can be an array of strings or a single string)
     *
     * @param  string $dir  Directory to clean
     * @param  string $mode Clean mode
     * @param  array  $tags Array of tags
     * @throws Zend_Cache_Exception
     * @return boolean True if no problem
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
		switch ($mode) {
            case Zend_Cache::CLEANING_MODE_ALL:
				$toDelete = $this->_conn->keys($this->_options['prefix'].':*');
				foreach ($toDelete as $key) {
					if ($key === $this->_options['prefix'].':datastructure_version') continue;
					$this->_conn->del($key);
				}
				return true;
                break;
            case Zend_Cache::CLEANING_MODE_OLD:
				// $now = $this->_conn->time();
				$now = time();
				$toDelete = $this->_conn->zRangeByScore($this->_options['prefix'].':expiry','(0', $now);
				foreach ($toDelete as $id) {
					$this->remove($id);
				}
				return true;
                break;
            case Zend_Cache::CLEANING_MODE_MATCHING_TAG:
				$toDelete = $this->getIdsMatchingTags($tags);
				foreach ($toDelete as $id) {
					$this->remove($id);
				}
				return true;
                break;
            case Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG:
				$toDelete = $this->getIdsNotMatchingTags($tags);
				foreach ($toDelete as $id) {
					$this->remove($id);
				}
				return true;
                break;
            case Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG:
				$toDelete = $this->getIdsMatchingAnyTags($tags);
				foreach ($toDelete as $id) {
					$this->remove($id);
				}
				return true;
                break;
            default:
                Zend_Cache::throwException('Invalid mode for clean() method');
                break;
        }
    }

    /**
     * Return true if the automatic cleaning is available for the backend
     *
     * @return boolean
     */
    public function isAutomaticCleaningAvailable()
    {
		return false;
    }

    /**
     * Set the frontend directives
     *
     * @param  array $directives Assoc of directives
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function setDirectives($directives)
    {
		parent::setDirectives($directives);
        $lifetime = $this->getLifetime(false);
        if ($lifetime === null) {
            // #ZF-4614 : we tranform null to zero to get the maximal lifetime
            parent::setDirectives(array('lifetime' => 0));
        }
    }

    /**
     * Return an array of stored cache ids
     *
     * @return array array of stored cache ids (string)
     */
    public function getIds()
    {
		$all = $this->_conn->keys($this->_options['prefix'].':data:*');
		$ret = array();
		foreach ($all as $key) {
			$ret[] = str_replace($this->_options['prefix'].':data:','',$key);
		}
		return $ret;
    }

    /**
     * Return an array of stored tags
     *
     * @return array array of stored tags (string)
     */
    public function getTags()
    {
		$all = $this->_conn->keys($this->_options['prefix'].':tags:*');
		$ret = array();
		foreach ($all as $key) {
			$ret[] = str_replace($this->_options['prefix'].':tags:','',$key);
		}
		return $ret;
    }

    /**
     * Return an array of stored cache ids which match given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param array $tags array of tags
     * @return array array of matching cache ids (string)
     */
    public function getIdsMatchingTags($tags = array())
    {
		$tagsPrefixed = array(); 
		foreach ($tags as $tag) {
			$tagsPrefixed[] = $this->doPrefix($tag, 'tagref');
		}
		$ret = $this->_conn->sInter(implode(',',$tagsPrefixed));
		
		return $ret;
    }

    /**
     * Return an array of stored cache ids which don't match given tags
     *
     * In case of multiple tags, a logical OR is made between tags
     *
     * @param array $tags array of tags
     * @return array array of not matching cache ids (string)
     */
    public function getIdsNotMatchingTags($tags = array())
    {
		$tagsPrefixed = array(); 
		foreach ($tags as $tag) {
			$tagsPrefixed[] = $this->doPrefix($tag, 'tagref');
		}
		$allKeys = $this->_conn->zRange($this->_options['prefix'].':expiry', 0, -1);
		foreach ($allKeys as $key) {
			$this->_conn->sAdd($this->_options['prefix'].':allKeys', $key);
		}
		$ret = $this->_conn->sDiff($this->_options['prefix'].':allKeys,'.implode(',',$tagsPrefixed));
		$this->_conn->del($this->_options['prefix'].':allKeys');
		
		return $ret;
    }

    /**
     * Return an array of stored cache ids which match any given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param array $tags array of tags
     * @return array array of any matching cache ids (string)
     */
    public function getIdsMatchingAnyTags($tags = array())
    {
		$tagsPrefixed = array(); 
		foreach ($tags as $tag) {
			$tagsPrefixed[] = $this->doPrefix($tag, 'tagref');
		}
		$ret = $this->_conn->sUnion(implode(',',$tagsPrefixed));
		
		return $ret;
    }

    /**
     * Get Filling percentage
     *
     * Returns 1 if no maxmemory set in redis.conf. Else the real filling percentage.
     *
     * @return int integer between 0 and 100
     */
    public function getFillingPercentage()
    {
		$maxMem = $this->_conn->config('GET','maxmemory');
		if (0 == (int) $maxMem['maxmemory']) {
			return 1;
		}
		$info = $this->_conn->info();
		return round(
					 ($info['used_memory']/$maxMem['maxmemory']*100)
					,0
					,PHP_ROUND_HALF_UP
				);
    }

    /**
     * Return an array of metadatas for the given cache id
     *
     * The array must include these keys :
     * - expire : the expire timestamp
     * - tags : a string array of tags
     * - mtime : timestamp of last modification time
     *
     * @param string $id cache id
     * @return array array of metadatas (false if the cache id is not found)
     */
    public function getMetadatas($id)
    {
		$data = $this->get($id);
		if (false === $data) {
			return false;
		}
		$data['tags'] = $this->_conn->sMembers( $this->doPrefix($id,'tags') );
		
		return array(
			 'expire' => $data['expire'],
			 'tags' => $data['tags'],
			 'mtime' => $data['mtime']
		 );
    }

    /**
     * Give (if possible) an extra lifetime to the given cache id
     *
     * @param string $id cache id
     * @param int $extraLifetime
     * @return boolean true if ok
     */
	public function touch($id, $extraLifetime)
	{
		$data = $this->get($id);
		if (false === $data) {
			return false;
		}
		$data['expire']+= $extraLifetime;
		
		$dataKey = $this->doPrefix($id, 'data');
		
		if ($this->_conn->exists($key)) {
			$this->_conn->del($key);
		}
		
		// write hash "data"
		$this->_conn->hMset($key, $h);
		
		// remove and re-add from Sorted set "expiry"
		$this->_conn->zRem(
			 $this->_options['prefix'].':expiry'
			,$id
		);
		$this->_conn->zAdd(
			 $this->_options['prefix'].':expiry'
			,$data['expire']
			,$id
		);
		return true;
	}

    /**
     * Return an associative array of capabilities (booleans) of the backend
     *
     * The array must include these keys :
     * - automatic_cleaning (is automating cleaning necessary)
     * - tags (are tags supported)
     * - expired_read (is it possible to read expired cache records
     *                 (for doNotTestCacheValidity option for example))
     * - priority does the backend deal with priority when saving
     * - infinite_lifetime (is infinite lifetime can work with this backend)
     * - get_list (is it possible to get the list of cache ids and the complete list of tags)
     *
     * @return array associative of with capabilities
     */
    public function getCapabilities()
    {
		return array(
            'automatic_cleaning' => true,
            'tags' => true,
            'expired_read' => true,
            'priority' => false,
            'infinite_lifetime' => true,
            'get_list' => true
        );
    }

    /**
	 * set a Cache Item
	 *
	 * In case the item already exists, it's deleted in advance.
	 *
     * @param int $id
     * @param array $data
     * @param int $lifetime
     * @param array $tags
     * @return boolean
     */
    function set($id, $data, $lifetime, $tags)
    {
		// set the lifetime to 1 year if it is null
		if(!$lifetime) {
			$lifetime = (86400*365);
		}
		$h = array();
		$h['d'] = $data; // data
		// $redisTime = $this->_conn->time();
		// $h['mtime'] = $redisTime[0]; // created/modified
		$h['mtime'] = time();
		$h['l'] = $lifetime; // TTL
		$h['expire'] = $h['mtime']+$h['l'];
		
		$dataKey = $this->doPrefix($id, 'data');
		
		if ($this->_conn->exists($key)) {
			$this->remove($key);
		}
		
		// write hash "data"
		$this->_conn->hMset($dataKey, $h); 
		
		// write tags & tagref
		$tagKey = $this->doPrefix($id,'tags');
		foreach ($tags as $tag) {
			$this->_conn->sAdd($tagKey, $tag); // tags
			$tagRefKey = $this->doPrefix($tag, 'tagref');
			$this->_conn->sAdd($tagRefKey, $id);
		}
		
		// write expiry
		$this->_conn->zAdd(
			 $this->_options['prefix'].':expiry'
			,$h['expire']
			,$id
		);

        return true;
    }

    /**
	 * Generic Method to get the data of a cached item
	 *
     * @param int $id
     * @return array|false
     */
    function get($id)
    {
		$ret = false;    
		$key = $this->doPrefix($id, 'data');
		if ($this->_conn->exists($key)) {
			$ret = $this->_conn->hGetAll($key);
		}
		$ret['_id'] = $id;
		$ret['created_at'] = $ret['mtime'];
		return $ret;
    }

}

