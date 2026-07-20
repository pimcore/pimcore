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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums;

/**
 * @internal
 */
enum ErrorHandlingMode: string
{
    case CONTINUE_ON_ERROR = 'continue_on_error';
    case STOP_ON_FIRST_ERROR = 'stop_on_first_error';
}
