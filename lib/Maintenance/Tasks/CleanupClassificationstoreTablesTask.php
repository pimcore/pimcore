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

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;

/**
 * @internal
 */
class CleanupClassificationstoreTablesTask implements TaskInterface
{
    private EngineResolver $engineResolver;

    public function __construct(EngineResolver $engineResolver)
    {
        $this->engineResolver = $engineResolver;
    }

    public function execute(): void
    {
        // every engine sweeps the storage it owns
        foreach ($this->engineResolver->getEngines() as $engine) {
            $engine->cleanup();
        }
    }
}
