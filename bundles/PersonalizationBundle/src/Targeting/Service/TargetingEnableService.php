<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\Service;

use Pimcore\Http\RequestHelper;

class TargetingEnableService
{
    private RequestHelper $requestHelper;

    private bool $enabled;

    public function __construct(RequestHelper $requestHelper, bool $enabled)
    {
        $this->enabled = $enabled;
        $this->requestHelper = $requestHelper;
    }

    public function isTargetingEnabled(): bool
    {
        $request = $this->requestHelper->getCurrentRequest();

        if ($this->enabled || $request->cookies->getBoolean('pimcore_targeting_enabled')) {
            return true;
        }

        return false;
    }
}
