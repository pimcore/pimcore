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

class Report_SeoContentDiagnostics_Queue_Item extends Pimcore_Model_Abstract {

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
    public $scheme;

    /**
     * @var string
     */
    public $host;

    /**
     * @var string
     */
    public $uri;

    /**
     * @var int
     */
    public $documentId;

    /**
     * @var string
     */
    public $responseHeaders;


    /**
     * @var string
     */
    public $requestHeaders;

    /**
     * @var int
     */
    public $responseCode;

    /**
     * @var string
     */
    public $content;

    /**
     * @var int
     */
    public $date;

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
     * @param int $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param int $documentId
     */
    public function setDocumentId($documentId)
    {
        $this->documentId = $documentId;
    }

    /**
     * @return int
     */
    public function getDocumentId()
    {
        return $this->documentId;
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
     * @param string $requestHeaders
     */
    public function setRequestHeaders($requestHeaders)
    {
        $this->requestHeaders = $requestHeaders;
    }

    /**
     * @return string
     */
    public function getRequestHeaders()
    {
        return $this->requestHeaders;
    }

    /**
     * @param int $responseCode
     */
    public function setResponseCode($responseCode)
    {
        $this->responseCode = $responseCode;
    }

    /**
     * @return int
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * @param string $responseHeaders
     */
    public function setResponseHeaders($responseHeaders)
    {
        $this->responseHeaders = $responseHeaders;
    }

    /**
     * @return string
     */
    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    /**
     * @param string $scheme
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
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
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }
}
