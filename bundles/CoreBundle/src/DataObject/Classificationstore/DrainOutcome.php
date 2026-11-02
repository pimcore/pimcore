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
 * What the drain did with one (objectId, fieldname) pair.
 *
 * AlreadyMigrated is the pair a user save converted while the drain was running: its document is
 * what the pair is read from, so the drain writes nothing and only removes the EAV rows that
 * survived. Diverged is the pair whose written document did not compare equal to the EAV side - its
 * document is rolled back and its EAV rows are left in place.
 *
 * @internal
 */
enum DrainOutcome
{
    case Converted;
    case AlreadyMigrated;
    case Diverged;
}
