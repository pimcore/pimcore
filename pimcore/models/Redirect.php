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
     * @var string
     */
    public $target;

    /**
     * @var string
     */
    public $statusCode = 301;


    /**
     * @var string
     */
    public $priority = 1;


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
        $this->id = $id;
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
}
