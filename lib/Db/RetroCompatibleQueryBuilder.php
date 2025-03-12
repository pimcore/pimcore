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
use Doctrine\DBAL\Connection;

class RetroCompatibleQueryBuilder extends QueryBuilder
{
    /**
     * Gets a query part by its name.
     *
     * @return mixed
     */
    public function getQueryPart(string $queryPartName)
    {
        $reflection = new \ReflectionClass($this);
        $property = $reflection->getParentClass()->getProperty($queryPartName);
        $property->setAccessible(true);

        return $property->getValue($this);
    }
}
