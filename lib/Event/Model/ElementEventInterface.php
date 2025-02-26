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
