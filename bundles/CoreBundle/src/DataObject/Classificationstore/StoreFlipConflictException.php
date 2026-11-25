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

namespace Pimcore\Bundle\CoreBundle\DataObject\Classificationstore;

use Exception;

/**
 * Raised when the stores a flip was asked for cannot go over on their own: a classification store
 * field name they carry is also defined on a store that would be left on the EAV engine.
 *
 * The value tables key their rows by (object, fieldname), so the definitions sharing a field name
 * read and write the same rows and cannot be split across two engines - every load, save and drain
 * of such a group is refused with
 * {@see \Pimcore\Model\DataObject\Exception\ConflictingClassificationStoreEnginesException}. The
 * flip therefore refuses before it writes anything, and names the stores that have to come along.
 *
 * @internal
 */
final class StoreFlipConflictException extends Exception
{
    /**
     * @param string[] $conflicts one line per field-name group that would be left split
     */
    public function __construct(array $conflicts)
    {
        parent::__construct(sprintf(
            'These Classification Store fields would be left split across two storage engines: %s.'
            . ' The value tables key their rows by field name, so the stores sharing one have to go'
            . ' over together: name them all in the same flip, or point the fields at one store.',
            implode('; ', $conflicts)
        ));
    }
}
