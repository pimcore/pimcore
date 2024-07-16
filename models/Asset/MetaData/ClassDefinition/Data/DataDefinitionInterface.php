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

namespace Pimcore\Model\Asset\MetaData\ClassDefinition\Data;

use Exception;

interface DataDefinitionInterface
{
    public function isEmpty(mixed $data, array $params = []): bool;

    /**
     *
     * @throws Exception
     */
    public function checkValidity(mixed $data, array $params = []): void;

    public function getDataForListfolderGrid(mixed $data, array $params = []): mixed;

    public function getDataFromEditMode(mixed $data, array $params = []): mixed;

    public function getDataFromListfolderGrid(mixed $data, array $params = []): mixed;

    public function resolveDependencies(mixed $data, array $params = []): array;
}
