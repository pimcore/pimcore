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

namespace Pimcore\Model\Version;

use DeepCopy\Filter\Filter;
use Pimcore\Model\Element\ElementDumpStateInterface;

/**
 * @internal
 */
final class SetDumpStateFilter implements Filter
{
    protected $state;

    public function __construct(bool $state)
    {
        $this->state = $state;
    }

    /**
     * {@inheritdoc}
     */
    public function apply($object, $property, $objectCopier)
    {
        if ($object instanceof ElementDumpStateInterface) {
            $object->setInDumpState($this->state);
        }
    }
}
