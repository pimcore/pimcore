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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Log;

use \Zend_Log;

class Log extends Zend_Log {

    private $component = null;
    private $fileObject = null;
    private $relatedObject = null;
    private $relatedObjectType = 'object';

    /**
     * @param \Zend_Log_Writer $writer
     */
    public function __construct($writer = null) {
        parent::__construct($writer);

    }

    /**
     * @param string $component
     * @return void
     */
    public function setComponent($component) {
        $this->component = $component;
    }

    /**
     * @param \Pimcore\Log\FileObject | string $fileObject
     * @return void
     */
    public function setFileObject($fileObject) {
        $this->fileObject = $fileObject;
    }

    /**
     * @param \\Pimcore\Model\Object\AbstractObject | \Pimcore\Model\Document | \Pimcore\Model\Asset | int $relatedObject
     * @return void
     */
    public function setRelatedObject($relatedObject) {
        $this->relatedObject = $relatedObject;

        if ($this->relatedObject instanceof \Pimcore\Model\Object\AbstractObject) {
            $this->relatedObjectType = 'object';
        } elseif ($this->relatedObject instanceof \Pimcore\Model\Asset) {
            $this->relatedObjectType = 'asset';
        } elseif ($this->relatedObject instanceof \Pimcore\Model\Document) {
            $this->relatedObjectType = 'document';
        } else {
            $this->relatedObjectType = 'object';
        }
    }

    /**
     * @param $message
     * @param $priority
     * @param $component
     * @param FileObject|string $fileObject
     * @param int|\Pimcore\Model\Object\AbstractObject $relatedObject
     * @return void
     */
    public function log($message, $priority, $relatedObject = null, $fileObject = null, $component = null) {
        $extras = array();

        if ($component) {
            $extras["component"] = $component;
        } else if ($this->component) {
            $extras["component"] = $this->component;
        }

        if (!$fileObject && $this->fileObject) {
            $fileObject = $this->fileObject;
        }

        if ($fileObject) {
            if (is_string($fileObject)) {
                $extras["fileobject"] = $this->getHostname() . "/" . str_replace(PIMCORE_DOCUMENT_ROOT,'',$fileObject);
            } else {
                $extras["fileobject"] = $this->getHostname() . "/" . str_replace(PIMCORE_DOCUMENT_ROOT,'',$fileObject->getFilename());
            }
        }


        if(!$relatedObject && $this->relatedObject) {
            $relatedObject = $this->relatedObject;
        }

        if ($relatedObject) {
            if ($relatedObject instanceof \Pimcore\Model\Object\AbstractObject OR $relatedObject instanceof \Pimcore\Model\Document OR $relatedObject instanceof \Pimcore\Model\Asset) {
                $relatedObject = $relatedObject->getId();
            }
            if (is_numeric($relatedObject)) {
                $extras["relatedobject"] = $relatedObject;
                $extras["relatedobjecttype"] = $this->relatedObjectType;
            }
        }


        $backtrace = debug_backtrace();
        $call = $backtrace[1];

        if($call["class"] == "Pimcore\\Log\\Log"){
            $call = $backtrace[2];
        }

        $call["line"] = $backtrace[0]["line"];
        #$message = $call["class"] . $call["type"] . $call["function"] . "() [" . $call["line"] . "]: " . $message;
        $extras['source'] = $call["class"] . $call["type"] . $call["function"] . "() :" . $call["line"];

        parent::log($message, $priority, $extras);
    }

    /**
     * @static
     * @return string
     */
    public static function getHostname() {
        $config = \Pimcore\Config::getSystemConfig();
        $domain = $config->general->domain;
        if (empty($domain)) {
            $domain = $_SERVER["HTTP_HOST"];
        }
        if(strpos($domain, "http")=== 0) {
            $hostname = $domain;
        } else {
            $protocol = $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
            $hostname = $protocol . '://' . $domain;
        }

        return $hostname;
    }

    /**
     * @return void
     */
    public function flush() {
        foreach ($this->_writers as $writer) {
            if (method_exists($writer, "flush")) {
                $writer->flush();
            }
        }
    }

