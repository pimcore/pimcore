<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Cache\Tool;

use Pimcore\Cache;
use Pimcore\Model\Listing\AbstractListing;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Object;
use Pimcore\Model\Asset;
use Pimcore\Logger;

class Warming
{


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
     */
    public static function documents($types = null)
    {
        if (empty($types)) {
            $types = ["page", "snippet", "folder", "link"];
        }

        $list = new Document\Listing();
        $list->setCondition("type IN ('" . implode("','", $types) . "')");

        self::loadToCache($list);
    }

    /**
     * @static
     * @param array $types
     * @param null $classes
     */
    public static function objects($types = null, $classes = null)
    {
        if (empty($types)) {
            $types = ["object", "folder", "variant"];
        }

        $classesCondition = "";
        if (!empty($classes)) {
            $classesCondition .= " AND o_className IN ('" . implode("','", $classes) . "')";
        }

        $list = new Object\Listing();
        $list->setCondition("o_type IN ('" . implode("','", $types) . "')" . $classesCondition);

        self::loadToCache($list);
    }

    /**
     * @static
     * @param array $types
     */
    public static function assets($types = null)
    {
        if (empty($types)) {
            $types = ["folder", "image", "text", "audio", "video", "document", "archive", "unknown"];
        }

        $list = new Asset\Listing();
        $list->setCondition("type IN ('" . implode("','", $types) . "')");

        self::loadToCache($list);
    }

    /**
     * Adds a Pimcore Object/Asset/Document to the cache
     *
     * @param $element
     */
    public static function loadElementToCache($element)
    {
        $cacheKey = Element\Service::getElementType($element) . "_" . $element->getId();
        Cache::save($element, $cacheKey, [], null, null, true);
    }

    /**
     * @param AbstractListing $list
     */
    protected static function loadToCache(AbstractListing $list)
    {
        $totalCount = $list->getTotalCount();
        $iterations = ceil($totalCount / self::getPerIteration());

        Logger::info("New list of elements queued for storing into the cache with " . $iterations . " iterations and " . $totalCount . " total items");

        for ($i=0; $i<$iterations; $i++) {
            Logger::info("Starting iteration " . $i . " with offset: " . (self::getPerIteration() * $i));

            $list->setLimit(self::getPerIteration());
            $list->setOffset(self::getPerIteration() * $i);
            $elements = $list->load();

            foreach ($elements as $element) {
                self::loadElementToCache($element);
            }

            \Pimcore::collectGarbage();
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
