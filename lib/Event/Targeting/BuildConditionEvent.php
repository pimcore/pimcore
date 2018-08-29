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

namespace Pimcore\Event\Targeting;

use Pimcore\Targeting\Condition\ConditionInterface;
use Symfony\Component\EventDispatcher\Event;

class BuildConditionEvent extends Event
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $class;

    /**
     * @var array
     */
    private $config;

    /**
     * @var ConditionInterface
     */
    private $condition;

    public function __construct(string $type, string $class, array $config)
    {
        $this->type = $type;
        $this->class = $class;
        $this->config = $config;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function hasCondition(): bool
    {
        return null !== $this->condition;
    }

    /**
     * @return ConditionInterface|null
     */
    public function getCondition()
    {
        return $this->condition;
    }

    public function setCondition(ConditionInterface $condition)
    {
        $this->condition = $condition;

        $this->stopPropagation();
    }
}
