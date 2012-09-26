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
 * @category   Pimcore
 * @package    Site
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Site extends Pimcore_Model_Abstract {

    /**
     * @var integer
     */
    public $id;

    /**
     * @var array
     */
    public $domains;

    /**
     * Contains the ID to the Root-Document
     *
     * @var integer
     */
    public $rootId;

    /**
     * @var Document_Page
     */
    public $rootDocument;

    /**
     * @var string
     */
    public $rootPath;

    /**
     * @param integer $id
     * @return Site
     */
    public static function getById($id) {
        $site = new self();
        $site->getResource()->getById(intval($id));

        return $site;
    }

    /**
     * @param integer $id
     * @return Site
     */
    public static function getByRootId($id) {
        $site = new self();
        $site->getResource()->getByRootId(intval($id));

        return $site;
    }

    /**
     * @param string $domain
     * @return Site
     */
    public static function getByDomain($domain) {
        
        // cached because this is called in the route (Pimcore_Controller_Router_Route_Frontend)
        $cacheKey = "site_domain_".str_replace(array(".","-"),"_",$domain);
        if (!$site = Pimcore_Model_Cache::load($cacheKey)) {
            $site = new self();
            
            try {
                $site->getResource()->getByDomain($domain);
            } catch (Exception $e) {
                $site = "failed";
            }
            
            Pimcore_Model_Cache::save($site, $cacheKey, array("system","site"));
        }
        
        if($site == "failed" || !$site) {
            throw new Exception("there is no site for the requested domain");
        }
        
        return $site;
    }

    /**
     * @param array $data
     * @return Site
     */
    public static function create($data) {

        $site = new self();
        $site->setValues($data);
        return $site;
    }

    /**
     * returns true if the current process/request is inside a site
     * @static
     * @return bool
     */
    public static function isSiteRequest () {

        if(Zend_Registry::isRegistered("pimcore_site")) {
            return true;
        }

        return false;
    }

    /**
     * returns the current active site if present, otherwise throws an exception
     * @static
     * @return Site
     * @throw Exception
     */
    public static function getCurrentSite() {
        if(Zend_Registry::isRegistered("pimcore_site")) {
            $site = Zend_Registry::get("pimcore_site");
            return $site;
        } else {
            throw new Exception("This request/process is not inside a subsite");
        }
    }

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getDomains() {
        return $this->domains;
    }

    /**
     * returns the main domain of the site (first domain in list)
     * @return string
     */
    public function getMainDomain() {
        $domains = $this->getDomains();
        return trim((string) $domains[0]);
    }

    /**
     * @return integer
     */
    public function getRootId() {
        return $this->rootId;
    }

    /**
     * @return Document_Page
     */
    public function getRootDocument() {
        return $this->rootDocument;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
    }

    /**
     * @param mixed $domains
     * @return void
     */
    public function setDomains($domains) {
        if (is_string($domains)) {
            $domains = Pimcore_Tool_Serialize::unserialize($domains);
        }
        $this->domains = $domains;
    }

    /**
     * @param integer $rootId
     * @return void
     */
    public function setRootId($rootId) {
        $this->rootId = (int) $rootId;

        $rd = Document::getById($this->rootId);
        $this->setRootDocument($rd);
    }

    /**
     * @param Document_Page $rootDocument
     * @return void
     */
    public function setRootDocument($rootDocument) {
        $this->rootDocument = $rootDocument;
    }

    /**
     * @param string $path
     */
    public function setRootPath($path) {
        $this->rootPath = $path;
    }

    /**
     * @return string
     */
    public function getRootPath() {
        if(!$this->rootPath && $this->getRootDocument()) {
            return $this->getRootDocument()->getRealFullPath();
        }
        return $this->rootPath;
    }
    
    
    /**
     * @return void
     */
    public function clearDependedCache() {
        
        // this is mostly called in Site_Resource not here
        try {
            Pimcore_Model_Cache::clearTag("site");
        }
        catch (Exception $e) {
            Logger::info($e);
        }
    }
}
