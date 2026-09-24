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
 * Thrown when the results of processing the data of an asset can't be saved, as the data or its processing was
 * changed by others in the meantime (see \Pimcore\Model\Asset::saveProcessingResults())
 *
 * @internal
 */
class DataStateChangedException extends Exception implements SaveAbortedExceptionInterface
{
}
