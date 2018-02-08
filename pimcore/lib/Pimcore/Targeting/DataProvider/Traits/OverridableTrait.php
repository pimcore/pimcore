<?php

declare(strict_types=1);

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

namespace Pimcore\Targeting\DataProvider\Traits;

trait OverridableTrait
{
    /**
     * @var array
     */
    private $overrides = [];

    private function extractOverriddenProperties(array $overrides, array $properties)
    {
        foreach ($properties as $property) {
            if (isset($overrides[$property]) && !empty($overrides[$property])) {
                $this->overrides[$property] = $overrides[$property];
            }
        }
    }
}
