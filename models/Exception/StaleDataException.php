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

use Exception;

/**
 * Thrown when an element is saved based on data that was changed by another process since it was loaded.
 * Reload the element, apply the changes again and save it.
 */
final class StaleDataException extends Exception
{
}
