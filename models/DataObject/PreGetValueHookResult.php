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

namespace Pimcore\Model\DataObject;

/**
 * Sentinel values which can be returned by PreGetValueHookInterface::preGetValue()
 *
 * Returning null from preGetValue() means "no override, use the stored value".
 * Return PreGetValueHookResult::ReturnNull to actively make the getter return null.
 */
enum PreGetValueHookResult
{
    case ReturnNull;
}
