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
 * Raised when a classification store key is of a type the engine behind its store cannot hold.
 *
 * A capability an engine does not offer is answered with `false` everywhere else. This is the one
 * place that throws instead: the alternative is a value the storage silently drops, and a key
 * definition is written once while the value it loses is written on every save.
 */
class UnsupportedEngineFeatureException extends Exception
{
    public function __construct(string $type, int $storeId, string $engine)
    {
        parent::__construct(sprintf(
            'A classification store key of type "%s" cannot be stored by the storage engine "%s" of store %d,'
            . ' which holds a value only as a scalar. Put the key on a store using an engine that holds'
            . ' structured values - set classificationstore_stores.engine of store %d to "document" and move the'
            . ' values over with pimcore:classificationstore:flip/drain/verify/cleanup - or choose a type whose'
            . ' value is a scalar.',
            $type,
            $engine,
            $storeId,
            $storeId
        ));
    }
}
