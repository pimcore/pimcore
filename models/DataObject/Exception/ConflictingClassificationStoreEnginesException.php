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
 * Raised when the classification store fields sharing one field name sit on stores with different
 * engines. The value tables key their rows by (object, fieldname), so those definitions read and
 * write the same rows and cannot be split across two backends.
 */
class ConflictingClassificationStoreEnginesException extends Exception
{
    /**
     * @param array<string, string> $enginesByIdentity container-qualified field identity => engine name
     */
    public function __construct(array $enginesByIdentity)
    {
        $parts = [];
        foreach ($enginesByIdentity as $identity => $engine) {
            $parts[] = sprintf('%s uses "%s"', $identity, $engine);
        }

        parent::__construct(sprintf(
            'The classification store fields sharing this name are configured for different storage engines (%s).'
            . ' The value tables key their rows by field name, so these definitions have to agree on one engine:'
            . ' point them at stores using the same engine, or rename one of the fields.',
            implode(', ', $parts)
        ));
    }
}
