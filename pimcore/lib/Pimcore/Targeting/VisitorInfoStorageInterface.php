<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Targeting;

use Pimcore\Targeting\Model\VisitorInfo;

/**
 * Similar to the TokenStorage for user objects, this contains the current
 * visitorInfo valid for the current request.
 */
interface VisitorInfoStorageInterface
{
    public function getVisitorInfo(): VisitorInfo;

    public function setVisitorInfo(VisitorInfo $visitorInfo);

    public function hasVisitorInfo(): bool;
}
