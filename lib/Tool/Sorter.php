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

namespace Pimcore\Tool;

use Pimcore\Bundle\AdminBundle\GDPR\DataProvider\DataProviderInterface;
use Pimcore\Cache\Core\CacheQueueItem;
use Pimcore\HttpKernel\BundleCollection\ItemInterface;
use Pimcore\Model\Element\Tag;
use Pimcore\Model\Staticroute;
use Pimcore\Targeting\Model\TargetGroupAssignment;
use Pimcore\Workflow\WorkflowConfig;

/**
 * Static callbacks for array sorting functions.
 *
 * @see https://www.php.net/manual/en/array.sorting.php
 */
class Sorter
{
    public static function date(array $a, array $b): int
    {
        return $a['date'] <=> $b['date'];
    }

    public static function index(array $a, array $b): int
    {
        return $a['index'] <=> $b['index'];
    }

    public static function name(array $a, array $b): int
    {
        return $a['name'] <=> $b['name'];
    }

    public static function population(array $a, array $b): int
    {
        return $a['population'] <=> $b['population'];
    }

    public static function position(array $a, array $b): int
    {
        return $a['position'] <=> $b['position'];
    }

    public static function priority(array $a, array $b): int
    {
        return $a['priority'] <=> $b['priority'];
    }

    public static function sortIndex(array $a, array $b): int
    {
        return $a['sortIndex'] <=> $b['sortIndex'];
    }

    public static function sort(array $a, array $b): int
    {
        $a = $a['sort'] ?: 0;
        $b = $b['sort'] ?: 0;

        return $a <=> $b;
    }

    /**
     * show enabled/active first, then order by priority for bundles with the
     * same enabled state. Uses reverse sort by priority -> higher comes first
     *
     * @param array $a
     * @param array $b
     */
    public function extensions(array $a, array $b): int
    {
        if ($a['active'] && !$b['active']) {
            return -1;
        }

        if (!$a['active'] && $b['active']) {
            return 1;
        }

        return $b['priority'] <=> $a['priority'];
    }

    public static function siteId(Staticroute $a, Staticroute $b): int
    {
        return $a->getSiteId() <=> $b->getSiteId();
    }

    /**
     * Order by key length, longer key have priority
     * (%abcd prior %ab, so that %ab doesn't replace %ab in [%ab]cd)
     * @param string|null $a
     * @param string|null $b
     */
    public static function strlen(?string $a, ?string $b): int
    {
        return strlen($b) <=> strlen($a);
    }

    public static function cpath(array $a, array $b): int
    {
        return strcmp($a['cpath'], $b['cpath']);
    }

    public static function filesize(array $a, array $b): int
    {
        return strcmp($a['filesize'], $b['filesize']);
    }

    public static function display(array $a, array $b): int
    {
        return strcmp($a['display'], $b['display']);
    }

    public static function itemPriority(CacheQueueItem|ItemInterface|WorkflowConfig $a, CacheQueueItem|ItemInterface|WorkflowConfig $b): int
    {
        return $a->getPriority() <=> $b->getPriority();
    }

    public static function namePath(Tag $a, Tag $b): int
    {
        return strcmp($a->getNamePath(), $b->getNamePath());
    }

    public static function count(TargetGroupAssignment $a, TargetGroupAssignment $b): int
    {
        return $a->getCount() <=> $b->getCount();
    }

    /**
     * @see DataProviderInterface::getSortPriority()
     * Higher is sorted first so $b is to the left of $a in the comparison
     *
     * @param DataProviderInterface $a
     * @param DataProviderInterface $b
     */
    public static function sortPriority(DataProviderInterface $a, DataProviderInterface $b): int
    {
        return $b->getSortPriority() <=> $a->getSortPriority();
    }
}
