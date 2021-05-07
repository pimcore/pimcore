<?php

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

namespace Pimcore\Model\DataObject\Traits;

@trigger_error(
    'DirtyIndicatorTrait is deprecated since version 6.6.0 and will be removed in Pimcore 10. ' .
    'Use `' . \Pimcore\Model\Element\Traits\DirtyIndicatorTrait::class . '` instead.',
    E_USER_DEPRECATED
);

trait_exists(\Pimcore\Model\Element\Traits\DirtyIndicatorTrait::class);

if (false) {
    /**
     * @deprecated use \Pimcore\Model\Element\Traits\DirtyIndicatorTrait instead
     */
    trait DirtyIndicatorTrait
    {
        use \Pimcore\Model\Element\Traits\DirtyIndicatorTrait;
    }
}
