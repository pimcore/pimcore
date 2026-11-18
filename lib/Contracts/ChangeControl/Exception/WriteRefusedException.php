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
 * This user may not write to the branch or merge it. The rights on single objects are no refusal of
 * the call: the merge's check names them.
 */
class WriteRefusedException extends RuntimeException implements ChangeControlExceptionInterface
{
}
