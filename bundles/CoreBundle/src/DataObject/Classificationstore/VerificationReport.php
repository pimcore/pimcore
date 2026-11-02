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
 * How much the verification of one class found, by kind - {@see VerificationFinding} says what each
 * kind is.
 *
 * Counts rather than the messages: a store that was never checksummed has one finding per stored
 * group, so a report that carried them all would grow with the data it is a report about. Each one
 * is handed to the caller of {@see DocumentEngineMigrationService::verify()} as it is found, and
 * what the cleanup needs to refuse is whether anything failed.
 *
 * @internal
 */
final readonly class VerificationReport
{
    public function __construct(public int $failures = 0, public int $unproven = 0)
    {
    }

    public function hasFailed(): bool
    {
        return $this->failures > 0;
    }
}
