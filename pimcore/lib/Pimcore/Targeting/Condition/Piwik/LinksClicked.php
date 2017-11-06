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

class LinksClicked implements DataProviderDependentConditionInterface
{
    /**
     * @var null|int
     */
    private $number;

    public function __construct(int $number = null)
    {
        $this->number = $number;
    }

    /**
     * @inheritDoc
     */
    public static function fromConfig(array $config)
    {
        return new static($config['number'] ?? null);
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
        return null !== $this->number && $this->number > 0;
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

        // TODO totalActions also includes reloads and direct views
        // find a way to filter links!
        return $visitData['totalActions'] >= $this->number;
    }
}
