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
 * @package    Redirect
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Redirect extends Pimcore_Model_Abstract {

    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $source;

    /**
     * @var bool
     */
    public $sourceEntireUrl;

    /**
     * @var int
     */
    public $sourceSite;

    /**
     * @var string
     */
    public $target;

    /**
     * @var int
     */
    public $targetSite;

    /**
     * @var string
     */
    public $statusCode = 301;

    /**
     * @var string
     */
    public $priority = 1;

    /**
     * @var int
     */
    public $expiry;


    /**
     * StatusCodes
     */
    public static $statusCodes = array(
        "300" => "Multiple Choices",
        "301" => "Moved Permanently",
        "302" => "Found",
        "303" => "See Other",
        "307" => "Temporary Redirect"
    );

    /**
     * @param integer $id
     * @return Redirect
     */
    public static function getById($id) {

        $redirect = new self();
        $redirect->setId(intval($id));
        $redirect->getResource()->getById();

        return $redirect;
    }

    /**
     * @return Redirect
     */
    public static function create() {
        $redirect = new self();
        $redirect->save();

        return $redirect;
    }


    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSource() {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getTarget() {
        return $this->target;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
    }

    /**
     * @param string $source
     * @return void
     */
    public function setSource($source) {
        $this->source = $source;
    }

    /**
     * @param string $target
     * @return void
     */
    public function setTarget($target) {
        $this->target = $target;
    }

    /**
     * @param integer $priority
     * @return void
     */
    public function setPriority($priority) {
        if($priority) {
           $this->priority = $priority; 
        }
    }

    /**
     * @return integer
     */
    public function getPriority() {
        return $this->priority;
    }

    /**
     * @param integer $statusCode
     * @return void
     */
    public function setStatusCode($statusCode) {
        if($statusCode) {
            $this->statusCode = $statusCode;
        }
    }

    /**
     * @return integer
     */
    public function getStatusCode() {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getHttpStatus() {
        $statusCode = $this->getStatusCode();
        if (empty($statusCode)) {
            $statusCode = "301";
        }
        return "HTTP/1.1 " . $statusCode . " " . self::$statusCodes[$statusCode];
    }
    
    /**
     * @return void
     */
    public function clearDependedCache() {
        
        // this is mostly called in Redirect_Resource not here
        try {
            Pimcore_Model_Cache::clearTag("redirect");
        }
        catch (Exception $e) {
            Logger::info($e);
        }
    }

    /**
     * @param int $expiry
     */
    public function setExpiry($expiry)
    {
        if(is_string($expiry)) {
            $expiry = strtotime($expiry);
        }
        $this->expiry = $expiry;
    }

    /**
     * @return int
     */
    public function getExpiry()
    {
        return $this->expiry;
    }

    /**
     *
     */
    public static function maintenanceCleanUp() {
        $list = new Redirect_List();
        $list->setCondition("expiry < " . time() . " AND expiry IS NOT NULL AND expiry != ''");
        $list->load();

        foreach ($list->getRedirects() as $redirect) {
            echo $redirect->getSource() . "\n";
            $redirect->delete();
        }
    }

    /**
     * @param boolean $sourceEntireUrl
     */
    public function setSourceEntireUrl($sourceEntireUrl)
    {
        if($sourceEntireUrl) {
            $this->sourceEntireUrl = (bool) $sourceEntireUrl;
        } else {
            $this->sourceEntireUrl = null;
        }
    }

    /**
     * @return boolean
     */
    public function getSourceEntireUrl()
    {
        return $this->sourceEntireUrl;
    }

    /**
     * @param int $sourceSite
     */
    public function setSourceSite($sourceSite)
    {
        if($sourceSite) {
            $this->sourceSite = (int) $sourceSite;
        } else {
            $this->sourceSite = null;
        }
    }

    /**
     * @return int
     */
    public function getSourceSite()
    {
        return $this->sourceSite;
    }

    /**
     * @param int $targetSite
     */
    public function setTargetSite($targetSite)
    {
        if($targetSite) {
            $this->targetSite = (int) $targetSite;
        } else {
            $this->targetSite = null;
        }
    }

    /**
     * @return int
     */
    public function getTargetSite()
    {
        return $this->targetSite;
    }
}
