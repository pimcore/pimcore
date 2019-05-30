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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager;

interface ConditionInterface
{
    /**
     * @param EnvironmentInterface $environment
     *
     * @return bool
     */
    public function check(EnvironmentInterface $environment);

    /**
     * @return string
     */
    public function toJSON();

    /**
     * @param string $string
     *
     * @return ConditionInterface
     */
    public function fromJSON($string);
}

class_alias(ConditionInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition');
