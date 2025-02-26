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

namespace Pimcore\Event\Model\Asset;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class ResolveUploadTargetEvent extends Event
{
    use ArgumentsAwareTrait;

    protected string $filename;

    /**
     * @deprecated Will be removed in Pimcore 12
     */
    protected array $context = [];

    protected int $parentId;

    /**
     * ResolveUploadTargetEvent constructor.
     *
     * @param array|null $context contextual information
     */
    public function __construct(int $parentId, string $filename, ?array $context = null)
    {
        $this->parentId = $parentId;
        $this->filename = $filename;
        if ($context !== null) {
            trigger_deprecation(
                'pimcore/pimcore',
                '11.5.0',
                'The context property is deprecated and will be removed in 12.0.0.
            Use setArgument() from the ArgumentsAwareTrait instead.'
            );

            $this->context = $context;
        }
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * @deprecated Will be removed in Pimcore 12
     */
    public function getContext(): array
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '11.5.0',
            'The context property is deprecated and will be removed in 12.0.0.
            Use getArgument() from the ArgumentsAwareTrait instead.'
        );

        return $this->context;
    }

    /**
     * @deprecated Will be removed in Pimcore 12
     */
    public function setContext(array $context): void
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '11.5.0',
            'The context property is deprecated and will be removed in 12.0.0.
            Use setArgument() from the ArgumentsAwareTrait instead.'
        );

        $this->context = $context;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function setParentId(int $parentId): void
    {
        $this->parentId = $parentId;
    }

    /**
     * Will be removed in Pimcore 12
     *
     * Override setArgument to handle the deprecated context property.
     */
    public function setArgument(string $key, mixed $value): static
    {
        if ($key === 'context' && is_array($value)) {
            $this->context = $value;
        }

        $this->arguments[$key] = $value;

        return $this;
    }
}
