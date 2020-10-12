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

namespace Pimcore\Templating\Helper;

use Pimcore\Config;
use Pimcore\Http\RequestHelper;
use Symfony\Component\Templating\Helper\Helper;

/**
 * @deprecated
 */
class WebsiteConfig extends Helper
{
    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @param RequestHelper $requestHelper
     */
    public function __construct(RequestHelper $requestHelper)
    {
        $this->requestHelper = $requestHelper;
    }

    public function getName()
    {
        return 'websiteConfig';
    }

    public function __invoke($key = null, $default = null)
    {
        $locale = $this->requestHelper->getCurrentRequest()->getLocale();

        return Config::getWebsiteConfigValue($key, $default, $locale);
    }
}
