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

/**
 * Stateful coauthor context for versions. When active, every version newly
 * created while the context is set is stamped with the given coauthor
 * information, unless the coauthor was already set explicitly on the version.
 */
interface CoauthorContextInterface
{
    public function set(string $type, string $coauthor): void;

    /**
     * Runs $callback with the given coauthor active and restores the previous
     * context values afterwards - the one-shot convenience for a single save:
     * $context->withCoauthor('automation', 'my-importer', fn () => $object->save());
     *
     * @template T
     *
     * @param-immediately-invoked-callable $callback
     *
     * @param (callable(): T) $callback
     *
     * @return T
     */
    public function withCoauthor(string $type, string $coauthor, callable $callback): mixed;

    public function clear(): void;

    public function getType(): ?string;

    public function getCoauthor(): ?string;

    public function enable(): void;

    public function disable(): void;

    public function isEnabled(): bool;

    public function isActive(): bool;
}
