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

namespace Pimcore\Document\Editable;

use Exception;
use JsonException;
use Pimcore\Model\Document\Editable;

/**
 * @internal
 */
final class EditmodeEditableDefinitionCollector
{
    private bool $stopped = false;

    private array $editableDefinitions = [];

    private array $stash = [];

    /**
     *
     * @throws Exception
     */
    public function add(Editable $editable): void
    {
        if ($this->stopped) {
            return;
        }

        $this->editableDefinitions[$editable->getName()] = $editable->getEditmodeDefinition();
    }

    public function remove(Editable $editable): void
    {
        if ($this->stopped) {
            return;
        }

        if (isset($this->editableDefinitions[$editable->getName()])) {
            unset($this->editableDefinitions[$editable->getName()]);
        }
    }

    public function start(): void
    {
        $this->stopped = false;
    }

    public function stop(): void
    {
        $this->stopped = true;
    }

    public function stashPush(): void
    {
        array_push($this->stash, $this->editableDefinitions);
        $this->editableDefinitions = [];
    }

    public function stashPull(): void
    {
        $this->editableDefinitions = array_pop($this->stash);
    }

    private function clearConfig(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as &$item) {
                $item = $this->clearConfig($item);
            }
        } elseif (!is_scalar($value)) {
            $value = null;
        }

        return $value;
    }

    public function getDefinitions(): array
    {
        $configs = [];
        foreach ($this->editableDefinitions as $definition) {
            $configs[] = $this->clearConfig($definition);
        }

        return $configs;
    }

    private function getJson(): string
    {
        return json_encode($this->getDefinitions(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     *
     * @throws JsonException
     */
    public function getHtml(): string
    {
        $code = '
            <script>
                var editableDefinitions = ' . $this->getJson() . ';
            </script>
        ';

        return $code;
    }
}
