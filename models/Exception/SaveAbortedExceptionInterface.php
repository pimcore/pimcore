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

namespace Pimcore\Model\Exception;

/**
 * Marks an exception which aborts saving an element on purpose (e.g. because a precondition of the save isn't met),
 * which is an expected outcome and therefore neither logged as critical nor dispatched as a failure event
 *
 * @internal
 */
interface SaveAbortedExceptionInterface
{
}
