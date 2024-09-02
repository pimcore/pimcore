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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

interface VarExporterInterface
{
    /**
     * @return string[]
     */
    public function getBlockedVarsForExport(): array;

    /**
     * Resolves blocked vars to cleanup on export
     *
     * @return string[]
     */
    public function resolveBlockedVars(): array;

    /**
     * @param string[] $vars
     *
     * @return $this
     */
    public function setBlockedVarsForExport(array $vars): static;
}
