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

namespace Pimcore\Targeting\DataProvider;

use DeviceDetector\Cache\PSR6Bridge;
use DeviceDetector\DeviceDetector;
use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Targeting\Model\VisitorInfo;

class Device implements DataProviderInterface
{
    const PROVIDER_KEY = 'device';

    /**
     * @var PimcoreCacheItemPoolInterface
     */
    private $cache;

    public function __construct(PimcoreCacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function load(VisitorInfo $visitorInfo)
    {
        if ($visitorInfo->has(self::PROVIDER_KEY)) {
            return;
        }

        $dd = $this->createDeviceDetector($visitorInfo->getRequest()->headers->get('User-Agent'));
        $dd->parse();

        $visitorInfo->set(
            self::PROVIDER_KEY,
            $dd
        );
    }

    protected function createDeviceDetector(string $userAgent): DeviceDetector
    {
        $dd = new DeviceDetector($userAgent);
        $dd->setCache(new PSR6Bridge($this->cache));

        return $dd;
    }
}
