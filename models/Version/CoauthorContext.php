<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Version;

use Symfony\Contracts\Service\ResetInterface;

final class CoauthorContext implements CoauthorContextInterface, ResetInterface
{
    private ?string $type = null;

    private ?string $coauthor = null;

    private bool $enabled = true;

    public function set(string $type, string $coauthor): void
    {
        $this->type = $type;
        $this->coauthor = $coauthor;
    }

public function withCoauthor(string $type, string $coauthor, callable $callback): mixed
{
    $previousType = $this->type;
    $previousCoauthor = $this->coauthor;
    $previousEnabled = $this->enabled;

    $this->enabled = true;
    $this->set($type, $coauthor);

    try {
        return $callback();
    } finally {
        $this->type = $previousType;
        $this->coauthor = $previousCoauthor;
        $this->enabled = $previousEnabled;
    }
}

    public function clear(): void
    {
        $this->type = null;
        $this->coauthor = null;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getCoauthor(): ?string
    {
        return $this->coauthor;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isActive(): bool
    {
        return $this->enabled && $this->type !== null && $this->coauthor !== null;
    }

    public function reset(): void
    {
        $this->clear();
        $this->enabled = true;
    }
}
