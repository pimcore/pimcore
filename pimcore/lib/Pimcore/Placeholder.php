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

class Pimcore_Placeholder
{

    /**
     * Prefix for the Placeholders
     *
     * @var string
     */
    protected static $placeholderPrefix = '%';

    /**
     * Suffix for the Placeholders
     *
     * @var string
     */
    protected static $placeholderSuffix = ';';

    /**
     * Prefixes for the Placeholder Classes
     *
     * @var string
     */
    protected static $placeholderClassPrefixes = array('Pimcore_Placeholder_',
                                                       'Website_Placeholder_');

    /**
     * Contains the document object
     *
     * @var Document | null
     */
    protected $document;

    /**
     * Adds a Placeholder class prefix
     *
     * @static
     * @param $classPrefix string
     * @throws Exception
     * @return null
     */
    public static function addPlaceholderClassPrefix($classPrefix){
        if(!is_string($classPrefix) || $classPrefix == ''){
            throw new Exception('$classPrefix has to be a valid string and mustn\'t be empty');
        }

        self::$placeholderClassPrefixes[] = $classPrefix;
    }

    /**
     * Removes a Placeholder class prefix
     *
     * @static
     * @param $classPrefix string
     * @return bool
     * @throws Exception
     */
    public static function removePlaceholderClassPrefix($classPrefix){
        if(!is_string($classPrefix) || $classPrefix == ''){
            throw new Exception('$classPrefix has to be a valid string and mustn\'t be empty');
        }

        $arrayIndex = array_search($classPrefix,self::$placeholderClassPrefixes);

        if($arrayIndex === false){
            return false;
        }else{
            unset(self::$placeholderClassPrefixes[$arrayIndex]);
            return true;
        }
    }

    /**
     * Returns the Placeholder class prefixes
     * @static
     * @return array
     */
    public static function getPlaceholderClassPrefixes(){
        return array_reverse(self::$placeholderClassPrefixes);
    }

    /**
     * Sets a custom website class prefix for the Placeholder Classes
     *
     * @static
     * @param $string
     * @deprecated deprecated since version 1.4.6
     */
    public static function setWebsiteClassPrefix($string)
    {
        self::addPlaceholderClassPrefix($string);
    }

    /**
     * Returns the website class prefix for the Placeholder Classes
     *
     * @static
     * @return string
     * @deprecated deprecated since version 1.4.6
     */
    public static function getWebsiteClassPrefix()
    {
        return self::$placeholderClassPrefixes[1];
    }

    /**
     * Set a custom Placeholder prefix
     *
     * @throws Exception
     * @param string $prefix
     * @return void
     * @deprecated deprecated since version 1.4.6
     */
    public static function setPlaceholderPrefix($prefix)
    {
        self::addPlaceholderClassPrefix($prefix);
    }

    /**
     * Returns the Placeholder prefix
     *
     * @return string
     */
    public static function getPlaceholderPrefix()
    {
        return self::$placeholderPrefix;
    }

    /**
     * Returns the Placeholder suffix
     *
     * @return string
     */
    public static function getPlaceholderSuffix()
    {
        return self::$placeholderSuffix;
    }

    /**
     * Sets a custom Placeholder suffix
     *
     * @throws Exception
     * @param string $suffix
     * @return void
     */
    public function setPlaceholderSuffix($suffix)
    {
        if (!is_string($suffix)) {
            throw new Exception("\$suffix mustn'n be empty");
        }
        self::$placeholderSuffix = $suffix;
    }


