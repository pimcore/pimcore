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
     * Prefix for the Placeholder Classes in the Website directory
     *
     * @var string
     */
    protected static $websiteClassPrefix = 'Website_Placeholder_';

    /**
     * Contains the document object
     *
     * @var Document | null
     */
    protected $document;

    /**
     * Sets a custom website class prefix for the Placeholder Classes
     *
     * @static
     * @param $string
     */
    public static function setWebsiteClassPrefix($string)
    {
        self::$websiteClassPrefix = $string;
    }

    /**
     * Returns the website class prefix for the Placeholder Classes
     *
     * @static
     * @return string
     */
    public static function getWebsiteClassPrefix()
    {
        return self::$websiteClassPrefix;
    }

    /**
     * Set a custom Placeholder prefix
     *
     * @throws Exception
     * @param string $prefix
     * @return void
     */
    public static function setPlaceholderPrefix($prefix)
    {
        if (!is_string($prefix)) {
            throw new Exception("\$prefix mustn'n be empty");
        }
        self::$placeholderPrefix = $prefix;
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
    public function replacePlaceholders($mixed, $params = array(), $document = null)
    {
        if (is_string($mixed)) {
            $contentString = $mixed;
        } elseif ($mixed instanceof Document) {
            $contentString = Document_Service::render($mixed, $params, true);
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
                $websiteClass = self::getWebsiteClassPrefix() . $placeholder['placeholderClass'];
                $pimcoreClass = 'Pimcore_Placeholder_' . $placeholder['placeholderClass'];

                if (is_null($stringReplaced)) {
                    $stringReplaced = $placeholder['contentString'];
                }

                //first try to init the website class -> then the pimcore class
                if (class_exists($websiteClass)) {
                    $placeholderObject = new $websiteClass();
                } elseif (class_exists($pimcoreClass)) {
                    $placeholderObject = new $pimcoreClass();
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
                    if (!$replaceWith) {
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