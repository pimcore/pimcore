<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
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
