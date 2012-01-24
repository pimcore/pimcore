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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Report_SeoContentDiagnostics_Item extends Pimcore_Model_Abstract {

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $siteId;

    /**
     * @var string
     */
    public $host;

    /**
     * @var string
     */
    public $path;

    /**
     * @var
     */
    public $queryString;

    /**
     * @var
     */
    public $statusCode;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $metaDescription;

    /**
     * @var string
     */
    public $content;

    /**
     * @var int
     */
    public $links;

    /**
     * @var bool
     */
    public $relCanonical;

    /**
     * @var bool
     */
    public $blockedByRobotsMeta;

    /**
     * @var bool
     */
    public $blockedByRobotsTxt;

    /**
     * @var bool
     */
    public $blockedByRobotsHeader;

    /**
     * @var bool
     */
    public $nofollowByRobotsMeta;


    /**
     * @param boolean $blockedByRobotsHeader
     */
    public function setBlockedByRobotsHeader($blockedByRobotsHeader)
    {
        $this->blockedByRobotsHeader = $blockedByRobotsHeader;
    }

    /**
     * @return boolean
     */
    public function getBlockedByRobotsHeader()
    {
        return $this->blockedByRobotsHeader;
    }

    /**
     * @param boolean $blockedByRobotsMeta
     */
    public function setBlockedByRobotsMeta($blockedByRobotsMeta)
    {
        $this->blockedByRobotsMeta = $blockedByRobotsMeta;
    }

    /**
     * @return boolean
     */
    public function getBlockedByRobotsMeta()
    {
        return $this->blockedByRobotsMeta;
    }

    /**
     * @param boolean $blockedByRobotsTxt
     */
    public function setBlockedByRobotsTxt($blockedByRobotsTxt)
    {
        $this->blockedByRobotsTxt = $blockedByRobotsTxt;
    }

    /**
     * @return boolean
     */
    public function getBlockedByRobotsTxt()
    {
        return $this->blockedByRobotsTxt;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $links
     */
    public function setLinks($links)
    {
        $this->links = $links;
    }

    /**
     * @return int
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * @param string $metaDescription
     */
    public function setMetaDescription($metaDescription)
    {
        $this->metaDescription = $metaDescription;
    }

    /**
     * @return string
     */
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    /**
     * @param boolean $nofollowByRobotsMeta
     */
    public function setNofollowByRobotsMeta($nofollowByRobotsMeta)
    {
        $this->nofollowByRobotsMeta = $nofollowByRobotsMeta;
    }

    /**
     * @return boolean
     */
    public function getNofollowByRobotsMeta()
    {
        return $this->nofollowByRobotsMeta;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param  $queryString
     */
    public function setQueryString($queryString)
    {
        $this->queryString = $queryString;
    }

    /**
     * @return
     */
    public function getQueryString()
    {
        return $this->queryString;
    }

    /**
     * @param boolean $relCanonical
     */
    public function setRelCanonical($relCanonical)
    {
        $this->relCanonical = $relCanonical;
    }

    /**
     * @return boolean
     */
    public function getRelCanonical()
    {
        return $this->relCanonical;
    }

    /**
     * @param int $siteId
     */
    public function setSiteId($siteId)
    {
        $this->siteId = $siteId;
    }

    /**
     * @return int
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param  $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @return
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}