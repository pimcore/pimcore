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

namespace Pimcore\HttpKernel\BundleLocator;

trigger_deprecation(
    'pimcore/pimcore',
    '12.3',
    'The "%s" class is deprecated and will be removed in Pimcore 13.',
);

/**
 * @deprecated
 */
class InvalidArgumentException extends \InvalidArgumentException
{
}
