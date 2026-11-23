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

class UnknownClassificationStoreEngineException extends Exception
{
    public function __construct(int $storeId, string $engine)
    {
        parent::__construct(sprintf(
            'Classification store %d is configured for the storage engine "%s", which is not registered.'
            . ' Set classificationstore_stores.engine of store %d back to NULL to use the default engine,'
            . ' or install the bundle that provides "%s".',
            $storeId,
            $engine,
            $storeId,
            $engine
        ));
    }
}
