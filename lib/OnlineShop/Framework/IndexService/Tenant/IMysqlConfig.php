<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


/**
 * Interface for IndexService Tenant Configurations using mysql as index
 *
 * Interface OnlineShop_Framework_IndexService_Tenant_IMysqlConfig
 */
interface OnlineShop_Framework_IndexService_Tenant_IMysqlConfig extends OnlineShop_Framework_IndexService_Tenant_IConfig {

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
     * @return string
     */
    public function getIdColumnType($isPrimary);

}