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

class SearchEngine implements ConditionInterface
{
    /**
     * @var string|null
     */
    private $engine;

    /**
     * @var array
     */
    private $validEngines = ['google', 'bing', 'yahoo'];

    /**
     * @param null|string $engine
     */
    public function __construct(string $engine = null)
    {
        if (!empty($engine)) {
            if (!in_array($engine, $this->validEngines)) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid engine: "%s"',
                    $engine
                ));
            }
        }

        $this->engine = $engine;
    }

    /**
     * @inheritDoc
     */
    public static function fromConfig(array $config)
    {
        return new static($config['searchengine'] ?? null);
    }

    /**
     * @inheritDoc
     */
    public function canMatch(): bool
    {
        // can always match as empty means all engines
        // which still restricts to checking engines
        return true;
    }

    /**
     * @inheritDoc
     */
    public function match(VisitorInfo $visitorInfo): bool
    {
        $request  = $visitorInfo->getRequest();
        $referrer = $request->headers->get('Referrer');

        if (empty($referrer)) {
            return false;
        }

        if (!empty($this->engine)) {
            $pattern = '/' . $this->engine . '/i';
        } else {
            $pattern = '/' . implode('|', $this->validEngines) . '/i';
        }

        if (preg_match($pattern, $referrer)) {
            return true;
        }

        return false;
    }
}
