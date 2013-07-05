<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 01.06.13
 * Time: 12:50
 */

class Pimcore_File_Transfer_Adapter_Http extends Zend_File_Transfer_Adapter_Http {

    protected $sourceFile = null;
    protected $destinationFile = null;

    protected $httpClient = null;

    public function setHttpClient(Zend_Http_Client $httpClient){
        $this->httpClient = $httpClient;
    }

    public function getHttpClient(){
        return $this->httpClient;
    }

    public function setSourceFile($sourceFile){
        $this->sourceFile = $sourceFile;
        return $this;
    }

    public function getSourceFile(){
        return $this->sourceFile;
    }

    public function setDestinationFile($destinationFile){
        $this->destinationFile  = $destinationFile;
    }

    public function getDestinationFile(){
        return $this->destinationFile;
    }

    public function send($options = null)
    {
        $sourceFile = $this->getSourceFile();
        $destinationFile = $this->getDestinationFile();

        if(!$sourceFile){
            throw new Exception("No sourceFile provided.");
        }

        if(!$destinationFile){
            throw new Exception("No destinationFile provided.");
        }

        if(is_array($options)){
            if($options['overwrite'] == false && file_exists($destinationFile)){
                throw new Exception("Destination file : '" . $destinationFile ."' already exists.");
            }
        }

        if(!$this->getHttpClient()){
            $httpClient = Pimcore_Tool::getHttpClient(null,array('timeout' => 3600*60));
        }else{
            $httpClient = $this->getHttpClient();
        }

        $httpClient->setUri($this->getSourceFile());
        $response = $httpClient->request();
        if($response->isSuccessful()){
            $data = $response->getBody();
            @mkdir(dirname($destinationFile),0755,true);
            $result = file_put_contents($destinationFile,$data);
            if($result === false){
                throw new Exception("Couldn't write destination file:" . $destinationFile);
            }
        }else{
            throw new Exception("Couldn't download file:" . $sourceFile);
        }
        return true;
    }


}