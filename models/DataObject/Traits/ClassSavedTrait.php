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

namespace Pimcore\Model\DataObject\Traits;

use Pimcore\Model\DataObject\ClassDefinition;

/**
 * @internal
 */
trait ClassSavedTrait
{
    public function preSave(mixed $containerDefinition, array $params = []): void
    {
        // nothing to do
    }

    public function postSave(mixed $containerDefinition, array $params = []): void
    {
        if ($containerDefinition instanceof ClassDefinition) {
            $this->classSaved($containerDefinition, $params);
        }
    }
}
