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

namespace Pimcore\Twig\Options;

/**
 * @internal
 */
final class BlockOptions
{
    private ?int $limit = null;

    private bool $reload = false;

    private int $default = 0;

    private bool $manual = false;

    private ?string $class = null;

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    public function setReload(bool $reload): void
    {
        $this->reload = $reload;
    }

    public function setDefault(int $default): void
    {
        $this->default = $default;
    }

    public function setManual(bool $manual): void
    {
        $this->manual = $manual;
    }

    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    private function getDefault(): int
    {
        return $this->default;
    }

    private function getClass(): ?string
    {
        return $this->class;
    }

    private function getLimit(): ?int
    {
        return $this->limit;
    }

    private function getReloadAsString(): string
    {
        if ($this->reload) {
            return 'true';
        }

        return 'false';
    }

    private function getManualAsString(): string
    {
        if ($this->manual) {
            return 'true';
        }

        return 'false';
    }

    public function toString(): string
    {
        $options = '[';
        $options .= "'manual' => " . $this->getManualAsString() .',';

        if ($this->getLimit()) {
            $options .= "'limit' => " . $this->getLimit() .',';
        }

        $options .= "'reload' => " . $this->getReloadAsString() .',';
        $options .= "'default' => " . $this->getDefault() .',';

        if ($this->getClass()) {
            $options .= "'class' => \"". $this->getClass() . '",';
        }

        $options .= ']';

        return $options;
    }
}
