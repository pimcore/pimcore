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

namespace Pimcore\Model\DataObject\Exception;

use Exception;

/**
 * Raised when a classification store key's type computes its value from other elements instead of
 * holding one - the reverse relation is the type that does - so there is nothing for the store to
 * write and a stored value would be read back as whatever the computation answers.
 */
class DerivedValueNotStorableException extends Exception
{
    public function __construct(int $groupId, int $keyId, string $type)
    {
        parent::__construct(sprintf(
            'Key %d of group %d has the type "%s", whose value is computed from other elements rather than stored'
            . ' on the object. A classification store cannot hold it: give the key a type that stores its value.',
            $keyId,
            $groupId,
            $type
        ));
    }
}
