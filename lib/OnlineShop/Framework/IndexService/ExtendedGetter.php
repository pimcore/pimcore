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
 * Class OnlineShop_Framework_IndexService_ExtendedGetter
 *
 * Interface for getter of product index colums which consider sub object ids and tenant configs
 */
interface OnlineShop_Framework_IndexService_ExtendedGetter {

    public static function get($object, $config = null, $subObjectId = null, OnlineShop_Framework_IndexService_Tenant_IConfig $tenantConfig = null);
}
