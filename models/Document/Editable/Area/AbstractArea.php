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

namespace Pimcore\Model\Document\Editable\Area;

abstract class AbstractArea
{
    /**
     * @internal
     *
     */
    protected array $config;

    /**
     * @internal
     *
     */
    protected Info $brick;

    /**
     * @internal
     *
     */
    protected array $params = [];

    public function setConfig(array $config): static
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getParam(string $key): mixed
    {
        if (array_key_exists($key, $this->params)) {
            return $this->params[$key];
        }

        return null;
    }

    public function getAllParams(): array
    {
        return $this->params;
    }

    public function addParam(string $key, mixed $value): void
    {
        $this->params[$key] = $value;
    }

    public function setParams(array $params): static
    {
        $this->params = $params;

        return $this;
    }

    public function setBrick(Info $brick): static
    {
        $this->brick = $brick;

        return $this;
    }

    public function getBrick(): Info
    {
        return $this->brick;
    }
}
