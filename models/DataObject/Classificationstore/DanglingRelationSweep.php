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

namespace Pimcore\Model\DataObject\Classificationstore;

/**
 * What one sweep for stored descriptors whose target is gone found, and what it left in place.
 *
 * The two lists are what keeps the report from claiming more than the run did: a document that was
 * saved between the read and the write is not rewritten, and its descriptors are still stored
 * afterwards. They are reported as found like any other and named as left alone, so that a run
 * which removed nothing cannot read as a run that found nothing.
 *
 * @internal
 */
final class DanglingRelationSweep
{
    /**
     * @param list<DanglingRelation> $findings every stored descriptor whose target is gone
     * @param list<DanglingRelation> $skipped the ones whose document was left as it was
     */
    public function __construct(
        private readonly array $findings,
        private readonly array $skipped
    ) {
    }

    /**
     * @return list<DanglingRelation>
     */
    public function getFindings(): array
    {
        return $this->findings;
    }

    /**
     * @return list<DanglingRelation>
     */
    public function getSkipped(): array
    {
        return $this->skipped;
    }

    /**
     * The documents of the skipped descriptors, one description each rather than one per descriptor.
     *
     * @return list<string>
     */
    public function describeSkippedDocuments(): array
    {
        $documents = [];

        foreach ($this->skipped as $relation) {
            $documents[sprintf(
                'object %d, field "%s", group %d',
                $relation->getObjectId(),
                $relation->getFieldname(),
                $relation->getGroupId()
            )] = true;
        }

        return array_keys($documents);
    }
}
