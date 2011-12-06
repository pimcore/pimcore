<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kisi
 * Date: 19.11.11
 * Time: 14:27
 * To change this template use File | Settings | File Templates.
 */
 
abstract class Pimcore_Placeholder_Abstract {

    protected $placeholderString    = null;
    protected $placeholderKey       = null;
    protected $placeholderConfig    = null;
    protected $document             = null;
    protected $params               = array();
    protected $contentString        = null;
    protected $locale               = null;


    public function setPlaceholderString($string){
        $this->placeholderString = $string;
    }

    public function getPlaceholderString(){
        return $this->placeholderString;
    }

    public function setPlaceholderKey($key){
        $this->placeholderKey = $key;
    }

    public function getPlaceholderKey(){
        return $this->placeholderKey;
    }

    public function setPlaceholderConfig(Zend_Config_Json $config){
        $this->placeholderConfig = $config;
    }

    public function getPlaceholderConfig(){
        return $this->placeholderConfig;
    }

    public function setParams($params){
        if(is_array($params)){
            $this->params = $params;
        }
    }

    public function getParams(){
        return $this->params;
    }

    public function getParam($key){
        return $this->params[$key];
    }

    public function setContentString($contentString){
        if(is_string($contentString)){
            $this->contentString = $contentString;
        }
    }

    public function getContentString(){
        return $this->contentString;
    }


    public function getValue(){
        return $this->getParam($this->getPlaceholderKey());
    }

    public function setDocument($document){
        if($document instanceof Document){
            $this->document = $document;
        }
    }

    public function getDocument(){
        return $this->document;
    }

    public function getLocale(){
        if(is_null($this->locale)){
            $this->setLocale();
        }
        return $this->locale;
    }

    /**
     * Trying to set the locale from different sources
     * @param $locale
     * @return void
     */
    public function setLocale($locale = null){
        if($locale instanceof Zend_Locale){
            $this->locale = $locale;
        }elseif(is_string($locale)){
            $this->locale = new Zend_Locale($locale);
        }elseif($this->getParam('locale') || $this->getParam('language')){
            $this->setLocale(($this->getParam('locale')) ? $this->getParam('locale') : $this->getParam('language'));
        }else{
            $document = $this->getDocument();
            if($document instanceof Document){
              $this->setLocale($document->getProperty("language"));
            }

            if(is_null($this->locale)){ //last chance -> get it from registry or use the first Language defined in the system settings
                try {
                    $this->locale = Zend_Registry::get("Zend_Locale");
                } catch (Exception $e) {
                    list($language) = Pimcore_Tool::getValidLanguages();
                    $this->locale = new Zend_Locale($language);
                }
            }
        }
    }

    public function getLanguage(){
        return $this->getLocale()->getLanguage();
    }

    public function getEmptyValue(){
        return '';
    }


    /**
     * Has to return an appropriate value for the test replacement
     *
     * @abstract
     * @return string
     */
    abstract function getTestValue();

    /**
     * Has to replace the placeholder with the corresponding value
     *
     * @abstract
     * @return string
     */
    abstract function getReplacement();

}