<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Version;

use DeepCopy\Filter\Filter;
use Pimcore\Model\Element\ElementDumpStateInterface;

/**
 * @internal
 */
final class SetDumpStateFilter implements Filter
{
    protected bool $state;

    public function __construct(bool $state)
    {
        $this->state = $state;
    }

    public function apply($object, $property, $objectCopier): void
    {
        if ($object instanceof ElementDumpStateInterface) {
            $object->setInDumpState($this->state);
        }
    }
}
