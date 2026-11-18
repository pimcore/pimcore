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

namespace Pimcore\Contracts\ChangeControl\Exception;

use RuntimeException;

/**
 * There is no such branch, or it belongs to another context: one exception for both, so that a
 * context cannot probe for another's branches.
 */
class BranchNotFoundException extends RuntimeException implements ChangeControlExceptionInterface
{
}
