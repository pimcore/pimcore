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

interface ICondition {

    /**
     * @param IEnvironment $environment
     *
     * @return boolean
     */
    public function check(IEnvironment $environment);

    /**
     * @return string
     */
    public function toJSON();

    /**
     * @param string $string
     *
     * @return ICondition
     */
    public function fromJSON($string);
}