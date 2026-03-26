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
