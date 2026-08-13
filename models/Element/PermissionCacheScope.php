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

namespace Pimcore\Model\Element;

/**
 * Distinguishes the two element permission DAO code paths that must not share a cache entry.
 *
 * The single-permission path ({@see AbstractElement::isAllowed()}, DAO isAllowed()) and the batch
 * path ({@see AbstractElement::getUserPermissions()}, DAO areAllowed()/permissionByTypes()) are not
 * interchangeable: for the "list" type the batch path excludes an allowed child that also carries a
 * direct-user deny, while the single path accepts any allowed child. Caching them under the same key
 * would let whichever runs first return the wrong result for the other.
 *
 * @internal
 */
enum PermissionCacheScope: string
{
    case Single = 'single';
    case Batch = 'batch';
}
