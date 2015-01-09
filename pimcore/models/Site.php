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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model;

class Site extends AbstractModel {

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
     * @var Document\Page
     */
    public $rootDocument;

    /**
     * @var string
     */
    public $rootPath;

    /**
     * @var string
     */
    public $mainDomain = "";

    /**
     * @var string
     */
    public $errorDocument = "";

    /**
     * @var bool
     */
    public $redirectToMainDomain = false;

    /**
     * @var integer
     */
    public $creationDate;

    /**
     * @var integer
     */
    public $modificationDate;

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
     * @param $domain
     * @return mixed|Site|string
     * @throws \Exception
     */
    public static function getByDomain($domain) {
        
        // cached because this is called in the route (Pimcore_Controller_Router_Route_Frontend)
        $cacheKey = "site_domain_". md5($domain);
        if (!$site = Cache::load($cacheKey)) {
            $site = new self();
            
            try {
                $site->getResource()->getByDomain($domain);
            } catch (\Exception $e) {
                $site = "failed";
            }
            
            Cache::save($site, $cacheKey, array("system","site"));
        }
        
        if($site == "failed" || !$site) {
            throw new \Exception("there is no site for the requested domain");
        }
        
        return $site;
    }


    /**
     * @param $mixed
     * @return Site
     */
    public static function getBy($mixed) {

        if(is_numeric($mixed)) {
            $site = self::getById($mixed);
        } else if (is_string($mixed)) {
            $site = self::getByDomain($mixed);
        } else if ($mixed instanceof Site) {
            $site = $mixed;
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

        if(\Zend_Registry::isRegistered("pimcore_site")) {
            return true;
        }

        return false;
    }

    /**
     * @throws \Exception
     * @throws \Zend_Exception
     */
    public static function getCurrentSite() {
        if(\Zend_Registry::isRegistered("pimcore_site")) {
            $site = \Zend_Registry::get("pimcore_site");
            return $site;
        } else {
            throw new \Exception("This request/process is not inside a subsite");
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
     * @return integer
     */
    public function getRootId() {
        return $this->rootId;
    }

    /**
     * @return Document\Page
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
        return $this;
    }

    /**
     * @param mixed $domains
     * @return void
     */
    public function setDomains($domains) {
        if (is_string($domains)) {
            $domains = \Pimcore\Tool\Serialize::unserialize($domains);
        }
        $this->domains = $domains;
        return $this;
    }

    /**
     * @param integer $rootId
     * @return void
     */
    public function setRootId($rootId) {
        $this->rootId = (int) $rootId;

        $rd = Document::getById($this->rootId);
        $this->setRootDocument($rd);
        return $this;
    }

    /**
     * @param Document\Page $rootDocument
     * @return void
     */
    public function setRootDocument($rootDocument) {
        $this->rootDocument = $rootDocument;
        return $this;
    }

    /**
     * @param $path
     * @return $this
     */
    public function setRootPath($path) {
        $this->rootPath = $path;
        return $this;
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
     * @param string $errorDocument
     */
    public function setErrorDocument($errorDocument)
    {
        $this->errorDocument = $errorDocument;
    }

    /**
     * @return string
     */
    public function getErrorDocument()
    {
        return $this->errorDocument;
    }

    /**
     * @param string $mainDomain
     */
    public function setMainDomain($mainDomain)
    {
        $this->mainDomain = $mainDomain;
    }

    /**
     * @return string
     */
    public function getMainDomain()
    {
        return $this->mainDomain;
    }

    /**
     * @param boolean $redirectToMainDomain
     */
    public function setRedirectToMainDomain($redirectToMainDomain)
    {
        $this->redirectToMainDomain = (bool) $redirectToMainDomain;
    }

    /**
     * @return boolean
     */
    public function getRedirectToMainDomain()
    {
        return $this->redirectToMainDomain;
    }

    /**
     * @return void
     */
    public function clearDependentCache() {
        
        // this is mostly called in Site\Resource not here
        try {
            Cache::clearTag("site");
        }
        catch (\Exception $e) {
            \Logger::crit($e);
        }
    }

    /**
     * @param $modificationDate
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;
        return $this;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param $creationDate
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int) $creationDate;
        return $this;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

}
