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
 * Interface for IndexService Tenant Configurations with mockup implementations
 *
 * Interface \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IMockupConfig
 */
interface IMockupConfig
{
    /**
     * creates object mockup for given data
     *
     * @param $objectId
     * @param $data
     * @param $relations
     *
     * @return mixed
     */
    public function createMockupObject($objectId, $data, $relations);
}
