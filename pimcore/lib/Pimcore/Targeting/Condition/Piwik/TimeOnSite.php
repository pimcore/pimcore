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

class TimeOnSite implements DataProviderDependentConditionInterface
{
    /**
     * @var int
     */
    private $seconds;

    public function __construct(int $seconds)
    {
        if ($seconds < 0) {
            throw new \InvalidArgumentException('Seconds needs to be a positive integer');
        }

        $this->seconds = $seconds;
    }

    /**
     * @inheritDoc
     */
    public static function fromConfig(array $config)
    {
        $seconds = $config['seconds'] ?? 0;
        $seconds += ($config['minutes'] ?? 0) * 60;
        $seconds += ($config['hours'] ?? 0) * 60 * 60;

        return new static($seconds);
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
        return $this->seconds > 0;
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

        if (0 === count($visitData['lastVisits'] ?? [])) {
            return false;
        }

        $lastVisit = $visitData['lastVisits'][0];
        $duration  = (int)($lastVisit['visitDuration'] ?? 0);

        return $duration >= $this->seconds;
    }
}
