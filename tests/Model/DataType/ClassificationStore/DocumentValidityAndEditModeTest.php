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

/**
 * ValidityAndEditModeTest against a store on the document engine: validation and the edit mode data
 * resolve in PHP at read time, so they have to come out the same whichever engine stored them.
 *
 * @group model.datatype.classificationstore
 */
class DocumentValidityAndEditModeTest extends ValidityAndEditModeTest
{
    protected function getStoreEngine(): ?string
    {
        return 'document';
    }

    /**
     * A null entry would shadow the collection a parent maps, because the mappings are merged as an
     * array union, so the document engine maps only the groups that have a collection.
     */
    protected function expectedMappingOfAGroupWithoutCollection(int $groupId): array
    {
        return [];
    }
}
