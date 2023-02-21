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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config;

/**
 * Interface for IndexService Tenant Configurations using mysql as index
 */
interface MysqlConfigInterface extends ConfigInterface
{
    /**
     * returns table name of product index
     *
     * @return string
     */
    public function getTablename(): string;

    /**
     * returns table name of product index reations
     *
     * @return string
     */
    public function getRelationTablename(): string;

    /**
     * return table name of product index tenant relations for subtenants
     *
     * @return string
     */
    public function getTenantRelationTablename(): string;

    /**
     * return join statement in case of subtenants
     *
     * @return string
     */
    public function getJoins(): string;

    /**
     * returns additional condition in case of subtenants
     *
     * @return string
     */
    public function getCondition(): string;
}
