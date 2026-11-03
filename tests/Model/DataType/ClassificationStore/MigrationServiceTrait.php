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

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Pimcore;
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\DocumentEngineMigrationService;
use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore\Engine\DocumentEngine;
use Pimcore\Model\DataObject\Classificationstore\Engine\EavEngine;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;

/**
 * The migration service as the four commands get it. Its DBAL connection is not autowirable, so the
 * two engines come from the resolver the container holds rather than from new instances - the drain
 * has to write through the very engine the model reads from.
 */
trait MigrationServiceTrait
{
    protected function getMigrationService(): DocumentEngineMigrationService
    {
        $resolver = Pimcore::getContainer()->get(EngineResolver::class);
        $engines = $resolver->getEngines();

        $documentEngine = $engines['document'] ?? null;
        $eavEngine = $engines['eav'] ?? null;
        $this->assertInstanceOf(DocumentEngine::class, $documentEngine);
        $this->assertInstanceOf(EavEngine::class, $eavEngine);

        return new DocumentEngineMigrationService($resolver, $documentEngine, $eavEngine, Db::get());
    }
}
