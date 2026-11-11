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

namespace Pimcore\Model\User\Exception;

use DomainException;

/**
 * Thrown when a service user would break one of its invariants: it never has a password, is never an
 * administrator, has no two-factor authentication and never changes its type.
 */
final class InvalidServiceUserException extends DomainException
{
}
