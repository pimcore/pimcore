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

class Pimcore_Cache_Tool_Warming {


    /**
     * @var int
     */
    protected static $perIteration = 20;

    /**
     * @var int
     */
    protected static $timoutBetweenIteration = 2;

    /**
     * @static
     * @param array $types
     * @return void
     */
    public static function documents ($types = null) {

        if(empty($types)) {
            $types = array("page", "snippet", "folder", "link");
        }

        $list = new Document_List();
        $list->setCondition("type IN ('" . implode("','", $types) . "')");

        self::loadToCache($list);
    }

    /**
     * @static
     * @param array $types
     * @return void
     */
    public static function objects ($types = null, $classes = null) {

        if(empty($types)) {
            $types = array("object", "folder", "variant");
        }

        $classesCondition = "";
        if(!empty($classes)) {
            $classesCondition .= " AND o_className IN ('" . implode("','", $classes) . "')";
        }

        $list = new Object_List();
        $list->setCondition("o_type IN ('" . implode("','", $types) . "')" . $classesCondition);

        self::loadToCache($list);
    }

    /**
     * @static
     * @param array $types
     * @return void
     */
    public static function assets ($types = null) {

        if(empty($types)) {
            $types = array("folder", "image", "text", "audio", "video", "document", "archive", "unknown");
        }

        $list = new Asset_List();
        $list->setCondition("type IN ('" . implode("','", $types) . "')");

        self::loadToCache($list);
    }


    /**
     * @static
     * @param Pimcore_Model_List_Abstract $list
     * @return void
     */
    protected static function loadToCache (Pimcore_Model_List_Abstract $list) {
        
        $totalCount = $list->getTotalCount();
        $iterations = ceil($totalCount / self::getPerIteration());

        Logger::info("New list of elements queued for storing into the cache with " . $iterations . " iterations and " . $totalCount . " total items");

        for ($i=0; $i<$iterations; $i++) {

            Logger::info("Starting iteration " . $i . " with offset: " . (self::getPerIteration() * $i));

            $list->setLimit(self::getPerIteration());
            $list->setOffset(self::getPerIteration() * $i);
            $elements = $list->load();

            foreach ($elements as $element) {
                $cacheKey = Element_Service::getElementType($element) . "_" . $element->getId();
                Pimcore_Model_Cache::storeToCache($element, $cacheKey);
            }

            Pimcore::collectGarbage();
            sleep(self::getTimoutBetweenIteration());
        }
    }

    /**
     * @param int $timoutBetweenIteration
     */
    public static function setTimoutBetweenIteration($timoutBetweenIteration)
    {
        self::$timoutBetweenIteration = $timoutBetweenIteration;
    }

    /**
     * @return int
     */
    public static function getTimoutBetweenIteration()
    {
        return self::$timoutBetweenIteration;
    }

    /**
     * @param int $perIteration
     */
    public static function setPerIteration($perIteration)
    {
        self::$perIteration = $perIteration;
    }

    /**
     * @return int
     */
    public static function getPerIteration()
    {
        return self::$perIteration;
    }
}