    /**
     * @return void
     */
    public function shutdown() {
        foreach ($this->_writers as $writer) {
            if (method_exists($writer, "shutdown")) {
                $writer->shutdown();
            }
        }
    }

    /**
     * @param $message
     * @param $component
     * @param FileObject|string $fileObject
     * @param int|\Pimcore\Model\Object\AbstractObject $relatedObject
     * @return void
     */
    public function emergency($message, $relatedObject = null, $fileObject = null, $component = null) {
        $this->log($message, Zend_Log::EMERG, $relatedObject, $fileObject, $component);
    }

    /**
     * @param $message
     * @param $component
     * @param FileObject|string $fileObject
     * @param int|\Pimcore\Model\Object\AbstractObject $relatedObject
     * @return void
     */
    public function critical($message, $relatedObject = null, $fileObject = null, $component = null) {
        $this->log($message, Zend_Log::CRIT, $relatedObject, $fileObject, $component);
    }

    /**
     * @param $message
     * @param $component
     * @param FileObject|string $fileObject
     * @param int|\Pimcore\Model\Object\AbstractObject $relatedObject
     * @return void
     */
    public function error($message, $relatedObject = null, $fileObject = null, $component = null) {
        $this->log($message, Zend_Log::ERR, $relatedObject, $fileObject, $component);
    }

    /**
     * @param $message
     * @param $component
     * @param FileObject|string $fileObject
     * @param int|\Pimcore\Model\Object\AbstractObject $relatedObject
     * @return void
     */
    public function alert($message, $relatedObject = null, $fileObject = null, $component = null) {
        $this->log($message, Zend_Log::ALERT, $relatedObject, $fileObject, $component);
    }

    /**
     * @param $message
     * @param $component
     * @param FileObject|string $fileObject
     * @param int|\Pimcore\Model\Object\AbstractObject $relatedObject
     * @return void
     */
    public function warning($message, $relatedObject = null, $fileObject = null, $component = null) {
        $this->log($message, Zend_Log::WARN, $relatedObject, $fileObject, $component);
    }

    /**
     * @param $message
     * @param $component
     * @param FileObject|string $fileObject
     * @param int|\Pimcore\Model\Object\AbstractObject $relatedObject
     * @return void
     */
    public function notice($message, $relatedObject = null, $fileObject = null, $component = null) {
        $this->log($message, Zend_Log::NOTICE, $relatedObject, $fileObject, $component);
    }

    /**
     * @param $message
     * @param $component
     * @param FileObject|string $fileObject
     * @param int|\Pimcore\Model\Object\AbstractObject $relatedObject
     * @return void
     */
    public function info($message, $relatedObject = null, $fileObject = null, $component = null) {
        $this->log($message, Zend_Log::INFO, $relatedObject, $fileObject, $component);
    }

    /**
     * @param $message
     * @param $component
     * @param FileObject|string $fileObject
     * @param int|\Pimcore\Model\Object\AbstractObject $relatedObject
     * @return void
     */
    public function debug($message, $relatedObject = null, $fileObject = null, $component = null) {
        $this->log($message, Zend_Log::DEBUG, $relatedObject, $fileObject, $component);
    }

    /**
     * Loggs the complete exception object as FileObject
     *
     * @param $message
     * @param \Exception $exceptionObject
     * @param int $priority
     * @param int|\Pimcore\Model\Object\AbstractObject $relatedObject
     * @param null $component
     */
     public function logException($message, $exceptionObject, $priority = Zend_Log::ALERT, $relatedObject = null, $component = null){
         if(is_null($priority)){
             $priority = Zend_Log::ALERT;
         }

         $message .= ' : '.$exceptionObject->getMessage();

         //workaround to prevent "nesting level to deep" errors when used var_export()
         ob_start();
         var_dump($exceptionObject);
         $dataDump = ob_get_clean();

         if(!$dataDump){
                 $dataDump = $exceptionObject->getMessage();
         }

         $fileObject = new \Pimcore\Log\FileObject($dataDump);

         $this->log($message, $priority, $relatedObject, $fileObject, $component);
     }

}
