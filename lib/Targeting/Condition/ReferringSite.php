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

namespace Pimcore\Targeting\Condition;

use Pimcore\Targeting\Model\VisitorInfo;

class ReferringSite extends AbstractVariableCondition implements ConditionInterface
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
        return new static($config['referrer'] ?? null);
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
        $request = $visitorInfo->getRequest();
        $referrer = $request->headers->get('Referer', 'direct');

        $result = preg_match($this->pattern, $referrer);
        if ($result) {
            $this->setMatchedVariable('referrer', $referrer);

            return true;
        }

        return false;
    }
}
