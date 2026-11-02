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
 * What kind of thing the verification found about one row.
 *
 * Failure is a check that did not hold - a document row whose recomputed checksum differs from its
 * stamp, or a pair that still holds EAV rows - and is what makes the cleanup refuse to drop the
 * tables of that class.
 *
 * Unproven is a document row carrying neither a checksum nor a `migratedAt`: it was written before
 * any writer stamped a checksum, so there is nothing to compare it against and it is reported
 * rather than called corrupt.
 *
 * @internal
 */
enum VerificationFinding
{
    case Failure;
    case Unproven;
}
