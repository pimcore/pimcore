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

    protected array $context;

    protected int $parentId;

    /**
     * ResolveUploadTargetEvent constructor.
     *
     * @param array $context contextual information
     */
    public function __construct(int $parentId, string $filename, array $context)
    {
        $this->parentId = $parentId;
        $this->filename = $filename;
        $this->context = $context;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): void
    {
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
}
