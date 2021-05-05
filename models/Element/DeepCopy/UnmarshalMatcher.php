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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element\DeepCopy;

use DeepCopy\TypeMatcher\TypeMatcher;

/**
 * @internal
 */
class UnmarshalMatcher extends TypeMatcher
{
    /**
     * UnmarshalMatcher constructor.
     */
    public function __construct()
    {
        parent::__construct(\Pimcore\Model\Element\ElementDescriptor::class);
    }
}
