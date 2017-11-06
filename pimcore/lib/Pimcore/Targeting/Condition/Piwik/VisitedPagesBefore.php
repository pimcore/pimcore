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

namespace Pimcore\Targeting\Condition\Piwik;

use Pimcore\Targeting\Condition\DataProviderDependentConditionInterface;
use Pimcore\Targeting\DataProvider\Piwik;
use Pimcore\Targeting\Model\VisitorInfo;

class VisitedPagesBefore implements DataProviderDependentConditionInterface
{
    /**
     * @var int
     */
    private $count;

    public function __construct(int $count)
    {
        $this->count = $count;
    }

    /**
     * @inheritDoc
     */
    public static function fromConfig(array $config)
    {
        return new static($config['number'] ?? 0);
    }

    /**
     * @inheritDoc
     */
    public function getDataProviderKeys(): array
    {
        return [Piwik::PROVIDER_KEY];
    }

    /**
     * @inheritDoc
     */
    public function canMatch(): bool
    {
        return $this->count > 0;
    }

    /**
     * @inheritDoc
     */
    public function match(VisitorInfo $visitorInfo): bool
    {
        $visitData = $visitorInfo->get(Piwik::PROVIDER_KEY);

        if (!$visitData || !is_array($visitData) || empty($visitData)) {
            return false;
        }

        // TODO is this the right metric or do we need to take current session
        // into account? scope?
        $totalPageViews = (int)($visitData['totalPageViews'] ?? 0);

        return $totalPageViews >= $this->count;
    }
}