    /**
     * Detects the Placeholders in a string and returns a array with the placeholder information
     *
     * @param string $contentString
     * @param null | array $params
     * @param null | Document $document
     * @return array
     */
    public function detectPlaceholders($contentString, $params, $document = null)
    {
        $placeholderStack = array();

        $regex = "/" . self::$placeholderPrefix . "([a-z_]+)\(([a-z_]+)[\s,]*(.*?)\)" . self::$placeholderSuffix . "/is";
        preg_match_all($regex, $contentString, $matches);

        if (is_array($matches[1])) {

            foreach ($matches[1] as $key => $match) {
                $placeholderString = $matches[0][$key]; //placeholder string
                $placeholderClass = $matches[1][$key]; //placeholder php class
                $placeholderKey = $matches[2][$key]; //key for the dynamic param
                $placeholderConfigString = $matches[3][$key];

                if ($placeholderConfigString) {
                    //try to create the json config object
                    try {
                        $configJsonString = str_replace("&quot;", '"', $placeholderConfigString);
                        $placeholderConfig = new Zend_Config_Json($configJsonString);
                    } catch (Exception $e) {
                        Logger::warn('PlaceholderConfig is not a valid JSON string. PlaceholderConfig for ' . $placeholderClass . ' ignored.');
                    }
                } else {
                    //create an empty config object if no config object was passed
                    $placeholderConfig = new Zend_Config_Json("{}");
                }

                $placeholderStack[] = array('placeholderString' => $placeholderString,
                    'placeholderClass' => $placeholderClass,
                    'placeholderKey' => $placeholderKey,
                    'placeholderConfig' => $placeholderConfig,
                    'document' => $document,
                    'params' => $params,
                    'contentString' => $contentString);
            }
        }

        return $placeholderStack;
    }

    /**
     * Helper to simply replace the placeholders with there value
     *
     * @param string | Document $mixed
     * @param array $params
     * @param null | Document $document
     * @return string
     */
    public function replacePlaceholders($mixed, $params = array(), $document = null,$enableLayoutOnPlaceholderReplacement = true)
    {
        if (is_string($mixed)) {
            $contentString = $mixed;
        } elseif ($mixed instanceof Document) {
            $contentString = Document_Service::render($mixed, $params, $enableLayoutOnPlaceholderReplacement);
        }
        if ($document instanceof Document === false) {
            $document = null;
        }

        //detects the placeholders
        $placeholderStack = $this->detectPlaceholders($contentString, $params, $document);

        //replaces the placeholders if any were found
        if (!empty($placeholderStack)) {
            $replacedString = $this->replacePlaceholdersFromStack($placeholderStack);
            return $replacedString;
        } else {
            return $contentString;
        }
    }

    /**
     * Creates the Placeholder objects and replaces the placeholder string
     * with the rendered content of the placeholder object
     *
     * @param array $placeholderStack
     * @return string
     */
    protected function replacePlaceholdersFromStack($placeholderStack = array())
    {
        $stringReplaced = null;
        if (!empty($placeholderStack)) {

            foreach ($placeholderStack as $placeholder) {
                $placeholderObject = null;
                $placeholderClassPrefixes = self::getPlaceholderClassPrefixes();

                $placeholderObject = null;

                foreach($placeholderClassPrefixes as $classPrefix){
                    $className = $classPrefix . $placeholder['placeholderClass'];
                    if(Pimcore_Tool::classExists($className)){
                        $placeholderObject = new $className();
                        break;
                    }
                }

                if (is_null($stringReplaced)) {
                    $stringReplaced = $placeholder['contentString'];
                }

                if ($placeholderObject instanceof Pimcore_Placeholder_Abstract) {

                    //setting values from placeholder stack to placeholder objects
                    foreach (array_keys($placeholder) as $key) {
                        if ($key == 'placeholderClass') {
                            continue;
                        }
                        $placeholderObject->{'set' . ucfirst($key)}($placeholder[$key]);
                    }
                    $placeholderObject->setLocale();

                    $replaceWith = $placeholderObject->getReplacement();
                    if (!isset($replaceWith)) {
                        $replaceWith = $placeholderObject->getEmptyValue();
                    }
                    $stringReplaced = str_replace($placeholderObject->getPlaceholderString(), $replaceWith, $stringReplaced);
                } else {
                    Logger::warn('Ignoring Placeholder "' . $placeholder['placeholderClass'] . '" -> Class not Found or not an instance of Pimcore_Placeholder_Abstract!');
                }
            }
        }
        return $stringReplaced;
    }
}