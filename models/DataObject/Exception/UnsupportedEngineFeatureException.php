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
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature;

/**
 * Raised when a value is written to a store whose engine does not offer the capability that value
 * needs - never a silent skip and never a fallback to a value the storage does happen to hold.
 */
class UnsupportedEngineFeatureException extends Exception
{
    public function __construct(EngineFeature $feature, int $groupId, int $keyId, string $type)
    {
        parent::__construct(sprintf(
            'The storage engine of this classification store does not support %s, which key %d of group %d needs:'
            . ' its type is "%s". Flip the store to an engine that supports %s, or give the key a type the engine'
            . ' can store.',
            $feature->name,
            $keyId,
            $groupId,
            $type,
            $feature->name
        ));
    }
}
