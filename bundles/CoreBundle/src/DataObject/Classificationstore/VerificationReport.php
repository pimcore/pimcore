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
 * What the verification of one class found.
 *
 * `failures` are the checks that did not hold - a document row whose recomputed checksum differs
 * from its stamp, or a pair that still holds EAV rows. `unproven` are the document rows that carry
 * neither a checksum nor a `migratedAt`: they were written before any writer stamped a checksum, so
 * there is nothing to compare against and they are reported rather than called corrupt.
 *
 * @internal
 */
final readonly class VerificationReport
{
    /**
     * @param string[] $failures
     * @param string[] $unproven
     */
    public function __construct(public array $failures = [], public array $unproven = [])
    {
    }

    public function hasFailed(): bool
    {
        return $this->failures !== [];
    }
}
