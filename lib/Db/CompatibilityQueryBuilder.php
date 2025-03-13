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

namespace Pimcore\Db;

use Doctrine\DBAL\Query\QueryBuilder;
use ReflectionClass;

final class CompatibilityQueryBuilder extends QueryBuilder
{
    /**
     * Gets a query part by its name like in DBAL3.
     * getQueryPart was removed with DBAL4
     * Adding method via reflection to keep compatibility with current state of code
     * Should be refactored and removed with the next major version e.g. Pimcore 13
     */
    public function getQueryPart($queryPartName): mixed
    {
        $reflection = new ReflectionClass($this);
        $property = $reflection->getParentClass()->getProperty($queryPartName);
        $property->setAccessible(true);

        return $property->getValue($this);
    }
}
