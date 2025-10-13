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

namespace Pimcore\Model\DataObject\ClassDefinition\Helper;

/**
 * @internal
 */
interface VarExportInterface
{
    /**
     * @return string[]
     */
    public function getBlockedVarsForExport(): array;

    /**
     * @param string[] $vars
     *
     * @return $this
     */
    public function setBlockedVarsForExport(array $vars): static;

    /**
     * @return string[]
     */
    public function resolveBlockedVars(): array;

    /**
     * @param array<string, mixed> $data
     */
    public static function __set_state(array $data): static;
}
