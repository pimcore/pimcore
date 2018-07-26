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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config;

/**
 * Interface for IndexService Tenant Configurations using mysql as index
 */
interface IMysqlConfig extends IConfig
{
    /**
     * returns table name of product index
     *
     * @return string
     */
    public function getTablename();

    /**
     * returns table name of product index reations
     *
     * @return string
     */
    public function getRelationTablename();

    /**
     * return table name of product index tenant relations for subtenants
     *
     * @return string
     */
    public function getTenantRelationTablename();

    /**
     * return join statement in case of subtenants
     *
     * @return string
     */
    public function getJoins();

    /**
     * returns additional condition in case of subtenants
     *
     * @return string
     */
    public function getCondition();

    /**
     * returns column type for id
     *
     * @param $isPrimary
     *
     * @return string
     */
    public function getIdColumnType($isPrimary);
}
