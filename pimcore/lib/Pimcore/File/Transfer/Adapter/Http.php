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

namespace Pimcore\FIle\Transfer\Adapter;

use Pimcore\File;

class Http extends \Zend_File_Transfer_Adapter_Http {

    /**
     * @var null
     */
    protected $sourceFile = null;

    /**
     * @var null
     */
    protected $destinationFile = null;

    /**
     * @var null
     */
    protected $httpClient = null;

    /**
     * @param \Zend_Http_Client $httpClient
     */
    public function setHttpClient(\Zend_Http_Client $httpClient){
        $this->httpClient = $httpClient;
    }

    /**
     * @return null
     */
    public function getHttpClient(){
        return $this->httpClient;
    }

    /**
     * @param $sourceFile
     * @return $this
     */
    public function setSourceFile($sourceFile){
        $this->sourceFile = $sourceFile;
        return $this;
    }

    /**
     * @return null
     */
    public function getSourceFile(){
        return $this->sourceFile;
    }

    /**
     * @param $destinationFile
     */
    public function setDestinationFile($destinationFile){
        $this->destinationFile  = $destinationFile;
    }

    /**
     * @return null
     */
    public function getDestinationFile(){
        return $this->destinationFile;
    }

    /**
     * @param null $options
     * @return bool|void
     * @throws \Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function send($options = null)
    {
        $sourceFile = $this->getSourceFile();
        $destinationFile = $this->getDestinationFile();

        if(!$sourceFile){
            throw new \Exception("No sourceFile provided.");
        }

        if(!$destinationFile){
            throw new \Exception("No destinationFile provided.");
        }

        if(is_array($options)){
            if($options['overwrite'] == false && file_exists($destinationFile)){
                throw new \Exception("Destination file : '" . $destinationFile ."' already exists.");
            }
        }

        if(!$this->getHttpClient()){
            $httpClient = \Pimcore\Tool::getHttpClient(null,array('timeout' => 3600*60));
        }else{
            $httpClient = $this->getHttpClient();
        }

        $httpClient->setUri($this->getSourceFile());
        $response = $httpClient->request();
        if($response->isSuccessful()){
            $data = $response->getBody();
            File::mkdir(dirname($destinationFile));
            $result = File::put($destinationFile,$data);
            if($result === false){
                throw new \Exception("Couldn't write destination file:" . $destinationFile);
            }
        }else{
            throw new \Exception("Couldn't download file:" . $sourceFile);
        }
        return true;
    }
}