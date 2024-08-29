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

namespace Pimcore\Event\Model;

use Pimcore\Model\WebsiteSetting;
use Symfony\Contracts\EventDispatcher\Event;

final class WebsiteSettingEvent extends Event
{
    private WebsiteSetting $websiteSetting;

    public function __construct(WebsiteSetting $websiteSetting)
    {
        $this->websiteSetting = $websiteSetting;
    }

    public function getWebsiteSetting(): WebsiteSetting
    {
        return $this->websiteSetting;
    }
}
