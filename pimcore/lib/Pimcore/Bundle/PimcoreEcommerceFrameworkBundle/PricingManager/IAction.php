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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager;

interface IAction
{
    /**
     * @param IEnvironment $environment
     *
     * @return IAction
     */
    public function executeOnProduct(IEnvironment $environment);

    /**
     * @param IEnvironment $environment
     *
     * @return IAction
     */
    public function executeOnCart(IEnvironment $environment);

    /**
     * @return string
     */
    public function toJSON();

    /**
     * @param string $string
     *
     * @return IAction
     */
    public function fromJSON($string);
}
