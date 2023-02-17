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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\DataProvider;

use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Service\VisitedPagesCounter as VisitedPagesCounterService;

class VisitedPagesCounter implements DataProviderInterface
{
    const PROVIDER_KEY = 'visited_pages_counter';

    private VisitedPagesCounterService $service;

    public function __construct(VisitedPagesCounterService $service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function load(VisitorInfo $visitorInfo): void
    {
        $visitorInfo->set(self::PROVIDER_KEY, $this->service);
    }
}
