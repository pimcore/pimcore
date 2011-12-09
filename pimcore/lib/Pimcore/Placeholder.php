<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kisi
 * Date: 20.11.11
 * Time: 07:27
 * To change this template use File | Settings | File Templates.
 */
 
class Pimcore_Placeholder{
    
    protected static $placeholderPrefix = '%';
    protected static $placeholderSuffix = ';';
    protected static $websiteClassPrefix = 'Website_Placeholder_';
    protected $document;

    public static function setWebsiteClassPrefix($string){
        self::$websiteClassPrefix = $string;
    }

    public static function getWebsiteClassPrefix(){
        return self::$websiteClassPrefix;
    }

    //static helper
    public static function render(){
         $placeholder = new self;

    }

    public function replacePlaceholders($mixed,$params = array(),$document = null){
        if(is_string($mixed)){
            $contentString = $mixed;
        }elseif($mixed instanceof Document){
            $contentString = Document_Service::render($mixed,$params,true);
        }
        if($document instanceof Document === false){
            $document = null;
        }
        $placeholderStack   = $this->detectPlaceholders($contentString,$params,$document);
        if(!empty($placeholderStack)){
            $replacedString     = $this->replacePlaceholdersFromStack($placeholderStack);
            return $replacedString;
        }else{
            return $contentString;
        }


    }

    public function detectPlaceholders($contentString,$params,$document = null){
        $placeholderStack = array();

        $regex = "/".self::$placeholderPrefix."([a-z_]+)\(([a-z_]+)[\s,]*(.*?)\)".self::$placeholderSuffix."/is";
        preg_match_all($regex,$contentString,$matches);
        if(is_array($matches[1])){
            foreach($matches[1] as $key => $match){ //$matches[1] contains placeholder objects
                $placeholderString      = $matches[0][$key];
                $placeholderClass       = $matches[1][$key];
                $placeholderKey         = $matches[2][$key];

                if($matches[3][$key]){
                    try{
                        $configJsonString = str_replace("&quot;", '"', $matches[3][$key]);
                        $placeholderConfig = new Zend_Config_Json($configJsonString);
                    }catch(Exception $e){
                        Logger::warn('PlaceholderConfig is not a valid JSON string. PlaceholderConfig for '.$placeholderClass.' ignored.');
                    }
                }else{
                    $placeholderConfig = new Zend_Config_Json("{}");
                }
                $placeholderStack[] = array('placeholderString' => $placeholderString,
                                                  'placeholderClass'  => $placeholderClass,
                                                  'placeholderKey'    => $placeholderKey,
                                                  'placeholderConfig' => $placeholderConfig,
                                                  'document'          => $document,
                                                  'params'            => $params,
                                                  'contentString'     => $contentString);
            }
        }
        return $placeholderStack;
    }

    protected function replacePlaceholdersFromStack($placeholderStack = array()){
        $stringReplaced = null;
        if(!empty($placeholderStack)){
            foreach($placeholderStack as $placeholder){
                $placeholderObject  = null;
                $websiteClass       = self::getWebsiteClassPrefix().$placeholder['placeholderClass'];
                $pimcoreClass       = 'Pimcore_Placeholder_'.$placeholder['placeholderClass'];
                #echo $placeholder['contentString'].'<br/><br/>';
                if(is_null($stringReplaced)){
                    $stringReplaced = $placeholder['contentString'];
                }
                if(class_exists($websiteClass)){
                    $placeholderObject = new $websiteClass();
                }elseif(class_exists($pimcoreClass)){
                    $placeholderObject = new $pimcoreClass();
                }

                if($placeholderObject instanceof Pimcore_Placeholder_Abstract){

                    foreach(array_keys($placeholder) as $key){
                        if($key == 'placeholderClass'){ continue; }
                        $placeholderObject->{'set'.ucfirst($key)}($placeholder[$key]);
                    }
                    $placeholderObject->setLocale();

                    $replaceWith = $placeholderObject->getReplacement();
                    if(!$replaceWith){
                        $replaceWith = $placeholderObject->getEmptyValue();
                    }
                    $stringReplaced = str_replace($placeholderObject->getPlaceholderString(),$replaceWith,$stringReplaced);
                }else{
                    Logger::warn('Ignoring Placeholder "'.$placeholder['placeholderClass'].'" -> Class not Found or not an instance of Pimcore_Placeholder_Abstract!');
                }
            }
        }
        return $stringReplaced;
    }

    /**
     * To set a custom Placeholder prefix
     *
     * @throws Exception
     * @param string $prefix
     * @return void
     */
    public static function setPlaceholderPrefix($prefix){
        if(!is_string($prefix)){
            throw new Exception("\$prefix mustn'n be empty");
        }
        self::$placeholderPrefix = $prefix;
    }

    /**
     * Returns the placeholder prefix
     *
     * @return string
     */
    public static function getPlaceholderPrefix(){
        return self::$placeholderPrefix;
    }

    /**
     * Returns the placeholder suffix
     *
     * @return string
     */
    public static function getPlaceholderSuffix(){
        return self::$placeholderSuffix;
    }

    /**
     * To set a custom Placeholder suffix
     *
     * @throws Exception
     * @param string $suffix
     * @return void
     */
    public function setPlaceholderSuffix($suffix){
        if(!is_string($suffix)){
            throw new Exception("\$suffix mustn'n be empty");
        }
        self::$placeholderSuffix = $suffix;
    }
}