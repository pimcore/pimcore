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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Getter;

/**
 * Class ExtendedGetter
 *
 * Interface for getter of product index colums which consider sub object ids and tenant configs
 */
interface IExtendedGetter {

    public static function get($object, $config = null, $subObjectId = null, \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config\IConfig $tenantConfig = null);
}
