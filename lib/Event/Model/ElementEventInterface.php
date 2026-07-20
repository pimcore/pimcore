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

namespace Pimcore\Event\Model;

use InvalidArgumentException;
use Pimcore\Model\Element\ElementInterface;

interface ElementEventInterface
{
    public function getElement(): ElementInterface;

    /**
     * Get argument by key.
     *
     * @param string $key Key
     *
     * @return mixed Contents of array key
     *
     * @throws InvalidArgumentException If key is not found.
     */
    public function getArgument(string $key): mixed;

    /**
     * Add argument to event.
     *
     * @param string $key   Argument name
     * @param mixed  $value Value
     *
     * @return $this
     */
    public function setArgument(string $key, mixed $value): static;

    /**
     * Getter for all arguments.
     *
     */
    public function getArguments(): array;

    /**
     * Set args property.
     *
     * @param array $args Arguments
     *
     * @return $this
     */
    public function setArguments(array $args = []): static;

    /**
     * Has argument.
     *
     * @param string $key Key of arguments array
     *
     */
    public function hasArgument(string $key): bool;
}
