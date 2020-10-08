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
 * @category   Pimcore
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element\DeepCopy;

use DeepCopy\TypeMatcher\TypeMatcher;

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

//TODO: remove in Pimcore 7
class_alias(UnmarshalMatcher::class, 'Pimcore\Model\Version\UnmarshalMatcher');
