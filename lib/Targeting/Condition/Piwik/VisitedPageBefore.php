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

use Pimcore\Targeting\Condition\ConditionInterface;
use Pimcore\Targeting\DataProvider\Piwik;
use Pimcore\Targeting\DataProviderDependentInterface;
use Pimcore\Targeting\Model\VisitorInfo;

class VisitedPageBefore implements ConditionInterface, DataProviderDependentInterface
{
    /**
     * @var string|null
     */
    private $pattern;

    /**
     * @param null|string $pattern
     */
    public function __construct(string $pattern = null)
    {
        $this->pattern = $pattern;
    }

    /**
     * @inheritDoc
     */
    public static function fromConfig(array $config)
    {
        return new static($config['url'] ?? null);
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
        return !empty($this->pattern);
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

        if (0 === count($visitData['visitedPages'] ?? [])) {
            return false;
        }

        foreach ($visitData['visitedPages'] as $pageVisit) {
            if (preg_match($this->pattern, $pageVisit['url'])) {
                return true;
            }
        }

        return false;
    }
}
