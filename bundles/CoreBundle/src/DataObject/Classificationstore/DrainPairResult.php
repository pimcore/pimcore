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

/**
 * The outcome of draining one (objectId, fieldname) pair, with the reason when it diverged.
 *
 * @internal
 */
final readonly class DrainPairResult
{
    public function __construct(
        public DrainOutcome $outcome,
        public int $objectId,
        public string $fieldname,
        public ?string $divergence = null
    ) {
    }

    /**
     * The pair and why it diverged, as the drain reports it.
     */
    public function describe(): string
    {
        return sprintf(
            'object %d, field "%s": %s',
            $this->objectId,
            $this->fieldname,
            $this->divergence ?? 'the values do not compare equal'
        );
    }
}
