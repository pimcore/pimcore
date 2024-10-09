<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Cache\Tool;

use Pimcore;
use Pimcore\Cache;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element;

/**
 * @internal
 */
class Warming
{
    protected static int $perIteration = 20;

    protected static int $timoutBetweenIteration = 2;

    public static function documents(array $types = null): void
    {
        if (empty($types)) {
            $types = ['page', 'snippet', 'folder', 'link'];
        }

        $list = new Document\Listing();
        $list->setCondition("`type` IN ('" . implode("','", $types) . "')");

        self::loadToCache($list);
    }

    public static function objects(array $types = null, array $classes = null): void
    {
        if (empty($types)) {
            $types = DataObject::$types;
        }

        $classesCondition = '';
        if (!empty($classes)) {
            $classesCondition .= " AND className IN ('" . implode("','", $classes) . "')";
        }

        $list = new DataObject\Listing();
        $list->setCondition("`type` IN ('" . implode("','", $types) . "')" . $classesCondition);

        self::loadToCache($list);
    }

    public static function assets(array $types = null): void
    {
        if (empty($types)) {
            $types = ['folder', 'image', 'text', 'audio', 'video', 'document', 'archive', 'unknown'];
        }

        $list = new Asset\Listing();
        $list->setCondition("`type` IN ('" . implode("','", $types) . "')");

        self::loadToCache($list);
    }

    /**
     * Adds a Pimcore Object/Asset/Document to the cache
     */
    public static function loadElementToCache(Element\ElementInterface $element): void
    {
        $cacheKey = Element\Service::getElementType($element) . '_' . $element->getId();
        Cache::save($element, $cacheKey, [], null, 0, true);
    }

    protected static function loadToCache(Document\Listing|Asset\Listing|DataObject\Listing $list): void
    {
        $totalCount = $list->getTotalCount();
        $iterations = ceil($totalCount / self::getPerIteration());

        Logger::info('New list of elements queued for storing into the cache with ' . $iterations . ' iterations and ' . $totalCount . ' total items');

        for ($i = 0; $i < $iterations; $i++) {
            Logger::info('Starting iteration ' . $i . ' with offset: ' . (self::getPerIteration() * $i));

            $list->setLimit(self::getPerIteration());
            $list->setOffset(self::getPerIteration() * $i);
            $elements = $list->load();

            foreach ($elements as $element) {
                self::loadElementToCache($element);
            }

            Pimcore::collectGarbage();
            sleep(self::getTimoutBetweenIteration());
        }
    }

    public static function setTimoutBetweenIteration(int $timoutBetweenIteration): void
    {
        self::$timoutBetweenIteration = $timoutBetweenIteration;
    }

    public static function getTimoutBetweenIteration(): int
    {
        return self::$timoutBetweenIteration;
    }

    public static function setPerIteration(int $perIteration): void
    {
        self::$perIteration = $perIteration;
    }

    public static function getPerIteration(): int
    {
        return self::$perIteration;
    }
}
