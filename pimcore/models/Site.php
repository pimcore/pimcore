<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Site
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model;

use Pimcore\Logger;

/**
 * @method \Pimcore\Model\Site\Dao getDao()
 */
class Site extends AbstractModel
{
    /**
     * @var Site
     */
    protected static $currentSite;


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
    public static function getById($id)
    {
        $site = new self();
        $site->getDao()->getById(intval($id));

        return $site;
    }

    /**
     * @param integer $id
     * @return Site
     */
    public static function getByRootId($id)
    {
        $site = new self();
        $site->getDao()->getByRootId(intval($id));

        return $site;
    }

    /**
     * @param $domain
     * @return mixed|Site|string
     * @throws \Exception
     */
    public static function getByDomain($domain)
    {

        // cached because this is called in the route (Pimcore_Controller_Router_Route_Frontend)
        $cacheKey = "site_domain_". md5($domain);
        if (!$site = \Pimcore\Cache::load($cacheKey)) {
            $site = new self();

            try {
                $site->getDao()->getByDomain($domain);
            } catch (\Exception $e) {
                Logger::debug($e);
                $site = "failed";
            }

            \Pimcore\Cache::save($site, $cacheKey, ["system", "site"], null, 999);
        }

        if ($site == "failed" || !$site) {
            $msg = "there is no site for the requested domain [" . $domain . "], content was [" . $site . "]";
            Logger::debug($msg);
            throw new \Exception($msg);
        }

        return $site;
    }


    /**
     * @param $mixed
     * @return Site
     */
    public static function getBy($mixed)
    {
        if (is_numeric($mixed)) {
            $site = self::getById($mixed);
        } elseif (is_string($mixed)) {
            $site = self::getByDomain($mixed);
        } elseif ($mixed instanceof Site) {
            $site = $mixed;
        }

        return $site;
    }

    /**
     * @param array $data
     * @return Site
     */
    public static function create($data)
    {
        $site = new self();
        $site->setValues($data);

        return $site;
    }

    /**
     * returns true if the current process/request is inside a site
     *
     * @static
     * @return bool
     */
    public static function isSiteRequest()
    {
        if (null !== self::$currentSite) {
            return true;
        }

        return false;
    }

    /**
     * @return Site
     *
     * @throws \Exception
     * @throws \Zend_Exception
     */
    public static function getCurrentSite()
    {
        if (null !== self::$currentSite) {
            return self::$currentSite;
        } else {
            throw new \Exception("This request/process is not inside a subsite");
        }
    }

    /**
     * Register the current site
     *
     * @param Site $site
     */
    public static function setCurrentSite(Site $site)
    {
        self::$currentSite = $site;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getDomains()
    {
        return $this->domains;
    }

    /**
     * @return integer
     */
    public function getRootId()
    {
        return $this->rootId;
    }

    /**
     * @return Document\Page
     */
    public function getRootDocument()
    {
        return $this->rootDocument;
    }

    /**
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @param mixed $domains
     * @return $this
     */
    public function setDomains($domains)
    {
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
    public function setRootId($rootId)
    {
        $this->rootId = (int) $rootId;

        $rd = Document::getById($this->rootId);
        $this->setRootDocument($rd);

        return $this;
    }

    /**
     * @param Document\Page $rootDocument
     * @return void
     */
    public function setRootDocument($rootDocument)
    {
        $this->rootDocument = $rootDocument;

        return $this;
    }

    /**
     * @param $path
     * @return $this
     */
    public function setRootPath($path)
    {
        $this->rootPath = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getRootPath()
    {
        if (!$this->rootPath && $this->getRootDocument()) {
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
    public function clearDependentCache()
    {

        // this is mostly called in Site\Dao not here
        try {
            \Pimcore\Cache::clearTag("site");
        } catch (\Exception $e) {
            Logger::crit($e);
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
